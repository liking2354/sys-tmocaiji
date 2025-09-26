#!/bin/bash

# 任务管理脚本 - 重构版
echo "🔧 任务管理脚本 (重构版)"
echo "====================================================="

PROJECT_PATH="/www/wwwroot/tmocaiji"

if [ ! -d "$PROJECT_PATH" ]; then
    echo "❌ 项目路径不存在: $PROJECT_PATH"
    exit 1
fi

cd "$PROJECT_PATH"

echo "📍 当前路径: $(pwd)"
echo ""

# 显示菜单
show_menu() {
    echo "请选择操作:"
    echo "1. 查看任务状态"
    echo "2. 执行指定任务"
    echo "3. 重置任务状态"
    echo "4. 取消正在执行的任务"
    echo "5. 清理完成的任务"
    echo "6. 系统诊断"
    echo "0. 退出"
    echo ""
    read -p "请输入选项 (0-6): " choice
}

# 查看任务状态
view_task_status() {
    echo "🔍 查看任务状态"
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
    echo '最近的任务:' . PHP_EOL;
    \$recentTasks = CollectionTask::orderBy('created_at', 'desc')->limit(10)->get();
    foreach (\$recentTasks as \$task) {
        echo '  ID: ' . \$task->id . ' | 名称: ' . \$task->name . ' | 状态: ' . (\$statusMap[\$task->status] ?? '未知') . ' | 创建时间: ' . \$task->created_at . PHP_EOL;
    }
    "
}

# 执行指定任务
execute_task() {
    echo "▶️ 执行指定任务"
    echo "-----------------------------------------------------"
    
    read -p "请输入任务ID: " task_id
    
    if [[ ! "$task_id" =~ ^[0-9]+$ ]]; then
        echo "❌ 无效的任务ID"
        return
    fi
    
    echo "正在执行任务 ID: $task_id"
    
    php -r "
    require 'vendor/autoload.php';
    \$app = require_once 'bootstrap/app.php';
    \$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
    \$kernel->bootstrap();

    use App\Services\TaskExecutionService;
    use App\Services\CollectionService;

    \$collectionService = new CollectionService();
    \$taskExecutionService = new TaskExecutionService(\$collectionService);

    echo '开始执行任务...' . PHP_EOL;
    \$result = \$taskExecutionService->executeBatchTask($task_id);

    if (\$result['success']) {
        echo '✅ ' . \$result['message'] . PHP_EOL;
    } else {
        echo '❌ ' . \$result['message'] . PHP_EOL;
    }
    "
}

# 重置任务状态
reset_task() {
    echo "🔄 重置任务状态"
    echo "-----------------------------------------------------"
    
    read -p "请输入任务ID (留空重置所有异常任务): " task_id
    
    if [ -z "$task_id" ]; then
        echo "重置所有异常任务..."
        php -r "
        require 'vendor/autoload.php';
        \$app = require_once 'bootstrap/app.php';
        \$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
        \$kernel->bootstrap();

        use App\Models\CollectionTask;
        use App\Models\TaskDetail;

        echo '查找异常任务...' . PHP_EOL;
        \$abnormalTasks = CollectionTask::where('status', 1)
            ->whereDoesntHave('taskDetails', function(\$query) {
                \$query->where('status', 1);
            })->get();

        foreach (\$abnormalTasks as \$task) {
            echo '重置任务 ID: ' . \$task->id . ' - ' . \$task->name . PHP_EOL;
            \$task->update([
                'status' => 0,
                'completed_servers' => 0,
                'failed_servers' => 0,
                'started_at' => null,
                'completed_at' => null
            ]);
            
            \$task->taskDetails()->update([
                'status' => 0,
                'result' => null,
                'error_message' => null,
                'execution_time' => null,
                'started_at' => null,
                'completed_at' => null
            ]);
        }

        echo '✅ 重置了 ' . \$abnormalTasks->count() . ' 个异常任务' . PHP_EOL;
        "
    else
        if [[ ! "$task_id" =~ ^[0-9]+$ ]]; then
            echo "❌ 无效的任务ID"
            return
        fi
        
        echo "重置任务 ID: $task_id"
        
        php -r "
        require 'vendor/autoload.php';
        \$app = require_once 'bootstrap/app.php';
        \$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
        \$kernel->bootstrap();

        use App\Services\TaskExecutionService;
        use App\Services\CollectionService;

        \$collectionService = new CollectionService();
        \$taskExecutionService = new TaskExecutionService(\$collectionService);

        \$result = \$taskExecutionService->resetTask($task_id);

        if (\$result['success']) {
            echo '✅ ' . \$result['message'] . PHP_EOL;
        } else {
            echo '❌ ' . \$result['message'] . PHP_EOL;
        }
        "
    fi
}

