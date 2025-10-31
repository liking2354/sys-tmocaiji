@extends('layouts.app')

@section('title', '创建配置模板')

@section('content')
<div class="container-fluid">
    <!-- 页面标题 -->
    <div class="mb-4">
        <h1 class="mb-1">
            <i class="fas fa-plus text-primary"></i> 创建配置模板 - 可视化配置
        </h1>
        <p class="text-muted">使用可视化界面创建系统变更模板</p>
    </div>
    
    <!-- 操作按钮 -->
    <div class="mb-4">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('system-change.templates.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 返回列表
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card card-primary shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-edit"></i> 创建表单
                    </h5>
                </div>

                <form method="POST" action="{{ route('system-change.templates.store') }}" id="template-form">
                    @csrf
                    <div class="card-body">
                        <!-- 基本信息 -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name" class="font-weight-bold">模板名称 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="template_type" class="font-weight-bold">模板类型</label>
                                    <select class="form-control" id="template_type" name="template_type">
                                        <option value="mixed">混合模式</option>
                                        <option value="directory">目录批量处理</option>
                                        <option value="file">文件精确处理</option>
                                        <option value="string">字符串替换</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="description">模板描述</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" name="description" rows="2">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="is_active">状态</label>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="is_active" 
                                               name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="is_active">启用模板</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 模板变量定义 -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-tags mr-2"></i>
                                    模板变量定义
                                </h5>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-sm btn-primary" onclick="addVariable(); return false;">
                                        <i class="fas fa-plus mr-1"></i>
                                        添加变量
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="variables-container">
                                    <div class="text-muted text-center py-3">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        点击"添加变量"开始定义模板变量
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 配置规则 -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-cogs mr-2"></i>
                                    配置规则
                                </h5>
                                <div class="card-tools">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-success dropdown-toggle" data-toggle="dropdown">
                                            <i class="fas fa-plus mr-1"></i>
                                            添加规则
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="#" onclick="addRule('directory'); return false;">
                                                <i class="fas fa-folder mr-2"></i>
                                                目录批量处理
                                            </a>
                                            <a class="dropdown-item" href="#" onclick="addRule('file'); return false;">
                                                <i class="fas fa-file mr-2"></i>
                                                文件精确处理
                                            </a>
                                            <a class="dropdown-item" href="#" onclick="addRule('string'); return false;">
                                                <i class="fas fa-search mr-2"></i>
                                                字符串替换
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="rules-container">
                                    <div class="text-muted text-center py-3">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        点击"添加规则"开始配置处理规则
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 预览区域 -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-eye mr-2"></i>
                                    配置预览
                                </h5>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-sm btn-info" id="preview-btn">
                                        <i class="fas fa-sync mr-1"></i>
                                        刷新预览
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="config-preview" class="border rounded p-3 bg-light" style="min-height: 200px;">
                                    <div class="text-muted text-center">
                                        <i class="fas fa-eye fa-2x mb-2"></i><br>
                                        配置完成后将显示预览
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 隐藏字段 -->
                        <input type="hidden" name="config_rules" id="config_rules_input">
                        <input type="hidden" name="template_variables" id="template_variables_input">
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>
                            保存模板
                        </button>
                        <button type="button" class="btn btn-info ml-2" id="preview-config-btn">
                            <i class="fas fa-eye mr-1"></i>
                            预览配置
                        </button>
                        <a href="{{ route('system-change.templates.index') }}" class="btn btn-default ml-2">
                            <i class="fas fa-times mr-1"></i>
                            取消
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 变量模板 -->
<template id="variable-template">
    <div class="variable-item border rounded p-3 mb-3" data-index="__INDEX__">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group mb-2">
                    <label class="form-label">变量名</label>
                    <input type="text" class="form-control variable-name" placeholder="例如: db_host">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group mb-2">
                    <label class="form-label">默认值</label>
                    <input type="text" class="form-control variable-default" placeholder="默认值">
                </div>
            </div>
            <div class="col-md-5">
                <div class="form-group mb-2">
                    <label class="form-label">描述</label>
                    <input type="text" class="form-control variable-description" placeholder="变量说明">
                </div>
            </div>
            <div class="col-md-1">
                <div class="form-group mb-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-sm d-block remove-variable">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<!-- 目录规则模板 -->
<template id="directory-rule-template">
    <div class="rule-item border rounded p-3 mb-3" data-type="directory" data-index="__INDEX__">
        <div class="rule-header d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">
                <i class="fas fa-folder text-warning mr-2"></i>
                目录批量处理规则
            </h6>
            <button type="button" class="btn btn-danger btn-sm remove-rule">
                <i class="fas fa-trash mr-1"></i>
                删除
            </button>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label required">目标目录</label>
                    <input type="text" class="form-control rule-directory" placeholder="/var/www/html/config/" required>
                    <small class="text-muted">要处理的目录路径</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">文件匹配模式</label>
                    <input type="text" class="form-control rule-pattern" placeholder="*.conf" value="*">
                    <small class="text-muted">文件名匹配模式，支持通配符</small>
                </div>
            </div>
        </div>
        
        <!-- 变量配置区域 -->
        <div class="variables-config-area">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label required">变量配置</label>
                <button type="button" class="btn btn-sm btn-outline-primary add-rule-variable">
                    <i class="fas fa-plus mr-1"></i>
                    添加变量
                </button>
            </div>
            <div class="rule-variables-container">
                <div class="rule-variable-item row mb-2" data-var-index="0">
                    <div class="col-md-3">
                        <input type="text" class="form-control rule-variable" placeholder="变量名 (如: db_host)" required>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control rule-match-type">
                            <option value="key_value">键值对模式</option>
                            <option value="regex">正则表达式</option>
                            <option value="exact">精确匹配</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <input type="text" class="form-control rule-match-pattern" placeholder="匹配表达式 (留空使用变量名)">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-sm btn-danger remove-rule-variable" style="display: none;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">规则描述</label>
            <input type="text" class="form-control rule-description" placeholder="描述这个规则的作用">
        </div>
    </div>
</template>

<!-- 文件规则模板 -->
<template id="file-rule-template">
    <div class="rule-item border rounded p-3 mb-3" data-type="file" data-index="__INDEX__">
        <div class="rule-header d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">
                <i class="fas fa-file text-info mr-2"></i>
                文件精确处理规则
            </h6>
            <button type="button" class="btn btn-danger btn-sm remove-rule">
                <i class="fas fa-trash mr-1"></i>
                删除
            </button>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label class="form-label required">目标文件</label>
                    <input type="text" class="form-control rule-file-path" placeholder="/etc/nginx/nginx.conf" required>
                    <small class="text-muted">要处理的具体文件路径</small>
                </div>
            </div>
        </div>
        
        <!-- 变量配置区域 -->
        <div class="variables-config-area">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label required">变量配置</label>
                <button type="button" class="btn btn-sm btn-outline-primary add-rule-variable">
                    <i class="fas fa-plus mr-1"></i>
                    添加变量
                </button>
            </div>
            <div class="rule-variables-container">
                <div class="rule-variable-item row mb-2" data-var-index="0">
                    <div class="col-md-3">
                        <input type="text" class="form-control rule-variable" placeholder="变量名 (如: server_name)" required>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control rule-match-type">
                            <option value="key_value">键值对模式</option>
                            <option value="regex">正则表达式</option>
                            <option value="line">整行替换</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <input type="text" class="form-control rule-match-pattern" placeholder="匹配表达式 (留空使用变量名)">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-sm btn-danger remove-rule-variable" style="display: none;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">规则描述</label>
            <input type="text" class="form-control rule-description" placeholder="描述这个规则的作用">
        </div>
    </div>
</template>

<!-- 字符串规则模板 -->
<template id="string-rule-template">
    <div class="rule-item border rounded p-3 mb-3" data-type="string" data-index="__INDEX__">
        <div class="rule-header d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">
                <i class="fas fa-search text-success mr-2"></i>
                字符串替换规则
            </h6>
            <button type="button" class="btn btn-danger btn-sm remove-rule">
                <i class="fas fa-trash mr-1"></i>
                删除
            </button>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label class="form-label required">目标文件</label>
                    <input type="text" class="form-control rule-file-path" placeholder="/var/www/html/config.php" required>
                    <small class="text-muted">要处理的文件路径</small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label required">查找字符串</label>
                    <textarea class="form-control rule-search-string" rows="3" placeholder="要查找的字符串内容" required></textarea>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label required">替换字符串</label>
                    <textarea class="form-control rule-replace-string" rows="3" placeholder="替换后的内容，支持变量 @{{variable_name@}}" required></textarea>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input rule-case-sensitive" id="case_sensitive___INDEX__">
                        <label class="custom-control-label" for="case_sensitive___INDEX__">区分大小写</label>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input rule-regex-mode" id="regex_mode___INDEX__">
                        <label class="custom-control-label" for="regex_mode___INDEX__">正则表达式模式</label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">规则描述</label>
            <input type="text" class="form-control rule-description" placeholder="描述这个规则的作用">
        </div>
    </div>
</template>
@endsection

@section('styles')
@endsection

@push('scripts')
<script src="{{ asset('assets/js/modules/system-change-templates-create.js') }}"></script>
@endpush
