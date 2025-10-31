@extends('layouts.app')

@section('title', '服务器分组 - 服务器管理与数据采集系统')

@section('content')
<div class="container-fluid">
    <!-- 页面标题 -->
    <div class="mb-4">
        <h1 class="mb-1">
            <i class="fas fa-layer-group text-primary"></i> 服务器分组管理
        </h1>
        <p class="text-muted">管理和组织服务器分组，便于批量操作和配置变更</p>
    </div>
    
    <!-- 操作按钮 -->
    <div class="mb-4">
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-danger" id="batch-delete-btn" disabled>
                <i class="fas fa-trash"></i> 批量删除
            </button>
            <a href="{{ route('server-groups.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> 新建分组
            </a>
        </div>
    </div>
    
    <!-- 搜索和筛选卡片 -->
    <div class="card card-warning mb-4 shadow-sm">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">
                <i class="fas fa-filter"></i> 搜索和筛选
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('server-groups.index') }}" method="GET" class="row align-items-end">
                <div class="col-md-4 mb-2">
                    <label for="name" class="font-weight-bold">分组名称</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ request('name') }}" placeholder="输入分组名称搜索">
                </div>
                <div class="col-md-4 mb-2">
                    <button type="submit" class="btn btn-warning">
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
    <div class="card card-primary shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-list"></i> 分组列表
            </h5>
        </div>
        <div class="card-body">
            <form id="batch-form" action="{{ route('server-groups.batch-delete') }}" method="POST">
                @csrf
                <div class="table-responsive">
                    <table class="table table-striped table-light table-hover">
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
                                            <a href="{{ route('server-groups.show', $group) }}" class="btn btn-info" title="查看">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('server-groups.edit', $group) }}" class="btn btn-warning" title="编辑">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-success" 
                                                    onclick="createChangeTask({{ $group->id }}, {{ json_encode($group->name) }})"
                                                    title="创建配置变更任务">
                                                <i class="fas fa-cogs"></i>
                                            </button>
                                            <form action="{{ route('server-groups.destroy', $group) }}" method="POST" class="d-inline" onsubmit="return confirm('确定要删除该分组吗？')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger" title="删除">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
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
                
                <div class="d-flex justify-content-center mt-3">
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
    $(document).ready(function() {
        // 初始化时检查按钮状态
        updateBatchDeleteButton();
        
        // 全选/取消全选
        $(document).on('change', '#select-all', function() {
            var isChecked = $(this).prop('checked');
            $('.group-checkbox').prop('checked', isChecked);
            updateBatchDeleteButton();
        });
        
        // 单个复选框变化时更新全选框状态
        $(document).on('change', '.group-checkbox', function() {
            updateBatchDeleteButton();
            
            // 如果所有复选框都选中，则全选框也选中
            var totalCheckboxes = $('.group-checkbox').length;
            var checkedCheckboxes = $('.group-checkbox:checked').length;
            
            $('#select-all').prop('checked', totalCheckboxes > 0 && checkedCheckboxes === totalCheckboxes);
        });
        
        // 更新批量删除按钮状态
        function updateBatchDeleteButton() {
            var checkedCount = $('.group-checkbox:checked').length;
            console.log('选中的复选框数量:', checkedCount);
            
            if (checkedCount > 0) {
                $('#batch-delete-btn').prop('disabled', false);
            } else {
                $('#batch-delete-btn').prop('disabled', true);
            }
        }
        
        // 批量删除按钮点击事件
        $(document).on('click', '#batch-delete-btn', function(e) {
            e.preventDefault();
            
            if ($('.group-checkbox:checked').length === 0) {
                alert('请至少选择一个分组');
                return false;
            }
            
            // 显示确认对话框
            if (confirm('确定要删除选中的分组吗？此操作不可恢复！')) {
                // 直接提交表单
                document.getElementById('batch-form').submit();
            }
        });
    });
    
    // 创建变更任务函数
    function createChangeTask(groupId, groupName) {
        // 跳转到创建变更任务页面，并预选该服务器分组
        const url = new URL('{{ route("system-change.tasks.create") }}', window.location.origin);
        url.searchParams.set('server_group_id', groupId);
        url.searchParams.set('server_group_name', groupName);
        window.location.href = url.toString();
    }
</script>
@endpush
