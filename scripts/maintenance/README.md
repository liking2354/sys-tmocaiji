# 维护脚本使用说明

## 脚本列表

### 1. update.sh - 完整更新脚本
功能：完整的项目更新流程，包含备份、权限修复等
```bash
# 使用默认路径更新
./scripts/maintenance/update.sh

# 指定项目路径更新
./scripts/maintenance/update.sh /path/to/project
```

### 2. quick_update.sh - 快速更新脚本
功能：生产环境快速更新，专门针对 `/www/wwwroot/tmocaiji` 路径
```bash
# 生产环境快速更新
./scripts/maintenance/quick_update.sh

# 指定其他路径
./scripts/maintenance/quick_update.sh /path/to/project
```

### 3. fix_permissions.sh - 权限修复脚本
功能：专门修复Laravel项目的文件权限问题
```bash
# 修复当前项目权限
./scripts/maintenance/fix_permissions.sh

# 修复指定项目权限
./scripts/maintenance/fix_permissions.sh /path/to/project
```

### 4. quick_fix_logs.sh - 快速日志权限修复
功能：快速修复日志文件权限问题，解决 "Permission denied" 错误
```bash
# 修复生产环境日志权限
./scripts/maintenance/quick_fix_logs.sh

# 修复指定路径日志权限
./scripts/maintenance/quick_fix_logs.sh /path/to/project
```

## 权限问题解决方案

### 问题描述
更新后出现日志权限错误：
```
The stream or file "/www/wwwroot/tmocaiji/storage/logs/laravel.log" could not be opened in append mode: Failed to open stream: Permission denied
```

### 解决方案

#### 方案1：使用快速修复脚本（推荐）
```bash
sudo ./scripts/maintenance/quick_fix_logs.sh
```

#### 方案2：手动修复
```bash
cd /www/wwwroot/tmocaiji
sudo mkdir -p storage/logs
sudo touch storage/logs/laravel.log
sudo chmod 775 storage/logs
sudo chmod 664 storage/logs/laravel.log
sudo chown -R www-data:www-data storage/logs
```

#### 方案3：使用完整权限修复脚本
```bash
sudo ./scripts/maintenance/fix_permissions.sh /www/wwwroot/tmocaiji
```

## 自动化部署建议

### 生产环境更新流程
1. 备份当前版本
2. 拉取最新代码
3. 更新依赖
4. 清除缓存
5. **修复权限（重要）**
6. 重启服务

### 推荐的更新命令
```bash
# 一键更新（包含权限修复）
sudo ./scripts/maintenance/quick_update.sh

# 或者分步执行
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan cache:clear && php artisan config:cache
sudo ./scripts/maintenance/quick_fix_logs.sh
```

## 注意事项

1. **权限问题**：所有涉及文件权限的操作都需要 sudo 权限
2. **Web服务器用户**：脚本会自动检测 www-data、nginx、apache 用户
3. **备份**：update.sh 会自动创建备份，quick_update.sh 不会
4. **生产环境**：建议使用 quick_update.sh 进行快速更新
5. **权限验证**：更新后建议访问应用检查日志功能是否正常

## 故障排除

### 如果权限修复后仍有问题
1. 检查SELinux设置
2. 检查Web服务器配置
3. 重启Web服务器
4. 检查磁盘空间

### 常用检查命令
```bash
# 检查文件权限
ls -la storage/logs/

# 检查文件所有者
ls -la storage/logs/laravel.log

# 测试日志写入
php artisan tinker --execute="Log::info('Test log entry');"