# WebSocket 终端服务器管理脚本

本目录包含用于管理 WebSocket 终端服务器的脚本。

## 📁 脚本列表

- `start.sh` - 启动终端服务器
- `stop.sh` - 停止终端服务器
- `status.sh` - 查看服务器状态

## ⚙️ 配置

WebSocket 终端服务器的配置位于 `.env` 文件中：

```env
# WebSocket 终端服务器配置
WEBSOCKET_TERMINAL_HOST=0.0.0.0
WEBSOCKET_TERMINAL_PORT=9000
WEBSOCKET_TERMINAL_PROTOCOL=ws
```

详细配置说明请参考：[WebSocket 配置文档](../../docs/WEBSOCKET_CONFIG.md)

## 🚀 使用方法

### 启动服务器

```bash
# 使用 .env 配置的端口启动
./scripts/terminal/start.sh

# 使用命令行参数指定端口（优先级高于 .env）
./scripts/terminal/start.sh 9000
```

**功能特性：**
- 自动检测并停止已存在的进程
- 自动清理占用的端口
- 后台运行，不阻塞终端
- 自动保存进程 PID
- 输出日志到文件

### 停止服务器

```bash
./scripts/terminal/stop.sh
```

**功能特性：**
- 优雅停止进程
- 超时后强制 kill
- 自动清理 PID 文件

### 查看状态

```bash
./scripts/terminal/status.sh
```

**显示信息：**
- 运行状态
- 进程 PID
- CPU 和内存占用
- 运行时长
- 监听端口
- 日志文件信息
- 最近日志内容

## 📂 相关文件

- **PID 文件**: `storage/terminal-server.pid`
- **日志文件**: `storage/logs/terminal-server.log`

## 📝 常用命令

```bash
# 查看实时日志
tail -f storage/logs/terminal-server.log

# 重启服务器
./scripts/terminal/stop.sh && ./scripts/terminal/start.sh

# 清空日志
> storage/logs/terminal-server.log

# 手动停止进程（如果脚本失败）
kill $(cat storage/terminal-server.pid)
```

## ⚠️ 注意事项

1. 确保脚本有执行权限：`chmod +x scripts/terminal/*.sh`
2. 首次运行会自动创建日志目录
3. 如果端口被占用，脚本会自动尝试清理
4. 建议定期清理日志文件，避免占用过多磁盘空间

## 🔧 故障排查

### 启动失败

1. 检查 PHP 是否安装：`php -v`
2. 查看日志文件：`cat storage/logs/terminal-server.log`
3. 检查端口是否被占用：`lsof -i :9000`

### 无法停止

1. 查看进程是否存在：`ps -p $(cat storage/terminal-server.pid)`
2. 手动强制停止：`kill -9 $(cat storage/terminal-server.pid)`
3. 清理 PID 文件：`rm storage/terminal-server.pid`

### 端口冲突

```bash
# 查找占用端口的进程
lsof -i :9000

# 停止占用进程
kill <PID>

# 或使用其他端口启动
./scripts/terminal/start.sh 9000
```
