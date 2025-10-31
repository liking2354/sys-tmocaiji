# 第二阶段：模块化脚本提取 - 完整指南

## 📋 目录

1. [概述](#概述)
2. [已完成工作](#已完成工作)
3. [技术架构](#技术架构)
4. [快速开始](#快速开始)
5. [常见问题](#常见问题)
6. [下一步](#下一步)

---

## 概述

第二阶段的目标是将 Blade 模板中的内联脚本提取到独立的模块文件中，实现前端代码的模块化管理。

### 为什么需要模块化？

| 问题 | 解决方案 |
|------|---------|
| 脚本混乱 | 集中管理在模块文件中 |
| 代码重复 | 模块可以被多个页面引用 |
| 难以维护 | 清晰的文件结构和命名规范 |
| 性能差 | 脚本可以被浏览器缓存 |

---

## 已完成工作

### 1. 创建的模块脚本

#### 📄 `public/assets/js/modules/servers.js` (578 行)

**功能**：服务器管理模块

```javascript
// 主要函数
- downloadServers(format, scope)        // 下载服务器数据
- downloadAllFiltered(format)           // 下载全部查询数据
- showDownloadFormatDialog(scope)       // 显示格式选择对话框
- updateServerButtonStates()            // 更新按钮状态
- loadAllComponents()                   // 加载所有采集组件
- loadCommonCollectors(serverIds)       // 加载共同的采集组件
```

**使用场景**：
- 服务器列表页面（`servers/index.blade.php`）
- 下载服务器数据（Excel/CSV）
- 批量采集任务创建
- 批量修改组件

#### 📄 `public/assets/js/modules/server-groups.js` (65 行)

**功能**：服务器分组管理模块

```javascript
// 主要函数
- updateBatchDeleteButton()              // 更新批量删除按钮状态
- createChangeTask(groupId, groupName)   // 创建变更任务
```

**使用场景**：
- 服务器分组列表页面（`server-groups/index.blade.php`）
- 全选/取消全选
- 批量删除分组
- 创建变更任务

#### 📄 `public/assets/js/modules/collection-tasks.js` (253 行)

**功能**：采集任务管理模块

```javascript
// 主要函数
- updateBatchDeleteTaskButton()          // 更新批量删除按钮状态
- retryTask(taskId)                      // 重试任务
- cancelTask(taskId)                     // 取消任务
- triggerBatchTask(taskId)               // 手动触发批量任务
```

**使用场景**：
- 采集任务列表页面（`collection-tasks/index.blade.php`）
- 任务重试/取消
- 批量删除任务
- 批量执行任务

### 2. 更新的 Blade 模板

#### 📝 `resources/views/servers/index.blade.php`

**变化**：
- 删除了 580 行内联脚本
- 添加了全局变量设置
- 添加了模块脚本引用

```blade
@push('scripts')
<script>
    window.csrfToken = '{{ csrf_token() }}';
    window.serversDownloadRoute = '{{ route("servers.download") }}';
    // ... 其他全局变量
</script>
<script src="{{ asset('assets/js/modules/servers.js') }}"></script>
@endpush
```

#### 📝 `resources/views/server-groups/index.blade.php`

**变化**：
- 删除了 60 行内联脚本
- 添加了全局变量设置
- 添加了模块脚本引用

#### 📝 `resources/views/collection-tasks/index.blade.php`

**变化**：
- 删除了 250 行内联脚本
- 添加了全局变量设置
- 添加了模块脚本引用

---

## 技术架构

### 全局变量管理

所有模块脚本都通过 `window` 对象访问全局变量：

```javascript
// 在 Blade 中设置
window.csrfToken = '{{ csrf_token() }}';
window.apiRoute = '{{ route("api.endpoint") }}';

// 在模块中使用
$.ajax({
    url: window.apiRoute,
    headers: {
        'X-CSRF-TOKEN': window.csrfToken
    }
});
```

### 模块脚本结构

```javascript
/**
 * 模块名称
 * 功能描述
 */

// 1. 全局函数（如需要）
window.globalFunction = function(param) {
    // 实现
};

// 2. 初始化
$(document).ready(function() {
    // 事件绑定
    $('#element').click(function() {
        // 处理逻辑
    });
    
    // 初始化逻辑
    initializeModule();
});

// 3. 辅助函数
function helperFunction() {
    // 实现
}
```

### 脚本加载顺序

```html
<!-- 1. 第三方库 -->
<script src="jquery.min.js"></script>
<script src="bootstrap.bundle.min.js"></script>

<!-- 2. 公共脚本 -->
<script src="common/utils.js"></script>
<script src="common/notifications.js"></script>
<script src="common/api.js"></script>

<!-- 3. 主脚本 -->
<script src="main.js"></script>

<!-- 4. 模块脚本（在 Blade 中通过 @push('scripts') 加载） -->
<script src="modules/servers.js"></script>
```

---

## 快速开始

### 如何在新页面中使用模块脚本

#### 步骤 1：创建模块脚本

在 `public/assets/js/modules/` 中创建 `my-module.js`：

```javascript
/**
 * 我的模块
 * 处理特定功能
 */

$(document).ready(function() {
    // 初始化逻辑
    console.log('模块已加载');
});
```

#### 步骤 2：在 Blade 中引用

在 Blade 模板中添加：

```blade
@push('scripts')
<script>
    // 设置全局变量
    window.myVariable = '{{ $value }}';
</script>
<script src="{{ asset('assets/js/modules/my-module.js') }}"></script>
@endpush
```

#### 步骤 3：测试

1. 打开浏览器开发者工具（F12）
2. 检查 Console 标签是否有错误
3. 验证功能是否正常工作

### 如何提取现有脚本

#### 步骤 1：识别脚本

在 Blade 模板中查找 `@section('scripts')` 或 `@push('scripts')`

#### 步骤 2：复制脚本

复制 `<script>` 标签内的所有代码

#### 步骤 3：创建模块文件

在 `public/assets/js/modules/` 中创建新文件

#### 步骤 4：替换硬编码路由

```javascript
// 之前
url: '{{ route("api.endpoint") }}',

// 之后
url: window.apiRoute,
```

#### 步骤 5：设置全局变量

在 Blade 中添加全局变量设置

#### 步骤 6：移除原始脚本

删除 Blade 模板中的 `@section('scripts')` 块

---

## 常见问题

### Q: 如何处理 Blade 模板中的变量？

A: 在 Blade 中设置全局变量，然后在模块中使用：

```blade
<!-- 在 Blade 中 -->
<script>
    window.taskId = {{ $task->id }};
    window.taskData = @json($task);
</script>
```

```javascript
// 在模块中
console.log(window.taskId);
console.log(window.taskData);
```

### Q: 如何处理多个 Blade 文件共享的脚本？

A: 创建一个通用模块文件，然后在多个 Blade 文件中引用：

```blade
<!-- 在多个 Blade 文件中 -->
@push('scripts')
<script src="{{ asset('assets/js/modules/common-tasks.js') }}"></script>
@endpush
```

### Q: 如何调试模块脚本？

A: 使用浏览器开发者工具：

1. 打开 F12 开发者工具
2. 在 Sources 标签中找到脚本文件
3. 设置断点进行调试
4. 在 Console 标签中查看错误信息

### Q: 如何处理脚本加载顺序问题？

A: 确保依赖脚本在模块脚本之前加载。在 `app.blade.php` 中的加载顺序：

1. jQuery 和其他第三方库
2. 公共脚本（utils.js, notifications.js, api.js）
3. 主脚本（main.js）
4. 模块脚本（通过 @push('scripts') 在各个 Blade 中加载）

### Q: 如何处理全局变量冲突？

A: 使用命名空间或前缀来避免冲突：

```javascript
// 不好的做法
window.id = 123;

// 好的做法
window.myModule = {
    id: 123,
    name: 'value'
};
```

---

## 下一步

### 立即行动（第二阶段继续）

1. **提取高优先级模块**
   - `servers/show.blade.php` → `servers-show.js`
   - `collection-tasks/show.blade.php` → `collection-tasks-show.js`
   - `system-change/tasks/index.blade.php` → `system-change-tasks.js`

2. **完整的功能测试**
   - 测试所有已提取的模块
   - 检查浏览器控制台是否有错误
   - 验证 AJAX 请求是否正确

3. **性能测试**
   - 测试页面加载时间
   - 检查脚本文件大小
   - 验证缓存是否有效

### 后续阶段（第三阶段）

4. **创建模块 CSS 文件**
   - 为各个模块创建对应的 CSS 文件
   - 将内联样式移到模块 CSS 中

5. **性能优化**
   - 最小化 JavaScript 文件
   - 最小化 CSS 文件
   - 优化图片和其他资源

6. **浏览器兼容性测试**
   - 测试不同浏览器的兼容性
   - 修复兼容性问题

---

## 相关文档

- 📖 [第二阶段进度](./第二阶段进度.md)
- 📖 [第二阶段快速指南](./第二阶段快速指南.md)
- 📖 [第二阶段总结报告](./PHASE2_SUMMARY.md)
- 📖 [重构进度追踪](./重构进度追踪.md)
- 📖 [前端重构方案](./前端重构方案.md)

---

## 支持

如有问题或建议，请参考相关文档或联系开发团队。

---

**最后更新**：2025-10-30  
**版本**：1.0  
**作者**：AI 编程助手
