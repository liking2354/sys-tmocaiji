#!/bin/bash

# WebSocket 服务器诊断脚本

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 获取项目路径
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_DIR="$( cd "$SCRIPT_DIR/../.." && pwd )"

cd "$PROJECT_DIR"

echo -e "${BLUE}=========================================="
echo "WebSocket 服务器诊断"
echo "==========================================${NC}"
echo ""

# 1. 检查配置文件
echo -e "${BLUE}[1/7] 检查配置文件${NC}"
if [ -f ".env" ]; then
    echo -e "${GREEN}✓ .env 文件存在${NC}"
    
    # 读取配置
    WS_PORT=$(grep "^WEBSOCKET_TERMINAL_PORT=" .env | cut -d '=' -f2)
    WS_HOST=$(grep "^WEBSOCKET_TERMINAL_HOST=" .env | cut -d '=' -f2)
    WS_PROTOCOL=$(grep "^WEBSOCKET_TERMINAL_PROTOCOL=" .env | cut -d '=' -f2)
    
    if [ -n "$WS_PORT" ]; then
        echo -e "  端口: ${GREEN}$WS_PORT${NC}"
    else
        echo -e "  ${YELLOW}⚠ 未配置 WEBSOCKET_TERMINAL_PORT${NC}"
        WS_PORT=8080
    fi
    
    if [ -n "$WS_HOST" ]; then
        echo -e "  主机: ${GREEN}$WS_HOST${NC}"
    else
        echo -e "  ${YELLOW}⚠ 未配置 WEBSOCKET_TERMINAL_HOST${NC}"
    fi
    
    if [ -n "$WS_PROTOCOL" ]; then
        echo -e "  协议: ${GREEN}$WS_PROTOCOL${NC}"
    else
        echo -e "  ${YELLOW}⚠ 未配置 WEBSOCKET_TERMINAL_PROTOCOL${NC}"
    fi
else
    echo -e "${RED}✗ .env 文件不存在${NC}"
fi
echo ""

# 2. 检查服务器进程
echo -e "${BLUE}[2/7] 检查服务器进程${NC}"
PID_FILE="storage/terminal-server.pid"
if [ -f "$PID_FILE" ]; then
    PID=$(cat "$PID_FILE")
    if ps -p "$PID" > /dev/null 2>&1; then
        echo -e "${GREEN}✓ 服务器正在运行 (PID: $PID)${NC}"
        PROCESS_INFO=$(ps -p "$PID" -o pid,ppid,%cpu,%mem,etime,command | tail -n 1)
        echo -e "  进程信息: $PROCESS_INFO"
    else
        echo -e "${RED}✗ 服务器未运行 (PID 文件存在但进程不存在)${NC}"
    fi
else
    echo -e "${RED}✗ 服务器未运行 (PID 文件不存在)${NC}"
fi
echo ""

# 3. 检查端口监听
echo -e "${BLUE}[3/7] 检查端口监听${NC}"
if command -v netstat &> /dev/null; then
    LISTEN=$(netstat -tlnp 2>/dev/null | grep ":$WS_PORT")
    if [ -n "$LISTEN" ]; then
        echo -e "${GREEN}✓ 端口 $WS_PORT 正在监听${NC}"
        echo -e "  $LISTEN"
    else
        echo -e "${RED}✗ 端口 $WS_PORT 未监听${NC}"
    fi
elif command -v lsof &> /dev/null; then
    LISTEN=$(lsof -i :$WS_PORT 2>/dev/null)
    if [ -n "$LISTEN" ]; then
        echo -e "${GREEN}✓ 端口 $WS_PORT 正在监听${NC}"
        echo -e "$LISTEN"
    else
        echo -e "${RED}✗ 端口 $WS_PORT 未监听${NC}"
    fi
else
    echo -e "${YELLOW}⚠ 无法检查端口（netstat 和 lsof 都不可用）${NC}"
fi
echo ""

# 4. 检查防火墙
echo -e "${BLUE}[4/7] 检查防火墙${NC}"
if command -v firewall-cmd &> /dev/null; then
    if firewall-cmd --list-ports 2>/dev/null | grep -q "$WS_PORT"; then
        echo -e "${GREEN}✓ 防火墙已开放端口 $WS_PORT${NC}"
    else
        echo -e "${YELLOW}⚠ 防火墙未开放端口 $WS_PORT${NC}"
        echo -e "  执行: firewall-cmd --permanent --add-port=$WS_PORT/tcp && firewall-cmd --reload"
    fi
