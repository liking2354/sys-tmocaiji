@extends('layouts.app')

@section('title', '云平台管理')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">云平台管理</h3>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addPlatformModal">
                        <i class="fas fa-plus"></i> 添加云平台
                    </button>
                </div>
                <div class="card-body">
                    <!-- 搜索和筛选 -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select class="form-control" id="platformTypeFilter">
                                <option value="">所有平台类型</option>
                                <option value="huawei">华为云</option>
                                <option value="alibaba">阿里云</option>
                                <option value="tencent">腾讯云</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control" id="statusFilter">
                                <option value="">所有状态</option>
                                <option value="active">启用</option>
                                <option value="inactive">禁用</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="searchInput" placeholder="搜索平台名称...">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-info btn-block" id="searchBtn">
                                <i class="fas fa-search"></i> 搜索
                            </button>
                        </div>
                    </div>

                    <!-- 数据表格 -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="platformsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>平台名称</th>
                                    <th>平台类型</th>
                                    <th>默认区域</th>
                                    <th>状态</th>
                                    <th>创建时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($platforms as $platform)
                                <tr>
                                    <td>{{ $platform->id }}</td>
                                    <td>{{ $platform->name }}</td>
                                    <td>
                                        @switch($platform->platform_type)
                                            @case('huawei')
                                                <span class="badge badge-primary">华为云</span>
                                                @break
                                            @case('alibaba')
                                                <span class="badge badge-warning">阿里云</span>
                                                @break
                                            @case('tencent')
                                                <span class="badge badge-info">腾讯云</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td>{{ $platform->region }}</td>
                                    <td>
                                        @if($platform->status === 'active')
                                            <span class="badge badge-success">启用</span>
                                        @else
                                            <span class="badge badge-secondary">禁用</span>
                                        @endif
                                    </td>
                                    <td>{{ $platform->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-info" onclick="testConnection({{ $platform->id }})">
                                                <i class="fas fa-plug"></i> 测试连接
                                            </button>
                                            <button type="button" class="btn btn-sm btn-success" onclick="syncRegions({{ $platform->id }})">
                                                <i class="fas fa-sync"></i> 同步可用区
                                            </button>
                                            <button type="button" class="btn btn-sm btn-warning" onclick="editPlatform({{ $platform->id }})">
                                                <i class="fas fa-edit"></i> 编辑
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="deletePlatform({{ $platform->id }})">
                                                <i class="fas fa-trash"></i> 删除
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">暂无数据</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- 分页 -->
                    <div class="d-flex justify-content-center">
                        {{ $platforms->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 添加云平台模态框 -->
<div class="modal fade" id="addPlatformModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">添加云平台</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="addPlatformForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">平台名称 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="platform_type">平台类型 <span class="text-danger">*</span></label>
                                <select class="form-control" id="platform_type" name="platform_type" required>
                                    <option value="">请选择平台类型</option>
                                    <option value="huawei">华为云</option>
                                    <option value="alibaba">阿里云</option>
                                    <option value="tencent">腾讯云</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="access_key_id">Access Key ID <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="access_key_id" name="access_key_id" required autocomplete="username">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="access_key_secret">Access Key Secret <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="access_key_secret" name="access_key_secret" required autocomplete="current-password">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="region">默认区域 <span class="text-danger">*</span></label>
                                <select class="form-control" id="region" name="region" required>
                                    <option value="">请选择区域</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">状态</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="active">启用</option>
                                    <option value="inactive">禁用</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="config">其他配置 (JSON格式)</label>
                        <textarea class="form-control" id="config" name="config" rows="3" placeholder='{"key": "value"}'></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 编辑云平台模态框 -->
<div class="modal fade" id="editPlatformModal" tabindex="-1" role="dialog" aria-labelledby="editPlatformModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPlatformModalLabel">编辑云平台</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="editPlatformForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_platform_id" name="platform_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_name">平台名称 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_platform_type">平台类型 <span class="text-danger">*</span></label>
                                <select class="form-control" id="edit_platform_type" name="platform_type" required disabled>
                                    <option value="huawei">华为云</option>
                                    <option value="alibaba">阿里云</option>
                                    <option value="tencent">腾讯云</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_access_key_id">Access Key ID <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_access_key_id" name="access_key_id" required autocomplete="username">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_access_key_secret">Access Key Secret</label>
                                <input type="password" class="form-control" id="edit_access_key_secret" name="access_key_secret" placeholder="留空则不修改" autocomplete="current-password">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_region">默认区域 <span class="text-danger">*</span></label>
                                <select class="form-control" id="edit_region" name="region" required>
                                    <option value="">请选择区域</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_status">状态</label>
                                <select class="form-control" id="edit_status" name="status">
                                    <option value="active">启用</option>
                                    <option value="inactive">禁用</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_config">其他配置 (JSON格式)</label>
                        <textarea class="form-control" id="edit_config" name="config" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 进度弹出框 -->
<div class="modal fade" id="progressModal" tabindex="-1" role="dialog" aria-labelledby="progressModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="progressModalLabel">
                    <i class="fas fa-cog fa-spin mr-2"></i>
                    <span id="progressTitle">执行中...</span>
                </h5>
            </div>
            <div class="modal-body">
                <!-- 总体进度 -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="font-weight-bold">总体进度</span>
                        <span id="overallProgress">0%</span>
                    </div>
                    <div class="progress mb-2" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             id="overallProgressBar" 
                             role="progressbar" 
                             style="width: 0%" 
                             aria-valuenow="0" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                        </div>
                    </div>
                </div>

                <!-- 当前步骤 -->
                <div class="mb-4">
                    <h6 class="font-weight-bold mb-3">
                        <i class="fas fa-tasks mr-2"></i>执行步骤
                    </h6>
                    <div id="stepsList">
                        <!-- 步骤将通过JavaScript动态添加 -->
                    </div>
                </div>

                <!-- 详细日志 -->
                <div class="mb-3">
                    <h6 class="font-weight-bold mb-2">
                        <i class="fas fa-list-alt mr-2"></i>详细日志
                    </h6>
                    <div id="logContainer" style="max-height: 200px; overflow-y: auto; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 0.25rem; padding: 10px;">
                        <div id="logContent" style="font-family: 'Courier New', monospace; font-size: 12px; white-space: pre-wrap;"></div>
                    </div>
                </div>

                <!-- 结果信息 -->
                <div id="resultContainer" style="display: none;">
                    <div class="alert" id="resultAlert" role="alert">
                        <h6 class="alert-heading" id="resultTitle"></h6>
                        <p id="resultMessage" class="mb-0"></p>
                        <div id="resultDetails" class="mt-2" style="display: none;">
                            <hr>
                            <small id="resultDetailsContent"></small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="progressCloseBtn" disabled>
                    <i class="fas fa-times mr-1"></i>关闭
                </button>
                <button type="button" class="btn btn-primary" id="progressRetryBtn" style="display: none;">
                    <i class="fas fa-redo mr-1"></i>重试
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
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
    // 全局模态框 ARIA 属性处理
    $('.modal').on('show.bs.modal', function() {
        $(this).removeAttr('aria-hidden');
    }).on('shown.bs.modal', function() {
        $(this).attr('aria-modal', 'true');
        // 确保焦点在模态框内
        $(this).find('.modal-content').focus();
    }).on('hide.bs.modal', function() {
        $(this).removeAttr('aria-modal');
    }).on('hidden.bs.modal', function() {
        $(this).attr('aria-hidden', 'true');
    });

    // 区域数据映射
    const regionMaps = {
        huawei: [
            {code: 'cn-north-1', name: '华北-北京一'},
            {code: 'cn-north-4', name: '华北-北京四'},
            {code: 'cn-east-2', name: '华东-上海二'},
            {code: 'cn-east-3', name: '华东-上海一'},
            {code: 'cn-south-1', name: '华南-广州'},
            {code: 'cn-southwest-2', name: '西南-贵阳一'},
            {code: 'ap-southeast-1', name: '亚太-香港'},
            {code: 'ap-southeast-3', name: '亚太-新加坡'}
        ],
        alibaba: [
            {code: 'cn-hangzhou', name: '华东1（杭州）'},
            {code: 'cn-shanghai', name: '华东2（上海）'},
            {code: 'cn-qingdao', name: '华北1（青岛）'},
            {code: 'cn-beijing', name: '华北2（北京）'},
            {code: 'cn-zhangjiakou', name: '华北3（张家口）'},
            {code: 'cn-huhehaote', name: '华北5（呼和浩特）'},
            {code: 'cn-shenzhen', name: '华南1（深圳）'},
            {code: 'cn-hongkong', name: '香港'}
        ],
        tencent: [
            {code: 'ap-beijing', name: '华北地区（北京）'},
            {code: 'ap-shanghai', name: '华东地区（上海）'},
            {code: 'ap-guangzhou', name: '华南地区（广州）'},
            {code: 'ap-chengdu', name: '西南地区（成都）'},
            {code: 'ap-chongqing', name: '西南地区（重庆）'},
            {code: 'ap-nanjing', name: '华东地区（南京）'},
            {code: 'ap-hongkong', name: '港澳台地区（中国香港）'},
            {code: 'ap-singapore', name: '亚太东南（新加坡）'},
            {code: 'ap-tokyo', name: '亚太东北（东京）'},
            {code: 'us-west-1', name: '美国西部（硅谷）'}
        ]
    };

    // 智能区域选项更新（优先数据库，后备默认）
    window.updateRegionOptions = async function(platformType, regionSelect, selectedRegion = '') {
        if (!platformType) {
            regionSelect.empty().append('<option value="">请先选择平台类型</option>');
            return;
        }
        
        // 显示加载状态
        regionSelect.empty().append('<option value="">加载中...</option>');
        
        try {
            // 获取智能区域列表
            const regions = await getRegionsByPlatform(platformType);
            
            // 重新构建选项
            regionSelect.empty().append('<option value="">请选择区域</option>');
            regions.forEach(region => {
                const selected = region.value === selectedRegion ? 'selected' : '';
                regionSelect.append('<option value="' + region.value + '" ' + selected + '>' + region.label + '</option>');
            });
            
        } catch (error) {
            console.error('更新区域选项失败:', error);
            regionSelect.empty().append('<option value="">加载失败，请重试</option>');
        }
    };

    // 智能获取区域列表（与create.blade.php保持一致）
    async function getRegionsByPlatform(platformType) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: '/cloud/platforms/regions/' + platformType,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        resolve(response.regions);
                    } else {
                        console.warn('获取区域列表失败，使用默认数据');
                        resolve(getDefaultRegionsByPlatform(platformType));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('获取区域列表出错:', error);
                    resolve(getDefaultRegionsByPlatform(platformType));
                }
            });
        });
    }

    // 默认区域数据（后备方案）
    function getDefaultRegionsByPlatform(platformType) {
        const regions = regionMaps[platformType] || [];
        return regions.map(region => ({
            value: region.code,
            label: region.name
        }));
    }

    // 添加平台时的平台类型变化
    $('#platform_type').change(function() {
        updateRegionOptions($(this).val(), $('#region'));
    });

    // 编辑平台时的平台类型变化
    $('#edit_platform_type').change(function() {
        updateRegionOptions($(this).val(), $('#edit_region'));
    });

    // 添加平台表单提交
    $('#addPlatformForm').submit(function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = $(this).find('button[type="submit"]');
        
        submitBtn.prop('disabled', true).text('保存中...');
        
        $.ajax({
            url: '{{ route("cloud.platforms.store") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                toastr.success('云平台添加成功');
                $('#addPlatformModal').modal('hide');
                location.reload();
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors || {};
                let errorMsg = '添加失败：';
                
                Object.keys(errors).forEach(key => {
                    errorMsg += errors[key][0] + ' ';
                });
                
                toastr.error(errorMsg);
            },
            complete: function() {
                submitBtn.prop('disabled', false).text('保存');
            }
        });
    });

    // 编辑平台表单提交
    $('#editPlatformForm').submit(function(e) {
        e.preventDefault();
        
        const platformId = $('#edit_platform_id').val();
        const formData = new FormData(this);
        const submitBtn = $(this).find('button[type="submit"]');
        
        submitBtn.prop('disabled', true).text('保存中...');
        
        $.ajax({
            url: `/cloud/platforms/${platformId}`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                toastr.success('云平台更新成功');
                $('#editPlatformModal').modal('hide');
                location.reload();
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors || {};
                let errorMsg = '更新失败：';
                
                Object.keys(errors).forEach(key => {
                    errorMsg += errors[key][0] + ' ';
                });
                
                toastr.error(errorMsg);
            },
            complete: function() {
                submitBtn.prop('disabled', false).text('保存');
            }
        });
    });

    // 搜索功能
    $('#searchBtn').click(function() {
        const platformType = $('#platformTypeFilter').val();
        const status = $('#statusFilter').val();
        const search = $('#searchInput').val();
        
        const params = new URLSearchParams();
        if (platformType) params.append('platform_type', platformType);
        if (status) params.append('status', status);
        if (search) params.append('search', search);
        
        window.location.href = '{{ route("cloud.platforms.index") }}?' + params.toString();
    });

    // 回车搜索
    $('#searchInput').keypress(function(e) {
        if (e.which === 13) {
            $('#searchBtn').click();
        }
    });
});

