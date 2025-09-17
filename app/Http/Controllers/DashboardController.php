<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\ServerGroup;
use App\Models\Collector;

class DashboardController extends Controller
{
    /**
     * 显示仪表盘
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // 获取统计数据
        $serverCount = Server::count();
        $groupCount = ServerGroup::count();
        $collectorCount = Collector::count();
        
        // 获取服务器状态分布
        $serverStatusStats = [
            'online' => Server::where('status', 1)->count(),
            'offline' => Server::where('status', 0)->count(),
        ];
        
        return view('dashboard', compact(
            'serverCount',
            'groupCount',
            'collectorCount',
            'serverStatusStats'
        ));
    }
}