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
        $query = CloudRegion::with('platform');

        // 平台筛选
        if ($request->filled('platform_id')) {
            $query->where('platform_id', $request->platform_id);
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

        // 权限控制：非管理员只能看到自己的数据
        $user = Auth::user();
        $isAdmin = $user && ($user->id === 1 || (property_exists($user, 'username') && $user->username === 'admin'));
        if (!$isAdmin) {
            $query->whereHas('platform', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $regions = $query->orderBy('platform_id')
                        ->orderBy('region_code')
                        ->paginate(15);

        // 保持查询参数
        $regions->appends($request->query());

        // 获取可用的云平台选项
        $platformsQuery = CloudPlatform::select('id', 'name', 'platform_type');
        if (!$isAdmin) {
            $platformsQuery->where('user_id', $user->id);
        }
        $platforms = $platformsQuery->get();

        return view('cloud.regions.index', compact('regions', 'platforms'));
    }

    /**
     * 显示创建可用区表单
     */
    public function create()
    {
        $user = Auth::user();
        $isAdmin = $user && ($user->id === 1 || (property_exists($user, 'username') && $user->username === 'admin'));
        
        $platformsQuery = CloudPlatform::select('id', 'name', 'platform_type');
        if (!$isAdmin) {
            $platformsQuery->where('user_id', $user->id);
        }
        $platforms = $platformsQuery->get();

        return view('cloud.regions.create', compact('platforms'));
    }

    /**
     * 存储新的可用区
     */
    public function store(Request $request)
    {
        $request->validate([
            'platform_id' => 'required|exists:cloud_platforms,id',
            'region_code' => 'required|string|max:50',
            'region_name' => 'required|string|max:100',
            'endpoint' => 'nullable|url|max:255',
            'is_active' => 'required|boolean',
            'description' => 'nullable|string|max:500',
        ]);

        // 权限检查：确保用户只能为自己的平台创建可用区
        $platform = CloudPlatform::findOrFail($request->platform_id);
        $user = Auth::user();
        $isAdmin = $user && ($user->id === 1 || (property_exists($user, 'username') && $user->username === 'admin'));
        
        if (!$isAdmin && $platform->user_id !== $user->id) {
            return back()->withErrors(['platform_id' => '您没有权限为该平台创建可用区'])->withInput();
        }

        // 检查是否已存在相同的平台和区域代码
        $exists = CloudRegion::where('platform_id', $request->platform_id)
                            ->where('region_code', $request->region_code)
                            ->exists();

        if ($exists) {
            return back()->withErrors(['region_code' => '该平台下已存在相同的区域代码'])->withInput();
        }

        CloudRegion::create([
            'platform_id' => $request->platform_id,
            'region_code' => $request->region_code,
            'region_name' => $request->region_name,
            'endpoint' => $request->endpoint,
            'is_active' => $request->is_active,
            'description' => $request->description,
        ]);

        return redirect()->route('cloud.regions.index')
                        ->with('success', '可用区创建成功');
    }

    /**
     * 显示可用区详情
     */
    public function show(CloudRegion $region)
    {
        $region->load('platform');
        
        // 权限检查
        $user = Auth::user();
        $isAdmin = $user && ($user->id === 1 || (property_exists($user, 'username') && $user->username === 'admin'));
        
        if (!$isAdmin && $region->platform->user_id !== $user->id) {
            abort(403, '您没有权限查看该可用区');
        }

        return view('cloud.regions.show', compact('region'));
    }

    /**
     * 显示编辑可用区表单
     */
    public function edit(CloudRegion $region)
    {
        $region->load('platform');
        
        // 权限检查
        $user = Auth::user();
        $isAdmin = $user && ($user->id === 1 || (property_exists($user, 'username') && $user->username === 'admin'));
        
        if (!$isAdmin && $region->platform->user_id !== $user->id) {
            abort(403, '您没有权限编辑该可用区');
        }

        $platformsQuery = CloudPlatform::select('id', 'name', 'platform_type');
        if (!$isAdmin) {
            $platformsQuery->where('user_id', $user->id);
        }
        $platforms = $platformsQuery->get();

        return view('cloud.regions.edit', compact('region', 'platforms'));
    }

    /**
     * 更新可用区
     */
    public function update(Request $request, CloudRegion $region)
    {
        $request->validate([
            'platform_id' => 'required|exists:cloud_platforms,id',
            'region_code' => 'required|string|max:50',
            'region_name' => 'required|string|max:100',
            'endpoint' => 'nullable|url|max:255',
            'is_active' => 'required|boolean',
            'description' => 'nullable|string|max:500',
        ]);

        // 权限检查
        $user = Auth::user();
        $isAdmin = $user && ($user->id === 1 || (property_exists($user, 'username') && $user->username === 'admin'));
        
        if (!$isAdmin && $region->platform->user_id !== $user->id) {
            abort(403, '您没有权限编辑该可用区');
        }

        // 检查新平台的权限
        $newPlatform = CloudPlatform::findOrFail($request->platform_id);
        if (!$isAdmin && $newPlatform->user_id !== $user->id) {
            return back()->withErrors(['platform_id' => '您没有权限选择该平台'])->withInput();
        }

        // 检查是否已存在相同的平台和区域代码（排除当前记录）
        $exists = CloudRegion::where('platform_id', $request->platform_id)
                            ->where('region_code', $request->region_code)
                            ->where('id', '!=', $region->id)
                            ->exists();

        if ($exists) {
            return back()->withErrors(['region_code' => '该平台下已存在相同的区域代码'])->withInput();
        }

        $region->update([
            'platform_id' => $request->platform_id,
            'region_code' => $request->region_code,
            'region_name' => $request->region_name,
            'endpoint' => $request->endpoint,
            'is_active' => $request->is_active,
            'description' => $request->description,
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
            // 权限检查
            $user = Auth::user();
            $isAdmin = $user && ($user->id === 1 || (property_exists($user, 'username') && $user->username === 'admin'));
            
            if (!$isAdmin && $region->platform->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => '您没有权限删除该可用区'
                ], 403);
            }

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
            $user = Auth::user();
            $isAdmin = $user && ($user->id === 1 || (property_exists($user, 'username') && $user->username === 'admin'));
            
            $query = CloudRegion::whereIn('id', $request->ids);
            
            // 权限控制：非管理员只能删除自己的数据
            if (!$isAdmin) {
                $query->whereHas('platform', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }
            
            $deleted = $query->delete();
            
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