<?php

namespace App\Http\Controllers;

use App\Models\Collector;
use App\Helpers\PaginationHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\CollectorDeploymentService;

class CollectorController extends Controller
{
    /**
     * 采集组件部署服务
     *
     * @var CollectorDeploymentService
     */
    protected $deploymentService;

    /**
     * 构造函数
     *
     * @param CollectorDeploymentService $deploymentService
     */
    public function __construct(CollectorDeploymentService $deploymentService)
    {
        $this->deploymentService = $deploymentService;
    }

    /**
     * 显示采集组件列表
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $perPage = PaginationHelper::getPerPage($request, 10);
        
        $query = Collector::query();
        
        // 按组件名称搜索
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }
        
        // 按组件代码搜索
        if ($request->filled('code')) {
            $query->where('code', 'like', '%' . $request->input('code') . '%');
        }
        
        $collectors = $query->paginate($perPage)->appends(PaginationHelper::getQueryParams($request));
        
        return view('collectors.index', compact('collectors'));
    }

    /**
     * 显示创建采集组件表单
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('collectors.create');
    }

    /**
     * 存储新创建的采集组件
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:50|unique:collectors',
            'description' => 'nullable|string|max:255',
            'status' => 'required|boolean',
            'version' => 'nullable|string|max:20',
            'type' => 'required|string|in:script,program',
        ];
        
        // 根据类型添加不同的验证规则
        if ($request->input('type') === 'script') {
            $rules['script_content'] = 'required|string';
            $rules['script_file'] = 'nullable|file|mimes:sh,txt,py,pl,rb,js,php';
        } else { // program类型
            $rules['program_file'] = 'required|file';
        }
        
        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        // 处理文件和内容
        $scriptContent = '';
        $filePath = null;
        $type = $request->input('type');
        
        if ($type === 'script') {
            // 处理脚本文件上传
            $scriptContent = $request->input('script_content');
            if ($request->hasFile('script_file')) {
                $scriptContent = file_get_contents($request->file('script_file')->path());
            }
        } else { // program类型
            if ($request->hasFile('program_file')) {
                $file = $request->file('program_file');
                $fileName = $request->input('code') . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('collectors/programs', $fileName);
                $filePath = 'collectors/programs/' . $fileName;
            }
        }
        
        // 设置版本号
        $version = $request->input('version') ?: '1.0.0';
        
        // 设置部署配置
        $deploymentConfig = [
            'remote_path' => '/opt/collectors/' . $request->input('code'),
            'auto_update' => true,
            'created_at' => now()->timestamp,
        ];
        
        $collector = Collector::create([
            'name' => $request->input('name'),
            'code' => $request->input('code'),
            'description' => $request->input('description'),
            'script_content' => $scriptContent,
            'file_path' => $filePath,
            'type' => $type,
            'status' => $request->input('status'),
            'version' => $version,
            'deployment_config' => $deploymentConfig,
            'script_path' => null, // 初始化script_path字段
        ]);
        
        // 保存脚本文件到本地存储
        if ($type === 'script') {
            $this->saveScriptToStorage($collector);
        }
        
        return redirect()->route('collectors.index')
            ->with('success', '采集组件创建成功！');
    }

    /**
     * 显示指定的采集组件
     *
     * @param  \App\Models\Collector  $collector
     * @return \Illuminate\Http\Response
     */
    public function show(Collector $collector)
    {
        // 获取已安装此采集组件的服务器列表
        $installedServers = $collector->servers;
        
        return view('collectors.show', compact('collector', 'installedServers'));
    }

    /**
     * 显示编辑采集组件表单
     *
     * @param  \App\Models\Collector  $collector
     * @return \Illuminate\Http\Response
     */
    public function edit(Collector $collector)
    {
        return view('collectors.edit', compact('collector'));
    }

