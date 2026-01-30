<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuth } from '../composables/useAuth'
import { useItemScanner } from '../composables/useItemScanner'
import { useCache } from '../composables/useCache'
import { useDraggable } from '../composables/useDraggable'
import { requestApi } from '../composables/useApi'
import '../styles/dialog.css'

// 引入组合式函数
const { isLoggedIn, userInfo, loading, loginForm, checkLoginStatus, handleLogin, handleLogout } = useAuth()
const { totalCount, filteredCount, filters, startScanning, stopScanning, isScanning, scanItems } = useItemScanner()
const { handleClearExtensionCache, handleClearTemuCache } = useCache()
const { position, onMouseDown, headerRef } = useDraggable()

// 组件内部状态
const isCollapsed = ref(false)
const collectedProducts = ref([])
const uploadedCount = ref(0)
const isUIInjected = ref(false)
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
    const result = await requestApi('GET', '/temu/products')
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
    console.log('[Feimao] 正在获取采集明细...')
    const result = await requestApi('GET', '/temu/products?per_page=100')
    console.log('[Feimao] 采集明细API返回:', result)
    if (result && result.success && result.data) {
      collectedProducts.value = result.data.data || []
      console.log('[Feimao] ✅ 获取到', collectedProducts.value.length, '个商品')
    }
  } catch (error) {
    console.error('[Feimao] ❌ 获取采集明细失败', error)
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
const handleStartCollecting = async () => {
  console.log('%c[采集] 用户点击"开始采集"按钮', 'color: #8b5cf6; font-weight: bold')
  
  if (isScanning.value) {
    alert('正在采集中，请勿重复点击')
    return
  }
  
  // 启动扫描器统计商品
  console.log('[采集] 启动扫描器统计商品数量...')
  startScanning()
  
  // 等待扫描完成
  await new Promise(resolve => setTimeout(resolve, 500))
  
  console.log(`[采集] 扫描完成: 总共${totalCount.value}个商品, 符合筛选${filteredCount.value}个`)
  
  if (totalCount.value === 0) {
    alert('当前页面没有检测到商品')
    stopScanning()
    return
  }
  
  // 获取所有商品ID
  console.log('[采集] 开始提取商品ID...')
  
  // 优先从已注入的UI中提取ID
  const injectedUIs = document.querySelectorAll('[data-fm-host="1"]')
  const productIds = []
  
  if (injectedUIs.length > 0) {
    console.log(`[采集] 从UI中提取到 ${injectedUIs.length} 个商品`)
    injectedUIs.forEach(ui => {
      const pid = ui.getAttribute('data-product-id')
      if (pid) productIds.push(pid)
    })
  } else {
    // 降级策略（如果还没注入UI）
    console.log('[采集] 未找到注入UI，尝试直接从DOM扫描...')
    let priceElements = Array.from(document.querySelectorAll('[data-type="price"]'))
    if (priceElements.length === 0) {
      priceElements = Array.from(document.querySelectorAll('[class*="price"], [class*="Price"]'))
    }
    
    if (priceElements.length > 0) {
      priceElements.forEach((priceEl) => {
        let card = priceEl.parentElement
        let productId = null
        
        for (let i = 0; i < 5; i++) {
          if (!card) break
          const hasImg = card.querySelector('img')
          if (hasImg) {
            productId = card.dataset.goodsId || card.getAttribute('data-goods-id') || card.getAttribute('data-product-id')
            
            if (!productId) {
              const link = card.querySelector('a[href]')
              if (link) {
                const href = link.href
                const patterns = [
                  /goods[_-]id=(\d+)/i,
                  /goodsId[=:](\d+)/i,
                  /goods[/-](\d+)/,
                  /product[/-](\d+)/,
                  /\/(\d{15,})/
                ]
                for (const pattern of patterns) {
                  const match = href.match(pattern)
                  if (match) {
                    productId = match[1]
                    break
                  }
                }
              }
            }
            
            if (productId) {
              productIds.push(productId)
            }
            break
          }
          card = card.parentElement
        }
      })
    }
  }

  // 去重
  const uniqueIds = [...new Set(productIds)]
  
  if (uniqueIds.length === 0) {
    alert('未能提取到有效的商品ID')
    stopScanning()
    return
  }
  
  console.log(`[采集] 提取到 ${productIds.length} 个商品ID`)
  
  if (productIds.length === 0) {
    alert('无法提取商品ID，请确保在商品列表页')
    stopScanning()
    return
  }
  
  // 提交到后端API
  try {
    console.log('[采集] 提交商品到后端API...')
    console.log('[采集] API地址: /feimao/products')
    console.log('[采集] 商品ID数量:', productIds.length)
    
    const res = await requestApi('POST', '/feimao/products', {
      productIds: uniqueIds,
      site_url: window.location.href
    })
    
    console.log('[采集] API完整返回:', res)
    const productList = res.data?.list || res.data?.records || []
    
    if (productList.length > 0) {
        console.log(`[采集] API返回了 ${productList.length} 条商品详情`)
    } else {
        console.warn('[采集] API返回的数据中没有 data.list 或 data.records 字段:', res)
    }
    
    if (res && (res.success || res.code === 200)) {
      console.log('%c[采集] ✅ 采集成功！', 'color: #10b981; font-weight: bold')
      
      const savedProducts = res.data?.saved_products || []
      const savedCount = savedProducts.length || productList.length || uniqueIds.length
      
      alert(`采集成功！\n提交: ${uniqueIds.length} 个商品\n保存: ${savedCount} 条记录`)
      
      // 刷新已上传数量
      await fetchUploadedCount()
      
      // 延迟一点时间
      await new Promise(resolve => setTimeout(resolve, 500))
      
      // 自动开始采集货源 (优先使用后端返回的已保存商品对象，包含数据库ID)
      if (savedProducts.length > 0) {
          collectSourcesForProducts(savedProducts)
      } else {
          collectSourcesForProducts(uniqueIds)
      }

    } else {
      console.error('[采集] ❌ API返回失败:', res)
      alert(res.message || '采集失败')
    }
  } catch (error) {
    console.error('[采集] ❌ 采集失败:', error)
    alert('采集失败: ' + error.message)
  } finally {
    stopScanning()
  }
}

// 批量采集1688货源
// input: 可以是 productId 字符串数组，也可以是包含 id 属性的商品对象数组
const collectSourcesForProducts = async (inputList) => {
  console.log(`[货源] 开始为 ${inputList.length} 个商品采集货源`)
  
  let targetProducts = []
  
  // 判断输入类型
  if (inputList.length > 0 && typeof inputList[0] === 'object' && inputList[0].id) {
      console.log('[货源] 使用API直接返回的商品数据 (无需再查询)')
      targetProducts = inputList
  } else {
      // 旧逻辑：先查询商品记录
      console.log('[货源] 正在查询新采集商品的数据库ID...')
      const temuProducts = await requestApi('GET', '/temu/products?per_page=100')
      const dbProductList = temuProducts?.data?.data || temuProducts?.data?.records || temuProducts?.data || []
      
      console.log(`[货源] 数据库查询到 ${dbProductList.length} 条记录`)
      
      // 匹配
      targetProducts = inputList.map(pid => {
          return dbProductList.find(p => p.product_id === pid)
      }).filter(p => !!p)
  }
  
  if (targetProducts.length === 0) {
      console.error('[货源] ❌ 未找到有效的商品记录，无法采集货源')
      return
  }

  let successCount = 0
  let failCount = 0
  
  for (const product of targetProducts) {
    try {
      console.log(`[货源] 正在采集同款: ${product.product_id} (DB_ID: ${product.id})`)
    
      await requestApi('POST', '/temu/products/collect-similar', {
        product_id: product.id,
        search_method: 'image',
        max_count: 20
      })
      successCount++
      console.log(`[货源] ${productId}: 采集成功 (${successCount}/${productIds.length})`)
    } catch (error) {
      failCount++
      console.error(`[货源] ${productId}: 采集失败`, error)
    }
  }
  
  console.log(`[货源] ✅ 批量采集完成: ${successCount}成功, ${failCount}失败`)
  
  // 触发页面刷新货源UI
  if (successCount > 0) {
    console.log('[货源] 触发页面刷新货源UI...')
    document.dispatchEvent(new CustomEvent('feimao:sources-updated'))
  }
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

// 检查UI注入状态
const checkUIInjection = () => {
  const injectedElements = document.querySelectorAll('[data-fm-host="1"]')
  isUIInjected.value = injectedElements.length > 0
}

// 生命周期钩子
onMounted(async () => {
    await checkLoginStatus()
    if (isLoggedIn.value) {
      await fetchUploadedCount()
    }
    
    // 定时检查UI注入状态
    setInterval(checkUIInjection, 1000)
    
    // 使用MutationObserver监听DOM变化，实时更新计数
    const observer = new MutationObserver(() => {
      if (!isScanning.value) {
        scanItems()
      }
    })
    observer.observe(document.body, { childList: true, subtree: true })
    
    // 初始扫描一次
    scanItems()
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
          <button class="feimao-btn feimao-btn-primary feimao-btn-small" @click="handleStartCollecting" :disabled="isScanning || !isUIInjected" :title="!isUIInjected ? 'UI注入中...' : ''"> 
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
