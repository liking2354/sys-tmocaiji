@extends('layouts.app')

@section('title', '创建配置模板')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-plus mr-2"></i>
                        创建配置模板 - 可视化配置
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('system-change.templates.index') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i>
                            返回列表
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('system-change.templates.store') }}" id="template-form">
                    @csrf
                    <div class="card-body">
                        <!-- 基本信息 -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name" class="required">模板名称</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="template_type">模板类型</label>
                                    <select class="form-control" id="template_type" name="template_type">
                                        <option value="mixed">混合模式</option>
                                        <option value="directory">目录批量处理</option>
                                        <option value="file">文件精确处理</option>
                                        <option value="string">字符串替换</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="description">模板描述</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" name="description" rows="2">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="is_active">状态</label>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="is_active" 
                                               name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="is_active">启用模板</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 模板变量定义 -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-tags mr-2"></i>
                                    模板变量定义
                                </h5>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-sm btn-primary" onclick="addVariable(); return false;">
                                        <i class="fas fa-plus mr-1"></i>
                                        添加变量
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="variables-container">
                                    <div class="text-muted text-center py-3">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        点击"添加变量"开始定义模板变量
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 配置规则 -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-cogs mr-2"></i>
                                    配置规则
                                </h5>
                                <div class="card-tools">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-success dropdown-toggle" data-toggle="dropdown">
                                            <i class="fas fa-plus mr-1"></i>
                                            添加规则
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="#" onclick="addRule('directory'); return false;">
                                                <i class="fas fa-folder mr-2"></i>
                                                目录批量处理
                                            </a>
                                            <a class="dropdown-item" href="#" onclick="addRule('file'); return false;">
                                                <i class="fas fa-file mr-2"></i>
                                                文件精确处理
                                            </a>
                                            <a class="dropdown-item" href="#" onclick="addRule('string'); return false;">
                                                <i class="fas fa-search mr-2"></i>
                                                字符串替换
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="rules-container">
                                    <div class="text-muted text-center py-3">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        点击"添加规则"开始配置处理规则
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 预览区域 -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-eye mr-2"></i>
                                    配置预览
                                </h5>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-sm btn-info" id="preview-btn">
                                        <i class="fas fa-sync mr-1"></i>
                                        刷新预览
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="config-preview" class="border rounded p-3 bg-light" style="min-height: 200px;">
                                    <div class="text-muted text-center">
                                        <i class="fas fa-eye fa-2x mb-2"></i><br>
                                        配置完成后将显示预览
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 隐藏字段 -->
                        <input type="hidden" name="config_rules" id="config_rules_input">
                        <input type="hidden" name="template_variables" id="template_variables_input">
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>
                            保存模板
                        </button>
                        <button type="button" class="btn btn-info ml-2" id="preview-config-btn">
                            <i class="fas fa-eye mr-1"></i>
                            预览配置
                        </button>
                        <a href="{{ route('system-change.templates.index') }}" class="btn btn-default ml-2">
                            <i class="fas fa-times mr-1"></i>
                            取消
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 变量模板 -->
<template id="variable-template">
    <div class="variable-item border rounded p-3 mb-3" data-index="__INDEX__">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group mb-2">
                    <label class="form-label">变量名</label>
                    <input type="text" class="form-control variable-name" placeholder="例如: db_host">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group mb-2">
                    <label class="form-label">默认值</label>
                    <input type="text" class="form-control variable-default" placeholder="默认值">
                </div>
            </div>
            <div class="col-md-5">
                <div class="form-group mb-2">
                    <label class="form-label">描述</label>
                    <input type="text" class="form-control variable-description" placeholder="变量说明">
                </div>
            </div>
            <div class="col-md-1">
                <div class="form-group mb-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-sm d-block remove-variable">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<!-- 目录规则模板 -->
