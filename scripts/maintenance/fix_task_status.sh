#!/bin/bash

# 任务状态修复脚本
echo "====================================================="
echo "任务状态修复脚本"
echo "====================================================="

PROJECT_PATH="/www/wwwroot/tmocaiji"
echo "项目路径: $PROJECT_PATH"

if [ ! -d "$PROJECT_PATH" ]; then
    echo "❌ 项目路径不存在: $PROJECT_PATH"
    exit 1
fi

cd "$PROJECT_PATH"

echo ""
echo "1. 首先检查需要修复的任务 (DRY RUN)"
echo "====================================================="
php artisan tasks:fix-status --dry-run

echo ""
echo "2. 检查队列状态"
echo "====================================================="
echo "当前队列驱动: $(grep QUEUE_CONNECTION .env)"
echo "队列大小:"
php artisan queue:size

echo ""
echo "3. 检查Redis连接"
php artisan tinker --execute="
try {
    \$redis = app('redis');
    \$redis->ping();
    echo '✓ Redis连接正常' . PHP_EOL;
} catch (Exception \$e) {
    echo '❌ Redis连接失败: ' . \$e->getMessage() . PHP_EOL;
    echo '建议切换到同步队列: QUEUE_CONNECTION=sync' . PHP_EOL;
}
"

echo ""
echo "4. 提供解决方案选择"
echo "====================================================="
echo "请选择解决方案:"
echo "1) 修复任务状态不一致问题"
echo "2) 启动队列处理器 (后台运行)"
echo "3) 切换到同步队列 (不使用Redis)"
echo "4) 清空所有队列任务"
echo "5) 重置所有卡住的任务"
echo "6) 执行完整修复 (推荐)"
echo "0) 退出"

read -p "请输入选择 (0-6): " choice

case $choice in
    1)
        echo "正在修复任务状态..."
        php artisan tasks:fix-status
        ;;
    2)
        echo "启动队列处理器..."
        nohup php artisan queue:work --daemon --timeout=300 > storage/logs/queue.log 2>&1 &
        echo "队列处理器已在后台启动，日志文件: storage/logs/queue.log"
        echo "可以使用 ps aux | grep 'queue:work' 检查进程状态"
        ;;
    3)
        echo "切换到同步队列..."
        sed -i 's/QUEUE_CONNECTION=redis/QUEUE_CONNECTION=sync/' .env
        echo "已切换到同步队列，任务将立即执行"
        php artisan config:clear
        ;;
    4)
        echo "清空队列任务..."
        php artisan queue:clear
        echo "队列已清空"
        ;;
    5)
        echo "重置卡住的任务..."
        php artisan tasks:reset-stuck --hours=1
        ;;
    6)
        echo "执行完整修复..."
        echo ""
        echo "步骤1: 修复任务状态"
        php artisan tasks:fix-status
        
        echo ""
        echo "步骤2: 重置卡住的任务"
        php artisan tasks:reset-stuck --hours=1
        
        echo ""
        echo "步骤3: 检查队列连接"
        php artisan tinker --execute="
        try {
            \$redis = app('redis');
            \$redis->ping();
            echo '✓ Redis连接正常，启动队列处理器...' . PHP_EOL;
            exec('nohup php artisan queue:work --daemon --timeout=300 > storage/logs/queue.log 2>&1 &');
            echo '✓ 队列处理器已启动' . PHP_EOL;
        } catch (Exception \$e) {
            echo '❌ Redis连接失败，切换到同步队列...' . PHP_EOL;
            file_put_contents('.env', str_replace('QUEUE_CONNECTION=redis', 'QUEUE_CONNECTION=sync', file_get_contents('.env')));
            echo '✓ 已切换到同步队列' . PHP_EOL;
        }
        "
        
        echo ""
        echo "步骤4: 清理配置缓存"
        php artisan config:clear
        php artisan cache:clear
        
        echo ""
        echo "✅ 完整修复完成!"
        echo "建议运行诊断脚本验证修复结果: ./scripts/maintenance/diagnose_tasks.sh"
        ;;
    0)
        echo "退出"
        exit 0
        ;;
    *)
        echo "无效选择"
        exit 1
        ;;
esac

echo ""
echo "修复完成! 建议运行诊断脚本验证结果:"
echo "./scripts/maintenance/diagnose_tasks.sh"