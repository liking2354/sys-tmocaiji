# 现代化设计规范

## 📐 设计系统

### 1. 色彩体系

#### 主色调
```css
--primary-color: #0066cc;        /* 现代蓝 - 主要操作 */
--primary-light: #e6f2ff;        /* 浅蓝 - 背景 */
--primary-dark: #004a99;         /* 深蓝 - 悬停 */
```

#### 辅助色
```css
--success-color: #00cc99;        /* 青绿 - 成功 */
--success-light: #e6f9f5;        /* 浅青绿 - 背景 */
--success-dark: #009966;         /* 深青绿 - 悬停 */

--warning-color: #ffb800;        /* 橙色 - 警告 */
--warning-light: #fff4e6;        /* 浅橙 - 背景 */
--warning-dark: #cc9200;         /* 深橙 - 悬停 */

--danger-color: #ff6b6b;         /* 红色 - 错误 */
--danger-light: #ffe6e6;         /* 浅红 - 背景 */
--danger-dark: #cc5555;          /* 深红 - 悬停 */

--info-color: #0099ff;           /* 浅蓝 - 信息 */
--info-light: #e6f5ff;           /* 浅浅蓝 - 背景 */
--info-dark: #0077cc;            /* 深浅蓝 - 悬停 */
```

#### 中性色
```css
--gray-50: #f9fafb;              /* 最浅灰 */
--gray-100: #f3f4f6;             /* 浅灰 */
--gray-200: #e5e7eb;             /* 浅中灰 */
--gray-300: #d1d5db;             /* 中灰 */
--gray-400: #9ca3af;             /* 深中灰 */
--gray-500: #6b7280;             /* 深灰 */
--gray-600: #4b5563;             /* 更深灰 */
--gray-700: #374151;             /* 很深灰 */
--gray-800: #1f2937;             /* 极深灰 */
--gray-900: #111827;             /* 最深灰 */
```

#### 文字色
```css
--text-primary: #111827;         /* 主文字 */
--text-secondary: #6b7280;       /* 次文字 */
--text-tertiary: #9ca3af;        /* 三级文字 */
--text-disabled: #d1d5db;        /* 禁用文字 */
--text-inverse: #ffffff;         /* 反色文字 */
```

#### 背景色
```css
--bg-primary: #ffffff;           /* 主背景 */
--bg-secondary: #f9fafb;         /* 次背景 */
--bg-tertiary: #f3f4f6;          /* 三级背景 */
--bg-overlay: rgba(0, 0, 0, 0.5); /* 覆盖层 */
```

---

### 2. 排版系统

#### 字体族
```css
--font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
--font-family-mono: 'Courier New', 'Monaco', monospace;
```

#### 字号
```css
--font-size-xs: 12px;            /* 极小 */
--font-size-sm: 13px;            /* 小 */
--font-size-base: 14px;          /* 基础 */
--font-size-md: 16px;            /* 中 */
--font-size-lg: 18px;            /* 大 */
--font-size-xl: 20px;            /* 很大 */
--font-size-2xl: 24px;           /* 极大 */
--font-size-3xl: 28px;           /* 超大 */
--font-size-4xl: 32px;           /* 巨大 */
```

#### 字重
```css
--font-weight-light: 300;        /* 细 */
--font-weight-normal: 400;       /* 正常 */
--font-weight-medium: 500;       /* 中等 */
--font-weight-semibold: 600;     /* 半粗 */
--font-weight-bold: 700;         /* 粗 */
```

#### 行高
```css
--line-height-tight: 1.2;        /* 紧凑 */
--line-height-normal: 1.5;       /* 正常 */
--line-height-relaxed: 1.6;      /* 宽松 */
--line-height-loose: 1.8;        /* 很宽松 */
```

