@extends('layouts.app')

@section('title', '编辑配置模板')

@section('content')
<div class="container-fluid">
    <!-- 页面标题 -->
    <div class="mb-4">
        <h1 class="mb-1">
            <i class="fas fa-edit text-primary"></i> 编辑配置模板
        </h1>
        <p class="text-muted">修改系统变更模板的配置和规则</p>
    </div>
    
    <!-- 操作按钮 -->
    <div class="mb-4">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('system-change.templates.show', $template) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 返回详情
            </a>
            <a href="{{ route('system-change.templates.index') }}" class="btn btn-secondary">
                <i class="fas fa-list"></i> 模板列表
            </a>
        </div>
    </div>

    <form action="{{ route('system-change.templates.update', $template) }}" method="POST" id="template-form">
        @csrf
        @method('PUT')
        
        <div class="row">
            <!-- 基本信息 -->
            <div class="col-md-4">
                <div class="card card-light-blue shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle"></i> 基本信息
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">模板名称 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name', $template->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">描述</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description', $template->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                       value="1" {{ old('is_active', $template->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    启用模板
                                </label>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> 保存模板
                            </button>
                            <button type="button" class="btn btn-secondaryinfo" id="preview-btn">
                                <i class="fas fa-eye"></i> 预览配置
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 模板变量 -->
                <div class="card mt-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">模板变量</h6>
                        <button type="button" class="btn btn-sm btn-secondary" id="add-variable-btn">
                            <i class="fas fa-plus"></i> 添加变量
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="variables-container">
                            @if(isset($template->variables) && is_array($template->variables))
                                @foreach($template->variables as $index => $variable)
                                    <div class="variable-item border rounded p-2 mb-2">
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <input type="text" class="form-control form-control-sm" 
                                                       name="variables[{{ $index }}][name]" 
                                                       placeholder="变量名" 
                                                       value="{{ $variable['name'] ?? '' }}">
                                            </div>
                                            <div class="col-12">
                                                <input type="text" class="form-control form-control-sm" 
                                                       name="variables[{{ $index }}][default_value]" 
                                                       placeholder="默认值" 
                                                       value="{{ $variable['default_value'] ?? '' }}">
                                            </div>
                                            <div class="col-10">
                                                <input type="text" class="form-control form-control-sm" 
                                                       name="variables[{{ $index }}][description]" 
                                                       placeholder="说明" 
                                                       value="{{ $variable['description'] ?? '' }}">
                                            </div>
                                            <div class="col-2">
                                                <button type="button" class="btn btn-sm btn-danger remove-variable-btn w-100">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                        <small class="text-muted">
                            变量可在配置项中使用 <code>@{{变量名@}}</code> 的格式引用
                        </small>
                    </div>
                </div>
            </div>

            <!-- 配置项编辑 -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">配置项设置</h5>
                        <div>
                            <button type="button" class="btn btn-sm btn-primary" id="add-config-item-btn">
                                <i class="fas fa-plus"></i> 添加配置项
                            </button>
                            <button type="button" class="btn btn-sm btn-primary" id="format-json-btn">
                                <i class="fas fa-code"></i> 格式化JSON
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="config_items" class="form-label">
                                配置项JSON <span class="text-danger">*</span>
                                <small class="text-muted">(请按照指定格式填写)</small>
                            </label>
                            <textarea class="form-control @error('config_items') is-invalid @enderror" 
                                      id="config_items" name="config_items" rows="20" required>{{ old('config_items', json_encode($template->config_items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) }}</textarea>
                            @error('config_items')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 配置项格式说明 -->
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> 配置项格式说明</h6>
                            <pre class="mb-0"><code>[
  {
    "name": "配置项名称",
    "file_path": "/path/to/config/file",
    "modifications": [
      {
        "pattern": "正则表达式匹配模式",
        "replacement": "替换内容",
        "description": "修改说明"
      }
    ]
  }
]</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- 预览模态框 -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">配置预览</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="preview-content">
                    <!-- 预览内容将在这里显示 -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let variableIndex = {{ isset($template->variables) ? count($template->variables) : 0 }};

    // 添加变量
    $('#add-variable-btn').click(function() {
        const variableHtml = 
            '<div class="variable-item border rounded p-2 mb-2">' +
                '<div class="row g-2">' +
                    '<div class="col-12">' +
                        '<input type="text" class="form-control form-control-sm" ' +
                               'name="variables[' + variableIndex + '][name]" ' +
                               'placeholder="变量名">' +
                    '</div>' +
                    '<div class="col-12">' +
                        '<input type="text" class="form-control form-control-sm" ' +
                               'name="variables[' + variableIndex + '][default_value]" ' +
                               'placeholder="默认值">' +
                    '</div>' +
                    '<div class="col-10">' +
                        '<input type="text" class="form-control form-control-sm" ' +
                               'name="variables[' + variableIndex + '][description]" ' +
                               'placeholder="说明">' +
                    '</div>' +
                    '<div class="col-2">' +
                        '<button type="button" class="btn btn-sm btn-danger remove-variable-btn w-100">' +
                            '<i class="fas fa-times"></i>' +
                        '</button>' +
                    '</div>' +
                '</div>' +
            '</div>';
        $('#variables-container').append(variableHtml);
        variableIndex++;
    });

    // 删除变量
    $(document).on('click', '.remove-variable-btn', function() {
        $(this).closest('.variable-item').remove();
    });

    // 添加配置项模板
    $('#add-config-item-btn').click(function() {
        const template = {
            "name": "新配置项",
            "file_path": "/path/to/config/file",
            "modifications": [
                {
                    "pattern": "匹配模式",
                    "replacement": "替换内容",
                    "description": "修改说明"
                }
            ]
        };

        try {
            const currentConfig = JSON.parse($('#config_items').val() || '[]');
            currentConfig.push(template);
            $('#config_items').val(JSON.stringify(currentConfig, null, 2));
        } catch (e) {
            alert('当前JSON格式有误，请先修正后再添加新配置项');
        }
    });

    // 格式化JSON
    $('#format-json-btn').click(function() {
        try {
            const config = JSON.parse($('#config_items').val());
            $('#config_items').val(JSON.stringify(config, null, 2));
            $(this).removeClass('btn-primary').addClass('btn-primary');
            setTimeout(() => {
                $(this).removeClass('btn-primary').addClass('btn-primary');
            }, 1000);
        } catch (e) {
            alert('JSON格式错误: ' + e.message);
        }
    });

    // 预览配置
    $('#preview-btn').click(function() {
        const configItems = $('#config_items').val();
        const variables = {};
        
        // 收集变量
        $('.variable-item').each(function() {
            const name = $(this).find('input[name*="[name]"]').val();
            const defaultValue = $(this).find('input[name*="[default_value]"]').val();
            if (name) {
                variables[name] = defaultValue || '';
            }
        });

        $.ajax({
            url: '{{ route("system-change.templates.preview") }}',
            method: 'POST',
            data: {
                config_items: configItems,
                variables: variables,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    displayPreview(response.preview);
                    $('#previewModal').modal('show');
                } else {
                    alert('预览失败: ' + (response.message || '未知错误'));
                }
            },
            error: function(xhr) {
                alert('预览失败: ' + (xhr.responseJSON?.message || '网络错误'));
            }
        });
    });

    // 显示预览内容
    function displayPreview(preview) {
        let html = '';
        
        preview.forEach((item, index) => {
            html += `
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">${item.name}</h6>
                        <small class="text-muted">${item.file_path}</small>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>匹配模式</th>
                                    <th>替换内容</th>
                                    <th>说明</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            
            item.modifications.forEach(mod => {
                html += `
                    <tr>
                        <td><code>${mod.pattern}</code></td>
                        <td><code>${mod.replacement}</code></td>
                        <td>${mod.description}</td>
                    </tr>
                `;
            });
            
            html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        });
        
        $('#preview-content').html(html);
    }

    // 表单提交前验证
    $('#template-form').submit(function(e) {
        try {
            JSON.parse($('#config_items').val());
        } catch (error) {
            e.preventDefault();
            alert('配置项JSON格式错误: ' + error.message);
            return false;
        }
    });
});
</script>
@endsection