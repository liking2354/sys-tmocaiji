#!/bin/bash

# 快速任务诊断脚本
# 使用方法: ./quick_diagnose.sh [项目路径]

# 颜色定义
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

# 自动检测项目路径
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_PATH="$(cd "$SCRIPT_DIR/../.." && pwd)"

if [ ! -z "$1" ]; then
    PROJECT_PATH="$1"
fi

echo -e "${BLUE}快速任务诊断${NC}"
echo -e "项目路径: ${GREEN}$PROJECT_PATH${NC}"

cd "$PROJECT_PATH"

# 检查是否为Laravel项目
if [ ! -f "artisan" ]; then
    echo -e "${RED}错误: 当前目录不是Laravel项目${NC}"
    exit 1
fi

echo -e "\n${BLUE}正在执行任务诊断...${NC}"

# 执行诊断命令
php artisan tasks:diagnose --hours=2

echo -e "\n${BLUE}快速检查完成!${NC}"