@extends('layouts.app')

@section('title', '字典管理')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">字典管理 - 云资源层次结构</h3>
                    <div>
                        <button type="button" class="btn btn-info btn-sm me-2" onclick="expandAll()">
                            <i class="fas fa-expand-arrows-alt"></i> 展开全部
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm me-2" onclick="collapseAll()">
                            <i class="fas fa-compress-arrows-alt"></i> 收起全部
                        </button>
                        <button type="button" class="btn btn-primary" onclick="window.initBasicData()">
                            <i class="fas fa-database"></i> 初始化基础数据
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- 平台筛选 -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="platformFilter" class="form-label">按云平台筛选：</label>
                            <select class="form-select" id="platformFilter" onchange="filterByPlatform()">
                                <option value="">全部平台</option>
                                <option value="aliyun">阿里云</option>
                                <option value="tencent">腾讯云</option>
                                <option value="huawei">华为云</option>
                                <option value="aws">AWS</option>
                                <option value="azure">Azure</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="levelFilter" class="form-label">按层级筛选：</label>
                            <select class="form-select" id="levelFilter" onchange="filterByLevel()">
                                <option value="">全部层级</option>
                                <option value="1">一级分类</option>
                                <option value="2">二级服务</option>
                                <option value="3">三级实现</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="searchInput" class="form-label">搜索：</label>
                            <input type="text" class="form-control" id="searchInput" placeholder="输入关键词搜索..." onkeyup="searchItems()">
                        </div>
                    </div>

                    <!-- 操作按钮 -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-success btn-sm" onclick="showAddModal(1)">
                                <i class="fas fa-plus"></i> 添加一级分类
                            </button>
                            <button type="button" class="btn btn-success btn-sm" onclick="showAddModal(2)">
                                <i class="fas fa-plus"></i> 添加二级服务
                            </button>
                            <button type="button" class="btn btn-success btn-sm" onclick="showAddModal(3)">
                                <i class="fas fa-plus"></i> 添加三级实现
                            </button>
                        </div>
                    </div>

                    <!-- 树形结构显示 -->
                    <div class="tree-container">
                        <div id="dictTree" class="dict-tree">
                            <!-- 树形结构将通过JavaScript动态加载 -->
                        </div>
                    </div>

                    <!-- 统计信息 -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle"></i> 统计信息</h6>
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>一级分类：</strong> <span id="level1Count">0</span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>二级服务：</strong> <span id="level2Count">0</span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>三级实现：</strong> <span id="level3Count">0</span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>总计：</strong> <span id="totalCount">0</span>
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

