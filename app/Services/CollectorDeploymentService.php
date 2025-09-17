<?php

namespace App\Services;

use App\Models\Server;
use App\Models\Collector;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use phpseclib3\Net\SSH2;
use phpseclib3\Net\SFTP;
use Exception;

class CollectorDeploymentService
{
    /**
     * 远程服务器上的采集组件目录
     *
     * @var string
     */
    protected $remoteBasePath = '/opt/collectors';

    /**
     * 本地采集组件目录
     *
     * @var string
     */
    protected $localBasePath;

    /**
     * 日志服务
     *
     * @var LogService
     */
    protected $logService;
    
    /**
     * 默认SSH连接超时时间（秒）
     *
     * @var int
     */
    protected $defaultTimeout = 60;
    
    /**
     * 默认命令执行超时时间（秒）
     *
     * @var int
     */
    protected $defaultCommandTimeout = 300;

    /**
     * 构造函数
     *
     * @param LogService|null $logService
     */
    public function __construct(?LogService $logService = null)
    {
        $this->localBasePath = storage_path('app/collectors');
        $this->logService = $logService ?? new LogService();
    }
    
    /**
     * 获取远程采集组件目录
     *
     * @return string
     */
    public function getRemoteCollectorDir(): string
    {
        return $this->remoteBasePath;
    }

