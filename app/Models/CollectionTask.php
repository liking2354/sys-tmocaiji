<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionTask extends Model
{
    use HasFactory;

    /**
     * 可批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'type',
        'status',
        'total_servers',
        'completed_servers',
        'failed_servers',
        'created_by',
        'started_at',
        'completed_at'
    ];

    /**
     * 应该被转换为日期的属性
     *
     * @var array
     */
    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * 关联任务详情
     */
    public function taskDetails()
    {
        return $this->hasMany(TaskDetail::class, 'task_id');
    }

    /**
     * 关联创建人
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 获取进度百分比
     */
    public function getProgressAttribute()
    {
        if ($this->total_servers == 0) return 0;
        return round(($this->completed_servers + $this->failed_servers) / $this->total_servers * 100, 2);
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
     * 获取类型文本
     */
    public function getTypeTextAttribute()
    {
        return $this->type === 'single' ? '单服务器' : '批量';
    }

    /**
     * 判断任务是否可以重试
     */
    public function canRetry()
    {
        return $this->status == 3 && $this->failed_servers > 0;
    }

    /**
     * 判断任务是否正在进行中
     */
    public function isRunning()
    {
        return $this->status == 1;
    }

    /**
     * 判断任务是否已完成
     */
    public function isCompleted()
    {
        return in_array($this->status, [2, 3]);
    }
}
