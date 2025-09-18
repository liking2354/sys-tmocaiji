<?php $__env->startSection('title', '服务器分组 - 服务器管理与数据采集系统'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>服务器分组管理</h1>
        <a href="<?php echo e(route('server-groups.create')); ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> 新建分组
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>分组名称</th>
                            <th>描述</th>
                            <th>服务器数量</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e($group->id); ?></td>
                                <td><?php echo e($group->name); ?></td>
                                <td><?php echo e($group->description); ?></td>
                                <td><?php echo e($group->servers_count); ?></td>
                                <td><?php echo e($group->created_at->format('Y-m-d H:i')); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="<?php echo e(route('server-groups.show', $group)); ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> 查看
                                        </a>
                                        <a href="<?php echo e(route('server-groups.edit', $group)); ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i> 编辑
                                        </a>
                                        <form action="<?php echo e(route('server-groups.destroy', $group)); ?>" method="POST" class="d-inline" onsubmit="return confirm('确定要删除该分组吗？')">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i> 删除
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="6" class="text-center py-3">暂无服务器分组</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-center mt-3">
                <?php echo e($groups->links()); ?>

            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/tanli/Documents/php-code/sys-tmocaiji/resources/views/server-groups/index.blade.php ENDPATH**/ ?>