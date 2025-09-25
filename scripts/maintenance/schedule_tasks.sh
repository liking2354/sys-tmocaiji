#!/bin/bash

# 系统采集项目定时任务脚本
# 此脚本用于设置系统采集项目的定时任务

# 自动检测项目路径（脚本位于 scripts/maintenance/ 目录下）
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_PATH="$(cd "$SCRIPT_DIR/../.." && pwd)"

# 如果是在生产环境，可以通过参数指定项目路径
if [ ! -z "$1" ]; then
    PROJECT_PATH="$1"
fi

echo "检测到的项目路径: $PROJECT_PATH"

# 确保脚本可执行
chmod +x "$PROJECT_PATH/artisan"

# 创建临时crontab文件
TEMP_CRON_FILE=$(mktemp)

# 导出当前crontab到临时文件
crontab -l > "$TEMP_CRON_FILE" 2>/dev/null || echo "" > "$TEMP_CRON_FILE"

# 检查是否已存在相同的定时任务
if grep -q "$PROJECT_PATH/artisan schedule:run" "$TEMP_CRON_FILE"; then
    echo "定时任务已存在，无需重复添加"
else
    # 添加Laravel调度任务（每分钟执行一次）
    echo "* * * * * cd $PROJECT_PATH && php artisan schedule:run >> /dev/null 2>&1" >> "$TEMP_CRON_FILE"
    
    # 添加任务状态重置任务（每小时执行一次）
    echo "0 * * * * cd $PROJECT_PATH && php artisan tasks:reset-stuck >> /dev/null 2>&1" >> "$TEMP_CRON_FILE"
    
    # 应用新的crontab
    crontab "$TEMP_CRON_FILE"
    echo "定时任务已添加到crontab"
fi

# 删除临时文件
rm "$TEMP_CRON_FILE"

echo "====================================================="
echo "定时任务设置完成!"
echo "====================================================="
echo "项目路径: $PROJECT_PATH"
echo "已添加以下定时任务:"
echo "1. Laravel调度任务 - 每分钟执行一次"
echo "2. 任务状态重置 - 每小时执行一次"
echo "====================================================="
echo "使用方法:"
echo "1. 自动检测路径: ./scripts/maintenance/schedule_tasks.sh"
echo "2. 指定项目路径: ./scripts/maintenance/schedule_tasks.sh /path/to/project"
echo "3. 查看定时任务: crontab -l"
echo "====================================================="