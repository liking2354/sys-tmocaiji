<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * 可批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'status',
        'last_login_time',
        'theme_color',
        'sidebar_style',
    ];

    /**
     * 应该被隐藏的属性
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * 应该被转换为日期的属性
     *
     * @var array
     */
    protected $dates = [
        'last_login_time',
    ];
    
    /**
     * 获取用户创建的采集任务
     */
    public function collectionTasks()
    {
        return $this->hasMany(CollectionTask::class, 'created_by');
    }
    
    /**
     * 用户拥有的角色
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
    
    /**
     * 检查用户是否拥有指定角色
     *
     * @param string $role
     * @return bool
     */
    public function hasRole($role)
    {
        return $this->roles->contains('slug', $role);
    }
    
    /**
     * 检查用户是否拥有指定权限
     *
     * @param string $permission
     * @return bool
     */
    public function hasPermission($permission)
    {
        foreach ($this->roles as $role) {
            if ($role->permissions->contains('slug', $permission)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * 获取用户的操作日志
     */
    public function operationLogs()
    {
        return $this->hasMany(OperationLog::class);
    }
}