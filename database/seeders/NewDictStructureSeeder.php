<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DictCategory;
use App\Models\DictItem;

class NewDictStructureSeeder extends Seeder
{
    public function run()
    {
        // 创建字典分类
        $categories = [
            [
                'category_code' => 'cloud_resource_hierarchy',
                'category_name' => '云资源层次结构',
                'description' => '支持三级层次的云资源分类体系',
                'sort_order' => 1,
                'status' => 'active'
            ],
            [
                'category_code' => 'platform_types',
                'category_name' => '云平台类型',
                'description' => '支持的云平台类型',
                'sort_order' => 2,
                'status' => 'active'
            ],
            [
                'category_code' => 'resource_status',
                'category_name' => '资源状态',
                'description' => '云资源的各种状态',
                'sort_order' => 3,
                'status' => 'active'
            ]
        ];

        foreach ($categories as $category) {
            DictCategory::create($category);
        }

        // 获取分类ID
        $hierarchyCategory = DictCategory::where('category_code', 'cloud_resource_hierarchy')->first();
        $platformCategory = DictCategory::where('category_code', 'platform_types')->first();
        $statusCategory = DictCategory::where('category_code', 'resource_status')->first();

        // 创建云平台类型字典项
        $platformTypes = [
            ['item_code' => 'aliyun', 'item_name' => '阿里云', 'sort_order' => 1],
            ['item_code' => 'tencent', 'item_name' => '腾讯云', 'sort_order' => 2],
            ['item_code' => 'huawei', 'item_name' => '华为云', 'sort_order' => 3],
            ['item_code' => 'aws', 'item_name' => 'AWS', 'sort_order' => 4],
            ['item_code' => 'azure', 'item_name' => 'Azure', 'sort_order' => 5],
        ];

        foreach ($platformTypes as $platform) {
            DictItem::create([
                'category_id' => $platformCategory->id,
                'item_code' => $platform['item_code'],
                'item_name' => $platform['item_name'],
                'level' => 1,
                'sort_order' => $platform['sort_order'],
                'status' => 'active'
            ]);
        }

        // 创建资源状态字典项
        $statuses = [
            ['item_code' => 'running', 'item_name' => '运行中', 'sort_order' => 1],
            ['item_code' => 'stopped', 'item_name' => '已停止', 'sort_order' => 2],
            ['item_code' => 'starting', 'item_name' => '启动中', 'sort_order' => 3],
            ['item_code' => 'stopping', 'item_name' => '停止中', 'sort_order' => 4],
            ['item_code' => 'unknown', 'item_name' => '未知', 'sort_order' => 5],
        ];

        foreach ($statuses as $status) {
            DictItem::create([
                'category_id' => $statusCategory->id,
                'item_code' => $status['item_code'],
                'item_name' => $status['item_name'],
                'level' => 1,
                'sort_order' => $status['sort_order'],
                'status' => 'active'
            ]);
        }

        // 创建三级层次结构的云资源字典项
        $this->createHierarchicalItems($hierarchyCategory->id);
    }

