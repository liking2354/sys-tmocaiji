<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
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
        'module',
    ];
    
    /**
     * 拥有此权限的角色
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
