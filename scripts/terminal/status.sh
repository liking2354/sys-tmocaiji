#!/bin/bash

# 启动 WebSocket 终端服务器脚本

# 获取脚本所在目录
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_DIR="$( cd "$SCRIPT_DIR/../.." && pwd )"

# 进入项目目录
cd "$PROJECT_DIR"

# 检查 PHP 是否安装
if ! command -v php &> /dev/null; then
    echo "错误: 未找到 PHP，请先安装 PHP"
    exit 1
fi

# 从 .env 文件读取默认端口
DEFAULT_PORT=9000
if [ -f "$PROJECT_DIR/.env" ]; then
    ENV_PORT=$(grep "^WEBSOCKET_TERMINAL_PORT=" "$PROJECT_DIR/.env" | cut -d '=' -f2)
    if [ -n "$ENV_PORT" ]; then
        DEFAULT_PORT=$ENV_PORT
    fi
fi

# 获取端口参数，优先使用命令行参数，否则使用 .env 配置
PORT=${1:-$DEFAULT_PORT}

# PID 文件路径
PID_FILE="$PROJECT_DIR/storage/terminal-server.pid"

# 日志文件路径
LOG_FILE="$PROJECT_DIR/storage/logs/terminal-server.log"

# 确保日志目录存在
mkdir -p "$PROJECT_DIR/storage/logs"

echo "=========================================="
echo "启动 WebSocket 终端服务器"
echo "=========================================="
echo "项目目录: $PROJECT_DIR"
echo "监听端口: $PORT"
echo "配置来源: $([ -n "$1" ] && echo "命令行参数" || echo ".env 文件")"
echo "WebSocket 地址: ws://0.0.0.0:$PORT"
echo "日志文件: $LOG_FILE"
echo "=========================================="
echo ""

# 检查是否已有进程在运行
if [ -f "$PID_FILE" ]; then
    OLD_PID=$(cat "$PID_FILE")
    if ps -p "$OLD_PID" > /dev/null 2>&1; then
        echo "检测到已存在的终端服务器进程 (PID: $OLD_PID)"
        echo "正在停止旧进程..."
        kill "$OLD_PID" 2>/dev/null
        
        # 等待进程结束，最多等待 5 秒
        for i in {1..5}; do
            if ! ps -p "$OLD_PID" > /dev/null 2>&1; then
                echo "旧进程已停止"
                break
            fi
            sleep 1
        done
        
        # 如果进程仍在运行，强制 kill
        if ps -p "$OLD_PID" > /dev/null 2>&1; then
            echo "强制停止旧进程..."
            kill -9 "$OLD_PID" 2>/dev/null
            sleep 1
        fi
    fi
    rm -f "$PID_FILE"
fi

# 检查端口是否被占用
if lsof -Pi :$PORT -sTCP:LISTEN -t >/dev/null 2>&1; then
    echo "警告: 端口 $PORT 已被占用"
    OCCUPYING_PID=$(lsof -Pi :$PORT -sTCP:LISTEN -t)
    echo "占用进程 PID: $OCCUPYING_PID"
    echo "正在尝试停止占用进程..."
    kill "$OCCUPYING_PID" 2>/dev/null
    sleep 2
    
    # 如果仍被占用，强制 kill
    if lsof -Pi :$PORT -sTCP:LISTEN -t >/dev/null 2>&1; then
        echo "强制停止占用进程..."
        kill -9 "$OCCUPYING_PID" 2>/dev/null
        sleep 1
    fi
fi

# 后台启动服务器
echo "正在后台启动终端服务器..."
nohup php artisan terminal:start --port=$PORT >> "$LOG_FILE" 2>&1 &

# 获取新进程的 PID
NEW_PID=$!

# 保存 PID 到文件
echo "$NEW_PID" > "$PID_FILE"

# 等待一下，检查进程是否成功启动
sleep 2

if ps -p "$NEW_PID" > /dev/null 2>&1; then
    echo ""
    echo "✓ 终端服务器启动成功！"
    echo "  PID: $NEW_PID"
    echo "  端口: $PORT"
    echo "  日志: $LOG_FILE"
    echo ""
    echo "查看日志: tail -f $LOG_FILE"
    echo "停止服务: kill $NEW_PID"
    echo "=========================================="
    exit 0
else
    echo ""
    echo "✗ 终端服务器启动失败"
    echo "请查看日志文件: $LOG_FILE"
    echo "=========================================="
    rm -f "$PID_FILE"
    exit 1
fi
[root@VM-20-4-opencloudos terminal]# cat status.sh 
#!/bin/bash

# 查看 WebSocket 终端服务器状态脚本

# 获取脚本所在目录
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_DIR="$( cd "$SCRIPT_DIR/../.." && pwd )"

# PID 文件路径
PID_FILE="$PROJECT_DIR/storage/terminal-server.pid"

# 日志文件路径
LOG_FILE="$PROJECT_DIR/storage/logs/terminal-server.log"

echo "=========================================="
echo "WebSocket 终端服务器状态"
echo "=========================================="

# 检查 PID 文件是否存在
if [ ! -f "$PID_FILE" ]; then
    echo "状态: 未运行"
    echo "=========================================="
    exit 1
fi

# 读取 PID
PID=$(cat "$PID_FILE")

# 检查进程是否存在
if ! ps -p "$PID" > /dev/null 2>&1; then
    echo "状态: 已停止 (PID 文件存在但进程不存在)"
    echo "PID 文件: $PID_FILE"
    echo ""
    echo "建议: 运行 ./scripts/start-terminal-server.sh 重新启动"
    echo "=========================================="
    exit 1
fi

# 获取进程信息
PROCESS_INFO=$(ps -p "$PID" -o pid,ppid,%cpu,%mem,etime,command | tail -n 1)

echo "状态: 运行中 ✓"
echo ""
echo "进程信息:"
echo "  PID: $PID"
echo "  详情: $PROCESS_INFO"
echo ""

# 检查端口占用
if command -v lsof &> /dev/null; then
    PORTS=$(lsof -Pan -p "$PID" -i 2>/dev/null | grep LISTEN | awk '{print $9}' | cut -d: -f2 | sort -u)
    if [ -n "$PORTS" ]; then
        echo "监听端口:"
        for port in $PORTS; do
            echo "  - $port"
        done
        echo ""
    fi
fi

# 显示日志文件信息
if [ -f "$LOG_FILE" ]; then
    LOG_SIZE=$(du -h "$LOG_FILE" | cut -f1)
    LOG_LINES=$(wc -l < "$LOG_FILE")
    echo "日志文件:"
    echo "  路径: $LOG_FILE"
    echo "  大小: $LOG_SIZE"
    echo "  行数: $LOG_LINES"
    echo ""
    echo "最近日志 (最后 10 行):"
    echo "----------------------------------------"
    tail -n 10 "$LOG_FILE"
fi

echo "=========================================="
echo ""
echo "管理命令:"
echo "  查看日志: tail -f $LOG_FILE"
echo "  停止服务: ./scripts/stop-terminal-server.sh"
echo "  重启服务: ./scripts/stop-terminal-server.sh && ./scripts/start-terminal-server.sh"
echo "=========================================="

exit 0