<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TMO云迁移系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        .feature-card {
            transition: transform 0.3s ease;
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .feature-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-cloud mr-2"></i>TMO云迁移系统
            </a>
            <div class="navbar-nav ms-auto">
                @auth
                    <a class="nav-link" href="{{ route('dashboard') }}">
                        <i class="fas fa-tachometer-alt mr-1"></i>控制台
                    </a>
                @else
                    <a class="nav-link" href="{{ route('login') }}">
                        <i class="fas fa-sign-in-alt mr-1"></i>登录
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- 英雄区域 -->
    <section class="hero-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <h1 class="display-4 fw-bold mb-4">TMO云迁移管理系统</h1>
                    <p class="lead mb-4">统一管理多云平台资源，简化云迁移流程，提升运维效率</p>
                    <div class="d-flex justify-content-center gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-light btn-lg">
                                <i class="fas fa-tachometer-alt mr-2"></i>进入控制台
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-light btn-lg">
                                <i class="fas fa-sign-in-alt mr-2"></i>立即登录
                            </a>
                        @endauth
                        <a href="/test/cloud-resources" class="btn btn-secondarylight btn-lg">
                            <i class="fas fa-play mr-2"></i>功能演示
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 功能特性 -->
    <section class="py-5">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="display-5 fw-bold">核心功能</h2>
                    <p class="lead text-muted">全面的云资源管理解决方案</p>
                </div>
            </div>
            
            <div class="row g-4">
                <!-- 云资源管理 -->
                <div class="col-md-4">
                    <div class="card feature-card border-0 shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="fas fa-cloud"></i>
                            </div>
                            <h5 class="card-title">云资源管理</h5>
                            <p class="card-text text-muted">
                                统一管理阿里云、腾讯云、华为云等多个云平台的资源，
                                支持资源查询、监控、同步等功能。
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 服务器管理 -->
                <div class="col-md-4">
                    <div class="card feature-card border-0 shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="fas fa-server"></i>
                            </div>
                            <h5 class="card-title">服务器管理</h5>
                            <p class="card-text text-muted">
                                集中管理物理服务器和虚拟机，支持批量操作、
                                远程连接、系统信息采集等功能。
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 配置管理 -->
                <div class="col-md-4">
                    <div class="card feature-card border-0 shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="fas fa-cogs"></i>
                            </div>
                            <h5 class="card-title">配置管理</h5>
                            <p class="card-text text-muted">
                                基于模板的配置管理，支持批量配置变更、
                                版本控制、回滚等功能。
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 数据采集 -->
                <div class="col-md-4">
                    <div class="card feature-card border-0 shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="fas fa-download"></i>
                            </div>
                            <h5 class="card-title">数据采集</h5>
                            <p class="card-text text-muted">
                                自动化数据采集任务，支持定时采集、
                                实时监控、历史数据查询等功能。
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 任务管理 -->
                <div class="col-md-4">
                    <div class="card feature-card border-0 shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <h5 class="card-title">任务管理</h5>
                            <p class="card-text text-muted">
                                灵活的任务调度系统，支持批量任务执行、
                                进度监控、失败重试等功能。
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 系统管理 -->
                <div class="col-md-4">
                    <div class="card feature-card border-0 shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="fas fa-users-cog"></i>
                            </div>
                            <h5 class="card-title">系统管理</h5>
                            <p class="card-text text-muted">
                                完善的用户权限管理，支持角色分配、
                                操作日志、系统监控等功能。
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 技术特性 -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="display-5 fw-bold">技术特性</h2>
                    <p class="lead text-muted">基于现代化技术栈构建</p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-3 text-center">
                    <div class="mb-3">
                        <i class="fab fa-laravel text-danger" style="font-size: 3rem;"></i>
                    </div>
                    <h5>Laravel 9</h5>
                    <p class="text-muted">现代化PHP框架</p>
                </div>
                <div class="col-md-3 text-center">
                    <div class="mb-3">
                        <i class="fab fa-bootstrap text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h5>Bootstrap 5</h5>
                    <p class="text-muted">响应式UI框架</p>
                </div>
                <div class="col-md-3 text-center">
                    <div class="mb-3">
                        <i class="fas fa-database text-info" style="font-size: 3rem;"></i>
                    </div>
                    <h5>MySQL</h5>
                    <p class="text-muted">可靠的数据存储</p>
                </div>
                <div class="col-md-3 text-center">
                    <div class="mb-3">
                        <i class="fab fa-docker text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h5>容器化部署</h5>
                    <p class="text-muted">支持Docker部署</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 页脚 -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>TMO云迁移系统</h5>
                    <p class="text-muted">专业的云资源管理解决方案</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">
                        © {{ date('Y') }} TMO云迁移系统. 保留所有权利.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>