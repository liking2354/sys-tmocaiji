<?php

namespace App\Exports;

use App\Models\Server;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Builder;

class ServersExport implements FromQuery, WithHeadings, WithMapping
{
    /**
     * 要导出的服务器ID数组
     *
     * @var array
     */
    protected $serverIds;
    
    /**
     * 要导出的采集组件ID数组
     *
     * @var array
     */
    protected $collectorIds;
    
    /**
     * 构造函数
     *
     * @param array $serverIds
     * @param array $collectorIds
     * @return void
     */
    public function __construct(array $serverIds = [], array $collectorIds = [])
    {
        $this->serverIds = $serverIds;
        $this->collectorIds = $collectorIds;
    }
    
    /**
     * 返回要导出的查询
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        $query = Server::query()->with(['group', 'collectors', 'collectionHistory']);
        
        if (!empty($this->serverIds)) {
            $query->whereIn('id', $this->serverIds);
        }
        
        return $query;
    }
    
    /**
     * 设置表头
     *
     * @return array
     */
    public function headings(): array
    {
        $headings = [
            '服务器名称',
            '所属分组',
            'IP地址',
            '端口',
            '用户名',
            '密码',
            '状态',
            '最后检查时间',
            '最后采集时间',
            '已安装采集组件',
        ];
        
        // 如果指定了采集组件，添加对应的表头
        if (!empty($this->collectorIds)) {
            $collectors = \App\Models\Collector::whereIn('id', $this->collectorIds)->get();
            foreach ($collectors as $collector) {
                $headings[] = $collector->name . ' 采集结果';
                $headings[] = $collector->name . ' 采集时间';
            }
        }
        
        return $headings;
    }
    
    /**
     * 映射数据
     *
     * @param mixed $server
     * @return array
     */
    public function map($server): array
    {
        // 获取最新的采集历史
        $latestHistory = $server->collectionHistory()->latest()->first();
        
        // 获取采集组件名称列表
        $collectorNames = $server->collectors->pluck('name')->implode(', ');
        
        $data = [
            $server->name,
            $server->group->name ?? '无分组',
            $server->ip,
            $server->port,
            $server->username,
            str_repeat('*', 8), // 不导出实际密码，用*替代
            $server->status ? '在线' : '离线',
            $server->last_check_time ? $server->last_check_time->format('Y-m-d H:i:s') : '未检查',
            $latestHistory ? $latestHistory->created_at->format('Y-m-d H:i:s') : '未采集',
            $collectorNames,
        ];
        
        // 如果指定了采集组件，添加对应的采集结果
        if (!empty($this->collectorIds)) {
            $collectors = \App\Models\Collector::whereIn('id', $this->collectorIds)->get();
            foreach ($collectors as $collector) {
                // 查找该服务器上此采集组件的最新采集结果
                $collectionResult = \App\Models\CollectionHistory::where('server_id', $server->id)
                    ->where('collector_id', $collector->id)
                    ->where('status', 2) // 只获取成功的结果
                    ->latest('created_at')
                    ->first();
                
                if ($collectionResult) {
                    $data[] = json_encode($collectionResult->result, JSON_UNESCAPED_UNICODE);
                    $data[] = $collectionResult->created_at->format('Y-m-d H:i:s');
                } else {
                    $data[] = '无数据';
                    $data[] = '未采集';
                }
            }
        }
        
        return $data;
    }
}