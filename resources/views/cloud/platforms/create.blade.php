@extends('layouts.app')

@section('title', '添加云平台')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">添加云平台</h3>
                    <div class="card-tools">
                        <a href="{{ route('cloud.platforms.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> 返回列表
                        </a>
                    </div>
                </div>
                <form action="{{ route('cloud.platforms.store') }}" method="POST" id="platformForm">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">平台名称 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name') }}" 
                                           placeholder="请输入平台名称，如：生产环境华为云">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="platform_type">平台类型 <span class="text-danger">*</span></label>
                                    <select class="form-control @error('platform_type') is-invalid @enderror" 
                                            id="platform_type" name="platform_type">
                                        <option value="">请选择平台类型</option>
                                        <option value="huawei" {{ old('platform_type') == 'huawei' ? 'selected' : '' }}>华为云</option>
                                        <option value="alibaba" {{ old('platform_type') == 'alibaba' ? 'selected' : '' }}>阿里云</option>
                                        <option value="tencent" {{ old('platform_type') == 'tencent' ? 'selected' : '' }}>腾讯云</option>
                                    </select>
                                    @error('platform_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="access_key_id">Access Key ID <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('access_key_id') is-invalid @enderror" 
                                           id="access_key_id" name="access_key_id" value="{{ old('access_key_id') }}" 
                                           placeholder="请输入Access Key ID">
                                    @error('access_key_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="access_key_secret">Access Key Secret <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control @error('access_key_secret') is-invalid @enderror" 
                                               id="access_key_secret" name="access_key_secret" value="{{ old('access_key_secret') }}" 
                                               placeholder="请输入Access Key Secret" autocomplete="new-password">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('access_key_secret')">
                                                <i class="fas fa-eye" id="access_key_secret_icon"></i>
                                            </button>
                                        </div>
                                        @error('access_key_secret')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="region">默认区域</label>
                                    <select class="form-control @error('region') is-invalid @enderror" 
                                            id="region" name="region">
                                        <option value="">请先选择平台类型</option>
                                    </select>
                                    @error('region')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">可选项，用作主要操作区域。如不选择，系统将使用第一个可用区域。</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status">状态</label>
                                    <select class="form-control @error('status') is-invalid @enderror" 
                                            id="status" name="status">
                                        <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>启用</option>
                                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>禁用</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="config">其他配置 <small class="text-muted">(JSON格式，可选)</small></label>
                                    <textarea class="form-control @error('config') is-invalid @enderror" 
                                              id="config" name="config" rows="4" 
                                              placeholder='{"timeout": 30, "retry": 3}'>{{ old('config') }}</textarea>
                                    @error('config')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">请输入有效的JSON格式配置，如超时时间、重试次数等</small>
                                </div>
                                
                                <!-- 华为云专用配置区域 -->
                                <div id="huawei-config" class="mt-3" style="display: none;">
                                    <div class="card border-info">
                                        <div class="card-header bg-info text-white">
                                            <h6 class="mb-0"><i class="fas fa-cloud"></i> 华为云区域配置</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-row">
                                                <div class="col-md-6">
                                                    <label for="huawei-region-select">选择区域</label>
                                                    <select id="huawei-region-select" class="form-control">
                                                        <option value="">请选择区域</option>
                                                    </select>
                                                    <small class="form-text text-muted">选择后将自动配置对应的Project ID</small>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>&nbsp;</label>
                                                    <div>
                                                        <button type="button" class="btn btn-info btn-block" onclick="applyHuaweiConfig()">
                                                            <i class="fas fa-magic"></i> 应用配置
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-3">
                                                <div class="alert alert-info">
                                                    <h6><i class="fas fa-info-circle"></i> 配置说明：</h6>
                                                    <ul class="mb-0">
                                                        <li>华为云需要配置Project ID才能正常访问资源</li>
                                                        <li>每个区域对应不同的Project ID</li>
                                                        <li>选择区域后点击"应用配置"将自动生成JSON配置</li>
                                                        <li>生成的配置会自动填入上方的"其他配置"字段</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 连接测试区域 -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-plug"></i> 连接测试
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted">在保存之前，建议先测试连接以确保配置正确。</p>
                                        <button type="button" class="btn btn-info" id="testConnectionBtn" disabled>
                                            <i class="fas fa-plug"></i> 测试连接
                                        </button>
                                        <div id="testResult" class="mt-3" style="display: none;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save"></i> 保存
                        </button>
                        <a href="{{ route('cloud.platforms.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> 取消
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // 平台类型变化时加载区域列表
    $('#platform_type').change(async function() {
        const platformType = $(this).val();
        const regionSelect = $('#region');
        const testBtn = $('#testConnectionBtn');
        
        regionSelect.html('<option value="">加载中...</option>');
        testBtn.prop('disabled', true);
        
        // 显示/隐藏华为云配置区域
        if (platformType === 'huawei') {
            $('#huawei-config').show();
            loadHuaweiRegions();
        } else {
            $('#huawei-config').hide();
        }
        
        if (!platformType) {
            regionSelect.html('<option value="">请先选择平台类型</option>');
            // 重置提示信息
            const helpText = regionSelect.siblings('.form-text');
            if (helpText.length) {
                helpText.removeClass('text-success text-warning text-danger')
                       .addClass('text-muted')
                       .text('选择平台类型后将自动加载可用区域');
            }
            return;
        }
        
        try {
            // 异步获取区域列表
            const regions = await getRegionsByPlatform(platformType);
            let options = '<option value="">请选择区域</option>';
            regions.forEach(region => {
                options += `<option value="${region.value}">${region.label}</option>`;
            });
            regionSelect.html(options);
        } catch (error) {
            console.error('加载区域列表失败:', error);
            regionSelect.html('<option value="">加载失败，请重试</option>');
            toastr.error('加载可用区列表失败，请重试');
        }
        
        // 启用测试连接按钮
        updateTestButtonState();
    });
    
    // 监听表单字段变化，更新测试按钮状态
    $('#access_key_id, #access_key_secret, #region').on('input change', function() {
        updateTestButtonState();
    });
    
    // 测试连接
    $('#testConnectionBtn').click(function() {
        testConnection();
    });
    
    // 表单提交验证
    $('#platformForm').submit(function(e) {
        if (!validateForm()) {
            e.preventDefault();
            return false;
        }
    });
    
    // 初始化测试按钮状态
    updateTestButtonState();
});

