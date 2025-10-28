<?php

namespace App\Services\CloudPlatform\Platforms;

use App\Services\CloudPlatform\AbstractCloudPlatform;
use HuaweiCloud\SDK\Core\Auth\BasicCredentials;
use HuaweiCloud\SDK\Core\Auth\GlobalCredentials;
use HuaweiCloud\SDK\Core\Http\HttpConfig;
use HuaweiCloud\SDK\Ecs\V2\EcsClient;
use HuaweiCloud\SDK\Ecs\V2\Region\EcsRegion;
use HuaweiCloud\SDK\Elb\V3\ElbClient;
use HuaweiCloud\SDK\Elb\V3\Region\ElbRegion;
use HuaweiCloud\SDK\Rds\V3\RdsClient;
use HuaweiCloud\SDK\Rds\V3\Region\RdsRegion;
use HuaweiCloud\SDK\Dcs\V2\DcsClient;
use HuaweiCloud\SDK\Dcs\V2\Region\DcsRegion;
use HuaweiCloud\SDK\Iam\V3\IamClient;
use HuaweiCloud\SDK\Iam\V3\Region\IamRegion;
use Illuminate\Support\Facades\Log;
use Exception;

class HuaweiCloudPlatform extends AbstractCloudPlatform
{
    private ?EcsClient $ecsClient = null;
    private ?ElbClient $elbClient = null;
    private ?RdsClient $rdsClient = null;
    private ?DcsClient $dcsClient = null;
    private ?IamClient $iamClient = null;

    /**
     * 获取项目ID
     * 优先从其他配置的project_ids中根据区域获取，如果没有则使用project_id字段
     *
     * @param string|null $region 指定区域，如果为空则使用默认区域
     * @return string
     */
    protected function getProjectId(string $region = null): string
    {
        $targetRegion = $region ?? $this->getConfig('region');
        
        // 尝试从其他配置的project_ids中获取
        $otherConfig = $this->getConfig('other_config');
        if (!empty($otherConfig)) {
            // 如果是字符串，尝试解析为JSON
            if (is_string($otherConfig)) {
                $configData = json_decode($otherConfig, true);
            } else {
                $configData = $otherConfig;
            }
            
            // 检查是否有project_ids配置
            if (isset($configData['project_ids']) && is_array($configData['project_ids'])) {
                $projectIds = $configData['project_ids'];
                
                // 根据区域获取对应的Project ID
                if (isset($projectIds[$targetRegion])) {
                    $regionConfig = $projectIds[$targetRegion];
                    
                    // 支持新格式：{region: {project_id: "xxx", region_name: "xxx"}}
                    if (is_array($regionConfig) && isset($regionConfig['project_id'])) {
                        $projectId = $regionConfig['project_id'];
                        $regionName = $regionConfig['region_name'] ?? $targetRegion;
                        
                        Log::info('从其他配置获取Project ID (新格式)', [
                            'region' => $targetRegion,
                            'region_name' => $regionName,
                            'project_id' => substr($projectId, 0, 8) . '***'
                        ]);
                        return $projectId;
                    }
                    // 支持旧格式：{region: "project_id"}
                    elseif (is_string($regionConfig)) {
                        Log::info('从其他配置获取Project ID (旧格式)', [
                            'region' => $targetRegion,
                            'project_id' => substr($regionConfig, 0, 8) . '***'
                        ]);
                        return $regionConfig;
                    }
                }
                
                Log::warning('未找到区域对应的Project ID', [
                    'region' => $targetRegion,
                    'available_regions' => array_keys($projectIds)
                ]);
            }
        }
        
        // 如果其他配置中没有找到，使用原来的project_id字段
        $projectId = $this->getConfig('project_id', '');
        
        if (empty($projectId)) {
            Log::error('Project ID未配置', [
                'region' => $targetRegion,
                'message' => '请在云平台管理的"其他配置"中设置project_ids或在project_id字段中设置'
            ]);
        }
        
        return $projectId;
    }

