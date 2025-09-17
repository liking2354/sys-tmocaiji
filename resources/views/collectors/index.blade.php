@extends('layouts.app')

@section('title', '采集组件 - 服务器管理与数据采集系统')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>采集组件管理</h1>
        <a href="{{ route('collectors.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> 新建采集组件
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>组件名称</th>
                            <th>组件代码</th>
                            <th>类型</th>
                            <th>描述</th>
                            <th>状态</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($collectors as $collector)
                            <tr>
                                <td>{{ $collector->id }}</td>
                                <td>{{ $collector->name }}</td>
                                <td><code>{{ $collector->code }}</code></td>
                                <td>
                                    <span class="badge badge-{{ $collector->type === 'script' ? 'info' : 'warning' }}">
                                        {{ $collector->typeName }}
                                    </span>
                                </td>
                                <td>{{ Str::limit($collector->description, 50) }}</td>
                                <td>
                                    @if ($collector->status == 1)
                                        <span class="badge badge-success">启用</span>
                                    @else
                                        <span class="badge badge-danger">禁用</span>
                                    @endif
                                </td>
                                <td>{{ $collector->created_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('collectors.show', $collector) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> 查看
                                        </a>
                                        <a href="{{ route('collectors.edit', $collector) }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i> 编辑
                                        </a>
                                        <form action="{{ route('collectors.destroy', $collector) }}" method="POST" class="d-inline" onsubmit="return confirm('确定要删除该采集组件吗？')">
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
                                <td colspan="8" class="text-center py-3">暂无采集组件</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-center mt-3">
                {{ $collectors->links() }}
            </div>
        </div>
    </div>
</div>
@endsection