<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CloudPlatform;
use App\Models\CloudPlatformComponent;
use App\Models\DictItem;

class CloudPlatformComponentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 获取所有云平台
        $platforms = CloudPlatform::all();
        
        if ($platforms->isEmpty()) {
            $this->command->info('没有找到云平台数据，请先创建云平台');
            return;
        }

        // 获取云资源类型的字典项（三级实现）
        $resourceTypes = DictItem::where('level', 3)
            ->whereNotNull('platform_type')
            ->get();

        if ($resourceTypes->isEmpty()) {
            $this->command->info('没有找到云资源类型字典数据，尝试获取所有三级字典项...');
            
            // 如果没有找到，尝试获取所有三级字典项
            $resourceTypes = DictItem::where('level', 3)->get();
            
            if ($resourceTypes->isEmpty()) {
                $this->command->info('没有找到任何三级字典数据，请先初始化字典数据');
                return;
            }
        }

        foreach ($platforms as $platform) {
            $this->command->info("为平台 {$platform->name} 创建组件...");
            
            // 根据平台类型筛选对应的资源类型
            $platformResourceTypes = $resourceTypes->filter(function ($item) use ($platform) {
                return $item->platform_type === $platform->platform_type;
            });

            if ($platformResourceTypes->isEmpty()) {
                $this->command->info("平台 {$platform->name} 没有匹配的资源类型");
                continue;
            }

            foreach ($platformResourceTypes as $index => $resourceType) {
                // 检查是否已存在
                $existingComponent = CloudPlatformComponent::where('platform_id', $platform->id)
                    ->where('component_dict_id', $resourceType->id)
                    ->first();

                if ($existingComponent) {
                    continue;
                }

                // 创建组件
                CloudPlatformComponent::create([
                    'platform_id' => $platform->id,
                    'component_dict_id' => $resourceType->id,
                    'is_enabled' => true,
                    'sync_priority' => 100 - $index, // 优先级递减
                    'config' => [
                        'sync_interval' => 3600, // 1小时
                        'batch_size' => 100,
                        'timeout' => 300, // 5分钟
                        'retry_count' => 3,
                    ],
                ]);

                $this->command->info("  - 创建组件: {$resourceType->item_name}");
            }
        }

        $this->command->info('云平台组件数据初始化完成');
    }
}