#!/bin/bash

# 队列任务诊断脚本
echo "====================================================="
echo "队列任务诊断脚本"
echo "====================================================="

PROJECT_PATH="/www/wwwroot/tmocaiji"
echo "项目路径: $PROJECT_PATH"

if [ ! -d "$PROJECT_PATH" ]; then
    echo "❌ 项目路径不存在: $PROJECT_PATH"
    exit 1
fi

cd "$PROJECT_PATH"

echo "====================================================="
echo "1. 检查队列配置"
echo "当前队列驱动:"
grep "QUEUE_CONNECTION" .env

echo ""
echo "2. 检查Redis连接"
php artisan tinker --execute="
try {
    \$redis = app('redis');
    \$redis->ping();
    echo '✓ Redis连接正常' . PHP_EOL;
} catch (Exception \$e) {
    echo '❌ Redis连接失败: ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""
echo "3. 检查队列中的任务"
php artisan queue:size

echo ""
echo "4. 检查失败的队列任务"
php artisan queue:failed

echo ""
echo "5. 检查任务状态统计"
php artisan tinker --execute="
\$tasks = \App\Models\CollectionTask::selectRaw('
    status,
    COUNT(*) as count,
    MAX(updated_at) as last_updated
')->groupBy('status')->get();

echo '任务状态统计:' . PHP_EOL;
\$statusMap = [0 => '未开始', 1 => '进行中', 2 => '已完成', 3 => '失败'];
foreach (\$tasks as \$task) {
    echo '  ' . (\$statusMap[\$task->status] ?? '未知') . ': ' . \$task->count . ' 个 (最后更新: ' . \$task->last_updated . ')' . PHP_EOL;
}
"

echo ""
echo "6. 检查任务详情状态"
php artisan tinker --execute="
\$details = \App\Models\TaskDetail::selectRaw('
    status,
    COUNT(*) as count,
    MAX(updated_at) as last_updated
')->groupBy('status')->get();

echo '任务详情状态统计:' . PHP_EOL;
\$statusMap = [0 => '未开始', 1 => '进行中', 2 => '已完成', 3 => '失败'];
foreach (\$details as \$detail) {
    echo '  ' . (\$statusMap[\$detail->status] ?? '未知') . ': ' . \$detail->count . ' 个 (最后更新: ' . \$detail->last_updated . ')' . PHP_EOL;
}
"

echo ""
echo "7. 检查最近的采集历史"
php artisan tinker --execute="
\$histories = \App\Models\CollectionHistory::orderBy('created_at', 'desc')->limit(5)->get();
echo '最近5条采集历史:' . PHP_EOL;
foreach (\$histories as \$history) {
    echo '  ID: ' . \$history->id . ' | 状态: ' . (\$history->status == 2 ? '成功' : '失败') . ' | 时间: ' . \$history->created_at . PHP_EOL;
}
"

echo ""
echo "8. 手动处理一个队列任务"
echo "尝试手动处理队列任务..."
timeout 30 php artisan queue:work --once --timeout=25 || echo "队列处理超时或无任务"

echo ""
echo "9. 建议的解决方案"
echo "如果发现问题，可以尝试以下解决方案："
echo "1. 启动队列处理器: nohup php artisan queue:work --daemon > /dev/null 2>&1 &"
echo "2. 重置失败任务: php artisan queue:retry all"
echo "3. 清空队列: php artisan queue:clear"
echo "4. 切换到同步队列: 在.env中设置 QUEUE_CONNECTION=sync"
echo "5. 手动重置卡住的任务: php artisan tasks:reset-stuck --hours=1"

echo ""
echo "诊断完成!"