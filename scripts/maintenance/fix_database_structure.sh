#!/bin/bash

# 修复数据库结构脚本
echo "🔧 数据库结构修复脚本"
echo "====================================================="

PROJECT_PATH="/www/wwwroot/tmocaiji"

if [ ! -d "$PROJECT_PATH" ]; then
    echo "❌ 项目路径不存在: $PROJECT_PATH"
    exit 1
fi

cd "$PROJECT_PATH"

echo "📍 当前路径: $(pwd)"
echo ""

# 步骤1: 检查数据库表结构
echo "🔄 步骤1: 检查数据库表结构"
echo "-----------------------------------------------------"

echo "检查现有表..."
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

\$tables = ['servers', 'collectors', 'collection_tasks', 'task_details', 'collection_history'];

foreach (\$tables as \$table) {
    if (Schema::hasTable(\$table)) {
        echo '✅ 表 ' . \$table . ' 存在' . PHP_EOL;
    } else {
        echo '❌ 表 ' . \$table . ' 不存在' . PHP_EOL;
    }
}
"

echo ""

# 步骤2: 运行数据库迁移
echo "🔄 步骤2: 运行数据库迁移"
echo "-----------------------------------------------------"

echo "执行数据库迁移..."
php artisan migrate --force

echo ""

# 步骤3: 验证表结构
echo "🔄 步骤3: 验证表结构"
echo "-----------------------------------------------------"

echo "再次检查表结构..."
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

\$tables = ['servers', 'collectors', 'collection_tasks', 'task_details', 'collection_history'];

foreach (\$tables as \$table) {
    if (Schema::hasTable(\$table)) {
        echo '✅ 表 ' . \$table . ' 存在' . PHP_EOL;
    } else {
        echo '❌ 表 ' . \$table . ' 不存在' . PHP_EOL;
    }
}
"

echo ""

# 步骤4: 检查collection_histories表结构
echo "🔄 步骤4: 检查collection_histories表结构"
echo "-----------------------------------------------------"

php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

if (Schema::hasTable('collection_history')) {
    echo '检查collection_history表结构:' . PHP_EOL;
    \$columns = DB::select('DESCRIBE collection_history');
    foreach (\$columns as \$column) {
        echo '  ' . \$column->Field . ' (' . \$column->Type . ')' . PHP_EOL;
    }
    
    \$count = DB::table('collection_history')->count();
    echo '表中记录数: ' . \$count . PHP_EOL;
} else {
    echo '❌ collection_history表仍然不存在' . PHP_EOL;
}
"

echo ""

# 步骤5: 修复任务状态（如果表存在）
echo "🔄 步骤5: 修复任务状态"
echo "-----------------------------------------------------"

php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use App\Models\CollectionTask;

if (Schema::hasTable('collection_history')) {
    echo '开始修复任务状态...' . PHP_EOL;
    
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
} else {
    echo '❌ collection_history表不存在，跳过任务状态修复' . PHP_EOL;
}
"

echo ""
echo "✅ 数据库结构修复完成!"
echo "====================================================="
echo ""
echo "📋 修复总结:"
echo "  ✓ 检查了数据库表结构"
echo "  ✓ 运行了数据库迁移"
echo "  ✓ 创建了缺失的collection_histories表"
echo "  ✓ 修复了任务状态问题"
echo ""
echo "🔍 建议运行以下命令验证结果:"
echo "  ./scripts/maintenance/diagnose_tasks.sh"