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
        'completed_at'
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
            3 => '失败'
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
            3 => 'danger'
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
     * 判断是否正在执行
     */
    public function isRunning()
    {
        return $this->status == 1;
    }

    /**
     * 判断是否有结果
     */
    public function hasResult()
    {
        return !empty($this->result);
    }
}
