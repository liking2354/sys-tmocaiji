<?php

namespace App\Services;

use App\Models\DictCategory;
use App\Models\DictItem;
use Illuminate\Support\Collection;

class DictService
{
    /**
     * 获取云资源层次结构
     */
    public function getCloudResourceHierarchy($platformType = null): Collection
    {
        $category = DictCategory::where('category_code', 'cloud_resource_hierarchy')->first();
        
        if (!$category) {
            return collect();
        }

        // 获取一级分类
        $level1Items = DictItem::where('category_id', $category->id)
            ->byLevel(1)
            ->active()
            ->ordered()
            ->get();

        // 为每个一级分类加载子项
        $level1Items->each(function ($item) use ($platformType) {
            $item->load(['children' => function ($query) use ($platformType) {
                $query->active()->ordered();
                if ($platformType) {
                    $query->where(function($q) use ($platformType) {
                        $q->where('platform_type', $platformType)
                          ->orWhereNull('platform_type');
                    });
                }
            }]);

            // 为二级分类加载三级子项
            $item->children->each(function ($child) use ($platformType) {
                $child->load(['children' => function ($query) use ($platformType) {
                    $query->active()->ordered();
                    if ($platformType) {
                        $query->where('platform_type', $platformType);
                    }
                }]);
            });
        });

        return $level1Items;
    }

    /**
     * 获取指定平台的所有三级资源
     */
    public function getResourcesByPlatform($platformType): Collection
    {
        $category = DictCategory::where('category_code', 'cloud_resource_hierarchy')->first();
        
        if (!$category) {
            return collect();
        }

        return DictItem::where('category_id', $category->id)
            ->byLevel(3)
            ->byPlatform($platformType)
            ->active()
            ->with(['parent.parent'])
            ->ordered()
            ->get();
    }

    /**
     * 获取指定资源类型下的所有平台实现
     */
    public function getPlatformImplementations($resourceCode): Collection
    {
        $category = DictCategory::where('category_code', 'cloud_resource_hierarchy')->first();
        
        if (!$category) {
            return collect();
        }

        // 查找二级资源项
        $resourceItem = DictItem::where('category_id', $category->id)
            ->where('item_code', $resourceCode)
            ->byLevel(2)
            ->first();

        if (!$resourceItem) {
            return collect();
        }

        // 获取所有三级平台实现
        return $resourceItem->children()
            ->active()
            ->ordered()
            ->get();
    }

    /**
     * 获取平台类型列表
     */
    public function getPlatformTypes(): Collection
    {
        $category = DictCategory::where('category_code', 'platform_types')->first();
        
        if (!$category) {
            return collect();
        }

        return DictItem::where('category_id', $category->id)
            ->active()
            ->ordered()
            ->get();
    }

    /**
     * 获取所有字典分类
     */
    public function getAllCategories(): Collection
    {
        return DictCategory::active()->ordered()->get();
    }

    /**
     * 根据分类代码获取字典分类
     */
    public function getCategoryByCode($categoryCode): ?DictCategory
    {
        return DictCategory::where('category_code', $categoryCode)->first();
    }

    /**
     * 获取分类下的所有字典项
     */
    public function getItemsByCategory($categoryCode, $level = null, $platformType = null): Collection
    {
        $category = $this->getCategoryByCode($categoryCode);
        
        if (!$category) {
            return collect();
        }

        $query = DictItem::where('category_id', $category->id)->active();

        if ($level) {
            $query->byLevel($level);
        }

        if ($platformType) {
            $query->where(function($q) use ($platformType) {
                $q->where('platform_type', $platformType)
                  ->orWhereNull('platform_type');
            });
        }

        return $query->ordered()->get();
    }

    /**
     * 根据分类ID获取字典项
     */
    public function getItemsByCategoryId($categoryId, $level = null, $platformType = null): Collection
    {
        $query = DictItem::where('category_id', $categoryId)
            ->active()
            ->ordered();

        if ($level !== null) {
            $query->byLevel($level);
        }

        if ($platformType !== null) {
            $query->byPlatform($platformType);
        }

        return $query->get();
    }

    /**
     * 获取资源状态列表
     */
    public function getResourceStatuses(): Collection
    {
        $category = DictCategory::where('category_code', 'resource_status')->first();
        
        if (!$category) {
            return collect();
        }

        return DictItem::where('category_id', $category->id)
            ->active()
            ->ordered()
            ->get();
    }

    /**
     * 获取资源类型列表（二级分类）
     */
    public function getResourceTypes($platformType = null): Collection
    {
        $category = DictCategory::where('category_code', 'cloud_resource_hierarchy')->first();
        
        if (!$category) {
            return collect();
        }

        $query = DictItem::where('category_id', $category->id)
            ->byLevel(2)
            ->active()
            ->ordered();

        // 如果指定了平台类型，则筛选有该平台实现的资源类型
        if ($platformType) {
            $query->whereHas('children', function ($q) use ($platformType) {
                $q->where('platform_type', $platformType)->active();
            });
        }

        return $query->with('parent')->get();
    }

    /**
     * 获取一级资源分类
     */
    public function getResourceCategories(): Collection
    {
        $category = DictCategory::where('category_code', 'cloud_resource_hierarchy')->first();
        
        if (!$category) {
            return collect();
        }

        return DictItem::where('category_id', $category->id)
            ->byLevel(1)
            ->active()
            ->ordered()
            ->get();
    }

    /**
     * 获取三级资源实现（各平台具体服务）
     */
    public function getResourceImplementations($resourceTypeCode = null, $platformType = null): Collection
    {
        $category = DictCategory::where('category_code', 'cloud_resource_hierarchy')->first();
        
        if (!$category) {
            return collect();
        }

        $query = DictItem::where('category_id', $category->id)
            ->byLevel(3)
            ->active()
            ->ordered();

        if ($resourceTypeCode) {
            $query->whereHas('parent', function ($q) use ($resourceTypeCode) {
                $q->where('item_code', $resourceTypeCode);
            });
        }

        if ($platformType) {
            $query->where('platform_type', $platformType);
        }

        return $query->with(['parent.parent'])->get();
    }

    /**
     * 根据代码获取字典项
     */
    public function getDictItemByCode($categoryCode, $itemCode): ?DictItem
    {
        return DictItem::whereHas('category', function ($query) use ($categoryCode) {
            $query->where('category_code', $categoryCode);
        })
        ->where('item_code', $itemCode)
        ->first();
    }

    /**
     * 获取字典项的完整层次路径
     */
    public function getItemHierarchyPath(DictItem $item): array
    {
        $path = [];
        $current = $item;

        while ($current) {
            array_unshift($path, [
                'id' => $current->id,
                'code' => $current->item_code,
                'name' => $current->item_name,
                'level' => $current->level,
                'platform_type' => $current->platform_type
            ]);
            $current = $current->parent;
        }

        return $path;
    }

    /**
     * 构建树形结构数据
     */
    public function buildTreeData($categoryCode, $platformType = null): array
    {
        $category = DictCategory::where('category_code', $categoryCode)->first();
        
        if (!$category) {
            return [];
        }

        $items = DictItem::where('category_id', $category->id)
            ->active()
            ->ordered()
            ->get();

        if ($platformType) {
            $items = $items->filter(function ($item) use ($platformType) {
                return is_null($item->platform_type) || $item->platform_type === $platformType;
            });
        }

        return $this->buildTree($items);
    }

    /**
     * 递归构建树形结构
     */
    private function buildTree($items, $parentId = null): array
    {
        $tree = [];

        foreach ($items as $item) {
            if ($item->parent_id == $parentId) {
                $node = [
                    'id' => $item->id,
                    'code' => $item->item_code,
                    'name' => $item->item_name,
                    'level' => $item->level,
                    'platform_type' => $item->platform_type,
                    'metadata' => $item->metadata,
                    'children' => $this->buildTree($items, $item->id)
                ];
                $tree[] = $node;
            }
        }

        return $tree;
    }

