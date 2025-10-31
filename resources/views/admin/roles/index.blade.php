@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- 页面标题 -->
    <div class="mb-4">
        <h1 class="mb-1">
            <i class="fas fa-shield-alt text-primary"></i> 角色管理
        </h1>
        <p class="text-muted">管理系统角色和权限</p>
    </div>
    
    <!-- 操作按钮 -->
    <div class="mb-4">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> 添加角色
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card card-light-blue shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list"></i> 角色列表
                    </h5>
                </div>

                <div class="card-body p-0">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped table-light table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>名称</th>
                                    <th>标识</th>
                                    <th>描述</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($roles as $role)
                                    <tr>
                                        <td><span class="badge badge-light">{{ $role->id }}</span></td>
                                        <td><strong>{{ $role->name }}</strong></td>
                                        <td><code>{{ $role->slug }}</code></td>
                                        <td>{{ $role->description ?: '-' }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('admin.roles.edit', $role->id) }}" class="btn btn-primary" title="编辑">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-danger" onclick="deleteRole({{ $role->id }})" title="删除">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-3 pb-3">
                        {{ $roles->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/common/delete-handler.js') }}"></script>
@endpush