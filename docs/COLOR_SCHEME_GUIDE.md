# 配色方案指南

## 概述

系统已更新为现代化的浅色主题配色方案，提供更好的视觉体验和可读性。

## 导航栏样式

### 新设计特点
- **背景**：浅色渐变 `#ffffff` 到 `#f8fafc`
- **品牌文字**：蓝色 `#0066cc`
- **品牌图标**：蓝色渐变
- **链接颜色**：
  - 默认：灰色 `#6b7280`
  - 悬停：蓝色 `#0066cc`
  - 活跃：蓝色 `#0066cc`
- **搜索框**：浅灰色背景，焦点时变为白色并显示蓝色边框

## 卡片头部颜色方案

系统提供了 6 种现代化的卡片头部颜色，替代了原来的深蓝色。

### 可用的卡片类型

#### 1. 浅蓝色卡片 (默认)
```html
<div class="card card-light-blue">
    <div class="card-header">
        <h5>标题</h5>
    </div>
</div>
```
- **颜色**：`#f0f7ff` (浅蓝色背景)
- **边框**：`#0066cc` (蓝色左边框)
- **文字**：`#0066cc` (蓝色)
- **用途**：主要列表、基本信息、默认卡片

#### 2. 浅紫色卡片
```html
<div class="card card-light-purple">
    <div class="card-header">
        <h5>标题</h5>
    </div>
</div>
```
- **颜色**：`#f5f3ff` (浅紫色背景)
- **边框**：`#7c3aed` (紫色左边框)
- **文字**：`#7c3aed` (紫色)
- **用途**：配置、设置、高级功能

#### 3. 浅绿色卡片
```html
<div class="card card-light-green">
    <div class="card-header">
        <h5>标题</h5>
    </div>
</div>
```
- **颜色**：`#f0fdf4` (浅绿色背景)
- **边框**：`#10b981` (绿色左边框)
- **文字**：`#10b981` (绿色)
- **用途**：成功、完成、健康状态

#### 4. 浅橙色卡片
```html
<div class="card card-light-orange">
    <div class="card-header">
        <h5>标题</h5>
    </div>
</div>
```
- **颜色**：`#fffbf0` (浅橙色背景)
- **边框**：`#f59e0b` (橙色左边框)
- **文字**：`#f59e0b` (橙色)
- **用途**：警告、注意、待处理

#### 5. 浅粉色卡片
```html
<div class="card card-light-pink">
    <div class="card-header">
        <h5>标题</h5>
    </div>
</div>
```
- **颜色**：`#fdf2f8` (浅粉色背景)
- **边框**：`#ec4899` (粉色左边框)
- **文字**：`#ec4899` (粉色)
- **用途**：特殊、重要、标记

#### 6. 浅青色卡片
```html
<div class="card card-light-cyan">
    <div class="card-header">
        <h5>标题</h5>
    </div>
</div>
```
- **颜色**：`#ecfdf5` (浅青色背景)
- **边框**：`#06b6d4` (青色左边框)
- **文字**：`#06b6d4` (青色)
- **用途**：信息、提示、辅助功能

## 使用示例

### 服务器列表卡片
```html
<div class="card card-light-blue shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list"></i> 服务器列表
        </h5>
    </div>
    <div class="card-body">
        <!-- 内容 -->
    </div>
</div>
```

### 配置模板卡片
```html
<div class="card card-light-purple shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-file-code"></i> 配置模板
        </h5>
    </div>
    <div class="card-body">
        <!-- 内容 -->
    </div>
</div>
```

### 采集任务卡片
```html
<div class="card card-light-green shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-tasks"></i> 采集任务
        </h5>
    </div>
    <div class="card-body">
        <!-- 内容 -->
    </div>
</div>
```

## 颜色变量

所有颜色都定义在 `/public/assets/css/common/variables.css` 中：

```css
/* 卡片头部颜色 */
--card-header-blue: #f0f7ff;
--card-header-purple: #f5f3ff;
--card-header-green: #f0fdf4;
--card-header-orange: #fffbf0;
--card-header-pink: #fdf2f8;
--card-header-cyan: #ecfdf5;
```

## 迁移指南

### 从旧样式迁移
**旧样式：**
```html
<div class="card card-primary">
    <div class="card-header bg-primary text-white">
        <h5>标题</h5>
    </div>
</div>
```

**新样式：**
```html
<div class="card card-light-blue">
    <div class="card-header">
        <h5>标题</h5>
    </div>
</div>
```

## 优势

✅ **更现代**：浅色主题更符合现代设计趋势  
✅ **更清晰**：浅色背景提高了文字可读性  
✅ **更灵活**：6 种颜色可用于不同场景  
✅ **更舒适**：减少了深色对眼睛的刺激  
✅ **更专业**：整体视觉效果更加专业和统一  

## 相关文件

- 样式定义：`/public/assets/css/components/cards.css`
- 颜色变量：`/public/assets/css/common/variables.css`
- 导航栏样式：`/public/assets/css/common/navbar-modern.css`
