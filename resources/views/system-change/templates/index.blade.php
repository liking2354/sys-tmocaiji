@extends('layouts.app')

@section('title', '配置模板管理')

@section('content')
<div class="container-fluid">
    <!-- 页面标题和操作按钮 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0">
                <i class="fas fa-file-code text-primary"></i> 配置模板管理
            </h1>
            <small class="text-muted">管理和配置系统变更模板</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('system-change.templates.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> 新增模板
            </a>
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#importModal">
                <i class="fas fa-upload"></i> 导入模板
            </button>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <!-- 搜索筛选卡片 -->
            <div class="search-filter-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-filter"></i> 搜索和筛选
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET">
                        <div class="search-row">
                            <div>
                                <label for="search">搜索</label>
                                <input type="text" name="search" class="form-control" placeholder="搜索模板名称或描述..." 
                                       value="{{ request('search') }}">
                            </div>
                            <div>
                                <label for="status">状态</label>
                                <select name="status" class="form-control">
                                    <option value="">全部状态</option>
                                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>启用</option>
                                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>禁用</option>
                                </select>
                            </div>
                        </div>
                        <div class="button-row">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> 搜索
                            </button>
                            <a href="{{ route('system-change.templates.index') }}" class="btn btn-secondary">
                                <i class="fas fa-sync"></i> 重置
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 模板列表卡片 -->
            <div class="card card-light-blue shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list"></i> 模板列表
                    </h5>
                </div>
                <div class="card-body p-0">
                    <!-- 模板列表 -->
                    <div class="table-responsive">
                        <table class="table table-striped table-light table-hover mb-0">
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
                                               class="btn btn-primary" title="查看">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('system-change.templates.edit', $template) }}" 
                                               class="btn btn-primary" title="编辑">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="{{ route('system-change.templates.export', $template) }}" 
                                               class="btn btn-primary" title="导出">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <button type="button" class="btn btn-secondary" onclick="duplicateTemplate({{ $template->id }})" title="复制">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                            <button type="button" class="btn {{ $template->is_active ? 'btn-secondary' : 'btn-primary' }}" onclick="toggleTemplateStatus({{ $template->id }})" title="{{ $template->is_active ? '禁用' : '启用' }}">
                                                <i class="fas fa-{{ $template->is_active ? 'pause' : 'play' }}"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger" onclick="deleteTemplate({{ $template->id }})" title="删除">
                                                <i class="fas fa-trash"></i>
                                            </button>
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
                        <a href="{{ route('system-change.templates.download-sample') }}" class="btn btn-sm btn-secondary">
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

@push('scripts')
<script src="{{ asset('assets/js/common/delete-handler.js') }}"></script>
<script>
    // 复制模板
    function duplicateTemplate(templateId) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `{{ route('system-change.templates.duplicate', '') }}/${templateId}`;
        form.innerHTML = `<input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').getAttribute('content')}">`;
        document.body.appendChild(form);
        form.submit();
    }
    
    // 切换模板状态
    function toggleTemplateStatus(templateId) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `{{ route('system-change.templates.toggle-status', '') }}/${templateId}`;
        form.innerHTML = `<input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').getAttribute('content')}">`;
        document.body.appendChild(form);
        form.submit();
    }
</script>
@endpush
