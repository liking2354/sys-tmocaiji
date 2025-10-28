<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DictCategory;
use App\Models\DictItem;

class CleanupDictData extends Command
{
    protected $signature = 'dict:cleanup';
    protected $description = '清理重复的字典数据';

    public function handle()
    {
        $this->info('开始清理重复的字典数据...');

        // 查找"云资源类型"分类
        $cloudResourceTypeCategory = DictCategory::where('category_name', '云资源类型')->first();
        
        if ($cloudResourceTypeCategory) {
            $this->info('找到"云资源类型"分类，准备删除...');
            
            // 删除该分类下的所有字典项
            $deletedItems = DictItem::where('category_id', $cloudResourceTypeCategory->id)->count();
            DictItem::where('category_id', $cloudResourceTypeCategory->id)->delete();
            
            // 删除分类
            $cloudResourceTypeCategory->delete();
            
            $this->info("已删除\"云资源类型\"分类及其 {$deletedItems} 个字典项");
        } else {
            $this->info('"云资源类型"分类不存在');
        }

        // 检查是否还有其他重复数据
        $duplicateCategories = DictCategory::select('category_name')
            ->groupBy('category_name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('category_name');

        if ($duplicateCategories->count() > 0) {
            $this->warn('发现重复的分类名称：');
            foreach ($duplicateCategories as $name) {
                $this->line("- {$name}");
            }
        }

        $this->info('字典数据清理完成！');
        
        return 0;
    }
}