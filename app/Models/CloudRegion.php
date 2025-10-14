<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CloudRegion extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform_id',
        'region_code',
        'region_name',
        'endpoint',
        'is_active',
        'description',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * 关联云平台
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(CloudPlatform::class, 'platform_id');
    }

    /**
     * 获取平台类型的中文名称
     */
    public function getPlatformNameAttribute(): string
    {
        if ($this->platform) {
            $names = [
                'huawei' => '华为云',
                'alibaba' => '阿里云',
                'tencent' => '腾讯云',
            ];
            return $names[$this->platform->platform_type] ?? $this->platform->platform_type;
        }
        return '';
    }

    /**
     * 获取状态的中文名称
     */
    public function getStatusNameAttribute(): string
    {
        return $this->is_active ? '可用' : '不可用';
    }

    /**
     * 检查区域是否可用
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * 作用域：只获取激活的区域
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 作用域：按平台ID筛选
     */
    public function scopeByPlatform($query, $platformId)
    {
        return $query->where('platform_id', $platformId);
    }

    /**
     * 作用域：按平台类型筛选
     */
    public function scopeByPlatformType($query, $platformType)
    {
        return $query->whereHas('platform', function ($q) use ($platformType) {
            $q->where('platform_type', $platformType);
        });
    }
}