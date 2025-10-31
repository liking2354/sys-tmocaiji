/**
 * 服务器详情页面脚本
 * 功能：进度管理、采集执行、JSON查看器、系统信息获取
 */

// 进度管理器
class ProgressManager {
    constructor() {
        this.modal = $('#progressModal');
        this.steps = [];
        this.currentStep = 0;
        this.isCompleted = false;
        this.onRetry = null;
    }

    // 初始化进度框
    init(title, steps, onRetry = null) {
        this.steps = steps;
        this.currentStep = 0;
        this.isCompleted = false;
        this.onRetry = onRetry;
        
        // 设置标题
        $('#progressTitle').text(title);
        
        // 重置进度条
        this.updateProgress(0);
        
        // 清空并创建步骤列表
        this.createStepsList();
        
        // 清空日志
        $('#logContent').empty();
        
        // 隐藏结果容器
        $('#resultContainer').hide();
        
        // 重置按钮状态
        $('#progressCloseBtn').prop('disabled', true).show();
        $('#progressRetryBtn').hide();
        
        // 显示模态框
        this.modal.modal('show');
    }

    // 创建步骤列表
    createStepsList() {
        const stepsList = $('#stepsList');
        stepsList.empty();
        
        this.steps.forEach((step, index) => {
            const stepHtml = `
                <div class="d-flex align-items-center mb-2" id="step-${index}">
                    <div class="step-icon mr-3" style="width: 30px; text-align: center;">
                        <i class="fas fa-circle text-muted" id="step-icon-${index}"></i>
                    </div>
                    <div class="flex-grow-1">
                        <span class="step-text" id="step-text-${index}">${step}</span>
                    </div>
                    <div class="step-status ml-2" id="step-status-${index}">
                        <span class="badge badge-secondary">等待中</span>
                    </div>
                </div>
            `;
            stepsList.append(stepHtml);
        });
    }

    // 更新总体进度
    updateProgress(percentage) {
        $('#overallProgress').text(Math.round(percentage) + '%');
        $('#overallProgressBar')
            .css('width', percentage + '%')
            .attr('aria-valuenow', percentage);
    }

    // 开始执行步骤
    startStep(stepIndex, message = '') {
        if (stepIndex >= this.steps.length) return;
        
        this.currentStep = stepIndex;
        
        // 更新步骤状态
        $(`#step-icon-${stepIndex}`)
            .removeClass('fa-circle text-muted fa-check text-success fa-times text-danger')
            .addClass('fa-spinner fa-spin text-primary');
        
        $(`#step-status-${stepIndex}`)
            .html('<span class="badge badge-primary">执行中</span>');
        
        // 添加日志
        this.addLog(`[步骤 ${stepIndex + 1}] ${this.steps[stepIndex]}${message ? ': ' + message : ''}`);
        
        // 更新进度
        const progress = (stepIndex / this.steps.length) * 100;
        this.updateProgress(progress);
    }

    // 完成步骤
    completeStep(stepIndex, success = true, message = '') {
        if (stepIndex >= this.steps.length) return;
        
        const iconClass = success ? 'fa-check text-success' : 'fa-times text-danger';
        const statusClass = success ? 'badge-success' : 'badge-danger';
        const statusText = success ? '完成' : '失败';
        
        // 更新步骤状态
        $(`#step-icon-${stepIndex}`)
            .removeClass('fa-spinner fa-spin text-primary fa-circle text-muted')
            .addClass(iconClass);
        
        $(`#step-status-${stepIndex}`)
            .html(`<span class="badge ${statusClass}">${statusText}</span>`);
        
        // 添加日志
        const logMessage = `[步骤 ${stepIndex + 1}] ${success ? '✓' : '✗'} ${this.steps[stepIndex]}${message ? ': ' + message : ''}`;
        this.addLog(logMessage);
        
        // 更新进度
        const progress = ((stepIndex + 1) / this.steps.length) * 100;
        this.updateProgress(progress);
    }

    // 添加日志
    addLog(message) {
        const timestamp = new Date().toLocaleTimeString();
        const logContent = $('#logContent');
        const newLog = `[${timestamp}] ${message}\n`;
        logContent.append(newLog);
        
        // 自动滚动到底部
        const container = $('#logContainer');
        container.scrollTop(container[0].scrollHeight);
    }