    /**
     * 安装采集组件到服务器
     *
     * @param Server $server 服务器模型
     * @param Collector $collector 采集组件模型
     * @param bool $forceUpdate 是否强制更新
     * @return array 安装结果
     */
    public function install(Server $server, Collector $collector, bool $forceUpdate = false): array
    {
        try {
            // 创建SSH连接
            $sftp = $this->connectSFTP($server);
            
            // 检查远程目录是否存在，不存在则创建
            $collectorDir = $this->getRemotePath($collector);
            if (!$sftp->is_dir($collectorDir)) {
                // 创建目录结构
                $sftp->mkdir($this->remoteBasePath, 0755, true);
                $sftp->mkdir($collectorDir, 0755, true);
                $isNew = true;
            } else {
                $isNew = false;
            }
            
            // 检查是否需要更新
            if (!$isNew && !$forceUpdate) {
                // 检查版本
                if ($this->checkVersion($sftp, $collector)) {
                    $message = '采集组件已是最新版本，无需更新';
                    $this->logService->logCollectorInstall($server, $collector, true, $message, [
                        'is_new' => false,
                        'is_updated' => false,
                        'is_latest' => true
                    ]);
                    
                    return [
                        'success' => true,
                        'message' => $message,
                        'is_new' => false,
                        'is_updated' => false
                    ];
                }
            }
            
            // 准备本地文件
            $localFilePath = $this->prepareCollectorFiles($collector);
            
            // 根据采集组件类型执行不同的安装逻辑
            if ($collector->isScriptType()) {
                // 脚本类组件：上传脚本文件
                $remoteFilePath = $collectorDir . '/collector.sh';
                $sftp->put($remoteFilePath, $collector->getScriptContent());
                
                // 设置执行权限
                $sftp->chmod(0755, $remoteFilePath);
            } else {
                // 程序类组件：上传程序文件
                if ($collector->file_path && Storage::exists($collector->file_path)) {
                    $programContent = Storage::get($collector->file_path);
                    $fileName = basename($collector->file_path);
                    $remoteFilePath = $collectorDir . '/' . $fileName;
                    
                    // 上传程序文件
                    $sftp->put($remoteFilePath, $programContent);
                    
                    // 如果是压缩包，解压
                    if (pathinfo($fileName, PATHINFO_EXTENSION) === 'zip') {
                        $ssh = $this->connectSSH($server);
                        $unzipCommand = "cd {$collectorDir} && unzip -o {$fileName}";
                        $ssh->exec($unzipCommand);
                    }
                    
                    // 创建可执行文件的软链接
                    $executablePath = $collectorDir . '/collector';
                    $ssh = $this->connectSSH($server);
                    $findExecutableCommand = "find {$collectorDir} -type f -executable -not -path '*/\.*' | head -n 1";
                    $foundExecutable = trim($ssh->exec($findExecutableCommand));
                    
                    if (!empty($foundExecutable)) {
                        // 创建软链接到可执行文件
                        $linkCommand = "ln -sf {$foundExecutable} {$executablePath}";
                        $ssh->exec($linkCommand);
                    } else {
                        // 如果没有找到可执行文件，尝试使用常见的可执行文件名
                        $commonExecutables = ['run.sh', 'start.sh', 'main', 'app', $collector->code];
                        foreach ($commonExecutables as $execName) {
                            $potentialPath = $collectorDir . '/' . $execName;
                            if ($sftp->file_exists($potentialPath)) {
                                $sftp->chmod(0755, $potentialPath);
                                $linkCommand = "ln -sf {$potentialPath} {$executablePath}";
                                $ssh->exec($linkCommand);
                                break;
                            }
                        }
                    }
                    
                    // 设置执行权限
                    if ($sftp->file_exists($executablePath)) {
                        $sftp->chmod(0755, $executablePath);
                    }
                } else {
                    throw new Exception('程序类采集组件缺少程序文件');
                }
            }
            
            // 创建版本文件
            $versionContent = json_encode([
                'version' => $collector->version,
                'name' => $collector->name,
                'code' => $collector->code,
                'type' => $collector->type,
                'installed_at' => now()->timestamp
            ]);
            $sftp->put($collectorDir . '/version.json', $versionContent);
            
            // 记录日志
            $message = $isNew ? '采集组件安装成功' : '采集组件更新成功';
            $this->logService->logCollectorInstall($server, $collector, true, $message, [
                'is_new' => $isNew,
                'is_updated' => !$isNew,
                'remote_path' => $collectorDir,
                'type' => $collector->type
            ]);
            
            return [
                'success' => true,
                'message' => $message,
                'is_new' => $isNew,
                'is_updated' => !$isNew,
                'type' => $collector->type
            ];
        } catch (Exception $e) {
            // 记录错误日志
            $this->logService->logCollectorInstall($server, $collector, false, '采集组件安装失败：' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => '采集组件安装失败：' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 卸载服务器上的采集组件
     *
     * @param Server $server 服务器模型
     * @param Collector $collector 采集组件模型
     * @return array 卸载结果
     */
    public function uninstall(Server $server, Collector $collector): array
    {
        try {
            // 创建SSH连接
            $sftp = $this->connectSFTP($server);
            
            // 检查远程目录是否存在
            $collectorDir = $this->getRemotePath($collector);
            if (!$sftp->is_dir($collectorDir)) {
                $message = '采集组件不存在，无需卸载';
                $this->logService->logCollectorUninstall($server, $collector, true, $message, [
                    'is_removed' => false,
                    'remote_path' => $collectorDir
                ]);
                
                return [
                    'success' => true,
                    'message' => $message,
                    'is_removed' => false
                ];
            }
            
            // 删除目录及其内容
            $this->removeDirectory($sftp, $collectorDir);
            
            // 记录日志
            $message = '采集组件卸载成功';
            $this->logService->logCollectorUninstall($server, $collector, true, $message, [
                'is_removed' => true,
                'remote_path' => $collectorDir
            ]);
            
            return [
                'success' => true,
                'message' => $message,
                'is_removed' => true
            ];
        } catch (Exception $e) {
            // 记录错误日志
            $this->logService->logCollectorUninstall($server, $collector, false, '采集组件卸载失败：' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => '采集组件卸载失败：' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 检查采集组件版本
     *
     * @param SFTP $sftp SFTP连接
     * @param Collector $collector 采集组件模型
     * @return bool 是否是最新版本
     */
    protected function checkVersion(SFTP $sftp, Collector $collector): bool
    {
        $versionFilePath = $this->getRemotePath($collector) . '/version.json';
        
        // 检查版本文件是否存在
        if (!$sftp->file_exists($versionFilePath)) {
            return false;
        }
        
        // 读取版本文件
        $versionContent = $sftp->get($versionFilePath);
        $versionData = json_decode($versionContent, true);
        
        if (!$versionData || !isset($versionData['version'])) {
            return false;
        }
        
        // 比较版本
        $remoteVersion = $versionData['version'];
        $localVersion = $collector->version;
        
        // 如果本地版本为空，则认为需要更新
        if (empty($localVersion)) {
            return false;
        }
        
        // 比较版本号
        return version_compare($remoteVersion, $localVersion, '>=');
    }

    /**
     * 准备采集组件文件
     *
     * @param Collector $collector 采集组件模型
     * @return string 本地文件路径
     */
    protected function prepareCollectorFiles(Collector $collector): string
    {
        try {
            // 确保本地目录存在
            $localDir = $this->getLocalPath($collector);
            if (!file_exists($localDir)) {
                mkdir($localDir, 0755, true);
            }
            
            $filePath = '';
            
            // 根据采集组件类型准备不同的文件
            if ($collector->isScriptType()) {
                // 脚本类组件：创建脚本文件
                $filePath = $localDir . '/collector.sh';
                file_put_contents($filePath, $collector->getScriptContent());
            } else {
                // 程序类组件：复制程序文件
                if ($collector->file_path && Storage::exists($collector->file_path)) {
                    $fileName = basename($collector->file_path);
                    $filePath = $localDir . '/' . $fileName;
                    
                    // 复制文件到本地目录
                    $programContent = Storage::get($collector->file_path);
                    file_put_contents($filePath, $programContent);
                } else {
                    throw new Exception('程序类采集组件缺少程序文件');
                }
            }
            
            // 创建版本文件
            $versionInfo = [
                'version' => $collector->version,
                'name' => $collector->name,
                'code' => $collector->code,
                'type' => $collector->type,
                'updated_at' => now()->timestamp
            ];
            file_put_contents($localDir . '/version.json', json_encode($versionInfo));
            
            return $filePath;
        } catch (Exception $e) {
            Log::error('准备采集组件文件失败', [
                'collector_id' => $collector->id,
                'collector_code' => $collector->code,
                'type' => $collector->type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * 递归删除目录
     *
     * @param SFTP $sftp SFTP连接
     * @param string $directory 目录路径
     * @return void
     */
    protected function removeDirectory(SFTP $sftp, string $directory): void
    {
        $files = $sftp->nlist($directory);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $path = $directory . '/' . $file;
            
            if ($sftp->is_dir($path)) {
                $this->removeDirectory($sftp, $path);
            } else {
                $sftp->delete($path);
            }
        }
        
        $sftp->rmdir($directory);
    }

    /**
     * 创建SFTP连接
     *
     * @param Server $server 服务器模型
     * @return SFTP SFTP连接实例
     * @throws Exception 连接失败时抛出异常
     */
    protected function connectSFTP(Server $server): SFTP
    {
        $sftp = new SFTP($server->ip, $server->port);
        $sftp->setTimeout(30); // 设置超时时间为30秒
        
        if (!$sftp->login($server->username, $server->password)) {
            throw new Exception('SFTP登录失败，请检查服务器连接信息');
        }
        
        return $sftp;
    }

    /**
     * 创建SSH连接
     *
     * @param Server $server 服务器模型
     * @param int $timeout 超时时间（秒）
     * @return SSH2 SSH连接实例
     * @throws Exception 连接失败时抛出异常
     */
    public function connectSSH(Server $server, int $timeout = null): SSH2
    {
        $timeout = $timeout ?? $this->defaultTimeout;
        
        try {
            $ssh = new SSH2($server->ip, $server->port);
            $ssh->setTimeout($timeout);
            
            $this->logService->debug('尝试SSH连接', [
                'server_id' => $server->id,
                'ip' => $server->ip,
                'port' => $server->port,
                'username' => $server->username,
                'timeout' => $timeout
            ]);
            
            if (!$ssh->login($server->username, $server->password)) {
                throw new Exception('SSH登录失败，请检查服务器连接信息');
            }
            
            return $ssh;
        } catch (\Exception $e) {
            $this->logService->error('SSH连接失败', [
                'server_id' => $server->id,
                'server_ip' => $server->ip,
                'error' => $e->getMessage(),
                'timeout' => $timeout
            ]);
            throw new Exception('SSH连接失败：' . $e->getMessage());
        }
    }

    /**
     * 执行远程命令
     *
     * @param Server $server 服务器模型
     * @param string $command 要执行的命令
     * @param int $timeout 命令执行超时时间（秒）
     * @return string 命令输出
     * @throws Exception 执行异常
     */
    protected function executeCommand(Server $server, string $command, int $timeout = null): string
    {
        $timeout = $timeout ?? $this->defaultCommandTimeout;
        $startTime = microtime(true);
        
        try {
            $ssh = $this->connectSSH($server, $timeout);
            
            // 设置命令超时
            $ssh->setTimeout($timeout);
            
            $this->logService->debug('执行远程命令', [
                'server_id' => $server->id,
                'command' => $command,
                'timeout' => $timeout
            ]);
            
            // 执行命令
            $output = $ssh->exec($command);
            $exitStatus = $ssh->getExitStatus();
            $executionTime = microtime(true) - $startTime;
            
            $this->logService->debug('命令执行完成', [
                'server_id' => $server->id,
                'exit_status' => $exitStatus,
                'execution_time' => $executionTime,
                'output_length' => strlen($output)
            ]);
            
            // 检查退出状态
            if ($exitStatus !== 0) {
                throw new Exception("命令执行失败，退出状态码: {$exitStatus}，输出: {$output}");
            }
            
            return $output;
        } catch (\Exception $e) {
            $executionTime = microtime(true) - $startTime;
            
            $this->logService->error('命令执行异常', [
                'server_id' => $server->id,
                'command' => $command,
                'error' => $e->getMessage(),
                'execution_time' => $executionTime
            ]);
            
            throw new Exception('命令执行失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取采集组件在服务器上的状态
     *
     * @param Server $server 服务器模型
     * @param Collector $collector 采集组件模型
     * @return array 状态信息
     */
    public function getStatus(Server $server, Collector $collector): array
    {
        try {
            $sftp = $this->connectSFTP($server);
            
            $collectorDir = $this->getRemotePath($collector);
            $versionFilePath = $collectorDir . '/version.json';
            
            if (!$sftp->is_dir($collectorDir)) {
                return [
                    'installed' => false,
                    'version' => null,
                    'installed_at' => null
                ];
            }
            
            if (!$sftp->file_exists($versionFilePath)) {
                return [
                    'installed' => true,
                    'version' => null,
                    'installed_at' => null
                ];
            }
            
            $versionContent = $sftp->get($versionFilePath);
            $versionData = json_decode($versionContent, true);
            
            return [
                'installed' => true,
                'version' => $versionData['version'] ?? null,
                'installed_at' => isset($versionData['installed_at']) ? date('Y-m-d H:i:s', $versionData['installed_at']) : null,
                'is_latest' => $this->checkVersion($sftp, $collector)
            ];
        } catch (Exception $e) {
            Log::error('获取采集组件状态失败', [
                'server_id' => $server->id,
                'collector_id' => $collector->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'installed' => false,
                'version' => null,
                'installed_at' => null,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取采集组件的远程路径
     *
     * @param Collector $collector 采集组件模型
     * @return string 远程路径
     */
    protected function getRemotePath(Collector $collector): string
    {
        if (!empty($collector->deployment_config) && isset($collector->deployment_config['remote_path'])) {
            return $collector->deployment_config['remote_path'];
        }
        
        return $this->remoteBasePath . '/' . $collector->code;
    }
    
    /**
     * 获取采集组件的本地路径
     *
     * @param Collector $collector 采集组件模型
     * @return string 本地路径
     */
    protected function getLocalPath(Collector $collector): string
    {
        return $this->localBasePath . '/' . $collector->code;
    }
    
    /**
     * 执行采集脚本或程序
     *
     * @param Server $server 服务器模型
     * @param Collector $collector 采集组件模型
     * @param int $timeout 超时时间（秒）
     * @return array 执行结果
     * @throws Exception 执行异常
     */
    public function executeCollectorScript(Server $server, Collector $collector, int $timeout = null): array
    {
        $timeout = $timeout ?? $this->defaultCommandTimeout;
        
        try {
            // 记录开始执行采集脚本
            $this->logService->logScriptExecution(
                $server, 
                $collector, 
                true, 
                '开始执行采集脚本', 
                [
                    'timeout' => $timeout,
                    'collector_type' => $collector->type
                ]
            );
            
            // 根据采集组件类型执行不同的操作
            if ($collector->isScriptType()) {
                // 脚本类组件：直接执行脚本内容
                // 创建临时脚本文件
                $tempScriptName = 'collector_' . $collector->id . '_' . time() . '.sh';
                $tempScriptPath = '/tmp/' . $tempScriptName;
                
                // 将脚本内容写入临时文件并执行
                $ssh = $this->connectSSH($server, $timeout);
                
                // 获取脚本内容（优先从文件系统读取）
                $scriptContent = $collector->getScriptContent();
                
                // 将Windows风格的换行符(\r\n)转换为Unix风格的换行符(\n)
                $scriptContent = str_replace("\r\n", "\n", $scriptContent);
                $scriptContent = str_replace("\r", "\n", $scriptContent); // 处理仅有\r的情况
                
                // 添加命令检查和替代方案的包装脚本
                $wrapperScript = $this->getWrapperScript();
                
                // 将包装脚本和实际脚本内容写入临时文件
                $this->logService->debug('创建临时脚本文件', [
                    'server_id' => $server->id,
                    'collector_id' => $collector->id,
                    'temp_script_path' => $tempScriptPath
                ]);
                
                $ssh->exec('cat > ' . $tempScriptPath . ' << \'EOT\'' . PHP_EOL . $wrapperScript . PHP_EOL . $scriptContent . PHP_EOL . 'EOT');
                $ssh->exec('chmod +x ' . $tempScriptPath);
                
                // 执行命令并获取结果
                $command = 'bash ' . $tempScriptPath . ' 2>&1';
                $this->logService->debug('执行命令', [
                    'server_id' => $server->id,
                    'collector_id' => $collector->id,
                    'command' => $command
                ]);
                
                $output = $this->executeCommand($server, $command, $timeout);
                
                // 清理临时文件
                $ssh->exec('rm -f ' . $tempScriptPath);
            } else {
                // 程序类组件：执行已安装的程序
                $programPath = $this->getRemoteCollectorDir() . '/' . $collector->code . '/collector';
                $command = $programPath . ' 2>&1';
                
                $this->logService->debug('执行程序', [
                    'server_id' => $server->id,
                    'collector_id' => $collector->id,
                    'program_path' => $programPath,
                    'command' => $command
                ]);
                
                $output = $this->executeCommand($server, $command, $timeout);
            }
            
            // 记录原始输出
            $this->logService->debug('采集脚本原始输出', [
                'server_id' => $server->id,
                'collector_id' => $collector->id,
                'output' => $output
            ]);
            
            // 解析JSON结果
            $result = $this->parseScriptOutput($output);
            
            // 记录执行成功
            $this->logService->logScriptExecution(
                $server, 
                $collector, 
                true, 
                '采集脚本执行成功', 
                [
                    'result' => $result
                ]
            );
            
            return $result;
        } catch (\Exception $e) {
            // 记录执行失败
            $this->logService->logScriptExecution(
                $server, 
                $collector, 
                false, 
                '采集脚本执行失败: ' . $e->getMessage(), 
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            );
            
            // 返回统一的错误信息格式
            return [
                'success' => false,
                'message' => '采集异常: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 解析脚本输出
     *
     * @param string $output 脚本输出
     * @return array 解析结果
     * @throws Exception 解析异常
     */
    protected function parseScriptOutput(string $output): array
    {
        // 尝试提取JSON部分
        $jsonStart = strpos($output, '{');
        $jsonEnd = strrpos($output, '}');
        
        if ($jsonStart !== false && $jsonEnd !== false && $jsonEnd > $jsonStart) {
            $jsonPart = substr($output, $jsonStart, $jsonEnd - $jsonStart + 1);
            $result = json_decode($jsonPart, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                // 添加原始输出到结果中
                $result['raw_output'] = $output;
                return $result;
            }
        }
        
        // 如果无法解析为JSON，尝试查找错误信息
        if (preg_match('/error|exception|失败|错误/i', $output)) {
            throw new Exception('脚本执行出错: ' . $output);
        }
        
        // 如果没有明显错误但也不是JSON，返回原始输出
        return [
            'success' => true,
            'message' => '脚本执行完成，但返回非JSON格式',
            'raw_output' => $output
        ];
    }
    
    /**
     * 获取包装脚本
     *
     * @return string 包装脚本内容
     */
    protected function getWrapperScript(): string
    {
        return <<<'EOW'
#!/bin/bash

# 设置错误处理
set -e

# 检查命令是否存在的函数
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# 创建一个简单的网络接口信息函数
get_network_interfaces() {
    # 尝试使用不同的命令获取网络接口信息
    if command_exists "ip"; then
        ip -o link show | grep -v "lo"
    elif command_exists "ifconfig"; then
        ifconfig | grep -E "^[a-zA-Z0-9]+:"
    elif [ -d "/sys/class/net" ]; then
        # 使用/sys文件系统
        for iface in /sys/class/net/*; do
            if [ "$(basename $iface)" != "lo" ]; then
                echo "$(basename $iface): $(cat $iface/address 2>/dev/null || echo 'unknown')"
            fi
        done
    else
        # 最后的备选方案
        echo "eth0: unknown"
    fi
}

# 获取IP地址的函数
get_ip_address() {
    local interface=$1
    if command_exists "ip"; then
        ip -o -4 addr show dev $interface 2>/dev/null | awk '{print $4}' | cut -d'/' -f1
    elif command_exists "ifconfig"; then
        ifconfig $interface 2>/dev/null | grep "inet " | awk '{print $2}'
    elif [ -f "/proc/net/fib_trie" ]; then
        # 使用/proc文件系统
        grep -A1 "$interface" /proc/net/fib_trie | grep -oE '\b([0-9]{1,3}\.){3}[0-9]{1,3}\b' | head -n1
    else
        echo "unknown"
    fi
}

# 获取默认网关
get_default_gateway() {
    if command_exists "ip"; then
        ip route | grep default | awk '{print $3}'
    elif command_exists "netstat"; then
        netstat -rn | grep "^0.0.0.0" | awk '{print $2}'
    elif [ -f "/proc/net/route" ]; then
        # 使用/proc文件系统
        awk '$2 == "00000000" {print $3}' /proc/net/route | while read hex; do
            printf "%d.%d.%d.%d\n" 0x${hex:6:2} 0x${hex:4:2} 0x${hex:2:2} 0x${hex:0:2}
        done
    else
        echo "unknown"
    fi
}

# 替代ip命令的函数
ip_command_alternative() {
    case "$1" in
        "link")
            if [ "$2" = "show" ]; then
                get_network_interfaces
            fi
            ;;
        "route")
            get_default_gateway
            ;;
        "-o")
            if [ "$2" = "link" ] && [ "$3" = "show" ]; then
                get_network_interfaces
            elif [ "$2" = "-4" ] && [ "$3" = "addr" ] && [ "$4" = "show" ] && [ "$5" = "dev" ]; then
                get_ip_address "$6"
            fi
            ;;
    esac
}

# 检查常用命令并提供替代方案
if ! command_exists "ip"; then
    echo "ip命令不存在，使用替代方案" >&2
    # 创建ip命令的替代函数
    ip() {
        ip_command_alternative "$@"
    }
    export -f ip
fi

# 检查ss命令
if ! command_exists "ss"; then
    echo "ss命令不存在，使用替代方案" >&2
    # 创建ss命令的替代函数
    ss() {
        if command_exists "netstat"; then
            netstat "$@"
        else
            echo "无法获取端口信息" >&2
            echo ""
        fi
    }
    export -f ss
fi

# 设置超时处理
timeout_handler() {
    echo '{"success":false,"message":"采集脚本执行超时"}'
    exit 124
}

# 设置错误处理
error_handler() {
    echo '{"success":false,"message":"采集脚本执行错误: 退出码 '$?'"}'
    exit 1
}

# 注册信号处理器
trap timeout_handler SIGALRM
trap error_handler ERR

# 执行实际脚本
EOW;
    }
    
    /**
     * 更新服务器上的所有采集组件
     *
     * @param Server $server 服务器模型
     * @return array 更新结果
     */
    public function updateAllCollectors(Server $server): array
    {
        $results = [];
        $collectors = $server->collectors;
        
        foreach ($collectors as $collector) {
            $results[$collector->id] = $this->install($server, $collector, true);
        }
        
        return $results;
    }
    
    /**
     * 更新采集组件到所有服务器
     *
     * @param Collector $collector 采集组件模型
     * @return array 更新结果
     */
    public function updateToAllServers(Collector $collector): array
    {
        $results = [];
        $servers = $collector->servers;
        
        foreach ($servers as $server) {
            $results[$server->id] = $this->install($server, $collector, true);
        }
        
        return $results;
    }
}