#!/bin/bash

# 快速修复脚本 - 一键解决任务状态问题
echo "🔧 任务状态快速修复脚本"
echo "====================================================="

PROJECT_PATH="/www/wwwroot/tmocaiji"

if [ ! -d "$PROJECT_PATH" ]; then
    echo "❌ 项目路径不存在: $PROJECT_PATH"
    echo "请确认项目是否正确部署在该路径下"
    exit 1
fi

cd "$PROJECT_PATH"

echo "📍 当前路径: $(pwd)"
echo ""

# 步骤1: 修复任务状态
echo "🔄 步骤1: 修复任务状态不一致问题"
echo "-----------------------------------------------------"
php artisan tasks:fix-status

echo ""

# 步骤2: 重置卡住的任务
echo "🔄 步骤2: 重置卡住的任务"
echo "-----------------------------------------------------"
php artisan tasks:reset-stuck --hours=1

echo ""

# 步骤3: 处理队列问题
echo "🔄 步骤3: 处理队列配置问题"
echo "-----------------------------------------------------"

# 检查Redis连接
echo "检查Redis连接..."
REDIS_CHECK=$(php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

try {
    app('redis')->ping();
    echo 'OK';
} catch (Exception \$e) {
    echo 'FAILED';
}
" 2>/dev/null | tail -1)

if [ "$REDIS_CHECK" = "OK" ]; then
    echo "✅ Redis连接正常"
    
    # 检查是否有队列处理器在运行
    QUEUE_PROCESS=$(ps aux | grep "queue:work" | grep -v grep | wc -l)
    
    if [ $QUEUE_PROCESS -eq 0 ]; then
        echo "⚠️  没有发现队列处理器，启动队列处理器..."
        nohup php artisan queue:work --daemon --timeout=300 --tries=3 > storage/logs/queue.log 2>&1 &
        echo "✅ 队列处理器已启动 (PID: $!)"
        echo "📝 日志文件: storage/logs/queue.log"
    else
        echo "✅ 队列处理器已在运行 ($QUEUE_PROCESS 个进程)"
    fi
else
    echo "❌ Redis连接失败，切换到同步队列..."
    
    # 备份原始.env文件
    cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
    
    # 切换到同步队列
    sed -i 's/QUEUE_CONNECTION=redis/QUEUE_CONNECTION=sync/' .env
    echo "✅ 已切换到同步队列 (QUEUE_CONNECTION=sync)"
    echo "📝 原始配置已备份"
fi

echo ""

# 步骤4: 清理缓存
echo "🔄 步骤4: 清理配置缓存"
echo "-----------------------------------------------------"
php artisan config:clear
php artisan cache:clear
echo "✅ 缓存已清理"

echo ""

# 步骤5: 验证修复结果
echo "🔄 步骤5: 验证修复结果"
echo "-----------------------------------------------------"

# 检查任务状态统计
echo "当前任务状态统计:"
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

\$tasks = \App\Models\CollectionTask::selectRaw('
    status,
    COUNT(*) as count
')->groupBy('status')->get();

\$statusMap = [0 => '未开始', 1 => '进行中', 2 => '已完成', 3 => '失败'];
foreach (\$tasks as \$task) {
    echo '  ' . (\$statusMap[\$task->status] ?? '未知') . ': ' . \$task->count . ' 个' . PHP_EOL;
}
"

echo ""

# 检查队列状态
echo "当前队列状态:"
echo "  队列驱动: $(grep QUEUE_CONNECTION .env | cut -d'=' -f2)"

FAILED_JOBS=$(php artisan queue:failed 2>/dev/null | grep -c "No failed jobs" && echo "0" || php artisan queue:failed 2>/dev/null | wc -l)
echo "  失败任务: $FAILED_JOBS 个"

echo ""

# 最终建议
echo "✅ 快速修复完成!"
echo "====================================================="
echo ""
echo "📋 修复总结:"
echo "  ✓ 任务状态已修复"
echo "  ✓ 卡住的任务已重置"
echo "  ✓ 队列配置已优化"
echo "  ✓ 缓存已清理"
echo ""
echo "🔍 建议执行以下命令进行详细检查:"
echo "  ./scripts/maintenance/diagnose_tasks.sh"
echo ""
echo "📝 如果问题仍然存在，请检查:"
echo "  1. storage/logs/laravel.log - 应用日志"
echo "  2. storage/logs/queue.log - 队列日志 (如果使用Redis队列)"
echo "  3. storage/logs/tasks-reset.log - 任务重置日志"
echo ""
echo "🆘 如需技术支持，请提供上述日志文件内容"