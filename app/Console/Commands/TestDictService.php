<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DictService;

class TestDictService extends Command
{
    protected $signature = 'dict:test';
    protected $description = '测试字典服务功能';

    public function handle()
    {
        $dictService = app(DictService::class);
        
        $this->info('🧪 测试字典服务功能...');
        $this->newLine();
        
        // 测试获取资源类型
        $this->info('📋 测试 getResourceTypes() 方法：');
        $resourceTypes = $dictService->getResourceTypes();
        $this->line("找到 {$resourceTypes->count()} 个资源类型");
        
        foreach ($resourceTypes->take(5) as $type) {
            $this->line("  - {$type->item_name} ({$type->item_code}) [父级: {$type->parent->item_name}]");
        }
        
        $this->newLine();
        
        // 测试获取平台类型
        $this->info('🌐 测试 getPlatformTypes() 方法：');
        $platformTypes = $dictService->getPlatformTypes();
        $this->line("找到 {$platformTypes->count()} 个平台类型");
        
        foreach ($platformTypes as $platform) {
            $this->line("  - {$platform->item_name} ({$platform->item_code})");
        }
        
        $this->newLine();
        
        // 测试获取华为云资源
        $this->info('☁️ 测试 getResourcesByPlatform("huawei") 方法：');
        $huaweiResources = $dictService->getResourcesByPlatform('huawei');
        $this->line("找到 {$huaweiResources->count()} 个华为云资源");
        
        foreach ($huaweiResources as $resource) {
            $this->line("  - {$resource->item_name} ({$resource->item_code})");
        }
        
        $this->newLine();
        
        // 测试获取云服务器的平台实现
        $this->info('💻 测试 getPlatformImplementations("ecs") 方法：');
        $ecsImplementations = $dictService->getPlatformImplementations('ecs');
        $this->line("找到 {$ecsImplementations->count()} 个云服务器平台实现");
        
        foreach ($ecsImplementations as $impl) {
            $this->line("  - {$impl->item_name} ({$impl->item_code}) [{$impl->platform_type}]");
        }
        
        $this->newLine();
        $this->info('✅ 字典服务测试完成！');
        
        return 0;
    }
}