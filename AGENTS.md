# 编程代理指南

## 项目概述

Laravel + Vue 3 + Vite 全栈应用，使用 Inertia.js 前后端分离，Element Plus UI 库，Pest 测试框架，Laravel Pint 代码格式化。

## 技术栈

- **前端**: Vue 3 (组合式 API), Vite, Element Plus, Tailwind CSS, Pinia, Axios
- **后端**: Laravel, Filament v3, Knuckles Scribe v5
- **测试**: Pest (PHP), 前端未配置
- **格式化**: Laravel Pint (PHP), 建议配置 ESLint + Prettier (前端)

## 构建和开发命令

### 基础命令
```bash
npm run dev          # 启动前端开发服务器
npm run build        # 构建生产版本
composer run dev     # 启动完整开发环境
```

### 测试命令
```bash
composer test                    # 运行所有测试
./vendor/bin/pest tests/Feature/ExampleTest.php  # 运行单个测试文件
./vendor/bin/pest --filter test_method_name      # 运行特定测试方法
./vendor/bin/pest tests/Unit                      # 运行单元测试
./vendor/bin/pest tests/Feature                   # 运行功能测试
./vendor/bin/pest --coverage                      # 生成测试覆盖率报告
```

### 格式化和检查
```bash
./vendor/bin/pint         # PHP 代码格式化
./vendor/bin/pint --test  # 检查 PHP 代码风格
npm run lint              # 前端代码检查（需安装）
npm run format            # 前端代码格式化（需安装）
```

### Filament 管理面板
```bash
php artisan filament:install        # 安装 Filament
php artisan make:filament-user      # 创建用户
php artisan filament:reset-password # 重置管理员密码
```

### API 文档生成
```bash
php artisan scribe:generate  # 生成 API 文档
# 访问 http://localhost:8000/docs
```

## 代码风格指南

### Vue 组件规范

#### 组件结构顺序
```vue
<template>
  <!-- 模板内容 -->
</template>

<script setup>
  // 1. 导入语句（Vue → 第三方库 → 本地组件 → 工具函数）
  // 2. Props 定义
  // 3. Emits 定义  
  // 4. 响应式数据
  // 5. 计算属性
  // 6. 方法定义
  // 7. 生命周期钩子
</script>

<style scoped>
  /* 组件特定样式，优先使用 Tailwind CSS */
</style>
```

#### 命名规范
- **组件文件名**: PascalCase (`UserProfile.vue`)
- **组件标签**: kebab-case (`<user-profile>`)
- **变量名**: camelCase (`userName`)
- **常量名**: UPPER_SNAKE_CASE (`API_BASE_URL`)
- **函数名**: camelCase，动词开头 (`fetchUserData()`)

#### 导入顺序
```javascript
// 1. Vue 相关
import { ref, computed, onMounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

// 2. 第三方库
import axios from 'axios'
import { ElMessage } from 'element-plus'

// 3. 本地组件
import BaseButton from '@/components/BaseButton.vue'

// 4. 工具函数和配置
import { formatDate } from '@/utils/date'
```

### JavaScript/Vue 代码规范

#### 响应式数据
```javascript
const count = ref(0)
const user = ref({ name: '', email: '' })
const fullName = computed(() => `${user.value.firstName} ${user.value.lastName}`)
```

#### 事件处理
```javascript
const emit = defineEmits(['update', 'delete'])
const handleUpdate = (data) => emit('update', data)
```

#### API 调用
```javascript
const fetchUsers = async () => {
  try {
    loading.value = true
    const response = await axios.get('/api/users')
    users.value = response.data
  } catch (error) {
    ElMessage.error('获取用户列表失败')
  } finally {
    loading.value = false
  }
}
```

### CSS 样式规范

- 优先使用 Tailwind CSS 实用类
- 避免内联样式，除非必要
- 复杂样式放在 `<style scoped>` 中

```vue
<template>
  <div class="flex items-center justify-between p-4 bg-white rounded-lg shadow">
    <!-- 内容 -->
  </div>
</template>
```

### Element Plus 使用规范

```javascript
import { ElButton, ElTable, ElDialog } from 'element-plus'
