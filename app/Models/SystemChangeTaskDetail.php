<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemChangeTaskDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'server_id',
        'template_id',
        'config_file_path',
        'original_content',
        'new_content',
        'status',
        'error_message',
        'execution_log',
        'execution_order',
        'backup_created',
        'backup_path',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'backup_created' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
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
    public function addLog($message)
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$message}";
        
        if (empty($this->execution_log)) {
            $this->execution_log = $logEntry;
        } else {
            $this->execution_log .= "\n" . $logEntry;
        }
        
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
}