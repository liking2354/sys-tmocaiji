<?php

namespace App\Services;

use App\Models\OperationLog;
use App\Models\Server;
use App\Models\Collector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log as LaravelLog;

class LogService
{
    /**
     * 记录信息日志
     *
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return void
     */
    public function info(string $message, array $context = [])
    {
        LaravelLog::info($message, $context);
    }
    
    /**
     * 记录警告日志
     *
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return void
     */
    public function warning(string $message, array $context = [])
    {
        LaravelLog::warning($message, $context);
    }
    
    /**
     * 记录调试日志
     *
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return void
     */
    public function debug(string $message, array $context = [])
    {
        LaravelLog::debug($message, $context);
    }
    
    /**
     * 记录错误日志
     *
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return void
     */
    public function error(string $message, array $context = [])
    {
        LaravelLog::error($message, $context);
    }
    /**
     * 记录操作日志
     *
     * @param string $action 操作类型
     * @param string $module 模块名称
     * @param string $description 操作描述
     * @param array $data 相关数据
     * @return \App\Models\OperationLog
     */
    public function log(string $action, string $module, string $description, array $data = [])
    {
        try {
            $userId = Auth::id();
            
            $log = OperationLog::create([
                'user_id' => $userId, // 如果没有登录用户，则为null
                'action' => $action,
                'module' => $module,
                'description' => $description,
                'content' => $description, // 确保content字段有值
                'data' => json_encode($data),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            
            // 同时记录到Laravel日志
            LaravelLog::info("[{$action}] {$description}", $data);
            
            return $log;
        } catch (\Exception $e) {
            // 记录错误但不中断程序流程
            LaravelLog::error("记录操作日志失败: {$e->getMessage()}", [
                'action' => $action,
                'module' => $module,
                'description' => $description,
                'exception' => $e->getMessage(),
            ]);
            
            return null;
        }
    }
    
    /**
     * 记录采集组件安装日志
     *
     * @param Server $server 服务器
     * @param Collector $collector 采集组件
     * @param bool $success 是否成功
     * @param string $message 消息
     * @param array $data 额外数据
     * @return \App\Models\OperationLog
     */
    public function logCollectorInstall(Server $server, Collector $collector, bool $success, string $message, array $data = [])
    {
        $action = $success ? 'install_success' : 'install_failed';
        $description = "在服务器 {$server->name} ({$server->ip}) 上" . ($success ? '成功' : '失败') . "安装采集组件 {$collector->name} ({$collector->code})";
        
        $logData = [
            'server_id' => $server->id,
            'server_name' => $server->name,
            'server_ip' => $server->ip,
            'collector_id' => $collector->id,
            'collector_name' => $collector->name,
            'collector_code' => $collector->code,
            'collector_version' => $collector->version,
            'message' => $message,
        ];
        
        return $this->log($action, 'collector', $description, array_merge($logData, $data));
    }
    

    
    /**
     * 记录脚本执行日志
     *
     * @param Server $server 服务器
     * @param Collector $collector 采集组件
     * @param bool $success 是否成功
     * @param string $message 消息
     * @param array $data 额外数据
     * @return \App\Models\OperationLog
     */
    public function logScriptExecution(Server $server, Collector $collector, bool $success, string $message, array $data = [])
    {
        $action = $success ? 'script_execution_success' : 'script_execution_failed';
        $description = "在服务器 {$server->name} ({$server->ip}) 上" . ($success ? '成功' : '失败') . "执行采集组件 {$collector->name} ({$collector->code}): {$message}";
        
        $logData = [
            'server_id' => $server->id,
            'server_name' => $server->name,
            'server_ip' => $server->ip,
            'collector_id' => $collector->id,
            'collector_name' => $collector->name,
            'collector_code' => $collector->code,
            'collector_type' => $collector->type,
            'message' => $message,
        ];
        
        return $this->log($action, 'script_execution', $description, array_merge($logData, $data));
    }
    
    /**
     * 记录采集组件更新日志
     *
     * @param Server $server 服务器
     * @param Collector $collector 采集组件
     * @param bool $success 是否成功
     * @param string $message 消息
     * @param array $data 额外数据
     * @return \App\Models\OperationLog
     */
    public function logCollectorUpdate(Server $server, Collector $collector, bool $success, string $message, array $data = [])
    {
        $action = $success ? 'update_success' : 'update_failed';
        $description = "在服务器 {$server->name} ({$server->ip}) 上" . ($success ? '成功' : '失败') . "更新采集组件 {$collector->name} ({$collector->code})";
        
        $logData = [
            'server_id' => $server->id,
            'server_name' => $server->name,
            'server_ip' => $server->ip,
            'collector_id' => $collector->id,
            'collector_name' => $collector->name,
            'collector_code' => $collector->code,
            'collector_version' => $collector->version,
            'message' => $message,
        ];
        
        return $this->log($action, 'collector', $description, array_merge($logData, $data));
    }
    
    /**
     * 记录采集组件卸载日志
     *
     * @param Server $server 服务器
     * @param Collector $collector 采集组件
     * @param bool $success 是否成功
     * @param string $message 消息
     * @param array $data 额外数据
     * @return \App\Models\OperationLog
     */
    public function logCollectorUninstall(Server $server, Collector $collector, bool $success, string $message, array $data = [])
    {
        $action = $success ? 'uninstall_success' : 'uninstall_failed';
        $description = "在服务器 {$server->name} ({$server->ip}) 上" . ($success ? '成功' : '失败') . "卸载采集组件 {$collector->name} ({$collector->code})";
        
        $logData = [
            'server_id' => $server->id,
            'server_name' => $server->name,
            'server_ip' => $server->ip,
            'collector_id' => $collector->id,
            'collector_name' => $collector->name,
            'collector_code' => $collector->code,
            'message' => $message,
        ];
        
        return $this->log($action, 'collector', $description, array_merge($logData, $data));
    }
    
    /**
     * 记录采集任务详情执行日志
     *
     * @param int $taskId 任务ID
     * @param Server $server 服务器
     * @param Collector $collector 采集组件
     * @param bool $success 是否成功
     * @param string $message 消息
     * @param array $data 额外数据
     * @return \App\Models\OperationLog
     */
    public function logTaskDetailExecutionByIds(int $taskId, Server $server, Collector $collector, bool $success, string $message, array $data = [])
    {
        $action = $success ? 'task_success' : 'task_failed';
        $description = "任务 #{$taskId} 在服务器 {$server->name} ({$server->ip}) 上" . ($success ? '成功' : '失败') . "执行采集组件 {$collector->name} ({$collector->code})";
        
        $logData = [
            'task_id' => $taskId,
            'server_id' => $server->id,
            'server_name' => $server->name,
            'server_ip' => $server->ip,
            'collector_id' => $collector->id,
            'collector_name' => $collector->name,
            'collector_code' => $collector->code,
            'message' => $message,
        ];
        
        return $this->log($action, 'task', $description, array_merge($logData, $data));
    }
}