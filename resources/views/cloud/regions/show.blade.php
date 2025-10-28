@extends('layouts.app')

@section('title', '查看可用区')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">可用区详情</h3>
                    <div class="card-tools">
                        <a href="{{ route('cloud.regions.edit', $region) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> 编辑
                        </a>
                        <a href="{{ route('cloud.regions.index') }}" class="btn btn-default">
                            <i class="fas fa-arrow-left"></i> 返回列表
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="30%">ID</th>
                                    <td>{{ $region->id }}</td>
                                </tr>
                                <tr>
                                    <th>平台类型</th>
                                    <td>
                                        <span class="badge badge-info">{{ $region->platform_name }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>可用区代码</th>
                                    <td><code>{{ $region->region_code }}</code></td>
                                </tr>
                                <tr>
                                    <th>可用区名称</th>
                                    <td>{{ $region->region_name }}</td>
                                </tr>
                                <tr>
                                    <th>英文名称</th>
                                    <td>{{ $region->region_name_en ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>排序</th>
                                    <td>{{ $region->sort_order }}</td>
                                </tr>
                                <tr>
                                    <th>状态</th>
                                    <td>
                                        @if($region->is_active)
                                            <span class="badge badge-success">启用</span>
                                        @else
                                            <span class="badge badge-secondary">禁用</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="30%">创建时间</th>
                                    <td>{{ $region->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th>更新时间</th>
                                    <td>{{ $region->updated_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th>描述</th>
                                    <td>
                                        @if($region->description)
                                            {{ $region->description }}
                                        @else
                                            <span class="text-muted">无描述</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="btn-group" role="group">
                        <a href="{{ route('cloud.regions.edit', $region) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> 编辑
                        </a>
                        <form action="{{ route('cloud.regions.destroy', $region) }}" method="POST" class="d-inline" onsubmit="return confirm('确定要删除这个可用区吗？')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash"></i> 删除
                            </button>
                        </form>
                        <a href="{{ route('cloud.regions.index') }}" class="btn btn-default">
                            <i class="fas fa-arrow-left"></i> 返回列表
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection