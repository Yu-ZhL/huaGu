<script setup>
import { ref, onMounted, reactive, computed } from 'vue'
import axios from 'axios'

// --- 状态定义 ---
const isCollapsed = ref(false)         // 是否收起
const isLoggedIn = ref(false)          // 是否登录
const loading = ref(false)             // 加载状态
const userInfo = ref(null)             // 用户信息
const position = reactive({ top: 200, left: 300 }) // 卡片位置

// 登录表单
const loginForm = reactive({
  account: '',
  password: ''
})

// 筛选条件 (示例数据)
const filters = reactive({
  sales: { checked: false, op: '>', val: 100 },
  price: { checked: false, op: '>', val: 33 },
  shippment: 'all',
  brand: 'all'
})

// --- 拖拽逻辑 ---
const headerRef = ref(null)
let isDragging = false
let startX = 0
let startY = 0
let startLeft = 0
let startTop = 0

const onMouseDown = (e) => {
  isDragging = true
  startX = e.clientX
  startY = e.clientY
  startLeft = position.left
  startTop = position.top
  
  document.addEventListener('mousemove', onMouseMove)
  document.addEventListener('mouseup', onMouseUp)
}

const onMouseMove = (e) => {
  if (!isDragging) return
  const dx = e.clientX - startX
  const dy = e.clientY - startY
  position.left = startLeft + dx
  position.top = startTop + dy
}

const onMouseUp = () => {
  isDragging = false
  document.removeEventListener('mousemove', onMouseMove)
  document.removeEventListener('mouseup', onMouseUp)
}

// --- API 交互 ---
const apiBase = 'http://103.214.173.247:3019/api'

// 通用请求函数，转发到 Background
const requestApi = (method, endpoint, data = null) => {
  return new Promise((resolve, reject) => {
    chrome.runtime.sendMessage({
      action: 'API_REQUEST',
      payload: {
        method,
        url: `${apiBase}${endpoint}`,
        data
      }
    }, (response) => {
       if (chrome.runtime.lastError) {
         return reject(chrome.runtime.lastError);
       }
       if (response && response.success) {
         resolve(response.data);
       } else {
         reject(response ? response.error : 'Unknown error');
       }
    });
  });
};

// 初始化检查登录状态
onMounted(async () => {
  const token = await chrome.storage.local.get('token')
  if (token && token.token) {
    // 实际项目中这里应该验证 Token 有效性
    isLoggedIn.value = true
    // 模拟获取用户信息
    userInfo.value = await chrome.storage.local.get('user')
  }
})

// 登录处理
const handleLogin = async () => {
  if (!loginForm.account || !loginForm.password) {
    alert('请输入账号和密码')
    return
  }
  
  loading.value = true
  try {
    // 改用 requestApi 通过 background 转发请求
    const res = await requestApi('POST', '/auth/login', {
      phone: loginForm.account, 
      password: loginForm.password
    })
    
    // res 即为返回的数据 body
    if (res && res.token) {
      isLoggedIn.value = true
      userInfo.value = res.user || { phone: loginForm.account } // 兜底
      
      // 保存至本地存储
      await chrome.storage.local.set({ 
        token: res.token,
        user: userInfo.value
      })
    } else {
      console.warn('Login API response structure might differ:', res)
    }
  } catch (error) {
    console.error('Login failed:', error)
    alert('登录失败，请检查账号密码')
  } finally {
    loading.value = false
  }
}

// 退出登录
const handleLogout = () => {
  isLoggedIn.value = false
  loginForm.password = ''
  chrome.storage.local.remove(['token', 'user'])
}

// 切换状态
const toggleCollapse = () => {
  isCollapsed.value = !isCollapsed.value
}
</script>

