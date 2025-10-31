@extends('layouts.app')

@section('title', '登录 - TMO云迁移')



@section('content')
<div class="login-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card card-primary shadow-lg border-0">
                    <div class="card-header bg-primary text-white border-0">
                        <h5 class="mb-0 text-center">
                            <i class="fas fa-server mr-2"></i>TMO云迁移系统
                        </h5>
                    </div>

                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="display-4 text-primary mb-3">
                                <i class="fas fa-sign-in-alt"></i>
                            </div>
                            <h4>用户登录</h4>
                            <p class="text-muted">请输入您的凭证以继续</p>
                        </div>
                        
                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <div class="form-group">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user mr-1"></i>用户名
                                </label>
                                <input id="username" type="text" placeholder="请输入用户名" class="form-control form-control-lg @error('username') is-invalid @enderror" name="username" value="{{ old('username') }}" required autocomplete="username" autofocus>
                                @error('username')
                                    <div class="invalid-feedback d-block mt-1">
                                        <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock mr-1"></i>密码
                                </label>
                                <input id="password" type="password" placeholder="请输入密码" class="form-control form-control-lg @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
                                @error('password')
                                    <div class="invalid-feedback d-block mt-1">
                                        <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="remember">
                                        记住我
                                    </label>
                                </div>
                            </div>

                            <div class="form-group mb-0">
                                <button type="submit" class="btn btn-primary btn-lg btn-block shadow-sm">
                                    <i class="fas fa-sign-in-alt mr-2"></i>登录
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <small class="text-muted">&copy; {{ date('Y') }} TMO云迁移系统</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection