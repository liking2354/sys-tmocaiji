<?php

namespace App\Services\CloudPlatform\Platforms;

use App\Services\CloudPlatform\AbstractCloudPlatform;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Cvm\V20170312\CvmClient;
use TencentCloud\Clb\V20180317\ClbClient;
use TencentCloud\Cdb\V20170320\CdbClient;
use TencentCloud\Redis\V20180412\RedisClient;
use TencentCloud\Domain\V20180808\DomainClient;
use Illuminate\Support\Facades\Log;
use Exception;

class TencentCloudPlatform extends AbstractCloudPlatform
{
    private ?CvmClient $cvmClient = null;
    private ?ClbClient $clbClient = null;
    private ?CdbClient $cdbClient = null;
    private ?RedisClient $redisClient = null;
    private ?DomainClient $domainClient = null;

    /**
     * 初始化客户端
     */
    protected function initializeClient(): void
    {
        $this->validateConfig(['access_key_id', 'access_key_secret', 'region']);

        $accessKeyId = $this->getConfig('access_key_id');
        $accessKeySecret = $this->getConfig('access_key_secret');
        $region = $this->getConfig('region');

        \Log::info('腾讯云客户端初始化开始', [
            'access_key_id' => substr($accessKeyId, 0, 8) . '***',
            'region' => $region
        ]);

        try {
            $credential = new Credential($accessKeyId, $accessKeySecret);

            $httpProfile = new HttpProfile();
            $httpProfile->setEndpoint("cvm.tencentcloudapi.com");

            $clientProfile = new ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);

            // 初始化各服务客户端
            $this->cvmClient = new CvmClient($credential, $region, $clientProfile);
            
            $clbProfile = new ClientProfile();
            $clbProfile->getHttpProfile()->setEndpoint("clb.tencentcloudapi.com");
            $this->clbClient = new ClbClient($credential, $region, $clbProfile);

            $cdbProfile = new ClientProfile();
            $cdbProfile->getHttpProfile()->setEndpoint("cdb.tencentcloudapi.com");
            $this->cdbClient = new CdbClient($credential, $region, $cdbProfile);

            $redisProfile = new ClientProfile();
            $redisProfile->getHttpProfile()->setEndpoint("redis.tencentcloudapi.com");
            $this->redisClient = new RedisClient($credential, $region, $redisProfile);

            $domainProfile = new ClientProfile();
            $domainProfile->getHttpProfile()->setEndpoint("domain.tencentcloudapi.com");
            $this->domainClient = new DomainClient($credential, $region, $domainProfile);

            \Log::info('腾讯云客户端初始化成功', [
                'access_key_id' => substr($accessKeyId, 0, 8) . '***',
                'region' => $region
            ]);
        } catch (\Exception $e) {
            \Log::error('腾讯云客户端初始化失败', [
                'error' => $e->getMessage(),
                'access_key_id' => substr($accessKeyId, 0, 8) . '***',
                'region' => $region
            ]);
            throw new \Exception('腾讯云客户端初始化失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取平台类型
     */
    public function getPlatformType(): string
    {
        return 'tencent';
    }

    /**
     * 获取平台名称
     */
    public function getPlatformName(): string
    {
        return '腾讯云';
    }

    /**
     * 获取区域列表
     */
    public function getRegions(): array
    {
        try {
            // 腾讯云区域列表
            return [
                ['region_code' => 'ap-beijing', 'region_name' => '华北地区（北京）'],
                ['region_code' => 'ap-shanghai', 'region_name' => '华东地区（上海）'],
                ['region_code' => 'ap-guangzhou', 'region_name' => '华南地区（广州）'],
                ['region_code' => 'ap-chengdu', 'region_name' => '西南地区（成都）'],
                ['region_code' => 'ap-chongqing', 'region_name' => '西南地区（重庆）'],
                ['region_code' => 'ap-nanjing', 'region_name' => '华东地区（南京）'],
                ['region_code' => 'ap-hongkong', 'region_name' => '港澳台地区（中国香港）'],
                ['region_code' => 'ap-singapore', 'region_name' => '亚太东南（新加坡）'],
                ['region_code' => 'ap-tokyo', 'region_name' => '亚太东北（东京）'],
                ['region_code' => 'us-west-1', 'region_name' => '美国西部（硅谷）'],
            ];
        } catch (Exception $e) {
            $this->handleApiException($e, 'getRegions');
            return [];
        }
    }

    /**
     * 获取云主机列表
     */
    public function getEcsInstances(?string $region = null): array
    {
        try {
            $region = $region ?? $this->getConfig('region');
            
            \Log::info('TencentCloud: 开始获取ECS实例', [
                'region' => $region,
                'config' => [
                    'access_key_id' => substr($this->getConfig('access_key_id'), 0, 8) . '***',
                    'region' => $this->getConfig('region')
                ]
            ]);
            
            $this->initializeClient();
            \Log::info('TencentCloud: 客户端初始化完成');

            // 调用腾讯云 CVM API 获取实例列表
            $req = new \TencentCloud\Cvm\V20170312\Models\DescribeInstancesRequest();
            
            // 设置分页参数
            $req->setLimit(100); // 每页最多100个实例
            $req->setOffset(0);
            
            // 不设置过滤器，查询所有状态的实例
            // $filter = new \TencentCloud\Cvm\V20170312\Models\Filter();
            // $filter->setName("instance-state");
            // $filter->setValues(["RUNNING"]);
            // $req->setFilters([$filter]);
            
            \Log::info('TencentCloud: 准备调用DescribeInstances API', [
                'limit' => 100,
                'offset' => 0,
                'region' => $region,
                'access_key_id' => substr($this->getConfig('access_key_id'), 0, 8) . '***'
            ]);
            
            $resp = $this->cvmClient->DescribeInstances($req);
            
            \Log::info('TencentCloud: API调用成功', [
                'total_count' => $resp->getTotalCount(),
                'instance_count' => $resp->getInstanceSet() ? count($resp->getInstanceSet()) : 0,
                'region' => $region,
                'response_class' => get_class($resp)
            ]);
            
            // 如果没有实例，记录详细信息
            if ($resp->getTotalCount() == 0) {
                \Log::info('TencentCloud: 该区域没有找到任何实例', [
                    'region' => $region,
                    'total_count' => $resp->getTotalCount(),
                    'message' => '可能原因：1) 该区域确实没有实例 2) 权限不足 3) 区域配置错误'
                ]);
            }
            
            $instances = [];
            
            if ($resp->getInstanceSet()) {
                \Log::info('TencentCloud: 开始处理实例数据', [
                    'instance_count' => count($resp->getInstanceSet())
                ]);
                
                foreach ($resp->getInstanceSet() as $index => $instance) {
                    \Log::info("TencentCloud: 处理实例 #{$index}", [
                        'instance_id' => $instance->getInstanceId(),
                        'instance_name' => $instance->getInstanceName(),
                        'instance_state' => $instance->getInstanceState(),
                        'zone' => $instance->getPlacement() ? $instance->getPlacement()->getZone() : 'unknown'
                    ]);
                    
                    $instanceData = [
                        'InstanceId' => $instance->getInstanceId(),
                        'InstanceName' => $instance->getInstanceName() ?: $instance->getInstanceId(),
                        'InstanceState' => $instance->getInstanceState(),
                        'Placement' => [
                            'Zone' => $instance->getPlacement() ? $instance->getPlacement()->getZone() : '',
                            'Region' => $region
                        ],
                        'InstanceType' => $instance->getInstanceType(),
                        'CPU' => $instance->getCPU(),
                        'Memory' => $instance->getMemory(),
                        'CreatedTime' => $instance->getCreatedTime(),
                        'PublicIpAddresses' => $instance->getPublicIpAddresses() ?: [],
                        'PrivateIpAddresses' => $instance->getPrivateIpAddresses() ?: [],
                        'ImageId' => $instance->getImageId(),
                        'SystemDisk' => $instance->getSystemDisk() ? [
                            'DiskType' => $instance->getSystemDisk()->getDiskType(),
                            'DiskSize' => $instance->getSystemDisk()->getDiskSize(),
                        ] : null,
                        'DataDisks' => $instance->getDataDisks() ? array_map(function($disk) {
                            return [
                                'DiskType' => $disk->getDiskType(),
                                'DiskSize' => $disk->getDiskSize(),
                            ];
                        }, $instance->getDataDisks()) : [],
                    ];
                    
                    $formattedData = $this->formatResourceData('ecs', $instanceData);
                    \Log::info("TencentCloud: 格式化后的实例数据 #{$index}", $formattedData);
                    
                    $instances[] = $formattedData;
                }
            } else {
                \Log::warning('TencentCloud: API返回的实例集合为空');
            }

            \Log::info('TencentCloud: 最终返回实例数量', ['count' => count($instances)]);
            $this->logApiCall('getEcsInstances', ['region' => $region], $instances);
            return $instances;
        } catch (Exception $e) {
            \Log::error('TencentCloud: getEcsInstances异常', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->handleApiException($e, 'getEcsInstances', ['region' => $region]);
            return [];
        }
    }

    /**
     * 获取负载均衡列表
     */
    public function getClbInstances(?string $region = null): array
    {
        try {
            $region = $region ?? $this->getConfig('region');
            $this->initializeClient();
            
            \Log::info('TencentCloud: 开始获取CLB实例列表', ['region' => $region]);
            
            // 调用腾讯云 CLB API 获取负载均衡列表
            $req = new \TencentCloud\Clb\V20180317\Models\DescribeLoadBalancersRequest();
            $req->setLimit(100);
            
            \Log::info('TencentCloud: 准备调用DescribeLoadBalancers API');
            $resp = $this->clbClient->DescribeLoadBalancers($req);
            
            $instances = [];
            $loadBalancers = $resp->getLoadBalancerSet();
            
            \Log::info('TencentCloud: CLB API调用成功', [
                'total_count' => $resp->getTotalCount(),
                'returned_count' => $loadBalancers ? count($loadBalancers) : 0
            ]);
            
            if ($loadBalancers) {
                foreach ($loadBalancers as $index => $lb) {
                    \Log::info("TencentCloud: 处理CLB实例 #{$index}", [
                        'LoadBalancerId' => $lb->getLoadBalancerId(),
                        'LoadBalancerName' => $lb->getLoadBalancerName(),
                        'Status' => $lb->getStatus()
                    ]);
                    
                    $lbData = [
                        'LoadBalancerId' => $lb->getLoadBalancerId(),
                        'LoadBalancerName' => $lb->getLoadBalancerName(),
                        'Status' => $lb->getStatus(),
                        'LoadBalancerType' => $lb->getLoadBalancerType(),
                        'LoadBalancerVips' => $lb->getLoadBalancerVips() ?: [],
                        'CreateTime' => $lb->getCreateTime(),
                        'Region' => $region,
                        'ProjectId' => $lb->getProjectId(),
                        'VpcId' => $lb->getVpcId(),
                        'SubnetId' => $lb->getSubnetId(),
                    ];
                    
                    $formattedData = $this->formatResourceData('clb', $lbData);
                    \Log::info("TencentCloud: 格式化后的CLB数据 #{$index}", $formattedData);
                    
                    $instances[] = $formattedData;
                }
            } else {
                \Log::warning('TencentCloud: API返回的CLB集合为空');
            }

            \Log::info('TencentCloud: 最终返回CLB数量', ['count' => count($instances)]);
            $this->logApiCall('getLoadBalancers', ['region' => $region], $instances);
            return $instances;
        } catch (Exception $e) {
            \Log::error('TencentCloud: getClbInstances异常', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            $this->handleApiException($e, 'getLoadBalancers', ['region' => $region]);
            return [];
        }
    }

    /**
     * 获取MySQL数据库列表
     */
    public function getCdbInstances(?string $region = null): array
    {
        try {
            $region = $region ?? $this->getConfig('region');
            $this->initializeClient();
            
            \Log::info('TencentCloud: 开始获取CDB实例列表', ['region' => $region]);
            
            // 调用腾讯云 CDB API 获取数据库实例列表
            $req = new \TencentCloud\Cdb\V20170320\Models\DescribeDBInstancesRequest();
            $req->setLimit(100);
            
            \Log::info('TencentCloud: 准备调用DescribeDBInstances API');
            $resp = $this->cdbClient->DescribeDBInstances($req);
            
            $instances = [];
            $dbInstances = $resp->getItems();
            
            \Log::info('TencentCloud: CDB API调用成功', [
                'total_count' => $resp->getTotalCount(),
                'returned_count' => $dbInstances ? count($dbInstances) : 0
            ]);
            
            if ($dbInstances) {
                foreach ($dbInstances as $index => $db) {
                    \Log::info("TencentCloud: 处理CDB实例 #{$index}", [
                        'InstanceId' => $db->getInstanceId(),
                        'InstanceName' => $db->getInstanceName(),
                        'Status' => $db->getStatus()
                    ]);
                    
                    $dbData = [
                        'InstanceId' => $db->getInstanceId(),
                        'InstanceName' => $db->getInstanceName(),
                        'Status' => $db->getStatus(),
                        'EngineVersion' => $db->getEngineVersion(),
                        'InstanceType' => $db->getInstanceType(),
                        'Memory' => $db->getMemory(),
                        'Volume' => $db->getVolume(),
                        'Vip' => $db->getVip(),
                        'Vport' => $db->getVport(),
                        'CreateTime' => $db->getCreateTime(),
                        'Region' => $region,
                        'Zone' => $db->getZone(),
                        'ProjectId' => $db->getProjectId(),
                        'VpcId' => $db->getUniqVpcId(),
                        'SubnetId' => $db->getUniqSubnetId(),
                    ];
                    
                    $formattedData = $this->formatResourceData('cdb', $dbData);
                    \Log::info("TencentCloud: 格式化后的CDB数据 #{$index}", $formattedData);
                    
                    $instances[] = $formattedData;
                }
            } else {
                \Log::warning('TencentCloud: API返回的CDB集合为空');
            }

            \Log::info('TencentCloud: 最终返回CDB数量', ['count' => count($instances)]);
            $this->logApiCall('getCdbInstances', ['region' => $region], $instances);
            return $instances;
        } catch (Exception $e) {
            \Log::error('TencentCloud: getCdbInstances异常', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            $this->handleApiException($e, 'getCdbInstances', ['region' => $region]);
            return [];
        }
    }

    /**
     * 获取Redis实例列表
     */
    public function getRedisInstances(?string $region = null): array
    {
        try {
            $region = $region ?? $this->getConfig('region');
            $this->initializeClient();
            
            \Log::info('TencentCloud: 开始获取Redis实例列表', ['region' => $region]);
            
            // 调用腾讯云 Redis API 获取实例列表
            $req = new \TencentCloud\Redis\V20180412\Models\DescribeInstancesRequest();
            $req->setLimit(100);
            
            \Log::info('TencentCloud: 准备调用DescribeInstances API (Redis)');
            $resp = $this->redisClient->DescribeInstances($req);
            
            $instances = [];
            $redisInstances = $resp->getInstanceSet();
            
            \Log::info('TencentCloud: Redis API调用成功', [
                'total_count' => $resp->getTotalCount(),
                'returned_count' => $redisInstances ? count($redisInstances) : 0
            ]);
            
            if ($redisInstances) {
                foreach ($redisInstances as $index => $redis) {
                    \Log::info("TencentCloud: 处理Redis实例 #{$index}", [
                        'InstanceId' => $redis->getInstanceId(),
                        'InstanceName' => $redis->getInstanceName(),
                        'Status' => $redis->getStatus()
                    ]);
                    
                    $redisData = [
                        'InstanceId' => $redis->getInstanceId(),
                        'InstanceName' => $redis->getInstanceName(),
                        'Status' => $redis->getStatus(),
                        'Type' => $redis->getType(),
                        'Size' => $redis->getSize(),
                        'RedisShardNum' => $redis->getRedisShardNum(),
                        'RedisReplicasNum' => $redis->getRedisReplicasNum(),
                        'Port' => $redis->getPort(),
                        'WanIp' => $redis->getWanIp(),
                        'CreateTime' => $redis->getCreatetime(),
                        'Region' => $region,
                        'Zone' => $redis->getZoneId(),
                        'ProjectId' => $redis->getProjectId(),
                        'VpcId' => $redis->getVpcId(),
                        'SubnetId' => $redis->getSubnetId(),
                    ];
                    
                    $formattedData = $this->formatResourceData('redis', $redisData);
                    \Log::info("TencentCloud: 格式化后的Redis数据 #{$index}", $formattedData);
                    
                    $instances[] = $formattedData;
                }
            } else {
                \Log::warning('TencentCloud: API返回的Redis集合为空');
            }

            \Log::info('TencentCloud: 最终返回Redis数量', ['count' => count($instances)]);
            $this->logApiCall('getRedisInstances', ['region' => $region], $instances);
            return $instances;
        } catch (Exception $e) {
            \Log::error('TencentCloud: getRedisInstances异常', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            $this->handleApiException($e, 'getRedisInstances', ['region' => $region]);
            return [];
        }
    }

    /**
     * 获取域名列表
     */
    public function getDomains(array $filters = []): array
    {
        try {
            $this->initializeClient();
            
            \Log::info('TencentCloud: 开始获取域名列表');
            
            // 调用腾讯云 Domain API 获取域名列表
            $req = new \TencentCloud\Domain\V20180808\Models\DescribeDomainListRequest();
            $req->setLimit(100);
            $req->setOffset(0);
            
            \Log::info('TencentCloud: 准备调用DescribeDomainList API');
            $resp = $this->domainClient->DescribeDomainList($req);
            
            $domains = [];
            $domainList = $resp->getDomainList();
            
            \Log::info('TencentCloud: Domain API调用成功', [
                'total_count' => $resp->getTotalCount(),
                'returned_count' => $domainList ? count($domainList) : 0
            ]);
            
            if ($domainList) {
                foreach ($domainList as $index => $domain) {
                    \Log::info("TencentCloud: 处理域名 #{$index}", [
                        'DomainName' => $domain->getDomainName(),
                        'Status' => $domain->getStatus(),
                        'CreationDate' => $domain->getCreationDate()
                    ]);
                    
                    $domainData = [
                        'DomainName' => $domain->getDomainName(),
                        'Status' => $domain->getStatus(),
                        'CreationDate' => $domain->getCreationDate(),
                        'ExpirationDate' => $domain->getExpirationDate(),
                        'IsPremium' => $domain->getIsPremium(),
                        'DnsStatus' => $domain->getDnsStatus(),
                        'DomainNameAuditStatus' => $domain->getDomainNameAuditStatus(),
                    ];
                    
                    $formattedData = $this->formatResourceData('domain', $domainData);
                    \Log::info("TencentCloud: 格式化后的域名数据 #{$index}", $formattedData);
                    
                    $domains[] = $formattedData;
                }
            } else {
                \Log::warning('TencentCloud: API返回的域名集合为空');
            }

            \Log::info('TencentCloud: 最终返回域名数量', ['count' => count($domains)]);
            $this->logApiCall('getDomains', [], $domains);
            return $domains;
        } catch (Exception $e) {
            \Log::error('TencentCloud: getDomains异常', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            $this->handleApiException($e, 'getDomains');
            return [];
        }
    }



    /**
     * 获取资源监控信息
     */
    public function getResourceMonitoring(string $resourceType, string $resourceId, array $options = []): array
    {
        try {
            $region = $options['region'] ?? $this->getConfig('region');
            
            // 这里应该调用腾讯云监控API获取监控数据
            // 暂时返回模拟数据
            $monitoringData = [
                'resource_id' => $resourceId,
                'resource_type' => $resourceType,
                'region' => $region,
                'metrics' => [],
                'timestamp' => now()->toISOString(),
            ];

            switch ($resourceType) {
                case 'ecs':
                    $monitoringData['metrics'] = [
                        'cpu_usage' => rand(12, 88),
                        'memory_usage' => rand(22, 78),
                        'disk_usage' => rand(32, 68),
                        'network_in' => rand(150, 1500),
                        'network_out' => rand(150, 1500),
                        'disk_read' => rand(75, 750),
                        'disk_write' => rand(75, 750),
                    ];
                    break;
                case 'clb':
                    $monitoringData['metrics'] = [
                        'active_connections' => rand(15, 150),
                        'new_connections' => rand(8, 80),
                        'requests_per_second' => rand(150, 1500),
                        'response_time' => rand(15, 150),
                        'error_rate' => rand(0, 3),
                    ];
                    break;
                case 'cdb':
                    $monitoringData['metrics'] = [
                        'cpu_usage' => rand(15, 75),
                        'memory_usage' => rand(35, 85),
                        'disk_usage' => rand(25, 75),
                        'connections' => rand(15, 150),
                        'qps' => rand(150, 1500),
                        'tps' => rand(75, 750),
                    ];
                    break;
                case 'redis':
                    $monitoringData['metrics'] = [
                        'cpu_usage' => rand(8, 58),
                        'memory_usage' => rand(45, 92),
                        'connections' => rand(15, 300),
                        'ops_per_second' => rand(1500, 15000),
                        'hit_rate' => rand(85, 98),
                        'expired_keys' => rand(0, 150),
                    ];
                    break;
            }

            return $monitoringData;
        } catch (Exception $e) {
            $this->handleApiException($e, 'getResourceMonitoring', [
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'region' => $region
            ]);
            return [];
        }
    }

    /**
     * 提取资源ID
     */
    protected function extractResourceId(string $resourceType, array $rawData): string
    {
        $idFields = [
            'ecs' => 'InstanceId',
            'clb' => 'LoadBalancerId',
            'cdb' => 'InstanceId',
            'redis' => 'InstanceId',
            'domain' => 'DomainName',
        ];

        return $rawData[$idFields[$resourceType] ?? 'id'] ?? '';
    }

    /**
     * 提取资源名称
     */
    protected function extractResourceName(string $resourceType, array $rawData): string
    {
        $nameFields = [
            'ecs' => 'InstanceName',
            'clb' => 'LoadBalancerName',
            'cdb' => 'InstanceName',
            'redis' => 'InstanceName',
            'domain' => 'DomainName',
        ];

        return $rawData[$nameFields[$resourceType] ?? 'name'] ?? '';
    }

    /**
     * 提取资源状态
     */
    protected function extractResourceStatus(string $resourceType, array $rawData): string
    {
        switch ($resourceType) {
            case 'ecs':
                return strtolower($rawData['InstanceState'] ?? 'unknown');
            case 'clb':
                return ($rawData['Status'] ?? 0) == 1 ? 'active' : 'inactive';
            case 'cdb':
                return ($rawData['Status'] ?? 0) == 1 ? 'running' : 'stopped';
            case 'redis':
                return ($rawData['Status'] ?? 0) == 2 ? 'running' : 'stopped';
            case 'domain':
                return strtolower($rawData['Status'] ?? 'unknown');
            default:
                return 'unknown';
        }
    }

    /**
     * 提取资源区域
     */
    protected function extractResourceRegion(string $resourceType, array $rawData): string
    {
        if ($resourceType === 'ecs' && isset($rawData['Placement']['Zone'])) {
            // 从可用区提取区域信息
            $zone = $rawData['Placement']['Zone'];
            return substr($zone, 0, strrpos($zone, '-'));
        }
        
        return $this->getConfig('region', '');
    }

    /**
     * 提取资源元数据
     */
    protected function extractResourceMetadata(string $resourceType, array $rawData): array
    {
        $metadata = [];

        switch ($resourceType) {
            case 'ecs':
                $metadata = [
                    'instance_type' => $rawData['InstanceType'] ?? '',
                    'cpu' => $rawData['CPU'] ?? 0,
                    'memory' => $rawData['Memory'] ?? 0,
                    'created_time' => $rawData['CreatedTime'] ?? '',
                    'public_ip' => $rawData['PublicIpAddresses'][0] ?? '',
                    'private_ip' => $rawData['PrivateIpAddresses'][0] ?? '',
                ];
                break;
            case 'clb':
                $metadata = [
                    'type' => $rawData['LoadBalancerType'] ?? '',
                    'vips' => $rawData['LoadBalancerVips'] ?? [],
                    'create_time' => $rawData['CreateTime'] ?? '',
                ];
                break;
            case 'cdb':
                $metadata = [
                    'engine_version' => $rawData['EngineVersion'] ?? '',
                    'instance_type' => $rawData['InstanceType'] ?? 0,
                    'memory' => $rawData['Memory'] ?? 0,
                    'volume' => $rawData['Volume'] ?? 0,
                    'vip' => $rawData['Vip'] ?? '',
                    'vport' => $rawData['Vport'] ?? 0,
                    'create_time' => $rawData['CreateTime'] ?? '',
                ];
                break;
            case 'redis':
                $metadata = [
                    'type' => $rawData['Type'] ?? 0,
                    'size' => $rawData['Size'] ?? 0,
                    'shard_num' => $rawData['RedisShardNum'] ?? 0,
                    'replicas_num' => $rawData['RedisReplicasNum'] ?? 0,
                    'port' => $rawData['Port'] ?? 0,
                    'wan_ip' => $rawData['WanIp'] ?? '',
                    'create_time' => $rawData['CreateTime'] ?? '',
                ];
                break;
            case 'domain':
                $metadata = [
                    'creation_date' => $rawData['CreationDate'] ?? '',
                    'expiration_date' => $rawData['ExpirationDate'] ?? '',
                    'is_premium' => $rawData['IsPremium'] ?? false,
                ];
                break;
        }

        return $metadata;
    }

    /**
     * 获取资源详情
     */
    public function getResourceDetail(string $resourceType, string $resourceId, ?string $region = null): ?array
    {
        try {
            $region = $region ?? $this->getConfig('region');
            
            // 根据资源类型调用相应的API获取详情
            switch ($resourceType) {
                case 'ecs':
                    // 调用腾讯云CVM API获取实例详情
                    $req = new \TencentCloud\Cvm\V20170312\Models\DescribeInstancesRequest();
                    $req->setInstanceIds([$resourceId]);
                    
                    $resp = $this->cvmClient->DescribeInstances($req);
                    $instances = $resp->getInstanceSet();
                    
                    if (!empty($instances)) {
                        $instance = $instances[0];
                        return [
                            'InstanceId' => $instance->getInstanceId(),
                            'InstanceName' => $instance->getInstanceName(),
                            'InstanceState' => $instance->getInstanceState(),
                            'InstanceType' => $instance->getInstanceType(),
                            'CPU' => $instance->getCPU(),
                            'Memory' => $instance->getMemory(),
                            'CreatedTime' => $instance->getCreatedTime(),
                            'PublicIpAddresses' => $instance->getPublicIpAddresses(),
                            'PrivateIpAddresses' => $instance->getPrivateIpAddresses(),
                            'ImageId' => $instance->getImageId(),
                            'SystemDisk' => $instance->getSystemDisk(),
                            'DataDisks' => $instance->getDataDisks(),
                        ];
                    }
                    return null;
                    
                default:
                    return null;
            }
        } catch (Exception $e) {
            $this->handleApiException($e, 'getResourceDetail', [
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'region' => $region
            ]);
            return null;
        }
    }

    /**
     * 根据资源类型获取资源
     */
    public function getResourcesByType(string $resourceType, array $config = []): array
    {
        try {
            $region = $config['region'] ?? $this->getConfig('region');
            
            \Log::info('TencentCloud: getResourcesByType调用', [
                'resource_type' => $resourceType,
                'region' => $region,
                'config' => $config
            ]);
            
            switch ($resourceType) {
                case 'ecs':
                case 'cvm':
                case 'tencent_cvm':
                    return $this->getEcsInstances($region);
                    
                case 'clb':
                case 'tencent_clb':
                    return $this->getClbInstances($region);
                    
                case 'cdb':
                case 'tencent_cdb':
                    return $this->getCdbInstances($region);
                    
                case 'redis':
                case 'tencent_redis':
                    return $this->getRedisInstances($region);
                    
                case 'domain':
                case 'tencent_domain':
                    return $this->getDomains($config);
                    
                default:
                    \Log::warning('TencentCloud: 不支持的资源类型', [
                        'resource_type' => $resourceType
                    ]);
                    return [];
            }
        } catch (Exception $e) {
            \Log::error('TencentCloud: getResourcesByType失败', [
                'resource_type' => $resourceType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->handleApiException($e, 'getResourcesByType', [
                'resource_type' => $resourceType,
                'config' => $config
            ]);
            return [];
        }
    }
}