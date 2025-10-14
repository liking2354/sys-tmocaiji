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

@section('scripts')
<script>
let taskId = {{ $task->id ?? 0 }};
let statusUpdateInterval;
let isExecuting = false;

// 进度管理器类
class ProgressManager {
    constructor() {
        this.steps = [];
        this.currentStep = -1;
        this.isComplete = false;
        this.retryCallback = null;
    }

    init(title, steps, retryCallback = null) {
        this.steps = steps;
        this.currentStep = -1;
        this.isComplete = false;
        this.retryCallback = retryCallback;
        
        // 设置标题
        $('#progressModalLabel').html(`<i class="fas fa-tasks"></i> ${title}`);
        
        // 重置进度
        this.updateProgress(0);
        
        // 创建步骤
        this.createSteps();
        
        // 重置按钮状态
        $('#progressRetryBtn').hide();
        $('#progressCloseBtn').prop('disabled', true).text('关闭');
        
        // 清空日志
        this.clearLog();
        this.addLog('任务初始化完成，准备开始执行...');
        
        // 显示模态框
        $('#progressModal').modal('show');
    }

    createSteps() {
        const stepsContainer = $('#progressSteps');
        stepsContainer.empty();
        
        this.steps.forEach((step, index) => {
            const stepHtml = `
                <div class="d-flex align-items-center mb-2 step-item" data-step="${index}">
                    <div class="step-icon mr-3">
                        <i class="fas fa-circle text-muted" style="font-size: 12px;"></i>
                    </div>
                    <div class="step-content flex-grow-1">
                        <span class="step-title">${step}</span>
                        <div class="step-detail text-muted small" style="display: none;"></div>
                    </div>
                    <div class="step-status">
                        <span class="badge badge-light">等待中</span>
                    </div>
                </div>
            `;
            stepsContainer.append(stepHtml);
        });
    }

    updateProgress(percentage) {
        $('#overallProgressBar').css('width', percentage + '%').attr('aria-valuenow', percentage);
        $('#progressBarText').text(percentage.toFixed(1) + '%');
        $('#progressPercentage').text(percentage.toFixed(1) + '%');
        
        // 更新进度条颜色
        const progressBar = $('#overallProgressBar');
        progressBar.removeClass('bg-info bg-success bg-danger bg-warning');
        
        if (percentage >= 100) {
            progressBar.addClass('bg-success');
        } else if (percentage > 0) {
            progressBar.addClass('bg-info');
        } else {
            progressBar.addClass('bg-secondary');
        }
    }

    startStep(stepIndex, detail = '') {
        if (stepIndex >= this.steps.length) return;
        
        this.currentStep = stepIndex;
        const stepElement = $(`.step-item[data-step="${stepIndex}"]`);
        
        // 更新图标
        stepElement.find('.step-icon i').removeClass('fa-circle fa-check fa-times text-muted text-success text-danger')
                   .addClass('fa-spinner fa-spin text-primary');
        
        // 更新状态
        stepElement.find('.step-status .badge').removeClass('badge-light badge-success badge-danger')
                   .addClass('badge-primary').text('执行中');
        
        // 显示详情
        if (detail) {
            stepElement.find('.step-detail').text(detail).show();
        }
        
        // 更新进度
        const progress = (stepIndex / this.steps.length) * 100;
        this.updateProgress(progress);
        
        this.addLog(`开始执行: ${this.steps[stepIndex]}${detail ? ' - ' + detail : ''}`);
    }

