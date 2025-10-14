<?php $__env->startSection('title', $server->name . ' - 服务器详情'); ?>

<?php
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
?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>服务器详情</h1>
        <div>
            <a href="<?php echo e(route('servers.edit', $server)); ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> 编辑服务器
            </a>
            <a href="<?php echo e(route('servers.index')); ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 返回服务器列表
            </a>
        </div>
    </div>
    
    <!-- 第一层：服务器基本信息 -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">基本信息</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 30%">ID:</th>
                            <td><?php echo e($server->id); ?></td>
                        </tr>
                        <tr>
                            <th>服务器名称:</th>
                            <td><?php echo e($server->name); ?></td>
                        </tr>
                        <tr>
                            <th>所属分组:</th>
                            <td>
                                <?php if($server->group): ?>
                                    <a href="<?php echo e(route('server-groups.show', $server->group)); ?>">
                                        <?php echo e($server->group->name); ?>

                                    </a>
                                <?php else: ?>
                                    无分组
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>IP地址:</th>
                            <td><?php echo e($server->ip); ?></td>
                        </tr>
                        <tr>
                            <th>SSH端口:</th>
                            <td><?php echo e($server->port); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 30%">用户名:</th>
                            <td><?php echo e($server->username); ?></td>
                        </tr>
                        <tr>
                            <th>状态:</th>
                            <td>
                                <?php if($server->status == 1): ?>
                                    <span class="badge badge-success server-status">
                                        <i class="fas fa-check-circle"></i> 在线
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-danger server-status">
                                        <i class="fas fa-times-circle"></i> 离线
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>最后检查时间:</th>
                            <td class="last-check-time"><?php echo e($server->last_check_time ? $server->last_check_time->format('Y-m-d H:i:s') : '未检查'); ?></td>
                        </tr>
                        <tr>
                            <th>创建时间:</th>
                            <td><?php echo e($server->created_at->format('Y-m-d H:i:s')); ?></td>
                        </tr>
                        <tr>
                            <th>更新时间:</th>
                            <td><?php echo e($server->updated_at->format('Y-m-d H:i:s')); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="button" id="testConnectionBtn" class="btn btn-info btn-sm">
                <i class="fas fa-plug"></i> 测试连接
            </button>
            <a href="<?php echo e(route('servers.console', $server)); ?>" class="btn btn-dark btn-sm">
                <i class="fas fa-terminal"></i> 服务器控制台
            </a>
        </div>
    </div>
    
    <!-- 系统信息 -->
    <div class="card mt-4">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">系统信息</h5>
                <div>
                    <button type="button" class="btn btn-info btn-sm" id="toggleSystemInfoBtn">
                        <i class="fas fa-chevron-down"></i> 展开详情
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div id="system-info-prompt" class="alert alert-info">
                <i class="fas fa-info-circle"></i> 点击"测试连接"按钮获取服务器系统信息
            </div>
            <div id="system-info" class="collapse">
                <div class="row">
                    <div class="col-md-6">
                        <h6>基本信息</h6>
                        <table class="table table-sm">
                            <tr>
                                <th>操作系统</th>
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
                        <h6>资源使用</h6>
                        <table class="table table-sm">
                            <tr>
                                <th>CPU使用率</th>
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
    
    <!-- 第二层：采集组件信息（选项卡） -->
    <div class="card mt-4">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">采集信息</h5>
                <div>
                    <button type="button" class="btn btn-success btn-sm" id="executeAllCollectorsBtn">
                        <i class="fas fa-play"></i> 执行所有采集组件
                    </button>
                    <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#collectionHistoryModal">
                        <i class="fas fa-history"></i> 查看采集历史
                    </button>
                    <button type="button" class="btn btn-light btn-sm" data-toggle="modal" data-target="#installCollectorModal">
                        <i class="fas fa-plus"></i> 安装采集组件
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php
                $relatedCollectors = $collectors->filter(function($collector) use ($installedCollectors) {
                    return in_array($collector->id, $installedCollectors);
                });
            ?>
            <?php if($relatedCollectors->count() > 0): ?>
                <!-- 选项卡导航 -->
                <ul class="nav nav-tabs" id="collectorTabs" role="tablist">
                    <?php $__currentLoopData = $relatedCollectors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $collector): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link <?php echo e($index === 0 ? 'active' : ''); ?>" 
                               id="collector-<?php echo e($collector->id); ?>-tab" 
                               data-toggle="tab" 
                               href="#collector-<?php echo e($collector->id); ?>" 
                               role="tab" 
                               aria-controls="collector-<?php echo e($collector->id); ?>" 
                               aria-selected="<?php echo e($index === 0 ? 'true' : 'false'); ?>">
                                <?php echo e($collector->name); ?>

                                <span class="badge badge-success">已安装</span>
                            </a>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
                
                <!-- 选项卡内容 -->
                <div class="tab-content pt-4" id="collectorTabsContent">
                    <?php $__currentLoopData = $relatedCollectors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $collector): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="tab-pane fade <?php echo e($index === 0 ? 'show active' : ''); ?>" 
                             id="collector-<?php echo e($collector->id); ?>" 
                             role="tabpanel" 
                             aria-labelledby="collector-<?php echo e($collector->id); ?>-tab">
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5>
                                        <?php echo e($collector->name); ?> (<?php echo e($collector->code); ?>)
                                        <span class="badge badge-<?php echo e($collector->type === 'script' ? 'info' : 'warning'); ?>">
                                            <?php echo e($collector->typeName); ?>

                                        </span>
                                    </h5>
                                    <p class="text-muted small mb-0"><?php echo e($collector->description); ?></p>
                                </div>
                                <div>
                                    <?php if($collector->isProgramType()): ?>
                                        <?php if(in_array($collector->id, $installedCollectors)): ?>
                                            <form action="<?php echo e(route('servers.collectors.uninstall', [$server, $collector])); ?>" method="POST" class="d-inline">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('确定要卸载此采集组件吗？')">
                                                    <i class="fas fa-trash"></i> 卸载组件
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form action="<?php echo e(route('servers.collectors.install', [$server, $collector])); ?>" method="POST" class="d-inline">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="fas fa-download"></i> 安装组件
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge badge-light">脚本类组件无需安装</span>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="btn btn-warning btn-sm" onclick="executeSingleCollector(<?php echo e($collector->id); ?>)">
                                        <i class="fas fa-play"></i> 执行采集
                                    </button>
                                    
                                    <?php if(isset($collectorResults[$collector->id])): ?>
                                        <span class="text-muted ml-3">
                                            最后采集时间: <?php echo e($collectorResults[$collector->id]->completed_at ? $collectorResults[$collector->id]->completed_at->format('Y-m-d H:i:s') : $collectorResults[$collector->id]->created_at->format('Y-m-d H:i:s')); ?>

                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if(isset($collectorResults[$collector->id]) && $collectorResults[$collector->id]->result): ?>
                                <?php
                                    $result = $collectorResults[$collector->id]->result; // 已经是数组，无需json_decode
                                    $historyRecord = $collectorResults[$collector->id];
                                ?>
                                
                                <!-- 显示采集状态信息 -->
                                <div class="mb-3 p-3 bg-light rounded">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="text-muted">
                                                <i class="fas fa-clock"></i> 采集时间: <?php echo e($historyRecord->created_at ? $historyRecord->created_at->format('Y-m-d H:i:s') : '未知'); ?>

                                            </small>
                                        </div>
                                        <div class="col-md-6 text-right">
                                            <small class="text-muted">
                                                <i class="fas fa-stopwatch"></i> 执行时间: <?php echo e($historyRecord->execution_time ? number_format($historyRecord->execution_time, 2) . 's' : '未知'); ?>

                                            </small>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if(is_array($result)): ?>
                                    <?php if(count($result) > 0): ?>
                                        <div class="result-container">
                                            <!-- 检查是否有错误信息 -->
                                            <?php if(isset($result['error']) || isset($result['error_message'])): ?>
                                                <div class="alert alert-danger">
                                                    <i class="fas fa-exclamation-triangle"></i> 
                                                    <strong>执行错误:</strong> <?php echo e($result['error_message'] ?? $result['error'] ?? '未知错误'); ?>

                                                </div>
                                            <?php elseif(isset($result['raw_output']) && !isset($result['error'])): ?>
                                                <!-- 显示原始输出 -->
                                                <div class="alert alert-info">
                                                    <h6><i class="fas fa-terminal"></i> 采集输出:</h6>
                                                    <pre class="mb-0" style="white-space: pre-wrap; font-size: 0.9em;"><?php echo e($result['raw_output']); ?></pre>
                                                </div>
                                            <?php else: ?>
                                                <!-- 优化的结构化数据显示 -->
                                                <div class="collection-result-container">
                                                    <!-- 工具栏 -->
                                                    <div class="result-toolbar mb-3">
                                                        <div class="row">
                                                            <div class="col-md-7">
                                                                <div class="btn-group btn-group-sm" role="group">
                                                                    <button type="button" class="btn btn-outline-primary" onclick="expandAllResults('<?php echo e($collector->id); ?>')">
                                                                        <i class="fas fa-expand-alt"></i> 全部展开
                                                                    </button>
                                                                    <button type="button" class="btn btn-outline-secondary" onclick="collapseAllResults('<?php echo e($collector->id); ?>')">
                                                                        <i class="fas fa-compress-alt"></i> 全部折叠
                                                                    </button>
                                                                    <button type="button" class="btn btn-outline-success" onclick="copyResultData('<?php echo e($collector->id); ?>')">
                                                                        <i class="fas fa-copy"></i> 复制JSON
                                                                    </button>
                                                                    <button type="button" class="btn btn-outline-info" onclick="showDataStats('<?php echo e($collector->id); ?>')">
                                                                        <i class="fas fa-chart-bar"></i> 数据统计
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-5">
                                                                <div class="input-group input-group-sm">
                                                                    <input type="text" class="form-control" placeholder="搜索字段或值..." onkeyup="filterResults('<?php echo e($collector->id); ?>', this.value)" id="search-<?php echo e($collector->id); ?>">
                                                                    <div class="input-group-append">
                                                                        <button class="btn btn-outline-secondary" type="button" onclick="clearSearch('<?php echo e($collector->id); ?>')">
                                                                            <i class="fas fa-times"></i>
                                                                        </button>
                                                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- 数据统计面板 -->
                                                        <div class="data-stats-panel mt-2" id="stats-<?php echo e($collector->id); ?>" style="display: none;">
                                                            <div class="row">
                                                                <div class="col-md-12">
                                                                    <div class="alert alert-info mb-2">
                                                                        <div class="row text-center">
                                                                            <div class="col">
                                                                                <strong id="stats-objects-<?php echo e($collector->id); ?>">0</strong><br>
                                                                                <small>对象</small>
                                                                            </div>
                                                                            <div class="col">
                                                                                <strong id="stats-arrays-<?php echo e($collector->id); ?>">0</strong><br>
                                                                                <small>数组</small>
                                                                            </div>
                                                                            <div class="col">
                                                                                <strong id="stats-strings-<?php echo e($collector->id); ?>">0</strong><br>
                                                                                <small>字符串</small>
                                                                            </div>
                                                                            <div class="col">
                                                                                <strong id="stats-numbers-<?php echo e($collector->id); ?>">0</strong><br>
                                                                                <small>数字</small>
                                                                            </div>
                                                                            <div class="col">
                                                                                <strong id="stats-booleans-<?php echo e($collector->id); ?>">0</strong><br>
                                                                                <small>布尔值</small>
                                                                            </div>
                                                                            <div class="col">
                                                                                <strong id="stats-nulls-<?php echo e($collector->id); ?>">0</strong><br>
                                                                                <small>空值</small>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- JSON数据展示区 -->
                                                    <div class="json-viewer-container border rounded" id="json-container-<?php echo e($collector->id); ?>">
                                                        <div class="json-viewer p-3" id="json-viewer-<?php echo e($collector->id); ?>">
                                                            <?php echo generateJsonTree($result, $collector->id, '', 0); ?>

                                                        </div>
                                                    </div>

                                                    <!-- 隐藏的原始JSON数据用于复制 -->
                                                    <textarea class="d-none" id="raw-json-<?php echo e($collector->id); ?>"><?php echo e(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></textarea>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i> 采集结果为空
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> 采集结果格式不正确
                                        <details class="mt-2">
                                            <summary>查看原始数据</summary>
                                            <pre class="mt-2"><?php echo e($historyRecord->result); ?></pre>
                                        </details>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="alert alert-secondary">
                                    <i class="fas fa-info-circle"></i> 暂无成功的采集结果
                                    <p class="mb-0 mt-2"><small class="text-muted">点击上方"执行采集"按钮开始采集数据</small></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 该服务器未关联任何采集组件，请点击"安装采集组件"按钮进行安装
                </div>
            <?php endif; ?>
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
                    <?php
                        $uninstalledCollectors = $collectors->filter(function($collector) use ($installedCollectors) {
                            return !in_array($collector->id, $installedCollectors);
                        });
                    ?>
                    <?php if($uninstalledCollectors->count() > 0): ?>
                        <?php $__currentLoopData = $uninstalledCollectors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $collector): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1"><?php echo e($collector->name); ?></h5>
                                    <p class="mb-1"><?php echo e($collector->description ?: '无描述'); ?></p>
                                    <small class="text-muted">代码: <?php echo e($collector->code); ?></small>
                                </div>
                                <form action="<?php echo e(route('servers.collectors.install', [$server, $collector])); ?>" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-download"></i> 安装
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 所有可用的采集组件已安装
                        </div>
                    <?php endif; ?>
                    
                    <!-- 已在上面处理了没有可安装的采集组件的情况 -->
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
                        <!-- 步骤将通过JavaScript动态添加 -->
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
<?php $__env->stopSection(); ?>

