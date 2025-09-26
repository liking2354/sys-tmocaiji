<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\ServerGroupController;
use App\Http\Controllers\CollectorController;
use App\Http\Controllers\CollectionTaskController;
use App\Http\Controllers\TaskExecutionController;
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
Route::delete('server-groups/batch-delete', [ServerGroupController::class, 'batchDelete'])->name('server-groups.batch-delete');
    
    // 服务器管理
    Route::resource('servers', ServerController::class);
    Route::post('servers/import', [ServerController::class, 'import'])->name('servers.import');
    Route::post('servers/verify', [ServerController::class, 'verifyConnection'])->name('servers.verify');
    Route::post('servers/system-info', [ServerController::class, 'getSystemInfo'])->name('servers.system-info');
    Route::post('servers/export', [ServerController::class, 'export'])->name('servers.export');
    Route::post('servers/export-selected', [ServerController::class, 'exportSelected'])->name('servers.export-selected');
    Route::get('servers/{server}/console', [ServerController::class, 'console'])->name('servers.console');
    Route::post('servers/{server}/execute', [ServerController::class, 'executeCommand'])->name('servers.execute');
    Route::get('servers/{server}/check', [ServerController::class, 'checkStatus'])->name('servers.check');
    Route::post('servers/batch-check', [ServerController::class, 'batchCheckStatus'])->name('servers.batch-check');
    Route::post('servers/batch-modify-components', [ServerController::class, 'batchModifyComponents'])->name('servers.batch-modify-components');
    
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
    Route::post('collection-tasks/batch-destroy', [CollectionTaskController::class, 'batchDestroy'])->name('collection-tasks.batch-destroy');
    Route::get('task-details/{detail}/result', [CollectionTaskController::class, 'getTaskDetailResult'])->name('task-details.result');
    
    // 任务执行管理 - 重构版
    Route::prefix('task-execution')->name('task-execution.')->group(function () {
        // 执行任务
        Route::post('execute/{taskId}', [TaskExecutionController::class, 'executeBatchTask'])->name('execute');
        Route::post('batch-execute', [TaskExecutionController::class, 'executeBatchTasks'])->name('batch-execute');
        
        // 任务控制
        Route::post('reset/{taskId}', [TaskExecutionController::class, 'resetTask'])->name('reset');
        Route::post('cancel/{taskId}', [TaskExecutionController::class, 'cancelTask'])->name('cancel');
        
        // 状态查询
        Route::get('status/{taskId}', [TaskExecutionController::class, 'getTaskStatus'])->name('status');
        Route::post('batch-status', [TaskExecutionController::class, 'getBatchTaskStatus'])->name('batch-status');
    });
    
    // 兼容旧路由（重定向到新的任务执行服务）
    Route::post('collection-tasks/{id}/trigger-batch', [CollectionTaskController::class, 'triggerBatchTask'])->name('collection-tasks.trigger-batch');
    Route::post('collection-tasks/{id}/cancel', [CollectionTaskController::class, 'cancel'])->name('collection-tasks.cancel');
    Route::post('collection-tasks/{id}/reset', [CollectionTaskController::class, 'resetTask'])->name('collection-tasks.reset');
    Route::get('collection-tasks/{id}/status', [CollectionTaskController::class, 'getTaskStatus'])->name('collection-tasks.status');
    Route::get('collection-tasks/{task}/progress', [CollectionTaskController::class, 'getProgress'])->name('collection-tasks.progress');
    
    // 采集组件管理
    Route::resource('collectors', CollectorController::class);
    
    // 采集历史管理
    Route::resource('collection-history', CollectionHistoryController::class)->only(['index', 'show']);
    
    // 数据清理
    Route::get('data/cleanup', [DataController::class, 'showCleanupForm'])->name('data.cleanup.form');
    Route::post('data/cleanup', [DataController::class, 'cleanup'])->name('data.cleanup');
    
    // 用户管理
    Route::prefix('admin')->name('admin.')->group(function () {
        // 用户管理
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
        
        // 角色管理
        Route::resource('roles', \App\Http\Controllers\Admin\RoleController::class);
        
        // 权限管理
        Route::resource('permissions', \App\Http\Controllers\Admin\PermissionController::class);
        
        // 操作日志管理
        Route::get('operation-logs/export', [\App\Http\Controllers\Admin\OperationLogController::class, 'export'])->name('operation-logs.export');
        Route::get('operation-logs/chart-data', [\App\Http\Controllers\Admin\OperationLogController::class, 'chartData'])->name('operation-logs.chart-data');
        Route::resource('operation-logs', \App\Http\Controllers\Admin\OperationLogController::class)->only(['index', 'show']);
        Route::post('operation-logs/batch-delete', [\App\Http\Controllers\Admin\OperationLogController::class, 'batchDelete'])->name('operation-logs.batch-delete');
        Route::post('operation-logs/cleanup', [\App\Http\Controllers\Admin\OperationLogController::class, 'cleanup'])->name('operation-logs.cleanup');
    });
});