    /**
     * 获取扁平化的选项列表（用于下拉框）
     */
    public function getFlatOptions($categoryCode, $platformType = null, $level = null): array
    {
        $category = DictCategory::where('category_code', $categoryCode)->first();
        
        if (!$category) {
            return [];
        }

        $query = DictItem::where('category_id', $category->id)->active();

        if ($platformType) {
            $query->where(function($q) use ($platformType) {
                $q->where('platform_type', $platformType)
                  ->orWhereNull('platform_type');
            });
        }

        if ($level) {
            $query->byLevel($level);
        }

        return $query->ordered()
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->item_code,
                    'label' => $item->full_path,
                    'level' => $item->level,
                    'platform_type' => $item->platform_type
                ];
            })
            ->toArray();
    }

    /**
     * 获取层次结构数据（用于新的字典管理页面）
     */
    public function getHierarchyData($categoryCode, $platformType = null): array
    {
        $category = DictCategory::where('category_code', $categoryCode)->first();
        
        if (!$category) {
            return [];
        }

        // 获取所有字典项
        $query = DictItem::where('category_id', $category->id)->active()->ordered();

        if ($platformType) {
            $query->where(function($q) use ($platformType) {
                $q->where('platform_type', $platformType)
                  ->orWhereNull('platform_type');
            });
        }

        $items = $query->get();

        // 构建层次结构
        return $this->buildHierarchyTree($items);
    }

    /**
     * 构建层次结构树
     */
    private function buildHierarchyTree($items, $parentId = null): array
    {
        $tree = [];

        foreach ($items as $item) {
            if ($item->parent_id == $parentId) {
                $node = [
                    'id' => $item->id,
                    'code' => $item->item_code,
                    'name' => $item->item_name,
                    'value' => $item->item_value,
                    'level' => $item->level,
                    'platform_type' => $item->platform_type,
                    'status' => $item->status,
                    'sort_order' => $item->sort_order,
                    'metadata' => $item->metadata,
                    'children' => $this->buildHierarchyTree($items, $item->id)
                ];
                $tree[] = $node;
            }
        }

        return $tree;
    }

    /**
     * 创建字典分类
     */
    public function createCategory(array $data): DictCategory
    {
        return DictCategory::create($data);
    }

    /**
     * 更新字典分类
     */
    public function updateCategory(int $id, array $data): bool
    {
        $category = DictCategory::findOrFail($id);
        return $category->update($data);
    }

    /**
     * 删除字典分类
     */
    public function deleteCategory(int $id): bool
    {
        $category = DictCategory::findOrFail($id);
        
        // 检查是否有关联的字典项
        if ($category->items()->count() > 0) {
            throw new \Exception('该分类下还有字典项，无法删除');
        }
        
        return $category->delete();
    }

    /**
     * 创建字典项
     */
    public function createItem(array $data): DictItem
    {
        // 处理JSON字段
        if (isset($data['metadata']) && is_string($data['metadata'])) {
            $data['metadata'] = json_decode($data['metadata'], true);
        }

        return DictItem::create($data);
    }

    /**
     * 更新字典项
     */
    public function updateItem(int $id, array $data): bool
    {
        $item = DictItem::findOrFail($id);
        
        // 处理JSON字段
        if (isset($data['metadata']) && is_string($data['metadata'])) {
            $data['metadata'] = json_decode($data['metadata'], true);
        }

        return $item->update($data);
    }

    /**
     * 删除字典项
     */
    public function deleteItem(int $id): bool
    {
        $item = DictItem::findOrFail($id);
        
        // 递归删除所有子项
        $this->deleteItemChildren($item);
        
        return $item->delete();
    }

    /**
     * 递归删除字典项的所有子项
     */
    private function deleteItemChildren(DictItem $item): void
    {
        foreach ($item->children as $child) {
            $this->deleteItemChildren($child);
            $child->delete();
        }
    }

    /**
     * 获取字典项详情
     */
    public function getItemById(int $id): ?DictItem
    {
        return DictItem::find($id);
    }
}