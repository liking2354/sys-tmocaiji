<?php

namespace App\Services;

use App\Models\OperationLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class OperationLogService
{
    /**
     * 记录操作日志
     *
     * @param string $action 操作类型
     * @param string $content 操作内容
     * @param int|null $userId 用户ID
     * @param string|null $ip IP地址
     * @return OperationLog
     */
    public static function log($action, $content, $userId = null, $ip = null)
    {
        return OperationLog::create([
            'user_id' => $userId ?: Auth::id(),
            'action' => $action,
            'content' => $content,
            'ip' => $ip ?: request()->ip(),
        ]);
    }

    /**
     * 记录登录日志
     *
     * @param int $userId
     * @param string $ip
     * @param bool $success
     * @return OperationLog
     */
    public static function logLogin($userId, $ip, $success = true)
    {
        $action = $success ? 'login' : 'login_failed';
        $content = $success ? '用户登录成功' : '用户登录失败';
        
        return static::log($action, $content, $userId, $ip);
    }

    /**
     * 记录登出日志
     *
     * @param int $userId
     * @param string $ip
     * @return OperationLog
     */
    public static function logLogout($userId, $ip)
    {
        return static::log('logout', '用户登出', $userId, $ip);
    }

    /**
     * 记录创建操作
     *
     * @param string $model 模型名称
     * @param mixed $modelId 模型ID
     * @param array $data 创建的数据
     * @return OperationLog
     */
    public static function logCreate($model, $modelId, $data = [])
    {
        $content = "创建了{$model}，ID: {$modelId}";
        if (!empty($data)) {
            $content .= "，数据: " . json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        
        return static::log('create', $content);
    }

    /**
     * 记录更新操作
     *
     * @param string $model 模型名称
     * @param mixed $modelId 模型ID
     * @param array $oldData 原始数据
     * @param array $newData 新数据
     * @return OperationLog
     */
    public static function logUpdate($model, $modelId, $oldData = [], $newData = [])
    {
        $content = "更新了{$model}，ID: {$modelId}";
        
        if (!empty($oldData) && !empty($newData)) {
            $changes = [];
            foreach ($newData as $key => $value) {
                if (isset($oldData[$key]) && $oldData[$key] != $value) {
                    $changes[$key] = [
                        'from' => $oldData[$key],
                        'to' => $value
                    ];
                }
            }
            if (!empty($changes)) {
                $content .= "，变更: " . json_encode($changes, JSON_UNESCAPED_UNICODE);
            }
        }
        
        return static::log('update', $content);
    }

    /**
     * 记录删除操作
     *
     * @param string $model 模型名称
     * @param mixed $modelId 模型ID
     * @param array $data 删除的数据
     * @return OperationLog
     */
    public static function logDelete($model, $modelId, $data = [])
    {
        $content = "删除了{$model}，ID: {$modelId}";
        if (!empty($data)) {
            $content .= "，数据: " . json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        
        return static::log('delete', $content);
    }

    /**
     * 记录批量操作
     *
     * @param string $action 操作类型
     * @param string $model 模型名称
     * @param array $ids ID数组
     * @param array $data 额外数据
     * @return OperationLog
     */
    public static function logBatchOperation($action, $model, $ids, $data = [])
    {
        $content = "批量{$action}了{$model}，数量: " . count($ids) . "，IDs: " . implode(',', $ids);
        if (!empty($data)) {
            $content .= "，数据: " . json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        
        return static::log('batch_operation', $content);
    }

    /**
     * 记录服务器操作
     *
     * @param string $action 操作类型
     * @param int $serverId 服务器ID
     * @param string $serverName 服务器名称
     * @param array $data 额外数据
     * @return OperationLog
     */
    public static function logServerOperation($action, $serverId, $serverName, $data = [])
    {
        $content = "对服务器 {$serverName}(ID: {$serverId}) 执行了{$action}操作";
        if (!empty($data)) {
            $content .= "，详情: " . json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        
        return static::log($action, $content);
    }

    /**
     * 记录采集任务操作
     *
     * @param string $action 操作类型
     * @param int $taskId 任务ID
     * @param string $taskName 任务名称
     * @param array $data 额外数据
     * @return OperationLog
     */
    public static function logCollectionTask($action, $taskId, $taskName, $data = [])
    {
        $content = "对采集任务 {$taskName}(ID: {$taskId}) 执行了{$action}操作";
        if (!empty($data)) {
            $content .= "，详情: " . json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        
        return static::log($action, $content);
    }

    /**
     * 记录文件操作
     *
     * @param string $action 操作类型 (upload, download, delete)
     * @param string $filename 文件名
     * @param string $path 文件路径
     * @param array $data 额外数据
     * @return OperationLog
     */
    public static function logFileOperation($action, $filename, $path, $data = [])
    {
        $content = "对文件 {$filename} 执行了{$action}操作，路径: {$path}";
        if (!empty($data)) {
            $content .= "，详情: " . json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        
        return static::log($action, $content);
    }

    /**
     * 记录导入导出操作
     *
     * @param string $action 操作类型 (import, export)
     * @param string $type 导入导出类型
     * @param int $count 记录数量
     * @param array $data 额外数据
     * @return OperationLog
     */
    public static function logImportExport($action, $type, $count, $data = [])
    {
        $actionText = $action === 'import' ? '导入' : '导出';
        $content = "{$actionText}了{$type}，数量: {$count}";
        if (!empty($data)) {
            $content .= "，详情: " . json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        
        return static::log($action, $content);
    }

    /**
     * 记录系统操作
     *
     * @param string $action 操作类型
     * @param string $description 操作描述
     * @param array $data 额外数据
     * @return OperationLog
     */
    public static function logSystemOperation($action, $description, $data = [])
    {
        $content = $description;
        if (!empty($data)) {
            $content .= "，详情: " . json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        
        return static::log($action, $content);
    }

    /**
     * 记录API调用
     *
     * @param string $endpoint API端点
     * @param string $method HTTP方法
     * @param array $params 请求参数
     * @param int $responseCode 响应代码
     * @return OperationLog
     */
    public static function logApiCall($endpoint, $method, $params = [], $responseCode = 200)
    {
        $content = "调用API: {$method} {$endpoint}，响应码: {$responseCode}";
        if (!empty($params)) {
            $content .= "，参数: " . json_encode($params, JSON_UNESCAPED_UNICODE);
        }
        
        return static::log('api_call', $content);
    }

    /**
     * 记录错误日志
     *
     * @param string $error 错误信息
     * @param string $context 错误上下文
     * @param array $data 额外数据
     * @return OperationLog
     */
    public static function logError($error, $context = '', $data = [])
    {
        $content = "发生错误: {$error}";
        if ($context) {
            $content .= "，上下文: {$context}";
        }
        if (!empty($data)) {
            $content .= "，详情: " . json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        
        return static::log('error', $content);
    }

    /**
     * 记录安全相关操作
     *
     * @param string $action 操作类型
     * @param string $description 操作描述
     * @param array $data 额外数据
     * @return OperationLog
     */
    public static function logSecurityOperation($action, $description, $data = [])
    {
        $content = "安全操作: {$description}";
        if (!empty($data)) {
            $content .= "，详情: " . json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        
        return static::log('security_' . $action, $content);
    }
}