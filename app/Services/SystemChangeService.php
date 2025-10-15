<?php

namespace App\Services;

use App\Models\SystemChangeTask;
use App\Models\SystemChangeTaskDetail;
use App\Models\ConfigTemplate;
use App\Models\Server;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;

class SystemChangeService
{
    /**
     * 创建任务详情
     */
    public function createTaskDetails(SystemChangeTask $task)
    {
        $servers = Server::whereIn('id', $task->server_ids)->get();
        $templates = ConfigTemplate::whereIn('id', $task->template_ids)->get();
        
        $executionOrder = 1;
        
        foreach ($servers as $server) {
            foreach ($templates as $template) {
                // 支持新的config_rules格式和旧的config_items格式
                $configItems = [];
                
                // 新格式：config_rules
                if ($template->config_rules) {
                    $rules = is_string($template->config_rules) 
                        ? json_decode($template->config_rules, true) 
                        : $template->config_rules;
                    
                    if (is_array($rules)) {
                        foreach ($rules as $rule) {
                            $filePath = null;
                            
                            // 根据规则类型获取文件路径
                            if ($rule['type'] === 'directory') {
                                $filePath = $rule['directory'] ?? null;
                            } elseif ($rule['type'] === 'file') {
                                $filePath = $rule['file_path'] ?? null;
                            } elseif ($rule['type'] === 'string') {
                                $filePath = $rule['file_path'] ?? null;
                            }
                            
                            if ($filePath) {
                                $configItems[] = [
                                    'file_path' => $filePath,
                                    'rule_type' => $rule['type'],
                                    'rule_data' => $rule
                                ];
                            }
                        }
                    }
                }
                
                // 旧格式：config_items（向后兼容）
                if (empty($configItems) && $template->config_items) {
                    $items = is_string($template->config_items) 
                        ? json_decode($template->config_items, true) 
                        : $template->config_items;
                    
                    if (is_array($items)) {
                        foreach ($items as $item) {
                            if (isset($item['file_path'])) {
                                $configItems[] = [
                                    'file_path' => $item['file_path'],
                                    'rule_type' => 'legacy',
                                    'rule_data' => $item
                                ];
                            }
                        }
                    }
                }
                
                // 如果没有配置项，至少创建一个基于模板的任务详情
                if (empty($configItems)) {
                    $configItems[] = [
                        'file_path' => '/tmp/template_' . $template->id,
                        'rule_type' => 'template',
                        'rule_data' => []
                    ];
                }
                
                // 创建任务详情
                foreach ($configItems as $configItem) {
                    // 处理变量配置
                    $configVariables = [];
                    if ($task->config_variables && is_array($task->config_variables)) {
                        $configVariables = $task->config_variables;
                    }
                    
                    SystemChangeTaskDetail::create([
                        'task_id' => $task->id,
                        'server_id' => $server->id,
                        'server_ip' => $server->ip,
                        'server_name' => $server->name,
                        'template_id' => $template->id,
                        'config_file_path' => $configItem['file_path'],
                        'rule_type' => $configItem['rule_type'],
                        'rule_data' => json_encode($configItem['rule_data']),
                        'config_variables' => json_encode($configVariables),
                        'target_path' => $configItem['file_path'],
                        'status' => 'pending',
                        'execution_order' => $executionOrder++
                    ]);
                }
            }
        }
    }

    /**
     * 执行变更任务
     */
    public function executeTask(SystemChangeTask $task)
    {
        if (!$task->canExecute()) {
            throw new \Exception('任务当前状态不允许执行');
        }

        // 如果是重新执行失败的任务，重置任务详情状态
        if ($task->status === SystemChangeTask::STATUS_FAILED) {
            $task->taskDetails()->update([
                'status' => 'pending',
                'execution_log' => null,
                'error_message' => null,
                'started_at' => null,
                'completed_at' => null
            ]);
            Log::info("重新执行失败任务，已重置任务详情状态: {$task->name} (ID: {$task->id})");
        }

        // 更新任务状态
        $task->update([
            'status' => SystemChangeTask::STATUS_RUNNING,
            'started_at' => now(),
            'completed_at' => null  // 重置完成时间
        ]);

        Log::info("开始执行系统变更任务: {$task->name} (ID: {$task->id})");

        try {
            if ($task->execution_order === SystemChangeTask::ORDER_SEQUENTIAL) {
                $this->executeSequential($task);
            } else {
                $this->executeParallel($task);
            }
        } catch (\Exception $e) {
            Log::error("任务执行失败: {$e->getMessage()}", [
                'task_id' => $task->id,
                'exception' => $e
            ]);
            
            $task->update([
                'status' => SystemChangeTask::STATUS_FAILED,
                'completed_at' => now()
            ]);
            
            throw $e;
        }
    }

    /**
     * 顺序执行任务
     */
    private function executeSequential(SystemChangeTask $task)
    {
        $details = $task->taskDetails()
            ->where('status', 'pending')
            ->orderBy('execution_order')
            ->get();

        Log::info("找到 " . $details->count() . " 个待执行的任务详情", ['task_id' => $task->id]);

        foreach ($details as $detail) {
            // 检查任务是否被暂停或取消
            $task->refresh();
            if ($task->status === SystemChangeTask::STATUS_PAUSED) {
                Log::info("任务被暂停，停止执行", ['task_id' => $task->id]);
                break;
            }
            
            if ($task->status === SystemChangeTask::STATUS_CANCELLED) {
                Log::info("任务被取消，停止执行", ['task_id' => $task->id]);
                break;
            }

            Log::info("开始执行任务详情", [
                'task_id' => $task->id,
                'detail_id' => $detail->id,
                'server_ip' => $detail->server_ip
            ]);

            $this->executeTaskDetail($detail);
            $task->updateProgress();
        }

        // 检查任务是否完成
        if ($task->status === SystemChangeTask::STATUS_RUNNING) {
            $task->update([
                'status' => SystemChangeTask::STATUS_COMPLETED,
                'completed_at' => now()
            ]);
            Log::info("任务执行完成", ['task_id' => $task->id]);
        }
    }

