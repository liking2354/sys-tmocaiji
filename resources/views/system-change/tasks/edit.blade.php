@extends('layouts.app')

@section('title', '编辑配置任务')

@section('content')
<div class="container-fluid">
    <!-- 页面标题 -->
    <div class="mb-4">
        <h1 class="mb-1">
            <i class="fas fa-edit text-primary"></i> 编辑配置任务
        </h1>
        <p class="text-muted">修改系统变更任务的配置和参数</p>
    </div>
    
    <!-- 操作按钮 -->
    <div class="mb-4">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('system-change.tasks.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 返回列表
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card card-light-blue shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-edit"></i> 编辑表单
                    </h5>
                </div>
                <form action="{{ route('system-change.tasks.update', $task->id) }}" method="POST" id="taskForm">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <!-- 基本信息 -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">任务名称 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $task->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="execution_order">执行顺序</label>
                                    <select class="form-control @error('execution_order') is-invalid @enderror" 
                                            id="execution_order" name="execution_order">
                                        <option value="sequential" {{ old('execution_order', $task->execution_order) == 'sequential' ? 'selected' : '' }}>顺序执行</option>
                                        <option value="parallel" {{ old('execution_order', $task->execution_order) == 'parallel' ? 'selected' : '' }}>并行执行</option>
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
                                      id="description" name="description" rows="3">{{ old('description', $task->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 服务器选择 -->
                        <div class="form-group">
                            <label>目标服务器 <span class="text-danger">*</span></label>
                            <div class="row">
                                <div class="col-md-4">
                                    <select class="form-control" id="server_group_id" name="server_group_id">
                                        <option value="">选择服务器分组</option>
                                        @foreach($serverGroups as $group)
                                            <option value="{{ $group->id }}" 
                                                    {{ old('server_group_id', $task->server_group_id) == $group->id ? 'selected' : '' }}>
                                                {{ $group->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <div id="servers-container">
                                        <div class="form-check-inline">
                                            <input type="checkbox" id="select-all-servers" class="form-check-input">
                                            <label for="select-all-servers" class="form-check-label">全选</label>
                                        </div>
                                        <div id="servers-list" class="mt-2">
                                            <!-- 服务器列表将通过AJAX加载 -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @error('server_ids')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 配置模板选择 -->
                        <div class="form-group">
                            <label>配置模板 <span class="text-danger">*</span></label>
                            <div class="row">
                                @foreach($templates as $template)
                                <div class="col-md-6 mb-2">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input template-checkbox" 
                                               id="template_{{ $template->id }}" 
                                               name="template_ids[]" 
                                               value="{{ $template->id }}"
                                               {{ in_array($template->id, old('template_ids', $task->template_ids ?? [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="template_{{ $template->id }}">
                                            <strong>{{ $template->name }}</strong>
                                            <br><small class="text-muted">{{ $template->description }}</small>
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @error('template_ids')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 变量配置 -->
                        <div id="variables-section" style="display: none;">
                            <h5>变量配置</h5>
                            <div id="variables-container">
                                <!-- 变量配置将通过JavaScript动态生成 -->
                            </div>
                        </div>

                        <!-- 计划执行时间 -->
                        <div class="form-group">
                            <label for="scheduled_at">计划执行时间</label>
                            <input type="datetime-local" class="form-control @error('scheduled_at') is-invalid @enderror" 
                                   id="scheduled_at" name="scheduled_at" 
                                   value="{{ old('scheduled_at', $task->scheduled_at ? $task->scheduled_at->format('Y-m-d\TH:i') : '') }}">
                            <small class="form-text text-muted">留空表示立即执行</small>
                            @error('scheduled_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 保存任务
                        </button>
                        <a href="{{ route('system-change.tasks.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> 取消
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.taskServerGroupId = {{ $task->server_group_id ?? 0 }};
    window.taskServerIds = {{ json_encode($task->server_ids ?? []) }};
    window.configVariables = {{ json_encode(old('config_variables', $task->config_variables ?? [])) }};
    window.getServersUrl = '{{ route("system-change.server-groups.get-servers") }}';
    window.getVariablesUrl = '{{ route("system-change.templates.get-variables") }}';
    window.csrfToken = '{{ csrf_token() }}';
</script>
<script src="{{ asset('assets/js/modules/system-change-tasks-edit.js') }}"></script>
@endpush