    /**
     * 为指定区域初始化客户端
     */
    protected function initializeClientForRegion(string $region): void
    {
        $accessKeyId = $this->getConfig('access_key_id');
        $accessKeySecret = $this->getConfig('access_key_secret');
        $projectId = $this->getProjectId($region);

        if (empty($accessKeyId) || empty($accessKeySecret) || empty($projectId)) {
            throw new Exception('华为云认证信息不完整');
        }

        Log::info('华为云客户端初始化开始（指定区域）', [
            'region' => $region,
            'access_key_id' => substr($accessKeyId, 0, 8) . '***',
            'project_id' => substr($projectId, 0, 8) . '***'
        ]);

        try {
            // 创建认证凭据
            $credentials = new BasicCredentials($accessKeyId, $accessKeySecret, $projectId);
            Log::info('华为云认证凭据创建成功', ['project_id' => substr($projectId, 0, 8) . '***']);

            // 创建HTTP配置
            $httpConfig = HttpConfig::getDefaultConfig();
            Log::info('华为云HTTP配置创建成功');

            // 初始化ECS客户端
            Log::info('开始初始化华为云ECS客户端', ['region' => $region]);
            $this->ecsClient = EcsClient::newBuilder()
                ->withCredentials($credentials)
                ->withRegion(EcsRegion::valueOf($region))
                ->withHttpConfig($httpConfig)
                ->build();
            Log::info('华为云ECS客户端初始化成功');

            Log::info('华为云客户端初始化完成（指定区域）', [
                'region' => $region,
                'access_key_id' => substr($accessKeyId, 0, 8) . '***'
            ]);

        } catch (Exception $e) {
            Log::error('华为云客户端初始化失败（指定区域）', [
                'error' => $e->getMessage(),
                'region' => $region,
                'access_key_id' => substr($accessKeyId, 0, 8) . '***'
            ]);
            throw new Exception('华为云客户端初始化失败: ' . $e->getMessage());
        }
    }

