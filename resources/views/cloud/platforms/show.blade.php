@extends('layouts.app')

@section('title', '查看云平台')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">查看云平台 - {{ $platform->name }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('cloud.platforms.edit', $platform) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> 编辑
                        </a>
                        <a href="{{ route('cloud.platforms.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> 返回列表
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- 基本信息 -->
                        <div class="col-md-6">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> 基本信息</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="font-weight-bold" width="30%">平台名称：</td>
                                            <td>{{ $platform->name }}</td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">平台类型：</td>
                                            <td>
                                                @switch($platform->platform_type)
                                                    @case('huawei')
                                                        <span class="badge badge-info">华为云</span>
                                                        @break
                                                    @case('alibaba')
                                                        <span class="badge badge-warning">阿里云</span>
                                                        @break
                                                    @case('tencent')
                                                        <span class="badge badge-success">腾讯云</span>
                                                        @break
                                                    @default
                                                        <span class="badge badge-secondary">{{ $platform->platform_type }}</span>
                                                @endswitch
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">默认区域：</td>
                                            <td>
                                                <code>{{ $platform->region }}</code>
                                                @if($platform->platform_type === 'huawei' && $platform->other_config)
                                                    @php
                                                        $config = json_decode($platform->other_config, true);
                                                        $regionName = $config['project_ids'][$platform->region]['region_name'] ?? '';
                                                    @endphp
                                                    @if($regionName)
                                                        <small class="text-muted">({{ $regionName }})</small>
                                                    @endif
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">状态：</td>
                                            <td>
                                                @if($platform->status === 'active')
                                                    <span class="badge badge-success">启用</span>
                                                @else
                                                    <span class="badge badge-danger">禁用</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">创建时间：</td>
                                            <td>{{ $platform->created_at->format('Y-m-d H:i:s') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">更新时间：</td>
                                            <td>{{ $platform->updated_at->format('Y-m-d H:i:s') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">创建者：</td>
                                            <td>{{ $platform->user->name ?? '未知' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- 认证信息 -->
                        <div class="col-md-6">
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0"><i class="fas fa-key"></i> 认证信息</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="font-weight-bold" width="30%">Access Key ID：</td>
                                            <td>
                                                <code id="access-key-display">{{ substr($platform->access_key_id, 0, 8) }}****{{ substr($platform->access_key_id, -4) }}</code>
                                                <button type="button" class="btn btn-sm btn-outline-secondary ml-2" onclick="toggleAccessKey()">
                                                    <i class="fas fa-eye" id="access-key-icon"></i>
                                                </button>
                                                <input type="hidden" id="full-access-key" value="{{ $platform->access_key_id }}">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">Access Key Secret：</td>
                                            <td>
                                                <code>**********************</code>
                                                <small class="text-muted d-block">出于安全考虑，密钥不显示</small>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <!-- 连接测试 -->
                            <div class="card border-info mt-3">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0"><i class="fas fa-plug"></i> 连接测试</h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">测试当前配置是否能正常连接到云平台。</p>
                                    <button type="button" class="btn btn-info" id="testConnectionBtn" onclick="testConnection()">
                                        <i class="fas fa-plug"></i> 测试连接
                                    </button>
                                    <div id="testResult" class="mt-3" style="display: none;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 其他配置 -->
                    @if($platform->other_config)
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-cogs"></i> 其他配置</h5>
                                </div>
                                <div class="card-body">
                                    @if($platform->platform_type === 'huawei')
                                        @php
                                            $config = json_decode($platform->other_config, true);
                                        @endphp
                                        @if(isset($config['project_ids']) && is_array($config['project_ids']))
                                            <h6>华为云Project ID配置：</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>区域代码</th>
                                                            <th>区域名称</th>
                                                            <th>Project ID</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($config['project_ids'] as $regionCode => $regionData)
                                                            <tr>
                                                                <td><code>{{ $regionCode }}</code></td>
                                                                <td>{{ is_array($regionData) ? ($regionData['region_name'] ?? '未知') : '未知' }}</td>
                                                                <td><code>{{ is_array($regionData) ? ($regionData['project_id'] ?? '未配置') : $regionData }}</code></td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <h6>原始JSON配置：</h6>
                                            <pre class="bg-light p-3 rounded"><code>{{ json_encode(json_decode($platform->other_config), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                        @endif
                                    @else
                                        <h6>JSON配置：</h6>
                                        <pre class="bg-light p-3 rounded"><code>{{ json_encode(json_decode($platform->other_config), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- 操作历史 -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card border-secondary">
                                <div class="card-header bg-secondary text-white">
                                    <h5 class="mb-0"><i class="fas fa-history"></i> 操作记录</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="info-box bg-info">
                                                <span class="info-box-icon"><i class="fas fa-server"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">关联资源数</span>
                                                    <span class="info-box-number">{{ $platform->resources()->count() }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-box bg-success">
                                                <span class="info-box-icon"><i class="fas fa-sync"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">最后同步</span>
                                                    <span class="info-box-number">
                                                        @if($platform->resources()->exists())
                                                            {{ $platform->resources()->latest('updated_at')->first()->updated_at->diffForHumans() }}
                                                        @else
                                                            未同步
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-box bg-warning">
                                                <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">连接状态</span>
                                                    <span class="info-box-number" id="connection-status">未测试</span>
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
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // 页面加载完成后自动测试连接
    setTimeout(function() {
        testConnection(true); // 静默测试
    }, 1000);
});

// 切换Access Key显示
function toggleAccessKey() {
    const display = $('#access-key-display');
    const icon = $('#access-key-icon');
    const fullKey = $('#full-access-key').val();
    
    if (icon.hasClass('fa-eye')) {
        // 显示完整密钥
        display.text(fullKey);
        icon.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
        // 隐藏密钥
        const maskedKey = fullKey.substr(0, 8) + '****' + fullKey.substr(-4);
        display.text(maskedKey);
        icon.removeClass('fa-eye-slash').addClass('fa-eye');
    }
}

// 测试连接
function testConnection(silent = false) {
    const btn = $('#testConnectionBtn');
    const statusElement = $('#connection-status');
    
    if (!silent) {
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 测试中...');
    }
    
    statusElement.text('测试中...');

    $.ajax({
        url: '{{ route("cloud.platforms.test-connection", $platform) }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(res) {
            if (res.success) {
                statusElement.text('连接正常').removeClass('text-danger').addClass('text-success');
                if (!silent) {
                    toastr.success(res.message || '连接成功');
                }
            } else {
                statusElement.text('连接失败').removeClass('text-success').addClass('text-danger');
                if (!silent) {
                    toastr.error(res.message || '连接失败');
                }
            }
        },
        error: function(xhr) {
            statusElement.text('连接异常').removeClass('text-success').addClass('text-danger');
            if (!silent) {
                const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '连接测试失败';
                toastr.error(msg);
            }
        },
        complete: function() {
            if (!silent) {
                btn.prop('disabled', false).html('<i class="fas fa-plug"></i> 测试连接');
            }
        }
    });
}
</script>
@endsection