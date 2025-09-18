<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Server;
use App\Models\ServerGroup;
use App\Models\Collector;

class DataController extends Controller
{
    /**
     * 显示数据清理表单
     *
     * @return \Illuminate\Http\Response
     */
    public function showCleanupForm()
    {
        // 获取所有服务器分组
        $serverGroups = ServerGroup::with('servers')->get();
        
        // 获取所有采集组件
        $collectors = Collector::all();
        
        return view('data.cleanup', compact('serverGroups', 'collectors'));
    }

    /**
     * 执行数据清理
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function cleanup(Request $request)
    {
        // 验证请求数据
        $request->validate([
            'server_ids' => 'required|array',
            'server_ids.*' => 'exists:servers,id',
            'collector_ids' => 'required|array',
            'collector_ids.*' => 'exists:collectors,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // 执行数据清理逻辑
        // ...

        return redirect()->route('data.cleanup.form')
            ->with('success', '数据清理操作已成功执行');
    }
}