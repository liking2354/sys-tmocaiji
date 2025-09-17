<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServerGroup extends Model
{
    use HasFactory;
    
    /**
     * 可批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
    ];
    
    /**
     * 获取该分组下的所有服务器
     */
    public function servers()
    {
        return $this->hasMany(Server::class, 'group_id');
    }
}