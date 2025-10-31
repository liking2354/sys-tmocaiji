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
    <!-- 现代化导航栏 -->
    <nav class="navbar navbar-expand-md fixed-top" id="navbar">
        <div class="container-fluid">
            <!-- 品牌 -->
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <i class="fas fa-cloud"></i>
                <span>TMO云迁移</span>
            </a>

            <!-- 搜索框 (中等屏幕及以上) -->
            <div class="navbar-search d-none d-md-flex">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="搜索..." id="navbar-search-input">
            </div>

            <!-- 右侧菜单 -->
            <div class="navbar-nav ml-auto d-flex align-items-center">
                <!-- 通知中心 -->
                <div class="navbar-notifications nav-item" id="navbar-notifications">
                    <a class="nav-link" href="javascript:void(0);">
                        <i class="fas fa-bell"></i>
                        <span class="badge">3</span>
                    </a>
                </div>

                <!-- 用户菜单 -->
                @guest
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>登录</span>
                        </a>
                    </li>
                @else
                    <li class="nav-item dropdown">
                        <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                            <div class="navbar-user">
                                <div class="navbar-user-avatar">
                                    {{ substr(Auth::user()->username, 0, 1) }}
                                </div>
                                <span class="navbar-user-name d-none d-md-inline">{{ Auth::user()->username }}</span>
                            </div>
                        </a>

                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                            <a class="dropdown-item" href="javascript:void(0);">
                                <i class="fas fa-user-circle"></i>
                                <span>个人资料</span>
                            </a>
                            <a class="dropdown-item" href="javascript:void(0);">
                                <i class="fas fa-cog"></i>
                                <span>设置</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ route('logout') }}"
                               onclick="event.preventDefault();
                                         document.getElementById('logout-form').submit();">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>退出登录</span>
                            </a>

                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </div>
                    </li>
                @endguest
            </div>

            <!-- 移动菜单切换按钮 -->
            <button class="navbar-toggler d-md-none" type="button" id="navbar-toggler">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>

    <!-- 主容器 -->
    <div class="main-container">
        @auth
            <!-- 现代化侧边栏 -->
            <aside class="sidebar" id="sidebar">
                <div class="sidebar-toggle" id="sidebar-toggle">
                    <i class="fas fa-chevron-left" id="toggle-icon"></i>
                </div>
                <nav class="sidebar-nav">
                    <ul class="nav">
                        <!-- 仪表盘 -->
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>仪表盘</span>
                            </a>
                        </li>
                        
                        <!-- 基础设施菜单 -->
                        <li class="nav-item">
                            <a class="nav-link sidebar-submenu-toggle {{ request()->routeIs(['server-groups.*', 'servers.*', 'collectors.*', 'collection-tasks.*', 'collection-history.*', 'data.cleanup.*']) ? 'active' : '' }}" href="javascript:void(0);">
                                <i class="fas fa-cloud-download-alt"></i>
                                <span>基础设施</span>
                                <i class="fas fa-chevron-down submenu-icon"></i>
                            </a>
                            <ul class="sidebar-submenu{{ !request()->routeIs(['server-groups.*', 'servers.*', 'collectors.*', 'collection-tasks.*', 'collection-history.*', 'data.cleanup.*']) ? ' collapsed' : '' }}">
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('server-groups.*') ? 'active' : '' }}" href="{{ route('server-groups.index') }}">
                                        <i class="fas fa-layer-group"></i>
                                        <span>服务器分组</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('servers.*') ? 'active' : '' }}" href="{{ route('servers.index') }}">
                                        <i class="fas fa-server"></i>
                                        <span>服务器管理</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('collectors.*') ? 'active' : '' }}" href="{{ route('collectors.index') }}">
                                        <i class="fas fa-plug"></i>
                                        <span>采集组件</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('collection-tasks.*') ? 'active' : '' }}" href="{{ route('collection-tasks.index') }}">
                                        <i class="fas fa-tasks"></i>
                                        <span>采集任务</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('collection-history.*') ? 'active' : '' }}" href="{{ route('collection-history.index') }}">
                                        <i class="fas fa-history"></i>
                                        <span>采集历史</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('data.cleanup.*') ? 'active' : '' }}" href="{{ route('data.cleanup.form') }}">
                                        <i class="fas fa-broom"></i>
                                        <span>数据清理</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        
                        <!-- 配置管理菜单 -->
                        <li class="nav-item">
                            <a class="nav-link sidebar-submenu-toggle {{ request()->routeIs('system-change.*') ? 'active' : '' }}" href="javascript:void(0);">
                                <i class="fas fa-cogs"></i>
                                <span>配置管理</span>
                                <i class="fas fa-chevron-down submenu-icon"></i>
                            </a>
                            <ul class="sidebar-submenu{{ !request()->routeIs('system-change.*') ? ' collapsed' : '' }}">
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('system-change.templates.*') ? 'active' : '' }}" href="{{ route('system-change.templates.index') }}">
                                        <i class="fas fa-file-code"></i>
                                        <span>配置模板</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('system-change.tasks.*') ? 'active' : '' }}" href="{{ route('system-change.tasks.index') }}">
                                        <i class="fas fa-tasks"></i>
                                        <span>配置任务</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        
                        <!-- 系统管理菜单 -->
                        <li class="nav-item">
                            <a class="nav-link sidebar-submenu-toggle {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="javascript:void(0);">
                                <i class="fas fa-cogs"></i>
                                <span>系统管理</span>
                                <i class="fas fa-chevron-down submenu-icon"></i>
                            </a>
                            <ul class="sidebar-submenu{{ !request()->routeIs('admin.*') ? ' collapsed' : '' }}">
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                                        <i class="fas fa-users"></i>
                                        <span>用户管理</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}" href="{{ route('admin.roles.index') }}">
                                        <i class="fas fa-user-tag"></i>
                                        <span>角色管理</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}" href="{{ route('admin.permissions.index') }}">
                                        <i class="fas fa-key"></i>
                                        <span>权限管理</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('admin.operation-logs.*') ? 'active' : '' }}" href="{{ route('admin.operation-logs.index') }}">
                                        <i class="fas fa-list-alt"></i>
                                        <span>操作日志</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </nav>
            </aside>

            <!-- 主内容区 -->
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
            <!-- 未认证用户 - 全宽布局 -->
            <main class="main-content main-content-full" id="main-content">
                @yield('content')
            </main>
        @endauth
    </div>

    <!-- Scripts -->
    <script src="{{ asset('assets/js/vendor/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/vendor/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/vendor/toastr.min.js') }}"></script>
    
    <!-- 公共脚本 -->
    <script src="{{ asset('assets/js/common/utils.js') }}"></script>
    <script src="{{ asset('assets/js/common/notifications.js') }}"></script>
    <script src="{{ asset('assets/js/common/api.js') }}"></script>
    <script src="{{ asset('assets/js/common/layout.js') }}"></script>
    
    <!-- 现代化导航栏和侧边栏脚本 -->
    <script src="{{ asset('assets/js/common/navbar-modern.js') }}"></script>
    <script src="{{ asset('assets/js/common/sidebar-modern.js') }}"></script>
    
    <!-- 主脚本 -->
    <script src="{{ asset('assets/js/main.js') }}"></script>
    
    @yield('scripts')
    @stack('scripts')
</body>
</html>