<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DictCategory;
use App\Models\DictItem;
use Illuminate\Support\Facades\DB;

class DictSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // 创建资源类型分类
            $resourceTypeCategory = DictCategory::updateOrCreate(
                ['category_code' => 'resource_type'],
                [
                    'category_name' => '资源类型',
                    'description' => '云资源类型分类',
                    'sort_order' => 1,
                    'status' => 'active'
                ]
            );

            // 创建组件类型分类
            $componentTypeCategory = DictCategory::updateOrCreate(
                ['category_code' => 'component_type'],
                [
                    'category_name' => '组件类型',
                    'description' => '云平台组件类型',
                    'sort_order' => 2,
                    'status' => 'active'
                ]
            );

            // 创建平台组件分类
            $platformComponentCategory = DictCategory::updateOrCreate(
                ['category_code' => 'platform_component'],
                [
                    'category_name' => '平台组件',
                    'description' => '云平台支持的组件映射',
                    'sort_order' => 3,
                    'status' => 'active'
                ]
            );

            // 创建资源状态分类
            $resourceStatusCategory = DictCategory::updateOrCreate(
                ['category_code' => 'resource_status'],
                [
                    'category_name' => '资源状态',
                    'description' => '云资源运行状态',
                    'sort_order' => 4,
                    'status' => 'active'
                ]
            );

            // 初始化资源类型数据
            $resourceTypes = [
                ['item_code' => 'compute', 'item_name' => '计算资源', 'item_value' => 'compute', 'sort_order' => 1],
                ['item_code' => 'storage', 'item_name' => '存储资源', 'item_value' => 'storage', 'sort_order' => 2],
                ['item_code' => 'network', 'item_name' => '网络资源', 'item_value' => 'network', 'sort_order' => 3],
                ['item_code' => 'database', 'item_name' => '数据库资源', 'item_value' => 'database', 'sort_order' => 4],
                ['item_code' => 'security', 'item_name' => '安全资源', 'item_value' => 'security', 'sort_order' => 5],
                ['item_code' => 'monitor', 'item_name' => '监控资源', 'item_value' => 'monitor', 'sort_order' => 6],
                ['item_code' => 'cdn', 'item_name' => 'CDN资源', 'item_value' => 'cdn', 'sort_order' => 7],
                ['item_code' => 'domain', 'item_name' => '域名资源', 'item_value' => 'domain', 'sort_order' => 8],
            ];

            foreach ($resourceTypes as $type) {
                DictItem::updateOrCreate(
                    [
                        'category_id' => $resourceTypeCategory->id,
                        'item_code' => $type['item_code']
                    ],
                    array_merge($type, ['status' => 'active'])
                );
            }

            // 初始化组件类型数据
            $componentTypes = [
                // 阿里云组件
                ['item_code' => 'ecs', 'item_name' => '云服务器ECS', 'item_value' => 'ecs', 'sort_order' => 1, 'attributes' => ['supported_resource_types' => ['compute'], 'platforms' => ['aliyun']]],
                ['item_code' => 'rds', 'item_name' => '关系型数据库RDS', 'item_value' => 'rds', 'sort_order' => 2, 'attributes' => ['supported_resource_types' => ['database'], 'platforms' => ['aliyun']]],
                ['item_code' => 'slb', 'item_name' => '负载均衡SLB', 'item_value' => 'slb', 'sort_order' => 3, 'attributes' => ['supported_resource_types' => ['network'], 'platforms' => ['aliyun']]],
                ['item_code' => 'oss', 'item_name' => '对象存储OSS', 'item_value' => 'oss', 'sort_order' => 4, 'attributes' => ['supported_resource_types' => ['storage'], 'platforms' => ['aliyun']]],
                ['item_code' => 'vpc', 'item_name' => '专有网络VPC', 'item_value' => 'vpc', 'sort_order' => 5, 'attributes' => ['supported_resource_types' => ['network'], 'platforms' => ['aliyun']]],
                
                // 腾讯云组件
                ['item_code' => 'cvm', 'item_name' => '云服务器CVM', 'item_value' => 'cvm', 'sort_order' => 6, 'attributes' => ['supported_resource_types' => ['compute'], 'platforms' => ['tencent']]],
                ['item_code' => 'cdb', 'item_name' => '云数据库MySQL', 'item_value' => 'cdb', 'sort_order' => 7, 'attributes' => ['supported_resource_types' => ['database'], 'platforms' => ['tencent']]],
                ['item_code' => 'clb', 'item_name' => '负载均衡CLB', 'item_value' => 'clb', 'sort_order' => 8, 'attributes' => ['supported_resource_types' => ['network'], 'platforms' => ['tencent']]],
                ['item_code' => 'cos', 'item_name' => '对象存储COS', 'item_value' => 'cos', 'sort_order' => 9, 'attributes' => ['supported_resource_types' => ['storage'], 'platforms' => ['tencent']]],
                
                // 华为云组件
                ['item_code' => 'ecs_huawei', 'item_name' => '弹性云服务器ECS', 'item_value' => 'ecs', 'sort_order' => 10, 'attributes' => ['supported_resource_types' => ['compute'], 'platforms' => ['huawei']]],
                ['item_code' => 'rds_huawei', 'item_name' => '关系型数据库RDS', 'item_value' => 'rds', 'sort_order' => 11, 'attributes' => ['supported_resource_types' => ['database'], 'platforms' => ['huawei']]],
                ['item_code' => 'elb', 'item_name' => '弹性负载均衡ELB', 'item_value' => 'elb', 'sort_order' => 12, 'attributes' => ['supported_resource_types' => ['network'], 'platforms' => ['huawei']]],
                ['item_code' => 'obs', 'item_name' => '对象存储服务OBS', 'item_value' => 'obs', 'sort_order' => 13, 'attributes' => ['supported_resource_types' => ['storage'], 'platforms' => ['huawei']]],
            ];

            foreach ($componentTypes as $type) {
                $attributes = $type['attributes'] ?? null;
                unset($type['attributes']);
                
                $item = DictItem::updateOrCreate(
                    [
                        'category_id' => $componentTypeCategory->id,
                        'item_code' => $type['item_code']
                    ],
                    array_merge($type, ['status' => 'active', 'attributes' => $attributes ? json_encode($attributes) : null])
                );
            }

            // 初始化平台组件映射数据
            $platformComponents = [
                // 阿里云平台组件
                ['item_code' => 'aliyun_ecs', 'item_name' => '阿里云ECS', 'item_value' => 'ecs', 'sort_order' => 1, 'attributes' => ['supported_platforms' => ['aliyun'], 'component_type' => 'ecs', 'api_version' => '2014-05-26']],
                ['item_code' => 'aliyun_rds', 'item_name' => '阿里云RDS', 'item_value' => 'rds', 'sort_order' => 2, 'attributes' => ['supported_platforms' => ['aliyun'], 'component_type' => 'rds', 'api_version' => '2014-08-15']],
                ['item_code' => 'aliyun_slb', 'item_name' => '阿里云SLB', 'item_value' => 'slb', 'sort_order' => 3, 'attributes' => ['supported_platforms' => ['aliyun'], 'component_type' => 'slb', 'api_version' => '2014-05-15']],
                ['item_code' => 'aliyun_oss', 'item_name' => '阿里云OSS', 'item_value' => 'oss', 'sort_order' => 4, 'attributes' => ['supported_platforms' => ['aliyun'], 'component_type' => 'oss', 'api_version' => '2013-10-15']],
                ['item_code' => 'aliyun_vpc', 'item_name' => '阿里云VPC', 'item_value' => 'vpc', 'sort_order' => 5, 'attributes' => ['supported_platforms' => ['aliyun'], 'component_type' => 'vpc', 'api_version' => '2016-04-28']],
                
                // 腾讯云平台组件
                ['item_code' => 'tencent_cvm', 'item_name' => '腾讯云CVM', 'item_value' => 'cvm', 'sort_order' => 6, 'attributes' => ['supported_platforms' => ['tencent'], 'component_type' => 'cvm', 'api_version' => '2017-03-12']],
                ['item_code' => 'tencent_cdb', 'item_name' => '腾讯云CDB', 'item_value' => 'cdb', 'sort_order' => 7, 'attributes' => ['supported_platforms' => ['tencent'], 'component_type' => 'cdb', 'api_version' => '2017-03-20']],
                ['item_code' => 'tencent_clb', 'item_name' => '腾讯云CLB', 'item_value' => 'clb', 'sort_order' => 8, 'attributes' => ['supported_platforms' => ['tencent'], 'component_type' => 'clb', 'api_version' => '2018-03-17']],
                ['item_code' => 'tencent_cos', 'item_name' => '腾讯云COS', 'item_value' => 'cos', 'sort_order' => 9, 'attributes' => ['supported_platforms' => ['tencent'], 'component_type' => 'cos', 'api_version' => '2018-11-26']],
                
                // 华为云平台组件
                ['item_code' => 'huawei_ecs', 'item_name' => '华为云ECS', 'item_value' => 'ecs', 'sort_order' => 10, 'attributes' => ['supported_platforms' => ['huawei'], 'component_type' => 'ecs', 'api_version' => 'v2.1']],
                ['item_code' => 'huawei_rds', 'item_name' => '华为云RDS', 'item_value' => 'rds', 'sort_order' => 11, 'attributes' => ['supported_platforms' => ['huawei'], 'component_type' => 'rds', 'api_version' => 'v3']],
                ['item_code' => 'huawei_elb', 'item_name' => '华为云ELB', 'item_value' => 'elb', 'sort_order' => 12, 'attributes' => ['supported_platforms' => ['huawei'], 'component_type' => 'elb', 'api_version' => 'v2.0']],
                ['item_code' => 'huawei_obs', 'item_name' => '华为云OBS', 'item_value' => 'obs', 'sort_order' => 13, 'attributes' => ['supported_platforms' => ['huawei'], 'component_type' => 'obs', 'api_version' => '3.0']],
            ];

            foreach ($platformComponents as $component) {
                $attributes = $component['attributes'] ?? null;
                unset($component['attributes']);
                
                $item = DictItem::updateOrCreate(
                    [
                        'category_id' => $platformComponentCategory->id,
                        'item_code' => $component['item_code']
                    ],
                    array_merge($component, ['status' => 'active', 'attributes' => $attributes ? json_encode($attributes) : null])
                );
            }

            // 初始化资源状态数据
            $resourceStatuses = [
                ['item_code' => 'running', 'item_name' => '运行中', 'item_value' => 'running', 'sort_order' => 1, 'attributes' => ['color' => 'success', 'icon' => 'play-circle']],
                ['item_code' => 'stopped', 'item_name' => '已停止', 'item_value' => 'stopped', 'sort_order' => 2, 'attributes' => ['color' => 'danger', 'icon' => 'stop-circle']],
                ['item_code' => 'starting', 'item_name' => '启动中', 'item_value' => 'starting', 'sort_order' => 3, 'attributes' => ['color' => 'warning', 'icon' => 'play']],
                ['item_code' => 'stopping', 'item_name' => '停止中', 'item_value' => 'stopping', 'sort_order' => 4, 'attributes' => ['color' => 'warning', 'icon' => 'stop']],
                ['item_code' => 'pending', 'item_name' => '待处理', 'item_value' => 'pending', 'sort_order' => 5, 'attributes' => ['color' => 'info', 'icon' => 'clock']],
                ['item_code' => 'error', 'item_name' => '错误', 'item_value' => 'error', 'sort_order' => 6, 'attributes' => ['color' => 'danger', 'icon' => 'exclamation-triangle']],
                ['item_code' => 'unknown', 'item_name' => '未知', 'item_value' => 'unknown', 'sort_order' => 7, 'attributes' => ['color' => 'secondary', 'icon' => 'question-circle']],
            ];

            foreach ($resourceStatuses as $status) {
                $attributes = $status['attributes'] ?? null;
                unset($status['attributes']);
                
                $item = DictItem::updateOrCreate(
                    [
                        'category_id' => $resourceStatusCategory->id,
                        'item_code' => $status['item_code']
                    ],
                    array_merge($status, ['status' => 'active', 'attributes' => $attributes ? json_encode($attributes) : null])
                );
            }

            $this->command->info('字典数据初始化完成！');
        });
    }
}