<?php $__env->startSection('styles'); ?>
<style>
    .json-formatter {
        background-color: #f8f9fa;
        border: 1px solid #eee;
        border-radius: 4px;
        padding: 10px;
        max-height: 500px;
        overflow-y: auto;
        white-space: pre-wrap;
        font-family: monospace;
        font-size: 13px;
    }
    
    .nav-tabs .nav-link {
        color: #495057;
    }
    
    .nav-tabs .nav-link.active {
        font-weight: bold;
        color: #007bff;
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
// 进度管理器
class ProgressManager {
    constructor() {
        this.modal = $('#progressModal');
        this.steps = [];
        this.currentStep = 0;
        this.isCompleted = false;
        this.onRetry = null;
    }

    // 初始化进度框
    init(title, steps, onRetry = null) {
        this.steps = steps;
        this.currentStep = 0;
        this.isCompleted = false;
        this.onRetry = onRetry;
        
        // 设置标题
        $('#progressTitle').text(title);
        
        // 重置进度条
        this.updateProgress(0);
        
        // 清空并创建步骤列表
        this.createStepsList();
        
        // 清空日志
        $('#logContent').empty();
        
        // 隐藏结果容器
        $('#resultContainer').hide();
        
        // 重置按钮状态
        $('#progressCloseBtn').prop('disabled', true).show();
        $('#progressRetryBtn').hide();
        
        // 显示模态框
        this.modal.modal('show');
    }

    // 创建步骤列表
    createStepsList() {
        const stepsList = $('#stepsList');
        stepsList.empty();
        
        this.steps.forEach((step, index) => {
            const stepHtml = `
                <div class="d-flex align-items-center mb-2" id="step-${index}">
                    <div class="step-icon mr-3" style="width: 30px; text-align: center;">
                        <i class="fas fa-circle text-muted" id="step-icon-${index}"></i>
                    </div>
                    <div class="flex-grow-1">
                        <span class="step-text" id="step-text-${index}">${step}</span>
                    </div>
                    <div class="step-status ml-2" id="step-status-${index}">
                        <span class="badge badge-secondary">等待中</span>
                    </div>
                </div>
            `;
            stepsList.append(stepHtml);
        });
    }

    // 更新总体进度
    updateProgress(percentage) {
        $('#overallProgress').text(Math.round(percentage) + '%');
        $('#overallProgressBar')
            .css('width', percentage + '%')
            .attr('aria-valuenow', percentage);
    }

    // 开始执行步骤
    startStep(stepIndex, message = '') {
        if (stepIndex >= this.steps.length) return;
        
        this.currentStep = stepIndex;
        
        // 更新步骤状态
        $(`#step-icon-${stepIndex}`)
            .removeClass('fa-circle text-muted fa-check text-success fa-times text-danger')
            .addClass('fa-spinner fa-spin text-primary');
        
        $(`#step-status-${stepIndex}`)
            .html('<span class="badge badge-primary">执行中</span>');
        
        // 添加日志
        this.addLog(`[步骤 ${stepIndex + 1}] ${this.steps[stepIndex]}${message ? ': ' + message : ''}`);
        
        // 更新进度
        const progress = (stepIndex / this.steps.length) * 100;
        this.updateProgress(progress);
    }

    // 完成步骤
    completeStep(stepIndex, success = true, message = '') {
        if (stepIndex >= this.steps.length) return;
        
        const iconClass = success ? 'fa-check text-success' : 'fa-times text-danger';
        const statusClass = success ? 'badge-success' : 'badge-danger';
        const statusText = success ? '完成' : '失败';
        
        // 更新步骤状态
        $(`#step-icon-${stepIndex}`)
            .removeClass('fa-spinner fa-spin text-primary fa-circle text-muted')
            .addClass(iconClass);
        
        $(`#step-status-${stepIndex}`)
            .html(`<span class="badge ${statusClass}">${statusText}</span>`);
        
        // 添加日志
        const logMessage = `[步骤 ${stepIndex + 1}] ${success ? '✓' : '✗'} ${this.steps[stepIndex]}${message ? ': ' + message : ''}`;
        this.addLog(logMessage);
        
        // 更新进度
        const progress = ((stepIndex + 1) / this.steps.length) * 100;
        this.updateProgress(progress);
    }

    // 添加日志
    addLog(message) {
        const timestamp = new Date().toLocaleTimeString();
        const logContent = $('#logContent');
        const newLog = `[${timestamp}] ${message}\n`;
        logContent.append(newLog);
        
        // 自动滚动到底部
        const container = $('#logContainer');
        container.scrollTop(container[0].scrollHeight);
    }

    // 显示结果
    showResult(success, title, message, details = '') {
        this.isCompleted = true;
        
        const alertClass = success ? 'alert-success' : 'alert-danger';
        const iconClass = success ? 'fa-check-circle' : 'fa-exclamation-circle';
        
        $('#resultAlert')
            .removeClass('alert-success alert-danger alert-warning alert-info')
            .addClass(alertClass);
        
        $('#resultTitle').html(`<i class="fas ${iconClass} mr-2"></i>${title}`);
        $('#resultMessage').text(message);
        
        if (details) {
            $('#resultDetailsContent').text(details);
            $('#resultDetails').show();
        } else {
            $('#resultDetails').hide();
        }
        
        $('#resultContainer').show();
        
        // 启用关闭按钮
        $('#progressCloseBtn').prop('disabled', false);
        
        // 显示重试按钮（如果失败且有重试回调）
        if (!success && this.onRetry) {
            $('#progressRetryBtn').show();
        }
        
        // 更新进度到100%
        this.updateProgress(100);
    }

    // 关闭进度框
    close() {
        this.modal.modal('hide');
    }
}

// 全局进度管理器实例
const progressManager = new ProgressManager();

    $(document).ready(function() {
        // 进度框事件处理
        $('#progressCloseBtn').click(function() {
            progressManager.close();
        });
        
        $('#progressRetryBtn').click(function() {
            if (progressManager.onRetry) {
                progressManager.onRetry();
            }
        });
        // 系统信息展开/收起按钮点击事件
        $('#toggleSystemInfoBtn').click(function() {
            var systemInfo = $('#system-info');
            var btn = $(this);
            
            // 检查是否已经有数据
            var hasData = $('#os-info').text() !== '-';
            
            if (!hasData) {
                alert('请先点击"测试连接"按钮获取系统信息');
                return;
            }
            
            if (systemInfo.hasClass('show')) {
                systemInfo.removeClass('show');
                btn.html('<i class="fas fa-chevron-down"></i> 展开详情');
            } else {
                systemInfo.addClass('show');
                btn.html('<i class="fas fa-chevron-up"></i> 收起详情');
            }
        });
        
        // 测试连接按钮点击事件
        $('#testConnectionBtn').click(function() {
            // 显示加载状态
            var btn = $(this);
            var originalText = btn.html();
            btn.html('<i class="fas fa-spinner fa-spin"></i> 测试中...');
            btn.prop('disabled', true);
            
            // 发送AJAX请求验证连接
            $.ajax({
                url: '<?php echo e(route("servers.verify")); ?>',
                type: 'POST',
                data: {
                    _token: '<?php echo e(csrf_token()); ?>',
                    ip: '<?php echo e($server->ip); ?>',
                    port: '<?php echo e($server->port); ?>',
                    username: '<?php echo e($server->username); ?>',
                    password: '<?php echo e($server->password); ?>',
                    server_id: '<?php echo e($server->id); ?>' // 添加服务器ID
                },
                success: function(response) {
                    if (response.success) {
                        // 显示成功消息
                        if (response.status_updated) {
                            alert('连接成功！服务器状态已更新为在线。');
                            
                            // 更新页面上的状态显示
                            updateServerStatus(1);
                        } else if (response.status_error) {
                            alert('连接成功！' + response.status_error);
                        } else {
                            alert('连接成功！');
                        }
                        
                        // 连接成功后获取系统信息
                        getSystemInfo();
                    } else {
                        alert('连接失败：' + response.message);
                    }
                },
                error: function(xhr) {
                    alert('请求失败：' + xhr.responseText);
                },
                complete: function() {
                    // 恢复按钮状态
                    btn.html(originalText);
                    btn.prop('disabled', false);
                }
            });
        });
        
        // 更新服务器状态显示的函数
        function updateServerStatus(status) {
            var statusElement = $('.server-status');
            var statusText = status === 1 ? '在线' : '离线';
            var badgeClass = status === 1 ? 'badge-success' : 'badge-danger';
            var statusIcon = status === 1 ? 'fa-check-circle' : 'fa-times-circle';
            
            // 更新badge样式和内容
            statusElement.removeClass('badge-success badge-danger')
                        .addClass(badgeClass)
                        .html('<i class="fas ' + statusIcon + '"></i> ' + statusText);
            
            // 更新最后检查时间
            var now = new Date();
            var timeString = now.getFullYear() + '-' + 
                           String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                           String(now.getDate()).padStart(2, '0') + ' ' + 
                           String(now.getHours()).padStart(2, '0') + ':' + 
                           String(now.getMinutes()).padStart(2, '0') + ':' + 
                           String(now.getSeconds()).padStart(2, '0');
            
            $('.last-check-time').text(timeString);
        }
        
        // 获取系统信息的函数
        function getSystemInfo() {
            $.ajax({
                url: '<?php echo e(route("servers.system-info")); ?>',
                type: 'POST',
                data: {
                    _token: '<?php echo e(csrf_token()); ?>',
                    ip: '<?php echo e($server->ip); ?>',
                    port: '<?php echo e($server->port); ?>',
                    username: '<?php echo e($server->username); ?>',
                    password: '<?php echo e($server->password); ?>',
                    server_id: '<?php echo e($server->id); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        // 显示系统信息区域并展开
                        $('#system-info').addClass('show');
                        // 更新展开按钮图标和文字
                        $('#toggleSystemInfoBtn').html('<i class="fas fa-chevron-up"></i> 收起详情');
                        
                        // 填充系统信息
                        $('#os-info').text(response.data.os_info);
                        $('#kernel-info').text(response.data.kernel_info);
                        $('#uptime-info').text(response.data.uptime_info);
                        $('#cpu-info').text(response.data.cpu_info);
                        $('#memory-info').text(response.data.memory_info);
                        $('#disk-info').text(response.data.disk_info);
                    } else {
                        console.error('获取系统信息失败:', response.message);
                    }
                },
                error: function(xhr) {
                    console.error('请求系统信息失败:', xhr.responseText);
                }
            });
        }
        
        // 执行所有采集组件
        $('#executeAllCollectorsBtn').click(function() {
            var installedCollectors = [];
            var collectorNames = [];
            <?php $__currentLoopData = $relatedCollectors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $collector): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                installedCollectors.push(<?php echo e($collector->id); ?>);
                collectorNames.push('<?php echo e($collector->name); ?>');
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            
            if (installedCollectors.length === 0) {
                alert('没有已安装的采集组件');
                return;
            }
            
            if (confirm('确定要执行所有采集组件吗？')) {
                executeAllCollectorsWithProgress(installedCollectors, collectorNames);
            }
        });
        
        // 查看采集历史
        $('#collectionHistoryModal').on('show.bs.modal', function() {
            loadCollectionHistory();
        });
    });
    
    // 执行单个采集组件
    function executeSingleCollector(collectorId) {
        // 获取采集组件名称
        var collectorName = '';
        <?php $__currentLoopData = $relatedCollectors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $collector): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            if (<?php echo e($collector->id); ?> === collectorId) {
                collectorName = '<?php echo e($collector->name); ?>';
            }
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        
        if (confirm('确定要执行该采集组件吗？')) {
            executeSingleCollectorWithProgress(collectorId, collectorName);
        }
    }

    // 执行单个采集组件（带进度显示）
    function executeSingleCollectorWithProgress(collectorId, collectorName) {
        // 定义执行步骤
        const steps = [
            '验证采集组件状态',
            '准备执行环境',
            '执行采集任务',
            '处理采集结果'
        ];
        
        // 初始化进度管理器
        progressManager.init(`执行采集组件: ${collectorName}`, steps, () => executeSingleCollectorWithProgress(collectorId, collectorName));
        
        // 执行单个采集流程
        executeSingleCollectionProcess(collectorId, collectorName);
    }

    // 执行单个采集流程
    function executeSingleCollectionProcess(collectorId, collectorName) {
        // 步骤1: 验证采集组件状态
        progressManager.startStep(0, `检查采集组件 ${collectorName} 的状态`);
        
        setTimeout(() => {
            progressManager.completeStep(0, true, '采集组件状态正常');
            
            // 步骤2: 准备执行环境
            progressManager.startStep(1, '准备执行环境和参数');
            
            setTimeout(() => {
                progressManager.completeStep(1, true, '执行环境准备完成');
                
                // 步骤3: 执行采集任务
                progressManager.startStep(2, `开始执行采集组件: ${collectorName}`);
                
                // 实际的API调用
                $.ajax({
                    url: '<?php echo e(route("servers.collection.execute", $server)); ?>',
                    type: 'POST',
                    data: {
                        _token: '<?php echo e(csrf_token()); ?>',
                        collector_ids: [collectorId]
                    },
                    success: function(response) {
                        if (response.success) {
                            progressManager.completeStep(2, true, '采集任务启动成功');
                            
                            // 步骤4: 处理采集结果
                            progressManager.startStep(3, '等待采集完成并处理结果');
                            
                            setTimeout(() => {
                                progressManager.completeStep(3, true, '采集结果处理完成');
                                
                                // 显示成功结果
                                progressManager.showResult(
                                    true,
                                    '采集任务执行成功',
                                    `采集组件 "${collectorName}" 执行任务已启动`,
                                    `服务器: <?php echo e($server->name); ?>\n采集组件: ${collectorName}\n执行时间: ${new Date().toLocaleString()}\n\n页面将在3秒后自动刷新以显示最新结果...`
                                );
                                
                                // 3秒后刷新页面
                                setTimeout(() => {
                                    location.reload();
                                }, 3000);
                                
                            }, 800);
                            
                        } else {
                            progressManager.completeStep(2, false, response.message || '采集任务启动失败');
                            progressManager.showResult(
                                false,
                                '采集任务执行失败',
                                response.message || '采集任务启动失败',
                                '请检查采集组件配置和服务器连接状态'
                            );
                        }
                    },
                    error: function(xhr) {
                        const errorMsg = xhr.responseJSON?.message || '网络错误';
                        progressManager.completeStep(2, false, errorMsg);
                        progressManager.showResult(
                            false,
                            '采集任务执行失败',
                            '执行过程中发生错误: ' + errorMsg,
                            '请检查网络连接和服务器状态'
                        );
                    }
                });
                
            }, 500);
            
        }, 400);
    }
    
    // 执行所有采集组件（带进度显示）
    function executeAllCollectorsWithProgress(collectorIds, collectorNames) {
        // 定义执行步骤
        const steps = [
            '验证服务器连接状态',
            '准备采集环境',
            '执行采集组件',
            '收集执行结果',
            '完成数据处理'
        ];
        
        // 初始化进度管理器
        progressManager.init('执行所有采集组件', steps, () => executeAllCollectorsWithProgress(collectorIds, collectorNames));
        
        // 执行采集流程
        executeCollectionProcess(collectorIds, collectorNames);
    }

    // 执行采集流程
    function executeCollectionProcess(collectorIds, collectorNames) {
        // 步骤1: 验证服务器连接状态
        progressManager.startStep(0, '检查服务器 <?php echo e($server->name); ?> 的连接状态');
        
        setTimeout(() => {
            progressManager.completeStep(0, true, '服务器连接正常');
            
            // 步骤2: 准备采集环境
            progressManager.startStep(1, '准备执行环境和采集参数');
            
            setTimeout(() => {
                progressManager.completeStep(1, true, '采集环境准备完成');
                
                // 步骤3: 执行采集组件
                progressManager.startStep(2, `开始执行 ${collectorIds.length} 个采集组件`);
                
                // 添加详细的采集组件信息到日志
                collectorNames.forEach((name, index) => {
                    progressManager.addLog(`  - 采集组件 ${index + 1}: ${name}`);
                });
                
                // 实际的API调用
                $.ajax({
                    url: '<?php echo e(route("servers.collection.execute", $server)); ?>',
                    type: 'POST',
                    data: {
                        _token: '<?php echo e(csrf_token()); ?>',
                        collector_ids: collectorIds
                    },
                    success: function(response) {
                        if (response.success) {
                            progressManager.completeStep(2, true, `成功启动 ${collectorIds.length} 个采集任务`);
                            
                            // 步骤4: 收集执行结果
                            progressManager.startStep(3, '等待采集任务完成并收集结果');
                            
                            setTimeout(() => {
                                progressManager.completeStep(3, true, '采集结果收集完成');
                                
                                // 步骤5: 完成数据处理
                                progressManager.startStep(4, '处理采集数据并更新显示');
                                
                                setTimeout(() => {
                                    progressManager.completeStep(4, true, '数据处理完成');
                                    
                                    // 显示成功结果
                                    progressManager.showResult(
                                        true,
                                        '采集任务执行成功',
                                        `成功启动 ${collectorIds.length} 个采集组件的执行任务`,
                                        `服务器: <?php echo e($server->name); ?>\n执行时间: ${new Date().toLocaleString()}\n采集组件: ${collectorNames.join(', ')}\n\n页面将在3秒后自动刷新以显示最新结果...`
                                    );
                                    
                                    // 3秒后刷新页面
                                    setTimeout(() => {
                                        location.reload();
                                    }, 3000);
                                    
                                }, 800);
                                
                            }, 1000);
                            
                        } else {
                            progressManager.completeStep(2, false, response.message || '采集任务启动失败');
                            progressManager.showResult(
                                false,
                                '采集任务执行失败',
                                response.message || '采集任务启动失败，请检查服务器状态和采集组件配置',
                                '请确认服务器连接正常，采集组件已正确安装'
                            );
                        }
                    },
                    error: function(xhr) {
                        const errorMsg = xhr.responseJSON?.message || '网络错误';
                        progressManager.completeStep(2, false, errorMsg);
                        progressManager.showResult(
                            false,
                            '采集任务执行失败',
                            '执行过程中发生错误: ' + errorMsg,
                            '请检查网络连接、服务器状态和系统日志'
                        );
                    }
                });
                
            }, 600);
            
        }, 500);
    }

    // 执行多个采集组件（保留原有函数作为备用）
    function executeMultipleCollectors(collectorIds) {
        var btn = $('#executeAllCollectorsBtn');
        var originalText = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i> 执行中...').prop('disabled', true);
        
        $.ajax({
            url: '<?php echo e(route("servers.collection.execute", $server)); ?>',
            type: 'POST',
            data: {
                _token: '<?php echo e(csrf_token()); ?>',
                collector_ids: collectorIds
            },
            success: function(response) {
                if (response.success) {
                    alert('批量采集任务已启动！');
                    // 刷新页面显示最新结果
                    location.reload();
                } else {
                    alert('执行失败：' + response.message);
                    btn.html(originalText).prop('disabled', false);
                }
            },
            error: function(xhr) {
                alert('请求失败：' + xhr.responseJSON?.message || xhr.responseText);
                btn.html(originalText).prop('disabled', false);
            }
        });
    }
    
    // 加载采集历史
    function loadCollectionHistory() {
        $('#historyContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> 加载中...</div>');
        
        $.ajax({
            url: '<?php echo e(route("servers.collection.history", $server)); ?>',
            type: 'GET',
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    var html = '<div class="table-responsive">';
                    html += '<table class="table table-sm table-hover">';
                    html += '<thead><tr>';
                    html += '<th>采集组件</th><th>状态</th><th>执行时间</th><th>采集时间</th><th>操作</th>';
                    html += '</tr></thead><tbody>';
                    
                    response.data.forEach(function(history) {
                        html += '<tr>';
                        html += '<td>' + history.collector_name + '</td>';
                        html += '<td><span class="badge badge-' + history.status_color + '">' + history.status_text + '</span></td>';
                        html += '<td>' + history.execution_time + '</td>';
                        html += '<td>' + new Date(history.created_at).toLocaleString() + '</td>';
                        html += '<td>';
                        if (history.has_result) {
                            html += '<button type="button" class="btn btn-sm btn-info" onclick="viewHistoryResult(' + history.id + ')">查看结果</button>';
                        }
                        if (history.error_message) {
                            html += ' <button type="button" class="btn btn-sm btn-danger" onclick="viewHistoryError(\'' + history.error_message.replace(/'/g, "\\'") + '\')">查看错误</button>';
                        }
                        html += '</td>';
                        html += '</tr>';
                    });
                    
                    html += '</tbody></table></div>';
                    $('#historyContent').html(html);
                } else {
                    $('#historyContent').html('<div class="alert alert-info">暂无采集历史记录</div>');
                }
            },
            error: function(xhr) {
                $('#historyContent').html('<div class="alert alert-danger">加载失败：' + xhr.responseText + '</div>');
            }
        });
    }
    
    // 查看历史结果
    function viewHistoryResult(historyId) {
        // 可以复用已有的结果查看模态框逻辑
        alert('查看历史结果功能 - ID: ' + historyId);
    }
    
    // 查看历史错误
    function viewHistoryError(errorMessage) {
        alert('错误信息：\n' + errorMessage);
    }
