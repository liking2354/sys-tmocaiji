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
        
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label required">变量名</label>
                    <input type="text" class="form-control rule-variable" placeholder="db_host" required>
                    <small class="text-muted">要替换的变量名</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label required">匹配模式</label>
                    <select class="form-control rule-match-type">
                        <option value="key_value">键值对模式</option>
                        <option value="regex">正则表达式</option>
                        <option value="exact">精确匹配</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">匹配表达式</label>
                    <input type="text" class="form-control rule-match-pattern" placeholder="DB_HOST=.*">
                    <small class="text-muted">留空则使用变量名作为key</small>
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
        
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label required">变量名</label>
                    <input type="text" class="form-control rule-variable" placeholder="server_name" required>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label required">匹配模式</label>
                    <select class="form-control rule-match-type">
                        <option value="key_value">键值对模式</option>
                        <option value="regex">正则表达式</option>
                        <option value="line">整行替换</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">匹配表达式</label>
                    <input type="text" class="form-control rule-match-pattern" placeholder="server_name .*;">
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