# 脚本目录说明

本目录包含系统采集项目的各类脚本文件，按功能分类组织。

## 目录结构

```
scripts/
├── deployment/          # 部署相关脚本
│   ├── deploy.sh       # 项目部署脚本
│   └── init_production.sh # 生产环境初始化脚本
├── maintenance/        # 维护相关脚本
│   ├── schedule_tasks.sh # 定时任务设置脚本
│   ├── update.sh       # 完整更新脚本
│   ├── quick_update.sh # 快速更新脚本
│   ├── diagnose_tasks.sh # 任务状态诊断脚本
│   └── quick_diagnose.sh # 快速诊断脚本
├── utils/             # 工具脚本目录（预留）
└── README.md          # 本说明文件
```

## 脚本说明

### 部署脚本 (deployment/)

#### deploy.sh
项目部署脚本，用于将项目部署到生产环境。

**使用方法：**
```bash
# 使用默认目标目录 /www/wwwroot/tmocaiji
./scripts/deployment/deploy.sh

# 指定目标目录
./scripts/deployment/deploy.sh /path/to/target/directory
```

**功能特性：**
- 自动检测项目根目录
- 同步项目文件到目标目录
- 自动设置文件权限
- 安装Composer依赖
- 清除Laravel缓存
- 创建存储链接

#### init_production.sh
生产环境初始化脚本，用于将现有项目配置为Git管理或全新部署。

**使用方法：**
```bash
# 使用默认目录
./scripts/deployment/init_production.sh

# 指定目标目录
./scripts/deployment/init_production.sh /path/to/target
```

**功能特性：**
- 智能检测现有项目状态
- 支持备份现有文件后重新克隆
- 支持将现有目录转换为Git仓库
- 自动配置远程仓库地址
- 完整的项目初始化流程
- 自动设置权限和依赖安装

### 维护脚本 (maintenance/)

#### schedule_tasks.sh
定时任务设置脚本，用于配置系统的定时任务。

**使用方法：**
```bash
# 自动检测项目路径
./scripts/maintenance/schedule_tasks.sh

# 指定项目路径
./scripts/maintenance/schedule_tasks.sh /path/to/project
```

**功能特性：**
- 自动检测项目路径
- 设置Laravel调度任务（每分钟执行）
- 设置任务状态重置（每小时执行）
- 避免重复添加相同任务

#### update.sh
完整的项目更新脚本，从Git仓库拉取最新代码并执行完整的更新流程。

**使用方法：**
```bash
# 自动检测项目路径
./scripts/maintenance/update.sh

# 指定项目路径
./scripts/maintenance/update.sh /path/to/project
```

**功能特性：**
- 自动备份当前状态和重要文件
- 从远程Git仓库拉取最新代码
- 更新Composer依赖
- 执行Laravel维护命令
- 可选执行数据库迁移
- 重启队列工作进程
- 设置正确的文件权限
- 彩色输出和详细日志

#### quick_update.sh
快速更新脚本，执行基本的代码更新操作。

**使用方法：**
```bash
# 自动检测项目路径
./scripts/maintenance/quick_update.sh

# 指定项目路径
./scripts/maintenance/quick_update.sh /path/to/project
```

**功能特性：**
- 快速拉取最新代码
- 更新Composer依赖
- 清除Laravel缓存
- 设置基本文件权限
- 适合频繁的小更新

#### diagnose_tasks.sh
任务状态诊断脚本，用于检查和修复卡住的采集任务。

**使用方法：**
```bash
# 自动检测项目路径
./scripts/maintenance/diagnose_tasks.sh

# 指定项目路径
./scripts/maintenance/diagnose_tasks.sh /path/to/project
```

**功能特性：**
- 检查定时任务配置状态
- 检查Laravel调度配置
- 识别卡住的主任务和任务详情
- 查看任务重置日志
- 提供手动重置选项
- 给出详细的解决建议

#### quick_diagnose.sh
快速任务诊断脚本，简化版的任务状态检查工具。

**使用方法：**
```bash
# 自动检测项目路径
./scripts/maintenance/quick_diagnose.sh

# 指定项目路径
./scripts/maintenance/quick_diagnose.sh /path/to/project
```

**功能特性：**
- 快速执行任务状态检查
- 使用专门的artisan命令进行诊断
- 简洁的输出和操作
- 适合日常快速检查

## 使用注意事项

1. **权限要求**：确保脚本具有可执行权限
2. **路径检测**：脚本会自动检测项目根目录，无需手动配置
3. **生产环境**：在生产环境使用时，建议先在测试环境验证
4. **备份数据**：部署前请备份重要数据

## 扩展说明

- `utils/` 目录预留给未来的工具脚本
- 所有脚本都支持相对路径调用
- 脚本遵循Shell最佳实践，包含错误处理和用户确认

## 更新脚本使用建议

### 选择合适的更新脚本

- **完整更新 (update.sh)**: 适用于重要版本更新、生产环境部署
  - 包含完整的备份机制
  - 支持数据库迁移
  - 详细的更新日志和确认提示

- **快速更新 (quick_update.sh)**: 适用于日常开发、小版本更新
  - 快速执行基本更新操作
  - 无备份机制，适合开发环境
  - 简洁的输出信息

### 更新流程建议

1. **开发环境**: 使用 `quick_update.sh` 进行日常更新
2. **测试环境**: 使用 `update.sh` 进行完整测试
3. **生产环境**: 使用 `update.sh` 并仔细检查每个步骤

## 维护记录

- 2025-09-25: 重构目录结构，按功能分类组织脚本
- 2025-09-25: 优化路径检测逻辑，支持自动检测项目根目录
- 2025-09-25: 添加完整更新脚本和快速更新脚本