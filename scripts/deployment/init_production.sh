#!/bin/bash

# 生产环境初始化脚本
# 使用方法: ./init_production.sh [目标目录]

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# 设置默认目标目录
TARGET_DIR=${1:-"/www/wwwroot/tmocaiji"}
REPO_URL="https://github.com/liking2354/sys-tmocaiji.git"

echo -e "${BLUE}====================================================="
echo "生产环境初始化脚本"
echo "====================================================="
echo -e "目标目录: ${GREEN}$TARGET_DIR${NC}"
echo -e "远程仓库: ${GREEN}$REPO_URL${NC}"
echo -e "${BLUE}=====================================================${NC}"

# 检查目标目录是否存在
if [ ! -d "$TARGET_DIR" ]; then
    echo -e "${RED}错误: 目标目录不存在: $TARGET_DIR${NC}"
    echo -e "${YELLOW}请先创建目录或使用正确的路径${NC}"
    exit 1
fi

cd "$TARGET_DIR"

# 检查目录是否为空或已有项目文件
if [ -d ".git" ]; then
    echo -e "${GREEN}目录已经是Git仓库，无需重新初始化${NC}"
    echo -e "${BLUE}正在检查远程仓库配置...${NC}"
    
    CURRENT_REMOTE=$(git remote get-url origin 2>/dev/null || echo "")
    if [ "$CURRENT_REMOTE" != "$REPO_URL" ]; then
        echo -e "${YELLOW}更新远程仓库地址...${NC}"
        git remote set-url origin "$REPO_URL"
    fi
    
    echo -e "${GREEN}Git仓库配置正确${NC}"
    exit 0
fi

# 检查目录是否有文件
FILE_COUNT=$(find . -maxdepth 1 -type f | wc -l)
if [ $FILE_COUNT -gt 0 ]; then
    echo -e "${YELLOW}检测到目录中已有文件，需要选择初始化方式:${NC}"
    echo "1. 备份现有文件并重新克隆 (推荐)"
    echo "2. 将现有目录转换为Git仓库"
    echo "3. 取消操作"
    
    read -p "请选择 (1-3): " -n 1 -r
    echo
    
    case $REPLY in
        1)
            echo -e "${BLUE}选择方案1: 备份现有文件并重新克隆${NC}"
            BACKUP_DIR="${TARGET_DIR}_backup_$(date +%Y%m%d_%H%M%S)"
            echo -e "${YELLOW}正在备份现有文件到: $BACKUP_DIR${NC}"
            
            cd ..
            mv "$TARGET_DIR" "$BACKUP_DIR"
            
            echo -e "${BLUE}正在克隆仓库...${NC}"
            git clone "$REPO_URL" "$TARGET_DIR"
            
            if [ $? -eq 0 ]; then
                echo -e "${GREEN}仓库克隆成功${NC}"
                
                # 恢复重要配置文件
                if [ -f "$BACKUP_DIR/.env" ]; then
                    echo -e "${BLUE}恢复 .env 配置文件...${NC}"
                    cp "$BACKUP_DIR/.env" "$TARGET_DIR/.env"
                fi
                
                if [ -d "$BACKUP_DIR/storage" ]; then
                    echo -e "${BLUE}恢复 storage 目录...${NC}"
                    cp -r "$BACKUP_DIR/storage"/* "$TARGET_DIR/storage/" 2>/dev/null || true
                fi
                
                echo -e "${GREEN}备份位置: $BACKUP_DIR${NC}"
            else
                echo -e "${RED}克隆失败，恢复原目录${NC}"
                mv "$BACKUP_DIR" "$TARGET_DIR"
                exit 1
            fi
            ;;
        2)
            echo -e "${BLUE}选择方案2: 将现有目录转换为Git仓库${NC}"
            
            # 初始化Git仓库
            git init
            git remote add origin "$REPO_URL"
            
            # 获取远程分支信息
            git fetch origin
            
            # 检查是否有冲突
            echo -e "${YELLOW}正在检查与远程仓库的差异...${NC}"
            git checkout -b temp-local
            git add .
            git commit -m "本地现有文件备份"
            
            # 切换到主分支
            git checkout -b main origin/main
            
            echo -e "${YELLOW}请手动解决可能的文件冲突，然后运行:${NC}"
            echo "git merge temp-local"
            echo "git branch -d temp-local"
            ;;
        3)
            echo -e "${YELLOW}操作已取消${NC}"
            exit 0
            ;;
        *)
            echo -e "${RED}无效选择${NC}"
            exit 1
            ;;
    esac
else
    echo -e "${BLUE}目录为空，直接克隆仓库...${NC}"
    cd ..
    git clone "$REPO_URL" "$TARGET_DIR"
    
    if [ $? -ne 0 ]; then
        echo -e "${RED}克隆失败${NC}"
        exit 1
    fi
fi

cd "$TARGET_DIR"

# 设置文件权限
echo -e "${BLUE}正在设置文件权限...${NC}"
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod +x artisan
find scripts -name "*.sh" -exec chmod +x {} \;

# 创建.env文件
if [ ! -f ".env" ]; then
    echo -e "${BLUE}创建 .env 配置文件...${NC}"
    cp .env.example .env
    echo -e "${YELLOW}请记得编辑 .env 文件配置数据库等设置${NC}"
fi

# 安装依赖
echo -e "${BLUE}正在安装Composer依赖...${NC}"
composer install --no-dev --optimize-autoloader

# 生成应用密钥
if ! grep -q "APP_KEY=base64:" .env; then
    echo -e "${BLUE}生成应用密钥...${NC}"
    php artisan key:generate
fi

# 创建存储链接
echo -e "${BLUE}创建存储链接...${NC}"
php artisan storage:link

# 设置存储目录权限
echo -e "${BLUE}设置存储目录权限...${NC}"
chmod -R 775 storage bootstrap/cache

# 清除缓存
echo -e "${BLUE}清除缓存...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo -e "${GREEN}====================================================="
echo "生产环境初始化完成!"
echo "====================================================="
echo -e "项目目录: ${GREEN}$TARGET_DIR${NC}"
echo -e "Git仓库: ${GREEN}已配置${NC}"
echo -e "依赖安装: ${GREEN}已完成${NC}"
echo -e "${GREEN}=====================================================${NC}"
echo -e "${YELLOW}接下来需要:${NC}"
echo "1. 编辑 .env 文件配置数据库连接"
echo "2. 运行数据库迁移: php artisan migrate"
echo "3. 配置Web服务器指向 public 目录"
echo "4. 设置定时任务: ./scripts/maintenance/schedule_tasks.sh"
echo "5. 测试更新功能: ./scripts/maintenance/update.sh"
echo -e "${GREEN}=====================================================${NC}"