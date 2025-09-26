@extends('layouts.app')

@section('title', '导出确认')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">导出确认</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 您已选择 <strong>{{ count($servers) }}</strong> 台服务器进行导出。请选择要导出的采集组件数据。
                    </div>
                    
                    <form id="exportSelectedForm" action="{{ route('servers.export-selected') }}" method="POST">
                        @csrf
                        
                        <!-- 隐藏的服务器ID -->
                        @foreach($serverIds as $serverId)
                            <input type="hidden" name="server_ids[]" value="{{ $serverId }}">
                        @endforeach
                        
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">选择要导出的采集组件数据</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="selectAllCollectors">
                                                <label class="custom-control-label" for="selectAllCollectors"><strong>全选/取消全选</strong></label>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                            @forelse($collectors as $collector)
                                                <div class="col-md-4 mb-3">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input collector-checkbox" 
                                                               name="collector_ids[]" 
                                                               id="collector-{{ $collector->id }}" 
                                                               value="{{ $collector->id }}">
                                                        <label class="custom-control-label" for="collector-{{ $collector->id }}">
                                                            {{ $collector->name }}
                                                            <small class="text-muted">({{ $collector->code }})</small>
                                                        </label>
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="col-12">
                                                    <div class="alert alert-warning">
                                                        所选服务器没有安装任何采集组件。
                                                    </div>
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">选中的服务器</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>服务器名称</th>
                                                        <th>IP地址</th>
                                                        <th>分组</th>
                                                        <th>状态</th>
                                                        <th>已安装采集组件</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($servers as $server)
                                                        <tr>
                                                            <td>{{ $server->id }}</td>
                                                            <td>{{ $server->name }}</td>
                                                            <td>{{ $server->ip }}</td>
                                                            <td>{{ $server->group->name ?? '无分组' }}</td>
                                                            <td>
                                                                @if($server->status)
                                                                    <span class="badge badge-success">在线</span>
                                                                @else
                                                                    <span class="badge badge-danger">离线</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if($server->collectors->count() > 0)
                                                                    @foreach($server->collectors as $collector)
                                                                        <span class="badge badge-info">{{ $collector->name }}</span>
                                                                    @endforeach
                                                                @else
                                                                    <span class="text-muted">无</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 格式选择 -->
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">选择导出格式</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <div class="custom-control custom-radio custom-control-inline">
                                                <input type="radio" class="custom-control-input" id="formatXlsx" name="format" value="xlsx" checked>
                                                <label class="custom-control-label" for="formatXlsx">
                                                    <i class="fas fa-file-excel text-success"></i> Excel (.xlsx)
                                                </label>
                                            </div>
                                            <div class="custom-control custom-radio custom-control-inline">
                                                <input type="radio" class="custom-control-input" id="formatCsv" name="format" value="csv">
                                                <label class="custom-control-label" for="formatCsv">
                                                    <i class="fas fa-file-csv text-info"></i> CSV (.csv)
                                                </label>
                                            </div>
                                        </div>
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle"></i> 
                                            Excel格式支持更好的格式化和样式，CSV格式更适合数据处理和导入其他系统。
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-12 text-center">
                                <button type="submit" id="exportBtn" class="btn btn-primary">
                                    <i class="fas fa-file-export"></i> 导出选中数据
                                </button>
                                <a href="{{ route('servers.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> 返回服务器列表
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // 全选/取消全选
        $('#selectAllCollectors').change(function() {
            $('.collector-checkbox').prop('checked', $(this).prop('checked'));
            updateExportButtonState();
        });
        
        // 单个复选框变化时更新全选框状态
        $('.collector-checkbox').change(function() {
            updateSelectAllCheckbox();
            updateExportButtonState();
        });
        
        // 更新全选复选框状态
        function updateSelectAllCheckbox() {
            var allChecked = $('.collector-checkbox:checked').length === $('.collector-checkbox').length;
            $('#selectAllCollectors').prop('checked', allChecked);
        }
        
        // 更新导出按钮状态
        function updateExportButtonState() {
            var hasSelectedCollectors = $('.collector-checkbox:checked').length > 0;
            $('#exportBtn').prop('disabled', !hasSelectedCollectors);
        }
        
        // 表单提交前验证
        $('#exportSelectedForm').submit(function(e) {
            if ($('.collector-checkbox:checked').length === 0) {
                e.preventDefault();
                alert('请至少选择一个采集组件');
                return false;
            }
            return true;
        });
        
        // 初始化按钮状态
        updateExportButtonState();
    });
</script>
@endsection