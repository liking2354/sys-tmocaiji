<?php $__env->startSection('title', '创建采集组件 - 服务器管理与数据采集系统'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>创建采集组件</h1>
        <a href="<?php echo e(route('collectors.index')); ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> 返回组件列表
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form action="<?php echo e(route('collectors.store')); ?>" method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">组件名称 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="name" name="name" value="<?php echo e(old('name')); ?>" required>
                            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="code">组件代码 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="code" name="code" value="<?php echo e(old('code')); ?>" required placeholder="例如：system_process">
                            <small class="form-text text-muted">唯一标识符，只能包含字母、数字和下划线</small>
                            <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="version">版本号</label>
                            <input type="text" class="form-control <?php $__errorArgs = ['version'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="version" name="version" value="<?php echo e(old('version', '1.0.0')); ?>" placeholder="例如：1.0.0">
                            <small class="form-text text-muted">采用语义化版本号，格式为：主版本.次版本.修订版本</small>
                            <?php $__errorArgs = ['version'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>组件类型 <span class="text-danger">*</span></label>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="type_script" name="type" value="script" class="custom-control-input" <?php echo e(old('type', 'script') == 'script' ? 'checked' : ''); ?> required>
                                <label class="custom-control-label" for="type_script">脚本类</label>
                                <small class="form-text text-muted">直接执行脚本，无需安装</small>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="type_program" name="type" value="program" class="custom-control-input" <?php echo e(old('type') == 'program' ? 'checked' : ''); ?> required>
                                <label class="custom-control-label" for="type_program">程序类</label>
                                <small class="form-text text-muted">需要上传程序文件并安装到服务器</small>
                            </div>
                            <?php $__errorArgs = ['type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="text-danger"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </div>
                
                <!-- 脚本类组件表单项 -->
                <div id="script_type_fields">
                    <div class="form-group">
                        <label for="script_file">上传脚本文件</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input <?php $__errorArgs = ['script_file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="script_file" name="script_file">
                            <label class="custom-file-label" for="script_file">选择文件</label>
                        </div>
                        <small class="form-text text-muted">支持的文件类型：.sh, .txt, .py, .pl, .rb, .js, .php</small>
                        <?php $__errorArgs = ['script_file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
                
                <!-- 程序类组件表单项 -->
                <div id="program_type_fields" style="display: none;">
                    <div class="form-group">
                        <label for="program_file">上传程序文件 <span class="text-danger">*</span></label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input <?php $__errorArgs = ['program_file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="program_file" name="program_file">
                            <label class="custom-file-label" for="program_file">选择文件</label>
                        </div>
                        <small class="form-text text-muted">支持的文件类型：压缩包、可执行文件等</small>
                        <?php $__errorArgs = ['program_file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">组件描述</label>
                    <textarea class="form-control <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="description" name="description" rows="2"><?php echo e(old('description')); ?></textarea>
                    <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                
                <div class="form-group" id="script_content_field">
                    <label for="script_content">采集脚本内容 <span class="text-danger">*</span></label>
                    <textarea class="form-control <?php $__errorArgs = ['script_content'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="script_content" name="script_content" rows="15"><?php echo e(old('script_content', "#!/bin/bash\n\n# 采集脚本模板\n# 输出必须是JSON格式\n\n# 错误处理\nset -e\n\n# 采集逻辑\n# ...\n# 输出结果\necho '{\n  \"status\": \"success\",\n  \"data\": {\n    \"example\": \"value\"\n  }\n}'\n")); ?></textarea>
                    <small class="form-text text-muted">采集脚本必须输出JSON格式的数据</small>
                    <?php $__errorArgs = ['script_content'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                
                <div class="form-group">
                    <label>状态</label>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="status_1" name="status" value="1" class="custom-control-input" <?php echo e(old('status', '1') == '1' ? 'checked' : ''); ?>>
                        <label class="custom-control-label" for="status_1">启用</label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="status_0" name="status" value="0" class="custom-control-input" <?php echo e(old('status') == '0' ? 'checked' : ''); ?>>
                        <label class="custom-control-label" for="status_0">禁用</label>
                    </div>
                    <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="text-danger"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 保存
                    </button>
                    <a href="<?php echo e(route('collectors.index')); ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> 取消
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
    $(document).ready(function() {
        // 文件上传显示文件名
        $('.custom-file-input').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').html(fileName);
        });
        
        // 根据组件类型显示/隐藏相应表单项
        function toggleTypeFields() {
            var type = $('input[name="type"]:checked').val();
            if (type === 'script') {
                $('#script_type_fields').show();
                $('#program_type_fields').hide();
                $('#script_content_field').show();
                $('#script_content').prop('required', true);
                $('#program_file').prop('required', false);
            } else {
                $('#script_type_fields').hide();
                $('#program_type_fields').show();
                $('#script_content_field').hide();
                $('#script_content').prop('required', false);
                $('#program_file').prop('required', true);
            }
        }
        
        // 初始化表单状态
        toggleTypeFields();
        
        // 监听类型选择变化
        $('input[name="type"]').change(function() {
            toggleTypeFields();
        });
    });
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/tanli/Documents/php-code/sys-tmocaiji/resources/views/collectors/create.blade.php ENDPATH**/ ?>