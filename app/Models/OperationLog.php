<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationLog extends Model
{
    use HasFactory;

    /**
     * 可批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'action',
        'content',
        'ip',
    ];

    /**
     * 应该被转换为日期的属性
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 获取操作类型的中文描述
     */
    public function getActionTextAttribute()
    {
        $actionMap = [
            'login' => '用户登录',
            'logout' => '用户登出',
            'create' => '创建',
            'update' => '更新',
            'delete' => '删除',
            'view' => '查看',
            'export' => '导出',
            'import' => '导入',
            'execute' => '执行',
            'install' => '安装',
            'uninstall' => '卸载',
            'verify' => '验证',
            'batch_operation' => '批量操作',
            'system_info' => '获取系统信息',
            'collection' => '数据采集',
            'cleanup' => '数据清理',
            'retry' => '重试',
            'cancel' => '取消',
            'reset' => '重置',
        ];

        return $actionMap[$this->action] ?? $this->action;
    }

    /**
     * 获取操作类型的颜色类
     */
    public function getActionColorAttribute()
    {
        $colorMap = [
            'login' => 'success',
            'logout' => 'secondary',
            'create' => 'primary',
            'update' => 'info',
            'delete' => 'danger',
            'view' => 'light',
            'export' => 'warning',
            'import' => 'warning',
            'execute' => 'dark',
            'install' => 'success',
            'uninstall' => 'danger',
            'verify' => 'info',
            'batch_operation' => 'primary',
            'system_info' => 'info',
            'collection' => 'success',
            'cleanup' => 'warning',
            'retry' => 'warning',
            'cancel' => 'secondary',
            'reset' => 'danger',
        ];

        return $colorMap[$this->action] ?? 'secondary';
    }

    /**
     * 获取用户名称（处理用户被删除的情况）
     */
    public function getUsernameAttribute()
    {
        return $this->user ? $this->user->username : '已删除用户';
    }

    /**
     * 记录操作日志
     *
     * @param string $action 操作类型
     * @param string $content 操作内容
     * @param int|null $userId 用户ID
     * @param string|null $ip IP地址
     * @return static
     */
    public static function record($action, $content, $userId = null, $ip = null)
    {
        return static::create([
            'user_id' => $userId ?: auth()->id(),
            'action' => $action,
            'content' => $content,
            'ip' => $ip ?: request()->ip(),
        ]);
    }

    /**
     * 按日期范围查询
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        return $query;
    }

    /**
     * 按操作类型查询
     */
    public function scopeAction($query, $action)
    {
        if ($action) {
            $query->where('action', $action);
        }
        return $query;
    }

    /**
     * 按用户查询
     */
    public function scopeUser($query, $userId)
    {
        if ($userId) {
            $query->where('user_id', $userId);
        }
        return $query;
    }

    /**
     * 按IP地址查询
     */
    public function scopeIp($query, $ip)
    {
        if ($ip) {
            $query->where('ip', 'like', "%{$ip}%");
        }
        return $query;
    }

    /**
     * 按内容搜索
     */
    public function scopeContent($query, $content)
    {
        if ($content) {
            $query->where('content', 'like', "%{$content}%");
        }
        return $query;
    }
}