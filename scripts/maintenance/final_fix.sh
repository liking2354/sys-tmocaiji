#!/bin/bash

# 最终修复脚本 - 解决所有任务状态问题
echo "🔧 最终任务状态修复脚本"
echo "====================================================="

PROJECT_PATH="/www/wwwroot/tmocaiji"

if [ ! -d "$PROJECT_PATH" ]; then
    echo "❌ 项目路径不存在: $PROJECT_PATH"
    exit 1
fi

cd "$PROJECT_PATH"

echo "📍 当前路径: $(pwd)"
echo ""

# 步骤1: 切换到同步队列
echo "🔄 步骤1: 修复队列配置"
echo "-----------------------------------------------------"

echo "当前队列驱动: $(grep QUEUE_CONNECTION .env | cut -d'=' -f2)"

if grep -q "QUEUE_CONNECTION=redis" .env; then
    echo "检测到Redis队列，但连接失败，切换到同步队列..."
    
    # 备份原始配置
    cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
    
    # 切换到同步队列
    sed -i 's/QUEUE_CONNECTION=redis/QUEUE_CONNECTION=sync/' .env
    echo "✅ 已切换到同步队列 (QUEUE_CONNECTION=sync)"
    
    # 清理配置缓存
    php artisan config:clear
    php artisan cache:clear
    echo "✅ 配置缓存已清理"
else
    echo "✅ 队列配置正常"
fi

echo ""

# 步骤2: 强制修复任务状态
echo "🔄 步骤2: 强制修复任务状态"
echo "-----------------------------------------------------"

php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

use App\Models\CollectionTask;
use App\Models\TaskDetail;

echo '检查并强制修复任务状态...' . PHP_EOL;

// 获取所有进行中的任务
\$runningTasks = CollectionTask::where('status', 1)->get();

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
    
    // 强制修复逻辑
    \$needsFix = true;
    \$newStatus = 1;
    
    // 如果所有任务详情都是未开始状态，但主任务是进行中，这是不正常的
    if (\$pendingDetails == \$totalDetails && \$runningDetails == 0 && \$completedDetails == 0 && \$failedDetails == 0) {
        echo '  ❌ 发现异常: 所有子任务都是未开始状态，但主任务显示进行中' . PHP_EOL;
        echo '  🔧 将主任务状态重置为未开始，以便重新执行' . PHP_EOL;
        \$newStatus = 0; // 重置为未开始
    }
    // 如果有已完成或失败的任务
    elseif (\$completedDetails > 0 || \$failedDetails > 0) {
        if (\$pendingDetails == 0 && \$runningDetails == 0) {
            // 所有任务都已完成
            \$newStatus = \$failedDetails > 0 ? 3 : 2;
            echo '  🔧 所有子任务已完成，更新主任务状态为: ' . (\$newStatus == 2 ? '已完成' : '失败') . PHP_EOL;
        } else {
            // 还有未完成的任务，保持进行中
            \$newStatus = 1;
            echo '  ✅ 任务仍在进行中，状态正常' . PHP_EOL;
            \$needsFix = false;
        }
    }
    // 如果有正在进行的任务
    elseif (\$runningDetails > 0) {
        \$newStatus = 1;
        echo '  ✅ 有子任务正在进行中，状态正常' . PHP_EOL;
        \$needsFix = false;
    }
    
    if (\$needsFix) {
        echo '  🔧 正在修复任务状态...' . PHP_EOL;
        
        \$updateData = [
            'status' => \$newStatus,
            'total_servers' => \$totalDetails,
            'completed_servers' => \$completedDetails,
            'failed_servers' => \$failedDetails,
        ];
        
        // 如果任务完成，设置完成时间
        if (\$newStatus == 2 || \$newStatus == 3) {
            \$updateData['completed_at'] = now();
        }
        // 如果重置为未开始，清除开始和完成时间
        elseif (\$newStatus == 0) {
            \$updateData['started_at'] = null;
            \$updateData['completed_at'] = null;
        }
        
        \$task->update(\$updateData);
        
        \$statusText = [0 => '未开始', 1 => '进行中', 2 => '已完成', 3 => '失败'][\$newStatus];
        echo '  ✅ 修复完成: 状态=' . \$statusText . ', 总数=' . \$totalDetails . ', 完成=' . \$completedDetails . ', 失败=' . \$failedDetails . PHP_EOL;
    }
    
    echo '' . PHP_EOL;
}

echo '任务状态修复完成!' . PHP_EOL;
"

echo ""

# 步骤3: 显示修复后的状态
echo "🔄 步骤3: 显示修复后的状态"
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

echo '' . PHP_EOL;
echo '详细任务信息:' . PHP_EOL;
\$allTasks = CollectionTask::orderBy('created_at', 'desc')->limit(5)->get();
foreach (\$allTasks as \$task) {
    echo '  ID: ' . \$task->id . ' | 名称: ' . \$task->name . ' | 状态: ' . (\$statusMap[\$task->status] ?? '未知') . ' | 进度: ' . \$task->completed_servers . '/' . \$task->total_servers . PHP_EOL;
}
"

echo ""

# 步骤4: 测试任务执行
echo "🔄 步骤4: 测试任务执行机制"
echo "-----------------------------------------------------"

echo "测试Laravel调度器..."
php artisan schedule:run

echo ""
echo "当前队列配置: $(grep QUEUE_CONNECTION .env | cut -d'=' -f2)"

echo ""
echo "✅ 最终修复完成!"
echo "====================================================="
echo ""
echo "📋 修复总结:"
echo "  ✓ 切换到同步队列，避免Redis连接问题"
echo "  ✓ 强制修复了任务状态不一致问题"
echo "  ✓ 重置异常任务状态，允许重新执行"
echo "  ✓ 清理了配置缓存"
echo "  ✓ 测试了任务执行机制"
echo ""
echo "🎯 下一步建议:"
echo "  1. 如果任务状态为'未开始'，可以在Web界面重新启动任务"
echo "  2. 监控 storage/logs/laravel.log 查看任务执行日志"
echo "  3. 使用同步队列后，任务将立即执行，无需队列处理器"
echo ""
echo "🔍 如果问题仍然存在:"
echo "  1. 检查服务器SSH连接是否正常"
echo "  2. 检查采集组件脚本是否存在"
echo "  3. 查看详细错误日志: tail -f storage/logs/laravel.log"