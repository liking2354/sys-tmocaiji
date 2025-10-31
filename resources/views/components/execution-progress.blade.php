<!-- 执行进度模态框 - 优化布局版本 -->
<div class="modal fade" id="executionProgressModal" tabindex="-1" role="dialog" aria-labelledby="executionProgressModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-xl" role="document" style="max-width: 90vw;">
        <div class="modal-content" style="height: 85vh; display: flex; flex-direction: column;">
            <!-- 固定头部 -->
            <div class="modal-header" style="flex-shrink: 0;">
                <h5 class="modal-title" id="executionProgressTitle">
                    <i class="fas fa-cogs mr-2"></i>执行进度
                </h5>
                <div class="ml-auto">
                    <span class="badge badge-info" id="executionOverallProgress">0%</span>
                </div>
            </div>
            
            <!-- 总体进度条 -->
            <div class="px-3 py-2" style="flex-shrink: 0; border-bottom: 1px solid #dee2e6;">
                <div class="progress" style="height: 6px;">
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

            <!-- 主体内容区域 - 左右分栏 -->
            <div class="modal-body p-0" style="flex: 1; overflow: hidden; display: flex;">
                <!-- 左侧：执行步骤 -->
                <div class="col-4 p-3" style="border-right: 1px solid #dee2e6; height: 100%; overflow-y: auto;">
                    <h6 class="font-weight-bold mb-3">
                        <i class="fas fa-tasks mr-2"></i>执行步骤
                    </h6>
                    <div id="executionStepsList">
                        <!-- 步骤列表将通过JavaScript动态生成 -->
                    </div>
                </div>

                <!-- 右侧：详细日志 -->
                <div class="col-8 p-3" style="height: 100%; display: flex; flex-direction: column;">
                    <h6 class="font-weight-bold mb-3">
                        <i class="fas fa-list-alt mr-2"></i>详细日志
                    </h6>
                    <div id="executionLogContainer" style="flex: 1; overflow-y: auto; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 0.25rem; padding: 15px;">
                        <div id="executionLogContent" style="font-family: 'Courier New', monospace; font-size: 13px; line-height: 1.4; white-space: pre-wrap; color: #495057;"></div>
                    </div>
                </div>
            </div>

            <!-- 固定底部：执行结果和操作按钮 -->
            <div style="flex-shrink: 0;">
                <!-- 执行结果区域 -->
                <div id="executionResultContainer" class="px-3 py-2" style="display: none; border-top: 1px solid #dee2e6;">
                    <div class="alert mb-0" id="executionResultAlert" role="alert">
                        <div class="d-flex align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="alert-heading mb-1" id="executionResultTitle"></h6>
                                <p id="executionResultMessage" class="mb-0"></p>
                            </div>
                            <button class="btn btn-sm btn-secondary ml-2" type="button" id="executionResultToggleBtn" onclick="toggleResultDetails()">
                                <i class="fas fa-chevron-down" id="executionResultToggleIcon"></i>
                            </button>
                        </div>
                        <div id="executionResultDetails" class="mt-2" style="display: none;">
                            <hr class="my-2">
                            <small id="executionResultDetailsContent" style="white-space: pre-wrap; font-family: 'Courier New', monospace;"></small>
                        </div>
                    </div>
                </div>

                <!-- 操作按钮区域 -->
                <div class="modal-footer" style="border-top: 1px solid #dee2e6;">
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div>
                            <small class="text-muted" id="executionStatusText">准备执行...</small>
                        </div>
                        <div>
                            <button type="button" class="btn btn-secondary" id="executionProgressCloseBtn" disabled>
                                <i class="fas fa-times mr-1"></i>关闭
                            </button>
                            <button type="button" class="btn btn-primary ml-2" id="executionProgressRetryBtn" style="display: none;">
                                <i class="fas fa-redo mr-1"></i>重新执行
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 切换结果详情显示
function toggleResultDetails() {
    const details = $('#executionResultDetails');
    const icon = $('#executionResultToggleIcon');
    
    if (details.is(':visible')) {
        details.slideUp();
        icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
    } else {
        details.slideDown();
        icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
    }
}

// 执行进度管理器 - 优化版本
class ExecutionProgressManager {
    constructor() {
        this.modal = null;
        this.steps = [];
        this.currentStep = 0;
        this.isCompleted = false;
        this.onRetry = null;
        this.initialized = false;
        this.startTime = null;
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
        this.startTime = new Date();
        
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
        
        // 更新状态文本
        $('#executionStatusText').text('准备执行...');
        
        // 显示模态框
        this.modal.modal('show');
        
        // 添加初始日志
        this.addLog(`开始执行任务: ${title}`, 'info');
        this.addLog(`总共 ${steps.length} 个步骤`, 'info');
    }

