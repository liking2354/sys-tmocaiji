<?php

namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use App\Models\Server;
use phpseclib3\Net\SSH2;
use Illuminate\Support\Facades\Log;

/**
 * WebSocket 终端服务器
 * 处理 SSH 连接和终端交互
 */
class TerminalServer implements MessageComponentInterface
{
    protected $clients;
    protected $sshConnections = [];
    protected $sessionData = [];
    protected $shellBuffers = [];
    protected $loop;

    public function __construct($loop = null)
    {
        $this->clients = new \SplObjectStorage();
        $this->loop = $loop;
    }

    /**
     * 当客户端连接时
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        Log::info('WebSocket 客户端连接', ['resource_id' => $conn->resourceId]);
    }

    /**
     * 当收到消息时
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        try {
            $data = json_decode($msg, true);
            
            if (!$data) {
                $this->sendError($from, '无效的消息格式');
                return;
            }

            $action = $data['action'] ?? null;

            switch ($action) {
                case 'connect':
                    $this->handleConnect($from, $data);
                    break;
                case 'input':
                    $this->handleInput($from, $data);
                    break;
                case 'autocomplete':
                    $this->handleAutocomplete($from, $data);
                    break;
                case 'resize':
                    $this->handleResize($from, $data);
                    break;
                case 'disconnect':
                    $this->handleDisconnect($from);
                    break;
                default:
                    $this->sendError($from, '未知的操作: ' . $action);
            }
        } catch (\Exception $e) {
            Log::error('WebSocket 消息处理错误', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->sendError($from, '处理消息时发生错误: ' . $e->getMessage());
        }
    }

    /**
     * 处理连接请求
     */
    private function handleConnect(ConnectionInterface $conn, $data)
    {
        $serverId = $data['server_id'] ?? null;
        $token = $data['token'] ?? null;

        if (!$serverId || !$token) {
            $this->sendError($conn, '缺少必要参数');
            return;
        }

        try {
            $server = Server::find($serverId);
            if (!$server) {
                $this->sendError($conn, '服务器不存在');
                return;
            }

            // 创建 SSH 连接
            $ssh = new SSH2($server->ip, $server->port);
            $ssh->setTimeout(30);

            if (!$ssh->login($server->username, $server->password)) {
                Log::error('SSH 登录失败', [
                    'server_id' => $serverId,
                    'server_ip' => $server->ip,
                    'errors' => $ssh->getErrors()
                ]);
                $this->sendError($conn, 'SSH 登录失败，请检查连接信息');
                return;
            }

            // 获取用户的 home 目录
            $homeDir = trim($ssh->exec('pwd'));
            
            // 保存连接信息
            $resourceId = $conn->resourceId;
            $this->sshConnections[$resourceId] = $ssh;
            $this->sessionData[$resourceId] = [
                'server_id' => $serverId,
                'server_ip' => $server->ip,
                'username' => $server->username,
                'connected_at' => time(),
                'cols' => 120,
                'rows' => 30,
                'shell_mode' => false, // 暂时禁用 shell 模式，回到 exec 模式
                'cwd' => $homeDir, // 当前工作目录
                'home' => $homeDir, // 用户 home 目录
            ];
            
            // 初始化 shell 缓冲区
            $this->shellBuffers[$resourceId] = '';

            // 发送连接成功消息
            $this->send($conn, [
                'type' => 'connected',
                'message' => "已连接到 {$server->username}@{$server->ip}",
                'server_name' => $server->name,
            ]);

            // 发送初始提示符
            $prompt = $server->username . '@' . $server->ip . ':~$ ';
            Log::info('发送初始提示符', ['prompt' => $prompt]);
            $this->send($conn, [
                'type' => 'prompt',
                'data' => $prompt
            ]);

            Log::info('SSH 连接成功', [
                'server_id' => $serverId,
                'resource_id' => $resourceId
            ]);

        } catch (\Exception $e) {
            Log::error('连接处理错误', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->sendError($conn, '连接失败: ' . $e->getMessage());
        }
    }

    /**
     * 处理用户输入
     */
    private function handleInput(ConnectionInterface $conn, $data)
    {
        $resourceId = $conn->resourceId;
        $input = $data['input'] ?? '';

        if (!isset($this->sshConnections[$resourceId])) {
            $this->sendError($conn, '未连接到服务器');
            return;
        }

        try {
            $ssh = $this->sshConnections[$resourceId];
            $sessionData = $this->sessionData[$resourceId];

            // Shell 模式：直接写入输入到 SSH
            if (isset($sessionData['shell_mode']) && $sessionData['shell_mode']) {
                // 在 shell 模式下，直接发送输入（不添加换行，因为前端会发送 \r）
                Log::info('Shell 模式输入', [
                    'input' => $input,
                    'input_hex' => bin2hex($input),
                    'resource_id' => $resourceId
                ]);
                $ssh->write($input);
                
                // 输出会通过 shell reader 异步返回
                return;
            }
            
            // 兼容旧的 exec 模式（如果需要）
            // 处理特殊命令
            if ($input === 'exit' || $input === 'quit') {
                $this->send($conn, [
                    'type' => 'output',
                    'data' => "\r\n正在关闭连接...\r\n"
                ]);
                $this->handleDisconnect($conn);
                return;
            }

            $commandOutput = '';
            $cwd = $sessionData['cwd'] ?? '~';
            
            // 处理 cd 命令
            if (preg_match('/^cd\s*(.*)$/', trim($input), $matches)) {
                $targetDir = trim($matches[1]);
                if (empty($targetDir)) {
                    $targetDir = '~';
                }
                
                // 尝试切换目录并获取新的工作目录
                $cmd = "cd $cwd 2>/dev/null && cd $targetDir 2>/dev/null && pwd || echo 'ERROR'";
                $output = $ssh->exec($cmd);
                $newCwd = trim($output);
                
                if ($newCwd && $newCwd !== 'ERROR') {
                    // 更新当前工作目录
                    $this->sessionData[$resourceId]['cwd'] = $newCwd;
                    $cwd = $newCwd;
                } else {
                    // 目录不存在
                    $commandOutput = "bash: cd: $targetDir: No such file or directory";
                }
            } else {
                // 对于 clear 命令，使用特殊处理
                if (trim($input) === 'clear') {
                    // 使用 printf 来清屏（不依赖 TERM 变量）
                    $cmd = 'printf "\\033[2J\\033[H"; echo "___PROMPT___"';
                } else {
                    // 其他命令：在当前工作目录下执行
                    // 如果是 ls 命令，自动添加 --color=always 参数
                    $processedInput = $input;
                    if (preg_match('/^ls(\s|$)/', $input)) {
                        // 检查是否已经有 --color 参数
                        if (!preg_match('/--color/', $input)) {
                            $processedInput = preg_replace('/^ls/', 'ls --color=always', $input);
                        }
                    }
                    
                    // 自定义 LS_COLORS 配置
                    // di=1;36 - 目录使用亮青色（更柔和）
                    // ln=1;35 - 符号链接使用亮紫色
                    // ex=1;32 - 可执行文件使用亮绿色
                    // *.tar=1;31 - 压缩文件使用亮红色
                    $lsColors = 'di=1;36:ln=1;35:ex=1;32:*.tar=1;31:*.gz=1;31:*.zip=1;31:*.rar=1;31';
                    
                    $cmd = "cd $cwd 2>/dev/null && TERM=xterm LS_COLORS='$lsColors' " . $processedInput . '; echo "___PROMPT___"';
                }
                
                $output = $ssh->exec($cmd);
                
                // 分离输出和提示符标记
                $parts = explode('___PROMPT___', $output);
                $commandOutput = isset($parts[0]) ? $parts[0] : '';
                
                // 只移除最后一个换行符（保留内容中的换行）
                if (substr($commandOutput, -1) === "\n") {
                    $commandOutput = substr($commandOutput, 0, -1);
                }
            }

            // 发送命令输出（即使为空也发送，因为可能文件确实是空的）
            if (!empty($commandOutput)) {
                $this->send($conn, [
                    'type' => 'output',
                    'data' => $commandOutput
                ]);
            }

            // 获取当前目录的显示名称（将 home 目录显示为 ~）
            $displayCwd = $cwd;
            $homeDir = $sessionData['home'] ?? '';
            if ($homeDir && $cwd === $homeDir) {
                $displayCwd = '~';
            } elseif ($homeDir && strpos($cwd, $homeDir . '/') === 0) {
                $displayCwd = '~' . substr($cwd, strlen($homeDir));
            }

            // 发送新的提示符（包含当前目录）
            $prompt = $sessionData['username'] . '@' . $sessionData['server_ip'] . ':' . $displayCwd . '$ ';
            $this->send($conn, [
                'type' => 'prompt',
                'data' => $prompt
            ]);

            Log::info('命令执行', [
                'server_id' => $sessionData['server_id'] ?? null,
                'command' => $input,
                'output_length' => strlen($commandOutput)
            ]);

        } catch (\Exception $e) {
            Log::error('命令执行错误', [
                'error' => $e->getMessage(),
                'command' => $input
            ]);
            $this->send($conn, [
                'type' => 'output',
                'data' => "错误: " . $e->getMessage()
            ]);
        }
    }

    /**
     * 处理自动补全
     */
    private function handleAutocomplete(ConnectionInterface $conn, $data)
    {
        $resourceId = $conn->resourceId;
        $input = $data['input'] ?? '';

        if (!isset($this->sshConnections[$resourceId])) {
            return;
        }

        try {
            $ssh = $this->sshConnections[$resourceId];
            $sessionData = $this->sessionData[$resourceId];
            $cwd = $sessionData['cwd'] ?? '~';
            
            // 使用 bash 的 compgen 命令进行补全
            // 分析输入，确定是命令补全还是文件补全
            $parts = explode(' ', $input);
            $lastPart = end($parts);
            
            if (count($parts) === 1) {
                // 命令补全
                $cmd = "compgen -c " . escapeshellarg($lastPart) . " 2>/dev/null | head -20";
            } else {
                // 文件/目录补全 - 在当前工作目录下执行
                $cmd = "cd " . escapeshellarg($cwd) . " 2>/dev/null && compgen -f " . escapeshellarg($lastPart) . " 2>/dev/null | head -20";
            }
            
            $output = $ssh->exec($cmd);
            $suggestions = array_filter(explode("\n", trim($output)));
            
            if (count($suggestions) === 1) {
                // 只有一个匹配，直接补全
                $parts[count($parts) - 1] = $suggestions[0];
                $completion = implode(' ', $parts);
                
                $this->send($conn, [
                    'type' => 'autocomplete',
                    'completion' => $completion
                ]);
            } elseif (count($suggestions) > 1) {
                // 多个匹配，找到公共前缀
                $commonPrefix = $this->findCommonPrefix($suggestions);
                
                if ($commonPrefix && $commonPrefix !== $lastPart) {
                    // 有公共前缀，补全到公共前缀
                    $parts[count($parts) - 1] = $commonPrefix;
                    $completion = implode(' ', $parts);
                    
                    $this->send($conn, [
                        'type' => 'autocomplete',
                        'completion' => $completion
                    ]);
                } else {
                    // 显示所有匹配项（每行显示多个，更紧凑）
                    $displayItems = [];
                    foreach ($suggestions as $item) {
                        $displayItems[] = basename($item);
                    }
                    
                    // 计算列数（假设终端宽度 120）
                    $maxLen = max(array_map('strlen', $displayItems)) + 2;
                    $cols = max(1, floor(120 / $maxLen));
                    $rows = ceil(count($displayItems) / $cols);
                    
                    $output = "\n";
                    for ($row = 0; $row < $rows; $row++) {
                        for ($col = 0; $col < $cols; $col++) {
                            $idx = $row + $col * $rows;
                            if ($idx < count($displayItems)) {
                                $output .= str_pad($displayItems[$idx], $maxLen);
                            }
                        }
                        $output .= "\n";
                    }
                    
                    $this->send($conn, [
                        'type' => 'output',
                        'data' => $output
                    ]);
                    
                    // 重新显示提示符和当前输入（包含当前目录）
                    $displayCwd = $cwd;
                    $homeDir = $sessionData['home'] ?? '';
                    if ($homeDir && $cwd === $homeDir) {
                        $displayCwd = '~';
                    } elseif ($homeDir && strpos($cwd, $homeDir . '/') === 0) {
                        $displayCwd = '~' . substr($cwd, strlen($homeDir));
                    }
                    
                    $prompt = $sessionData['username'] . '@' . $sessionData['server_ip'] . ':' . $displayCwd . '$ ';
                    $this->send($conn, [
                        'type' => 'prompt',
                        'data' => $prompt . $input
                    ]);
                }
            }
            
        } catch (\Exception $e) {
            Log::error('自动补全错误', [
                'error' => $e->getMessage(),
                'input' => $input
            ]);
        }
    }

    /**
     * 查找字符串数组的公共前缀
     */
    private function findCommonPrefix($strings)
    {
        if (empty($strings)) {
            return '';
        }
        
        $prefix = $strings[0];
        foreach ($strings as $string) {
            while (strpos($string, $prefix) !== 0) {
                $prefix = substr($prefix, 0, -1);
                if (empty($prefix)) {
                    return '';
                }
            }
        }
        
        return $prefix;
    }

    /**
     * 处理终端大小调整
     */
    private function handleResize(ConnectionInterface $conn, $data)
    {
        $cols = $data['cols'] ?? 80;
        $rows = $data['rows'] ?? 24;

        $resourceId = $conn->resourceId;
        if (isset($this->sessionData[$resourceId])) {
            $this->sessionData[$resourceId]['cols'] = $cols;
            $this->sessionData[$resourceId]['rows'] = $rows;
            
            Log::info('终端大小调整', [
                'cols' => $cols,
                'rows' => $rows,
                'resource_id' => $resourceId
            ]);
        }
    }

    /**
     * 处理断开连接
     */
    private function handleDisconnect(ConnectionInterface $conn)
    {
        $resourceId = $conn->resourceId;

        // 清理定时器
        if (isset($this->sessionData[$resourceId]['timer']) && $this->loop) {
            $this->loop->cancelTimer($this->sessionData[$resourceId]['timer']);
        }

        if (isset($this->sshConnections[$resourceId])) {
            try {
                $this->sshConnections[$resourceId]->disconnect();
            } catch (\Exception $e) {
                Log::warning('SSH 断开连接时出错', ['error' => $e->getMessage()]);
            }
            unset($this->sshConnections[$resourceId]);
        }

        if (isset($this->sessionData[$resourceId])) {
            unset($this->sessionData[$resourceId]);
        }

        if (isset($this->shellBuffers[$resourceId])) {
            unset($this->shellBuffers[$resourceId]);
        }

        Log::info('WebSocket 客户端断开连接', ['resource_id' => $resourceId]);
    }

    /**
     * 当客户端关闭连接时
     */
    public function onClose(ConnectionInterface $conn)
    {
        $this->handleDisconnect($conn);
        $this->clients->detach($conn);
    }

    /**
     * 当发生错误时
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        Log::error('WebSocket 错误', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        $conn->close();
    }

    /**
     * 发送消息给客户端
     */
    private function send(ConnectionInterface $conn, $data)
    {
        $conn->send(json_encode($data));
    }

    /**
     * 发送错误消息
     */
    private function sendError(ConnectionInterface $conn, $message)
    {
        $this->send($conn, [
            'type' => 'error',
            'message' => $message
        ]);
    }

    /**
     * 启动 Shell 输出读取器
     */
    private function startShellReader(ConnectionInterface $conn, $resourceId)
    {
        // 如果没有事件循环，则无法启动定时器
        if (!$this->loop) {
            Log::warning('事件循环未设置，无法启动 shell 读取器');
            return;
        }
        
        Log::info('启动 Shell 读取器', ['resource_id' => $resourceId]);
        
        $timer = $this->loop->addPeriodicTimer(0.1, function() use ($conn, $resourceId) {
            if (!isset($this->sshConnections[$resourceId])) {
                return;
            }
            
            try {
                $ssh = $this->sshConnections[$resourceId];
                $output = $ssh->read();
                
                if (!empty($output)) {
                    Log::info('Shell 输出', [
                        'output_length' => strlen($output),
                        'resource_id' => $resourceId
                    ]);
                    // 发送输出到客户端
                    $this->send($conn, [
                        'type' => 'shell_output',
                        'data' => $output
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('读取 shell 输出错误', [
                    'error' => $e->getMessage(),
                    'resource_id' => $resourceId
                ]);
            }
        });
        
        // 保存定时器引用以便后续清理
        $this->sessionData[$resourceId]['timer'] = $timer;
    }
}