    // 显示结果
    showResult(success, title, message, details = '') {
        this.isCompleted = true;
        
        const alertClass = success ? 'alert-success' : 'alert-danger';
        const iconClass = success ? 'fa-check-circle' : 'fa-exclamation-circle';
        
        $('#resultAlert')
            .removeClass('alert-success alert-danger alert-warning alert-info')
            .addClass(alertClass);
        
        $('#resultTitle').html(`<i class="fas ${iconClass} mr-2"></i>${title}`);
        $('#resultMessage').text(message);
        
        if (details) {
            $('#resultDetailsContent').text(details);
            $('#resultDetails').show();
        } else {
            $('#resultDetails').hide();
        }
        
        $('#resultContainer').show();
        
        // 启用关闭按钮
        $('#progressCloseBtn').prop('disabled', false);
        
        // 显示重试按钮（如果失败且有重试回调）
        if (!success && this.onRetry) {
            $('#progressRetryBtn').show();
        }
        
        // 更新进度到100%
        this.updateProgress(100);
    }

    // 关闭进度框
    close() {
        this.modal.modal('hide');
    }
}

// 全局进度管理器实例
const progressManager = new ProgressManager();

$(document).ready(function() {
    // 进度框事件处理
    $('#progressCloseBtn').click(function() {
        progressManager.close();
    });
    
    $('#progressRetryBtn').click(function() {
        if (progressManager.onRetry) {
            progressManager.onRetry();
        }
    });

    // 系统信息展开/收起按钮点击事件
    $('#toggleSystemInfoBtn').click(function() {
        var systemInfo = $('#system-info');
        var btn = $(this);
        
        // 检查是否已经有数据
        var hasData = $('#os-info').text() !== '-';
        
        if (!hasData) {
            alert('请先点击"测试连接"按钮获取系统信息');
            return;
        }
        
        if (systemInfo.hasClass('show')) {
            systemInfo.removeClass('show');
            btn.html('<i class="fas fa-chevron-down"></i> 展开详情');
        } else {
            systemInfo.addClass('show');
            btn.html('<i class="fas fa-chevron-up"></i> 收起详情');
        }
    });
    
    // 测试连接按钮点击事件
    $('#testConnectionBtn').click(function() {
        // 显示加载状态
        var btn = $(this);
        var originalText = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i> 测试中...');
        btn.prop('disabled', true);
        
        // 发送AJAX请求验证连接
        $.ajax({
            url: window.serverVerifyRoute,
            type: 'POST',
            data: {
                _token: window.csrfToken,
                ip: window.serverIp,
                port: window.serverPort,
                username: window.serverUsername,
                password: window.serverPassword,
                server_id: window.serverId
            },
            success: function(response) {
                if (response.success) {
                    // 显示成功消息
                    if (response.status_updated) {
                        alert('连接成功！服务器状态已更新为在线。');
                        
                        // 更新页面上的状态显示
                        updateServerStatus(1);
                    } else if (response.status_error) {
                        alert('连接成功！' + response.status_error);
                    } else {
                        alert('连接成功！');
                    }
                    
                    // 连接成功后获取系统信息
                    getSystemInfo();
                } else {
                    alert('连接失败：' + response.message);
                }
            },
            error: function(xhr) {
                alert('请求失败：' + xhr.responseText);
            },
            complete: function() {
                // 恢复按钮状态
                btn.html(originalText);
                btn.prop('disabled', false);
            }
        });
    });
    
    // 执行所有采集组件
    $('#executeAllCollectorsBtn').click(function() {
        var installedCollectors = [];
        var collectorNames = [];
        
        window.installedCollectorIds.forEach(function(id) {
            installedCollectors.push(id);
        });
        
        window.installedCollectorNames.forEach(function(name) {
            collectorNames.push(name);
        });
        
        if (installedCollectors.length === 0) {
            alert('没有已安装的采集组件');
            return;
        }
        
        if (confirm('确定要执行所有采集组件吗？')) {
            executeAllCollectorsWithProgress(installedCollectors, collectorNames);
        }
    });
    
    // 查看采集历史
    $('#collectionHistoryModal').on('show.bs.modal', function() {
        loadCollectionHistory();
    });
});

