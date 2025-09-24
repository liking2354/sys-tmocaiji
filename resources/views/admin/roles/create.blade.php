@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>添加角色</span>
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary btn-sm">返回列表</a>
                    </div>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('admin.roles.store') }}">
                        @csrf

                        <div class="form-group row mb-3">
                            <label for="name" class="col-md-2 col-form-label text-md-right">角色名称</label>
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
                            <label for="description" class="col-md-2 col-form-label text-md-right">角色描述</label>
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
                            <label class="col-md-2 col-form-label text-md-right">权限</label>
                            <div class="col-md-9">
                                <div class="row">
                                    @foreach ($permissions as $module => $modulePermissions)
                                        <div class="col-md-12 mb-3">
                                            <div class="card">
                                                <div class="card-header">{{ $module }}</div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        @foreach ($modulePermissions as $permission)
                                                            <div class="col-md-4 mb-2">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->id }}" id="permission-{{ $permission->id }}">
                                                                    <label class="form-check-label" for="permission-{{ $permission->id }}">
                                                                        {{ $permission->name }}
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @error('permissions')
                                    <span class="invalid-feedback d-block" role="alert">
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