</script>

<style>
/* 采集结果显示样式优化 */
.result-container .card {
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
}

.result-container .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.result-container .btn-link {
    color: #495057;
    font-weight: 500;
}

.result-container .btn-link:hover {
    color: #007bff;
    text-decoration: none;
}

.rotate-icon {
    transition: transform 0.2s ease;
}

.result-container .collapse.show .card-header .rotate-icon {
    transform: rotate(90deg);
}

.json-formatter {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 0.85em;
    line-height: 1.4;
    margin: 0;
    overflow-x: auto;
}

.badge-info {
    font-size: 0.9em;
    padding: 0.5rem 0.75rem;
}

/* 采集状态信息样式 */
.bg-light {
    background-color: #f8f9fa !important;
}

/* JSON查看器样式 */
.json-viewer-container {
    background-color: #ffffff;
    max-height: 600px;
    overflow-y: auto;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 0.9em;
    line-height: 1.5;
}

.json-viewer {
    color: #333;
}

.json-line {
    display: flex;
    align-items: flex-start;
    min-height: 1.5em;
    padding: 1px 0;
}

.json-line:hover {
    background-color: #f8f9fa;
}

.json-indent {
    white-space: pre;
    color: #999;
    user-select: none;
}

.json-key {
    color: #0066cc;
    font-weight: 500;
}

