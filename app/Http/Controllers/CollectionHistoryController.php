<?php

namespace App\Http\Controllers;

use App\Models\CollectionHistory;
use App\Models\Server;
use App\Models\Collector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CollectionHistoryController extends Controller
{
    /**
     * 构造函数
     */
    public function __construct()
    {
        // 移除JsonFormatterService依赖
    }
    
    /**
     * 显示采集历史列表
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = CollectionHistory::with(['server', 'collector', 'taskDetail.task']);

        // 服务器筛选
        if ($request->filled('server_id')) {
            $query->where('server_id', $request->server_id);
        }

        // 采集组件筛选
        if ($request->filled('collector_id')) {
            $query->where('collector_id', $request->collector_id);
        }

        // 状态筛选
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 日期范围筛选
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // 排序
        $query->orderBy('created_at', 'desc');

        // 分页
        $histories = $query->paginate(20);

        // 获取筛选选项数据
        $servers = Server::select('id', 'name', 'ip')->orderBy('name')->get();
        $collectors = Collector::select('id', 'name')->orderBy('name')->get();

        // 计算统计信息
        $statistics = $this->getStatistics($request);

        return view('collection-history.index', compact('histories', 'servers', 'collectors', 'statistics'));
    }

    /**
     * 获取统计信息
     *
     * @param Request $request
     * @return array
     */
    private function getStatistics(Request $request)
    {
        $query = CollectionHistory::query();

        // 应用相同的筛选条件
        if ($request->filled('server_id')) {
            $query->where('server_id', $request->server_id);
        }

        if ($request->filled('collector_id')) {
            $query->where('collector_id', $request->collector_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as success,
            SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as failed
        ')->first();

        $total = $stats->total ?: 0;
        $success = $stats->success ?: 0;
        $failed = $stats->failed ?: 0;
        $successRate = $total > 0 ? ($success / $total) * 100 : 0;

        return [
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'success_rate' => $successRate,
        ];
    }

    /**
     * 显示指定采集历史记录
     *
     * @param CollectionHistory $collectionHistory
     * @return \Illuminate\Http\Response
     */
    public function show(CollectionHistory $collectionHistory)
    {
        $collectionHistory->load(['server', 'collector', 'taskDetail.task']);

        return view('collection-history.show', compact('collectionHistory'));
    }

    /**
     * API: 获取采集历史结果
     *
     * @param CollectionHistory $collectionHistory
     * @return \Illuminate\Http\JsonResponse
     */
    public function getResult(CollectionHistory $collectionHistory)
    {
        try {
            // 直接返回原始数据
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $collectionHistory->id,
                    'result' => $collectionHistory->result,
                    'server' => $collectionHistory->server->name,
                    'collector' => $collectionHistory->collector->name,
                    'created_at' => $collectionHistory->created_at->format('Y-m-d H:i:s'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取结果失败: ' . $e->getMessage()
            ], 500);
        }
    }
}
