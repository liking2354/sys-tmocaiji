<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DictService;

class TestAllDictMethods extends Command
{
    protected $signature = 'dict:test-all';
    protected $description = '测试所有字典服务方法';

    public function handle()
    {
        $dictService = app(DictService::class);
        
        $this->info('🧪 测试所有字典服务方法...');
        $this->newLine();
        
        // 测试 getAllCategories()
        $this->info('📂 测试 getAllCategories() 方法：');
        $categories = $dictService->getAllCategories();
        $this->line("找到 {$categories->count()} 个字典分类");
        
        foreach ($categories as $category) {
            $this->line("  - {$category->category_name} ({$category->category_code})");
        }
        
        $this->newLine();
        
        // 测试 getCategoryByCode()
        $this->info('🔍 测试 getCategoryByCode("cloud_resource_hierarchy") 方法：');
        $category = $dictService->getCategoryByCode('cloud_resource_hierarchy');
        if ($category) {
            $this->line("找到分类: {$category->category_name} ({$category->category_code})");
        } else {
            $this->error('未找到指定分类');
        }
        
        $this->newLine();
        
        // 测试 getItemsByCategory()
        $this->info('📋 测试 getItemsByCategory("cloud_resource_hierarchy", 1) 方法：');
        $level1Items = $dictService->getItemsByCategory('cloud_resource_hierarchy', 1);
        $this->line("找到 {$level1Items->count()} 个一级分类");
        
        foreach ($level1Items as $item) {
            $this->line("  - {$item->item_name} ({$item->item_code})");
        }
        
        $this->newLine();
        
        // 测试 getResourceCategories()
        $this->info('🗂️ 测试 getResourceCategories() 方法：');
        $resourceCategories = $dictService->getResourceCategories();
        $this->line("找到 {$resourceCategories->count()} 个资源分类");
        
        foreach ($resourceCategories as $category) {
            $this->line("  - {$category->item_name} ({$category->item_code})");
        }
        
        $this->newLine();
        
        // 测试 getResourceImplementations()
        $this->info('⚙️ 测试 getResourceImplementations("ecs", "huawei") 方法：');
        $implementations = $dictService->getResourceImplementations('ecs', 'huawei');
        $this->line("找到 {$implementations->count()} 个华为云ECS实现");
        
        foreach ($implementations as $impl) {
            $this->line("  - {$impl->item_name} ({$impl->item_code}) [{$impl->platform_type}]");
        }
        
        $this->newLine();
        
        // 测试 getFlatOptions()
        $this->info('📄 测试 getFlatOptions("cloud_resource_hierarchy", "huawei", 3) 方法：');
        $options = $dictService->getFlatOptions('cloud_resource_hierarchy', 'huawei', 3);
        $optionCount = count($options);
        $this->line("找到 {$optionCount} 个华为云三级选项");
        
        foreach (array_slice($options, 0, 5) as $option) {
            $this->line("  - {$option['label']} ({$option['value']}) [Level: {$option['level']}]");
        }
        
        $this->newLine();
        
        // 测试 buildTreeData()
        $this->info('🌳 测试 buildTreeData("platform_types") 方法：');
        $treeData = $dictService->buildTreeData('platform_types');
        $this->line("构建了 " . count($treeData) . " 个树节点");
        
        foreach (array_slice($treeData, 0, 3) as $node) {
            $this->line("  - {$node['name']} ({$node['code']})");
        }
        
        $this->newLine();
        $this->info('✅ 所有字典服务方法测试完成！');
        
        return 0;
    }
}