<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuth } from '../composables/useAuth'
import { useItemScanner } from '../composables/useItemScanner'
import { useCache } from '../composables/useCache'
import { useDraggable } from '../composables/useDraggable'
import { requestApi } from '../composables/useApi'

const { isLoggedIn, userInfo, loading, loginForm, checkLoginStatus, handleLogin, handleLogout } = useAuth()
const { totalCount, filteredCount, filters, startScanning, stopScanning, isScanning, scanItems } = useItemScanner()
const { handleClearExtensionCache, handleClearTemuCache } = useCache()
const { position, onMouseDown, headerRef } = useDraggable()

const isCollapsed = ref(false)
const collectedProducts = ref([])
const uploadedCount = ref(0)
const isUIInjected = ref(false)
const showDetailDialog = ref(false)
const selectedProducts = ref([])
// 模拟AI批量开关
const isAiBatchEnabled = ref(false)

const collectingStatusText = computed(() => {
  if (isScanning.value) return '采集中...'
  return '空闲'
})

// 获取已采集上传的数量
const fetchUploadedCount = async () => {
  try {
     // 此处API路径根据实际后端调整，暂时保持原状
    const result = await requestApi('GET', '/temu/products')
    if (result.success && result.data) {
      uploadedCount.value = result.data.total || 0
    }
  } catch (error) {
    // 静默处理错误
  }
}

const fetchCollectedDetails = async () => {
  try {
    const result = await requestApi('GET', '/temu/products?per_page=100')
    if (result && result.success && result.data) {
      // 兼容直接数组或分页对象结构
      collectedProducts.value = Array.isArray(result.data) ? result.data : (result.data.data || [])
    }
  } catch (error) {
    // 静默处理错误
  }
}

const handleShowDetail = async () => {
  await fetchCollectedDetails()
  showDetailDialog.value = true
}

const closeDetailDialog = () => {
  showDetailDialog.value = false
}

// 新增导出状态
const isExporting = ref(false)

// 辅助函数：统一获取货源列表 (优先使用后端返回的全量 sources1688)
const getUnifiedSources = (product) => {
  // 1. 优先使用从数据库关联查询出来的全量列表
  if (product.sources1688 && product.sources1688.length > 0) {
    return product.sources1688.map(s => ({
      subject: s.title,
      price: s.price,
      image: s.image,
      detailUrl: s.url,
      // 注意：数据库里 is_primary 是 1/0 或 true/false
      isPrimary: s.is_primary == 1 || s.is_primary === true
    }))
  }
  
  // 2. 降级使用 product_data 中的数据 (通常只包含主图或少量信息)
  if (product.product_data && product.product_data.relatedSource) {
     return product.product_data.relatedSource.map((s, idx) => ({
        ...s,
        isPrimary: idx === 0 // 假设第一个为主
     }))
  }
  
  return []
}

