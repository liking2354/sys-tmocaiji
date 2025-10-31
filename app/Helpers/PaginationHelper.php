<?php

namespace App\Helpers;

use Illuminate\Http\Request;

class PaginationHelper
{
    /**
     * 获取每页显示的条数
     * 
     * @param Request $request
     * @param int $default 默认每页条数
     * @return int
     */
    public static function getPerPage(Request $request, int $default = 10): int
    {
        $perPage = $request->input('per_page', $default);
        
        // 允许的每页条数选项
        $allowedPerPage = [10, 20, 30, 50];
        
        // 如果不在允许的选项中，使用默认值
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = $default;
        }
        
        return (int) $perPage;
    }

    /**
     * 获取分页查询参数（用于appends）
     * 
     * @param Request $request
     * @return array
     */
    public static function getQueryParams(Request $request): array
    {
        return $request->except('page');
    }
}
