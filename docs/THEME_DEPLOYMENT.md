# 主题自定义功能部署指南

## 快速部署步骤

### 1. 运行数据库迁移

```bash
php artisan migrate
```

这将在 `users` 表中添加两个新列：
- `theme_color` - 用户选择的主题颜色（默认：blue）
- `sidebar_style` - 用户选择的侧边栏风格（默认：light）

### 2. 清除缓存

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### 3. 验证部署

访问以下 URL 验证功能是否正常工作：

- 设置页面：`http://your-domain/settings`
- API 端点：`http://your-domain/api/theme-config`

## 文件清单

以下是新增或修改的文件：

### 新增文件

1. **后端**
   - `app/Http/Controllers/SettingController.php` - 设置控制器
   - `database/migrations/2024_10_31_000000_add_theme_preference_to_users.php` - 数据库迁移

2. **前端**
   - `resources/views/settings/index.blade.php` - 设置页面视图
   - `public/assets/js/common/theme-switcher.js` - 主题切换脚本
   - `public/assets/css/common/theme-colors.css` - 主题颜色样式

3. **文档**
   - `docs/THEME_CUSTOMIZATION_GUIDE.md` - 主题自定义指南
   - `docs/THEME_DEPLOYMENT.md` - 部署指南

### 修改文件

1. **模型**
   - `app/Models/User.php` - 添加 `theme_color` 和 `sidebar_style` 字段到 `$fillable`

2. **路由**
   - `routes/web.php` - 添加设置路由

3. **视图**
   - `resources/views/layouts/app.blade.php` - 添加主题配置元标签和脚本引入

4. **样式**
   - `public/assets/css/main.css` - 导入主题颜色样式

## 功能验证清单

部署后，请按照以下步骤验证功能：

- [ ] 用户可以访问设置页面
- [ ] 用户可以选择不同的主题颜色
- [ ] 用户可以选择侧边栏风格
- [ ] 用户可以保存设置
- [ ] 页面刷新后主题仍然保持
- [ ] 不同用户有不同的主题设置
- [ ] 主题预览正确显示
- [ ] API 端点返回正确的主题配置

## 故障排除

### 问题：迁移失败

**解决方案：**
```bash
# 检查迁移状态
php artisan migrate:status

# 如果需要回滚
php artisan migrate:rollback

# 重新运行迁移
php artisan migrate
```

### 问题：设置页面 404

**解决方案：**
1. 确保路由已正确添加到 `routes/web.php`
2. 清除路由缓存：`php artisan route:clear`
3. 检查 SettingController 是否存在

### 问题：主题不切换

**解决方案：**
1. 检查浏览器控制台是否有 JavaScript 错误
2. 确保 `theme-switcher.js` 已正确加载
3. 检查 API 端点是否返回正确的数据
4. 清除浏览器缓存

### 问题：设置无法保存

**解决方案：**
1. 检查数据库迁移是否成功
2. 检查 User 模型的 `$fillable` 是否包含新字段
3. 查看服务器日志获取详细错误信息

## 性能优化建议

1. **缓存主题配置**
   ```php
   // 在 SettingController 中
   $config = Cache::remember('user_theme_' . Auth::id(), 3600, function() {
       // 获取主题配置
   });
   ```

2. **预加载主题**
   ```javascript
   // 在 theme-switcher.js 中
   // 使用 localStorage 缓存主题，减少 API 调用
   ```

3. **压缩 CSS**
   ```bash
   # 生产环境中压缩 CSS
   npm run production
   ```

## 回滚步骤

如果需要回滚此功能：

```bash
# 回滚迁移
php artisan migrate:rollback

# 删除新增文件
rm app/Http/Controllers/SettingController.php
rm resources/views/settings/index.blade.php
rm public/assets/js/common/theme-switcher.js
rm public/assets/css/common/theme-colors.css

# 恢复修改的文件
git checkout app/Models/User.php
git checkout routes/web.php
git checkout resources/views/layouts/app.blade.php
git checkout public/assets/css/main.css

# 清除缓存
php artisan cache:clear
```

## 支持的浏览器

- Chrome 49+
- Firefox 31+
- Safari 9.1+
- Edge 15+
- Opera 36+

## 相关命令

```bash
# 查看迁移状态
php artisan migrate:status

# 运行特定迁移
php artisan migrate --path=database/migrations/2024_10_31_000000_add_theme_preference_to_users.php

# 清除所有缓存
php artisan cache:clear && php artisan config:clear && php artisan view:clear

# 生成应用密钥（如果需要）
php artisan key:generate

# 运行测试
php artisan test
```

## 下一步

1. 测试所有主题颜色
2. 收集用户反馈
3. 考虑添加更多主题选项
4. 实现侧边栏深色主题
5. 添加主题预设功能