const handleExportExcel = async () => {
  if (isExporting.value) return
  isExporting.value = true

  try {
    let targets = []
    
    // 1. 确定导出数据源
    if (selectedProducts.value.length > 0) {
      targets = selectedProducts.value
    } else {
      // 如果未选择，拉取最新全量数据
      const res = await requestApi('GET', '/temu/products?per_page=2000')
      if (res && res.success && res.data) {
        targets = Array.isArray(res.data) ? res.data : (res.data.data || res.data.records || [])
      }
    }
    
    if (targets.length === 0) {
      alert('没有可导出的数据')
      return
    }

    // 2. 定义详细的字段映射 (只保留 TEMU 信息)
    const fieldMap = {
      'product_id': '商品ID',
      'title': 'Temu标题',
      'sale_price': 'Temu售价',
      'weight': '重量(kg)',
      'cover_image': '主图链接',
      'product_data.link': '商品链接',
      'site_url': '采集页URL',
      'collected_at': '采集时间'
    }

    // 3. 构建 CSV 内容
    const headers = Object.values(fieldMap)
    const keys = Object.keys(fieldMap)
    
    // 辅助函数：深度获取属性值
    const getDeepValue = (obj, path) => {
      return path.split('.').reduce((acc, part) => {
        if (acc === null || acc === undefined) return undefined
        if (part.includes('[')) { // 处理数组索引
           const [key, index] = part.replace(']', '').split('[')
           return acc[key] ? acc[key][parseInt(index)] : undefined
        }
        return acc[part]
      }, obj)
    }

    // 处理 CSV 转义
    const formatCell = (val, key) => {
      if (val === null || val === undefined) return ''
      
      // 特殊处理 ID 防止科学计数法
      if (key === 'product_id') {
        return `"\t${val}"`
      }

      const str = String(val)
      if (str.includes(',') || str.includes('"') || str.includes('\n')) {
        return `"${str.replace(/"/g, '""')}"`
      }
      return str
    }

    const csvRows = [headers.join(',')]
    
    targets.forEach(item => {
      const row = keys.map(key => {
        let val = getDeepValue(item, key)
        
        // 特殊兜底逻辑
        if (key === 'product_data.link' && !val) {
             val = `https://www.temu.com/goods-${item.product_id}.html`
        }
        // 尝试从 product_data 中获取重量作为兜底
        if (key === 'weight' && !val && item.product_data) {
             val = item.product_data.totalWeightMidKg
        }

        return formatCell(val, key)
      })
      csvRows.push(row.join(','))
    })
    
    // 4. 触发下载
    const blob = new Blob(['\ufeff' + csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' })
    const link = document.createElement('a')
    link.href = URL.createObjectURL(blob)
    link.download = `feimao_temu_export_${new Date().toISOString().slice(0,10)}.csv`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
  } catch (error) {
    // 静默处理错误
    alert('导出过程中发生错误: ' + error.message)
  } finally {
    isExporting.value = false
  }
}

// 核心采集逻辑保持不变
const handleStartCollecting = async () => {
  if (isScanning.value) return
  startScanning()
  await new Promise(resolve => setTimeout(resolve, 500))
  
  const uniqueIds = []
  const seenIds = new Set()
  const injectedUIs = document.querySelectorAll('[data-fm-host="1"]') // 复用原有逻辑
  
  if (injectedUIs.length > 0) {
    injectedUIs.forEach(ui => {
      const pid = ui.getAttribute('data-product-id')
      if (pid && !seenIds.has(pid)) {
          seenIds.add(pid)
          uniqueIds.push(pid)
      }
    })
  } else {
     // 降级扫描DOM... (保留原有逻辑)
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
          productId = card.dataset.goodsId || card.getAttribute('data-goods-id') || card.getAttribute('data-product-id')
          if (!productId) {
               const link = card.querySelector('a[href]')
               if (link) {
                 const href = link.href
                 const match = href.match(/goods_id=(\d+)/) || href.match(/goodsId=(\d+)/) || href.match(/\/(\d{15,})/)
                 if (match) productId = match[1]
               }
          }
          if (productId && !seenIds.has(productId)) {
              seenIds.add(productId)
              uniqueIds.push(productId)
              break
          }
          card = card.parentElement
        }
      })
    }
  }

  if (uniqueIds.length === 0) {
    alert('未能提取到有效的商品ID')
    stopScanning()
    return
  }
  
  try {
    const res = await requestApi('POST', '/feimao/products', {
      productIds: uniqueIds,
      site_url: window.location.href
    })
    
    if (res && (res.success || res.code === 200)) {
      alert(`采集成功！\n提交: ${uniqueIds.length} 个商品`)
      await fetchUploadedCount()
      
      const savedProducts = res.data?.saved_products || []
      // 复用原有货源采集触发逻辑
      if (savedProducts.length > 0) {
          collectSourcesForProducts(savedProducts)
      } else {
          collectSourcesForProducts(uniqueIds)
      }
    } else {
      alert(res.message || '采集失败')
    }
  } catch (error) {
    alert('采集失败: ' + error.message)
  } finally {
    stopScanning()
  }
}

