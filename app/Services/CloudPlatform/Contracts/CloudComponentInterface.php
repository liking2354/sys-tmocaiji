<?php

namespace App\Services\CloudPlatform\Contracts;

interface CloudComponentInterface
{
    /**
     * 获取组件类型
     *
     * @return string
     */
    public function getComponentType(): string;

    /**
     * 获取组件名称
     *
     * @return string
     */
    public function getComponentName(): string;

    /**
     * 同步资源数据
     *
     * @param int $platformId 平台ID
     * @param string|null $region 区域
     * @return array 同步结果
     */
    public function syncResources(int $platformId, ?string $region = null): array;

    /**
     * 获取资源详情
     *
     * @param string $resourceId 资源ID
     * @param string|null $region 区域
     * @return array|null
     */
    public function getResourceDetail(string $resourceId, ?string $region = null): ?array;

    /**
     * 获取资源监控信息
     *
     * @param string $resourceId 资源ID
     * @param array $options 选项
     * @return array
     */
    public function getResourceMonitoring(string $resourceId, array $options = []): array;

    /**
     * 检查组件是否可用
     *
     * @return bool
     */
    public function isAvailable(): bool;
}