#### 标题样式
```css
h1 {
    font-size: var(--font-size-4xl);    /* 32px */
    font-weight: var(--font-weight-bold);
    line-height: var(--line-height-tight);
    margin-bottom: var(--spacing-lg);
}

h2 {
    font-size: var(--font-size-3xl);    /* 28px */
    font-weight: var(--font-weight-bold);
    line-height: var(--line-height-tight);
    margin-bottom: var(--spacing-md);
}

h3 {
    font-size: var(--font-size-2xl);    /* 24px */
    font-weight: var(--font-weight-semibold);
    line-height: var(--line-height-tight);
    margin-bottom: var(--spacing-md);
}

h4 {
    font-size: var(--font-size-xl);     /* 20px */
    font-weight: var(--font-weight-semibold);
    line-height: var(--line-height-normal);
    margin-bottom: var(--spacing-sm);
}

h5 {
    font-size: var(--font-size-lg);     /* 18px */
    font-weight: var(--font-weight-medium);
    line-height: var(--line-height-normal);
    margin-bottom: var(--spacing-sm);
}

h6 {
    font-size: var(--font-size-md);     /* 16px */
    font-weight: var(--font-weight-medium);
    line-height: var(--line-height-normal);
    margin-bottom: var(--spacing-sm);
}

p {
    font-size: var(--font-size-base);   /* 14px */
    line-height: var(--line-height-relaxed);
    margin-bottom: var(--spacing-md);
}
```

---

### 3. 间距系统

#### 基础单位：8px

```css
--spacing-xs: 4px;               /* 0.5 单位 */
--spacing-sm: 8px;               /* 1 单位 */
--spacing-md: 16px;              /* 2 单位 */
--spacing-lg: 24px;              /* 3 单位 */
--spacing-xl: 32px;              /* 4 单位 */
--spacing-2xl: 48px;             /* 6 单位 */
--spacing-3xl: 64px;             /* 8 单位 */
```

#### 应用规则
```
内边距（padding）：
- 按钮：md（16px）
- 卡片：lg（24px）
- 输入框：md（16px）
- 表单组：md（16px）

外边距（margin）：
- 标题：lg（24px）
- 段落：md（16px）
- 卡片：lg（24px）
- 列表项：sm（8px）

间隙（gap）：
- 按钮组：sm（8px）
- 表单组：md（16px）
- 网格：lg（24px）
```

---

### 4. 圆角系统

```css
--border-radius-none: 0px;       /* 无圆角 */
--border-radius-sm: 4px;         /* 小圆角 */
--border-radius-md: 8px;         /* 中圆角 */
--border-radius-lg: 12px;        /* 大圆角 */
--border-radius-xl: 16px;        /* 很大圆角 */
--border-radius-full: 9999px;    /* 完全圆形 */
```

#### 应用规则
```
按钮：md（8px）
卡片：lg（12px）
输入框：md（8px）
下拉菜单：md（8px）
模态框：lg（12px）
头像：full（9999px）
徽章：sm（4px）
```

---

### 5. 阴影系统

```css
--shadow-none: none;
--shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
--shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
--shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
--shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.1);
--shadow-2xl: 0 25px 50px rgba(0, 0, 0, 0.15);

--shadow-inner: inset 0 2px 4px rgba(0, 0, 0, 0.06);
```

#### 应用规则
```
卡片：md
按钮悬停：lg
下拉菜单：md
模态框：xl
浮动按钮：lg
输入框焦点：sm
```

---

### 6. 动画系统

#### 过渡时间
```css
--transition-fast: 150ms;        /* 快速 */
--transition-base: 300ms;        /* 标准 */
--transition-slow: 500ms;        /* 缓慢 */
```

#### 缓动函数
```css
--ease-in-out: cubic-bezier(0.4, 0, 0.2, 1);
--ease-in: cubic-bezier(0.4, 0, 1, 1);
--ease-out: cubic-bezier(0, 0, 0.2, 1);
--ease-linear: linear;
```

#### 应用规则
```
按钮悬停：fast + ease-in-out
页面过渡：base + ease-in-out
模态框：slow + ease-in-out
菜单展开：base + ease-out
菜单收起：base + ease-in
```

