# VIP系统使用说明

## 定时任务设置

### VIP和AI点数过期清理

系统已创建定时任务命令 `vip:clean-expired`，用于每天自动清理过期的VIP和过期的AI点数。

#### 在宝塔面板设置定时任务

1. **登录宝塔面板**
2. **进入「计划任务」**
3. **添加任务**：
   - 任务类型：`Shell脚本`
   - 任务名称：`VIP过期清理`
   - 执行周期：`每天` （建议凌晨3点执行）
   - 脚本内容：
   ```bash
   cd /www/wwwroot/你的项目路径
   /usr/bin/php artisan vip:clean-expired
   ```

#### 手动执行命令

在项目目录下执行：
```bash
php artisan vip:clean-expired
```

#### 定时任务说明

- **执行频率**：每天凌晨执行一次
- **功能**：
  1. 检查所有VIP用户，将过期的VIP状态重置为普通用户
  2. 检查所有赠送的AI点数，清理已过期的点数记录
- **日志**：命令会输出清理的VIP数量和点数记录数

---

## VIP过期检测逻辑

### 自动检测
- 用户登录时检查VIP状态（通过User模型的 `isVip()` 方法）
- API接口调用时检查VIP权限
- 定时任务每天清理过期VIP

### 手动检测
管理员可在后台用户管理中查看用户的VIP过期时间

---

## Filament后台操作说明

### 模态窗口编辑

所有资源（用户、VIP套餐、网站配置）的新增和编辑操作都已改为模态窗口：
- 点击「新增」按钮 → 在当前页面弹出模态窗口
- 点击列表中的「编辑」图标 → 在当前页面弹出模态窗口
- 填写完成后点击「创建」或「保存」
- 无需跳转页面，操作更流畅

### 用户AI点数调整

在用户列表中：
1. 找到要调整点数的用户
2. 点击该用户行的「调整点数」按钮  
3. 在弹出的模态窗口中输入：
   - 调整点数：正数为增加，负数为扣除
   - 调整说明：必填，记录调整原因
4. 点击确认即可完成调整

---

## API 文档访问

### 本地开发环境
```
http://127.0.0.1:8000/docs
```

### 生产环境
```
http://你的域名/docs
```

### API文档包含
- HTML文档（可在浏览器直接查看）
- Postman集合（`/docs/collection.json`）
- OpenAPI规范（`/docs/openapi.yaml`）

---

## 故障排查

### API文档没有显示新接口

1. **确认路由文件已更新**：
   检查 `routes/api.php` 是否包含新增的路由

2. **重新生成文档**：
   ```bash
   php artisan scribe:generate
   ```

3. **清除缓存**：
   ```bash
   php artisan route:clear
   php artisan cache:clear
   ```

4. **Git部署后**：
   确保webhook_deploy_fixed.sh脚本包含了生成API文档的命令

### VIP过期但状态未更新

1. **检查定时任务是否运行**：
   查看宝塔计划任务的执行日志

2. **手动执行清理命令**：
   ```bash
   php artisan vip:clean-expired
   ```

### 模态窗口未生效

1. **清除Filament缓存**：
   ```bash
   php artisan filament:cache-clear
   ```

2. **检查Resource配置**：
   确保 `getPages()` 中只有 `index` 页面路由
