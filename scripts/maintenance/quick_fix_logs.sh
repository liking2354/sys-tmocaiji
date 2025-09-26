#!/bin/bash

# 快速修复Laravel日志权限脚本
# 专门用于生产环境快速修复日志权限问题

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# 项目路径（生产环境路径）
PROJECT_PATH="/www/wwwroot/tmocaiji"

# 如果通过参数指定项目路径
if [ ! -z "$1" ]; then
    PROJECT_PATH="$1"
fi

echo -e "${BLUE}快速修复Laravel日志权限${NC}"
echo -e "项目路径: ${GREEN}$PROJECT_PATH${NC}"

# 检查项目目录
if [ ! -d "$PROJECT_PATH" ]; then
    echo -e "${RED}错误: 项目目录不存在: $PROJECT_PATH${NC}"
    exit 1
fi

cd "$PROJECT_PATH"

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
else
    echo -e "${RED}错误: 未找到Web服务器用户${NC}"
    exit 1
fi

echo -e "${BLUE}使用Web服务器用户: ${GREEN}$WEB_USER${NC}"

# 创建日志目录
mkdir -p storage/logs

# 创建或修复laravel.log文件
if [ ! -f "storage/logs/laravel.log" ]; then
    echo -e "${BLUE}创建laravel.log文件...${NC}"
    touch storage/logs/laravel.log
fi

# 设置权限
echo -e "${BLUE}设置权限...${NC}"
chmod 775 storage/logs
chmod 664 storage/logs/laravel.log

# 设置所有者
echo -e "${BLUE}设置所有者...${NC}"
chown -R $WEB_USER:$WEB_USER storage/logs

# 验证权限
echo -e "${BLUE}验证权限设置...${NC}"
ls -la storage/logs/laravel.log

echo -e "${GREEN}日志权限修复完成!${NC}"
echo -e "${YELLOW}建议重启Web服务器${NC}"