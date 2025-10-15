<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemChangeTaskDetail extends Model
{
    use HasFactory;

    /**
     * 日志缓冲区（用于批量保存日志）
     */
    private $logBuffer = [];

    protected $fillable = [
        'task_id',
        'server_id',
        'server_ip',
        'server_name',
        'template_id',
        'config_file_path',
        'rule_type',
        'rule_data',
        'config_variables',
        'target_path',
        'original_content',
        'new_content',
        'status',
        'error_message',
        'execution_log',
        'execution_order',
        'backup_created',
        'backup_path',
        'started_at',
        'completed_at',
        'is_reverted',
        'reverted_at',
        'revert_log',
        'reverted_by'
    ];

    protected $casts = [
        'backup_created' => 'boolean',
        'is_reverted' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'reverted_at' => 'datetime',
        'config_variables' => 'array',
        'rule_data' => 'array'
    ];

    /**
     * 状态常量
     */
    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_SKIPPED = 'skipped';
    const STATUS_ROLLED_BACK = 'rolled_back';

    /**
     * 关联任务
     */
    public function task()
    {
        return $this->belongsTo(SystemChangeTask::class, 'task_id');
    }

    /**
     * 关联服务器
     */
    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * 关联配置模板
     */
    public function template()
    {
        return $this->belongsTo(ConfigTemplate::class, 'template_id');
    }

    /**
     * 获取执行耗时（秒）
     */
    public function getExecutionTimeAttribute()
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }
        
        return $this->completed_at->diffInSeconds($this->started_at);
    }

    /**
     * 获取格式化的执行耗时
     */
    public function getFormattedExecutionTimeAttribute()
    {
        $seconds = $this->execution_time;
        
        if ($seconds === null) {
            return '-';
        }
        
        if ($seconds < 60) {
            return $seconds . '秒';
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        return $minutes . '分' . $remainingSeconds . '秒';
    }

    /**
     * 检查是否可以回滚
     */
    public function canRollback()
    {
        return $this->status === self::STATUS_COMPLETED && 
               $this->backup_created && 
               !empty($this->backup_path);
    }

    /**
     * 检查是否可以重试
     */
    public function canRetry()
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_SKIPPED]);
    }

    /**
     * 添加执行日志
     */
    public function addLog($message, $autoSave = false)
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$message}";
        
        if (empty($this->execution_log)) {
            $this->execution_log = $logEntry;
        } else {
            $this->execution_log .= "\n" . $logEntry;
        }
        
        // 只有明确要求时才自动保存，否则需要手动调用 save()
        if ($autoSave) {
            $this->save();
        }
    }
    
    /**
     * 批量保存日志
     */
    public function saveLogs()
    {
        if (!empty($this->logBuffer)) {
            // 合并现有日志和缓冲区日志
            $existingLog = $this->execution_log ?? '';
            $newLogs = implode("\n", $this->logBuffer);
            
            $this->execution_log = $existingLog ? $existingLog . "\n" . $newLogs : $newLogs;
            $this->save();
            
            // 清空缓冲区
            $this->logBuffer = [];
        }
    }
    
    /**
     * 保存单条日志（向后兼容）
     */
    public function saveLog()
    {
        $this->save();
    }

    /**
     * 范围查询：按状态
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * 范围查询：按任务ID
     */
    public function scopeByTask($query, $taskId)
    {
        return $query->where('task_id', $taskId);
    }

    /**
     * 范围查询：按服务器ID
     */
    public function scopeByServer($query, $serverId)
    {
        return $query->where('server_id', $serverId);
    }

    /**
     * 范围查询：按执行顺序排序
     */
    public function scopeOrderByExecution($query)
    {
        return $query->orderBy('execution_order')->orderBy('id');
    }

    /**
     * 范围查询：失败的详情
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * 范围查询：成功的详情
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * 检查是否可以还原
     */
    public function canRevert()
    {
        // 必须是已完成且未还原的任务
        if ($this->status !== self::STATUS_COMPLETED || $this->is_reverted) {
            return false;
        }
        
        // 检查是否有实际的变更内容
        $hasChanges = false;
        
        // 方式1：有原始内容记录（单文件修改）
        if (!empty($this->original_content)) {
            $hasChanges = true;
        }
        
        // 方式2：有变更详情记录（目录批量修改）
        if (!empty($this->new_content)) {
            // 解析变更详情，检查是否有实际修改
            $changeDetails = json_decode($this->new_content, true);
            if (is_array($changeDetails) && !empty($changeDetails)) {
                $hasChanges = true;
            }
        }
        
        // 只有存在实际变更且有备份时才可还原
        return $hasChanges && 
               $this->backup_created && 
               !empty($this->backup_path);
    }

    /**
     * 执行还原操作
     */
    public function revert($revertedBy = null)
    {
        if (!$this->canRevert()) {
            throw new \Exception('该任务详情不能还原');
        }

        $this->update([
            'is_reverted' => true,
            'reverted_at' => now(),
            'reverted_by' => $revertedBy ?? 'system'
        ]);
    }

    /**
     * 添加还原日志
     */
    public function addRevertLog($message)
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$message}";
        
        $existingLog = $this->revert_log ?? '';
        $this->revert_log = $existingLog ? $existingLog . "\n" . $logEntry : $logEntry;
        $this->save();
    }

    /**
     * 范围查询：可还原的详情
     */
    public function scopeCanRevert($query)
    {
        return $query->where('status', self::STATUS_COMPLETED)
                    ->where('is_reverted', false)
                    ->where('backup_created', true)
                    ->whereNotNull('backup_path')
                    ->where(function($q) {
                        // 有原始内容记录（单文件修改）
                        $q->whereNotNull('original_content')
                          // 或有变更详情记录（目录批量修改）
                          ->orWhereNotNull('new_content');
                    });
    }

    /**
     * 范围查询：已还原的详情
     */
    public function scopeReverted($query)
    {
        return $query->where('is_reverted', true);
    }
}