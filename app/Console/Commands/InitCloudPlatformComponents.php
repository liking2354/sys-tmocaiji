<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CloudPlatform;
use App\Models\CloudPlatformComponent;
use App\Models\DictItem;

class InitCloudPlatformComponents extends Command
{
    protected $signature = 'init:cloud-platform-components';
    protected $description = '初始化云平台组件配置';

    public function handle()
    {
        $this->info('开始初始化云平台组件配置...');

        // 获取计算类型的字典项
        $computeDict = DictItem::where('item_code', 'compute')->first();
        if (!$computeDict) {
            $this->error('未找到compute字典项，请先运行字典初始化');
            return 1;
        }

        // 获取所有云平台
        $platforms = CloudPlatform::all();
        if ($platforms->isEmpty()) {
            $this->error('未找到云平台配置，请先添加云平台');
            return 1;
        }

        $created = 0;
        foreach ($platforms as $platform) {
            // 为每个平台创建ECS组件配置
            $component = CloudPlatformComponent::updateOrCreate(
                [
                    'platform_id' => $platform->id,
                    'component_dict_id' => $computeDict->id,
                ],
                [
                    'is_enabled' => true,
                    'sync_priority' => 100,
                    'config' => [
                        'auto_sync' => true,
                        'sync_interval' => 3600, // 1小时
                        'regions' => [], // 空表示所有区域
                    ]
                ]
            );

            if ($component->wasRecentlyCreated) {
                $created++;
                $this->info("为平台 {$platform->name} 创建了ECS组件配置");
            } else {
                $this->info("平台 {$platform->name} 的ECS组件配置已存在");
            }
        }

        $this->info("初始化完成！共创建了 {$created} 个组件配置。");
        return 0;
    }
}