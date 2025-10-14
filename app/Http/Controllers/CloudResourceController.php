<?php

namespace App\Http\Controllers;

use App\Models\CloudResource;
use App\Models\CloudPlatform;
use App\Services\CloudResourceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class CloudResourceController extends Controller
{
    protected CloudResourceService $cloudResourceService;

    public function __construct(CloudResourceService $cloudResourceService)
    {
        $this->cloudResourceService = $cloudResourceService;
    }

    /**
     * 显示云资源列表
     */
    public function index(Request $request)
    {
        $query = CloudResource::with(['platform', 'user'])->forUser();

        // 搜索过滤
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('resource_id', 'like', "%{$search}%")
                  ->orWhere('resource_type', 'like', "%{$search}%");
            });
        }

        // 平台过滤
        if ($request->filled('platform_id')) {
            $query->where('platform_id', $request->get('platform_id'));
        }

        // 资源类型过滤
        if ($request->filled('resource_type')) {
            $query->where('resource_type', $request->get('resource_type'));
        }

        // 状态过滤
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // 区域过滤
        if ($request->filled('region')) {
            $query->where('region', $request->get('region'));
        }

        $resources = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        // 保持筛选参数在分页链接中
        $resources->appends($request->query());

        // 获取过滤选项，包含资源数量
        $platforms = CloudPlatform::forUser()->get(['id', 'name', 'platform_type']);
        
        // 为每个平台计算当前用户可见的资源数量
        foreach ($platforms as $platform) {
            $platform->resources_count = CloudResource::forUser()->where('platform_id', $platform->id)->count();
        }
        $resourceTypes = CloudResource::forUser()
            ->distinct()
            ->pluck('resource_type')
            ->sort();
        $regions = CloudResource::forUser()
            ->distinct()
            ->pluck('region')
            ->sort();

        // 获取统计数据
        $statistics = [
            'ecs' => CloudResource::forUser()->where('resource_type', 'ecs')->count(),
            'clb' => CloudResource::forUser()->where('resource_type', 'clb')->count(),
            'cdb' => CloudResource::forUser()->where('resource_type', 'cdb')->count(),
            'redis' => CloudResource::forUser()->where('resource_type', 'redis')->count(),
            'domain' => CloudResource::forUser()->where('resource_type', 'domain')->count(),
        ];

        return view('cloud.resources.index', compact(
            'resources', 
            'platforms', 
            'resourceTypes', 
            'regions',
            'statistics'
        ));
    }

    /**
     * 显示云资源详情
     */
    public function show(CloudResource $cloudResource)
    {
        $this->authorize('view', $cloudResource);

        $cloudResource->load(['platform', 'user']);

        // 获取资源详细信息
        $detailInfo = null;
        
        if ($cloudResource->platform) {
            try {
                $detailInfo = $this->cloudResourceService->getResourceDetail(
                    $cloudResource->platform,
                    $cloudResource->resource_type,
                    $cloudResource->resource_id
                );
            } catch (Exception $e) {
                Log::warning('Failed to get resource detail', [
                    'resource_id' => $cloudResource->id,
                    'platform_id' => $cloudResource->platform_id,
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            Log::warning('CloudResource has no associated platform', [
                'resource_id' => $cloudResource->id,
                'platform_id' => $cloudResource->platform_id
            ]);
        }

        return view('cloud.resources.show', [
            'cloudResource' => $cloudResource,
            'resourceDetail' => $detailInfo
        ]);
    }

    /**
     * 刷新单个资源信息
     */
    public function refresh(CloudResource $cloudResource)
    {
        $this->authorize('update', $cloudResource);

        try {
            $result = $this->cloudResourceService->refreshResource($cloudResource);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => '资源信息刷新成功！',
                    'data' => $result['resource']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => '资源信息刷新失败：' . $result['error']
                ], 500);
            }

        } catch (Exception $e) {
            Log::error('Failed to refresh cloud resource', [
                'resource_id' => $cloudResource->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => '资源信息刷新失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 删除云资源
     */
    public function destroy(CloudResource $cloudResource)
    {
        $this->authorize('delete', $cloudResource);

        try {
            $cloudResource->delete();

            return redirect()->route('cloud.resources.index')
                ->with('success', '云资源删除成功！');

        } catch (Exception $e) {
            Log::error('Failed to delete cloud resource', [
                'resource_id' => $cloudResource->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->withErrors(['error' => '删除失败：' . $e->getMessage()]);
        }
    }

    /**
     * 批量操作
     */
    public function batchAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,refresh',
            'resource_ids' => 'required'
        ]);

        try {
            // 处理 "刷新所有资源" 的情况
            if ($request->resource_ids === 'all' && $request->action === 'refresh') {
                $resources = CloudResource::forUser()->get();
            } else {
                // 验证具体的资源ID数组
                $request->validate([
                    'resource_ids' => 'array|min:1',
                    'resource_ids.*' => 'exists:cloud_resources,id'
                ]);
                
                $resources = CloudResource::whereIn('id', $request->resource_ids)
                    ->forUser()
                    ->get();
            }

            if ($resources->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => '未找到可操作的云资源'
                ], 404);
            }

            $successCount = 0;
            $errors = [];

            foreach ($resources as $resource) {
                try {
                    switch ($request->action) {
                        case 'delete':
                            $resource->delete();
                            break;
                        case 'refresh':
                            $this->cloudResourceService->refreshResource($resource);
                            break;
                    }
                    $successCount++;
                } catch (Exception $e) {
                    $errors[] = "资源 {$resource->name}: " . $e->getMessage();
                }
            }

            $message = "成功处理 {$successCount} 个云资源";
            if (!empty($errors)) {
                $message .= "，失败：" . implode('; ', $errors);
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (Exception $e) {
            Log::error('Failed to perform batch action on cloud resources', [
                'action' => $request->action,
                'resource_ids' => $request->resource_ids,
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
     * 导出云资源数据
     */
    public function export(Request $request)
    {
        $request->validate([
            'format' => 'required|in:xlsx,csv',
            'platform_id' => 'nullable|exists:cloud_platforms,id',
            'resource_type' => 'nullable|string',
            'status' => 'nullable|string',
            'region' => 'nullable|string',
        ]);

        try {
            $query = CloudResource::with(['platform', 'user'])->forUser();

            // 应用过滤条件
            if ($request->filled('platform_id')) {
                $query->where('platform_id', $request->platform_id);
            }
            if ($request->filled('resource_type')) {
                $query->where('resource_type', $request->resource_type);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('region')) {
                $query->where('region', $request->region);
            }

            $resources = $query->get();

            $filename = 'cloud_resources_' . date('Y-m-d_H-i-s') . '.' . $request->format;

            return $this->cloudResourceService->exportResources($resources, $request->format, $filename);

        } catch (Exception $e) {
            Log::error('Failed to export cloud resources', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            return back()->withErrors(['error' => '导出失败：' . $e->getMessage()]);
        }
    }

    /**
     * 获取资源统计信息
     */
    public function statistics(Request $request)
    {
        try {
            $query = CloudResource::forUser();

            // 应用时间范围过滤
            if ($request->filled('start_date')) {
                $query->where('created_at', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->where('created_at', '<=', $request->end_date . ' 23:59:59');
            }

            // 按平台统计
            $platformStats = $query->clone()
                ->join('cloud_platforms', 'cloud_resources.platform_id', '=', 'cloud_platforms.id')
                ->selectRaw('cloud_platforms.name as platform_name, cloud_platforms.platform_type, count(*) as count')
                ->groupBy('cloud_platforms.id', 'cloud_platforms.name', 'cloud_platforms.platform_type')
                ->get();

            // 按资源类型统计
            $resourceTypeStats = $query->clone()
                ->selectRaw('resource_type, count(*) as count')
                ->groupBy('resource_type')
                ->get();

            // 按状态统计
            $statusStats = $query->clone()
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->get();

            // 按区域统计
            $regionStats = $query->clone()
                ->selectRaw('region, count(*) as count')
                ->groupBy('region')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get();

            // 时间趋势统计（最近30天）
            $trendStats = $query->clone()
                ->where('created_at', '>=', now()->subDays(30))
                ->selectRaw('DATE(created_at) as date, count(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'platform_stats' => $platformStats,
                    'resource_type_stats' => $resourceTypeStats,
                    'status_stats' => $statusStats,
                    'region_stats' => $regionStats,
                    'trend_stats' => $trendStats,
                    'total_count' => $query->count(),
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get cloud resource statistics', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => '获取统计信息失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取资源监控信息
     */
    public function monitoring(CloudResource $cloudResource)
    {
        $this->authorize('view', $cloudResource);

        try {
            $monitoringData = $this->cloudResourceService->getResourceMonitoring(
                $cloudResource->platform,
                $cloudResource->resource_type,
                $cloudResource->resource_id
            );

            return response()->json([
                'success' => true,
                'data' => $monitoringData
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get resource monitoring data', [
                'resource_id' => $cloudResource->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => '获取监控信息失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 清理云平台资源
     */
    public function cleanup(Request $request)
    {
        $request->validate([
            'platform_id' => 'required|exists:cloud_platforms,id'
        ]);

        try {
            $platform = CloudPlatform::findOrFail($request->platform_id);
            
            // 检查权限
            if ($platform->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => '无权限操作此云平台'
                ], 403);
            }

            Log::info('开始清理云平台资源', [
                'platform_id' => $platform->id,
                'platform_name' => $platform->name,
                'platform_type' => $platform->platform_type,
                'user_id' => Auth::id()
            ]);

            // 删除该云平台的所有资源
            $deletedCount = CloudResource::where('platform_id', $platform->id)->delete();

            Log::info('云平台资源清理完成', [
                'platform_id' => $platform->id,
                'deleted_count' => $deletedCount,
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => '资源清理成功！',
                'deleted_count' => $deletedCount
            ]);

        } catch (Exception $e) {
            Log::error('云平台资源清理失败', [
                'platform_id' => $request->platform_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => '资源清理失败：' . $e->getMessage(),
                'error_details' => [
                    'type' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }

    /**
     * 同步云平台资源
     */
    public function syncPlatformResources(Request $request)
    {
        $request->validate([
            'platform_id' => 'required|exists:cloud_platforms,id'
        ]);

        try {
            $platform = CloudPlatform::findOrFail($request->platform_id);
            
            // 检查权限
            if ($platform->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => '无权限操作此云平台'
                ], 403);
            }

            Log::info('开始同步云平台资源', [
                'platform_id' => $platform->id,
                'platform_name' => $platform->name,
                'platform_type' => $platform->platform_type,
                'user_id' => Auth::id()
            ]);

            $result = $this->cloudResourceService->syncPlatformResources($platform);
            
            if ($result['success']) {
                // 计算总的同步资源数量
                $totalCount = 0;
                if (isset($result['synced_resources'])) {
                    foreach ($result['synced_resources'] as $regionResources) {
                        $totalCount += array_sum($regionResources);
                    }
                }

                Log::info('云平台资源同步成功', [
                    'platform_id' => $platform->id,
                    'total_synced' => $totalCount,
                    'results' => $result
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => '资源同步成功！',
                    'data' => $result,
                    'total_count' => $totalCount
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
                'platform_id' => $request->platform_id,
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
     * 获取资源详情
     */
    public function detail($id)
    {
        try {
            $resource = CloudResource::with('platform')->findOrFail($id);
            
            // 检查权限
            if ($resource->platform && $resource->platform->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => '无权限查看此资源'
                ], 403);
            }
            
            return response()->json([
                'resource_id' => $resource->resource_id,
                'name' => $resource->name,
                'resource_type' => $resource->resource_type,
                'status' => $resource->status,
                'region' => $resource->region,
                'instance_type' => $resource->instance_type,
                'private_ip' => $resource->private_ip,
                'public_ip' => $resource->public_ip,
                'platform' => [
                    'name' => $resource->platform->name ?? 'Unknown',
                    'type' => $resource->platform->platform_type ?? 'unknown'
                ],
                'metadata' => $resource->metadata ?? [],
                'raw_data' => $resource->raw_data ?? [],
                'created_at' => $resource->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $resource->updated_at->format('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            Log::error('获取资源详情失败', [
                'resource_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '获取资源详情失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取资源监控信息（用于模态框）
     */
    public function monitoringModal($id)
    {
        try {
            $resource = CloudResource::with('platform')->findOrFail($id);
            
            // 检查权限
            if ($resource->platform && $resource->platform->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => '无权限查看此资源'
                ], 403);
            }
            
            // 生成模拟监控数据
            $metrics = $this->generateMockMetrics($resource);
            
            return response()->json([
                'resource_id' => $resource->resource_id,
                'resource_type' => $resource->resource_type,
                'region' => $resource->region,
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'metrics' => $metrics
            ]);
            
        } catch (\Exception $e) {
            Log::error('获取监控信息失败', [
                'resource_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '获取监控信息失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 生成模拟监控数据
     */
    private function generateMockMetrics($resource)
    {
        $metrics = [];
        
        switch ($resource->resource_type) {
            case 'ecs':
                $metrics = [
                    'cpu_usage' => rand(10, 80),
                    'memory_usage' => rand(20, 70),
                    'disk_usage' => rand(15, 60),
                    'network_in' => rand(100, 1000),
                    'network_out' => rand(50, 800),
                    'disk_read' => rand(10, 100),
                    'disk_write' => rand(5, 50)
                ];
                break;
                
            case 'clb':
                $metrics = [
                    'active_connections' => rand(50, 500),
                    'new_connections' => rand(10, 100),
                    'requests_per_second' => rand(100, 1000),
                    'response_time' => rand(50, 200),
                    'error_rate' => rand(0, 5)
                ];
                break;
                
            case 'cdb':
                $metrics = [
                    'cpu_usage' => rand(10, 60),
                    'memory_usage' => rand(30, 80),
                    'connections' => rand(10, 100),
                    'qps' => rand(50, 500),
                    'tps' => rand(20, 200),
                    'disk_usage' => rand(20, 70)
                ];
                break;
                
            case 'redis':
                $metrics = [
                    'memory_usage' => rand(20, 80),
                    'connections' => rand(5, 50),
                    'ops_per_second' => rand(100, 1000),
                    'hit_rate' => rand(85, 99),
                    'expired_keys' => rand(0, 100)
                ];
                break;
                
            default:
                $metrics = [
                    'status' => $resource->status,
                    'last_check' => now()->format('Y-m-d H:i:s')
                ];
        }
        
        return $metrics;
    }
}