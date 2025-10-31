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
                        创建配置模板
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('system-change.templates.index') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i>
                            返回列表
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('system-change.templates.store') }}">
                    @csrf
                    <div class="card-body">
                        <div class="row">
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
                                    <label for="is_active">状态</label>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="is_active" 
                                               name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="is_active">启用模板</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">模板描述</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="config_items" class="required">配置项 <small class="text-muted">(JSON格式)</small></label>
                            <textarea class="form-control @error('config_items') is-invalid @enderror" 
                                      id="config_items" name="config_items" rows="20" required>{{ old('config_items', $defaultConfigItems ?? '') }}</textarea>
                            @error('config_items')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                请输入有效的JSON格式配置项。<a href="#" data-toggle="modal" data-target="#exampleModal">查看示例</a>
                            </small>
                        </div>

                        <!-- 预览区域 -->
                        <div class="form-group">
                            <label>配置预览</label>
                            <div id="config-preview" class="border rounded p-3 bg-light" style="min-height: 100px;">
                                <div class="text-muted text-center">
                                    <i class="fas fa-eye fa-2x mb-2"></i><br>
                                    输入配置项后将显示预览
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>
                            保存模板
                        </button>
                        <button type="button" class="btn btn-info ml-2" id="preview-btn">
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

<!-- 配置示例模态框 -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">配置项示例</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>以下是一个配置项的JSON示例：</p>
                <pre><code>{
  "config_items": [
    {
      "name": "数据库配置",
      "file_path": "/var/www/html/.env",
      "modifications": [
        {
          "type": "replace",
          "pattern": "DB_HOST=.*",
          "replacement": "DB_HOST=@{{db_host@}}",
          "description": "数据库主机地址"
        },
        {
          "type": "replace",
          "pattern": "DB_DATABASE=.*",
          "replacement": "DB_DATABASE=@{{db_name@}}",
          "description": "数据库名称"
        }
      ]
    },
    {
      "name": "域名配置",
      "file_path": "/etc/nginx/sites-available/default",
      "modifications": [
        {
          "type": "replace",
          "pattern": "server_name .*;",
          "replacement": "server_name @{{domain_name@}};",
          "description": "服务器域名"
        }
      ]
    }
  ]
}</code></pre>
                <div class="mt-3">
                    <h6>字段说明：</h6>
                    <ul>
                        <li><code>name</code>: 配置项名称</li>
                        <li><code>file_path</code>: 要修改的文件路径</li>
                        <li><code>type</code>: 修改类型（目前支持 replace）</li>
                        <li><code>pattern</code>: 匹配的正则表达式模式</li>
                        <li><code>replacement</code>: 替换的内容，支持变量 @{{variable_name@}}</li>
                        <li><code>description</code>: 修改描述</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
                <button type="button" class="btn btn-primary" onclick="useExample()">使用此示例</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // 预览按钮点击事件
    $('#preview-btn').click(function() {
        previewConfig();
    });
    
    // 配置项内容变化时自动预览
    $('#config_items').on('input', function() {
        clearTimeout(window.previewTimeout);
        window.previewTimeout = setTimeout(previewConfig, 1000);
    });
});

function previewConfig() {
    const configItems = $('#config_items').val();
    
    if (!configItems.trim()) {
        $('#config-preview').html(`
            <div class="text-muted text-center">
                <i class="fas fa-eye fa-2x mb-2"></i><br>
                输入配置项后将显示预览
            </div>
        `);
        return;
    }
    
    try {
        const config = JSON.parse(configItems);
        displayPreview(config);
    } catch (e) {
        $('#config-preview').html(`
            <div class="text-danger text-center">
                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>
                JSON格式错误: ${e.message}
            </div>
        `);
    }
}

function displayPreview(config) {
    if (!config.config_items || !Array.isArray(config.config_items)) {
        $('#config-preview').html(`
            <div class="text-warning text-center">
                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>
                配置格式错误：缺少 config_items 数组
            </div>
        `);
        return;
    }
    
    let html = '';
    config.config_items.forEach((item, index) => {
        html += `
            <div class="config-item">
                <h6><i class="fas fa-file-code mr-2"></i>${item.name || '未命名配置项'}</h6>
                <p><strong>文件路径:</strong> <code>${item.file_path || '未指定'}</code></p>
                <div class="modifications">
                    <strong>修改规则:</strong>
        `;
        
        if (item.modifications && Array.isArray(item.modifications)) {
            item.modifications.forEach((mod, modIndex) => {
                html += `
                    <div class="modification-item">
                        <div><strong>类型:</strong> ${mod.type || 'replace'}</div>
                        <div><strong>匹配:</strong> <code>${mod.pattern || ''}</code></div>
                        <div><strong>替换:</strong> <code>${mod.replacement || ''}</code></div>
                        ${mod.description ? `<div><strong>说明:</strong> ${mod.description}</div>` : ''}
                    </div>
                `;
            });
        } else {
            html += '<div class="text-muted">无修改规则</div>';
        }
        
        html += `
                </div>
            </div>
        `;
    });
    
    $('#config-preview').html(html);
}

function useExample() {
    const example = `{
  "config_items": [
    {
      "name": "数据库配置",
      "file_path": "/var/www/html/.env",
      "modifications": [
        {
          "type": "replace",
          "pattern": "DB_HOST=.*",
          "replacement": "DB_HOST=\\{\\{db_host\\}\\}",
          "description": "数据库主机地址"
        },
        {
          "type": "replace",
          "pattern": "DB_DATABASE=.*",
          "replacement": "DB_DATABASE=\\{\\{db_name\\}\\}",
          "description": "数据库名称"
        }
      ]
    },
    {
      "name": "域名配置",
      "file_path": "/etc/nginx/sites-available/default",
      "modifications": [
        {
          "type": "replace",
          "pattern": "server_name .*;",
          "replacement": "server_name \\{\\{domain_name\\}\\};",
          "description": "服务器域名"
        }
      ]
    }
  ]
}`;
    
    $('#config_items').val(example);
    $('#exampleModal').modal('hide');
    previewConfig();
}
</script>
@endpush