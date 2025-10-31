<?php $__env->startSection('title', '采集组件 - 服务器管理与数据采集系统'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0">
                <i class="fas fa-cube text-primary"></i> 采集组件管理
            </h1>
            <small class="text-muted">管理和配置数据采集组件</small>
        </div>
        <a href="<?php echo e(route('collectors.create')); ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> 新建采集组件
        </a>
    </div>
    
    <!-- 搜索和筛选卡片 -->
    <div class="search-filter-card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-filter"></i> 搜索和筛选
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="<?php echo e(route('collectors.index')); ?>">
                <div class="search-row">
                    <div>
                        <label for="name">组件名称</label>
                        <input type="text" name="name" class="form-control" id="name" placeholder="搜索组件名称..." 
                               value="<?php echo e(request('name')); ?>">
                    </div>
                    <div>
                        <label for="code">组件代码</label>
                        <input type="text" name="code" class="form-control" id="code" placeholder="搜索组件代码..." 
                               value="<?php echo e(request('code')); ?>">
                    </div>
                </div>
                <div class="button-row">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> 搜索
                    </button>
                    <a href="<?php echo e(route('collectors.index')); ?>" class="btn btn-secondary">
                        <i class="fas fa-sync"></i> 重置
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-light-blue shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list"></i> 采集组件列表
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>组件名称</th>
                            <th>组件代码</th>
                            <th style="width: 80px;">类型</th>
                            <th>描述</th>
                            <th style="width: 80px;">状态</th>
                            <th>创建时间</th>
                            <th style="width: 120px;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $collectors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $collector): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><span class="badge badge-light"><?php echo e($collector->id); ?></span></td>
                                <td><strong><?php echo e($collector->name); ?></strong></td>
                                <td><code><?php echo e($collector->code); ?></code></td>
                                <td>
                                    <span class="badge badge-<?php echo e($collector->type === 'script' ? 'primary' : 'secondary'); ?>">
                                        <?php echo e($collector->typeName); ?>

                                    </span>
                                </td>
                                <td><small class="text-muted"><?php echo e(Str::limit($collector->description, 50)); ?></small></td>
                                <td>
                                    <?php if($collector->status == 1): ?>
                                        <span class="badge badge-success"><i class="fas fa-check-circle"></i> 启用</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><i class="fas fa-times-circle"></i> 禁用</span>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?php echo e($collector->created_at->format('Y-m-d H:i')); ?></small></td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="<?php echo e(route('collectors.show', $collector)); ?>" class="btn btn-primary" title="查看详情">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?php echo e(route('collectors.edit', $collector)); ?>" class="btn btn-primary" title="编辑">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger" onclick="deleteCollector(<?php echo e($collector->id); ?>)" title="删除">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-inbox text-muted" style="font-size: 2rem;"></i>
                                    <p class="text-muted mt-2">暂无采集组件</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-center mt-3 pb-3">
                <?php echo e($collectors->links()); ?>

            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="<?php echo e(asset('assets/js/common/delete-handler.js')); ?>"></script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/tanli/Documents/php-code/sys-tmocaiji/resources/views/collectors/index.blade.php ENDPATH**/ ?>