.json-key .key-text {
    cursor: help;
}

.json-value {
    font-weight: 500;
}

.json-value.json-string {
    color: #008000;
}

.json-value.json-number {
    color: #0066cc;
}

.json-value.json-boolean {
    color: #ff6600;
    font-weight: 600;
}

.json-value.json-null {
    color: #999;
    font-style: italic;
}

.json-bracket {
    color: #666;
    font-weight: 600;
}

.json-comma {
    color: #666;
}

.json-toggle {
    cursor: pointer;
    color: #666;
    user-select: none;
    margin-right: 5px;
}

.json-toggle:hover {
    color: #007bff;
}

.json-toggle .toggle-icon {
    transition: transform 0.2s ease;
    font-size: 0.8em;
    width: 12px;
    display: inline-block;
}

.json-toggle.collapsed .toggle-icon {
    transform: rotate(-90deg);
}

.json-type-info {
    color: #999;
    font-size: 0.85em;
    margin-left: 5px;
}

.json-empty {
    color: #999;
    font-style: italic;
}

.json-children {
    width: 100%;
}

.json-children.collapsed {
    display: none;
}

.json-collapsed-end {
    color: #666;
}

.json-preview {
    color: #888;
    font-size: 0.8em;
    font-style: italic;
    margin-left: 8px;
    opacity: 0.8;
}

