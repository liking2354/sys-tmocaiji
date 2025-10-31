<?php

namespace App\Http\Controllers;

use App\Models\ConfigTemplate;
use App\Helpers\PaginationHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ConfigTemplateController extends Controller
{
    /**
     * 显示配置模板列表
     */
    public function index(Request $request)
    {
        $query = ConfigTemplate::query();
        
        // 搜索功能
        if ($request->filled('search')) {
            $query->search($request->search);
        }
        
        // 状态筛选
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
        
        // 类型筛选
        if ($request->filled('type')) {
            $query->where('template_type', $request->type);
        }
        
        $perPage = PaginationHelper::getPerPage($request, 10);
        $templates = $query->orderBy('created_at', 'desc')->paginate($perPage)->appends(PaginationHelper::getQueryParams($request));
        
        return view('system-change.templates.index', compact('templates'));
    }

    /**
     * 显示创建配置模板表单
     */
    public function create()
    {
        return view('system-change.templates.create-visual');
    }

    /**
     * 存储新的配置模板
     */
    public function store(Request $request)
    {
        // 添加调试信息
        \Log::info('ConfigTemplate Store - 接收到的数据:', [
            'name' => $request->name,
            'description' => $request->description,
            'template_type' => $request->template_type,
            'config_rules' => $request->config_rules,
            'template_variables' => $request->template_variables,
            'is_active' => $request->is_active
        ]);
        
        $validator = $this->validateTemplate($request);
        
        if ($validator->fails()) {
            \Log::error('ConfigTemplate Store - 验证失败:', $validator->errors()->toArray());
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        // 解析配置数据
        $configRules = $this->parseConfigRules($request->config_rules);
        $templateVariables = $this->parseTemplateVariables($request->template_variables);
        
        \Log::info('ConfigTemplate Store - 解析后的数据:', [
            'config_rules' => $configRules,
            'template_variables' => $templateVariables
        ]);
        
        try {
            \Log::info('ConfigTemplate Store - 准备创建数据库记录');
            
            $template = ConfigTemplate::create([
                'name' => $request->name,
                'description' => $request->description,
                'config_rules' => $configRules,
                'template_variables' => $templateVariables,
                'template_type' => $request->template_type ?? 'mixed',
                'is_active' => $request->boolean('is_active', true),
                'created_by' => Auth::id()
            ]);
            
            \Log::info('ConfigTemplate Store - 数据库记录创建成功', ['template_id' => $template->id]);
            
            // 验证配置项
            \Log::info('ConfigTemplate Store - 开始验证配置项');
            $errors = $template->validateConfig();
            if (!empty($errors)) {
                \Log::error('ConfigTemplate Store - 配置验证失败', ['errors' => $errors]);
                $template->delete();
                return redirect()->back()
                    ->withErrors(['config_rules' => $errors])
                    ->withInput();
            }
            
            \Log::info('ConfigTemplate Store - 配置验证通过，创建完成');
            
            return redirect()->route('system-change.templates.index')
                ->with('success', '配置模板创建成功');
                
        } catch (\Exception $e) {
            \Log::error('ConfigTemplate Store - 创建失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->withErrors(['error' => '创建配置模板失败: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * 显示配置模板详情
     */
    public function show(ConfigTemplate $template)
    {
        // 加载关联的任务数据，并确保获取服务器数量
        $template->load(['changeTasks' => function($query) {
            $query->orderBy('created_at', 'desc');
        }]);
        
        // 为每个任务计算服务器数量（如果total_servers为0）
        foreach ($template->changeTasks as $task) {
            if ($task->total_servers == 0 && is_array($task->server_ids)) {
                $task->total_servers = count($task->server_ids);
                $task->save();
            }
        }
        
        return view('system-change.templates.show', compact('template'));
    }

    /**
     * 显示编辑配置模板表单
     */
    public function edit(ConfigTemplate $template)
    {
        return view('system-change.templates.edit-visual', compact('template'));
    }

    /**
     * 更新配置模板
     */
    public function update(Request $request, ConfigTemplate $template)
    {
        $validator = $this->validateTemplate($request, $template->id);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        // 解析配置数据
        $configRules = $this->parseConfigRules($request->config_rules);
        $templateVariables = $this->parseTemplateVariables($request->template_variables);
        
        $template->update([
            'name' => $request->name,
            'description' => $request->description,
            'config_rules' => $configRules,
            'template_variables' => $templateVariables,
            'template_type' => $request->template_type ?? 'mixed',
            'is_active' => $request->boolean('is_active', true)
        ]);
        
        // 验证配置项
        $errors = $template->validateConfig();
        if (!empty($errors)) {
            return redirect()->back()
                ->withErrors(['config_rules' => $errors])
                ->withInput();
        }
        
        return redirect()->route('system-change.templates.index')
            ->with('success', '配置模板更新成功');
    }

    /**
     * 删除配置模板
     */
    public function destroy(ConfigTemplate $template)
    {
        // 检查是否有正在使用的任务
        $activeTasks = $template->changeTasks()
            ->whereIn('system_change_tasks.status', ['pending', 'running'])
            ->count();
            
        if ($activeTasks > 0) {
            return redirect()->back()
                ->with('error', '该模板正在被使用中，无法删除');
        }
        
        $template->delete();
        
        return redirect()->route('system-change.templates.index')
            ->with('success', '配置模板删除成功');
    }

    /**
     * 切换模板状态
     */
    public function toggleStatus(ConfigTemplate $template)
    {
        $template->update(['is_active' => !$template->is_active]);
        
        $status = $template->is_active ? '启用' : '禁用';
        
        return redirect()->back()
            ->with('success', "模板已{$status}");
    }

    /**
     * 复制模板
     */
    public function duplicate(ConfigTemplate $template)
    {
        $newTemplate = $template->replicate();
        $newTemplate->name = $template->name . ' - 副本';
        $newTemplate->created_by = Auth::id();
        $newTemplate->save();
        
        return redirect()->route('system-change.templates.edit', $newTemplate)
            ->with('success', '模板复制成功，请修改相关信息');
    }

    /**
     * 预览配置
     */
    public function preview(Request $request)
    {
        $configRules = $this->parseConfigRules($request->config_rules);
        $templateVariables = $this->parseTemplateVariables($request->template_variables);
        $variables = $request->variables ?? [];
        
        // 处理变量替换预览
        $preview = $this->generatePreview($configRules, $templateVariables, $variables);
        
        return response()->json([
            'success' => true,
            'preview' => $preview
        ]);
    }

    /**
     * 验证模板数据
     */
    private function validateTemplate(Request $request, $excludeId = null)
    {
        $rules = [
            'name' => 'required|string|max:255|unique:config_templates,name' . ($excludeId ? ",{$excludeId}" : ''),
            'description' => 'nullable|string|max:1000',
            'template_type' => 'required|in:mixed,directory,file,string',
            'config_rules' => 'nullable|json',
            'template_variables' => 'nullable|json',
            'is_active' => 'boolean'
        ];
        
        $messages = [
            'name.required' => '模板名称不能为空',
            'name.unique' => '模板名称已存在',
            'template_type.required' => '请选择模板类型',
            'config_rules.json' => '配置规则格式错误'
        ];
        
        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * 解析配置规则
     */
    private function parseConfigRules($configRulesJson)
    {
        if (empty($configRulesJson)) {
            return [];
        }
        
        $rules = json_decode($configRulesJson, true);
        
        if (!is_array($rules)) {
            return [];
        }
        
        // 清理和验证规则数据
        return array_map(function($rule) {
            return array_filter($rule, function($value) {
                return $value !== null && $value !== '';
            });
        }, $rules);
    }

    /**
     * 解析模板变量
     */
    private function parseTemplateVariables($templateVariablesJson)
    {
        if (empty($templateVariablesJson)) {
            return [];
        }
        
        $variables = json_decode($templateVariablesJson, true);
        
        if (!is_array($variables)) {
            return [];
        }
        
        // 清理变量数据
        return array_filter($variables, function($variable) {
            return !empty($variable['name']);
        });
    }

    /**
     * 生成配置预览
     */
    private function generatePreview($configRules, $templateVariables, $variables = [])
    {
        $preview = [];
        
        foreach ($configRules as $rule) {
            $rulePreview = [
                'type' => $rule['type'],
                'description' => $rule['description'] ?? '',
            ];
            
            switch ($rule['type']) {
                case 'directory':
                    $rulePreview['directory'] = $rule['directory'];
                    $rulePreview['pattern'] = $rule['pattern'] ?? '*';
                    $rulePreview['variable'] = $rule['variable'];
                    $rulePreview['match_type'] = $rule['match_type'];
                    $rulePreview['example'] = $this->generateDirectoryExample($rule, $variables);
                    break;
                    
                case 'file':
                    $rulePreview['file_path'] = $rule['file_path'];
                    $rulePreview['variable'] = $rule['variable'];
                    $rulePreview['match_type'] = $rule['match_type'];
                    $rulePreview['example'] = $this->generateFileExample($rule, $variables);
                    break;
                    
                case 'string':
                    $rulePreview['file_path'] = $rule['file_path'];
                    $rulePreview['search_string'] = $rule['search_string'];
                    $rulePreview['replace_string'] = $this->replaceVariables($rule['replace_string'], $variables);
                    break;
            }
            
            $preview[] = $rulePreview;
        }
        
        return $preview;
    }

    /**
     * 生成目录规则示例
     */
    private function generateDirectoryExample($rule, $variables)
    {
        $variable = $rule['variable'];
        $value = $variables[$variable] ?? "{{$variable}}";
        
        switch ($rule['match_type']) {
            case 'key_value':
                return "{$variable}={$value}";
            case 'regex':
                $pattern = $rule['match_pattern'] ?? "{$variable}=.*";
                return "匹配: {$pattern} → 替换为包含 {$value} 的内容";
            case 'exact':
                return "精确匹配 {$variable} → 替换为 {$value}";
            default:
                return "{$variable} → {$value}";
        }
    }

    /**
     * 生成文件规则示例
     */
    private function generateFileExample($rule, $variables)
    {
        return $this->generateDirectoryExample($rule, $variables);
    }

    /**
     * 替换字符串中的变量
     */
    private function replaceVariables($string, $variables)
    {
        foreach ($variables as $key => $value) {
            $string = str_replace("{{$key}}", $value, $string);
        }
        return $string;
    }

    /**
     * 下载样例模板
     */
    public function downloadSample()
    {
        try {
            // 读取样例模板文件
            $samplePath = storage_path('app/config_template_sample.json');
            
            if (!file_exists($samplePath)) {
                return redirect()->back()
                    ->with('error', '样例模板文件不存在');
            }

            $content = file_get_contents($samplePath);
            $filename = '配置模板样例_' . date('Y-m-d_H-i-s') . '.json';

            return response($content)
                ->header('Content-Type', 'application/json; charset=utf-8')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', '下载样例模板失败：' . $e->getMessage());
        }
    }

    /**
     * 导入配置模板
     */
    public function import(Request $request)
    {
        $request->validate([
            'template_file' => 'required|file|mimes:json|max:2048'
        ], [
            'template_file.required' => '请选择要导入的模板文件',
            'template_file.mimes' => '只支持JSON格式的文件',
            'template_file.max' => '文件大小不能超过2MB'
        ]);

        try {
            $file = $request->file('template_file');
            $content = file_get_contents($file->getRealPath());
            $templateData = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return redirect()->back()
                    ->with('error', 'JSON文件格式错误：' . json_last_error_msg());
            }

            // 验证必需字段
            $requiredFields = ['name', 'config_rules'];
            foreach ($requiredFields as $field) {
                if (!isset($templateData[$field])) {
                    return redirect()->back()
                        ->with('error', "模板文件缺少必需字段：{$field}");
                }
            }

            // 检查模板名称是否已存在
            if (ConfigTemplate::where('name', $templateData['name'])->exists()) {
                $templateData['name'] = $templateData['name'] . '_导入_' . date('Y-m-d_H-i-s');
            }

            // 创建模板
            $template = ConfigTemplate::create([
                'name' => $templateData['name'],
                'description' => $templateData['description'] ?? '',
                'config_rules' => $templateData['config_rules'],
                'template_variables' => $templateData['template_variables'] ?? [],
                'template_type' => $templateData['template_type'] ?? 'mixed',
                'is_active' => $templateData['is_active'] ?? true,
                'created_by' => Auth::id()
            ]);

            // 验证配置
            $errors = $template->validateConfig();
            if (!empty($errors)) {
                $template->delete();
                return redirect()->back()
                    ->with('error', '模板配置验证失败：' . implode(', ', $errors));
            }

            return redirect()->route('system-change.templates.index')
                ->with('success', '配置模板导入成功');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', '导入失败：' . $e->getMessage());
        }
    }

    /**
     * 导出配置模板
     */
    public function export(ConfigTemplate $template)
    {
        $exportData = [
            'name' => $template->name,
            'description' => $template->description,
            'template_type' => $template->template_type,
            'is_active' => $template->is_active,
            'config_rules' => $template->config_rules,
            'template_variables' => $template->template_variables,
            'exported_at' => now()->toISOString(),
            'exported_by' => Auth::user()->name ?? 'Unknown'
        ];

        $filename = '配置模板_' . $template->name . '_' . date('Y-m-d_H-i-s') . '.json';
        $content = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return response($content)
            ->header('Content-Type', 'application/json; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * 获取模板变量 (用于AJAX请求)
     */
    public function getVariables(ConfigTemplate $template)
    {
        try {
            // 获取模板的变量信息
            $variables = [];
            
            // 检查新的可视化配置格式 - 支持多变量
            if ($template->config_rules) {
                $rules = is_string($template->config_rules) 
                    ? json_decode($template->config_rules, true) 
                    : $template->config_rules;
                
                if (is_array($rules)) {
                    foreach ($rules as $rule) {
                        // 新的多变量格式
                        if (isset($rule['variables']) && is_array($rule['variables'])) {
                            foreach ($rule['variables'] as $varConfig) {
                                if (isset($varConfig['variable']) && !empty($varConfig['variable'])) {
                                    $variables[$varConfig['variable']] = [
                                        'description' => $rule['description'] ?? '',
                                        'default_value' => '',
                                        'required' => true,
                                        'match_type' => $varConfig['match_type'] ?? 'key_value',
                                        'match_pattern' => $varConfig['match_pattern'] ?? ''
                                    ];
                                }
                            }
                        }
                        // 兼容旧的单变量格式
                        elseif (isset($rule['variable']) && !empty($rule['variable'])) {
                            $variables[$rule['variable']] = [
                                'description' => $rule['description'] ?? '',
                                'default_value' => '',
                                'required' => true,
                                'match_type' => $rule['match_type'] ?? 'key_value',
                                'match_pattern' => $rule['match_pattern'] ?? ''
                            ];
                        }
                        // 检查replacement_rules格式
                        elseif (isset($rule['variable_name']) && !empty($rule['variable_name'])) {
                            $variables[$rule['variable_name']] = [
                                'description' => $rule['description'] ?? '',
                                'default_value' => $rule['default_value'] ?? '',
                                'required' => $rule['required'] ?? false
                            ];
                        }
                    }
                }
            }
            
            // 检查旧的template_variables格式
            if (empty($variables) && $template->template_variables) {
                $templateVars = is_string($template->template_variables) 
                    ? json_decode($template->template_variables, true) 
                    : $template->template_variables;
                
                if (is_array($templateVars)) {
                    foreach ($templateVars as $var) {
                        if (isset($var['name']) && !empty($var['name'])) {
                            $variables[$var['name']] = [
                                'description' => $var['description'] ?? '',
                                'default_value' => $var['default_value'] ?? '',
                                'required' => $var['required'] ?? false
                            ];
                        }
                    }
                }
            }
            
            // 检查旧的config_items格式
            if (empty($variables) && $template->config_items) {
                $configItems = is_string($template->config_items) 
                    ? json_decode($template->config_items, true) 
                    : $template->config_items;
                
                if (is_array($configItems)) {
                    foreach ($configItems as $item) {
                        if (isset($item['key']) && !empty($item['key'])) {
                            $variables[$item['key']] = [
                                'description' => $item['description'] ?? '',
                                'default_value' => $item['default_value'] ?? '',
                                'required' => true
                            ];
                        }
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'variables' => $variables,
                'template_name' => $template->name
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取模板变量失败: ' . $e->getMessage()
            ], 500);
        }
    }
}