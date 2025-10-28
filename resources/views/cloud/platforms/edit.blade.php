@extends('layouts.app')

@section('title', '编辑云平台')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">编辑云平台</h3>
                    <div class="card-tools">
                        <a href="{{ route('cloud.platforms.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> 返回列表
                        </a>
                    </div>
                </div>
                <form action="{{ route('cloud.platforms.update', $platform) }}" method="POST" id="platformForm">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">平台名称 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $platform->name) }}" 
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
                                        <option value="huawei" {{ old('platform_type', $platform->platform_type) == 'huawei' ? 'selected' : '' }}>华为云</option>
                                        <option value="alibaba" {{ old('platform_type', $platform->platform_type) == 'alibaba' ? 'selected' : '' }}>阿里云</option>
                                        <option value="tencent" {{ old('platform_type', $platform->platform_type) == 'tencent' ? 'selected' : '' }}>腾讯云</option>
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
                                           id="access_key_id" name="access_key_id" value="{{ old('access_key_id', $platform->access_key_id) }}" 
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
                                               id="access_key_secret" name="access_key_secret" value="{{ old('access_key_secret', $platform->access_key_secret) }}"
                                               autocomplete="new-password" 
                                               placeholder="请输入Access Key Secret">
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
                                    <small class="form-text text-muted">选择平台类型后将自动加载可用区域</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status">状态</label>
                                    <select class="form-control @error('status') is-invalid @enderror" 
                                            id="status" name="status">
                                        <option value="active" {{ old('status', $platform->status) == 'active' ? 'selected' : '' }}>启用</option>
                                        <option value="inactive" {{ old('status', $platform->status) == 'inactive' ? 'selected' : '' }}>禁用</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- 华为云专用配置区域 -->
                        <div id="huaweiCloudConfig" style="display: none;">
                            <div class="row">
                                <div class="col-12">
                                    <div class="card bg-light">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">
                                                <i class="fas fa-cloud"></i> 华为云区域配置
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label for="huawei_region_selector">选择华为云区域和Project ID</label>
                                                <select class="form-control" id="huawei_region_selector">
                                                    <option value="">请选择区域</option>
                                                </select>
                                                <small class="form-text text-muted">选择区域后将自动配置对应的Project ID到其他配置中</small>
                                            </div>
                                            <div class="form-group">
                                                <button type="button" class="btn btn-success btn-sm" onclick="applyHuaweiConfig()">
                                                    <i class="fas fa-check"></i> 应用配置
                                                </button>
                                                <button type="button" class="btn btn-info btn-sm" onclick="showHuaweiConfigHelp()">
                                                    <i class="fas fa-question-circle"></i> 配置说明
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="config">其他配置 <small class="text-muted">(JSON格式，可选)</small></label>
                                    <textarea class="form-control @error('config') is-invalid @enderror" 
                                              id="config" name="config" rows="6" 
                                              placeholder='{"timeout": 30, "retry": 3}'>{{ old('config', is_array($platform->config) ? json_encode($platform->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $platform->config) }}</textarea>
                                    @error('config')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        请输入有效的JSON格式配置。
                                        <span id="huaweiConfigHint" style="display: none;">
                                            <br><strong>华为云:</strong> 使用上方的区域选择器可自动生成project_ids配置
                                        </span>
                                    </small>
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
                                        <button type="button" class="btn btn-info" id="testConnectionBtn">
                                            <i class="fas fa-plug"></i> 测试连接
                                        </button>
                                        <div id="testResult" class="mt-3" style="display: none;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 平台信息 -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card bg-info">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-info-circle"></i> 平台信息
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>创建时间：</strong><br>
                                                <small>{{ $platform->created_at->format('Y-m-d H:i:s') }}</small>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>更新时间：</strong><br>
                                                <small>{{ $platform->updated_at->format('Y-m-d H:i:s') }}</small>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>创建用户：</strong><br>
                                                <small>{{ $platform->user->name ?? '未知' }}</small>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>关联资源：</strong><br>
                                                <small>{{ $platform->resources->count() }} 个</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save"></i> 更新
                        </button>
                        <a href="{{ route('cloud.platforms.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> 取消
                        </a>
                        <div class="float-right">

                            <button type="button" class="btn btn-danger" onclick="deletePlatform()">
                                <i class="fas fa-trash"></i> 删除平台
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 删除确认模态框 -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">确认删除</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>您确定要删除云平台 <strong>{{ $platform->name }}</strong> 吗？</p>
                <p class="text-danger">
                    <i class="fas fa-exclamation-triangle"></i> 
                    删除后将同时删除该平台下的所有资源记录（{{ $platform->resources->count() }} 个），此操作不可恢复！
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                <form action="{{ route('cloud.platforms.destroy', $platform) }}" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">确认删除</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // 页面加载时初始化区域列表
    const currentPlatformType = $('#platform_type').val();
    const currentRegion = '{{ old("region", $platform->region) }}';
    
    if (currentPlatformType) {
        loadRegions(currentPlatformType, currentRegion);
    }
    
    // 平台类型变化时加载区域列表
    $('#platform_type').change(function() {
        const platformType = $(this).val();
        loadRegions(platformType);
        updateTestButtonState();
        toggleHuaweiCloudConfig(platformType);
    });
    
    // 初始化华为云配置显示
    toggleHuaweiCloudConfig(currentPlatformType);
    
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

// 加载区域列表
function loadRegions(platformType, selectedRegion = '') {
    const regionSelect = $('#region');
    const testBtn = $('#testConnectionBtn');
    
    if (!platformType) {
        regionSelect.html('<option value="">请先选择平台类型</option>');
        testBtn.prop('disabled', true);
        return;
    }
    
    const regions = getRegionsByPlatform(platformType);
    let options = '<option value="">请选择区域</option>';
    regions.forEach(region => {
        const selected = region.value === selectedRegion ? 'selected' : '';
        options += `<option value="${region.value}" ${selected}>${region.label}</option>`;
    });
    regionSelect.html(options);
}

// 根据平台类型获取区域列表
function getRegionsByPlatform(platformType) {
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
            { value: 'ap-tokyo', label: '亚太东北（东京）' }
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



// 删除平台
function deletePlatform() {
    $('#deleteModal').modal('show');
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
    const requiredFields = ['name', 'platform_type', 'access_key_id', 'access_key_secret', 'region'];
    
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

// 切换华为云配置显示
function toggleHuaweiCloudConfig(platformType) {
    const huaweiConfig = $('#huaweiCloudConfig');
    const huaweiHint = $('#huaweiConfigHint');
    
    if (platformType === 'huawei') {
        huaweiConfig.show();
        huaweiHint.show();
        loadHuaweiRegions();
    } else {
        huaweiConfig.hide();
        huaweiHint.hide();
    }
}

// 加载华为云区域列表
function loadHuaweiRegions() {
    const huaweiRegions = [
        { region: 'cn-north-1', project_id: '请填写华北-北京一的Project ID', name: '华北-北京一' },
        { region: 'cn-north-4', project_id: '07b37758028010f82fb7c0099dea07b3', name: '华北-北京四' },
        { region: 'cn-east-2', project_id: '076ff2363b80266a2f13c00900b40074', name: '华东-上海二' },
        { region: 'cn-east-3', project_id: '07e1b7d435800f922f41c009ce66505d', name: '华东-上海一' },
        { region: 'cn-south-1', project_id: '0861c7c0e880267d2fcbc009e8aa3eb8', name: '华南-广州' },
        { region: 'cn-southwest-2', project_id: '916d0ea2830f4be9b822f97758f7a01b', name: '西南-贵阳一' },
        { region: 'ap-southeast-1', project_id: '请填写亚太-香港的Project ID', name: '亚太-香港' },
        { region: 'ap-southeast-3', project_id: '请填写亚太-新加坡的Project ID', name: '亚太-新加坡' },
        { region: 'cn-north-9', project_id: '7468873636f34f29b81ee3767f27cf58', name: '华北-乌兰察布一' },
        { region: 'cn-south-4', project_id: 'adc061ad79d845e987d1a96b29c372c4', name: '华南-广州四' }
    ];
    
    const selector = $('#huawei_region_selector');
    let options = '<option value="">请选择区域</option>';
    
    huaweiRegions.forEach(region => {
        options += `<option value="${region.region}" data-project-id="${region.project_id}">${region.region} - ${region.name}</option>`;
    });
    
    selector.html(options);
}

// 应用华为云配置
function applyHuaweiConfig() {
    const selectedRegion = $('#huawei_region_selector').val();
    const selectedOption = $('#huawei_region_selector option:selected');
    const projectId = selectedOption.data('project-id');
    const regionName = selectedOption.text().split(' - ')[1];
    
    if (!selectedRegion || !projectId) {
        toastr.warning('请先选择一个区域');
        return;
    }
    
    // 更新默认区域
    $('#region').val(selectedRegion);
    
    // 构建新的配置JSON
    const newConfig = {
        project_ids: {}
    };
    newConfig.project_ids[selectedRegion] = {
        project_id: projectId,
        region_name: regionName
    };
    
    // 尝试合并现有配置
    const currentConfig = $('#config').val().trim();
    if (currentConfig) {
        try {
            const existingConfig = JSON.parse(currentConfig);
            // 合并配置，保留其他配置项
            Object.assign(newConfig, existingConfig);
            newConfig.project_ids = Object.assign(existingConfig.project_ids || {}, newConfig.project_ids);
        } catch (e) {
            console.warn('现有配置不是有效的JSON，将被替换');
        }
    }
    
    // 更新配置文本框
    $('#config').val(JSON.stringify(newConfig, null, 2));
    
    toastr.success(`已应用华为云区域配置：${selectedRegion} - ${regionName}`);
}

// 显示华为云配置帮助
function showHuaweiConfigHelp() {
    const helpContent = `
        <div class="alert alert-info">
            <h6><i class="fas fa-info-circle"></i> 华为云配置说明</h6>
            <p><strong>1. 区域选择：</strong>选择您要使用的华为云区域，系统会自动配置对应的Project ID。</p>
            <p><strong>2. Project ID：</strong>华为云API需要Project ID来访问区域资源，每个区域都有独立的Project ID。</p>
            <p><strong>3. 配置格式：</strong>系统会自动生成以下JSON格式的配置：</p>
            <pre><code>{
  "project_ids": {
    "区域代码": {
      "project_id": "项目ID",
      "region_name": "区域名称"
    }
  }
}</code></pre>
            <p><strong>4. 多区域支持：</strong>您可以为同一个华为云账号配置多个区域的Project ID。</p>
        </div>
    `;
    
    // 创建模态框显示帮助信息
    if ($('#helpModal').length === 0) {
        $('body').append(`
            <div class="modal fade" id="helpModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">华为云配置帮助</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body" id="helpModalBody">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
                        </div>
                    </div>
                </div>
            </div>
        `);
    }
    
    $('#helpModalBody').html(helpContent);
    $('#helpModal').modal('show');
}
</script>
@endsection