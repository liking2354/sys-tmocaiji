@extends('layouts.app')

@section('title', ($task->name ?? '未知任务') . ' - 采集任务详情')

@section('content')
@if(!$task)
    <div class="container-fluid">
        <div class="alert alert-danger">
            <h4>错误</h4>
            <p>任务不存在或已被删除。</p>
            <a href="{{ route('collection-tasks.index') }}" class="btn btn-primary">返回任务列表</a>
        </div>
    </div>
@else
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>采集任务详情</h1>
        <div>
            <!-- 任务控制按钮 -->
            @if ($task->status == 0)
                <button type="button" class="btn btn-success" onclick="executeTask('{{ $task->id }}')">
                    <i class="fas fa-play"></i> 开始执行
                </button>
            @endif
            
            @if ($task->status == 1)
                <button type="button" class="btn btn-danger" onclick="cancelTask('{{ $task->id }}')">
                    <i class="fas fa-stop"></i> 取消任务
                </button>
            @endif
            
            @if (in_array($task->status, [2, 3]))
                <button type="button" class="btn btn-warning" onclick="resetTask('{{ $task->id }}')">
                    <i class="fas fa-redo"></i> 重置任务
                </button>
            @endif
            
            <button type="button" class="btn btn-info" onclick="refreshTaskStatus()">
                <i class="fas fa-sync"></i> 刷新状态
            </button>
            
            <button type="button" class="btn btn-warning" onclick="detectTimeoutTasks()">
                <i class="fas fa-clock"></i> 检测超时
            </button>
            
            <a href="{{ route('collection-tasks.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 返回任务列表
            </a>
        </div>
    </div>
    
    <!-- 任务基本信息 -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-info-circle"></i> 任务基本信息
                <span class="float-right" id="lastUpdateTime">最后更新: {{ now()->format('H:i:s') }}</span>
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 30%">任务ID:</th>
                            <td>{{ $task->id }}</td>
                        </tr>
                        <tr>
                            <th>任务名称:</th>
                            <td>{{ $task->name }}</td>
                        </tr>
                        <tr>
                            <th>任务描述:</th>
                            <td>{{ $task->description ?: '无描述' }}</td>
                        </tr>
                        <tr>
                            <th>任务类型:</th>
                            <td>
                                <span class="badge badge-{{ $task->type === 'single' ? 'info' : 'primary' }}">
                                    {{ $task->typeText }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>任务状态:</th>
                            <td id="taskStatusDisplay">
                                @include('collection-tasks.partials.status-badge', ['status' => $task->status, 'statusText' => $task->statusText])
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 30%">创建人:</th>
                            <td>{{ $task->creator->username ?? '未知' }}</td>
                        </tr>
                        <tr>
                            <th>创建时间:</th>
                            <td>{{ $task->created_at ? $task->created_at->format('Y-m-d H:i:s') : '未知' }}</td>
                        </tr>
                        <tr>
                            <th>开始时间:</th>
                            <td id="startedAtDisplay">{{ $task->started_at ? $task->started_at->format('Y-m-d H:i:s') : '未开始' }}</td>
                        </tr>
                        <tr>
                            <th>完成时间:</th>
                            <td id="completedAtDisplay">{{ $task->completed_at ? $task->completed_at->format('Y-m-d H:i:s') : '未完成' }}</td>
                        </tr>
                        <tr>
                            <th>执行时长:</th>
                            <td id="durationDisplay">
                                @if ($task->started_at && $task->completed_at)
                                    {{ $task->started_at->diffForHumans($task->completed_at, true) }}
                                @elseif ($task->started_at)
                                    {{ $task->started_at->diffForHumans() }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 实时任务进度 -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">
                <i class="fas fa-chart-line"></i> 实时任务进度
                <span class="float-right">
                    <small id="progressUpdateTime">更新时间: {{ now()->format('H:i:s') }}</small>
                </span>
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="progress mb-3" style="height: 30px;">
                        <div class="progress-bar bg-info" id="taskProgressBar" role="progressbar" 
                             style="width: {{ $task->progress }}%" 
                             aria-valuenow="{{ $task->progress }}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            <span id="progressText">{{ number_format($task->progress, 1) }}%</span>
                        </div>
                    </div>
                    
                    <!-- 执行日志 -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-list"></i> 执行日志
                                <button class="btn btn-sm btn-outline-secondary float-right" onclick="clearExecutionLog()">
                                    <i class="fas fa-trash"></i> 清空
                                </button>
                            </h6>
                        </div>
                        <div class="card-body p-2">
                            <div id="executionLog" style="height: 200px; overflow-y: auto; background: #f8f9fa; padding: 10px; font-family: monospace; font-size: 12px;">
                                <div class="text-muted">等待任务执行...</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <!-- 统计卡片 -->
                    <div class="row">
                        <div class="col-6 mb-3">
                            <div class="card bg-light text-center">
                                <div class="card-body py-2">
                                    <h4 class="text-primary mb-0" id="totalCount">{{ $stats['total'] ?? 0 }}</h4>
                                    <small class="text-muted">总计</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="card bg-light text-center">
                                <div class="card-body py-2">
                                    <h4 class="text-secondary mb-0" id="pendingCount">{{ $stats['pending'] ?? 0 }}</h4>
                                    <small class="text-muted">未开始</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="card bg-light text-center">
                                <div class="card-body py-2">
                                    <h4 class="text-warning mb-0" id="runningCount">{{ $stats['running'] ?? 0 }}</h4>
                                    <small class="text-muted">进行中</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="card bg-light text-center">
                                <div class="card-body py-2">
                                    <h4 class="text-success mb-0" id="completedCount">{{ $stats['completed'] ?? 0 }}</h4>
                                    <small class="text-muted">已完成</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card bg-light text-center">
                                <div class="card-body py-2">
                                    <h4 class="text-danger mb-0" id="failedCount">{{ $stats['failed'] ?? 0 }}</h4>
                                    <small class="text-muted">失败</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 任务详情列表 -->
    <div class="card">
        <div class="card-header bg-success text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list-alt"></i> 执行详情
                </h5>
                <div>
                    <select id="statusFilter" class="form-control form-control-sm" style="width: auto; display: inline-block;">
                        <option value="">所有状态</option>
                        <option value="0">未开始</option>
                        <option value="1">进行中</option>
                        <option value="2">已完成</option>
                        <option value="3">失败</option>
                        <option value="4">超时</option>
                    </select>
                    <button type="button" class="btn btn-light btn-sm ml-2" onclick="refreshDetails()">
                        <i class="fas fa-sync"></i> 刷新
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm" id="detailsTable">
                    <thead>
                        <tr>
                            <th>服务器</th>
                            <th>采集组件</th>
                            <th>状态</th>
                            <th>执行时间(秒)</th>
                            <th>开始时间</th>
                            <th>完成时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody id="detailsTableBody">
                        @forelse ($detailsByServer->flatten()->all() as $detail)
                            <tr data-status="{{ $detail->status }}" data-detail-id="{{ $detail->id }}">
                                <td>
                                    <strong>{{ $detail->server->name }}</strong><br>
                                    <small class="text-muted">{{ $detail->server->ip }}</small>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $detail->collector->type === 'script' ? 'info' : 'warning' }}">
                                        {{ $detail->collector->name }}
                                    </span><br>
                                    <small class="text-muted">{{ $detail->collector->code }}</small>
                                </td>
                                <td class="status-cell">
                                    @include('collection-tasks.partials.status-badge', ['status' => $detail->status, 'statusText' => $detail->statusText])
                                </td>
                                <td class="execution-time">{{ $detail->execution_time > 0 ? number_format($detail->execution_time, 3) : '-' }}</td>
                                <td class="started-at">{{ $detail->started_at ? $detail->started_at->format('H:i:s') : '-' }}</td>
                                <td class="completed-at">{{ $detail->completed_at ? $detail->completed_at->format('H:i:s') : '-' }}</td>
                                <td>
                                    @if ($detail->hasResult())
                                        <button type="button" class="btn btn-sm btn-info" onclick="viewResult('{{ $detail->id }}')">
                                            <i class="fas fa-eye"></i> 查看结果
                                        </button>
                                    @endif
                                    @if ($detail->isFailed() && $detail->error_message)
                                        <button type="button" class="btn btn-sm btn-danger" onclick="viewError('{{ $detail->id }}', '{{ addslashes($detail->error_message) }}')">
                                            <i class="fas fa-exclamation-triangle"></i> 查看错误
                                        </button>
                                    @endif
                                    @if (in_array($detail->status, [3, 4])) {{-- 失败或超时状态可以重新执行 --}}
                                        <button type="button" class="btn btn-sm btn-warning" onclick="retryTaskDetail('{{ $detail->id }}')">
                                            <i class="fas fa-redo"></i> 重新执行
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-3">暂无执行详情</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- 结果查看模态框 -->
<div class="modal fade" id="resultModal" tabindex="-1" role="dialog" aria-labelledby="resultModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resultModalLabel">采集结果</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="resultContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> 加载中...
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<!-- 错误查看模态框 -->
<div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-labelledby="errorModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="errorModalLabel">错误信息</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="errorContent">
                    <pre class="bg-light p-3" style="white-space: pre-wrap;"></pre>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>
