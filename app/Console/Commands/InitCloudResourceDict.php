<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InitCloudResourceDict extends Command
{
    protected $signature = 'dict:init-cloud-resources';
    protected $description = '初始化完整的云资源字典数据';

    public function handle()
    {
        $this->info('🚀 开始初始化云资源字典数据...');
        $this->newLine();
        
        // 确认操作
        if (!$this->confirm('这将清空现有的字典数据并重新创建，是否继续？')) {
            $this->info('操作已取消');
            return 0;
        }
        
        // 运行数据填充
        $this->info('📊 正在创建字典数据...');
        Artisan::call('db:seed', ['--class' => 'CompleteCloudResourceSeeder']);
        
        $this->info('✅ 云资源字典数据初始化完成！');
        $this->newLine();
        
        // 显示统计信息
        $this->showStatistics();
        
        $this->newLine();
        $this->info('💡 使用以下命令查看数据：');
        $this->line('  php artisan dict:hierarchy                    # 查看完整层次结构');
        $this->line('  php artisan dict:hierarchy --platform=huawei  # 查看华为云资源');
        $this->line('  php artisan dict:hierarchy --platform=aliyun  # 查看阿里云资源');
        $this->line('  php artisan dict:hierarchy --platform=tencent # 查看腾讯云资源');
        
        return 0;
    }
    
    private function showStatistics()
    {
        $categories = \App\Models\DictCategory::count();
        $totalItems = \App\Models\DictItem::count();
        $level1Items = \App\Models\DictItem::where('level', 1)->count();
        $level2Items = \App\Models\DictItem::where('level', 2)->count();
        $level3Items = \App\Models\DictItem::where('level', 3)->count();
        
        $this->info('📈 数据统计：');
        $this->line("  字典分类：{$categories} 个");
        $this->line("  字典项总数：{$totalItems} 个");
        $this->line("  一级分类：{$level1Items} 个");
        $this->line("  二级分类：{$level2Items} 个");
        $this->line("  三级分类：{$level3Items} 个");
        
        // 显示平台分布
        $platforms = \App\Models\DictItem::whereNotNull('platform_type')
            ->groupBy('platform_type')
            ->selectRaw('platform_type, count(*) as count')
            ->pluck('count', 'platform_type');
            
        if ($platforms->count() > 0) {
            $this->line("  平台分布：");
            foreach ($platforms as $platform => $count) {
                $this->line("    {$platform}: {$count} 个资源");
            }
        }
    }
}