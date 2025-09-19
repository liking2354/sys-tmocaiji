<?php $__env->startSection('title', $collector->name . ' - 采集组件详情'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>采集组件详情</h1>
        <div>
            <a href="<?php echo e(route('collectors.edit', $collector)); ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> 编辑组件
            </a>
            <a href="<?php echo e(route('collectors.index')); ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 返回组件列表
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">基本信息</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 30%">ID:</th>
                            <td><?php echo e($collector->id); ?></td>
                        </tr>
                        <tr>
                            <th>组件名称:</th>
                            <td><?php echo e($collector->name); ?></td>
                        </tr>
                        <tr>
                            <th>组件代码:</th>
                            <td><code><?php echo e($collector->code); ?></code></td>
                        </tr>
                        <tr>
                            <th>类型:</th>
                            <td>
                                <span class="badge badge-<?php echo e($collector->type === 'script' ? 'info' : 'warning'); ?>">
                                    <?php echo e($collector->typeName); ?>

                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>版本:</th>
                            <td>
                                <span class="badge badge-info"><?php echo e($collector->version ?: '1.0.0'); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th>描述:</th>
                            <td><?php echo e($collector->description ?: '无'); ?></td>
                        </tr>
                        <tr>
                            <th>状态:</th>
                            <td>
                                <?php if($collector->status == 1): ?>
                                    <span class="badge badge-success">启用</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">禁用</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>创建时间:</th>
                            <td><?php echo e($collector->created_at->format('Y-m-d H:i:s')); ?></td>
                        </tr>
                        <tr>
                            <th>更新时间:</th>
                            <td><?php echo e($collector->updated_at->format('Y-m-d H:i:s')); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">部署信息</h5>
                </div>
                <div class="card-body">
                    <?php if($collector->deployment_config): ?>
                        <table class="table table-borderless">
                            <tr>
                                <th style="width: 40%">远程路径:</th>
                                <td><code><?php echo e($collector->deployment_config['remote_path'] ?? '/opt/collectors/'.$collector->code); ?></code></td>
                            </tr>
                            <tr>
                                <th>自动更新:</th>
                                <td>
                                    <?php if(isset($collector->deployment_config['auto_update']) && $collector->deployment_config['auto_update']): ?>
                                        <span class="badge badge-success">启用</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">禁用</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if(isset($collector->deployment_config['created_at'])): ?>
                                <tr>
                                    <th>配置创建时间:</th>
                                    <td><?php echo e(date('Y-m-d H:i:s', $collector->deployment_config['created_at'])); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if(isset($collector->deployment_config['updated_at'])): ?>
                                <tr>
                                    <th>配置更新时间:</th>
                                    <td><?php echo e(date('Y-m-d H:i:s', $collector->deployment_config['updated_at'])); ?></td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 暂无部署配置信息
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">关联的服务器</h5>
                        <span class="badge badge-light"><?php echo e($installedServers->count()); ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if($installedServers->count() > 0): ?>
                        <div class="list-group">
                            <?php $__currentLoopData = $installedServers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $server): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <a href="<?php echo e(route('servers.show', $server)); ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo e($server->name); ?></strong>
                                        <small class="d-block text-muted"><?php echo e($server->ip); ?></small>
                                        <?php if($server->pivot->installed_at): ?>
                                            <small class="text-muted">安装时间: <?php echo e(\Carbon\Carbon::parse($server->pivot->installed_at)->format('Y-m-d H:i:s')); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if($server->status == 1): ?>
                                            <span class="badge badge-success">在线</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">离线</span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 该组件未关联任何服务器
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><?php echo e($collector->isScriptType() ? '采集脚本内容' : '程序文件信息'); ?></h5>
                </div>
                <div class="card-body">
                    <?php if($collector->isScriptType()): ?>
                        <pre class="bg-light p-3 rounded"><code><?php echo e($collector->getScriptContent()); ?></code></pre>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <h5><i class="fas fa-file-archive"></i> 程序文件</h5>
                            <?php if($collector->file_path): ?>
                                <p class="mb-1"><strong>文件路径:</strong> <code><?php echo e($collector->file_path); ?></code></p>
                                <p class="mb-1"><strong>文件名:</strong> <code><?php echo e(basename($collector->file_path)); ?></code></p>
                                <p class="mb-0"><strong>上传时间:</strong> <?php echo e($collector->updated_at->format('Y-m-d H:i:s')); ?></p>
                            <?php else: ?>
                                <p class="mb-0">未上传程序文件</p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="alert alert-warning mt-3">
                            <h5><i class="fas fa-info-circle"></i> 程序类组件说明</h5>
                            <p class="mb-0">程序类组件需要上传到服务器并安装后才能使用。安装过程会自动处理程序文件的解压和权限设置。</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">组件使用说明</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> 使用指南</h5>
                        <p>此采集组件可用于收集服务器的系统信息和性能数据。</p>
                        <ul>
                            <li>对于脚本类组件，可直接在服务器上执行</li>
                            <li>对于程序类组件，需要先安装到服务器</li>
                        </ul>
                    </div>
                    
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6>输出数据格式示例</h6>
                            <pre><code>{
  "system": {
    "hostname": "server-name",
    "os": "Linux 5.4.0",
    "uptime": "10 days, 4 hours"
  },
  "resources": {
    "cpu": "23%",
    "memory": "45%",
    "disk": "32%"
  }
}</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('styles'); ?>
<style>
    pre code {
        white-space: pre-wrap;
        word-break: break-word;
    }
</style>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/tanli/Documents/php-code/sys-tmocaiji/resources/views/collectors/show.blade.php ENDPATH**/ ?>