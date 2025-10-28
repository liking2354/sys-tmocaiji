<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DictItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'item_code',
        'item_name',
        'item_value',
        'parent_id',
        'sort_order',
        'attributes',
        'status',
        'level',
        'platform_type',
        'metadata',
    ];

    protected $casts = [
        'attributes' => 'json',
        'metadata' => 'json',
        'sort_order' => 'integer',
        'level' => 'integer',
    ];

    protected $appends = [
        'status_name',
    ];

    /**
     * 获取字典项所属的分类
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(DictCategory::class, 'category_id');
    }

    /**
     * 获取父级字典项
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(DictItem::class, 'parent_id');
    }

    /**
     * 获取子级字典项
     */
    public function children(): HasMany
    {
        return $this->hasMany(DictItem::class, 'parent_id');
    }

    /**
     * 获取活跃的子级字典项
     */
    public function activeChildren(): HasMany
    {
        return $this->children()->where('status', 'active');
    }

    /**
     * 作用域：只获取活跃的字典项
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
     * 作用域：根分类项（没有父级）
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * 作用域：按分类筛选
     */
    public function scopeByCategory($query, $categoryCode)
    {
        return $query->whereHas('category', function ($q) use ($categoryCode) {
            $q->where('category_code', $categoryCode);
        });
    }

    /**
     * 作用域：按层级筛选
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * 作用域：按平台类型筛选
     */
    public function scopeByPlatform($query, $platformType)
    {
        return $query->where('platform_type', $platformType);
    }

    /**
     * 检查字典项是否激活
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

    /**
     * 获取完整路径（包含父级名称）
     */
    public function getFullPathAttribute(): string
    {
        $path = [$this->item_name];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($path, $parent->item_name);
            $parent = $parent->parent;
        }
        
        return implode(' > ', $path);
    }

    /**
     * 获取扩展属性值
     */
    public function getAttributeValue($key, $default = null)
    {
        $attributes = $this->attributes ?? [];
        if (isset($attributes[$key])) {
            return $attributes[$key];
        }
        
        return $default;
    }

    /**
     * 设置扩展属性值
     */
    public function setAttributeValue($key, $value): void
    {
        $attributes = $this->attributes ?? [];
        $attributes[$key] = $value;
        $this->attributes = $attributes;
    }

    /**
     * 获取指定平台的子项
     */
    public function getChildrenByPlatform($platformType = null)
    {
        $query = $this->children()->active()->ordered();
        
        if ($platformType) {
            $query->where(function($q) use ($platformType) {
                $q->where('platform_type', $platformType)
                  ->orWhereNull('platform_type');
            });
        }
        
        return $query->get();
    }

    /**
     * 获取层级名称
     */
    public function getLevelNameAttribute(): string
    {
        $levels = [
            1 => '一级',
            2 => '二级', 
            3 => '三级'
        ];
        
        return $levels[$this->level] ?? '未知';
    }

    /**
     * 检查是否为指定层级
     */
    public function isLevel($level): bool
    {
        return $this->level === $level;
    }

    /**
     * 获取所有子孙项（递归）
     */
    public function getAllDescendants()
    {
        $descendants = collect();
        
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getAllDescendants());
        }
        
        return $descendants;
    }

    /**
     * 获取指定平台的所有子孙项
     */
    public function getDescendantsByPlatform($platformType)
    {
        return $this->getAllDescendants()->filter(function($item) use ($platformType) {
            return $item->platform_type === $platformType || is_null($item->platform_type);
        });
    }
}