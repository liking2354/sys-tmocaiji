<?php

namespace App\Services\CloudPlatform\Contracts;

interface CloudPlatformInterface
{
    /**
     * 初始化云平台客户端
     *
     * @param array $config 配置信息
     * @return void
     */
    public function initialize(array $config): void;

    /**
     * 测试连接
     *
     * @return bool
     */
    public function testConnection(): bool;

    /**
     * 获取支持的区域列表
     *
     * @return array
     */
    public function getRegions(): array;

    /**
     * 获取云主机列表
     *
     * @param string|null $region 区域
     * @return array
     */
    public function getEcsInstances(string $region = null): array;

    /**
     * 获取负载均衡列表
     *
     * @param string|null $region 区域
     * @return array
     */
    public function getClbInstances(string $region = null): array;

    /**
     * 获取MySQL数据库列表
     *
     * @param string|null $region 区域
     * @return array
     */
    public function getCdbInstances(string $region = null): array;

    /**
     * 获取Redis实例列表
     *
     * @param string|null $region 区域
     * @return array
     */
    public function getRedisInstances(string $region = null): array;

    /**
     * 获取域名列表
     *
     * @return array
     */
    public function getDomains(): array;

    /**
     * 获取资源详情
     *
     * @param string $resourceType 资源类型
     * @param string $resourceId 资源ID
     * @param string|null $region 区域
     * @return array|null
     */
    public function getResourceDetail(string $resourceType, string $resourceId, ?string $region = null): ?array;

    /**
     * 获取资源监控信息
     *
     * @param string $resourceType 资源类型
     * @param string $resourceId 资源ID
     * @param array $options 选项
     * @return array
     */
    public function getResourceMonitoring(string $resourceType, string $resourceId, array $options = []): array;



    /**
     * 获取平台类型
     *
     * @return string
     */
    public function getPlatformType(): string;

    /**
     * 获取平台名称
     *
     * @return string
     */
    public function getPlatformName(): string;

    /**
     * 格式化资源数据
     *
     * @param string $resourceType 资源类型
     * @param array $rawData 原始数据
     * @return array
     */
    public function formatResourceData(string $resourceType, array $rawData): array;
}