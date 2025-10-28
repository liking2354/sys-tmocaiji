<?php

namespace App\Http\Controllers;

use App\Models\CloudPlatform;
use App\Models\CloudResource;
use App\Models\CloudComputeResource;
use App\Services\CloudPlatform\CloudPlatformFactory;
use App\Services\CloudPlatform\Components\EcsComponent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class CloudEcsController extends Controller
{
    /**
     * 显示ECS资源列表页面
     */
    public function index()
    {
        $platforms = CloudPlatform::forUser()->active()->get();
        return view('cloud.ecs.index', compact('platforms'));
    }

    /**
     * 获取ECS资源列表
     */
    public function list(Request $request): JsonResponse
    {
        try {
            $query = CloudResource::with(['platform', 'computeResource'])
                ->where('resource_type', 'ecs');

            // 按平台筛选
            if ($request->filled('platform_id')) {
                $query->where('platform_id', $request->platform_id);
            }

            // 按区域筛选
            if ($request->filled('region')) {
                $query->where('region', $request->region);
            }

            // 按状态筛选
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // 搜索
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('resource_id', 'like', "%{$search}%");
                });
            }

            // 分页
            $perPage = $request->get('per_page', 15);
            $resources = $query->orderBy('updated_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $resources->items(),
                'pagination' => [
                    'current_page' => $resources->currentPage(),
                    'last_page' => $resources->lastPage(),
                    'per_page' => $resources->perPage(),
                    'total' => $resources->total(),
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取ECS资源列表失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 同步ECS资源
     */
    public function sync(Request $request): JsonResponse
    {
        $request->validate([
            'platform_id' => 'required|exists:cloud_platforms,id',
            'region' => 'nullable|string',
        ]);

        try {
            $platform = CloudPlatform::findOrFail($request->platform_id);
            
            // 检查权限
            if (!$this->canAccessPlatform($platform)) {
                return response()->json([
                    'success' => false,
                    'message' => '无权限访问该云平台'
                ], 403);
            }

            // 创建云平台实例
            $cloudPlatform = CloudPlatformFactory::create($platform);
            
            // 创建ECS组件实例
            $ecsComponent = new EcsComponent($cloudPlatform);
            
            // 执行同步
            $result = $ecsComponent->syncResources($platform->id, $request->region);

            return response()->json($result);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '同步ECS资源失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取ECS资源详情
     */
    public function show($id): JsonResponse
    {
        try {
            $resource = CloudResource::with(['platform', 'computeResource'])
                ->where('resource_type', 'ecs')
                ->findOrFail($id);

            // 检查权限
            if (!$this->canAccessPlatform($resource->platform)) {
                return response()->json([
                    'success' => false,
                    'message' => '无权限访问该资源'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $resource
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取ECS资源详情失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取ECS资源监控信息
     */
    public function monitoring($id, Request $request): JsonResponse
    {
        try {
            $resource = CloudResource::with('platform')
                ->where('resource_type', 'ecs')
                ->findOrFail($id);

            // 检查权限
            if (!$this->canAccessPlatform($resource->platform)) {
                return response()->json([
                    'success' => false,
                    'message' => '无权限访问该资源'
                ], 403);
            }

            // 创建云平台实例
            $cloudPlatform = CloudPlatformFactory::create($resource->platform);
            
            // 创建ECS组件实例
            $ecsComponent = new EcsComponent($cloudPlatform);
            
            // 获取监控数据
            $options = [
                'start_time' => $request->get('start_time'),
                'end_time' => $request->get('end_time'),
                'metrics' => $request->get('metrics', ['cpu', 'memory', 'network']),
            ];
            
            $monitoringData = $ecsComponent->getResourceMonitoring($resource->resource_id, $options);

            return response()->json([
                'success' => true,
                'data' => $monitoringData
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取监控信息失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 批量同步ECS资源
     */
    public function batchSync(Request $request): JsonResponse
    {
        $request->validate([
            'platform_ids' => 'required|array',
            'platform_ids.*' => 'exists:cloud_platforms,id',
            'region' => 'nullable|string',
        ]);

        try {
            $results = [];
            $totalSynced = 0;
            $totalErrors = 0;

            foreach ($request->platform_ids as $platformId) {
                $platform = CloudPlatform::findOrFail($platformId);
                
                // 检查权限
                if (!$this->canAccessPlatform($platform)) {
                    $results[] = [
                        'platform_id' => $platformId,
                        'platform_name' => $platform->name,
                        'success' => false,
                        'message' => '无权限访问该云平台'
                    ];
                    continue;
                }

                try {
                    // 创建云平台实例
                    $cloudPlatform = CloudPlatformFactory::create($platform);
                    
                    // 创建ECS组件实例
                    $ecsComponent = new EcsComponent($cloudPlatform);
                    
                    // 执行同步
                    $result = $ecsComponent->syncResources($platform->id, $request->region);
                    
                    $results[] = array_merge($result, [
                        'platform_id' => $platformId,
                        'platform_name' => $platform->name,
                    ]);
                    
                    $totalSynced += $result['synced_count'] ?? 0;
                    $totalErrors += $result['error_count'] ?? 0;

                } catch (Exception $e) {
                    $results[] = [
                        'platform_id' => $platformId,
                        'platform_name' => $platform->name,
                        'success' => false,
                        'message' => '同步失败：' . $e->getMessage()
                    ];
                    $totalErrors++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "批量同步完成：成功 {$totalSynced} 个，失败 {$totalErrors} 个",
                'data' => $results,
                'summary' => [
                    'total_synced' => $totalSynced,
                    'total_errors' => $totalErrors,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '批量同步失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取统计信息
     */
    public function statistics(): JsonResponse
    {
        try {
            $query = CloudResource::where('resource_type', 'ecs');
            
            // 如果不是管理员，只统计当前用户的资源
            $user = auth()->user();
            $isAdmin = $user && ($user->id === 1 || (property_exists($user, 'username') && $user->username === 'admin'));
            if (!$isAdmin) {
                $platformIds = CloudPlatform::forUser()->pluck('id');
                $query->whereIn('platform_id', $platformIds);
            }

            $statistics = [
                'total_instances' => $query->count(),
                'running_instances' => $query->where('status', 'running')->count(),
                'stopped_instances' => $query->where('status', 'stopped')->count(),
                'by_platform' => $query->join('cloud_platforms', 'cloud_resources.platform_id', '=', 'cloud_platforms.id')
                    ->selectRaw('cloud_platforms.platform_type, cloud_platforms.name, COUNT(*) as count')
                    ->groupBy('cloud_platforms.id', 'cloud_platforms.platform_type', 'cloud_platforms.name')
                    ->get(),
                'by_region' => $query->selectRaw('region, COUNT(*) as count')
                    ->groupBy('region')
                    ->get(),
                'by_status' => $query->selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->get(),
            ];

            // 获取即将到期的实例数量
            $expiringInstances = CloudComputeResource::whereHas('cloudResource', function($q) use ($isAdmin) {
                $q->where('resource_type', 'ecs');
                if (!$isAdmin) {
                    $platformIds = CloudPlatform::forUser()->pluck('id');
                    $q->whereIn('platform_id', $platformIds);
                }
            })->expiringSoon()->count();

            $statistics['expiring_instances'] = $expiringInstances;

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取统计信息失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 检查是否可以访问平台
     */
    private function canAccessPlatform(CloudPlatform $platform): bool
    {
        $user = auth()->user();
        
        // 管理员可以访问所有平台
        $isAdmin = $user && ($user->id === 1 || (property_exists($user, 'username') && $user->username === 'admin'));
        if ($isAdmin) {
            return true;
        }

        // 普通用户只能访问自己的平台
        return $platform->user_id === $user->id;
    }
}