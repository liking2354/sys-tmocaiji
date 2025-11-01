<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\WebSocket\TerminalServer;

class StartTerminalServer extends Command
{
    /**
     * 命令的名称和签名
     *
     * @var string
     */
    protected $signature = 'terminal:start {--port= : WebSocket 服务器端口}';

    /**
     * 命令的描述
     *
     * @var string
     */
    protected $description = '启动 WebSocket 终端服务器';

    /**
     * 执行命令
     *
     * @return int
     */
    public function handle()
    {
        // 从命令行参数或配置文件获取端口
        $port = $this->option('port') ?: config('websocket.terminal.port', 9000);
        $host = config('websocket.terminal.host', '0.0.0.0');

        $this->info("启动 WebSocket 终端服务器...");
        $this->info("监听地址: {$host}:{$port}");

        try {
            $loop = \React\EventLoop\Factory::create();

            $webSocket = new IoServer(
                new HttpServer(
                    new WsServer(
                        new TerminalServer($loop)
                    )
                ),
                new \React\Socket\SocketServer("{$host}:{$port}", [], $loop),
                $loop
            );

            $this->info("✓ WebSocket 服务器已启动");
            $this->info("✓ 监听地址: ws://{$host}:{$port}");
            $this->info("✓ 按 Ctrl+C 停止服务器");

            $loop->run();

        } catch (\Exception $e) {
            $this->error('启动失败: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
