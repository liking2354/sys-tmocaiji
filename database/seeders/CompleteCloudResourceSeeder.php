<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DictCategory;
use App\Models\DictItem;

class CompleteCloudResourceSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('开始创建完整的云资源字典数据...');

        // 禁用外键检查
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // 清空现有数据
        DictItem::truncate();
        DictCategory::truncate();
        
        // 启用外键检查
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 创建字典分类
        $categories = $this->createCategories();
        
        // 创建云平台类型
        $this->createPlatformTypes($categories['platform_types']);
        
        // 创建资源状态
        $this->createResourceStatuses($categories['resource_status']);
        
        // 创建完整的三级云资源层次结构
        $this->createCompleteCloudResourceHierarchy($categories['cloud_resource_hierarchy']);

        $this->command->info('云资源字典数据创建完成！');
    }

    private function createCategories()
    {
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
            ],
            [
                'category_code' => 'instance_types',
                'category_name' => '实例规格',
                'description' => '云服务器实例规格分类',
                'sort_order' => 4,
                'status' => 'active'
            ],
            [
                'category_code' => 'billing_modes',
                'category_name' => '计费模式',
                'description' => '云资源计费模式',
                'sort_order' => 5,
                'status' => 'active'
            ]
        ];

        $result = [];
        foreach ($categories as $category) {
            $result[$category['category_code']] = DictCategory::create($category);
        }

        return $result;
    }

    private function createPlatformTypes($category)
    {
        $platforms = [
            ['item_code' => 'aliyun', 'item_name' => '阿里云', 'description' => '阿里巴巴云计算平台', 'sort_order' => 1],
            ['item_code' => 'tencent', 'item_name' => '腾讯云', 'description' => '腾讯云计算平台', 'sort_order' => 2],
            ['item_code' => 'huawei', 'item_name' => '华为云', 'description' => '华为云计算平台', 'sort_order' => 3],
            ['item_code' => 'aws', 'item_name' => 'AWS', 'description' => '亚马逊云服务', 'sort_order' => 4],
            ['item_code' => 'azure', 'item_name' => 'Azure', 'description' => '微软云服务', 'sort_order' => 5],
            ['item_code' => 'gcp', 'item_name' => 'Google Cloud', 'description' => '谷歌云平台', 'sort_order' => 6],
        ];

        foreach ($platforms as $platform) {
            DictItem::create([
                'category_id' => $category->id,
                'item_code' => $platform['item_code'],
                'item_name' => $platform['item_name'],
                'description' => $platform['description'],
                'level' => 1,
                'sort_order' => $platform['sort_order'],
                'status' => 'active'
            ]);
        }
    }

    private function createResourceStatuses($category)
    {
        $statuses = [
            ['item_code' => 'running', 'item_name' => '运行中', 'description' => '资源正常运行状态', 'sort_order' => 1],
            ['item_code' => 'stopped', 'item_name' => '已停止', 'description' => '资源已停止运行', 'sort_order' => 2],
            ['item_code' => 'starting', 'item_name' => '启动中', 'description' => '资源正在启动', 'sort_order' => 3],
            ['item_code' => 'stopping', 'item_name' => '停止中', 'description' => '资源正在停止', 'sort_order' => 4],
            ['item_code' => 'rebooting', 'item_name' => '重启中', 'description' => '资源正在重启', 'sort_order' => 5],
            ['item_code' => 'creating', 'item_name' => '创建中', 'description' => '资源正在创建', 'sort_order' => 6],
            ['item_code' => 'deleting', 'item_name' => '删除中', 'description' => '资源正在删除', 'sort_order' => 7],
            ['item_code' => 'error', 'item_name' => '异常', 'description' => '资源状态异常', 'sort_order' => 8],
            ['item_code' => 'unknown', 'item_name' => '未知', 'description' => '资源状态未知', 'sort_order' => 9],
        ];

        foreach ($statuses as $status) {
            DictItem::create([
                'category_id' => $category->id,
                'item_code' => $status['item_code'],
                'item_name' => $status['item_name'],
                'description' => $status['description'],
                'level' => 1,
                'sort_order' => $status['sort_order'],
                'status' => 'active'
            ]);
        }
    }

    private function createCompleteCloudResourceHierarchy($category)
    {
        // 一级：资源大类
        $level1Items = [
            ['code' => 'compute', 'name' => '计算资源', 'desc' => '提供计算能力的云服务', 'sort' => 1],
            ['code' => 'storage', 'name' => '存储资源', 'desc' => '提供数据存储的云服务', 'sort' => 2],
            ['code' => 'network', 'name' => '网络资源', 'desc' => '提供网络连接的云服务', 'sort' => 3],
            ['code' => 'database', 'name' => '数据库资源', 'desc' => '提供数据库服务的云服务', 'sort' => 4],
            ['code' => 'security', 'name' => '安全资源', 'desc' => '提供安全防护的云服务', 'sort' => 5],
            ['code' => 'monitor', 'name' => '监控资源', 'desc' => '提供监控告警的云服务', 'sort' => 6],
            ['code' => 'ai', 'name' => 'AI资源', 'desc' => '提供人工智能的云服务', 'sort' => 7],
        ];

        $level1Ids = [];
        foreach ($level1Items as $item) {
            $dictItem = DictItem::create([
                'category_id' => $category->id,
                'item_code' => $item['code'],
                'item_name' => $item['name'],
                'description' => $item['desc'],
                'level' => 1,
                'sort_order' => $item['sort'],
                'status' => 'active'
            ]);
            $level1Ids[$item['code']] = $dictItem->id;
        }

        // 二级：具体服务类型
        $level2Items = [
            // 计算资源
            ['parent' => 'compute', 'code' => 'ecs', 'name' => '云服务器', 'desc' => '弹性云服务器实例', 'sort' => 1],
            ['parent' => 'compute', 'code' => 'container', 'name' => '容器服务', 'desc' => 'Docker容器托管服务', 'sort' => 2],
            ['parent' => 'compute', 'code' => 'serverless', 'name' => '函数计算', 'desc' => '无服务器计算服务', 'sort' => 3],
            ['parent' => 'compute', 'code' => 'batch', 'name' => '批量计算', 'desc' => '大规模并行批处理', 'sort' => 4],
            
            // 存储资源
            ['parent' => 'storage', 'code' => 'object_storage', 'name' => '对象存储', 'desc' => '海量非结构化数据存储', 'sort' => 1],
            ['parent' => 'storage', 'code' => 'block_storage', 'name' => '块存储', 'desc' => '高性能块设备存储', 'sort' => 2],
            ['parent' => 'storage', 'code' => 'file_storage', 'name' => '文件存储', 'desc' => '共享文件系统存储', 'sort' => 3],
            ['parent' => 'storage', 'code' => 'backup', 'name' => '备份存储', 'desc' => '数据备份和恢复服务', 'sort' => 4],
            
            // 网络资源
            ['parent' => 'network', 'code' => 'vpc', 'name' => '专有网络', 'desc' => '隔离的虚拟网络环境', 'sort' => 1],
            ['parent' => 'network', 'code' => 'load_balancer', 'name' => '负载均衡', 'desc' => '流量分发和负载均衡', 'sort' => 2],
            ['parent' => 'network', 'code' => 'cdn', 'name' => '内容分发', 'desc' => '全球内容加速分发', 'sort' => 3],
            ['parent' => 'network', 'code' => 'nat_gateway', 'name' => 'NAT网关', 'desc' => '网络地址转换服务', 'sort' => 4],
            ['parent' => 'network', 'code' => 'vpn', 'name' => 'VPN网关', 'desc' => '虚拟专用网络连接', 'sort' => 5],
            
            // 数据库资源
            ['parent' => 'database', 'code' => 'rds', 'name' => '关系型数据库', 'desc' => 'MySQL、PostgreSQL等关系型数据库', 'sort' => 1],
            ['parent' => 'database', 'code' => 'nosql', 'name' => 'NoSQL数据库', 'desc' => 'MongoDB、DynamoDB等非关系型数据库', 'sort' => 2],
            ['parent' => 'database', 'code' => 'cache', 'name' => '缓存数据库', 'desc' => 'Redis、Memcached等缓存服务', 'sort' => 3],
            ['parent' => 'database', 'code' => 'data_warehouse', 'name' => '数据仓库', 'desc' => '大数据分析和数据仓库服务', 'sort' => 4],
            
            // 安全资源
            ['parent' => 'security', 'code' => 'waf', 'name' => 'Web应用防火墙', 'desc' => 'Web应用安全防护', 'sort' => 1],
            ['parent' => 'security', 'code' => 'security_group', 'name' => '安全组', 'desc' => '虚拟防火墙规则', 'sort' => 2],
            ['parent' => 'security', 'code' => 'ssl', 'name' => 'SSL证书', 'desc' => 'HTTPS安全证书服务', 'sort' => 3],
            ['parent' => 'security', 'code' => 'ddos', 'name' => 'DDoS防护', 'desc' => '分布式拒绝服务攻击防护', 'sort' => 4],
            
            // 监控资源
            ['parent' => 'monitor', 'code' => 'cloud_monitor', 'name' => '云监控', 'desc' => '资源监控和告警服务', 'sort' => 1],
            ['parent' => 'monitor', 'code' => 'log_service', 'name' => '日志服务', 'desc' => '日志收集和分析服务', 'sort' => 2],
            ['parent' => 'monitor', 'code' => 'apm', 'name' => '应用性能监控', 'desc' => '应用程序性能监控', 'sort' => 3],
            
            // AI资源
            ['parent' => 'ai', 'code' => 'machine_learning', 'name' => '机器学习', 'desc' => '机器学习平台服务', 'sort' => 1],
            ['parent' => 'ai', 'code' => 'image_recognition', 'name' => '图像识别', 'desc' => '计算机视觉和图像识别', 'sort' => 2],
            ['parent' => 'ai', 'code' => 'speech', 'name' => '语音服务', 'desc' => '语音识别和合成服务', 'sort' => 3],
        ];

        $level2Ids = [];
        foreach ($level2Items as $item) {
            $dictItem = DictItem::create([
                'category_id' => $category->id,
                'item_code' => $item['code'],
                'item_name' => $item['name'],
                'description' => $item['desc'],
                'parent_id' => $level1Ids[$item['parent']],
                'level' => 2,
                'sort_order' => $item['sort'],
                'status' => 'active'
            ]);
            $level2Ids[$item['code']] = $dictItem->id;
        }

        // 三级：各平台具体实现
        $level3Items = [
            // 云服务器 - 各平台实现
            ['parent' => 'ecs', 'code' => 'aliyun_ecs', 'name' => '阿里云ECS', 'platform' => 'aliyun', 'sort' => 1],
            ['parent' => 'ecs', 'code' => 'tencent_cvm', 'name' => '腾讯云CVM', 'platform' => 'tencent', 'sort' => 2],
            ['parent' => 'ecs', 'code' => 'huawei_ecs', 'name' => '华为云ECS', 'platform' => 'huawei', 'sort' => 3],
            ['parent' => 'ecs', 'code' => 'aws_ec2', 'name' => 'AWS EC2', 'platform' => 'aws', 'sort' => 4],
            ['parent' => 'ecs', 'code' => 'azure_vm', 'name' => 'Azure虚拟机', 'platform' => 'azure', 'sort' => 5],
            
            // 容器服务 - 各平台实现
            ['parent' => 'container', 'code' => 'aliyun_ack', 'name' => '阿里云ACK', 'platform' => 'aliyun', 'sort' => 1],
            ['parent' => 'container', 'code' => 'tencent_tke', 'name' => '腾讯云TKE', 'platform' => 'tencent', 'sort' => 2],
            ['parent' => 'container', 'code' => 'huawei_cce', 'name' => '华为云CCE', 'platform' => 'huawei', 'sort' => 3],
            ['parent' => 'container', 'code' => 'aws_eks', 'name' => 'AWS EKS', 'platform' => 'aws', 'sort' => 4],
            ['parent' => 'container', 'code' => 'azure_aks', 'name' => 'Azure AKS', 'platform' => 'azure', 'sort' => 5],
            
            // 对象存储 - 各平台实现
            ['parent' => 'object_storage', 'code' => 'aliyun_oss', 'name' => '阿里云OSS', 'platform' => 'aliyun', 'sort' => 1],
            ['parent' => 'object_storage', 'code' => 'tencent_cos', 'name' => '腾讯云COS', 'platform' => 'tencent', 'sort' => 2],
            ['parent' => 'object_storage', 'code' => 'huawei_obs', 'name' => '华为云OBS', 'platform' => 'huawei', 'sort' => 3],
            ['parent' => 'object_storage', 'code' => 'aws_s3', 'name' => 'AWS S3', 'platform' => 'aws', 'sort' => 4],
            ['parent' => 'object_storage', 'code' => 'azure_blob', 'name' => 'Azure Blob', 'platform' => 'azure', 'sort' => 5],
            
            // 专有网络 - 各平台实现
            ['parent' => 'vpc', 'code' => 'aliyun_vpc', 'name' => '阿里云VPC', 'platform' => 'aliyun', 'sort' => 1],
            ['parent' => 'vpc', 'code' => 'tencent_vpc', 'name' => '腾讯云VPC', 'platform' => 'tencent', 'sort' => 2],
            ['parent' => 'vpc', 'code' => 'huawei_vpc', 'name' => '华为云VPC', 'platform' => 'huawei', 'sort' => 3],
            ['parent' => 'vpc', 'code' => 'aws_vpc', 'name' => 'AWS VPC', 'platform' => 'aws', 'sort' => 4],
            ['parent' => 'vpc', 'code' => 'azure_vnet', 'name' => 'Azure VNet', 'platform' => 'azure', 'sort' => 5],
            
            // 负载均衡 - 各平台实现
            ['parent' => 'load_balancer', 'code' => 'aliyun_slb', 'name' => '阿里云SLB', 'platform' => 'aliyun', 'sort' => 1],
            ['parent' => 'load_balancer', 'code' => 'tencent_clb', 'name' => '腾讯云CLB', 'platform' => 'tencent', 'sort' => 2],
            ['parent' => 'load_balancer', 'code' => 'huawei_elb', 'name' => '华为云ELB', 'platform' => 'huawei', 'sort' => 3],
            ['parent' => 'load_balancer', 'code' => 'aws_elb', 'name' => 'AWS ELB', 'platform' => 'aws', 'sort' => 4],
            ['parent' => 'load_balancer', 'code' => 'azure_lb', 'name' => 'Azure负载均衡', 'platform' => 'azure', 'sort' => 5],
            
            // 关系型数据库 - 各平台实现
            ['parent' => 'rds', 'code' => 'aliyun_rds', 'name' => '阿里云RDS', 'platform' => 'aliyun', 'sort' => 1],
            ['parent' => 'rds', 'code' => 'tencent_cdb', 'name' => '腾讯云CDB', 'platform' => 'tencent', 'sort' => 2],
            ['parent' => 'rds', 'code' => 'huawei_rds', 'name' => '华为云RDS', 'platform' => 'huawei', 'sort' => 3],
            ['parent' => 'rds', 'code' => 'aws_rds', 'name' => 'AWS RDS', 'platform' => 'aws', 'sort' => 4],
            ['parent' => 'rds', 'code' => 'azure_sql', 'name' => 'Azure SQL', 'platform' => 'azure', 'sort' => 5],
            
            // 缓存数据库 - 各平台实现
            ['parent' => 'cache', 'code' => 'aliyun_redis', 'name' => '阿里云Redis', 'platform' => 'aliyun', 'sort' => 1],
            ['parent' => 'cache', 'code' => 'tencent_redis', 'name' => '腾讯云Redis', 'platform' => 'tencent', 'sort' => 2],
            ['parent' => 'cache', 'code' => 'huawei_redis', 'name' => '华为云Redis', 'platform' => 'huawei', 'sort' => 3],
            ['parent' => 'cache', 'code' => 'aws_elasticache', 'name' => 'AWS ElastiCache', 'platform' => 'aws', 'sort' => 4],
            ['parent' => 'cache', 'code' => 'azure_redis', 'name' => 'Azure Redis', 'platform' => 'azure', 'sort' => 5],
        ];

        foreach ($level3Items as $item) {
            DictItem::create([
                'category_id' => $category->id,
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