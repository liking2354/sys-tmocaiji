<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OperationLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperationLogController extends Controller
{
    /**
     * 显示操作日志列表
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = OperationLog::with('user')->orderBy('created_at', 'desc');

        // 搜索过滤
        if ($request->filled('start_date')) {
            $query->dateRange($request->start_date, null);
        }
        
        if ($request->filled('end_date')) {
            $query->dateRange(null, $request->end_date);
        }

        if ($request->filled('action')) {
            $query->action($request->action);
        }

        if ($request->filled('user_id')) {
            $query->user($request->user_id);
        }

        if ($request->filled('ip')) {
            $query->ip($request->ip);
        }

        if ($request->filled('content')) {
            $query->content($request->content);
        }

        $logs = $query->paginate(20)->appends($request->query());

        // 获取所有用户用于筛选
        $users = User::select('id', 'username')->orderBy('username')->get();

        // 获取所有操作类型用于筛选
        $actions = OperationLog::select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->toArray();

        // 统计数据
        $stats = $this->getLogStats($request);

        return view('admin.operation-logs.index', compact('logs', 'users', 'actions', 'stats'));
    }

    /**
     * 显示操作日志详情
     *
     * @param OperationLog $operationLog
     * @return \Illuminate\Http\Response
     */
    public function show(OperationLog $operationLog)
    {
        $operationLog->load('user');
        return view('admin.operation-logs.show', compact('operationLog'));
    }

    /**
     * 批量删除操作日志
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function batchDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:operation_logs,id'
        ]);

        $count = OperationLog::whereIn('id', $request->ids)->delete();

        // 记录操作日志
        OperationLog::record('delete', "批量删除了 {$count} 条操作日志");

        return response()->json([
            'success' => true,
            'message' => "成功删除 {$count} 条日志记录"
        ]);
    }

    /**
     * 清理指定天数前的日志
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function cleanup(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:365'
        ]);

        $days = $request->days;
        $cutoffDate = now()->subDays($days);
        
        $count = OperationLog::where('created_at', '<', $cutoffDate)->delete();

        // 记录操作日志
        OperationLog::record('cleanup', "清理了 {$days} 天前的操作日志，共删除 {$count} 条记录");

        return response()->json([
            'success' => true,
            'message' => "成功清理 {$count} 条日志记录"
        ]);
    }

    /**
     * 导出操作日志
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        $query = OperationLog::with('user')->orderBy('created_at', 'desc');

        // 应用相同的过滤条件
        if ($request->filled('start_date')) {
            $query->dateRange($request->start_date, null);
        }
        
        if ($request->filled('end_date')) {
            $query->dateRange(null, $request->end_date);
        }

        if ($request->filled('action')) {
            $query->action($request->action);
        }

        if ($request->filled('user_id')) {
            $query->user($request->user_id);
        }

        if ($request->filled('ip')) {
            $query->ip($request->ip);
        }

        if ($request->filled('content')) {
            $query->content($request->content);
        }

        $logs = $query->get();

        // 记录操作日志
        OperationLog::record('export', "导出了 {$logs->count()} 条操作日志");

        $filename = 'operation_logs_' . date('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            
            // 添加 BOM 以支持中文
            fwrite($handle, "\xEF\xBB\xBF");
            
            // 写入表头
            fputcsv($handle, [
                'ID',
                '用户名',
                '操作类型',
                '操作内容',
                'IP地址',
                '操作时间'
            ]);

            // 写入数据
            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->username,
                    $log->action_text,
                    $log->content,
                    $log->ip,
                    $log->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * 获取日志统计数据
     *
     * @param Request $request
     * @return array
     */
    private function getLogStats(Request $request)
    {
        $query = OperationLog::query();

        // 应用相同的过滤条件
        if ($request->filled('start_date')) {
            $query->dateRange($request->start_date, null);
        }
        
        if ($request->filled('end_date')) {
            $query->dateRange(null, $request->end_date);
        }

        if ($request->filled('action')) {
            $query->action($request->action);
        }

        if ($request->filled('user_id')) {
            $query->user($request->user_id);
        }

        if ($request->filled('ip')) {
            $query->ip($request->ip);
        }

        if ($request->filled('content')) {
            $query->content($request->content);
        }

        return [
            'total' => $query->count(),
            'today' => $query->clone()->whereDate('created_at', today())->count(),
            'this_week' => $query->clone()->whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'this_month' => $query->clone()->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)->count(),
        ];
    }

    /**
     * 获取日志统计图表数据
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function chartData(Request $request)
    {
        $days = $request->get('days', 7);
        $startDate = now()->subDays($days - 1)->startOfDay();
        
        $data = OperationLog::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 填充缺失的日期
        $result = [];
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($days - 1 - $i)->format('Y-m-d');
            $count = $data->where('date', $date)->first()->count ?? 0;
            $result[] = [
                'date' => $date,
                'count' => $count
            ];
        }

        return response()->json($result);
    }
}