#!/bin/bash

# 个人资料功能部署脚本
# 用途：在服务器上部署个人资料和头像上传功能

set -e

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_DIR="$( cd "$SCRIPT_DIR/../.." && pwd )"

cd "$PROJECT_DIR"

echo "=========================================="
echo "部署个人资料功能"
echo "=========================================="

# 1. 拉取最新代码
echo "1. 拉取最新代码..."
git pull

# 2. 更新依赖
echo "2. 更新 Composer 依赖..."
composer install --no-dev --optimize-autoloader

# 3. 运行数据库迁移
echo "3. 运行数据库迁移..."
php artisan migrate --force

# 4. 创建 storage 链接
echo "4. 创建 storage 软链接..."
php artisan storage:link

# 5. 创建头像目录
echo "5. 创建头像目录..."
mkdir -p storage/app/public/avatars

# 6. 设置权限
echo "6. 设置目录权限..."
chmod -R 775 storage/app/public/avatars
chmod -R 775 public/storage

# 检测 web 服务器用户
if id "www" &>/dev/null; then
    WEB_USER="www"
elif id "www-data" &>/dev/null; then
    WEB_USER="www-data"
elif id "nginx" &>/dev/null; then
    WEB_USER="nginx"
else
    WEB_USER="www"
fi

echo "Web 服务器用户: $WEB_USER"
chown -R "$WEB_USER:$WEB_USER" storage/app/public/avatars
chown -R "$WEB_USER:$WEB_USER" public/storage

# 7. 清除缓存
echo "7. 清除缓存..."
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

echo ""
echo "=========================================="
echo "✓ 个人资料功能部署完成！"
echo "=========================================="
echo ""
echo "新增功能："
echo "  - 个人资料页面 (访问: /profile)"
echo "  - 头像上传/修改/删除"
echo "  - 修改用户名和邮箱"
echo "  - 修改密码"
echo ""
echo "请访问系统，点击右上角头像 -> 个人资料 进行测试"
echo ""
