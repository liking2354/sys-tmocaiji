<?php

namespace App\Services;

use App\Models\Server;
use App\Models\Collector;
use App\Models\CollectionTask;
use App\Models\TaskDetail;
use App\Models\CollectionHistory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use phpseclib3\Net\SSH2;
use Exception;

class CollectionService
{
    /**
     * 构造函数
     */
    public function __construct()
    {
        // 移除JsonFormatterService依赖
    }
    /**
     * 执行单服务器采集
     *
     * @param Server $server
     * @param array $collectorIds
     * @return array
     */
    public function executeSingleCollection(Server $server, array $collectorIds)
    {
        $results = [];
        $success = 0;
        $failed = 0;

        foreach ($collectorIds as $collectorId) {
            $collector = Collector::find($collectorId);
            if (!$collector) {
                $failed++;
                $results[] = [
                    'collector_name' => "未知组件 (ID: {$collectorId})",
                    'success' => false,
                    'message' => '采集组件不存在',
                    'data' => null,
                    'execution_time' => 0
                ];
                continue;
            }

            try {
                $startTime = microtime(true);
                $result = $this->executeCollectorScript($server, $collector);
                $endTime = microtime(true);
                $executionTime = round($endTime - $startTime, 3);

                if ($result['success']) {
                    $success++;
                    
                    // 保存到采集历史
                    CollectionHistory::create([
                        'server_id' => $server->id,
                        'collector_id' => $collector->id,
                        'result' => $result['data'],
                        'status' => 2, // 成功
                        'execution_time' => $executionTime
                    ]);
                } else {
                    $failed++;
                    
                    // 保存失败记录
                    CollectionHistory::create([
                        'server_id' => $server->id,
                        'collector_id' => $collector->id,
                        'status' => 3, // 失败
                        'error_message' => $result['message'],
                        'execution_time' => $executionTime
                    ]);
                }

                $results[] = [
                    'collector_name' => $collector->name,
                    'success' => $result['success'],
                    'message' => $result['message'],
                    'data' => $result['data'] ?? null,
                    'execution_time' => $executionTime
                ];

            } catch (Exception $e) {
                $failed++;
                
                Log::error('单服务器采集异常', [
                    'server_id' => $server->id,
                    'collector_id' => $collector->id,
                    'error' => $e->getMessage()
                ]);

                $results[] = [
                    'collector_name' => $collector->name,
                    'success' => false,
                    'message' => '执行异常：' . $e->getMessage(),
                    'data' => null,
                    'execution_time' => 0
                ];
            }
        }

        return [
            'success' => $failed == 0,
            'message' => "采集完成：成功 {$success} 个，失败 {$failed} 个",
            'results' => $results,
            'summary' => [
                'total' => count($collectorIds),
                'success' => $success,
                'failed' => $failed
            ]
        ];
    }

