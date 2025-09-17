<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionHistory extends Model
{
    use HasFactory;

    /**
     * 数据表名
     *
     * @var string
     */
    protected $table = 'collection_history';

    /**
     * 可批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'server_id',
        'collector_id',
        'task_detail_id',
        'result',
        'status',
        'error_message',
        'execution_time'
    ];

    /**
     * 应该被转换为原生类型的属性
     *
     * @var array
     */
    protected $casts = [
        'result' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

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
     * 关联任务详情
     */
    public function taskDetail()
    {
        return $this->belongsTo(TaskDetail::class);
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute()
    {
        $statusMap = [
            2 => '成功',
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
     * 判断是否有结果
     */
    public function hasResult()
    {
        return !empty($this->result);
    }

    /**
     * 作用域：成功的记录
     */
    public function scopeSuccess($query)
    {
        return $query->where('status', 2);
    }

    /**
     * 作用域：失败的记录
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 3);
    }

    /**
     * 作用域：指定服务器的记录
     */
    public function scopeForServer($query, $serverId)
    {
        return $query->where('server_id', $serverId);
    }

    /**
     * 作用域：指定采集组件的记录
     */
    public function scopeForCollector($query, $collectorId)
    {
        return $query->where('collector_id', $collectorId);
    }

    /**
     * 作用域：最近的记录
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