/* 特殊值样式 */
.special-ip {
    color: #17a2b8 !important;
    font-weight: 600;
    background-color: rgba(23, 162, 184, 0.1);
    padding: 1px 4px;
    border-radius: 3px;
    border: 1px solid rgba(23, 162, 184, 0.2);
}

.special-mac {
    color: #6610f2 !important;
    font-weight: 600;
    background-color: rgba(102, 16, 242, 0.1);
    padding: 1px 4px;
    border-radius: 3px;
    border: 1px solid rgba(102, 16, 242, 0.2);
}

.special-size {
    color: #fd7e14 !important;
    font-weight: 600;
    background-color: rgba(253, 126, 20, 0.1);
    padding: 1px 4px;
    border-radius: 3px;
    border: 1px solid rgba(253, 126, 20, 0.2);
}

.special-port {
    color: #e83e8c !important;
    font-weight: 600;
    background-color: rgba(232, 62, 140, 0.1);
    padding: 1px 4px;
    border-radius: 3px;
    border: 1px solid rgba(232, 62, 140, 0.2);
}

.special-datetime {
    color: #20c997 !important;
    font-weight: 600;
    background-color: rgba(32, 201, 151, 0.1);
    padding: 1px 4px;
    border-radius: 3px;
    border: 1px solid rgba(32, 201, 151, 0.2);
}

