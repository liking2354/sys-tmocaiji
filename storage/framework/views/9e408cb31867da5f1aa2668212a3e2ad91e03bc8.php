<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>权限管理</span>
                        <a href="<?php echo e(route('admin.permissions.create')); ?>" class="btn btn-primary btn-sm">添加权限</a>
                    </div>
                </div>

                <div class="card-body">
                    <?php if(session('success')): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo e(session('success')); ?>

                        </div>
                    <?php endif; ?>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>名称</th>
                                <th>标识</th>
                                <th>模块</th>
                                <th>描述</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $permissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $permission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($permission->id); ?></td>
                                    <td><?php echo e($permission->name); ?></td>
                                    <td><?php echo e($permission->slug); ?></td>
                                    <td><?php echo e($permission->module); ?></td>
                                    <td><?php echo e($permission->description); ?></td>
                                    <td>
                                        <a href="<?php echo e(route('admin.permissions.edit', $permission->id)); ?>" class="btn btn-sm btn-info">编辑</a>
                                        <form action="<?php echo e(route('admin.permissions.destroy', $permission->id)); ?>" method="POST" style="display: inline-block;">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('确定要删除该权限吗？')">删除</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>

                    <div class="d-flex justify-content-center">
                        <?php echo e($permissions->links()); ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/tanli/Documents/php-code/sys-tmocaiji/resources/views/admin/permissions/index.blade.php ENDPATH**/ ?>