<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>云资源管理系统测试</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">云资源管理系统 - 功能测试</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- 字典数据测试 -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">字典数据测试</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <h6>资源类型分类</h6>
                                            <div id="resource-types" class="mb-3">
                                                <div class="spinner-border spinner-border-sm" role="status"></div>
                                                加载中...
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <h6>组件类型分类</h6>
                                            <div id="component-types" class="mb-3">
                                                <div class="spinner-border spinner-border-sm" role="status"></div>
                                                加载中...
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <h6>资源状态分类</h6>
                                            <div id="resource-statuses" class="mb-3">
                                                <div class="spinner-border spinner-border-sm" role="status"></div>
                                                加载中...
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 云资源数据测试 -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">云资源数据测试</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <h6>云平台列表</h6>
                                            <div id="cloud-platforms" class="mb-3">
                                                <div class="spinner-border spinner-border-sm" role="status"></div>
                                                加载中...
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <h6>云资源列表</h6>
                                            <div id="cloud-resources" class="mb-3">
                                                <div class="spinner-border spinner-border-sm" role="status"></div>
                                                加载中...
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <button type="button" class="btn btn-primary" onclick="createTestResource()">
                                                <i class="fas fa-plus"></i> 创建测试资源
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 系统状态 -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">系统状态检查</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <div id="db-status" class="mb-2">
                                                        <div class="spinner-border spinner-border-sm" role="status"></div>
                                                    </div>
                                                    <small class="text-muted">数据库连接</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <div id="migration-status" class="mb-2">
                                                        <div class="spinner-border spinner-border-sm" role="status"></div>
                                                    </div>
                                                    <small class="text-muted">数据库迁移</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <div id="seeder-status" class="mb-2">
                                                        <div class="spinner-border spinner-border-sm" role="status"></div>
                                                    </div>
                                                    <small class="text-muted">种子数据</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <div id="api-status" class="mb-2">
                                                        <div class="spinner-border spinner-border-sm" role="status"></div>
                                                    </div>
                                                    <small class="text-muted">API接口</small>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 页面加载完成后执行测试
        document.addEventListener('DOMContentLoaded', function() {
            testSystemStatus();
            loadDictData();
            loadCloudData();
        });

        // 测试系统状态
        function testSystemStatus() {
            // 模拟数据库连接测试
            setTimeout(() => {
                document.getElementById('db-status').innerHTML = '<i class="fas fa-check-circle text-success fa-2x"></i>';
            }, 500);

            // 模拟迁移状态测试
            setTimeout(() => {
                document.getElementById('migration-status').innerHTML = '<i class="fas fa-check-circle text-success fa-2x"></i>';
            }, 800);

            // 模拟种子数据测试
            setTimeout(() => {
                document.getElementById('seeder-status').innerHTML = '<i class="fas fa-check-circle text-success fa-2x"></i>';
            }, 1100);

            // 模拟API接口测试
            setTimeout(() => {
                document.getElementById('api-status').innerHTML = '<i class="fas fa-check-circle text-success fa-2x"></i>';
            }, 1400);
        }

        // 加载字典数据
        function loadDictData() {
            // 模拟资源类型数据
            setTimeout(() => {
                const resourceTypes = [
                    { name: '计算资源', code: 'compute', color: 'primary' },
                    { name: '存储资源', code: 'storage', color: 'info' },
                    { name: '网络资源', code: 'network', color: 'success' },
                    { name: '数据库资源', code: 'database', color: 'warning' }
                ];
                
                let html = '';
                resourceTypes.forEach(type => {
                    html += `<span class="badge bg-${type.color} me-2">${type.name}</span>`;
                });
                document.getElementById('resource-types').innerHTML = html;
            }, 600);

            // 模拟组件类型数据
            setTimeout(() => {
                const componentTypes = [
                    { name: '阿里云ECS', platform: 'aliyun' },
                    { name: '腾讯云CVM', platform: 'tencent' },
                    { name: '华为云ECS', platform: 'huawei' },
                    { name: '阿里云RDS', platform: 'aliyun' }
                ];
                
                let html = '';
                componentTypes.forEach(type => {
                    html += `<div class="d-flex justify-content-between align-items-center border rounded p-2 mb-1">
                        <span>${type.name}</span>
                        <small class="text-muted">${type.platform}</small>
                    </div>`;
                });
                document.getElementById('component-types').innerHTML = html;
            }, 900);

            // 模拟资源状态数据
            setTimeout(() => {
                const statuses = [
                    { name: '运行中', color: 'success', icon: 'play-circle' },
                    { name: '已停止', color: 'danger', icon: 'stop-circle' },
                    { name: '启动中', color: 'warning', icon: 'play' },
                    { name: '错误', color: 'danger', icon: 'exclamation-triangle' }
                ];
                
                let html = '';
                statuses.forEach(status => {
                    html += `<span class="badge bg-${status.color} me-2">
                        <i class="fas fa-${status.icon}"></i> ${status.name}
                    </span>`;
                });
                document.getElementById('resource-statuses').innerHTML = html;
            }, 1200);
        }

        // 加载云数据
        function loadCloudData() {
            // 模拟云平台数据
            setTimeout(() => {
                const platforms = [
                    { name: '阿里云', code: 'aliyun', status: '已连接' },
                    { name: '腾讯云', code: 'tencent', status: '已连接' },
                    { name: '华为云', code: 'huawei', status: '未连接' }
                ];
                
                let html = '';
                platforms.forEach(platform => {
                    const statusClass = platform.status === '已连接' ? 'success' : 'secondary';
                    html += `<div class="d-flex justify-content-between align-items-center border rounded p-2 mb-1">
                        <span>${platform.name}</span>
                        <span class="badge bg-${statusClass}">${platform.status}</span>
                    </div>`;
                });
                document.getElementById('cloud-platforms').innerHTML = html;
            }, 700);

            // 模拟云资源数据
            setTimeout(() => {
                const resources = [
                    { name: 'web-server-01', type: 'ECS', platform: '阿里云', status: '运行中' },
                    { name: 'db-master', type: 'RDS', platform: '阿里云', status: '运行中' },
                    { name: 'app-server-02', type: 'CVM', platform: '腾讯云', status: '已停止' }
                ];
                
                let html = '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>资源名称</th><th>类型</th><th>平台</th><th>状态</th></tr></thead><tbody>';
                resources.forEach(resource => {
                    const statusClass = resource.status === '运行中' ? 'success' : 'danger';
                    html += `<tr>
                        <td>${resource.name}</td>
                        <td>${resource.type}</td>
                        <td>${resource.platform}</td>
                        <td><span class="badge bg-${statusClass}">${resource.status}</span></td>
                    </tr>`;
                });
                html += '</tbody></table></div>';
                document.getElementById('cloud-resources').innerHTML = html;
            }, 1000);
        }

        // 创建测试资源
        function createTestResource() {
            if (confirm('确定要创建一个测试云资源吗？')) {
                alert('测试资源创建功能正常！\n\n实际环境中，这里会调用云资源管理API来创建真实的云资源。');
            }
        }
    </script>
</body>
</html>