<template id="directory-rule-template">
    <div class="rule-item border rounded p-3 mb-3" data-type="directory" data-index="__INDEX__">
        <div class="rule-header d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">
                <i class="fas fa-folder text-warning mr-2"></i>
                目录批量处理规则
            </h6>
            <button type="button" class="btn btn-danger btn-sm remove-rule">
                <i class="fas fa-trash mr-1"></i>
                删除
            </button>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label required">目标目录</label>
                    <input type="text" class="form-control rule-directory" placeholder="/var/www/html/config/" required>
                    <small class="text-muted">要处理的目录路径</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">文件匹配模式</label>
                    <input type="text" class="form-control rule-pattern" placeholder="*.conf" value="*">
                    <small class="text-muted">文件名匹配模式，支持通配符</small>
                </div>
            </div>
        </div>
        
        <!-- 变量配置区域 -->
        <div class="variables-config-area">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label required">变量配置</label>
                <button type="button" class="btn btn-sm btn-outline-primary add-rule-variable">
                    <i class="fas fa-plus mr-1"></i>
                    添加变量
                </button>
            </div>
            <div class="rule-variables-container">
                <div class="rule-variable-item row mb-2" data-var-index="0">
                    <div class="col-md-3">
                        <input type="text" class="form-control rule-variable" placeholder="变量名 (如: db_host)" required>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control rule-match-type">
                            <option value="key_value">键值对模式</option>
                            <option value="regex">正则表达式</option>
                            <option value="exact">精确匹配</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <input type="text" class="form-control rule-match-pattern" placeholder="匹配表达式 (留空使用变量名)">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-sm btn-danger remove-rule-variable" style="display: none;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">规则描述</label>
            <input type="text" class="form-control rule-description" placeholder="描述这个规则的作用">
        </div>
    </div>
</template>

<!-- 文件规则模板 -->
<template id="file-rule-template">
    <div class="rule-item border rounded p-3 mb-3" data-type="file" data-index="__INDEX__">
        <div class="rule-header d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">
                <i class="fas fa-file text-info mr-2"></i>
                文件精确处理规则
            </h6>
            <button type="button" class="btn btn-danger btn-sm remove-rule">
                <i class="fas fa-trash mr-1"></i>
                删除
            </button>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label class="form-label required">目标文件</label>
                    <input type="text" class="form-control rule-file-path" placeholder="/etc/nginx/nginx.conf" required>
                    <small class="text-muted">要处理的具体文件路径</small>
                </div>
            </div>
        </div>
        
        <!-- 变量配置区域 -->
        <div class="variables-config-area">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label required">变量配置</label>
                <button type="button" class="btn btn-sm btn-outline-primary add-rule-variable">
                    <i class="fas fa-plus mr-1"></i>
                    添加变量
                </button>
            </div>
            <div class="rule-variables-container">
                <div class="rule-variable-item row mb-2" data-var-index="0">
                    <div class="col-md-3">
                        <input type="text" class="form-control rule-variable" placeholder="变量名 (如: server_name)" required>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control rule-match-type">
                            <option value="key_value">键值对模式</option>
                            <option value="regex">正则表达式</option>
                            <option value="line">整行替换</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <input type="text" class="form-control rule-match-pattern" placeholder="匹配表达式 (留空使用变量名)">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-sm btn-danger remove-rule-variable" style="display: none;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">规则描述</label>
            <input type="text" class="form-control rule-description" placeholder="描述这个规则的作用">
        </div>
    </div>
</template>

<!-- 字符串规则模板 -->
<template id="string-rule-template">
    <div class="rule-item border rounded p-3 mb-3" data-type="string" data-index="__INDEX__">
        <div class="rule-header d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">
                <i class="fas fa-search text-success mr-2"></i>
                字符串替换规则
            </h6>
            <button type="button" class="btn btn-danger btn-sm remove-rule">
                <i class="fas fa-trash mr-1"></i>
                删除
            </button>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label class="form-label required">目标文件</label>
                    <input type="text" class="form-control rule-file-path" placeholder="/var/www/html/config.php" required>
                    <small class="text-muted">要处理的文件路径</small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label required">查找字符串</label>
                    <textarea class="form-control rule-search-string" rows="3" placeholder="要查找的字符串内容" required></textarea>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label required">替换字符串</label>
                    <textarea class="form-control rule-replace-string" rows="3" placeholder="替换后的内容，支持变量 @{{variable_name@}}" required></textarea>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input rule-case-sensitive" id="case_sensitive___INDEX__">
                        <label class="custom-control-label" for="case_sensitive___INDEX__">区分大小写</label>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input rule-regex-mode" id="regex_mode___INDEX__">
                        <label class="custom-control-label" for="regex_mode___INDEX__">正则表达式模式</label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">规则描述</label>
            <input type="text" class="form-control rule-description" placeholder="描述这个规则的作用">
        </div>
    </div>
</template>
@endsection

@section('styles')
<style>
.required::after {
    content: " *";
    color: red;
}

.variable-item, .rule-item {
    background-color: #f8f9fa;
    transition: all 0.3s ease;
}

.variable-item:hover, .rule-item:hover {
    background-color: #e9ecef;
}

.rule-header {
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 0.5rem;
}

#config-preview {
    max-height: 500px;
    overflow-y: auto;
}

.preview-rule {
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    padding: 1rem;
    margin-bottom: 1rem;
    background-color: white;
}

.preview-rule h6 {
    color: #495057;
    margin-bottom: 0.5rem;
}

.badge-rule-type {
    font-size: 0.75rem;
}

/* 多变量配置样式 */
.variables-config-area {
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    padding: 1rem;
    background-color: #f8f9fa;
    margin-bottom: 1rem;
}

.rule-variable-item {
    background-color: white;
    border: 1px solid #e9ecef;
    border-radius: 0.25rem;
    padding: 0.5rem;
    margin-bottom: 0.5rem;
    transition: all 0.2s;
}

.rule-variable-item:hover {
    border-color: #007bff;
    box-shadow: 0 2px 4px rgba(0,123,255,0.1);
}

.rule-variables-container {
    max-height: 300px;
    overflow-y: auto;
}

.add-rule-variable {
    font-size: 0.875rem;
}

.remove-rule-variable {
    padding: 0.25rem 0.5rem;
}
</style>
@endsection

@section('scripts')
<script>
let variableIndex = 0;
let ruleIndex = 0;

