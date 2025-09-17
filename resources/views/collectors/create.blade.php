@extends('layouts.app')

@section('title', '创建采集组件 - 服务器管理与数据采集系统')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>创建采集组件</h1>
        <a href="{{ route('collectors.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> 返回组件列表
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form action="{{ route('collectors.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">组件名称 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="code">组件代码 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" required placeholder="例如：system_process">
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
                            <input type="text" class="form-control @error('version') is-invalid @enderror" id="version" name="version" value="{{ old('version', '1.0.0') }}" placeholder="例如：1.0.0">
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
                                <input type="radio" id="type_script" name="type" value="script" class="custom-control-input" {{ old('type', 'script') == 'script' ? 'checked' : '' }} required>
                                <label class="custom-control-label" for="type_script">脚本类</label>
                                <small class="form-text text-muted">直接执行脚本，无需安装</small>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="type_program" name="type" value="program" class="custom-control-input" {{ old('type') == 'program' ? 'checked' : '' }} required>
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
                        <label for="program_file">上传程序文件 <span class="text-danger">*</span></label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input @error('program_file') is-invalid @enderror" id="program_file" name="program_file">
                            <label class="custom-file-label" for="program_file">选择文件</label>
                        </div>
                        <small class="form-text text-muted">支持的文件类型：压缩包、可执行文件等</small>
                        @error('program_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">组件描述</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="2">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group" id="script_content_field">
                    <label for="script_content">采集脚本内容 <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('script_content') is-invalid @enderror" id="script_content" name="script_content" rows="15">{{ old('script_content', "#!/bin/bash\n\n# 采集脚本模板\n# 输出必须是JSON格式\n\n# 错误处理\nset -e\n\n# 采集逻辑\n# ...\n# 输出结果\necho '{\n  \"status\": \"success\",\n  \"data\": {\n    \"example\": \"value\"\n  }\n}'\n") }}</textarea>
                    <small class="form-text text-muted">采集脚本必须输出JSON格式的数据</small>
                    @error('script_content')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label>状态</label>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="status_1" name="status" value="1" class="custom-control-input" {{ old('status', '1') == '1' ? 'checked' : '' }}>
                        <label class="custom-control-label" for="status_1">启用</label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="status_0" name="status" value="0" class="custom-control-input" {{ old('status') == '0' ? 'checked' : '' }}>
                        <label class="custom-control-label" for="status_0">禁用</label>
                    </div>
                    @error('status')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
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
                $('#program_file').prop('required', true);
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