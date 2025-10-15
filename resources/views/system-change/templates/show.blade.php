@extends('layouts.app')

@section('title', '配置模板详情')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">配置模板详情</h1>
                <div>
                    <a href="{{ route('system-change.templates.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> 返回列表
                    </a>
                    <a href="{{ route('system-change.templates.edit', $template) }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> 编辑
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- 基本信息 -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">基本信息</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td class="fw-bold" width="120">模板名称:</td>
                            <td>{{ $template->name }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">描述:</td>
                            <td>{{ $template->description ?: '无' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">状态:</td>
                            <td>
                                @if($template->is_active)
                                    <span class="badge bg-success">启用</span>
                                @else
                                    <span class="badge bg-secondary">禁用</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-bold">创建者:</td>
                            <td>{{ $template->created_by }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">创建时间:</td>
                            <td>{{ $template->created_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">更新时间:</td>
                            <td>{{ $template->updated_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- 使用统计 -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">使用统计</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="border-end">
                                <h4 class="text-primary mb-1">{{ $template->changeTasks->count() }}</h4>
                                <small class="text-muted">总使用次数</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border-end">
                                <h4 class="text-success mb-1">{{ $template->changeTasks->where('status', 'completed')->count() }}</h4>
                                <small class="text-muted">成功执行</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <h4 class="text-danger mb-1">{{ $template->changeTasks->where('status', 'failed')->count() }}</h4>
                            <small class="text-muted">执行失败</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 配置项详情 -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">配置项详情</h5>
                </div>
                <div class="card-body">
                    @if(is_array($template->config_items) && count($template->config_items) > 0)
                        @foreach($template->config_items as $index => $item)
                            <div class="config-item mb-4 p-3 border rounded">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-file-code"></i> 
                                    {{ $item['name'] ?? '配置项 ' . ($index + 1) }}
                                </h6>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <strong>文件路径:</strong>
                                        <code class="d-block mt-1">{{ $item['file_path'] ?? '未指定' }}</code>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>修改项数量:</strong>
                                        <span class="badge bg-info">{{ count($item['modifications'] ?? []) }} 项</span>
                                    </div>
                                </div>

                                @if(isset($item['modifications']) && is_array($item['modifications']))
                                    <div class="modifications">
                                        <strong>修改规则:</strong>
                                        <div class="table-responsive mt-2">
                                            <table class="table table-sm table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th width="30%">匹配模式</th>
                                                        <th width="30%">替换内容</th>
                                                        <th width="40%">说明</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($item['modifications'] as $mod)
                                                        <tr>
                                                            <td><code>{{ $mod['pattern'] ?? '' }}</code></td>
                                                            <td><code>{{ $mod['replacement'] ?? '' }}</code></td>
                                                            <td>{{ $mod['description'] ?? '无' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>暂无配置项</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 模板变量 -->
    @if(isset($template->variables) && is_array($template->variables) && count($template->variables) > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">模板变量</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>变量名</th>
                                    <th>默认值</th>
                                    <th>说明</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($template->variables as $variable)
                                    <tr>
                                        <td><code>{{ $variable['name'] ?? '' }}</code></td>
                                        <td><code>{{ $variable['default_value'] ?? '' }}</code></td>
                                        <td>{{ $variable['description'] ?? '无' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- 使用历史 -->
    @if($template->changeTasks->count() > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">使用历史</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>任务名称</th>
                                    <th>执行状态</th>
                                    <th>服务器数量</th>
                                    <th>创建时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($template->changeTasks->take(10) as $task)
                                    <tr>
                                        <td>{{ $task->name }}</td>
                                        <td>
                                            @switch($task->status)
                                                @case('pending')
                                                    <span class="badge bg-warning">等待执行</span>
                                                    @break
                                                @case('running')
                                                    <span class="badge bg-primary">执行中</span>
                                                    @break
                                                @case('completed')
                                                    <span class="badge bg-success">已完成</span>
                                                    @break
                                                @case('failed')
                                                    <span class="badge bg-danger">执行失败</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary">未知</span>
                                            @endswitch
                                        </td>
                                        <td>{{ $task->server_count ?? 0 }} 台</td>
                                        <td>{{ $task->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            <a href="{{ route('system-change.tasks.show', $task) }}" class="btn btn-sm btn-outline-primary">
                                                查看详情
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    @if($template->changeTasks->count() > 10)
                        <div class="text-center mt-3">
                            <a href="{{ route('system-change.tasks.index', ['template_id' => $template->id]) }}" class="btn btn-outline-secondary">
                                查看全部使用记录
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // 可以添加一些交互功能
});
</script>
@endsection