<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\ServerGroupController;
use App\Http\Controllers\CollectorController;
use App\Http\Controllers\CollectionTaskController;
use App\Http\Controllers\CollectionHistoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// 认证路由
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// 不需要认证的路由
Route::post('servers/download', [ServerController::class, 'downloadServers'])->name('servers.download');

// 需要认证的路由
Route::middleware(['auth'])->group(function () {
    // 首页仪表盘
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // 服务器分组管理
    Route::resource('server-groups', ServerGroupController::class);
    
    // 服务器管理
    Route::resource('servers', ServerController::class);
    Route::post('servers/import', [ServerController::class, 'import'])->name('servers.import');
    Route::post('servers/verify', [ServerController::class, 'verifyConnection'])->name('servers.verify');
    Route::post('servers/export', [ServerController::class, 'export'])->name('servers.export');
    Route::post('servers/export-selected', [ServerController::class, 'exportSelected'])->name('servers.export-selected');
    Route::get('servers/{server}/console', [ServerController::class, 'console'])->name('servers.console');
    Route::post('servers/{server}/execute', [ServerController::class, 'executeCommand'])->name('servers.execute');
    
    // 服务器与采集组件关联
    Route::post('servers/{server}/collectors/{collector}/install', [ServerController::class, 'installCollector'])->name('servers.collectors.install');
    Route::delete('servers/{server}/collectors/{collector}/uninstall', [ServerController::class, 'uninstallCollector'])->name('servers.collectors.uninstall');
    
    // 服务器采集功能
    Route::post('servers/{server}/collection/execute', [ServerController::class, 'executeCollection'])->name('servers.collection.execute');
    Route::get('servers/{server}/collection/history', [ServerController::class, 'collectionHistory'])->name('servers.collection.history');
    Route::post('servers/batch/select', [ServerController::class, 'batchSelect'])->name('servers.batch.select');
    Route::post('servers/batch/collection', [ServerController::class, 'batchCollection'])->name('servers.batch.collection');
    
    // 采集任务管理
    Route::resource('collection-tasks', CollectionTaskController::class);
    Route::get('servers/{server}/collection/create', [CollectionTaskController::class, 'createSingle'])->name('collection-tasks.single.create');
    Route::post('servers/{server}/collection/execute-single', [CollectionTaskController::class, 'executeSingle'])->name('collection-tasks.single.execute');
    Route::get('collection-tasks/batch/create', [CollectionTaskController::class, 'createBatch'])->name('collection-tasks.batch.create');
    Route::post('collection-tasks/batch/execute', [CollectionTaskController::class, 'executeBatch'])->name('collection-tasks.batch.execute');
    Route::post('collection-tasks/{task}/retry', [CollectionTaskController::class, 'retryFailed'])->name('collection-tasks.retry');
    Route::post('collection-tasks/{task}/cancel', [CollectionTaskController::class, 'cancel'])->name('collection-tasks.cancel');
    Route::get('collection-tasks/{task}/progress', [CollectionTaskController::class, 'getProgress'])->name('collection-tasks.progress');
    Route::get('task-details/{detail}/result', [CollectionTaskController::class, 'getTaskDetailResult'])->name('task-details.result');
    
    // 采集组件管理
    Route::resource('collectors', CollectorController::class);
    
    // 采集历史管理
    Route::resource('collection-history', CollectionHistoryController::class)->only(['index', 'show']);
    
    // 数据清理
    Route::get('data/cleanup', [DataController::class, 'showCleanupForm'])->name('data.cleanup.form');
    Route::post('data/cleanup', [DataController::class, 'cleanup'])->name('data.cleanup');
});