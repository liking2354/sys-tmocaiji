@extends('layouts.app')

@section('title', '创建配置任务')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-plus mr-2"></i>
                        创建配置任务
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('system-change.tasks.index') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i>
                            返回列表
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('system-change.tasks.store') }}">
                    @csrf
                    <div class="card-body">
                        <!-- 基本信息 -->
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="name" class="required">任务名称</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $defaultTaskName ?? '') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="execution_order">执行方式</label>
                                    <select class="form-control @error('execution_order') is-invalid @enderror" 
                                            id="execution_order" name="execution_order">
                                        <option value="sequential" {{ old('execution_order', 'sequential') === 'sequential' ? 'selected' : '' }}>
                                            顺序执行
                                        </option>
                                        <option value="parallel" {{ old('execution_order') === 'parallel' ? 'selected' : '' }}>
                                            并行执行
                                        </option>
                                    </select>
                                    @error('execution_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">任务描述</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 隐藏字段 -->
                        <input type="hidden" name="server_group_id" id="server_group_id" value="{{ $selectedServerGroupId ?? '' }}">
                        
                        <!-- 服务器选择 -->
                        <div class="form-group">
                            <label class="required">选择服务器</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="card-title mb-0">服务器分组</h6>
                                        </div>
                                        <div class="card-body p-2">
                                            <div class="checkbox-container">
                                                <div class="row">
                                                    @foreach($serverGroups as $group)
                                                    <div class="col-md-6 mb-2">
                                                        <div class="form-check">
                                                            <input class="form-check-input server-group-checkbox" 
                                                                   type="checkbox" 
                                                                   id="group_{{ $group->id }}"
                                                                   data-group-id="{{ $group->id }}"
                                                                   data-group-name="{{ $group->name }}"
                                                                   {{ (isset($selectedServerGroupId) && $selectedServerGroupId == $group->id) || in_array($group->id, old('server_group_ids', [])) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="group_{{ $group->id }}">
                                                                <strong>{{ $group->name }}</strong>
                                                                <br><small class="text-muted">({{ $group->servers_count ?? $group->servers->count() }} 台)</small>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="card-title mb-0">选择具体服务器</h6>
                                            <div class="card-tools">
                                                <button type="button" class="btn btn-tool text-primary" id="select-all-servers">
                                                    <i class="fas fa-check-square"></i> 全选
                                                </button>
                                                <button type="button" class="btn btn-tool text-secondary" id="deselect-all-servers">
                                                    <i class="fas fa-square"></i> 取消全选
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body p-2">
                                            <div class="checkbox-container">
                                                <div id="servers-container">
                                                    <div class="text-muted text-center">
                                                        <i class="fas fa-server fa-2x mb-2"></i><br>
                                                        请先选择服务器分组
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @error('server_ids')
                                <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 模板选择 -->
                        <div class="form-group">
                            <label class="required">选择配置模板</label>
                            <div class="card">
                                <div class="card-body p-2">
                                    <div class="template-container">
                                        <div class="row">
                                            @foreach($templates as $template)
                                            <div class="col-md-6 mb-2">
                                                <div class="form-check template-item">
                                                    <input class="form-check-input template-checkbox" 
                                                           type="checkbox" 
                                                           id="template_{{ $template->id }}"
                                                           name="template_ids[]"
                                                           value="{{ $template->id }}"
                                                           data-template-id="{{ $template->id }}"
                                                           {{ in_array($template->id, old('template_ids', [])) ? 'checked' : '' }}>
                                                    <label class="form-check-label w-100" for="template_{{ $template->id }}">
                                                        <strong>{{ $template->name }}</strong>
                                                        @if($template->description)
                                                            <br><small class="text-muted">{{ Str::limit($template->description, 50) }}</small>
                                                        @endif
                                                        <br><small class="text-info">
                                                            @if(isset($template->template_variables) && is_array($template->template_variables))
                                                                {{ count($template->template_variables) }} 个变量
                                                            @elseif(isset($template->config_items_count))
                                                                {{ $template->config_items_count }} 个配置项
                                                            @else
                                                                配置模板
                                                            @endif
                                                        </small>
                                                    </label>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @error('template_ids')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 变量配置 -->
                        <div class="form-group">
                            <label>配置变量</label>
                            <div id="variables-container">
                                <div class="text-muted text-center">
                                    <i class="fas fa-cogs fa-2x mb-2"></i><br>
                                    选择模板后将显示需要配置的变量
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>
                            创建任务
                        </button>
                        <a href="{{ route('system-change.tasks.index') }}" class="btn btn-default ml-2">
                            <i class="fas fa-times mr-1"></i>
                            取消
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
@endpush

@push('scripts')
<script>
    window.getVariablesUrl = '{{ route("system-change.templates.get-variables") }}';
    window.csrfToken = '{{ csrf_token() }}';
</script>
<script src="{{ asset('assets/js/modules/system-change-tasks-create.js') }}"></script>
@endpush
