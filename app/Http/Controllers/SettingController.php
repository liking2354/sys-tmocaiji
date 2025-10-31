<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    /**
     * 显示设置页面
     */
    public function index()
    {
        $user = Auth::user();
        
        // 可用的主题颜色
        $themeColors = [
            'blue' => [
                'name' => '蓝色',
                'primary' => '#0066cc',
                'light' => '#f0f7ff',
                'description' => '专业、可信、默认主题'
            ],
            'purple' => [
                'name' => '紫色',
                'primary' => '#7c3aed',
                'light' => '#f5f3ff',
                'description' => '创意、高级、优雅'
            ],
            'green' => [
                'name' => '绿色',
                'primary' => '#10b981',
                'light' => '#f0fdf4',
                'description' => '健康、成功、生机'
            ],
            'orange' => [
                'name' => '橙色',
                'primary' => '#f59e0b',
                'light' => '#fffbf0',
                'description' => '温暖、活力、注意'
            ],
            'pink' => [
                'name' => '粉色',
                'primary' => '#ec4899',
                'light' => '#fdf2f8',
                'description' => '温柔、特殊、突出'
            ],
            'cyan' => [
                'name' => '青色',
                'primary' => '#06b6d4',
                'light' => '#ecfdf5',
                'description' => '清爽、现代、信息'
            ],
        ];
        
        // 可用的侧边栏风格
        $sidebarStyles = [
            'light' => [
                'name' => '浅色',
                'description' => '浅色背景，深色文字'
            ],
            'dark' => [
                'name' => '深色',
                'description' => '深色背景，浅色文字'
            ],
        ];
        
        return view('settings.index', compact('user', 'themeColors', 'sidebarStyles'));
    }
    
    /**
     * 更新用户设置
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'theme_color' => 'required|in:blue,purple,green,orange,pink,cyan',
            'sidebar_style' => 'required|in:light,dark',
        ], [
            'theme_color.required' => '请选择主题颜色',
            'theme_color.in' => '选择的主题颜色无效',
            'sidebar_style.required' => '请选择侧边栏风格',
            'sidebar_style.in' => '选择的侧边栏风格无效',
        ]);
        
        $user = Auth::user();
        $user->update($validated);
        
        // 如果是 AJAX 请求，返回 JSON
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => '设置已保存成功！',
                'theme' => $validated['theme_color'],
            ]);
        }
        
        return redirect()->route('settings.index')
            ->with('success', '设置已保存成功！');
    }
    
    /**
     * 获取用户的主题配置 (API)
     */
    public function getThemeConfig()
    {
        $user = Auth::user();
        
        $themeConfig = [
            'blue' => [
                'primary' => '#0066cc',
                'primaryDark' => '#004499',
                'primaryLight' => '#e6f2ff',
                'cardHeaderBg' => '#f0f7ff',
            ],
            'purple' => [
                'primary' => '#7c3aed',
                'primaryDark' => '#6d28d9',
                'primaryLight' => '#ede9fe',
                'cardHeaderBg' => '#f5f3ff',
            ],
            'green' => [
                'primary' => '#10b981',
                'primaryDark' => '#059669',
                'primaryLight' => '#d1fae5',
                'cardHeaderBg' => '#f0fdf4',
            ],
            'orange' => [
                'primary' => '#f59e0b',
                'primaryDark' => '#d97706',
                'primaryLight' => '#fef3c7',
                'cardHeaderBg' => '#fffbf0',
            ],
            'pink' => [
                'primary' => '#ec4899',
                'primaryDark' => '#db2777',
                'primaryLight' => '#fbcfe8',
                'cardHeaderBg' => '#fdf2f8',
            ],
            'cyan' => [
                'primary' => '#06b6d4',
                'primaryDark' => '#0891b2',
                'primaryLight' => '#cffafe',
                'cardHeaderBg' => '#ecfdf5',
            ],
        ];
        
        $theme = $user->theme_color ?? 'blue';
        $config = $themeConfig[$theme];
        $config['theme'] = $theme;
        $config['sidebarStyle'] = $user->sidebar_style ?? 'light';
        
        return response()->json($config);
    }
}
