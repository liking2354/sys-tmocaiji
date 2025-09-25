#!/bin/bash

# 系统采集项目部署脚本
# 使用方法: ./deploy.sh [目标目录]

# 设置默认目标目录
TARGET_DIR=${1:-"/www/wwwroot/tmocaiji"}
# 获取项目根目录（脚本位于 scripts/deployment/ 目录下）
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
CURRENT_DIR="$PROJECT_ROOT"

echo "====================================================="
echo "系统采集项目部署脚本"
echo "====================================================="
echo "项目根目录: $PROJECT_ROOT"
echo "源目录: $CURRENT_DIR"
echo "目标目录: $TARGET_DIR"
echo "====================================================="

# 确认是否继续
read -p "是否继续部署? (y/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "部署已取消"
    exit 1
fi

# 检查目标目录是否存在，不存在则创建
if [ ! -d "$TARGET_DIR" ]; then
    echo "目标目录不存在，正在创建..."
    mkdir -p "$TARGET_DIR"
fi

# 同步文件到目标目录
echo "正在同步文件到目标目录..."
rsync -av --exclude='.git' \
    --exclude='node_modules' \
    --exclude='.env' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='vendor' \
    "$PROJECT_ROOT/" "$TARGET_DIR/"

# 设置文件权限
echo "正在设置文件权限..."
find "$TARGET_DIR" -type f -exec chmod 644 {} \;
find "$TARGET_DIR" -type d -exec chmod 755 {} \;
chmod +x "$TARGET_DIR/artisan"

# 如果目标目录没有.env文件，则复制.env.example
if [ ! -f "$TARGET_DIR/.env" ]; then
    echo "正在创建.env文件..."
    cp "$TARGET_DIR/.env.example" "$TARGET_DIR/.env"
    echo "请记得编辑.env文件配置数据库和其他设置"
fi

# 安装依赖
echo "正在安装依赖..."
cd "$TARGET_DIR"
composer install --no-dev --optimize-autoloader

# 清除缓存
echo "正在清除缓存..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 创建存储链接
echo "正在创建存储链接..."
php artisan storage:link

# 设置存储目录权限
echo "正在设置存储目录权限..."
chmod -R 755 "$TARGET_DIR/."
chmod -R 777 "$TARGET_DIR/storage"
chown -R www:www  "$TARGET_DIR/."
chmod -R 775 "$TARGET_DIR/bootstrap/cache"

# 如果需要，可以在这里添加数据库迁移命令
# echo "正在执行数据库迁移..."
# php artisan migrate --force

echo "====================================================="
echo "部署完成!"
echo "====================================================="
echo "请确保以下事项:"
echo "1. 配置了正确的.env文件"
echo "2. 设置了正确的文件权限"
echo "3. 配置了Web服务器指向public目录"
echo "4. 设置了定时任务 (可运行: $TARGET_DIR/scripts/maintenance/schedule_tasks.sh)"
echo "====================================================="
echo "脚本位置已调整到标准目录结构:"
echo "- 部署脚本: scripts/deployment/deploy.sh"
echo "- 维护脚本: scripts/maintenance/schedule_tasks.sh"
echo "====================================================="