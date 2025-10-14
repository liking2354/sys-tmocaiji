@extends('layouts.app')

@section('title', '可用区管理')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">可用区管理</h3>
                    <div>
                        <button type="button" class="btn btn-danger mr-2" onclick="clearAllRegions()">
                            <i class="fas fa-trash-alt"></i> 清空数据
                        </button>
                        <a href="{{ route('cloud.regions.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> 添加可用区
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <!-- 筛选表单 -->
                    <form method="GET" action="{{ route('cloud.regions.index') }}" class="mb-3">
                        <div class="row">
                            <div class="col-md-3">
                                <select name="platform_id" class="form-control">
                                    <option value="">选择云平台</option>
                                    @foreach($platforms as $platform)
                                        <option value="{{ $platform->id }}" {{ request('platform_id') == $platform->id ? 'selected' : '' }}>
                                            {{ $platform->name }} ({{ $platform->platform_type }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="is_active" class="form-control">
                                    <option value="">选择状态</option>
                                    <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>启用</option>
                                    <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>禁用</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control" placeholder="搜索可用区名称或代码" value="{{ request('search') }}">
                            </div>
                            <div class="col-md-4">
                                <div class="btn-group" role="group">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="fas fa-search"></i> 搜索
                                    </button>
                                    <a href="{{ route('cloud.regions.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-undo"></i> 重置
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- 可用区列表 -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>云平台</th>
                                    <th>可用区代码</th>
                                    <th>可用区名称</th>
                                    <th>状态</th>
                                    <th>创建时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($regions as $region)
                                    <tr>
                                        <td>{{ $region->id }}</td>
                                        <td>
                                            <span class="badge badge-info">{{ $region->platform->name }}</span>
                                        </td>
                                        <td><code>{{ $region->region_code }}</code></td>
                                        <td>{{ $region->region_name }}</td>
                                        <td>
                                            @if($region->is_active)
                                                <span class="badge badge-success">启用</span>
                                            @else
                                                <span class="badge badge-secondary">禁用</span>
                                            @endif
                                        </td>
                                        <td>{{ $region->created_at->format('Y-m-d H:i:s') }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('cloud.regions.edit', $region) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i> 编辑
                                                </a>
                                                <form action="{{ route('cloud.regions.destroy', $region) }}" method="POST" class="d-inline" onsubmit="return confirm('确定要删除这个可用区吗？')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i> 删除
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">暂无可用区数据</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- 分页 -->
                    @if($regions->hasPages())
                        <div class="d-flex justify-content-center">
                            {{ $regions->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 清空数据确认模态框 -->
<div class="modal fade" id="clearAllModal" tabindex="-1" role="dialog" aria-labelledby="clearAllModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clearAllModalLabel">
                    <i class="fas fa-exclamation-triangle text-warning"></i> 确认清空数据
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <strong>警告：</strong>此操作将删除所有可用区数据，且不可恢复！
                </div>
                <p>您确定要清空所有可用区数据吗？</p>
                <p class="text-muted">
                    <small>
                        <i class="fas fa-info-circle"></i> 
                        清空后，您需要重新同步云平台可用区数据。
                    </small>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> 取消
                </button>
                <button type="button" class="btn btn-danger" onclick="confirmClearAll()">
                    <i class="fas fa-trash-alt"></i> 确认清空
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function clearAllRegions() {
    console.log('clearAllRegions 函数被调用');
    $('#clearAllModal').modal('show');
}

function confirmClearAll() {
    console.log('confirmClearAll 函数被调用');
    
    // 显示加载状态
    const btn = event.target;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 清空中...';
    btn.disabled = true;
    
    console.log('准备发送AJAX请求到:', '{{ route("cloud.regions.clear-all") }}');
    
    $.ajax({
        url: '{{ route("cloud.regions.clear-all") }}',
        type: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: {
            _token: '{{ csrf_token() }}'
        },
        beforeSend: function() {
            console.log('AJAX请求开始发送');
        },
        success: function(response) {
            console.log('AJAX请求成功，响应:', response);
            
            if (response.success) {
                // 显示成功消息
                if (typeof toastr !== 'undefined') {
                    toastr.success(response.message);
                } else {
                    alert('成功：' + response.message);
                }
                
                // 关闭模态框
                $('#clearAllModal').modal('hide');
                
                // 刷新页面
                setTimeout(function() {
                    window.location.reload();
                }, 1500);
            } else {
                const errorMsg = response.message || '清空失败';
                console.error('清空失败:', errorMsg);
                
                if (typeof toastr !== 'undefined') {
                    toastr.error(errorMsg);
                } else {
                    alert('错误：' + errorMsg);
                }
                
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX请求失败:', {
                status: xhr.status,
                statusText: xhr.statusText,
                responseText: xhr.responseText,
                error: error
            });
            
            let message = '清空失败';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            } else if (xhr.status === 419) {
                message = 'CSRF令牌过期，请刷新页面后重试';
            } else if (xhr.status === 500) {
                message = '服务器内部错误';
            }
            
            if (typeof toastr !== 'undefined') {
                toastr.error(message);
            } else {
                alert('错误：' + message);
            }
            
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    });
}

// 页面加载完成后的初始化
$(document).ready(function() {
    console.log('页面加载完成，jQuery版本:', $.fn.jquery);
    console.log('toastr是否可用:', typeof toastr !== 'undefined');
    console.log('CSRF token:', $('meta[name="csrf-token"]').attr('content'));
});
</script>
@endpush