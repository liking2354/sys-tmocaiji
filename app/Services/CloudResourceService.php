<?php

namespace App\Services;

use App\Models\CloudPlatform;
use App\Models\CloudResource;
use App\Models\CloudRegion;
use App\Services\CloudPlatform\CloudPlatformFactory;
use App\Services\CloudPlatform\Contracts\CloudPlatformInterface;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CloudResourceService
{
    /**
     * 同步指定平台的所有资源
     *
     * @param CloudPlatform $platform
     * @return array
     */
    public function syncPlatformResources(CloudPlatform $platform): array
    {
        try {
            Log::info('CloudResourceService: 开始同步平台资源', [
                'platform_id' => $platform->id,
                'platform_name' => $platform->name,
                'platform_type' => $platform->platform_type,
                'region' => $platform->region
            ]);

            // 使用createFromPlatform方法，这样会正确处理config字段中的配置
            $cloudPlatform = CloudPlatformFactory::createFromPlatform($platform);

            \Log::info('CloudResourceService: 云平台适配器创建成功');

            $results = [
                'success' => true,
                'platform_id' => $platform->id,
                'platform_name' => $platform->name,
                'synced_resources' => [],
                'errors' => [],
            ];

            // 获取该平台支持的区域
            $regions = $this->getAvailableRegions($platform->platform_type);
            \Log::info('CloudResourceService: 获取到可用区域', [
                'region_count' => count($regions),
                'regions' => array_column($regions, 'region_code')
            ]);

            foreach ($regions as $region) {
                \Log::info('CloudResourceService: 开始同步区域资源', [
                    'region_code' => $region['region_code'],
                    'region_name' => $region['region_name'] ?? ''
                ]);
                
                $regionResults = $this->syncRegionResources($cloudPlatform, $platform, $region['region_code']);
                $results['synced_resources'][$region['region_code']] = $regionResults;
                
                \Log::info('CloudResourceService: 区域资源同步完成', [
                    'region_code' => $region['region_code'],
                    'results' => $regionResults
                ]);
            }

            $totalSynced = 0;
            foreach ($results['synced_resources'] as $regionResults) {
                $totalSynced += array_sum($regionResults);
            }

            \Log::info('CloudResourceService: 平台资源同步完成', [
                'platform_id' => $platform->id,
                'total_synced' => $totalSynced,
                'results' => $results['synced_resources']
            ]);

            return $results;
        } catch (Exception $e) {
            \Log::error('CloudResourceService: 同步平台资源失败', [
                'platform_id' => $platform->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'platform_id' => $platform->id,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 同步指定区域的资源
     *
     * @param CloudPlatformInterface $cloudPlatform
     * @param CloudPlatform $platform
     * @param string $region
     * @return array
     */
    private function syncRegionResources(CloudPlatformInterface $cloudPlatform, CloudPlatform $platform, string $region): array
    {
        $results = [
            'ecs' => 0,
            'clb' => 0,
            'cdb' => 0,
            'redis' => 0,
            'domain' => 0,
        ];

        // 暂时只同步ECS资源，其他资源类型使用模拟数据，暂不同步
        $resourceTypes = ['ecs'];
        // TODO: 实现真实的CLB、CDB、Redis API调用后，再启用这些资源类型
        // $resourceTypes = ['ecs', 'clb', 'cdb', 'redis'];

        foreach ($resourceTypes as $resourceType) {
            try {
                \Log::info("CloudResourceService: 开始获取{$resourceType}资源", [
                    'platform_id' => $platform->id,
                    'region' => $region,
                    'resource_type' => $resourceType
                ]);

                $resources = $this->getResourcesByType($cloudPlatform, $resourceType, $region);
                
                \Log::info("CloudResourceService: 获取到{$resourceType}资源", [
                    'platform_id' => $platform->id,
                    'region' => $region,
                    'resource_type' => $resourceType,
                    'resource_count' => count($resources)
                ]);

                $syncedCount = $this->saveResources($platform, $resources, $resourceType);
                $results[$resourceType] = $syncedCount;

                \Log::info("CloudResourceService: {$resourceType}资源保存完成", [
                    'platform_id' => $platform->id,
                    'region' => $region,
                    'resource_type' => $resourceType,
                    'synced_count' => $syncedCount
                ]);
            } catch (Exception $e) {
                \Log::error("CloudResourceService: 同步{$resourceType}资源失败", [
                    'platform_id' => $platform->id,
                    'region' => $region,
                    'resource_type' => $resourceType,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        // 域名资源不分区域
        if ($region === $platform->region) {
            try {
                \Log::info("CloudResourceService: 开始获取域名资源", [
                    'platform_id' => $platform->id,
                    'region' => $region
                ]);

                $domains = $cloudPlatform->getDomains();
                
                \Log::info("CloudResourceService: 获取到域名资源", [
                    'platform_id' => $platform->id,
                    'domain_count' => count($domains)
                ]);

                $results['domain'] = $this->saveResources($platform, $domains, 'domain');

                \Log::info("CloudResourceService: 域名资源保存完成", [
                    'platform_id' => $platform->id,
                    'synced_count' => $results['domain']
                ]);
            } catch (Exception $e) {
                \Log::error("CloudResourceService: 同步域名资源失败", [
                    'platform_id' => $platform->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        return $results;
    }

    /**
     * 根据资源类型获取资源
     *
     * @param CloudPlatformInterface $cloudPlatform
     * @param string $resourceType
     * @param string $region
     * @return array
     */
    private function getResourcesByType(CloudPlatformInterface $cloudPlatform, string $resourceType, string $region): array
    {
        switch ($resourceType) {
            case 'ecs':
                return $cloudPlatform->getEcsInstances($region);
            case 'clb':
                return $cloudPlatform->getClbInstances($region);
            case 'cdb':
                return $cloudPlatform->getCdbInstances($region);
            case 'redis':
                return $cloudPlatform->getRedisInstances($region);
            default:
                return [];
        }
    }

    /**
     * 保存资源到数据库
     *
     * @param CloudPlatform $platform
     * @param array $resources
     * @param string $resourceType
     * @return int
     */
    private function saveResources(CloudPlatform $platform, array $resources, string $resourceType): int
    {
        $syncedCount = 0;

        \Log::info("CloudResourceService: 开始保存资源到数据库", [
            'platform_id' => $platform->id,
            'resource_type' => $resourceType,
            'resource_count' => count($resources)
        ]);

        DB::beginTransaction();
        try {
            // 获取当前同步时间
            $syncTime = now();
            
            // 先删除该平台该类型的旧资源（只保留最新数据）
            $deletedCount = CloudResource::where('platform_id', $platform->id)
                ->where('resource_type', $resourceType)
                ->delete();
                
            if ($deletedCount > 0) {
                \Log::info("CloudResourceService: 清理旧资源数据", [
                    'platform_id' => $platform->id,
                    'resource_type' => $resourceType,
                    'deleted_count' => $deletedCount
                ]);
            }

            // 批量插入新资源数据
            $insertData = [];
            foreach ($resources as $index => $resourceData) {
                \Log::debug("CloudResourceService: 准备保存资源 #{$index}", [
                    'platform_id' => $platform->id,
                    'resource_type' => $resourceType,
                    'resource_id' => $resourceData['resource_id'] ?? 'unknown',
                    'resource_name' => $resourceData['name'] ?? 'unknown'
                ]);

                $insertData[] = [
                    'platform_id' => $platform->id,
                    'resource_id' => $resourceData['resource_id'],
                    'resource_type' => $resourceType,
                    'name' => $resourceData['name'],
                    'status' => $resourceData['status'],
                    'region' => $resourceData['region'],
                    'user_id' => $platform->user_id,
                    'raw_data' => json_encode($resourceData['raw_data'] ?? []),
                    'metadata' => json_encode($resourceData['metadata'] ?? []),
                    'last_sync_at' => $syncTime,
                    'created_at' => $syncTime,
                    'updated_at' => $syncTime,
                ];
                $syncedCount++;
            }

            // 批量插入新数据
            if (!empty($insertData)) {
                CloudResource::insert($insertData);
            }

            DB::commit();
            
            \Log::info("CloudResourceService: 资源保存成功", [
                'platform_id' => $platform->id,
                'resource_type' => $resourceType,
                'synced_count' => $syncedCount,
                'deleted_old_count' => $deletedCount
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error("CloudResourceService: 资源保存失败", [
                'platform_id' => $platform->id,
                'resource_type' => $resourceType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }

        return $syncedCount;
    }

    /**
     * 获取用户的所有云资源
     *
     * @param int|null $userId
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserResources($userId = null, array $filters = [])
    {
        $query = CloudResource::with(['platform', 'user'])
            ->forUser($userId);

        // 应用过滤条件
        if (!empty($filters['platform_id'])) {
            $query->where('platform_id', $filters['platform_id']);
        }

        if (!empty($filters['resource_type'])) {
            $query->where('resource_type', $filters['resource_type']);
        }

        if (!empty($filters['region'])) {
            $query->where('region', $filters['region']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('resource_id', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('last_sync_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * 获取资源统计信息
     *
     * @param int|null $userId
     * @return array
     */
    public function getResourceStatistics($userId = null): array
    {
        $query = CloudResource::forUser($userId);

        $statistics = [
            'total' => $query->count(),
            'by_type' => $query->groupBy('resource_type')
                ->selectRaw('resource_type, count(*) as count')
                ->pluck('count', 'resource_type')
                ->toArray(),
            'by_platform' => $query->join('cloud_platforms', 'cloud_resources.platform_id', '=', 'cloud_platforms.id')
                ->groupBy('cloud_platforms.platform_type')
                ->selectRaw('cloud_platforms.platform_type, count(*) as count')
                ->pluck('count', 'platform_type')
                ->toArray(),
            'by_status' => $query->groupBy('status')
                ->selectRaw('status, count(*) as count')
                ->pluck('count', 'status')
                ->toArray(),
            'sync_status' => [
                'synced' => $query->where('last_sync_at', '>', now()->subMinutes(30))->count(),
                'need_sync' => $query->where('last_sync_at', '<=', now()->subMinutes(30))
                    ->orWhereNull('last_sync_at')->count(),
            ],
        ];

        return $statistics;
    }

    /**
     * 测试云平台连接
     *
     * @param CloudPlatform $platform
     * @return array
     */
    public function testPlatformConnection(CloudPlatform $platform): array
    {
        try {
            // 记录收到的参数，便于定位问题
            \Log::info('TestPlatformConnection received platform', [
                'platform_id' => $platform->id ?? null,
                'name' => $platform->name ?? null,
                'platform_type_raw' => $platform->platform_type ?? null,
                'region' => $platform->region ?? null,
            ]);

            $platformType = strtolower(trim((string)$platform->platform_type));

            // 若类型为空，尝试从数据库 fresh() 重新读取
            if ($platformType === '') {
                $fresh = CloudPlatform::find($platform->id);
                if ($fresh && !empty($fresh->platform_type)) {
                    $platformType = strtolower(trim((string)$fresh->platform_type));
                    \Log::info('Recovered platform_type from fresh DB read', [
                        'platform_id' => $platform->id,
                        'platform_type' => $platformType,
                    ]);
                }
            }

            if ($platformType === '') {
                return [
                    'success' => false,
                    'message' => '连接失败: 平台类型为空，请选择 huawei/alibaba/tencent',
                    'platform_name' => $platform->name,
                ];
            }

            if (!CloudPlatformFactory::isSupported($platformType)) {
                return [
                    'success' => false,
                    'message' => '连接失败: 不支持的平台类型: ' . $platformType,
                    'platform_name' => $platform->name,
                ];
            }

            // 使用createFromPlatform方法，这样会正确处理config字段中的配置
            $cloudPlatform = CloudPlatformFactory::createFromPlatform($platform);

            $connected = $cloudPlatform->testConnection();

            return [
                'success' => $connected,
                'message' => $connected ? '连接成功' : '连接失败: SDK初始化或凭证/区域错误，请检查 Access Key 与 Region',
                'platform_name' => $platform->name,
            ];
        } catch (Exception $e) {
            \Log::error('TestPlatformConnection failed', [
                'platform_id' => $platform->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => '连接失败: ' . $e->getMessage(),
                'platform_name' => $platform->name,
            ];
        }
    }

    /**
     * 获取可用区域列表
     *
     * @param string $platformType
     * @return array
     */
    public function getAvailableRegions(string $platformType): array
    {
        try {
            $regions = CloudRegion::byPlatformType($platformType)
                ->active()
                ->orderBy('region_code')
                ->get()
                ->toArray();
            
            // 如果数据库中没有区域数据，返回默认区域配置
            if (empty($regions)) {
                return $this->getDefaultRegions($platformType);
            }
            
            return $regions;
        } catch (\Exception $e) {
            \Log::warning('获取区域信息失败，使用默认配置', [
                'platform_type' => $platformType,
                'error' => $e->getMessage()
            ]);
            
            return $this->getDefaultRegions($platformType);
        }
    }

    /**
     * 获取默认区域配置
     *
     * @param string $platformType
     * @return array
     */
    private function getDefaultRegions(string $platformType): array
    {
        $defaultRegions = [
            'huawei' => [
                ['region_code' => 'cn-north-1', 'region_name' => '华北-北京一'],
                ['region_code' => 'cn-north-4', 'region_name' => '华北-北京四'],
                ['region_code' => 'cn-east-2', 'region_name' => '华东-上海二'],
                ['region_code' => 'cn-east-3', 'region_name' => '华东-上海一'],
                ['region_code' => 'cn-south-1', 'region_name' => '华南-广州'],
            ],
            'alibaba' => [
                ['region_code' => 'cn-beijing', 'region_name' => '华北2（北京）'],
                ['region_code' => 'cn-shanghai', 'region_name' => '华东2（上海）'],
                ['region_code' => 'cn-shenzhen', 'region_name' => '华南1（深圳）'],
                ['region_code' => 'cn-hangzhou', 'region_name' => '华东1（杭州）'],
                ['region_code' => 'cn-qingdao', 'region_name' => '华北1（青岛）'],
            ],
            'tencent' => [
                ['region_code' => 'ap-beijing', 'region_name' => '北京'],
                ['region_code' => 'ap-shanghai', 'region_name' => '上海'],
                ['region_code' => 'ap-guangzhou', 'region_name' => '广州'],
                ['region_code' => 'ap-chengdu', 'region_name' => '成都'],
                ['region_code' => 'ap-nanjing', 'region_name' => '南京'],
            ],
        ];

        return $defaultRegions[$platformType] ?? [
            ['region_code' => 'default', 'region_name' => '默认区域']
        ];
    }

    /**
     * 同步云平台可用区
     *
     * @param CloudPlatform $platform
     * @return array
     */
    public function syncRegions(CloudPlatform $platform): array
    {
        try {
            \Log::info('开始同步可用区', [
                'platform_id' => $platform->id,
                'platform_type' => $platform->platform_type
            ]);

            // 使用createFromPlatform方法以正确处理配置
            $cloudPlatform = CloudPlatformFactory::createFromPlatform($platform);

            $regions = $cloudPlatform->getRegions();
            $syncedCount = 0;

            foreach ($regions as $regionData) {
                // 使用新的CloudRegion模型结构，只与平台类型关联
                CloudRegion::updateOrCreate(
                    [
                        'platform_type' => $platform->platform_type,
                        'region_code' => $regionData['region_code'],
                    ],
                    [
                        'region_name' => $regionData['region_name'] ?? $regionData['region_code'],
                        'endpoint' => $regionData['endpoint'] ?? null,
                        'is_active' => ($regionData['status'] ?? 'active') === 'active',
                        'description' => "从{$platform->name}同步的区域信息",
                        'metadata' => $regionData['metadata'] ?? null,
                    ]
                );
                $syncedCount++;
            }

            \Log::info('可用区同步完成', [
                'platform_type' => $platform->platform_type,
                'synced_regions' => $syncedCount
            ]);

            return [
                'synced_regions' => $syncedCount,
                'regions' => $regions
            ];

        } catch (\Exception $e) {
            \Log::error('可用区同步失败', [
                'platform_id' => $platform->id,
                'platform_type' => $platform->platform_type,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 同步云平台区域信息（兼容旧方法名）
     *
     * @param CloudPlatform $platform
     * @return array
     */
    public function syncPlatformRegions(CloudPlatform $platform): array
    {
        try {
            $result = $this->syncRegions($platform);
            
            return [
                'success' => true,
                'synced_count' => $result['synced_regions'],
                'message' => "成功同步 {$result['synced_regions']} 个区域",
            ];
        } catch (Exception $e) {
            \Log::error('Failed to sync platform regions', [
                'platform_id' => $platform->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 删除过期的资源数据
     *
     * @param int $days 保留天数
     * @return int
     */
    public function cleanupExpiredResources(int $days = 30): int
    {
        $cutoffDate = now()->subDays($days);
        
        return CloudResource::where('last_sync_at', '<', $cutoffDate)
            ->orWhereNull('last_sync_at')
            ->delete();
    }

    /**
     * 批量删除资源
     *
     * @param array $resourceIds
     * @param int|null $userId
     * @return int
     */
    public function batchDeleteResources(array $resourceIds, $userId = null): int
    {
        $query = CloudResource::whereIn('id', $resourceIds);
        
        if ($userId && !(auth()->check() && (auth()->user()->id === 1 || (property_exists(auth()->user(), 'username') && auth()->user()->username === 'admin')))) {
            $query->where('user_id', $userId);
        }

        return $query->delete();
    }

    /**
     * 获取资源详细信息
     */
    public function getResourceDetail(?CloudPlatform $platform, string $resourceType, string $resourceId)
    {
        if (!$platform) {
            \Log::warning('Cannot get resource detail: platform is null', [
                'resource_type' => $resourceType,
                'resource_id' => $resourceId
            ]);
            return null;
        }

        try {
            // 使用createFromPlatform方法以正确处理配置
            $cloudPlatform = CloudPlatformFactory::createFromPlatform($platform);
            return $cloudPlatform->getResourceDetail($resourceType, $resourceId);
        } catch (Exception $e) {
            \Log::error('Failed to get resource detail', [
                'platform_id' => $platform->id,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 刷新单个资源信息
     */
    public function refreshResource(CloudResource $resource)
    {
        try {
            $platform = $resource->platform;
            
            if (!$platform) {
                \Log::warning('Cannot refresh resource: platform is null', [
                    'resource_id' => $resource->id,
                    'platform_id' => $resource->platform_id
                ]);
                
                return [
                    'success' => false,
                    'error' => '资源关联的云平台不存在'
                ];
            }
            
            // 使用createFromPlatform方法以正确处理配置
            $cloudPlatform = CloudPlatformFactory::createFromPlatform($platform);
            
            // 获取最新的资源信息
            $resourceData = $cloudPlatform->getResourceDetail($resource->resource_type, $resource->resource_id);
            
            if ($resourceData) {
                // 更新资源信息
                $resource->update([
                    'name' => $resourceData['name'] ?? $resource->name,
                    'status' => $resourceData['status'] ?? $resource->status,
                    'region' => $resourceData['region'] ?? $resource->region,
                    'raw_data' => $resourceData,
                    'last_sync_at' => now(),
                ]);

                return [
                    'success' => true,
                    'resource' => $resource->fresh()
                ];
            } else {
                return [
                    'success' => false,
                    'error' => '无法获取资源信息'
                ];
            }
        } catch (Exception $e) {
            \Log::error('Failed to refresh resource', [
                'resource_id' => $resource->id,
                'platform_id' => $resource->platform_id ?? null,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 导出资源数据
     */
    public function exportResources($resources, string $format, string $filename)
    {
        try {
            $data = [];
            
            foreach ($resources as $resource) {
                $data[] = [
                    'ID' => $resource->id,
                    '资源名称' => $resource->name,
                    '资源类型' => $resource->resource_type,
                    '资源ID' => $resource->resource_id,
                    '状态' => $resource->status,
                    '区域' => $resource->region,
                    '云平台' => $resource->platform->name,
                    '平台类型' => $resource->platform->platform_type,
                    '创建时间' => $resource->created_at->format('Y-m-d H:i:s'),
                    '最后同步' => $resource->last_sync_at ? $resource->last_sync_at->format('Y-m-d H:i:s') : '',
                ];
            }

            if ($format === 'xlsx') {
                return $this->exportToExcel($data, $filename);
            } else {
                return $this->exportToCsv($data, $filename);
            }
        } catch (Exception $e) {
            \Log::error('Failed to export resources', [
                'format' => $format,
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取资源监控信息
     */
    public function getResourceMonitoring(CloudPlatform $platform, string $resourceType, string $resourceId)
    {
        try {
            // 使用createFromPlatform方法以正确处理配置
            $cloudPlatform = CloudPlatformFactory::createFromPlatform($platform);
            return $cloudPlatform->getResourceMonitoring($resourceType, $resourceId);
        } catch (Exception $e) {
            \Log::error('Failed to get resource monitoring', [
                'platform_id' => $platform->id,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 导出到Excel
     */
    private function exportToExcel(array $data, string $filename)
    {
        // 这里需要使用 Laravel Excel 包
        // 暂时返回简单的响应，后续可以集成 maatwebsite/excel
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        // 简单的CSV格式作为临时解决方案
        return $this->exportToCsv($data, str_replace('.xlsx', '.csv', $filename));
    }

    /**
     * 导出到CSV
     */
    private function exportToCsv(array $data, string $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // 添加BOM以支持中文
            fwrite($file, "\xEF\xBB\xBF");
            
            if (!empty($data)) {
                // 写入表头
                fputcsv($file, array_keys($data[0]));
                
                // 写入数据
                foreach ($data as $row) {
                    fputcsv($file, $row);
                }
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}