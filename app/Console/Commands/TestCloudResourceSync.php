<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CloudPlatform;
use App\Services\CloudResourceManagementService;

class TestCloudResourceSync extends Command
{
    protected $signature = 'test:cloud-resource-sync {platform_type?}';
    protected $description = '测试云资源同步功能';

    protected $resourceService;

    public function __construct(CloudResourceManagementService $resourceService)
    {
        parent::__construct();
        $this->resourceService = $resourceService;
    }

    public function handle()
    {
        $platformType = $this->argument('platform_type') ?: 'huawei';
        
        $this->info("开始测试云资源同步功能...");
        
        // 获取指定类型的云平台
        $platform = CloudPlatform::where('platform_type', $platformType)->first();
        
        if (!$platform) {
            $this->error("未找到类型为 {$platformType} 的云平台");
            return 1;
        }

        $this->info("使用云平台: {$platform->name} (ID: {$platform->id})");

        try {
            // 测试同步功能
            $results = $this->resourceService->syncPlatformResources(
                $platform->id,
                ['compute'] // 只同步计算资源
            );

            $this->info("同步完成！");
            $this->info("结果统计:");
            
            if (is_array($results)) {
                foreach ($results as $type => $result) {
                    if (is_array($result)) {
                        $success = $result['success'] ?? 0;
                        $failed = $result['failed'] ?? 0;
                        $message = $result['message'] ?? '';
                        
                        $this->info("- {$type}: 成功 {$success} 个, 失败 {$failed} 个");
                        if (!empty($message)) {
                            $this->info("  消息: {$message}");
                        }
                    } else {
                        $this->info("- {$type}: {$result}");
                    }
                }
            } else {
                $this->info("同步结果: " . json_encode($results));
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("同步失败: " . $e->getMessage());
            $this->error("错误详情: " . $e->getTraceAsString());
            return 1;
        }
    }
}