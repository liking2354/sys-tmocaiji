<?php $__env->startSection('title', '采集任务管理 - 服务器管理与数据采集系统'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>采集任务管理</h1>
        <div>
            <a href="<?php echo e(route('servers.index')); ?>" class="btn btn-primary mr-2">
                <i class="fas fa-plus"></i> 去服务器页面创建批量任务
            </a>
            <button type="button" class="btn btn-success mr-2" id="batchExecuteBtn">
                <i class="fas fa-play"></i> 立即执行批量任务
            </button>
            <button type="button" class="btn btn-danger" id="batchDeleteBtn" disabled>
                <i class="fas fa-trash"></i> 批量删除
            </button>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 使用JavaScript设置进度条宽度
            var progressBars = [
                <?php $__currentLoopData = $tasks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $task): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                {
                    id: '<?php echo e($task->id); ?>',
                    progress: <?php echo e($task->progress); ?>

                },
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            ];
            
            progressBars.forEach(function(task) {
                var progressBar = document.querySelector('.task-progress-bar-' + task.id);
                if (progressBar) {
                    progressBar.style.width = task.progress + '%';
                }
            });
        });
    </script>
    
    <!-- 筛选条件 -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="<?php echo e(route('collection-tasks.index')); ?>" method="GET" class="form-row align-items-center">
                <div class="col-md-3 mb-2">
                    <label for="status">任务状态</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">所有状态</option>
                        <option value="0" <?php echo e(request('status') == '0' ? 'selected' : ''); ?>>未开始</option>
                        <option value="1" <?php echo e(request('status') == '1' ? 'selected' : ''); ?>>进行中</option>
                        <option value="2" <?php echo e(request('status') == '2' ? 'selected' : ''); ?>>已完成</option>
                        <option value="3" <?php echo e(request('status') == '3' ? 'selected' : ''); ?>>失败</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label for="type">任务类型</label>
                    <select class="form-control" id="type" name="type">
                        <option value="">所有类型</option>
                        <option value="single" <?php echo e(request('type') == 'single' ? 'selected' : ''); ?>>单服务器</option>
                        <option value="batch" <?php echo e(request('type') == 'batch' ? 'selected' : ''); ?>>批量服务器</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label for="search">搜索</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="任务名称" value="<?php echo e(request('search')); ?>">
                </div>
                <div class="col-md-2 mb-2 align-self-end">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-search"></i> 筛选
                    </button>
                </div>
                <div class="col-md-1 mb-2 align-self-end">
                    <a href="<?php echo e(route('collection-tasks.index')); ?>" class="btn btn-secondary btn-block">
                        <i class="fas fa-sync"></i> 重置
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 任务列表 -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="selectAllTasks">
                                    <label class="custom-control-label" for="selectAllTasks"></label>
                                </div>
                            </th>
                            <th>ID</th>
                            <th>任务名称</th>
                            <th>类型</th>
                            <th>状态</th>
                            <th>进度</th>
                            <th>服务器数量</th>
                            <th>创建人</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $tasks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $task): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input task-checkbox" id="task_<?php echo e($task->id); ?>" value="<?php echo e($task->id); ?>" <?php echo e($task->isRunning() ? 'disabled' : ''); ?>>
                                        <label class="custom-control-label" for="task_<?php echo e($task->id); ?>"></label>
                                    </div>
                                </td>
                                <td><?php echo e($task->id); ?></td>
                                <td>
                                    <a href="<?php echo e(route('collection-tasks.show', $task)); ?>" class="text-decoration-none">
                                        <?php echo e($task->name); ?>

                                    </a>
                                    <?php if($task->description): ?>
                                        <br><small class="text-muted"><?php echo e($task->description); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo e($task->type === 'single' ? 'info' : 'primary'); ?>">
                                        <?php echo e($task->typeText); ?>

                                    </span>
                                </td>
                                <td>
                                    <?php switch($task->status):
                                        case (0): ?>
                                            <span class="badge badge-secondary"><?php echo e($task->statusText); ?></span>
                                            <?php break; ?>
                                        <?php case (1): ?>
                                            <span class="badge badge-warning">
                                                <i class="fas fa-spinner fa-spin"></i> <?php echo e($task->statusText); ?>

                                            </span>
                                            <?php break; ?>
                                        <?php case (2): ?>
                                            <span class="badge badge-success"><?php echo e($task->statusText); ?></span>
                                            <?php break; ?>
                                        <?php case (3): ?>
                                            <span class="badge badge-danger"><?php echo e($task->statusText); ?></span>
                                            <?php break; ?>
                                    <?php endswitch; ?>
                                </td>
                                <td>
                                    <?php if($task->total_servers > 0): ?>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar task-progress-bar-<?php echo e($task->id); ?>

                                                <?php if($task->progress >= 100): ?> bg-success
                                                <?php elseif($task->failed_servers > 0): ?> bg-warning
                                                <?php else: ?> bg-info
                                                <?php endif; ?>" 
                                                role="progressbar" 
                                                aria-valuenow="<?php echo e($task->progress); ?>" 
                                                aria-valuemin="0" 
                                                aria-valuemax="100">
                                                <?php echo e(number_format($task->progress, 1)); ?>%
                                            </div>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo e($task->completed_servers); ?>/<?php echo e($task->total_servers); ?>

                                            <?php if($task->failed_servers > 0): ?>
                                                (失败: <?php echo e($task->failed_servers); ?>)
                                            <?php endif; ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($task->total_servers); ?></td>
                                <td><?php echo e($task->creator->username ?? '未知'); ?></td>
                                <td><?php echo e($task->created_at->format('Y-m-d H:i')); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="<?php echo e(route('collection-tasks.show', $task)); ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> 查看
                                        </a>
                                        <?php if($task->status === 2 && $task->error_count > 0): ?>
                                            <button type="button" class="btn btn-sm btn-warning" onclick="retryTask('<?php echo e($task->id); ?>')">
                                                <i class="fas fa-redo"></i> 重试
                                            </button>
                                        <?php endif; ?>
                                        <?php if($task->status === 0 && $task->type !== 'single'): ?>
                                            <button type="button" class="btn btn-sm btn-primary" onclick="triggerBatchTask('<?php echo e($task->id); ?>')">
                                                <i class="fas fa-play"></i> 执行
                                            </button>
                                        <?php endif; ?>
                                        <?php if($task->isRunning()): ?>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="cancelTask('<?php echo e($task->id); ?>')">
                                                <i class="fas fa-stop"></i> 取消
                                            </button>
                                        <?php endif; ?>
                                        <?php if(!$task->isRunning()): ?>
                                            <form action="<?php echo e(route('collection-tasks.destroy', $task)); ?>" method="POST" class="d-inline" onsubmit="return confirm('确定要删除该任务吗？')">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i> 删除
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="10" class="text-center py-3">暂无采集任务</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-center mt-3">
                <?php echo e($tasks->appends(request()->query())->links()); ?>

            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    $(document).ready(function() {
        // 自动刷新进行中的任务
        setInterval(function() {
            if ($('.badge-warning').length > 0) {
                location.reload();
            }
        }, 5000); // 每5秒刷新一次
        
        // 全选/取消全选
        $('#selectAllTasks').change(function() {
            $('.task-checkbox:not(:disabled)').prop('checked', $(this).prop('checked'));
            updateBatchDeleteButton();
        });
        
        // 单个复选框变化时更新按钮状态
        $('.task-checkbox').change(function() {
            updateBatchDeleteButton();
        });
        
        // 更新批量删除按钮状态
        function updateBatchDeleteButton() {
            var selectedCount = $('.task-checkbox:checked').length;
            $('#batchDeleteBtn').prop('disabled', selectedCount === 0);
        }
        
        // 批量删除按钮点击事件
        $('#batchDeleteBtn').click(function() {
            var selectedIds = [];
            $('.task-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            if (selectedIds.length === 0) {
                toastr.warning('请先选择要删除的任务');
                return;
            }
            
            if (confirm('确定要删除选中的 ' + selectedIds.length + ' 个任务吗？此操作不可恢复！')) {
                $.ajax({
                    url: '<?php echo e(route("collection-tasks.batch-destroy")); ?>',
                    type: 'POST',
                    data: {
                        _token: '<?php echo e(csrf_token()); ?>',
                        task_ids: selectedIds
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            // 刷新页面
                            window.location.reload();
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        var errorMsg = '批量删除失败';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        toastr.error(errorMsg);
                    }
                });
            }
        });
        
        // 重试任务
        window.retryTask = function(taskId) {
            if (confirm("确定要重试失败的任务吗？")) {
                $.ajax({
                    url: "<?php echo e(route('collection-tasks.retry', ':id')); ?>".replace(":id", taskId),
                    type: "POST",
                    data: {
                        _token: "<?php echo e(csrf_token()); ?>"
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success("任务重试已启动！");
                            location.reload();
                        } else {
                            toastr.error("重试失败：" + response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error("请求失败：" + (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : xhr.responseText));
                    }
                });
            }
        };
        
        // 取消任务
        window.cancelTask = function(taskId) {
            if (confirm("确定要取消正在执行的任务吗？")) {
                $.ajax({
                    url: "<?php echo e(route('collection-tasks.cancel', ':id')); ?>".replace(":id", taskId),
                    type: "POST",
                    data: {
                        _token: "<?php echo e(csrf_token()); ?>"
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success("任务已取消！");
                            location.reload();
                        } else {
                            toastr.error("取消失败：" + response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error("请求失败：" + (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : xhr.responseText));
                    }
                });
            }
        };
        
        // 手动触发批量任务
        window.triggerBatchTask = function(taskId) {
            if (confirm("确定要手动触发执行此批量任务吗？")) {
                // 禁用相关按钮防止重复点击
                var $button = $('button[onclick*="triggerBatchTask(' + taskId + ')"]');
                $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 执行中...');
                
                $.ajax({
                    url: "<?php echo e(route('collection-tasks.trigger-batch', ':id')); ?>".replace(":id", taskId),
                    type: "POST",
                    data: {
                        _token: "<?php echo e(csrf_token()); ?>"
                    },
                    success: function(response) {
                        if (response.success) {
                            // 显示成功提示并刷新页面
                            alert("批量任务已开始执行！页面即将刷新...");
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            alert("触发失败：" + response.message);
                            // 恢复按钮状态
                            $button.prop('disabled', false).html('<i class="fas fa-play"></i> 执行');
                        }
                    },
                    error: function(xhr) {
                        var errorMsg = "请求失败";
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        } else if (xhr.responseText) {
                            errorMsg = xhr.responseText;
                        }
                        alert("请求失败：" + errorMsg);
                        // 恢复按钮状态
                        $button.prop('disabled', false).html('<i class="fas fa-play"></i> 执行');
                    }
                });
            }
        };
        
        // 立即执行批量任务按钮点击事件
        $('#batchExecuteBtn').click(function() {
            // 获取所有未执行的批量任务
            var pendingBatchTasks = [];
            $('.task-checkbox').each(function() {
                var taskId = $(this).val();
                var taskType = $(this).closest('tr').find('td:nth-child(4) .badge').text().trim();
                var taskStatus = $(this).closest('tr').find('td:nth-child(5) .badge').text().trim();
                
                if (taskType === '批量服务器' && taskStatus === '未开始') {
                    pendingBatchTasks.push({
                        id: taskId,
                        name: $(this).closest('tr').find('td:nth-child(3) a').text().trim()
                    });
                }
            });
            
            if (pendingBatchTasks.length === 0) {
                toastr.warning('没有可执行的未开始批量任务');
                return;
            }
            
            // 构建选择列表
            var taskOptions = '';
            pendingBatchTasks.forEach(function(task) {
                taskOptions += '<option value="' + task.id + '">' + task.name + ' (ID: ' + task.id + ')</option>';
            });
            
            // 显示选择对话框
            var selectDialog = $('<div class="modal fade" tabindex="-1" role="dialog">' +
                '<div class="modal-dialog" role="document">' +
                '<div class="modal-content">' +
                '<div class="modal-header">' +
                '<h5 class="modal-title">选择要执行的批量任务</h5>' +
                '<button type="button" class="close" data-dismiss="modal" aria-label="Close">' +
                '<span aria-hidden="true">&times;</span>' +
                '</button>' +
                '</div>' +
                '<div class="modal-body">' +
                '<div class="form-group">' +
                '<label for="taskSelect">选择任务：</label>' +
                '<select class="form-control" id="taskSelect">' +
                taskOptions +
                '</select>' +
                '</div>' +
                '</div>' +
                '<div class="modal-footer">' +
                '<button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>' +
                '<button type="button" class="btn btn-primary" id="confirmExecuteBtn">确认执行</button>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>');
            
            $('body').append(selectDialog);
            selectDialog.modal('show');
            
            // 确认执行按钮点击事件
            $('#confirmExecuteBtn').click(function() {
                var taskId = $('#taskSelect').val();
                
                $.ajax({
                    url: '<?php echo e(url("collection-tasks")); ?>/' + taskId + '/trigger',
                    type: 'POST',
                    data: {
                        _token: '<?php echo e(csrf_token()); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            selectDialog.modal('hide');
                            // 刷新页面
                            window.location.reload();
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        var errorMsg = '触发任务失败';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        toastr.error(errorMsg);
                    }
                });
            });
            
            // 模态框关闭时移除
            selectDialog.on('hidden.bs.modal', function() {
                $(this).remove();
            });
        });
    });
</script>
<?php $__env->startPush('scripts'); ?>
<script>
    window.csrfToken = '<?php echo e(csrf_token()); ?>';
    window.collectionTasksRetryRoute = '<?php echo e(route("collection-tasks.retry", ":id")); ?>';
    window.collectionTasksCancelRoute = '<?php echo e(route("collection-tasks.cancel", ":id")); ?>';
    window.collectionTasksTriggerBatchRoute = '<?php echo e(route("collection-tasks.trigger-batch", ":id")); ?>';
    window.collectionTasksBatchDestroyRoute = '<?php echo e(route("collection-tasks.batch-destroy")); ?>';
    window.collectionTasksTriggerRoute = '<?php echo e(url("collection-tasks")); ?>/<?php echo e(":id"); ?>/trigger';
</script>
<script src="<?php echo e(asset('assets/js/modules/collection-tasks.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/tanli/Documents/php-code/sys-tmocaiji/resources/views/collection-tasks/index.blade.php ENDPATH**/ ?>