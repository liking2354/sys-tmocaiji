<?php

namespace App\Http\Controllers;

use App\Models\CloudPlatform;
use App\Models\CloudResource;
use App\Services\CloudResourceManagementService;
use App\Services\DictService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CloudResourceController extends Controller
{
    protected CloudResourceManagementService $resourceService;
    protected DictService $dictService;
    protected $platformFactory;

    public function __construct(
        CloudResourceManagementService $resourceService, 
        DictService $dictService,
        \App\Services\CloudPlatform\CloudPlatformFactory $platformFactory
    ) {
        $this->resourceService = $resourceService;
        $this->dictService = $dictService;
        $this->platformFactory = $platformFactory;
    }

    /**
     * 显示云资源列表
     */
    public function index(Request $request): View
    {
        $platforms = CloudPlatform::active()->get();
        $resourceCategories = $this->dictService->getResourceCategories();
        $resourceTypes = $this->dictService->getResourceTypes();
        
        $filters = $request->only([
            'platform_id', 'resource_category', 'resource_type', 'status', 'region_id', 
            'name', 'resource_id', 'sync_date_from', 'sync_date_to'
        ]);
        
        $resources = $this->resourceService->searchResources($filters);
        $statistics = $this->resourceService->getResourceStatistics();

        return view('cloud.resources.index', compact(
            'resources', 'platforms', 'resourceCategories', 'resourceTypes', 'statistics', 'filters'
        ));
    }

    /**
     * 显示资源详情
     */
    public function show(CloudResource $resource): View
    {
        $resource->load(['platform', 'region']);
        
        return view('cloud.resources.show', compact('resource'));
    }

    /**
     * 同步平台资源
     */
    public function sync(Request $request): JsonResponse
    {
        $request->validate([
            'platform_id' => 'required|exists:cloud_platforms,id',
            'resource_types' => 'array',
            'resource_types.*' => 'string'
        ]);

        try {
            $results = $this->resourceService->syncPlatformResources(
                $request->platform_id,
                $request->resource_types ?? []
            );

            return response()->json([
                'success' => true,
                'message' => '资源同步完成',
                'data' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '资源同步失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取资源统计信息
     */
    public function statistics(Request $request): JsonResponse
    {
        $platformId = $request->get('platform_id');
        $statistics = $this->resourceService->getResourceStatistics($platformId);

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    /**
     * 搜索资源
     */
    public function search(Request $request): JsonResponse
    {
        $filters = $request->only([
            'platform_id', 'resource_type', 'status', 'region',
            'name', 'resource_id', 'sync_date_from', 'sync_date_to',
            'sort_by', 'sort_order', 'per_page'
        ]);

        $resources = $this->resourceService->searchResources($filters);

        return response()->json([
            'success' => true,
            'data' => $resources
        ]);
    }

    /**
     * 批量删除资源
     */
    public function batchDelete(Request $request): JsonResponse
    {
        $request->validate([
            'resource_ids' => 'required|array',
            'resource_ids.*' => 'integer|exists:cloud_resources,id'
        ]);

        try {
            $deletedCount = $this->resourceService->batchDeleteResources($request->resource_ids);

            return response()->json([
                'success' => true,
                'message' => "成功删除 {$deletedCount} 条资源记录"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '删除失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 清理过期资源
     */
    public function cleanup(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'integer|min:1|max:365'
        ]);

        try {
            $days = $request->get('days', 30);
            $deletedCount = $this->resourceService->cleanupExpiredResources($days);

            return response()->json([
                'success' => true,
                'message' => "成功清理 {$deletedCount} 条过期资源记录"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '清理失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取平台支持的资源类型
     */
    public function platformResourceTypes(Request $request): JsonResponse
    {
        $request->validate([
            'platform_id' => 'required|exists:cloud_platforms,id'
        ]);

        $resourceTypes = $this->resourceService->getPlatformSupportedResourceTypes($request->platform_id);

        return response()->json([
            'success' => true,
            'data' => $resourceTypes
        ]);
    }

    /**
     * 验证云平台连接
     */
    public function validateConnection(Request $request): JsonResponse
    {
        $request->validate([
            'platform_id' => 'required|exists:cloud_platforms,id'
        ]);

        try {
            $platform = CloudPlatform::findOrFail($request->platform_id);
            // 使用createFromPlatform方法以正确处理配置
            $platformService = $this->platformFactory->createFromPlatform($platform);
            
            // 测试连接
            $isConnected = $platformService->testConnection();
            
            return response()->json([
                'success' => $isConnected,
                'message' => $isConnected ? '连接验证成功' : '连接验证失败'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '连接验证失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取同步配置
     */
    public function getSyncConfig(Request $request): JsonResponse
    {
        $request->validate([
            'platform_id' => 'required|exists:cloud_platforms,id',
            'resource_category' => 'nullable|string',
            'resource_types' => 'nullable|array'
        ]);

        try {
            $config = $this->resourceService->prepareSyncConfig(
                $request->platform_id,
                $request->resource_category,
                $request->resource_types ?? []
            );

            return response()->json([
                'success' => true,
                'data' => $config
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取同步配置失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 带进度的同步资源
     */
    public function syncWithProgress(Request $request): JsonResponse
    {
        $request->validate([
            'platform_id' => 'required|exists:cloud_platforms,id',
            'config' => 'required|array'
        ]);

        try {
            $taskId = $this->resourceService->startSyncTask(
                $request->platform_id,
                $request->config
            );

            return response()->json([
                'success' => true,
                'data' => ['task_id' => $taskId]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '启动同步任务失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取同步进度
     */
    public function getSyncProgress(Request $request): JsonResponse
    {
        $request->validate([
            'task_id' => 'required|string'
        ]);

        try {
            $progress = $this->resourceService->getSyncProgress($request->task_id);

            return response()->json([
                'success' => true,
                'data' => $progress
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取同步进度失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 导出资源数据
     */
    public function export(Request $request): JsonResponse
    {
        $filters = $request->only([
            'platform_id', 'resource_type', 'status', 'region',
            'name', 'resource_id', 'sync_date_from', 'sync_date_to'
        ]);

        try {
            // 这里可以实现导出逻辑，比如生成Excel文件
            // $exportService = new CloudResourceExportService();
            // $filePath = $exportService->export($filters);

            return response()->json([
                'success' => true,
                'message' => '导出功能待实现',
                'data' => ['filters' => $filters]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '导出失败：' . $e->getMessage()
            ], 500);
        }
    }
}