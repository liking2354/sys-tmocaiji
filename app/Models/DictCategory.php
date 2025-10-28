<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DictCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_code',
        'category_name',
        'description',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * 获取分类下的所有字典项
     */
    public function items(): HasMany
    {
        return $this->hasMany(DictItem::class, 'category_id');
    }

    /**
     * 获取分类下的活跃字典项
     */
    public function activeItems(): HasMany
    {
        return $this->items()->where('status', 'active');
    }

    /**
     * 作用域：只获取活跃的分类
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * 作用域：按排序顺序
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * 检查分类是否激活
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
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
}