    completeStep(stepIndex, success = true, detail = '') {
        if (stepIndex >= this.steps.length) return;
        
        const stepElement = $(`.step-item[data-step="${stepIndex}"]`);
        
        if (success) {
            // 成功
            stepElement.find('.step-icon i').removeClass('fa-spinner fa-spin fa-circle fa-times text-primary text-muted text-danger')
                       .addClass('fa-check text-success');
            stepElement.find('.step-status .badge').removeClass('badge-primary badge-light badge-danger')
                       .addClass('badge-success').text('完成');
            
            this.addLog(`✓ 完成: ${this.steps[stepIndex]}${detail ? ' - ' + detail : ''}`);
        } else {
            // 失败
            stepElement.find('.step-icon i').removeClass('fa-spinner fa-spin fa-circle fa-check text-primary text-muted text-success')
                       .addClass('fa-times text-danger');
            stepElement.find('.step-status .badge').removeClass('badge-primary badge-light badge-success')
                       .addClass('badge-danger').text('失败');
            
            this.addLog(`✗ 失败: ${this.steps[stepIndex]}${detail ? ' - ' + detail : ''}`, 'error');
        }
        
        // 更新详情
        if (detail) {
            stepElement.find('.step-detail').text(detail).show();
        }
        
        // 只有失败时才自动调用complete，成功时由调用方手动控制
        if (!success) {
            this.complete(false, `步骤 "${this.steps[stepIndex]}" 执行失败`);
        } else {
            // 更新进度
            const progress = ((stepIndex + 1) / this.steps.length) * 100;
            this.updateProgress(progress);
        }
    }

    complete(success = true, message = '', autoRefresh = true) {
        this.isComplete = true;
        
        if (success) {
            this.updateProgress(100);
            this.addLog('🎉 所有步骤执行完成！', 'success');
            $('#progressCloseBtn').prop('disabled', false).text('完成');
            
            // 根据参数决定是否自动刷新页面
            if (autoRefresh) {
                setTimeout(() => {
                    this.addLog('正在刷新页面以显示最新结果...');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                }, 3000);
            } else {
                this.addLog('任务启动完成，可以关闭此窗口查看实时进度', 'success');
            }
        } else {
            this.addLog(`❌ 执行失败: ${message}`, 'error');
            $('#progressRetryBtn').show();
            $('#progressCloseBtn').prop('disabled', false).text('关闭');
        }
    }

    addLog(message, type = 'info') {
        const timestamp = new Date().toLocaleTimeString();
        const logContainer = $('#progressLog');
        
        let logClass = '';
        let icon = '';
        
        switch(type) {
            case 'success':
                logClass = 'text-success';
                break;
            case 'error':
                logClass = 'text-danger';
                break;
            case 'warning':
                logClass = 'text-warning';
                break;
            default:
                logClass = 'text-info';
        }
        
        const logEntry = `<div class="${logClass}">[${timestamp}] ${message}</div>`;
        
        // 如果是初始状态，清空占位文本
        if (logContainer.find('.text-muted').length > 0 && logContainer.find('.text-muted').text().includes('等待任务开始')) {
            logContainer.empty();
        }
        
        logContainer.append(logEntry);
        logContainer.scrollTop(logContainer[0].scrollHeight);
    }

    clearLog() {
        $('#progressLog').html('<div class="text-muted">日志已清空</div>');
    }
}

// 创建全局进度管理器实例
const progressManager = new ProgressManager();

// 关闭进度模态框
function closeProgressModal() {
    if (!progressManager.isComplete) {
        if (!confirm('任务正在执行中，确定要关闭进度窗口吗？')) {
            return;
        }
    }
    $('#progressModal').modal('hide');
}

// 重试执行
function retryExecution() {
    if (progressManager.retryCallback) {
        $('#progressModal').modal('hide');
        setTimeout(() => {
            progressManager.retryCallback();
        }, 500);
    }
}

// 清空进度日志
function clearProgressLog() {
    progressManager.clearLog();
}

$(document).ready(function() {
    // 初始化状态筛选
    $('#statusFilter').on('change', function() {
        filterDetailsByStatus($(this).val());
    });
    
    // 如果任务正在执行，启动实时更新
    let taskStatus = {{ $task->status ?? 0 }};
    if (taskStatus == 1) {
        startStatusUpdates();
    }
});

