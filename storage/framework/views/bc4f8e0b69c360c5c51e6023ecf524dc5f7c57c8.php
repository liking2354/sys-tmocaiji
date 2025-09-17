<?php $__env->startSection('title', '仪表盘 - 服务器管理与数据采集系统'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <h1 class="mb-4">系统仪表盘</h1>
    
    <!-- 统计卡片 -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">服务器总数</h6>
                            <h2 class="mb-0"><?php echo e($serverCount); ?></h2>
                        </div>
                        <i class="fas fa-server fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <span>在线: <?php echo e($serverStatusStats['online']); ?></span>
                    <span>离线: <?php echo e($serverStatusStats['offline']); ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">服务器分组</h6>
                            <h2 class="mb-0"><?php echo e($groupCount); ?></h2>
                        </div>
                        <i class="fas fa-layer-group fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="<?php echo e(route('server-groups.index')); ?>" class="text-white">查看所有分组</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">采集组件</h6>
                            <h2 class="mb-0"><?php echo e($collectorCount); ?></h2>
                        </div>
                        <i class="fas fa-plug fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="<?php echo e(route('collectors.index')); ?>" class="text-white">查看所有组件</a>
                </div>
            </div>
        </div>
        

    </div>
    
    <!-- 系统信息 -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">系统信息</h5>
                </div>
                <div class="card-body">
                    <p>服务器管理与数据采集系统提供了对服务器和采集组件的全面管理功能。</p>
                    <p>您可以通过左侧菜单访问各项功能，或使用右侧的快速操作链接。</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">快速操作</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="<?php echo e(route('servers.create')); ?>" class="list-group-item list-group-item-action">
                            <i class="fas fa-plus-circle mr-2"></i> 添加服务器
                        </a>
                        <a href="<?php echo e(route('server-groups.create')); ?>" class="list-group-item list-group-item-action">
                            <i class="fas fa-folder-plus mr-2"></i> 创建服务器分组
                        </a>
                        <a href="<?php echo e(route('collectors.create')); ?>" class="list-group-item list-group-item-action">
                            <i class="fas fa-plug mr-2"></i> 添加采集组件
                        </a>
                        <a href="<?php echo e(route('data.cleanup.form')); ?>" class="list-group-item list-group-item-action">
                            <i class="fas fa-broom mr-2"></i> 数据清理
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/tanli/Documents/php-code/sys-tmocaiji/resources/views/dashboard.blade.php ENDPATH**/ ?>