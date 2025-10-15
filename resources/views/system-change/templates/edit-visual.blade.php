@extends('layouts.app')

@section('title', '编辑配置模板')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-edit mr-2"></i>
                        编辑配置模板 - {{ $template->name }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('system-change.templates.show', $template) }}" class="btn btn-info btn-sm">
                            <i class="fas fa-eye mr-1"></i>
                            查看详情
                        </a>
                        <a href="{{ route('system-change.templates.index') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i>
                            返回列表
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('system-change.templates.update', $template) }}" id="template-form">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <!-- 基本信息 -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name" class="required">模板名称</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $template->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="template_type">模板类型</label>
                                    <select class="form-control" id="template_type" name="template_type">
                                        <option value="mixed" {{ old('template_type', $template->template_type) == 'mixed' ? 'selected' : '' }}>混合模式</option>
                                        <option value="directory" {{ old('template_type', $template->template_type) == 'directory' ? 'selected' : '' }}>目录批量处理</option>
                                        <option value="file" {{ old('template_type', $template->template_type) == 'file' ? 'selected' : '' }}>文件精确处理</option>
                                        <option value="string" {{ old('template_type', $template->template_type) == 'string' ? 'selected' : '' }}>字符串替换</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="description">模板描述</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" name="description" rows="2">{{ old('description', $template->description) }}</textarea>
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
                                               name="is_active" value="1" {{ old('is_active', $template->is_active) ? 'checked' : '' }}>
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
                                    <button type="button" class="btn btn-sm btn-primary" id="add-variable-btn">
                                        <i class="fas fa-plus mr-1"></i>
                                        添加变量
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="variables-container">
                                    @if($template->template_variables && count($template->template_variables) > 0)
                                        @foreach($template->template_variables as $index => $variable)
                                            <div class="variable-item border rounded p-3 mb-3" data-index="{{ $index }}">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <div class="form-group mb-2">
                                                            <label class="form-label">变量名</label>
                                                            <input type="text" class="form-control variable-name" 
                                                                   placeholder="例如: db_host" value="{{ $variable['name'] ?? '' }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group mb-2">
                                                            <label class="form-label">默认值</label>
                                                            <input type="text" class="form-control variable-default" 
                                                                   placeholder="默认值" value="{{ $variable['default_value'] ?? '' }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <div class="form-group mb-2">
                                                            <label class="form-label">描述</label>
                                                            <input type="text" class="form-control variable-description" 
                                                                   placeholder="变量说明" value="{{ $variable['description'] ?? '' }}">
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
                                        @endforeach
                                    @else
                                        <div class="text-muted text-center py-3">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            点击"添加变量"开始定义模板变量
                                        </div>
                                    @endif
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
                                            <a class="dropdown-item" href="#" onclick="addRule('directory')">
                                                <i class="fas fa-folder mr-2"></i>
                                                目录批量处理
                                            </a>
                                            <a class="dropdown-item" href="#" onclick="addRule('file')">
                                                <i class="fas fa-file mr-2"></i>
                                                文件精确处理
                                            </a>
                                            <a class="dropdown-item" href="#" onclick="addRule('string')">
                                                <i class="fas fa-search mr-2"></i>
                                                字符串替换
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="rules-container">
                                    @if($template->config_rules && count($template->config_rules) > 0)
                                        @foreach($template->config_rules as $index => $rule)
                                            @if($rule['type'] == 'directory')
                                                @include('system-change.templates.partials.directory-rule', ['rule' => $rule, 'index' => $index])
                                            @elseif($rule['type'] == 'file')
                                                @include('system-change.templates.partials.file-rule', ['rule' => $rule, 'index' => $index])
                                            @elseif($rule['type'] == 'string')
                                                @include('system-change.templates.partials.string-rule', ['rule' => $rule, 'index' => $index])
                                            @endif
                                        @endforeach
                                    @else
                                        <div class="text-muted text-center py-3">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            点击"添加规则"开始配置处理规则
                                        </div>
                                    @endif
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
                            更新模板
                        </button>
                        <button type="button" class="btn btn-info ml-2" id="preview-config-btn">
                            <i class="fas fa-eye mr-1"></i>
                            预览配置
                        </button>
                        <a href="{{ route('system-change.templates.show', $template) }}" class="btn btn-default ml-2">
                            <i class="fas fa-times mr-1"></i>
                            取消
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 使用相同的模板 -->
@include('system-change.templates.partials.rule-templates')
@endsection

@push('styles')
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
</style>
@endpush

@section('scripts')
<script>
let variableIndex = {{ $template->template_variables ? count($template->template_variables) : 0 }};
let ruleIndex = {{ $template->config_rules ? count($template->config_rules) : 0 }};

$(document).ready(function() {
    // 初始化预览
    updatePreview();
    
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
        if (!collectFormData()) {
            e.preventDefault();
            alert('请检查配置项是否完整');
        }
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
    
    // 输入变化时更新预览
    $(document).on('input change', '.variable-name, .variable-default, .variable-description, .rule-item input, .rule-item select, .rule-item textarea', function() {
        clearTimeout(window.previewTimeout);
        window.previewTimeout = setTimeout(updatePreview, 500);
    });
});

