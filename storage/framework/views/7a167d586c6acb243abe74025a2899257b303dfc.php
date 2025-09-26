<?php $__env->startSection('title', '服务器管理 - 服务器管理与数据采集系统'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>服务器管理</h1>
        <div>
            <a href="<?php echo e(route('servers.create')); ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> 添加服务器
            </a>
            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#importModal">
                <i class="fas fa-file-import"></i> 批量导入
            </button>
            <button type="button" class="btn btn-warning" id="batchCollectionBtn" disabled>
                <i class="fas fa-play"></i> 批量采集
            </button>
            <button type="button" class="btn btn-info" id="batchModifyComponentsBtn" disabled>
                <i class="fas fa-cogs"></i> 批量修改组件
            </button>
            <button type="button" class="btn btn-success" id="downloadBtn" disabled>
                <i class="fas fa-download"></i> 直接下载
            </button>
        </div>
    </div>
    
    <!-- 搜索和筛选 -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="<?php echo e(route('servers.index')); ?>" method="GET" class="form-row align-items-center">
                <div class="col-md-3 mb-2">
                    <label for="search">搜索</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="服务器名称或IP" value="<?php echo e(request('search')); ?>">
                </div>
                <div class="col-md-3 mb-2">
                    <label for="group_id">服务器分组</label>
                    <select class="form-control" id="group_id" name="group_id">
                        <option value="">所有分组</option>
                        <?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($group->id); ?>" <?php echo e(request('group_id') == $group->id ? 'selected' : ''); ?>><?php echo e($group->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label for="status">服务器状态</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">全部状态</option>
                        <option value="1" <?php echo e(request('status') == '1' ? 'selected' : ''); ?>>在线</option>
                        <option value="0" <?php echo e(request('status') == '0' ? 'selected' : ''); ?>>离线</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2 align-self-end">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-search"></i> 搜索
                    </button>
                </div>
                <div class="col-md-2 mb-2 align-self-end">
                    <a href="<?php echo e(route('servers.index')); ?>" class="btn btn-secondary btn-block">
                        <i class="fas fa-sync"></i> 重置
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 服务器列表 -->
    <div class="card">
        <div class="card-body">
                <?php echo csrf_field(); ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                    </div>
                                </th>
                                <th>ID</th>
                                <th>名称</th>
                                <th>分组</th>
                                <th>IP地址</th>
                                <th>端口</th>
                                <th>状态</th>
                                <th>最后检查时间</th>
                                <th>最后采集时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $servers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $server): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input server-checkbox" type="checkbox" name="server_ids[]" value="<?php echo e($server->id); ?>">
                                        </div>
                                    </td>
                                    <td><?php echo e($server->id); ?></td>
                                    <td><?php echo e($server->name); ?></td>
                                    <td><?php echo e($server->group->name ?? '无分组'); ?></td>
                                    <td><?php echo e($server->ip); ?></td>
                                    <td><?php echo e($server->port); ?></td>
                                    <td>
                                        <?php if($server->status == 1): ?>
                                            <span class="badge badge-success">在线</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">离线</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo e($server->last_check_time ? $server->last_check_time->format('Y-m-d H:i') : '未检查'); ?></td>
                                    <td><?php echo e($server->lastCollectionTime ? $server->lastCollectionTime->format('Y-m-d H:i') : '未采集'); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="<?php echo e(route('servers.show', $server)); ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> 查看
                                            </a>
                                            <a href="<?php echo e(route('servers.edit', $server)); ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i> 编辑
                                            </a>
                                            <form action="<?php echo e(route('servers.destroy', $server)); ?>" method="POST" class="d-inline" onsubmit="return confirm('确定要删除该服务器吗？')">
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
                                    <td colspan="10" class="text-center py-3">暂无服务器</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            
            <div class="d-flex justify-content-center mt-3">
                <?php echo e($servers->links()); ?>

            </div>
        </div>
    </div>
</div>

