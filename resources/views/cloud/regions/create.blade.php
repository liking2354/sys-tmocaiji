@extends('layouts.app')

@section('title', '添加可用区')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">添加可用区</h3>
                    <div class="card-tools">
                        <a href="{{ route('cloud.regions.index') }}" class="btn btn-default">
                            <i class="fas fa-arrow-left"></i> 返回列表
                        </a>
                    </div>
                </div>
                <form action="{{ route('cloud.regions.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="platform_id">云平台 <span class="text-danger">*</span></label>
                                    <select name="platform_id" id="platform_id" class="form-control @error('platform_id') is-invalid @enderror" required>
                                        <option value="">请选择云平台</option>
                                        @foreach($platforms as $platform)
                                            <option value="{{ $platform->id }}" {{ old('platform_id') == $platform->id ? 'selected' : '' }}>
                                                {{ $platform->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('platform_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="region_code">可用区代码 <span class="text-danger">*</span></label>
                                    <input type="text" name="region_code" id="region_code" class="form-control @error('region_code') is-invalid @enderror" 
                                           value="{{ old('region_code') }}" placeholder="例如：ap-beijing-1" required>
                                    @error('region_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">云平台的可用区标识符</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="region_name">可用区名称 <span class="text-danger">*</span></label>
                                    <input type="text" name="region_name" id="region_name" class="form-control @error('region_name') is-invalid @enderror" 
                                           value="{{ old('region_name') }}" placeholder="例如：北京一区" required>
                                    @error('region_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="is_active">状态</label>
                                    <select name="is_active" id="is_active" class="form-control @error('is_active') is-invalid @enderror">
                                        <option value="1" {{ old('is_active', 1) == 1 ? 'selected' : '' }}>启用</option>
                                        <option value="0" {{ old('is_active') == 0 ? 'selected' : '' }}>禁用</option>
                                    </select>
                                    @error('is_active')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">描述</label>
                            <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" 
                                      rows="3" placeholder="可用区描述信息（可选）">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 保存
                        </button>
                        <a href="{{ route('cloud.regions.index') }}" class="btn btn-default">
                            <i class="fas fa-times"></i> 取消
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection