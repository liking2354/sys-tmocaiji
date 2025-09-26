#!/bin/bash

# 快速更新脚本 - 生产环境专用
# 包含权限修复功能

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# 生产环境项目路径
PROJECT_PATH="/www/wwwroot/tmocaiji"

# 如果通过参数指定项目路径
if [ ! -z "$1" ]; then
    PROJECT_PATH="$1"
fi

echo -e "${BLUE}====================================================="
echo "快速更新脚本 - 生产环境"
echo "====================================================="
echo -e "项目路径: ${GREEN}$PROJECT_PATH${NC}"
echo -e "${BLUE}=====================================================${NC}"

# 检查项目目录
if [ ! -d "$PROJECT_PATH" ]; then
    echo -e "${RED}错误: 项目目录不存在: $PROJECT_PATH${NC}"
    exit 1
fi

cd "$PROJECT_PATH"

# 检查Git仓库
if [ ! -d ".git" ]; then
    echo -e "${RED}错误: 不是Git仓库${NC}"
    exit 1
fi

# 检测Web服务器用户
WEB_USER=""
if id "www" &>/dev/null; then
    WEB_USER="www"
elif id "www-data" &>/dev/null; then
    WEB_USER="www-data"
elif id "nginx" &>/dev/null; then
    WEB_USER="nginx"
elif id "apache" &>/dev/null; then
    WEB_USER="apache"
fi

echo -e "${BLUE}Web服务器用户: ${GREEN}$WEB_USER${NC}"

# 拉取最新代码
echo -e "${BLUE}拉取最新代码...${NC}"
git pull origin main

# 更新Composer依赖
echo -e "${BLUE}更新依赖...${NC}"
composer install --no-dev --optimize-autoloader

# 清除缓存
echo -e "${BLUE}清除缓存...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 重新生成缓存
echo -e "${BLUE}生成缓存...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 修复权限
echo -e "${BLUE}修复文件权限...${NC}"

# 设置目录权限
chmod -R 775 storage bootstrap/cache

# 创建日志目录和文件
mkdir -p storage/logs
if [ ! -f "storage/logs/laravel.log" ]; then
    touch storage/logs/laravel.log
fi

# 设置日志权限
chmod 775 storage/logs
chmod 664 storage/logs/laravel.log
find storage/logs -name "*.log" -exec chmod 664 {} \;

# 设置所有者
if [ ! -z "$WEB_USER" ] && id "$WEB_USER" &>/dev/null; then
    chown -R $WEB_USER:$WEB_USER storage
    chown -R $WEB_USER:$WEB_USER bootstrap/cache
    echo -e "${GREEN}文件所有者设置完成${NC}"
fi

# 重启队列（如果存在）
if pgrep -f "artisan queue:work" > /dev/null; then
    echo -e "${BLUE}重启队列...${NC}"
    pkill -f "artisan queue:work"
fi

echo -e "${GREEN}====================================================="
echo "快速更新完成!"
echo "====================================================="
echo -e "- 代码版本: ${GREEN}$(git rev-parse --short HEAD)${NC}"
echo -e "- 更新时间: ${GREEN}$(date)${NC}"
echo -e "- 权限修复: ${GREEN}完成${NC}"
echo -e "${GREEN}=====================================================${NC}"

echo -e "${YELLOW}建议重启Web服务器以确保更改生效${NC}"