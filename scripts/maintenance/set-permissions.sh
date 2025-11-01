#!/bin/bash

# 设置项目文件权限脚本
# 适用于宝塔面板等Web服务器环境

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 自动检测项目路径（脚本位于 scripts/maintenance/ 目录下）
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_PATH="$(cd "$SCRIPT_DIR/../.." && pwd)"

# 如果通过参数指定项目路径
if [ ! -z "$1" ]; then
    PROJECT_PATH="$1"
fi

echo -e "${BLUE}====================================================="
echo "设置项目文件权限"
echo "====================================================="
echo -e "项目路径: ${GREEN}$PROJECT_PATH${NC}"
echo -e "${BLUE}=====================================================${NC}"

# 检查项目目录是否存在
if [ ! -d "$PROJECT_PATH" ]; then
    echo -e "${RED}错误: 项目目录不存在: $PROJECT_PATH${NC}"
    exit 1
fi

# 进入项目目录
cd "$PROJECT_PATH"

# 检测Web服务器用户
WEB_USER=""
if id "www" &>/dev/null; then
    WEB_USER="www"
    echo -e "${BLUE}检测到宝塔面板用户: ${GREEN}www${NC}"
elif id "www-data" &>/dev/null; then
    WEB_USER="www-data"
    echo -e "${BLUE}检测到Web服务器用户: ${GREEN}www-data${NC}"
elif id "nginx" &>/dev/null; then
    WEB_USER="nginx"
    echo -e "${BLUE}检测到Web服务器用户: ${GREEN}nginx${NC}"
elif id "apache" &>/dev/null; then
    WEB_USER="apache"
    echo -e "${BLUE}检测到Web服务器用户: ${GREEN}apache${NC}"
else
    echo -e "${YELLOW}警告: 未检测到常见的Web服务器用户${NC}"
    echo -e "${YELLOW}将使用当前用户设置权限${NC}"
fi

# 确认是否继续
echo ""
echo -e "${YELLOW}即将执行以下操作:${NC}"
echo -e "  1. 设置项目根目录权限为 ${GREEN}755${NC}"
echo -e "  2. 设置 storage 目录权限为 ${GREEN}777${NC}"
echo -e "  3. 设置 bootstrap/cache 目录权限为 ${GREEN}777${NC}"
if [ ! -z "$WEB_USER" ]; then
    echo -e "  4. 设置目录所有者为 ${GREEN}$WEB_USER:$WEB_USER${NC}"
fi
echo ""
read -p "是否继续? (y/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}操作已取消${NC}"
    exit 1
fi

# 设置项目根目录权限（755）
echo ""
echo -e "${BLUE}[1/4] 正在设置项目根目录权限...${NC}"
chmod -R 755 .
echo -e "${GREEN}✓ 项目根目录权限设置完成 (755)${NC}"

# 设置storage目录权限（777）
echo -e "${BLUE}[2/4] 正在设置storage目录权限...${NC}"
if [ -d "storage" ]; then
    chmod -R 777 storage
    echo -e "${GREEN}✓ storage目录权限设置完成 (777)${NC}"
else
    echo -e "${YELLOW}⚠ storage目录不存在，跳过${NC}"
fi

# 设置bootstrap/cache目录权限（777）
echo -e "${BLUE}[3/4] 正在设置bootstrap/cache目录权限...${NC}"
if [ -d "bootstrap/cache" ]; then
    chmod -R 777 bootstrap/cache
    echo -e "${GREEN}✓ bootstrap/cache目录权限设置完成 (777)${NC}"
else
    echo -e "${YELLOW}⚠ bootstrap/cache目录不存在，正在创建...${NC}"
    mkdir -p bootstrap/cache
    chmod -R 777 bootstrap/cache
    echo -e "${GREEN}✓ bootstrap/cache目录创建并设置权限完成 (777)${NC}"
fi

# 设置目录所有者
if [ ! -z "$WEB_USER" ]; then
    echo -e "${BLUE}[4/4] 正在设置目录所有者为: ${GREEN}$WEB_USER:$WEB_USER${NC}"
    
    if command -v sudo &> /dev/null; then
        sudo chown -R $WEB_USER:$WEB_USER .
        echo -e "${GREEN}✓ 目录所有者设置完成${NC}"
    else
        echo -e "${YELLOW}⚠ 未找到sudo命令，跳过所有者设置${NC}"
        echo -e "${YELLOW}请手动执行: chown -R $WEB_USER:$WEB_USER .${NC}"
    fi
else
    echo -e "${BLUE}[4/4] 跳过所有者设置（未检测到Web服务器用户）${NC}"
fi

# 确保必要的目录存在
echo ""
echo -e "${BLUE}正在检查必要的目录...${NC}"
mkdir -p storage/logs
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/framework/cache
mkdir -p storage/framework/testing
mkdir -p storage/app/public
mkdir -p bootstrap/cache
echo -e "${GREEN}✓ 必要目录检查完成${NC}"

# 设置脚本文件执行权限
echo -e "${BLUE}正在设置脚本文件执行权限...${NC}"
find . -type f -name "*.sh" -exec chmod +x {} \;
echo -e "${GREEN}✓ 脚本文件执行权限设置完成${NC}"

# 显示摘要
echo ""
echo -e "${GREEN}====================================================="
echo "权限设置完成!"
echo "====================================================="
echo -e "权限设置摘要:"
echo -e "  - 项目根目录: ${GREEN}755${NC}"
echo -e "  - storage目录: ${GREEN}777${NC}"
echo -e "  - bootstrap/cache目录: ${GREEN}777${NC}"
if [ ! -z "$WEB_USER" ]; then
    echo -e "  - 目录所有者: ${GREEN}$WEB_USER:$WEB_USER${NC}"
fi
echo -e "${GREEN}=====================================================${NC}"

# 验证权限设置
echo ""
echo -e "${BLUE}验证权限设置:${NC}"
echo -e "storage目录权限: $(stat -f '%A' storage 2>/dev/null || stat -c '%a' storage 2>/dev/null)"
echo -e "bootstrap/cache目录权限: $(stat -f '%A' bootstrap/cache 2>/dev/null || stat -c '%a' bootstrap/cache 2>/dev/null)"
if [ ! -z "$WEB_USER" ]; then
    echo -e "storage目录所有者: $(stat -f '%Su:%Sg' storage 2>/dev/null || stat -c '%U:%G' storage 2>/dev/null)"
fi

echo ""
echo -e "${GREEN}✓ 所有操作已完成${NC}"