---

### 7. 边框系统

```css
--border-width-none: 0px;
--border-width-sm: 1px;
--border-width-md: 2px;
--border-width-lg: 4px;

--border-color: var(--gray-200);
--border-color-light: var(--gray-100);
--border-color-dark: var(--gray-300);
```

---

## 🎨 组件设计规范

### 按钮

#### 主要按钮
```css
.btn-primary {
    background-color: var(--primary-color);
    color: white;
    border: none;
    padding: var(--spacing-md) var(--spacing-lg);
    border-radius: var(--border-radius-md);
    font-weight: var(--font-weight-medium);
    cursor: pointer;
    transition: all var(--transition-fast) var(--ease-in-out);
}

.btn-primary:hover {
    background-color: var(--primary-dark);
    box-shadow: var(--shadow-lg);
    transform: translateY(-2px);
}

.btn-primary:active {
    transform: translateY(0);
    box-shadow: var(--shadow-md);
}

.btn-primary:disabled {
    background-color: var(--gray-300);
    cursor: not-allowed;
    opacity: 0.6;
}
```

#### 次要按钮
```css
.btn-secondary {
    background-color: var(--gray-100);
    color: var(--text-primary);
    border: 1px solid var(--border-color);
    padding: var(--spacing-md) var(--spacing-lg);
    border-radius: var(--border-radius-md);
    font-weight: var(--font-weight-medium);
    cursor: pointer;
    transition: all var(--transition-fast) var(--ease-in-out);
}

.btn-secondary:hover {
    background-color: var(--gray-200);
    border-color: var(--border-color-dark);
}
```

#### 按钮大小
```css
.btn-sm {
    padding: var(--spacing-sm) var(--spacing-md);
    font-size: var(--font-size-sm);
}

.btn-md {
    padding: var(--spacing-md) var(--spacing-lg);
    font-size: var(--font-size-base);
}

.btn-lg {
    padding: var(--spacing-lg) var(--spacing-xl);
    font-size: var(--font-size-md);
}
```

---

### 卡片

```css
.card {
    background-color: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-sm);
    padding: var(--spacing-lg);
    transition: all var(--transition-base) var(--ease-in-out);
}

.card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.card-header {
    padding: var(--spacing-lg);
    border-bottom: 1px solid var(--border-color);
    margin: calc(-1 * var(--spacing-lg)) calc(-1 * var(--spacing-lg)) var(--spacing-lg) calc(-1 * var(--spacing-lg));
    background-color: var(--bg-secondary);
}

.card-body {
    padding: var(--spacing-lg);
}

.card-footer {
    padding: var(--spacing-lg);
    border-top: 1px solid var(--border-color);
    margin: var(--spacing-lg) calc(-1 * var(--spacing-lg)) calc(-1 * var(--spacing-lg)) calc(-1 * var(--spacing-lg));
    background-color: var(--bg-secondary);
}
```

---

### 输入框

```css
.form-control {
    background-color: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
    font-size: var(--font-size-base);
    font-family: var(--font-family);
    color: var(--text-primary);
    transition: all var(--transition-fast) var(--ease-in-out);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px var(--primary-light);
}

.form-control:disabled {
    background-color: var(--bg-tertiary);
    color: var(--text-disabled);
    cursor: not-allowed;
}

.form-control::placeholder {
    color: var(--text-tertiary);
}
```

---

### 表格

```css
.table {
    width: 100%;
    border-collapse: collapse;
    background-color: var(--bg-primary);
}

.table thead {
    background-color: var(--bg-tertiary);
}

.table th {
    padding: var(--spacing-md);
    text-align: left;
    font-weight: var(--font-weight-semibold);
    color: var(--text-primary);
    border-bottom: 2px solid var(--border-color);
}

.table td {
    padding: var(--spacing-md);
    border-bottom: 1px solid var(--border-color);
    color: var(--text-primary);
}

.table tbody tr:hover {
    background-color: var(--bg-secondary);
}

.table tbody tr:nth-child(even) {
    background-color: var(--bg-secondary);
}
```

