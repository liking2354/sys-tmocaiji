<?php

namespace App\Services\CloudPlatform\Components;

use App\Models\CloudResource;
use App\Models\CloudComputeResource;
use App\Services\CloudPlatform\Contracts\CloudPlatformInterface;
use App\Services\CloudPlatform\Contracts\CloudComponentInterface;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EcsComponent implements CloudComponentInterface
{
    private CloudPlatformInterface $platform;
    private array $config;

    public function __construct(CloudPlatformInterface $platform, array $config = [])
    {
        $this->platform = $platform;
        $this->config = $config;
    }

    /**
     * 获取组件类型
     */
    public function getComponentType(): string
    {
        return 'ecs';
    }

    /**
     * 获取组件名称
     */
    public function getComponentName(): string
    {
        return 'ECS云服务器';
    }

    /**
     * 同步资源数据
     */
    public function syncResources(int $platformId, ?string $region = null): array
    {
        $result = [
            'success' => true,
            'message' => '',
            'synced_count' => 0,
            'error_count' => 0,
            'errors' => []
        ];

        try {
            Log::info("开始同步ECS资源", [
                'platform_id' => $platformId,
                'region' => $region,
                'platform_type' => $this->platform->getPlatformType()
            ]);

            // 获取ECS实例列表
            $instances = $this->platform->getEcsInstances($region);
            
            if (empty($instances)) {
                $result['message'] = '未发现ECS实例';
                return $result;
            }

            Log::info("获取到ECS实例", ['count' => count($instances)]);

            DB::transaction(function () use ($instances, $platformId, $region, &$result) {
                foreach ($instances as $instanceData) {
                    try {
                        $this->syncSingleInstance($instanceData, $platformId, $region);
                        $result['synced_count']++;
                    } catch (Exception $e) {
                        $result['error_count']++;
                        $result['errors'][] = [
                            'instance_id' => $instanceData['instance_id'] ?? 'unknown',
                            'error' => $e->getMessage()
                        ];
                        
                        Log::error("同步ECS实例失败", [
                            'instance_id' => $instanceData['instance_id'] ?? 'unknown',
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            });

            $result['message'] = "同步完成：成功 {$result['synced_count']} 个，失败 {$result['error_count']} 个";
            
            if ($result['error_count'] > 0) {
                $result['success'] = false;
            }

        } catch (Exception $e) {
            $result['success'] = false;
            $result['message'] = "同步失败：" . $e->getMessage();
            
            Log::error("ECS资源同步异常", [
                'platform_id' => $platformId,
                'region' => $region,
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }

    /**
     * 同步单个ECS实例
     */
    private function syncSingleInstance(array $instanceData, int $platformId, ?string $region): void
    {
        // 格式化资源数据
        $formattedData = $this->platform->formatResourceData('ecs', $instanceData);
        
        // 查找或创建云资源记录
        $cloudResource = CloudResource::updateOrCreate(
            [
                'platform_id' => $platformId,
                'resource_type' => 'ecs',
                'resource_id' => $formattedData['resource_id'],
                'region' => $formattedData['region'] ?? $region,
            ],
            [
                'name' => $formattedData['name'],
                'status' => $formattedData['status'],
                'raw_data' => $formattedData['raw_data'],
                'metadata' => $formattedData['metadata'],
                'last_sync_at' => now(),
            ]
        );

        // 提取ECS特定数据
        $ecsData = $this->extractEcsData($instanceData, $formattedData);
        
        // 更新或创建ECS计算资源记录
        CloudComputeResource::updateOrCreate(
            ['cloud_resource_id' => $cloudResource->id],
            $ecsData
        );

        Log::debug("ECS实例同步成功", [
            'instance_id' => $formattedData['resource_id'],
            'name' => $formattedData['name']
        ]);
    }

    /**
     * 提取ECS特定数据
     */
    private function extractEcsData(array $instanceData, array $formattedData): array
    {
        $platformType = $this->platform->getPlatformType();
        
        switch ($platformType) {
            case 'alibaba':
                return $this->extractAlibabaEcsData($instanceData);
            case 'tencent':
                return $this->extractTencentEcsData($instanceData);
            default:
                throw new Exception("不支持的平台类型: {$platformType}");
        }
    }

    /**
     * 提取阿里云ECS数据
     */
    private function extractAlibabaEcsData(array $instanceData): array
    {
        return [
            'instance_id' => $instanceData['InstanceId'] ?? '',
            'instance_name' => $instanceData['InstanceName'] ?? '',
            'instance_type' => $instanceData['InstanceType'] ?? '',
            'cpu_cores' => $instanceData['Cpu'] ?? 0,
            'memory_gb' => ($instanceData['Memory'] ?? 0) / 1024, // MB转GB
            'os_type' => $this->normalizeOsType($instanceData['OSType'] ?? ''),
            'os_name' => $instanceData['OSName'] ?? '',
            'image_id' => $instanceData['ImageId'] ?? '',
            'vpc_id' => $instanceData['VpcAttributes']['VpcId'] ?? '',
            'subnet_id' => $instanceData['VpcAttributes']['VSwitchId'] ?? '',
            'security_group_ids' => $instanceData['SecurityGroupIds']['SecurityGroupId'] ?? [],
            'public_ip' => $instanceData['PublicIpAddress']['IpAddress'][0] ?? '',
            'private_ip' => $instanceData['InnerIpAddress']['IpAddress'][0] ?? '',
            'bandwidth_mbps' => $instanceData['InternetMaxBandwidthOut'] ?? 0,
            'disk_type' => $instanceData['InstanceTypeFamily'] ?? '',
            'disk_size_gb' => 0, // 需要单独获取磁盘信息
            'instance_status' => $this->normalizeInstanceStatus($instanceData['Status'] ?? ''),
            'instance_charge_type' => $this->normalizeChargeType($instanceData['InstanceChargeType'] ?? ''),
            'expired_time' => isset($instanceData['ExpiredTime']) ? 
                \Carbon\Carbon::parse($instanceData['ExpiredTime']) : null,
            'created_time' => isset($instanceData['CreationTime']) ? 
                \Carbon\Carbon::parse($instanceData['CreationTime']) : null,
            'tags' => $this->extractTags($instanceData['Tags']['Tag'] ?? []),
            'monitoring_enabled' => true,
            'auto_scaling_enabled' => false,
        ];
    }

    /**
     * 提取腾讯云ECS数据
     */
    private function extractTencentEcsData(array $instanceData): array
    {
        return [
            'instance_id' => $instanceData['InstanceId'] ?? '',
            'instance_name' => $instanceData['InstanceName'] ?? '',
            'instance_type' => $instanceData['InstanceType'] ?? '',
            'cpu_cores' => $instanceData['CPU'] ?? 0,
            'memory_gb' => $instanceData['Memory'] ?? 0,
            'os_type' => $this->normalizeOsType($instanceData['OsName'] ?? ''),
            'os_name' => $instanceData['OsName'] ?? '',
            'image_id' => $instanceData['ImageId'] ?? '',
            'vpc_id' => $instanceData['VirtualPrivateCloud']['VpcId'] ?? '',
            'subnet_id' => $instanceData['VirtualPrivateCloud']['SubnetId'] ?? '',
            'security_group_ids' => $instanceData['SecurityGroupIds'] ?? [],
            'public_ip' => $instanceData['PublicIpAddresses'][0] ?? '',
            'private_ip' => $instanceData['PrivateIpAddresses'][0] ?? '',
            'bandwidth_mbps' => $instanceData['InternetAccessible']['InternetMaxBandwidthOut'] ?? 0,
            'disk_type' => $instanceData['SystemDisk']['DiskType'] ?? '',
            'disk_size_gb' => $instanceData['SystemDisk']['DiskSize'] ?? 0,
            'instance_status' => $this->normalizeInstanceStatus($instanceData['InstanceState'] ?? ''),
            'instance_charge_type' => $this->normalizeChargeType($instanceData['InstanceChargeType'] ?? ''),
            'expired_time' => isset($instanceData['ExpiredTime']) ? 
                \Carbon\Carbon::parse($instanceData['ExpiredTime']) : null,
            'created_time' => isset($instanceData['CreatedTime']) ? 
                \Carbon\Carbon::parse($instanceData['CreatedTime']) : null,
            'tags' => $this->extractTags($instanceData['Tags'] ?? []),
            'monitoring_enabled' => true,
            'auto_scaling_enabled' => false,
        ];
    }

    /**
     * 标准化操作系统类型
     */
    private function normalizeOsType(string $osType): string
    {
        $osType = strtolower($osType);
        
        if (strpos($osType, 'windows') !== false) {
            return 'windows';
        } elseif (strpos($osType, 'linux') !== false || 
                  strpos($osType, 'ubuntu') !== false || 
                  strpos($osType, 'centos') !== false || 
                  strpos($osType, 'debian') !== false) {
            return 'linux';
        }
        
        return 'unknown';
    }

    /**
     * 标准化实例状态
     */
    private function normalizeInstanceStatus(string $status): string
    {
        $statusMap = [
            // 阿里云状态
            'Running' => 'running',
            'Stopped' => 'stopped',
            'Starting' => 'starting',
            'Stopping' => 'stopping',
            'Pending' => 'pending',
            
            // 腾讯云状态
            'RUNNING' => 'running',
            'STOPPED' => 'stopped',
            'STARTING' => 'starting',
            'STOPPING' => 'stopping',
            'REBOOTING' => 'rebooting',
            'PENDING' => 'pending',
            'TERMINATING' => 'terminated',
        ];

        return $statusMap[$status] ?? 'unknown';
    }

    /**
     * 标准化计费类型
     */
    private function normalizeChargeType(string $chargeType): string
    {
        $chargeTypeMap = [
            // 阿里云
            'PrePaid' => 'prepaid',
            'PostPaid' => 'postpaid',
            
            // 腾讯云
            'PREPAID' => 'prepaid',
            'POSTPAID_BY_HOUR' => 'postpaid',
            'SPOTPAID' => 'spot',
        ];

        return $chargeTypeMap[$chargeType] ?? 'postpaid';
    }

    /**
     * 提取标签信息
     */
    private function extractTags(array $tags): array
    {
        $result = [];
        
        foreach ($tags as $tag) {
            if (isset($tag['Key']) && isset($tag['Value'])) {
                $result[$tag['Key']] = $tag['Value'];
            } elseif (isset($tag['TagKey']) && isset($tag['TagValue'])) {
                $result[$tag['TagKey']] = $tag['TagValue'];
            }
        }
        
        return $result;
    }

    /**
     * 获取资源详情
     */
    public function getResourceDetail(string $resourceId, ?string $region = null): ?array
    {
        try {
            return $this->platform->getResourceDetail('ecs', $resourceId, $region);
        } catch (Exception $e) {
            Log::error("获取ECS资源详情失败", [
                'resource_id' => $resourceId,
                'region' => $region,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * 获取资源监控信息
     */
    public function getResourceMonitoring(string $resourceId, array $options = []): array
    {
        try {
            return $this->platform->getResourceMonitoring('ecs', $resourceId, $options);
        } catch (Exception $e) {
            Log::error("获取ECS监控信息失败", [
                'resource_id' => $resourceId,
                'options' => $options,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * 检查组件是否可用
     */
    public function isAvailable(): bool
    {
        try {
            // 尝试获取区域列表来测试连接
            $regions = $this->platform->getRegions();
            return !empty($regions);
        } catch (Exception $e) {
            return false;
        }
    }
}