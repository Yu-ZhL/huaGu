<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useAuth } from '../composables/useAuth'
import { useItemScanner } from '../composables/useItemScanner'
import { useCache } from '../composables/useCache'
import { useDraggable } from '../composables/useDraggable'

// Composables
const { isLoggedIn, userInfo, loading, loginForm, checkLoginStatus, handleLogin, handleLogout } = useAuth()
const { totalCount, filteredCount, filters, startScanning, stopScanning } = useItemScanner()
const { handleClearExtensionCache, handleClearTemuCache } = useCache()
const { position, onMouseDown, headerRef } = useDraggable()

// Component State
const isCollapsed = ref(false)

const toggleCollapse = () => {
  isCollapsed.value = !isCollapsed.value
}

// Lifecycle
onMounted(() => {
    checkLoginStatus()
    startScanning()
})

onUnmounted(() => {
    stopScanning()
})
</script>

<template>
  <div 
    class="feimao-card"
    :style="{ top: position.top + 'px', left: position.left + 'px', height: isCollapsed ? 'auto' : '' }"
  >
    <!-- 头部区域 (可拖拽) -->
    <div class="feimao-card-header" @mousedown="onMouseDown" ref="headerRef">
      <div class="feimao-logo">
        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
           <circle cx="16" cy="16" r="16" fill="#60A5FA"/>
           <path d="M10 16L14 20L22 12" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      
      <div class="feimao-title-block">
        <div class="feimao-title-main">飞猫选品采集助手</div>
        <div class="feimao-title-sub" v-if="!isCollapsed">Temu 采集助手 <span class="feimao-version">v0.0.1</span></div>
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
        <span class="feimao-value" style="color: rgb(34, 197, 94);">{{ userInfo?.ai_points || 0 }}</span>
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
            <span class="feimao-filter-tip"> 显示 {{ filteredCount }}/{{ totalCount }}</span>
          </div>
        </div>

        <!-- 按钮组 -->
        <div class="feimao-btn-row">
          <button class="feimao-btn feimao-btn-primary"> 开始采集 </button>
          <button class="feimao-btn feimao-btn-ghost" disabled> 停止 </button>
          
          <button class="feimao-btn feimao-btn-ghost" style="border-color: #f97373; color: #f97373;" @click="handleClearExtensionCache"> 清空缓存 </button>
          <button class="feimao-btn feimao-btn-ghost"> 导出Excel </button>
          
          <button class="feimao-btn feimao-btn-ghost" style="border-color: #fb923c; color: #fb923c;" @click="handleClearTemuCache"> 清除Temu缓存 </button>
          <button class="feimao-btn feimao-btn-ghost" style="border-color: #f97373; color: #f97373;" @click="handleLogout"> 退出登录 </button>
        </div>
        
        <button class="feimao-btn-link"> 查看采集明细 </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.feimao-card-header:active {
  cursor: grabbing;
}
</style>
