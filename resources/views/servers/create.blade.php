@extends('layouts.app')

@section('title', '添加服务器 - 服务器管理与数据采集系统')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>添加服务器</h1>
        <a href="{{ route('servers.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> 返回服务器列表
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form action="{{ route('servers.store') }}" method="POST">
                @csrf
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">服务器名称 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="group_id">所属分组 <span class="text-danger">*</span></label>
                            <select class="form-control @error('group_id') is-invalid @enderror" id="group_id" name="group_id" required>
                                <option value="">请选择分组</option>
                                @foreach ($groups as $group)
                                    <option value="{{ $group->id }}" {{ old('group_id', request('group_id')) == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                                @endforeach
                            </select>
                            @error('group_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="ip">IP地址 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('ip') is-invalid @enderror" id="ip" name="ip" value="{{ old('ip') }}" required>
                            @error('ip')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="port">SSH端口 <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('port') is-invalid @enderror" id="port" name="port" value="{{ old('port', 22) }}" required>
                            @error('port')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="username">用户名 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username') }}" required>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="password">密码 <span class="text-danger">*</span></label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="verify_connection" name="verify_connection" value="1" {{ old('verify_connection', '1') ? 'checked' : '' }}>
                        <label class="custom-control-label" for="verify_connection">验证SSH连接</label>
                    </div>
                    @error('connection')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label>选择采集组件</label>
                    <div class="row">
                        @foreach ($collectors as $collector)
                            <div class="col-md-4">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="collector_{{ $collector->id }}" name="collectors[]" value="{{ $collector->id }}" {{ in_array($collector->id, old('collectors', [])) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="collector_{{ $collector->id }}">{{ $collector->name }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 保存
                    </button>
                    <button type="button" id="testConnectionBtn" class="btn btn-primary">
                        <i class="fas fa-plug"></i> 测试连接
                    </button>
                    <a href="{{ route('servers.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> 取消
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.serverVerifyUrl = '{{ route("servers.verify") }}';
    window.csrfToken = '{{ csrf_token() }}';
</script>
<script src="{{ asset('assets/js/modules/servers-create.js') }}"></script>
@endpush