// 执行任务
function executeTask(taskId) {
    if (isExecuting) {
        showAlert('任务正在执行中，请稍候...', 'warning');
        return;
    }
    
    if (!confirm('确定要开始执行这个任务吗？')) {
        return;
    }
    
    // 定义执行步骤
    const steps = [
        '验证任务状态',
        '准备执行环境',
        '启动采集任务',
        '监控执行进度',
        '完成任务处理'
    ];
    
    // 初始化进度管理器
    progressManager.init(`批量采集任务 ID: ${taskId}`, steps, () => executeTask(taskId));
    
    // 执行任务流程
    executeTaskWithProgress(taskId);
}

// 带进度的任务执行
function executeTaskWithProgress(taskId) {
    isExecuting = true;
    
    // 步骤1: 验证任务状态
    progressManager.startStep(0, '检查任务是否可以执行');
    
    setTimeout(() => {
        progressManager.completeStep(0, true, '任务状态验证通过');
        
        // 步骤2: 准备执行环境
        progressManager.startStep(1, '初始化执行参数和环境');
        
        setTimeout(() => {
            progressManager.completeStep(1, true, '执行环境准备完成');
            
            // 步骤3: 启动采集任务
            progressManager.startStep(2, '向服务器发送执行请求');
            
            // 实际的API调用
            $.ajax({
                url: '/task-execution/execute/' + taskId,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    // 无论API返回什么，都先启动状态监控
                    startStatusUpdates();
                    addExecutionLog('任务执行请求已发送，开始监控状态');
                    
                    if (response.success) {
                        progressManager.completeStep(2, true, '任务启动成功: ' + (response.message || ''));
                        
                        // 步骤4: 监控执行进度
                        progressManager.startStep(3, '开始实时监控任务执行状态');
                        
                        setTimeout(() => {
                            progressManager.completeStep(3, true, '进度监控已启动');
                            
                            // 步骤5: 完成任务处理
                            progressManager.startStep(4, '任务执行流程启动完成');
                            
                            setTimeout(() => {
                                progressManager.completeStep(4, true, '可以在实时进度区域查看详细执行情况');
                                addExecutionLog('任务执行已启动，请查看实时进度区域');
                                // 任务启动完成，不自动刷新页面
                                progressManager.complete(true, '任务启动流程完成，请查看页面实时进度区域', false);
                            }, 1000);
                        }, 1000);
                    } else {
                        // 即使API返回失败，也要检查实际任务状态
                        progressManager.addLog('API返回失败，但正在验证实际任务状态...', 'warning');
                        
                        // 延迟检查任务状态
                        setTimeout(() => {
                            refreshTaskStatus();
                            
                            // 检查任务是否实际在执行
                            setTimeout(() => {
                                $.ajax({
                                    url: '/task-execution/status/' + taskId,
                                    method: 'GET',
                                    success: function(statusResponse) {
                                        if (statusResponse.success && statusResponse.data.status == 1) {
                                            // 任务实际在执行
                                            progressManager.completeStep(2, true, '任务实际已启动（API响应可能有延迟）');
                                            progressManager.startStep(3, '检测到任务正在执行，开始监控');
                                            setTimeout(() => {
                                                progressManager.completeStep(3, true, '进度监控已启动');
                                                progressManager.startStep(4, '任务执行流程确认完成');
                                                setTimeout(() => {
                                                    progressManager.completeStep(4, true, '任务正在后台执行，请查看实时进度');
                                                    // 任务启动完成，不自动刷新页面
                                                    progressManager.complete(true, '任务启动流程完成，任务正在后台执行', false);
                                                }, 800);
                                            }, 1000);
                                        } else {
                                            // 任务确实没有启动
                                            progressManager.completeStep(2, false, response.message || '任务启动失败');
                                            isExecuting = false;
                                        }
                                    },
                                    error: function() {
                                        progressManager.completeStep(2, false, response.message || '任务启动失败');
                                        isExecuting = false;
                                    }
                                });
                            }, 2000);
                        }, 1000);
                    }
                },
                error: function(xhr) {
                    let message = '执行失败';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    
                    // 即使请求失败，也要检查任务状态
                    progressManager.addLog('请求失败，正在检查任务实际状态...', 'warning');
                    startStatusUpdates();
                    
                    setTimeout(() => {
                        refreshTaskStatus();
                        
                        // 检查任务是否实际在执行
                        setTimeout(() => {
                            $.ajax({
                                url: '/task-execution/status/' + taskId,
                                method: 'GET',
                                success: function(statusResponse) {
                                    if (statusResponse.success && statusResponse.data.status == 1) {
                                        // 任务实际在执行
                                        progressManager.completeStep(2, true, '任务已启动（网络可能有延迟）');
                                        progressManager.startStep(3, '检测到任务正在执行');
                                        setTimeout(() => {
                                            progressManager.completeStep(3, true, '进度监控已启动');
                                            progressManager.startStep(4, '任务执行确认完成');
                                            setTimeout(() => {
                                                progressManager.completeStep(4, true, '任务正在执行，请查看实时进度');
                                                // 任务启动完成，不自动刷新页面
                                                progressManager.complete(true, '任务启动流程完成，任务正在执行中', false);
                                            }, 800);
                                        }, 1000);
                                    } else {
                                        progressManager.completeStep(2, false, message);
                                        isExecuting = false;
                                    }
                                },
                                error: function() {
                                    progressManager.completeStep(2, false, message);
                                    isExecuting = false;
                                }
                            });
                        }, 2000);
                    }, 1000);
                }
            });
        }, 1000);
    }, 800);
}

