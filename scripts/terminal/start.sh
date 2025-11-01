#!/bin/bash

# 启动 WebSocket 终端服务器脚本

# 获取脚本所在目录（scripts/terminal/）
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
# 项目根目录（向上两级：scripts/terminal/ -> scripts/ -> 项目根目录）
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
