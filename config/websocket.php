<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WebSocket 终端服务器配置
    |--------------------------------------------------------------------------
    |
    | 这里配置 WebSocket 终端服务器的相关参数
    |
    */

    'terminal' => [
        // 服务器监听地址
        'host' => env('WEBSOCKET_TERMINAL_HOST', '0.0.0.0'),
        
        // 服务器监听端口
        'port' => env('WEBSOCKET_TERMINAL_PORT', 9000),
        
        // WebSocket 协议 (ws 或 wss)
        'protocol' => env('WEBSOCKET_TERMINAL_PROTOCOL', 'ws'),
        
        // 客户端连接超时时间（秒）
        'timeout' => env('WEBSOCKET_TERMINAL_TIMEOUT', 300),
        
        // 最大连接数
        'max_connections' => env('WEBSOCKET_TERMINAL_MAX_CONNECTIONS', 100),
        
        // 是否启用调试模式
        'debug' => env('WEBSOCKET_TERMINAL_DEBUG', false),
        
        // 终端滚动缓冲区大小（行数）
        'scrollback' => env('TERMINAL_SCROLLBACK', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | SSH 连接配置
    |--------------------------------------------------------------------------
    */

    'ssh' => [
        // SSH 连接超时时间（秒）
        'timeout' => env('SSH_TIMEOUT', 30),
    ],

];
