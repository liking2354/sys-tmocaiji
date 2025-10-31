@extends('layouts.auth')

@section('title', '登录 - TMO云迁移')

@section('content')
<div class="login-page">
    <div class="login-container">
        <!-- 左侧装饰 -->
        <div class="login-decoration login-decoration-left">
            <div class="decoration-circle decoration-circle-1"></div>
            <div class="decoration-circle decoration-circle-2"></div>
            <div class="decoration-circle decoration-circle-3"></div>
        </div>

        <!-- 右侧装饰 -->
        <div class="login-decoration login-decoration-right">
            <div class="decoration-circle decoration-circle-4"></div>
            <div class="decoration-circle decoration-circle-5"></div>
        </div>

        <!-- 登录卡片 -->
        <div class="login-card">
            <!-- 卡片头部 -->
            <div class="login-card-header">
                <div class="login-logo">
                    <i class="fas fa-cloud"></i>
                </div>
                <h2 class="login-title">TMO云迁移</h2>
                <p class="login-subtitle">服务器管理与数据采集系统</p>
            </div>

            <!-- 卡片主体 -->
            <div class="login-card-body">
                <div class="login-welcome">
                    <h3>欢迎回来</h3>
                    <p>请输入您的凭证以继续</p>
                </div>

                <form method="POST" action="{{ route('login') }}" class="login-form">
                    @csrf

                    <!-- 用户名输入 -->
                    <div class="form-group login-form-group">
                        <label for="username" class="form-label">
                            <i class="fas fa-user"></i>
                            <span>用户名</span>
                        </label>
                        <input id="username" type="text" placeholder="输入用户名" 
                               class="form-control login-input @error('username') is-invalid @enderror" 
                               name="username" value="{{ old('username') }}" required autocomplete="username" autofocus>
                        @error('username')
                            <div class="invalid-feedback">
                                <i class="fas fa-exclamation-circle"></i> {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <!-- 密码输入 -->
                    <div class="form-group login-form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i>
                            <span>密码</span>
                        </label>
                        <input id="password" type="password" placeholder="输入密码" 
                               class="form-control login-input @error('password') is-invalid @enderror" 
                               name="password" required autocomplete="current-password">
                        @error('password')
                            <div class="invalid-feedback">
                                <i class="fas fa-exclamation-circle"></i> {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <!-- 记住我 -->
                    <div class="login-remember">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label" for="remember">
                                记住我
                            </label>
                        </div>
                    </div>

                    <!-- 登录按钮 -->
                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>登录</span>
                    </button>
                </form>

                <!-- 底部链接 -->
                <div class="login-footer">
                    <p class="text-muted">
                        <small>&copy; {{ date('Y') }} TMO云迁移系统 | 版本 1.0</small>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection