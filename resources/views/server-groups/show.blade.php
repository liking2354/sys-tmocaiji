@extends('layouts.app')

@section('title', $serverGroup->name . ' - 服务器分组详情')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>服务器分组详情</h1>
        <div>
            <a href="{{ route('server-groups.edit', $serverGroup) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> 编辑分组
            </a>
            <a href="{{ route('server-groups.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 返回分组列表
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">基本信息</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 30%">ID:</th>
                            <td>{{ $serverGroup->id }}</td>
                        </tr>
                        <tr>
                            <th>分组名称:</th>
                            <td>{{ $serverGroup->name }}</td>
                        </tr>
                        <tr>
                            <th>描述:</th>
                            <td>{{ $serverGroup->description ?: '无' }}</td>
                        </tr>
                        <tr>
                            <th>服务器数量:</th>
                            <td>{{ $serverGroup->servers->count() }}</td>
                        </tr>
                        <tr>
                            <th>创建时间:</th>
                            <td>{{ $serverGroup->created_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                        <tr>
                            <th>更新时间:</th>
                            <td>{{ $serverGroup->updated_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">分组内的服务器</h5>
                    <a href="{{ route('servers.create', ['group_id' => $serverGroup->id]) }}" class="btn btn-sm btn-light">
                        <i class="fas fa-plus"></i> 添加服务器
                    </a>
                </div>
                <div class="card-body">
                    @if ($serverGroup->servers->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
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
                                            <td>{{ $server->name }}</td>
                                            <td>{{ $server->ip }}</td>
                                            <td>
                                                @if ($server->status == 1)
                                                    <span class="badge badge-success">在线</span>
                                                @else
                                                    <span class="badge badge-danger">离线</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('servers.show', $server) }}" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i> 查看
                                                    </a>
                                                    <a href="{{ route('servers.edit', $server) }}" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i> 编辑
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
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