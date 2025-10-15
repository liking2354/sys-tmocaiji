<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'config_rules',
        'template_variables',
        'template_type',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'config_rules' => 'array',
        'template_variables' => 'array',
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
     * 获取模板的配置规则数量
     */
    public function getConfigRulesCountAttribute()
    {
        return is_array($this->config_rules) ? count($this->config_rules) : 0;
    }

    /**
     * 获取模板变量数量
     */
    public function getTemplateVariablesCountAttribute()
    {
        return is_array($this->template_variables) ? count($this->template_variables) : 0;
    }

    /**
     * 获取模板中使用的所有变量名
     */
    public function getUsedVariablesAttribute()
    {
        $variables = [];
        
        // 从模板变量定义中获取
        if (is_array($this->template_variables)) {
            foreach ($this->template_variables as $variable) {
                if (isset($variable['name'])) {
                    $variables[] = $variable['name'];
                }
            }
        }
        
        // 从配置规则中提取使用的变量
        if (is_array($this->config_rules)) {
            foreach ($this->config_rules as $rule) {
                // 支持新的多变量格式
                if (isset($rule['variables']) && is_array($rule['variables'])) {
                    foreach ($rule['variables'] as $varConfig) {
                        if (isset($varConfig['variable'])) {
                            $variables[] = $varConfig['variable'];
                        }
                    }
                }
                // 兼容旧的单变量格式
                elseif (isset($rule['variable'])) {
                    $variables[] = $rule['variable'];
                }
                
                // 从替换字符串中提取变量
                if (isset($rule['replace_string'])) {
                    preg_match_all('/\{\{(\w+)\}\}/', $rule['replace_string'], $matches);
                    if (!empty($matches[1])) {
                        $variables = array_merge($variables, $matches[1]);
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
        
        // 验证配置规则
        if (!is_array($this->config_rules)) {
            $errors[] = '配置规则必须是数组格式';
            return $errors;
        }

        foreach ($this->config_rules as $index => $rule) {
            if (!isset($rule['type'])) {
                $errors[] = "配置规则 {$index} 缺少类型";
                continue;
            }
            
            switch ($rule['type']) {
                case 'directory':
                    if (empty($rule['directory'])) {
                        $errors[] = "目录规则 {$index} 缺少目录路径";
                    }
                    // 支持新的多变量格式和旧的单变量格式
                    if (empty($rule['variables']) && empty($rule['variable'])) {
                        $errors[] = "目录规则 {$index} 缺少变量配置";
                    }
                    // 验证多变量格式
                    if (isset($rule['variables']) && is_array($rule['variables'])) {
                        foreach ($rule['variables'] as $varIndex => $varConfig) {
                            if (empty($varConfig['variable'])) {
                                $errors[] = "目录规则 {$index} 的变量 {$varIndex} 缺少变量名";
                            }
                        }
                    }
                    break;
                    
                case 'file':
                    if (empty($rule['file_path'])) {
                        $errors[] = "文件规则 {$index} 缺少文件路径";
                    }
                    // 支持新的多变量格式和旧的单变量格式
                    if (empty($rule['variables']) && empty($rule['variable'])) {
                        $errors[] = "文件规则 {$index} 缺少变量配置";
                    }
                    // 验证多变量格式
                    if (isset($rule['variables']) && is_array($rule['variables'])) {
                        foreach ($rule['variables'] as $varIndex => $varConfig) {
                            if (empty($varConfig['variable'])) {
                                $errors[] = "文件规则 {$index} 的变量 {$varIndex} 缺少变量名";
                            }
                        }
                    }
                    break;
                    
                case 'string':
                    if (empty($rule['file_path'])) {
                        $errors[] = "字符串规则 {$index} 缺少文件路径";
                    }
                    if (empty($rule['search_string'])) {
                        $errors[] = "字符串规则 {$index} 缺少查找字符串";
                    }
                    if (empty($rule['replace_string'])) {
                        $errors[] = "字符串规则 {$index} 缺少替换字符串";
                    }
                    break;
                    
                default:
                    $errors[] = "配置规则 {$index} 类型无效: {$rule['type']}";
            }
        }
        
        // 验证模板变量
        if (is_array($this->template_variables)) {
            foreach ($this->template_variables as $index => $variable) {
                if (empty($variable['name'])) {
                    $errors[] = "模板变量 {$index} 缺少变量名";
                }
            }
        }
        
        return $errors;
    }

    /**
     * 获取指定类型的规则
     */
    public function getRulesByType($type)
    {
        if (!is_array($this->config_rules)) {
            return [];
        }
        
        return array_filter($this->config_rules, function($rule) use ($type) {
            return isset($rule['type']) && $rule['type'] === $type;
        });
    }

    /**
     * 获取模板类型的中文名称
     */
    public function getTemplateTypeNameAttribute()
    {
        $types = [
            'mixed' => '混合模式',
            'directory' => '目录批量处理',
            'file' => '文件精确处理',
            'string' => '字符串替换'
        ];
        
        return $types[$this->template_type] ?? '未知类型';
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