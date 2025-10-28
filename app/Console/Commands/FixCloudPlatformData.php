<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CloudPlatform;

class FixCloudPlatformData extends Command
{
    protected $signature = 'fix:cloud-platform-data';
    protected $description = '修复云平台数据中的platform_type字段';

    public function handle()
    {
        $this->info('开始修复云平台数据...');

        // 定义平台类型映射
        $platformTypeMapping = [
            'alibaba' => 'alibaba',
            'tencent' => 'tencent', 
            'huawei' => 'huawei',
            'aws' => 'aws',
            'azure' => 'azure',
            'google' => 'google',
        ];

        $platforms = CloudPlatform::all();
        $updated = 0;

        foreach ($platforms as $platform) {
            $this->info("处理平台: {$platform->name} (ID: {$platform->id})");
            
            // 如果platform_type为空，尝试从name或其他字段推断
            if (empty($platform->platform_type)) {
                $platformType = null;
                
                // 根据平台名称推断类型
                $name = strtolower($platform->name);
                if (str_contains($name, '阿里云') || str_contains($name, 'alibaba') || str_contains($name, 'aliyun')) {
                    $platformType = 'alibaba';
                } elseif (str_contains($name, '腾讯云') || str_contains($name, 'tencent') || str_contains($name, 'qcloud')) {
                    $platformType = 'tencent';
                } elseif (str_contains($name, '华为云') || str_contains($name, 'huawei')) {
                    $platformType = 'huawei';
                } elseif (str_contains($name, 'aws') || str_contains($name, 'amazon')) {
                    $platformType = 'aws';
                } elseif (str_contains($name, 'azure') || str_contains($name, 'microsoft')) {
                    $platformType = 'azure';
                } elseif (str_contains($name, 'google') || str_contains($name, 'gcp')) {
                    $platformType = 'google';
                }
                
                if ($platformType) {
                    $platform->platform_type = $platformType;
                    $platform->save();
                    $updated++;
                    $this->info("  ✅ 更新platform_type为: {$platformType}");
                } else {
                    $this->warn("  ⚠️  无法推断platform_type，请手动设置");
                }
            } else {
                $this->info("  ✅ platform_type已存在: {$platform->platform_type}");
            }
        }

        $this->info("修复完成！共更新了 {$updated} 条记录。");
        
        // 显示当前所有平台数据
        $this->info("\n当前云平台数据:");
        $platforms = CloudPlatform::all(['id', 'name', 'platform_type']);
        foreach ($platforms as $platform) {
            $this->line("ID: {$platform->id}, Name: {$platform->name}, Type: " . ($platform->platform_type ?? 'NULL'));
        }
        
        return 0;
    }
}