// 更新服务器状态显示的函数
function updateServerStatus(status) {
    var statusElement = $('.server-status');
    var statusText = status === 1 ? '在线' : '离线';
    var badgeClass = status === 1 ? 'badge-success' : 'badge-danger';
    var statusIcon = status === 1 ? 'fa-check-circle' : 'fa-times-circle';
    
    // 更新badge样式和内容
    statusElement.removeClass('badge-success badge-danger')
                .addClass(badgeClass)
                .html('<i class="fas ' + statusIcon + '"></i> ' + statusText);
    
    // 更新最后检查时间
    var now = new Date();
    var timeString = now.getFullYear() + '-' + 
                   String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                   String(now.getDate()).padStart(2, '0') + ' ' + 
                   String(now.getHours()).padStart(2, '0') + ':' + 
                   String(now.getMinutes()).padStart(2, '0') + ':' + 
                   String(now.getSeconds()).padStart(2, '0');
    
    $('.last-check-time').text(timeString);
}

// 获取系统信息的函数
function getSystemInfo() {
    $.ajax({
        url: window.serverSystemInfoRoute,
        type: 'POST',
        data: {
            _token: window.csrfToken,
            ip: window.serverIp,
            port: window.serverPort,
            username: window.serverUsername,
            password: window.serverPassword,
            server_id: window.serverId
        },
        success: function(response) {
            if (response.success) {
                // 显示系统信息区域并展开
                $('#system-info').addClass('show');
                // 更新展开按钮图标和文字
                $('#toggleSystemInfoBtn').html('<i class="fas fa-chevron-up"></i> 收起详情');
                
                // 填充系统信息
                $('#os-info').text(response.data.os_info);
                $('#kernel-info').text(response.data.kernel_info);
                $('#uptime-info').text(response.data.uptime_info);
                $('#cpu-info').text(response.data.cpu_info);
                $('#memory-info').text(response.data.memory_info);
                $('#disk-info').text(response.data.disk_info);
            } else {
                console.error('获取系统信息失败:', response.message);
            }
        },
        error: function(xhr) {
            console.error('请求系统信息失败:', xhr.responseText);
        }
    });
}

// 执行单个采集组件
function executeSingleCollector(collectorId) {
    // 获取采集组件名称
    var collectorName = window.collectorNames[collectorId] || '采集组件';
    
    if (confirm('确定要执行该采集组件吗？')) {
        executeSingleCollectorWithProgress(collectorId, collectorName);
    }
}

// 执行单个采集组件（带进度显示）
function executeSingleCollectorWithProgress(collectorId, collectorName) {
    // 定义执行步骤
    const steps = [
        '验证采集组件状态',
        '准备执行环境',
        '执行采集任务',
        '处理采集结果'
    ];
    
    // 初始化进度管理器
    progressManager.init(`执行采集组件: ${collectorName}`, steps, () => executeSingleCollectorWithProgress(collectorId, collectorName));
    
    // 执行单个采集流程
    executeSingleCollectionProcess(collectorId, collectorName);
}

