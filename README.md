<p align="center">
  <a href="https://laravel.com" target="_blank">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
  </a>
</p>

<p align="center">
  <a href="https://vuejs.org" target="_blank">
    <img src="https://img.shields.io/badge/Vue.js-3.x-green" alt="Vue.js">
  </a>
  <a href="https://element-plus.org" target="_blank">
    <img src="https://img.shields.io/badge/Element%20Plus-2.x-blue" alt="Element Plus">
  </a>
  <a href="https://tailwindcss.com" target="_blank">
    <img src="https://img.shields.io/badge/Tailwind%20CSS-3.x-38B2AC" alt="Tailwind CSS">
  </a>
  <a href="https://filamentphp.com" target="_blank">
    <img src="https://img.shields.io/badge/Filament-v3-FF6B35" alt="Filament">
  </a>
  <a href="https://scribe.knuckles.wtf" target="_blank">
    <img src="https://img.shields.io/badge/Scribe-v5-7B68EE" alt="Scribe">
  </a>
</p>

# 华谷系统 - Laravel + Vue 3 全栈应用

这是一个使用 Laravel + Vue 3 + Vite 构建的现代化全栈 Web 应用项目，集成 Filament 管理面板和 Scribe API 文档生成。

## ✨ 主要特性

- 🚀 **现代化技术栈**: Vue 3 + Vite + Laravel + Inertia.js
- 🎨 **美观界面**: Element Plus + Tailwind CSS 组件库
- 📱 **响应式设计**: 完美支持移动端和桌面端
- ⚡ **快速开发**: 热重载和快速构建体验
- 🔒 **完整认证**: Laravel 内置认证系统
- 📊 **管理面板**: Filament v3 现代化管理界面
- 📚 **API 文档**: Scribe 自动生成 API 文档
- 🌐 **单页应用**: Inertia.js 无缝前后端集成
- 🧪 **测试覆盖**: Pest 测试框架
- 📝 **代码规范**: Laravel Pint 代码格式化

## 🛠️ 技术栈

### 后端
- **Laravel** - PHP Web 框架
- **Filament v3** - 现代化管理面板
- **Knuckles Scribe v5** - API 文档生成
- **MySQL/PostgreSQL** - 数据库支持
- **Eloquent ORM** - 数据库操作
- **Pest** - 测试框架
- **Laravel Pint** - 代码格式化

### 前端
- **Vue 3** - 使用组合式 API
- **Vite** - 快速构建工具
- **Element Plus** - 企业级 UI 组件库
- **Tailwind CSS** - 实用优先的 CSS 框架
- **Pinia** - 状态管理
- **Axios** - HTTP 客户端
- **Inertia.js** - 前后端路由无缝集成

## 📋 环境要求

- **PHP** >= 8.2
- **Node.js** >= 18
- **Composer** >= 2.0
- **MySQL** >= 8.0 或 **PostgreSQL** >= 12

## 🚀 快速开始

### 1. 克隆项目

```bash
git clone <repository-url>
cd project-name
```

### 2. 安装依赖

```bash
# 安装后端依赖
composer install

# 安装前端依赖
npm install --legacy-peer-deps
```

### 3. 环境配置

```bash
# 复制环境配置文件
cp .env.example .env

# 生成应用密钥
php artisan key:generate

# 配置数据库连接（编辑 .env 文件）
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. 数据库设置

```bash
# 运行数据库迁移
php artisan migrate

# 填充基础数据（可选）
php artisan db:seed
```

### 5. 启动开发服务器

#### 方式一：完整开发环境（推荐）
```bash
composer run dev
```
这将同时启动：
- Laravel 开发服务器
- Vite 前端开发服务器
- 队列处理器
- 日志监控

#### 方式二：分别启动
```bash
# 终端 1: 启动后端服务器
php artisan serve

