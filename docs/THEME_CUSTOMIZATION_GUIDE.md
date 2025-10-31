# 主题自定义指南

## 概述

系统现已支持用户自定义主题颜色功能。用户可以根据个人偏好选择不同的主题颜色，系统会自动应用到整个界面。

## 功能特性

### 支持的主题颜色

系统提供了 6 种现代化的主题颜色供用户选择：

1. **蓝色** (默认)
   - 主色：`#0066cc`
   - 描述：专业、可信、默认主题
   - 适用场景：企业应用、正式场合

2. **紫色**
   - 主色：`#7c3aed`
   - 描述：创意、高级、优雅
   - 适用场景：创意团队、高端应用

3. **绿色**
   - 主色：`#10b981`
   - 描述：健康、成功、生机
   - 适用场景：成功提示、健康相关

4. **橙色**
   - 主色：`#f59e0b`
   - 描述：温暖、活力、注意
   - 适用场景：警告提示、重要信息

5. **粉色**
   - 主色：`#ec4899`
   - 描述：温柔、特殊、突出
   - 适用场景：特殊标记、重点突出

6. **青色**
   - 主色：`#06b6d4`
   - 描述：清爽、现代、信息
   - 适用场景：信息提示、现代设计

### 侧边栏风格

用户还可以选择侧边栏的风格：

- **浅色**：浅色背景，深色文字（默认）
- **深色**：深色背景，浅色文字（开发中）

## 使用方法

### 访问设置页面

1. 点击导航栏右上角的用户菜单
2. 选择"设置"选项
3. 进入用户设置页面

### 选择主题颜色

1. 在"主题颜色"部分，选择您喜欢的颜色
2. 实时预览会显示选择的主题效果
3. 点击"保存设置"按钮保存您的选择

### 选择侧边栏风格

1. 在"侧边栏风格"部分，选择您喜欢的风格
2. 点击"保存设置"按钮保存您的选择

## 技术实现

### 数据库

用户的主题偏好存储在 `users` 表中：

```sql
ALTER TABLE users ADD COLUMN theme_color VARCHAR(50) DEFAULT 'blue';
ALTER TABLE users ADD COLUMN sidebar_style VARCHAR(50) DEFAULT 'light';
```

### 后端

**SettingController** (`app/Http/Controllers/SettingController.php`)

- `index()` - 显示设置页面
- `update()` - 更新用户设置
- `getThemeConfig()` - 获取用户的主题配置 (API)

### 前端

**主题切换脚本** (`public/assets/js/common/theme-switcher.js`)

- 页面加载时自动加载用户的主题偏好
- 动态应用 CSS 变量
- 支持实时切换主题

**主题颜色样式** (`public/assets/css/common/theme-colors.css`)

- 定义各个主题的颜色变量
- 定义主题相关的 CSS 类

### 路由

```php
// 用户设置
Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
Route::get('api/theme-config', [SettingController::class, 'getThemeConfig'])->name('api.theme-config');
```

## 主题应用范围

选择的主题颜色会应用到以下元素：

- ✅ 导航栏
- ✅ 侧边栏活跃链接
- ✅ 卡片头部
- ✅ 按钮
- ✅ 链接
- ✅ 表单控件焦点
- ✅ 徽章
- ✅ 进度条
- ✅ 分页
- ✅ 标签页
- ✅ 警告框

## CSS 变量

系统使用 CSS 变量来实现主题切换，主要变量包括：

```css
--primary-color: 主色
--primary-dark: 深色
--primary-light: 浅色
--card-header-bg: 卡片头部背景色
```

## 扩展主题

### 添加新主题

1. 在 `SettingController` 的 `THEME_CONFIG` 中添加新主题：

```php
'newTheme' => [
    'name' => '新主题',
    'primary' => '#新颜色',
    'light' => '#浅色',
    'description' => '描述'
],
```

2. 在 `theme-switcher.js` 中添加新主题配置：

```javascript
newTheme: {
    primary: '#新颜色',
    primaryDark: '#深色',
    primaryLight: '#浅色',
    cardHeaderBg: '#卡片头部背景',
    navbarBg: 'linear-gradient(...)',
    navbarBrand: '#品牌颜色',
},
```

3. 在 `theme-colors.css` 中添加新主题的样式：

```css
.card-header.theme-newTheme {
    background-color: #新颜色;
    color: #文字颜色;
    border-bottom-color: #边框颜色;
}
```

4. 在设置页面的视图中添加新主题选项

5. 更新数据库验证规则

## 迁移步骤

如果您是从旧版本升级，需要执行以下步骤：

1. 运行迁移：
```bash
php artisan migrate
```

2. 清除缓存：
```bash
php artisan cache:clear
php artisan config:clear
```

3. 访问设置页面，选择您喜欢的主题

## 常见问题

### Q: 主题设置会保存多久？
A: 主题设置会永久保存在数据库中，直到您手动更改。

### Q: 可以为不同的用户设置不同的主题吗？
A: 是的，每个用户都有独立的主题设置。

### Q: 如何重置为默认主题？
A: 在设置页面选择"蓝色"主题，然后保存即可。

### Q: 主题切换是否需要刷新页面？
A: 不需要，主题会立即应用。

### Q: 可以自定义主题颜色吗？
A: 目前不支持完全自定义，但可以通过修改源代码来添加新主题。

## 相关文件

- 控制器：`app/Http/Controllers/SettingController.php`
- 视图：`resources/views/settings/index.blade.php`
- 脚本：`public/assets/js/common/theme-switcher.js`
- 样式：`public/assets/css/common/theme-colors.css`
- 迁移：`database/migrations/2024_10_31_000000_add_theme_preference_to_users.php`
- 模型：`app/Models/User.php`
- 路由：`routes/web.php`
