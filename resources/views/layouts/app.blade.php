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
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/all.min.css') }}" rel="stylesheet">
    @yield('styles')
    
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #1abc9c;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 70px; /* 为固定导航栏留出空间 */
            font-size: 14px; /* 减小全局字体大小 */
        }
        
        .navbar {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, var(--secondary-color), #34495e);
        }
        
        .navbar-brand {
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .sidebar {
            position: fixed;
            top: 70px; /* 导航栏高度 */
            left: 0;
            height: calc(100vh - 70px);
            background-color: white;
            box-shadow: var(--card-shadow);
            border-radius: 0 0 5px 0;
            padding-top: 1rem;
            transition: all 0.3s ease;
            z-index: 1000;
            width: 250px;
        }
        
        .sidebar-collapsed {
            left: -220px;
        }
        
        .sidebar-toggle {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .main-content {
            transition: all 0.3s ease;
            margin-left: 250px;
        }
        
        .main-content-expanded {
            margin-left: 30px;
        }
        
        .sidebar .nav-link {
            color: #555;
            border-radius: 5px;
            margin: 3px 10px;
            transition: all 0.3s ease;
            padding: 10px 15px;
        }
        
        .sidebar .nav-link:hover {
            color: var(--primary-color);
            background-color: rgba(52, 152, 219, 0.1);
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            color: white;
            background-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .main-content {
            padding: 25px;
        }
        
        .card {
            border: none;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease;
            margin-bottom: 20px;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
            border-radius: 8px 8px 0 0 !important;
        }
        
        .btn {
            border-radius: 5px;
            padding: 8px 16px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            box-shadow: var(--card-shadow);
        }
        
        /* 响应式优化 */
        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
                margin-bottom: 20px;
            }
        }
    </style>
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
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('server-groups.*') ? 'active' : '' }}" href="{{ route('server-groups.index') }}">
                                <i class="fas fa-layer-group mr-2"></i> 服务器分组
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('servers.*') ? 'active' : '' }}" href="{{ route('servers.index') }}">
                                <i class="fas fa-server mr-2"></i> 服务器管理
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('collectors.*') ? 'active' : '' }}" href="{{ route('collectors.index') }}">
                                <i class="fas fa-plug mr-2"></i> 采集组件
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('collection-tasks.*') ? 'active' : '' }}" href="{{ route('collection-tasks.index') }}">
                                <i class="fas fa-tasks mr-2"></i> 采集任务
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('collection-history.*') ? 'active' : '' }}" href="{{ route('collection-history.index') }}">
                                <i class="fas fa-history mr-2"></i> 采集历史
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('data.cleanup.*') ? 'active' : '' }}" href="{{ route('data.cleanup.form') }}">
                                <i class="fas fa-broom mr-2"></i> 数据清理
                            </a>
                        </li>
                    </ul>
                </div>
                <main class="col-md-10 main-content" id="main-content">
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
    <script src="{{ asset('assets/js/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // 侧边栏展开/隐藏功能
            $('#sidebar-toggle').click(function() {
                $('#sidebar').toggleClass('sidebar-collapsed');
                $('#main-content').toggleClass('main-content-expanded');
                
                // 切换图标
                if ($('#sidebar').hasClass('sidebar-collapsed')) {
                    $('#toggle-icon').removeClass('fa-chevron-left').addClass('fa-chevron-right');
                } else {
                    $('#toggle-icon').removeClass('fa-chevron-right').addClass('fa-chevron-left');
                }
                
                // 保存状态到本地存储
                localStorage.setItem('sidebar-collapsed', $('#sidebar').hasClass('sidebar-collapsed'));
            });
            
            // 页面加载时恢复侧边栏状态
            if (localStorage.getItem('sidebar-collapsed') === 'true') {
                $('#sidebar').addClass('sidebar-collapsed');
                $('#main-content').addClass('main-content-expanded');
                $('#toggle-icon').removeClass('fa-chevron-left').addClass('fa-chevron-right');
            }
        });
    </script>
    @yield('scripts')
</body>
</html>