<!-- 添加/编辑模态框 -->
<div class="modal fade" id="itemModal" tabindex="-1" role="dialog" aria-labelledby="itemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="itemModalLabel">字典项</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="itemForm">
                <div class="modal-body">
                    <input type="hidden" name="item_id" id="itemId">
                    <input type="hidden" name="category_id" id="itemCategoryId" value="1">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">项目代码 *</label>
                                <input type="text" name="item_code" class="form-control" required>
                                <div class="form-text">英文字母、数字和下划线</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">项目名称 *</label>
                                <input type="text" name="item_name" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">层级 *</label>
                                <select name="level" class="form-control" required onchange="updateParentOptions()">
                                    <option value="1">一级分类</option>
                                    <option value="2">二级服务</option>
                                    <option value="3">三级实现</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">父级项目</label>
                                <select name="parent_id" class="form-control" id="parentSelect">
                                    <option value="">无父级</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">云平台类型</label>
                                <select name="platform_type" class="form-control">
                                    <option value="">通用</option>
                                    <option value="aliyun">阿里云</option>
                                    <option value="tencent">腾讯云</option>
                                    <option value="huawei">华为云</option>
                                    <option value="aws">AWS</option>
                                    <option value="azure">Azure</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">排序</label>
                                <input type="number" name="sort_order" class="form-control" value="0" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">描述</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">扩展属性 (JSON格式)</label>
                        <textarea name="metadata" class="form-control" rows="4" placeholder='{"key": "value"}'></textarea>
                        <div class="form-text">可选，用于存储额外的配置信息</div>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.dict-tree {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.tree-node {
    margin: 2px 0;
    padding: 8px 12px;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    background-color: #fff;
    transition: all 0.2s ease;
}

.tree-node:hover {
    background-color: #f8f9fa;
    border-color: #dee2e6;
}

.tree-node.level-1 {
    background-color: #e3f2fd;
    border-left: 4px solid #2196f3;
    font-weight: 600;
}

.tree-node.level-2 {
    background-color: #f3e5f5;
    border-left: 4px solid #9c27b0;
    margin-left: 20px;
    font-weight: 500;
}

.tree-node.level-3 {
    background-color: #e8f5e8;
    border-left: 4px solid #4caf50;
    margin-left: 40px;
}

.node-header {
    display: flex;
    justify-content: between;
    align-items: center;
    cursor: pointer;
}

.node-content {
    flex: 1;
}

.node-title {
    font-size: 14px;
    margin: 0;
    color: #333;
}

.node-code {
    font-size: 12px;
    color: #666;
    font-family: 'Courier New', monospace;
}

.node-actions {
    display: flex;
    gap: 5px;
}

.platform-badge {
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
    background-color: #007bff;
    color: white;
    margin-left: 8px;
}

.platform-badge.aliyun { background-color: #ff6900; }
.platform-badge.tencent { background-color: #006eff; }
.platform-badge.huawei { background-color: #ff0000; }
.platform-badge.aws { background-color: #ff9900; }
.platform-badge.azure { background-color: #0078d4; }

.expand-icon {
    margin-right: 8px;
    transition: transform 0.2s ease;
    cursor: pointer;
}

.expand-icon.expanded {
    transform: rotate(90deg);
}

.children {
    margin-top: 8px;
    display: none;
}

.children.show {
    display: block;
}

.btn-group-sm .btn {
    padding: 2px 6px;
    font-size: 11px;
}
</style>
@endpush

@push('scripts')
<script>
let treeData = [];
let filteredData = [];

// 初始化基础数据函数
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
                    loadTreeData();
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

// 初始化云资源数据
window.initCloudResources = function() {
    if (confirm('确定要初始化云资源数据吗？这将创建完整的三级层次结构数据。')) {
        $.ajax({
            url: '{{ route("admin.dict.init-cloud-resources") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert('云资源数据初始化成功！');
                    loadTreeData();
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
    loadTreeData();
    
    // 表单提交
    $('#itemForm').submit(function(e) {
        e.preventDefault();
        saveItem();
    });
});

// 加载树形数据
function loadTreeData() {
    $.ajax({
        url: '{{ route("admin.dict.tree-data") }}',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                treeData = response.data;
                filteredData = treeData;
                renderTree();
                updateStatistics();
            } else {
                alert('加载数据失败: ' + response.message);
            }
        },
        error: function(xhr) {
            alert('加载数据失败: ' + (xhr.responseJSON?.message || '网络错误'));
        }
    });
}

// 渲染树形结构
function renderTree(data = filteredData) {
    const container = $('#dictTree');
    container.empty();
    
    if (data.length === 0) {
        container.html('<div class="text-center text-muted py-5"><p>暂无数据，请点击"初始化云资源数据"创建基础数据</p></div>');
        return;
    }
    
    data.forEach(function(node) {
        container.append(createTreeNode(node));
    });
}

// 创建树节点
function createTreeNode(node) {
    const hasChildren = node.children && node.children.length > 0;
    const platformBadge = node.platform_type ? 
        `<span class="platform-badge ${node.platform_type}">${getPlatformName(node.platform_type)}</span>` : '';
    
    let html = `
        <div class="tree-node level-${node.level}" data-id="${node.id}" data-level="${node.level}">
            <div class="node-header" onclick="toggleNode(${node.id})">
                <div class="node-content">
                    ${hasChildren ? '<i class="fas fa-chevron-right expand-icon" id="icon-' + node.id + '"></i>' : '<i class="fas fa-circle expand-icon" style="font-size: 6px; opacity: 0.5;"></i>'}
                    <div class="node-title">${node.item_name}${platformBadge}</div>
                    <div class="node-code">${node.item_code}</div>
                </div>
                <div class="node-actions" onclick="event.stopPropagation()">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary" onclick="editItem(${node.id})" title="编辑">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-success" onclick="addChild(${node.id}, ${node.level + 1})" title="添加子项">
                            <i class="fas fa-plus"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger" onclick="deleteItem(${node.id})" title="删除">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
    `;
    
    if (hasChildren) {
        html += '<div class="children" id="children-' + node.id + '">';
        node.children.forEach(function(child) {
            html += createTreeNode(child);
        });
        html += '</div>';
    }
    
    html += '</div>';
    
    return html;
}

// 获取平台名称
function getPlatformName(type) {
    const names = {
        'aliyun': '阿里云',
        'tencent': '腾讯云', 
        'huawei': '华为云',
        'aws': 'AWS',
        'azure': 'Azure'
    };
    return names[type] || type;
}

// 切换节点展开/收起
function toggleNode(nodeId) {
    const icon = $('#icon-' + nodeId);
    const children = $('#children-' + nodeId);
    
    if (children.hasClass('show')) {
        children.removeClass('show');
        icon.removeClass('expanded');
    } else {
        children.addClass('show');
        icon.addClass('expanded');
    }
}

// 展开全部
function expandAll() {
    $('.children').addClass('show');
    $('.expand-icon').addClass('expanded');
}

// 收起全部
function collapseAll() {
    $('.children').removeClass('show');
    $('.expand-icon').removeClass('expanded');
}

// 按平台筛选
function filterByPlatform() {
    const platform = $('#platformFilter').val();
    applyFilters();
}

// 按层级筛选
function filterByLevel() {
    const level = $('#levelFilter').val();
    applyFilters();
}

// 搜索
function searchItems() {
    const keyword = $('#searchInput').val().toLowerCase();
    applyFilters();
}

// 应用筛选
function applyFilters() {
    const platform = $('#platformFilter').val();
    const level = $('#levelFilter').val();
    const keyword = $('#searchInput').val().toLowerCase();
    
    filteredData = filterTreeData(treeData, platform, level, keyword);
    renderTree(filteredData);
    updateStatistics();
}

// 筛选树数据
function filterTreeData(data, platform, level, keyword) {
    return data.filter(function(node) {
        // 平台筛选
        if (platform && node.platform_type !== platform) {
            return false;
        }
        
        // 层级筛选
        if (level && node.level != level) {
            return false;
        }
        
        // 关键词搜索
        if (keyword && !node.item_name.toLowerCase().includes(keyword) && 
            !node.item_code.toLowerCase().includes(keyword)) {
            return false;
        }
        
        return true;
    }).map(function(node) {
        // 递归筛选子节点
        const filteredNode = {...node};
        if (node.children) {
            filteredNode.children = filterTreeData(node.children, platform, level, keyword);
        }
        return filteredNode;
    });
}

// 更新统计信息
function updateStatistics() {
    let level1Count = 0, level2Count = 0, level3Count = 0;
    
    function countNodes(nodes) {
        nodes.forEach(function(node) {
            if (node.level === 1) level1Count++;
            else if (node.level === 2) level2Count++;
            else if (node.level === 3) level3Count++;
            
            if (node.children) {
                countNodes(node.children);
            }
        });
    }
    
    countNodes(treeData);
    
    $('#level1Count').text(level1Count);
    $('#level2Count').text(level2Count);
    $('#level3Count').text(level3Count);
    $('#totalCount').text(level1Count + level2Count + level3Count);
}

// 显示添加模态框
function showAddModal(level, parentId = null) {
    $('#itemForm')[0].reset();
    $('#itemId').val('');
    $('select[name="level"]').val(level);
    
    if (parentId) {
        $('select[name="parent_id"]').val(parentId);
    }
    
    updateParentOptions();
    $('#itemModalLabel').text('添加' + getLevelName(level));
    $('#itemModal').modal('show');
}

// 添加子项
function addChild(parentId, level) {
    showAddModal(level, parentId);
}

// 编辑项目
function editItem(itemId) {
    $.ajax({
        url: '{{ route("admin.dict.items.show", ":id") }}'.replace(':id', itemId),
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const item = response.data;
                $('#itemId').val(item.id);
                $('input[name="item_code"]').val(item.item_code);
                $('input[name="item_name"]').val(item.item_name);
                $('select[name="level"]').val(item.level);
                $('select[name="parent_id"]').val(item.parent_id || '');
                $('select[name="platform_type"]').val(item.platform_type || '');
                $('input[name="sort_order"]').val(item.sort_order);
                $('textarea[name="description"]').val(item.description || '');
                $('textarea[name="metadata"]').val(item.metadata ? JSON.stringify(item.metadata, null, 2) : '');
                $('select[name="status"]').val(item.status);
                
                updateParentOptions();
                $('#itemModalLabel').text('编辑' + getLevelName(item.level));
                $('#itemModal').modal('show');
            }
        },
        error: function(xhr) {
            alert('加载数据失败: ' + (xhr.responseJSON?.message || '网络错误'));
        }
    });
}

// 删除项目
function deleteItem(itemId) {
    if (confirm('确定要删除这个项目吗？删除后其子项目也会被删除。')) {
        $.ajax({
            url: '{{ route("admin.dict.items.destroy", ":id") }}'.replace(':id', itemId),
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    loadTreeData();
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

// 保存项目
function saveItem() {
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
                loadTreeData();
            } else {
                alert('保存失败: ' + response.message);
            }
        },
        error: function(xhr) {
            alert('保存失败: ' + (xhr.responseJSON?.message || '网络错误'));
        }
    });
}

// 更新父级选项
function updateParentOptions() {
    const level = parseInt($('select[name="level"]').val());
    const parentSelect = $('#parentSelect');
    
    parentSelect.empty().append('<option value="">无父级</option>');
    
    if (level > 1) {
        const parentLevel = level - 1;
        
        function addParentOptions(nodes, prefix = '') {
            nodes.forEach(function(node) {
                if (node.level === parentLevel) {
                    parentSelect.append(`<option value="${node.id}">${prefix}${node.item_name}</option>`);
                }
                if (node.children) {
                    addParentOptions(node.children, prefix + '  ');
                }
            });
        }
        
        addParentOptions(treeData);
    }
}

// 获取层级名称
function getLevelName(level) {
    const names = {1: '一级分类', 2: '二级服务', 3: '三级实现'};
    return names[level] || '项目';
}
</script>
@endpush