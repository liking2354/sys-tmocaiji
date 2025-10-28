<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CloudComputeResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'cloud_resource_id',
        'instance_id',
        'instance_name',
        'instance_type',
        'cpu_cores',
        'memory_gb',
        'os_type',
        'os_name',
        'image_id',
        'vpc_id',
        'subnet_id',
        'security_group_ids',
        'public_ip',
        'private_ip',
        'bandwidth_mbps',
        'disk_type',
        'disk_size_gb',
        'instance_status',
        'instance_charge_type',
        'expired_time',
        'created_time',
        'tags',
        'monitoring_enabled',
        'auto_scaling_enabled',
    ];

    protected $casts = [
        'cpu_cores' => 'integer',
        'memory_gb' => 'float',
        'bandwidth_mbps' => 'integer',
        'disk_size_gb' => 'integer',
        'security_group_ids' => 'array',
        'tags' => 'array',
        'monitoring_enabled' => 'boolean',
        'auto_scaling_enabled' => 'boolean',
        'expired_time' => 'datetime',
        'created_time' => 'datetime',
    ];

    /**
     * 获取关联的云资源
     */
    public function cloudResource(): BelongsTo
    {
        return $this->belongsTo(CloudResource::class, 'cloud_resource_id');
    }

    /**
     * 获取实例状态的中文名称
     */
    public function getInstanceStatusNameAttribute(): string
    {
        $statusMap = [
            'running' => '运行中',
            'stopped' => '已停止',
            'starting' => '启动中',
            'stopping' => '停止中',
            'rebooting' => '重启中',
            'pending' => '创建中',
            'terminated' => '已销毁',
            'unknown' => '未知',
        ];

        return $statusMap[$this->instance_status] ?? $this->instance_status;
    }

    /**
     * 获取操作系统类型的中文名称
     */
    public function getOsTypeNameAttribute(): string
    {
        $osTypeMap = [
            'linux' => 'Linux',
            'windows' => 'Windows',
            'unknown' => '未知',
        ];

        return $osTypeMap[$this->os_type] ?? $this->os_type;
    }

    /**
     * 获取计费类型的中文名称
     */
    public function getInstanceChargeTypeNameAttribute(): string
    {
        $chargeTypeMap = [
            'prepaid' => '包年包月',
            'postpaid' => '按量付费',
            'spot' => '竞价实例',
        ];

        return $chargeTypeMap[$this->instance_charge_type] ?? $this->instance_charge_type;
    }

    /**
     * 检查实例是否在运行
     */
    public function isRunning(): bool
    {
        return $this->instance_status === 'running';
    }

    /**
     * 检查实例是否已停止
     */
    public function isStopped(): bool
    {
        return $this->instance_status === 'stopped';
    }

    /**
     * 检查实例是否即将到期（7天内）
     */
    public function isExpiringSoon(): bool
    {
        if (!$this->expired_time || $this->instance_charge_type !== 'prepaid') {
            return false;
        }

        return $this->expired_time->diffInDays(now()) <= 7;
    }

    /**
     * 获取实例规格信息
     */
    public function getSpecificationAttribute(): string
    {
        return "{$this->cpu_cores}核{$this->memory_gb}GB";
    }

    /**
     * 作用域：按实例状态筛选
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('instance_status', $status);
    }

    /**
     * 作用域：按操作系统类型筛选
     */
    public function scopeByOsType($query, string $osType)
    {
        return $query->where('os_type', $osType);
    }

    /**
     * 作用域：按实例类型筛选
     */
    public function scopeByInstanceType($query, string $instanceType)
    {
        return $query->where('instance_type', $instanceType);
    }

    /**
     * 作用域：运行中的实例
     */
    public function scopeRunning($query)
    {
        return $query->where('instance_status', 'running');
    }

    /**
     * 作用域：即将到期的实例
     */
    public function scopeExpiringSoon($query)
    {
        return $query->where('instance_charge_type', 'prepaid')
                    ->where('expired_time', '<=', now()->addDays(7))
                    ->where('expired_time', '>', now());
    }
}