elif command -v ufw &> /dev/null; then
    if ufw status 2>/dev/null | grep -q "$WS_PORT"; then
        echo -e "${GREEN}✓ 防火墙已开放端口 $WS_PORT${NC}"
    else
        echo -e "${YELLOW}⚠ 防火墙未开放端口 $WS_PORT${NC}"
        echo -e "  执行: ufw allow $WS_PORT/tcp"
    fi
else
    echo -e "${YELLOW}⚠ 无法检查防火墙（firewall-cmd 和 ufw 都不可用）${NC}"
fi
echo ""

# 5. 检查 PHP 扩展
echo -e "${BLUE}[5/7] 检查 PHP 扩展${NC}"
REQUIRED_EXTS=("sockets" "pcntl" "posix")
for ext in "${REQUIRED_EXTS[@]}"; do
    if php -m 2>/dev/null | grep -q "^$ext$"; then
        echo -e "${GREEN}✓ $ext 扩展已安装${NC}"
    else
        echo -e "${RED}✗ $ext 扩展未安装${NC}"
    fi
done
echo ""

# 6. 检查日志文件
echo -e "${BLUE}[6/7] 检查日志文件${NC}"
LOG_FILE="storage/logs/terminal-server.log"
if [ -f "$LOG_FILE" ]; then
    LOG_SIZE=$(du -h "$LOG_FILE" | cut -f1)
    LOG_LINES=$(wc -l < "$LOG_FILE")
    echo -e "${GREEN}✓ 日志文件存在${NC}"
    echo -e "  路径: $LOG_FILE"
    echo -e "  大小: $LOG_SIZE"
    echo -e "  行数: $LOG_LINES"
    echo ""
    echo -e "  ${BLUE}最近 10 行日志:${NC}"
    tail -n 10 "$LOG_FILE" | sed 's/^/  /'
else
    echo -e "${YELLOW}⚠ 日志文件不存在${NC}"
fi
echo ""

# 7. 网络连接测试
echo -e "${BLUE}[7/7] 网络连接测试${NC}"
# 获取外网 IP
EXTERNAL_IP=$(curl -s ifconfig.me 2>/dev/null || curl -s icanhazip.com 2>/dev/null)
if [ -n "$EXTERNAL_IP" ]; then
    echo -e "  外网 IP: ${GREEN}$EXTERNAL_IP${NC}"
    echo -e "  WebSocket 地址: ${GREEN}ws://$EXTERNAL_IP:$WS_PORT${NC}"
else
    echo -e "${YELLOW}⚠ 无法获取外网 IP${NC}"
fi

# 测试本地连接
if command -v nc &> /dev/null; then
    if nc -z 127.0.0.1 $WS_PORT 2>/dev/null; then
        echo -e "${GREEN}✓ 本地端口可访问${NC}"
    else
        echo -e "${RED}✗ 本地端口不可访问${NC}"
    fi
else
    echo -e "${YELLOW}⚠ nc 命令不可用，跳过连接测试${NC}"
fi
echo ""

# 总结
echo -e "${BLUE}=========================================="
echo "诊断总结"
echo "==========================================${NC}"
echo ""

# 检查关键问题
ISSUES=0

if [ ! -f "$PID_FILE" ] || ! ps -p "$(cat $PID_FILE 2>/dev/null)" > /dev/null 2>&1; then
    echo -e "${RED}✗ WebSocket 服务器未运行${NC}"
    echo -e "  解决: ./scripts/terminal/start.sh"
    ISSUES=$((ISSUES+1))
fi

if ! netstat -tlnp 2>/dev/null | grep -q ":$WS_PORT" && ! lsof -i :$WS_PORT 2>/dev/null | grep -q "LISTEN"; then
    echo -e "${RED}✗ 端口 $WS_PORT 未监听${NC}"
    echo -e "  解决: 启动 WebSocket 服务器"
    ISSUES=$((ISSUES+1))
fi

if [ $ISSUES -eq 0 ]; then
    echo -e "${GREEN}✓ 所有检查通过！${NC}"
    echo ""
    echo -e "${BLUE}建议操作:${NC}"
    echo -e "  1. 确保云服务器安全组已开放端口 $WS_PORT"
    echo -e "  2. 在浏览器中访问控制台页面测试连接"
    echo -e "  3. 查看浏览器控制台是否有错误信息"
else
    echo ""
    echo -e "${YELLOW}发现 $ISSUES 个问题，请按照上述提示解决${NC}"
fi

echo ""
echo -e "${BLUE}==========================================${NC}"