$(document).ready(function() {
    console.log('JavaScript已加载 - 可视化配置模板');
    
    // 添加变量按钮
    $('#add-variable-btn').click(function() {
        addVariable();
    });
    
    // 预览按钮
    $('#preview-btn, #preview-config-btn').click(function() {
        updatePreview();
    });
    
    // 表单提交前处理
    $('#template-form').submit(function(e) {
        console.log('表单提交事件触发');
        
        // 先直接检查所有输入框的值
        console.log('=== 直接检查输入框值 ===');
        $('.rule-directory').each(function(i) {
            console.log(`目录输入框 ${i}: "${$(this).val()}"`);
        });
        $('.rule-file-path').each(function(i) {
            console.log(`文件路径输入框 ${i}: "${$(this).val()}"`);
        });
        $('.rule-search-string').each(function(i) {
            console.log(`搜索字符串输入框 ${i}: "${$(this).val()}"`);
        });
        
        // 收集数据
        const isValid = collectFormData();
        console.log('collectFormData 返回结果:', isValid);
        
        // 验证基本信息
        const name = $('#name').val().trim();
        if (!name) {
            e.preventDefault();
            alert('请填写模板名称');
            return false;
        }
        
        // 检查隐藏字段的值
        const configRules = $('#config_rules_input').val();
        const templateVariables = $('#template_variables_input').val();
        console.log('config_rules 值:', configRules);
        console.log('template_variables 值:', templateVariables);
        
        console.log('表单验证通过，准备提交');
        return true;
    });
    
    // 删除变量事件委托
    $(document).on('click', '.remove-variable', function() {
        $(this).closest('.variable-item').remove();
        updatePreview();
    });
    
    // 删除规则事件委托
    $(document).on('click', '.remove-rule', function() {
        $(this).closest('.rule-item').remove();
        updatePreview();
    });
    
    // 添加规则变量
    $(document).on('click', '.add-rule-variable', function() {
        const container = $(this).closest('.rule-item').find('.rule-variables-container');
        const currentCount = container.find('.rule-variable-item').length;
        const newIndex = currentCount;
        
        const newVariableHtml = `
            <div class="rule-variable-item row mb-2" data-var-index="${newIndex}">
                <div class="col-md-3">
                    <input type="text" class="form-control rule-variable" placeholder="变量名" required>
                </div>
                <div class="col-md-3">
                    <select class="form-control rule-match-type">
                        <option value="key_value">键值对模式</option>
                        <option value="regex">正则表达式</option>
                        <option value="exact">精确匹配</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <input type="text" class="form-control rule-match-pattern" placeholder="匹配表达式">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-danger remove-rule-variable">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        
        container.append(newVariableHtml);
        updateVariableButtons(container);
        updatePreview();
    });
    
    // 删除规则变量
    $(document).on('click', '.remove-rule-variable', function() {
        const container = $(this).closest('.rule-variables-container');
        $(this).closest('.rule-variable-item').remove();
        updateVariableButtons(container);
        updatePreview();
    });
    
    // 输入变化时更新预览
    $(document).on('input change', '.variable-name, .variable-default, .variable-description, .rule-item input, .rule-item select, .rule-item textarea', function() {
        clearTimeout(window.previewTimeout);
        window.previewTimeout = setTimeout(updatePreview, 500);
    });
});

function addVariable() {
    console.log('添加变量，当前索引:', variableIndex);
    const template = $('#variable-template').html();
    
    if (!template) {
        console.error('找不到变量模板');
        return;
    }
    
    const html = template.replace(/__INDEX__/g, variableIndex);
    
    // 只在第一次添加时清空提示信息
    if ($('#variables-container .text-muted').length && $('#variables-container .variable-item').length === 0) {
        $('#variables-container').empty();
    }
    
    $('#variables-container').append(html);
    variableIndex++;
    updatePreview();
    
    console.log('变量添加完成，当前变量数量:', $('#variables-container .variable-item').length);
}

function addRule(type) {
    console.log('添加规则类型:', type, '当前索引:', ruleIndex);
    
    // 使用更可靠的方法获取模板内容
    const templateElement = document.getElementById(type + '-rule-template');
    if (!templateElement) {
        console.error('找不到模板:', type + '-rule-template');
        return;
    }
    
    let template;
    if (templateElement.content) {
        // 现代浏览器支持 template.content
        template = templateElement.content.firstElementChild.outerHTML;
    } else {
        // 降级方案
        template = templateElement.innerHTML;
    }
    
    if (!template) {
        console.error('模板内容为空:', type + '-rule-template');
        return;
    }
    
    const html = template.replace(/__INDEX__/g, ruleIndex);
    
    // 只在第一次添加时清空提示信息
    if ($('#rules-container .text-muted').length && $('#rules-container .rule-item').length === 0) {
        $('#rules-container').empty();
    }
    
    $('#rules-container').append(html);
    ruleIndex++;
    updatePreview();
    
    console.log('规则添加完成，当前规则数量:', $('#rules-container .rule-item').length);
    
    // 初始化新添加规则的变量按钮状态
    const newRule = $('#rules-container .rule-item').last();
    const container = newRule.find('.rule-variables-container');
    updateVariableButtons(container);
}

function updateVariableButtons(container) {
    const variableItems = container.find('.rule-variable-item');
    const removeButtons = container.find('.remove-rule-variable');
    
    if (variableItems.length <= 1) {
        removeButtons.hide();
    } else {
        removeButtons.show();
    }
}

function collectFormData() {
    console.log('开始收集表单数据...');
    
    // 收集变量数据
    const variables = [];
    $('.variable-item').each(function() {
        const name = $(this).find('.variable-name').val();
        const defaultValue = $(this).find('.variable-default').val();
        const description = $(this).find('.variable-description').val();
        
        if (name) {
            variables.push({
                name: name,
                default_value: defaultValue,
                description: description
            });
        }
    });
    
    // 收集规则数据
    const rules = [];
    console.log('找到的规则元素数量:', $('.rule-item').length);
    
    $('.rule-item').each(function(index) {
        const type = $(this).data('type');
        console.log(`规则 ${index}: type=${type}`);
        
        const ruleData = {
            type: type,
            description: $(this).find('.rule-description').val()
        };
        
        if (type === 'directory') {
            const directoryInput = $(this).find('.rule-directory');
            const patternInput = $(this).find('.rule-pattern');
            console.log(`目录输入框数量: ${directoryInput.length}, 模式输入框数量: ${patternInput.length}`);
            
            ruleData.directory = directoryInput.val();
            ruleData.pattern = patternInput.val();
            console.log(`目录规则: directory=${ruleData.directory}, pattern=${ruleData.pattern}`);
            
            // 收集多个变量配置
            const variables = [];
            $(this).find('.rule-variable-item').each(function() {
                const variable = $(this).find('.rule-variable').val();
                const matchType = $(this).find('.rule-match-type').val();
                const matchPattern = $(this).find('.rule-match-pattern').val();
                
                if (variable) {
                    variables.push({
                        variable: variable,
                        match_type: matchType,
                        match_pattern: matchPattern
                    });
                }
            });
            ruleData.variables = variables;
            
        } else if (type === 'file') {
            ruleData.file_path = $(this).find('.rule-file-path').val();
            console.log(`文件规则: file_path=${ruleData.file_path}`);
            
            // 收集多个变量配置
            const variables = [];
            $(this).find('.rule-variable-item').each(function() {
                const variable = $(this).find('.rule-variable').val();
                const matchType = $(this).find('.rule-match-type').val();
                const matchPattern = $(this).find('.rule-match-pattern').val();
                
                if (variable) {
                    variables.push({
                        variable: variable,
                        match_type: matchType,
                        match_pattern: matchPattern
                    });
                }
            });
            ruleData.variables = variables;
            
        } else if (type === 'string') {
            const filePathInput = $(this).find('.rule-file-path');
            const searchInput = $(this).find('.rule-search-string');
            const replaceInput = $(this).find('.rule-replace-string');
            console.log(`字符串输入框数量: file_path=${filePathInput.length}, search=${searchInput.length}, replace=${replaceInput.length}`);
            
            ruleData.file_path = filePathInput.val();
            ruleData.search_string = searchInput.val();
            ruleData.replace_string = replaceInput.val();
            ruleData.case_sensitive = $(this).find('.rule-case-sensitive').is(':checked');
            ruleData.regex_mode = $(this).find('.rule-regex-mode').is(':checked');
            console.log(`字符串规则: file_path=${ruleData.file_path}, search=${ruleData.search_string}, replace=${ruleData.replace_string}`);
        }
        
        // 验证必填字段
        let isValidRule = false;
        
        if (type === 'directory' && ruleData.directory) {
            isValidRule = true;
        } else if (type === 'file' && ruleData.file_path) {
            isValidRule = true;
        } else if (type === 'string' && ruleData.file_path && ruleData.search_string && ruleData.replace_string) {
            isValidRule = true;
        }
        
        console.log(`规则验证结果: isValidRule=${isValidRule}`);
        
        if (isValidRule) {
            rules.push(ruleData);
        }
    });
    
    // 设置隐藏字段
    $('#template_variables_input').val(JSON.stringify(variables));
    $('#config_rules_input').val(JSON.stringify(rules));
    
    console.log('收集到的变量数据:', variables);
    console.log('收集到的规则数据:', rules);
    
    // 不再要求必须有规则，允许只保存基本信息和变量
    return true;
}

function updatePreview() {
    collectFormData();
    
    const variables = JSON.parse($('#template_variables_input').val() || '[]');
    const rules = JSON.parse($('#config_rules_input').val() || '[]');
    
    if (variables.length === 0 && rules.length === 0) {
        $('#config-preview').html(`
            <div class="text-muted text-center">
                <i class="fas fa-eye fa-2x mb-2"></i><br>
                配置完成后将显示预览
            </div>
        `);
        return;
    }
    
    let html = '';
    
    // 显示变量
    if (variables.length > 0) {
        html += '<div class="preview-section mb-4">';
        html += '<h6><i class="fas fa-tags mr-2"></i>模板变量 (' + variables.length + '个)</h6>';
        html += '<div class="row">';
        variables.forEach(variable => {
            html += `
                <div class="col-md-4 mb-2">
                    <div class="card card-outline card-primary">
                        <div class="card-body p-2">
                            <strong>@{{${variable.name}@}}</strong><br>
                            <small class="text-muted">默认: ${variable.default_value || '无'}</small><br>
                            <small>${variable.description || '无描述'}</small>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div></div>';
    }
    
    // 显示规则
    if (rules.length > 0) {
        html += '<div class="preview-section">';
        html += '<h6><i class="fas fa-cogs mr-2"></i>配置规则 (' + rules.length + '个)</h6>';
        
        rules.forEach((rule, index) => {
            html += '<div class="preview-rule">';
            
            if (rule.type === 'directory') {
                html += `
                    <h6><i class="fas fa-folder text-warning mr-2"></i>目录批量处理 
                        <span class="badge badge-warning badge-rule-type">目录</span>
                    </h6>
                    <p><strong>目录:</strong> <code>${rule.directory}</code></p>
                    <p><strong>文件模式:</strong> <code>${rule.pattern}</code></p>
                `;
                
                // 显示多个变量
                if (rule.variables && rule.variables.length > 0) {
                    html += '<p><strong>变量配置:</strong></p><ul>';
                    rule.variables.forEach(varConfig => {
                        html += `<li><code>@{{${varConfig.variable}@}}</code> - ${varConfig.match_type}`;
                        if (varConfig.match_pattern) {
                            html += ` - <code>${varConfig.match_pattern}</code>`;
                        }
                        html += '</li>';
                    });
                    html += '</ul>';
                } else if (rule.variable) {
                    // 兼容旧格式
                    html += `<p><strong>变量:</strong> <code>@{{${rule.variable}@}}</code></p>`;
                    html += `<p><strong>匹配模式:</strong> ${rule.match_type} 
                        ${rule.match_pattern ? '- <code>' + rule.match_pattern + '</code>' : ''}
                    </p>`;
                }
                
            } else if (rule.type === 'file') {
                html += `
                    <h6><i class="fas fa-file text-info mr-2"></i>文件精确处理 
                        <span class="badge badge-info badge-rule-type">文件</span>
                    </h6>
                    <p><strong>文件:</strong> <code>${rule.file_path}</code></p>
                `;
                
                // 显示多个变量
                if (rule.variables && rule.variables.length > 0) {
                    html += '<p><strong>变量配置:</strong></p><ul>';
                    rule.variables.forEach(varConfig => {
                        html += `<li><code>@{{${varConfig.variable}@}}</code> - ${varConfig.match_type}`;
                        if (varConfig.match_pattern) {
                            html += ` - <code>${varConfig.match_pattern}</code>`;
                        }
                        html += '</li>';
                    });
                    html += '</ul>';
                } else if (rule.variable) {
                    // 兼容旧格式
                    html += `<p><strong>变量:</strong> <code>@{{${rule.variable}@}}</code></p>`;
                    html += `<p><strong>匹配模式:</strong> ${rule.match_type} 
                        ${rule.match_pattern ? '- <code>' + rule.match_pattern + '</code>' : ''}
                    </p>`;
                }
                
            } else if (rule.type === 'string') {
                html += `
                    <h6><i class="fas fa-search text-success mr-2"></i>字符串替换 
                        <span class="badge badge-success badge-rule-type">字符串</span>
                    </h6>
                    <p><strong>文件:</strong> <code>${rule.file_path}</code></p>
                    <p><strong>查找:</strong> <code>${rule.search_string}</code></p>
                    <p><strong>替换:</strong> <code>${rule.replace_string}</code></p>
                    <p><strong>选项:</strong> 
                        ${rule.case_sensitive ? '<span class="badge badge-secondary">区分大小写</span>' : ''}
                        ${rule.regex_mode ? '<span class="badge badge-secondary">正则模式</span>' : ''}
                    </p>
                `;
            }
            
            if (rule.description) {
                html += `<p><strong>说明:</strong> ${rule.description}</p>`;
            }
            
            html += '</div>';
        });
        
        html += '</div>';
    }
    
    $('#config-preview').html(html);
}
</script>
@endsection