@extends('layouts.app')

@section('title', '仪表盘 - TMO云迁移')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>系统仪表盘</h1>
        <div>
            <span class="badge badge-pill badge-primary p-2">
                <i class="fas fa-clock mr-1"></i> {{ date('Y-m-d H:i') }}
            </span>
        </div>
    </div>
    
    <!-- 统计卡片 -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card card-primary shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small font-weight-bold text-uppercase mb-2">服务器总数</div>
                            <div class="h2 mb-0 font-weight-bold">{{ $serverCount }}</div>
                            <div class="mt-3 small">
                                <span class="badge badge-success mr-2">
                                    <i class="fas fa-circle"></i> 在线: {{ $serverStatusStats['online'] }}
                                </span>
                                <span class="badge badge-danger">
                                    <i class="fas fa-circle"></i> 离线: {{ $serverStatusStats['offline'] }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <i class="fas fa-server fa-3x text-primary opacity-50"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('servers.index') }}" class="btn btn-primary btn-sm btn-block">
                            <i class="fas fa-arrow-right mr-1"></i> 管理服务器
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card card-success shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small font-weight-bold text-uppercase mb-2">服务器分组</div>
                            <div class="h2 mb-0 font-weight-bold">{{ $groupCount }}</div>
                            <div class="mt-3 small text-muted">
                                有效组织和管理您的服务器
                            </div>
                        </div>
                        <div>
                            <i class="fas fa-layer-group fa-3x text-success opacity-50"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('server-groups.index') }}" class="btn btn-success btn-sm btn-block">
                            <i class="fas fa-arrow-right mr-1"></i> 管理分组
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card card-info shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small font-weight-bold text-uppercase mb-2">采集组件</div>
                            <div class="h2 mb-0 font-weight-bold">{{ $collectorCount }}</div>
                            <div class="mt-3 small text-muted">
                                用于数据采集的可用组件
                            </div>
                        </div>
                        <div>
                            <i class="fas fa-plug fa-3x text-info opacity-50"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('collectors.index') }}" class="btn btn-info btn-sm btn-block">
                            <i class="fas fa-arrow-right mr-1"></i> 管理组件
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card card-warning shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small font-weight-bold text-uppercase mb-2">采集任务</div>
                            <div class="h2 mb-0 font-weight-bold">{{ $taskCount ?? 0 }}</div>
                            <div class="mt-3 small text-muted">
                                数据采集任务管理
                            </div>
                        </div>
                        <div>
                            <i class="fas fa-tasks fa-3x text-warning opacity-50"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('collection-tasks.index') }}" class="btn btn-warning btn-sm btn-block">
                            <i class="fas fa-arrow-right mr-1"></i> 管理任务
                        </a>
                    </div>
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
                    <p>TMO云迁移提供了对服务器和采集组件的全面管理功能。</p>
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
                        <a href="{{ route('servers.create') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-plus-circle mr-2"></i> 添加服务器
                        </a>
                        <a href="{{ route('server-groups.create') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-folder-plus mr-2"></i> 创建服务器分组
                        </a>
                        <a href="{{ route('collectors.create') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-plug mr-2"></i> 添加采集组件
                        </a>
                        <a href="{{ route('data.cleanup.form') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-broom mr-2"></i> 数据清理
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 系统状态概览和最近活动 -->
    <div class="row">
        <!-- 系统状态概览 -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">服务器状态分布</h6>
                    <span class="badge badge-success">运行正常</span>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 300px;">
                        <canvas id="serverStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 最近活动 -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">最近活动</h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">服务器 [Server-01] 状态更新</h6>
                                <small class="text-muted">今天 10:30</small>
                                <p class="mb-0 small">服务器状态从离线变为在线</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-info"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">采集任务 [Task-05] 完成</h6>
                                <small class="text-muted">今天 09:15</small>
                                <p class="mb-0 small">成功采集数据 156 条</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-warning"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">新增服务器分组 [测试环境]</h6>
                                <small class="text-muted">昨天 16:45</small>
                                <p class="mb-0 small">管理员创建了新的服务器分组</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-danger"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">服务器 [Server-03] 状态异常</h6>
                                <small class="text-muted">昨天 14:20</small>
                                <p class="mb-0 small">服务器连接超时，请检查网络</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <a href="#" class="btn btn-sm btn-primary">查看所有活动</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 采集数据趋势 -->
    <div class="row">
        <div class="col-lg-12 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">采集数据趋势</h6>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 300px;">
                        <canvas id="dataCollectionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .border-left-primary {
        border-left: 4px solid #4e73df;
    }
    .border-left-success {
        border-left: 4px solid #1cc88a;
    }
    .border-left-info {
        border-left: 4px solid #36b9cc;
    }
    .border-left-warning {
        border-left: 4px solid #f6c23e;
    }
    .timeline {
        position: relative;
        padding: 0;
        list-style: none;
    }
    .timeline-item {
        position: relative;
        padding-left: 30px;
        margin-bottom: 15px;
    }
    .timeline-marker {
        position: absolute;
        left: 0;
        top: 5px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
    }
    .timeline-content {
        padding-bottom: 15px;
        border-bottom: 1px solid #e3e6f0;
    }
    .timeline-item:last-child .timeline-content {
        border-bottom: none;
        padding-bottom: 0;
    }
    
    /* 响应式优化 */
    @media (max-width: 768px) {
        .container-fluid {
            padding-left: 10px;
            padding-right: 10px;
        }
        .card-body {
            padding: 0.75rem;
        }
        .timeline-item {
            padding-left: 25px;
        }
        .timeline-marker {
            width: 10px;
            height: 10px;
        }
    }
    </style>
    
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
    <script>
    $(document).ready(function() {
        // 检查 Canvas 元素是否存在
        var serverStatusElement = document.getElementById('serverStatusChart');
        var dataCollectionElement = document.getElementById('dataCollectionChart');
        
        // 服务器状态分布图表
        if (serverStatusElement) {
            var serverStatusCtx = serverStatusElement.getContext('2d');
            var serverStatusChart = new Chart(serverStatusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['在线', '离线', '维护中', '未知'],
                    datasets: [{
                        data: [{{ $serverStatusStats['online'] }}, {{ $serverStatusStats['offline'] }}, 2, 1],
                        backgroundColor: [
                            '#1cc88a', // 在线 - 绿色
                            '#e74a3b', // 离线 - 红色
                            '#f6c23e', // 维护中 - 黄色
                            '#858796'  // 未知 - 灰色
                        ],
                        hoverBackgroundColor: [
                            '#17a673',
                            '#be3c2d',
                            '#dda20a',
                            '#6e7081'
                        ],
                        hoverBorderColor: "rgba(234, 236, 244, 1)",
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    tooltips: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyFontColor: "#858796",
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        xPadding: 15,
                        yPadding: 15,
                        displayColors: false,
                        caretPadding: 10,
                    },
                    legend: {
                        display: true,
                        position: 'bottom'
                    },
                    cutoutPercentage: 70,
                },
            });
        }
        
        // 采集数据趋势图表
        if (dataCollectionElement) {
            var dataCollectionCtx = dataCollectionElement.getContext('2d');
            var dataCollectionChart = new Chart(dataCollectionCtx, {
                type: 'line',
                data: {
                    labels: ["1月", "2月", "3月", "4月", "5月", "6月", "7月"],
                    datasets: [{
                        label: "采集数据量",
                        lineTension: 0.3,
                        backgroundColor: "rgba(78, 115, 223, 0.05)",
                        borderColor: "rgba(78, 115, 223, 1)",
                        pointRadius: 3,
                        pointBackgroundColor: "rgba(78, 115, 223, 1)",
                        pointBorderColor: "rgba(78, 115, 223, 1)",
                        pointHoverRadius: 3,
                        pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                        pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                        pointHitRadius: 10,
                        pointBorderWidth: 2,
                        data: [1250, 1900, 2800, 2400, 3100, 3500, 4200],
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            left: 10,
                            right: 25,
                            top: 25,
                            bottom: 0
                        }
                    },
                    scales: {
                        xAxes: [{
                            time: {
                                unit: 'date'
                            },
                            gridLines: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                maxTicksLimit: 7
                            }
                        }],
                        yAxes: [{
                            ticks: {
                                maxTicksLimit: 5,
                                padding: 10,
                                callback: function(value, index, values) {
                                    return value;
                                }
                            },
                            gridLines: {
                                color: "rgb(234, 236, 244)",
                                zeroLineColor: "rgb(234, 236, 244)",
                                drawBorder: false,
                                borderDash: [2],
                                zeroLineBorderDash: [2]
                            }
                        }],
                    },
                    legend: {
                        display: false
                    },
                    tooltips: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyFontColor: "#858796",
                        titleMarginBottom: 10,
                        titleFontColor: '#6e707e',
                        titleFontSize: 14,
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        xPadding: 15,
                        yPadding: 15,
                        displayColors: false,
                        intersect: false,
                        mode: 'index',
                        caretPadding: 10,
                        callbacks: {
                            label: function(tooltipItem, chart) {
                                var datasetLabel = chart.datasets[tooltipItem.datasetIndex].label || '';
                                return datasetLabel + ': ' + tooltipItem.yLabel + ' 条';
                            }
                        }
                    }
                }
            });
        }
    });
    </script>
    @endpush
</div>
@endsection
