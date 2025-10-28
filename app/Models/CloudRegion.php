<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CloudRegion extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform_type',
        'region_code',
        'region_name',
        'region_name_en',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * 获取平台类型的中文名称
     */
    public function getPlatformNameAttribute(): string
    {
        $names = [
            'huawei' => '华为云',
            'alibaba' => '阿里云',
            'tencent' => '腾讯云',
        ];
        return $names[$this->platform_type] ?? $this->platform_type;
    }

    /**
     * 根据平台类型筛选
     */
    public function scopeByPlatformType(Builder $query, string $platformType): Builder
    {
        return $query->where('platform_type', $platformType);
    }

    /**
     * 只获取启用的区域
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * 获取所有支持的平台类型
     */
    public static function getSupportedPlatformTypes(): array
    {
        return ['huawei', 'alibaba', 'tencent'];
    }

    /**
     * 获取平台类型选项
     */
    public static function getPlatformTypeOptions(): array
    {
        return [
            'huawei' => '华为云',
            'alibaba' => '阿里云',
            'tencent' => '腾讯云',
        ];
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
     * 获取指定平台类型的活跃区域列表
     */
    public static function getActiveRegionsByPlatform(string $platformType): Collection
    {
        return static::where('platform_type', $platformType)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}