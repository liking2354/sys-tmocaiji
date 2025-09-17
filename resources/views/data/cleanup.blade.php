@extends('layouts.app')

@section('title', '数据清理 - 服务器管理与数据采集系统')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>数据清理</h1>
        <a href="{{ route('dashboard') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> 返回仪表盘
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">清理条件</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('data.cleanup') }}" method="POST" id="cleanupForm">
                        @csrf
                        
                        <div class="form-group">
                            <label>选择服务器</label>
                            <div class="card">
                                <div class="card-header bg-light p-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="selectAllServers">
                                        <label class="form-check-label font-weight-bold" for="selectAllServers">
                                            全选/取消全选
                                        </label>
                                    </div>
                                </div>
                                <div class="card-body p-2" style="max-height: 200px; overflow-y: auto;">
                                    @foreach ($serverGroups as $group)
                                        <div class="mb-2" data-group-id="{{ $group->id }}">
                                            <h6>
                                                {{ $group->name ?? '无分组' }}
                                                <button type="button" class="btn btn-sm btn-outline-primary load-group-servers" data-group-id="{{ $group->id }}">
                                                    <i class="fas fa-sync-alt"></i> 加载服务器
                                                </button>
                                            </h6>
                                            <div class="row group-servers-container" id="group-servers-{{ $group->id }}">
                                                <div class="col-12 text-center py-2">
                                                    <span class="text-muted">点击按钮加载此分组的服务器</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @error('server_ids')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label>选择采集组件</label>
                            <div class="card">
                                <div class="card-header bg-light p-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="selectAllCollectors">
                                        <label class="form-check-label font-weight-bold" for="selectAllCollectors">
                                            全选/取消全选
                                        </label>
                                    </div>
                                </div>
                                <div class="card-body p-2" style="max-height: 200px; overflow-y: auto;">
                                    <div class="row">
                                        <div class="col-12 mb-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary load-collectors" data-status="1">
                                                <i class="fas fa-sync-alt"></i> 加载启用的采集组件
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary load-collectors" data-status="0">
                                                <i class="fas fa-sync-alt"></i> 加载禁用的采集组件
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row" id="collectors-container">
                                        <div class="col-12 text-center py-2">
                                            <span class="text-muted">点击按钮加载采集组件</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @error('collector_ids')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label>时间范围</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="start_date">开始日期</label>
                                        <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date') }}">
                                        @error('start_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="end_date">结束日期</label>
                                        <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ old('end_date') }}">
                                        @error('end_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> 警告：数据清理操作不可恢复，请谨慎操作！
                        </div>
                        
                        <div class="form-group">
                            <button type="button" class="btn btn-danger" id="cleanupBtn">
                                <i class="fas fa-trash"></i> 清理数据
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">数据统计</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card mb-3">
                                <div class="card-body text-center">
                                    <h2>{{ App\Models\Server::count() }}</h2>
                                    <p class="mb-0">服务器总数</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card mb-3">
                                <div class="card-body text-center">
                                    <h2>{{ App\Models\Collector::count() }}</h2>
                                    <p class="mb-0">采集组件总数</p>
                                </div>
                            </div>
                        </div>

                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">数据存储占用</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="storageChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
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
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
<script>
    $(document).ready(function() {
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
            $('#serverCount').text(count);
        }
        
        // 更新采集组件计数
        function updateCollectorCount() {
            var count = $('.collector-checkbox:checked').length;
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
        
        // 加载服务器分组
        $('.load-group-servers').click(function() {
            var groupId = $(this).data('group-id');
            var container = $('#group-servers-' + groupId);
            var button = $(this);
            
            // 显示加载中
            container.html('<div class="col-12 text-center py-2"><i class="fas fa-spinner fa-spin"></i> 加载中...</div>');
            button.prop('disabled', true);
            
            // 发送AJAX请求获取服务器列表
            $.ajax({
                url: '/api/servers',
                method: 'GET',
                data: { group_id: groupId },
                success: function(response) {
                    var html = '';
                    if (response.data.length > 0) {
                        $.each(response.data, function(index, server) {
                            html += '<div class="col-md-6">';
                            html += '<div class="form-check">';
                            html += '<input class="form-check-input server-checkbox" type="checkbox" id="server_' + server.id + '" name="server_ids[]" value="' + server.id + '">';
                            html += '<label class="form-check-label" for="server_' + server.id + '">';
                            html += server.name;
                            html += '<small class="text-muted">(' + server.ip + ')</small>';
                            if (server.status == 1) {
                                html += '<span class="badge badge-success">在线</span>';
                            } else {
                                html += '<span class="badge badge-danger">离线</span>';
                            }
                            html += '</label>';
                            html += '</div>';
                            html += '</div>';
                        });
                    } else {
                        html = '<div class="col-12 text-center"><span class="text-muted">该分组下没有服务器</span></div>';
                    }
                    container.html(html);
                    updateServerCount();
                    updateServerSelectAllState();
                },
                error: function() {
                    container.html('<div class="col-12 text-center"><span class="text-danger">加载失败，请重试</span></div>');
                },
                complete: function() {
                    button.prop('disabled', false);
                }
            });
        });
        
        // 加载采集组件
        $('.load-collectors').click(function() {
            var status = $(this).data('status');
            var container = $('#collectors-container');
            var button = $(this);
            
            // 显示加载中
            container.html('<div class="col-12 text-center py-2"><i class="fas fa-spinner fa-spin"></i> 加载中...</div>');
            $('.load-collectors').prop('disabled', true);
            
            // 发送AJAX请求获取采集组件列表
            $.ajax({
                url: '/api/collectors',
                method: 'GET',
                data: { status: status },
                success: function(response) {
                    var html = '';
                    if (response.data.length > 0) {
                        $.each(response.data, function(index, collector) {
                            html += '<div class="col-md-6">';
                            html += '<div class="form-check">';
                            html += '<input class="form-check-input collector-checkbox" type="checkbox" id="collector_' + collector.id + '" name="collector_ids[]" value="' + collector.id + '">';
                            html += '<label class="form-check-label" for="collector_' + collector.id + '">';
                            html += collector.name;
                            html += '</label>';
                            html += '</div>';
                            html += '</div>';
                        });
                    } else {
                        html = '<div class="col-12 text-center"><span class="text-muted">没有' + (status == 1 ? '启用' : '禁用') + '的采集组件</span></div>';
                    }
                    container.html(html);
                    updateCollectorCount();
                    updateCollectorSelectAllState();
                },
                error: function() {
                    container.html('<div class="col-12 text-center"><span class="text-danger">加载失败，请重试</span></div>');
                },
                complete: function() {
                    $('.load-collectors').prop('disabled', false);
                }
            });
        });
        
        // 清理按钮点击事件
        $('#cleanupBtn').click(function() {
            updateServerCount();
            updateCollectorCount();
            updateDateRange();
            $('#confirmModal').modal('show');
        });
        
        // 确认清理按钮点击事件
        $('#confirmCleanupBtn').click(function() {
            $('#cleanupForm').submit();
        });
        
        // 初始化计数
        updateServerCount();
        updateCollectorCount();
        updateDateRange();
        
        // 任务详情数据量图表
        var taskDetailCtx = document.getElementById('taskDetailChart').getContext('2d');
        var taskDetailChart = new Chart(taskDetailCtx, {
            type: 'line',
            data: {
                labels: ['1月', '2月', '3月', '4月', '5月', '6月'],
                datasets: [{
                    label: '任务详情数据量',
                    data: [120, 190, 300, 250, 280, 350],
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            }
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
            }
        });
    });
</script>
@endsection