// 批量采集1688货源逻辑 (找回了这一块)
const collectSourcesForProducts = async (inputList) => {
  let targetProducts = []
  if (inputList.length > 0 && typeof inputList[0] === 'object' && inputList[0].id) {
      targetProducts = inputList
  } else {
      const temuProducts = await requestApi('GET', '/temu/products?per_page=100')
      const dbProductList = temuProducts?.data?.data || temuProducts?.data?.records || temuProducts?.data || []
      targetProducts = inputList.map(pid => dbProductList.find(p => p.product_id === pid)).filter(p => !!p)
  }
  
  for (const product of targetProducts) {
    try {
      if (product.sources1688_count > 0) {
          document.dispatchEvent(new CustomEvent('feimao:sources-updated', { detail: { productId: product.product_id, dbId: product.id } }))
          continue 
      }
      await requestApi('POST', '/temu/products/collect-similar', {
        product_id: product.id,
        search_method: 'image',
        max_count: 20
      })
      document.dispatchEvent(new CustomEvent('feimao:sources-updated', { detail: { productId: product.product_id, dbId: product.id } }))
      await new Promise(resolve => setTimeout(resolve, 100))
    } catch (error) {
      // 静默处理错误
    }
  }
}

const handleSelectAll = (event) => {
  selectedProducts.value = event.target.checked ? [...collectedProducts.value] : []
}

const toggleCollapse = () => {
  isCollapsed.value = !isCollapsed.value
}

const checkUIInjection = () => {
  isUIInjected.value = document.querySelectorAll('[data-fm-host="1"]').length > 0
}

const extensionVersion = ref('1.0.0')

onMounted(async () => {
    // 动态获取版本号
    try {
        if (typeof chrome !== 'undefined' && chrome.runtime && chrome.runtime.getManifest) {
            extensionVersion.value = chrome.runtime.getManifest().version
        }
    } catch (e) {
        // 静默处理错误
    }

    // 强制初始化位置到右侧 (适配 320px 宽度)
    const initialLeft = window.innerWidth - 350
    const initialTop = 100
    
    // 直接覆盖，不检查旧值
    position.left = initialLeft
    position.top = initialTop

    await checkLoginStatus()
    if (isLoggedIn.value) await fetchUploadedCount()
    setInterval(checkUIInjection, 1000)
    const observer = new MutationObserver(() => { if (!isScanning.value) scanItems() })
    observer.observe(document.body, { childList: true, subtree: true })
    scanItems()
})

onUnmounted(() => stopScanning())
</script>

