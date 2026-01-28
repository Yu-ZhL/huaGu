#!/bin/bash

# ================= 配置区 =================
PROJECT_NAME="huaGu"
SITE_PATH="/www/wwwroot/huaGu"
PHP_BIN="/www/server/php/82/bin/php"
COMPOSER_BIN="composer"
# =========================================

echo "🚀 开始执行自动部署 [$PROJECT_NAME]..."
date '+%Y-%m-%d %H:%M:%S'

# 进入项目目录
if [ ! -d "$SITE_PATH" ]; then
    echo "❌ 错误：找不到项目目录 $SITE_PATH"
    exit 1
fi
cd $SITE_PATH
echo "📂 当前工作目录：$(pwd)"

# 预处理文件权限
echo "🔓 预处理文件权限..."
if [ -f "public/.user.ini" ]; then
    chattr -i public/.user.ini
fi

# 拉取代码
echo "📡 正在拉取 GitHub 代码..."
git fetch --all
git reset --hard origin/main

# 更新 PHP 依赖
echo "📦 更新 PHP 依赖..."
# 增加 --no-interaction 确保脚本不被中断
$PHP_BIN $(which $COMPOSER_BIN) install --no-dev --optimize-autoloader --ignore-platform-reqs --no-interaction

# 执行数据库迁移
echo "🗄️ 执行数据库迁移..."
$PHP_BIN artisan migrate --force

# 编译前端资源
echo "🎨 编译前端 Vue/Vite..."
npm install --legacy-peer-deps
npm run build

# 清理并重构 Laravel 缓存
echo "🧹 清理并优化 Laravel 缓存..."
$PHP_BIN artisan optimize
$PHP_BIN artisan view:cache
$PHP_BIN artisan event:cache

# Filament 专用优化
echo "✨ 优化 Filament 资源..."
$PHP_BIN artisan filament:optimize

# 最终权限修正
echo "🛡️ 最终权限修正..."
chown -R www:www $SITE_PATH
chmod -R 755 $SITE_PATH
chmod -R 777 $SITE_PATH/storage $SITE_PATH/bootstrap/cache

# 重新锁定 .user.ini
if [ -f "public/.user.ini" ]; then
    chattr +i public/.user.ini
fi

echo "✅ 部署结束！SUCCESS"
