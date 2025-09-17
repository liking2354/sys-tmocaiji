@extends('layouts.app')

@section('title', '编辑采集组件 - 服务器管理与数据采集系统')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>编辑采集组件</h1>
        <a href="{{ route('collectors.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> 返回组件列表
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form action="{{ route('collectors.update', $collector) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">组件名称 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $collector->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="code">组件代码 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $collector->code) }}" required>
                            <small class="form-text text-muted">唯一标识符，只能包含字母、数字和下划线</small>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="version">版本号</label>
                            <input type="text" class="form-control @error('version') is-invalid @enderror" id="version" name="version" value="{{ old('version', $collector->version ?: '1.0.0') }}" placeholder="例如：1.0.0">
                            <small class="form-text text-muted">采用语义化版本号，格式为：主版本.次版本.修订版本</small>
                            @error('version')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>组件类型 <span class="text-danger">*</span></label>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="type_script" name="type" value="script" class="custom-control-input" {{ old('type', $collector->type) == 'script' ? 'checked' : '' }} required>
                                <label class="custom-control-label" for="type_script">脚本类</label>
                                <small class="form-text text-muted">直接执行脚本，无需安装</small>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="type_program" name="type" value="program" class="custom-control-input" {{ old('type', $collector->type) == 'program' ? 'checked' : '' }} required>
                                <label class="custom-control-label" for="type_program">程序类</label>
                                <small class="form-text text-muted">需要上传程序文件并安装到服务器</small>
                            </div>
                            @error('type')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <!-- 脚本类组件表单项 -->
                <div id="script_type_fields">
                    <div class="form-group">
                        <label for="script_file">上传脚本文件</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input @error('script_file') is-invalid @enderror" id="script_file" name="script_file">
                            <label class="custom-file-label" for="script_file">选择文件</label>
                        </div>
                        <small class="form-text text-muted">支持的文件类型：.sh, .txt, .py, .pl, .rb, .js, .php</small>
                        @error('script_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <!-- 程序类组件表单项 -->
                <div id="program_type_fields" style="display: none;">
                    <div class="form-group">
                        <label for="program_file">上传程序文件</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input @error('program_file') is-invalid @enderror" id="program_file" name="program_file">
                            <label class="custom-file-label" for="program_file">选择文件</label>
                        </div>
                        <small class="form-text text-muted">支持的文件类型：压缩包、可执行文件等</small>
                        @if($collector->file_path)
                            <div class="mt-2 alert alert-info">
                                <i class="fas fa-info-circle"></i> 当前已上传文件：{{ basename($collector->file_path) }}
                            </div>
                        @endif
                        @error('program_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">组件描述</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="2">{{ old('description', $collector->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group" id="script_content_field">
                    <label for="script_content">采集脚本内容 <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('script_content') is-invalid @enderror" id="script_content" name="script_content" rows="15">{{ old('script_content', $collector->getScriptContent()) }}</textarea>
                    <small class="form-text text-muted">采集脚本必须输出JSON格式的数据</small>
                    @error('script_content')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label>状态</label>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="status_1" name="status" value="1" class="custom-control-input" {{ old('status', $collector->status) == 1 ? 'checked' : '' }}>
                        <label class="custom-control-label" for="status_1">启用</label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="status_0" name="status" value="0" class="custom-control-input" {{ old('status', $collector->status) == 0 ? 'checked' : '' }}>
                        <label class="custom-control-label" for="status_0">禁用</label>
                    </div>
                    @error('status')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="update_servers" name="update_servers" value="1" {{ old('update_servers') ? 'checked' : '' }}>
                    <label class="form-check-label" for="update_servers">更新已安装此组件的服务器</label>
                    <small class="form-text text-muted">选中此项将自动更新所有已安装此组件的服务器</small>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 保存
                    </button>
                    <a href="{{ route('collectors.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> 取消
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // 文件上传显示文件名
        $('.custom-file-input').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').html(fileName);
        });
        
        // 根据组件类型显示/隐藏相应表单项
        function toggleTypeFields() {
            var type = $('input[name="type"]:checked').val();
            if (type === 'script') {
                $('#script_type_fields').show();
                $('#program_type_fields').hide();
                $('#script_content_field').show();
                $('#script_content').prop('required', true);
                $('#program_file').prop('required', false);
            } else {
                $('#script_type_fields').hide();
                $('#program_type_fields').show();
                $('#script_content_field').hide();
                $('#script_content').prop('required', false);
                $('#program_file').prop('required', false); // 编辑时不要求必填
            }
        }
        
        // 初始化表单状态
        toggleTypeFields();
        
        // 监听类型选择变化
        $('input[name="type"]').change(function() {
            toggleTypeFields();
        });
    });
</script>
@endpush
@endsection