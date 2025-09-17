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
     * 获取用户的操作日志
     */
    public function operationLogs()
    {
        return $this->hasMany(OperationLog::class);
    }
}