    /**
     * 更新指定的采集组件
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Collector  $collector
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Collector $collector)
    {
        $rules = [
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:50|unique:collectors,code,' . $collector->id,
            'description' => 'nullable|string|max:255',
            'status' => 'required|boolean',
            'version' => 'nullable|string|max:20',
            'type' => 'required|string|in:script,program',
            'update_servers' => 'nullable|boolean',
        ];
        
        // 根据类型添加不同的验证规则
        if ($request->input('type') === 'script') {
            $rules['script_content'] = 'required|string';
            $rules['script_file'] = 'nullable|file|mimes:sh,txt,py,pl,rb,js,php';
        } else { // program类型
            $rules['program_file'] = 'nullable|file';
        }
        
        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        // 处理文件和内容
        $scriptContent = $collector->script_content;
        $filePath = $collector->file_path;
        $type = $request->input('type');
        
        if ($type === 'script') {
            // 处理脚本文件上传
            $scriptContent = $request->input('script_content');
            if ($request->hasFile('script_file')) {
                $scriptContent = file_get_contents($request->file('script_file')->path());
            }
        } else { // program类型
            if ($request->hasFile('program_file')) {
                // 如果有旧文件，先删除
                if ($filePath && Storage::exists($filePath)) {
                    Storage::delete($filePath);
                }
                
                $file = $request->file('program_file');
                $fileName = $request->input('code') . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('collectors/programs', $fileName);
                $filePath = 'collectors/programs/' . $fileName;
            }
        }
        
        // 更新版本号
        $version = $request->input('version');
        if (empty($version)) {
            // 如果未提供版本号，则自动递增版本号
            $version = $this->incrementVersion($collector->version ?: '1.0.0');
        }
        
        // 更新部署配置
        $deploymentConfig = $collector->deployment_config ?: [];
        $deploymentConfig['updated_at'] = now()->timestamp;
        
        $collector->update([
            'name' => $request->input('name'),
            'code' => $request->input('code'),
            'description' => $request->input('description'),
            'script_content' => $scriptContent,
            'file_path' => $filePath,
            'type' => $type,
            'status' => $request->input('status'),
            'version' => $version,
            'deployment_config' => $deploymentConfig,
        ]);
        
        // 保存脚本文件到本地存储
        if ($type === 'script') {
            $this->saveScriptToStorage($collector);
        }
        
        // 如果选择了更新服务器上的采集组件，则执行更新
        if ($request->has('update_servers') && $request->input('update_servers')) {
            $this->updateServers($collector);
            return redirect()->route('collectors.index')
                ->with('success', '采集组件更新成功，并已开始更新服务器上的组件！');
        }
        
        return redirect()->route('collectors.index')
            ->with('success', '采集组件更新成功！');
    }

    /**
     * 删除指定的采集组件
     *
     * @param  \App\Models\Collector  $collector
     * @return \Illuminate\Http\Response
     */
    public function destroy(Collector $collector)
    {
        // 检查是否有关联的服务器
        if ($collector->servers()->count() > 0) {
            return redirect()->back()
                ->with('error', '无法删除，该采集组件已安装在服务器上！请先卸载所有服务器上的此组件。');
        }
        
        // 删除本地存储的脚本文件
        $this->deleteScriptFromStorage($collector);
        
        $collector->delete();
        
        return redirect()->route('collectors.index')
            ->with('success', '采集组件删除成功！');
    }
    
    /**
     * 保存脚本文件到本地存储
     *
     * @param  \App\Models\Collector  $collector
     * @return void
     */
    protected function saveScriptToStorage(Collector $collector)
    {
        try {
            $path = 'collectors/' . $collector->code;
            $filename = 'collector.sh';
            $scriptPath = $path . '/' . $filename;
            
            // 确保目录存在
            if (!Storage::exists($path)) {
                Storage::makeDirectory($path);
            }
            
            // 保存脚本文件
            Storage::put($scriptPath, $collector->script_content);
            
            // 更新数据库中的script_path字段
            $collector->update([
                'script_path' => $scriptPath
            ]);
            
            // 保存版本信息
            $versionInfo = [
                'version' => $collector->version,
                'name' => $collector->name,
                'code' => $collector->code,
                'updated_at' => now()->timestamp
            ];
            Storage::put($path . '/version.json', json_encode($versionInfo));
            
            Log::info('采集组件脚本保存成功', [
                'collector_id' => $collector->id,
                'collector_code' => $collector->code,
                'version' => $collector->version,
                'script_path' => $scriptPath
            ]);
        } catch (\Exception $e) {
            Log::error('采集组件脚本保存失败', [
                'collector_id' => $collector->id,
                'collector_code' => $collector->code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * 从本地存储删除脚本文件
     *
     * @param  \App\Models\Collector  $collector
     * @return void
     */
    protected function deleteScriptFromStorage(Collector $collector)
    {
        try {
            $path = 'collectors/' . $collector->code;
            
            // 删除目录及其内容
            if (Storage::exists($path)) {
                Storage::deleteDirectory($path);
            }
            
            Log::info('采集组件脚本删除成功', [
                'collector_id' => $collector->id,
                'collector_code' => $collector->code
            ]);
        } catch (\Exception $e) {
            Log::error('采集组件脚本删除失败', [
                'collector_id' => $collector->id,
                'collector_code' => $collector->code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * 递增版本号
     *
     * @param  string  $version
     * @return string
     */
    protected function incrementVersion($version)
    {
        $parts = explode('.', $version);
        
        // 确保版本号有三个部分
        while (count($parts) < 3) {
            $parts[] = '0';
        }
        
        // 递增补丁版本号
        $parts[2] = (int)$parts[2] + 1;
        
        return implode('.', $parts);
    }
    
    /**
     * 更新服务器上的采集组件
     *
     * @param  \App\Models\Collector  $collector
     * @return void
     */
    protected function updateServers(Collector $collector)
    {
        // 获取已安装此采集组件的服务器
        $servers = $collector->servers;
        
        foreach ($servers as $server) {
            try {
                // 使用部署服务更新采集组件
                $this->deploymentService->install($server, $collector, true);
                
                Log::info('服务器上的采集组件更新成功', [
                    'server_id' => $server->id,
                    'server_ip' => $server->ip,
                    'collector_id' => $collector->id,
                    'collector_code' => $collector->code,
                    'version' => $collector->version
                ]);
            } catch (\Exception $e) {
                Log::error('服务器上的采集组件更新失败', [
                    'server_id' => $server->id,
                    'server_ip' => $server->ip,
                    'collector_id' => $collector->id,
                    'collector_code' => $collector->code,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }

    /**
     * 获取所有采集组件（API接口）
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllCollectors()
    {
        try {
            $collectors = Collector::select('id', 'name', 'code', 'description', 'version')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $collectors
            ]);
        } catch (\Exception $e) {
            Log::error('获取所有采集组件失败', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '获取采集组件失败：' . $e->getMessage()
            ], 500);
        }
    }
}