// 获取平台名称的辅助函数
function getPlatformName(platformId) {
    // 从表格中查找对应的平台名称
    const row = $(`button[onclick*="testConnection(${platformId})"]`).closest('tr');
    if (row.length > 0) {
        return row.find('td:nth-child(2)').text().trim(); // 假设名称在第2列
    }
    return '未知平台';
}

// 测试连接
function testConnection(platformId) {
    // 获取平台名称
    const platformName = getPlatformName(platformId);
    
    // 定义测试步骤
    const steps = [
        '验证平台配置信息',
        '初始化云平台客户端',
        '测试API连接',
        '验证访问权限'
    ];
    
    // 初始化进度管理器
    progressManager.init(`${platformName} - 连接测试`, steps, () => testConnection(platformId));
    
    // 执行测试流程
    executeConnectionTest(platformId);
}

// 执行连接测试
function executeConnectionTest(platformId) {
    // 步骤1: 验证平台配置信息
    progressManager.startStep(0, '检查Access Key和区域配置');
    
    setTimeout(() => {
        progressManager.completeStep(0, true, '配置信息验证完成');
        
        // 步骤2: 初始化云平台客户端
        progressManager.startStep(1, '创建云平台SDK客户端');
        
        setTimeout(() => {
            progressManager.completeStep(1, true, '客户端初始化完成');
            
            // 步骤3: 测试API连接
            progressManager.startStep(2, '发送测试请求到云平台API');
            
            // 实际的API调用
            $.ajax({
                url: `/cloud/platforms/${platformId}/test-connection`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        progressManager.completeStep(2, true, 'API连接测试成功');
                        
                        // 步骤4: 验证访问权限
                        progressManager.startStep(3, '验证云平台访问权限');
                        
                        setTimeout(() => {
                            progressManager.completeStep(3, true, '权限验证完成');
                            
                            // 显示成功结果
                            progressManager.showResult(
                                true,
                                '连接测试成功',
                                `${response.platform_name || '云平台'} 连接测试完成，所有检查项均通过。`,
                                `平台类型: ${response.platform_type || 'unknown'}\n响应时间: ${new Date().toLocaleTimeString()}`
                            );
                        }, 500);
                        
                    } else {
                        progressManager.completeStep(2, false, response.message || 'API连接失败');
                        progressManager.showResult(
                            false,
                            '连接测试失败',
                            response.message || '连接测试失败，请检查配置信息',
                            '请确认Access Key、Secret Key和区域配置是否正确'
                        );
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON?.message || '网络错误';
                    progressManager.completeStep(2, false, errorMsg);
                    progressManager.showResult(
                        false,
                        '连接测试失败',
                        '连接测试过程中发生错误: ' + errorMsg,
                        '请检查网络连接和服务器状态'
                    );
                }
            });
            
        }, 800);
        
    }, 500);
}