# 终端 2: 启动前端开发服务器
npm run dev
```

### 6. 访问应用

- **主应用**: http://localhost:8000
- **管理面板**: http://localhost:8000/admin
- **API 文档**: http://localhost:8000/docs

## 📁 项目结构

```
├── app/                          # Laravel 应用核心
│   ├── Filament/                 # Filament 管理面板
│   │   ├── Resources/           # 资源文件
│   │   └── Pages/              # 页面文件
│   ├── Http/Controllers/         # 控制器
│   ├── Models/                  # 模型文件
│   └── Services/                # 业务服务层
├── config/                      # 配置文件
├── database/                    # 数据库文件
│   ├── migrations/             # 数据库迁移
│   └── seeders/                # 数据填充
├── resources/                   # 前端资源
│   ├── js/                     # Vue.js 应用
│   │   ├── Components/         # Vue 组件
│   │   ├── Pages/              # 页面组件
│   │   ├── Layouts/            # 布局组件
│   │   ├── Composables/        # 组合式函数
│   │   ├── Utils/              # 工具函数
│   │   ├── Stores/             # Pinia 状态管理
│   │   ├── app.js              # 应用入口
│   │   └── bootstrap.js        # Bootstrap 文件
│   ├── css/                    # 样式文件
│   └── views/                  # Blade 模板
├── routes/                      # 路由定义
│   ├── web.php                 # Web 路由
│   ├── api.php                 # API 路由
│   └── console.php             # 控制台路由
├── storage/                     # 存储文件
├── tests/                       # 测试文件
│   ├── Feature/                # 功能测试
│   └── Unit/                   # 单元测试
└── public/                      # 公共资源
```

## 🧪 测试

```bash
# 运行所有测试
composer test

# 运行特定测试文件
./vendor/bin/pest tests/Feature/ExampleTest.php

# 运行特定测试方法
./vendor/bin/pest --filter test_method_name

# 生成测试覆盖率报告
./vendor/bin/pest --coverage
```

## 🔧 开发工具

### 代码格式化
```bash
# 格式化 PHP 代码
./vendor/bin/pint

# 检查代码风格
./vendor/bin/pint --test

# 格式化前端代码（需要先配置）
npm run format
npm run lint
```

### Filament 管理面板
```bash
# 安装 Filament（如果未安装）
php artisan filament:install

# 创建管理员用户
php artisan make:filament-user

# 重置管理员密码
php artisan filament:reset-password
```

### API 文档生成
```bash
# 生成 API 文档
php artisan scribe:generate

# 启动文档服务器
php artisan serve

# 访问文档
http://localhost:8000/docs
```

## 📖 开发规范

详细的代码风格和开发规范请参考：
- **开发指南**: [AGENTS.md](./AGENTS.md)
- **API 文档**: http://localhost:8000/docs

## 🏗️ 构建生产版本

```bash
# 构建前端资源
npm run build

# 优化生产环境
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 🤝 贡献指南

1. Fork 项目
2. 创建功能分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'feat: 添加某个功能'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 创建 Pull Request

### 提交信息规范

请使用 [Conventional Commits](https://www.conventionalcommits.org/) 规范：

```
feat: 新功能
fix: 修复问题
docs: 文档更新
style: 代码格式化
refactor: 代码重构
test: 测试相关
chore: 构建过程或辅助工具的变动
```

## 📝 更新日志

### v1.0.0 (2026-01-23)
- ✨ 初始版本发布
- 🚀 Laravel + Vue 3 + Vite 架构
- 🎨 Element Plus UI 组件集成
- 📊 Filament 管理面板
- 📚 Scribe API 文档生成
- 🧪 Pest 测试框架集成
- 📝 Laravel Pint 代码格式化

## 📄 许可证

本项目采用 [MIT 许可证](LICENSE) - 查看 LICENSE 文件了解详情。

## 🙏 致谢

- [Laravel](https://laravel.com/) - 优秀的 PHP 框架
- [Vue.js](https://vuejs.org/) - 渐进式 JavaScript 框架
- [Element Plus](https://element-plus.org/) - 企业级 UI 组件库
- [Tailwind CSS](https://tailwindcss.com/) - 实用优先的 CSS 框架
- [Filament](https://filamentphp.com/) - 优雅的管理面板
- [Scribe](https://scribe.knuckles.wtf/) - API 文档生成工具

---

<p align="center">
  Made with ❤️ by 华谷团队
</p>