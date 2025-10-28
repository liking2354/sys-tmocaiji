<?php

namespace App\Services;

use App\Models\CloudPlatform;
use App\Models\CloudResource;
use App\Models\CloudPlatformComponent;
use App\Models\DictItem;
use App\Services\CloudPlatform\CloudPlatformFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CloudResourceManagementService
{
    protected DictService $dictService;
    protected CloudPlatformFactory $platformFactory;

    public function __construct(DictService $dictService, CloudPlatformFactory $platformFactory)
    {
        $this->dictService = $dictService;
        $this->platformFactory = $platformFactory;
    }

    /**
     * 获取平台支持的资源类型
     */
    public function getPlatformSupportedResourceTypes(int $platformId): Collection
    {
        $platform = CloudPlatform::findOrFail($platformId);
        
        // 获取平台组件配置
        $components = CloudPlatformComponent::where('platform_id', $platformId)
            ->enabled()
            ->with('componentDict')
            ->get();

        $resourceTypes = collect();
        
        foreach ($components as $component) {
            $supportedTypes = $component->getConfig('supported_resource_types', []);
            foreach ($supportedTypes as $typeCode) {
                $type = $this->dictService->getDictItemByCode('cloud_resource_hierarchy', $typeCode);
                if ($type) {
                    $resourceTypes->push($type);
                }
            }
        }

        return $resourceTypes->unique('id');
    }

    /**
     * 同步平台资源（控制器调用的方法）
     */
    public function syncPlatformResources(int $platformId, array $resourceTypes = []): array
    {
        Log::info("控制器调用同步平台资源", [
            'platform_id' => $platformId,
            'resource_types' => $resourceTypes
        ]);
        
        // 调用实际的同步方法
        return $this->syncCloudResources($platformId, null, $resourceTypes);
    }

    /**
     * 同步云资源
     */
    public function syncCloudResources(int $platformId, ?string $resourceCategory = null, array $resourceTypes = []): array
    {
        $platform = CloudPlatform::findOrFail($platformId);
        // 使用createFromPlatform方法以正确处理配置
        $platformService = $this->platformFactory->createFromPlatform($platform);
        
        // 获取要同步的组件
        $componentsQuery = CloudPlatformComponent::where('platform_id', $platformId)->enabled();
        
        if (!empty($resourceTypes)) {
            $componentsQuery->whereHas('componentDict', function ($q) use ($resourceTypes) {
                $q->whereIn('item_code', $resourceTypes);
            });
        } elseif ($resourceCategory) {
            // 如果指定了分类，获取该分类下的所有资源类型
            $categoryTypes = $this->dictService->getResourceTypes()->filter(function ($type) use ($resourceCategory) {
                return $type->parent && $type->parent->item_code === $resourceCategory;
            })->pluck('item_code')->toArray();
            
            if (!empty($categoryTypes)) {
                $componentsQuery->whereHas('componentDict', function ($q) use ($categoryTypes) {
                    $q->whereIn('item_code', $categoryTypes);
                });
            }
        }
        
        $components = $componentsQuery->with('componentDict')->byPriority()->get();
        
        $results = [];
        
        Log::info('开始同步云资源', [
            'platform_id' => $platform->id,
            'platform_name' => $platform->name,
            'platform_type' => $platform->platform_type,
            'components_count' => $components->count(),
            'components' => $components->map(function($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->componentDict->item_name ?? '未知组件',
                    'code' => $c->componentDict->item_code ?? 'unknown'
                ];
            })->toArray()
        ]);

        DB::transaction(function () use ($platform, $components, $platformService, &$results) {
            foreach ($components as $component) {
                $componentName = $component->componentDict->item_name ?? '未知组件';
                $componentCode = $component->componentDict->item_code ?? 'unknown';
                
                Log::info("开始同步组件: {$componentName}", [
                    'component_id' => $component->id,
                    'component_code' => $componentCode,
                    'platform_id' => $platform->id
                ]);
                
                try {
                    $syncResult = $this->syncComponentResources($platform, $component, $platformService);
                    
                    $results[] = [
                        'component_id' => $component->id,
                        'component_name' => $componentName,
                        'status' => 'success',
                        'message' => "同步成功，处理了 {$syncResult['processed']} 个资源",
                        'processed_count' => $syncResult['processed'],
                        'created_count' => $syncResult['created'],
                        'updated_count' => $syncResult['updated']
                    ];
                    
                    Log::info("组件同步完成: {$componentName}", [
                        'component_id' => $component->id,
                        'processed' => $syncResult['processed'],
                        'created' => $syncResult['created'],
                        'updated' => $syncResult['updated']
                    ]);
                    
                } catch (\Exception $e) {
                    $results[] = [
                        'component_id' => $component->id,
                        'component_name' => $componentName,
                        'status' => 'error',
                        'message' => $e->getMessage(),
                        'processed_count' => 0,
                        'created_count' => 0,
                        'updated_count' => 0
                    ];
                    
                    Log::error("组件同步失败: {$componentName}", [
                        'platform_id' => $platform->id,
                        'component_id' => $component->id,
                        'component_code' => $componentCode,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
        });

        Log::info('云资源同步完成', [
            'platform_id' => $platform->id,
            'total_components' => count($results),
            'success_count' => count(array_filter($results, fn($r) => $r['status'] === 'success')),
            'error_count' => count(array_filter($results, fn($r) => $r['status'] === 'error')),
            'results' => $results
        ]);

        return $results;
    }

    /**
     * 同步单个组件的资源
     */
    protected function syncComponentResources(CloudPlatform $platform, CloudPlatformComponent $component, $platformService): array
    {
        $componentCode = $component->getComponentCodeAttribute();
        $config = $component->config ?? [];
        
        Log::info("调用云平台接口获取资源", [
            'platform_type' => $platform->platform_type,
            'component_code' => $componentCode,
            'config' => $config
        ]);

        // 根据组件类型调用相应的同步方法
        switch ($componentCode) {
            case 'ecs':
            case 'cvm':
            case 'huawei_ecs':
            case 'tencent_cvm':
                return $this->syncComputeResources($platform, $component, $platformService, $config);
                
            case 'rds':
            case 'cdb':
            case 'huawei_rds':
            case 'tencent_cdb':
                return $this->syncDatabaseResources($platform, $component, $platformService, $config);
                
            case 'vpc':
            case 'slb':
            case 'clb':
            case 'huawei_vpc':
            case 'huawei_elb':
            case 'tencent_clb':
                return $this->syncNetworkResources($platform, $component, $platformService, $config);
                
            case 'redis':
            case 'huawei_redis':
            case 'tencent_redis':
                return $this->syncCacheResources($platform, $component, $platformService, $config);
                
            case 'domain':
            case 'tencent_domain':
                return $this->syncDomainResources($platform, $component, $platformService, $config);
                
            default:
                return $this->syncGenericResources($platform, $component, $platformService, $config);
        }
    }

    /**
     * 同步计算资源
     */
    protected function syncComputeResources(CloudPlatform $platform, CloudPlatformComponent $component, $platformService, array $config): array
    {
        $componentCode = $component->getComponentCodeAttribute();
        
        Log::info("调用{$platform->platform_type}云计算接口", [
            'platform_id' => $platform->id,
            'platform_type' => $platform->platform_type,
            'component_id' => $component->id,
            'component_code' => $componentCode,
            'config' => $config
        ]);
        
        try {
            // 根据平台类型调用不同的方法
            switch ($platform->platform_type) {
                case 'tencent':
                    $instances = $platformService->getEcsInstances($config['region'] ?? null);
                    break;
                case 'huawei':
                    $instances = $platformService->getComputeInstances($config);
                    break;
                case 'alibaba':
                    $instances = $platformService->getEcsInstances($config['region'] ?? null);
                    break;
                default:
                    $instances = $platformService->getComputeInstances($config);
                    break;
            }
            
            Log::info("{$platform->platform_type}云计算接口调用成功", [
                'platform_id' => $platform->id,
                'platform_type' => $platform->platform_type,
                'instances_count' => is_array($instances) ? count($instances) : 0,
                'instances_sample' => is_array($instances) && count($instances) > 0 ? array_slice($instances, 0, 2) : []
            ]);
            
            if (empty($instances) || !is_array($instances)) {
                Log::warning("{$platform->platform_type}云计算接口返回空数据", [
                    'platform_id' => $platform->id,
                    'platform_type' => $platform->platform_type,
                    'response_type' => gettype($instances),
                    'response_data' => $instances
                ]);
                return ['processed' => 0, 'created' => 0, 'updated' => 0];
            }
            
            return $this->processInstances($platform, $component, $instances);
            
        } catch (\Exception $e) {
            Log::error("{$platform->platform_type}云计算接口调用失败", [
                'platform_id' => $platform->id,
                'platform_type' => $platform->platform_type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * 同步数据库资源
     */
    protected function syncDatabaseResources(CloudPlatform $platform, CloudPlatformComponent $component, $platformService, array $config): array
    {
        $componentCode = $component->getComponentCodeAttribute();
        
        Log::info("调用{$platform->platform_type}云数据库接口", [
            'platform_id' => $platform->id,
            'platform_type' => $platform->platform_type,
            'component_id' => $component->id,
            'component_code' => $componentCode,
            'config' => $config
        ]);
        
        try {
            // 根据平台类型调用不同的方法
            switch ($platform->platform_type) {
                case 'tencent':
                    $instances = $platformService->getCdbInstances($config['region'] ?? null);
                    break;
                case 'huawei':
                    $instances = $platformService->getDatabaseInstances($config);
                    break;
                case 'alibaba':
                    $instances = $platformService->getRdsInstances($config['region'] ?? null);
                    break;
                default:
                    $instances = $platformService->getDatabaseInstances($config);
                    break;
            }
            
            Log::info("{$platform->platform_type}云数据库接口调用成功", [
                'platform_id' => $platform->id,
                'platform_type' => $platform->platform_type,
                'instances_count' => is_array($instances) ? count($instances) : 0
            ]);
            
            if (empty($instances) || !is_array($instances)) {
                Log::warning("{$platform->platform_type}云数据库接口返回空数据", [
                    'platform_id' => $platform->id,
                    'platform_type' => $platform->platform_type,
                    'response_type' => gettype($instances)
                ]);
                return ['processed' => 0, 'created' => 0, 'updated' => 0];
            }
            
            return $this->processInstances($platform, $component, $instances);
            
        } catch (\Exception $e) {
            Log::error("{$platform->platform_type}云数据库接口调用失败", [
                'platform_id' => $platform->id,
                'platform_type' => $platform->platform_type,
                'component_code' => $componentCode,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 同步网络资源
     */
    protected function syncNetworkResources(CloudPlatform $platform, CloudPlatformComponent $component, $platformService, array $config): array
    {
        $componentCode = $component->getComponentCodeAttribute();
        
        Log::info("调用{$platform->platform_type}云网络接口", [
            'platform_id' => $platform->id,
            'platform_type' => $platform->platform_type,
            'component_id' => $component->id,
            'component_code' => $componentCode,
            'config' => $config
        ]);
        
        try {
            // 根据平台类型调用不同的方法
            switch ($platform->platform_type) {
                case 'tencent':
                    $instances = $platformService->getClbInstances($config['region'] ?? null);
                    break;
                case 'huawei':
                    $instances = $platformService->getNetworkResources($config);
                    break;
                case 'alibaba':
                    $instances = $platformService->getSlbInstances($config['region'] ?? null);
                    break;
                default:
                    $instances = $platformService->getNetworkResources($config);
                    break;
            }
            
            Log::info("{$platform->platform_type}云网络接口调用成功", [
                'platform_id' => $platform->id,
                'platform_type' => $platform->platform_type,
                'instances_count' => is_array($instances) ? count($instances) : 0
            ]);
            
            if (empty($instances) || !is_array($instances)) {
                Log::warning("{$platform->platform_type}云网络接口返回空数据", [
                    'platform_id' => $platform->id,
                    'platform_type' => $platform->platform_type,
                    'response_type' => gettype($instances)
                ]);
                return ['processed' => 0, 'created' => 0, 'updated' => 0];
            }
            
            return $this->processInstances($platform, $component, $instances);
            
        } catch (\Exception $e) {
            Log::error("{$platform->platform_type}云网络接口调用失败", [
                'platform_id' => $platform->id,
                'platform_type' => $platform->platform_type,
                'component_code' => $componentCode,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 同步缓存资源
     */
    protected function syncCacheResources(CloudPlatform $platform, CloudPlatformComponent $component, $platformService, array $config): array
    {
        $componentCode = $component->getComponentCodeAttribute();
        
        Log::info("调用{$platform->platform_type}云缓存接口", [
            'platform_id' => $platform->id,
            'platform_type' => $platform->platform_type,
            'component_id' => $component->id,
            'component_code' => $componentCode,
            'config' => $config
        ]);
        
        try {
            // 根据平台类型调用不同的方法
            switch ($platform->platform_type) {
                case 'tencent':
                    $instances = $platformService->getRedisInstances($config['region'] ?? null);
                    break;
                case 'huawei':
                    $instances = $platformService->getRedisInstances($config['region'] ?? null);
                    break;
                case 'alibaba':
                    $instances = $platformService->getRedisInstances($config['region'] ?? null);
                    break;
                default:
                    $instances = [];
                    break;
            }
            
            Log::info("{$platform->platform_type}云缓存接口调用成功", [
                'platform_id' => $platform->id,
                'platform_type' => $platform->platform_type,
                'instances_count' => is_array($instances) ? count($instances) : 0
            ]);
            
            if (empty($instances) || !is_array($instances)) {
                Log::warning("{$platform->platform_type}云缓存接口返回空数据", [
                    'platform_id' => $platform->id,
                    'platform_type' => $platform->platform_type,
                    'response_type' => gettype($instances)
                ]);
                return ['processed' => 0, 'created' => 0, 'updated' => 0];
            }
            
            return $this->processInstances($platform, $component, $instances);
            
        } catch (\Exception $e) {
            Log::error("{$platform->platform_type}云缓存接口调用失败", [
                'platform_id' => $platform->id,
                'platform_type' => $platform->platform_type,
                'component_code' => $componentCode,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 同步域名资源
     */
    protected function syncDomainResources(CloudPlatform $platform, CloudPlatformComponent $component, $platformService, array $config): array
    {
        $componentCode = $component->getComponentCodeAttribute();
        
        Log::info("调用{$platform->platform_type}云域名接口", [
            'platform_id' => $platform->id,
            'platform_type' => $platform->platform_type,
            'component_id' => $component->id,
            'component_code' => $componentCode,
            'config' => $config
        ]);
        
        try {
            // 根据平台类型调用不同的方法
            switch ($platform->platform_type) {
                case 'tencent':
                    $instances = $platformService->getDomains($config);
                    break;
                case 'huawei':
                    $instances = $platformService->getDomains($config);
                    break;
                case 'alibaba':
                    $instances = $platformService->getDomains($config);
                    break;
                default:
                    $instances = [];
                    break;
            }
            
            Log::info("{$platform->platform_type}云域名接口调用成功", [
                'platform_id' => $platform->id,
                'platform_type' => $platform->platform_type,
                'instances_count' => is_array($instances) ? count($instances) : 0
            ]);
            
            if (empty($instances) || !is_array($instances)) {
                Log::warning("{$platform->platform_type}云域名接口返回空数据", [
                    'platform_id' => $platform->id,
                    'platform_type' => $platform->platform_type,
                    'response_type' => gettype($instances)
                ]);
                return ['processed' => 0, 'created' => 0, 'updated' => 0];
            }
            
            return $this->processInstances($platform, $component, $instances);
            
        } catch (\Exception $e) {
            Log::error("{$platform->platform_type}云域名接口调用失败", [
                'platform_id' => $platform->id,
                'platform_type' => $platform->platform_type,
                'component_code' => $componentCode,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 同步通用资源
     */
    protected function syncGenericResources(CloudPlatform $platform, CloudPlatformComponent $component, $platformService, array $config): array
    {
        $componentCode = $component->getComponentCodeAttribute();
        
        Log::info("调用{$platform->platform_type}云通用接口", [
            'platform_id' => $platform->id,
            'platform_type' => $platform->platform_type,
            'component_id' => $component->id,
            'component_code' => $componentCode,
            'config' => $config
        ]);
        
        try {
            $instances = $platformService->getResourcesByType($componentCode, $config);
            
            Log::info("{$platform->platform_type}云通用接口调用成功", [
                'platform_id' => $platform->id,
                'platform_type' => $platform->platform_type,
                'component_code' => $componentCode,
                'instances_count' => is_array($instances) ? count($instances) : 0
            ]);
            
            if (empty($instances) || !is_array($instances)) {
                Log::warning("{$platform->platform_type}云通用接口返回空数据", [
                    'platform_id' => $platform->id,
                    'platform_type' => $platform->platform_type,
                    'component_code' => $componentCode,
                    'response_type' => gettype($instances)
                ]);
                return ['processed' => 0, 'created' => 0, 'updated' => 0];
            }
            
            return $this->processInstances($platform, $component, $instances);
            
        } catch (\Exception $e) {
            Log::error("{$platform->platform_type}云通用接口调用失败", [
                'platform_id' => $platform->id,
                'platform_type' => $platform->platform_type,
                'component_code' => $componentCode,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 处理实例数据
     */
    protected function processInstances(CloudPlatform $platform, CloudPlatformComponent $component, array $instances): array
    {
        $processed = 0;
        $created = 0;
        $updated = 0;
        
        foreach ($instances as $instance) {
            $result = $this->createOrUpdateCloudResource($platform, $component, $instance);
            $processed++;
            
            if ($result['created']) {
                $created++;
            } else {
                $updated++;
            }
        }
        
        return [
            'processed' => $processed,
            'created' => $created,
            'updated' => $updated
        ];
    }

    /**
     * 创建或更新云资源记录
     */
    protected function createOrUpdateCloudResource(CloudPlatform $platform, CloudPlatformComponent $component, array $resourceData): array
    {
        $resourceId = $resourceData['resource_id'] ?? null;
        if (!$resourceId) {
            Log::warning("资源数据缺少resource_id", [
                'platform_id' => $platform->id,
                'resource_data' => $resourceData
            ]);
            return ['created' => false, 'updated' => false];
        }

        // 获取正确的resource_type值（使用父级字典项的代码）
        $resourceType = $component->componentDict->parent->item_code ?? 'unknown';
        
        $result = CloudResource::updateOrCreate(
            [
                'platform_id' => $platform->id,
                'resource_id' => $resourceId,
                'resource_type' => $resourceType
            ],
            [
                'name' => $resourceData['resource_name'] ?? $resourceData['name'] ?? '',
                'region' => $resourceData['region'] ?? $resourceData['region_id'] ?? null,
                'status' => $resourceData['status'] ?? 'unknown',
                'user_id' => auth()->id() ?? 1, // 默认用户ID
                'metadata' => [
                    'specifications' => $resourceData['specifications'] ?? [],
                    'tags' => $resourceData['tags'] ?? [],
                    'created_time' => $resourceData['created_time'] ?? null,
                ],
                'last_sync_at' => now(),
                'raw_data' => $resourceData
            ]
        );

        Log::info("资源记录处理完成", [
            'platform_id' => $platform->id,
            'resource_id' => $resourceId,
            'resource_name' => $resourceData['resource_name'] ?? $resourceData['name'] ?? '',
            'was_recently_created' => $result->wasRecentlyCreated
        ]);

        return [
            'created' => $result->wasRecentlyCreated,
            'updated' => !$result->wasRecentlyCreated
        ];
    }

    /**
     * 获取平台组件配置
     */
    public function getPlatformComponents(int $platformId): Collection
    {
        return CloudPlatformComponent::where('platform_id', $platformId)
            ->with('componentDict')
            ->byPriority()
            ->get();
    }

    /**
     * 更新平台组件配置
     */
    public function updatePlatformComponentConfig(int $componentId, array $config): bool
    {
        $component = CloudPlatformComponent::findOrFail($componentId);
        
        return $component->update(['config' => array_merge($component->config ?? [], $config)]);
    }

    /**
     * 启用/禁用平台组件
     */
    public function togglePlatformComponent(int $componentId, bool $enabled): bool
    {
        $component = CloudPlatformComponent::findOrFail($componentId);
        
        return $component->update(['is_enabled' => $enabled]);
    }

    /**
     * 准备同步配置
     */
    public function prepareSyncConfig(int $platformId, ?string $resourceCategory = null, array $resourceTypes = []): array
    {
        $platform = CloudPlatform::findOrFail($platformId);
        
        Log::info('准备同步配置', [
            'platform_id' => $platformId,
            'platform_name' => $platform->name,
            'resource_category' => $resourceCategory,
            'resource_types' => $resourceTypes
        ]);
        
        // 获取要同步的组件
        $componentsQuery = CloudPlatformComponent::where('platform_id', $platformId)->enabled();
        
        if (!empty($resourceTypes)) {
            // 获取平台类型，用于匹配具体的平台实现
            $platformType = $platform->platform_type;
            
            // 查找匹配的平台实现代码
            $platformImplementations = [];
            foreach ($resourceTypes as $resourceType) {
                // 查找该资源类型下对应平台的实现
                $implementations = DictItem::where('item_code', 'like', $platformType . '_%')
                    ->whereHas('parent', function ($q) use ($resourceType) {
                        $q->where('item_code', $resourceType);
                    })
                    ->pluck('item_code')
                    ->toArray();
                
                $platformImplementations = array_merge($platformImplementations, $implementations);
            }
            
            Log::info('查找平台实现', [
                'platform_type' => $platformType,
                'resource_types' => $resourceTypes,
                'platform_implementations' => $platformImplementations
            ]);
            
            if (!empty($platformImplementations)) {
                $componentsQuery->whereHas('componentDict', function ($q) use ($platformImplementations) {
                    $q->whereIn('item_code', $platformImplementations);
                });
            } else {
                // 如果没找到平台实现，尝试直接匹配资源类型（兼容旧数据）
                $componentsQuery->whereHas('componentDict', function ($q) use ($resourceTypes) {
                    $q->whereIn('item_code', $resourceTypes);
                });
            }
            
            Log::info('按资源类型筛选组件', [
                'resource_types' => $resourceTypes,
                'platform_implementations' => $platformImplementations
            ]);
        } elseif ($resourceCategory) {
            // 如果指定了分类，获取该分类下的所有资源类型
            $categoryTypes = $this->dictService->getResourceTypes()->filter(function ($type) use ($resourceCategory) {
                return $type->parent && $type->parent->item_code === $resourceCategory;
            })->pluck('item_code')->toArray();
            
            Log::info('按资源分类筛选组件', [
                'resource_category' => $resourceCategory,
                'category_types' => $categoryTypes
            ]);
            
            if (!empty($categoryTypes)) {
                $componentsQuery->whereHas('componentDict', function ($q) use ($categoryTypes) {
                    $q->whereIn('item_code', $categoryTypes);
                });
            }
        }
        
        $components = $componentsQuery->with('componentDict')->byPriority()->get();
        
        Log::info('查询到的组件数量', [
            'component_count' => $components->count(),
            'components' => $components->map(function ($component) {
                return [
                    'id' => $component->id,
                    'dict_id' => $component->component_dict_id,
                    'dict_name' => $component->componentDict->item_name ?? 'N/A',
                    'dict_code' => $component->componentDict->item_code ?? 'N/A'
                ];
            })->toArray()
        ]);
        
        // 如果没有找到匹配的组件，不要回退到所有组件，而是返回空结果
        if ($components->isEmpty()) {
            Log::warning('没有找到匹配的组件', [
                'platform_id' => $platformId,
                'resource_category' => $resourceCategory,
                'resource_types' => $resourceTypes,
                'message' => '请检查所选资源类型是否在该平台中配置了对应的组件'
            ]);
        }
        
        $componentsArray = $components->map(function ($component) {
            return [
                'id' => $component->id,
                'name' => $component->componentDict->item_name ?? '未知组件',
                'code' => $component->componentDict->item_code ?? '',
                'priority' => $component->sync_priority,
                'config' => $component->config ?? []
            ];
        })->toArray();
        
        $config = [
            'platform_id' => $platformId,
            'platform_name' => $platform->name,
            'platform_type' => $platform->platform_type,
            'resource_category' => $resourceCategory,
            'resource_types' => $resourceTypes,
            'resource_count' => count($componentsArray),
            'components' => $componentsArray
        ];
        
        Log::info('最终同步配置', $config);
        
        return $config;
    }

    /**
     * 启动同步任务
     */
    public function startSyncTask(int $platformId, array $config): string
    {
        $taskId = 'sync_' . $platformId . '_' . time() . '_' . uniqid();
        
        // 确保config中有components键
        if (!isset($config['components'])) {
            throw new \Exception('同步配置中缺少组件信息');
        }
        
        $components = $config['components'];
        if (!is_array($components)) {
            throw new \Exception('组件配置格式错误');
        }
        
        if (empty($components)) {
            throw new \Exception('同步配置中缺少组件信息');
        }
        
        // 存储任务配置
        cache()->put("sync_task_{$taskId}", [
            'platform_id' => $platformId,
            'config' => $config,
            'status' => 'pending',
            'created_at' => now()
        ], 3600);
        
        // 立即开始执行同步任务
        try {
            $this->executeSyncTaskAsync($taskId);
        } catch (\Exception $e) {
            Log::error("启动同步任务失败", [
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);
            // 更新任务状态为失败
            cache()->put("sync_task_{$taskId}", [
                'platform_id' => $platformId,
                'config' => $config,
                'status' => 'failed',
                'error' => $e->getMessage(),
                'created_at' => now()
            ], 3600);
            throw $e;
        }
        
        return $taskId;
    }

    /**
     * 执行同步任务
     */
    public function executeSyncTaskAsync(string $taskId): void
    {
        $taskData = cache()->get("sync_task_{$taskId}");
        if (!$taskData) {
            throw new \Exception('任务不存在或已过期');
        }
        
        $platformId = $taskData['platform_id'];
        $config = $taskData['config'];
        
        Log::info("开始执行同步任务", [
            'task_id' => $taskId,
            'platform_id' => $platformId,
            'config' => $config
        ]);
        
        // 更新任务状态
        $taskData = array_merge($taskData, [
            'status' => 'running',
            'started_at' => now(),
            'results' => []
        ]);
        cache()->put("sync_task_{$taskId}", $taskData, 3600);
        
        try {
            // 从组件配置中提取资源类型
            $resourceTypes = [];
            if (isset($config['components']) && is_array($config['components'])) {
                foreach ($config['components'] as $component) {
                    if (isset($component['code'])) {
                        $resourceTypes[] = $component['code'];
                    }
                }
            }
            
            Log::info("执行同步任务 - 提取的资源类型", [
                'task_id' => $taskId,
                'resource_types' => $resourceTypes,
                'resource_category' => $config['resource_category'] ?? null
            ]);
            
            // 执行同步
            $results = $this->syncCloudResources(
                $platformId,
                $config['resource_category'] ?? null,
                $resourceTypes
            );
            
            // 检查是否有任何成功的同步结果
            $hasData = false;
            $totalProcessed = 0;
            foreach ($results as $result) {
                if (isset($result['processed_count']) && $result['processed_count'] > 0) {
                    $hasData = true;
                    $totalProcessed += $result['processed_count'];
                }
            }
            
            Log::info("同步任务执行完成", [
                'task_id' => $taskId,
                'has_data' => $hasData,
                'total_processed' => $totalProcessed,
                'results' => $results
            ]);
            
            // 更新任务状态
            $finalTaskData = array_merge($taskData, [
                'status' => 'completed',
                'results' => $results,
                'has_data' => $hasData,
                'total_processed' => $totalProcessed,
                'completed_at' => now()
            ]);
            
            cache()->put("sync_task_{$taskId}", $finalTaskData, 3600);
            
            if (!$hasData) {
                Log::info("同步任务完成但无数据", [
                    'task_id' => $taskId,
                    'message' => '华为云接口返回空数据，任务正常结束'
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error("同步任务执行失败", [
                'task_id' => $taskId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // 更新任务状态
            cache()->put("sync_task_{$taskId}", array_merge($taskData, [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'failed_at' => now()
            ]), 3600);
            
            throw $e;
        }
    }

    /**
     * 获取同步进度
     */
    public function getSyncProgress(string $taskId): array
    {
        $taskData = cache()->get("sync_task_{$taskId}");
        if (!$taskData) {
            return [
                'status' => 'not_found',
                'message' => '任务不存在或已过期',
                'progress' => 0,
                'processed' => 0,
                'total' => 0,
                'success' => 0,
                'failed' => 0,
                'completed' => false
            ];
        }
        
        $results = $taskData['results'] ?? [];
        $totalComponents = count($taskData['config']['components'] ?? []);
        $processedComponents = count($results);
        $successCount = 0;
        $failedCount = 0;
        $totalProcessedResources = 0;
        
        // 统计成功和失败的组件数量，以及处理的资源总数
        foreach ($results as $result) {
            if (($result['status'] ?? '') === 'success') {
                $successCount++;
            } else {
                $failedCount++;
            }
            
            // 累计处理的资源数量
            $totalProcessedResources += $result['processed_count'] ?? 0;
        }
        
        $isCompleted = $taskData['status'] === 'completed' || $taskData['status'] === 'failed';
        $hasData = $taskData['has_data'] ?? false;
        
        return [
            'status' => $taskData['status'],
            'progress' => $this->calculateProgress($taskData),
            'processed' => $processedComponents,
            'total' => $totalComponents,
            'success' => $successCount,
            'failed' => $failedCount,
            'completed' => $isCompleted,
            'has_data' => $hasData,
            'total_processed_resources' => $totalProcessedResources,
            'results' => $results,
            'error' => $taskData['error'] ?? null,
            'error_details' => $taskData['error_details'] ?? [],
            'message' => $isCompleted && !$hasData ? '同步完成，但华为云接口返回空数据' : null,
            'created_at' => $taskData['created_at'],
            'started_at' => $taskData['started_at'] ?? null,
            'completed_at' => $taskData['completed_at'] ?? null
        ];
    }

    /**
     * 计算任务进度
     */
    protected function calculateProgress(array $taskData): int
    {
        switch ($taskData['status']) {
            case 'pending':
                return 0;
            case 'running':
                return 50;
            case 'completed':
                return 100;
            case 'failed':
                return 0;
            default:
                return 0;
        }
    }

    /**
     * 搜索云资源
     */
    public function searchResources(array $filters = [])
    {
        $query = CloudResource::with(['platform']);

        // 平台筛选
        if (!empty($filters['platform_id'])) {
            $query->where('platform_id', $filters['platform_id']);
        }

        // 资源分类筛选
        if (!empty($filters['resource_category'])) {
            // 根据资源分类获取对应的资源类型
            $categoryTypes = $this->dictService->getResourceTypes()
                ->filter(function ($type) use ($filters) {
                    return $type->parent && $type->parent->item_code === $filters['resource_category'];
                })
                ->pluck('item_code')
                ->toArray();

            if (!empty($categoryTypes)) {
                $query->whereIn('resource_type', $categoryTypes);
            }
        }

        // 资源类型筛选
        if (!empty($filters['resource_type'])) {
            $query->where('resource_type', $filters['resource_type']);
        }

        // 状态筛选
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // 区域筛选
        if (!empty($filters['region_id'])) {
            $query->where('region_id', $filters['region_id']);
        }

        // 资源名称筛选
        if (!empty($filters['name'])) {
            $query->where('resource_name', 'like', '%' . $filters['name'] . '%');
        }

        // 资源ID筛选
        if (!empty($filters['resource_id'])) {
            $query->where('resource_id', 'like', '%' . $filters['resource_id'] . '%');
        }

        // 同步时间筛选
        if (!empty($filters['sync_date_from'])) {
            $query->whereDate('last_sync_at', '>=', $filters['sync_date_from']);
        }

        if (!empty($filters['sync_date_to'])) {
            $query->whereDate('last_sync_at', '<=', $filters['sync_date_to']);
        }

        return $query->orderBy('last_sync_at', 'desc')->paginate(20);
    }

    /**
     * 获取资源统计信息
     */
    public function getResourceStatistics(): array
    {
        $totalResources = CloudResource::count();
        $platformStats = CloudResource::select('platform_id')
            ->with('platform:id,name')
            ->groupBy('platform_id')
            ->selectRaw('platform_id, count(*) as count')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->platform->name ?? '未知平台' => $item->count];
            });

        $statusStats = CloudResource::select('status')
            ->groupBy('status')
            ->selectRaw('status, count(*) as count')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status ?? '未知状态' => $item->count];
            });

        $typeStats = CloudResource::select('resource_type')
            ->groupBy('resource_type')
            ->selectRaw('resource_type, count(*) as count')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->resource_type ?? '未知类型' => $item->count];
            });

        $regionStats = CloudResource::select('region')
            ->groupBy('region')
            ->selectRaw('region, count(*) as count')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->region ?? '未知区域' => $item->count];
            });

        return [
            'total' => $totalResources,
            'by_platform' => $platformStats->toArray(),
            'by_status' => $statusStats->toArray(),
            'by_type' => $typeStats->toArray(),
            'by_region' => $regionStats->toArray(),
            'last_sync' => CloudResource::max('last_sync_at')
        ];
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
     * 清理过期资源
     *
     * @param int $days
     * @return int
     */
    public function cleanupOldResources(int $days = 30): int
    {
        $cutoffDate = now()->subDays($days);
        
        return CloudResource::where('last_sync_at', '<', $cutoffDate)
            ->orWhereNull('last_sync_at')
            ->delete();
    }
}