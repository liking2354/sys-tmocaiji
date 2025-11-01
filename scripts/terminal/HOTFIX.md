# 紧急修复：路径问题

## 问题描述

启动脚本识别项目目录错误：
- 错误路径：`/www/wwwroot/tmocaiji/scripts`
- 正确路径：`/www/wwwroot/tmocaiji`

## 快速修复方法

### 方法 1：使用修复脚本（推荐）

```bash
cd /www/wwwroot/tmocaiji/scripts/terminal
./fix-path.sh
```

### 方法 2：手动修复

在服务器上执行以下命令：

```bash
cd /www/wwwroot/tmocaiji/scripts/terminal

# 修复 start.sh
sed -i 's|PROJECT_DIR="\$( dirname "\$SCRIPT_DIR" )"|PROJECT_DIR="\$( cd "\$SCRIPT_DIR/../.." \&\& pwd )"|g' start.sh

# 修复 stop.sh
sed -i 's|PROJECT_DIR="\$( dirname "\$SCRIPT_DIR" )"|PROJECT_DIR="\$( cd "\$SCRIPT_DIR/../.." \&\& pwd )"|g' stop.sh

# 修复 status.sh
sed -i 's|PROJECT_DIR="\$( dirname "\$SCRIPT_DIR" )"|PROJECT_DIR="\$( cd "\$SCRIPT_DIR/../.." \&\& pwd )"|g' status.sh
```

### 方法 3：直接编辑文件

编辑三个脚本文件，将：
```bash
PROJECT_DIR="$( dirname "$SCRIPT_DIR" )"
```

改为：
```bash
PROJECT_DIR="$( cd "$SCRIPT_DIR/../.." && pwd )"
```

需要修改的文件：
- `start.sh` (第 7 行)
- `stop.sh` (第 7 行)
- `status.sh` (第 7 行)

## 验证修复

修复后重新启动：

```bash
./start.sh
```

应该看到正确的项目目录：
```
项目目录: /www/wwwroot/tmocaiji
```

## 原因说明

脚本位于 `scripts/terminal/` 目录下：
- 原代码：`dirname(scripts/terminal)` = `scripts` ❌
- 修复后：`scripts/terminal/../..` = 项目根目录 ✅

## 后续

修复后请拉取最新代码：
```bash
cd /www/wwwroot/tmocaiji
git pull origin main
```
