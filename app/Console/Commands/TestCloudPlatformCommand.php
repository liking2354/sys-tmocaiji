<?php

namespace App\Console\Commands;

use App\Models\CloudPlatform;
use App\Services\CloudResourceService;
use App\Services\CloudPlatform\CloudPlatformFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class TestCloudPlatformCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cloud:test {platform_type} {--access_key_id=} {--access_key_secret=} {--region=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '测试云平台连接和资源获取功能';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $platformType = $this->argument('platform_type');
        $accessKeyId = $this->option('access_key_id');
        $accessKeySecret = $this->option('access_key_secret');
        $region = $this->option('region');

        // 验证参数
        if (!in_array($platformType, ['huawei', 'alibaba', 'tencent'])) {
            $this->error('不支持的云平台类型。支持的类型：huawei, alibaba, tencent');
            return 1;
        }

        // 如果没有提供认证信息，使用测试配置
        if (!$accessKeyId || !$accessKeySecret || !$region) {
            $this->info('使用测试配置进行连接测试...');
            $config = $this->getTestConfig($platformType);
        } else {
            $config = [
                'access_key_id' => $accessKeyId,
                'access_key_secret' => $accessKeySecret,
                'region' => $region,
            ];
        }

        $this->info("开始测试 {$platformType} 云平台...");

        try {
            // 创建云平台实例
            $cloudPlatform = CloudPlatformFactory::create($platformType, $config);
            
            $this->info("✓ 云平台实例创建成功");

            // 测试连接
            $this->info("正在测试连接...");
            $connected = $cloudPlatform->testConnection();
            
            if ($connected) {
                $this->info("✓ 连接测试成功");
            } else {
                $this->warn("⚠ 连接测试失败（可能是因为使用了测试配置）");
            }

            // 获取区域列表
            $this->info("正在获取区域列表...");
            $regions = $cloudPlatform->getRegions();
            $this->info("✓ 获取到 " . count($regions) . " 个区域");
            
            $this->table(['区域代码', '区域名称'], array_map(function($region) {
                return [$region['region_code'], $region['region_name']];
            }, array_slice($regions, 0, 5))); // 只显示前5个

            // 测试获取资源（使用模拟数据）
            $testRegion = $regions[0]['region_code'] ?? $config['region'];
            
            $this->info("正在获取 ECS 实例列表（区域：{$testRegion}）...");
            $ecsInstances = $cloudPlatform->getEcsInstances($testRegion);
            $this->info("✓ 获取到 " . count($ecsInstances) . " 个 ECS 实例");

            $this->info("正在获取负载均衡列表...");
            $loadBalancers = $cloudPlatform->getLoadBalancers($testRegion);
            $this->info("✓ 获取到 " . count($loadBalancers) . " 个负载均衡实例");

            $this->info("正在获取 MySQL 实例列表...");
            $mysqlInstances = $cloudPlatform->getMysqlInstances($testRegion);
            $this->info("✓ 获取到 " . count($mysqlInstances) . " 个 MySQL 实例");

            $this->info("正在获取 Redis 实例列表...");
            $redisInstances = $cloudPlatform->getRedisInstances($testRegion);
            $this->info("✓ 获取到 " . count($redisInstances) . " 个 Redis 实例");

            $this->info("正在获取域名列表...");
            $domains = $cloudPlatform->getDomains();
            $this->info("✓ 获取到 " . count($domains) . " 个域名");

            // 测试资源详情获取
            if (!empty($ecsInstances)) {
                $firstEcs = $ecsInstances[0];
                $this->info("正在获取 ECS 实例详情...");
                $detail = $cloudPlatform->getResourceDetail('ecs', $firstEcs['resource_id'], $testRegion);
                if ($detail) {
                    $this->info("✓ 成功获取资源详情");
                    $this->line("  资源ID: " . $detail['resource_id']);
                    $this->line("  资源名称: " . $detail['name']);
                    $this->line("  资源状态: " . $detail['status']);
                }
            }

            // 测试监控数据获取
            if (!empty($ecsInstances)) {
                $firstEcs = $ecsInstances[0];
                $this->info("正在获取监控数据...");
                $monitoring = $cloudPlatform->getResourceMonitoring('ecs', $firstEcs['resource_id'], $testRegion);
                if (!empty($monitoring)) {
                    $this->info("✓ 成功获取监控数据");
                    if (isset($monitoring['metrics'])) {
                        foreach ($monitoring['metrics'] as $metric => $value) {
                            $this->line("  {$metric}: {$value}");
                        }
                    }
                }
            }

            $this->info("\n🎉 所有测试完成！云平台适配器工作正常。");
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ 测试失败: " . $e->getMessage());
            $this->error("堆栈跟踪: " . $e->getTraceAsString());
            return 1;
        }
    }

    /**
     * 获取测试配置
     */
    private function getTestConfig(string $platformType): array
    {
        $configs = [
            'huawei' => [
                'access_key_id' => 'test_huawei_key',
                'access_key_secret' => 'test_huawei_secret',
                'region' => 'cn-north-4',
            ],
            'alibaba' => [
                'access_key_id' => 'test_alibaba_key',
                'access_key_secret' => 'test_alibaba_secret',
                'region' => 'cn-hangzhou',
            ],
            'tencent' => [
                'access_key_id' => 'test_tencent_key',
                'access_key_secret' => 'test_tencent_secret',
                'region' => 'ap-beijing',
            ],
        ];

        return $configs[$platformType] ?? [];
    }
}