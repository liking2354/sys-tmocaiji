# 现代化分页组件使用指南

## 概述

本项目已实现了一个现代化、功能完整的分页组件，支持动态设置每页显示条数、页码跳转等功能。

## 功能特性

### 1. 每页显示条数选择
- 默认显示 10 条记录
- 支持选择：10、20、30、50 条
- 用户选择后自动刷新页面并重置到第一页

### 2. 分页导航
- **首页/末页按钮**：快速跳转到第一页或最后一页
- **上一页/下一页按钮**：逐页浏览
- **页码按钮**：直接点击跳转到指定页
- **省略号**：当页码过多时自动显示省略号

### 3. 页码跳转
- 输入框快速跳转到指定页码
- 自动验证页码有效性

### 4. 分页信息
- 显示当前显示的记录范围
- 显示总记录数
- 显示当前页码和总页数

### 5. 响应式设计
- 桌面端：完整显示所有功能
- 平板端：自适应布局
- 手机端：简化显示，优化触摸体验

### 6. 深色模式支持
- 自动适配系统深色模式
- 提供舒适的视觉体验

## 样式特点

- **现代化设计**：渐变背景、圆角、阴影等现代 UI 元素
- **交互反馈**：悬停效果、点击动画、焦点状态
- **无障碍支持**：完整的 ARIA 标签和语义化 HTML
- **性能优化**：CSS 动画使用 GPU 加速

## 在控制器中使用

### 基础用法

```php
use App\Helpers\PaginationHelper;

public function index(Request $request)
{
    // 获取每页显示条数（默认 15 条）
    $perPage = PaginationHelper::getPerPage($request, 15);
    
    // 执行分页查询
    $items = Item::paginate($perPage);
    
    // 保留查询参数（用于筛选条件）
    $items->appends(PaginationHelper::getQueryParams($request));
    
    return view('items.index', compact('items'));
}
```

### 带筛选条件的用法

```php
public function index(Request $request)
{
    $query = Item::query();
    
    // 应用筛选条件
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }
    
    if ($request->filled('category')) {
        $query->where('category', $request->category);
    }
    
    // 分页
    $perPage = PaginationHelper::getPerPage($request, 15);
    $items = $query->paginate($perPage)
        ->appends(PaginationHelper::getQueryParams($request));
    
    return view('items.index', compact('items'));
}
```

## 在视图中使用

### 基础用法

```blade
<!-- 在表格或列表下方显示分页 -->
<div class="table-responsive">
    <table class="table">
        <!-- 表格内容 -->
    </table>
</div>

<!-- 分页组件会自动显示 -->
{{ $items->links() }}
```

### 保留查询参数

```blade
<!-- 分页组件会自动保留所有查询参数 -->
{{ $items->links() }}
```

## 已应用的页面

以下页面已应用现代化分页组件：

### 服务器管理
- `/server-groups` - 服务器分组列表
- `/servers` - 服务器列表

### 采集管理
- `/collectors` - 采集组件列表
- `/collection-tasks` - 采集任务列表
- `/collection-history` - 采集历史列表

### 系统变更
- `/system-change/templates` - 配置模板列表
- `/system-change/tasks` - 变更任务列表

### 管理功能
- `/admin/users` - 用户管理
- `/admin/roles` - 角色管理
- `/admin/permissions` - 权限管理
- `/admin/operation-logs` - 操作日志

## 自定义配置

### 修改默认每页条数

在 `PaginationHelper` 中修改允许的选项：

```php
// app/Helpers/PaginationHelper.php
$allowedPerPage = [10, 20, 30, 50, 100]; // 添加 100 条选项
```

### 修改样式

编辑 CSS 文件：`public/assets/css/components/pagination.css`

主要可自定义的变量：
- 颜色：`#0066cc`（主色）、`#2c3e50`（文字色）
- 间距：`gap`、`padding`、`margin`
- 圆角：`border-radius`
- 阴影：`box-shadow`

### 修改分页视图

编辑 Blade 模板：`resources/views/pagination/modern.blade.php`

## 性能考虑

1. **数据库查询**：分页会自动添加 LIMIT 和 OFFSET，确保只查询需要的数据
2. **URL 参数**：使用 `appends()` 保留筛选参数，避免重复查询
3. **缓存**：对于大数据集，考虑使用 Redis 缓存分页结果

## 浏览器兼容性

- Chrome/Edge：完全支持
- Firefox：完全支持
- Safari：完全支持
- IE 11：基础功能支持（不支持某些 CSS 特性）

## 常见问题

### Q: 如何修改默认每页条数？
A: 在控制器中调用 `PaginationHelper::getPerPage($request, 20)` 时，第二个参数就是默认值。

### Q: 如何禁用每页条数选择？
A: 编辑 `resources/views/pagination/modern.blade.php`，注释掉 `.pagination-header` 部分。

### Q: 如何自定义分页样式？
A: 编辑 `public/assets/css/components/pagination.css` 文件。

### Q: 分页参数如何保留？
A: 使用 `PaginationHelper::getQueryParams($request)` 和 `appends()` 方法自动保留。

## 技术栈

- **前端**：HTML5、CSS3、JavaScript（原生）
- **后端**：Laravel Pagination
- **样式**：现代 CSS（Flexbox、Grid、渐变、阴影）
- **无障碍**：WAI-ARIA 标准

## 更新日志

### v1.0.0 (2024-10-31)
- 初始版本发布
- 实现现代化分页组件
- 支持动态每页条数设置
- 应用到所有主要列表页面