// 根据平台类型获取区域列表（智能获取：优先数据库，后备默认）
function getRegionsByPlatform(platformType) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: `/cloud/platforms/regions/${platformType}`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    // 显示数据来源信息
                    const sourceMessages = {
                        'database': '✓ 已从数据库加载可用区',
                        'default': '⚠ 使用默认可用区（建议先同步）',
                        'fallback': '⚠ 数据库连接失败，使用默认数据'
                    };
                    
                    const sourceClass = {
                        'database': 'text-success',
                        'default': 'text-warning', 
                        'fallback': 'text-danger'
                    };
                    
                    // 更新提示信息
                    const helpText = $('#region').siblings('.form-text');
                    if (helpText.length) {
                        helpText.removeClass('text-muted text-success text-warning text-danger')
                               .addClass(sourceClass[response.source])
                               .text(sourceMessages[response.source]);
                    }
                    
                    resolve(response.regions);
                } else {
                    console.warn('获取区域列表失败，使用默认数据');
                    resolve(getDefaultRegionsByPlatform(platformType));
                }
            },
            error: function(xhr, status, error) {
                console.error('获取区域列表出错:', error);
                resolve(getDefaultRegionsByPlatform(platformType));
            }
        });
    });
}

// 默认区域数据（后备方案）
function getDefaultRegionsByPlatform(platformType) {
    const regions = {
        'huawei': [
            { value: 'cn-north-1', label: '华北-北京一' },
            { value: 'cn-north-4', label: '华北-北京四' },
            { value: 'cn-east-2', label: '华东-上海二' },
            { value: 'cn-east-3', label: '华东-上海一' },
            { value: 'cn-south-1', label: '华南-广州' },
            { value: 'cn-southwest-2', label: '西南-贵阳一' },
            { value: 'ap-southeast-1', label: '亚太-香港' },
            { value: 'ap-southeast-2', label: '亚太-曼谷' },
            { value: 'ap-southeast-3', label: '亚太-新加坡' }
        ],
        'alibaba': [
            { value: 'cn-hangzhou', label: '华东1（杭州）' },
            { value: 'cn-shanghai', label: '华东2（上海）' },
            { value: 'cn-qingdao', label: '华北1（青岛）' },
            { value: 'cn-beijing', label: '华北2（北京）' },
            { value: 'cn-zhangjiakou', label: '华北3（张家口）' },
            { value: 'cn-huhehaote', label: '华北5（呼和浩特）' },
            { value: 'cn-shenzhen', label: '华南1（深圳）' },
            { value: 'cn-hongkong', label: '香港' },
            { value: 'ap-southeast-1', label: '新加坡' }
        ],
        'tencent': [
            { value: 'ap-beijing', label: '华北地区（北京）' },
            { value: 'ap-shanghai', label: '华东地区（上海）' },
            { value: 'ap-guangzhou', label: '华南地区（广州）' },
            { value: 'ap-chengdu', label: '西南地区（成都）' },
            { value: 'ap-chongqing', label: '西南地区（重庆）' },
            { value: 'ap-nanjing', label: '华东地区（南京）' },
            { value: 'ap-hongkong', label: '港澳台地区（中国香港）' },
            { value: 'ap-singapore', label: '亚太东南（新加坡）' },
            { value: 'ap-tokyo', label: '亚太东北（东京）' },
            { value: 'us-west-1', label: '美国西部（硅谷）' }
        ]
    };
    
    return regions[platformType] || [];
}