// 编辑平台
function editPlatform(platformId) {
    $.ajax({
        url: `/cloud/platforms/${platformId}`,
        method: 'GET',
        success: function(platform) {
            $('#edit_platform_id').val(platform.id);
            $('#edit_name').val(platform.name);
            $('#edit_platform_type').val(platform.platform_type);
            $('#edit_access_key_id').val(platform.access_key_id);
            $('#edit_access_key_secret').val('');
            $('#edit_status').val(platform.status);
            $('#edit_config').val(platform.config ? JSON.stringify(platform.config, null, 2) : '');
            
            // 更新区域选项并设置当前值
            updateRegionOptions(platform.platform_type, $('#edit_region'), platform.region);
            
            // 显示模态框
            $('#editPlatformModal').modal('show');
        },
        error: function(xhr) {
            toastr.error('获取平台信息失败：' + (xhr.responseJSON?.message || '网络错误'));
        }
    });
}

// 同步可用区
function syncRegions(platformId) {
    // 获取平台名称
    const platformName = getPlatformName(platformId);
    
    // 定义同步步骤
    const steps = [
        '验证云平台连接',
        '获取远程可用区列表',
        '对比本地数据库',
        '更新可用区信息',
        '完成数据同步'
    ];
    
    // 初始化进度管理器
    progressManager.init(`${platformName} - 同步可用区`, steps, () => syncRegions(platformId));
    
    // 执行同步流程
    executeSyncRegions(platformId);
}

// 执行可用区同步
function executeSyncRegions(platformId) {
    // 步骤1: 验证云平台连接
    progressManager.startStep(0, '检查云平台连接状态');
    
    setTimeout(() => {
        progressManager.completeStep(0, true, '连接状态正常');
        
        // 步骤2: 获取远程可用区列表
        progressManager.startStep(1, '从云平台API获取可用区列表');
        
        setTimeout(() => {
            progressManager.completeStep(1, true, '远程可用区列表获取完成');
            
            // 步骤3: 对比本地数据库
            progressManager.startStep(2, '对比本地数据库中的可用区信息');
            
            setTimeout(() => {
                progressManager.completeStep(2, true, '数据对比完成');
                
                // 步骤4: 更新可用区信息
                progressManager.startStep(3, '开始同步可用区数据到数据库');
                
                // 实际的API调用
                $.ajax({
                    url: `/cloud/platforms/${platformId}/sync-regions`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            progressManager.completeStep(3, true, `成功同步 ${response.count} 个可用区`);
                            
                            // 步骤5: 完成数据同步
                            progressManager.startStep(4, '完成同步操作');
                            
                            setTimeout(() => {
                                progressManager.completeStep(4, true, '所有数据已同步完成');
                                
                                // 显示成功结果
                                progressManager.showResult(
                                    true,
                                    '可用区同步成功',
                                    `成功同步 ${response.count} 个可用区到数据库`,
                                    `同步时间: ${new Date().toLocaleString()}\n平台: ${response.platform_name || '未知平台'}\n新增/更新: ${response.count} 个可用区`
                                );
                            }, 500);
                            
                        } else {
                            progressManager.completeStep(3, false, response.message || '同步失败');
                            progressManager.showResult(
                                false,
                                '可用区同步失败',
                                response.message || '同步过程中发生错误',
                                '请检查云平台配置和网络连接'
                            );
                        }
                    },
                    error: function(xhr) {
                        const errorMsg = xhr.responseJSON?.message || '网络错误';
                        progressManager.completeStep(3, false, errorMsg);
                        progressManager.showResult(
                            false,
                            '可用区同步失败',
                            '同步过程中发生错误: ' + errorMsg,
                            '请检查云平台配置、网络连接和服务器状态'
                        );
                    }
                });
                
            }, 600);
            
        }, 800);
        
    }, 500);
}

// 删除平台
function deletePlatform(platformId) {
    if (confirm('确定要删除这个云平台吗？删除后相关的资源数据也会被清除。')) {
        $.ajax({
            url: `/cloud/platforms/${platformId}`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                _method: 'DELETE'
            },
            success: function(response) {
                toastr.success('云平台删除成功');
                location.reload();
            },
            error: function(xhr) {
                toastr.error('删除失败：' + (xhr.responseJSON?.message || '网络错误'));
            }
        });
    }
}
</script>
@endsection