    /**
     * 并行执行任务（简化版，实际应该使用队列）
     */
    private function executeParallel(SystemChangeTask $task)
    {
        // 这里简化处理，实际应该使用Laravel队列进行并行处理
        $this->executeSequential($task);
    }

    /**
     * 执行单个任务详情
     */
    public function executeTaskDetail(SystemChangeTaskDetail $detail)
    {
        $detail->update([
            'status' => 'running',
            'started_at' => now()
        ]);

        $detail->addLog("开始执行配置变更");

        try {
            // 建立SSH连接
            $ssh = $this->connectToServer($detail->server);
            
            // 备份原始文件/目录
            $this->backupOriginalFile($ssh, $detail);
            
            // 检查是文件还是目录
            $checkCommand = "test -d '{$detail->config_file_path}'";
            $ssh->exec($checkCommand);
            $isDirectory = ($ssh->getExitStatus() === 0);
            
            if ($isDirectory) {
                // 目录操作逻辑
                $this->executeDirectoryChanges($ssh, $detail);
            } else {
                // 文件操作逻辑
                $this->executeFileChanges($ssh, $detail);
            }
            
            $detail->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);
            
            $detail->addLog("配置变更执行成功");
            
        } catch (\Exception $e) {
            Log::error("任务详情执行失败", [
                'detail_id' => $detail->id,
                'server_id' => $detail->server_id,
                'error' => $e->getMessage()
            ]);
            
            $detail->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now()
            ]);
            
            $detail->addLog("执行失败: " . $e->getMessage());
            
            // 尝试回滚
            if ($detail->backup_created) {
                try {
                    $this->rollbackChanges($detail);
                    $detail->addLog("已自动回滚到原始配置");
                } catch (\Exception $rollbackException) {
                    $detail->addLog("回滚失败: " . $rollbackException->getMessage());
                }
            }
        }
    }

    /**
     * 连接到服务器
     */
    private function connectToServer(Server $server)
    {
        $ssh = new SSH2($server->ip, $server->port ?? 22);
        
        // 目前只支持密码认证
        if (!$ssh->login($server->username, $server->password)) {
            throw new \Exception("SSH密码认证失败: {$server->ip}");
        }
        
        return $ssh;
    }

    /**
     * 备份原始文件
     */
    private function backupOriginalFile(SSH2 $ssh, SystemChangeTaskDetail $detail)
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        
        // 首先检查路径是文件还是目录
        $checkCommand = "test -d '{$detail->config_file_path}'";
        $ssh->exec($checkCommand);
        $isDirectory = ($ssh->getExitStatus() === 0);
        
        // 为目录和文件使用不同的备份策略
        if ($isDirectory) {
            // 目录备份：在源目录内创建backup目录
            $sourcePath = rtrim($detail->config_file_path, '/');
            $backupDir = "{$sourcePath}/backup";
            $dirName = basename($sourcePath);
            $backupPath = "{$backupDir}/{$dirName}_{$timestamp}";
            
            // 创建backup目录
            $mkdirCommand = "mkdir -p '{$backupDir}'";
            $ssh->exec($mkdirCommand);
            
            if ($ssh->getExitStatus() !== 0) {
                throw new \Exception("创建备份目录失败");
            }
            
            // 备份目录内容（排除backup目录本身）
            $command = "rsync -av --exclude='backup' '{$sourcePath}/' '{$backupPath}/'";
            $detail->addLog("备份目录内容: {$detail->config_file_path} -> {$backupPath}");
        } else {
            // 文件备份：在文件所在目录创建backup目录
            $fileDir = dirname($detail->config_file_path);
            $fileName = basename($detail->config_file_path);
            $backupDir = "{$fileDir}/backup";
            $backupPath = "{$backupDir}/{$fileName}_{$timestamp}";
            
            // 创建backup目录
            $mkdirCommand = "mkdir -p '{$backupDir}'";
            $ssh->exec($mkdirCommand);
            
            if ($ssh->getExitStatus() !== 0) {
                throw new \Exception("创建备份目录失败");
            }
            
            $command = "cp '{$detail->config_file_path}' '{$backupPath}'";
            $detail->addLog("备份文件: {$detail->config_file_path} -> {$backupPath}");
        }
        
        $result = $ssh->exec($command);
        
        if ($ssh->getExitStatus() !== 0) {
            throw new \Exception("备份失败: {$result}");
        }
        
        $detail->update([
            'backup_created' => true,
            'backup_path' => $backupPath
        ]);
        
        $detail->addLog("已创建备份: {$backupPath}");
    }

    /**
     * 执行文件变更
     */
    private function executeFileChanges(SSH2 $ssh, SystemChangeTaskDetail $detail)
    {
        // 读取原始文件内容
        $originalContent = $this->readFileContent($ssh, $detail->config_file_path);
        $detail->update(['original_content' => $originalContent]);
        
        // 应用配置变更
        $newContent = $this->applyConfigChanges($detail, $originalContent);
        $detail->update(['new_content' => $newContent]);
        
        // 写入新内容
        $this->writeFileContent($ssh, $detail->config_file_path, $newContent);
        
        // 验证变更
        $this->validateChanges($ssh, $detail);
        
        $detail->addLog("文件配置变更完成");
    }

    /**
     * 执行目录变更
     */
    private function executeDirectoryChanges(SSH2 $ssh, SystemChangeTaskDetail $detail)
    {
        $template = $detail->template;
        $task = $detail->task;
        
        // 获取配置规则
        $rules = is_string($template->config_rules) 
            ? json_decode($template->config_rules, true) 
            : $template->config_rules;
        
        if (!is_array($rules)) {
            throw new \Exception("模板配置规则格式错误");
        }
        
        $detail->addLog("开始执行目录批量处理，目标目录: {$detail->config_file_path}，共 " . count($rules) . " 个处理规则");
        
        $totalChanges = 0;
        $changeLog = [];
        
        foreach ($rules as $index => $rule) {
            $ruleChanges = $this->executeDirectoryRule($ssh, $detail, $rule, $index + 1);
            $totalChanges += $ruleChanges['count'];
            if (!empty($ruleChanges['details'])) {
                $changeLog = array_merge($changeLog, $ruleChanges['details']);
            }
        }
        
        // 记录变更摘要并批量保存
        $detail->addLog("目录处理完成，共处理 {$totalChanges} 个文件", true);
        
        if (!empty($changeLog)) {
            $detail->update([
                'new_content' => json_encode($changeLog, JSON_UNESCAPED_UNICODE)
            ]);
        }
    }

    /**
     * 执行单个目录规则
     */
    private function executeDirectoryRule(SSH2 $ssh, SystemChangeTaskDetail $detail, $rule, $ruleIndex)
    {
        $ruleType = $rule['rule_type'] ?? $rule['type'] ?? 'string';
        $detail->addLog("执行规则 {$ruleIndex}: {$ruleType}");
        
        $changes = ['count' => 0, 'details' => []];
        
        switch ($ruleType) {
            case 'directory':
                $changes = $this->processDirectoryRule($ssh, $detail, $rule, $ruleIndex);
                break;
            case 'file':
                $changes = $this->processFileRule($ssh, $detail, $rule, $ruleIndex);
                break;
            case 'string':
                $changes = $this->processStringRule($ssh, $detail, $rule, $ruleIndex);
                break;
            default:
                throw new \Exception("不支持的规则类型: {$ruleType}");
        }
        
        $detail->addLog("规则 {$ruleIndex} 执行完成，处理了 {$changes['count']} 个文件");
        
        return $changes;
    }

    /**
     * 处理目录规则 - 在目录下查找并处理文件
     */
    private function processDirectoryRule(SSH2 $ssh, SystemChangeTaskDetail $detail, $rule, $ruleIndex)
    {
        $changes = ['count' => 0, 'details' => []];
        
        // 获取任务变量配置
        $taskVariables = $detail->task->config_variables ?? [];
        $ruleVariables = $rule['variables'] ?? [];
        
        if (empty($ruleVariables)) {
            $detail->addLog("规则 {$ruleIndex}: 无变量配置，跳过处理");
            return $changes;
        }
        
        // 获取文件匹配模式
        $filePattern = $rule['file_pattern'] ?? '*.php';
        $targetDirectory = $detail->config_file_path;
        
        // 查找匹配的文件，排除备份目录
        $findCommand = "find '{$targetDirectory}' -type f -name '{$filePattern}' -not -path '*/backup/*' 2>/dev/null";
        
        $result = $ssh->exec($findCommand);
        $exitStatus = $ssh->getExitStatus();
        
        if ($exitStatus === 0 && !empty(trim($result))) {
            $files = array_filter(explode("\n", trim($result)));
            $detail->addLog("规则 {$ruleIndex}: 找到 " . count($files) . " 个 {$filePattern} 文件");
            
            // 处理每个文件
            foreach ($files as $file) {
                $file = trim($file);
                
                $fileChanges = $this->processFileWithVariables($ssh, $detail, $file, $ruleVariables, $taskVariables);
                $changes['count'] += $fileChanges['count'];
                $changes['details'] = array_merge($changes['details'], $fileChanges['details']);
            }
        } else {
            $detail->addLog("规则 {$ruleIndex}: 未找到匹配的 {$filePattern} 文件");
        }
        
        return $changes;
    }

    /**
     * 处理文件规则 - 处理单个指定文件
     */
    private function processFileRule(SSH2 $ssh, SystemChangeTaskDetail $detail, $rule, $ruleIndex)
    {
        $changes = ['count' => 0, 'details' => []];
        
        // 获取任务变量配置
        $taskVariables = $detail->task->config_variables ?? [];
        $ruleVariables = $rule['variables'] ?? [];
        
        if (empty($ruleVariables)) {
            $detail->addLog("规则 {$ruleIndex}: 无变量配置，跳过处理");
            return $changes;
        }
        
        // 获取目标文件路径
        $filePath = $rule['file_path'] ?? '';
        if (empty($filePath)) {
            $detail->addLog("规则 {$ruleIndex}: 未指定文件路径");
            return $changes;
        }
        
        // 如果是相对路径，则相对于配置目录
        if (!str_starts_with($filePath, '/')) {
            $filePath = rtrim($detail->config_file_path, '/') . '/' . $filePath;
        }
        
        // 检查文件是否存在
        $checkCommand = "test -f '{$filePath}' && echo 'exists' || echo 'not_found'";
        $result = $ssh->exec($checkCommand);
        
        if (trim($result) === 'exists') {
            $fileChanges = $this->processFileWithVariables($ssh, $detail, $filePath, $ruleVariables, $taskVariables);
            $changes['count'] += $fileChanges['count'];
            $changes['details'] = array_merge($changes['details'], $fileChanges['details']);
            
            $detail->addLog("规则 {$ruleIndex}: 处理文件 " . basename($filePath) . "，修改了 {$fileChanges['count']} 处内容");
        } else {
            $detail->addLog("规则 {$ruleIndex}: 文件不存在 - " . basename($filePath));
        }
        
        return $changes;
    }

    /**
     * 处理字符串规则 - 在目录下查找并替换字符串
     */
    private function processStringRule(SSH2 $ssh, SystemChangeTaskDetail $detail, $rule, $ruleIndex)
    {
        $changes = ['count' => 0, 'details' => []];
        
        // 获取文件匹配模式和替换规则
        $filePattern = $rule['file_path'] ?? '*.php';
        $searchString = $rule['search_string'] ?? '';
        $replaceString = $rule['replace_string'] ?? '';
        $variables = $rule['variables'] ?? [];
        
        if (empty($searchString)) {
            $detail->addLog("规则 {$ruleIndex}: 搜索字符串为空，跳过处理");
            return $changes;
        }
        
        // 在目标目录下递归查找匹配的文件，排除备份目录
        $findCommand = "find '{$detail->config_file_path}' -type f -name '{$filePattern}' -not -path '*/backup/*' 2>/dev/null";
        $result = $ssh->exec($findCommand);
        
        if ($ssh->getExitStatus() === 0 && !empty(trim($result))) {
            $files = array_filter(explode("\n", trim($result)));
            
            foreach ($files as $file) {
                $file = trim($file);
                
                // 检查文件是否包含搜索字符串
                $grepCommand = "grep -l '{$searchString}' '{$file}' 2>/dev/null";
                $ssh->exec($grepCommand);
                
                if ($ssh->getExitStatus() === 0) {
                    $detail->addLog("在文件中找到匹配内容: {$file}");
                    
                    // 读取原始内容
                    $originalContent = $this->readFileContent($ssh, $file);
                    
                    // 应用变量替换到替换字符串
                    $finalReplaceString = $this->applyTaskVariables($replaceString, $detail->task->config_variables ?? []);
                    
                    // 执行字符串替换
                    $newContent = str_replace($searchString, $finalReplaceString, $originalContent);
                    
                    if ($originalContent !== $newContent) {
                        // 写入修改后的内容
                        $this->writeFileContent($ssh, $file, $newContent);
                        
                        // 记录变更详情
                        $changeDetail = [
                            'file' => $file,
                            'rule_type' => 'string',
                            'search' => $searchString,
                            'replace' => $finalReplaceString,
                            'before' => $this->getContextLines($originalContent, $searchString),
                            'after' => $this->getContextLines($newContent, $finalReplaceString),
                            'timestamp' => now()->format('Y-m-d H:i:s')
                        ];
                        
                        $changes['details'][] = $changeDetail;
                        $changes['count']++;
                        
                        $detail->addLog("已修改文件: {$file}");
                        $detail->addLog("  替换: '{$searchString}' -> '{$finalReplaceString}'");
                    }
                }
            }
        }
        
        return $changes;
    }

    /**
     * 处理文件中的变量替换 - 核心处理逻辑（高性能版）
     */
    private function processFileWithVariables(SSH2 $ssh, SystemChangeTaskDetail $detail, $filePath, $ruleVariables, $taskVariables)
    {
        $changes = ['count' => 0, 'details' => []];
        
        // 读取文件内容
        $originalContent = $this->readFileContent($ssh, $filePath);
        
        if ($originalContent === false) {
            return $changes;
        }
        
        $newContent = $originalContent;
        $hasChanges = false;
        $processedVariables = [];
        
        // 批量处理所有变量配置
        foreach ($ruleVariables as $varConfig) {
            $variableName = $varConfig['variable'] ?? '';
            $matchType = $varConfig['match_type'] ?? 'placeholder';
            $matchPattern = $varConfig['match_pattern'] ?? '';
            
            if (empty($variableName) || !isset($taskVariables[$variableName])) {
                continue;
            }
            
            $newValue = $taskVariables[$variableName];
            $replaceCount = 0;
            $beforeContent = $newContent;
            
            // 根据匹配类型进行替换
            switch ($matchType) {
                case 'exact':
                    if (!empty($matchPattern) && strpos($newContent, $matchPattern) !== false) {
                        $newContent = str_replace($matchPattern, $newValue, $newContent, $replaceCount);
                    }
                    break;
                    
                case 'regex':
                    if (!empty($matchPattern)) {
                        $pattern = '/' . str_replace('/', '\/', $matchPattern) . '/';
                        $newContent = preg_replace($pattern, $newValue, $newContent, -1, $replaceCount);
                    }
                    break;
                    
                case 'key_value':
                    $pattern = '/(\$' . preg_quote($variableName, '/') . '\s*=\s*["\'])([^"\']*?)(["\'])/';
                    $newContent = preg_replace($pattern, '$1' . $newValue . '$3', $newContent, -1, $replaceCount);
                    break;
                    
                case 'placeholder':
                default:
                    $placeholder = '{{' . $variableName . '}}';
                    if (strpos($newContent, $placeholder) !== false) {
                        $newContent = str_replace($placeholder, $newValue, $newContent, $replaceCount);
                    }
                    break;
            }
            
            // 只记录有变更的详情
            if ($replaceCount > 0) {
                $hasChanges = true;
                
                // 简化的变更记录，减少上下文获取的开销
                $changeDetail = [
                    'file' => $filePath,
                    'variable' => $variableName,
                    'match_type' => $matchType,
                    'match_pattern' => $matchPattern,
                    'old_value' => $matchType === 'exact' ? $matchPattern : "匹配模式: {$matchPattern}",
                    'new_value' => $newValue,
                    'replace_count' => $replaceCount,
                    'timestamp' => now()->format('Y-m-d H:i:s')
                ];
                
                // 只在需要时获取上下文（减少性能开销）
                if ($changes['count'] < 10) { // 只为前10个变更获取详细上下文
                    $changeDetail['before_context'] = $this->getContextLines($beforeContent, $matchType === 'exact' ? $matchPattern : $variableName);
                    $changeDetail['after_context'] = $this->getContextLines($newContent, $newValue);
                }
                
                $changes['details'][] = $changeDetail;
                $changes['count']++;
                $processedVariables[] = "{$variableName}({$replaceCount})";
            }
        }
        
        // 如果有变更，写入文件
        if ($hasChanges) {
            $writeResult = $this->writeFileContent($ssh, $filePath, $newContent);
            
            if ($writeResult) {
                // 只记录摘要信息，不记录每个文件的详细日志
                if (count($processedVariables) <= 3) {
                    $detail->addLog("✓ " . basename($filePath) . ": " . implode(', ', $processedVariables));
                } else {
                    $detail->addLog("✓ " . basename($filePath) . ": 修改了 " . count($processedVariables) . " 个变量");
                }
            }
        }
        
        return $changes;
    }

    /**
     * 获取包含指定字符串的上下文行（优化版）
     */
    private function getContextLines($content, $searchString, $contextLines = 2)
    {
        if (empty($content) || empty($searchString)) {
            return '';
        }
        
        // 限制内容长度，避免处理过大的文件
        if (strlen($content) > 10000) {
            $content = substr($content, 0, 10000) . "\n... (内容过长，已截断)";
        }
        
        $lines = explode("\n", $content);
        $totalLines = count($lines);
        
        // 限制行数，避免处理过大的文件
        if ($totalLines > 100) {
            $lines = array_slice($lines, 0, 100);
            $lines[] = "... (文件还有 " . ($totalLines - 100) . " 行)";
        }
        
        foreach ($lines as $index => $line) {
            if (strpos($line, $searchString) !== false) {
                $start = max(0, $index - $contextLines);
                $end = min(count($lines) - 1, $index + $contextLines);
                
                $result = [];
                for ($i = $start; $i <= $end; $i++) {
                    $lineNumber = $i + 1;
                    $lineContent = isset($lines[$i]) ? $lines[$i] : '';
                    
                    if ($i === $index) {
                        $result[] = ">>> {$lineNumber}: " . substr($lineContent, 0, 200);
                    } else {
                        $result[] = "    {$lineNumber}: " . substr($lineContent, 0, 200);
                    }
                }
                return implode("\n", $result);
            }
        }
        
        // 如果没有找到，返回文件的前3行
        $result = [];
        $maxLines = min(3, count($lines));
        for ($i = 0; $i < $maxLines; $i++) {
            $result[] = "    " . ($i + 1) . ": " . substr($lines[$i], 0, 200);
        }
        
        return implode("\n", $result);
    }

    /**
     * 应用变量替换
     */
    private function applyVariables($content, $variables)
    {
        if (empty($variables) || !is_array($variables)) {
            return $content;
        }
        
        foreach ($variables as $key => $value) {
            $content = str_replace("{{$key}}", $value, $content);
        }
        
        return $content;
    }

    /**
     * 应用任务变量替换
     */
    private function applyTaskVariables($content, $variables)
    {
        if (empty($variables) || !is_array($variables)) {
            return $content;
        }
        
        foreach ($variables as $key => $value) {
            $content = str_replace("{{$key}}", $value, $content);
        }
        
        return $content;
    }

    /**
     * 读取文件内容
     */
    private function readFileContent(SSH2 $ssh, $filePath)
    {
        $command = "cat '{$filePath}'";
        $content = $ssh->exec($command);
        
        if ($ssh->getExitStatus() !== 0) {
            throw new \Exception("读取文件失败: {$filePath}");
        }
        
        return $content;
    }

    /**
     * 应用配置变更
     */
    private function applyConfigChanges(SystemChangeTaskDetail $detail, $originalContent)
    {
        $template = $detail->template;
        $task = $detail->task;
        $configVariables = $task->config_variables ?? [];
        
        $newContent = $originalContent;
        
        // 获取规则数据
        $ruleData = json_decode($detail->rule_data, true);
        if (!$ruleData) {
            throw new \Exception("规则数据为空或格式错误");
        }
        
        $detail->addLog("开始应用配置变更，规则类型: {$detail->rule_type}");
        
        // 根据规则类型处理
        switch ($detail->rule_type) {
            case 'string':
                $newContent = $this->applyStringRule($ruleData, $originalContent, $configVariables, $detail);
                break;
                
            case 'file':
            case 'directory':
                // 对于文件和目录规则，应用变量替换
                $newContent = $this->applyVariableReplacements($ruleData, $originalContent, $configVariables, $detail);
                break;
                
            case 'legacy':
                // 兼容旧格式
                $newContent = $this->applyLegacyRule($ruleData, $originalContent, $configVariables, $detail);
                break;
                
            default:
                throw new \Exception("不支持的规则类型: {$detail->rule_type}");
        }
        
        return $newContent;
    }
    
    /**
     * 应用字符串规则
     */
    private function applyStringRule($ruleData, $originalContent, $configVariables, $detail)
    {
        $newContent = $originalContent;
        
        if (isset($ruleData['variables']) && is_array($ruleData['variables'])) {
            foreach ($ruleData['variables'] as $variable) {
                if (empty($variable['variable']) || !isset($configVariables[$variable['variable']])) {
                    continue;
                }
                
                $varName = $variable['variable'];
                $varValue = $configVariables[$varName];
                $matchType = $variable['match_type'] ?? 'key_value';
                
                if ($matchType === 'key_value') {
                    // 键值对替换
                    $pattern = '/^(\s*' . preg_quote($varName, '/') . '\s*[=:]\s*)(.*)$/m';
                    $replacement = '${1}' . $varValue;
                    $newContent = preg_replace($pattern, $replacement, $newContent);
                    $detail->addLog("应用变量替换: {$varName} = {$varValue}");
                } elseif ($matchType === 'placeholder') {
                    // 占位符替换
                    $placeholder = '{{' . $varName . '}}';
                    $newContent = str_replace($placeholder, $varValue, $newContent);
                    $detail->addLog("应用占位符替换: {$placeholder} -> {$varValue}");
                }
            }
        }
        
        return $newContent;
    }
    
    /**
     * 应用变量替换
     */
    private function applyVariableReplacements($ruleData, $originalContent, $configVariables, $detail)
    {
        $newContent = $originalContent;
        
        if (isset($ruleData['variables']) && is_array($ruleData['variables'])) {
            foreach ($ruleData['variables'] as $variable) {
                if (empty($variable['variable']) || !isset($configVariables[$variable['variable']])) {
                    $detail->addLog("跳过变量 {$variable['variable']}：未提供配置值");
                    continue;
                }
                
                $varName = $variable['variable'];
                $varValue = $configVariables[$varName];
                $matchType = $variable['match_type'] ?? 'placeholder';
                $matchPattern = $variable['match_pattern'] ?? '';
                
                $detail->addLog("处理变量 {$varName}，匹配类型: {$matchType}，匹配模式: {$matchPattern}，替换值: {$varValue}");
                
                switch ($matchType) {
                    case 'exact':
                        // 精确匹配替换
                        if (!empty($matchPattern)) {
                            $oldCount = substr_count($newContent, $matchPattern);
                            $newContent = str_replace($matchPattern, $varValue, $newContent);
                            
                            if ($oldCount > 0) {
                                $detail->addLog("精确匹配替换成功: '{$matchPattern}' -> '{$varValue}' (替换了 {$oldCount} 处)");
                            } else {
                                $detail->addLog("精确匹配未找到: '{$matchPattern}'");
                            }
                        }
                        break;
                        
                    case 'regex':
                        // 正则表达式替换
                        if (!empty($matchPattern)) {
                            $pattern = '/' . $matchPattern . '/';
                            $matches = [];
                            $matchCount = preg_match_all($pattern, $newContent, $matches);
                            
                            if ($matchCount > 0) {
                                $newContent = preg_replace($pattern, $varValue, $newContent);
                                $detail->addLog("正则匹配替换成功: 模式 '{$matchPattern}' -> '{$varValue}' (替换了 {$matchCount} 处)");
                            } else {
                                $detail->addLog("正则匹配未找到: 模式 '{$matchPattern}'");
                            }
                        }
                        break;
                        
                    case 'key_value':
                        // 键值对替换（如：key = value）
                        $pattern = '/^(\s*' . preg_quote($varName, '/') . '\s*[=:]\s*)(.*)$/m';
                        $matches = [];
                        $matchCount = preg_match_all($pattern, $newContent, $matches);
                        
                        if ($matchCount > 0) {
                            $replacement = '${1}' . $varValue;
                            $newContent = preg_replace($pattern, $replacement, $newContent);
                            $detail->addLog("键值对替换成功: {$varName} -> {$varValue} (替换了 {$matchCount} 处)");
                        } else {
                            $detail->addLog("键值对匹配未找到: {$varName}");
                        }
                        break;
                        
                    case 'placeholder':
                    default:
                        // 占位符替换（如：{{variable}}）
                        $placeholder = '{{' . $varName . '}}';
                        $oldCount = substr_count($newContent, $placeholder);
                        $newContent = str_replace($placeholder, $varValue, $newContent);
                        
                        if ($oldCount > 0) {
                            $detail->addLog("占位符替换成功: {$placeholder} -> {$varValue} (替换了 {$oldCount} 处)");
                        } else {
                            $detail->addLog("占位符未找到: {$placeholder}");
                        }
                        break;
                }
            }
        }
        
        return $newContent;
    }
    
    /**
     * 应用旧格式规则（向后兼容）
     */
    private function applyLegacyRule($ruleData, $originalContent, $configVariables, $detail)
    {
        $newContent = $originalContent;
        
        if (isset($ruleData['modifications']) && is_array($ruleData['modifications'])) {
            foreach ($ruleData['modifications'] as $modification) {
                $pattern = $modification['pattern'];
                $replacement = $modification['replacement'];
                
                // 替换变量
                foreach ($configVariables as $key => $value) {
                    $replacement = str_replace('{{' . $key . '}}', $value, $replacement);
                }
                
                // 检查是否还有未替换的变量
                if (preg_match('/\{\{(\w+)\}\}/', $replacement, $matches)) {
                    throw new \Exception("变量 '{$matches[1]}' 未提供值");
                }
                
                // 应用替换
                if ($modification['type'] === 'replace') {
                    $newContent = preg_replace('/' . $pattern . '/', $replacement, $newContent);
                    
                    if ($newContent === null) {
                        throw new \Exception("正则表达式替换失败: {$pattern}");
                    }
                }
                
                $detail->addLog("应用规则: {$pattern} -> {$replacement}");
            }
        }
        
        return $newContent;
    }

    /**
     * 写入文件内容（优化版）
     */
    private function writeFileContent(SSH2 $ssh, $filePath, $content)
    {
        // 使用临时文件写入，然后移动到目标位置
        $tempFile = $filePath . '.tmp.' . uniqid();
        
        // 写入临时文件
        $command = "cat > '{$tempFile}' << 'EOF'\n{$content}\nEOF";
        $result = $ssh->exec($command);
        
        if ($ssh->getExitStatus() !== 0) {
            throw new \Exception("写入临时文件失败");
        }
        
        // 移动到目标位置
        $command = "mv '{$tempFile}' '{$filePath}'";
        $ssh->exec($command);
        
        if ($ssh->getExitStatus() !== 0) {
            throw new \Exception("移动文件失败");
        }
        
        return true;
    }

    /**
     * 验证变更
     */
    private function validateChanges(SSH2 $ssh, SystemChangeTaskDetail $detail)
    {
        // 检查文件是否存在
        $command = "test -f '{$detail->config_file_path}'";
        $ssh->exec($command);
        
        if ($ssh->getExitStatus() !== 0) {
            throw new \Exception("配置文件不存在");
        }
        
        // 检查文件语法（针对特定文件类型）
        $this->validateFileSyntax($ssh, $detail->config_file_path);
        
        $detail->addLog("配置文件验证通过");
    }

    /**
     * 验证文件语法
     */
    private function validateFileSyntax(SSH2 $ssh, $filePath)
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        
        switch ($extension) {
            case 'conf':
                // Nginx配置文件
                if (strpos($filePath, 'nginx') !== false) {
                    $command = "nginx -t -c '{$filePath}' 2>&1";
                    $result = $ssh->exec($command);
                    
                    if ($ssh->getExitStatus() !== 0 && strpos($result, 'syntax is ok') === false) {
                        throw new \Exception("Nginx配置语法错误: {$result}");
                    }
                }
                break;
                
            case 'php':
                // PHP文件语法检查
                $command = "php -l '{$filePath}' 2>&1";
                $result = $ssh->exec($command);
                
                if ($ssh->getExitStatus() !== 0) {
                    throw new \Exception("PHP语法错误: {$result}");
                }
                break;
                
            case 'json':
                // JSON文件语法检查
                $command = "python -m json.tool '{$filePath}' > /dev/null 2>&1";
                $ssh->exec($command);
                
                if ($ssh->getExitStatus() !== 0) {
                    throw new \Exception("JSON格式错误");
                }
                break;
        }
    }

    /**
     * 回滚变更
     */
    public function rollbackChanges(SystemChangeTaskDetail $detail)
    {
        if (!$detail->canRollback()) {
            throw new \Exception("该任务详情不支持回滚");
        }

        $ssh = $this->connectToServer($detail->server);
        
        // 检查备份路径是文件还是目录
        $checkCommand = "test -d '{$detail->backup_path}'";
        $ssh->exec($checkCommand);
        $isDirectory = ($ssh->getExitStatus() === 0);
        
        // 根据类型选择合适的恢复命令
        if ($isDirectory) {
            // 目录恢复：从backup目录恢复内容
            $sourcePath = rtrim($detail->config_file_path, '/');
            
            // 清空目标目录内容（保留backup目录）
            $clearCommand = "find '{$sourcePath}' -mindepth 1 -not -path '{$sourcePath}/backup*' -delete";
            $ssh->exec($clearCommand);
            
            // 从备份恢复内容
            $command = "rsync -av '{$detail->backup_path}/' '{$sourcePath}/'";
            $detail->addLog("恢复目录内容: {$detail->backup_path} -> {$detail->config_file_path}");
        } else {
            // 文件恢复：从backup目录恢复文件
            $command = "cp '{$detail->backup_path}' '{$detail->config_file_path}'";
            $detail->addLog("恢复文件: {$detail->backup_path} -> {$detail->config_file_path}");
        }
        
        $result = $ssh->exec($command);
        
        if ($ssh->getExitStatus() !== 0) {
            throw new \Exception("回滚失败: {$result}");
        }
        
        $detail->update(['status' => 'rolled_back']);
        $detail->addLog("已回滚到原始配置");
        
        Log::info("任务详情已回滚", ['detail_id' => $detail->id]);
    }

    /**
     * 重试失败的任务详情
     */
    public function retryTaskDetail(SystemChangeTaskDetail $detail)
    {
        if (!$detail->canRetry()) {
            throw new \Exception("该任务详情不支持重试");
        }

        // 重置状态
        $detail->update([
            'status' => 'pending',
            'error_message' => null,
            'started_at' => null,
            'completed_at' => null
        ]);
        
        $detail->addLog("开始重试执行");
        
        // 重新执行
        $this->executeTaskDetail($detail);
        
        // 更新任务进度
        $detail->task->updateProgress();
    }

    /**
     * 获取任务执行日志
     */
    public function getTaskLogs(SystemChangeTask $task)
    {
        return $task->taskDetails()
            ->with(['server', 'template'])
            ->orderByExecution()
            ->get()
            ->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'server_name' => $detail->server->name,
                    'template_name' => $detail->template->name,
                    'file_path' => $detail->config_file_path,
                    'status' => $detail->status,
                    'execution_log' => $detail->execution_log,
                    'error_message' => $detail->error_message,
                    'execution_time' => $detail->formatted_execution_time,
                    'started_at' => $detail->started_at,
                    'completed_at' => $detail->completed_at
                ];
            });
    }

    /**
     * 清理过期的备份文件
     */
    public function cleanupBackups($days = 7)
    {
        $expiredDetails = SystemChangeTaskDetail::where('backup_created', true)
            ->where('created_at', '<', now()->subDays($days))
            ->get();

        foreach ($expiredDetails as $detail) {
            try {
                $ssh = $this->connectToServer($detail->server);
                
                if (!empty($detail->backup_path)) {
                    $command = "rm -f '{$detail->backup_path}'";
                    $ssh->exec($command);
                    
                    Log::info("已清理过期备份文件", [
                        'detail_id' => $detail->id,
                        'backup_path' => $detail->backup_path
                    ]);
                }
                
            } catch (\Exception $e) {
                Log::warning("清理备份文件失败", [
                    'detail_id' => $detail->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * 还原单个任务详情
     */
    public function revertTaskDetail(SystemChangeTaskDetail $detail, $revertedBy = null)
    {
        if (!$detail->canRevert()) {
            throw new \Exception('该任务详情不能还原');
        }

        $detail->addRevertLog("开始还原操作...");
        
        try {
            // 获取服务器连接
            $server = $detail->server;
            if (!$server) {
                throw new \Exception('服务器信息不存在');
            }

            $ssh = $this->connectToServer($server);
            $detail->addRevertLog("连接服务器成功: {$server->ip}");

            // 根据不同的还原方式进行处理
            if (!empty($detail->original_content)) {
                // 方式1：使用原始内容还原（单文件修改）
                $result = $this->revertUsingOriginalContent($ssh, $detail);
            } elseif ($detail->backup_created && !empty($detail->backup_path)) {
                // 方式2：使用备份文件还原（目录或文件备份）
                $result = $this->revertUsingBackupFile($ssh, $detail);
            } else {
                throw new \Exception('没有可用的还原数据');
            }

            // 标记为已还原
            $detail->revert($revertedBy);
            $detail->addRevertLog("还原操作完成");

            return $result;

        } catch (\Exception $e) {
            $detail->addRevertLog("还原失败: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 使用原始内容还原
     */
    private function revertUsingOriginalContent($ssh, SystemChangeTaskDetail $detail)
    {
        $filePath = $detail->config_file_path;
        $detail->addRevertLog("使用原始内容还原文件: {$filePath}");
        
        $this->writeFileContent($ssh, $filePath, $detail->original_content);
        $detail->addRevertLog("原始内容还原成功");
        
        return [
            'success' => true,
            'message' => '使用原始内容还原成功',
            'method' => 'original_content'
        ];
    }

    /**
     * 使用备份文件还原
     */
    private function revertUsingBackupFile($ssh, SystemChangeTaskDetail $detail)
    {
        $targetPath = $detail->config_file_path;
        $backupPath = $detail->backup_path;
        
        $detail->addRevertLog("使用备份文件还原: {$backupPath} -> {$targetPath}");
        
        // 检查备份路径是文件还是目录
        $checkCommand = "test -d '{$backupPath}'";
        $ssh->exec($checkCommand);
        $isDirectory = ($ssh->getExitStatus() === 0);
        
        if ($isDirectory) {
            // 目录恢复：从backup目录恢复内容
            $sourcePath = rtrim($targetPath, '/');
            
            // 清空目标目录内容（保留backup目录）
            $clearCommand = "find '{$sourcePath}' -mindepth 1 -not -path '{$sourcePath}/backup*' -delete";
            $ssh->exec($clearCommand);
            $detail->addRevertLog("清空目标目录内容");
            
            // 从备份恢复内容
            $command = "rsync -av '{$backupPath}/' '{$sourcePath}/'";
            $detail->addRevertLog("恢复目录内容: {$backupPath} -> {$targetPath}");
        } else {
            // 文件恢复：从backup目录恢复文件
            $command = "cp '{$backupPath}' '{$targetPath}'";
            $detail->addRevertLog("恢复文件: {$backupPath} -> {$targetPath}");
        }
        
        $result = $ssh->exec($command);
        
        if ($ssh->getExitStatus() !== 0) {
            throw new \Exception("备份文件还原失败: " . $result);
        }
        
        $detail->addRevertLog("备份文件还原成功");
        
        return [
            'success' => true,
            'message' => '使用备份文件还原成功',
            'method' => 'backup_file',
            'type' => $isDirectory ? 'directory' : 'file'
        ];
    }

    /**
     * 批量还原任务详情
     */
    public function batchRevertTaskDetails($detailIds, $revertedBy = null)
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($detailIds as $detailId) {
            try {
                $detail = SystemChangeTaskDetail::findOrFail($detailId);
                $this->revertTaskDetail($detail, $revertedBy);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "详情ID {$detailId}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * 还原单个文件
     */
    private function revertSingleFile($ssh, $filePath, $detail)
    {
        try {
            // 优先使用原始内容还原
            if (!empty($detail->original_content)) {
                $detail->addRevertLog("使用原始内容还原文件: {$filePath}");
                $this->writeFileContent($ssh, $filePath, $detail->original_content);
                return;
            }

            // 备选使用备份文件还原
            if (!empty($detail->backup_path)) {
                $detail->addRevertLog("使用备份文件还原: {$detail->backup_path} -> {$filePath}");
                
                // 检查备份路径是文件还是目录
                $checkCommand = "test -d '{$detail->backup_path}'";
                $ssh->exec($checkCommand);
                $isDirectory = ($ssh->getExitStatus() === 0);
                
                if ($isDirectory) {
                    // 目录恢复：从backup目录恢复内容
                    $sourcePath = rtrim($filePath, '/');
                    
                    // 清空目标目录内容（保留backup目录）
                    $clearCommand = "find '{$sourcePath}' -mindepth 1 -not -path '{$sourcePath}/backup*' -delete";
                    $ssh->exec($clearCommand);
                    
                    // 从备份恢复内容
                    $command = "rsync -av '{$detail->backup_path}/' '{$sourcePath}/'";
                    $detail->addRevertLog("恢复目录内容: {$detail->backup_path} -> {$filePath}");
                } else {
                    // 文件恢复：从backup目录恢复文件
                    $command = "cp '{$detail->backup_path}' '{$filePath}'";
                    $detail->addRevertLog("恢复文件: {$detail->backup_path} -> {$filePath}");
                }
                
                $result = $ssh->exec($command);
                
                if ($ssh->getExitStatus() !== 0) {
                    throw new \Exception("备份文件还原失败: " . $result);
                }
                
                $detail->addRevertLog("备份文件还原成功");
                return;
            }

            throw new \Exception("没有可用的还原数据（原始内容和备份文件都不存在）");

        } catch (\Exception $e) {
            $detail->addRevertLog("还原文件失败: {$filePath} - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 解析变更详情，提取文件路径信息
     */
    private function parseChangeDetails($executionLog)
    {
        $changes = [];
        
        if (empty($executionLog)) {
            return $changes;
        }

        // 解析执行日志，提取文件路径
        $lines = explode("\n", $executionLog);
        
        foreach ($lines as $line) {
            // 匹配文件处理日志
            if (preg_match('/✓\s+(.+?):\s+/', $line, $matches)) {
                $fileName = trim($matches[1]);
                $changes[] = ['file_path' => $fileName];
            }
            // 匹配文件写入成功日志
            elseif (preg_match('/文件写入成功:\s*(.+)/', $line, $matches)) {
                $filePath = trim($matches[1]);
                if (!in_array(['file_path' => $filePath], $changes)) {
                    $changes[] = ['file_path' => $filePath];
                }
            }
        }

        return $changes;
    }
}