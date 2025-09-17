<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Server;
use App\Models\Collector;
use App\Services\CollectorDeploymentService;
use App\Services\LogService;

class CollectorUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'collector:update
                            {--server= : 服务器ID，不指定则更新所有服务器}
                            {--collector= : 采集组件ID，不指定则更新所有采集组件}
                            {--force : 强制更新，忽略版本检查}
                            {--check : 仅检查版本，不执行更新}'; 

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '检测和更新服务器上的采集组件';

    /**
     * 采集组件部署服务
     *
     * @var CollectorDeploymentService
     */
    protected $deploymentService;

    /**
     * 日志服务
     *
     * @var LogService
     */
    protected $logService;

    /**
     * Create a new command instance.
     *
     * @param CollectorDeploymentService $deploymentService
     * @param LogService $logService
     * @return void
     */
    public function __construct(CollectorDeploymentService $deploymentService, LogService $logService)
    {
        parent::__construct();
        $this->deploymentService = $deploymentService;
        $this->logService = $logService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $serverId = $this->option('server');
        $collectorId = $this->option('collector');
        $force = $this->option('force');
        $checkOnly = $this->option('check');
        
        // 获取服务器列表
        if ($serverId) {
            $servers = Server::where('id', $serverId)->get();
            if ($servers->isEmpty()) {
                $this->error("未找到ID为 {$serverId} 的服务器");
                return 1;
            }
        } else {
            $servers = Server::all();
            if ($servers->isEmpty()) {
                $this->error("未找到任何服务器");
                return 1;
            }
        }
        
        // 获取采集组件列表
        if ($collectorId) {
            $collectors = Collector::where('id', $collectorId)->get();
            if ($collectors->isEmpty()) {
                $this->error("未找到ID为 {$collectorId} 的采集组件");
                return 1;
            }
        } else {
            $collectors = Collector::where('status', 1)->get();
            if ($collectors->isEmpty()) {
                $this->error("未找到任何启用的采集组件");
                return 1;
            }
        }
        
        $this->info("开始" . ($checkOnly ? '检查' : '更新') . "采集组件...");
        $this->info("服务器数量: " . $servers->count());
        $this->info("采集组件数量: " . $collectors->count());
        $this->info("强制更新: " . ($force ? '是' : '否'));
        $this->newLine();
        
        $successCount = 0;
        $failCount = 0;
        $skipCount = 0;
        
        $progressBar = $this->output->createProgressBar($servers->count() * $collectors->count());
        $progressBar->start();
        
        foreach ($servers as $server) {
            $this->info("处理服务器: {$server->name} ({$server->ip})");
            
            // 检查服务器是否在线
            if ($server->status != 1) {
                $this->warn("  服务器离线，跳过");
                $skipCount += $collectors->count();
                $progressBar->advance($collectors->count());
                continue;
            }
            
            foreach ($collectors as $collector) {
                $progressBar->advance();
                
                // 检查是否已安装
                $status = $this->deploymentService->getStatus($server, $collector);
                
                if (!$status['installed']) {
                    $this->line("  采集组件 {$collector->name} 未安装，跳过");
                    $skipCount++;
                    continue;
                }
                
                // 检查版本
                if (!$force && isset($status['is_latest']) && $status['is_latest']) {
                    $this->info("  采集组件 {$collector->name} 已是最新版本 {$collector->version}，跳过");
                    $skipCount++;
                    continue;
                }
                
                // 如果只是检查版本，则不执行更新
                if ($checkOnly) {
                    $this->warn("  采集组件 {$collector->name} 需要更新，当前版本: {$status['version']}，最新版本: {$collector->version}");
                    continue;
                }
                
                // 执行更新
                $this->info("  正在更新采集组件 {$collector->name}...");
                $result = $this->deploymentService->install($server, $collector, true);
                
                if ($result['success']) {
                    $this->info("  采集组件 {$collector->name} 更新成功");
                    $successCount++;
                } else {
                    $this->error("  采集组件 {$collector->name} 更新失败: {$result['message']}");
                    $failCount++;
                }
            }
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        $this->info("采集组件" . ($checkOnly ? '检查' : '更新') . "完成");
        $this->info("成功: {$successCount}");
        $this->info("失败: {$failCount}");
        $this->info("跳过: {$skipCount}");
        
        return 0;
    }
}