    /**
     * 执行批量采集任务
     *
     * @param string $name
     * @param string $description
     * @param array $serverIds
     * @param array $collectorIds
     * @return array
     */
    public function executeBatchCollection(string $name, string $description, array $serverIds, array $collectorIds)
    {
        try {
            // 验证服务器和采集组件是否存在
            $servers = Server::whereIn('id', $serverIds)->get();
            $collectors = Collector::whereIn('id', $collectorIds)->get();

            if ($servers->count() != count($serverIds)) {
                return [
                    'success' => false,
                    'message' => '部分服务器不存在'
                ];
            }

            if ($collectors->count() != count($collectorIds)) {
                return [
                    'success' => false,
                    'message' => '部分采集组件不存在'
                ];
            }

            // 创建采集任务
            $task = CollectionTask::create([
                'name' => $name,
                'description' => $description,
                'type' => 'batch',
                'status' => 1, // 进行中
                'total_servers' => count($serverIds) * count($collectorIds), // 总的任务详情数量
                'created_by' => Auth::id() ?: 1, // 如果没有认证用户，使用默认用户ID 1
                'started_at' => now()
            ]);

            // 创建任务详情
            foreach ($serverIds as $serverId) {
                foreach ($collectorIds as $collectorId) {
                    TaskDetail::create([
                        'task_id' => $task->id,
                        'server_id' => $serverId,
                        'collector_id' => $collectorId,
                        'status' => 0 // 未开始
                    ]);
                }
            }

            // 异步执行任务（使用队列）
            dispatch(new \App\Jobs\ExecuteBatchCollectionJob($task->id));

            return [
                'success' => true,
                'message' => '批量采集任务创建成功',
                'task_id' => $task->id
            ];

        } catch (Exception $e) {
            Log::error('创建批量采集任务失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => '创建任务失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 执行采集组件脚本
     *
     * @param Server $server
     * @param Collector $collector
     * @return array
     */
    public function executeCollectorScript(Server $server, Collector $collector)
    {
        try {
            // 创建SSH连接
            $ssh = new SSH2($server->ip, $server->port);
            $ssh->setTimeout(30);

            if (!$ssh->login($server->username, $server->password)) {
                Log::warning('SSH连接失败 - 完整连接信息', [
                    'server_id' => $server->id,
                    'server_name' => $server->name,
                    'server_ip' => $server->ip,
                    'server_port' => $server->port,
                    'username' => $server->username,
                    'collector_id' => $collector->id,
                    'collector_name' => $collector->name,
                    'collector_code' => $collector->code,
                    'ssh_errors' => $ssh->getErrors(),
                    'ssh_log' => $ssh->getLog()
                ]);

                return [
                    'success' => false,
                    'message' => 'SSH连接失败',
                    'data' => null
                ];
            }

            // 根据采集组件类型执行不同的逻辑
            if ($collector->type === 'script') {
                // 脚本类：创建临时脚本文件并执行
                $scriptContent = $collector->getScriptContent();
                
                // 处理换行符问题：将Windows风格换行符转换为Unix风格
                $scriptContent = str_replace("\r\n", "\n", $scriptContent);
                $scriptContent = str_replace("\r", "\n", $scriptContent);
                
                // 创建临时脚本文件
                $tempScriptName = 'collector_' . $collector->id . '_' . time() . '.sh';
                $tempScriptPath = '/tmp/' . $tempScriptName;
                
                // 将脚本内容写入临时文件
                $ssh->exec('cat > ' . $tempScriptPath . ' << \'EOT\'' . PHP_EOL . $scriptContent . PHP_EOL . 'EOT');
                $ssh->exec('chmod +x ' . $tempScriptPath);
                
                // 执行脚本文件
                $output = $ssh->exec('bash ' . $tempScriptPath . ' 2>&1');
                
                // 清理临时文件
                $ssh->exec('rm -f ' . $tempScriptPath);
            } else {
                // 程序类：执行已安装的程序
                $programPath = "/opt/collectors/{$collector->code}/run.sh";
                
                // 检查程序是否存在
                $checkResult = $ssh->exec("test -f {$programPath} && echo 'exists' || echo 'not_found'");
                if (trim($checkResult) !== 'exists') {
                    Log::warning('采集程序未找到 - 完整路径信息', [
                        'server_id' => $server->id,
                        'server_name' => $server->name,
                        'server_ip' => $server->ip,
                        'collector_id' => $collector->id,
                        'collector_name' => $collector->name,
                        'collector_code' => $collector->code,
                        'program_path' => $programPath,
                        'check_result' => trim($checkResult),
                        'ls_result' => $ssh->exec("ls -la /opt/collectors/")
                    ]);
                    
                    return [
                        'success' => false,
                        'message' => '采集程序未安装或路径不存在',
                        'data' => null
                    ];
                }
                
                $output = $ssh->exec("bash {$programPath}");
            }

            // 打印完整的响应信息
            Log::info('采集组件执行完成 - 完整响应信息', [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'server_ip' => $server->ip,
                'collector_id' => $collector->id,
                'collector_name' => $collector->name,
                'collector_code' => $collector->code,
                'output_length' => strlen($output),
                'raw_output' => $output,
                'output_preview' => strlen($output) > 500 ? substr($output, 0, 500) . '...' : $output
            ]);

            // 解析输出结果
            $result = $this->parseCollectorOutput($output);

            Log::info('采集组件执行成功', [
                'server_id' => $server->id,
                'collector_id' => $collector->id,
                'output_length' => strlen($output),
                'parsed_result_keys' => is_array($result) ? array_keys($result) : 'not_array',
                'parsed_result_type' => gettype($result)
            ]);

            return [
                'success' => true,
                'message' => '采集成功',
                'data' => $result
            ];

        } catch (Exception $e) {
            Log::error('采集组件执行失败 - 完整错误信息', [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'server_ip' => $server->ip,
                'collector_id' => $collector->id,
                'collector_name' => $collector->name,
                'collector_code' => $collector->code,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => '执行失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 解析采集器输出
     *
     * @param string $output
     * @return array
     */
    private function parseCollectorOutput($output)
    {
        // 清理输出内容
        $output = trim($output);
        
        if (empty($output)) {
            return [
                'message' => '采集输出为空',
                'raw_output' => '',
                'parsed_at' => now()->toDateTimeString()
            ];
        }

        // 检查是否包含bash错误信息
        if (strpos($output, 'bash:') !== false || strpos($output, 'command not found') !== false || strpos($output, 'syntax error') !== false) {
            Log::warning('脚本执行错误', [
                'output' => $output,
                'error_type' => 'bash_error'
            ]);
            
            return [
                'error' => '脚本执行错误',
                'error_message' => $output,
                'raw_output' => $output,
                'parsed_at' => now()->toDateTimeString()
            ];
        }

        // 尝试解析JSON格式的输出
        $decoded = json_decode($output, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            // 如果能解析为JSON，返回格式化的JSON字符串
            $formattedJson = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            
            Log::info('JSON数据采集成功', [
                'keys' => array_keys($decoded),
                'size' => strlen($output),
                'formatted_size' => strlen($formattedJson)
            ]);
            
            return [
                'data_type' => 'json',
                'formatted_json' => $formattedJson,
                'parsed_data' => $decoded,
                'raw_output' => $output,
                'parsed_at' => now()->toDateTimeString()
            ];
        }

        // 如果无法解析为JSON，直接存储完整的原始响应信息
        Log::info('原始数据采集', [
            'type' => 'raw_output',
            'size' => strlen($output),
            'json_error' => json_last_error_msg()
        ]);
        
        return [
            'data_type' => 'raw',
            'raw_output' => $output,
            'parsed_at' => now()->toDateTimeString(),
            'note' => '无法解析为JSON格式，存储完整响应信息'
        ];
    }

    /**
     * 重新执行失败的任务
     *
     * @param CollectionTask $task
     * @return array
     */
    public function retryFailedTasks(CollectionTask $task)
    {
        $failedDetails = $task->taskDetails()->where('status', 3)->get();
        
        if ($failedDetails->isEmpty()) {
            return [
                'success' => false,
                'message' => '没有失败的任务需要重试'
            ];
        }

        $retried = 0;
        foreach ($failedDetails as $detail) {
            $detail->update([
                'status' => 0, // 重置为未开始
                'error_message' => null,
                'result' => null,
                'started_at' => null,
                'completed_at' => null
            ]);
            $retried++;
        }

        // 重新启动任务
        $task->update([
            'status' => 1, // 进行中
            'failed_servers' => $task->failed_servers - $retried,
            'completed_servers' => $task->completed_servers
        ]);

        // 异步执行重试任务
        dispatch(new \App\Jobs\ExecuteBatchCollectionJob($task->id));

        return [
            'success' => true,
            'message' => "已重新启动 {$retried} 个失败任务"
        ];
    }

    /**
     * 获取服务器共同的采集组件
     *
     * @param array $serverIds
     * @return array
     */
    public function getCommonCollectors(array $serverIds)
    {
        if (empty($serverIds)) {
            return [];
        }

        $servers = Server::whereIn('id', $serverIds)->with('collectors')->get();
        
        if ($servers->isEmpty()) {
            return [];
        }

        // 获取第一个服务器的采集组件
        $commonCollectors = $servers->first()->collectors;
        
        // 找出所有服务器共同的采集组件
        foreach ($servers as $server) {
            $commonCollectors = $commonCollectors->intersect($server->collectors);
        }

        return $commonCollectors->map(function ($collector) {
            return [
                'id' => $collector->id,
                'name' => $collector->name,
                'code' => $collector->code,
                'description' => $collector->description,
                'type' => $collector->type
            ];
        })->values()->toArray();
    }

    /**
     * 获取任务进度信息
     *
     * @param CollectionTask $task
     * @return array
     */
    public function getTaskProgress(CollectionTask $task)
    {
        $task->load('taskDetails');
        
        $statusCounts = $task->taskDetails->groupBy('status')->map(function ($items) {
            return $items->count();
        });

        return [
            'task_id' => $task->id,
            'status' => $task->status,
            'progress' => $task->progress,
            'total' => $task->total_servers,
            'pending' => $statusCounts->get(0, 0),
            'running' => $statusCounts->get(1, 0),
            'completed' => $statusCounts->get(2, 0),
            'failed' => $statusCounts->get(3, 0),
            'started_at' => $task->started_at,
            'completed_at' => $task->completed_at
        ];
    }
}
