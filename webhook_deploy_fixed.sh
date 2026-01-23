#!/bin/bash
echo ' 开始执行自动部署 [huaGu]...'
date '+%Y-%m-%d %H:%M:%S'

# ================= 配置区 =================
# 项目路径
SITE_PATH='/www/wwwroot/huaGu'
# PHP 路径 (宝塔 PHP8.2)
PHP_BIN='/www/server/php/82/bin/php'
# =========================================

# 1. 进入项目目录
if [ ! -d "$SITE_PATH" ]; then
    echo ' 错误：找不到项目目录 $SITE_PATH'
    exit 1
fi
cd $SITE_PATH
echo ' 当前工作目录：$(pwd)'

# 拉取代码
echo '  正在拉取 GitHub 代码...'
git fetch --all
git reset --hard origin/main
git pull origin main --allow-unrelated-histories

# 预先修正权限
echo ' 预修复文件权限...'
chown -R www:www $SITE_PATH
chmod +x artisan

# 检查 artisan 是否存在
if [ ! -f 'artisan' ]; then
    echo ' 致命错误：当前目录下找不到 artisan 文件！'
    ls -la
    exit 1
fi

# 更新后端依赖 (Composer) - 【修改这里：包含 dev 依赖】
echo ' 更新 PHP 依赖...'
# 改为安装所有依赖（包含 Scribe），强制使用 PHP 8.2 执行 Composer，避免环境冲突
$PHP_BIN /usr/bin/composer install --optimize-autoload --ignore-platform-reqs

# 6. 更新数据库
echo '  执行数据库迁移...'
$PHP_BIN artisan migrate --force

# 编译前端 (Vue/Vite)
echo ' 编译前端 Vue...'
npm install --legacy-peer-deps
npm run build || echo ' 前端编译有警告，请检查日志'

# 清理缓存
echo ' 清理 Laravel 缓存...'
$PHP_BIN artisan optimize:clear
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache

# 发布 Filament 静态资源 (修复后台无样式问题)
echo ' 发布 Filament 资源...'
$PHP_BIN artisan vendor:publish --tag=laravel-assets --force

# 最终权限修正
echo ' 最终权限修正...'
chown -R www:www .
chmod -R 775 storage bootstrap/cache

echo ' 部署结束！SUCCESS'
