<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SystemChangeTask extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'server_group_id',
        'server_ids',
        'template_ids',
        'config_variables',
        'execution_order',
        'status',
        'progress',
        'total_servers',
        'completed_servers',
        'failed_servers',
        'scheduled_at',
        'started_at',
        'completed_at',
        'created_by'
    ];

    protected $casts = [
        'server_ids' => 'array',
        'template_ids' => 'array',
        'config_variables' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    /**
     * 状态常量
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_PAUSED = 'paused';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * 执行顺序常量
     */
    const ORDER_SEQUENTIAL = 'sequential';
    const ORDER_PARALLEL = 'parallel';

    /**
     * 关联服务器分组
     */
    public function serverGroup()
    {
        return $this->belongsTo(ServerGroup::class);
    }

    /**
     * 关联服务器（通过server_ids字段）
     */
    public function servers()
    {
        return $this->belongsToMany(Server::class, 'system_change_task_details', 'task_id', 'server_id')
                    ->whereIn('servers.id', $this->server_ids ?? []);
    }

    /**
     * 关联配置模板（通过template_ids字段）
     */
    public function templates()
    {
        return $this->belongsToMany(ConfigTemplate::class, 'system_change_task_details', 'task_id', 'template_id')
                    ->whereIn('config_templates.id', $this->template_ids ?? []);
    }

    /**
     * 关联任务详情
     */
    public function taskDetails()
    {
        return $this->hasMany(SystemChangeTaskDetail::class, 'task_id');
    }

    /**
     * 获取选中的服务器
     */
    public function getSelectedServersAttribute()
    {
        if (empty($this->server_ids)) {
            return collect();
        }
        
        return Server::whereIn('id', $this->server_ids)->get();
    }

    /**
     * 获取选中的模板
     */
    public function getSelectedTemplatesAttribute()
    {
        if (empty($this->template_ids)) {
            return collect();
        }
        
        return ConfigTemplate::whereIn('id', $this->template_ids)->get();
    }

    /**
     * 获取任务进度百分比
     */
    public function getProgressPercentageAttribute()
    {
        if ($this->total_servers == 0) {
            return 0;
        }
        
        return round(($this->completed_servers / $this->total_servers) * 100, 2);
    }

    /**
     * 获取剩余服务器数量
     */
    public function getRemainingServersAttribute()
    {
        return $this->total_servers - $this->completed_servers - $this->failed_servers;
    }

    /**
     * 检查任务是否可以执行
     */
    public function canExecute()
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING, self::STATUS_PAUSED]);
    }

    /**
     * 检查任务是否可以暂停
     */
    public function canPause()
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * 检查任务是否可以取消
     */
    public function canCancel()
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING, self::STATUS_RUNNING, self::STATUS_PAUSED]);
    }

    /**
     * 检查任务是否已完成
     */
    public function isCompleted()
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_CANCELLED]);
    }

    /**
     * 更新任务进度
     */
    public function updateProgress()
    {
        $this->completed_servers = $this->taskDetails()->where('status', 'completed')->count();
        $this->failed_servers = $this->taskDetails()->where('status', 'failed')->count();
        
        if ($this->total_servers > 0) {
            $this->progress = round(($this->completed_servers / $this->total_servers) * 100);
        }
        
        // 检查是否全部完成
        if ($this->completed_servers + $this->failed_servers >= $this->total_servers) {
            $this->status = $this->failed_servers > 0 ? self::STATUS_FAILED : self::STATUS_COMPLETED;
            $this->completed_at = now();
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
     * 范围查询：按服务器分组
     */
    public function scopeByServerGroup($query, $serverGroupId)
    {
        return $query->where('server_group_id', $serverGroupId);
    }

    /**
     * 范围查询：搜索
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * 范围查询：最近的任务
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}