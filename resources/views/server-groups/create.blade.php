@extends('layouts.app')

@section('title', '创建服务器分组 - 服务器管理与数据采集系统')

@section('content')
<div class="container-fluid">
    <!-- 页面标题 -->
    <div class="mb-4">
        <h1 class="mb-1">
            <i class="fas fa-layer-group text-primary"></i> 创建服务器分组
        </h1>
        <p class="text-muted">创建新的服务器分组，便于批量管理和配置变更</p>
    </div>
    
    <!-- 操作按钮 -->
    <div class="mb-4">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('server-groups.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 返回分组列表
            </a>
        </div>
    </div>
    
    <div class="card card-light-blue shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-edit"></i> 分组信息
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('server-groups.store') }}" method="POST">
                @csrf
                
                <div class="form-group">
                    <label for="name">分组名称 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="description">分组描述</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 保存
                        </button>
                        <a href="{{ route('server-groups.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> 取消
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection