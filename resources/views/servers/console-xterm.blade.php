@extends('layouts.app')

@section('title', $server->name . ' - 服务器控制台')

@section('content')
<div class="container-fluid">
    <!-- 页面标题和操作按钮 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0">
                <i class="fas fa-terminal text-primary"></i> 服务器控制台
            </h1>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('servers.show', $server) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-arrow-left"></i> 返回服务器详情
            </a>
            <button type="button" class="btn btn-primary btn-sm" id="disconnect-btn" style="display: none;">
                <i class="fas fa-plug"></i> 断开连接
            </button>
            <button type="button" class="btn btn-primary btn-sm" id="clear-btn" style="display: none;">
                <i class="fas fa-trash"></i> 清空屏幕
            </button>
        </div>
    </div>
    
    <div class="card card-primary shadow-sm">
        <div class="card-header bg-dark text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-terminal"></i> 
                    <span id="terminal-title">{{ $server->username . '@' . $server->ip }}</span>
                    <span class="badge badge-success" id="connection-status">连接中...</span>
                </h5>
                <span class="badge badge-light">SSH端口: {{ $server->port }}</span>
            </div>
        </div>
        <div class="card-body bg-dark p-0" style="height: 500px; overflow: hidden;">
            <div id="terminal" style="height: 100%; width: 100%;"></div>
        </div>
    </div>
</div>

<!-- xterm.js CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/xterm@4.18.0/css/xterm.css" />

<style>
    /* 按钮间距 */
    .d-flex.gap-2 > * {
        margin-right: 0.5rem;
    }
    .d-flex.gap-2 > *:last-child {
        margin-right: 0;
    }

    #terminal {
        padding: 10px;
    }

    .xterm {
        font-family: "Courier New", Courier, monospace;
        font-size: 14px;
        line-height: 1.5;
    }

    .xterm-screen {
        background-color: #1e1e1e;
    }

    .xterm-viewport {
        background-color: #1e1e1e;
    }

    /* 光标闪动动画 */
    @keyframes blink {
        0%, 49% {
            opacity: 1;
        }
        50%, 100% {
            opacity: 0;
        }
    }

    .xterm-cursor-block {
        background-color: #d4d4d4;
        color: #1e1e1e;
        animation: blink 1s infinite;
    }

    .xterm-cursor-bar {
        background-color: #d4d4d4;
        animation: blink 1s infinite;
    }

    .xterm-cursor-underline {
        border-bottom: 2px solid #d4d4d4;
        animation: blink 1s infinite;
    }
</style>

@push('scripts')
<!-- xterm.js JS -->
<script src="https://cdn.jsdelivr.net/npm/xterm@4.18.0/lib/xterm.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xterm-addon-fit@0.5.0/lib/xterm-addon-fit.js"></script>

<script>
    $(document).ready(function() {
        // 配置
        const config = {
            serverId: {{ $server->id }},
            serverIp: '{{ $server->ip }}',
            serverUsername: '{{ $server->username }}',
            serverPort: {{ $server->port }},
            wsProtocol: '{{ $websocketConfig['protocol'] }}',
            wsHost: window.location.hostname,
            wsPort: {{ $websocketConfig['port'] }},
            csrfToken: '{{ csrf_token() }}'
        };

        // 初始化 xterm
        const term = new Terminal({
            cols: 120,
            rows: 30,
            fontSize: 14,
            fontFamily: '"Courier New", Courier, monospace',
            cursorBlink: true,
            cursorStyle: 'block',
            theme: {
                background: '#1e1e1e',
                foreground: '#d4d4d4',
                cursor: '#d4d4d4',
                cursorAccent: '#1e1e1e',
                selection: 'rgba(255, 255, 255, 0.3)',
                black: '#000000',
                red: '#cd3131',
                green: '#0dbc79',
                yellow: '#e5e510',
                blue: '#2b91f7',
                magenta: '#bc3fbc',
                cyan: '#11a8cd',
                white: '#e5e5e5',
                brightBlack: '#666666',
                brightRed: '#f14c4c',
                brightGreen: '#23d18b',
                brightYellow: '#f5f543',
                brightBlue: '#3b8eea',
                brightMagenta: '#d670d6',
                brightCyan: '#29b8db',
                brightWhite: '#ffffff'
            },
            scrollback: 1000,
            tabStopWidth: 8,
            rendererType: 'canvas',
            willReadFrequently: true
        });

        const fitAddon = new FitAddon.FitAddon();
        term.loadAddon(fitAddon);

        // 挂载终端
        term.open(document.getElementById('terminal'));
        fitAddon.fit();

        // WebSocket 连接
        let ws = null;
        let isConnected = false;
        let currentLineInput = '';

        function connectWebSocket() {
            const wsUrl = `${config.wsProtocol}//${config.wsHost}:${config.wsPort}`;
            
            term.writeln('正在连接到 WebSocket 服务器...');
            term.writeln(`地址: ${wsUrl}`);
            term.writeln('');

            ws = new WebSocket(wsUrl);

            ws.onopen = function() {
                term.writeln('✓ WebSocket 连接成功');
                term.writeln('正在连接到 SSH 服务器...');
                term.writeln('');

                // 发送连接请求
                ws.send(JSON.stringify({
                    action: 'connect',
                    server_id: config.serverId,
                    token: config.csrfToken
                }));
            };

            ws.onmessage = function(event) {
                try {
                    const data = JSON.parse(event.data);
                    console.log('收到消息:', data.type, data);

                    switch (data.type) {
                        case 'connected':
                            isConnected = true;
                            updateConnectionStatus('已连接', 'success');
                            term.writeln('✓ ' + data.message);
                            $('#disconnect-btn').show();
                            $('#clear-btn').show();
                            term.focus();
                            break;

                        case 'autocomplete':
                            // 处理自动补全
                            if (data.completion) {
                                // 清除当前输入
                                for (let i = 0; i < currentLineInput.length; i++) {
                                    term.write('\b \b');
                                }
                                // 写入补全后的内容
                                currentLineInput = data.completion;
                                term.write(currentLineInput);
                            }
                            break;

                        case 'shell_output':
                            // Shell 模式：直接写入输出（不需要换行处理）
                            term.write(data.data);
                            break;

                        case 'output':
                            // 处理多行输出，保留原始格式
                            const lines = data.data.split('\n');
                            for (let i = 0; i < lines.length; i++) {
                                if (i < lines.length - 1) {
                                    term.writeln(lines[i]);
                                } else if (lines[i]) {
                                    term.writeln(lines[i]);
                                }
                            }
                            break;

                        case 'prompt':
                            // 直接显示提示符
                            console.log('显示提示符:', data.data);
                            term.write(data.data);
                            break;

                        case 'error':
                            term.writeln('\x1b[31m✗ 错误: ' + data.message + '\x1b[0m');
                            updateConnectionStatus('错误', 'danger');
                            break;

                        default:
                            console.log('未知消息类型:', data.type);
                    }
                } catch (e) {
                    console.error('消息解析错误:', e);
                }
            };

            ws.onerror = function(error) {
                term.writeln('\x1b[31m✗ WebSocket 错误\x1b[0m');
                updateConnectionStatus('错误', 'danger');
                console.error('WebSocket 错误:', error);
            };

            ws.onclose = function() {
                isConnected = false;
                updateConnectionStatus('已断开', 'secondary');
                term.writeln('\x1b[33m连接已断开\x1b[0m');
                $('#disconnect-btn').hide();
                $('#clear-btn').hide();
            };
        }

        // 处理终端输入
        term.onData(function(data) {
            if (!isConnected) {
                term.writeln('\x1b[31m未连接到服务器\x1b[0m');
                return;
            }

            // 处理 Tab 键（自动补全）
            if (data === '\t') {
                // 发送补全请求到服务器
                ws.send(JSON.stringify({
                    action: 'autocomplete',
                    input: currentLineInput
                }));
                return;
            }
            
            // 处理回车键
            if (data === '\r') {
                console.log('用户输入命令:', currentLineInput);
                
                // 先显示换行，将光标移到下一行
                term.writeln('');
                
                // 发送命令到服务器
                if (currentLineInput.trim()) {
                    ws.send(JSON.stringify({
                        action: 'input',
                        input: currentLineInput
                    }));
                } else {
                    // 空命令，直接显示提示符
                    const prompt = config.serverUsername + '@' + config.serverIp + ':~$ ';
                    term.write(prompt);
                }
                currentLineInput = '';
            } else if (data === '\u007f' || data === '\b') {
                // 处理退格键 (DEL 或 Backspace)
                if (currentLineInput.length > 0) {
                    currentLineInput = currentLineInput.slice(0, -1);
                    term.write('\b \b');
                }
            } else if (data === '\u0003') {
                // 处理 Ctrl+C
                currentLineInput = '';
                term.writeln('^C');
                const prompt = config.serverUsername + '@' + config.serverIp + ':~$ ';
                term.write(prompt);
            } else {
                // 其他输入：显示并记录
                currentLineInput += data;
                term.write(data);
            }
        });

        // 处理窗口大小调整
        $(window).on('resize', function() {
            try {
                fitAddon.fit();
                
                // 发送新的终端大小到服务器
                if (isConnected && ws) {
                    ws.send(JSON.stringify({
                        action: 'resize',
                        cols: term.cols,
                        rows: term.rows
                    }));
                }
            } catch (e) {
                console.error('调整终端大小时出错:', e);
            }
        });

        // 断开连接按钮
        $('#disconnect-btn').click(function() {
            if (ws) {
                ws.send(JSON.stringify({
                    action: 'disconnect'
                }));
                ws.close();
            }
        });

        // 清空屏幕按钮
        $('#clear-btn').click(function() {
            term.clear();
        });

        // 更新连接状态
        function updateConnectionStatus(status, badgeClass) {
            $('#connection-status')
                .removeClass('badge-light badge-success badge-danger badge-secondary')
                .addClass('badge-' + badgeClass)
                .text(status);
        }

        // 初始化连接
        connectWebSocket();

        // 页面卸载时断开连接
        $(window).on('beforeunload', function() {
            if (ws) {
                ws.close();
            }
        });
    });
</script>
@endpush
@endsection
