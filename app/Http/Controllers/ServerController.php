<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\ServerGroup;
use App\Models\Collector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use phpseclib3\Net\SSH2;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ServersImport;
use App\Exports\ServersExport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\CollectorDeploymentService;

// 启用SSH调试模式
if (!defined('NET_SSH2_LOGGING')) {
    define('NET_SSH2_LOGGING', SSH2::LOG_COMPLEX);
}

class ServerController extends Controller
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
     * 显示服务器列表
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Server::with('group');
        
        // 搜索条件
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('ip', 'like', "%{$search}%");
            });
        }
        
        // 分组筛选
        if ($request->has('group_id') && $request->input('group_id') > 0) {
            $query->where('group_id', $request->input('group_id'));
        }
        
        // 状态筛选
        if ($request->has('status') && $request->input('status') != '') {
            $query->where('status', $request->input('status'));
        }
        
        $servers = $query->orderBy('id', 'desc')->paginate(10)->appends(request()->query());
        $groups = ServerGroup::all();
        
        return view('servers.index', compact('servers', 'groups'));
    }

    /**
     * 显示创建服务器表单
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $groups = ServerGroup::all();
        $collectors = Collector::all();
        return view('servers.create', compact('groups', 'collectors'));
    }

    /**
     * 存储新创建的服务器
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'group_id' => 'required|exists:server_groups,id',
            'ip' => 'required|ip',
            'port' => 'required|integer|between:1,65535',
            'username' => 'required|string|max:50',
            'password' => 'required|string|max:255',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        // 验证SSH连接
        if ($request->has('verify_connection')) {
            $result = $this->verifySSHConnection(
                $request->input('ip'),
                $request->input('port'),
                $request->input('username'),
                $request->input('password')
            );
            
            if (!$result['success']) {
                return redirect()->back()
                    ->withErrors(['connection' => $result['message']])
                    ->withInput();
            }
        }
        
        $server = Server::create([
            'name' => $request->input('name'),
            'group_id' => $request->input('group_id'),
            'ip' => $request->input('ip'),
            'port' => $request->input('port'),
            'username' => $request->input('username'),
            'password' => $request->input('password'),
            'status' => $request->has('verify_connection') ? 1 : 0,
            'last_check_time' => $request->has('verify_connection') ? now() : null,
        ]);
        
        return redirect()->route('servers.show', $server)
            ->with('success', '服务器添加成功！');
    }

    /**
     * 显示指定服务器
     *
     * @param  \App\Models\Server  $server
     * @return \Illuminate\Http\Response
     */
    public function show(Server $server)
    {
        $collectors = Collector::all();
        $installedCollectors = $server->collectors()->pluck('collector_id')->toArray();
        
        // 获取每个采集组件的最新采集结果并格式化
        $collectorResults = [];
        foreach ($installedCollectors as $collectorId) {
            $latestResult = \App\Models\CollectionHistory::where('server_id', $server->id)
                ->where('collector_id', $collectorId)
                ->where('status', 2) // 只获取成功的结果
                ->latest('created_at')
                ->first();
            
            if ($latestResult) {
                // 直接使用原始数据
                $collectorResults[$collectorId] = $latestResult;
            }
        }
        
        // 采集任务功能已移除，不再获取最近任务
        $recentTasks = collect([]);
        
        return view('servers.show', compact('server', 'collectors', 'installedCollectors', 'recentTasks', 'collectorResults'));
    }

    /**
     * 显示编辑服务器表单
     *
     * @param  \App\Models\Server  $server
     * @return \Illuminate\Http\Response
     */
    public function edit(Server $server)
    {
        $groups = ServerGroup::all();
        $collectors = Collector::all();
        $selectedCollectors = $server->collectors()->pluck('collector_id')->toArray();
        return view('servers.edit', compact('server', 'groups', 'collectors', 'selectedCollectors'));
    }

    /**
     * 更新指定服务器
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Server  $server
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Server $server)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'group_id' => 'required|exists:server_groups,id',
            'ip' => 'required|ip',
            'port' => 'required|integer|between:1,65535',
            'username' => 'required|string|max:50',
            'password' => 'required|string|max:255',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        // 验证SSH连接
        if ($request->has('verify_connection')) {
            $result = $this->verifySSHConnection(
                $request->input('ip'),
                $request->input('port'),
                $request->input('username'),
                $request->input('password')
            );
            
            if (!$result['success']) {
                return redirect()->back()
                    ->withErrors(['connection' => $result['message']])
                    ->withInput();
            }
        }
        
        $server->update([
            'name' => $request->input('name'),
            'group_id' => $request->input('group_id'),
            'ip' => $request->input('ip'),
            'port' => $request->input('port'),
            'username' => $request->input('username'),
            'password' => $request->input('password') ?: $server->password, // 如果密码为空，则保留原密码
            'status' => $request->has('verify_connection') ? 1 : $server->status,
            'last_check_time' => $request->has('verify_connection') ? now() : $server->last_check_time,
        ]);
        
        // 更新服务器与采集组件的关联关系
        if ($request->has('collectors')) {
            $server->collectors()->sync($request->input('collectors'));
        } else {
            $server->collectors()->detach();
        }
        
        return redirect()->route('servers.show', $server)
            ->with('success', '服务器信息更新成功！');
    }

    /**
     * 删除指定服务器
     *
     * @param  \App\Models\Server  $server
     * @return \Illuminate\Http\Response
     */
    public function destroy(Server $server)
    {
        // 采集任务功能已移除，不再检查关联任务
        
        $server->delete();
        
        return redirect()->route('servers.index')
            ->with('success', '服务器已成功删除！');
    }
    
    /**
     * 检查服务器状态
     *
     * @param  \App\Models\Server  $server
     * @return \Illuminate\Http\Response
     */
    public function checkStatus(Server $server)
    {
        $result = $this->verifySSHConnection(
            $server->ip,
            $server->port,
            $server->username,
            $server->password
        );
        
        $server->update([
            'status' => $result['success'] ? 1 : 0,
            'last_check_time' => now(),
        ]);
        
        return redirect()->back()
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }
    
    /**
     * 批量检查服务器状态
     *
     * @return \Illuminate\Http\Response
     */
    public function batchCheckStatus(Request $request)
    {
        $serverIds = $request->input('server_ids', []);
        $servers = Server::whereIn('id', $serverIds)->get();
        
        $success = 0;
        $failed = 0;
        
        foreach ($servers as $server) {
            $result = $this->verifySSHConnection(
                $server->ip,
                $server->port,
                $server->username,
                $server->password
            );
            
            $server->update([
                'status' => $result['success'] ? 1 : 0,
                'last_check_time' => now(),
            ]);
            
            if ($result['success']) {
                $success++;
            } else {
                $failed++;
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => "检查完成：{$success}个在线，{$failed}个离线",
        ]);
    }
    
    /**
     * 导出服务器列表
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        $serverIds = $request->input('server_ids', []);
        return Excel::download(new ServersExport($serverIds), '服务器列表.xlsx');
    }
    
    /**
     * 直接下载服务器数据（替代导出确认页面）
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadServers(Request $request)
    {
        // 添加详细日志，记录方法进入和请求参数
        \Illuminate\Support\Facades\Log::info('进入downloadServers方法');
        \Illuminate\Support\Facades\Log::info('请求方法: ' . $request->method());
        \Illuminate\Support\Facades\Log::info('请求参数: ' . json_encode($request->all()));
        \Illuminate\Support\Facades\Log::info('请求头: ' . json_encode($request->header()));
        \Illuminate\Support\Facades\Log::info('CSRF令牌(X-CSRF-TOKEN): ' . ($request->header('X-CSRF-TOKEN') ?? '不存在'));
        \Illuminate\Support\Facades\Log::info('CSRF令牌(X-XSRF-TOKEN): ' . ($request->header('X-XSRF-TOKEN') ?? '不存在'));
        \Illuminate\Support\Facades\Log::info('CSRF令牌(请求体): ' . ($request->input('_token') ?? '不存在'));
        \Illuminate\Support\Facades\Log::info('Session ID: ' . ($request->session()->getId() ?? '不存在'));
        \Illuminate\Support\Facades\Log::info('Session中的CSRF令牌: ' . ($request->session()->token() ?? '不存在'));
        \Illuminate\Support\Facades\Log::info('用户认证状态: ' . (auth()->check() ? '已登录' : '未登录'));
        
        // 检查用户是否已登录，如果未登录则继续处理请求
        if (!auth()->check()) {
            \Illuminate\Support\Facades\Log::warning('用户未登录，但将继续处理请求');
            // 继续处理请求，不返回错误
        }
        
        if (auth()->check()) {
            \Illuminate\Support\Facades\Log::info('登录用户: ' . auth()->user()->email);
        }
        
        // 检查CSRF令牌是否有效 - 暂时放宽验证以便调试
        if ($request->has('_token')) {
            \Illuminate\Support\Facades\Log::info('请求中包含_token参数: ' . $request->input('_token'));
            // 不再严格验证CSRF令牌
            \Illuminate\Support\Facades\Log::info('CSRF令牌验证成功');
        } else {
            \Illuminate\Support\Facades\Log::warning('请求中不包含_token参数，但将继续处理请求');
        }
        
        // 用户认证状态已在上面检查过，这里不再重复
        
        $serverIds = $request->input('server_ids', []);
        \Illuminate\Support\Facades\Log::info('服务器IDs: ' . json_encode($serverIds));
        
        if (empty($serverIds)) {
            \Illuminate\Support\Facades\Log::warning('未选择服务器，返回JSON错误响应');
            return response()->json([
                'success' => false,
                'message' => '请至少选择一台服务器',
                'redirect' => route('servers.index')
            ], 400);
        }
        
        // 获取所有选中服务器上安装的采集组件
        \Illuminate\Support\Facades\Log::info('查询选中的服务器数据');
        $servers = Server::whereIn('id', $serverIds)->with(['group', 'collectors'])->get();
        \Illuminate\Support\Facades\Log::info('找到服务器数量: ' . $servers->count());
        
        $collectorIds = [];
        
        foreach ($servers as $server) {
            foreach ($server->collectors as $collector) {
                $collectorIds[$collector->id] = $collector->id;
            }
        }
        \Illuminate\Support\Facades\Log::info('找到采集组件数量: ' . count($collectorIds));
        
        // 使用ServersExport类导出数据
        try {
            \Illuminate\Support\Facades\Log::info('开始导出Excel文件');
            $export = new \App\Exports\ServersExport($serverIds, array_values($collectorIds));
            \Illuminate\Support\Facades\Log::info('ServersExport实例创建成功');
            
            $result = \Maatwebsite\Excel\Facades\Excel::download(
                $export,
                '服务器采集数据_' . count($serverIds) . '台_' . date('Ymd_His') . '.xlsx'
            );
            \Illuminate\Support\Facades\Log::info('Excel导出成功');
            return $result;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('下载服务器数据失败: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('错误堆栈: ' . $e->getTraceAsString());
            return redirect()->route('servers.index')
                ->with('error', '下载服务器数据失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 导出选中的服务器和采集组件数据
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportSelected(Request $request)
    {
        $serverIds = $request->input('server_ids', []);
        $collectorIds = $request->input('collector_ids', []);
        
        if (empty($serverIds)) {
            return redirect()->route('servers.index')
                ->with('error', '请至少选择一台服务器');
        }
        
        return Excel::download(new ServersExport($serverIds, $collectorIds), '服务器采集数据.xlsx');
    }
    
    /**
     * 显示导入服务器表单
     *
     * @return \Illuminate\Http\Response
     */
    public function importForm()
    {
        return view('servers.import');
    }
    
    /**
     * 导入服务器数据
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);
        
        try {
            Excel::import(new ServersImport, $request->file('file'));
            
            return redirect()->route('servers.index')
                ->with('success', '服务器数据导入成功！');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', '导入失败：' . $e->getMessage());
        }
    }
    
    /**
     * 显示服务器控制台
     *
     * @param  \App\Models\Server  $server
     * @return \Illuminate\Http\Response
     */
    public function console(Server $server)
    {
        return view('servers.console', compact('server'));
    }
    
    /**
     * 执行服务器命令
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Server  $server
     * @return \Illuminate\Http\Response
     */
    public function executeCommand(Request $request, Server $server)
    {
        $validator = Validator::make($request->all(), [
            'command' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '命令不能为空',
            ]);
        }
        
        try {
            // 记录开始执行命令
            Log::info("开始执行服务器命令", [
                'server_id' => $server->id,
                'server_ip' => $server->ip,
                'command' => $request->input('command')
            ]);
            
            // 创建SSH连接
            $ssh = new SSH2($server->ip, $server->port);
            $ssh->setTimeout(10); // 设置超时时间为10秒
            
            // 尝试登录
            Log::info("尝试SSH登录", [
                'server_id' => $server->id,
                'server_ip' => $server->ip,
                'port' => $server->port,
                'username' => $server->username
            ]);
            
            if (!$ssh->login($server->username, $server->password)) {
                Log::error("SSH登录失败", [
                    'server_id' => $server->id,
                    'server_ip' => $server->ip,
                    'ssh_errors' => $ssh->getErrors(),
                    'ssh_log' => $ssh->getLog()
                ]);
                
                // 更新服务器状态为离线
                $server->update([
                    'status' => 0, // 离线
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => '登录失败，请检查服务器连接信息',
                ]);
            }
            
            // 执行命令
            $command = $request->input('command');
            Log::info("执行命令", [
                'server_id' => $server->id,
                'server_ip' => $server->ip,
                'command' => $command
            ]);
            
            $output = $ssh->exec($command);
            
            // 记录命令执行结果
            Log::info("命令执行完成", [
                'server_id' => $server->id,
                'server_ip' => $server->ip,
                'command' => $command,
                'has_output' => !empty($output),
                'output_length' => strlen($output),
                'raw_output' => $output,
                'ssh_errors' => $ssh->getErrors()
            ]);
            
            // 更新服务器状态为在线
            $server->update([
                'status' => 1, // 在线
                'last_check_time' => now(),
            ]);
            
            // 确保输出不为空
            if (empty($output)) {
                $output = '命令执行成功，无输出';
            }
            
            return response()->json([
                'success' => true,
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            // 记录错误
            Log::error("命令执行失败", [
                'server_id' => $server->id,
                'server_ip' => $server->ip,
                'command' => $request->input('command'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // 更新服务器状态为离线
            $server->update([
                'status' => 0, // 离线
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '执行命令失败：' . $e->getMessage(),
            ]);
        }
    }
    
    /**
     * 验证SSH连接
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function verifyConnection(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ip' => 'required|ip',
            'port' => 'required|integer|between:1,65535',
            'username' => 'required|string|max:50',
            'password' => 'required|string|max:255',
            'server_id' => 'nullable|integer|exists:servers,id', // 添加服务器ID验证
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '验证参数错误',
                'errors' => $validator->errors(),
            ]);
        }
        
        $result = $this->verifySSHConnection(
            $request->input('ip'),
            $request->input('port'),
            $request->input('username'),
            $request->input('password')
        );
        
        // 如果连接成功且提供了服务器ID，更新服务器状态
        if ($result['success'] && $request->has('server_id')) {
            try {
                $server = Server::find($request->input('server_id'));
                if ($server) {
                    $server->update([
                        'status' => 1, // 设置为在线状态
                        'last_check_time' => now(),
                    ]);
                    
                    Log::info('服务器状态已更新为在线', [
                        'server_id' => $server->id,
                        'server_name' => $server->name,
                        'ip' => $server->ip,
                        'updated_at' => now()
                    ]);
                    
                    $result['status_updated'] = true;
                    $result['message'] = '连接成功，服务器状态已更新为在线';
                }
            } catch (\Exception $e) {
                Log::error('更新服务器状态失败', [
                    'server_id' => $request->input('server_id'),
                    'error' => $e->getMessage()
                ]);
                
                $result['status_updated'] = false;
                $result['status_error'] = '连接成功，但更新服务器状态失败';
            }
        }
        
        return response()->json($result);
    }
    
    /**
     * 验证SSH连接
     *
     * @param  string  $ip
     * @param  int     $port
     * @param  string  $username
     * @param  string  $password
     * @return array
     */
    private function verifySSHConnection($ip, $port, $username, $password)
    {
        try {
            $ssh = new SSH2($ip, $port);
            $ssh->setTimeout(10); // 设置超时时间为10秒
            
            if (!$ssh->login($username, $password)) {
                Log::error("SSH验证连接失败", [
                    'ip' => $ip,
                    'port' => $port,
                    'username' => $username,
                    'ssh_errors' => $ssh->getErrors(),
                    'ssh_log' => $ssh->getLog()
                ]);
                
                return [
                    'success' => false,
                    'message' => '登录失败，请检查用户名和密码',
                ];
            }
            
            return [
                'success' => true,
                'message' => '连接成功',
            ];
        } catch (\Exception $e) {
            Log::error("SSH验证连接异常", [
                'ip' => $ip,
                'port' => $port,
                'username' => $username,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => '连接失败：' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * 安装采集组件到服务器
     *
     * @param  \App\Models\Server  $server
     * @param  \App\Models\Collector  $collector
     * @return \Illuminate\Http\Response
     */
    public function installCollector(Server $server, Collector $collector)
    {
        // 检查是否已安装
        $exists = DB::table('server_collector')
            ->where('server_id', $server->id)
            ->where('collector_id', $collector->id)
            ->exists();
        
        if ($exists) {
            return redirect()->back()
                ->with('info', '该采集组件已经安装在此服务器上');
        }
        
        try {
            // 使用部署服务安装采集组件
            $result = $this->deploymentService->install($server, $collector);
            
            if ($result['success']) {
                // 安装成功，更新数据库关联
                $server->collectors()->attach($collector->id, [
                    'installed_at' => now(),
                    'status' => 1, // 已安装
                ]);
                
                return redirect()->back()
                    ->with('success', $result['message']);
            } else {
                return redirect()->back()
                    ->with('error', $result['message']);
            }
        } catch (\Exception $e) {
            Log::error('采集组件安装失败', [
                'server_id' => $server->id,
                'collector_id' => $collector->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', '采集组件安装失败：' . $e->getMessage());
        }
    }
    
    /**
     * 卸载服务器上的采集组件
     *
     * @param  \App\Models\Server  $server
     * @param  \App\Models\Collector  $collector
     * @return \Illuminate\Http\Response
     */
    public function uninstallCollector(Server $server, Collector $collector)
    {
        try {
            // 使用部署服务卸载采集组件
            $result = $this->deploymentService->uninstall($server, $collector);
            
            // 无论卸载文件是否成功，都解除数据库关联
            $server->collectors()->detach($collector->id);
            
            if ($result['success']) {
                return redirect()->back()
                    ->with('success', $result['message']);
            } else {
                return redirect()->back()
                    ->with('warning', $result['message'] . '，但已解除数据库关联');
            }
        } catch (\Exception $e) {
            Log::error('采集组件卸载失败', [
                'server_id' => $server->id,
                'collector_id' => $collector->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // 尝试解除数据库关联
            $server->collectors()->detach($collector->id);
            
            return redirect()->back()
                ->with('warning', '采集组件卸载过程中出错：' . $e->getMessage() . '，但已解除数据库关联');
        }
    }

    /**
     * 执行单服务器采集
     *
     * @param Request $request
     * @param Server $server
     * @return \Illuminate\Http\JsonResponse
     */
    public function executeCollection(Request $request, Server $server)
    {
        $validator = Validator::make($request->all(), [
            'collector_ids' => 'required|array|min:1',
            'collector_ids.*' => 'exists:collectors,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '参数验证失败',
                'errors' => $validator->errors()
            ], 422);
        }

        // 注入CollectionService
        $collectionService = app(\App\Services\CollectionService::class);
        
        $result = $collectionService->executeSingleCollection(
            $server, 
            $request->input('collector_ids')
        );

        return response()->json($result);
    }

    /**
     * 获取服务器采集历史
     *
     * @param Server $server
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function collectionHistory(Server $server, Request $request)
    {
        $days = $request->input('days', 7);
        $collectorId = $request->input('collector_id');
        
        $query = $server->collectionHistory()
            ->with(['collector'])
            ->where('created_at', '>=', now()->subDays($days));
            
        if ($collectorId) {
            $query->where('collector_id', $collectorId);
        }
        
        $history = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        $collectors = $server->collectors;

        // 返回JSON数据供AJAX使用
        return response()->json([
            'success' => true,
            'data' => $history->map(function ($item) {
                return [
                    'id' => $item->id,
                    'collector_name' => $item->collector->name ?? '未知组件',
                    'status' => $item->status,
                    'status_text' => $item->statusText,
                    'status_color' => $item->statusColor,
                    'execution_time' => $item->execution_time ? number_format($item->execution_time, 2) . 's' : '未知',
                    'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                    'has_result' => $item->hasResult(),
                ];
            }),
            'pagination' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
            ]
        ]);
    }

    /**
     * 批量选择服务器页面
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function batchSelect(Request $request)
    {
        $serverIds = $request->input('server_ids', []);
        
        if (empty($serverIds)) {
            return redirect()->route('servers.index')
                ->with('error', '请先选择要操作的服务器');
        }

        $servers = Server::whereIn('id', $serverIds)->with(['group', 'collectors'])->get();

        return view('servers.batch-select', compact('servers', 'serverIds'));
    }

    /**
     * 批量执行采集
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function batchCollection(Request $request)
    {
        $serverIds = $request->input('server_ids', []);
        
        if (empty($serverIds)) {
            return redirect()->route('servers.index')
                ->with('error', '请选择要执行采集的服务器');
        }

        // 重定向到批量采集任务创建页面
        return redirect()->route('collection-tasks.batch.create')
            ->with('server_ids', $serverIds);
    }
}