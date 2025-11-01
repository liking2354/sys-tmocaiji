@extends('layouts.app')

@section('title', $server->name . ' - 服务器详情')

@php
/**
 * 生成JSON树形结构的HTML
 */
function generateJsonTree($data, $collectorId, $parentKey = '', $level = 0) {
    $html = '';
    $indent = str_repeat('  ', $level);
    
    if (is_array($data) || is_object($data)) {
        $isArray = is_array($data);
        $items = $isArray ? $data : (array) $data;
        $count = count($items);
        
        if ($count === 0) {
            return '<span class="json-empty">' . ($isArray ? '[]' : '{}') . '</span>';
        }
        
        $html .= '<div class="json-object" data-level="' . $level . '">';
        $html .= '<span class="json-bracket">' . ($isArray ? '[' : '{') . '</span>';
        
        $i = 0;
        foreach ($items as $key => $value) {
            $currentKey = $parentKey ? $parentKey . '.' . $key : $key;
            $isLast = ($i === $count - 1);
            
            $html .= '<div class="json-item" data-key="' . htmlspecialchars($currentKey) . '">';
            $html .= '<div class="json-line">';
            
            // 缩进
            $html .= '<span class="json-indent">' . $indent . '  </span>';
            
            // 键名（对于对象）
            if (!$isArray) {
                $html .= '<span class="json-key" title="' . htmlspecialchars($key) . '">';
                $html .= '"<span class="key-text">' . htmlspecialchars($key) . '</span>": ';
                $html .= '</span>';
            }
            
            // 值
            if (is_array($value) || is_object($value)) {
                $childCount = count((array) $value);
                $toggleId = 'toggle-' . $collectorId . '-' . str_replace('.', '-', $currentKey);
                $isArrayValue = is_array($value);
                $preview = $isArrayValue ? getArrayPreview($value) : '';
                
                $html .= '<span class="json-toggle" onclick="toggleJsonNode(\'' . $toggleId . '\')" title="点击折叠/展开">';
                $html .= '<i class="fas fa-chevron-down toggle-icon" id="icon-' . $toggleId . '"></i>';
                $html .= '<span class="json-bracket">' . ($isArrayValue ? '[' : '{') . '</span>';
                $html .= '<span class="json-type-info">(' . $childCount . ' ' . ($isArrayValue ? 'items' : 'properties') . ')</span>';
                if (!empty($preview)) {
                    $html .= '<span class="json-preview" title="数组预览"> ' . $preview . '</span>';
                }
                $html .= '</span>';
                
                $html .= '<div class="json-children" id="' . $toggleId . '">';
                $html .= generateJsonTree($value, $collectorId, $currentKey, $level + 1);
                $html .= '</div>';
                
                // 折叠状态下显示的结束括号
                $html .= '<span class="json-collapsed-end" id="collapsed-' . $toggleId . '" style="display:none;">';
                $html .= '<span class="json-indent">' . $indent . '  </span>';
                $html .= '<span class="json-bracket">' . ($isArrayValue ? ']' : '}') . '</span>';
                $html .= '</span>';
            } else {
                // 原始值
                $html .= '<span class="json-value ' . getJsonValueClass($value) . '" title="' . htmlspecialchars(gettype($value)) . '">';
                $html .= formatJsonValue($value);
                $html .= '</span>';
            }
            
            // 逗号
            if (!$isLast) {
                $html .= '<span class="json-comma">,</span>';
            }
            
            $html .= '</div>'; // json-line
            $html .= '</div>'; // json-item
            
            $i++;
        }
        
        $html .= '<div class="json-line">';
        $html .= '<span class="json-indent">' . $indent . '</span>';
        $html .= '<span class="json-bracket">' . ($isArray ? ']' : '}') . '</span>';
        $html .= '</div>';
        $html .= '</div>'; // json-object
        
    } else {
        $html .= '<span class="json-value ' . getJsonValueClass($data) . '">';
        $html .= htmlspecialchars(formatJsonValue($data));
        $html .= '</span>';
    }
    
    return $html;
}

