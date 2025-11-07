@extends('layouts.app')

@section('title', '个人资料')

@section('content')
<div class="container-fluid">
    <!-- 页面标题 -->
    <div class="mb-4">
        <h1 class="mb-0">
            <i class="fas fa-user-circle text-primary"></i> 个人资料
        </h1>
        <small class="text-muted">管理您的个人信息和头像</small>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- 头像上传 -->
        <div class="col-lg-4">
            <div class="card card-light-blue shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-image"></i> 头像设置
                    </h5>
                </div>
                <div class="card-body text-center">
                    <div class="avatar-upload-container">
                        <div class="avatar-preview-wrapper mb-3">
                            @if($user->avatar)
                                <img src="{{ asset('storage/' . $user->avatar) }}" alt="头像" class="avatar-preview" id="avatarPreview">
                            @else
                                <div class="avatar-placeholder" id="avatarPlaceholder">
                                    <span class="avatar-initials">{{ substr($user->username, 0, 1) }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <form id="avatarForm" enctype="multipart/form-data">
                                @csrf
                                <input type="file" name="avatar" id="avatarInput" class="d-none" accept="image/*">
                                <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('avatarInput').click()">
                                    <i class="fas fa-upload"></i> 上传头像
                                </button>
                                @if($user->avatar)
                                    <button type="button" class="btn btn-danger btn-sm" id="deleteAvatarBtn">
                                        <i class="fas fa-trash"></i> 删除头像
                                    </button>
                                @endif
                            </form>
                        </div>

                        <p class="small text-muted mb-0">
                            支持 JPG、PNG、GIF 格式<br>
                            文件大小不超过 2MB
                        </p>
                    </div>
                </div>
            </div>

            <!-- 账户信息 -->
            <div class="card card-light-green shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle"></i> 账户信息
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <small class="text-muted">注册时间</small>
                        <p class="mb-0 fw-semibold">{{ $user->created_at->format('Y-m-d H:i:s') }}</p>
                    </div>
                    <hr>
                    <div class="mb-2">
                        <small class="text-muted">最后登录</small>
                        <p class="mb-0 fw-semibold">
                            {{ $user->last_login_time ? $user->last_login_time->format('Y-m-d H:i:s') : '从未登录' }}
                        </p>
                    </div>
                    <hr>
                    <div>
                        <small class="text-muted">账户状态</small>
                        <p class="mb-0">
                            @if($user->status == 1)
                                <span class="badge bg-success">正常</span>
                            @else
                                <span class="badge bg-danger">已禁用</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 基本信息编辑 -->
        <div class="col-lg-8">
            <div class="card card-light-blue shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-edit"></i> 基本信息
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- 用户名 -->
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="fas fa-user"></i> 用户名
                            </label>
                            <input type="text" 
                                   class="form-control @error('username') is-invalid @enderror" 
                                   id="username" 
                                   name="username" 
                                   value="{{ old('username', $user->username) }}" 
                                   required>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 邮箱 -->
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i> 邮箱地址
                            </label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $user->email) }}" 
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr class="my-4">

                        <!-- 修改密码 -->
                        <h6 class="mb-3">
                            <i class="fas fa-key"></i> 修改密码
                            <small class="text-muted">(可选，不修改请留空)</small>
                        </h6>

                        <!-- 当前密码 -->
                        <div class="mb-3">
                            <label for="current_password" class="form-label">当前密码</label>
                            <input type="password" 
                                   class="form-control @error('current_password') is-invalid @enderror" 
                                   id="current_password" 
                                   name="current_password">
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 新密码 -->
                        <div class="mb-3">
                            <label for="new_password" class="form-label">新密码</label>
                            <input type="password" 
                                   class="form-control @error('new_password') is-invalid @enderror" 
                                   id="new_password" 
                                   name="new_password">
                            @error('new_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">密码至少6位</small>
                        </div>

                        <!-- 确认新密码 -->
                        <div class="mb-3">
                            <label for="new_password_confirmation" class="form-label">确认新密码</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="new_password_confirmation" 
                                   name="new_password_confirmation">
                        </div>

                        <!-- 按钮 -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> 保存修改
                            </button>
                            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> 取消
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .avatar-upload-container {
        padding: 20px;
    }

    .avatar-preview-wrapper {
        position: relative;
        display: inline-block;
    }

    .avatar-preview,
    .avatar-placeholder {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #e5e7eb;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .avatar-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #0066cc, #004499);
        color: white;
    }

    .avatar-initials {
        font-size: 60px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .avatar-preview-wrapper::after {
        content: '';
        position: absolute;
        bottom: 10px;
        right: 10px;
        width: 30px;
        height: 30px;
        background-color: #10b981;
        border: 3px solid white;
        border-radius: 50%;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const avatarInput = document.getElementById('avatarInput');
        const avatarForm = document.getElementById('avatarForm');
        const deleteAvatarBtn = document.getElementById('deleteAvatarBtn');

        // 头像上传预览和提交
        if (avatarInput) {
            avatarInput.addEventListener('change', function() {
                const file = this.files[0];
                if (!file) return;

                // 验证文件类型
                if (!file.type.match('image.*')) {
                    alert('请选择图片文件');
                    return;
                }

                // 验证文件大小 (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('文件大小不能超过 2MB');
                    return;
                }

                // 预览图片
                const reader = new FileReader();
                reader.onload = function(e) {
                    let preview = document.getElementById('avatarPreview');
                    const placeholder = document.getElementById('avatarPlaceholder');
                    
                    if (!preview) {
                        preview = document.createElement('img');
                        preview.id = 'avatarPreview';
                        preview.className = 'avatar-preview';
                        if (placeholder) {
                            placeholder.parentNode.replaceChild(preview, placeholder);
                        }
                    }
                    
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(file);

                // 上传头像
                const formData = new FormData(avatarForm);
                
                fetch('{{ route("profile.upload-avatar") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 刷新页面以显示删除按钮和更新导航栏头像
                        location.reload();
                    } else {
                        alert(data.message || '上传失败');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('上传失败，请重试');
                });
            });
        }

        // 删除头像
        if (deleteAvatarBtn) {
            deleteAvatarBtn.addEventListener('click', function() {
                if (!confirm('确定要删除头像吗？')) {
                    return;
                }

                fetch('{{ route("profile.delete-avatar") }}', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || '删除失败');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('删除失败，请重试');
                });
            });
        }
    });
</script>
@endsection
