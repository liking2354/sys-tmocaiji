<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CloudPlatformComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform_id',
        'component_dict_id',
        'is_enabled',
        'sync_priority',
        'config',
        'last_sync_at',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'sync_priority' => 'integer',
        'config' => 'array',
        'last_sync_at' => 'datetime',
    ];

    /**
     * 获取组件所属的云平台
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(CloudPlatform::class, 'platform_id');
    }

    /**
     * 获取组件类型字典项
     */
    public function componentDict(): BelongsTo
    {
        return $this->belongsTo(DictItem::class, 'component_dict_id');
    }

    /**
     * 作用域：只获取启用的组件
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * 作用域：按同步优先级排序
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('sync_priority', 'desc');
    }

    /**
     * 作用域：按平台筛选
     */
    public function scopeByPlatform($query, $platformId)
    {
        return $query->where('platform_id', $platformId);
    }

    /**
     * 检查组件是否启用
     */
    public function isEnabled(): bool
    {
        return $this->is_enabled;
    }

    /**
     * 获取组件名称
     */
    public function getComponentNameAttribute(): string
    {
        return $this->componentDict->item_name ?? '';
    }

    /**
     * 获取组件代码
     */
    public function getComponentCodeAttribute(): string
    {
        return $this->componentDict->item_code ?? '';
    }

    /**
     * 获取配置项值
     */
    public function getConfig($key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * 设置配置项值
     */
    public function setConfig($key, $value): void
    {
        $config = $this->config ?? [];
        $config[$key] = $value;
        $this->config = $config;
    }

    /**
     * 更新最后同步时间
     */
    public function updateLastSyncTime(): void
    {
        $this->update(['last_sync_at' => now()]);
    }
}