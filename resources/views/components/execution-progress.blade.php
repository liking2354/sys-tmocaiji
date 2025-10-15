<!-- 执行进度模态框 -->
<div class="modal fade" id="executionProgressModal" tabindex="-1" role="dialog" aria-labelledby="executionProgressModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="executionProgressTitle">
                    <i class="fas fa-cogs mr-2"></i>执行进度
                </h5>
            </div>
            <div class="modal-body">
                <!-- 总体进度 -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="font-weight-bold mb-0">
                            <i class="fas fa-chart-line mr-2"></i>总体进度
                        </h6>
                        <span class="badge badge-info" id="executionOverallProgress">0%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" 
                             id="executionOverallProgressBar"
                             style="width: 0%" 
                             aria-valuenow="0" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                        </div>
                    </div>
                </div>

                <!-- 执行步骤 -->
                <div class="mb-3">
                    <h6 class="font-weight-bold mb-2">
                        <i class="fas fa-tasks mr-2"></i>执行步骤
                    </h6>
                    <div id="executionStepsList">
                        <!-- 步骤列表将通过JavaScript动态生成 -->
                    </div>
                </div>

                <!-- 详细日志 -->
                <div class="mb-3">
                    <h6 class="font-weight-bold mb-2">
                        <i class="fas fa-list-alt mr-2"></i>详细日志
                    </h6>
                    <div id="executionLogContainer" style="max-height: 250px; overflow-y: auto; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 0.25rem; padding: 10px;">
                        <div id="executionLogContent" style="font-family: 'Courier New', monospace; font-size: 12px; white-space: pre-wrap; color: #495057;"></div>
                    </div>
                </div>

                <!-- 结果信息 -->
                <div id="executionResultContainer" style="display: none;">
                    <div class="alert" id="executionResultAlert" role="alert">
                        <h6 class="alert-heading" id="executionResultTitle"></h6>
                        <p id="executionResultMessage" class="mb-0"></p>
                        <div id="executionResultDetails" class="mt-2" style="display: none;">
                            <hr>
                            <small id="executionResultDetailsContent" style="white-space: pre-wrap;"></small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="executionProgressCloseBtn" disabled>
                    <i class="fas fa-times mr-1"></i>关闭
                </button>
                <button type="button" class="btn btn-primary" id="executionProgressRetryBtn" style="display: none;">
                    <i class="fas fa-redo mr-1"></i>重新执行
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// 执行进度管理器
class ExecutionProgressManager {
    constructor() {
        this.modal = null;
        this.steps = [];
        this.currentStep = 0;
        this.isCompleted = false;
        this.onRetry = null;
        this.initialized = false;
    }
    
    // 确保jQuery已加载并初始化DOM元素
    ensureInitialized() {
        if (!this.initialized && typeof $ !== 'undefined') {
            this.modal = $('#executionProgressModal');
            this.initialized = true;
        }
        return this.initialized;
    }

    // 初始化进度框
    init(title, steps, onRetry = null) {
        if (!this.ensureInitialized()) {
            console.error('ExecutionProgressManager: jQuery not available');
            return;
        }
        
        this.steps = steps;
        this.currentStep = 0;
        this.isCompleted = false;
        this.onRetry = onRetry;
        
        // 设置标题
        $('#executionProgressTitle').html(`<i class="fas fa-cogs mr-2"></i>${title}`);
        
        // 重置进度条
        this.updateProgress(0);
        
        // 清空并创建步骤列表
        this.createStepsList();
        
        // 清空日志
        $('#executionLogContent').empty();
        
        // 隐藏结果容器
        $('#executionResultContainer').hide();
        
        // 重置按钮状态
        $('#executionProgressCloseBtn').prop('disabled', true).show();
        $('#executionProgressRetryBtn').hide();
        
        // 显示模态框
        this.modal.modal('show');
    }

    // 创建步骤列表
    createStepsList() {
        const stepsList = $('#executionStepsList');
        stepsList.empty();
        
        this.steps.forEach((step, index) => {
            const stepHtml = `
                <div class="d-flex align-items-center mb-2 p-2 border rounded" id="execution-step-${index}" style="background-color: #f8f9fa;">
                    <div class="step-icon mr-3" style="width: 30px; text-align: center;">
                        <i class="fas fa-circle text-muted" id="execution-step-icon-${index}"></i>
                    </div>
                    <div class="flex-grow-1">
                        <span class="step-text font-weight-medium" id="execution-step-text-${index}">${step}</span>
                        <div class="step-detail text-muted small" id="execution-step-detail-${index}" style="display: none;"></div>
                    </div>
                    <div class="step-status ml-2" id="execution-step-status-${index}">
                        <span class="badge badge-secondary">等待中</span>
                    </div>
                </div>
            `;
            stepsList.append(stepHtml);
        });
    }

    // 更新总体进度
    updateProgress(percentage) {
        $('#executionOverallProgress').text(Math.round(percentage) + '%');
        $('#executionOverallProgressBar')
            .css('width', percentage + '%')
            .attr('aria-valuenow', percentage);
    }

    // 开始执行步骤
    startStep(stepIndex, message = '', detail = '') {
        if (stepIndex >= this.steps.length) return;
        
        this.currentStep = stepIndex;
        
        // 高亮当前步骤
        $(`#execution-step-${stepIndex}`)
            .removeClass('border')
            .addClass('border-primary')
            .css('background-color', '#e3f2fd');
        
        // 更新步骤状态
        $(`#execution-step-icon-${stepIndex}`)
            .removeClass('fa-circle text-muted fa-check text-success fa-times text-danger')
            .addClass('fa-spinner fa-spin text-primary');
        
        $(`#execution-step-status-${stepIndex}`)
            .html('<span class="badge badge-primary">执行中</span>');
        
        // 显示详细信息
        if (detail) {
            $(`#execution-step-detail-${stepIndex}`).text(detail).show();
        }
        
        // 添加日志
        this.addLog(`[步骤 ${stepIndex + 1}] ${this.steps[stepIndex]}${message ? ': ' + message : ''}`);
        
        // 更新进度
        const progress = (stepIndex / this.steps.length) * 100;
        this.updateProgress(progress);
    }

