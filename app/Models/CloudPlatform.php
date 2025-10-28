<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CloudPlatform extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'platform_type',
        'access_key_id',
        'access_key_secret',
        'region',
        'user_id',
        'status',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    protected $hidden = [
        'access_key_secret',
    ];

    protected $appends = [
        'platform_name',
        'status_name',
    ];

    /**
     * 获取平台所属用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 获取平台下的所有资源
     */
    public function resources(): HasMany
    {
        return $this->hasMany(CloudResource::class, 'platform_id');
    }

    /**
     * 获取平台下的所有云资源 (别名方法)
     */
    public function cloudResources(): HasMany
    {
        return $this->hasMany(CloudResource::class, 'platform_id');
    }

    /**
     * 获取平台下的所有可用区
     */
    public function regions(): HasMany
    {
        return $this->hasMany(CloudRegion::class, 'platform_id');
    }

    /**
     * 获取平台下的所有组件配置
     */
    public function components(): HasMany
    {
        return $this->hasMany(CloudPlatformComponent::class, 'platform_id');
    }

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
     * 获取状态的中文名称
     */
    public function getStatusNameAttribute(): string
    {
        $statuses = [
            'active' => '启用',
            'inactive' => '禁用',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * 检查平台是否激活
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * 获取脱敏后的访问密钥
     */
    public function getMaskedAccessKeySecretAttribute(): string
    {
        if (empty($this->access_key_secret)) {
            return '';
        }

        $length = strlen($this->access_key_secret);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($this->access_key_secret, 0, 4) . str_repeat('*', $length - 8) . substr($this->access_key_secret, -4);
    }

    /**
     * 作用域：只获取当前用户的平台
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
     * 作用域：只获取激活的平台
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * 作用域：按平台类型筛选
     */
    public function scopeByPlatformType($query, $platformType)
    {
        return $query->where('platform_type', $platformType);
    }
}