@endif

<!-- 进度管理模态框 -->
<div class="modal fade" id="progressModal" tabindex="-1" role="dialog" aria-labelledby="progressModalLabel" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="progressModalLabel">
                    <i class="fas fa-tasks"></i> 任务执行进度
                </h5>
            </div>
            <div class="modal-body">
                <!-- 总体进度 -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="font-weight-bold">总体进度</span>
                        <span id="progressPercentage" class="badge badge-info">0%</span>
                    </div>
                    <div class="progress" style="height: 25px;">
                        <div id="overallProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-info" 
                             role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                            <span id="progressBarText">0%</span>
                        </div>
                    </div>
                </div>

                <!-- 执行步骤 -->
                <div class="mb-4">
                    <h6 class="font-weight-bold mb-3">
                        <i class="fas fa-list-ol"></i> 执行步骤
                    </h6>
                    <div id="progressSteps">
                        <!-- 步骤将通过JavaScript动态添加 -->
                    </div>
                </div>

                <!-- 实时日志 -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="font-weight-bold mb-0">
                            <i class="fas fa-terminal"></i> 执行日志
                        </h6>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearProgressLog()">
                            <i class="fas fa-trash"></i> 清空
                        </button>
                    </div>
                    <div id="progressLog" class="border rounded p-3" style="height: 200px; overflow-y: auto; background-color: #f8f9fa; font-family: 'Courier New', monospace; font-size: 12px;">
                        <div class="text-muted">等待任务开始...</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="progressRetryBtn" class="btn btn-warning" onclick="retryExecution()" style="display: none;">
                    <i class="fas fa-redo"></i> 重试
                </button>
                <button type="button" id="progressCloseBtn" class="btn btn-secondary" onclick="closeProgressModal()" disabled>
                    关闭
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // 设置全局变量
    window.taskId = {{ $task->id ?? 0 }};
    window.taskStatus = {{ $task->status ?? 0 }};
</script>
<script src="{{ asset('assets/js/modules/collection-tasks-show.js') }}"></script>
@endpush

@section('scripts')
@endsection
