#!/bin/bash

# 修复脚本路径问题
# 使用方法: ./fix-path.sh

echo "正在修复脚本路径问题..."

# 获取当前脚本所在目录
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# 修复 start.sh
echo "修复 start.sh..."
sed -i.bak 's|PROJECT_DIR="\$( dirname "\$SCRIPT_DIR" )"|PROJECT_DIR="\$( cd "\$SCRIPT_DIR/../.." \&\& pwd )"|g' "$SCRIPT_DIR/start.sh"

# 修复 stop.sh
echo "修复 stop.sh..."
sed -i.bak 's|PROJECT_DIR="\$( dirname "\$SCRIPT_DIR" )"|PROJECT_DIR="\$( cd "\$SCRIPT_DIR/../.." \&\& pwd )"|g' "$SCRIPT_DIR/stop.sh"

# 修复 status.sh
echo "修复 status.sh..."
sed -i.bak 's|PROJECT_DIR="\$( dirname "\$SCRIPT_DIR" )"|PROJECT_DIR="\$( cd "\$SCRIPT_DIR/../.." \&\& pwd )"|g' "$SCRIPT_DIR/status.sh"

echo "✓ 修复完成！"
echo ""
echo "备份文件已保存为 *.bak"
echo "现在可以重新运行: ./start.sh"
