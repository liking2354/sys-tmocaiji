@extends('layouts.app')

@section('title', $collector->name . ' - 采集组件详情')

@section('content')
<div class="container-fluid">
    <!-- 页面标题 -->
    <div class="mb-4">
        <h1 class="mb-1">
            <i class="fas fa-cube text-info"></i> {{ $collector->name }}
        </h1>
        <p class="text-muted">查看和管理采集组件详情</p>
    </div>
    
    <!-- 操作按钮 -->
    <div class="mb-4">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('collectors.edit', $collector) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> 编辑组件
            </a>
            <a href="{{ route('collectors.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 返回组件列表
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card card-info shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle"></i> 基本信息
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 30%">ID:</th>
                            <td>{{ $collector->id }}</td>
                        </tr>
                        <tr>
                            <th>组件名称:</th>
                            <td>{{ $collector->name }}</td>
                        </tr>
                        <tr>
                            <th>组件代码:</th>
                            <td><code>{{ $collector->code }}</code></td>
                        </tr>
                        <tr>
                            <th>类型:</th>
                            <td>
                                <span class="badge badge-{{ $collector->type === 'script' ? 'info' : 'warning' }}">
                                    {{ $collector->typeName }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>版本:</th>
                            <td>
                                <span class="badge badge-info">{{ $collector->version ?: '1.0.0' }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>描述:</th>
                            <td>{{ $collector->description ?: '无' }}</td>
                        </tr>
                        <tr>
                            <th>状态:</th>
                            <td>
                                @if ($collector->status == 1)
                                    <span class="badge badge-success">启用</span>
                                @else
                                    <span class="badge badge-danger">禁用</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>创建时间:</th>
                            <td>{{ $collector->created_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                        <tr>
                            <th>更新时间:</th>
                            <td>{{ $collector->updated_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="card card-warning shadow-sm mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-cogs"></i> 部署信息
                    </h5>
                </div>
                <div class="card-body">
                    @if ($collector->deployment_config)
                        <table class="table table-borderless">
                            <tr>
                                <th style="width: 40%">远程路径:</th>
                                <td><code>{{ $collector->deployment_config['remote_path'] ?? '/opt/collectors/'.$collector->code }}</code></td>
                            </tr>
                            <tr>
                                <th>自动更新:</th>
                                <td>
                                    @if (isset($collector->deployment_config['auto_update']) && $collector->deployment_config['auto_update'])
                                        <span class="badge badge-success">启用</span>
                                    @else
                                        <span class="badge badge-secondary">禁用</span>
                                    @endif
                                </td>
                            </tr>
                            @if (isset($collector->deployment_config['created_at']))
                                <tr>
                                    <th>配置创建时间:</th>
                                    <td>{{ date('Y-m-d H:i:s', $collector->deployment_config['created_at']) }}</td>
                                </tr>
                            @endif
                            @if (isset($collector->deployment_config['updated_at']))
                                <tr>
                                    <th>配置更新时间:</th>
                                    <td>{{ date('Y-m-d H:i:s', $collector->deployment_config['updated_at']) }}</td>
                                </tr>
                            @endif
                        </table>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 暂无部署配置信息
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="card card-success shadow-sm">
                <div class="card-header bg-success text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-server"></i> 关联的服务器
                        </h5>
                        <span class="badge badge-light">{{ $installedServers->count() }}</span>
                    </div>
                </div>
                <div class="card-body">
                    @if ($installedServers->count() > 0)
                        <div class="list-group">
                            @foreach ($installedServers as $server)
                                <a href="{{ route('servers.show', $server) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $server->name }}</strong>
                                        <small class="d-block text-muted">{{ $server->ip }}</small>
                                        @if ($server->pivot->installed_at)
                                            <small class="text-muted">安装时间: {{ 
                                                \Carbon\Carbon::parse($server->pivot->installed_at)->format('Y-m-d H:i:s') 
                                            }}</small>
                                        @endif
                                    </div>
                                    <div>
                                        @if ($server->status == 1)
                                            <span class="badge badge-success">在线</span>
                                        @else
                                            <span class="badge badge-danger">离线</span>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 该组件未关联任何服务器
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card card-primary shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-code"></i> {{ $collector->isScriptType() ? '采集脚本内容' : '程序文件信息' }}
                    </h5>
                </div>
                <div class="card-body">
                    @if ($collector->isScriptType())
                        <pre class="bg-light p-3 rounded"><code>{{ $collector->getScriptContent() }}</code></pre>
                    @else
                        <div class="alert alert-info">
                            <h5><i class="fas fa-file-archive"></i> 程序文件</h5>
                            @if ($collector->file_path)
                                <p class="mb-1"><strong>文件路径:</strong> <code>{{ $collector->file_path }}</code></p>
                                <p class="mb-1"><strong>文件名:</strong> <code>{{ basename($collector->file_path) }}</code></p>
                                <p class="mb-0"><strong>上传时间:</strong> {{ $collector->updated_at->format('Y-m-d H:i:s') }}</p>
                            @else
                                <p class="mb-0">未上传程序文件</p>
                            @endif
                        </div>
                        
                        <div class="alert alert-warning mt-3">
                            <h5><i class="fas fa-info-circle"></i> 程序类组件说明</h5>
                            <p class="mb-0">程序类组件需要上传到服务器并安装后才能使用。安装过程会自动处理程序文件的解压和权限设置。</p>
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="card card-info shadow-sm mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-book"></i> 组件使用说明
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> 使用指南</h5>
                        <p>此采集组件可用于收集服务器的系统信息和性能数据。</p>
                        <ul>
                            <li>对于脚本类组件，可直接在服务器上执行</li>
                            <li>对于程序类组件，需要先安装到服务器</li>
                        </ul>
                    </div>
                    
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6>输出数据格式示例</h6>
                            <pre><code>{
  "system": {
    "hostname": "server-name",
    "os": "Linux 5.4.0",
    "uptime": "10 days, 4 hours"
  },
  "resources": {
    "cpu": "23%",
    "memory": "45%",
    "disk": "32%"
  }
}</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection



@push('scripts')
<script src="{{ asset('assets/js/modules/collectors-show.js') }}"></script>
@endpush
