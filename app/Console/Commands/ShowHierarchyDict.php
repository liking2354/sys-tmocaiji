<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DictService;

class ShowHierarchyDict extends Command
{
    protected $signature = 'dict:hierarchy {--platform= : 指定平台类型筛选}';
    protected $description = '显示三级层次结构的字典数据';

    private $dictService;

    public function __construct(DictService $dictService)
    {
        parent::__construct();
        $this->dictService = $dictService;
    }

    public function handle()
    {
        $platformType = $this->option('platform');
        
        $this->info('=== 云资源三级层次结构 ===');
        if ($platformType) {
            $this->info("筛选平台：{$platformType}");
        }
        $this->newLine();

        // 获取层次结构数据
        $hierarchy = $this->dictService->getCloudResourceHierarchy($platformType);

        foreach ($hierarchy as $level1) {
            $this->line("📁 {$level1->item_name} ({$level1->item_code}) [一级]");
            
            foreach ($level1->children as $level2) {
                $this->line("  📂 {$level2->item_name} ({$level2->item_code}) [二级]");
                
                foreach ($level2->children as $level3) {
                    $platformInfo = $level3->platform_type ? " [{$level3->platform_type}]" : "";
                    $this->line("    📄 {$level3->item_name} ({$level3->item_code}){$platformInfo} [三级]");
                }
            }
            $this->newLine();
        }

        // 显示平台类型
        $this->info('=== 支持的云平台类型 ===');
        $platforms = $this->dictService->getPlatformTypes();
        foreach ($platforms as $platform) {
            $this->line("🌐 {$platform->item_name} ({$platform->item_code})");
        }
        $this->newLine();

        // 显示使用示例
        $this->info('=== 使用示例 ===');
        $this->line('查看华为云相关资源：php artisan dict:hierarchy --platform=huawei');
        $this->line('查看阿里云相关资源：php artisan dict:hierarchy --platform=aliyun');
        $this->line('查看腾讯云相关资源：php artisan dict:hierarchy --platform=tencent');
        
        return 0;
    }
}