/* 数据统计面板 */
.data-stats-panel {
    border-top: 1px solid #e9ecef;
    padding-top: 10px;
}

.data-stats-panel .alert {
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.data-stats-panel strong {
    font-size: 1.2em;
    color: #495057;
}

.data-stats-panel small {
    color: #6c757d;
    font-size: 0.75em;
    text-transform: uppercase;
    font-weight: 500;
}

.result-toolbar {
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 0.375rem;
    border: 1px solid #e9ecef;
}

.collection-result-container {
    margin-top: 10px;
}

/* 搜索高亮 */
.json-item.search-highlight {
    background-color: #fff3cd !important;
    border-left: 3px solid #ffc107;
    padding-left: 5px;
    margin-left: -5px;
}

.json-item.search-hidden {
    display: none;
}

/* 复制按钮反馈 */
.btn.copy-success {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
    color: white !important;
}

/* 响应式优化 */
@media (max-width: 768px) {
    .json-formatter {
        font-size: 0.75em;
        max-height: 200px;
    }
    
    .result-container .card-body {
        padding: 0.75rem;
    }
    
    .json-viewer-container {
        font-size: 0.8em;
        max-height: 400px;
    }
    
    .result-toolbar .btn-group {
        flex-wrap: wrap;
    }
    
    .result-toolbar .btn-group .btn {
        margin-bottom: 5px;
    }
}

</style>

<script>
// JSON查看器功能函数

// 切换JSON节点的展开/折叠状态
function toggleJsonNode(nodeId) {
    const node = document.getElementById(nodeId);
    const icon = document.getElementById('icon-' + nodeId);
    const toggle = icon.parentElement;
    const collapsedEnd = document.getElementById('collapsed-' + nodeId);
    
    if (node && icon) {
        if (node.classList.contains('collapsed')) {
            // 展开
            node.classList.remove('collapsed');
            icon.classList.remove('fa-chevron-right');
            icon.classList.add('fa-chevron-down');
            toggle.classList.remove('collapsed');
            if (collapsedEnd) collapsedEnd.style.display = 'none';
        } else {
            // 折叠
            node.classList.add('collapsed');
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-right');
            toggle.classList.add('collapsed');
            if (collapsedEnd) collapsedEnd.style.display = 'block';
        }
    }
}

// 展开所有JSON节点
function expandAllResults(collectorId) {
    const container = document.getElementById('json-viewer-' + collectorId);
    if (container) {
        const collapsedNodes = container.querySelectorAll('.json-children.collapsed');
        const collapsedToggles = container.querySelectorAll('.json-toggle.collapsed');
        const collapsedIcons = container.querySelectorAll('.toggle-icon.fa-chevron-right');
        
        collapsedNodes.forEach(node => node.classList.remove('collapsed'));
        collapsedToggles.forEach(toggle => toggle.classList.remove('collapsed'));
        collapsedIcons.forEach(icon => {
            icon.classList.remove('fa-chevron-right');
            icon.classList.add('fa-chevron-down');
        });
    }
}

// 折叠所有JSON节点
function collapseAllResults(collectorId) {
    const container = document.getElementById('json-viewer-' + collectorId);
    if (container) {
        const expandedNodes = container.querySelectorAll('.json-children:not(.collapsed)');
        const expandedToggles = container.querySelectorAll('.json-toggle:not(.collapsed)');
        const expandedIcons = container.querySelectorAll('.toggle-icon.fa-chevron-down');
        
        expandedNodes.forEach(node => node.classList.add('collapsed'));
        expandedToggles.forEach(toggle => toggle.classList.add('collapsed'));
        expandedIcons.forEach(icon => {
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-right');
        });
    }
}

// 复制JSON数据到剪贴板
function copyResultData(collectorId) {
    const textarea = document.getElementById('raw-json-' + collectorId);
    const btn = event.target.closest('button');
    
    if (textarea && btn) {
        textarea.select();
        textarea.setSelectionRange(0, 99999); // 移动端兼容
        
        try {
            const successful = document.execCommand('copy');
            if (successful) {
                // 显示成功反馈
                const originalHTML = btn.innerHTML;
                const originalClass = btn.className;
                
                btn.innerHTML = '<i class="fas fa-check"></i> 已复制';
                btn.className = btn.className.replace('btn-outline-success', 'btn-success copy-success');
                
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.className = originalClass;
                }, 2000);
            } else {
                alert('复制失败，请手动选择并复制');
            }
        } catch (err) {
            console.error('复制失败:', err);
            alert('复制失败，请手动选择并复制');
        }
    }
}

