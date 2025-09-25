#!/bin/bash

# 快速更新脚本 - 简化版本
# 使用方法: ./quick_update.sh [项目路径]

# 颜色定义
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# 自动检测项目路径
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_PATH="$(cd "$SCRIPT_DIR/../.." && pwd)"

if [ ! -z "$1" ]; then
    PROJECT_PATH="$1"
fi

echo -e "${BLUE}快速更新系统采集项目${NC}"
echo -e "项目路径: ${GREEN}$PROJECT_PATH${NC}"

cd "$PROJECT_PATH"

# 检查Git仓库
if [ ! -d ".git" ]; then
    echo -e "${YELLOW}错误: 不是Git仓库${NC}"
    exit 1
fi

# 拉取最新代码
echo -e "${BLUE}正在拉取最新代码...${NC}"
git pull origin $(git branch --show-current)

# 更新依赖
echo -e "${BLUE}正在更新依赖...${NC}"
composer install --no-dev --optimize-autoloader

# 清除缓存
echo -e "${BLUE}正在清除缓存...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 设置权限
chmod -R 775 storage bootstrap/cache

echo -e "${GREEN}快速更新完成!${NC}"