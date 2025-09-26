#!/bin/bash

# 简化任务状态修复脚本
echo "🔧 简化任务状态修复脚本"
echo "====================================================="

PROJECT_PATH="/www/wwwroot/tmocaiji"

if [ ! -d "$PROJECT_PATH" ]; then
    echo "❌ 项目路径不存在: $PROJECT_PATH"
    exit 1
fi

cd "$PROJECT_PATH"

echo "📍 当前路径: $(pwd)"
echo ""

# 步骤1: 检查并修复任务状态
echo "🔄 步骤1: 检查并修复任务状态"
echo "-----------------------------------------------------"

php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

use App\Models\CollectionTask;
use App\Models\TaskDetail;

echo '检查当前任务状态...' . PHP_EOL;

// 获取所有进行中的任务
\$runningTasks = CollectionTask::where('status', 1)->get();

if (\$runningTasks->isEmpty()) {
    echo '✅ 没有进行中的任务需要修复' . PHP_EOL;
} else {
    foreach (\$runningTasks as \$task) {
        echo '处理任务 ID: ' . \$task->id . ' - ' . \$task->name . PHP_EOL;
        
        // 统计任务详情状态
        \$detailStats = \$task->taskDetails()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        \$totalDetails = array_sum(\$detailStats);
        \$completedDetails = (\$detailStats[2] ?? 0);
        \$failedDetails = (\$detailStats[3] ?? 0);
        \$runningDetails = (\$detailStats[1] ?? 0);
        \$pendingDetails = (\$detailStats[0] ?? 0);
        
        echo '  任务详情统计: 总计=' . \$totalDetails . ', 未开始=' . \$pendingDetails . ', 进行中=' . \$runningDetails . ', 已完成=' . \$completedDetails . ', 失败=' . \$failedDetails . PHP_EOL;
        
        // 检查是否需要修复
        \$needsFix = false;
        \$newStatus = 1;
        
        // 如果所有任务详情都已完成或失败
        if (\$pendingDetails == 0 && \$runningDetails == 0) {
            \$needsFix = true;
            \$newStatus = \$failedDetails > 0 ? 3 : 2;
            echo '  ❌ 需要修复: 所有子任务已完成，但主任务仍为进行中' . PHP_EOL;
        }
        
        // 检查统计数据
        if (\$task->total_servers != \$totalDetails || \$task->completed_servers != \$completedDetails || \$task->failed_servers != \$failedDetails) {
            \$needsFix = true;
            echo '  ❌ 需要修复: 统计数据不匹配' . PHP_EOL;
        }
        
        if (\$needsFix) {
            echo '  🔧 正在修复...' . PHP_EOL;
            
            \$updateData = [
                'status' => \$newStatus,
                'total_servers' => \$totalDetails,
                'completed_servers' => \$completedDetails,
                'failed_servers' => \$failedDetails,
            ];
            
            if (\$newStatus != 1 && !\$task->completed_at) {
                \$updateData['completed_at'] = now();
            }
            
            \$task->update(\$updateData);
            
            echo '  ✅ 修复完成: 状态=' . \$newStatus . ', 总数=' . \$totalDetails . ', 完成=' . \$completedDetails . ', 失败=' . \$failedDetails . PHP_EOL;
        } else {
            echo '  ✅ 状态正常' . PHP_EOL;
        }
        
        echo '' . PHP_EOL;
    }
}

echo '任务状态修复完成!' . PHP_EOL;
"

echo ""

# 步骤2: 显示最终状态
echo "🔄 步骤2: 显示修复后的状态"
echo "-----------------------------------------------------"

php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

use App\Models\CollectionTask;

echo '当前任务状态统计:' . PHP_EOL;

\$tasks = CollectionTask::selectRaw('
    status,
    COUNT(*) as count,
    MAX(updated_at) as last_updated
')->groupBy('status')->get();

\$statusMap = [0 => '未开始', 1 => '进行中', 2 => '已完成', 3 => '失败'];
foreach (\$tasks as \$task) {
    echo '  ' . (\$statusMap[\$task->status] ?? '未知') . ': ' . \$task->count . ' 个 (最后更新: ' . \$task->last_updated . ')' . PHP_EOL;
}
"

echo ""

# 步骤3: 检查队列配置
echo "🔄 步骤3: 检查队列配置"
echo "-----------------------------------------------------"

echo "当前队列驱动: $(grep QUEUE_CONNECTION .env | cut -d'=' -f2)"

# 如果是Redis队列，检查连接
if grep -q "QUEUE_CONNECTION=redis" .env; then
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
            echo "⚠️  没有发现队列处理器，建议启动:"
            echo "   nohup php artisan queue:work --daemon --timeout=300 > storage/logs/queue.log 2>&1 &"
        else
            echo "✅ 队列处理器已在运行 ($QUEUE_PROCESS 个进程)"
        fi
    else
        echo "❌ Redis连接失败，建议切换到同步队列:"
        echo "   sed -i 's/QUEUE_CONNECTION=redis/QUEUE_CONNECTION=sync/' .env"
        echo "   php artisan config:clear"
    fi
else
    echo "✅ 使用同步队列，任务将立即执行"
fi

echo ""
echo "✅ 简化修复完成!"
echo "====================================================="
echo ""
echo "📋 修复总结:"
echo "  ✓ 检查并修复了任务状态不一致问题"
echo "  ✓ 更新了任务统计数据"
echo "  ✓ 检查了队列配置"
echo ""
echo "🔍 如果问题仍然存在，请检查:"
echo "  1. storage/logs/laravel.log - 应用日志"
echo "  2. 确保数据库迁移已完成: php artisan migrate"
echo "  3. 清理缓存: php artisan config:clear && php artisan cache:clear"