# 头像URL问题修复指南

## 问题描述

在生产服务器上，头像上传成功但无法正确显示，出现 404 错误。

**问题现象：**
- 本地环境：头像正常显示
- 生产环境：头像上传成功，但浏览器无法访问图片
- 错误URL示例：`http://81.71.102.213/storage/avatars/1_1762507733.jpg` 返回 404

## 问题原因

1. **APP_URL 配置问题**
   - `.env` 文件中 `APP_URL` 可能配置为 `http://localhost`
   - 导致某些URL生成使用了错误的域名

2. **Storage 软链接问题**
   - `public/storage` 软链接可能不存在或损坏
   - Laravel 的 `storage:link` 命令未执行

3. **文件权限问题**
   - Web 服务器用户无权限访问头像目录
   - 目录权限设置不正确（需要 755 或 775）

4. **路径问题**
   - 数据库存储路径：`avatars/1_xxx.jpg`
   - 实际访问路径：`/storage/avatars/1_xxx.jpg`
   - 需要确保软链接正确映射

## 解决方案

### 方案一：使用自动修复脚本（推荐）

在服务器上执行：

```bash
cd /www/wwwroot/tmocaiji
bash scripts/maintenance/fix-avatar-url.sh
```

脚本会自动：
1. ✓ 检查并创建 storage 软链接
2. ✓ 检查并创建头像目录
3. ✓ 设置正确的文件权限
4. ✓ 检查 .env 配置
5. ✓ 清除缓存
6. ✓ 显示测试信息

### 方案二：手动修复

#### 1. 创建 Storage 软链接

```bash
cd /www/wwwroot/tmocaiji
php artisan storage:link
```

输出应该是：
```
The [public/storage] link has been connected to [storage/app/public].
```

验证软链接：
```bash
ls -la public/storage
# 应该显示：storage -> /www/wwwroot/tmocaiji/storage/app/public
```

#### 2. 创建头像目录

```bash
mkdir -p storage/app/public/avatars
```

#### 3. 设置文件权限

```bash
# 设置目录权限
chmod -R 775 storage/app/public/avatars
chmod -R 775 public/storage

# 设置所有者（www 是宝塔默认用户）
chown -R www:www storage/app/public/avatars
chown -R www:www public/storage
```

#### 4. 修改 .env 配置

编辑 `/www/wwwroot/tmocaiji/.env` 文件：

```bash
# 找到这一行
APP_URL=http://localhost

# 修改为
APP_URL=http://81.71.102.213
```

#### 5. 清除缓存

```bash
php artisan config:clear
php artisan view:clear
php artisan cache:clear
```

## 验证修复

### 1. 检查软链接

```bash
ls -la /www/wwwroot/tmocaiji/public/storage
```

应该显示：
```
storage -> /www/wwwroot/tmocaiji/storage/app/public
```

### 2. 检查头像文件

```bash
ls -la /www/wwwroot/tmocaiji/storage/app/public/avatars/
```

应该能看到上传的头像文件。

### 3. 测试访问

在浏览器中访问：
```
http://81.71.102.213/storage/avatars/1_1762507733.jpg
```

应该能正常显示图片。

### 4. 测试上传

1. 访问：`http://81.71.102.213/profile`
2. 点击"上传头像"
3. 选择一张图片
4. 上传成功后应该能立即看到预览

## 代码修改说明

### 修改的文件

1. **app/Http/Controllers/ProfileController.php**
   - 使用 `url('storage/' . $path)` 替代 `Storage::url($path)`
   - 确保生成的URL使用正确的域名

2. **resources/views/profile/index.blade.php**
   - 使用 `url('storage/' . $user->avatar)` 替代 `asset()`
   - 确保URL生成一致

3. **resources/views/layouts/app.blade.php**
   - 导航栏头像URL使用相同方式

4. **app/Models/User.php**
   - 添加 `getAvatarUrlAttribute()` 访问器
   - 统一头像URL生成逻辑

### URL生成方式对比

| 方法 | 本地 | 生产环境 | 依赖配置 |
|------|------|----------|----------|
| `asset()` | ✓ | ✓ | 无 |
| `url()` | ✓ | ✓ | APP_URL |
| `Storage::url()` | ✓ | ✗ | APP_URL + filesystems.php |

**最终选择：** 使用 `url()` 方法，因为：
- 自动使用当前请求的域名
- 不依赖 APP_URL 配置（当请求来自正确域名时）
- 兼容性最好

## 常见问题

### Q1: 软链接创建失败

**错误信息：**
```
The [public/storage] directory already exists.
```

**解决方案：**
```bash
# 删除旧的软链接或目录
rm -rf public/storage
# 重新创建
php artisan storage:link
```

### Q2: 权限被拒绝

**错误信息：**
```
Permission denied
```

**解决方案：**
```bash
# 使用 root 或 sudo 执行
sudo chown -R www:www storage/app/public/avatars
sudo chmod -R 775 storage/app/public/avatars
```

### Q3: 头像上传成功但仍显示404

**可能原因：**
1. 软链接失效
2. Nginx/Apache 配置问题
3. 文件权限问题

**检查步骤：**
```bash
# 1. 检查文件是否存在
ls -la storage/app/public/avatars/

# 2. 检查软链接
ls -la public/storage

# 3. 检查权限
ls -la storage/app/public/

# 4. 测试直接访问
curl http://81.71.102.213/storage/avatars/文件名.jpg
```

### Q4: 使用宝塔面板如何操作

1. **创建软链接：**
   - 文件 → 进入项目目录 → 终端
   - 执行 `php artisan storage:link`

2. **设置权限：**
   - 右键 `storage/app/public/avatars` 目录
   - 权限设置 → 775
   - 所有者 → www

3. **修改 .env：**
   - 文件 → 编辑 `.env`
   - 修改 `APP_URL` 为服务器地址

## Web服务器配置

### Nginx 配置（宝塔默认已配置）

确保 Nginx 配置中有：

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 测试配置

```bash
# 重启 Nginx
nginx -t && nginx -s reload
```

## 监控和日志

### 查看上传日志

```bash
tail -f storage/logs/laravel.log
```

### 查看 Nginx 错误日志

```bash
tail -f /www/wwwlogs/tmocaiji.error.log
```

## 预防措施

1. **部署检查清单：**
   - [ ] 执行 `php artisan storage:link`
   - [ ] 创建头像目录
   - [ ] 设置正确权限
   - [ ] 配置 APP_URL
   - [ ] 清除缓存

2. **自动化部署脚本：**
   ```bash
   # 使用 update-profile-feature.sh
   bash scripts/maintenance/update-profile-feature.sh
   ```

3. **定期检查：**
   - 软链接是否有效
   - 目录权限是否正确
   - 磁盘空间是否充足

## 更新记录

- 2025-11-07: 初始版本
- 修复头像URL生成问题
- 添加自动修复脚本
