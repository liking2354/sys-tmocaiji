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
                foreach ($template->config_items as $configItem) {
                    SystemChangeTaskDetail::create([
                        'task_id' => $task->id,
                        'server_id' => $server->id,
                        'template_id' => $template->id,
                        'config_file_path' => $configItem['file_path'],
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

        // 更新任务状态
        $task->update([
            'status' => SystemChangeTask::STATUS_RUNNING,
            'started_at' => now()
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
            ->orderByExecution()
            ->get();

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

            $this->executeTaskDetail($detail);
            $task->updateProgress();
        }

        // 检查任务是否完成
        if ($task->status === SystemChangeTask::STATUS_RUNNING) {
            $task->update([
                'status' => SystemChangeTask::STATUS_COMPLETED,
                'completed_at' => now()
            ]);
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
            
            // 备份原始文件
            $this->backupOriginalFile($ssh, $detail);
            
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
        $ssh = new SSH2($server->hostname, $server->port ?? 22);
        
        if ($server->auth_type === 'password') {
            if (!$ssh->login($server->username, $server->password)) {
                throw new \Exception("SSH密码认证失败: {$server->hostname}");
            }
        } elseif ($server->auth_type === 'key') {
            $key = PublicKeyLoader::load($server->private_key, $server->private_key_password ?? '');
            if (!$ssh->login($server->username, $key)) {
                throw new \Exception("SSH密钥认证失败: {$server->hostname}");
            }
        } else {
            throw new \Exception("不支持的认证类型: {$server->auth_type}");
        }
        
        return $ssh;
    }

    /**
     * 备份原始文件
     */
    private function backupOriginalFile(SSH2 $ssh, SystemChangeTaskDetail $detail)
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupPath = $detail->config_file_path . ".backup_{$timestamp}";
        
        $command = "cp '{$detail->config_file_path}' '{$backupPath}'";
        $result = $ssh->exec($command);
        
        if ($ssh->getExitStatus() !== 0) {
            throw new \Exception("备份文件失败: {$result}");
        }
        
        $detail->update([
            'backup_created' => true,
            'backup_path' => $backupPath
        ]);
        
        $detail->addLog("已创建备份文件: {$backupPath}");
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
        
        // 找到对应的配置项
        $configItem = null;
        foreach ($template->config_items as $item) {
            if ($item['file_path'] === $detail->config_file_path) {
                $configItem = $item;
                break;
            }
        }
        
        if (!$configItem) {
            throw new \Exception("未找到对应的配置项");
        }
        
        // 应用所有修改规则
        foreach ($configItem['modifications'] as $modification) {
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
        
        return $newContent;
    }

    /**
     * 写入文件内容
     */
    private function writeFileContent(SSH2 $ssh, $filePath, $content)
    {
        // 使用临时文件写入，然后移动到目标位置
        $tempFile = $filePath . '.tmp.' . uniqid();
        
        // 写入临时文件
        $command = "cat > '{$tempFile}' << 'EOF'\n{$content}\nEOF";
        $result = $ssh->exec($command);
        
        if ($ssh->getExitStatus() !== 0) {
            throw new \Exception("写入临时文件失败: {$result}");
        }
        
        // 移动到目标位置
        $command = "mv '{$tempFile}' '{$filePath}'";
        $result = $ssh->exec($command);
        
        if ($ssh->getExitStatus() !== 0) {
            throw new \Exception("移动文件失败: {$result}");
        }
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
        
        // 恢复备份文件
        $command = "cp '{$detail->backup_path}' '{$detail->config_file_path}'";
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
}