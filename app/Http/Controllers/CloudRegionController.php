<?php

namespace App\Http\Controllers;

use App\Models\CloudRegion;
use App\Models\CloudPlatform;
use App\Services\CloudResourceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CloudRegionController extends Controller
{
    protected $cloudResourceService;

    public function __construct(CloudResourceService $cloudResourceService)
    {
        $this->cloudResourceService = $cloudResourceService;
    }

    /**
     * 显示可用区列表
     */
    public function index(Request $request)
    {
        $query = CloudRegion::query();

        // 平台类型筛选
        if ($request->filled('platform_type')) {
            $query->where('platform_type', $request->platform_type);
        }

        // 状态筛选
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // 搜索
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('region_name', 'like', "%{$search}%")
                  ->orWhere('region_code', 'like', "%{$search}%");
            });
        }

        $regions = $query->orderBy('platform_type')
                        ->orderBy('sort_order')
                        ->orderBy('region_code')
                        ->paginate(15);

        // 保持查询参数
        $regions->appends($request->query());

        // 获取可用的平台类型选项
        $platformTypes = CloudRegion::getPlatformTypeOptions();

        return view('cloud.regions.index', compact('regions', 'platformTypes'));
    }

    /**
     * 显示创建可用区表单
     */
    public function create()
    {
        $platformTypes = CloudRegion::getPlatformTypeOptions();
        return view('cloud.regions.create', compact('platformTypes'));
    }

    /**
     * 存储新的可用区
     */
    public function store(Request $request)
    {
        $request->validate([
            'platform_type' => 'required|string|in:huawei,alibaba,tencent',
            'region_code' => 'required|string|max:100',
            'region_name' => 'required|string|max:255',
            'region_name_en' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // 检查是否已存在相同的平台类型和区域代码
        $exists = CloudRegion::where('platform_type', $request->platform_type)
                            ->where('region_code', $request->region_code)
                            ->exists();

        if ($exists) {
            return back()->withErrors(['region_code' => '该平台类型下已存在相同的区域代码'])->withInput();
        }

        CloudRegion::create([
            'platform_type' => $request->platform_type,
            'region_code' => $request->region_code,
            'region_name' => $request->region_name,
            'region_name_en' => $request->region_name_en,
            'is_active' => $request->is_active,
            'description' => $request->description,
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return redirect()->route('cloud.regions.index')
                        ->with('success', '可用区创建成功');
    }

    /**
     * 显示可用区详情
     */
    public function show(CloudRegion $region)
    {
        return view('cloud.regions.show', compact('region'));
    }

    /**
     * 显示编辑可用区表单
     */
    public function edit(CloudRegion $region)
    {
        $platformTypes = CloudRegion::getPlatformTypeOptions();
        return view('cloud.regions.edit', compact('region', 'platformTypes'));
    }

    /**
     * 更新可用区
     */
    public function update(Request $request, CloudRegion $region)
    {
        $request->validate([
            'platform_type' => 'required|string|in:huawei,alibaba,tencent',
            'region_code' => 'required|string|max:100',
            'region_name' => 'required|string|max:255',
            'region_name_en' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // 检查是否已存在相同的平台类型和区域代码（排除当前记录）
        $exists = CloudRegion::where('platform_type', $request->platform_type)
                            ->where('region_code', $request->region_code)
                            ->where('id', '!=', $region->id)
                            ->exists();

        if ($exists) {
            return back()->withErrors(['region_code' => '该平台类型下已存在相同的区域代码'])->withInput();
        }

        $region->update([
            'platform_type' => $request->platform_type,
            'region_code' => $request->region_code,
            'region_name' => $request->region_name,
            'region_name_en' => $request->region_name_en,
            'is_active' => $request->is_active,
            'description' => $request->description,
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return redirect()->route('cloud.regions.index')
                        ->with('success', '可用区更新成功');
    }

    /**
     * 删除可用区
     */
    public function destroy(CloudRegion $region)
    {
        try {
            $region->delete();
            return response()->json([
                'success' => true,
                'message' => '可用区删除成功'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '删除失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 批量删除可用区
     */
    public function batchDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:cloud_regions,id'
        ]);

        try {
            $deleted = CloudRegion::whereIn('id', $request->ids)->delete();
            
            return response()->json([
                'success' => true,
                'message' => "成功删除 {$deleted} 个可用区"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '批量删除失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 清空所有可用区数据
     */
    public function clearAll(Request $request)
    {
        try {
            // 简化权限控制，直接清空所有数据
            $count = CloudRegion::count();
            
            if ($count === 0) {
                return response()->json([
                    'success' => true,
                    'message' => '没有可用区数据需要清空'
                ]);
            }
            
            $deleted = CloudRegion::query()->delete();
            
            Log::info('Cloud regions cleared', [
                'user_id' => Auth::id(),
                'deleted_count' => $deleted,
                'total_count' => $count
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "成功清空 {$deleted} 个可用区数据"
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to clear cloud regions', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '清空失败: ' . $e->getMessage()
            ], 500);
        }
    }
}