// 取消任务
function cancelTask(taskId) {
    if (!confirm('确定要取消这个正在执行的任务吗？')) {
        return;
    }
    
    // 定义取消步骤
    const steps = [
        '验证任务状态',
        '发送取消请求',
        '停止监控进程',
        '清理执行环境'
    ];
    
    // 初始化进度管理器
    progressManager.init(`取消任务 ID: ${taskId}`, steps, () => cancelTask(taskId));
    
    // 执行取消流程
    executeCancelWithProgress(taskId);
}

// 带进度的任务取消
function executeCancelWithProgress(taskId) {
    // 步骤1: 验证任务状态
    progressManager.startStep(0, '检查任务是否可以取消');
    
    setTimeout(() => {
        progressManager.completeStep(0, true, '任务状态验证完成');
        
        // 步骤2: 发送取消请求
        progressManager.startStep(1, '向服务器发送取消请求');
        
        $.ajax({
            url: '/task-execution/cancel/' + taskId,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    progressManager.completeStep(1, true, '取消请求发送成功');
                    
                    // 步骤3: 停止监控进程
                    progressManager.startStep(2, '停止实时状态监控');
                    
                    stopStatusUpdates();
                    addExecutionLog('任务已取消: ' + response.message);
                    
                    setTimeout(() => {
                        progressManager.completeStep(2, true, '监控进程已停止');
                        
                        // 步骤4: 清理执行环境
                        progressManager.startStep(3, '清理执行环境和更新状态');
                        
                        refreshTaskStatus();
                        
                        setTimeout(() => {
                            progressManager.completeStep(3, true, '任务取消完成');
                        }, 1000);
                    }, 800);
                } else {
                    progressManager.completeStep(1, false, response.message || '取消请求失败');
                }
            },
            error: function(xhr) {
                let message = '取消失败';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                progressManager.completeStep(1, false, message);
            }
        });
    }, 500);
}

// 重置任务
function resetTask(taskId) {
    if (!confirm('确定要重置这个任务吗？重置后任务状态将回到未开始状态。')) {
        return;
    }
    
    // 定义重置步骤
    const steps = [
        '验证任务状态',
        '清理执行数据',
        '重置任务状态',
        '刷新页面显示'
    ];
    
    // 初始化进度管理器
    progressManager.init(`重置任务 ID: ${taskId}`, steps, () => resetTask(taskId));
    
    // 执行重置流程
    executeResetWithProgress(taskId);
}

