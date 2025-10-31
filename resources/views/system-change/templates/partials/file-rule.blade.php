<div class="rule-item border rounded p-3 mb-3" data-type="file" data-index="{{ $index }}">
    <div class="rule-header d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0">
            <i class="fas fa-file text-info mr-2"></i>
            文件精确处理规则 #{{ $index + 1 }}
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
                <input type="text" class="form-control rule-file-path" 
                       placeholder="/etc/nginx/nginx.conf" 
                       value="{{ $rule['file_path'] ?? '' }}" required>
                <small class="text-muted">要处理的具体文件路径</small>
            </div>
        </div>
    </div>
    
    <!-- 变量配置区域 -->
    <div class="variables-config-area">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="form-label required">变量配置</label>
            <button type="button" class="btn btn-sm btn-secondary add-rule-variable">
                <i class="fas fa-plus mr-1"></i>
                添加变量
            </button>
        </div>
        <div class="rule-variables-container">
            @php
                // 处理新旧格式兼容
                $variables = [];
                if (isset($rule['variables']) && is_array($rule['variables'])) {
                    // 新的多变量格式
                    $variables = $rule['variables'];
                } elseif (isset($rule['variable'])) {
                    // 旧的单变量格式，转换为新格式
                    $variables = [[
                        'variable' => $rule['variable'],
                        'match_type' => $rule['match_type'] ?? 'key_value',
                        'match_pattern' => $rule['match_pattern'] ?? ''
                    ]];
                }
                
                // 如果没有变量，至少显示一个空的
                if (empty($variables)) {
                    $variables = [[
                        'variable' => '',
                        'match_type' => 'key_value',
                        'match_pattern' => ''
                    ]];
                }
            @endphp
            
            @foreach($variables as $varIndex => $variable)
            <div class="rule-variable-item row mb-2" data-var-index="{{ $varIndex }}">
                <div class="col-md-3">
                    <input type="text" class="form-control rule-variable" 
                           placeholder="变量名 (如: server_name)" 
                           value="{{ $variable['variable'] ?? '' }}" required>
                </div>
                <div class="col-md-3">
                    <select class="form-control rule-match-type">
                        <option value="key_value" {{ ($variable['match_type'] ?? 'key_value') == 'key_value' ? 'selected' : '' }}>键值对模式</option>
                        <option value="regex" {{ ($variable['match_type'] ?? '') == 'regex' ? 'selected' : '' }}>正则表达式</option>
                        <option value="line" {{ ($variable['match_type'] ?? '') == 'line' ? 'selected' : '' }}>整行替换</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <input type="text" class="form-control rule-match-pattern" 
                           placeholder="匹配表达式 (如: server_name .*;)" 
                           value="{{ $variable['match_pattern'] ?? '' }}">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-danger remove-rule-variable" 
                            style="{{ count($variables) <= 1 ? 'display: none;' : '' }}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    
    <div class="form-group">
        <label class="form-label">规则描述</label>
        <input type="text" class="form-control rule-description" 
               placeholder="描述这个规则的作用" 
               value="{{ $rule['description'] ?? '' }}">
    </div>
</div>