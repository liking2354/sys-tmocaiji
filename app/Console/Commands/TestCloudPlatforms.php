<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CloudPlatform;

class TestCloudPlatforms extends Command
{
    protected $signature = 'test:cloud-platforms';
    protected $description = '测试查看云平台数据';

    public function handle()
    {
        try {
            $platforms = CloudPlatform::all(['id', 'name', 'platform_type']);
            
            if ($platforms->isEmpty()) {
                $this->info('没有找到云平台数据');
                return;
            }
            
            $this->info('云平台列表:');
            foreach ($platforms as $platform) {
                $this->info("ID: {$platform->id}, 名称: {$platform->name}, 类型: {$platform->platform_type}");
            }
            
        } catch (\Exception $e) {
            $this->error('查询失败: ' . $e->getMessage());
        }
    }
}