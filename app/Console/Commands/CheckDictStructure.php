<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DictItem;

class CheckDictStructure extends Command
{
    protected $signature = 'check:dict-structure';
    protected $description = '检查字典结构';

    public function handle()
    {
        // 查看所有字典项，特别是 ecs 相关的
        $ecsItems = DictItem::where('item_code', 'like', '%ecs%')->get();
        
        $this->info('=== ECS 相关字典项 ===');
        foreach ($ecsItems as $item) {
            $parent = $item->parent;
            $this->info("ID: {$item->id}, 代码: {$item->item_code}, 名称: {$item->item_name}, 父级: " . ($parent ? $parent->item_name : '无'));
        }
        
        // 查看计算资源分类下的所有项
        $computeCategory = DictItem::where('item_code', 'compute')->first();
        if ($computeCategory) {
            $this->info("\n=== 计算资源分类下的子项 ===");
            $children = DictItem::where('parent_id', $computeCategory->id)->get();
            foreach ($children as $child) {
                $this->info("ID: {$child->id}, 代码: {$child->item_code}, 名称: {$child->item_name}");
                
                // 查看子项的子项（平台实现）
                $grandChildren = DictItem::where('parent_id', $child->id)->get();
                foreach ($grandChildren as $grandChild) {
                    $this->info("  - ID: {$grandChild->id}, 代码: {$grandChild->item_code}, 名称: {$grandChild->item_name}");
                }
            }
        }
        
        return 0;
    }
}