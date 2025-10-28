<?php

namespace App\Http\Controllers;

use App\Models\CloudPlatform;
use App\Services\CloudResourceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class CloudPlatformController extends Controller
{
    protected CloudResourceService $cloudResourceService;

    public function __construct(CloudResourceService $cloudResourceService)
    {
        $this->cloudResourceService = $cloudResourceService;
    }

    /**
     * 显示云平台列表
     */
    public function index(Request $request)
    {
        $query = CloudPlatform::with('user')->forUser();

        // 搜索过滤
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('platform_type', 'like', "%{$search}%");
            });
        }

        // 平台类型过滤
        if ($request->filled('platform_type')) {
            $query->where('platform_type', $request->get('platform_type'));
        }

        // 状态过滤
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $platforms = $query->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('cloud.platforms.index', compact('platforms'));
    }

    /**
     * 显示创建云平台表单
     */
    public function create()
    {
        $platformTypes = [
            'huawei' => '华为云',
            'alibaba' => '阿里云',
            'tencent' => '腾讯云',
        ];

        return view('cloud.platforms.create', compact('platformTypes'));
    }

    /**
     * 存储新的云平台配置
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'platform_type' => 'required|in:huawei,alibaba,tencent',
            'access_key_id' => 'required|string|max:255',
            'access_key_secret' => 'required|string|max:255',
            'region' => 'nullable|string|max:255',
            'config' => 'nullable|string',
        ]);

        try {
            // 处理 config 字段
            $config = [];
            if ($request->filled('config')) {
                try {
                    $config = json_decode($request->config, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new \Exception('配置格式不正确，请输入有效的JSON格式');
                    }
                } catch (\Exception $e) {
                    return back()->withErrors(['config' => $e->getMessage()])->withInput();
                }
            }

            // 直接创建平台配置，不进行连接测试和区域同步
            $platform = CloudPlatform::create([
                'name' => $request->name,
                'platform_type' => $request->platform_type,
                'access_key_id' => $request->access_key_id,
                'access_key_secret' => $request->access_key_secret,
                'region' => $request->region,
                'user_id' => Auth::id(),
                'status' => $request->status ?? 'active',
                'config' => $config,
            ]);

            Log::info('Cloud platform created successfully', [
                'platform_id' => $platform->id,
                'platform_name' => $platform->name,
                'user_id' => Auth::id()
            ]);

            return redirect()->route('cloud.platforms.index')
                ->with('success', '云平台配置创建成功！');

        } catch (Exception $e) {
            Log::error('Failed to create cloud platform', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->except(['access_key_secret'])
            ]);

            return back()->withErrors(['error' => '创建失败：' . $e->getMessage()])
                ->withInput();
        }
    }





    /**
     * 显示云平台详情
     */
    public function show(CloudPlatform $platform)
    {
        $this->authorize('view', $platform);

        $platform->load('user', 'resources');

        // 获取资源统计
        $resourceStats = $platform->resources()
            ->selectRaw('resource_type, count(*) as count')
            ->groupBy('resource_type')
            ->pluck('count', 'resource_type')
            ->toArray();

        // 获取区域统计
        $regionStats = $platform->resources()
            ->selectRaw('region, count(*) as count')
            ->groupBy('region')
            ->pluck('count', 'region')
            ->toArray();

        // 如果是 AJAX/JSON 请求，返回 JSON 数据，供列表页编辑模态使用
        if (request()->ajax() || request()->wantsJson() || request()->expectsJson()) {
            return response()->json([
                'id' => $platform->id,
                'name' => $platform->name,
                'platform_type' => $platform->platform_type,
                'access_key_id' => $platform->access_key_id,
                // 不返回 access_key_secret 以保证安全
                'region' => $platform->region,
                'status' => $platform->status,
                'config' => $platform->config,
                'user' => [
                    'id' => $platform->user->id ?? null,
                    'name' => $platform->user->name ?? null,
                ],
                'resource_stats' => $resourceStats,
                'region_stats' => $regionStats,
            ]);
        }

        // 非 AJAX 请求，返回视图（如后续需要可创建 cloud.platforms.show）
        return view('cloud.platforms.show', compact('platform', 'resourceStats', 'regionStats'));
    }

    /**
     * 显示编辑云平台表单
     */
    public function edit(CloudPlatform $platform)
    {
        $this->authorize('update', $platform);

        $platformTypes = [
            'huawei' => '华为云',
            'alibaba' => '阿里云',
            'tencent' => '腾讯云',
        ];

        return view('cloud.platforms.edit', compact('platform', 'platformTypes'));
    }

    /**
     * 更新云平台配置
     */
    public function update(Request $request, CloudPlatform $platform)
    {
        $this->authorize('update', $platform);

        $request->validate([
            'name' => 'required|string|max:255',
            'access_key_id' => 'required|string|max:255',
            'access_key_secret' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'config' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // 处理 config 字段
            $config = $platform->config ?? [];
            if ($request->filled('config')) {
                try {
                    $config = json_decode($request->config, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new \Exception('配置格式不正确，请输入有效的JSON格式');
                    }
                } catch (\Exception $e) {
                    return back()->withErrors(['config' => $e->getMessage()])->withInput();
                }
            }

            // 准备更新数据
            $updateData = [
                'name' => $request->name,
                'access_key_id' => $request->access_key_id,
                'region' => $request->region,
                'status' => $request->status,
                'config' => $config,
            ];

            // 只有在提供了新密码时才更新
            if ($request->filled('access_key_secret')) {
                $updateData['access_key_secret'] = $request->access_key_secret;
            }

            $platform->update($updateData);

            // 如果状态为激活，测试连接
            if ($request->status === 'active') {
                try {
                    $connectionResult = $this->cloudResourceService->testPlatformConnection($platform);
                    
                    if (!$connectionResult['success']) {
                        DB::rollBack();
                        return back()->withErrors(['connection' => $connectionResult['message']])
                            ->withInput();
                    }
                } catch (\Exception $e) {
                    // 如果连接测试失败，记录日志但不阻止保存
                    Log::warning('Connection test failed during platform update', [
                        'platform_id' => $platform->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            // 如果是 AJAX 请求，返回 JSON 响应
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => '云平台配置更新成功！'
                ]);
            }

            return redirect()->route('cloud.platforms.index')
                ->with('success', '云平台配置更新成功！');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update cloud platform', [
                'platform_id' => $platform->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            // 如果是 AJAX 请求，返回 JSON 错误响应
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '更新失败：' . $e->getMessage()
                ], 500);
            }

            return back()->withErrors(['error' => '更新失败：' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * 删除云平台配置
     */
    public function destroy(CloudPlatform $platform)
    {
        $this->authorize('delete', $platform);

        try {
            DB::beginTransaction();

            // 删除相关资源
            $platform->resources()->delete();
            
            // 删除平台配置
            $platform->delete();

            DB::commit();

            // 如果是 AJAX 请求，返回 JSON 响应
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => '云平台删除成功！'
                ]);
            }

            return redirect()->route('cloud.platforms.index')
                ->with('success', '云平台配置删除成功！');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete cloud platform', [
                'platform_id' => $platform->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            // 如果是 AJAX 请求，返回 JSON 错误响应
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '删除失败：' . $e->getMessage()
                ], 500);
            }

            return back()->withErrors(['error' => '删除失败：' . $e->getMessage()]);
        }
    }

    /**
     * 测试云平台连接
     */
    public function testConnection(CloudPlatform $platform)
    {
        $this->authorize('view', $platform);

        try {
            // 记录收到的模型字段
            Log::info('TestConnection controller received', [
                'platform_id' => $platform->id,
                'name' => $platform->name,
                'platform_type_raw' => $platform->platform_type,
                'region' => $platform->region,
            ]);

            // 若平台类型为空，尝试 fresh 一次
            if (empty($platform->platform_type)) {
                $fresh = CloudPlatform::find($platform->id);
                if ($fresh) {
                    $platform = $fresh;
                    Log::info('TestConnection controller refreshed model', [
                        'platform_id' => $platform->id,
                        'name' => $platform->name,
                        'platform_type_raw' => $platform->platform_type,
                        'region' => $platform->region,
                    ]);
                }
            }

            $result = $this->cloudResourceService->testPlatformConnection($platform);

            // 附带调试信息返回，方便前端确认
            $result['platform_type'] = $platform->platform_type;
            $result['platform_name'] = $platform->name ?? ($result['platform_name'] ?? null);

            return response()->json($result);

        } catch (Exception $e) {
            Log::error('Failed to test cloud platform connection', [
                'platform_id' => $platform->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => '连接测试失败：' . $e->getMessage(),
                'platform_type' => $platform->platform_type,
                'platform_name' => $platform->name,
            ], 500);
        }
    }

    /**
     * 同步云平台资源
     */
    /**
     * 使用请求体配置测试连接（创建/编辑页）
     */
    public function testConnectionConfig(Request $request)
    {
        $this->authorize('viewAny', CloudPlatform::class);

        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'platform_type' => 'required|in:huawei,alibaba,tencent',
            'access_key_id' => 'required|string|max:255',
            'access_key_secret' => 'required|string|max:255',
            'region' => 'nullable|string|max:255',
            'config' => 'nullable|string', // 添加config参数验证
        ]);

        try {
            Log::info('TestConnectionConfig received', [
                'platform_type' => $data['platform_type'],
                'region' => $data['region'],
                'has_access_key_id' => !empty($data['access_key_id']),
                'has_access_key_secret' => !empty($data['access_key_secret']),
                'has_config' => !empty($data['config']),
                'user_id' => Auth::id(),
            ]);

            // 创建临时CloudPlatform对象用于测试
            $tempPlatform = new CloudPlatform([
                'name' => $data['name'] ?? '测试配置',
                'platform_type' => $data['platform_type'],
                'access_key_id' => $data['access_key_id'],
                'access_key_secret' => $data['access_key_secret'],
                'region' => $data['region'],
                'config' => $data['config'] ?? null, // 正确的字段名是config
                'status' => 'active',
                'user_id' => Auth::id(),
            ]);

            // 通过工厂创建适配器
            $adapter = \App\Services\CloudPlatform\CloudPlatformFactory::createFromPlatform($tempPlatform);

            $ok = $adapter->testConnection();

            return response()->json([
                'success' => $ok,
                'message' => $ok ? '连接成功' : '连接失败: SDK初始化或凭证/区域错误，请检查 Access Key 与 Region',
                'platform_type' => $data['platform_type'],
                'region' => $data['region'],
            ]);
        } catch (Exception $e) {
            Log::error('Failed to test cloud platform connection (config)', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->except(['access_key_secret'])
            ]);

            return response()->json([
                'success' => false,
                'message' => '连接测试失败：' . $e->getMessage(),
                'platform_type' => $data['platform_type'] ?? null,
            ], 500);
        }
    }

    public function syncResources(CloudPlatform $platform)
    {
        $this->authorize('update', $platform);

        try {
            Log::info('开始同步云平台资源', [
                'platform_id' => $platform->id,
                'platform_name' => $platform->name,
                'platform_type' => $platform->platform_type
            ]);

            $result = $this->cloudResourceService->syncPlatformResources($platform);
            
            if ($result['success']) {
                Log::info('云平台资源同步成功', [
                    'platform_id' => $platform->id,
                    'results' => $result
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => '资源同步成功！',
                    'data' => $result
                ]);
            } else {
                Log::warning('云平台资源同步失败', [
                    'platform_id' => $platform->id,
                    'error' => $result['error'] ?? '未知错误'
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => '资源同步失败：' . ($result['error'] ?? '未知错误'),
                    'data' => $result
                ], 500);
            }

        } catch (Exception $e) {
            Log::error('云平台资源同步异常', [
                'platform_id' => $platform->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => '资源同步失败：' . $e->getMessage(),
                'error_details' => [
                    'type' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }

    /**
     * 同步云平台可用区
     */
    public function syncRegions(CloudPlatform $platform)
    {
        try {
            // 检查平台状态
            if ($platform->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => '云平台未启用，无法同步可用区'
                ]);
            }

            // 同步可用区
            $result = $this->cloudResourceService->syncRegions($platform);

            Log::info('Cloud platform regions synced successfully', [
                'platform_id' => $platform->id,
                'platform_name' => $platform->name,
                'regions_count' => $result['synced_regions'] ?? 0,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "成功同步 {$result['synced_regions']} 个可用区",
                'count' => $result['synced_regions'],
                'data' => $result
            ]);

        } catch (Exception $e) {
            Log::error('Failed to sync cloud platform regions', [
                'platform_id' => $platform->id,
                'platform_name' => $platform->name,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => '可用区同步失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 批量操作
     */
    public function batchAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,activate,deactivate,sync',
            'platform_ids' => 'required|array|min:1',
            'platform_ids.*' => 'exists:cloud_platforms,id'
        ]);

        try {
            $platforms = CloudPlatform::whereIn('id', $request->platform_ids)
                ->forUser()
                ->get();

            if ($platforms->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => '未找到可操作的云平台'
                ], 404);
            }

            DB::beginTransaction();

            $successCount = 0;
            $errors = [];

            foreach ($platforms as $platform) {
                try {
                    switch ($request->action) {
                        case 'delete':
                            $platform->resources()->delete();
                            $platform->delete();
                            break;
                        case 'activate':
                            $platform->update(['status' => 'active']);
                            break;
                        case 'deactivate':
                            $platform->update(['status' => 'inactive']);
                            break;
                        case 'sync':
                            $this->cloudResourceService->syncPlatformResources($platform);
                            break;
                    }
                    $successCount++;
                } catch (Exception $e) {
                    $errors[] = "平台 {$platform->name}: " . $e->getMessage();
                }
            }

            DB::commit();

            $message = "成功处理 {$successCount} 个云平台";
            if (!empty($errors)) {
                $message .= "，失败：" . implode('; ', $errors);
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to perform batch action on cloud platforms', [
                'action' => $request->action,
                'platform_ids' => $request->platform_ids,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => '批量操作失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取智能默认区域列表
     * 优先从数据库中的可用区管理获取，没有则返回默认数据
     */
    public function getRegionsByPlatform($platformType)
    {
        // 验证平台类型
        if (!in_array($platformType, ['huawei', 'alibaba', 'tencent'])) {
            return response()->json([
                'success' => false,
                'message' => '不支持的平台类型'
            ], 422);
        }

        try {
            // 首先尝试从数据库中获取该平台类型的可用区
            $dbRegions = \App\Models\CloudRegion::byPlatformType($platformType)
                ->active()
                ->select('region_code', 'region_name')
                ->get();

            // 如果数据库中有数据，使用数据库数据
            if ($dbRegions->isNotEmpty()) {
                $regions = $dbRegions->map(function ($region) {
                    return [
                        'value' => $region->region_code,
                        'label' => $region->region_name
                    ];
                })->toArray();

                return response()->json([
                    'success' => true,
                    'regions' => $regions,
                    'source' => 'database',
                    'message' => '从数据库获取可用区列表'
                ]);
            }

            // 如果数据库中没有数据，使用默认数据
            $defaultRegions = $this->getDefaultRegionsByPlatform($platformType);

            return response()->json([
                'success' => true,
                'regions' => $defaultRegions,
                'source' => 'default',
                'message' => '使用默认可用区列表'
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get regions by platform', [
                'platform_type' => $platformType,
                'error' => $e->getMessage()
            ]);

            // 出错时返回默认数据
            $defaultRegions = $this->getDefaultRegionsByPlatform($platformType);

            return response()->json([
                'success' => true,
                'regions' => $defaultRegions,
                'source' => 'fallback',
                'message' => '获取数据库数据失败，使用默认数据'
            ]);
        }
    }

    /**
     * 获取默认区域数据
     */
    private function getDefaultRegionsByPlatform($platformType)
    {
        $regions = [
            'huawei' => [
                ['value' => 'cn-north-1', 'label' => '华北-北京一'],
                ['value' => 'cn-north-4', 'label' => '华北-北京四'],
                ['value' => 'cn-east-2', 'label' => '华东-上海二'],
                ['value' => 'cn-east-3', 'label' => '华东-上海一'],
                ['value' => 'cn-south-1', 'label' => '华南-广州'],
                ['value' => 'cn-southwest-2', 'label' => '西南-贵阳一'],
                ['value' => 'ap-southeast-1', 'label' => '亚太-香港'],
                ['value' => 'ap-southeast-2', 'label' => '亚太-曼谷'],
                ['value' => 'ap-southeast-3', 'label' => '亚太-新加坡']
            ],
            'alibaba' => [
                ['value' => 'cn-hangzhou', 'label' => '华东1（杭州）'],
                ['value' => 'cn-shanghai', 'label' => '华东2（上海）'],
                ['value' => 'cn-qingdao', 'label' => '华北1（青岛）'],
                ['value' => 'cn-beijing', 'label' => '华北2（北京）'],
                ['value' => 'cn-zhangjiakou', 'label' => '华北3（张家口）'],
                ['value' => 'cn-huhehaote', 'label' => '华北5（呼和浩特）'],
                ['value' => 'cn-shenzhen', 'label' => '华南1（深圳）'],
                ['value' => 'cn-hongkong', 'label' => '香港'],
                ['value' => 'ap-southeast-1', 'label' => '新加坡']
            ],
            'tencent' => [
                ['value' => 'ap-beijing', 'label' => '华北地区（北京）'],
                ['value' => 'ap-shanghai', 'label' => '华东地区（上海）'],
                ['value' => 'ap-guangzhou', 'label' => '华南地区（广州）'],
                ['value' => 'ap-chengdu', 'label' => '西南地区（成都）'],
                ['value' => 'ap-chongqing', 'label' => '西南地区（重庆）'],
                ['value' => 'ap-nanjing', 'label' => '华东地区（南京）'],
                ['value' => 'ap-hongkong', 'label' => '港澳台地区（中国香港）'],
                ['value' => 'ap-singapore', 'label' => '亚太东南（新加坡）'],
                ['value' => 'ap-tokyo', 'label' => '亚太东北（东京）'],
                ['value' => 'us-west-1', 'label' => '美国西部（硅谷）']
            ]
        ];

        return $regions[$platformType] ?? [];
    }
}