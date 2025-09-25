#!/bin/bash

# 任务状态诊断脚本
# 使用方法: ./diagnose_tasks.sh [项目路径]

# 颜色定义
RED='\033[0;31m'
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

echo -e "${BLUE}====================================================="
echo "任务状态诊断脚本"
echo "====================================================="
echo -e "项目路径: ${GREEN}$PROJECT_PATH${NC}"
echo -e "${BLUE}=====================================================${NC}"

cd "$PROJECT_PATH"

# 检查是否为Laravel项目
if [ ! -f "artisan" ]; then
    echo -e "${RED}错误: 当前目录不是Laravel项目${NC}"
    exit 1
fi

echo -e "${BLUE}1. 检查定时任务设置${NC}"
echo "当前crontab设置:"
crontab -l | grep -E "(schedule:run|tasks:reset-stuck)" || echo -e "${YELLOW}未找到相关定时任务${NC}"

echo -e "\n${BLUE}2. 检查Laravel调度配置${NC}"
php artisan schedule:list | grep -E "(tasks:reset-stuck|schedule:run)" || echo -e "${YELLOW}未找到任务重置调度${NC}"

echo -e "\n${BLUE}3. 执行任务状态诊断${NC}"
php artisan tasks:diagnose --hours=2

echo -e "\n${BLUE}4. 检查最近的任务执行日志${NC}"
if [ -f "storage/logs/tasks-reset.log" ]; then
    echo "最近的任务重置日志:"
    tail -10 storage/logs/tasks-reset.log
else
    echo -e "${YELLOW}未找到任务重置日志文件${NC}"
fi

echo -e "\n${BLUE}5. 检查Laravel应用日志${NC}"
if [ -f "storage/logs/laravel.log" ]; then
    echo "最近的应用日志 (最后10行):"
    tail -10 storage/logs/laravel.log
else
    echo -e "${YELLOW}未找到应用日志文件${NC}"
fi

echo -e "\n${BLUE}6. 其他有用的命令${NC}"
echo -e "${YELLOW}如需进一步排查，可以使用以下命令:${NC}"
echo "1. 查看定时任务: crontab -l"
echo "2. 测试Laravel调度: php artisan schedule:run"
echo "3. 手动重置任务: php artisan tasks:reset-stuck --hours=2"
echo "4. 查看实时日志: tail -f storage/logs/laravel.log"
echo "5. 检查数据库连接: php artisan db:show"

echo -e "\n${GREEN}诊断完成!${NC}"