/**
 * 获取JSON值的CSS类
 */
function getJsonValueClass($value) {
    if (is_null($value)) return 'json-null';
    if (is_bool($value)) return 'json-boolean';
    if (is_numeric($value)) return 'json-number';
    if (is_string($value)) return 'json-string';
    return 'json-unknown';
}

/**
 * 格式化JSON值
 */
function formatJsonValue($value) {
    if (is_null($value)) return 'null';
    if (is_bool($value)) return $value ? 'true' : 'false';
    if (is_string($value)) return '"' . formatSpecialValue($value) . '"';
    return (string) $value;
}

/**
 * 格式化特殊值（IP地址、端口等）
 */
function formatSpecialValue($value) {
    // 检测IP地址
    if (filter_var($value, FILTER_VALIDATE_IP)) {
        return '<span class="special-ip" title="IP地址">' . htmlspecialchars($value) . '</span>';
    }
    
    // 检测MAC地址
    if (preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $value)) {
        return '<span class="special-mac" title="MAC地址">' . htmlspecialchars($value) . '</span>';
    }
    
    // 检测文件大小
    if (preg_match('/^\d+[KMGT]B?$/i', $value)) {
        return '<span class="special-size" title="存储大小">' . htmlspecialchars($value) . '</span>';
    }
    
    // 检测端口号
    if (is_numeric($value) && $value >= 1 && $value <= 65535) {
        return '<span class="special-port" title="端口号">' . htmlspecialchars($value) . '</span>';
    }
    
    // 检测时间格式
    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
        return '<span class="special-datetime" title="时间">' . htmlspecialchars($value) . '</span>';
    }
    
    return htmlspecialchars($value);
}

/**
 * 获取数组预览文本
 */
function getArrayPreview($array, $maxItems = 3) {
    if (!is_array($array) || empty($array)) return '';
    
    $count = count($array);
    $preview = [];
    $i = 0;
    
    foreach ($array as $item) {
        if ($i >= $maxItems) break;
        if (is_string($item)) {
            $preview[] = '"' . (strlen($item) > 20 ? substr($item, 0, 17) . '...' : $item) . '"';
        } else {
            $preview[] = json_encode($item);
        }
        $i++;
    }
    
    $previewText = implode(', ', $preview);
    if ($count > $maxItems) {
        $previewText .= ', ...';
    }
    
    return $previewText;
}
@endphp

