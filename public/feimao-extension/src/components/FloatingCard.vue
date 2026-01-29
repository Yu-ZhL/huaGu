<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuth } from '../composables/useAuth'
import { useItemScanner } from '../composables/useItemScanner'
import { useCache } from '../composables/useCache'
import { useDraggable } from '../composables/useDraggable'
import { apiRequest } from '../content/api.js'
import '../styles/dialog.css'

// 引入组合式函数
const { isLoggedIn, userInfo, loading, loginForm, checkLoginStatus, handleLogin, handleLogout } = useAuth()
const { totalCount, filteredCount, filters, startScanning, stopScanning, isScanning } = useItemScanner()
const { handleClearExtensionCache, handleClearTemuCache } = useCache()
const { position, onMouseDown, headerRef } = useDraggable()

// 组件内部状态
const isCollapsed = ref(false)
const collectedProducts = ref([])
const uploadedCount = ref(0)
const showDetailDialog = ref(false)
const selectedProducts = ref([])

// 采集状态文本
const collectingStatusText = computed(() => {
  if (isScanning.value) return '采集中...'
  return '空闲'
})

// 获取已采集上传的数量
const fetchUploadedCount = async () => {
  try {
    const result = await apiRequest('/temu/products')
    if (result.success && result.data) {
      uploadedCount.value = result.data.total || 0
    }
  } catch (error) {
    console.error('获取上传数量失败', error)
  }
}

// 获取采集明细
const fetchCollectedDetails = async () => {
  try {
    const result = await apiRequest('/temu/products?per_page=100')
    if (result.success && result.data) {
      collectedProducts.value = result.data.data || []
    }
  } catch (error) {
    console.error('获取采集明细失败', error)
  }
}

// 显示采集明细
const handleShowDetail = async () => {
  await fetchCollectedDetails()
  showDetailDialog.value = true
}

// 关闭采集明细弹窗
const closeDetailDialog = () => {
  showDetailDialog.value = false
}

// 导出Excel
const handleExportExcel = () => {
  if (selectedProducts.value.length === 0) {
    alert('请先选择要导出的商品')
    return
  }
  
  // 构建CSV内容
  const headers = ['商品ID', '标题', '销售价格', '重量(kg)', '品牌', '采集时间', '1688货源数量']
  const csvContent = [
    headers.join(','),
    ...selectedProducts.value.map(product => [
      product.product_id,
      `"${product.title || ''}"`,
      product.sale_price || 0,
      product.weight || 0,
      product.brand || '否',
      product.collected_at || '',
      product.sources_count || 0
    ].join(','))
  ].join('\n')
  
  // 创建Blob并下载
  const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `temu_products_${new Date().getTime()}.csv`
  link.click()
}

// 开始采集
const handleStartCollecting = () => {
  startScanning()
  alert('开始采集当前页面商品')
}

// 全选/取消全选
const handleSelectAll = (event) => {
  if (event.target.checked) {
    selectedProducts.value = [...collectedProducts.value]
  } else {
    selectedProducts.value = []
  }
}

const toggleCollapse = () => {
  isCollapsed.value = !isCollapsed.value
}

// 生命周期钩子
onMounted(async () => {
    await checkLoginStatus()
    if (isLoggedIn.value) {
      await fetchUploadedCount()
    }
    // 进入页面即开始扫描
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
          <span class="feimao-value" :style="{ color: isScanning ? '#fb7701' : '#666' }">{{ collectingStatusText }}</span>
        </div>
        <div class="feimao-row">
          <span class="feimao-label">已采集上传</span>
          <span class="feimao-counter">{{ uploadedCount }}</span>
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
            <span class="feimao-filter-tip" style="margin-left: auto;">选中的商品: {{ filteredCount }}/{{ totalCount }}</span>
          </div>
        </div>

        <!-- 按钮组 -->
        <div class="feimao-btn-row">
          <button class="feimao-btn feimao-btn-primary feimao-btn-small" @click="handleStartCollecting" :disabled="isScanning"> 
            {{ isScanning ? '采集中...' : '开始采集' }} 
          </button>
          <button class="feimao-btn feimao-btn-ghost feimao-btn-small" @click="stopScanning" :disabled="!isScanning"> 停止 </button>
          
          <button class="feimao-btn feimao-btn-ghost feimao-btn-small" style="border-color: #f97373; color: #f97373;" @click="handleClearExtensionCache"> 清空缓存 </button>
          <button class="feimao-btn feimao-btn-ghost feimao-btn-small" @click="handleExportExcel"> 导出Excel </button>
          
          <button class="feimao-btn feimao-btn-ghost feimao-btn-small" style="border-color: #fb923c; color: #fb923c;" @click="handleClearTemuCache"> 清除Temu缓存 </button>
          <button class="feimao-btn feimao-btn-ghost feimao-btn-small" style="border-color: #f97373; color: #f97373;" @click="handleLogout"> 退出登录 </button>
        </div>
        
        <button class="feimao-btn-link" @click="handleShowDetail"> 查看采集明细 </button>
      </div>
    </div>

    <!-- 采集明细弹窗 -->
    <div v-if="showDetailDialog" class="fm-mask" @click="closeDetailDialog">
      <div class="fm-dialog" @click.stop role="dialog" aria-modal="true">
        <div class="fm-head">
          <div class="fm-head-title">
            采集明细汇总
            <span class="fm-badge">{{ collectedProducts.length }}个商品</span>
          </div>
          <button class="fm-close-x" type="button" @click="closeDetailDialog">×</button>
        </div>
        <div class="fm-body">
          <div class="fm-table-wrap">
            <table class="fm-table">
              <thead>
                <tr>
                  <th style="width: 50px;">
                    <input type="checkbox" @change="handleSelectAll" />
                  </th>
                  <th>商品ID</th>
                  <th>标题</th>
                  <th>价格</th>
                  <th>重量</th>
                  <th>1688货源</th>
                  <th>采集时间</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="product in collectedProducts" :key="product.id">
                  <td style="text-align: center;">
                    <input type="checkbox" :value="product" v-model="selectedProducts" />
                  </td>
                  <td>{{ product.product_id }}</td>
                  <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    {{ product.title }}
                  </td>
                  <td>${{ product.sale_price }}</td>
                  <td>{{ product.weight }}kg</td>
                  <td>{{ product.sources_count || 0 }}件</td>
                  <td>{{ product.collected_at }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="fm-foot">
          <div class="fm-hint">已选择 {{ selectedProducts.length }} 个商品</div>
          <button class="fm-btn" @click="closeDetailDialog">关闭</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.feimao-card-header:active {
  cursor: grabbing;
}

.feimao-btn-small {
  height: 28px !important;
  padding: 0 10px !important;
  font-size: 12px !important;
}

.feimao-filter-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.feimao-filter-tip {
  font-size: 12px;
  color: #22c55e;
  margin-left: auto;
}

.feimao-counter {
  color: #22c55e;
  font-weight: 600;
}
</style>
