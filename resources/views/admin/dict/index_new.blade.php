@extends('layouts.app')

@section('title', '字典管理')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">字典管理 - 三级层次结构</h3>
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#categoryModal">
                            <i class="fas fa-plus"></i> 新增分类
                        </button>
                        <button type="button" class="btn btn-success" onclick="window.initBasicData()">
                            <i class="fas fa-database"></i> 初始化基础数据
                        </button>
                        <button type="button" class="btn btn-info" onclick="toggleViewMode()">
                            <i class="fas fa-exchange-alt"></i> <span id="viewModeText">切换到表格视图</span>
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <!-- 分类选择器 -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">选择字典分类：</label>
                            <select class="form-control" id="categorySelector">
                                <option value="">请选择字典分类</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" data-code="{{ $category->category_code }}">
                                        {{ $category->category_name }} ({{ $category->category_code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">平台筛选：</label>
                            <select class="form-control" id="platformFilter">
                                <option value="">所有平台</option>
                                <option value="aliyun">阿里云</option>
                                <option value="tencent">腾讯云</option>
                                <option value="huawei">华为云</option>
                                <option value="aws">AWS</option>
                                <option value="azure">Azure</option>
                                <option value="gcp">Google Cloud</option>
                            </select>
                        </div>
                    </div>

                    <!-- 树形视图 -->
                    <div id="treeView" class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">层次结构视图</h5>
                                    <button type="button" class="btn btn-sm btn-primary" onclick="showItemModal()" id="addItemBtn" style="display: none;">
                                        <i class="fas fa-plus"></i> 新增字典项
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div id="hierarchyContainer">
                                        <div class="text-center text-muted py-5">
                                            <i class="fas fa-sitemap fa-2x mb-3"></i>
                                            <p>请选择字典分类查看层次结构</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 表格视图 -->
                    <div id="tableView" class="row" style="display: none;">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">表格视图</h5>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-success" onclick="expandAll()">
                                            <i class="fas fa-expand-arrows-alt"></i> 展开全部
                                        </button>
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="collapseAll()">
                                            <i class="fas fa-compress-arrows-alt"></i> 收起全部
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="tableContainer">
                                        <div class="text-center text-muted py-5">
                                            <i class="fas fa-table fa-2x mb-3"></i>
                                            <p>请选择字典分类查看表格数据</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 分类模态框 -->
<div class="modal fade" id="categoryModal" tabindex="-1" role="dialog" aria-labelledby="categoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryModalLabel">字典分类</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="categoryForm">
                <div class="modal-body">
                    <input type="hidden" name="category_id" id="categoryId">
                    <div class="mb-3">
                        <label class="form-label">分类代码</label>
                        <input type="text" name="category_code" class="form-control" required>
                        <div class="form-text">英文字母、数字和下划线，用于程序调用</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">分类名称</label>
                        <input type="text" name="category_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">描述</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">排序</label>
                        <input type="number" name="sort_order" class="form-control" value="0" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">状态</label>
                        <select name="status" class="form-control" required>
                            <option value="active">启用</option>
                            <option value="inactive">禁用</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 字典项模态框 -->
<div class="modal fade" id="itemModal" tabindex="-1" role="dialog" aria-labelledby="itemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="itemModalLabel">字典项</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="itemForm">
                <div class="modal-body">
                    <input type="hidden" name="item_id" id="itemId">
                    <input type="hidden" name="category_id" id="itemCategoryId">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">项目代码 <span class="text-danger">*</span></label>
                                <input type="text" name="item_code" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">项目名称 <span class="text-danger">*</span></label>
                                <input type="text" name="item_name" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">项目值</label>
                                <input type="text" name="item_value" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">层级</label>
                                <select name="level" class="form-control" id="levelSelect">
                                    <option value="1">一级分类</option>
                                    <option value="2">二级分类</option>
                                    <option value="3">三级分类</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">父级项目</label>
                                <select name="parent_id" class="form-control" id="parentSelect">
                                    <option value="">无父级</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">平台类型</label>
                                <select name="platform_type" class="form-control" id="platformTypeSelect">
                                    <option value="">通用</option>
                                    <option value="aliyun">阿里云</option>
                                    <option value="tencent">腾讯云</option>
                                    <option value="huawei">华为云</option>
                                    <option value="aws">AWS</option>
                                    <option value="azure">Azure</option>
                                    <option value="gcp">Google Cloud</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">排序</label>
                                <input type="number" name="sort_order" class="form-control" value="0" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">状态</label>
                                <select name="status" class="form-control" required>
                                    <option value="active">启用</option>
                                    <option value="inactive">禁用</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">扩展属性 (JSON格式)</label>
                        <textarea name="metadata" class="form-control" rows="4" placeholder='{"key": "value"}'></textarea>
                        <div class="form-text">可选，用于存储额外的配置信息</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.hierarchy-item {
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    margin-bottom: 0.5rem;
    background: #fff;
}

.hierarchy-item.level-1 {
    border-left: 4px solid #007bff;
}

.hierarchy-item.level-2 {
    border-left: 4px solid #28a745;
    margin-left: 1rem;
}

.hierarchy-item.level-3 {
    border-left: 4px solid #ffc107;
    margin-left: 2rem;
}

.hierarchy-header {
    padding: 0.75rem 1rem;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    cursor: pointer;
}

.hierarchy-content {
    padding: 0.75rem 1rem;
}

.platform-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.tree-table {
    font-size: 0.9rem;
}

.tree-table .level-1 {
    font-weight: bold;
    background-color: #f8f9fa;
}

.tree-table .level-2 {
    padding-left: 2rem;
    background-color: #ffffff;
}

.tree-table .level-3 {
    padding-left: 4rem;
    background-color: #f9f9f9;
}

.expand-icon {
    cursor: pointer;
    transition: transform 0.2s;
}

.expand-icon.expanded {
    transform: rotate(90deg);
}
</style>
@endpush

@push('scripts')
<script>
let currentCategoryId = null;
let currentCategoryCode = null;
let currentViewMode = 'tree'; // 'tree' or 'table'
let hierarchyData = [];

// 初始化基础数据函数 - 放到全局作用域
window.initBasicData = function() {
    if (confirm('确定要初始化基础字典数据吗？这将创建云资源管理所需的基础字典分类和项目。')) {
        $.ajax({
            url: '{{ route("admin.dict.init-cloud-resources") }}',
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert('基础数据初始化成功');
                    location.reload();
                } else {
                    alert('初始化失败: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('初始化失败: ' + (xhr.responseJSON?.message || '网络错误'));
            }
        });
    }
};

$(document).ready(function() {
    // 分类选择器变化事件
    $('#categorySelector').change(function() {
        const categoryId = $(this).val();
        const categoryCode = $(this).find('option:selected').data('code');
        
        if (categoryId) {
            currentCategoryId = categoryId;
            currentCategoryCode = categoryCode;
            loadHierarchyData();
            $('#addItemBtn').show();
        } else {
            currentCategoryId = null;
            currentCategoryCode = null;
            clearViews();
            $('#addItemBtn').hide();
        }
    });

    // 平台筛选器变化事件
    $('#platformFilter').change(function() {
        if (currentCategoryId) {
            loadHierarchyData();
        }
    });

    // 层级选择变化事件
    $('#levelSelect').change(function() {
        loadParentOptions();
    });

    // 表单提交事件
    $('#categoryForm').submit(function(e) {
        e.preventDefault();
        saveCategoryForm();
    });

    $('#itemForm').submit(function(e) {
        e.preventDefault();
        saveItemForm();
    });
});

// 切换视图模式
function toggleViewMode() {
    if (currentViewMode === 'tree') {
        currentViewMode = 'table';
        $('#treeView').hide();
        $('#tableView').show();
        $('#viewModeText').text('切换到树形视图');
        if (hierarchyData.length > 0) {
            renderTableView();
        }
    } else {
        currentViewMode = 'tree';
        $('#tableView').hide();
        $('#treeView').show();
        $('#viewModeText').text('切换到表格视图');
        if (hierarchyData.length > 0) {
            renderTreeView();
        }
    }
}

// 加载层次数据
function loadHierarchyData() {
    const platformType = $('#platformFilter').val();
    
    $.ajax({
        url: '{{ route("admin.dict.hierarchy") }}',
        method: 'GET',
        data: { 
            category_code: currentCategoryCode,
            platform_type: platformType
        },
        success: function(response) {
            if (response.success) {
                hierarchyData = response.data;
                if (currentViewMode === 'tree') {
                    renderTreeView();
                } else {
                    renderTableView();
                }
            } else {
                alert('加载失败: ' + response.message);
            }
        },
        error: function(xhr) {
            alert('加载失败: ' + (xhr.responseJSON?.message || '网络错误'));
        }
    });
}

// 渲染树形视图
function renderTreeView() {
    let html = '';
    
    if (hierarchyData.length === 0) {
        html = '<div class="text-center text-muted py-5"><p>暂无数据</p></div>';
    } else {
        html = buildHierarchyHTML(hierarchyData);
    }
    
    $('#hierarchyContainer').html(html);
}

// 构建层次HTML
function buildHierarchyHTML(items, level = 1) {
    let html = '';
    
    items.forEach(function(item) {
        html += '<div class="hierarchy-item level-' + level + '" data-item-id="' + item.id + '">';
        html += '<div class="hierarchy-header" onclick="toggleHierarchyItem(' + item.id + ')">';
        html += '<div class="d-flex justify-content-between align-items-center">';
        html += '<div>';
        html += '<i class="fas fa-chevron-right expand-icon me-2" id="icon-' + item.id + '"></i>';
        html += '<strong>' + item.name + '</strong>';
        html += '<span class="text-muted ms-2">(' + item.code + ')</span>';
        if (item.platform_type) {
            html += '<span class="badge bg-info platform-badge ms-2">' + item.platform_type + '</span>';
        }
        html += '</div>';
        html += '<div class="btn-group btn-group-sm">';
        html += '<button type="button" class="btn btn-outline-primary" onclick="editItem(' + item.id + ', event)"><i class="fas fa-edit"></i></button>';
        html += '<button type="button" class="btn btn-outline-success" onclick="addChildItem(' + item.id + ', ' + (level + 1) + ', event)"><i class="fas fa-plus"></i></button>';
        html += '<button type="button" class="btn btn-outline-danger" onclick="deleteItem(' + item.id + ', event)"><i class="fas fa-trash"></i></button>';
        html += '</div>';
        html += '</div>';
        html += '</div>';
        
        if (item.children && item.children.length > 0) {
            html += '<div class="hierarchy-content" id="content-' + item.id + '" style="display: none;">';
            html += buildHierarchyHTML(item.children, level + 1);
            html += '</div>';
        }
        
        html += '</div>';
    });
    
    return html;
}

// 切换层次项展开/收起
function toggleHierarchyItem(itemId) {
    const content = $('#content-' + itemId);
    const icon = $('#icon-' + itemId);
    
    if (content.is(':visible')) {
        content.slideUp();
        icon.removeClass('expanded');
    } else {
        content.slideDown();
        icon.addClass('expanded');
    }
}

// 渲染表格视图
function renderTableView() {
    let html = '';
    
    if (hierarchyData.length === 0) {
        html = '<div class="text-center text-muted py-5"><p>暂无数据</p></div>';
    } else {
        html = '<div class="table-responsive">';
        html += '<table class="table table-striped tree-table">';
        html += '<thead><tr><th>名称</th><th>代码</th><th>层级</th><th>平台</th><th>状态</th><th>排序</th><th>操作</th></tr></thead>';
        html += '<tbody>';
        html += buildTableRows(hierarchyData, 1);
        html += '</tbody></table></div>';
    }
    
    $('#tableContainer').html(html);
}

// 构建表格行
function buildTableRows(items, level) {
    let html = '';
    
    items.forEach(function(item) {
        html += '<tr class="level-' + level + '" data-item-id="' + item.id + '">';
        html += '<td>';
        if (item.children && item.children.length > 0) {
            html += '<i class="fas fa-chevron-right expand-icon me-2" onclick="toggleTableRow(' + item.id + ')"></i>';
        } else {
            html += '<span class="me-4"></span>';
        }
        html += item.name;
        html += '</td>';
        html += '<td><code>' + item.code + '</code></td>';
        html += '<td><span class="badge bg-secondary">Level ' + level + '</span></td>';
        html += '<td>' + (item.platform_type ? '<span class="badge bg-info">' + item.platform_type + '</span>' : '-') + '</td>';
        html += '<td><span class="badge bg-' + (item.status === 'active' ? 'success' : 'secondary') + '">' + (item.status === 'active' ? '启用' : '禁用') + '</span></td>';
        html += '<td>' + (item.sort_order || 0) + '</td>';
        html += '<td>';
        html += '<div class="btn-group btn-group-sm">';
        html += '<button type="button" class="btn btn-outline-primary" onclick="editItem(' + item.id + ')"><i class="fas fa-edit"></i></button>';
        html += '<button type="button" class="btn btn-outline-success" onclick="addChildItem(' + item.id + ', ' + (level + 1) + ')"><i class="fas fa-plus"></i></button>';
        html += '<button type="button" class="btn btn-outline-danger" onclick="deleteItem(' + item.id + ')"><i class="fas fa-trash"></i></button>';
        html += '</div>';
        html += '</td>';
        html += '</tr>';
        
        if (item.children && item.children.length > 0) {
            html += buildTableRows(item.children, level + 1);
        }
    });
    
    return html;
}

// 切换表格行展开/收起
function toggleTableRow(itemId) {
    // 实现表格行的展开收起逻辑
    console.log('Toggle table row:', itemId);
}

// 展开全部
function expandAll() {
    $('.hierarchy-content').slideDown();
    $('.expand-icon').addClass('expanded');
}

// 收起全部
function collapseAll() {
    $('.hierarchy-content').slideUp();
    $('.expand-icon').removeClass('expanded');
}

// 清空视图
function clearViews() {
    $('#hierarchyContainer').html('<div class="text-center text-muted py-5"><i class="fas fa-sitemap fa-2x mb-3"></i><p>请选择字典分类查看层次结构</p></div>');
    $('#tableContainer').html('<div class="text-center text-muted py-5"><i class="fas fa-table fa-2x mb-3"></i><p>请选择字典分类查看表格数据</p></div>');
}

// 显示字典项模态框
function showItemModal(itemId = null, parentId = null, level = 1) {
    if (!currentCategoryId) {
        alert('请先选择字典分类');
        return;
    }

    $('#itemForm')[0].reset();
    $('#itemId').val(itemId || '');
    $('#itemCategoryId').val(currentCategoryId);
    
    if (parentId) {
        $('#parentSelect').val(parentId);
        $('#levelSelect').val(level);
    }
    
    loadParentOptions(itemId);
    
    if (itemId) {
        loadItemData(itemId);
    }
    
    $('#itemModal').modal('show');
}

// 添加子项
function addChildItem(parentId, level, event) {
    if (event) {
        event.stopPropagation();
    }
    showItemModal(null, parentId, level);
}

// 加载父级选项
function loadParentOptions(itemId = null) {
    const level = parseInt($('#levelSelect').val());
    
    $.ajax({
        url: '{{ route("admin.dict.items") }}',
        method: 'GET',
        data: { category_id: currentCategoryId },
        success: function(response) {
            if (response.success) {
                let options = '<option value="">无父级</option>';
                
                response.data.forEach(function(item) {
                    // 只显示比当前层级低的项目作为父级选项
                    if (item.level < level && (!itemId || item.id != itemId)) {
                        const indent = '　'.repeat((item.level - 1) * 2);
                        options += '<option value="' + item.id + '">' + indent + item.item_name + '</option>';
                    }
                });
                
                $('#parentSelect').html(options);
            }
        }
    });
}

// 加载字典项数据
function loadItemData(itemId) {
    $.ajax({
        url: '{{ route("admin.dict.items.show", ":id") }}'.replace(':id', itemId),
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const item = response.data;
                $('input[name="item_code"]').val(item.item_code);
                $('input[name="item_name"]').val(item.item_name);
                $('input[name="item_value"]').val(item.item_value || '');
                $('select[name="level"]').val(item.level || 1);
                $('select[name="parent_id"]').val(item.parent_id || '');
                $('select[name="platform_type"]').val(item.platform_type || '');
                $('input[name="sort_order"]').val(item.sort_order);
                $('select[name="status"]').val(item.status);
                $('textarea[name="metadata"]').val(item.metadata ? JSON.stringify(item.metadata, null, 2) : '');
            }
        }
    });
}

// 编辑字典项
function editItem(itemId, event) {
    if (event) {
        event.stopPropagation();
    }
    showItemModal(itemId);
}

// 删除字典项
function deleteItem(itemId, event) {
    if (event) {
        event.stopPropagation();
    }
    
    if (confirm('确定要删除这个字典项吗？删除后其子项也会一并删除。')) {
        $.ajax({
            url: '{{ route("admin.dict.items.destroy", ":id") }}'.replace(':id', itemId),
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    loadHierarchyData();
                } else {
                    alert('删除失败: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('删除失败: ' + (xhr.responseJSON?.message || '网络错误'));
            }
        });
    }
}

// 保存分类表单
function saveCategoryForm() {
    const formData = new FormData($('#categoryForm')[0]);
    const categoryId = $('#categoryId').val();
    const url = categoryId ? 
        '{{ route("admin.dict.categories.update", ":id") }}'.replace(':id', categoryId) : 
        '{{ route("admin.dict.categories.store") }}';
    
    $.ajax({
        url: url,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                alert(response.message);
                $('#categoryModal').modal('hide');
                location.reload();
            } else {
                alert('保存失败: ' + response.message);
            }
        },
        error: function(xhr) {
            alert('保存失败: ' + (xhr.responseJSON?.message || '网络错误'));
        }
    });
}

// 保存字典项表单
function saveItemForm() {
    const formData = new FormData($('#itemForm')[0]);
    const itemId = $('#itemId').val();
    const url = itemId ? 
        '{{ route("admin.dict.items.update", ":id") }}'.replace(':id', itemId) : 
        '{{ route("admin.dict.items.store") }}';
    
    // 验证JSON格式
    const metadata = $('textarea[name="metadata"]').val();
    if (metadata) {
        try {
            JSON.parse(metadata);
        } catch (e) {
            alert('扩展属性必须是有效的JSON格式');
            return;
        }
    }
    
    $.ajax({
        url: url,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                alert(response.message);
                $('#itemModal').modal('hide');
                loadHierarchyData();
            } else {
                alert('保存失败: ' + response.message);
            }
        },
        error: function(xhr) {
            alert('保存失败: ' + (xhr.responseJSON?.message || '网络错误'));
        }
    });
}
</script>
@endpush