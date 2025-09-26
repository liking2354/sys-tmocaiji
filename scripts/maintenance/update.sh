#!/bin/bash

# 系统采集项目更新脚本
# 使用方法: ./update.sh [项目路径]

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

# Git仓库地址
REPO_URL="https://github.com/liking2354/sys-tmocaiji.git"

# 备份目录
BACKUP_DIR="$PROJECT_PATH/backups/$(date +%Y%m%d_%H%M%S)"

echo -e "${BLUE}====================================================="
echo "系统采集项目更新脚本"
echo "====================================================="
echo -e "项目路径: ${GREEN}$PROJECT_PATH${NC}"
echo -e "远程仓库: ${GREEN}$REPO_URL${NC}"
echo -e "备份目录: ${GREEN}$BACKUP_DIR${NC}"
echo -e "${BLUE}=====================================================${NC}"

# 检查项目目录是否存在
if [ ! -d "$PROJECT_PATH" ]; then
    echo -e "${RED}错误: 项目目录不存在: $PROJECT_PATH${NC}"
    exit 1
fi

# 进入项目目录
cd "$PROJECT_PATH"

# 检查是否为Git仓库
if [ ! -d ".git" ]; then
    echo -e "${RED}错误: 当前目录不是Git仓库${NC}"
    echo -e "${YELLOW}提示: 如果这是首次部署，请使用以下命令克隆仓库:${NC}"
    echo "git clone $REPO_URL $PROJECT_PATH"
    exit 1
fi

# 确认是否继续更新
echo -e "${YELLOW}警告: 此操作将更新项目代码，可能会覆盖本地修改${NC}"
read -p "是否继续更新? (y/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}更新已取消${NC}"
    exit 1
fi

# 检查当前分支
CURRENT_BRANCH=$(git branch --show-current)
echo -e "${BLUE}当前分支: ${GREEN}$CURRENT_BRANCH${NC}"

# 检查是否有未提交的更改
if ! git diff-index --quiet HEAD --; then
    echo -e "${YELLOW}检测到未提交的更改，正在创建备份...${NC}"
    
    # 创建备份目录
    mkdir -p "$BACKUP_DIR"
    
    # 备份当前状态
    echo -e "${BLUE}正在备份当前状态...${NC}"
    git stash push -m "Auto backup before update $(date)"
    
    # 备份重要文件
    if [ -f ".env" ]; then
        cp .env "$BACKUP_DIR/.env.backup"
        echo -e "${GREEN}已备份 .env 文件${NC}"
    fi
    
    if [ -d "storage" ]; then
        cp -r storage "$BACKUP_DIR/storage.backup"
        echo -e "${GREEN}已备份 storage 目录${NC}"
    fi
    
    echo -e "${GREEN}备份完成: $BACKUP_DIR${NC}"
fi

# 获取远程更新
echo -e "${BLUE}正在获取远程更新...${NC}"
git fetch origin

# 检查是否有新的提交
LOCAL_COMMIT=$(git rev-parse HEAD)
REMOTE_COMMIT=$(git rev-parse origin/$CURRENT_BRANCH)

if [ "$LOCAL_COMMIT" = "$REMOTE_COMMIT" ]; then
    echo -e "${GREEN}代码已是最新版本，无需更新${NC}"
    exit 0
fi

echo -e "${YELLOW}发现新的提交，正在更新...${NC}"

# 显示即将更新的提交
echo -e "${BLUE}即将应用的更新:${NC}"
git log --oneline $LOCAL_COMMIT..$REMOTE_COMMIT

# 拉取最新代码
echo -e "${BLUE}正在拉取最新代码...${NC}"
if git pull origin $CURRENT_BRANCH; then
    echo -e "${GREEN}代码更新成功${NC}"
else
    echo -e "${RED}代码更新失败${NC}"
    exit 1
fi

# 更新Composer依赖
echo -e "${BLUE}正在更新Composer依赖...${NC}"
if command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader
    echo -e "${GREEN}Composer依赖更新完成${NC}"
else
    echo -e "${YELLOW}警告: 未找到Composer命令，请手动运行 composer install${NC}"