@section('content')
<div class="container-fluid">
    <!-- 页面标题和操作按钮 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">
                <i class="fas fa-server text-primary"></i> {{ $server->name }}
            </h1>
            <p class="text-muted">查看和管理服务器详情信息</p>
        </div>
        <div class="d-flex gap-2 flex-wrap" style="align-items: flex-start;">
            <a href="{{ route('servers.edit', $server) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-edit"></i> 编辑服务器
            </a>
            <a href="{{ route('servers.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> 返回列表
            </a>
        </div>
    </div>
    
    <!-- 服务器信息卡片（整合基本信息和系统信息） -->
    <div class="card card-light-blue shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-info-circle"></i> 服务器信息
            </h5>
        </div>
        <div class="card-body">
            <!-- 基本信息 -->
            <div class="mb-4">
                <h6 class="text-primary font-weight-bold mb-3">基本信息</h6>
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th style="width: 30%">ID:</th>
                                <td>{{ $server->id }}</td>
                            </tr>
                            <tr>
                                <th>服务器名称:</th>
                                <td>{{ $server->name }}</td>
                            </tr>
                            <tr>
                                <th>所属分组:</th>
                                <td>
                                    @if ($server->group)
                                        <a href="{{ route('server-groups.show', $server->group) }}">
                                            {{ $server->group->name }}
                                        </a>
                                    @else
                                        无分组
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>IP地址:</th>
                                <td>{{ $server->ip }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th style="width: 30%">SSH端口:</th>
                                <td>{{ $server->port }}</td>
                            </tr>
                            <tr>
                                <th>用户名:</th>
                                <td>{{ $server->username }}</td>
                            </tr>
                            <tr>
                                <th>状态:</th>
                                <td>
                                    @if ($server->status == 1)
                                        <span class="badge badge-success server-status">
                                            <i class="fas fa-check-circle"></i> 在线
                                        </span>
                                    @else
                                        <span class="badge badge-danger server-status">
                                            <i class="fas fa-times-circle"></i> 离线
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>最后检查:</th>
                                <td class="last-check-time">{{ $server->last_check_time ? $server->last_check_time->format('Y-m-d H:i') : '未检查' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <hr>

            <!-- 系统信息 -->
            <div>
                <h6 class="text-primary font-weight-bold mb-3">系统信息</h6>
                <div id="system-info-loading" class="text-center text-muted py-3">
                    <i class="fas fa-spinner fa-spin"></i> 正在获取系统信息...
                </div>
                <div id="system-info" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="small text-muted mb-2">基本信息</h6>
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <th style="width: 30%">操作系统</th>
                                    <td id="os-info">-</td>
                                </tr>
                                <tr>
                                    <th>内核版本</th>
                                    <td id="kernel-info">-</td>
                                </tr>
                                <tr>
                                    <th>运行时间</th>
                                    <td id="uptime-info">-</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="small text-muted mb-2">资源使用</h6>
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <th style="width: 30%">CPU使用率</th>
                                    <td id="cpu-info">-</td>
                                </tr>
                                <tr>
                                    <th>内存使用</th>
                                    <td id="memory-info">-</td>
                                </tr>
                                <tr>
                                    <th>磁盘使用</th>
                                    <td id="disk-info">-</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="button" id="testConnectionBtn" class="btn btn-primary btn-sm">
                <i class="fas fa-plug"></i> 测试连接
            </button>
            <a href="{{ route('servers.console', $server) }}" class="btn btn-dark btn-sm">
                <i class="fas fa-terminal"></i> 服务器控制台
            </a>
        </div>
    </div>
    
    <!-- 第二层：采集组件信息（选项卡） -->
    <div class="card card-light-blue mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-database"></i> 采集信息
            </h5>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary btn-sm" id="executeAllCollectorsBtn">
                    <i class="fas fa-play"></i> 执行采集
                </button>
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#collectionHistoryModal">
                    <i class="fas fa-history"></i> 采集历史
                </button>
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#installCollectorModal">
                    <i class="fas fa-plus"></i> 安装组件
                </button>
            </div>
        </div>
        <div class="card-body">
            @php
                $relatedCollectors = $collectors->filter(function($collector) use ($installedCollectors) {
                    return in_array($collector->id, $installedCollectors);
                });
            @endphp
            @if ($relatedCollectors->count() > 0)
                <!-- 选项卡导航 -->
                <ul class="nav nav-tabs" id="collectorTabs" role="tablist">
                    @foreach ($relatedCollectors as $index => $collector)
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $index === 0 ? 'active' : '' }}" 
                               id="collector-{{ $collector->id }}-tab" 
                               data-toggle="tab" 
                               href="#collector-{{ $collector->id }}" 
                               role="tab" 
                               aria-controls="collector-{{ $collector->id }}" 
                               aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                                {{ $collector->name }}
                                <span class="badge badge-success">已安装</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                
                <!-- 选项卡内容 -->
                <div class="tab-content pt-4" id="collectorTabsContent">
                    @foreach ($relatedCollectors as $index => $collector)
                        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" 
                             id="collector-{{ $collector->id }}" 
                             role="tabpanel" 
                             aria-labelledby="collector-{{ $collector->id }}-tab">
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5>
                                        {{ $collector->name }} ({{ $collector->code }})
                                        <span class="badge badge-{{ $collector->type === 'script' ? 'info' : 'warning' }}">
                                            {{ $collector->typeName }}
                                        </span>
                                    </h5>
                                    <p class="text-muted small mb-0">{{ $collector->description }}</p>
                                </div>
                                <div>
                                    @if ($collector->isProgramType())
                                        @if (in_array($collector->id, $installedCollectors))
                                            <form action="{{ route('servers.collectors.uninstall', [$server, $collector]) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('确定要卸载此采集组件吗？')">
                                                    <i class="fas fa-trash"></i> 卸载组件
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('servers.collectors.install', [$server, $collector]) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-download"></i> 安装组件
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        <span class="badge badge-light">脚本类组件无需安装</span>
                                    @endif
                                    
                                    <button type="button" class="btn btn-primary btn-sm" onclick="executeSingleCollector({{ $collector->id }})">
                                        <i class="fas fa-play"></i> 执行采集
                                    </button>
                                </div>
                            </div>
                            
                            @if (isset($collectorResults[$collector->id]) && $collectorResults[$collector->id]->result)
                                @php
                                    $result = $collectorResults[$collector->id]->result;
                                    $historyRecord = $collectorResults[$collector->id];
                                @endphp
                                
                                <!-- 显示采集状态信息 -->
                                <div class="mb-3 p-3 bg-light rounded collection-status-info">
                                    <div class="d-flex justify-content-center align-items-center flex-wrap gap-3">
                                        <div class="text-center">
                                            <small class="text-muted d-block">
                                                <i class="fas fa-clock"></i> 采集时间
                                            </small>
                                            <small class="font-weight-bold">{{ $historyRecord->created_at ? $historyRecord->created_at->format('Y-m-d H:i:s') : '未知' }}</small>
                                        </div>
                                        <div class="text-center">
                                            <small class="text-muted d-block">
                                                <i class="fas fa-stopwatch"></i> 执行时间
                                            </small>
                                            <small class="font-weight-bold">{{ $historyRecord->execution_time ? number_format($historyRecord->execution_time, 2) . 's' : '未知' }}</small>
                                        </div>
                                    </div>
                                </div>
                                
                                @if (is_array($result))
                                    @if (count($result) > 0)
                                        <div class="result-container">
                                            @if (isset($result['error']) || isset($result['error_message']))
                                                <div class="alert alert-danger">
                                                    <i class="fas fa-exclamation-triangle"></i> 
                                                    <strong>执行错误:</strong> {{ $result['error_message'] ?? $result['error'] ?? '未知错误' }}
                                                </div>
                                            @elseif (isset($result['raw_output']) && !isset($result['error']))
                                                <div class="alert alert-info">
                                                    <h6><i class="fas fa-terminal"></i> 采集输出:</h6>
                                                    <pre class="mb-0 raw-output-display">{{ $result['raw_output'] }}</pre>
                                                </div>
                                            @else
                                                <div class="collection-result-container">
                                                    <div class="result-toolbar mb-3">
                                                        <div class="row">
                                                            <div class="col-md-7">
                                                                <div class="btn-group btn-group-sm" role="group">
                                                                    <button type="button" class="btn btn-primary" onclick="expandAllResults('{{ $collector->id }}')">
                                                                        <i class="fas fa-expand-alt"></i> 全部展开
                                                                    </button>
                                                                    <button type="button" class="btn btn-secondary" onclick="collapseAllResults('{{ $collector->id }}')">
                                                                        <i class="fas fa-compress-alt"></i> 全部折叠
                                                                    </button>
                                                                    <button type="button" class="btn btn-secondary" onclick="copyResultData('{{ $collector->id }}')">
                                                                        <i class="fas fa-copy"></i> 复制JSON
                                                                    </button>
                                                                    <button type="button" class="btn btn-secondary" onclick="showDataStats('{{ $collector->id }}')">
                                                                        <i class="fas fa-chart-bar"></i> 数据统计
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-5">
                                                                <div class="input-group input-group-sm">
                                                                    <input type="text" class="form-control" placeholder="搜索字段或值..." onkeyup="filterResults('{{ $collector->id }}', this.value)" id="search-{{ $collector->id }}">
                                                                    <div class="input-group-append">
                                                                        <button class="btn btn-secondary" type="button" onclick="clearSearch('{{ $collector->id }}')">
                                                                            <i class="fas fa-times"></i>
                                                                        </button>
                                                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="data-stats-panel mt-2" id="stats-{{ $collector->id }}" style="display: none;">
                                                            <div class="row">
                                                                <div class="col-md-12">
                                                                    <div class="alert alert-info mb-2">
                                                                        <div class="row text-center">
                                                                            <div class="col">
                                                                                <strong id="stats-objects-{{ $collector->id }}">0</strong><br>
                                                                                <small>对象</small>
                                                                            </div>
                                                                            <div class="col">
                                                                                <strong id="stats-arrays-{{ $collector->id }}">0</strong><br>
                                                                                <small>数组</small>
                                                                            </div>
                                                                            <div class="col">
                                                                                <strong id="stats-strings-{{ $collector->id }}">0</strong><br>
                                                                                <small>字符串</small>
                                                                            </div>
                                                                            <div class="col">
                                                                                <strong id="stats-numbers-{{ $collector->id }}">0</strong><br>
                                                                                <small>数字</small>
                                                                            </div>
                                                                            <div class="col">
                                                                                <strong id="stats-booleans-{{ $collector->id }}">0</strong><br>
                                                                                <small>布尔值</small>
                                                                            </div>
                                                                            <div class="col">
                                                                                <strong id="stats-nulls-{{ $collector->id }}">0</strong><br>
                                                                                <small>空值</small>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="json-viewer-container border rounded" id="json-container-{{ $collector->id }}">
                                                        <div class="json-viewer p-3" id="json-viewer-{{ $collector->id }}">
                                                            {!! generateJsonTree($result, $collector->id, '', 0) !!}
                                                        </div>
                                                    </div>

                                                    <textarea class="d-none" id="raw-json-{{ $collector->id }}">{{ json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i> 采集结果为空
                                        </div>
                                    @endif
                                @else
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> 采集结果格式不正确
                                        <details class="mt-2">
                                            <summary>查看原始数据</summary>
                                            <pre class="mt-2">{{ $historyRecord->result }}</pre>
                                        </details>
                                    </div>
                                @endif
                            @else
                                <div class="alert alert-secondary text-center d-flex flex-column align-items-center justify-content-center" style="min-height: 150px;">
                                    <div>
                                        <i class="fas fa-info-circle fa-2x mb-3"></i>
                                        <p class="mb-2"><strong>暂无成功的采集结果</strong></p>
                                        <small class="text-muted">点击上方"执行采集"按钮开始采集数据</small>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 该服务器未关联任何采集组件，请点击"安装采集组件"按钮进行安装
                </div>
            @endif
        </div>
    </div>

</div>

<!-- 安装采集组件模态框 -->
<div class="modal fade" id="installCollectorModal" tabindex="-1" role="dialog" aria-labelledby="installCollectorModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="installCollectorModalLabel">安装采集组件</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="list-group">
                    @php
                        $uninstalledCollectors = $collectors->filter(function($collector) use ($installedCollectors) {
                            return !in_array($collector->id, $installedCollectors);
                        });
                    @endphp
                    @if($uninstalledCollectors->count() > 0)
                        @foreach ($uninstalledCollectors as $collector)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1">{{ $collector->name }}</h5>
                                    <p class="mb-1">{{ $collector->description ?: '无描述' }}</p>
                                    <small class="text-muted">代码: {{ $collector->code }}</small>
                                </div>
                                <form action="{{ route('servers.collectors.install', [$server, $collector]) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-download"></i> 安装
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 所有可用的采集组件已安装
                        </div>
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<!-- 采集历史模态框 -->
<div class="modal fade" id="collectionHistoryModal" tabindex="-1" role="dialog" aria-labelledby="collectionHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="collectionHistoryModalLabel">采集历史记录</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="historyContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> 加载中...
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<!-- 进度弹出框 -->
<div class="modal fade" id="progressModal" tabindex="-1" role="dialog" aria-labelledby="progressModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="progressModalLabel">
                    <i class="fas fa-cog fa-spin mr-2"></i>
                    <span id="progressTitle">执行中...</span>
                </h5>
            </div>
            <div class="modal-body">
                <!-- 总体进度 -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="font-weight-bold">总体进度</span>
                        <span id="overallProgress">0%</span>
                    </div>
                    <div class="progress mb-2" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             id="overallProgressBar" 
                             role="progressbar" 
                             style="width: 0%" 
                             aria-valuenow="0" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                        </div>
                    </div>
                </div>

                <!-- 当前步骤 -->
                <div class="mb-4">
                    <h6 class="font-weight-bold mb-3">
                        <i class="fas fa-tasks mr-2"></i>执行步骤
                    </h6>
                    <div id="stepsList">
                    </div>
                </div>

                <!-- 详细日志 -->
                <div class="mb-3">
                    <h6 class="font-weight-bold mb-2">
                        <i class="fas fa-list-alt mr-2"></i>详细日志
                    </h6>
                    <div id="logContainer" style="max-height: 200px; overflow-y: auto; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 0.25rem; padding: 10px;">
                        <div id="logContent" style="font-family: 'Courier New', monospace; font-size: 12px; white-space: pre-wrap;"></div>
                    </div>
                </div>

                <!-- 结果信息 -->
                <div id="resultContainer" style="display: none;">
                    <div class="alert" id="resultAlert" role="alert">
                        <h6 class="alert-heading" id="resultTitle"></h6>
                        <p id="resultMessage" class="mb-0"></p>
                        <div id="resultDetails" class="mt-2" style="display: none;">
                            <hr>
                            <small id="resultDetailsContent"></small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="progressCloseBtn" disabled>
                    <i class="fas fa-times mr-1"></i>关闭
                </button>
                <button type="button" class="btn btn-primary" id="progressRetryBtn" style="display: none;">
                    <i class="fas fa-redo mr-1"></i>重试
                </button>
            </div>
        </div>
    </div>
</div>
@endsection



@push('scripts')
<script>
    // 设置全局变量
    window.csrfToken = '{{ csrf_token() }}';
    window.serverId = '{{ $server->id }}';
    window.serverName = '{{ $server->name }}';
    window.serverIp = '{{ $server->ip }}';
    window.serverPort = '{{ $server->port }}';
    window.serverUsername = '{{ $server->username }}';
    window.serverPassword = '{{ $server->password }}';
    window.serverVerifyRoute = '{{ route("servers.verify") }}';
    window.serverSystemInfoRoute = '{{ route("servers.system-info") }}';
    window.collectionExecuteRoute = '{{ route("servers.collection.execute", $server) }}';
    window.collectionHistoryRoute = '{{ route("servers.collection.history", $server) }}';
    
    // 采集组件列表
    window.installedCollectorIds = [];
    window.installedCollectorNames = [];
    window.collectorNames = {};
    
    @foreach ($relatedCollectors as $collector)
        window.installedCollectorIds.push({{ $collector->id }});
        window.installedCollectorNames.push('{{ $collector->name }}');
        window.collectorNames[{{ $collector->id }}] = '{{ $collector->name }}';
    @endforeach
</script>
<script src="{{ asset('assets/js/modules/servers-show.js') }}"></script>
@endpush

@section('scripts')
@endsection