<!-- 导入模态框 -->
<div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">批量导入服务器</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?php echo e(route('servers.import')); ?>" method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="file">选择Excel文件</label>
                        <input type="file" class="form-control-file" id="file" name="file" required accept=".xlsx,.xls,.csv">
                    </div>
                    <div class="alert alert-info">
                        <h6>导入说明：</h6>
                        <p>请使用以下列标题的Excel文件：</p>
                        <ul>
                            <li>name - 服务器名称（必填）</li>
                            <li>group - 服务器分组（选填，不存在则自动创建）</li>
                            <li>ip - IP地址（必填）</li>
                            <li>port - 端口（选填，默认22）</li>
                            <li>username - 用户名（必填）</li>
                            <li>password - 密码（必填）</li>
                            <li>verify_connection - 是否验证连接（选填，默认true）</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">导入</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 批量修改组件模态框 -->
<div class="modal fade" id="batchModifyComponentsModal" tabindex="-1" role="dialog" aria-labelledby="batchModifyComponentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="batchModifyComponentsModalLabel">批量修改组件</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="batchModifyComponentsForm" action="<?php echo e(route('servers.batch-modify-components')); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" id="selected_server_ids_modify" name="server_ids" value="">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        已选择 <span id="selectedServerCountModify">0</span> 个服务器，请选择要关联的采集组件：
                    </div>
                    
                    <div class="form-group">
                        <label>操作类型：</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="operation_type" id="operationReplace" value="replace" checked>
                            <label class="form-check-label" for="operationReplace">
                                <strong>替换</strong> - 清除现有关联，只保留选中的组件
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="operation_type" id="operationAdd" value="add">
                            <label class="form-check-label" for="operationAdd">
                                <strong>添加</strong> - 在现有关联基础上添加选中的组件
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="operation_type" id="operationRemove" value="remove">
                            <label class="form-check-label" for="operationRemove">
                                <strong>移除</strong> - 从现有关联中移除选中的组件
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>采集组件：</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="selectAllComponents">
                            <label class="form-check-label" for="selectAllComponents">
                                <strong>全选/取消全选</strong>
                            </label>
                        </div>
                        <div class="row" id="componentsContainer">
                            <!-- 采集组件列表将通过AJAX加载 -->
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" id="submitBatchModifyComponents">
                    <i class="fas fa-save"></i> 确认修改
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 批量采集模态框 -->
<div class="modal fade" id="batchCollectionModal" tabindex="-1" role="dialog" aria-labelledby="batchCollectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="batchCollectionModalLabel">批量采集</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="batchCollectionForm" action="<?php echo e(route('collection-tasks.batch.execute')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="task_name">任务名称</label>
                        <input type="text" class="form-control" id="task_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="task_description">任务描述</label>
                        <textarea class="form-control" id="task_description" name="description" rows="2" placeholder="可选"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>选中的服务器 (<span id="selectedServerCount">0</span> 台)</label>
                        <div id="selectedServerList" class="border rounded p-2 bg-light" style="max-height: 150px; overflow-y: auto;">
                            <div class="text-muted">请先选择服务器</div>
                        </div>
                        <input type="hidden" id="selected_server_ids" name="server_ids">
                    </div>
                    
                    <div class="form-group">
                        <label>采集组件</label>
                        <div id="collectorsList">
                            <div class="text-muted">
                                <i class="fas fa-spinner fa-spin"></i> 正在加载共同的采集组件...
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary" id="submitBatchCollection" disabled>
                        <i class="fas fa-play"></i> 开始采集
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
    $(document).ready(function() {
        // 全选/取消全选
        $('#selectAll').change(function() {
            $('.server-checkbox').prop('checked', $(this).prop('checked'));
            updateButtonStates();
        });
        
        // 单个复选框变化时更新按钮状态
        $('.server-checkbox').change(function() {
            updateButtonStates();
        });
        
        // 更新按钮状态的函数
        function updateButtonStates() {
            var checkedCount = $('.server-checkbox:checked').length;
            $('#downloadBtn').prop('disabled', checkedCount === 0);
            $('#batchCollectionBtn').prop('disabled', checkedCount === 0);
            $('#batchModifyComponentsBtn').prop('disabled', checkedCount === 0);
            
            // 更新全选框状态
            var allChecked = checkedCount === $('.server-checkbox').length;
            $('#selectAll').prop('checked', allChecked && checkedCount > 0);
        }
        
        // 直接下载按钮点击事件
        $('#downloadBtn').click(function(e) {
            e.preventDefault();

            var serverIds = $('.server-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (serverIds.length === 0) {
                alert('请至少选择一台服务器');
                return false;
            }
            
            // 创建临时表单（推荐方案）
            var tempForm = $('<form>', {
                action: '<?php echo e(route("servers.download")); ?>',
                method: 'POST',
                style: 'display: none;',
                target: '_blank' // 在新窗口打开下载，避免影响当前页面
            });
            
            // 添加CSRF令牌
            tempForm.append('<input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">');
            
            // 添加服务器ID
            serverIds.forEach(function(serverId) {
                tempForm.append('<input type="hidden" name="server_ids[]" value="' + serverId + '">');
            });
            
            // 添加到body并提交
            $('body').append(tempForm);
            tempForm.submit();
            
            // 稍后移除临时表单
            setTimeout(function() {
                tempForm.remove();
            }, 1000);
        });
        
        // 批量采集按钮点击事件
        $('#batchCollectionBtn').click(function() {
            // 获取选中的服务器ID
            var checkedBoxes = $('.server-checkbox:checked');
            if (checkedBoxes.length > 0) {
                var serverIds = [];
                var serverList = '';
                checkedBoxes.each(function() {
                    var row = $(this).closest('tr');
                    var serverId = $(this).val();
                    var serverName = row.find('td:nth-child(3)').text();
                    var serverIp = row.find('td:nth-child(5)').text();
                    
                    serverIds.push(serverId);
                    serverList += '<div class="badge badge-info mr-1 mb-1">' + serverName + ' (' + serverIp + ')</div>';
                });
                
                $('#selectedServerCount').text(serverIds.length);
                $('#selectedServerList').html(serverList);
                // 将server_ids存储为数组，而不是JSON字符串
                $('#selected_server_ids').val(serverIds.join(','));
                
                // 加载共同的采集组件
                loadCommonCollectors(serverIds);
                
                // 生成默认任务名称
                var now = new Date();
                var defaultName = '批量采集任务_' + now.getFullYear() + 
                    String(now.getMonth() + 1).padStart(2, '0') + 
                    String(now.getDate()).padStart(2, '0') + '_' + 
                    String(now.getHours()).padStart(2, '0') + 
                    String(now.getMinutes()).padStart(2, '0');
                $('#task_name').val(defaultName);
                
                $('#batchCollectionModal').modal('show');
            } else {
                toastr.warning('请先选择要执行采集的服务器');
            }
        });
        
        // 批量修改组件按钮点击事件
        $('#batchModifyComponentsBtn').click(function() {
            var checkedBoxes = $('.server-checkbox:checked');
            if (checkedBoxes.length > 0) {
                var serverIds = [];
                checkedBoxes.each(function() {
                    serverIds.push($(this).val());
                });
                
                $('#selectedServerCountModify').text(serverIds.length);
                $('#selected_server_ids_modify').val(serverIds.join(','));
                
                // 加载所有采集组件
                loadAllComponents();
                
                $('#batchModifyComponentsModal').modal('show');
            } else {
                alert('请先选择要修改组件的服务器');
            }
        });
        
        // 加载所有采集组件
        function loadAllComponents() {
            $('#componentsContainer').html('<div class="col-12"><div class="text-muted"><i class="fas fa-spinner fa-spin"></i> 正在加载采集组件...</div></div>');
            
            $.ajax({
                url: '<?php echo e(route("api.collectors.all")); ?>',
                type: 'GET',
                headers: {
                    'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                    'Accept': 'application/json'
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        var html = '';
                        response.data.forEach(function(collector) {
                            html += '<div class="col-md-6 mb-2">';
                            html += '<div class="form-check">';
                            html += '<input class="form-check-input component-checkbox" type="checkbox" name="collector_ids[]" value="' + collector.id + '" id="component_' + collector.id + '">';
                            html += '<label class="form-check-label" for="component_' + collector.id + '">';
                            html += '<strong>' + collector.name + '</strong> (' + collector.code + ')';
                            if (collector.description) {
                                html += '<br><small class="text-muted">' + collector.description + '</small>';
                            }
                            html += '</label>';
                            html += '</div>';
                            html += '</div>';
                        });
                        $('#componentsContainer').html(html);
                        
                        // 监听组件选择变化
                        $('.component-checkbox').change(function() {
                            updateSelectAllComponents();
                        });
                        
                        // 全选/取消全选功能
                        $('#selectAllComponents').change(function() {
                            $('.component-checkbox').prop('checked', $(this).prop('checked'));
                        });
                        
                    } else {
                        $('#componentsContainer').html('<div class="col-12"><div class="alert alert-warning">没有可用的采集组件</div></div>');
                    }
                },
                error: function(xhr) {
                    $('#componentsContainer').html('<div class="col-12"><div class="alert alert-danger">加载采集组件失败：' + xhr.responseText + '</div></div>');
                }
            });
        }
        
        // 更新全选组件复选框状态
        function updateSelectAllComponents() {
            var totalComponents = $('.component-checkbox').length;
            var checkedComponents = $('.component-checkbox:checked').length;
            $('#selectAllComponents').prop('checked', totalComponents > 0 && checkedComponents === totalComponents);
        }
        
        // 批量修改组件表单提交
        $('#submitBatchModifyComponents').click(function() {
            var checkedComponents = $('.component-checkbox:checked').length;
            var operationType = $('input[name="operation_type"]:checked').val();
            
            if (operationType !== 'remove' && checkedComponents === 0) {
                alert('请至少选择一个采集组件');
                return;
            }
            
            if (operationType === 'remove' && checkedComponents === 0) {
                alert('移除操作需要选择要移除的组件');
                return;
            }
            
            var btn = $(this);
            var originalText = btn.html();
            btn.html('<i class="fas fa-spinner fa-spin"></i> 处理中...').prop('disabled', true);
            
            var formData = $('#batchModifyComponentsForm').serialize();
            
            $.ajax({
                url: $('#batchModifyComponentsForm').attr('action'),
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                    'Accept': 'application/json'
                },
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $('#batchModifyComponentsModal').modal('hide');
                        alert('批量修改组件成功！' + response.message);
                        // 可选：刷新页面或更新显示
                        location.reload();
                    } else {
                        alert('修改失败：' + response.message);
                    }
                    btn.html(originalText).prop('disabled', false);
                },
                error: function(xhr) {
                    var errorMessage = '请求失败';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    alert('修改失败：' + errorMessage);
                    btn.html(originalText).prop('disabled', false);
                }
            });
        });
        
        // 加载共同的采集组件
        function loadCommonCollectors(serverIds) {
            $('#collectorsList').html('<div class="text-muted"><i class="fas fa-spinner fa-spin"></i> 正在加载共同的采集组件...</div>');
            $('#submitBatchCollection').prop('disabled', true);
            
            $.ajax({
                url: '<?php echo e(route("api.servers.common-collectors")); ?>',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                    'Accept': 'application/json'
                },
                data: {
                    server_ids: serverIds
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        var html = '';
                        response.data.forEach(function(collector) {
                            html += '<div class="form-check">';
                            html += '<input class="form-check-input collector-checkbox" type="checkbox" name="collector_ids[]" value="' + collector.id + '" id="collector_' + collector.id + '">';
                            html += '<label class="form-check-label" for="collector_' + collector.id + '">';
                            html += collector.name + ' (' + collector.code + ')';
                            if (collector.description) {
                                html += '<br><small class="text-muted">' + collector.description + '</small>';
                            }
                            html += '</label>';
                            html += '</div>';
                        });
                        $('#collectorsList').html(html);
                        
                        // 监听采集组件选择变化
                        $('.collector-checkbox').change(function() {
                            var checkedCollectors = $('.collector-checkbox:checked').length;
                            $('#submitBatchCollection').prop('disabled', checkedCollectors === 0);
                        });
                    } else {
                        // 没有共同采集组件时，显示所有可用采集组件供统一选择
                        $('#collectorsList').html('<div class="alert alert-warning mb-3">所选服务器没有共同的采集组件，您可以在下方选择采集组件进行批量关联</div>');
                        
                        // 加载所有采集组件
                        $.ajax({
                            url: '<?php echo e(route("api.collectors.all")); ?>',
                            type: 'GET',
                            headers: {
                                'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                                'Accept': 'application/json'
                            },
                            success: function(response) {
                                if (response.success && response.data.length > 0) {
                                    var html = '<div class="form-group">';
                                    html += '<div class="custom-control custom-checkbox mb-2">';
                                    html += '<input type="checkbox" class="custom-control-input" id="linkCollectors" name="link_collectors" checked>';
                                    html += '<label class="custom-control-label" for="linkCollectors">将选择的采集组件关联到未安装该组件的服务器</label>';
                                    html += '</div>';
                                    html += '</div>';
                                    
                                    html += '<div class="form-group">';
                                    html += '<label>可用采集组件：</label>';
                                    response.data.forEach(function(collector) {
                                        html += '<div class="form-check">';
                                        html += '<input class="form-check-input collector-checkbox" type="checkbox" name="collector_ids[]" value="' + collector.id + '" id="collector_' + collector.id + '">';
                                        html += '<label class="form-check-label" for="collector_' + collector.id + '">';
                                        html += collector.name + ' (' + collector.code + ')';
                                        if (collector.description) {
                                            html += '<br><small class="text-muted">' + collector.description + '</small>';
                                        }
                                        html += '</label>';
                                        html += '</div>';
                                    });
                                    html += '</div>';
                                    
                                    $('#collectorsList').append(html);
                                    
                                    // 监听采集组件选择变化
                                    $('.collector-checkbox').change(function() {
                                        var checkedCollectors = $('.collector-checkbox:checked').length;
                                        $('#submitBatchCollection').prop('disabled', checkedCollectors === 0);
                                    });
                                } else {
                                    $('#collectorsList').append('<div class="alert alert-danger">没有可用的采集组件</div>');
                                }
                            },
                            error: function(xhr) {
                                $('#collectorsList').append('<div class="alert alert-danger">加载采集组件失败：' + xhr.responseText + '</div>');
                            }
                        });
                    }
                },
                error: function(xhr) {
                    $('#collectorsList').html('<div class="alert alert-danger">加载采集组件失败：' + xhr.responseText + '</div>');
                }
            });
        }
        
        // 批量采集表单提交
        $('#batchCollectionForm').submit(function(e) {
            e.preventDefault();
            
            var checkedCollectors = $('.collector-checkbox:checked').length;
            if (checkedCollectors === 0) {
                alert('请至少选择一个采集组件');
                return;
            }
            
            var formData = $(this).serialize();
            var btn = $('#submitBatchCollection');
            var originalText = btn.html();
            var serverIds = $('#selected_server_ids').val().split(',');
            
            btn.html('<i class="fas fa-spinner fa-spin"></i> 创建中...').prop('disabled', true);
            
            // 检查是否需要关联采集组件
            var linkCollectors = $('#linkCollectors').is(':checked');
            
            if (linkCollectors) {
                // 先关联采集组件，再开始采集
                var collectorIds = [];
                $('.collector-checkbox:checked').each(function() {
                    collectorIds.push($(this).val());
                });
                
                $.ajax({
                    url: '<?php echo e(route("api.servers.batch-associate-collectors")); ?>',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                        'Accept': 'application/json'
                    },
                    data: {
                        server_ids: serverIds,
                        collector_ids: collectorIds
                    },
                    success: function(response) {
                        if (response.success) {
                            // 关联成功后开始采集
                            startBatchCollection();
                        } else {
                            alert('关联采集组件失败：' + response.message);
                            btn.html(originalText).prop('disabled', false);
                        }
                    },
                    error: function(xhr) {
                        var errorMsg = '关联采集组件失败';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        alert(errorMsg);
                        btn.html(originalText).prop('disabled', false);
                    }
                });
            } else {
                // 直接开始采集
                startBatchCollection();
            }
            
            function startBatchCollection() {
                $.ajax({
                    url: $('#batchCollectionForm').attr('action'),
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#batchCollectionModal').modal('hide');
                            alert('批量采集任务创建成功！正在后台执行...');
                            // 跳转到任务详情页面
                            window.location.href = '<?php echo e(route("collection-tasks.show", ":id")); ?>'.replace(':id', response.data.id);
                        } else {
                            alert('创建失败：' + response.message);
                            btn.html(originalText).prop('disabled', false);
                        }
                    },
                    error: function(xhr) {
                        var errorMessage = '请求失败';
                        if (xhr.responseJSON) {
                            errorMessage = xhr.responseJSON.message || xhr.responseJSON.error || errorMessage;
                        } else if (xhr.responseText) {
                            errorMessage = xhr.responseText;
                        }
                        alert(errorMessage);
                        btn.html(originalText).prop('disabled', false);
                    }
                });
            }
        });
        
        // 初始化按钮状态
        updateButtonStates();
    });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/tanli/Documents/php-code/sys-tmocaiji/resources/views/servers/index.blade.php ENDPATH**/ ?>