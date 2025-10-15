@extends('layouts.app')

@section('title', '配置模板管理')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-code mr-2"></i>
                        配置模板管理
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('system-change.templates.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus mr-1"></i>
                            新增模板
                        </a>
                        <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#importModal">
                            <i class="fas fa-upload mr-1"></i>
                            导入模板
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <!-- 搜索筛选 -->
                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="搜索模板名称或描述..." 
                                           value="{{ request('search') }}">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="submit">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-control" onchange="this.form.submit();">
                                    <option value="">全部状态</option>
                                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>启用</option>
                                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>禁用</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <a href="{{ route('system-change.templates.index') }}" class="btn btn-default">
                                    <i class="fas fa-undo mr-1"></i>
                                    重置
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- 模板列表 -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th width="5%">ID</th>
                                    <th>模板名称</th>
                                    <th>描述</th>
                                    <th width="10%">规则数</th>
                                    <th width="10%">变量数</th>
                                    <th width="8%">状态</th>
                                    <th width="10%">创建人</th>
                                    <th width="12%">创建时间</th>
                                    <th width="15%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($templates as $template)
                                <tr>
                                    <td>{{ $template->id }}</td>
                                    <td>
                                        <strong>{{ $template->name }}</strong>
                                    </td>
                                    <td>
                                        {{ Str::limit($template->description, 50) ?: '-' }}
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ is_array($template->config_rules) ? count($template->config_rules) : 0 }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary">{{ is_array($template->template_variables) ? count($template->template_variables) : 0 }}</span>
                                    </td>
                                    <td>
                                        @if($template->is_active)
                                            <span class="badge badge-success">启用</span>
                                        @else
                                            <span class="badge badge-secondary">禁用</span>
                                        @endif
                                    </td>
                                    <td>{{ $template->created_by ?: '-' }}</td>
                                    <td>{{ $template->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('system-change.templates.show', $template) }}" 
                                               class="btn btn-info" title="查看">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('system-change.templates.edit', $template) }}" 
                                               class="btn btn-warning" title="编辑">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="{{ route('system-change.templates.export', $template) }}" 
                                               class="btn btn-success" title="导出">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <form method="POST" action="{{ route('system-change.templates.duplicate', $template) }}" 
                                                  style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-secondary" title="复制">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('system-change.templates.toggle-status', $template) }}" 
                                                  style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn {{ $template->is_active ? 'btn-secondary' : 'btn-success' }}" 
                                                        title="{{ $template->is_active ? '禁用' : '启用' }}">
                                                    <i class="fas fa-{{ $template->is_active ? 'pause' : 'play' }}"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('system-change.templates.destroy', $template) }}" 
                                                  style="display: inline;" 
                                                  onsubmit="return confirm('确定要删除这个模板吗？')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger" title="删除">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3"></i><br>
                                        暂无配置模板数据
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- 分页 -->
                    <div class="d-flex justify-content-center">
                        {{ $templates->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 导入模板模态框 -->
<div class="modal fade" id="importModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <form method="POST" action="{{ route('system-change.templates.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">导入配置模板</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> 导入说明：</h6>
                        <ul class="mb-2">
                            <li>支持JSON格式的配置模板文件</li>
                            <li>文件大小不超过2MB</li>
                            <li>如果不知道格式，请先下载样例模板</li>
                        </ul>
                        <a href="{{ route('system-change.templates.download-sample') }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-download"></i> 下载样例模板
                        </a>
                    </div>
                    
                    <div class="form-group">
                        <label for="template_file">选择模板文件</label>
                        <input type="file" class="form-control-file" id="template_file" name="template_file" 
                               accept=".json" required>
                        <small class="form-text text-muted">
                            请选择JSON格式的模板文件
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">导入</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
.table th {
    white-space: nowrap;
}
.btn-group-sm > .btn {
    margin-right: 2px;
}
</style>
@endpush