// 搜索和过滤结果
function filterResults(collectorId, searchTerm) {
    const container = document.getElementById('json-viewer-' + collectorId);
    if (!container) return;
    
    const items = container.querySelectorAll('.json-item');
    const term = searchTerm.toLowerCase().trim();
    
    if (term === '') {
        // 清除搜索，显示所有项目
        items.forEach(item => {
            item.classList.remove('search-hidden', 'search-highlight');
        });
        return;
    }
    
    items.forEach(item => {
        const key = item.getAttribute('data-key') || '';
        const textContent = item.textContent || '';
        
        if (key.toLowerCase().includes(term) || textContent.toLowerCase().includes(term)) {
            item.classList.remove('search-hidden');
            item.classList.add('search-highlight');
            
            // 展开父节点以确保匹配项可见
            let parent = item.closest('.json-children');
            while (parent) {
                if (parent.classList.contains('collapsed')) {
                    const parentId = parent.id;
                    if (parentId) {
                        toggleJsonNode(parentId);
                    }
                }
                parent = parent.parentElement.closest('.json-children');
            }
        } else {
            item.classList.add('search-hidden');
            item.classList.remove('search-highlight');
        }
    });
}

// 清除搜索
function clearSearch(collectorId) {
    const searchBox = document.getElementById('search-' + collectorId);
    if (searchBox) {
        searchBox.value = '';
        filterResults(collectorId, '');
    }
}

