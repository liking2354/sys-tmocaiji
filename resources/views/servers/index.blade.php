@extends('layouts.app')

@section('title', '服务器管理 - 服务器管理与数据采集系统')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0">
                <i class="fas fa-server text-primary"></i> 服务器管理
            </h1>
            <small class="text-muted">管理和监控所有服务器</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('servers.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> 添加服务器
            </a>
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#batchImportModal">
                <i class="fas fa-file-import"></i> 批量导入
            </button>
            <button type="button" class="btn btn-primary btn-sm" id="batchCollectionBtn" disabled>
                <i class="fas fa-play"></i> 批量采集
            </button>
            <button type="button" class="btn btn-primary btn-sm" id="batchModifyComponentsBtn" disabled>
                <i class="fas fa-cogs"></i> 批量修改组件
            </button>
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#downloadModal">
                <i class="fas fa-download"></i> 直接下载
            </button>
        </div>
    </div>
    
    <!-- 搜索和筛选 -->
    <div class="search-filter-card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-filter"></i> 搜索和筛选
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('servers.index') }}" method="GET">
                <div class="search-row">
                    <div>
                        <label for="search">搜索</label>
                        <input type="text" class="form-control" id="search" name="search" placeholder="服务器名称或IP" value="{{ request('search') }}">
                    </div>
                    <div>
                        <label for="group_id">服务器分组</label>
                        <select class="form-control" id="group_id" name="group_id">
                            <option value="">所有分组</option>
                            @foreach ($groups as $group)
                                <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="status">服务器状态</label>
                        <select class="form-control" id="status" name="status">
                            <option value="">全部状态</option>
                            <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>在线</option>
                            <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>离线</option>
                        </select>
                    </div>
                </div>
                <div class="button-row">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> 搜索
                    </button>
                    <a href="{{ route('servers.index') }}" class="btn btn-secondary">
                        <i class="fas fa-sync"></i> 重置
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 服务器列表 -->
    <div class="card card-light-blue shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list"></i> 服务器列表
            </h5>
        </div>
        <div class="card-body p-0">
                @csrf
                <div class="table-responsive">
                    <table class="table table-striped table-light table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                    </div>
                                </th>
                                <th style="width: 60px;">ID</th>
                                <th>名称</th>
                                <th>分组</th>
                                <th>IP地址</th>
                                <th style="width: 60px;">端口</th>
                                <th style="width: 80px;">状态</th>
                                <th>最后检查时间</th>
                                <th>最后采集时间</th>
                                <th style="width: 150px;">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($servers as $server)
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input server-checkbox" type="checkbox" name="server_ids[]" value="{{ $server->id }}">
                                        </div>
                                    </td>
                                    <td><span class="badge badge-light">{{ $server->id }}</span></td>
                                    <td><strong>{{ $server->name }}</strong></td>
                                    <td><span class="badge badge-info">{{ $server->group->name ?? '无分组' }}</span></td>
                                    <td><code>{{ $server->ip }}</code></td>
                                    <td>{{ $server->port }}</td>
                                    <td>
                                        @if ($server->status == 1)
                                            <span class="badge badge-success"><i class="fas fa-circle"></i> 在线</span>
                                        @else
                                            <span class="badge badge-danger"><i class="fas fa-circle"></i> 离线</span>
                                        @endif
                                    </td>
                                    <td><small class="text-muted">{{ $server->last_check_time ? $server->last_check_time->format('Y-m-d H:i') : '未检查' }}</small></td>
                                    <td><small class="text-muted">{{ $server->lastCollectionTime ? $server->lastCollectionTime->format('Y-m-d H:i') : '未采集' }}</small></td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('servers.show', $server) }}" class="btn btn-primary" title="查看详情">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('servers.edit', $server) }}" class="btn btn-primary" title="编辑">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger" onclick="deleteServer({{ $server->id }})" title="删除">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <i class="fas fa-inbox text-muted" style="font-size: 2rem;"></i>
                                        <p class="text-muted mt-2">暂无服务器</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            
            <div class="d-flex justify-content-center mt-3 pb-3">
                {{ $servers->links() }}
            </div>
        </div>
    </div>
</div>

