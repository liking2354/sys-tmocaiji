#!/bin/bash

# 停止 WebSocket 终端服务器脚本

# 获取脚本所在目录
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_DIR="$( cd "$SCRIPT_DIR/../.." && pwd )"

# PID 文件路径
PID_FILE="$PROJECT_DIR/storage/terminal-server.pid"

echo "=========================================="
echo "停止 WebSocket 终端服务器"
echo "=========================================="

# 检查 PID 文件是否存在
if [ ! -f "$PID_FILE" ]; then
    echo "未找到 PID 文件，服务器可能未运行"
    echo "=========================================="
    exit 1
fi

# 读取 PID
PID=$(cat "$PID_FILE")

# 检查进程是否存在
if ! ps -p "$PID" > /dev/null 2>&1; then
    echo "进程 (PID: $PID) 不存在，可能已停止"
    rm -f "$PID_FILE"
    echo "=========================================="
    exit 1
fi

# 停止进程
echo "正在停止进程 (PID: $PID)..."
kill "$PID" 2>/dev/null

# 等待进程结束，最多等待 5 秒
for i in {1..5}; do
    if ! ps -p "$PID" > /dev/null 2>&1; then
        echo "✓ 服务器已成功停止"
        rm -f "$PID_FILE"
        echo "=========================================="
        exit 0
    fi
    sleep 1
done

# 如果进程仍在运行，强制 kill
echo "进程未响应，强制停止..."
kill -9 "$PID" 2>/dev/null
sleep 1

if ! ps -p "$PID" > /dev/null 2>&1; then
    echo "✓ 服务器已强制停止"
    rm -f "$PID_FILE"
    echo "=========================================="
    exit 0
else
    echo "✗ 无法停止服务器进程"
    echo "=========================================="
    exit 1
fi
[root@VM-20-4-opencloudos terminal]# 
[root@VM-20-4-opencloudos terminal]# 
[root@VM-20-4-opencloudos terminal]# 
[root@VM-20-4-opencloudos terminal]# 
[root@VM-20-4-opencloudos terminal]# cat stop.sh 
#!/bin/bash

# 停止 WebSocket 终端服务器脚本

# 获取脚本所在目录
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_DIR="$( cd "$SCRIPT_DIR/../.." && pwd )"

# PID 文件路径
PID_FILE="$PROJECT_DIR/storage/terminal-server.pid"

echo "=========================================="
echo "停止 WebSocket 终端服务器"
echo "=========================================="

# 检查 PID 文件是否存在
if [ ! -f "$PID_FILE" ]; then
    echo "未找到 PID 文件，服务器可能未运行"
    echo "=========================================="
    exit 1
fi

# 读取 PID
PID=$(cat "$PID_FILE")

# 检查进程是否存在
if ! ps -p "$PID" > /dev/null 2>&1; then
    echo "进程 (PID: $PID) 不存在，可能已停止"
    rm -f "$PID_FILE"
    echo "=========================================="
    exit 1
fi

# 停止进程
echo "正在停止进程 (PID: $PID)..."
kill "$PID" 2>/dev/null

# 等待进程结束，最多等待 5 秒
for i in {1..5}; do
    if ! ps -p "$PID" > /dev/null 2>&1; then
        echo "✓ 服务器已成功停止"
        rm -f "$PID_FILE"
        echo "=========================================="
        exit 0
    fi
    sleep 1
done

# 如果进程仍在运行，强制 kill
echo "进程未响应，强制停止..."
kill -9 "$PID" 2>/dev/null
sleep 1

if ! ps -p "$PID" > /dev/null 2>&1; then
    echo "✓ 服务器已强制停止"
    rm -f "$PID_FILE"
    echo "=========================================="
    exit 0
else
    echo "✗ 无法停止服务器进程"
    echo "=========================================="
    exit 1
fi