# 取消正在执行的任务
cancel_task() {
    echo "⏹️ 取消正在执行的任务"
    echo "-----------------------------------------------------"
    
    # 显示正在执行的任务
    php -r "
    require 'vendor/autoload.php';
    \$app = require_once 'bootstrap/app.php';
    \$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
    \$kernel->bootstrap();

    use App\Models\CollectionTask;

    echo '正在执行的任务:' . PHP_EOL;
    \$runningTasks = CollectionTask::where('status', 1)->get();
    
    if (\$runningTasks->isEmpty()) {
        echo '  没有正在执行的任务' . PHP_EOL;
    } else {
        foreach (\$runningTasks as \$task) {
            echo '  ID: ' . \$task->id . ' | 名称: ' . \$task->name . ' | 开始时间: ' . \$task->started_at . PHP_EOL;
        }
    }
    "
    
    read -p "请输入要取消的任务ID: " task_id
    
    if [[ ! "$task_id" =~ ^[0-9]+$ ]]; then
        echo "❌ 无效的任务ID"
        return
    fi
    
    echo "取消任务 ID: $task_id"
    
    php -r "
    require 'vendor/autoload.php';
    \$app = require_once 'bootstrap/app.php';
    \$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
    \$kernel->bootstrap();

    use App\Services\TaskExecutionService;
    use App\Services\CollectionService;

    \$collectionService = new CollectionService();
    \$taskExecutionService = new TaskExecutionService(\$collectionService);

    \$result = \$taskExecutionService->cancelTask($task_id);

    if (\$result['success']) {
        echo '✅ ' . \$result['message'] . PHP_EOL;
    } else {
        echo '❌ ' . \$result['message'] . PHP_EOL;
    }
    "
}

# 清理完成的任务
cleanup_tasks() {
    echo "🧹 清理完成的任务"
    echo "-----------------------------------------------------"
    
    read -p "清理多少天前的已完成任务? (默认30天): " days
    days=${days:-30}
    
    if [[ ! "$days" =~ ^[0-9]+$ ]]; then
        echo "❌ 无效的天数"
        return
    fi
    
    echo "清理 $days 天前的已完成任务..."
    
    php -r "
    require 'vendor/autoload.php';
    \$app = require_once 'bootstrap/app.php';
    \$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
    \$kernel->bootstrap();

    use App\Models\CollectionTask;

    \$cutoffDate = now()->subDays($days);
    echo '清理截止日期: ' . \$cutoffDate . PHP_EOL;

    \$tasksToDelete = CollectionTask::whereIn('status', [2, 3])
        ->where('completed_at', '<', \$cutoffDate)
        ->get();

    echo '找到 ' . \$tasksToDelete->count() . ' 个可清理的任务' . PHP_EOL;

    foreach (\$tasksToDelete as \$task) {
        echo '删除任务: ID=' . \$task->id . ', 名称=' . \$task->name . ', 完成时间=' . \$task->completed_at . PHP_EOL;
        \$task->delete();
    }

    echo '✅ 清理完成' . PHP_EOL;
    "
}

# 系统诊断
system_diagnosis() {
    echo "🔍 系统诊断"
    echo "-----------------------------------------------------"
    
    echo "1. 检查队列配置:"
    echo "   当前队列驱动: $(grep QUEUE_CONNECTION .env | cut -d'=' -f2)"
    
    echo ""
    echo "2. 检查数据库连接:"
    php artisan db:show --database=mysql 2>/dev/null || echo "   数据库连接失败"
    
    echo ""
    echo "3. 检查任务状态一致性:"
    php -r "
    require 'vendor/autoload.php';
    \$app = require_once 'bootstrap/app.php';
    \$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
    \$kernel->bootstrap();

    use App\Models\CollectionTask;

    echo '检查任务状态一致性...' . PHP_EOL;
    \$inconsistentTasks = CollectionTask::where('status', 1)
        ->whereDoesntHave('taskDetails', function(\$query) {
            \$query->where('status', 1);
        })->count();

    if (\$inconsistentTasks > 0) {
        echo '   ❌ 发现 ' . \$inconsistentTasks . ' 个状态不一致的任务' . PHP_EOL;
    } else {
        echo '   ✅ 任务状态一致' . PHP_EOL;
    }
    "
    
    echo ""
    echo "4. 检查磁盘空间:"
    df -h | grep -E "(Filesystem|/dev/)"
    
    echo ""
    echo "5. 检查内存使用:"
    free -h
}

# 主循环
while true; do
    echo ""
    show_menu
    
    case $choice in
        1)
            view_task_status
            ;;
        2)
            execute_task
            ;;
        3)
            reset_task
            ;;
        4)
            cancel_task
            ;;
        5)
            cleanup_tasks
            ;;
        6)
            system_diagnosis
            ;;
        0)
            echo "退出任务管理脚本"
            exit 0
            ;;
        *)
            echo "❌ 无效选项，请重新选择"
            ;;
    esac
    
    echo ""
    read -p "按回车键继续..."
done