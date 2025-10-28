<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DictCategory;
use App\Models\DictItem;

class ShowDictData extends Command
{
    protected $signature = 'dict:show';
    protected $description = '显示字典数据';

    public function handle()
    {
        $this->info("=== 字典分类 ===");
        $categories = DictCategory::orderBy('sort_order')->get();
        
        foreach ($categories as $category) {
            $this->info("ID: {$category->id} | 分类: {$category->category_name} | 代码: {$category->category_code}");
        }

        $this->info("\n=== 字典项 ===");
        $items = DictItem::with('category')->orderBy('category_id')->orderBy('sort_order')->get();
        
        foreach ($items as $item) {
            $categoryName = $item->category->category_name ?? '未知分类';
            $this->info("分类: {$categoryName} | 项目: {$item->item_name} | 代码: {$item->item_code}");
        }

        return 0;
    }
}