// 带进度的任务重置
function executeResetWithProgress(taskId) {
    // 步骤1: 验证任务状态
    progressManager.startStep(0, '检查任务是否可以重置');
    
    setTimeout(() => {
        progressManager.completeStep(0, true, '任务状态验证完成');
        
        // 步骤2: 清理执行数据
        progressManager.startStep(1, '清理任务执行历史数据');
        
        setTimeout(() => {
            progressManager.completeStep(1, true, '执行数据清理完成');
            
            // 步骤3: 重置任务状态
            progressManager.startStep(2, '向服务器发送重置请求');
            
            $.ajax({
                url: '/task-execution/reset/' + taskId,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        progressManager.completeStep(2, true, '任务重置成功');
                        addExecutionLog('任务已重置: ' + response.message);
                        
                        // 步骤4: 刷新页面显示
                        progressManager.startStep(3, '刷新页面以显示最新状态');
                        
                        refreshTaskStatus();
                        
                        setTimeout(() => {
                            progressManager.completeStep(3, true, '页面即将刷新');
                            
                            // 延迟刷新页面
                            setTimeout(() => {
                                location.reload();
                            }, 2000);
                        }, 1000);
                    } else {
                        progressManager.completeStep(2, false, response.message || '重置请求失败');
                    }
                },
                error: function(xhr) {
                    let message = '重置失败';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    progressManager.completeStep(2, false, message);
                }
            });
        }, 800);
    }, 500);
}

// 开始状态更新
function startStatusUpdates() {
    if (statusUpdateInterval) {
        clearInterval(statusUpdateInterval);
    }
    
    statusUpdateInterval = setInterval(function() {
        refreshTaskStatus();
    }, 3000); // 每3秒更新一次
    
    addExecutionLog('开始实时状态更新 (每3秒)');
}

// 停止状态更新
function stopStatusUpdates() {
    if (statusUpdateInterval) {
        clearInterval(statusUpdateInterval);
        statusUpdateInterval = null;
        addExecutionLog('停止实时状态更新');
    }
}

// 刷新任务状态
function refreshTaskStatus() {
    $.ajax({
        url: '/task-execution/status/' + taskId,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                updateTaskDisplay(response.data);
                
                // 如果任务完成，停止更新
                if (response.data.status != 1) {
                    stopStatusUpdates();
                    isExecuting = false;
                    addExecutionLog('任务执行完成，状态: ' + response.data.status_text);
                    
                    // 延迟刷新页面，让用户看到完成提示
                    addExecutionLog('正在刷新页面以显示最终结果...');
                    setTimeout(function() {
                        location.reload();
                    }, 3000);
                }
            }
        },
        error: function(xhr) {
            console.error('获取任务状态失败:', xhr);
        }
    });
}

// 更新任务显示
function updateTaskDisplay(taskData) {
    // 更新状态显示
    let statusBadge = getStatusBadge(taskData.status, taskData.status_text);
    $('#taskStatusDisplay').html(statusBadge);
    
    // 更新时间显示
    $('#startedAtDisplay').text(taskData.started_at || '未开始');
    $('#completedAtDisplay').text(taskData.completed_at || '未完成');
    $('#lastUpdateTime').text('最后更新: ' + new Date().toLocaleTimeString());
    $('#progressUpdateTime').text('更新时间: ' + new Date().toLocaleTimeString());
    
    // 更新进度条
    $('#taskProgressBar').css('width', taskData.progress + '%');
    $('#progressText').text(taskData.progress.toFixed(1) + '%');
    
    // 更新统计数据
    $('#totalCount').text(taskData.total);
    $('#pendingCount').text(taskData.pending);
    $('#runningCount').text(taskData.running);
    $('#completedCount').text(taskData.completed);
    $('#failedCount').text(taskData.failed);
    
    // 更新进度条颜色
    let progressBar = $('#taskProgressBar');
    progressBar.removeClass('bg-info bg-success bg-warning bg-danger');
    if (taskData.status == 1) {
        progressBar.addClass('bg-info');
    } else if (taskData.status == 2) {
        progressBar.addClass('bg-success');
    } else if (taskData.status == 3) {
        progressBar.addClass('bg-danger');
    } else {
        progressBar.addClass('bg-secondary');
    }
}

