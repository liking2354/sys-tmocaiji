@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- 页面标题 -->
    <div class="mb-4">
        <h1 class="mb-1">
            <i class="fas fa-lock text-primary"></i> 添加权限
        </h1>
        <p class="text-muted">创建新的系统权限</p>
    </div>
    
    <!-- 操作按钮 -->
    <div class="mb-4">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.permissions.index') }}" class="btn btn-secondary">返回列表</a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card card-primary shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-edit"></i> 权限表单
                    </h5>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('admin.permissions.store') }}">
                        @csrf

                        <div class="form-group row mb-3">
                            <label for="name" class="col-md-2 col-form-label text-md-right">权限名称</label>
                            <div class="col-md-9">
                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autofocus>
                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="slug" class="col-md-2 col-form-label text-md-right">权限标识</label>
                            <div class="col-md-9">
                                <input id="slug" type="text" class="form-control @error('slug') is-invalid @enderror" name="slug" value="{{ old('slug') }}" required>
                                @error('slug')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="module" class="col-md-2 col-form-label text-md-right">所属模块</label>
                            <div class="col-md-9">
                                <input id="module" type="text" class="form-control @error('module') is-invalid @enderror" name="module" value="{{ old('module') }}" required>
                                @error('module')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="description" class="col-md-2 col-form-label text-md-right">权限描述</label>
                            <div class="col-md-9">
                                <textarea id="description" class="form-control @error('description') is-invalid @enderror" name="description">{{ old('description') }}</textarea>
                                @error('description')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <div class="col-md-9 offset-md-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> 添加
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection