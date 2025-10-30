<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'TMO云迁移')</title>
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Styles -->
    <link href="{{ asset('assets/css/main.css') }}" rel="stylesheet">
    @yield('styles')
    @stack('styles')
</head>
<body>
    <nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('dashboard') }}">TMO云迁移</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <!-- Left Side Of Navbar -->
                <ul class="navbar-nav mr-auto">
                </ul>

                <!-- Right Side Of Navbar -->
                <ul class="navbar-nav ml-auto">
                    @guest
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">登录</a>
                        </li>
                    @else
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                {{ Auth::user()->username }}
                            </a>

                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                   onclick="event.preventDefault();
                                                 document.getElementById('logout-form').submit();">
                                    退出登录
                                </a>

                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </div>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            @auth
                <div class="col-md-2 sidebar py-3" id="sidebar">
                    <div class="sidebar-toggle" id="sidebar-toggle">
                        <i class="fas fa-chevron-left" id="toggle-icon"></i>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                                <i class="fas fa-tachometer-alt mr-2"></i> 仪表盘
                            </a>
                        </li>
                        
                        <!-- 系统采集菜单 -->
                        <li class="nav-item">
                            <a class="nav-link sidebar-submenu-toggle {{ request()->routeIs(['server-groups.*', 'servers.*', 'collectors.*', 'collection-tasks.*', 'collection-history.*', 'data.cleanup.*']) ? 'active' : '' }}" href="javascript:void(0);">
                                <i class="fas fa-cloud-download-alt mr-2"></i> 基础设施
                                <i class="fas fa-chevron-{{ request()->routeIs(['server-groups.*', 'servers.*', 'collectors.*', 'collection-tasks.*', 'collection-history.*', 'data.cleanup.*']) ? 'up' : 'down' }} float-right mt-1"></i>
                            </a>
                            <ul class="sidebar-submenu" style="display: {{ request()->routeIs(['server-groups.*', 'servers.*', 'collectors.*', 'collection-tasks.*', 'collection-history.*', 'data.cleanup.*']) ? 'block' : 'none' }};">
                                <li class="nav-item">
                                    <a class="nav-link pl-4 {{ request()->routeIs('server-groups.*') ? 'active' : '' }}" href="{{ route('server-groups.index') }}">
                                        <i class="fas fa-layer-group mr-2"></i> 服务器分组
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link pl-4 {{ request()->routeIs('servers.*') ? 'active' : '' }}" href="{{ route('servers.index') }}">
                                        <i class="fas fa-server mr-2"></i> 服务器管理
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link pl-4 {{ request()->routeIs('collectors.*') ? 'active' : '' }}" href="{{ route('collectors.index') }}">
                                        <i class="fas fa-plug mr-2"></i> 采集组件
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link pl-4 {{ request()->routeIs('collection-tasks.*') ? 'active' : '' }}" href="{{ route('collection-tasks.index') }}">
                                        <i class="fas fa-tasks mr-2"></i> 采集任务
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link pl-4 {{ request()->routeIs('collection-history.*') ? 'active' : '' }}" href="{{ route('collection-history.index') }}">
                                        <i class="fas fa-history mr-2"></i> 采集历史
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link pl-4 {{ request()->routeIs('data.cleanup.*') ? 'active' : '' }}" href="{{ route('data.cleanup.form') }}">
                                        <i class="fas fa-broom mr-2"></i> 数据清理
                                    </a>
                                </li>
                            </ul>
                        </li>
                        
                        <!-- 配置管理菜单 -->
                        <li class="nav-item">
                            <a class="nav-link sidebar-submenu-toggle {{ request()->routeIs('system-change.*') ? 'active' : '' }}" href="javascript:void(0);">
                                <i class="fas fa-cogs mr-2"></i> 配置管理
                                <i class="fas fa-chevron-down float-right mt-1"></i>
                            </a>
                            <ul class="sidebar-submenu" style="display: {{ request()->routeIs('system-change.*') ? 'block' : 'none' }};">
                                <li class="nav-item">
                                    <a class="nav-link pl-4 {{ request()->routeIs('system-change.templates.*') ? 'active' : '' }}" href="{{ route('system-change.templates.index') }}">
                                        <i class="fas fa-file-code mr-2"></i> 配置模板
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link pl-4 {{ request()->routeIs('system-change.tasks.*') ? 'active' : '' }}" href="{{ route('system-change.tasks.index') }}">
                                        <i class="fas fa-tasks mr-2"></i> 配置任务
                                    </a>
                                </li>
                            </ul>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link sidebar-submenu-toggle {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="javascript:void(0);">
                                <i class="fas fa-cogs mr-2"></i> 系统管理
                                <i class="fas fa-chevron-down float-right mt-1"></i>
                            </a>
                            <ul class="sidebar-submenu" style="display: {{ request()->routeIs('admin.*') ? 'block' : 'none' }};">
                                <li class="nav-item">
                                    <a class="nav-link pl-4 {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                                        <i class="fas fa-users mr-2"></i> 用户管理
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link pl-4 {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}" href="{{ route('admin.roles.index') }}">
                                        <i class="fas fa-user-tag mr-2"></i> 角色管理
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link pl-4 {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}" href="{{ route('admin.permissions.index') }}">
                                        <i class="fas fa-key mr-2"></i> 权限管理
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link pl-4 {{ request()->routeIs('admin.operation-logs.*') ? 'active' : '' }}" href="{{ route('admin.operation-logs.index') }}">
                                        <i class="fas fa-list-alt mr-2"></i> 操作日志
                                    </a>
                                </li>
                                <li class="nav-item">
                        </li>
                    </ul>
                </div>
                <main class="main-content" id="main-content">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @yield('content')
                </main>
            @else
                <main class="col-md-12">
                    @yield('content')
                </main>
            @endauth
        </div>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/toastr.min.js') }}"></script>
    
    <!-- 公共脚本 -->
    <script src="{{ asset('assets/js/common/utils.js') }}"></script>
    <script src="{{ asset('assets/js/common/notifications.js') }}"></script>
    <script src="{{ asset('assets/js/common/api.js') }}"></script>
    <script src="{{ asset('assets/js/common/layout.js') }}"></script>
    
    <!-- 主脚本 -->
    <script src="{{ asset('assets/js/main.js') }}"></script>
    
    @yield('scripts')
    @stack('scripts')
</body>
</html>