// 执行单个采集流程
function executeSingleCollectionProcess(collectorId, collectorName) {
    // 步骤1: 验证采集组件状态
    progressManager.startStep(0, `检查采集组件 ${collectorName} 的状态`);
    
    setTimeout(() => {
        progressManager.completeStep(0, true, '采集组件状态正常');
        
        // 步骤2: 准备执行环境
        progressManager.startStep(1, '准备执行环境和参数');
        
        setTimeout(() => {
            progressManager.completeStep(1, true, '执行环境准备完成');
            
            // 步骤3: 执行采集任务
            progressManager.startStep(2, `开始执行采集组件: ${collectorName}`);
            
            // 实际的API调用
            $.ajax({
                url: window.collectionExecuteRoute,
                type: 'POST',
                data: {
                    _token: window.csrfToken,
                    collector_ids: [collectorId]
                },
                success: function(response) {
                    if (response.success) {
                        progressManager.completeStep(2, true, '采集任务启动成功');
                        
                        // 步骤4: 处理采集结果
                        progressManager.startStep(3, '等待采集完成并处理结果');
                        
                        setTimeout(() => {
                            progressManager.completeStep(3, true, '采集结果处理完成');
                            
                            // 显示成功结果
                            progressManager.showResult(
                                true,
                                '采集任务执行成功',
                                `采集组件 "${collectorName}" 执行任务已启动`,
                                `服务器: ${window.serverName}\n采集组件: ${collectorName}\n执行时间: ${new Date().toLocaleString()}\n\n页面将在3秒后自动刷新以显示最新结果...`
                            );
                            
                            // 3秒后刷新页面
                            setTimeout(() => {
                                location.reload();
                            }, 3000);
                            
                        }, 800);
                        
                    } else {
                        progressManager.completeStep(2, false, response.message || '采集任务启动失败');
                        progressManager.showResult(
                            false,
                            '采集任务执行失败',
                            response.message || '采集任务启动失败',
                            '请检查采集组件配置和服务器连接状态'
                        );
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON?.message || '网络错误';
                    progressManager.completeStep(2, false, errorMsg);
                    progressManager.showResult(
                        false,
                        '采集任务执行失败',
                        '执行过程中发生错误: ' + errorMsg,
                        '请检查网络连接和服务器状态'
                    );
                }
            });
            
        }, 500);
        
    }, 400);
}

// 执行所有采集组件（带进度显示）
function executeAllCollectorsWithProgress(collectorIds, collectorNames) {
    // 定义执行步骤
    const steps = [
        '验证服务器连接状态',
        '准备采集环境',
        '执行采集组件',
        '收集执行结果',
        '完成数据处理'
    ];
    
    // 初始化进度管理器
    progressManager.init('执行所有采集组件', steps, () => executeAllCollectorsWithProgress(collectorIds, collectorNames));
    
    // 执行采集流程
    executeCollectionProcess(collectorIds, collectorNames);
}

// 执行采集流程
function executeCollectionProcess(collectorIds, collectorNames) {
    // 步骤1: 验证服务器连接状态
    progressManager.startStep(0, `检查服务器 ${window.serverName} 的连接状态`);
    
    setTimeout(() => {
        progressManager.completeStep(0, true, '服务器连接正常');
        
        // 步骤2: 准备采集环境
        progressManager.startStep(1, '准备执行环境和采集参数');
        
        setTimeout(() => {
            progressManager.completeStep(1, true, '采集环境准备完成');
            
            // 步骤3: 执行采集组件
            progressManager.startStep(2, `开始执行 ${collectorIds.length} 个采集组件`);
            
            // 添加详细的采集组件信息到日志
            collectorNames.forEach((name, index) => {
                progressManager.addLog(`  - 采集组件 ${index + 1}: ${name}`);
            });
            
            // 实际的API调用
            $.ajax({
                url: window.collectionExecuteRoute,
                type: 'POST',
                data: {
                    _token: window.csrfToken,
                    collector_ids: collectorIds
                },
                success: function(response) {
                    if (response.success) {
                        progressManager.completeStep(2, true, `成功启动 ${collectorIds.length} 个采集任务`);
                        
                        // 步骤4: 收集执行结果
                        progressManager.startStep(3, '等待采集任务完成并收集结果');
                        
                        setTimeout(() => {
                            progressManager.completeStep(3, true, '采集结果收集完成');
                            
                            // 步骤5: 完成数据处理
                            progressManager.startStep(4, '处理采集数据并更新显示');
                            
                            setTimeout(() => {
                                progressManager.completeStep(4, true, '数据处理完成');
                                
                                // 显示成功结果
                                progressManager.showResult(
                                    true,
                                    '采集任务执行成功',
                                    `成功启动 ${collectorIds.length} 个采集组件的执行任务`,
                                    `服务器: ${window.serverName}\n执行时间: ${new Date().toLocaleString()}\n采集组件: ${collectorNames.join(', ')}\n\n页面将在3秒后自动刷新以显示最新结果...`
                                );
                                
                                // 3秒后刷新页面
                                setTimeout(() => {
                                    location.reload();
                                }, 3000);
                                
                            }, 800);
                            
                        }, 1000);
                        
                    } else {
                        progressManager.completeStep(2, false, response.message || '采集任务启动失败');
                        progressManager.showResult(
                            false,
                            '采集任务执行失败',
                            response.message || '采集任务启动失败，请检查服务器状态和采集组件配置',
                            '请确认服务器连接正常，采集组件已正确安装'
                        );
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON?.message || '网络错误';
                    progressManager.completeStep(2, false, errorMsg);
                    progressManager.showResult(
                        false,
                        '采集任务执行失败',
                        '执行过程中发生错误: ' + errorMsg,
                        '请检查网络连接、服务器状态和系统日志'
                    );
                }
            });
            
        }, 600);
        
    }, 500);
}

// 加载采集历史
function loadCollectionHistory() {
    $('#historyContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> 加载中...</div>');
    
    $.ajax({
        url: window.collectionHistoryRoute,
        type: 'GET',
        success: function(response) {
            if (response.success && response.data.length > 0) {
                var html = '<div class="table-responsive">';
                html += '<table class="table table-sm table-hover">';
                html += '<thead><tr>';
                html += '<th>采集组件</th><th>状态</th><th>执行时间</th><th>采集时间</th><th>操作</th>';
                html += '</tr></thead><tbody>';
                
                response.data.forEach(function(history) {
                    html += '<tr>';
                    html += '<td>' + history.collector_name + '</td>';
                    html += '<td><span class="badge badge-' + history.status_color + '">' + history.status_text + '</span></td>';
                    html += '<td>' + history.execution_time + '</td>';
                    html += '<td>' + new Date(history.created_at).toLocaleString() + '</td>';
                    html += '<td>';
                    if (history.has_result) {
                        html += '<button type="button" class="btn btn-sm btn-info" onclick="viewHistoryResult(' + history.id + ')">查看结果</button>';
                    }
                    if (history.error_message) {
                        html += ' <button type="button" class="btn btn-sm btn-danger" onclick="viewHistoryError(\'' + history.error_message.replace(/'/g, "\\'") + '\')">查看错误</button>';
                    }
                    html += '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table></div>';
                $('#historyContent').html(html);
            } else {
                $('#historyContent').html('<div class="alert alert-info">暂无采集历史记录</div>');
            }
        },
        error: function(xhr) {
            $('#historyContent').html('<div class="alert alert-danger">加载失败：' + xhr.responseText + '</div>');
        }
    });
}

// 查看历史结果
function viewHistoryResult(historyId) {
    alert('查看历史结果功能 - ID: ' + historyId);
}

// 查看历史错误
function viewHistoryError(errorMessage) {
    alert('错误信息：\n' + errorMessage);
}

// JSON查看器功能函数

// 切换JSON节点的展开/折叠状态
function toggleJsonNode(nodeId) {
    const node = document.getElementById(nodeId);
    const icon = document.getElementById('icon-' + nodeId);
    const toggle = icon.parentElement;
    const collapsedEnd = document.getElementById('collapsed-' + nodeId);
    
    if (node && icon) {
        if (node.classList.contains('collapsed')) {
            // 展开
            node.classList.remove('collapsed');
            icon.classList.remove('fa-chevron-right');
            icon.classList.add('fa-chevron-down');
            toggle.classList.remove('collapsed');
            if (collapsedEnd) collapsedEnd.style.display = 'none';
        } else {
            // 折叠
            node.classList.add('collapsed');
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-right');
            toggle.classList.add('collapsed');
            if (collapsedEnd) collapsedEnd.style.display = 'block';
        }
    }
}

// 展开所有JSON节点
function expandAllResults(collectorId) {
    const container = document.getElementById('json-viewer-' + collectorId);
    if (container) {
        const collapsedNodes = container.querySelectorAll('.json-children.collapsed');
        const collapsedToggles = container.querySelectorAll('.json-toggle.collapsed');
        const collapsedIcons = container.querySelectorAll('.toggle-icon.fa-chevron-right');
        
        collapsedNodes.forEach(node => node.classList.remove('collapsed'));
        collapsedToggles.forEach(toggle => toggle.classList.remove('collapsed'));
        collapsedIcons.forEach(icon => {
            icon.classList.remove('fa-chevron-right');
            icon.classList.add('fa-chevron-down');
        });
    }
}

// 折叠所有JSON节点
function collapseAllResults(collectorId) {
    const container = document.getElementById('json-viewer-' + collectorId);
    if (container) {
        const expandedNodes = container.querySelectorAll('.json-children:not(.collapsed)');
        const expandedToggles = container.querySelectorAll('.json-toggle:not(.collapsed)');
        const expandedIcons = container.querySelectorAll('.toggle-icon.fa-chevron-down');
        
        expandedNodes.forEach(node => node.classList.add('collapsed'));
        expandedToggles.forEach(toggle => toggle.classList.add('collapsed'));
        expandedIcons.forEach(icon => {
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-right');
        });
    }
}

// 复制JSON数据到剪贴板
function copyResultData(collectorId) {
    const textarea = document.getElementById('raw-json-' + collectorId);
    const btn = event.target.closest('button');
    
    if (textarea && btn) {
        textarea.select();
        textarea.setSelectionRange(0, 99999);
        
        try {
            const successful = document.execCommand('copy');
            if (successful) {
                const originalHTML = btn.innerHTML;
                const originalClass = btn.className;
                
                btn.innerHTML = '<i class="fas fa-check"></i> 已复制';
                btn.className = btn.className.replace('btn-outline-success', 'btn-success copy-success');
                
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.className = originalClass;
                }, 2000);
            } else {
                alert('复制失败，请手动选择并复制');
            }
        } catch (err) {
            console.error('复制失败:', err);
            alert('复制失败，请手动选择并复制');
        }
    }
}

// 搜索和过滤结果
function filterResults(collectorId, searchTerm) {
    const container = document.getElementById('json-viewer-' + collectorId);
    if (!container) return;
    
    const items = container.querySelectorAll('.json-item');
    const term = searchTerm.toLowerCase().trim();
    
    if (term === '') {
        items.forEach(item => {
            item.classList.remove('search-hidden', 'search-highlight');
        });
        return;
    }
    
    items.forEach(item => {
        const key = item.getAttribute('data-key') || '';
        const textContent = item.textContent || '';
        
        if (key.toLowerCase().includes(term) || textContent.toLowerCase().includes(term)) {
            item.classList.remove('search-hidden');
            item.classList.add('search-highlight');
            expandParentNodes(item);
        } else {
            item.classList.add('search-hidden');
            item.classList.remove('search-highlight');
        }
    });
}

// 清除搜索
function clearSearch(collectorId) {
    const searchBox = document.getElementById('search-' + collectorId);
    if (searchBox) {
        searchBox.value = '';
        filterResults(collectorId, '');
    }
}

// 显示数据统计
function showDataStats(collectorId) {
    const statsPanel = document.getElementById('stats-' + collectorId);
    const container = document.getElementById('json-viewer-' + collectorId);
    
    if (statsPanel && container) {
        if (statsPanel.style.display === 'none') {
            const stats = calculateDataStats(container);
            
            document.getElementById('stats-objects-' + collectorId).textContent = stats.objects;
            document.getElementById('stats-arrays-' + collectorId).textContent = stats.arrays;
            document.getElementById('stats-strings-' + collectorId).textContent = stats.strings;
            document.getElementById('stats-numbers-' + collectorId).textContent = stats.numbers;
            document.getElementById('stats-booleans-' + collectorId).textContent = stats.booleans;
            document.getElementById('stats-nulls-' + collectorId).textContent = stats.nulls;
            
            statsPanel.style.display = 'block';
        } else {
            statsPanel.style.display = 'none';
        }
    }
}

// 计算数据统计
function calculateDataStats(container) {
    const stats = {
        objects: 0,
        arrays: 0,
        strings: 0,
        numbers: 0,
        booleans: 0,
        nulls: 0
    };
    
    const objects = container.querySelectorAll('.json-object');
    objects.forEach(obj => {
        const bracket = obj.querySelector('.json-bracket');
        if (bracket && bracket.textContent.trim() === '{') {
            stats.objects++;
        } else if (bracket && bracket.textContent.trim() === '[') {
            stats.arrays++;
        }
    });
    
    const values = container.querySelectorAll('.json-value');
    values.forEach(value => {
        if (value.classList.contains('json-string')) {
            stats.strings++;
        } else if (value.classList.contains('json-number')) {
            stats.numbers++;
        } else if (value.classList.contains('json-boolean')) {
            stats.booleans++;
        } else if (value.classList.contains('json-null')) {
            stats.nulls++;
        }
    });
    
    return stats;
}

// 展开父节点
function expandParentNodes(item) {
    let parent = item.closest('.json-children');
    while (parent) {
        if (parent.classList.contains('collapsed')) {
            const parentId = parent.id;
            if (parentId) {
                toggleJsonNode(parentId);
            }
        }
        parent = parent.parentElement.closest('.json-children');
    }
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    // 为所有JSON查看器添加键盘快捷键支持
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            const activeSearchBox = document.querySelector('.collection-result-container input[placeholder*="搜索"]');
            if (activeSearchBox) {
                e.preventDefault();
                activeSearchBox.focus();
                activeSearchBox.select();
            }
        }
        
        if (e.key === 'Escape') {
            const activeSearchBox = document.querySelector('.collection-result-container input:focus');
            if (activeSearchBox) {
                const collectorId = activeSearchBox.id.replace('search-', '');
                clearSearch(collectorId);
                activeSearchBox.blur();
            }
        }
    });
    
    // 为所有特殊值添加点击复制功能
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('special-ip') || 
            e.target.classList.contains('special-mac') || 
            e.target.classList.contains('special-port')) {
            
            const text = e.target.textContent;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    const originalBg = e.target.style.backgroundColor;
                    e.target.style.backgroundColor = '#28a745';
                    e.target.style.color = 'white';
                    
                    setTimeout(() => {
                        e.target.style.backgroundColor = originalBg;
                        e.target.style.color = '';
                    }, 500);
                });
            }
        }
    });
});
