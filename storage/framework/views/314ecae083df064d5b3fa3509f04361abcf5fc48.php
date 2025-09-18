<?php $__env->startSection('title', '采集任务管理 - 服务器管理与数据采集系统'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>采集任务管理</h1>
        <div>
            <a href="<?php echo e(route('servers.index')); ?>" class="btn btn-primary mr-2">
                <i class="fas fa-plus"></i> 去服务器页面创建批量任务
            </a>
            <button type="button" class="btn btn-success" id="batchExecuteBtn">
                <i class="fas fa-play"></i> 立即执行批量任务
            </button>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 使用JavaScript设置进度条宽度
            <?php
            foreach($tasks as $task) {
                echo "(function() {";
                echo "    var progressBar = document.querySelector('.task-progress-bar-".$task->id."');";
                echo "    if (progressBar) {";
                echo "        progressBar.style.width = '".$task->progress."%';";
                echo "    }";
                echo "})();";
            }
            ?>
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
                                <td colspan="9" class="text-center py-3">暂无采集任务</td>
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

<?php $__env->startSection('scripts'); ?>
<script>
$(document).ready(function() {
    // 自动刷新进行中的任务
    setInterval(function() {
        if ($('.badge-warning').length > 0) {
            location.reload();
        }
    }, 5000); // 每5秒刷新一次
    
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
                        alert("任务重试已启动！");
                        location.reload();
                    } else {
                        alert("重试失败：" + response.message);
                    }
                },
                error: function(xhr) {
                    alert("请求失败：" + (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : xhr.responseText));
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
                        alert("任务已取消！");
                        location.reload();
                    } else {
                        alert("取消失败：" + response.message);
                    }
                },
                error: function(xhr) {
                    alert("请求失败：" + (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : xhr.responseText));
                }
            });
        }
    };
    
    // 手动触发批量任务
    window.triggerBatchTask = function(taskId) {
        if (confirm("确定要手动触发执行此批量任务吗？")) {
            $.ajax({
                url: "<?php echo e(route('collection-tasks.trigger-batch', ':id')); ?>".replace(":id", taskId),
                type: "POST",
                data: {
                    _token: "<?php echo e(csrf_token()); ?>"
                },
                success: function(response) {
                    if (response.success) {
                        alert("批量任务已开始执行！");
                        location.reload();
                    } else {
                        alert("触发失败：" + response.message);
                    }
                },
                error: function(xhr) {
                    alert("请求失败：" + (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : xhr.responseText));
                }
            });
        }
    };
    
    // 立即执行批量任务按钮点击事件
    $(document).ready(function() {
        $("#batchExecuteBtn").click(function() {
            // 获取所有未执行的批量任务
            var pendingBatchTasks = [];
            $(".task-row").each(function() {
                var taskId = $(this).data("task-id");
                var taskType = $(this).data("task-type");
                var taskStatus = $(this).data("task-status");
                
                if (taskType !== 'single' && taskStatus === 0) {
                    pendingBatchTasks.push({
                        id: taskId,
                        name: $(this).data("task-name")
                    });
                }
            });
            
            if (pendingBatchTasks.length === 0) {
                alert("没有找到可执行的批量任务！");
                return;
            }
            
            // 显示任务选择对话框
            var taskOptions = "";
            pendingBatchTasks.forEach(function(task) {
                taskOptions += '<option value="' + task.id + '">' + task.name + '</option>';
            });
            
            var selectDialog = '<div class="modal fade" id="selectTaskModal" tabindex="-1" role="dialog">' +
                '<div class="modal-dialog" role="document">' +
                '<div class="modal-content">' +
                '<div class="modal-header">' +
                '<h5 class="modal-title">选择要执行的批量任务</h5>' +
                '<button type="button" class="close" data-dismiss="modal" aria-label="Close">' +
                '<span aria-hidden="true">&times;</span>' +
                '</button>' +
                '</div>' +
                '<div class="modal-body">' +
                '<select class="form-control" id="taskSelect">' +
                taskOptions +
                '</select>' +
                '</div>' +
                '<div class="modal-footer">' +
                '<button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>' +
                '<button type="button" class="btn btn-primary" id="confirmExecuteBtn">确认执行</button>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>';
            
            $("body").append(selectDialog);
            $("#selectTaskModal").modal("show");
            
            // 确认执行按钮点击事件
            $("#confirmExecuteBtn").click(function() {
                var selectedTaskId = $("#taskSelect").val();
                if (selectedTaskId) {
                    $("#selectTaskModal").modal("hide");
                    triggerBatchTask(selectedTaskId);
                }
            });
            
            // 模态框关闭后移除
            $("#selectTaskModal").on("hidden.bs.modal", function() {
                $(this).remove();
            });
        });
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/tanli/Documents/php-code/sys-tmocaiji/resources/views/collection-tasks/index.blade.php ENDPATH**/ ?>