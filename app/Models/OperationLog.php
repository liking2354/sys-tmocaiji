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
     * 获取执行操作的用户
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}