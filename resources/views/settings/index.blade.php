@extends('layouts.app')

@section('title', '用户设置 - 系统设置')

@section('content')
<div class="container-fluid">
    <!-- 页面标题 -->
    <div class="mb-4">
        <h1 class="mb-0">
            <i class="fas fa-cog text-primary"></i> 用户设置
        </h1>
        <small class="text-muted">自定义您的系统主题和界面风格</small>
    </div>

    <div class="row">
        <!-- 主题颜色设置 -->
        <div class="col-lg-8">
            <div class="card card-light-blue shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-palette"></i> 主题颜色
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('settings.update') }}" method="POST" id="settingsForm">
                        @csrf
                        @method('PUT')

                        <!-- 主题颜色选择 -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold mb-3">选择您喜欢的主题颜色</label>
                            <div class="row g-3">
                                @foreach($themeColors as $colorKey => $colorData)
                                    <div class="col-md-6 col-lg-4">
                                        <div class="theme-color-card" data-color="{{ $colorKey }}">
                                            <input type="radio" name="theme_color" value="{{ $colorKey }}" 
                                                   id="color_{{ $colorKey }}" 
                                                   {{ $user->theme_color === $colorKey ? 'checked' : '' }}
                                                   class="theme-radio">
                                            <label for="color_{{ $colorKey }}" class="theme-label">
                                                <div class="color-preview" style="background: linear-gradient(135deg, {{ $colorData['primary'] }}, {{ $colorData['light'] }});">
                                                    <i class="fas fa-check-circle"></i>
                                                </div>
                                                <div class="color-info">
                                                    <h6 class="mb-1">{{ $colorData['name'] }}</h6>
                                                    <small class="text-muted">{{ $colorData['description'] }}</small>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- 侧边栏风格选择 -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold mb-3">侧边栏风格</label>
                            <div class="row g-3">
                                @foreach($sidebarStyles as $styleKey => $styleData)
                                    <div class="col-md-6">
                                        <div class="sidebar-style-card">
                                            <input type="radio" name="sidebar_style" value="{{ $styleKey }}" 
                                                   id="style_{{ $styleKey }}" 
                                                   {{ $user->sidebar_style === $styleKey ? 'checked' : '' }}
                                                   class="sidebar-radio">
                                            <label for="style_{{ $styleKey }}" class="sidebar-label">
                                                <div class="style-preview" style="background-color: {{ $styleKey === 'light' ? '#ffffff' : '#1f2937' }};">
                                                    <div class="preview-item" style="background-color: {{ $styleKey === 'light' ? '#f3f4f6' : '#374151' }}; color: {{ $styleKey === 'light' ? '#111827' : '#f3f4f6' }};">
                                                        <i class="fas fa-bars"></i>
                                                    </div>
                                                </div>
                                                <div class="style-info">
                                                    <h6 class="mb-1">{{ $styleData['name'] }}</h6>
                                                    <small class="text-muted">{{ $styleData['description'] }}</small>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- 按钮 -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> 保存设置
                            </button>
                            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> 取消
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 预览卡片 -->
            <div class="card card-light-blue shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-eye"></i> 预览
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">以下是您选择的主题在系统中的效果预览：</p>
                    
                    <!-- 预览导航栏 -->
                    <div class="preview-navbar mb-4" id="previewNavbar">
                        <div class="preview-navbar-brand">
                            <i class="fas fa-cloud"></i>
                            <span>TMO云迁移</span>
                        </div>
                        <div class="preview-navbar-items">
                            <span class="preview-navbar-item">通知</span>
                            <span class="preview-navbar-item">用户菜单</span>
                        </div>
                    </div>

                    <!-- 预览卡片 -->
                    <div class="preview-card" id="previewCard">
                        <div class="preview-card-header">
                            <i class="fas fa-list"></i> 示例卡片
                        </div>
                        <div class="preview-card-body">
                            这是使用您选择的主题颜色的卡片示例。
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 侧边栏 - 快速帮助 -->
        <div class="col-lg-4">
            <div class="card card-light-green shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-lightbulb"></i> 提示
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="fw-semibold mb-2">主题颜色说明</h6>
                        <ul class="list-unstyled small text-muted">
                            <li><i class="fas fa-circle" style="color: #0066cc;"></i> <strong>蓝色</strong> - 专业、可信、默认主题</li>
                            <li><i class="fas fa-circle" style="color: #7c3aed;"></i> <strong>紫色</strong> - 创意、高级、优雅</li>
                            <li><i class="fas fa-circle" style="color: #10b981;"></i> <strong>绿色</strong> - 健康、成功、生机</li>
                            <li><i class="fas fa-circle" style="color: #f59e0b;"></i> <strong>橙色</strong> - 温暖、活力、注意</li>
                            <li><i class="fas fa-circle" style="color: #ec4899;"></i> <strong>粉色</strong> - 温柔、特殊、突出</li>
                            <li><i class="fas fa-circle" style="color: #06b6d4;"></i> <strong>青色</strong> - 清爽、现代、信息</li>
                        </ul>
                    </div>
                    <hr>
                    <div>
                        <h6 class="fw-semibold mb-2">侧边栏风格</h6>
                        <p class="small text-muted mb-0">
                            选择浅色或深色侧边栏风格，以适应您的工作环境和个人偏好。
                        </p>
                    </div>
                </div>
            </div>

            <!-- 其他设置 -->
            <div class="card card-light-orange shadow-sm mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle"></i> 其他设置
                    </h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-0">
                        更多设置选项（如语言、时区、通知等）将在后续版本中推出。
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* 主题颜色卡片样式 */
    .theme-color-card {
        position: relative;
    }

    .theme-radio {
        display: none;
    }

    .theme-label {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        background-color: #ffffff;
    }

    .theme-color-card input:checked + .theme-label {
        border-color: #0066cc;
        background-color: #f0f7ff;
        box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
    }

    .color-preview {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .theme-color-card input:checked + .theme-label .color-preview {
        opacity: 1;
    }

    .color-info {
        flex: 1;
    }

    .color-info h6 {
        margin: 0;
        font-weight: 600;
        color: #111827;
    }

    /* 侧边栏风格卡片样式 */
    .sidebar-style-card {
        position: relative;
    }

    .sidebar-radio {
        display: none;
    }

    .sidebar-label {
        display: flex;
        flex-direction: column;
        gap: 12px;
        padding: 12px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        background-color: #ffffff;
    }

    .sidebar-style-card input:checked + .sidebar-label {
        border-color: #0066cc;
        background-color: #f0f7ff;
        box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
    }

    .style-preview {
        width: 100%;
        height: 60px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .preview-item {
        width: 40px;
        height: 40px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .style-info {
        text-align: center;
    }

    .style-info h6 {
        margin: 0;
        font-weight: 600;
        color: #111827;
    }

    /* 预览样式 */
    .preview-navbar {
        background: linear-gradient(135deg, #0066cc, #004499);
        color: white;
        padding: 12px 16px;
        border-radius: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .preview-navbar-brand {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
    }

    .preview-navbar-items {
        display: flex;
        gap: 16px;
        font-size: 12px;
    }

    .preview-navbar-item {
        opacity: 0.8;
    }

    .preview-card {
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
    }

    .preview-card-header {
        background-color: #f0f7ff;
        color: #0066cc;
        padding: 12px 16px;
        font-weight: 600;
        border-bottom: 2px solid #0066cc;
    }

    .preview-card-body {
        padding: 16px;
        color: #6b7280;
    }

    /* 动态主题预览 */
    .preview-navbar.theme-purple {
        background: linear-gradient(135deg, #7c3aed, #6d28d9);
    }

    .preview-navbar.theme-green {
        background: linear-gradient(135deg, #10b981, #059669);
    }

    .preview-navbar.theme-orange {
        background: linear-gradient(135deg, #f59e0b, #d97706);
    }

    .preview-navbar.theme-pink {
        background: linear-gradient(135deg, #ec4899, #db2777);
    }

    .preview-navbar.theme-cyan {
        background: linear-gradient(135deg, #06b6d4, #0891b2);
    }

    .preview-card-header.theme-purple {
        background-color: #f5f3ff;
        color: #7c3aed;
        border-bottom-color: #7c3aed;
    }

    .preview-card-header.theme-green {
        background-color: #f0fdf4;
        color: #10b981;
        border-bottom-color: #10b981;
    }

    .preview-card-header.theme-orange {
        background-color: #fffbf0;
        color: #f59e0b;
        border-bottom-color: #f59e0b;
    }

    .preview-card-header.theme-pink {
        background-color: #fdf2f8;
        color: #ec4899;
        border-bottom-color: #ec4899;
    }

    .preview-card-header.theme-cyan {
        background-color: #ecfdf5;
        color: #06b6d4;
        border-bottom-color: #06b6d4;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const themeRadios = document.querySelectorAll('input[name="theme_color"]');
        const previewNavbar = document.getElementById('previewNavbar');
        const previewCardHeader = document.querySelector('.preview-card-header');
        const settingsForm = document.getElementById('settingsForm');

        function updatePreview() {
            const selectedColor = document.querySelector('input[name="theme_color"]:checked').value;
            
            // 移除所有主题类
            previewNavbar.classList.remove('theme-purple', 'theme-green', 'theme-orange', 'theme-pink', 'theme-cyan');
            previewCardHeader.classList.remove('theme-purple', 'theme-green', 'theme-orange', 'theme-pink', 'theme-cyan');
            
            // 添加选中的主题类
            if (selectedColor !== 'blue') {
                previewNavbar.classList.add('theme-' + selectedColor);
                previewCardHeader.classList.add('theme-' + selectedColor);
            }
        }

        themeRadios.forEach(radio => {
            radio.addEventListener('change', updatePreview);
        });

        // 表单提交处理
        if (settingsForm) {
            settingsForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const selectedTheme = document.querySelector('input[name="theme_color"]:checked').value;
                
                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // 立即应用新主题
                    if (typeof ThemeSwitcher !== 'undefined') {
                        ThemeSwitcher.applyTheme(selectedTheme);
                    }
                    
                    // 显示成功消息
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('保存失败，请重试');
                });
            });
        }

        // 初始化预览
        updatePreview();
    });
</script>
@endsection