// 添加变量到规则
$(document).on('click', '.add-rule-variable', function() {
    var container = $(this).closest('.rule-item').find('.rule-variables-container');
    var varIndex = container.find('.rule-variable-item').length;
    
    var variableHtml = `
        <div class="rule-variable-item row mb-2" data-var-index="${varIndex}">
            <div class="col-md-3">
                <input type="text" class="form-control rule-variable" 
                       placeholder="变量名" required>
            </div>
            <div class="col-md-3">
                <select class="form-control rule-match-type">
                    <option value="key_value">键值对模式</option>
                    <option value="regex">正则表达式</option>
                    <option value="exact">精确匹配</option>
                    <option value="line">整行替换</option>
                </select>
            </div>
            <div class="col-md-5">
                <input type="text" class="form-control rule-match-pattern" 
                       placeholder="匹配表达式">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-sm btn-danger remove-rule-variable">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    
    container.append(variableHtml);
    updateVariableButtons();
});

// 删除变量
$(document).on('click', '.remove-rule-variable', function() {
    $(this).closest('.rule-variable-item').remove();
    updateVariableButtons();
});

// 添加变量
function addVariable() {
    console.log('添加变量，当前索引:', variableIndex);
    
    // 使用更可靠的方法获取模板内容
    const templateElement = document.getElementById('variable-template');
    if (!templateElement) {
        console.error('找不到变量模板');
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
        console.error('变量模板内容为空');
        return;
    }
    
    const html = template.replace(/__INDEX__/g, variableIndex);
    
    // 如果容器中有提示信息，先清空
    if ($('#variables-container .text-muted').length && $('#variables-container .variable-item').length === 0) {
        $('#variables-container').empty();
    }
    
    $('#variables-container').append(html);
    variableIndex++;
    updatePreview();
    
    console.log('变量添加完成，当前变量数量:', $('#variables-container .variable-item').length);
}

// 添加规则
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
    
    // 如果容器中有提示信息，先清空
    if ($('#rules-container .text-muted').length && $('#rules-container .rule-item').length === 0) {
        $('#rules-container').empty();
    }
    
    $('#rules-container').append(html);
    ruleIndex++;
    updatePreview();
    
    console.log('规则添加完成，当前规则数量:', $('#rules-container .rule-item').length);
    
    // 初始化新添加规则的变量按钮状态
    updateVariableButtons();
}

// 更新变量按钮显示状态
function updateVariableButtons() {
    $('.rule-item').each(function() {
        var variableItems = $(this).find('.rule-variable-item');
        if (variableItems.length <= 1) {
            variableItems.find('.remove-rule-variable').hide();
        } else {
            variableItems.find('.remove-rule-variable').show();
        }
    });
}

// 收集表单数据（更新为支持多变量）
function collectFormData() {
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
    
    // 收集规则数据（支持多变量）
    const rules = [];
    $('.rule-item').each(function() {
        const type = $(this).data('type');
        const ruleData = {
            type: type,
            description: $(this).find('.rule-description').val()
        };
        
        if (type === 'directory') {
            ruleData.directory = $(this).find('.rule-directory').val();
            ruleData.pattern = $(this).find('.rule-pattern').val();
            
            // 收集多个变量
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
            
            if (variables.length > 0) {
                ruleData.variables = variables;
            }
            
        } else if (type === 'file') {
            ruleData.file_path = $(this).find('.rule-file-path').val();
            
            // 收集多个变量
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
            
            if (variables.length > 0) {
                ruleData.variables = variables;
            }
            
        } else if (type === 'string') {
            ruleData.file_path = $(this).find('.rule-file-path').val();
            ruleData.search_string = $(this).find('.rule-search-string').val();
            ruleData.replace_string = $(this).find('.rule-replace-string').val();
            ruleData.case_sensitive = $(this).find('.rule-case-sensitive').is(':checked');
            ruleData.regex_mode = $(this).find('.rule-regex-mode').is(':checked');
        }
        
        // 验证必填字段
        if (ruleData.file_path || ruleData.directory) {
            rules.push(ruleData);
        }
    });
    
    // 设置隐藏字段
    $('#template_variables_input').val(JSON.stringify(variables));
    $('#config_rules_input').val(JSON.stringify(rules));
    
    return rules.length > 0;
}

// 更新预览（支持多变量显示）
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
                    rule.variables.forEach(variable => {
                        html += `<li><code>@{{${variable.variable}@}}</code> - ${variable.match_type}`;
                        if (variable.match_pattern) {
                            html += ` - <code>${variable.match_pattern}</code>`;
                        }
                        html += '</li>';
                    });
                    html += '</ul>';
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
                    rule.variables.forEach(variable => {
                        html += `<li><code>@{{${variable.variable}@}}</code> - ${variable.match_type}`;
                        if (variable.match_pattern) {
                            html += ` - <code>${variable.match_pattern}</code>`;
                        }
                        html += '</li>';
                    });
                    html += '</ul>';
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

// 初始化变量按钮状态
updateVariableButtons();
</script>
@endsection