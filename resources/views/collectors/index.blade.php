@extends('layouts.app')

@section('title', '采集组件 - 服务器管理与数据采集系统')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0">
                <i class="fas fa-cube text-primary"></i> 采集组件管理
            </h1>
            <small class="text-muted">管理和配置数据采集组件</small>
        </div>
        <a href="{{ route('collectors.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> 新建采集组件
        </a>
    </div>
    
    <!-- 搜索和筛选卡片 -->
    <div class="search-filter-card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-filter"></i> 搜索和筛选
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('collectors.index') }}">
                <div class="search-row">
                    <div>
                        <label for="name">组件名称</label>
                        <input type="text" name="name" class="form-control" id="name" placeholder="搜索组件名称..." 
                               value="{{ request('name') }}">
                    </div>
                    <div>
                        <label for="code">组件代码</label>
                        <input type="text" name="code" class="form-control" id="code" placeholder="搜索组件代码..." 
                               value="{{ request('code') }}">
                    </div>
                </div>
                <div class="button-row">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> 搜索
                    </button>
                    <a href="{{ route('collectors.index') }}" class="btn btn-secondary">
                        <i class="fas fa-sync"></i> 重置
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-light-blue shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list"></i> 采集组件列表
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>组件名称</th>
                            <th>组件代码</th>
                            <th style="width: 80px;">类型</th>
                            <th>描述</th>
                            <th style="width: 80px;">状态</th>
                            <th>创建时间</th>
                            <th style="width: 120px;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($collectors as $collector)
                            <tr>
                                <td><span class="badge badge-light">{{ $collector->id }}</span></td>
                                <td><strong>{{ $collector->name }}</strong></td>
                                <td><code>{{ $collector->code }}</code></td>
                                <td>
                                    <span class="badge badge-{{ $collector->type === 'script' ? 'primary' : 'secondary' }}">
                                        {{ $collector->typeName }}
                                    </span>
                                </td>
                                <td><small class="text-muted">{{ Str::limit($collector->description, 50) }}</small></td>
                                <td>
                                    @if ($collector->status == 1)
                                        <span class="badge badge-success"><i class="fas fa-check-circle"></i> 启用</span>
                                    @else
                                        <span class="badge badge-danger"><i class="fas fa-times-circle"></i> 禁用</span>
                                    @endif
                                </td>
                                <td><small class="text-muted">{{ $collector->created_at->format('Y-m-d H:i') }}</small></td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('collectors.show', $collector) }}" class="btn btn-primary" title="查看详情">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('collectors.edit', $collector) }}" class="btn btn-primary" title="编辑">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger" onclick="deleteCollector({{ $collector->id }})" title="删除">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-inbox text-muted" style="font-size: 2rem;"></i>
                                    <p class="text-muted mt-2">暂无采集组件</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-center mt-3 pb-3">
                {{ $collectors->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/common/delete-handler.js') }}"></script>
@endpush