    // 创建步骤列表 - 紧凑版本
    createStepsList() {
        const stepsList = $('#executionStepsList');
        stepsList.empty();
        
        this.steps.forEach((step, index) => {
            const stepHtml = `
                <div class="d-flex align-items-center mb-2 p-2 border rounded-sm" id="execution-step-${index}" style="background-color: #f8f9fa; font-size: 14px;">
                    <div class="step-icon mr-2" style="width: 20px; text-align: center;">
                        <i class="fas fa-circle text-muted" id="execution-step-icon-${index}" style="font-size: 8px;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="step-text" id="execution-step-text-${index}">${step}</div>
                        <div class="step-detail text-muted small" id="execution-step-detail-${index}" style="display: none; font-size: 12px;"></div>
                    </div>
                    <div class="step-status ml-2" id="execution-step-status-${index}">
                        <span class="badge badge-secondary badge-sm">等待</span>
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
        
        // 重置之前步骤的高亮
        $('.execution-step').removeClass('border-primary').addClass('border').css('background-color', '#f8f9fa');
        
        // 高亮当前步骤
        $(`#execution-step-${stepIndex}`)
            .removeClass('border')
            .addClass('border-primary')
            .css('background-color', '#e3f2fd');
        
        // 更新步骤状态
        $(`#execution-step-icon-${stepIndex}`)
            .removeClass('fa-circle text-muted fa-check text-success fa-times text-danger')
            .addClass('fa-spinner fa-spin text-primary')
            .css('font-size', '12px');
        
        $(`#execution-step-status-${stepIndex}`)
            .html('<span class="badge badge-primary badge-sm">执行中</span>');
        
        // 显示详细信息
        if (detail) {
            $(`#execution-step-detail-${stepIndex}`).text(detail).show();
        }
        
        // 更新状态文本
        $('#executionStatusText').text(`正在执行步骤 ${stepIndex + 1}/${this.steps.length}: ${this.steps[stepIndex]}`);
        
        // 添加日志
        this.addLog(`[步骤 ${stepIndex + 1}/${this.steps.length}] 开始: ${this.steps[stepIndex]}${message ? ' - ' + message : ''}`);
        
        // 更新进度
        const progress = (stepIndex / this.steps.length) * 100;
        this.updateProgress(progress);
        
        // 自动滚动步骤列表到当前步骤
        this.scrollToStep(stepIndex);
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
            .addClass(iconClass)
            .css('font-size', '12px');
        
        $(`#execution-step-status-${stepIndex}`)
            .html(`<span class="badge ${statusClass} badge-sm">${statusText}</span>`);
        
        // 更新详细信息
        if (detail) {
            $(`#execution-step-detail-${stepIndex}`).text(detail).show();
        }
        
        // 添加日志
        const logMessage = `[步骤 ${stepIndex + 1}/${this.steps.length}] ${success ? '✓ 完成' : '✗ 失败'}: ${this.steps[stepIndex]}${message ? ' - ' + message : ''}`;
        this.addLog(logMessage, success ? 'success' : 'error');
        
        // 更新进度
        const progress = ((stepIndex + 1) / this.steps.length) * 100;
        this.updateProgress(progress);
        
        // 更新状态文本
        if (stepIndex + 1 < this.steps.length) {
            $('#executionStatusText').text(`步骤 ${stepIndex + 1}/${this.steps.length} ${success ? '完成' : '失败'}，准备下一步...`);
        }
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

    // 滚动到指定步骤
    scrollToStep(stepIndex) {
        const stepElement = $(`#execution-step-${stepIndex}`);
        const container = stepElement.closest('.col-4');
        
        if (stepElement.length && container.length) {
            const elementTop = stepElement.position().top;
            const containerHeight = container.height();
            const scrollTop = container.scrollTop();
            
            if (elementTop < 0 || elementTop > containerHeight - 100) {
                container.animate({
                    scrollTop: scrollTop + elementTop - containerHeight / 2
                }, 300);
            }
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
        const endTime = new Date();
        const duration = Math.round((endTime - this.startTime) / 1000);
        
        const alertClass = success ? 'alert-success' : 'alert-danger';
        const iconClass = success ? 'fa-check-circle' : 'fa-exclamation-circle';
        
        $('#executionResultAlert')
            .removeClass('alert-success alert-danger alert-warning alert-info')
            .addClass(alertClass);
        
        $('#executionResultTitle').html(`<i class="fas ${iconClass} mr-2"></i>${title}`);
        $('#executionResultMessage').text(message);
        
        if (details) {
            $('#executionResultDetailsContent').text(details);
            $('#executionResultToggleBtn').show();
        } else {
            $('#executionResultToggleBtn').hide();
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
        
        // 更新状态文本
        $('#executionStatusText').text(`执行${success ? '完成' : '失败'} (耗时: ${duration}秒)`);
        
        // 添加结果日志
        this.addLog(`执行${success ? '完成' : '失败'}: ${message} (总耗时: ${duration}秒)`, success ? 'success' : 'error');
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

<style>
/* 优化进度模态框样式 */
#executionProgressModal .modal-dialog {
    margin: 2vh auto;
}

#executionProgressModal .badge-sm {
    font-size: 0.7em;
    padding: 0.2em 0.4em;
}

#executionProgressModal .step-text {
    font-weight: 500;
    line-height: 1.3;
}

#executionProgressModal .step-detail {
    margin-top: 2px;
}

/* 自定义滚动条样式 */
#executionProgressModal .col-4::-webkit-scrollbar,
#executionLogContainer::-webkit-scrollbar {
    width: 6px;
}

#executionProgressModal .col-4::-webkit-scrollbar-track,
#executionLogContainer::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

#executionProgressModal .col-4::-webkit-scrollbar-thumb,
#executionLogContainer::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

#executionProgressModal .col-4::-webkit-scrollbar-thumb:hover,
#executionLogContainer::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* 响应式调整 */
@media (max-width: 768px) {
    #executionProgressModal .modal-dialog {
        max-width: 95vw;
        margin: 1vh auto;
    }
    
    #executionProgressModal .modal-content {
        height: 90vh;
    }
    
    #executionProgressModal .modal-body .col-4,
    #executionProgressModal .modal-body .col-8 {
        padding: 10px;
    }
}
</style>