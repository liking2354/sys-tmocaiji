@extends('layouts.app')

@section('title', '数据清理 - 服务器管理与数据采集系统')

@section('content')
<div class="container-fluid">
    <!-- 页面标题 -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1">
                <i class="fas fa-trash-alt mr-2"></i>数据清理
            </h2>
            <p class="text-muted">清理过期或不需要的数据，释放存储空间</p>
        </div>
    </div>
    
    <!-- 数据统计部分 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-info shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie mr-2"></i>数据统计</h5>
                </div>
                <div class="card-body py-4">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card mb-3 border-primary shadow-sm">
                                <div class="card-body text-center py-4">
                                    <div class="display-4 text-primary mb-2">{{ App\Models\Server::count() }}</div>
                                    <p class="mb-0 text-muted"><i class="fas fa-server mr-1"></i>服务器总数</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card mb-3 border-success shadow-sm">
                                <div class="card-body text-center py-4">
                                    <div class="display-4 text-success mb-2">{{ App\Models\Collector::count() }}</div>
                                    <p class="mb-0 text-muted"><i class="fas fa-puzzle-piece mr-1"></i>采集组件总数</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3 shadow-sm">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0"><i class="fas fa-database mr-2"></i>数据存储占用</h6>
                                    <small class="text-muted">单位: GB</small>
                                </div>
                                <div class="card-body">
                                    <canvas id="storageChart" height="120"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 清理条件部分 -->
    <div class="row">
        <div class="col-12">
            <div class="card card-warning shadow-sm">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0"><i class="fas fa-filter mr-2"></i>清理条件</h5>
                </div>
                <div class="card-body" id="cleanupConditions">
                    <form action="{{ route('data.cleanup') }}" method="POST" id="cleanupForm">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-server mr-1"></i>选择服务器</label>
                                    <div class="card shadow-sm">
                                        <div class="card-header bg-light p-2 d-flex justify-content-between align-items-center">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAllServers">
                                                <label class="form-check-label font-weight-bold" for="selectAllServers">
                                                    全选/取消全选
                                                </label>
                                            </div>
                                            <span class="badge badge-primary server-count">0 已选择</span>
                                        </div>
                                        <div class="card-body p-2" style="max-height: 200px; overflow-y: auto;">
                                            @foreach ($serverGroups as $group)
                                                <div class="mb-2 server-group" data-group-id="{{ $group->id }}">
                                                    <h6 class="d-flex justify-content-between align-items-center">
                                                        <span>{{ $group->name ?? '无分组' }} ({{ $group->servers->count() }})</span>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary toggle-group-servers" data-group-id="{{ $group->id }}">
                                                            <i class="fas fa-chevron-down"></i>
                                                        </button>
                                                    </h6>
                                                    <div class="row group-servers-container" id="group-servers-{{ $group->id }}" style="display: {{ $loop->first ? 'flex' : 'none' }};">
                                                        @if($group->servers->count() > 0)
                                                            @foreach($group->servers as $server)
                                                                <div class="col-md-6">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input server-checkbox" type="checkbox" id="server_{{ $server->id }}" name="server_ids[]" value="{{ $server->id }}">
                                                                        <label class="form-check-label" for="server_{{ $server->id }}">
                                                                            {{ $server->name }}
                                                                            <small class="text-muted">({{ $server->ip }})</small>
                                                                            @if($server->status == 1)
                                                                                <span class="badge badge-success">在线</span>
                                                                            @else
                                                                                <span class="badge badge-danger">离线</span>
                                                                            @endif
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        @else
                                                            <div class="col-12 text-center">
                                                                <span class="text-muted">该分组下没有服务器</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @error('server_ids')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-puzzle-piece mr-1"></i>选择采集组件</label>
                                    <div class="card shadow-sm">
                                        <div class="card-header bg-light p-2 d-flex justify-content-between align-items-center">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAllCollectors">
                                                <label class="form-check-label font-weight-bold" for="selectAllCollectors">
                                                    全选/取消全选
                                                </label>
                                            </div>
                                            <span class="badge badge-success collector-count">0 已选择</span>
                                        </div>
                                        <div class="card-body p-2" style="max-height: 200px; overflow-y: auto;">
                                            <div class="row" id="collectors-container">
                                                @foreach($collectors as $collector)
                                                    <div class="col-md-6">
                                                        <div class="form-check">
                                                            <input class="form-check-input collector-checkbox" type="checkbox" id="collector_{{ $collector->id }}" name="collector_ids[]" value="{{ $collector->id }}">
                                                            <label class="form-check-label" for="collector_{{ $collector->id }}">
                                                                {{ $collector->name }}
                                                                @if($collector->status == 1)
                                                                    <span class="badge badge-success">启用</span>
                                                                @else
                                                                    <span class="badge badge-secondary">禁用</span>
                                                                @endif
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    @error('collector_ids')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-calendar-alt mr-1"></i>时间范围</label>
                                    <div class="card shadow-sm">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="start_date">开始日期</label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                                            </div>
                                                            <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date') }}">
                                                        </div>
                                                        @error('start_date')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="end_date">结束日期</label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                                            </div>
                                                            <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ old('end_date') }}">
                                                        </div>
                                                        @error('end_date')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-warning mt-4 shadow-sm">
                                    <i class="fas fa-exclamation-triangle mr-2"></i> <strong>警告：</strong>数据清理操作不可恢复，请谨慎操作！
                                </div>
                                
                                <div class="form-group text-right mt-4">
                                    <button type="button" class="btn btn-danger btn-lg shadow-sm" id="cleanupBtn">
                                        <i class="fas fa-trash mr-2"></i> 清理数据
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 确认模态框 -->
<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title" id="confirmModalLabel">
                    <i class="fas fa-exclamation-circle mr-2"></i>确认清理数据
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> 警告：此操作将永久删除所选数据，无法恢复！
                </div>
                <p>您确定要清理以下数据吗？</p>
                <ul id="cleanupSummary">
                    <li>服务器：<span id="serverCount">0</span> 台</li>
                    <li>采集组件：<span id="collectorCount">0</span> 个</li>
                    <li>时间范围：<span id="dateRange">全部</span></li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-danger" id="confirmCleanupBtn">
                    <i class="fas fa-trash"></i> 确认清理
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
<script src="{{ asset('assets/js/modules/data-cleanup.js') }}"></script>
@endpush
