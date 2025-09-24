@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>添加权限</span>
                        <a href="{{ route('admin.permissions.index') }}" class="btn btn-secondary btn-sm">返回列表</a>
                    </div>
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
                                    添加
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