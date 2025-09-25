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

echo -e "\n${BLUE}3. 检查卡住的主任务${NC}"
php artisan tinker --execute="
\$stuckTasks = App\Models\CollectionTask::where('status', 1)
    ->where('started_at', '<', now()->subHours(2))
    ->get(['id', 'name', 'status', 'started_at', 'total_servers', 'completed_servers', 'failed_servers']);

if (\$stuckTasks->count() > 0) {
    echo \"发现 {\$stuckTasks->count()} 个卡住的主任务:\n\";
    foreach (\$stuckTasks as \$task) {
        echo \"ID: {\$task->id}, 名称: {\$task->name}, 开始时间: {\$task->started_at}, 进度: {\$task->completed_servers + \$task->failed_servers}/{\$task->total_servers}\n\";
    }
} else {
    echo \"没有发现卡住的主任务\n\";
}
"

echo -e "\n${BLUE}4. 检查卡住的任务详情${NC}"
php artisan tinker --execute="
\$stuckDetails = App\Models\TaskDetail::where('status', 1)
    ->where('started_at', '<', now()->subHours(2))
    ->with(['task', 'server'])
    ->get(['id', 'task_id', 'server_id', 'status', 'started_at']);

if (\$stuckDetails->count() > 0) {
    echo \"发现 {\$stuckDetails->count()} 个卡住的任务详情:\n\";
    foreach (\$stuckDetails as \$detail) {
        echo \"详情ID: {\$detail->id}, 任务ID: {\$detail->task_id}, 服务器: {\$detail->server->name ?? 'N/A'}, 开始时间: {\$detail->started_at}\n\";
    }
} else {
    echo \"没有发现卡住的任务详情\n\";
}
"

echo -e "\n${BLUE}5. 检查最近的任务执行日志${NC}"
if [ -f "storage/logs/tasks-reset.log" ]; then
    echo "最近的任务重置日志:"
    tail -10 storage/logs/tasks-reset.log
else
    echo -e "${YELLOW}未找到任务重置日志文件${NC}"
fi

echo -e "\n${BLUE}6. 手动执行任务重置${NC}"
read -p "是否立即执行任务重置? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${BLUE}正在执行任务重置...${NC}"
    php artisan tasks:reset-stuck --hours=2
    echo -e "${GREEN}任务重置完成${NC}"
fi

echo -e "\n${BLUE}7. 建议的解决方案${NC}"
echo -e "${YELLOW}如果发现卡住的任务，建议:${NC}"
echo "1. 确保定时任务正常运行: crontab -l"
echo "2. 检查Laravel调度是否正常: php artisan schedule:run"
echo "3. 手动重置卡住的任务: php artisan tasks:reset-stuck"
echo "4. 检查服务器连接状态和采集脚本"
echo "5. 查看应用日志: tail -f storage/logs/laravel.log"

echo -e "\n${GREEN}诊断完成!${NC}"