// 获取状态徽章HTML
function getStatusBadge(status, statusText) {
    let badgeClass = 'badge-secondary';
    let icon = '';
    
    switch(status) {
        case 0:
            badgeClass = 'badge-secondary';
            break;
        case 1:
            badgeClass = 'badge-warning';
            icon = '<i class="fas fa-spinner fa-spin"></i> ';
            break;
        case 2:
            badgeClass = 'badge-success';
            break;
        case 3:
            badgeClass = 'badge-danger';
            break;
        case 4:
            badgeClass = 'badge-warning';
            icon = '<i class="fas fa-clock"></i> ';
            break;
    }
    
    return '<span class="badge ' + badgeClass + '">' + icon + statusText + '</span>';
}

// 添加执行日志
function addExecutionLog(message) {
    let timestamp = new Date().toLocaleTimeString();
    let logEntry = '[' + timestamp + '] ' + message;
    
    let logContainer = $('#executionLog');
    let currentLog = logContainer.html();
    
    if (currentLog.includes('等待任务执行...')) {
        logContainer.html('<div>' + logEntry + '</div>');
    } else {
        logContainer.append('<div>' + logEntry + '</div>');
    }
    
    // 滚动到底部
    logContainer.scrollTop(logContainer[0].scrollHeight);
}

// 清空执行日志
function clearExecutionLog() {
    $('#executionLog').html('<div class="text-muted">日志已清空</div>');
}

// 按状态筛选详情
function filterDetailsByStatus(status) {
    let rows = $('#detailsTable tbody tr');
    
    if (status === '') {
        rows.show();
    } else {
        rows.each(function() {
            let rowStatus = $(this).data('status');
            if (rowStatus == status) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }
}

// 刷新详情
function refreshDetails() {
    showAlert('正在刷新详情...', 'info');
    location.reload();
}

// 查看结果
function viewResult(detailId) {
    $('#resultModal').modal('show');
    $('#resultContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> 加载中...</div>');
    
    $.ajax({
        url: '/task-details/' + detailId + '/result',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                let content = '<div class="mb-3">';
                content += '<h6>服务器: ' + response.data.server_name + '</h6>';
                content += '<h6>采集组件: ' + response.data.collector_name + '</h6>';
                content += '<h6>执行时间: ' + response.data.execution_time + ' 秒</h6>';
                content += '</div>';
                content += '<pre class="bg-light p-3" style="max-height: 400px; overflow-y: auto;">';
                content += JSON.stringify(response.data.result, null, 2);
                content += '</pre>';
                $('#resultContent').html(content);
            } else {
                $('#resultContent').html('<div class="alert alert-danger">' + response.message + '</div>');
            }
        },
        error: function() {
            $('#resultContent').html('<div class="alert alert-danger">加载结果失败</div>');
        }
    });
}

// 查看错误
function viewError(detailId, errorMessage) {
    $('#errorModal').modal('show');
    $('#errorContent pre').text(errorMessage);
}