<!-- 导入模态框 -->
<div class="modal fade" id="batchImportModal" tabindex="-1" role="dialog" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title" id="importModalLabel">
                    <i class="fas fa-file-import"></i> 批量导入服务器
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('servers.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="file" class="font-weight-bold">选择Excel文件</label>
                        <input type="file" class="form-control-file" id="file" name="file" required accept=".xlsx,.xls,.csv">
                        <small class="form-text text-muted">支持 .xlsx, .xls, .csv 格式</small>
                    </div>
                    <div class="alert alert-info border-0">
                        <h6 class="font-weight-bold"><i class="fas fa-info-circle"></i> 导入说明：</h6>
                        <p class="mb-2">请使用以下列标题的Excel文件：</p>
                        <ul class="mb-0 small">
                            <li><strong>name</strong> - 服务器名称（必填）</li>
                            <li><strong>group</strong> - 服务器分组（选填，不存在则自动创建）</li>
                            <li><strong>ip</strong> - IP地址（必填）</li>
                            <li><strong>port</strong> - 端口（选填，默认22）</li>
                            <li><strong>username</strong> - 用户名（必填）</li>
                            <li><strong>password</strong> - 密码（必填）</li>
                            <li><strong>verify_connection</strong> - 是否验证连接（选填，默认true）</li>
                        </ul>
                        <hr class="my-2">
                        <p class="mb-0">
                            <a href="{{ route('servers.download-template') }}" class="btn btn-sm btn-primary" target="_blank">
                                <i class="fas fa-download"></i> 下载导入模板
                            </a>
                        </p>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> 导入
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 直接下载模态框 -->
<div class="modal fade" id="downloadModal" tabindex="-1" role="dialog" aria-labelledby="downloadModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title" id="downloadModalLabel">
                    <i class="fas fa-download"></i> 直接下载
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="font-weight-bold">下载格式</label>
                    <div class="btn-group btn-group-block" role="group" style="display: flex; gap: 10px;">
                        <button type="button" class="btn btn-primary flex-grow-1" id="downloadFormatExcel">
                            <i class="fas fa-file-excel"></i> Excel (.xlsx)
                        </button>
                        <button type="button" class="btn btn-primary flex-grow-1" id="downloadFormatCsv">
                            <i class="fas fa-file-csv"></i> CSV (.csv)
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">下载范围</label>
                    <div class="list-group">
                        <button type="button" class="list-group-item list-group-item-action text-left" id="downloadScopeSelected">
                            <i class="fas fa-check-square"></i> 已勾选的数据
                        </button>
                        <button type="button" class="list-group-item list-group-item-action text-left" id="downloadScopeCurrentPage">
                            <i class="fas fa-list"></i> 当前页数据
                        </button>
                        <button type="button" class="list-group-item list-group-item-action text-left" id="downloadScopeAllFiltered">
                            <i class="fas fa-database"></i> 全部查询数据
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
            </div>
        </div>
    </div>
</div>

<!-- 批量修改组件模态框 -->
<div class="modal fade" id="batchModifyComponentsModal" tabindex="-1" role="dialog" aria-labelledby="batchModifyComponentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="batchModifyComponentsModalLabel">
                    <i class="fas fa-cogs text-primary"></i> 批量修改组件
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="batchModifyComponentsForm" action="{{ route('servers.batch-modify-components') }}" method="POST">
                    @csrf
                    <input type="hidden" id="selected_server_ids_modify" name="server_ids" value="">
                    
                    <div class="alert alert-info border-0 mb-3">
                        <i class="fas fa-info-circle"></i>
                        已选择 <span id="selectedServerCountModify" class="font-weight-bold">0</span> 个服务器，请选择要关联的采集组件：
                    </div>
                    
                    <div class="form-group">
                        <label class="font-weight-bold mb-2">操作类型：</label>
                        <div class="custom-control custom-radio mb-2">
                            <input class="custom-control-input" type="radio" name="operation_type" id="operationReplace" value="replace" checked>
                            <label class="custom-control-label" for="operationReplace">
                                <strong>替换</strong> - 清除现有关联，只保留选中的组件
                            </label>
                        </div>
                        <div class="custom-control custom-radio mb-2">
                            <input class="custom-control-input" type="radio" name="operation_type" id="operationAdd" value="add">
                            <label class="custom-control-label" for="operationAdd">
                                <strong>添加</strong> - 在现有关联基础上添加选中的组件
                            </label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input class="custom-control-input" type="radio" name="operation_type" id="operationRemove" value="remove">
                            <label class="custom-control-label" for="operationRemove">
                                <strong>移除</strong> - 从现有关联中移除选中的组件
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="font-weight-bold mb-2">采集组件：</label>
                        <div id="componentsContainer" class="collectors-container border rounded bg-light">
                            <!-- 采集组件列表将通过AJAX加载 -->
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
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
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title" id="batchCollectionModalLabel">
                    <i class="fas fa-play-circle"></i> 批量采集
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="batchCollectionForm" action="{{ route('collection-tasks.batch.execute') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="task_name" class="font-weight-bold">任务名称</label>
                        <input type="text" class="form-control" id="task_name" name="name" required placeholder="输入任务名称">
                    </div>
                    <div class="form-group">
                        <label for="task_description" class="font-weight-bold">任务描述</label>
                        <textarea class="form-control" id="task_description" name="description" rows="2" placeholder="可选，输入任务描述"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="font-weight-bold">选中的服务器 (<span id="selectedServerCount" class="badge badge-warning">0</span> 台)</label>
                        <div id="selectedServerList" class="border rounded p-3 bg-light" style="max-height: 150px; overflow-y: auto;">
                            <div class="text-muted text-center">
                                <i class="fas fa-inbox"></i> 请先选择服务器
                            </div>
                        </div>
                        <input type="hidden" id="selected_server_ids" name="server_ids">
                    </div>
                    
                    <div class="form-group">
                        <label class="font-weight-bold">采集组件</label>
                        <div id="collectorsList" class="collectors-container border rounded bg-light">
                            <div class="text-muted text-center">
                                <i class="fas fa-spinner fa-spin"></i> 正在加载共同的采集组件...
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary" id="submitBatchCollection" disabled>
                        <i class="fas fa-play"></i> 开始采集
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // ============ 全局函数定义 ============
    
    // 初始化采集组件 tooltip
    function initComponentTooltips() {
        // 初始化所有带有 data-bs-toggle="tooltip" 的元素
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            try {
                // 尝试使用 Bootstrap 5 的 Tooltip
                if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                    new bootstrap.Tooltip(tooltipTriggerEl);
                }
            } catch(e) {
                console.log('Tooltip initialization error:', e);
            }
        });
    }
    
    // 下载功能通用函数
    function downloadServers(format, scope) {
        var serverIds = [];
        var fileName = '';
        
        // 根据scope确定要下载的服务器ID
        if (scope === 'selected') {
            // 已勾选的数据
            serverIds = $('.server-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (serverIds.length === 0) {
                alert('请至少选择一台服务器');
                return false;
            }
            fileName = '服务器数据_已勾选_' + serverIds.length + '台';
        } else if (scope === 'currentPage') {
            // 当前页数据
            serverIds = $('.server-checkbox').map(function() {
                return $(this).val();
            }).get();
            
            if (serverIds.length === 0) {
                alert('当前页没有服务器数据');
                return false;
            }
            fileName = '服务器数据_当前页_' + serverIds.length + '台';
        } else if (scope === 'allFiltered') {
            // 全部查询数据 - 需要调用后端API获取所有符合条件的数据
            downloadAllFiltered(format);
            return;
        } else {
            // 默认为已勾选
            serverIds = $('.server-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (serverIds.length === 0) {
                alert('请至少选择一台服务器');
                return false;
            }
            fileName = '服务器数据_已勾选_' + serverIds.length + '台';
        }
        
        // 创建临时表单
        var tempForm = $('<form>', {
            action: '{{ route("servers.download") }}',
            method: 'POST',
            style: 'display: none;',
            target: '_blank'
        });
        
        // 添加CSRF令牌
        tempForm.append('<input type="hidden" name="_token" value="{{ csrf_token() }}">');
        
        // 添加格式参数
        tempForm.append('<input type="hidden" name="format" value="' + format + '">');
        
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
    }
    
    // 下载全部查询数据
    function downloadAllFiltered(format) {
        // 获取当前搜索条件
        var search = $('#search').val() || '';
        var groupId = $('#group_id').val() || '';
        var status = $('#status').val() || '';
        
        // 创建临时表单
        var tempForm = $('<form>', {
            action: '{{ route("servers.download-all-filtered") }}',
            method: 'POST',
            style: 'display: none;',
            target: '_blank'
        });
        
        // 添加CSRF令牌
        tempForm.append('<input type="hidden" name="_token" value="{{ csrf_token() }}">');
        
        // 添加格式参数
        tempForm.append('<input type="hidden" name="format" value="' + format + '">');
        
        // 添加搜索条件
        if (search) tempForm.append('<input type="hidden" name="search" value="' + search + '">');
        if (groupId) tempForm.append('<input type="hidden" name="group_id" value="' + groupId + '">');
        if (status) tempForm.append('<input type="hidden" name="status" value="' + status + '">');
        
        // 添加到body并提交
        $('body').append(tempForm);
        tempForm.submit();
        
        // 稍后移除临时表单
        setTimeout(function() {
            tempForm.remove();
        }, 1000);
    }
    
    // 显示格式选择对话框 - 已移至模态框
    function showDownloadFormatDialog_deprecated(scope) {
        var scopeText = {
            'selected': '已勾选的数据',
            'currentPage': '当前页数据',
            'allFiltered': '全部查询数据'
        };
        
        var html = '<div class="alert alert-info mb-3">' +
            '<i class="fas fa-info-circle"></i> 您将下载：<strong>' + scopeText[scope] + '</strong>' +
            '</div>' +
            '<div class="btn-group btn-block" role="group">' +
            '<button type="button" class="btn btn-primary" onclick="downloadServers(\'xlsx\', \'' + scope + '\'); $(\'#formatDialog\').modal(\'hide\');">' +
            '<i class="fas fa-file-excel"></i> Excel (.xlsx)' +
            '</button>' +
            '<button type="button" class="btn btn-primary" onclick="downloadServers(\'csv\', \'' + scope + '\'); $(\'#formatDialog\').modal(\'hide\');">' +
            '<i class="fas fa-file-csv"></i> CSV (.csv)' +
            '</button>' +
            '</div>';
        
        // 如果对话框不存在，创建它
        if ($('#formatDialog').length === 0) {
            $('body').append(
                '<div class="modal fade" id="formatDialog" tabindex="-1" role="dialog">' +
                '<div class="modal-dialog" role="document">' +
                '<div class="modal-content">' +
                '<div class="modal-header">' +
                '<h5 class="modal-title">选择下载格式</h5>' +
                '<button type="button" class="close" data-dismiss="modal" aria-label="Close">' +
                '<span aria-hidden="true">&times;</span>' +
                '</button>' +
                '</div>' +
                '<div class="modal-body" id="formatDialogContent"></div>' +
                '</div>' +
                '</div>' +
                '</div>'
            );
        }
        
        $('#formatDialogContent').html(html);
        $('#formatDialog').modal('show');
    }
    
    // ============ 文档就绪后的初始化 ============
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
            
            // 更新其他按钮状态
            $('#batchCollectionBtn').prop('disabled', checkedCount === 0);
            $('#batchModifyComponentsBtn').prop('disabled', checkedCount === 0);
            
            // 更新全选框状态
            var allChecked = checkedCount === $('.server-checkbox').length;
            $('#selectAll').prop('checked', allChecked && checkedCount > 0);
        }
        
        // 直接下载模态框 - 格式选择
        var downloadFormat = 'xlsx';
        var downloadScope = 'selected';
        
        $('#downloadFormatExcel').click(function() {
            downloadFormat = 'xlsx';
            $(this).addClass('active').siblings().removeClass('active');
        });
        
        $('#downloadFormatCsv').click(function() {
            downloadFormat = 'csv';
            $(this).addClass('active').siblings().removeClass('active');
        });
        
        // 下载范围选择
        $('#downloadScopeSelected').click(function() {
            downloadScope = 'selected';
            var checkedCount = $('.server-checkbox:checked').length;
            if (checkedCount === 0) {
                alert('请先选择要下载的服务器');
                return;
            }
            downloadServers(downloadFormat, downloadScope);
            $('#downloadModal').modal('hide');
        });
        
        $('#downloadScopeCurrentPage').click(function() {
            downloadScope = 'currentPage';
            downloadServers(downloadFormat, downloadScope);
            $('#downloadModal').modal('hide');
        });
        
        $('#downloadScopeAllFiltered').click(function() {
            downloadScope = 'allFiltered';
            downloadServers(downloadFormat, downloadScope);
            $('#downloadModal').modal('hide');
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
        
        // 初始化采集组件 tooltip
        function initComponentTooltips() {
            // 初始化所有带有 data-bs-toggle="tooltip" 的元素
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                try {
                    // 尝试使用 Bootstrap 5 的 Tooltip
                    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                        new bootstrap.Tooltip(tooltipTriggerEl);
                    }
                } catch(e) {
                    console.log('Tooltip initialization error:', e);
                }
            });
        }
        
        // 加载所有采集组件
        function loadAllComponents() {
            $('#componentsContainer').html('<div class="text-muted text-center p-3"><i class="fas fa-spinner fa-spin"></i> 正在加载采集组件...</div>');
            
            $.ajax({
                url: '{{ route("api.collectors.all") }}',
                type: 'GET',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        var html = '<div class="collectors-grid">';
                        response.data.forEach(function(collector) {
                            var fullName = collector.name + ' (' + collector.code + ')';
                            var nameTitle = fullName.length > 40 ? fullName : '';
                            var descTitle = collector.description && collector.description.length > 60 ? collector.description : '';
                            
                            html += '<div class="collector-item">';
                            html += '<label class="form-check-label" for="component_' + collector.id + '">';
                            html += '<input class="form-check-input component-checkbox" type="checkbox" name="collector_ids[]" value="' + collector.id + '" id="component_' + collector.id + '">';
                            html += '<div class="collector-content">';
                            html += '<span class="collector-name" ' + (nameTitle ? 'data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="' + nameTitle.replace(/"/g, '&quot;') + '"' : '') + '>' + fullName + '</span>';
                            if (collector.description) {
                                html += '<div class="collector-description"><small class="text-muted" ' + (descTitle ? 'data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="' + descTitle.replace(/"/g, '&quot;') + '"' : '') + '>' + collector.description + '</small></div>';
                            }
                            html += '</div>';
                            html += '</label>';
                            html += '</div>';
                        });
                        html += '</div>';
                        $('#componentsContainer').html(html);
                        
                        // 初始化 tooltip
                        initComponentTooltips();
                        
                        // 监听组件选择变化
                        $('.component-checkbox').change(function() {
                            updateSelectAllComponents();
                        });
                        
                    } else {
                        $('#componentsContainer').html('<div class="alert alert-warning mb-0">没有可用的采集组件</div>');
                    }
                },
                error: function(xhr) {
                    $('#componentsContainer').html('<div class="alert alert-danger mb-0">加载采集组件失败：' + xhr.responseText + '</div>');
                }
            });
        }
        
        // 更新全选组件复选框状态（如果需要）
        function updateSelectAllComponents() {
            // 此函数保留以兼容现有代码，但在新的采集组件样式中不需要全选功能
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
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $('#batchModifyComponentsModal').modal('hide');
                        alert('批量修改组件成功！' + response.message);
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
        
        // 初始化采集组件 tooltip
        function initCollectorTooltips() {
            // 初始化所有带有 data-bs-toggle="tooltip" 的元素
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                try {
                    // 尝试使用 Bootstrap 5 的 Tooltip
                    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                        new bootstrap.Tooltip(tooltipTriggerEl);
                    }
                } catch(e) {
                    console.log('Tooltip initialization error:', e);
                }
            });
        }
        
        // 加载共同的采集组件
        function loadCommonCollectors(serverIds) {
            $('#collectorsList').html('<div class="text-muted"><i class="fas fa-spinner fa-spin"></i> 正在加载共同的采集组件...</div>');
            $('#submitBatchCollection').prop('disabled', true);
            
            $.ajax({
                url: '{{ route("api.servers.common-collectors") }}',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                data: {
                    server_ids: serverIds
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        var html = '<div class="collectors-grid">';
                        response.data.forEach(function(collector) {
                            var fullName = collector.name + ' (' + collector.code + ')';
                            var nameTitle = fullName.length > 40 ? fullName : '';
                            var descTitle = collector.description && collector.description.length > 60 ? collector.description : '';
                            
                            html += '<div class="collector-item">';
                            html += '<label class="form-check-label" for="collector_' + collector.id + '">';
                            html += '<input class="form-check-input collector-checkbox" type="checkbox" name="collector_ids[]" value="' + collector.id + '" id="collector_' + collector.id + '">';
                            html += '<div class="collector-content">';
                            html += '<span class="collector-name" ' + (nameTitle ? 'data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="' + nameTitle.replace(/"/g, '&quot;') + '"' : '') + '>' + fullName + '</span>';
                            if (collector.description) {
                                html += '<div class="collector-description"><small class="text-muted" ' + (descTitle ? 'data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="' + descTitle.replace(/"/g, '&quot;') + '"' : '') + '>' + collector.description + '</small></div>';
                            }
                            html += '</div>';
                            html += '</label>';
                            html += '</div>';
                        });
                        html += '</div>';
                        $('#collectorsList').html(html);
                        
                        // 初始化 tooltip
                        initCollectorTooltips();
                        
                        // 监听采集组件选择变化
                        $('.collector-checkbox').change(function() {
                            var checkedCollectors = $('.collector-checkbox:checked').length;
                            $('#submitBatchCollection').prop('disabled', checkedCollectors === 0);
                        });
                    } else {
                        $('#collectorsList').html('<div class="alert alert-warning mb-3">所选服务器没有共同的采集组件，您可以在下方选择采集组件进行批量关联</div>');
                        
                        // 加载所有采集组件
                        $.ajax({
                            url: '{{ route("api.collectors.all") }}',
                            type: 'GET',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            success: function(response) {
                                if (response.success && response.data.length > 0) {
                                    var html = '<div class="form-group">';
                                    html += '<div class="custom-control custom-checkbox mb-3">';
                                    html += '<input type="checkbox" class="custom-control-input" id="linkCollectors" name="link_collectors" checked>';
                                    html += '<label class="custom-control-label" for="linkCollectors">将选择的采集组件关联到未安装该组件的服务器</label>';
                                    html += '</div>';
                                    html += '</div>';
                                    
                                    html += '<div class="form-group">';
                                    html += '<label class="font-weight-bold">可用采集组件：</label>';
                                    html += '<div class="collectors-grid">';
                                    response.data.forEach(function(collector) {
                                        var fullName = collector.name + ' (' + collector.code + ')';
                                        var nameTitle = fullName.length > 40 ? fullName : '';
                                        var descTitle = collector.description && collector.description.length > 60 ? collector.description : '';
                                        
                                        html += '<div class="collector-item">';
                                        html += '<label class="form-check-label" for="collector_' + collector.id + '">';
                                        html += '<input class="form-check-input collector-checkbox" type="checkbox" name="collector_ids[]" value="' + collector.id + '" id="collector_' + collector.id + '">';
                                        html += '<div class="collector-content">';
                                        html += '<span class="collector-name" ' + (nameTitle ? 'data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="' + nameTitle.replace(/"/g, '&quot;') + '"' : '') + '>' + fullName + '</span>';
                                        if (collector.description) {
                                            html += '<div class="collector-description"><small class="text-muted" ' + (descTitle ? 'data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="' + descTitle.replace(/"/g, '&quot;') + '"' : '') + '>' + collector.description + '</small></div>';
                                        }
                                        html += '</div>';
                                        html += '</label>';
                                        html += '</div>';
                                    });
                                    html += '</div>';
                                    html += '</div>';
                                    
                                    $('#collectorsList').html(html);
                                    
                                    // 初始化 tooltip
                                    initCollectorTooltips();
                                    
                                    // 监听采集组件选择变化
                                    $('.collector-checkbox').change(function() {
                                        var checkedCollectors = $('.collector-checkbox:checked').length;
                                        $('#submitBatchCollection').prop('disabled', checkedCollectors === 0);
                                    });
                                } else {
                                    $('#collectorsList').html('<div class="alert alert-danger">没有可用的采集组件</div>');
                                }
                            },
                            error: function(xhr) {
                                $('#collectorsList').html('<div class="alert alert-danger">加载采集组件失败：' + xhr.responseText + '</div>');
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
                    url: '{{ route("api.servers.batch-associate-collectors") }}',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
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
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#batchCollectionModal').modal('hide');
                            alert('批量采集任务创建成功！正在后台执行...');
                            // 跳转到任务详情页面
                            window.location.href = '{{ route("collection-tasks.show", ":id") }}'.replace(':id', response.data.id);
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
@endpush

@push('scripts')
<script>
    // 设置全局变量供 servers.js 使用
    window.csrfToken = '{{ csrf_token() }}';
    window.serversDownloadRoute = '{{ route("servers.download") }}';
    window.serversDownloadAllFilteredRoute = '{{ route("servers.download-all-filtered") }}';
    window.serversDownloadTemplateRoute = '{{ route("servers.download-template") }}';
    window.apiCollectorsAllRoute = '{{ route("api.collectors.all") }}';
    window.apiServersCommonCollectorsRoute = '{{ route("api.servers.common-collectors") }}';
    window.apiServersBatchAssociateCollectorsRoute = '{{ route("api.servers.batch-associate-collectors") }}';
    window.collectionTasksShowRoute = '{{ route("collection-tasks.show", ":id") }}';
</script>
<script src="{{ asset('assets/js/modules/servers.js') }}"></script>
<script src="{{ asset('assets/js/common/delete-handler.js') }}"></script>
@endpush
