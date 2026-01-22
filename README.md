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
</p>

# Laravel + Vue 3 全栈应用

这是一个使用 Laravel + Vue 3 + Vite 构建的现代化全栈 Web 应用项目。

## 技术栈

### 后端
- **Laravel** - PHP Web 框架
- **MySQL/PostgreSQL** - 数据库
- **Eloquent ORM** - 数据库操作

### 前端
- **Vue 3** - 使用组合式 API
- **Vite** - 构建工具
- **Element Plus** - UI 组件库
- **Tailwind CSS** - CSS 框架
- **Pinia** - 状态管理
- **Axios** - HTTP 客户端
- **Inertia.js** - 前后端路由

## 快速开始

### 环境要求
- PHP >= 8.1
- Node.js >= 18
- Composer
- MySQL/PostgreSQL

### 安装步骤

1. **克隆项目**
```bash
git clone <repository-url>
cd project-name
```

2. **安装后端依赖**
```bash
composer install
cp .env.example .env
php artisan key:generate
```

3. **安装前端依赖**
```bash
npm install --legacy-peer-deps
```

4. **配置数据库**
编辑 `.env` 文件中的数据库配置

5. **运行数据库迁移**
```bash
php artisan migrate
```

6. **启动开发服务器**
```bash
# 启动后端服务器
php artisan serve

# 启动前端开发服务器（新终端）
npm run dev
```

## 可用脚本

### 开发
```bash
npm run dev      # 启动开发服务器
npm run build    # 构建生产版本
```

### 后端
```bash
php artisan serve           # 启动 Laravel 开发服务器
php artisan migrate         # 运行数据库迁移
php artisan tinker          # Laravel REPL
php artisan queue:work      # 启动队列处理器
```

## 项目结构

```
├── app/                     # Laravel 应用核心
├── database/                # 数据库文件
├── resources/
│   ├── js/                 # Vue.js 应用
│   │   ├── Components/     # Vue 组件
│   │   ├── Pages/          # 页面组件
│   │   ├── Layouts/        # 布局组件
│   │   └── app.js          # 应用入口
│   ├── css/                # 样式文件
│   └── views/              # Blade 模板
├── routes/                 # 路由定义
├── storage/                # 存储文件
└── public/                 # 公共资源
```

## 开发规范

请参考 `AGENTS.md` 文件了解详细的代码风格和开发规范。

## 主要特性

- 🚀 **现代化技术栈**: Vue 3 + Vite + Laravel
- 🎨 **美观界面**: Element Plus + Tailwind CSS
- 📱 **响应式设计**: 支持移动端和桌面端
- ⚡ **快速开发**: 热重载和快速构建
- 🔒 **安全认证**: Laravel 内置认证系统
- 📊 **状态管理**: Pinia 状态管理
- 🌐 **单页应用**: Inertia.js 无缝集成

## 贡献指南

1. Fork 项目
2. 创建功能分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 创建 Pull Request

## 许可证

本项目采用 MIT 许可证 - 查看 [LICENSE](LICENSE) 文件了解详情。
