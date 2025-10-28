<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Test Routes
|--------------------------------------------------------------------------
|
| 这里定义测试路由，用于验证云资源管理系统的功能
|
*/

Route::get('/cloud-resources', function () {
    return view('test.cloud-resources');
})->name('test.cloud-resources');

Route::get('/system-info', function () {
    return response()->json([
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
        'database' => [
            'connection' => config('database.default'),
            'host' => config('database.connections.' . config('database.default') . '.host'),
            'database' => config('database.connections.' . config('database.default') . '.database'),
        ],
        'environment' => app()->environment(),
        'debug' => config('app.debug'),
        'timezone' => config('app.timezone'),
        'locale' => config('app.locale'),
    ]);
})->name('test.system-info');