<template>
  <!-- 主面板 -->
  <div class="fm-ui fm-main-card"
    :style="{ 
      top: position.top + 'px', 
      left: position.left + 'px', 
      width: isCollapsed ? '160px' : '320px',
      maxHeight: isCollapsed ? '60px' : '85vh'
    }"
  >
    <!-- 头部 -->
    <div class="fm-header" @mousedown="onMouseDown" ref="headerRef">
      <div style="display: flex; flex-direction: column;">
        <span class="fm-header-title">飞猫选品采集助手</span>
        <span class="fm-header-ver" v-if="!isCollapsed">Temu 采集助手 v{{ extensionVersion }}</span>
      </div>
      <button class="fm-btn fm-btn-xs" style="background: rgba(0,0,0,0.2); color: white; border-radius: 12px;" @click.stop="toggleCollapse">
        {{ isCollapsed ? '展开' : '收起' }}
      </button>
    </div>

    <!-- 内容区 -->
    <div class="fm-body custom-scrollbar" v-if="!isCollapsed">
      <!-- 未登录 -->
      <div v-if="!isLoggedIn" style="display: flex; flex-direction: column; gap: 12px;">
         <p style="color: #ef4444; font-weight: 700;">请先登录</p>
         <input type="text" class="fm-input" style="width: 100% !important; height: 36px; padding: 0 10px;" v-model="loginForm.account" placeholder="手机号">
         <input type="password" class="fm-input" style="width: 100% !important; height: 36px; padding: 0 10px;" v-model="loginForm.password" placeholder="密码">
         <button class="fm-btn fm-btn-md fm-btn-primary" style="width: 100% !important; height: 36px;" @click="handleLogin" :disabled="loading">登录</button>
      </div>

      <!-- 已登录：信息列表 -->
      <div v-else>
        <div class="fm-user-row">
          <span class="fm-label">登录用户</span>
          <span class="fm-val green">{{ userInfo?.phone }}</span>
        </div>
        <div class="fm-user-row">
          <span class="fm-label">AI点数</span>
          <!-- 恢复原有逻辑，有多少显示多少，只在点数<=0时提示 -->
          <span class="fm-val red" v-if="(userInfo?.ai_points || 0) <= 0">{{ userInfo?.ai_points || 0 }} 点数不足，已暂停预估</span>
          <span class="fm-val white" v-else>{{ userInfo?.ai_points }}</span>
        </div>
        <div class="fm-user-row">
          <span class="fm-label">采集状态</span>
          <span class="fm-val white">{{ collectingStatusText }}</span>
        </div>
        <div class="fm-user-row">
          <span class="fm-label">已采集上传</span>
          <span class="fm-val yellow" style="font-size: 16px;">{{ uploadedCount }}</span>
        </div>

        <!-- 筛选区域 -->
        <div class="fm-filter-box">
          <div style="color: white; font-weight: 700; margin-bottom: 8px;">筛选条件</div>
          
          <div class="fm-filter-row">
            <input type="checkbox" class="fm-checkbox" v-model="filters.sales.checked">
            <span style="color: white; width: 40px;">销量</span>
            <select class="fm-select"><option>></option><option><</option></select>
            <input type="number" class="fm-input" v-model="filters.sales.val">
          </div>

          <div class="fm-filter-row">
            <input type="checkbox" class="fm-checkbox" v-model="filters.price.checked">
            <span style="color: white; width: 40px;">价格</span>
            <select class="fm-select"><option><</option><option>></option></select>
            <input type="number" class="fm-input" v-model="filters.price.val">
          </div>

          <div class="fm-filter-row">
            <span style="color: white; width: 40px; margin-left: 24px;">包邮</span>
            <select class="fm-select" style="width: 110px;" v-model="filters.shippment">
              <option value="all">全部</option>
              <option value="free">仅包邮</option>
              <option value="paid">仅不包邮</option>
            </select>
          </div>
           
          <div class="fm-filter-row" style="display: flex; align-items: center; justify-content: space-between;">
             <div style="display: flex; align-items: center; gap: 8px;">
                <span style="color: white; width: 40px; margin-left: 24px;">品牌</span>
                <select class="fm-select" style="width: 110px;" v-model="filters.brand">
                  <option value="all">全部</option>
                  <option value="brand">仅品牌</option>
                  <option value="no_brand">仅非品牌</option>
                </select>
             </div>
             <div style="color: #94a3b8; font-size: 11px; margin-left: 8px; white-space: nowrap;">
                显示 {{ filteredCount }}/{{ totalCount }}
             </div>
          </div>
        </div>

        <!-- 按钮组 -->
        <div class="fm-actions-grid">
           <button class="fm-btn fm-btn-md fm-btn-primary" @click="handleStartCollecting" :disabled="isScanning">开始采集</button>
           <button class="fm-btn fm-btn-md fm-btn-outline" @click="stopScanning">停止</button>
           <button class="fm-btn fm-btn-md fm-btn-outline-red" @click="handleClearExtensionCache">清空缓存</button>
           <button class="fm-btn fm-btn-md fm-btn-outline" @click="handleExportExcel" :disabled="isExporting">
             {{ isExporting ? '导出中...' : '导出Excel' }}
           </button>
           <button class="fm-btn fm-btn-md fm-btn-outline-orange" @click="handleClearTemuCache">清除Temu缓存</button>
           <button class="fm-btn fm-btn-md fm-btn-outline-red" @click="handleLogout">退出登录</button>
        </div>

        <div class="fm-footer-link">
          <a class="fm-link" @click="handleShowDetail">查看采集明细</a>
        </div>
      </div>
    </div>
  </div>

  <!-- 采集明细：右侧抽屉 -->
  <div v-if="showDetailDialog" class="fm-ui fm-drawer-mask" @click.self="closeDetailDialog">
    <div class="fm-drawer">
      <!-- 抽屉头 -->
      <div class="fm-drawer-header">
        <div style="display: flex; flex-direction: column;">
          <span class="fm-drawer-title">采集明细</span>
          <span style="font-size: 11px; opacity: 0.8;">当前 {{ collectedProducts.length }} 条</span>
        </div>
        
        <div style="display: flex; align-items: center; gap: 12px;">
           <label style="display: flex; align-items: center; gap: 4px; font-size: 12px; cursor: pointer;">
             <input type="checkbox" v-model="isAiBatchEnabled">
             AI批量自动计算
           </label>
           <button style="background: none; border: none; font-size: 24px; color: white; cursor: pointer;" @click="closeDetailDialog">×</button>
        </div>
      </div>

      <!-- 抽屉内容 -->
      <div class="fm-drawer-content custom-scrollbar">
         <div class="fm-list-item" v-for="product in collectedProducts" :key="product.id">
            <div class="fm-item-checkbox">
              <input type="checkbox" class="fm-checkbox" :value="product" v-model="selectedProducts">
            </div>
            
            <img class="fm-item-img" :src="product.cover_image || product.img_url || product.product_data?.imageUrl || 'https://via.placeholder.com/64'" @error="$event.target.src='https://via.placeholder.com/64'" style="border: 1px solid #334155;">
            
            <div class="fm-item-info" style="min-width: 0;">
              <!-- 顶部：Temu 基础信息 -->
              <div style="display: flex; gap: 6px; font-size: 11px; color: #94a3b8; margin-bottom: 2px;">
                 <span style="flex: 1; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;" :title="product.title">Temu: {{ product.title }}</span>
                 <span style="color: #cbd5e1;">$ {{ product.sale_price || product.product_data?.price }}</span>
              </div>

              <!-- 中间：1688 货源展示 (显示所有匹配) -->
              <div v-if="getUnifiedSources(product).length > 0" 
                   style="background: #111827; padding: 6px; border-radius: 4px; border: 1px solid #374151; display: flex; flex-direction: column; gap: 6px;">
                  
                  <div v-for="(source, sIdx) in getUnifiedSources(product)" :key="sIdx" 
                       style="display: flex; gap: 8px; border-bottom: 1px dashed #374151; padding-bottom: 6px;">
                      
                      <img :src="source.image" style="width: 32px; height: 32px; border-radius: 2px; flex-shrink: 0; object-fit: cover;">
                      
                      <div style="flex: 1; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between;">
                          <div style="font-size: 11px; color: #e5e7eb; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;" :title="source.subject">
                             <span v-if="source.isPrimary" style="color: #fab005; margin-right: 4px;">[主]</span>
                             1688: {{ source.subject }}
                          </div>
                          <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 2px;">
                              <span style="color: #fca5a5; font-size: 11px;">¥ {{ source.price }}</span>
                              <a :href="source.detailUrl" target="_blank" style="color: #3b82f6; font-size: 10px; margin-left: auto;">详情</a>
                          </div>
                      </div>
                  </div>
                  
                  <!-- 利润只显示一次，基于主图/选定图 -->
                  <div v-if="product.product_data.forecastProfits" style="text-align: right; font-size: 11px; padding-top: 2px; border-top: 1px solid #374151; margin-top: 2px;">
                        <span :style="{color: Number(product.product_data.forecastProfits) >= 0 ? '#4ade80' : '#ef4444', fontWeight: '700'}">
                            预估利润: {{ product.product_data.forecastProfits }}
                        </span>
                   </div>
              </div>
              
              <!-- 兜底 -->
              <div v-else style="background: #111827; padding: 6px; border-radius: 4px; font-size: 11px; color: #6b7280; text-align: center; border: 1px dashed #374151;">
                  未匹配 1688 货源
              </div>
              
              <div class="fm-item-actions">
                <a class="fm-link" style="font-size: 10px;" 
                   :href="product.product_data?.link || `https://www.temu.com/goods-${product.product_id}.html`" 
                   target="_blank">Temu详情</a>
                
                <a v-if="product.product_data?.relatedSource?.[0]?.detailUrl" 
                   class="fm-link" style="font-size: 10px; margin-left: auto;" 
                   :href="product.product_data.relatedSource[0].detailUrl" 
                   target="_blank">1688详情</a>
              </div>
            </div>
         </div>

        <div v-if="collectedProducts.length === 0" style="padding: 40px; text-align: center; color: #64748b;">
           暂无数据
        </div>
      </div>

      <!-- 抽屉底部 -->
      <div class="fm-drawer-footer">
         <div style="font-size: 12px; color: #94a3b8;">
           <input type="checkbox" @change="handleSelectAll" style="margin-right: 4px; vertical-align: middle;">
           全选 (已选 {{ selectedProducts.length }})
         </div>
         <button class="fm-btn fm-btn-sm fm-btn-primary" @click="handleExportExcel">导出所选</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
</style>
