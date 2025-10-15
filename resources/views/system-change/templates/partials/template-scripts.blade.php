$(document).ready(function() {
    // 删除规则
    $(document).on('click', '.remove-rule', function() {
        $(this).closest('.rule-item').remove();
        updateRuleIndexes();
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
    
    // 更新规则索引
    function updateRuleIndexes() {
        $('.rule-item').each(function(index) {
            $(this).attr('data-index', index);
            $(this).find('.rule-header h6').html(function() {
                var icon = $(this).find('i').prop('outerHTML');
                var text = $(this).text().replace(/#\d+/, '#' + (index + 1));
                return icon + text;
            });
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
    
    // 绑定表单提交前的数据收集
    $('form').on('submit', function() {
        return collectFormData();
    });
});
</script>