---

### 导航栏

```css
.navbar {
    background-color: var(--bg-primary);
    border-bottom: 1px solid var(--border-color);
    box-shadow: var(--shadow-sm);
    padding: var(--spacing-md) var(--spacing-lg);
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.navbar-brand {
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-bold);
    color: var(--text-primary);
}

.navbar-nav {
    display: flex;
    gap: var(--spacing-md);
    align-items: center;
}

.nav-link {
    color: var(--text-secondary);
    padding: var(--spacing-sm) var(--spacing-md);
    border-radius: var(--border-radius-md);
    transition: all var(--transition-fast) var(--ease-in-out);
}

.nav-link:hover {
    color: var(--primary-color);
    background-color: var(--bg-secondary);
}

.nav-link.active {
    color: var(--primary-color);
    background-color: var(--primary-light);
}
```

---

### 侧边栏

```css
.sidebar {
    background-color: var(--bg-primary);
    border-right: 1px solid var(--border-color);
    width: 260px;
    padding: var(--spacing-lg);
    height: calc(100vh - 64px);
    overflow-y: auto;
}

.sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-menu-item {
    margin-bottom: var(--spacing-sm);
}

.sidebar-menu-link {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    padding: var(--spacing-md);
    color: var(--text-secondary);
    border-radius: var(--border-radius-md);
    transition: all var(--transition-fast) var(--ease-in-out);
}

.sidebar-menu-link:hover {
    color: var(--primary-color);
    background-color: var(--bg-secondary);
    transform: translateX(4px);
}

.sidebar-menu-link.active {
    color: var(--primary-color);
    background-color: var(--primary-light);
    font-weight: var(--font-weight-semibold);
}
```

---

## 📱 响应式设计

### 断点
```css
--breakpoint-xs: 0px;            /* 超小屏幕 */
--breakpoint-sm: 576px;          /* 小屏幕 */
--breakpoint-md: 768px;          /* 中等屏幕 */
--breakpoint-lg: 992px;          /* 大屏幕 */
--breakpoint-xl: 1200px;         /* 超大屏幕 */
--breakpoint-2xl: 1400px;        /* 巨大屏幕 */
```

### 媒体查询
```css
/* 小屏幕 */
@media (max-width: 576px) {
    /* 隐藏侧边栏 */
    .sidebar {
        display: none;
    }
    
    /* 调整导航栏 */
    .navbar {
        padding: var(--spacing-sm);
    }
}

/* 中等屏幕 */
@media (max-width: 768px) {
    /* 调整间距 */
    .container {
        padding: var(--spacing-md);
    }
}

/* 大屏幕 */
@media (min-width: 1200px) {
    /* 调整容器宽度 */
    .container {
        max-width: 1140px;
    }
}
```

---

## 🌙 深色模式

### 深色模式色彩
```css
[data-theme="dark"] {
    --bg-primary: #1f2937;
    --bg-secondary: #111827;
    --bg-tertiary: #374151;
    
    --text-primary: #f9fafb;
    --text-secondary: #d1d5db;
    --text-tertiary: #9ca3af;
    
    --border-color: #374151;
    --border-color-light: #4b5563;
    --border-color-dark: #1f2937;
}
```

---

## ✅ 检查清单

- [ ] 色彩体系已定义
- [ ] 排版系统已定义
- [ ] 间距系统已定义
- [ ] 圆角系统已定义
- [ ] 阴影系统已定义
- [ ] 动画系统已定义
- [ ] 组件设计已定义
- [ ] 响应式设计已定义
- [ ] 深色模式已定义

---

## 📝 签名

**规范创建日期**：2025-10-30  
**规范作者**：AI 编程助手  
**项目名称**：sys-tmocaiji 前端重构

---

**END OF DESIGN SPECIFICATION**
