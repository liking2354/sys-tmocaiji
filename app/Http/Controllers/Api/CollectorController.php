<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Collector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CollectorController extends Controller
{
    /**
     * 获取采集组件列表（分页）
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $status = $request->input('status');
        $type = $request->input('type');
        
        // 构建缓存键名
        $cacheKey = 'collectors';
        if ($status !== null) {
            $cacheKey .= ':status_' . $status;
        }
        if ($type) {
            $cacheKey .= ':type_' . $type;
        }
        $cacheKey .= ':page_' . $request->input('page', 1) . ':per_page_' . $perPage;
        
        // 尝试从缓存获取数据，采集组件数据变动较少，可以缓存更长时间
        $collectors = Cache::remember($cacheKey, 86400, function () use ($request, $perPage, $status, $type) {
            Log::info('从数据库获取采集组件数据');
            
            $query = Collector::query();
            
            if ($status !== null) {
                $query->where('status', $status);
            }
            
            if ($type) {
                $query->where('type', $type);
            }
            
            return $query->orderBy('name')
                ->paginate($perPage);
        });
        
        return response()->json($collectors);
    }
}