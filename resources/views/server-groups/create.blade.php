@extends('layouts.app')

@section('title', '创建服务器分组 - 服务器管理与数据采集系统')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>创建服务器分组</h1>
        <a href="{{ route('server-groups.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> 返回分组列表
        </a>
    </div>
    
    <div class="card">
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
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 保存
                    </button>
                    <a href="{{ route('server-groups.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> 取消
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection