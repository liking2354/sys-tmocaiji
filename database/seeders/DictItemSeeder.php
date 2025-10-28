<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DictItem;
use App\Models\DictCategory;

class DictItemSeeder extends Seeder
{
    public function run()
    {
        // 先创建字典分类
        $category = DictCategory::updateOrCreate(
            ['category_code' => 'cloud_resource_type'],
            [
                'category_name' => '云资源类型',
                'description' => '云平台资源类型分类',
                'sort_order' => 1,
                'status' => 'active'
            ]
        );

        $items = [
            [
                'category_id' => $category->id,
                'item_code' => 'compute',
                'item_name' => '计算资源',
                'item_value' => 'compute',
                'sort_order' => 1,
                'status' => 'active',
                'description' => 'ECS等计算资源'
            ],
            [
                'category_id' => $category->id,
                'item_code' => 'storage',
                'item_name' => '存储资源',
                'item_value' => 'storage',
                'sort_order' => 2,
                'status' => 'active',
                'description' => '云存储资源'
            ],
            [
                'category_id' => $category->id,
                'item_code' => 'network',
                'item_name' => '网络资源',
                'item_value' => 'network',
                'sort_order' => 3,
                'status' => 'active',
                'description' => 'VPC、负载均衡等网络资源'
            ],
            [
                'category_id' => $category->id,
                'item_code' => 'database',
                'item_name' => '数据库资源',
                'item_value' => 'database',
                'sort_order' => 4,
                'status' => 'active',
                'description' => 'RDS等数据库资源'
            ]
        ];

        foreach ($items as $item) {
            DictItem::updateOrCreate(
                ['category_id' => $item['category_id'], 'item_code' => $item['item_code']],
                $item
            );
        }
    }
}