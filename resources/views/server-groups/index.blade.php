@extends('layouts.app')

@section('title', '服务器分组 - 服务器管理与数据采集系统')

@section('content')
<div class="container-fluid">
    <!-- 页面标题和操作按钮 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0">
                <i class="fas fa-layer-group text-primary"></i> 服务器分组管理
            </h1>
            <small class="text-muted">管理和组织服务器分组，便于批量操作和配置变更</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('server-groups.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> 新建分组
            </a>
            <button type="button" class="btn btn-danger btn-sm" id="batch-delete-btn" disabled>
                <i class="fas fa-trash"></i> 批量删除
            </button>
        </div>
    </div>
    
    <!-- 搜索和筛选卡片 -->
    <div class="search-filter-card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-filter"></i> 搜索和筛选
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('server-groups.index') }}" method="GET">
                <div class="search-row">
                    <div>
                        <label for="name">分组名称</label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ request('name') }}" placeholder="输入分组名称搜索">
                    </div>
                </div>
                <div class="button-row">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> 搜索
                    </button>
                    <a href="{{ route('server-groups.index') }}" class="btn btn-secondary">
                        <i class="fas fa-sync"></i> 重置
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 分组列表卡片 -->
    <div class="card card-light-blue shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list"></i> 分组列表
            </h5>
        </div>
        <div class="card-body p-0">
            <form id="batch-form" action="{{ route('server-groups.batch-delete') }}" method="POST">
                @csrf
                <div class="table-responsive">
                    <table class="table table-striped table-light table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="select-all">
                                    </div>
                                </th>
                                <th>ID</th>
                                <th>分组名称</th>
                                <th>描述</th>
                                <th>服务器数量</th>
                                <th>创建时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($groups as $group)
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input group-checkbox" type="checkbox" name="group_ids[]" value="{{ $group->id }}">
                                        </div>
                                    </td>
                                    <td>{{ $group->id }}</td>
                                    <td><strong>{{ $group->name }}</strong></td>
                                    <td>{{ $group->description ?: '-' }}</td>
                                    <td><span class="badge badge-info">{{ $group->servers_count }}</span></td>
                                    <td>{{ $group->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('server-groups.show', $group) }}" class="btn btn-primary" title="查看">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('server-groups.edit', $group) }}" class="btn btn-primary" title="编辑">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-primary" 
                                                    onclick="createChangeTask({{ $group->id }}, {{ json_encode($group->name) }})"
                                                    title="创建配置变更任务">
                                                <i class="fas fa-cogs"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger" 
                                                    onclick="deleteGroup({{ $group->id }})"
                                                    title="删除">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-inbox text-muted" style="font-size: 2rem;"></i>
                                        <p class="text-muted mt-2">暂无服务器分组</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-center mt-3 pb-3">
                    {{ $groups->links() }}
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // 设置全局变量供 server-groups.js 使用
    window.systemChangeTasksCreateRoute = '{{ route("system-change.tasks.create") }}';
</script>
<script src="{{ asset('assets/js/modules/server-groups.js') }}"></script>
<script>
    // 删除分组函数
    function deleteGroup(groupId) {
        if (confirm('确定要删除该分组吗？')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("server-groups.destroy", "") }}/' + groupId;
            form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE">';
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>
@endpush
