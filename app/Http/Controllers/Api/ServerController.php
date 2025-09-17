<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Services\CollectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ServerController extends Controller
{
    /**
     * 采集服务
     *
     * @var CollectionService
     */
    protected $collectionService;

    /**
     * 构造函数
     *
     * @param CollectionService $collectionService
     */
    public function __construct(CollectionService $collectionService)
    {
        $this->collectionService = $collectionService;
    }

    /**
     * 获取服务器列表（分页）
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $groupId = $request->input('group_id');
        
        // 构建缓存键名
        $cacheKey = 'servers';
        if ($groupId) {
            $cacheKey .= ':group_' . $groupId;
        }
        $cacheKey .= ':page_' . $request->input('page', 1) . ':per_page_' . $perPage;
        
        // 尝试从缓存获取数据
        $servers = Cache::remember($cacheKey, 3600, function () use ($request, $perPage, $groupId) {
            Log::info('从数据库获取服务器数据');
            
            $query = Server::query();
            
            if ($groupId) {
                $query->where('group_id', $groupId);
            }
            
            return $query->with('group')
                ->orderBy('group_id')
                ->orderBy('name')
                ->paginate($perPage);
        });
        
        return response()->json($servers);
    }

    /**
     * 获取服务器共同的采集组件
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCommonCollectors(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'server_ids' => 'required|array|min:1',
            'server_ids.*' => 'exists:servers,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '参数验证失败',
                'errors' => $validator->errors()
            ], 422);
        }

        $serverIds = $request->input('server_ids', []);
        $collectors = $this->collectionService->getCommonCollectors($serverIds);

        return response()->json([
            'success' => true,
            'data' => $collectors
        ]);
    }

    /**
     * 获取服务器的采集历史
     *
     * @param Server $server
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCollectionHistory(Server $server, Request $request)
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

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }

    /**
     * 获取服务器状态统计
     *
     * @param Server $server
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats(Server $server)
    {
        $stats = [
            'basic_info' => [
                'id' => $server->id,
                'name' => $server->name,
                'ip' => $server->ip,
                'status' => $server->status,
                'group_name' => $server->group->name ?? '无分组',
                'last_check_time' => $server->last_check_time,
                'last_collection_time' => $server->lastCollectionTime
            ],
            'collectors' => [
                'total' => $server->collectors()->count(),
                'active' => $server->collectors()->where('status', 1)->count()
            ],
            'recent_collections' => [
                'total' => $server->collectionHistory()->recent(7)->count(),
                'success' => $server->collectionHistory()->recent(7)->success()->count(),
                'failed' => $server->collectionHistory()->recent(7)->failed()->count()
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}