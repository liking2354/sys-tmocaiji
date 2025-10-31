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



@push('scripts')
<script>
    window.variableIndex = {{ $template->template_variables ? count($template->template_variables) : 0 }};
    window.ruleIndex = {{ $template->config_rules ? count($template->config_rules) : 0 }};
</script>
<script src="{{ asset('assets/js/modules/system-change-templates-edit.js') }}"></script>
@endpush
