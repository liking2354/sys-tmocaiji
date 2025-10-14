<?php

namespace App\Http\Controllers;

use App\Models\ConfigTemplate;
use Illuminate\Http\Request;
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
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }
        
        $templates = $query->orderBy('created_at', 'desc')->paginate(15);
        
        return view('system-change.templates.index', compact('templates'));
    }

    /**
     * 显示创建配置模板表单
     */
    public function create()
    {
        return view('system-change.templates.create');
    }

    /**
     * 存储新的配置模板
     */
    public function store(Request $request)
    {
        $validator = $this->validateTemplate($request);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $template = ConfigTemplate::create([
            'name' => $request->name,
            'description' => $request->description,
            'config_items' => $this->parseConfigItems($request->config_items),
            'is_active' => $request->boolean('is_active', true),
            'created_by' => auth()->user()->name ?? 'system'
        ]);
        
        // 验证配置项
        $errors = $template->validateConfig();
        if (!empty($errors)) {
            $template->delete();
            return redirect()->back()
                ->withErrors(['config_items' => $errors])
                ->withInput();
        }
        
        return redirect()->route('system-change.templates.index')
            ->with('success', '配置模板创建成功');
    }

    /**
     * 显示配置模板详情
     */
    public function show(ConfigTemplate $template)
    {
        $template->load('changeTasks');
        
        return view('system-change.templates.show', compact('template'));
    }

    /**
     * 显示编辑配置模板表单
     */
    public function edit(ConfigTemplate $template)
    {
        return view('system-change.templates.edit', compact('template'));
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
        
        $configItems = $this->parseConfigItems($request->config_items);
        
        $template->update([
            'name' => $request->name,
            'description' => $request->description,
            'config_items' => $configItems,
            'is_active' => $request->boolean('is_active', true)
        ]);
        
        // 验证配置项
        $errors = $template->validateConfig();
        if (!empty($errors)) {
            return redirect()->back()
                ->withErrors(['config_items' => $errors])
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
            ->whereIn('status', ['pending', 'running'])
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
        $newTemplate->name = $template->name . ' (副本)';
        $newTemplate->created_by = auth()->user()->name ?? 'system';
        $newTemplate->save();
        
        return redirect()->route('system-change.templates.edit', $newTemplate)
            ->with('success', '模板复制成功');
    }

    /**
     * 预览模板配置
     */
    public function preview(Request $request)
    {
        $configItems = $this->parseConfigItems($request->config_items);
        
        // 模拟变量替换
        $variables = $request->input('variables', []);
        $previewData = $this->previewConfigChanges($configItems, $variables);
        
        return response()->json([
            'success' => true,
            'preview' => $previewData
        ]);
    }

    /**
     * 获取模板变量
     */
    public function getVariables(ConfigTemplate $template)
    {
        return response()->json([
            'variables' => $template->variables
        ]);
    }

    /**
     * 导出模板
     */
    public function export(ConfigTemplate $template)
    {
        $data = [
            'name' => $template->name,
            'description' => $template->description,
            'config_items' => $template->config_items,
            'exported_at' => now()->toISOString(),
            'version' => '1.0'
        ];
        
        $filename = 'template_' . str_replace(' ', '_', $template->name) . '_' . date('Y-m-d') . '.json';
        
        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * 导入模板
     */
    public function import(Request $request)
    {
        $request->validate([
            'template_file' => 'required|file|mimes:json'
        ]);
        
        $content = file_get_contents($request->file('template_file')->getRealPath());
        $data = json_decode($content, true);
        
        if (!$data || !isset($data['config_items'])) {
            return redirect()->back()
                ->with('error', '无效的模板文件格式');
        }
        
        $template = ConfigTemplate::create([
            'name' => $data['name'] . ' (导入)',
            'description' => $data['description'] ?? '',
            'config_items' => $data['config_items'],
            'is_active' => true,
            'created_by' => auth()->user()->name ?? 'system'
        ]);
        
        return redirect()->route('system-change.templates.edit', $template)
            ->with('success', '模板导入成功');
    }

    /**
     * 验证模板数据
     */
    private function validateTemplate(Request $request, $ignoreId = null)
    {
        $rules = [
            'name' => 'required|string|max:255|unique:config_templates,name' . ($ignoreId ? ",{$ignoreId}" : ''),
            'description' => 'nullable|string|max:1000',
            'config_items' => 'required|string',
            'is_active' => 'boolean'
        ];
        
        return Validator::make($request->all(), $rules, [
            'name.required' => '模板名称不能为空',
            'name.unique' => '模板名称已存在',
            'config_items.required' => '配置项不能为空'
        ]);
    }

    /**
     * 解析配置项数据
     */
    private function parseConfigItems($configItemsString)
    {
        try {
            $configItems = json_decode($configItemsString, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON格式错误');
            }
            
            return $configItems;
            
        } catch (\Exception $e) {
            throw new \Exception('配置项格式错误: ' . $e->getMessage());
        }
    }

    /**
     * 预览配置变更
     */
    private function previewConfigChanges($configItems, $variables = [])
    {
        $preview = [];
        
        foreach ($configItems as $item) {
            $itemPreview = [
                'name' => $item['name'],
                'file_path' => $item['file_path'],
                'modifications' => []
            ];
            
            foreach ($item['modifications'] as $modification) {
                $replacement = $modification['replacement'];
                
                // 替换变量
                foreach ($variables as $key => $value) {
                    $replacement = str_replace('{{' . $key . '}}', $value, $replacement);
                }
                
                $itemPreview['modifications'][] = [
                    'pattern' => $modification['pattern'],
                    'replacement' => $replacement,
                    'description' => $modification['description'] ?? ''
                ];
            }
            
            $preview[] = $itemPreview;
        }
        
        return $preview;
    }
}