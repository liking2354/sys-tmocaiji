#!/bin/bash

# 强制修复任务状态脚本
echo "🔧 强制修复任务状态脚本"
echo "====================================================="

PROJECT_PATH="/www/wwwroot/tmocaiji"

if [ ! -d "$PROJECT_PATH" ]; then
    echo "❌ 项目路径不存在: $PROJECT_PATH"
    exit 1
fi

cd "$PROJECT_PATH"

echo "📍 当前路径: $(pwd)"
echo ""

# 步骤1: 直接通过数据库修复任务状态
echo "🔄 步骤1: 直接修复数据库中的任务状态"
echo "-----------------------------------------------------"

php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

use App\Models\CollectionTask;
use App\Models\TaskDetail;
use App\Models\CollectionHistory;

echo '检查当前任务状态...' . PHP_EOL;

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

echo '任务状态修复完成!' . PHP_EOL;
"

echo ""

# 步骤2: 检查并创建缺失的采集历史
echo "🔄 步骤2: 检查并创建缺失的采集历史"
echo "-----------------------------------------------------"

php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

use App\Models\TaskDetail;
use App\Models\CollectionHistory;

echo '检查孤立的任务详情...' . PHP_EOL;

// 查找已完成但没有采集历史的任务详情
\$orphanedDetails = TaskDetail::where('status', 2)
    ->whereNotExists(function(\$query) {
        \$query->select(\DB::raw(1))
              ->from('collection_history')
              ->whereRaw('collection_history.task_detail_id = task_details.id');
    })
    ->with(['task', 'server', 'collector'])
    ->get();

if (\$orphanedDetails->count() > 0) {
    echo '发现 ' . \$orphanedDetails->count() . ' 个孤立的任务详情' . PHP_EOL;
    
    foreach (\$orphanedDetails as \$detail) {
        echo '  创建采集历史: 任务详情 ID ' . \$detail->id . PHP_EOL;
        
        CollectionHistory::create([
            'server_id' => \$detail->server_id,
            'collector_id' => \$detail->collector_id,
            'task_detail_id' => \$detail->id,
            'result' => \$detail->result,
            'status' => \$detail->status,
            'error_message' => \$detail->error_message,
            'execution_time' => \$detail->execution_time ?? 0,
            'created_at' => \$detail->completed_at ?? \$detail->updated_at,
            'updated_at' => \$detail->completed_at ?? \$detail->updated_at,
        ]);
    }
    
    echo '✅ 已创建 ' . \$orphanedDetails->count() . ' 条采集历史记录' . PHP_EOL;
} else {
    echo '✅ 没有发现孤立的任务详情' . PHP_EOL;
}
"

echo ""

# 步骤3: 显示最终状态
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
"

echo ""
echo "✅ 强制修复完成!"
echo "====================================================="
echo ""
echo "📋 修复总结:"
echo "  ✓ 直接修复了数据库中的任务状态"
echo "  ✓ 创建了缺失的采集历史记录"
echo "  ✓ 更新了任务统计数据"
echo ""
echo "🔍 建议运行诊断脚本验证结果:"
echo "  ./scripts/maintenance/diagnose_tasks.sh"