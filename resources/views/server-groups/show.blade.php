@extends('layouts.app')

@section('title', $serverGroup->name . ' - 服务器分组详情')

@section('content')
<div class="container-fluid">
    <!-- 页面标题 -->
    <div class="mb-4">
        <h1 class="mb-1">
            <i class="fas fa-layer-group text-primary"></i> {{ $serverGroup->name }}
        </h1>
        <p class="text-muted">查看和管理服务器分组详情</p>
    </div>
    
    <!-- 操作按钮 -->
    <div class="mb-4">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('server-groups.edit', $serverGroup) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> 编辑分组
            </a>
            <a href="{{ route('server-groups.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 返回分组列表
            </a>
        </div>
    </div>
    
    <div class="row">
        <!-- 基本信息卡片 -->
        <div class="col-md-4">
            <div class="card card-primary shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle"></i> 基本信息
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <th style="width: 40%">ID:</th>
                            <td><strong>{{ $serverGroup->id }}</strong></td>
                        </tr>
                        <tr>
                            <th>分组名称:</th>
                            <td><strong>{{ $serverGroup->name }}</strong></td>
                        </tr>
                        <tr>
                            <th>描述:</th>
                            <td>{{ $serverGroup->description ?: '-' }}</td>
                        </tr>
                        <tr>
                            <th>服务器数量:</th>
                            <td><span class="badge badge-info">{{ $serverGroup->servers->count() }}</span></td>
                        </tr>
                        <tr>
                            <th>创建时间:</th>
                            <td><small>{{ $serverGroup->created_at->format('Y-m-d H:i:s') }}</small></td>
                        </tr>
                        <tr>
                            <th>更新时间:</th>
                            <td><small>{{ $serverGroup->updated_at->format('Y-m-d H:i:s') }}</small></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- 分组内的服务器卡片 -->
        <div class="col-md-8">
            <div class="card card-primary shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-server"></i> 分组内的服务器
                    </h5>
                    <a href="{{ route('servers.create', ['group_id' => $serverGroup->id]) }}" class="btn btn-sm btn-light">
                        <i class="fas fa-plus"></i> 添加服务器
                    </a>
                </div>
                <div class="card-body">
                    @if ($serverGroup->servers->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-light table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>名称</th>
                                        <th>IP地址</th>
                                        <th>状态</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($serverGroup->servers as $server)
                                        <tr>
                                            <td>{{ $server->id }}</td>
                                            <td><strong>{{ $server->name }}</strong></td>
                                            <td>{{ $server->ip }}</td>
                                            <td>
                                                @if ($server->status == 1)
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-check-circle"></i> 在线
                                                    </span>
                                                @else
                                                    <span class="badge badge-danger">
                                                        <i class="fas fa-times-circle"></i> 离线
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="{{ route('servers.show', $server) }}" class="btn btn-info" title="查看">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('servers.edit', $server) }}" class="btn btn-warning" title="编辑">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle"></i> 该分组下暂无服务器
                        </div>
                        <a href="{{ route('servers.create', ['group_id' => $serverGroup->id]) }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> 添加服务器到此分组
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection