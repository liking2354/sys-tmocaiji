<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CloudPlatform;
use App\Models\CloudPlatformComponent;
use App\Services\DictService;

class DiagnoseCloudSync extends Command
{
    protected $signature = 'diagnose:cloud-sync {platformId}';
    protected $description = '诊断云平台同步配置';

    public function handle()
    {
        $platformId = $this->argument('platformId');
        
        $this->info("=== 诊断云平台同步配置 ===");
        $this->info("平台ID: {$platformId}");
        
        // 1. 检查云平台信息
        $platform = CloudPlatform::find($platformId);
        if (!$platform) {
            $this->error("云平台ID {$platformId} 不存在");
            return 1;
        }
        
        $this->info("云平台: {$platform->name} ({$platform->platform_type})");
        
        // 2. 检查平台组件
        $components = CloudPlatformComponent::where('platform_id', $platformId)
            ->with('componentDict')
            ->get();
        
        $this->info("平台组件数量: " . $components->count());
        foreach ($components as $component) {
            $dict = $component->componentDict;
            $enabled = $component->is_enabled ? '是' : '否';
            $this->info("  - 组件ID: {$component->id}, 字典ID: {$component->component_dict_id}, 启用: {$enabled}");
            if ($dict) {
                $this->info("    字典项: {$dict->item_name} ({$dict->item_code})");
            }
        }
        
        // 3. 检查字典数据
        $dictService = app(DictService::class);
        
        $resourceCategories = $dictService->getResourceCategories();
        $this->info("资源分类数量: " . $resourceCategories->count());
        
        foreach ($resourceCategories as $category) {
            $this->info("  - 分类: {$category->item_name} ({$category->item_code})");
        }
        
        $resourceTypes = $dictService->getResourceTypes();
        $this->info("资源类型数量: " . $resourceTypes->count());
        
        foreach ($resourceTypes as $type) {
            $parent = $type->parent;
            $this->info("  - 类型: {$type->item_name} ({$type->item_code}), 分类: " . ($parent ? $parent->item_name : '无'));
        }
        
        // 4. 模拟同步配置准备
        $this->info("=== 模拟同步配置 ===");
        
        $resourceCategory = 'compute';
        $resourceTypes = ['ecs'];
        
        $this->info("选择的资源分类: {$resourceCategory}");
        $this->info("选择的资源类型: " . implode(', ', $resourceTypes));
        
        // 使用修复后的 CloudResourceManagementService 来测试
        $cloudResourceService = app(\App\Services\CloudResourceManagementService::class);
        
        try {
            $config = $cloudResourceService->prepareSyncConfig($platformId, $resourceCategory, $resourceTypes);
            
            $this->info("同步配置准备结果:");
            $this->info("  - 平台: {$config['platform_name']} ({$config['platform_type']})");
            $this->info("  - 资源数量: {$config['resource_count']}");
            $this->info("  - 组件列表:");
            
            foreach ($config['components'] as $component) {
                $this->info("    * {$component['name']} ({$component['code']}) - 优先级: {$component['priority']}");
            }
            
        } catch (\Exception $e) {
            $this->error("同步配置准备失败: " . $e->getMessage());
        }
        
        return 0;
    }
}