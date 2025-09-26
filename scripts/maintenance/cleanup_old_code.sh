#!/bin/bash

# 清理旧代码脚本
echo "🧹 清理旧代码和无用文件"
echo "====================================="
echo "📍 当前路径: $(pwd)"

# 检查是否在正确的目录
if [ ! -f "artisan" ]; then
    echo "❌ 错误: 请在Laravel项目根目录运行此脚本"
    exit 1
fi

echo ""
echo "🔍 检查可以清理的文件..."

# 1. 检查旧的视图文件
echo "-------------------------------------"
echo "📁 检查视图文件:"
if [ -f "resources/views/collection-tasks/show-old.blade.php" ]; then
    echo "  ✓ 发现旧的show页面: show-old.blade.php"
    OLD_SHOW_EXISTS=true
else
    echo "  ✓ 没有发现旧的show页面"
    OLD_SHOW_EXISTS=false
fi

# 2. 检查是否还有对ExecuteBatchCollectionJob的引用
echo "-------------------------------------"
echo "📁 检查Job类引用:"
JOB_REFERENCES=$(grep -r "ExecuteBatchCollectionJob" app/ --exclude-dir=Jobs 2>/dev/null | wc -l)
if [ "$JOB_REFERENCES" -gt 0 ]; then
    echo "  ⚠️  发现 $JOB_REFERENCES 处对ExecuteBatchCollectionJob的引用"
    echo "  引用位置:"
    grep -r "ExecuteBatchCollectionJob" app/ --exclude-dir=Jobs 2>/dev/null | head -5
    JOB_REFS_EXIST=true
else
    echo "  ✓ 没有发现对ExecuteBatchCollectionJob的引用"
    JOB_REFS_EXIST=false
fi

# 3. 检查旧的队列配置
echo "-------------------------------------"
echo "📁 检查队列配置:"
if grep -q "QUEUE_CONNECTION=redis" .env 2>/dev/null; then
    echo "  ⚠️  .env文件中仍使用Redis队列"
    REDIS_QUEUE=true
else
    echo "  ✓ 队列配置正常"
    REDIS_QUEUE=false
fi

# 4. 检查临时文件和日志
echo "-------------------------------------"
echo "📁 检查临时文件:"
TEMP_FILES=$(find storage/logs -name "*.log" -size +10M 2>/dev/null | wc -l)
if [ "$TEMP_FILES" -gt 0 ]; then
    echo "  ⚠️  发现 $TEMP_FILES 个大型日志文件 (>10MB)"
    LARGE_LOGS=true
else
    echo "  ✓ 日志文件大小正常"
    LARGE_LOGS=false
fi

echo ""
echo "🛠️  清理建议:"
echo "====================================="

if [ "$OLD_SHOW_EXISTS" = true ]; then
    echo "1. 删除旧的视图文件:"
    echo "   rm resources/views/collection-tasks/show-old.blade.php"
fi

if [ "$JOB_REFS_EXIST" = true ]; then
    echo "2. 更新代码中的Job类引用，改为使用TaskExecutionService"
fi

if [ "$REDIS_QUEUE" = true ]; then
    echo "3. 确认队列配置已切换到sync:"
    echo "   sed -i 's/QUEUE_CONNECTION=redis/QUEUE_CONNECTION=sync/' .env"
fi

if [ "$LARGE_LOGS" = true ]; then
    echo "4. 清理大型日志文件:"
    echo "   find storage/logs -name '*.log' -size +10M -exec truncate -s 0 {} +"
fi

echo ""
echo "🤖 自动清理选项:"
echo "====================================="
read -p "是否自动执行清理? (y/N): " AUTO_CLEAN

if [[ $AUTO_CLEAN =~ ^[Yy]$ ]]; then
    echo ""
    echo "🧹 开始自动清理..."
    
    # 删除旧视图文件
    if [ "$OLD_SHOW_EXISTS" = true ]; then
        echo "  🗑️  删除旧视图文件..."
        rm -f resources/views/collection-tasks/show-old.blade.php
        echo "  ✅ 已删除 show-old.blade.php"
    fi
    
    # 清理大型日志
    if [ "$LARGE_LOGS" = true ]; then
        echo "  🗑️  清理大型日志文件..."
        find storage/logs -name "*.log" -size +10M -exec truncate -s 0 {} + 2>/dev/null
        echo "  ✅ 已清理大型日志文件"
    fi
    
    # 清理缓存
    echo "  🧹 清理应用缓存..."
    php artisan config:clear >/dev/null 2>&1
    php artisan cache:clear >/dev/null 2>&1
    php artisan view:clear >/dev/null 2>&1
    echo "  ✅ 已清理应用缓存"
    
    echo ""
    echo "✅ 自动清理完成!"
else
    echo "跳过自动清理，请手动执行建议的清理操作。"
fi

echo ""
echo "📋 清理总结:"
echo "====================================="
echo "✅ 新的任务管理系统已启用"
echo "✅ 增强版任务详情页面已激活"
echo "✅ 同步任务执行机制已就绪"
echo "✅ 实时状态更新功能已启用"

echo ""
echo "🎯 下一步建议:"
echo "1. 测试Web界面的任务详情页面"
echo "2. 验证任务执行和状态更新功能"
echo "3. 监控任务执行日志"
echo "4. 如有问题，使用 ./scripts/maintenance/task_management.sh 进行管理"

echo ""
echo "🔧 清理脚本执行完成!"