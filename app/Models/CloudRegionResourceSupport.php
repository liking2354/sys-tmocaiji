<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CloudRegionResourceSupport extends Model
{
    use HasFactory;

    protected $table = 'cloud_region_resource_support';

    protected $fillable = [
        'region_id',
        'resource_type',
        'is_supported',
        'limitations',
    ];

    protected $casts = [
        'is_supported' => 'boolean',
        'limitations' => 'array',
    ];

    /**
     * 获取所属区域
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(CloudRegion::class, 'region_id');
    }

    /**
     * 获取资源类型的中文名称
     */
    public function getResourceTypeNameAttribute(): string
    {
        $types = [
            'ecs' => '云主机',
            'clb' => '负载均衡',
            'cdb' => 'MySQL数据库',
            'redis' => 'Redis缓存',
            'domain' => '域名',
        ];

        return $types[$this->resource_type] ?? $this->resource_type;
    }

    /**
     * 获取支持状态的中文名称
     */
    public function getSupportStatusAttribute(): string
    {
        return $this->is_supported ? '支持' : '不支持';
    }

    /**
     * 作用域：只获取支持的资源类型
     */
    public function scopeSupported($query)
    {
        return $query->where('is_supported', true);
    }

    /**
     * 作用域：按资源类型筛选
     */
    public function scopeByResourceType($query, $resourceType)
    {
        return $query->where('resource_type', $resourceType);
    }

    /**
     * 作用域：按区域筛选
     */
    public function scopeByRegion($query, $regionId)
    {
        return $query->where('region_id', $regionId);
    }
}