    private function createHierarchicalItems($categoryId)
    {
        // 一级：资源大类
        $level1Items = [
            ['code' => 'compute', 'name' => '计算资源', 'sort' => 1],
            ['code' => 'storage', 'name' => '存储资源', 'sort' => 2],
            ['code' => 'network', 'name' => '网络资源', 'sort' => 3],
            ['code' => 'database', 'name' => '数据库资源', 'sort' => 4],
            ['code' => 'security', 'name' => '安全资源', 'sort' => 5],
        ];

        $level1Ids = [];
        foreach ($level1Items as $item) {
            $dictItem = DictItem::create([
                'category_id' => $categoryId,
                'item_code' => $item['code'],
                'item_name' => $item['name'],
                'level' => 1,
                'sort_order' => $item['sort'],
                'status' => 'active'
            ]);
            $level1Ids[$item['code']] = $dictItem->id;
        }

        // 二级：具体服务类型
        $level2Items = [
            // 计算资源下的二级
            ['parent' => 'compute', 'code' => 'ecs', 'name' => '云服务器', 'sort' => 1],
            ['parent' => 'compute', 'code' => 'container', 'name' => '容器服务', 'sort' => 2],
            ['parent' => 'compute', 'code' => 'serverless', 'name' => '函数计算', 'sort' => 3],
            
            // 存储资源下的二级
            ['parent' => 'storage', 'code' => 'object_storage', 'name' => '对象存储', 'sort' => 1],
            ['parent' => 'storage', 'code' => 'block_storage', 'name' => '块存储', 'sort' => 2],
            ['parent' => 'storage', 'code' => 'file_storage', 'name' => '文件存储', 'sort' => 3],
            
            // 网络资源下的二级
            ['parent' => 'network', 'code' => 'vpc', 'name' => '专有网络', 'sort' => 1],
            ['parent' => 'network', 'code' => 'load_balancer', 'name' => '负载均衡', 'sort' => 2],
            ['parent' => 'network', 'code' => 'cdn', 'name' => '内容分发', 'sort' => 3],
            
            // 数据库资源下的二级
            ['parent' => 'database', 'code' => 'rds', 'name' => '关系型数据库', 'sort' => 1],
            ['parent' => 'database', 'code' => 'nosql', 'name' => 'NoSQL数据库', 'sort' => 2],
            ['parent' => 'database', 'code' => 'cache', 'name' => '缓存数据库', 'sort' => 3],
            
            // 安全资源下的二级
            ['parent' => 'security', 'code' => 'waf', 'name' => 'Web应用防火墙', 'sort' => 1],
            ['parent' => 'security', 'code' => 'security_group', 'name' => '安全组', 'sort' => 2],
        ];

        $level2Ids = [];
        foreach ($level2Items as $item) {
            $dictItem = DictItem::create([
                'category_id' => $categoryId,
                'item_code' => $item['code'],
                'item_name' => $item['name'],
                'parent_id' => $level1Ids[$item['parent']],
                'level' => 2,
                'sort_order' => $item['sort'],
                'status' => 'active'
            ]);
            $level2Ids[$item['code']] = $dictItem->id;
        }

        // 三级：各平台具体实现
        $level3Items = [
            // 云服务器的各平台实现
            ['parent' => 'ecs', 'code' => 'aliyun_ecs', 'name' => '阿里云ECS', 'platform' => 'aliyun', 'sort' => 1],
            ['parent' => 'ecs', 'code' => 'tencent_cvm', 'name' => '腾讯云CVM', 'platform' => 'tencent', 'sort' => 2],
            ['parent' => 'ecs', 'code' => 'huawei_ecs', 'name' => '华为云ECS', 'platform' => 'huawei', 'sort' => 3],
            ['parent' => 'ecs', 'code' => 'aws_ec2', 'name' => 'AWS EC2', 'platform' => 'aws', 'sort' => 4],
            
            // 对象存储的各平台实现
            ['parent' => 'object_storage', 'code' => 'aliyun_oss', 'name' => '阿里云OSS', 'platform' => 'aliyun', 'sort' => 1],
            ['parent' => 'object_storage', 'code' => 'tencent_cos', 'name' => '腾讯云COS', 'platform' => 'tencent', 'sort' => 2],
            ['parent' => 'object_storage', 'code' => 'huawei_obs', 'name' => '华为云OBS', 'platform' => 'huawei', 'sort' => 3],
            ['parent' => 'object_storage', 'code' => 'aws_s3', 'name' => 'AWS S3', 'platform' => 'aws', 'sort' => 4],
            
            // 专有网络的各平台实现
            ['parent' => 'vpc', 'code' => 'aliyun_vpc', 'name' => '阿里云VPC', 'platform' => 'aliyun', 'sort' => 1],
            ['parent' => 'vpc', 'code' => 'tencent_vpc', 'name' => '腾讯云VPC', 'platform' => 'tencent', 'sort' => 2],
            ['parent' => 'vpc', 'code' => 'huawei_vpc', 'name' => '华为云VPC', 'platform' => 'huawei', 'sort' => 3],
            
            // 负载均衡的各平台实现
            ['parent' => 'load_balancer', 'code' => 'aliyun_slb', 'name' => '阿里云SLB', 'platform' => 'aliyun', 'sort' => 1],
            ['parent' => 'load_balancer', 'code' => 'tencent_clb', 'name' => '腾讯云CLB', 'platform' => 'tencent', 'sort' => 2],
            ['parent' => 'load_balancer', 'code' => 'huawei_elb', 'name' => '华为云ELB', 'platform' => 'huawei', 'sort' => 3],
            
            // 关系型数据库的各平台实现
            ['parent' => 'rds', 'code' => 'aliyun_rds', 'name' => '阿里云RDS', 'platform' => 'aliyun', 'sort' => 1],
            ['parent' => 'rds', 'code' => 'tencent_cdb', 'name' => '腾讯云CDB', 'platform' => 'tencent', 'sort' => 2],
            ['parent' => 'rds', 'code' => 'huawei_rds', 'name' => '华为云RDS', 'platform' => 'huawei', 'sort' => 3],
            ['parent' => 'rds', 'code' => 'aws_rds', 'name' => 'AWS RDS', 'platform' => 'aws', 'sort' => 4],
        ];

        foreach ($level3Items as $item) {
            DictItem::create([
                'category_id' => $categoryId,
                'item_code' => $item['code'],
                'item_name' => $item['name'],
                'parent_id' => $level2Ids[$item['parent']],
                'level' => 3,
                'platform_type' => $item['platform'],
                'sort_order' => $item['sort'],
                'status' => 'active',
                'metadata' => json_encode([
                    'platform_type' => $item['platform'],
                    'service_type' => $item['parent']
                ])
            ]);
        }
    }
}