// 更新测试按钮状态
function updateTestButtonState() {
    const platformType = $('#platform_type').val();
    const accessKeyId = $('#access_key_id').val().trim();
    const accessKeySecret = $('#access_key_secret').val().trim();
    const region = $('#region').val();
    
    const canTest = platformType && accessKeyId && accessKeySecret && region;
    $('#testConnectionBtn').prop('disabled', !canTest);
}

// 测试连接（非模型绑定）
function testConnection() {
    const btn = $('#testConnectionBtn');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 测试中...');

    const payload = {
        name: $('#name').val() || '未保存配置',
        platform_type: $('#platform_type').val(),
        access_key_id: $('#access_key_id').val(),
        access_key_secret: $('#access_key_secret').val(),
        region: $('#region').val(),
        config: $('#config').val(), // 添加config参数
        _token: $('input[name="_token"]').val()
    };

    $.ajax({
        url: '/cloud/platforms/test-connection',
        method: 'POST',
        data: payload,
        success: function(res) {
            toastr[res.success ? 'success' : 'error'](res.message || (res.success ? '连接成功' : '连接失败'));
        },
        error: function(xhr) {
            const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '连接测试接口调用失败';
            toastr.error(msg);
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-plug"></i> 测试连接');
        }
    });
}

// 切换密码显示
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '_icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        field.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// 表单验证
function validateForm() {
    let isValid = true;
    const requiredFields = ['name', 'platform_type', 'access_key_id', 'access_key_secret'];
    
    requiredFields.forEach(field => {
        const element = document.getElementById(field);
        const value = element.value.trim();
        
        if (!value) {
            element.classList.add('is-invalid');
            isValid = false;
        } else {
            element.classList.remove('is-invalid');
        }
    });
    
    // 验证JSON格式
    const configField = document.getElementById('config');
    const configValue = configField.value.trim();
    if (configValue) {
        try {
            JSON.parse(configValue);
            configField.classList.remove('is-invalid');
        } catch (e) {
            configField.classList.add('is-invalid');
            isValid = false;
        }
    }
    
    if (!isValid) {
        toastr.error('请填写所有必填字段并确保格式正确');
    }
    
    return isValid;
}

// 华为云区域配置数据
const huaweiRegions = {
    "cn-north-1": { "project_id": "请填写华北-北京一的Project ID", "region_name": "华北-北京一" },
    "cn-north-4": { "project_id": "07b37758028010f82fb7c0099dea07b3", "region_name": "华北-北京四" },
    "cn-east-2": { "project_id": "076ff2363b80266a2f13c00900b40074", "region_name": "华东-上海二" },
    "cn-east-3": { "project_id": "07e1b7d435800f922f41c009ce66505d", "region_name": "华东-上海一" },
    "cn-south-1": { "project_id": "0861c7c0e880267d2fcbc009e8aa3eb8", "region_name": "华南-广州" },
    "cn-southwest-2": { "project_id": "916d0ea2830f4be9b822f97758f7a01b", "region_name": "西南-贵阳一" },
    "ap-southeast-1": { "project_id": "请填写亚太-香港的Project ID", "region_name": "亚太-香港" },
    "ap-southeast-3": { "project_id": "请填写亚太-新加坡的Project ID", "region_name": "亚太-新加坡" },
    "cn-north-9": { "project_id": "7468873636f34f29b81ee3767f27cf58", "region_name": "华北-乌兰察布一" },
    "cn-south-4": { "project_id": "adc061ad79d845e987d1a96b29c372c4", "region_name": "华南-广州四" }
};

// 加载华为云区域选项
function loadHuaweiRegions() {
    const select = $('#huawei-region-select');
    let options = '<option value="">请选择区域</option>';
    
    Object.keys(huaweiRegions).forEach(regionCode => {
        const region = huaweiRegions[regionCode];
        options += `<option value="${regionCode}">${regionCode} - ${region.region_name}</option>`;
    });
    
    select.html(options);
}

// 应用华为云配置
function applyHuaweiConfig() {
    const selectedRegion = $('#huawei-region-select').val();
    if (!selectedRegion) {
        toastr.warning('请先选择一个区域');
        return;
    }
    
    const regionData = huaweiRegions[selectedRegion];
    if (!regionData) {
        toastr.error('选择的区域数据不存在');
        return;
    }
    
    // 生成JSON配置
    const config = {
        project_ids: {}
    };
    config.project_ids[selectedRegion] = {
        project_id: regionData.project_id,
        region_name: regionData.region_name
    };
    
    // 填入配置字段
    $('#config').val(JSON.stringify(config, null, 2));
    
    // 同时设置默认区域
    $('#region').val(selectedRegion);
    
    toastr.success(`已应用 ${regionData.region_name} 的配置`);
}
</script>
@endsection