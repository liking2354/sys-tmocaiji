@extends('layouts.app')

@section('title', '查看配置模板')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-eye mr-2"></i>
                        配置模板详情 - {{ $template->name }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('system-change.templates.edit', $template) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit mr-1"></i>
                            编辑模板
                        </a>
                        <a href="{{ route('system-change.templates.index') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i>
                            返回列表
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- 基本信息 -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="120"><strong>模板名称:</strong></td>
                                    <td>{{ $template->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>模板类型:</strong></td>
                                    <td>
                                        @switch($template->template_type)
                                            @case('mixed')
                                                <span class="badge badge-primary">混合模式</span>
                                                @break
                                            @case('directory')
                                                <span class="badge badge-warning">目录批量处理</span>
                                                @break
                                            @case('file')
                                                <span class="badge badge-info">文件精确处理</span>
                                                @break
                                            @case('string')
                                                <span class="badge badge-success">字符串替换</span>
                                                @break
                                            @default
                                                <span class="badge badge-secondary">未知类型</span>
                                        @endswitch
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>模板描述:</strong></td>
                                    <td>{{ $template->description ?: '无描述' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>状态:</strong></td>
                                    <td>
                                        @if($template->is_active)
                                            <span class="badge badge-success">启用</span>
                                        @else
                                            <span class="badge badge-secondary">禁用</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>创建时间:</strong></td>
                                    <td>{{ $template->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>更新时间:</strong></td>
                                    <td>{{ $template->updated_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-tags"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">模板变量</span>
                                    <span class="info-box-number">{{ $template->template_variables ? count($template->template_variables) : 0 }} 个</span>
                                </div>
                            </div>
                            <div class="info-box">
                                <span class="info-box-icon bg-success">
                                    <i class="fas fa-cogs"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">配置规则</span>
                                    <span class="info-box-number">{{ $template->config_rules ? count($template->config_rules) : 0 }} 个</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 模板变量 -->
                    @if($template->template_variables && count($template->template_variables) > 0)
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-tags mr-2"></i>
                                    模板变量 ({{ count($template->template_variables) }}个)
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    @foreach($template->template_variables as $variable)
                                        <div class="col-md-4 mb-3">
                                            <div class="card card-outline card-primary">
                                                <div class="card-body">
                                                    <h6 class="card-title">
                                                        <code>@{{{{ $variable['name'] }}@}}</code>
                                                    </h6>
                                                    <p class="card-text">
                                                        <small class="text-muted">默认值:</small> 
                                                        <code>{{ $variable['default_value'] ?: '无' }}</code>
                                                    </p>
                                                    @if(!empty($variable['description']))
                                                        <p class="card-text">
                                                            <small>{{ $variable['description'] }}</small>
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- 配置规则 -->
                    @if($template->config_rules && count($template->config_rules) > 0)
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-cogs mr-2"></i>
                                    配置规则 ({{ count($template->config_rules) }}个)
                                </h5>
                            </div>
                            <div class="card-body">
                                @foreach($template->config_rules as $index => $rule)
                                    <div class="rule-display border rounded p-3 mb-3">
                                        @if($rule['type'] == 'directory')
                                            <h6>
                                                <i class="fas fa-folder text-warning mr-2"></i>
                                                目录批量处理规则 #{{ $index + 1 }}
                                                <span class="badge badge-warning ml-2">目录</span>
                                            </h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong>目标目录:</strong> <code>{{ $rule['directory'] }}</code></p>
                                                    <p><strong>文件模式:</strong> <code>{{ $rule['pattern'] ?: '*' }}</code></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>变量名:</strong> <code>@{{{{ $rule['variable'] }}@}}</code></p>
                                                    <p><strong>匹配模式:</strong> {{ $rule['match_type'] }}</p>
                                                    @if(!empty($rule['match_pattern']))
                                                        <p><strong>匹配表达式:</strong> <code>{{ $rule['match_pattern'] }}</code></p>
                                                    @endif
                                                </div>
                                            </div>
                                        @elseif($rule['type'] == 'file')
                                            <h6>
                                                <i class="fas fa-file text-info mr-2"></i>
                                                文件精确处理规则 #{{ $index + 1 }}
                                                <span class="badge badge-info ml-2">文件</span>
                                            </h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong>目标文件:</strong> <code>{{ $rule['file_path'] }}</code></p>
                                                    <p><strong>变量名:</strong> <code>@{{{{ $rule['variable'] }}@}}</code></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>匹配模式:</strong> {{ $rule['match_type'] }}</p>
                                                    @if(!empty($rule['match_pattern']))
                                                        <p><strong>匹配表达式:</strong> <code>{{ $rule['match_pattern'] }}</code></p>
                                                    @endif
                                                </div>
                                            </div>
                                        @elseif($rule['type'] == 'string')
                                            <h6>
                                                <i class="fas fa-search text-success mr-2"></i>
                                                字符串替换规则 #{{ $index + 1 }}
                                                <span class="badge badge-success ml-2">字符串</span>
                                            </h6>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <p><strong>目标文件:</strong> <code>{{ $rule['file_path'] }}</code></p>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong>查找字符串:</strong></p>
                                                    <pre class="bg-light p-2 border rounded"><code>{{ $rule['search_string'] }}</code></pre>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>替换字符串:</strong></p>
                                                    <pre class="bg-light p-2 border rounded"><code>{{ $rule['replace_string'] }}</code></pre>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <p><strong>选项:</strong>
                                                        @if($rule['case_sensitive'] ?? false)
                                                            <span class="badge badge-secondary">区分大小写</span>
                                                        @endif
                                                        @if($rule['regex_mode'] ?? false)
                                                            <span class="badge badge-secondary">正则模式</span>
                                                        @endif
                                                        @if(!($rule['case_sensitive'] ?? false) && !($rule['regex_mode'] ?? false))
                                                            <span class="text-muted">无特殊选项</span>
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>
                                        @endif
                                        
                                        @if(!empty($rule['description']))
                                            <div class="mt-2">
                                                <p><strong>规则说明:</strong> {{ $rule['description'] }}</p>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- 使用统计 -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-bar mr-2"></i>
                                使用统计
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="info-box bg-info">
                                        <span class="info-box-icon">
                                            <i class="fas fa-tasks"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">关联任务</span>
                                            <span class="info-box-number">{{ $template->systemChangeTasks()->count() }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box bg-success">
                                        <span class="info-box-icon">
                                            <i class="fas fa-check"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">成功执行</span>
                                            <span class="info-box-number">{{ $template->systemChangeTasks()->where('status', 'completed')->count() }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box bg-warning">
                                        <span class="info-box-icon">
                                            <i class="fas fa-clock"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">执行中</span>
                                            <span class="info-box-number">{{ $template->systemChangeTasks()->where('status', 'running')->count() }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box bg-danger">
                                        <span class="info-box-icon">
                                            <i class="fas fa-times"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">执行失败</span>
                                            <span class="info-box-number">{{ $template->systemChangeTasks()->where('status', 'failed')->count() }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <a href="{{ route('system-change.templates.edit', $template) }}" class="btn btn-warning">
                        <i class="fas fa-edit mr-1"></i>
                        编辑模板
                    </a>
                    <button type="button" class="btn btn-info ml-2" onclick="duplicateTemplate()">
                        <i class="fas fa-copy mr-1"></i>
                        复制模板
                    </button>
                    <button type="button" class="btn btn-secondary ml-2" onclick="toggleStatus()">
                        <i class="fas fa-toggle-{{ $template->is_active ? 'on' : 'off' }} mr-1"></i>
                        {{ $template->is_active ? '禁用' : '启用' }}模板
                    </button>
                    <a href="{{ route('system-change.templates.index') }}" class="btn btn-default ml-2">
                        <i class="fas fa-arrow-left mr-1"></i>
                        返回列表
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection



@push('scripts')
<script>
    window.duplicateTemplateUrl = '{{ route("system-change.templates.duplicate", $template) }}';
    window.toggleStatusUrl = '{{ route("system-change.templates.toggle-status", $template) }}';
    window.csrfToken = '{{ csrf_token() }}';
    window.templateIsActive = {{ $template->is_active ? 'true' : 'false' }};
</script>
<script src="{{ asset('assets/js/modules/system-change-templates-show.js') }}"></script>
@endpush
