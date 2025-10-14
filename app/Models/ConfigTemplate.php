<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConfigTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'config_items',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'config_items' => 'array',
        'is_active' => 'boolean'
    ];

    /**
     * 获取使用此模板的变更任务
     */
    public function changeTasks()
    {
        return $this->belongsToMany(SystemChangeTask::class, 'system_change_task_details', 'template_id', 'task_id');
    }

    /**
     * 获取模板的配置项数量
     */
    public function getConfigItemsCountAttribute()
    {
        return is_array($this->config_items) ? count($this->config_items) : 0;
    }

    /**
     * 获取模板中的所有变量
     */
    public function getVariablesAttribute()
    {
        $variables = [];
        if (is_array($this->config_items)) {
            foreach ($this->config_items as $item) {
                if (isset($item['modifications']) && is_array($item['modifications'])) {
                    foreach ($item['modifications'] as $modification) {
                        if (isset($modification['replacement'])) {
                            preg_match_all('/\{\{(\w+)\}\}/', $modification['replacement'], $matches);
                            if (!empty($matches[1])) {
                                $variables = array_merge($variables, $matches[1]);
                            }
                        }
                    }
                }
            }
        }
        return array_unique($variables);
    }

    /**
     * 验证模板配置
     */
    public function validateConfig()
    {
        $errors = [];
        
        if (!is_array($this->config_items)) {
            $errors[] = '配置项必须是数组格式';
            return $errors;
        }

        foreach ($this->config_items as $index => $item) {
            if (!isset($item['name'])) {
                $errors[] = "配置项 {$index} 缺少名称";
            }
            
            if (!isset($item['file_path'])) {
                $errors[] = "配置项 {$index} 缺少文件路径";
            }
            
            if (!isset($item['modifications']) || !is_array($item['modifications'])) {
                $errors[] = "配置项 {$index} 缺少修改规则";
                continue;
            }
            
            foreach ($item['modifications'] as $modIndex => $modification) {
                if (!isset($modification['type'])) {
                    $errors[] = "配置项 {$index} 修改规则 {$modIndex} 缺少类型";
                }
                
                if (!isset($modification['pattern'])) {
                    $errors[] = "配置项 {$index} 修改规则 {$modIndex} 缺少匹配模式";
                }
                
                if (!isset($modification['replacement'])) {
                    $errors[] = "配置项 {$index} 修改规则 {$modIndex} 缺少替换内容";
                }
            }
        }
        
        return $errors;
    }

    /**
     * 范围查询：启用的模板
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 范围查询：按名称搜索
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }
}