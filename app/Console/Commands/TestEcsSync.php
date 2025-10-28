<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CloudPlatform\Components\EcsComponent;
use App\Services\CloudPlatform\CloudPlatformFactory;
use App\Models\CloudPlatform;

class TestEcsSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:ecs-sync {platform=alibaba}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '测试ECS组件同步功能';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $platformCode = $this->argument('platform');
        
        $this->info("开始测试 {$platformCode} ECS 同步功能...");
        
        try {
            // 获取云平台配置
            $cloudPlatform = CloudPlatform::where('platform_type', $platformCode)->first();
            if (!$cloudPlatform) {
                $this->error("未找到云平台配置: {$platformCode}");
                return 1;
            }
            
            // 创建平台实例
            $platform = CloudPlatformFactory::create($cloudPlatform);
            
            // 创建ECS组件
            $ecsComponent = new EcsComponent($platform);
            
            // 测试同步功能
            $this->info("正在同步ECS数据到数据库...");
            $syncResult = $ecsComponent->syncResources($cloudPlatform->id);
            
            $this->info("同步完成:");
            $this->line("- 成功: {$syncResult['synced_count']} 个");
            $this->line("- 失败: {$syncResult['error_count']} 个");
            $this->line("- 消息: {$syncResult['message']}");
            
            if (!empty($syncResult['errors'])) {
                $this->error("错误详情:");
                foreach ($syncResult['errors'] as $error) {
                    $this->line("  - 实例 {$error['instance_id']}: {$error['error']}");
                }
            }
            
            $this->info("测试完成!");
            return 0;
            
        } catch (\Exception $e) {
            $this->error("测试失败: " . $e->getMessage());
            $this->error("错误堆栈: " . $e->getTraceAsString());
            return 1;
        }
    }
}