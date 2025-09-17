<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ServerController;
use App\Http\Controllers\Api\CollectorController;
use App\Http\Controllers\CollectionHistoryController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// API认证路由
Route::post('login', function() {
    return response()->json(['message' => 'API认证功能已禁用'], 403);
});

// 不需要认证的API路由
// 采集组件API
Route::get('collectors', [CollectorController::class, 'index']);
Route::get('collectors/{collector}', [CollectorController::class, 'show']);

// 服务器API（不需要认证的部分）
Route::get('servers', [ServerController::class, 'index']);
Route::post('servers/common-collectors', [ServerController::class, 'getCommonCollectors'])->name('api.servers.common-collectors');

// 公共API路由

// 需要认证的API路由
Route::middleware('auth')->group(function () {
    // 服务器管理API
    Route::get('servers/{server}/stats', [ServerController::class, 'getStats'])->name('api.servers.stats');
    Route::get('servers/{server}/collection-history', [ServerController::class, 'getCollectionHistory'])->name('api.servers.collection-history');
    
    // 采集任务API
    Route::get('collection-tasks/{task}/progress', [\App\Http\Controllers\CollectionTaskController::class, 'getProgress'])->name('api.collection-tasks.progress');
    Route::get('task-details/{detail}/result', [\App\Http\Controllers\CollectionTaskController::class, 'getTaskDetailResult'])->name('api.task-details.result');
    
    // 采集历史API
    Route::get('collection-history/{collectionHistory}/result', [CollectionHistoryController::class, 'getResult'])->name('api.collection-history.result');
});