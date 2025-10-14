<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskDetail extends Model
{
    use HasFactory;

    /**
     * 可批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'task_id',
        'server_id',
        'collector_id',
        'status',
        'result',
        'error_message',
        'execution_time',
        'started_at',
        'completed_at',
        'timeout_at',
        'retry_count',
        'max_retries'
    ];

    /**
     * 应该被转换为原生类型的属性
     *
     * @var array
     */
    protected $casts = [
        'result' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'timeout_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * 关联任务
     */
    public function task()
    {
        return $this->belongsTo(CollectionTask::class, 'task_id');
    }

    /**
     * 关联服务器
     */
    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * 关联采集组件
     */
    public function collector()
    {
        return $this->belongsTo(Collector::class);
    }

    /**
     * 关联采集历史
     */
    public function collectionHistory()
    {
        return $this->hasOne(CollectionHistory::class);
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute()
    {
        $statusMap = [
            0 => '未开始',
            1 => '进行中',
            2 => '已完成',
            3 => '失败',
            4 => '超时'
        ];
        return $statusMap[$this->status] ?? '未知';
    }

    /**
     * 获取状态颜色类
     */
    public function getStatusColorAttribute()
    {
        $colorMap = [
            0 => 'secondary',
            1 => 'warning',
            2 => 'success',
            3 => 'danger',
            4 => 'info'
        ];
        return $colorMap[$this->status] ?? 'secondary';
    }

    /**
     * 判断是否成功
     */
    public function isSuccess()
    {
        return $this->status == 2;
    }

    /**
     * 判断是否失败
     */
    public function isFailed()
    {
        return $this->status == 3;
    }

    /**
     * 判断是否超时
     */
    public function isTimeout()
    {
        return $this->status == 4;
    }

    /**
     * 判断是否正在执行
     */
    public function isRunning()
    {
        return $this->status == 1;
    }

    /**
     * 判断是否可以重试
     */
    public function canRetry()
    {
        return in_array($this->status, [3, 4]) && $this->retry_count < $this->max_retries;
    }

    /**
     * 判断是否需要重试
     */
    public function needsRetry()
    {
        return $this->canRetry() && ($this->isFailed() || $this->isTimeout());
    }

    /**
     * 判断是否有结果
     */
    public function hasResult()
    {
        return !empty($this->result);
    }

    /**
     * 获取重试信息
     */
    public function getRetryInfoAttribute()
    {
        return [
            'current' => $this->retry_count,
            'max' => $this->max_retries,
            'remaining' => max(0, $this->max_retries - $this->retry_count),
            'can_retry' => $this->canRetry()
        ];
    }
}
