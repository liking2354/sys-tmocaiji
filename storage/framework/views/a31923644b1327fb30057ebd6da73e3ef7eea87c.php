<?php $__env->startSection('title', '采集历史记录 - 服务器管理与数据采集系统'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>采集历史记录</h1>
    </div>
    
    <!-- 筛选条件 -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="<?php echo e(route('collection-history.index')); ?>" method="GET" class="form-row align-items-center">
                <div class="col-md-2 mb-2">
                    <label for="server_id">服务器</label>
                    <select class="form-control" id="server_id" name="server_id">
                        <option value="">所有服务器</option>
                        <?php $__currentLoopData = $servers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $server): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($server->id); ?>" <?php echo e(request('server_id') == $server->id ? 'selected' : ''); ?>>
                                <?php echo e($server->name); ?> (<?php echo e($server->ip); ?>)
                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label for="collector_id">采集组件</label>
                    <select class="form-control" id="collector_id" name="collector_id">
                        <option value="">所有组件</option>
                        <?php $__currentLoopData = $collectors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $collector): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($collector->id); ?>" <?php echo e(request('collector_id') == $collector->id ? 'selected' : ''); ?>>
                                <?php echo e($collector->name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label for="status">状态</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">所有状态</option>
                        <option value="2" <?php echo e(request('status') == '2' ? 'selected' : ''); ?>>成功</option>
                        <option value="3" <?php echo e(request('status') == '3' ? 'selected' : ''); ?>>失败</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label for="date_from">开始日期</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo e(request('date_from')); ?>">
                </div>
                <div class="col-md-2 mb-2">
                    <label for="date_to">结束日期</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo e(request('date_to')); ?>">
                </div>
                <div class="col-md-2 mb-2 align-self-end">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-search"></i> 筛选
                    </button>
                </div>
            </form>
            <div class="form-row mt-2">
                <div class="col-md-2">
                    <a href="<?php echo e(route('collection-history.index')); ?>" class="btn btn-secondary btn-block">
                        <i class="fas fa-sync"></i> 重置
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 统计信息 -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h4><?php echo e($statistics['total']); ?></h4>
                    <p class="mb-0">总采集次数</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h4><?php echo e($statistics['success']); ?></h4>
                    <p class="mb-0">成功次数</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h4><?php echo e($statistics['failed']); ?></h4>
                    <p class="mb-0">失败次数</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h4><?php echo e(number_format($statistics['success_rate'], 1)); ?>%</h4>
                    <p class="mb-0">成功率</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 历史记录列表 -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>服务器</th>
                            <th>采集组件</th>
                            <th>状态</th>
                            <th>执行时间(秒)</th>
                            <th>采集时间</th>
                            <th>关联任务</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $histories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $history): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e($history->id); ?></td>
                                <td>
                                    <a href="<?php echo e(route('servers.show', $history->server)); ?>" class="text-decoration-none">
                                        <strong><?php echo e($history->server->name); ?></strong>
                                    </a>
                                    <br><small class="text-muted"><?php echo e($history->server->ip); ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo e($history->collector->type === 'script' ? 'info' : 'warning'); ?>">
                                        <?php echo e($history->collector->name); ?>

                                    </span>
                                    <br><small class="text-muted"><?php echo e($history->collector->code); ?></small>
                                </td>
                                <td>
                                    <?php if($history->status == 2): ?>
                                        <span class="badge badge-success"><?php echo e($history->statusText); ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><?php echo e($history->statusText); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($history->execution_time > 0 ? number_format($history->execution_time, 3) : '-'); ?></td>
                                <td><?php echo e($history->created_at->format('Y-m-d H:i:s')); ?></td>
                                <td>
                                    <?php if($history->taskDetail): ?>
                                        <a href="<?php echo e(route('collection-tasks.show', $history->taskDetail->task)); ?>" class="text-decoration-none">
                                            <small><?php echo e($history->taskDetail->task->name); ?></small>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">单独执行</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <?php if($history->hasResult()): ?>
                                            <button type="button" class="btn btn-sm btn-info" onclick="viewResult(<?php echo e($history->id); ?>)">
                                                <i class="fas fa-eye"></i> 查看结果
                                            </button>
                                        <?php endif; ?>
                                        <?php if($history->isFailed() && $history->error_message): ?>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="viewError(<?php echo e($history->id); ?>, '<?php echo e(str_replace("'", "\\'", $history->error_message)); ?>')">
                                                <i class="fas fa-exclamation-triangle"></i> 查看错误
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="8" class="text-center py-3">暂无采集历史记录</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-center mt-3">
                <?php echo e($histories->appends(request()->query())->links()); ?>

            </div>
        </div>
    </div>
</div>

<!-- 结果查看模态框 -->
<div class="modal fade" id="resultModal" tabindex="-1" role="dialog" aria-labelledby="resultModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resultModalLabel">采集结果</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="resultContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> 加载中...
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<!-- 错误查看模态框 -->
<div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-labelledby="errorModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="errorModalLabel">错误信息</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <pre id="errorContent" class="bg-light p-3" style="white-space: pre-wrap;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    // 设置全局变量 - API 基础路由
    window.apiBaseUrl = '<?php echo e(url("/api/public/collection-history")); ?>';
</script>
<script src="<?php echo e(asset('assets/js/modules/collection-history.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
// 查看结果
function viewResult(historyId) {
    $('#resultModal').modal('show');
    $('#resultContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> 加载中...</div>');
    
    // 构建 API URL
    var url = window.apiBaseUrl + '/' + historyId + '/result';
    
    $.ajax({
        url: url,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                var content = '';
                if (typeof response.data.result === 'object') {
                    content = '<pre class="json-formatter">' + JSON.stringify(response.data.result, null, 2) + '</pre>';
                } else {
                    content = '<pre class="bg-light p-3">' + response.data.result + '</pre>';
                }
                $('#resultContent').html(content);
            } else {
                $('#resultContent').html('<div class="alert alert-danger">加载失败：' + response.message + '</div>');
            }
        },
        error: function(xhr) {
            $('#resultContent').html('<div class="alert alert-danger">请求失败：' + xhr.responseText + '</div>');
        }
    });
}

// 查看错误
function viewError(historyId, errorMessage) {
    $('#errorModal').modal('show');
    $('#errorContent').text(errorMessage);
}
</script>

<style>
.json-formatter {
    background-color: #f8f9fa;
    border: 1px solid #eee;
    border-radius: 4px;
    padding: 15px;
    max-height: 500px;
    overflow-y: auto;
    white-space: pre-wrap;
    font-family: monospace;
    font-size: 13px;
}
</style>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/tanli/Documents/php-code/sys-tmocaiji/resources/views/collection-history/index.blade.php ENDPATH**/ ?>