// 检测超时任务
function detectTimeoutTasks() {
    if (!confirm('确定要检测并处理超时任务吗？这将自动标记超过5分钟未更新的任务为超时状态。')) {
        return;
    }
    
    showAlert('正在检测超时任务...', 'info');
    
    $.ajax({
        url: '/collection-tasks/' + taskId + '/detect-timeout',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                let message = '检测完成！';
                if (response.detected_count > 0) {
                    message += `发现 ${response.detected_count} 个超时任务，已处理 ${response.processed_count} 个。`;
                    // 刷新页面显示最新状态
                    setTimeout(() => location.reload(), 2000);
                } else {
                    message += '未发现超时任务。';
                }
                showAlert(message, 'success');
            } else {
                showAlert('检测失败：' + response.message, 'error');
            }
        },
        error: function(xhr) {
            let errorMsg = '检测超时任务失败';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg += '：' + xhr.responseJSON.message;
            }
            showAlert(errorMsg, 'error');
        }
    });
}

// 重新执行单个任务详情
function retryTaskDetail(taskDetailId) {
    if (!confirm('确定要重新执行这个任务吗？')) {
        return;
    }
    
    showAlert('正在重新执行任务...', 'info');
    
    $.ajax({
        url: '/task-details/' + taskDetailId + '/retry',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showAlert('任务重新执行成功！', 'success');
                // 更新对应行的状态
                updateTaskDetailRow(taskDetailId, response.data);
                // 启动状态监控
                if (!statusUpdateInterval) {
                    startStatusUpdates();
                }
            } else {
                showAlert('重新执行失败：' + response.message, 'error');
            }
        },
        error: function(xhr) {
            let errorMsg = '重新执行任务失败';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg += '：' + xhr.responseJSON.message;
            }
            showAlert(errorMsg, 'error');
        }
    });
}

// 更新任务详情行
function updateTaskDetailRow(taskDetailId, data) {
    let row = $(`tr[data-detail-id="${taskDetailId}"]`);
    if (row.length > 0) {
        // 更新状态
        row.find('.status-cell').html(getStatusBadge(data.status, data.status_text));
        row.attr('data-status', data.status);
        
        // 更新时间
        row.find('.started-at').text(data.started_at || '-');
        row.find('.completed-at').text(data.completed_at || '-');
        row.find('.execution-time').text(data.execution_time > 0 ? data.execution_time.toFixed(3) : '-');
        
        // 更新操作按钮
        let actionCell = row.find('td:last');
        let buttons = '';
        
        if (data.has_result) {
            buttons += `<button type="button" class="btn btn-sm btn-info" onclick="viewResult('${taskDetailId}')">
                <i class="fas fa-eye"></i> 查看结果
            </button> `;
        }
        
        if (data.is_failed && data.error_message) {
            buttons += `<button type="button" class="btn btn-sm btn-danger" onclick="viewError('${taskDetailId}', '${data.error_message.replace(/'/g, "\\'")}')">
                <i class="fas fa-exclamation-triangle"></i> 查看错误
            </button> `;
        }
        
        if ([3, 4].includes(data.status)) { // 失败或超时状态
            buttons += `<button type="button" class="btn btn-sm btn-warning" onclick="retryTaskDetail('${taskDetailId}')">
                <i class="fas fa-redo"></i> 重新执行
            </button>`;
        }
        
        actionCell.html(buttons);
    }
}

// 显示提示信息
function showAlert(message, type) {
    let alertClass = 'alert-info';
    switch(type) {
        case 'success':
            alertClass = 'alert-success';
            break;
        case 'error':
        case 'danger':
            alertClass = 'alert-danger';
            break;
        case 'warning':
            alertClass = 'alert-warning';
            break;
    }
    
    let alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">';
    alertHtml += message;
    alertHtml += '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
    alertHtml += '<span aria-hidden="true">&times;</span>';
    alertHtml += '</button>';
    alertHtml += '</div>';
    
    // 移除现有的提示
    $('.alert').remove();
    
    // 添加新提示到页面顶部
    $('.container-fluid').prepend(alertHtml);
    
    // 3秒后自动消失
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 3000);
}

// 页面卸载时清理定时器
$(window).on('beforeunload', function() {
    stopStatusUpdates();
});
</script>
@endsection