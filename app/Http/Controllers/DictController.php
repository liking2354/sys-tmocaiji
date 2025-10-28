<?php

namespace App\Http\Controllers;

use App\Models\DictCategory;
use App\Models\DictItem;
use App\Services\DictService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class DictController extends Controller
{
    protected DictService $dictService;

    public function __construct(DictService $dictService)
    {
        $this->dictService = $dictService;
    }

    /**
     * 显示字典管理页面
     */
    public function index(): View
    {
        $categories = $this->dictService->getAllCategories();
        
        return view('admin.dict.index_new', compact('categories'));
    }

    /**
     * 获取字典分类列表
     */
    public function categories(): JsonResponse
    {
        $categories = $this->dictService->getAllCategories();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * 创建字典分类
     */
    public function storeCategory(Request $request): JsonResponse
    {
        $request->validate([
            'category_code' => 'required|string|max:50|unique:dict_categories',
            'category_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'integer|min:0',
            'status' => 'required|in:active,inactive'
        ]);

        try {
            $category = $this->dictService->createCategory($request->all());

            return response()->json([
                'success' => true,
                'message' => '字典分类创建成功',
                'data' => $category
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '创建失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 更新字典分类
     */
    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'category_code' => 'required|string|max:50|unique:dict_categories,category_code,' . $id,
            'category_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'integer|min:0',
            'status' => 'required|in:active,inactive'
        ]);

        try {
            $this->dictService->updateCategory($id, $request->all());

            return response()->json([
                'success' => true,
                'message' => '字典分类更新成功'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '更新失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 删除字典分类
     */
    public function destroyCategory(int $id): JsonResponse
    {
        try {
            $this->dictService->deleteCategory($id);

            return response()->json([
                'success' => true,
                'message' => '字典分类删除成功'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '删除失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取分类下的字典项
     */
    public function items(Request $request): JsonResponse
    {
        $request->validate([
            'category_id' => 'required|exists:dict_categories,id'
        ]);

        $items = $this->dictService->getItemsByCategoryId($request->category_id);

        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }

    /**
     * 获取层次结构数据
     */
    public function hierarchy(Request $request): JsonResponse
    {
        $request->validate([
            'category_code' => 'required|string',
            'platform_type' => 'nullable|string'
        ]);

        $data = $this->dictService->getHierarchyData(
            $request->category_code,
            $request->platform_type
        );

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * 获取字典项详情
     */
    public function showItem(int $id): JsonResponse
    {
        $item = $this->dictService->getItemById($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => '字典项不存在'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }

    /**
     * 创建字典项
     */
    public function storeItem(Request $request): JsonResponse
    {
        $request->validate([
            'category_id' => 'required|exists:dict_categories,id',
            'item_code' => 'required|string|max:50',
            'item_name' => 'required|string|max:100',
            'item_value' => 'nullable|string|max:500',
            'level' => 'required|integer|min:1|max:3',
            'parent_id' => 'nullable|exists:dict_items,id',
            'platform_type' => 'nullable|string|max:50',
            'sort_order' => 'integer|min:0',
            'metadata' => 'nullable|string',
            'status' => 'required|in:active,inactive'
        ]);

        try {
            $item = $this->dictService->createItem($request->all());

            return response()->json([
                'success' => true,
                'message' => '字典项创建成功',
                'data' => $item
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '创建失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 更新字典项
     */
    public function updateItem(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'category_id' => 'required|exists:dict_categories,id',
            'item_code' => 'required|string|max:50',
            'item_name' => 'required|string|max:100',
            'item_value' => 'nullable|string|max:500',
            'level' => 'required|integer|min:1|max:3',
            'parent_id' => 'nullable|exists:dict_items,id',
            'platform_type' => 'nullable|string|max:50',
            'sort_order' => 'integer|min:0',
            'metadata' => 'nullable|string',
            'status' => 'required|in:active,inactive'
        ]);

        try {
            $this->dictService->updateItem($id, $request->all());

            return response()->json([
                'success' => true,
                'message' => '字典项更新成功'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '更新失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 删除字典项
     */
    public function destroyItem(int $id): JsonResponse
    {
        try {
            $this->dictService->deleteItem($id);

            return response()->json([
                'success' => true,
                'message' => '字典项删除成功'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '删除失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 根据分类代码获取字典项
     */
    public function itemsByCategory(string $categoryCode): JsonResponse
    {
        $items = $this->dictService->getItemsByCategory($categoryCode);

        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }

    /**
     * 初始化云资源字典数据
     */
    public function initCloudResources(): JsonResponse
    {
        try {
            Artisan::call('dict:init-cloud-resources');
            
            return response()->json([
                'success' => true,
                'message' => '云资源字典数据初始化成功'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '初始化失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 初始化基础字典数据（保持兼容性）
     */
    public function initBasicData(): JsonResponse
    {
        return $this->initCloudResources();
    }
}