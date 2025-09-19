@extends('layouts.app')

@section('title', '服务器分组 - 服务器管理与数据采集系统')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>服务器分组管理</h1>
        <div>
            <button type="button" class="btn btn-danger me-2" id="batch-delete-btn" disabled data-bs-toggle="modal" data-bs-target="#deleteModal">
                <i class="fas fa-trash"></i> 批量删除
            </button>
            <a href="{{ route('server-groups.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> 新建分组
            </a>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('server-groups.index') }}" method="GET" class="row align-items-end">
                <div class="col-md-4 mb-2">
                    <label for="name">分组名称</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ request('name') }}" placeholder="输入分组名称搜索">
                </div>
                <div class="col-md-4 mb-2">
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
    
    <div class="card">
        <div class="card-body">
            <form id="batch-form" action="{{ route('server-groups.batch-delete') }}" method="POST">
                @csrf
                @method('DELETE')
            </form>   
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>
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
                                <td>{{ $group->name }}</td>
                                <td>{{ $group->description }}</td>
                                <td>{{ $group->servers_count }}</td>
                                <td>{{ $group->created_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('server-groups.show', $group) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> 查看
                                        </a>
                                        <a href="{{ route('server-groups.edit', $group) }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i> 编辑
                                        </a>
                                        <form action="{{ route('server-groups.destroy', $group) }}" method="POST" class="d-inline" onsubmit="return confirm('确定要删除该分组吗？')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i> 删除
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-3">暂无服务器分组</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-center mt-3">
                {{ $groups->links() }}
            </div>
        </div>
    </div>
</div>

<!-- 删除确认模态框 -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">确认删除</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                确定要删除选中的分组吗？此操作不可恢复！
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-danger" id="confirm-delete">确认删除</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
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
            
            // 使用Bootstrap 4的模态框显示方法
            $('#deleteModal').modal('show');
        });
        
        // 确认删除按钮点击事件
        $(document).on('click', '#confirm-delete', function() {
            $('#batch-form').submit();
        });
    });
</script>
@endsection