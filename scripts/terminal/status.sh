#!/bin/bash

# 查看 WebSocket 终端服务器状态脚本

# 获取脚本所在目录
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_DIR="$( dirname "$SCRIPT_DIR" )"

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
