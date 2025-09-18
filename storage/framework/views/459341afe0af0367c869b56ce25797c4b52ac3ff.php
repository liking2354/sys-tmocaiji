<?php $__env->startSection('title', $server->name . ' - 服务器控制台'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>服务器控制台 - <?php echo e($server->name); ?></h1>
        <div>
            <a href="<?php echo e(route('servers.show', $server)); ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 返回服务器详情
            </a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-dark text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-terminal"></i> <?php echo e($server->username); ?> <?php echo e($server->ip); ?></h5>
                <span class="badge badge-light">SSH端口: <?php echo e($server->port); ?></span>
            </div>
        </div>
        <div class="card-body bg-dark text-white">
            <div id="terminal-output" class="mb-3" style="height: 400px; overflow-y: auto; font-family: monospace; white-space: pre-wrap; background-color: #000; color: #fff; padding: 10px; border-radius: 5px;">
                <!-- 终端输出将显示在这里 -->
                <div class="text-success">连接到服务器 <?php echo e($server->ip); ?> (<?php echo e($server->name); ?>)...</div>
                <div class="text-success">用户: <?php echo e($server->username); ?></div>
                <div class="text-success">输入命令并按回车执行</div>
                <div class="text-muted">提示: 输入 'exit' 可以关闭会话</div>
            </div>
            
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-dark text-light border-0"><?php echo e($server->username); ?> <?php echo e($server->ip); ?> :~$</span>
                </div>
                <input type="text" id="command-input" class="form-control bg-dark text-light border-secondary" placeholder="输入命令...">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="button" id="execute-btn">
                        <i class="fas fa-play"></i> 执行
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
    $(document).ready(function() {
        const terminalOutput = $('#terminal-output');
        const commandInput = $('#command-input');
        const executeBtn = $('#execute-btn');
        const serverUsername = "<?php echo e($server->username); ?>";
        const serverIp = "<?php echo e($server->ip); ?>";
        
        // 命令历史记录
        const commandHistory = [];
        let historyIndex = -1;
        
        // 自动滚动到底部
        function scrollToBottom() {
            terminalOutput.scrollTop(terminalOutput[0].scrollHeight);
        }
        
        // 添加命令到终端
        function addCommand(command) {
            terminalOutput.append(`<div class="text-info">${serverUsername}@${serverIp}:~$ ${command}</div>`);
            scrollToBottom();
        }
        
        // 添加输出到终端
        function addOutput(output) {
            if (output && output.trim() !== '') {
                terminalOutput.append(`<div>${output}</div>`);
            } else {
                terminalOutput.append(`<div><em class="text-muted">命令执行成功，无输出</em></div>`);
            }
            scrollToBottom();
        }
        
        // 添加错误到终端
        function addError(error) {
            terminalOutput.append(`<div class="text-danger">${error}</div>`);
            scrollToBottom();
        }
        
        // 执行命令
        function executeCommand() {
            const command = commandInput.val().trim();
            
            if (!command) {
                return;
            }
            
            // 添加到历史记录
            commandHistory.push(command);
            historyIndex = commandHistory.length;
            
            // 显示命令
            addCommand(command);
            
            // 清空输入框
            commandInput.val('');
            
            // 如果是exit命令，返回服务器详情页
            if (command.toLowerCase() === 'exit') {
                addOutput('关闭会话...');
                setTimeout(function() {
                    window.location.href = '<?php echo e(route("servers.show", $server)); ?>';
                }, 1000);
                return;
            }
            
            // 禁用输入和按钮
            commandInput.prop('disabled', true);
            executeBtn.prop('disabled', true);
            
            // 发送AJAX请求执行命令
            $.ajax({
                url: '<?php echo e(route("servers.execute", $server)); ?>',
                type: 'POST',
                data: {
                    _token: '<?php echo e(csrf_token()); ?>',
                    command: command
                },
                success: function(response) {
                    if (response.success) {
                        // 处理命令输出，确保即使是空字符串也显示"无输出"提示
                        if (response.output === '命令执行成功，无输出' || response.output === '' || !response.output) {
                            addOutput('');  // 调用addOutput函数处理空输出情况
                        } else {
                            addOutput(response.output);
                        }
                    } else {
                        addError(response.message || '执行命令失败');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX错误:', xhr.responseText);
                    addError('执行命令时发生错误: ' + error);
                },
                complete: function() {
                    // 启用输入和按钮
                    commandInput.prop('disabled', false);
                    executeBtn.prop('disabled', false);
                    commandInput.focus();
                }
            });
        }
        
        // 绑定执行按钮点击事件
        executeBtn.click(executeCommand);
        
        // 绑定输入框回车事件
        commandInput.keypress(function(e) {
            if (e.which === 13) { // 回车键
                executeCommand();
                return false;
            }
        });
        
        // 绑定上下键浏览历史记录
        commandInput.keydown(function(e) {
            if (e.which === 38) { // 上键
                if (historyIndex > 0) {
                    historyIndex--;
                    commandInput.val(commandHistory[historyIndex]);
                }
                return false;
            } else if (e.which === 40) { // 下键
                if (historyIndex < commandHistory.length - 1) {
                    historyIndex++;
                    commandInput.val(commandHistory[historyIndex]);
                } else if (historyIndex === commandHistory.length - 1) {
                    historyIndex = commandHistory.length;
                    commandInput.val('');
                }
                return false;
            }
        });
        
        // 调整终端窗口大小
        $(window).resize(function() {
            const windowHeight = $(window).height();
            const terminalTop = terminalOutput.offset().top;
            const footerHeight = 150; // 估计的底部空间
            const newHeight = windowHeight - terminalTop - footerHeight;
            if (newHeight >= 200) { // 设置最小高度
                terminalOutput.css('height', newHeight + 'px');
            }
        }).resize(); // 立即触发一次
        
        // 初始化时聚焦输入框
        commandInput.focus();
    });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/tanli/Documents/php-code/sys-tmocaiji/resources/views/servers/console.blade.php ENDPATH**/ ?>