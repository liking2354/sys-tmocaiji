# 项目维护脚本

本目录包含用于项目维护和管理的脚本。

## 📁 脚本列表

- `update.sh` - 项目更新脚本
- `set-permissions.sh` - 文件权限设置脚本

## 🚀 使用方法

### 1. 项目更新脚本 (update.sh)

自动更新项目代码、依赖和配置。

```bash
# 基本使用
./scripts/maintenance/update.sh

# 指定项目路径
./scripts/maintenance/update.sh /www/wwwroot/your-project
```

**功能特性：**
- ✅ 自动备份当前状态
- ✅ 拉取最新代码
- ✅ 更新 Composer 依赖
- ✅ 清除缓存
- ✅ 执行数据库迁移（可选）
- ✅ 设置文件权限
- ✅ 重启队列进程

**执行流程：**
1. 检查 Git 仓库状态
2. 备份未提交的更改
3. 拉取远程更新
4. 更新 Composer 依赖
5. 清除 Laravel 缓存
6. 执行数据库迁移（可选）
7. 设置文件权限
8. 重启队列进程

### 2. 权限设置脚本 (set-permissions.sh)

设置项目文件和目录的正确权限。

```bash
# 基本使用
./scripts/maintenance/set-permissions.sh

# 指定项目路径
./scripts/maintenance/set-permissions.sh /www/wwwroot/your-project
```

**权限设置：**
- 项目根目录：`755`
- storage 目录：`777`
- bootstrap/cache 目录：`777`
- 目录所有者：自动检测（www/www-data/nginx/apache）

**适用场景：**
- 新部署项目后设置权限
- 权限错误导致的问题修复
- 更新后重新设置权限
- 迁移服务器后调整权限

## ⚙️ 权限说明

### 标准权限配置

```bash
# 项目根目录
chmod -R 755 .

# storage 目录（需要写入权限）
chmod -R 777 storage

# bootstrap/cache 目录（需要写入权限）
chmod -R 777 bootstrap/cache

# 设置所有者（宝塔面板）
chown -R www:www .
```

### 权限含义

| 权限 | 数字 | 说明 |
|------|------|------|
| `755` | rwxr-xr-x | 所有者可读写执行，其他用户可读执行 |
| `777` | rwxrwxrwx | 所有用户可读写执行 |

### Web 服务器用户

脚本会自动检测以下用户：
- `www` - 宝塔面板默认用户
- `www-data` - Ubuntu/Debian 默认用户
- `nginx` - Nginx 默认用户
- `apache` - Apache 默认用户

## 📝 使用示例

### 场景 1：首次部署后设置权限

```bash
cd /www/wwwroot/your-project
./scripts/maintenance/set-permissions.sh
```

### 场景 2：更新项目

```bash
cd /www/wwwroot/your-project
./scripts/maintenance/update.sh
```

### 场景 3：修复权限问题

```bash
# 如果遇到权限错误
./scripts/maintenance/set-permissions.sh

# 或手动执行
chmod -R 755 .
chmod -R 777 storage
chmod -R 777 bootstrap/cache
chown -R www:www .
```

### 场景 4：宝塔面板环境

```bash
# 进入项目目录
cd /www/wwwroot/程序主目录

# 设置权限
./scripts/maintenance/set-permissions.sh

# 或手动执行
chmod -R 755 .
chmod -R 777 storage
chmod -R 777 bootstrap/cache
chown -R www:www .
```

## ⚠️ 注意事项

### 1. 备份重要文件

更新前会自动备份，但建议手动备份重要文件：
```bash
cp .env .env.backup
tar -czf backup-$(date +%Y%m%d).tar.gz storage/
```

### 2. 权限安全

- `777` 权限允许所有用户读写，仅用于需要写入的目录
- 生产环境建议使用更严格的权限配置
- 定期检查文件权限，避免安全风险

### 3. 所有者设置

- 确保 Web 服务器用户有权限访问项目文件
- 使用 `sudo` 命令需要管理员权限
- 宝塔面板默认使用 `www` 用户

### 4. 数据库迁移

- 更新脚本会询问是否执行数据库迁移
- 建议先在测试环境验证迁移
- 生产环境迁移前务必备份数据库

## 🔧 故障排查

### 权限错误

```bash
# 错误：Permission denied
# 解决：重新设置权限
./scripts/maintenance/set-permissions.sh
```

### 所有者错误

```bash
# 错误：无法写入 storage 目录
# 解决：检查并设置所有者
sudo chown -R www:www .
```

### 脚本无执行权限

```bash
# 错误：Permission denied
# 解决：添加执行权限
chmod +x scripts/maintenance/*.sh
```

### 更新失败

```bash
# 1. 检查 Git 状态
git status

# 2. 查看错误日志
git pull origin main

# 3. 手动解决冲突
git stash
git pull origin main
git stash pop
```

## 📚 相关文档

- [部署检查清单](../../DEPLOYMENT_CHECKLIST.md)
- [快速开始指南](../../QUICK_START.md)

## 🔍 验证权限

### 查看目录权限

```bash
# Linux
ls -la storage/
ls -la bootstrap/cache/

# 查看详细权限
stat storage/
```

### 查看所有者

```bash
# Linux
ls -l | grep storage
ls -l | grep bootstrap

# 详细信息
stat -c '%U:%G' storage/
```

## 💡 最佳实践

1. **定期更新**：建议每周检查并更新项目
2. **备份优先**：更新前确保有完整备份
3. **测试环境**：先在测试环境验证更新
4. **权限检查**：更新后检查文件权限
5. **日志监控**：关注 Laravel 日志文件

## 📞 技术支持

如有问题，请检查：
- Laravel 日志：`storage/logs/laravel.log`
- Web 服务器日志：`/www/wwwlogs/` (宝塔面板)
- 权限设置：`ls -la storage/`
