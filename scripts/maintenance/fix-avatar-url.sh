#!/bin/bash

# 修复头像URL问题
# 用途：确保服务器上头像能正确显示

set -e

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_DIR="$( cd "$SCRIPT_DIR/../.." && pwd )"

cd "$PROJECT_DIR"

echo "=========================================="
echo "修复头像URL问题"
echo "=========================================="

# 1. 检查 storage 链接
echo "1. 检查 storage 软链接..."
if [ -L "public/storage" ]; then
    echo "✓ storage 链接已存在"
    ls -la public/storage
else
    echo "✗ storage 链接不存在，正在创建..."
    php artisan storage:link
fi

# 2. 检查头像目录
echo ""
echo "2. 检查头像目录..."
if [ -d "storage/app/public/avatars" ]; then
    echo "✓ 头像目录已存在"
    ls -la storage/app/public/avatars/ 2>/dev/null || echo "目录为空"
else
    echo "✗ 头像目录不存在，正在创建..."
    mkdir -p storage/app/public/avatars
fi

# 3. 设置权限
echo ""
echo "3. 设置目录权限..."

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

chmod -R 775 storage/app/public/avatars 2>/dev/null || true
chmod -R 775 public/storage 2>/dev/null || true
chown -R "$WEB_USER:$WEB_USER" storage/app/public/avatars 2>/dev/null || true
chown -R "$WEB_USER:$WEB_USER" public/storage 2>/dev/null || true

echo "✓ 权限设置完成"

# 4. 检查 .env 配置
echo ""
echo "4. 检查 .env 配置..."
if [ -f .env ]; then
    APP_URL=$(grep "^APP_URL=" .env | cut -d'=' -f2)
    echo "当前 APP_URL: $APP_URL"
    
    if [ "$APP_URL" = "http://localhost" ] || [ -z "$APP_URL" ]; then
        echo "⚠ 警告: APP_URL 配置不正确"
        echo "请手动修改 .env 文件："
        echo "  APP_URL=http://81.71.102.213"
    else
        echo "✓ APP_URL 配置正确"
    fi
else
    echo "✗ .env 文件不存在"
fi

# 5. 测试头像URL
echo ""
echo "5. 测试头像访问..."
if [ -d "storage/app/public/avatars" ]; then
    AVATAR_COUNT=$(find storage/app/public/avatars -name "*.jpg" -o -name "*.png" -o -name "*.gif" | wc -l)
    echo "找到 $AVATAR_COUNT 个头像文件"
    
    if [ $AVATAR_COUNT -gt 0 ]; then
        echo ""
        echo "头像文件列表："
        find storage/app/public/avatars -type f -name "*.jpg" -o -name "*.png" -o -name "*.gif" | while read file; do
            filename=$(basename "$file")
            echo "  - $filename"
            echo "    访问URL: http://81.71.102.213/storage/avatars/$filename"
        done
    fi
fi

# 6. 清除缓存
echo ""
echo "6. 清除缓存..."
php artisan config:clear
php artisan view:clear
echo "✓ 缓存已清除"

echo ""
echo "=========================================="
echo "✓ 修复完成！"
echo "=========================================="
echo ""
echo "请测试头像访问："
echo "1. 访问个人资料页面: http://81.71.102.213/profile"
echo "2. 检查导航栏头像是否正常显示"
echo "3. 尝试上传新头像"
echo ""
echo "如果仍有问题，请检查："
echo "- Nginx/Apache 配置是否正确"
echo "- public/storage 软链接是否有效"
echo "- storage/app/public/avatars 目录权限"
echo ""
