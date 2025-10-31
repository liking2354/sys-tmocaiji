@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- 页面标题 -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1">
                <i class="fas fa-users text-primary mr-2"></i>用户管理
            </h2>
            <p class="text-muted">管理系统用户账户和权限</p>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <!-- 成功提示 -->
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <!-- 用户列表卡片 -->
            <div class="card card-light-blue shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list mr-2"></i>用户列表
                    </h5>
                    <a href="{{ route('admin.users.create') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-plus mr-1"></i>添加用户
                    </a>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%;">ID</th>
                                    <th style="width: 15%;">用户名</th>
                                    <th style="width: 20%;">邮箱</th>
                                    <th style="width: 10%;">状态</th>
                                    <th style="width: 15%;">角色</th>
                                    <th style="width: 20%;">最后登录时间</th>
                                    <th style="width: 15%;">操作</th>
                                </tr>
                            </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <td>{{ $user->id }}</td>
                                    <td>
                                        <strong>{{ $user->username }}</strong>
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @if($user->status)
                                            <span class="badge badge-success">
                                                <i class="fas fa-check-circle mr-1"></i>启用
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">
                                                <i class="fas fa-times-circle mr-1"></i>禁用
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @foreach ($user->roles as $role)
                                            <span class="badge badge-primary">{{ $role->name }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        @if($user->last_login_time)
                                            <small>{{ $user->last_login_time->format('Y-m-d H:i') }}</small>
                                        @else
                                            <small class="text-muted">未登录</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-primary" title="编辑">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger" onclick="deleteUser({{ $user->id }})" title="删除">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>

                    <!-- 分页 -->
                    <div class="card-footer bg-light d-flex justify-content-center">
                        {{ $users->links() }}
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