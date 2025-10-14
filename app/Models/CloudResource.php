<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CloudResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'resource_id',
        'resource_type',
        'name',
        'status',
        'region',
        'platform_id',
        'user_id',
        'raw_data',
        'metadata',
        'last_sync_at',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'metadata' => 'array',
        'last_sync_at' => 'datetime',
    ];

    /**
     * 获取资源所属平台
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(CloudPlatform::class, 'platform_id');
    }

    /**
     * 获取资源所属用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
     * 获取资源状态的中文名称
     */
    public function getStatusNameAttribute(): string
    {
        // 通用状态映射
        $commonStatuses = [
            'running' => '运行中',
            'stopped' => '已停止',
            'starting' => '启动中',
            'stopping' => '停止中',
            'rebooting' => '重启中',
            'active' => '正常',
            'inactive' => '异常',
            'pending' => '处理中',
            'available' => '可用',
            'unavailable' => '不可用',
        ];

        return $commonStatuses[strtolower($this->status)] ?? $this->status;
    }

    /**
     * 获取同步状态
     */
    public function getSyncStatusAttribute(): string
    {
        if (!$this->last_sync_at) {
            return '未同步';
        }

        $diffInMinutes = $this->last_sync_at->diffInMinutes(now());
        
        if ($diffInMinutes < 30) {
            return '已同步';
        } elseif ($diffInMinutes < 60) {
            return '需要同步';
        } else {
            return '同步过期';
        }
    }

    /**
     * 获取资源的关键信息
     */
    public function getKeyInfoAttribute(): array
    {
        $rawData = $this->raw_data ?? [];
        
        switch ($this->resource_type) {
            case 'ecs':
                return [
                    'cpu' => $rawData['cpu'] ?? '-',
                    'memory' => $rawData['memory'] ?? '-',
                    'disk' => $rawData['disk'] ?? '-',
                    'ip' => $rawData['public_ip'] ?? $rawData['private_ip'] ?? '-',
                ];
            case 'clb':
                return [
                    'type' => $rawData['type'] ?? '-',
                    'vip' => $rawData['vip'] ?? '-',
                    'listeners' => count($rawData['listeners'] ?? []),
                ];
            case 'cdb':
                return [
                    'engine' => $rawData['engine'] ?? '-',
                    'version' => $rawData['version'] ?? '-',
                    'storage' => $rawData['storage'] ?? '-',
                ];
            case 'redis':
                return [
                    'version' => $rawData['version'] ?? '-',
                    'memory' => $rawData['memory'] ?? '-',
                    'mode' => $rawData['mode'] ?? '-',
                ];
            case 'domain':
                return [
                    'registrar' => $rawData['registrar'] ?? '-',
                    'expire_date' => $rawData['expire_date'] ?? '-',
                    'status' => $rawData['status'] ?? '-',
                ];
            default:
                return [];
        }
    }

    /**
     * 作用域：只获取当前用户的资源
     */
    public function scopeForUser($query, $userId = null)
    {
        $userId = $userId ?: auth()->id();
        
        // 如果是管理员，返回所有数据（避免依赖 hasRole）
        $user = auth()->user();
        $isAdmin = $user && ($user->id === 1 || (property_exists($user, 'username') && $user->username === 'admin'));
        if ($isAdmin) {
            return $query;
        }

        return $query->where('user_id', $userId);
    }

    /**
     * 作用域：按资源类型筛选
     */
    public function scopeByResourceType($query, $resourceType)
    {
        return $query->where('resource_type', $resourceType);
    }

    /**
     * 作用域：按平台筛选
     */
    public function scopeByPlatform($query, $platformId)
    {
        return $query->where('platform_id', $platformId);
    }

    /**
     * 作用域：按区域筛选
     */
    public function scopeByRegion($query, $region)
    {
        return $query->where('region', $region);
    }

    /**
     * 作用域：按状态筛选
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * 作用域：需要同步的资源
     */
    public function scopeNeedSync($query, $minutes = 30)
    {
        return $query->where(function ($q) use ($minutes) {
            $q->whereNull('last_sync_at')
              ->orWhere('last_sync_at', '<', now()->subMinutes($minutes));
        });
    }
}