<template>
  <div 
    class="feimao-card"
    :style="{ top: position.top + 'px', left: position.left + 'px', height: isCollapsed ? 'auto' : '' }"
  >
    <!-- 头部区域 (可拖拽) -->
    <div class="feimao-card-header" @mousedown="onMouseDown" ref="headerRef">
      <div class="feimao-logo">
        <!-- 占位 SVG -->
        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
           <circle cx="16" cy="16" r="16" fill="#60A5FA"/>
           <path d="M10 16L14 20L22 12" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      
      <div class="feimao-title-block">
        <div class="feimao-title-main">飞猫选品采集助手</div>
        <div class="feimao-title-sub" v-if="!isCollapsed">Temu 采集助手 <span class="feimao-version">v1.3</span></div>
      </div>
      
      <div class="feimao-header-right">
        <div class="feimao-status-dot" :style="{ background: isLoggedIn ? '#4a78f5' : '#f97373' }"></div>
        <button class="feimao-collapse-btn" @click.stop="toggleCollapse">
          {{ isCollapsed ? '展开' : '收起' }}
        </button>
      </div>
    </div>

    <!-- 内容区域 -->
    <div class="feimao-card-body" v-if="!isCollapsed">
      <!-- 状态 1: 未登录 -->
      <div v-if="!isLoggedIn">
        <div class="feimao-row">
          <span class="feimao-label">登录状态</span>
          <span class="feimao-value" style="color: #f97373;">未登录</span>
        </div>
        
        <div class="feimao-login">
          <input class="feimao-input" v-model="loginForm.account" placeholder="手机号">
          <input type="password" class="feimao-input" v-model="loginForm.password" placeholder="密码">
          <button class="feimao-btn feimao-btn-primary" @click="handleLogin" :disabled="loading">
            {{ loading ? '登录中...' : '登录飞猫选品' }}
          </button>
        </div>
      </div>

      <!-- 状态 2: 已登录 -->
      <div v-else>
        <div class="feimao-row">
          <span class="feimao-label">登录用户</span>
          <span class="feimao-value feimao-value--ok">{{ userInfo?.phone || '用户' }}</span>
        </div>
        <div class="feimao-row">
          <span class="feimao-label">AI点数</span>
          <span class="feimao-value" style="color: rgb(34, 197, 94);">16</span>
        </div>
        <div class="feimao-row">
          <span class="feimao-label">采集状态</span>
          <span class="feimao-value">空闲</span>
        </div>
        <div class="feimao-row">
          <span class="feimao-label">已采集上传</span>
          <span class="feimao-counter">149</span>
        </div>

        <!-- 筛选框 -->
        <div class="feimao-filter-box">
          <div class="feimao-filter-title">筛选条件</div>
          
          <div class="feimao-filter-row">
            <label class="feimao-filter-check">
              <input type="checkbox" v-model="filters.sales.checked">
              <span>销量</span>
            </label>
            <select class="feimao-filter-select" :disabled="!filters.sales.checked">
              <option value=">">＞</option>
              <option value="<">＜</option>
            </select>
            <input class="feimao-filter-input" type="number" placeholder="100" v-model="filters.sales.val" :disabled="!filters.sales.checked">
          </div>

          <div class="feimao-filter-row">
            <label class="feimao-filter-check">
              <input type="checkbox" v-model="filters.price.checked">
              <span>价格</span>
            </label>
            <select class="feimao-filter-select" :disabled="!filters.price.checked">
              <option value=">">＞</option>
              <option value="<">＜</option>
            </select>
            <input class="feimao-filter-input" type="number" placeholder="30" v-model="filters.price.val" :disabled="!filters.price.checked">
          </div>

          <div class="feimao-filter-row">
            <span class="feimao-filter-label">包邮</span>
            <select class="feimao-filter-select" v-model="filters.shippment">
              <option value="all">全部</option>
              <option value="free">仅包邮</option>
              <option value="paid">仅不包邮</option>
            </select>
          </div>
           <div class="feimao-filter-row">
            <span class="feimao-filter-label">品牌</span>
            <select class="feimao-filter-select" v-model="filters.brand">
              <option value="all">全部</option>
              <option value="brand">仅品牌</option>
              <option value="nonbrand">仅非品牌</option>
            </select>
            <span class="feimao-filter-tip"> 显示 149/149</span>
          </div>
        </div>

        <!-- 按钮组 -->
        <div class="feimao-btn-row">
          <button class="feimao-btn feimao-btn-primary"> 开始采集 </button>
          <button class="feimao-btn feimao-btn-ghost" disabled> 停止 </button>
          
          <button class="feimao-btn feimao-btn-ghost" style="border-color: #f97373; color: #f97373;"> 清空缓存 </button>
          <button class="feimao-btn feimao-btn-ghost"> 导出Excel </button>
          
          <button class="feimao-btn feimao-btn-ghost" style="border-color: #fb923c; color: #fb923c;"> 清除Temu缓存 </button>
          <button class="feimao-btn feimao-btn-ghost" style="border-color: #f97373; color: #f97373;" @click="handleLogout"> 退出登录 </button>
        </div>
        
        <button class="feimao-btn-link"> 查看采集明细 </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* 局部样式，由于 style.css 在全局加载了，这里其实可以留空，
   或者把特定如拖拽游标之类的放在这里 */
.feimao-card-header:active {
  cursor: grabbing;
}
</style>