fi

# 执行Laravel维护命令
echo -e "${BLUE}正在执行Laravel维护命令...${NC}"

# 清除缓存
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 重新生成配置缓存（生产环境）
if [ -f ".env" ] && grep -q "APP_ENV=production" .env; then
    echo -e "${BLUE}生产环境，正在优化性能...${NC}"
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# 执行数据库迁移（可选）
read -p "是否执行数据库迁移? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${BLUE}正在执行数据库迁移...${NC}"
    php artisan migrate --force
    echo -e "${GREEN}数据库迁移完成${NC}"
fi

# 重启队列工作进程（如果使用队列）
if pgrep -f "artisan queue:work" > /dev/null; then
    echo -e "${BLUE}正在重启队列工作进程...${NC}"
    pkill -f "artisan queue:work"
    echo -e "${GREEN}队列工作进程已重启${NC}"
fi

# 设置文件权限
echo -e "${BLUE}正在设置文件权限...${NC}"

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
    echo -e "${YELLOW}警告: 未检测到常见的Web服务器用户，使用当前用户设置权限${NC}"
fi

# 设置storage和bootstrap/cache目录权限
chmod -R 775 storage bootstrap/cache

# 如果检测到Web服务器用户，设置所有者
if [ ! -z "$WEB_USER" ]; then
    echo -e "${BLUE}检测到Web服务器用户: ${GREEN}$WEB_USER${NC}"
    echo -e "${BLUE}正在设置目录所有者...${NC}"
    
    # 设置storage目录所有者和权限
    sudo chown -R $WEB_USER:$WEB_USER storage
    sudo chmod -R 775 storage
    
    # 设置bootstrap/cache目录所有者和权限
    sudo chown -R $WEB_USER:$WEB_USER bootstrap/cache
    sudo chmod -R 775 bootstrap/cache
    
    echo -e "${GREEN}目录所有者设置完成${NC}"
fi

# 确保日志目录存在并设置权限
mkdir -p storage/logs
chmod 775 storage/logs

# 处理日志文件权限
if [ -f "storage/logs/laravel.log" ]; then
    echo -e "${BLUE}正在设置日志文件权限...${NC}"
    chmod 664 storage/logs/laravel.log
    if [ ! -z "$WEB_USER" ]; then
        sudo chown $WEB_USER:$WEB_USER storage/logs/laravel.log
    fi
    echo -e "${GREEN}日志文件权限设置完成${NC}"
else
    echo -e "${BLUE}创建日志文件并设置权限...${NC}"
    touch storage/logs/laravel.log
    chmod 664 storage/logs/laravel.log
    if [ ! -z "$WEB_USER" ]; then
        sudo chown $WEB_USER:$WEB_USER storage/logs/laravel.log
    fi
    echo -e "${GREEN}日志文件创建并设置权限完成${NC}"
fi

# 设置其他日志文件权限
find storage/logs -name "*.log" -exec chmod 664 {} \;
if [ ! -z "$WEB_USER" ]; then
    find storage/logs -name "*.log" -exec sudo chown $WEB_USER:$WEB_USER {} \;
fi

# 设置脚本文件执行权限
find . -type f -name "*.sh" -exec chmod +x {} \;

echo -e "${GREEN}文件权限设置完成${NC}"

echo -e "${GREEN}====================================================="
echo "更新完成!"
echo "====================================================="
echo -e "更新摘要:"
echo -e "- 代码版本: ${GREEN}$(git rev-parse --short HEAD)${NC}"
echo -e "- 更新时间: ${GREEN}$(date)${NC}"
if [ -d "$BACKUP_DIR" ]; then
    echo -e "- 备份位置: ${GREEN}$BACKUP_DIR${NC}"
fi
echo -e "${GREEN}=====================================================${NC}"

# 显示更新日志
echo -e "${BLUE}最近的提交记录:${NC}"
git log --oneline -5

echo -e "${YELLOW}请注意:${NC}"
echo "1. 检查应用是否正常运行"
echo "2. 如有问题，可使用备份进行回滚"
echo "3. 建议重启Web服务器以确保更改生效"