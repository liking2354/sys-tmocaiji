#!/bin/bash

# Laravel项目权限修复脚本
# 专门用于修复storage和日志文件权限问题

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 自动检测项目路径
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_PATH="$(cd "$SCRIPT_DIR/../.." && pwd)"

# 如果通过参数指定项目路径
if [ ! -z "$1" ]; then
    PROJECT_PATH="$1"
fi

echo -e "${BLUE}====================================================="
echo "Laravel项目权限修复脚本"
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

# 检查是否为Laravel项目
if [ ! -f "artisan" ]; then
    echo -e "${RED}错误: 当前目录不是Laravel项目${NC}"
    exit 1
fi

echo -e "${BLUE}正在修复文件权限...${NC}"

# 检测Web服务器用户
WEB_USER=""
if id "www" &>/dev/null; then
    WEB_USER="www"
    echo -e "${BLUE}检测到宝塔面板用户: ${GREEN}www${NC}"
elif id "www-data" &>/dev/null; then
    WEB_USER="www-data"
elif id "nginx" &>/dev/null; then
    WEB_USER="nginx"
elif id "apache" &>/dev/null; then
    WEB_USER="apache"
else
    echo -e "${YELLOW}警告: 未检测到常见的Web服务器用户${NC}"
    echo -e "${YELLOW}将使用当前用户权限设置${NC}"
fi

if [ ! -z "$WEB_USER" ]; then
    echo -e "${BLUE}检测到Web服务器用户: ${GREEN}$WEB_USER${NC}"
fi

# 创建必要的目录
echo -e "${BLUE}创建必要的目录...${NC}"
mkdir -p storage/app
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

# 设置基本目录权限
echo -e "${BLUE}设置基本目录权限...${NC}"
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# 如果有Web服务器用户，设置所有者
if [ ! -z "$WEB_USER" ]; then
    echo -e "${BLUE}设置目录所有者为Web服务器用户...${NC}"
    
    # 检查是否有sudo权限
    if sudo -n true 2>/dev/null; then
        sudo chown -R $WEB_USER:$WEB_USER storage
        sudo chown -R $WEB_USER:$WEB_USER bootstrap/cache
        echo -e "${GREEN}目录所有者设置完成${NC}"
    else
        echo -e "${YELLOW}警告: 需要sudo权限来设置文件所有者${NC}"
        echo -e "${YELLOW}请手动执行: sudo chown -R $WEB_USER:$WEB_USER storage bootstrap/cache${NC}"
    fi
fi

# 处理日志文件
echo -e "${BLUE}处理日志文件权限...${NC}"

# 确保laravel.log存在
if [ ! -f "storage/logs/laravel.log" ]; then
    echo -e "${BLUE}创建laravel.log文件...${NC}"
    touch storage/logs/laravel.log
fi

# 设置日志文件权限
chmod 664 storage/logs/laravel.log

# 设置所有日志文件权限
find storage/logs -name "*.log" -exec chmod 664 {} \;

# 如果有Web服务器用户，设置日志文件所有者
if [ ! -z "$WEB_USER" ] && sudo -n true 2>/dev/null; then
    sudo chown $WEB_USER:$WEB_USER storage/logs/laravel.log
    find storage/logs -name "*.log" -exec sudo chown $WEB_USER:$WEB_USER {} \;
    echo -e "${GREEN}日志文件所有者设置完成${NC}"
fi

# 设置脚本文件执行权限
echo -e "${BLUE}设置脚本文件执行权限...${NC}"
find . -type f -name "*.sh" -exec chmod +x {} \;

# 测试日志写入
echo -e "${BLUE}测试日志写入权限...${NC}"
if php artisan tinker --execute="Log::info('Permission test: ' . date('Y-m-d H:i:s'));" 2>/dev/null; then
    echo -e "${GREEN}日志写入测试成功${NC}"
else
    echo -e "${YELLOW}日志写入测试失败，可能需要手动检查权限${NC}"
fi

echo -e "${GREEN}====================================================="
echo "权限修复完成!"
echo "====================================================="
echo -e "修复摘要:"
echo -e "- storage目录权限: ${GREEN}775${NC}"
echo -e "- bootstrap/cache目录权限: ${GREEN}775${NC}"
echo -e "- 日志文件权限: ${GREEN}664${NC}"
if [ ! -z "$WEB_USER" ]; then
    echo -e "- 文件所有者: ${GREEN}$WEB_USER${NC}"
fi
echo -e "- 修复时间: ${GREEN}$(date)${NC}"
echo -e "${GREEN}=====================================================${NC}"

echo -e "${YELLOW}建议:${NC}"
echo "1. 重启Web服务器以确保权限更改生效"
echo "2. 检查应用日志功能是否正常"
echo "3. 如果仍有权限问题，请检查SELinux设置"