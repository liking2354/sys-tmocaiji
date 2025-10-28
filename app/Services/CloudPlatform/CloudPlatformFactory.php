<?php

namespace App\Services\CloudPlatform;

use App\Services\CloudPlatform\Contracts\CloudPlatformInterface;
use App\Services\CloudPlatform\Platforms\HuaweiCloudPlatform;
use App\Services\CloudPlatform\Platforms\AlibabaCloudPlatform;
use App\Services\CloudPlatform\Platforms\TencentCloudPlatform;
use Exception;

class CloudPlatformFactory
{
    /**
     * 支持的云平台映射
     */
    private static array $platformMap = [
        'huawei' => HuaweiCloudPlatform::class,
        'alibaba' => AlibabaCloudPlatform::class,
        'tencent' => TencentCloudPlatform::class,
    ];

    /**
     * 创建云平台实例
     *
     * @param string|\App\Models\CloudPlatform $platformOrType 平台类型或CloudPlatform模型
     * @param array $config 配置信息
     * @return CloudPlatformInterface
     * @throws Exception
     */
    public static function create($platformOrType, array $config = []): CloudPlatformInterface
    {
        // 支持传入 CloudPlatform 模型或平台类型字符串
        if ($platformOrType instanceof \App\Models\CloudPlatform) {
            $platformType = strtolower(trim((string)$platformOrType->platform_type));
            $config = [
                'access_key_id' => $platformOrType->access_key_id,
                'access_key_secret' => $platformOrType->access_key_secret,
                'region' => $platformOrType->region,
            ];
        } else {
            $platformType = strtolower(trim((string)$platformOrType));
        }

        if ($platformType === '') {
            throw new Exception("Unsupported cloud platform: empty platform_type");
        }

        if (!isset(self::$platformMap[$platformType])) {
            throw new Exception("Unsupported cloud platform: {$platformType}");
        }

        $platformClass = self::$platformMap[$platformType];
        
        if (!class_exists($platformClass)) {
            throw new Exception("Cloud platform class not found: {$platformClass}");
        }

        $platform = new $platformClass();
        $platform->initialize($config);

        return $platform;
    }

    /**
     * 从CloudPlatform模型创建云平台实例
     *
     * @param \App\Models\CloudPlatform $cloudPlatform
     * @return CloudPlatformInterface
     * @throws Exception
     */
    public static function createFromPlatform(\App\Models\CloudPlatform $cloudPlatform): CloudPlatformInterface
    {
        $platformType = strtolower(trim((string)$cloudPlatform->platform_type));

        if ($platformType === '') {
            throw new Exception("Unsupported cloud platform: empty platform_type");
        }

        if (!isset(self::$platformMap[$platformType])) {
            throw new Exception("Unsupported cloud platform: {$platformType}");
        }

        $platformClass = self::$platformMap[$platformType];
        
        if (!class_exists($platformClass)) {
            throw new Exception("Cloud platform class not found: {$platformClass}");
        }

        $platform = new $platformClass();
        
        // 直接传递CloudPlatform模型，让平台类自己处理配置
        $platform->initializeFromModel($cloudPlatform);

        return $platform;
    }

    /**
     * 获取支持的平台类型列表
     *
     * @return array
     */
    public static function getSupportedPlatforms(): array
    {
        return array_keys(self::$platformMap);
    }

    /**
     * 检查平台类型是否支持
     *
     * @param string $platformType
     * @return bool
     */
    public static function isSupported(string $platformType): bool
    {
        return isset(self::$platformMap[$platformType]);
    }

    /**
     * 获取平台类型的中文名称
     *
     * @param string $platformType
     * @return string
     */
    public static function getPlatformName(string $platformType): string
    {
        $names = [
            'huawei' => '华为云',
            'alibaba' => '阿里云',
            'tencent' => '腾讯云',
        ];

        return $names[$platformType] ?? $platformType;
    }

    /**
     * 获取所有平台的名称映射
     *
     * @return array
     */
    public static function getAllPlatformNames(): array
    {
        $names = [];
        foreach (self::$platformMap as $type => $class) {
            $names[$type] = self::getPlatformName($type);
        }
        return $names;
    }

    /**
     * 注册新的云平台
     *
     * @param string $platformType
     * @param string $platformClass
     * @return void
     * @throws Exception
     */
    public static function register(string $platformType, string $platformClass): void
    {
        if (!class_exists($platformClass)) {
            throw new Exception("Platform class not found: {$platformClass}");
        }

        if (!is_subclass_of($platformClass, CloudPlatformInterface::class)) {
            throw new Exception("Platform class must implement CloudPlatformInterface");
        }

        self::$platformMap[$platformType] = $platformClass;
    }
}