/**
 * 系统变更模板编辑模块
 * 功能：可视化编辑、变量管理、规则管理、预览
 */

let variableIndex = window.variableIndex || 0;
let ruleIndex = window.ruleIndex || 0;

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

// 收集表单数据（支持多变量）
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