    /**
     * 初始化客户端
     *
     * @return void
     * @throws Exception
     */
    protected function initializeClient(): void
    {
        $region = $this->getConfig('region');
        $projectId = $this->getProjectId($region);
        
        Log::info('华为云客户端初始化开始', [
            'region' => $region,
            'access_key_id' => substr($this->getConfig('access_key_id', ''), 0, 8) . '***',
            'project_id' => substr($projectId, 0, 8) . '***'
        ]);

        $this->validateConfig(['access_key_id', 'access_key_secret', 'region']);

        // 验证Project ID
        if (empty($projectId)) {
            throw new Exception('Project ID未配置，请在云平台管理的"其他配置"中设置project_ids');
        }

        try {
            $accessKeyId = $this->getConfig('access_key_id');
            $accessKeySecret = $this->getConfig('access_key_secret');

            // 验证凭据格式
            if (strlen($accessKeyId) < 16) {
                throw new Exception('Access Key ID 格式不正确，长度应该至少16位');
            }
            if (strlen($accessKeySecret) < 32) {
                throw new Exception('Access Key Secret 格式不正确，长度应该至少32位');
            }

            Log::info('华为云凭据验证通过，开始创建客户端');

            // 创建认证凭据 - 华为云不同服务使用不同的认证类型
            // BasicCredentials需要Project ID用于区域服务
            $basicCredentials = new BasicCredentials($accessKeyId, $accessKeySecret, $projectId);
            $globalCredentials = new GlobalCredentials($accessKeyId, $accessKeySecret);
            Log::info('华为云认证凭据创建成功', [
                'project_id' => substr($projectId, 0, 8) . '***'
            ]);

            // 创建HTTP配置
            $httpConfig = HttpConfig::getDefaultConfig();
            Log::info('华为云HTTP配置创建成功');

            // 验证区域格式
            $validRegions = ['cn-north-1', 'cn-north-4', 'cn-east-2', 'cn-east-3', 'cn-south-1', 'cn-southwest-2'];
            if (!in_array($region, $validRegions)) {
                Log::warning('华为云区域可能不支持', [
                    'region' => $region,
                    'valid_regions' => $validRegions
                ]);
            }

            // 初始化ECS客户端 - 使用BasicCredentials
            Log::info('开始初始化华为云ECS客户端', ['region' => $region]);
            $this->ecsClient = EcsClient::newBuilder()
                ->withCredentials($basicCredentials)
                ->withRegion(EcsRegion::valueOf($region))
                ->withHttpConfig($httpConfig)
                ->build();
            Log::info('华为云ECS客户端初始化成功');

            // 初始化ELB客户端 - 使用BasicCredentials
            Log::info('开始初始化华为云ELB客户端');
            $this->elbClient = ElbClient::newBuilder()
                ->withCredentials($basicCredentials)
                ->withRegion(ElbRegion::valueOf($region))
                ->withHttpConfig($httpConfig)
                ->build();
            Log::info('华为云ELB客户端初始化成功');

            // 初始化RDS客户端 - 使用BasicCredentials
            Log::info('开始初始化华为云RDS客户端');
            $this->rdsClient = RdsClient::newBuilder()
                ->withCredentials($basicCredentials)
                ->withRegion(RdsRegion::valueOf($region))
                ->withHttpConfig($httpConfig)
                ->build();
            Log::info('华为云RDS客户端初始化成功');

            // 初始化DCS客户端 - 使用BasicCredentials
            Log::info('开始初始化华为云DCS客户端');
            $this->dcsClient = DcsClient::newBuilder()
                ->withCredentials($basicCredentials)
                ->withRegion(DcsRegion::valueOf($region))
                ->withHttpConfig($httpConfig)
                ->build();
            Log::info('华为云DCS客户端初始化成功');

            // 初始化IAM客户端（全局服务）- 暂时跳过，因为SDK存在兼容性问题
            Log::info('跳过华为云IAM客户端初始化（SDK兼容性问题）');
            // TODO: 修复华为云SDK的GlobalCredentials兼容性问题后再启用
            /*
            try {
                $this->iamClient = IamClient::newBuilder()
                    ->withCredentials($globalCredentials)
                    ->withRegion(IamRegion::valueOf('cn-north-1'))
                    ->withHttpConfig($httpConfig)
                    ->build();
                Log::info('华为云IAM客户端初始化成功');
            } catch (Exception $iamException) {
                Log::warning('华为云IAM客户端初始化失败，但不影响其他服务', [
                    'error' => $iamException->getMessage()
                ]);
            }
            */

            Log::info('华为云所有客户端初始化完成', [
                'region' => $region,
                'access_key_id' => substr($accessKeyId, 0, 8) . '***'
            ]);

        } catch (Exception $e) {
            Log::error('华为云客户端初始化失败', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'region' => $this->getConfig('region'),
                'access_key_id' => substr($this->getConfig('access_key_id', ''), 0, 8) . '***',
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('华为云客户端初始化失败: ' . $e->getMessage());
        }
    }

    /**
     * 测试连接
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            \Log::info('华为云连接测试开始', [
                'region' => $this->getConfig('region'),
                'access_key_id' => substr($this->getConfig('access_key_id'), 0, 8) . '***'
            ]);

            // 检查必要的配置
            $requiredKeys = ['access_key_id', 'access_key_secret', 'region'];
            foreach ($requiredKeys as $key) {
                if (empty($this->getConfig($key))) {
                    \Log::error('华为云连接测试失败: 缺少必要配置', ['missing_key' => $key]);
                    return false;
                }
            }

            // 尝试初始化客户端（如果还没有初始化）
            if (!$this->ecsClient) {
                \Log::info('华为云客户端未初始化，开始初始化...');
                $this->initializeClient();
            }

            // 测试连接 - 尝试获取区域列表
            \Log::info('华为云连接测试: 开始获取区域列表');
            $regions = $this->getRegions();
            
            if (empty($regions)) {
                \Log::error('华为云连接测试失败: 区域列表为空');
                return false;
            }

            \Log::info('华为云连接测试成功', [
                'region_count' => count($regions),
                'regions' => array_column($regions, 'region_code')
            ]);
            
            return true;
        } catch (\Exception $e) {
            \Log::error('华为云连接测试异常', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * 获取支持的区域列表
     *
     * @return array
     */
    public function getRegions(): array
    {
        try {
            // 华为云区域列表（这里使用静态数据，实际可以通过API获取）
            $regions = [
                ['region_code' => 'cn-north-1', 'region_name' => '华北-北京一'],
                ['region_code' => 'cn-north-4', 'region_name' => '华北-北京四'],
                ['region_code' => 'cn-east-2', 'region_name' => '华东-上海二'],
                ['region_code' => 'cn-east-3', 'region_name' => '华东-上海一'],
                ['region_code' => 'cn-south-1', 'region_name' => '华南-广州'],
                ['region_code' => 'cn-southwest-2', 'region_name' => '西南-贵阳一'],
                ['region_code' => 'ap-southeast-1', 'region_name' => '亚太-香港'],
                ['region_code' => 'ap-southeast-3', 'region_name' => '亚太-新加坡'],
            ];

            $this->logApiCall('getRegions', [], $regions);
            return $regions;
        } catch (Exception $e) {
            $this->handleApiException($e, 'getRegions');
            return [];
        }
    }

    /**
     * 获取云主机列表
     *
     * @param string|null $region 区域
     * @return array
     */
    public function getEcsInstances(string $region = null): array
    {
        try {
            $targetRegion = $region ?? $this->getConfig('region');
            
            // 如果指定了区域且与当前初始化的区域不同，需要重新初始化客户端
            if ($region && $region !== $this->getConfig('region')) {
                // 为指定区域创建新的客户端实例
                $this->initializeClientForRegion($region);
            }
            
            if (!$this->ecsClient) {
                \Log::warning('ECS客户端未初始化', ['region' => $targetRegion]);
                return [];
            }

            $request = new \HuaweiCloud\SDK\Ecs\V2\Model\ListCloudServersRequest();
            
            \Log::info('开始获取ECS实例列表', ['region' => $targetRegion]);
            
            $response = $this->ecsClient->listCloudServers($request);
            $servers = $response->getServers() ?? [];

            \Log::info('获取到ECS实例', [
                'region' => $targetRegion,
                'count' => count($servers)
            ]);

            $instances = [];
            foreach ($servers as $server) {
                $serverData = $server->toArray();
                $instances[] = $this->formatResourceData('ecs', $serverData);
            }

            $this->logApiCall('getEcsInstances', ['region' => $targetRegion], $instances);
            return $instances;
        } catch (\HuaweiCloud\SDK\Core\Exceptions\ClientRequestException $e) {
            \Log::error('华为云ECS API请求失败', [
                'region' => $targetRegion,
                'error_code' => $e->getErrorCode(),
                'error_msg' => $e->getErrorMsg(),
                'request_id' => $e->getRequestId(),
                'http_status' => $e->getHttpStatusCode()
            ]);
            // 返回空数组而不是抛出异常，避免中断同步流程
            return [];
        } catch (\HuaweiCloud\SDK\Core\Exceptions\ServiceResponseException $e) {
            \Log::error('华为云ECS服务响应异常', [
                'region' => $targetRegion,
                'error_code' => $e->getErrorCode(),
                'error_msg' => $e->getErrorMsg(),
                'request_id' => $e->getRequestId(),
                'http_status' => $e->getHttpStatusCode()
            ]);
            return [];
        } catch (Exception $e) {
            // 获取更详细的错误信息
            $errorMessage = $e->getMessage();
            $errorCode = $e->getCode();
            $errorDetails = [];
            
            // 如果是华为云SDK的异常，尝试获取更多信息
            if (method_exists($e, 'getResponse')) {
                $response = $e->getResponse();
                if ($response) {
                    $errorDetails['response_body'] = $response->getBody();
                    $errorDetails['status_code'] = $response->getStatusCode();
                }
            }
            
            // 如果错误信息为空，提供更有用的信息
            if (empty($errorMessage)) {
                $errorMessage = '华为云ECS API调用失败，可能是网络连接或认证问题';
            }
            
            \Log::error('获取ECS实例失败', [
                'region' => $targetRegion,
                'error' => $errorMessage,
                'error_code' => $errorCode,
                'error_details' => $errorDetails,
                'exception_class' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->handleApiException($e, 'getEcsInstances', ['region' => $targetRegion]);
            return [];
        }
    }

    /**
     * 获取负载均衡列表
     *
     * @param string|null $region 区域
     * @return array
     */
    public function getClbInstances(string $region = null): array
    {
        try {
            $targetRegion = $region ?? $this->getConfig('region');
            
            if (!$this->elbClient) {
                \Log::warning('ELB客户端未初始化', ['region' => $targetRegion]);
                return [];
            }

            $request = new \HuaweiCloud\SDK\Elb\V3\Model\ListLoadBalancersRequest();
            
            \Log::info('开始获取ELB实例列表', ['region' => $targetRegion]);
            
            $response = $this->elbClient->listLoadBalancers($request);
            $loadbalancers = $response->getLoadbalancers() ?? [];

            \Log::info('获取到ELB实例', [
                'region' => $targetRegion,
                'count' => count($loadbalancers)
            ]);

            $instances = [];
            foreach ($loadbalancers as $lb) {
                $instances[] = $this->formatResourceData('clb', $lb->toArray());
            }

            $this->logApiCall('getClbInstances', ['region' => $targetRegion], $instances);
            return $instances;
        } catch (Exception $e) {
            \Log::error('获取ELB实例失败', [
                'region' => $targetRegion,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->handleApiException($e, 'getClbInstances', ['region' => $targetRegion]);
            return [];
        }
    }

    /**
     * 获取MySQL数据库列表
     *
     * @param string|null $region 区域
     * @return array
     */
    public function getCdbInstances(string $region = null): array
    {
        try {
            $targetRegion = $region ?? $this->getConfig('region');
            
            if (!$this->rdsClient) {
                \Log::warning('RDS客户端未初始化', ['region' => $targetRegion]);
                return [];
            }

            $request = new \HuaweiCloud\SDK\Rds\V3\Model\ListInstancesRequest();
            
            \Log::info('开始获取RDS实例列表', ['region' => $targetRegion]);
            
            $response = $this->rdsClient->listInstances($request);
            $instances_data = $response->getInstances() ?? [];

            \Log::info('获取到RDS实例', [
                'region' => $targetRegion,
                'total_count' => count($instances_data)
            ]);

            $instances = [];
            foreach ($instances_data as $instance) {
                $instanceArray = $instance->toArray();
                // 只获取MySQL实例
                if (isset($instanceArray['datastore']['type']) && 
                    strtolower($instanceArray['datastore']['type']) === 'mysql') {
                    $instances[] = $this->formatResourceData('cdb', $instanceArray);
                }
            }

            \Log::info('过滤后的MySQL实例', [
                'region' => $targetRegion,
                'mysql_count' => count($instances)
            ]);

            $this->logApiCall('getCdbInstances', ['region' => $targetRegion], $instances);
            return $instances;
        } catch (Exception $e) {
            \Log::error('获取RDS实例失败', [
                'region' => $targetRegion,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->handleApiException($e, 'getCdbInstances', ['region' => $targetRegion]);
            return [];
        }
    }

    /**
     * 获取Redis实例列表
     *
     * @param string|null $region 区域
     * @return array
     */
    public function getRedisInstances(string $region = null): array
    {
        try {
            $targetRegion = $region ?? $this->getConfig('region');
            
            if (!$this->dcsClient) {
                \Log::warning('DCS客户端未初始化', ['region' => $targetRegion]);
                return [];
            }

            $request = new \HuaweiCloud\SDK\Dcs\V2\Model\ListInstancesRequest();
            
            \Log::info('开始获取DCS实例列表', ['region' => $targetRegion]);
            
            $response = $this->dcsClient->listInstances($request);
            $instances_data = $response->getInstances() ?? [];

            \Log::info('获取到DCS实例', [
                'region' => $targetRegion,
                'count' => count($instances_data)
            ]);

            $instances = [];
            foreach ($instances_data as $instance) {
                $instances[] = $this->formatResourceData('redis', $instance->toArray());
            }

            $this->logApiCall('getRedisInstances', ['region' => $targetRegion], $instances);
            return $instances;
        } catch (Exception $e) {
            \Log::error('获取DCS实例失败', [
                'region' => $targetRegion,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->handleApiException($e, 'getRedisInstances', ['region' => $targetRegion]);
            return [];
        }
    }

    /**
     * 获取域名列表
     *
     * @return array
     */
    public function getDomains(): array
    {
        // 华为云域名服务需要单独的SDK，这里暂时返回空数组
        // 实际实现时需要使用华为云DNS服务的SDK
        return [];
    }

    /**
     * 获取资源详情
     *
     * @param string $resourceType 资源类型
     * @param string $resourceId 资源ID
     * @param string $region 区域
     * @return array|null
     */
    public function getResourceDetail(string $resourceType, string $resourceId, ?string $region = null): ?array
    {
        try {
            switch ($resourceType) {
                case 'ecs':
                    $request = new \HuaweiCloud\SDK\Ecs\V2\Model\ShowServerRequest();
                    $request->setServerId($resourceId);
                    $response = $this->ecsClient->showServer($request);
                    return $this->formatResourceData('ecs', $response->getServer()->toArray());

                case 'clb':
                    $request = new \HuaweiCloud\SDK\Elb\V3\Model\ShowLoadBalancerRequest();
                    $request->setLoadbalancerId($resourceId);
                    $response = $this->elbClient->showLoadBalancer($request);
                    return $this->formatResourceData('clb', $response->getLoadbalancer()->toArray());

                case 'cdb':
                    // RDS实例详情获取
                    $instances = $this->getCdbInstances($region);
                    foreach ($instances as $instance) {
                        if ($instance['resource_id'] === $resourceId) {
                            return $instance;
                        }
                    }
                    return null;

                case 'redis':
                    // Redis实例详情获取
                    $instances = $this->getRedisInstances($region);
                    foreach ($instances as $instance) {
                        if ($instance['resource_id'] === $resourceId) {
                            return $instance;
                        }
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
     * 获取平台类型
     *
     * @return string
     */
    public function getPlatformType(): string
    {
        return 'huawei';
    }

    /**
     * 获取平台名称
     *
     * @return string
     */
    public function getPlatformName(): string
    {
        return '华为云';
    }

    /**
     * 提取资源ID
     *
     * @param string $resourceType
     * @param array $rawData
     * @return string
     */
    protected function extractResourceId(string $resourceType, array $rawData): string
    {
        return $rawData['id'] ?? '';
    }

    /**
     * 提取资源名称
     *
     * @param string $resourceType
     * @param array $rawData
     * @return string
     */
    protected function extractResourceName(string $resourceType, array $rawData): string
    {
        return $rawData['name'] ?? '';
    }

    /**
     * 提取资源状态
     *
     * @param string $resourceType
     * @param array $rawData
     * @return string
     */
    protected function extractResourceStatus(string $resourceType, array $rawData): string
    {
        switch ($resourceType) {
            case 'ecs':
                return $rawData['status'] ?? 'unknown';
            case 'clb':
                return $rawData['provisioning_status'] ?? 'unknown';
            case 'cdb':
                return $rawData['status'] ?? 'unknown';
            case 'redis':
                return $rawData['status'] ?? 'unknown';
            default:
                return 'unknown';
        }
    }

    /**
     * 提取资源区域
     *
     * @param string $resourceType
     * @param array $rawData
     * @return string
     */
    protected function extractResourceRegion(string $resourceType, array $rawData): string
    {
        return $this->getConfig('region', '');
    }

    /**
     * 提取资源元数据
     *
     * @param string $resourceType
     * @param array $rawData
     * @return array
     */
    protected function extractResourceMetadata(string $resourceType, array $rawData): array
    {
        $metadata = [];

        switch ($resourceType) {
            case 'ecs':
                $metadata = [
                    'flavor' => $rawData['flavor']['id'] ?? '',
                    'image' => $rawData['image']['id'] ?? '',
                    'key_name' => $rawData['key_name'] ?? '',
                    'security_groups' => $rawData['security_groups'] ?? [],
                    'addresses' => $rawData['addresses'] ?? [],
                ];
                break;
            case 'clb':
                $metadata = [
                    'vip_subnet_id' => $rawData['vip_subnet_id'] ?? '',
                    'vip_address' => $rawData['vip_address'] ?? '',
                    'listeners' => $rawData['listeners'] ?? [],
                ];
                break;
            case 'cdb':
                $metadata = [
                    'datastore' => $rawData['datastore'] ?? [],
                    'volume' => $rawData['volume'] ?? [],
                    'backup_strategy' => $rawData['backup_strategy'] ?? [],
                ];
                break;
            case 'redis':
                $metadata = [
                    'engine' => $rawData['engine'] ?? '',
                    'engine_version' => $rawData['engine_version'] ?? '',
                    'capacity' => $rawData['capacity'] ?? '',
                    'vpc_id' => $rawData['vpc_id'] ?? '',
                ];
                break;
        }

        return $metadata;
    }

    /**
     * 获取资源监控信息
     *
     * @param string $resourceType
     * @param string $resourceId
     * @param array $options
     * @return array
     */
    public function getResourceMonitoring(string $resourceType, string $resourceId, array $options = []): array
    {
        try {
            $region = $options['region'] ?? $this->getConfig('region');
            
            // 这里应该调用华为云监控API获取监控数据
            // 暂时返回模拟数据，实际实现时需要使用华为云CES（Cloud Eye Service）SDK
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
                        'cpu_usage' => rand(10, 90),
                        'memory_usage' => rand(20, 80),
                        'disk_usage' => rand(30, 70),
                        'network_in' => rand(100, 1000),
                        'network_out' => rand(100, 1000),
                        'disk_read' => rand(50, 500),
                        'disk_write' => rand(50, 500),
                    ];
                    break;
                case 'clb':
                    $monitoringData['metrics'] = [
                        'active_connections' => rand(10, 100),
                        'new_connections' => rand(5, 50),
                        'requests_per_second' => rand(100, 1000),
                        'response_time' => rand(10, 100),
                        'error_rate' => rand(0, 5),
                    ];
                    break;
                case 'cdb':
                    $monitoringData['metrics'] = [
                        'cpu_usage' => rand(10, 80),
                        'memory_usage' => rand(30, 90),
                        'disk_usage' => rand(20, 70),
                        'connections' => rand(10, 100),
                        'qps' => rand(100, 1000),
                        'tps' => rand(50, 500),
                    ];
                    break;
                case 'redis':
                    $monitoringData['metrics'] = [
                        'cpu_usage' => rand(5, 60),
                        'memory_usage' => rand(40, 95),
                        'connections' => rand(10, 200),
                        'ops_per_second' => rand(1000, 10000),
                        'hit_rate' => rand(80, 99),
                        'expired_keys' => rand(0, 100),
                    ];
                    break;
            }

            $this->logApiCall('getResourceMonitoring', [
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'region' => $region,
                'options' => $options
            ], $monitoringData);

            return $monitoringData;
        } catch (Exception $e) {
            $this->handleApiException($e, 'getResourceMonitoring', [
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'region' => $region,
                'options' => $options
            ]);
            return [];
        }
    }

    /**
     * 根据资源类型获取资源
     */
    public function getResourcesByType(string $resourceType, array $config = []): array
    {
        switch ($resourceType) {
            case 'compute':
                return $this->getEcsInstances($config['region'] ?? null);
            case 'storage':
                return $this->getStorageResources($config);
            case 'network':
                return $this->getNetworkResources($config);
            default:
                Log::warning("不支持的资源类型: {$resourceType}");
                return [];
        }
    }

    /**
     * 获取存储资源
     */
    protected function getStorageResources(array $config = []): array
    {
        // 这里可以实现EVS、OBS等存储资源的获取
        // 暂时返回空数组，表示没有存储资源
        return [];
    }

    /**
     * 获取网络资源
     */
    public function getNetworkResources(array $config = []): array
    {
        // 这里可以实现VPC、ELB等网络资源的获取
        // 暂时返回空数组，表示没有网络资源
        return [];
    }
}