<?php

namespace App\Services\CloudPlatform;

use App\Services\CloudPlatform\Contracts\CloudPlatformInterface;
use Exception;
use Illuminate\Support\Facades\Log;

abstract class AbstractCloudPlatform implements CloudPlatformInterface
{
    protected array $config = [];
    protected $client;

    /**
     * 初始化云平台客户端
     *
     * @param array $config 配置信息
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->config = $config;
        $this->initializeClient();
    }

    /**
     * 初始化客户端（由子类实现）
     *
     * @return void
     */
    abstract protected function initializeClient(): void;

    /**
     * 测试连接
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            // 尝试获取区域列表来测试连接
            $regions = $this->getRegions();
            return !empty($regions);
        } catch (Exception $e) {
            Log::error("Cloud platform connection test failed: " . $e->getMessage(), [
                'platform' => $this->getPlatformType(),
                'config' => array_merge($this->config, ['access_key_secret' => '***'])
            ]);
            return false;
        }
    }

    /**
     * 批量获取资源
     *
     * @param string $resourceType 资源类型
     * @param string $region 区域
     * @param array $resourceIds 资源ID列表
     * @return array
     */
    public function batchGetResources(string $resourceType, string $region, array $resourceIds): array
    {
        $resources = [];
        
        foreach ($resourceIds as $resourceId) {
            try {
                $resource = $this->getResourceDetail($resourceType, $resourceId, $region);
                if ($resource) {
                    $resources[] = $resource;
                }
            } catch (Exception $e) {
                Log::warning("Failed to get resource detail", [
                    'platform' => $this->getPlatformType(),
                    'resource_type' => $resourceType,
                    'resource_id' => $resourceId,
                    'region' => $region,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $resources;
    }

    /**
     * 格式化资源数据
     *
     * @param string $resourceType 资源类型
     * @param array $rawData 原始数据
     * @return array
     */
    public function formatResourceData(string $resourceType, array $rawData): array
    {
        $baseData = [
            'resource_id' => $this->extractResourceId($resourceType, $rawData),
            'name' => $this->extractResourceName($resourceType, $rawData),
            'status' => $this->extractResourceStatus($resourceType, $rawData),
            'region' => $this->extractResourceRegion($resourceType, $rawData),
            'raw_data' => $rawData,
            'metadata' => $this->extractResourceMetadata($resourceType, $rawData),
        ];

        return $baseData;
    }

    /**
     * 提取资源ID（由子类实现）
     *
     * @param string $resourceType
     * @param array $rawData
     * @return string
     */
    abstract protected function extractResourceId(string $resourceType, array $rawData): string;

    /**
     * 提取资源名称（由子类实现）
     *
     * @param string $resourceType
     * @param array $rawData
     * @return string
     */
    abstract protected function extractResourceName(string $resourceType, array $rawData): string;

    /**
     * 提取资源状态（由子类实现）
     *
     * @param string $resourceType
     * @param array $rawData
     * @return string
     */
    abstract protected function extractResourceStatus(string $resourceType, array $rawData): string;

    /**
     * 提取资源区域（由子类实现）
     *
     * @param string $resourceType
     * @param array $rawData
     * @return string
     */
    abstract protected function extractResourceRegion(string $resourceType, array $rawData): string;

    /**
     * 提取资源元数据（由子类实现）
     *
     * @param string $resourceType
     * @param array $rawData
     * @return array
     */
    abstract protected function extractResourceMetadata(string $resourceType, array $rawData): array;

    /**
     * 处理API异常
     *
     * @param Exception $e
     * @param string $operation
     * @param array $context
     * @return void
     * @throws Exception
     */
    protected function handleApiException(Exception $e, string $operation, array $context = []): void
    {
        Log::error("Cloud platform API error", [
            'platform' => $this->getPlatformType(),
            'operation' => $operation,
            'error' => $e->getMessage(),
            'context' => $context
        ]);

        throw $e;
    }

    /**
     * 记录API调用日志
     *
     * @param string $operation
     * @param array $params
     * @param mixed $result
     * @return void
     */
    protected function logApiCall(string $operation, array $params, $result): void
    {
        Log::info("Cloud platform API call", [
            'platform' => $this->getPlatformType(),
            'operation' => $operation,
            'params' => $params,
            'result_count' => is_array($result) ? count($result) : (is_object($result) ? 1 : 0)
        ]);
    }

    /**
     * 验证配置
     *
     * @param array $requiredKeys
     * @return void
     * @throws Exception
     */
    protected function validateConfig(array $requiredKeys): void
    {
        foreach ($requiredKeys as $key) {
            if (!isset($this->config[$key]) || empty($this->config[$key])) {
                throw new Exception("Missing required config key: {$key}");
            }
        }
    }

    /**
     * 获取配置值
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * 获取资源详细信息（由子类实现）
     *
     * @param string $resourceType
     * @param string $resourceId
     * @param string|null $region
     * @return array|null
     */
    abstract public function getResourceDetail(string $resourceType, string $resourceId, ?string $region = null): ?array;

    /**
     * 获取资源监控信息（由子类实现）
     *
     * @param string $resourceType
     * @param string $resourceId
     * @param string|null $region
     * @return array
     */
    abstract public function getResourceMonitoring(string $resourceType, string $resourceId, array $options = []): array;
}