    // 完成步骤
    completeStep(stepIndex, success = true, message = '', detail = '') {
        if (stepIndex >= this.steps.length) return;
        
        const iconClass = success ? 'fa-check text-success' : 'fa-times text-danger';
        const statusClass = success ? 'badge-success' : 'badge-danger';
        const statusText = success ? '完成' : '失败';
        const borderClass = success ? 'border-success' : 'border-danger';
        const bgColor = success ? '#e8f5e8' : '#fdeaea';
        
        // 更新步骤样式
        $(`#execution-step-${stepIndex}`)
            .removeClass('border-primary')
            .addClass(borderClass)
            .css('background-color', bgColor);
        
        // 更新步骤状态
        $(`#execution-step-icon-${stepIndex}`)
            .removeClass('fa-spinner fa-spin text-primary fa-circle text-muted')
            .addClass(iconClass);
        
        $(`#execution-step-status-${stepIndex}`)
            .html(`<span class="badge ${statusClass}">${statusText}</span>`);
        
        // 更新详细信息
        if (detail) {
            $(`#execution-step-detail-${stepIndex}`).text(detail).show();
        }
        
        // 添加日志
        const logMessage = `[步骤 ${stepIndex + 1}] ${success ? '✓' : '✗'} ${this.steps[stepIndex]}${message ? ': ' + message : ''}`;
        this.addLog(logMessage);
        
        // 更新进度
        const progress = ((stepIndex + 1) / this.steps.length) * 100;
        this.updateProgress(progress);
    }

    // 更新步骤进度（不完成步骤，只更新进度信息）
    updateStepProgress(stepIndex, message = '', detail = '') {
        if (stepIndex >= this.steps.length) return;
        
        // 更新详细信息
        if (detail) {
            $(`#execution-step-detail-${stepIndex}`).text(detail).show();
        }
        
        // 添加日志（如果有消息）
        if (message) {
            this.addLog(`[步骤 ${stepIndex + 1}] ${message}`);
        }
    }

    // 添加日志
    addLog(message, type = 'info') {
        const timestamp = new Date().toLocaleTimeString();
        const logContent = $('#executionLogContent');
        
        let color = '#495057';
        let icon = '';
        
        switch (type) {
            case 'success':
                color = '#28a745';
                icon = '✓ ';
                break;
            case 'error':
                color = '#dc3545';
                icon = '✗ ';
                break;
            case 'warning':
                color = '#ffc107';
                icon = '⚠ ';
                break;
            case 'info':
            default:
                color = '#495057';
                icon = 'ℹ ';
                break;
        }
        
        const newLog = `<span style="color: #6c757d;">[${timestamp}]</span> <span style="color: ${color};">${icon}${message}</span>\n`;
        logContent.append(newLog);
        
        // 自动滚动到底部
        const container = $('#executionLogContainer');
        container.scrollTop(container[0].scrollHeight);
    }

    // 显示结果
    showResult(success, title, message, details = '') {
        this.isCompleted = true;
        
        const alertClass = success ? 'alert-success' : 'alert-danger';
        const iconClass = success ? 'fa-check-circle' : 'fa-exclamation-circle';
        
        $('#executionResultAlert')
            .removeClass('alert-success alert-danger alert-warning alert-info')
            .addClass(alertClass);
        
        $('#executionResultTitle').html(`<i class="fas ${iconClass} mr-2"></i>${title}`);
        $('#executionResultMessage').text(message);
        
        if (details) {
            $('#executionResultDetailsContent').text(details);
            $('#executionResultDetails').show();
        } else {
            $('#executionResultDetails').hide();
        }
        
        $('#executionResultContainer').show();
        
        // 启用关闭按钮
        $('#executionProgressCloseBtn').prop('disabled', false);
        
        // 显示重试按钮（如果失败且有重试回调）
        if (!success && this.onRetry) {
            $('#executionProgressRetryBtn').show();
        }
        
        // 更新进度到100%
        this.updateProgress(100);
        
        // 添加结果日志
        this.addLog(message, success ? 'success' : 'error');
    }

    // 关闭进度框
    close() {
        this.modal.modal('hide');
    }
}

// 全局执行进度管理器实例
window.executionProgressManager = null;

// 确保在jQuery准备好后初始化
function initExecutionProgressManager() {
    if (typeof $ !== 'undefined' && !window.executionProgressManager) {
        window.executionProgressManager = new ExecutionProgressManager();
        
        // 绑定事件处理
        $(document).ready(function() {
            $('#executionProgressCloseBtn').click(function() {
                if (window.executionProgressManager) {
                    window.executionProgressManager.close();
                }
            });
            
            $('#executionProgressRetryBtn').click(function() {
                if (window.executionProgressManager && window.executionProgressManager.onRetry) {
                    window.executionProgressManager.onRetry();
                }
            });
        });
    }
}

// 尝试立即初始化，如果jQuery还没准备好则等待
if (typeof $ !== 'undefined') {
    initExecutionProgressManager();
} else {
    // 如果jQuery还没加载，等待DOM加载完成后再尝试
    document.addEventListener('DOMContentLoaded', function() {
        // 给jQuery一些时间加载
        setTimeout(initExecutionProgressManager, 100);
    });
}
</script>