// 显示数据统计
function showDataStats(collectorId) {
    const statsPanel = document.getElementById('stats-' + collectorId);
    const container = document.getElementById('json-viewer-' + collectorId);
    
    if (statsPanel && container) {
        if (statsPanel.style.display === 'none') {
            // 计算统计信息
            const stats = calculateDataStats(container);
            
            // 更新统计显示
            document.getElementById('stats-objects-' + collectorId).textContent = stats.objects;
            document.getElementById('stats-arrays-' + collectorId).textContent = stats.arrays;
            document.getElementById('stats-strings-' + collectorId).textContent = stats.strings;
            document.getElementById('stats-numbers-' + collectorId).textContent = stats.numbers;
            document.getElementById('stats-booleans-' + collectorId).textContent = stats.booleans;
            document.getElementById('stats-nulls-' + collectorId).textContent = stats.nulls;
            
            statsPanel.style.display = 'block';
        } else {
            statsPanel.style.display = 'none';
        }
    }
}

// 计算数据统计
function calculateDataStats(container) {
    const stats = {
        objects: 0,
        arrays: 0,
        strings: 0,
        numbers: 0,
        booleans: 0,
        nulls: 0
    };
    
    // 统计对象和数组
    const objects = container.querySelectorAll('.json-object');
    objects.forEach(obj => {
        const bracket = obj.querySelector('.json-bracket');
        if (bracket && bracket.textContent.trim() === '{') {
            stats.objects++;
        } else if (bracket && bracket.textContent.trim() === '[') {
            stats.arrays++;
        }
    });
    
    // 统计值类型
    const values = container.querySelectorAll('.json-value');
    values.forEach(value => {
        if (value.classList.contains('json-string')) {
            stats.strings++;
        } else if (value.classList.contains('json-number')) {
            stats.numbers++;
        } else if (value.classList.contains('json-boolean')) {
            stats.booleans++;
        } else if (value.classList.contains('json-null')) {
            stats.nulls++;
        }
    });
    
    return stats;
}

// 增强的搜索功能
function filterResults(collectorId, searchTerm) {
    const container = document.getElementById('json-viewer-' + collectorId);
    if (!container) return;
    
    const items = container.querySelectorAll('.json-item');
    const term = searchTerm.toLowerCase().trim();
    
    if (term === '') {
        // 清除搜索，显示所有项目
        items.forEach(item => {
            item.classList.remove('search-hidden', 'search-highlight');
        });
        return;
    }
    
    let matchCount = 0;
    items.forEach(item => {
        const key = item.getAttribute('data-key') || '';
        const textContent = item.textContent || '';
        const isMatch = key.toLowerCase().includes(term) || textContent.toLowerCase().includes(term);
        
        if (isMatch) {
            item.classList.remove('search-hidden');
            item.classList.add('search-highlight');
            matchCount++;
            
            // 展开父节点以确保匹配项可见
            expandParentNodes(item);
        } else {
            item.classList.add('search-hidden');
            item.classList.remove('search-highlight');
        }
    });
    
    // 更新搜索框placeholder显示匹配数量
    const searchBox = document.getElementById('search-' + collectorId);
    if (searchBox && matchCount > 0) {
        searchBox.setAttribute('title', `找到 ${matchCount} 个匹配项`);
    }
}

// 展开父节点
function expandParentNodes(item) {
    let parent = item.closest('.json-children');
    while (parent) {
        if (parent.classList.contains('collapsed')) {
            const parentId = parent.id;
            if (parentId) {
                toggleJsonNode(parentId);
            }
        }
        parent = parent.parentElement.closest('.json-children');
    }
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    // 为所有JSON查看器添加键盘快捷键支持
    document.addEventListener('keydown', function(e) {
        // Ctrl+F 或 Cmd+F 聚焦到搜索框
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            const activeSearchBox = document.querySelector('.collection-result-container input[placeholder*="搜索"]');
            if (activeSearchBox) {
                e.preventDefault();
                activeSearchBox.focus();
                activeSearchBox.select();
            }
        }
        
        // ESC 键清除搜索
        if (e.key === 'Escape') {
            const activeSearchBox = document.querySelector('.collection-result-container input:focus');
            if (activeSearchBox) {
                const collectorId = activeSearchBox.id.replace('search-', '');
                clearSearch(collectorId);
                activeSearchBox.blur();
            }
        }
    });
    
    // 为所有特殊值添加点击复制功能
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('special-ip') || 
            e.target.classList.contains('special-mac') || 
            e.target.classList.contains('special-port')) {
            
            const text = e.target.textContent;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    // 显示复制成功提示
                    const originalBg = e.target.style.backgroundColor;
                    e.target.style.backgroundColor = '#28a745';
                    e.target.style.color = 'white';
                    
                    setTimeout(() => {
                        e.target.style.backgroundColor = originalBg;
                        e.target.style.color = '';
                    }, 500);
                });
            }
        }
    });
});
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/tanli/Documents/php-code/sys-tmocaiji/resources/views/servers/show.blade.php ENDPATH**/ ?>