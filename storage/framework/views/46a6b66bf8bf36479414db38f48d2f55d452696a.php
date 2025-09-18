<?php $__env->startSection('title', '数据清理 - 服务器管理与数据采集系统'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>数据清理</h1>
        <a href="<?php echo e(route('dashboard')); ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> 返回仪表盘
        </a>
    </div>
    
    <!-- 数据统计部分 - 第一层 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-chart-pie mr-2"></i>数据统计</h5>
                    <span class="badge badge-light">实时数据</span>
                </div>
                <div class="card-body py-4">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card mb-3 border-primary shadow-sm">
                                <div class="card-body text-center py-4">
                                    <div class="display-4 text-primary mb-2"><?php echo e(App\Models\Server::count()); ?></div>
                                    <p class="mb-0 text-muted"><i class="fas fa-server mr-1"></i>服务器总数</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card mb-3 border-success shadow-sm">
                                <div class="card-body text-center py-4">
                                    <div class="display-4 text-success mb-2"><?php echo e(App\Models\Collector::count()); ?></div>
                                    <p class="mb-0 text-muted"><i class="fas fa-puzzle-piece mr-1"></i>采集组件总数</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3 shadow-sm">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0"><i class="fas fa-database mr-2"></i>数据存储占用</h6>
                                    <small class="text-muted">单位: GB</small>
                                </div>
                                <div class="card-body">
                                    <canvas id="storageChart" height="120"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 清理条件部分 - 第二层 -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-filter mr-2"></i>清理条件</h5>
                    <button class="btn btn-sm btn-light" type="button" data-toggle="collapse" data-target="#cleanupConditions" aria-expanded="true">
                        <i class="fas fa-chevron-up"></i>
                    </button>
                </div>
                <div class="card-body collapse show" id="cleanupConditions">
                    <form action="<?php echo e(route('data.cleanup')); ?>" method="POST" id="cleanupForm">
                        <?php echo csrf_field(); ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-server mr-1"></i>选择服务器</label>
                                    <div class="card shadow-sm">
                                        <div class="card-header bg-light p-2 d-flex justify-content-between align-items-center">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAllServers">
                                                <label class="form-check-label font-weight-bold" for="selectAllServers">
                                                    全选/取消全选
                                                </label>
                                            </div>
                                            <span class="badge badge-primary server-count">0 已选择</span>
                                        </div>
                                        <div class="card-body p-2" style="max-height: 200px; overflow-y: auto;">
                                            <?php $__currentLoopData = $serverGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <div class="mb-2 server-group" data-group-id="<?php echo e($group->id); ?>">
                                                    <h6 class="d-flex justify-content-between align-items-center">
                                                        <span><?php echo e($group->name ?? '无分组'); ?> (<?php echo e($group->servers->count()); ?>)</span>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary toggle-group-servers" data-group-id="<?php echo e($group->id); ?>">
                                                            <i class="fas fa-chevron-down"></i>
                                                        </button>
                                                    </h6>
                                                    <div class="row group-servers-container" id="group-servers-<?php echo e($group->id); ?>" style="display: <?php echo e($loop->first ? 'flex' : 'none'); ?>;">
                                                        <?php if($group->servers->count() > 0): ?>
                                                            <?php $__currentLoopData = $group->servers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $server): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <div class="col-md-6">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input server-checkbox" type="checkbox" id="server_<?php echo e($server->id); ?>" name="server_ids[]" value="<?php echo e($server->id); ?>">
                                                                        <label class="form-check-label" for="server_<?php echo e($server->id); ?>">
                                                                            <?php echo e($server->name); ?>

                                                                            <small class="text-muted">(<?php echo e($server->ip); ?>)</small>
                                                                            <?php if($server->status == 1): ?>
                                                                                <span class="badge badge-success">在线</span>
                                                                            <?php else: ?>
                                                                                <span class="badge badge-danger">离线</span>
                                                                            <?php endif; ?>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        <?php else: ?>
                                                            <div class="col-12 text-center">
                                                                <span class="text-muted">该分组下没有服务器</span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                    </div>
                                    <?php $__errorArgs = ['server_ids'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="text-danger"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-puzzle-piece mr-1"></i>选择采集组件</label>
                                    <div class="card shadow-sm">
                                        <div class="card-header bg-light p-2 d-flex justify-content-between align-items-center">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAllCollectors">
                                                <label class="form-check-label font-weight-bold" for="selectAllCollectors">
                                                    全选/取消全选
                                                </label>
                                            </div>
                                            <span class="badge badge-success collector-count">0 已选择</span>
                                        </div>
                                        <div class="card-body p-2" style="max-height: 200px; overflow-y: auto;">
                                            <div class="row" id="collectors-container">
                                                <?php $__currentLoopData = $collectors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $collector): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <div class="col-md-6">
                                                        <div class="form-check">
                                                            <input class="form-check-input collector-checkbox" type="checkbox" id="collector_<?php echo e($collector->id); ?>" name="collector_ids[]" value="<?php echo e($collector->id); ?>">
                                                            <label class="form-check-label" for="collector_<?php echo e($collector->id); ?>">
                                                                <?php echo e($collector->name); ?>

                                                                <?php if($collector->status == 1): ?>
                                                                    <span class="badge badge-success">启用</span>
                                                                <?php else: ?>
                                                                    <span class="badge badge-secondary">禁用</span>
                                                                <?php endif; ?>
                                                            </label>
                                                        </div>
                                                    </div>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php $__errorArgs = ['collector_ids'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="text-danger"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-calendar-alt mr-1"></i>时间范围</label>
                                    <div class="card shadow-sm">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="start_date">开始日期</label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                                            </div>
                                                            <input type="date" class="form-control <?php $__errorArgs = ['start_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="start_date" name="start_date" value="<?php echo e(old('start_date')); ?>">
                                                        </div>
                                                        <?php $__errorArgs = ['start_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="end_date">结束日期</label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                                            </div>
                                                            <input type="date" class="form-control <?php $__errorArgs = ['end_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="end_date" name="end_date" value="<?php echo e(old('end_date')); ?>">
                                                        </div>
                                                        <?php $__errorArgs = ['end_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-warning mt-4 shadow-sm">
                                    <i class="fas fa-exclamation-triangle mr-2"></i> <strong>警告：</strong>数据清理操作不可恢复，请谨慎操作！
                                </div>
                                
                                <div class="form-group text-right mt-4">
                                    <button type="button" class="btn btn-danger btn-lg shadow-sm" id="cleanupBtn">
                                        <i class="fas fa-trash mr-2"></i> 清理数据
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 确认模态框 -->
<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="confirmModalLabel">确认清理数据</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> 警告：此操作将永久删除所选数据，无法恢复！
                </div>
                <p>您确定要清理以下数据吗？</p>
                <ul id="cleanupSummary">
                    <li>服务器：<span id="serverCount">0</span> 台</li>
                    <li>采集组件：<span id="collectorCount">0</span> 个</li>
                    <li>时间范围：<span id="dateRange">全部</span></li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-danger" id="confirmCleanupBtn">
                    <i class="fas fa-trash"></i> 确认清理
                </button>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
<script>
    $(document).ready(function() {
        // 初始化计数
        updateServerCount();
        updateCollectorCount();
        updateDateRange();
        
        // 服务器全选/取消全选
        $('#selectAllServers').change(function() {
            $('.server-checkbox').prop('checked', $(this).prop('checked'));
            updateServerCount();
        });
        
        // 采集组件全选/取消全选
        $('#selectAllCollectors').change(function() {
            $('.collector-checkbox').prop('checked', $(this).prop('checked'));
            updateCollectorCount();
        });
        
        // 动态绑定单个服务器复选框变化事件
        $(document).on('change', '.server-checkbox', function() {
            updateServerCount();
            updateServerSelectAllState();
        });
        
        // 动态绑定单个采集组件复选框变化事件
        $(document).on('change', '.collector-checkbox', function() {
            updateCollectorCount();
            updateCollectorSelectAllState();
        });
        
        // 更新服务器全选状态
        function updateServerSelectAllState() {
            var allChecked = $('.server-checkbox').length > 0 && 
                             $('.server-checkbox').length === $('.server-checkbox:checked').length;
            $('#selectAllServers').prop('checked', allChecked);
        }
        
        // 更新采集组件全选状态
        function updateCollectorSelectAllState() {
            var allChecked = $('.collector-checkbox').length > 0 && 
                             $('.collector-checkbox').length === $('.collector-checkbox:checked').length;
            $('#selectAllCollectors').prop('checked', allChecked);
        }
        
        // 更新服务器计数
        function updateServerCount() {
            var count = $('.server-checkbox:checked').length;
            $('.server-count').text(count + ' 已选择');
            $('#serverCount').text(count);
        }
        
        // 更新采集组件计数
        function updateCollectorCount() {
            var count = $('.collector-checkbox:checked').length;
            $('.collector-count').text(count + ' 已选择');
            $('#collectorCount').text(count);
        }
        
        // 更新日期范围显示
        function updateDateRange() {
            var startDate = $('#start_date').val();
            var endDate = $('#end_date').val();
            
            if (startDate && endDate) {
                $('#dateRange').text(startDate + ' 至 ' + endDate);
            } else if (startDate) {
                $('#dateRange').text(startDate + ' 至 现在');
            } else if (endDate) {
                $('#dateRange').text('全部 至 ' + endDate);
            } else {
                $('#dateRange').text('全部');
            }
        }
        
        // 日期输入框变化时更新日期范围
        $('#start_date, #end_date').change(function() {
            updateDateRange();
        });
        
        // 服务器组展开/收起功能
        $('.toggle-group-servers').click(function() {
            var groupId = $(this).data('group-id');
            var container = $('#group-servers-' + groupId);
            var icon = $(this).find('i');
            
            if (container.is(':visible')) {
                container.hide();
                icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
            } else {
                container.show();
                icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
            }
        });
        
        // 清理按钮点击事件
        $('#cleanupBtn').click(function() {
            if ($('.server-checkbox:checked').length === 0) {
                alert('请至少选择一个服务器');
                return;
            }
            
            if ($('.collector-checkbox:checked').length === 0) {
                alert('请至少选择一个采集组件');
                return;
            }
            
            updateServerCount();
            updateCollectorCount();
            updateDateRange();
            $('#confirmModal').modal('show');
        });
        
        // 确认清理按钮点击事件
        $('#confirmCleanupBtn').click(function() {
            $('#cleanupForm').submit();
        });
        
        // 数据存储占用图表
        var storageCtx = document.getElementById('storageChart').getContext('2d');
        var storageChart = new Chart(storageCtx, {
            type: 'pie',
            data: {
                labels: ['系统进程', '环境变量', 'Nginx配置', 'PHP配置'],
                datasets: [{
                    label: '数据存储占用',
                    data: [35, 15, 25, 25],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/tanli/Documents/php-code/sys-tmocaiji/resources/views/data/cleanup.blade.php ENDPATH**/ ?>