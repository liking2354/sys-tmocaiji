<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\TaskDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * 显示系统仪表盘
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        // 获取服务器状态分布数据
        $serverStatusData = $this->getServerStatusData();
        
        // 获取近期采集数据趋势
        $collectionTrendData = $this->getCollectionTrendData();
        
        // 获取服务器总数
        $serverCount = Server::count();
        
        // 获取服务器状态统计
        $serverStatusStats = [
            'online' => Server::where('status', 1)->count(),
            'offline' => Server::where('status', 0)->count(),
            'error' => Server::where('status', 2)->count()
        ];
        
        // 获取服务器分组数量
        $groupCount = \App\Models\ServerGroup::count();
        
        // 获取采集组件数量
        $collectorCount = \App\Models\Collector::count();
        
        return view('dashboard', compact(
            'serverStatusData', 
            'collectionTrendData', 
            'serverCount', 
            'serverStatusStats',
            'groupCount',
            'collectorCount'
        ));
    }
    
    /**
     * 获取服务器状态分布数据
     *
     * @return array
     */
    private function getServerStatusData()
    {
        $serverStatus = Server::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->toArray();
        
        // 状态映射
        $statusLabels = [
            1 => '正常',
            0 => '离线',
            2 => '故障'
        ];
        
        // 状态对应的颜色
        $statusColors = [
            1 => '#28a745', // 绿色 - 正常
            0 => '#dc3545', // 红色 - 离线
            2 => '#ffc107'  // 黄色 - 故障
        ];
        
        $labels = [];
        $data = [];
        $colors = [];
        
        foreach ($statusLabels as $status => $label) {
            $labels[] = $label;
            $data[] = $serverStatus[$status] ?? 0;
            $colors[] = $statusColors[$status];
        }
        
        return [
            'labels' => $labels,
            'data' => $data,
            'colors' => $colors
        ];
    }
    
    /**
     * 获取近期采集数据趋势
     *
     * @return array
     */
    private function getCollectionTrendData()
    {
        // 获取最近7天的日期
        $dates = [];
        for ($i = 6; $i >= 0; $i--) {
            $dates[] = Carbon::now()->subDays($i)->format('Y-m-d');
        }
        
        // 获取成功的采集数据
        $successData = $this->getTaskCountByStatus($dates, 1);
        
        // 获取失败的采集数据
        $failedData = $this->getTaskCountByStatus($dates, 0);
        
        return [
            'labels' => $dates,
            'datasets' => [
                [
                    'label' => '成功采集',
                    'data' => $successData,
                    'borderColor' => '#28a745',
                    'backgroundColor' => 'rgba(40, 167, 69, 0.1)',
                    'fill' => true
                ],
                [
                    'label' => '失败采集',
                    'data' => $failedData,
                    'borderColor' => '#dc3545',
                    'backgroundColor' => 'rgba(220, 53, 69, 0.1)',
                    'fill' => true
                ]
            ]
        ];
    }
    
    /**
     * 根据状态获取每日任务数量
     *
     * @param array $dates 日期数组
     * @param int $status 状态 (1成功, 0失败)
     * @return array
     */
    private function getTaskCountByStatus($dates, $status)
    {
        $result = [];
        
        foreach ($dates as $date) {
            $count = TaskDetail::where('status', $status)
                ->whereDate('created_at', $date)
                ->count();
            
            $result[] = $count;
        }
        
        return $result;
    }
}