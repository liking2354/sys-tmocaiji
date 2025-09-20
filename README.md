# 服务器管理与数据采集系统

## 项目简介

服务器管理与数据采集系统是一个基于PHP Laravel框架开发的Web应用，用于管理服务器并从服务器上采集各类数据。系统支持服务器分组管理、SSH连接验证、模块化采集组件、采集任务执行和结果查看等功能。

## 主要功能

### 1. 基础功能

- **服务器管理**
  - 服务器分组管理，便于对不同业务、环境、部门进行分类，实现批量操作
  - 服务器信息增删改查（单台新增、修改、删除、查看）
  - 批量导入（Excel），录入服务器基础信息（包括 IP、端口、用户名、密码）
  - 录入时支持 SSH 登录验证，确保信息准确性与可用性
  - 支持在录入时选择采集组件（可多选），为后续采集配置打好基础

- **服务器查询**
  - 提供查询界面，支持分页显示
  - 查询结果支持单选、多选，可批量导出/下载服务器信息

- **采集任务**
  - 支持对服务器发起采集任务执行
  - 支持对任务执行结果进行查看、检索

### 2. 模块化采集组件

- **组件设计**
  - 采集功能模块化，当前包含：
    - 系统进程采集
    - 系统环境变量采集
    - Nginx 配置与状态采集
    - PHP 程序关键配置采集
  - 每个组件对应独立的采集脚本，支持新增、修改、删除，方便扩展
  - 后续可扩展支持数据库（MySQL/Redis等）、中间件、容器服务等采集模块

- **执行机制**
  - 录入的服务器通过 Shell 脚本/命令执行采集任务
  - 所有采集结果统一记录和存储，保持格式规范

### 3. 数据存储与管理

- **采集数据持久化**
  - 所有采集数据统一存储，便于后续查询、统计、分析
  - 存储结构支持按照服务器、组件、时间维度进行索引

- **数据清理**
  - 支持对采集数据进行清理/归档
  - 清理方式可按以下条件筛选：
    - 指定服务器
    - 指定采集组件
    - 指定时间范围

## 技术栈

- **后端框架**：Laravel 9.x
- **前端框架**：Bootstrap 4.x
- **数据库**：MySQL
- **SSH连接**：phpseclib3
- **Excel处理**：Laravel Excel

## 系统要求

- PHP >= 8.3
- MySQL >= 5.7
- Composer
- Node.js & NPM (用于前端资源编译)

## 安装步骤

1. 克隆代码库

```bash
git clone https://github.com/liking2354/sys-tmocaiji.git
cd sys-tmocaiji
```

2. 安装PHP依赖

```bash
composer install
```

3. 复制环境配置文件并修改配置

```bash
cp .env.example .env
```

4. 生成应用密钥

```bash
php artisan key:generate
```

5. 配置数据库连接（在.env文件中）

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=server_collector
DB_USERNAME=root
DB_PASSWORD=your_password
```

6. 运行数据库迁移

```bash
php artisan migrate
```

7. 创建初始管理员用户

```bash
php artisan db:seed --class=UserSeeder
```

8. 启动开发服务器

```bash
php artisan serve
```

9. 访问系统

打开浏览器访问 http://localhost:8000

默认管理员账号：admin
默认密码：admini123

## 使用说明

1. **服务器分组管理**
   - 先创建服务器分组，用于对服务器进行分类管理

2. **服务器管理**
   - 添加服务器，填写基本信息并选择所属分组
   - 可以选择是否验证SSH连接
   - 选择需要关联的采集组件

3. **采集组件管理**
   - 查看系统内置的采集组件
   - 可以添加自定义采集组件，编写采集脚本

4. **采集任务执行**
   - 创建采集任务，选择目标服务器和采集组件
   - 执行任务并查看结果

5. **数据清理**
   - 根据条件筛选并清理历史采集数据

## 开发与扩展

### 添加新的采集组件

1. 在 `app/Models/Collector.php` 中添加新的采集组件类型
2. 在 `app/Http/Controllers/TaskController.php` 中的 `executeCollectorScript` 方法中添加对应的处理逻辑
3. 创建对应的采集脚本模板

### 自定义采集脚本

采集脚本应当遵循以下规范：

1. 使用Shell脚本编写
2. 输出结果必须是JSON格式
3. 包含错误处理机制

## 贡献指南

1. Fork 项目
2. 创建功能分支 (`git checkout -b feature/amazing-feature`)
3. 提交更改 (`git commit -m 'Add some amazing feature'`)
4. 推送到分支 (`git push origin feature/amazing-feature`)
5. 创建 Pull Request

## 许可证

本项目采用 MIT 许可证。详见 [LICENSE](LICENSE) 文件。