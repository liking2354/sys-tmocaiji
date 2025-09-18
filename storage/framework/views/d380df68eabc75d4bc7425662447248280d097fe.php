<?php $__env->startSection('title', '采集组件 - 服务器管理与数据采集系统'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>采集组件管理</h1>
        <a href="<?php echo e(route('collectors.create')); ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> 新建采集组件
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>组件名称</th>
                            <th>组件代码</th>
                            <th>类型</th>
                            <th>描述</th>
                            <th>状态</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $collectors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $collector): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e($collector->id); ?></td>
                                <td><?php echo e($collector->name); ?></td>
                                <td><code><?php echo e($collector->code); ?></code></td>
                                <td>
                                    <span class="badge badge-<?php echo e($collector->type === 'script' ? 'info' : 'warning'); ?>">
                                        <?php echo e($collector->typeName); ?>

                                    </span>
                                </td>
                                <td><?php echo e(Str::limit($collector->description, 50)); ?></td>
                                <td>
                                    <?php if($collector->status == 1): ?>
                                        <span class="badge badge-success">启用</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">禁用</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($collector->created_at->format('Y-m-d H:i')); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="<?php echo e(route('collectors.show', $collector)); ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> 查看
                                        </a>
                                        <a href="<?php echo e(route('collectors.edit', $collector)); ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i> 编辑
                                        </a>
                                        <form action="<?php echo e(route('collectors.destroy', $collector)); ?>" method="POST" class="d-inline" onsubmit="return confirm('确定要删除该采集组件吗？')">
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
                                <td colspan="8" class="text-center py-3">暂无采集组件</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-center mt-3">
                <?php echo e($collectors->links()); ?>

            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/tanli/Documents/php-code/sys-tmocaiji/resources/views/collectors/index.blade.php ENDPATH**/ ?>