<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;
    
    /**
     * 可批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];
    
    /**
     * 角色拥有的权限
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }
    
    /**
     * 拥有此角色的用户
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
