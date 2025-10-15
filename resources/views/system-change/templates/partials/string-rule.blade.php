<div class="rule-item border rounded p-3 mb-3" data-type="string" data-index="{{ $index }}">
    <div class="rule-header d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0">
            <i class="fas fa-search text-success mr-2"></i>
            字符串替换规则 #{{ $index + 1 }}
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
                       placeholder="/var/www/html/config.php" 
                       value="{{ $rule['file_path'] ?? '' }}" required>
                <small class="text-muted">要处理的文件路径</small>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label required">查找字符串</label>
                <textarea class="form-control rule-search-string" rows="3" 
                          placeholder="要查找的字符串内容" required>{{ $rule['search_string'] ?? '' }}</textarea>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label required">替换字符串</label>
                <textarea class="form-control rule-replace-string" rows="3" 
                          placeholder="替换后的内容，支持变量 @{{variable_name@}}" required>{{ $rule['replace_string'] ?? '' }}</textarea>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input rule-case-sensitive" 
                           id="case_sensitive_{{ $index }}" 
                           {{ ($rule['case_sensitive'] ?? false) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="case_sensitive_{{ $index }}">区分大小写</label>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input rule-regex-mode" 
                           id="regex_mode_{{ $index }}" 
                           {{ ($rule['regex_mode'] ?? false) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="regex_mode_{{ $index }}">正则表达式模式</label>
                </div>
            </div>
        </div>
    </div>
    
    <div class="form-group">
        <label class="form-label">规则描述</label>
        <input type="text" class="form-control rule-description" 
               placeholder="描述这个规则的作用" 
               value="{{ $rule['description'] ?? '' }}">
    </div>
</div>