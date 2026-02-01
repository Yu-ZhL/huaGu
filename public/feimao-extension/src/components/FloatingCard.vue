<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useAuth } from '../composables/useAuth'
import { useItemScanner } from '../composables/useItemScanner'
import { useCache } from '../composables/useCache'
import { useDraggable } from '../composables/useDraggable'
import { requestApi } from '../composables/useApi'
import html2canvas from 'html2canvas'

const { isLoggedIn, userInfo, loading, loginForm, checkLoginStatus, handleLogin, handleLogout } = useAuth()
const { totalCount, filteredCount, filters, startScanning, stopScanning, isScanning, scanItems } = useItemScanner()
const { handleClearExtensionCache, handleClearTemuCache } = useCache()
const { position, onMouseDown, headerRef } = useDraggable()

const isCollapsed = ref(false)
const collectedProducts = ref([])
const uploadedCount = ref(0)
const isUIInjected = ref(false)
const showDetailDialog = ref(false)
const isLoadingDetail = ref(false)
const selectedProducts = ref([])
// 模拟AI批量开关
const isAiBatchEnabled = ref(false)
// 日志系统状态
const logs = ref([])
const activeLogMsg = ref(null)
const activeLogId = ref(null)
const logListRef = ref(null)
let clearTimer = null
const isTaskRunning = ref(false)
const isRefreshing = ref(false) // AI点数刷新节流状态

const collectingStatusText = computed(() => {
  if (isScanning.value) return '扫描商品ID...'
  if (isTaskRunning.value) return '批量采集货源...'
  return '空闲'
})

// 添加日志 (替代 alert)
const addLog = (msg, type = 'info') => {
  const id = Date.now()
  // 插入到历史记录底部 
  logs.value.push({ id, message: msg, type })
  
  // 设置顶部醒目提示
  activeLogMsg.value = msg
  activeLogId.value = id
  
  if (clearTimer) clearTimeout(clearTimer)
  // 1秒后自动关闭顶部提示
  clearTimer = setTimeout(() => {
    if (activeLogId.value === id) {
      activeLogMsg.value = null
      activeLogId.value = null
    }
  }, 1000)

  // 自动滚动到底部
  nextTick(() => {
    if (logListRef.value) {
      logListRef.value.scrollTop = logListRef.value.scrollHeight
    }
  })
}

// AI点数刷新功能(带节流)
const handleRefreshPoints = async () => {
  if (isRefreshing.value) return
  
  isRefreshing.value = true
  try {
    await checkLoginStatus()
    addLog('AI点数已刷新', 'success')
  } catch (error) {
    addLog('刷新失败', 'error')
  } finally {
    setTimeout(() => {
      isRefreshing.value = false
    }, 3000)
  }
}

// 手动关闭顶部提示
const removeLog = () => {
  activeLogMsg.value = null
  activeLogId.value = null
}

// 停止任务逻辑
const handleStop = () => {
  stopScanning()
  if (isTaskRunning.value) {
      isTaskRunning.value = false
      addLog('已手动停止任务', 'warning')
  }
}

// 设置商品高亮 - 滚动边框特效 
const setHighlight = (pid) => {
  // 移除所有旧高亮
  try {
     const old = document.querySelectorAll('.fm-collecting-highlight')
     old.forEach(el => el.classList.remove('fm-collecting-highlight'))
  } catch(e) {}

  if (!pid) return

  // 尝试匹配元素
  const selectors = [
      `[data-product-id="${pid}"]`,
      `[data-goods-id="${pid}"]`,
      `[data-id="${pid}"]`
  ]
  
  let targetEl = null
  for (const sel of selectors) {
      targetEl = document.querySelector(sel)
      if (targetEl) break
  }

  // 如果找不到，尝试模糊匹配链接
  if (!targetEl) {
     const links = document.querySelectorAll('a[href*="' + pid + '"]')
     if (links.length > 0) {
         // 往上找父容器
         targetEl = links[0].closest('[class*="card"]') || links[0].parentElement
     }
  }

  if (targetEl) {
      targetEl.classList.add('fm-collecting-highlight')
      targetEl.scrollIntoView({ behavior: 'auto', block: 'center' })
  }
}

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
    if (result && (result.success || result.code === 200) && result.data) {
      // 兼容直接数组或分页对象的 list/records/data
      let list = Array.isArray(result.data) ? result.data : (result.data.data || result.data.records || result.data.list || [])
      
      // 数据清洗：防止 null 或 JSON 字符串导致的渲染崩溃
      collectedProducts.value = list.filter(p => p && p.id).map(p => {
          // 如果 product_data 是字符串，尝试解析
          if (typeof p.product_data === 'string') {
              try { p.product_data = JSON.parse(p.product_data) } catch(e) {}
          }
          // 兜底保证 product_data 是对象
          if (!p.product_data || typeof p.product_data !== 'object') {
              p.product_data = {}
          }
          return p
      })
    }
  } catch (error) {
    console.error('Fetch details error:', error)
  }
}

const handleShowDetail = async () => {
  showDetailDialog.value = true
  isLoadingDetail.value = true
  addLog('正在加载采集明细...', 'loading')
  await fetchCollectedDetails()
  isLoadingDetail.value = false
}

const closeDetailDialog = () => {
  showDetailDialog.value = false
}

// 新增导出状态
const isExporting = ref(false)

const getUnifiedSources = (product) => {
  try {
      // 优先使用从数据库关联查询出来的全量列表
      // 严格检查是否为数组，防止 .map 崩溃
      if (Array.isArray(product.sources1688) && product.sources1688.length > 0) {
        return product.sources1688.map(s => ({
          subject: s.title || '无标题',
          price: s.price || '--',
          image: s.image,
          detailUrl: s.url,
          // 数据库里 is_primary 是 1/0 或 true/false
          isPrimary: s.is_primary == 1 || s.is_primary === true
        }))
      }
      
      // 降级使用 product_data 中的数据
      if (product.product_data && Array.isArray(product.product_data.relatedSource)) {
         return product.product_data.relatedSource.map((s, idx) => ({
            ...s,
            isPrimary: idx === 0 
         }))
      }
  } catch (e) {
      console.error('[Feimao] Source parsing error:', e)
      return []
  }
  
  return []
}

const handleExportExcel = async () => {
  if (isExporting.value) return
  isExporting.value = true

  try {
    let targets = []
    
    // 确定导出数据源
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

    // 定义详细的字段映射 
    const fieldMap = {
      'product_id': '商品ID',
      'title': '商品标题',
      'sale_price': '售价',
      'product_data.sales': '销量',
      'product_data.score': '评分',
      'product_data.commonNum': '评价数',
      'product_data.shopName': '店铺名称',
      'product_data.totalWeightMidKg': '重量(kg)',
      'product_data.category': '分类',
      'product_data.brandName': '品牌',
      'product_data.tags': '标签',
      'cover_image': '图片链接',
      'product_data.link': '商品链接',
      'site_url': '采集来源'
    }

    // 3. 构建 CSV 内容
    const headers = Object.values(fieldMap)
    const keys = Object.keys(fieldMap)
    // 辅助函数：深度获取属性值
    const getDeepValue = (obj, path) => {
      return path.split('.').reduce((acc, part) => {
        if (acc === null || acc === undefined) return undefined
        if (part.includes('[')) { 
           const [key, index] = part.replace(']', '').split('[')
           return acc[key] ? acc[key][parseInt(index)] : undefined
        }
        return acc[part]
      }, obj)
    }

    // 处理 CSV 转义
    const formatCell = (val) => {
      if (val === null || val === undefined) return ''
      const str = String(val)
      
      // 修复科学计数法
      if (/^\d{11,}$/.test(str)) {
        return `"\t${str}"`
      }

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



        // 处理分类和标签（仅保留 tags 填充逻辑）
        if (key === 'product_data.category' || key === 'product_data.tags') {
            if (!val || key === 'product_data.tags') {
                const source = item.product_data?.relatedSource?.[0] || item.sources1688?.[0]
                if (source && source.tags && Array.isArray(source.tags)) {
                    // 如果本来就是 category 且没值，才去借用 tags
                    if (key === 'product_data.category' && !val) {
                         val = source.tags.map(t => (typeof t === 'string' ? t : t.text)).join(', ')
                    }
                    if (key === 'product_data.tags') {
                         val = source.tags.map(t => (typeof t === 'string' ? t : t.text)).join(', ')
                    }
                }
            }
        }

        return formatCell(val)
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
    console.error(error)
    alert('导出过程中发生错误: ' + error.message)
  } finally {
    isExporting.value = false
  }
}

// 核心采集逻辑保持不变
// 核心采集逻辑 (重构版：使用日志和高亮)
// 核心采集逻辑 (重构版：使用日志和高亮)
const handleStartCollecting = async () => {
  if (isScanning.value || isTaskRunning.value) return
  
  // 检查AI点数
  if (!userInfo.value || userInfo.value.ai_points <= 0) {
    addLog('AI点数不足,无法采集', 'error')
    return
  }
  
  startScanning()
  addLog('开始扫描页面商品...', 'info')
  await new Promise(resolve => setTimeout(resolve, 500))
  
  const uniqueIds = []
  const uniqueProductsMap = new Map()
  const seenIds = new Set()
  const injectedUIs = document.querySelectorAll('[data-fm-host="1"]') 
  
  // 提取 ID 逻辑
  if (injectedUIs.length > 0) {
    injectedUIs.forEach(ui => {
      const pid = ui.getAttribute('data-product-id')
      if (pid && !seenIds.has(pid)) {
          seenIds.add(pid)
          uniqueIds.push(pid)
          
          // 查找该注入点关联的图片元素
          // 注入点通常在卡片内部，我们向上找卡片，再向下找图片
          const card = ui.closest('[class*="card"], [class*="item"], .Ois68FAW') || ui.parentElement
          if (card) {
               // 别截整个卡片了，不然会截到我们自己的按钮。尝试找专门存图片的容器
               const imgBox = card.querySelector('.goods-image-container-external, [class*="image-container"], [class*="img-container"], .goods-img-external') || 
                              card.querySelector('img')?.parentElement || card;
               uniqueProductsMap.set(pid, { id: pid, element: imgBox })
          }
      }
    })
  } else {
    // 自动扫描页面
    let priceElements = Array.from(document.querySelectorAll('[data-type="price"]'))
    if (priceElements.length === 0) {
      priceElements = Array.from(document.querySelectorAll('[class*="price"], [class*="Price"]'))
    }

    if (priceElements.length > 0) {
      priceElements.forEach((priceEl) => {
        let card = priceEl.parentElement
        let productId = null
        let imgUrl = null
        
        for (let i = 0; i < 5; i++) {
          if (!card) break
          
          if (!productId) {
               productId = card.dataset.goodsId || card.getAttribute('data-goods-id') || card.getAttribute('data-product-id')
               if (!productId) {
                   const link = card.querySelector('a[href]')
                   if (link) {
                     const href = link.href
                     const match = href.match(/goods_id=(\d+)/) || href.match(/goodsId=(\d+)/) || href.match(/\/(\d{15,})/)
                     if (match) productId = match[1]
                   }
               }
          }
          
          // 查找图片容器：优先找直接的 img，没有的话找 div 容器用截图
          let imgContainer = card.querySelector('img')
          if (!imgContainer) {
              // Temu 有时图片是在 div 的 background 或者 mask 里
              imgContainer = card.querySelector('[class*="img"], [class*="Img"]') || card
          }
          
          if (productId && !seenIds.has(productId)) {
              seenIds.add(productId)
              uniqueIds.push(productId)
              
              // 存储待截图元素引用，稍后统一处理或者在采集时按需处理
              // 为了不阻塞主线程，我们先把 DOM 引用存起来
              if (card) {
                  const imgBox = card.querySelector('.goods-image-container-external, [class*="image-container"], [class*="img-container"], .goods-img-external') || 
                                 card.querySelector('img')?.parentElement || card;
                  uniqueProductsMap.set(productId, { id: productId, element: imgBox })
              }
              break
          }
          card = card.parentElement
        }
      })
    }
  }

  if (uniqueIds.length === 0) {
    addLog('未能提取到有效的商品ID', 'error')
    stopScanning()
    return
  }

  addLog(`扫描到 ${uniqueIds.length} 个商品,正在提交...`, 'success')
  
  try {
    const res = await requestApi('POST', '/feimao/products', {
      productIds: uniqueIds,
      site_url: window.location.href,
      pageSize: uniqueIds.length + 10,
      pageNum: 1
    })
    
    if (res && (res.success || res.code === 200)) {
      addLog(`商品提交成功！准备采集货源(过程可能较慢,请耐心等待)...`, 'success')
      fetchUploadedCount()
      
      const savedProducts = res.data?.saved_products || []
      const records = res.data?.list || res.data?.data?.list || res.data?.records || []
      
      // 计算实际需要采集的商品数量(排除已有货源的)
      const needsCollection = savedProducts.filter(p => {
        const sources = p.product_data?.relatedSource || p.related_sources || []
        return sources.length === 0
      })
      
      // 点数预估和确认
      const countToCollect = needsCollection.length > 0 ? needsCollection.length : uniqueIds.length
      const estimatedPoints = countToCollect * 2
      const currentPoints = userInfo.value?.ai_points || 0
      
      let confirmMsg = `共扫描到 ${uniqueIds.length} 个商品\n`
      if (needsCollection.length > 0) {
        confirmMsg += `检测到 ${needsCollection.length} 个商品需要采集货源\n`
      } else {
        confirmMsg += `(未检测到明确缺货源的商品,将对所有商品进行检查)\n`
      }
      
      confirmMsg += `预计最大消耗约 ${estimatedPoints} 点AI点数\n当前剩余 ${currentPoints} 点`
      
      // 点数不足时提醒但不阻止
      if (estimatedPoints > currentPoints) {
        confirmMsg += `\n\n⚠️ 警告:点数可能不足,采集过程中可能因点数耗尽而中断`
      }
      
      confirmMsg += `\n\n是否继续采集?`
      
      if (!confirm(confirmMsg)) {
        addLog('用户取消采集', 'warning')
        stopScanning()
        isTaskRunning.value = false
        return
      }
      
      stopScanning()
      isTaskRunning.value = true
      await collectSourcesForProducts(uniqueIds, records, savedProducts, uniqueProductsMap)
    } else {
      addLog(res?.message || '提交失败', 'error')
      stopScanning()
    }
  } catch (error) {
    addLog('采集请求失败: ' + (error?.message || error || 'Unknown error'), 'error')
    stopScanning()
  } finally {
    isTaskRunning.value = false
  }
}

// 批量采集1688货源逻辑 (带高亮和日志)
// 批量采集1688货源逻辑 (优先使用 API 返回的 records)
const collectSourcesForProducts = async (allIds, records = [], savedList = [], scannedMap = new Map()) => {
  // 统计变量
  let successCount = 0
  let failCount = 0
  let totalPoints = 0
  let skippedCount = 0

  // 构建任务队列：尝试把 allIds 映射为数据库对象或 API 返回对象
  let dbMap = new Map()
  
  // 1. 先用 savedList 填充 (后端刚入库的)
  // 1. 先用 savedList 填充 (后端刚入库的)
  savedList.forEach(p => dbMap.set(String(p.product_id), p))
  
  // 3. 尝试同步缺少DB信息的 (找出所有还未匹配到 DB 记录的 IDs)
  const missingDbIds = allIds.filter(pid => !dbMap.has(pid))
  
  if (missingDbIds.length > 0) {
      try {
         addLog(`正在同步 ${missingDbIds.length} 个商品的数据库信息...`, 'loading')
         const dbRes = await requestApi('GET', `/temu/products?per_page=${missingDbIds.length + 50}&product_ids=${missingDbIds.join(',')}`)
         const dbRecords = dbRes?.data?.data || dbRes?.data?.records || dbRes?.data || []
         dbRecords.forEach(p => { dbMap.set(String(p.product_id), p) })
      } catch(e) {
         addLog('同步数据库信息部分失败，将尝试直接采集', 'warning')
      }
  }

  let processed = 0
  for (const pid of allIds) {
    // 检查停止标志
    if (!isTaskRunning.value) {
        setHighlight(null)
        break // 使用 break 而不是 return，确保能执行到下方的总结逻辑
    }

    processed++
    setHighlight(pid)
    
    // 获取当前商品的相关信息对象
    const record = records.find(r => String(r.productId) === String(pid) || String(r.product_id) === String(pid)) || {}
    const dbProduct = dbMap.get(String(pid))
    
    // 显示日志
    const displayTitle = record.title || dbProduct?.title || pid
    addLog(`正在采集 [${processed}/${allIds.length}]: ...${displayTitle.slice(0, 10)}`, 'loading')
    
    // === 回显数据到 UI ===
    // 只要有 record 信息就尝试更新 UI
    // 把 dbId 也传过去，确保列表能正确关联
    if (record.productId) {
         document.dispatchEvent(new CustomEvent('feimao:sources-updated', { 
             detail: { 
                 productId: pid, 
                 data: {
                    ...record,
                    cover_image: record.imageUrl,
                    price: record.price,
                    id: dbProduct?.id // 重要：回填 DB ID
                 }
             } 
         }))
    }
    
    try {
      // 判断已有货源 (仅当有明确标志时跳过)
      if (dbProduct && dbProduct.sources1688_count > 0) {
          addLog(`商品 ${pid} 已有货源，刷新显示`, 'info')
          skippedCount++
          document.dispatchEvent(new CustomEvent('feimao:sources-updated', { detail: { productId: pid, dbId: dbProduct.id } }))
      } else {
          // === 执行采集 ===
          // 优先使用 API 返回的图片链接 (record.imageUrl)，其次是 DB 里的
          let imgUrl = record.imageUrl || dbProduct?.cover_image || dbProduct?.img_url
          let searchMethod = 'url'
          
          // 如果已有图片，但格式是 AVIF (API不认)，强行重置，触发下方的重构逻辑
          if (imgUrl && (imgUrl.includes('avif') || imgUrl.includes('image/avif'))) {
              imgUrl = null;
          }

          // 如果没有图片链接，尝试使用截图 / DOM 提取
          if (!imgUrl) {
               addLog(`商品 ${pid} 正在嗅探高清主图...`, 'loading')
               try {
                    
                    // 1. 构造多维选择器寻找“锚点”
                    // 有时 PID 藏在 data-tooltip 里，例如 goodsImage-601099998926720
                    const possibleContainers = [
                        `[data-product-id="${pid}"]:not(.fm-ui)`,
                        `[data-goods-id="${pid}"]:not(.fm-ui)`,
                        `[data-tooltip*="${pid}"]:not(.fm-ui)`,
                        `.goods-image-container-external` // 保底类名
                    ];
                    
                    let bestImageNode = null;
                    
                    // 尝试从各个可能的容器里精准定位主图
                    for (const selector of possibleContainers) {
                        const containers = document.querySelectorAll(selector);
                        for (const container of containers) {
                            // 优先找带有官方主图标记的 (500规格)
                            const mainImg = container.querySelector('img[data-js-main-img="true"]');
                            if (mainImg) {
                                bestImageNode = mainImg;
                                break;
                            }
                        }
                        if (bestImageNode) break;
                    }

                    // 2. 如果容器匹配失败，执行全页面“地毯式扫描”
                    if (!bestImageNode) {
                        const allMainImgs = document.querySelectorAll('img[data-js-main-img="true"]');
                        for (const img of allMainImgs) {
                            // 检查图片的父级或邻近节点是否包含 PID 信息 (哪怕是文本或属性)
                            const context = img.closest('div, a')?.innerHTML || '';
                            if (context.includes(pid)) {
                                bestImageNode = img;
                                break;
                            }
                        }
                    }

                    // 3. 实在不行，取常规图片
                    if (!bestImageNode) {
                         const scannedInfo = scannedMap.get(pid);
                         const backupEl = scannedInfo?.element || document.querySelector(`[data-product-id="${pid}"]`);
                         bestImageNode = backupEl?.querySelector('img') || backupEl;
                    }

                    if (bestImageNode && bestImageNode.tagName === 'IMG') {
                        const src = bestImageNode.currentSrc || bestImageNode.src || bestImageNode.getAttribute('data-src');
                        if (src) {
                            const proxyRes = await new Promise(r => chrome.runtime.sendMessage({ action: 'FETCH_IMAGE_BASE64', url: src }, r));
                            if (proxyRes && proxyRes.success) {
                                try {
                                    const img = new Image();
                                    await new Promise((res, rej) => {
                                        img.onload = res;
                                        img.onerror = () => rej(new Error('图片解码失败'));
                                        img.src = proxyRes.data;
                                        setTimeout(() => rej(new Error('转换超时')), 8000);
                                    });

                                    const canvas = document.createElement('canvas');
                                    canvas.width = img.naturalWidth;
                                    canvas.height = img.naturalHeight;
                                    const ctx = canvas.getContext('2d');
                                    
                                    ctx.fillStyle = '#FFFFFF';
                                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                                    ctx.drawImage(img, 0, 0);

                                    imgUrl = canvas.toDataURL('image/jpeg', 0.9);
                                } catch (convErr) {
                                    imgUrl = proxyRes.data;
                                }
                            }
                        }
                    } else if (bestImageNode && bestImageNode.tagName === 'CANVAS') {
                        imgUrl = bestImageNode.toDataURL('image/jpeg', 0.9);
                    }

                    if (imgUrl) {
                        searchMethod = 'image';
                        addLog(`商品 ${pid} 封面提取成功`, 'success');
                    } else {
                        throw new Error('无法从 DOM 中嗅探到该商品的有效主图节点');
                    }
               } catch(e) {
                    console.error('Advanced image capture failed:', pid, e);
                    addLog(`商品 ${pid} 获取失败: ${e.message}`, 'error');
               }
          }
          
          // 直接使用当前遍历的 ID (Temu Product ID) 作为目标ID发给后端
          // 后端会自动处理它是 TemuID 还是 DatabaseID
          const targetId = pid
          
          if (!targetId) {
              addLog(`商品 ${pid}: ID无效，跳过`, 'warning')
              continue
          }

          if (!imgUrl) {
              addLog(`商品 ${pid} 缺少图片信息，跳过采集`, 'warning')
              continue
          }

          // 通知 UI 进入正在搜索状态
          document.dispatchEvent(new CustomEvent('feimao:sources-collecting', { detail: { productId: pid } }))

          // 调用后端采集接口
          const apiRes = await requestApi('POST', '/temu/products/collect-similar', {
              product_id: targetId,
              site_url: window.location.href,
              img_url: imgUrl || undefined
          })
          
          // 处理点数消耗信息
          if (apiRes?.ai_points_used) {
            addLog(`消耗 ${apiRes.ai_points_used} 点AI点数`, 'info')
            totalPoints += apiRes.ai_points_used
            // 刷新用户信息以更新点数显示
            await checkLoginStatus()
          }
          
          if (apiRes && apiRes.success) {
              addLog(`商品 ${pid} 货源采集完成: ${apiRes.message || ''}`, 'success')
              successCount++
              
              // 获取后端返回的数据库主键 ID
              const realDbId = apiRes.product_id
              
              document.dispatchEvent(new CustomEvent('feimao:sources-updated', { 
                  detail: { 
                      productId: pid, 
                      dbId: realDbId 
                  } 
              }))
          } else {
              const msg = apiRes?.message || '未知错误'
              addLog(`商品 ${pid} 采集未成功: ${msg}`, 'warning')
              failCount++

              document.dispatchEvent(new CustomEvent('feimao:sources-updated', { 
                  detail: { productId: pid, dbId: null, error: msg } 
              }))
          }
      }
    } catch (error) {
      addLog(`商品 ${pid} 系统报错: ${error.message || error}`, 'error')
      failCount++
      document.dispatchEvent(new CustomEvent('feimao:sources-updated', { 
          detail: { productId: pid, dbId: null, error: '系统异常' } 
      }))
    }
    
    await new Promise(resolve => setTimeout(resolve, 800))
  } // end for loop
  
  setHighlight(null)
  
  // 采集任务完成总结
  if (isTaskRunning.value) {
      const summary = `所有采集任务已完成！\n共采集: ${allIds.length} 个\n成功: ${successCount} 个\n跳过: ${skippedCount} 个\n失败: ${failCount} 个\n总消耗点数: ${totalPoints} 点`
      addLog(summary, 'success')
      // 稍微延迟关闭 loading 状态，让用户看清
      setTimeout(() => {
          isTaskRunning.value = false
      }, 2000)
  } else {
      // 手动停止的情况
      const summary = `采集任务已停止\n当前已完成: ${successCount + failCount + skippedCount} 个\n成功: ${successCount} 个\n跳过: ${skippedCount} 个\n失败: ${failCount} 个\n总消耗点数: ${totalPoints} 点`
      addLog(summary, 'warning')
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
          <div style="display: flex; align-items: center; gap: 8px;">
            <span class="fm-val red" v-if="(userInfo?.ai_points || 0) <= 0">{{ userInfo?.ai_points || 0 }} 点数不足</span>
            <span class="fm-val white" v-else>{{ userInfo?.ai_points }}</span>
            <button 
              class="fm-btn-icon" 
              @click="handleRefreshPoints" 
              :disabled="isRefreshing"
              :style="{ opacity: isRefreshing ? 0.5 : 1, cursor: isRefreshing ? 'not-allowed' : 'pointer' }"
              title="刷新AI点数"
            >
              <svg :class="{ 'rotating': isRefreshing }" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
              </svg>
            </button>
          </div>
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

        <!-- 操作按钮 -->
        <div class="fm-actions-grid">
           <button 
             class="fm-btn fm-btn-md fm-btn-primary" 
             @click="handleStartCollecting" 
             :disabled="isScanning || isTaskRunning || !isUIInjected || (userInfo?.ai_points || 0) <= 0"
           >
             {{ isScanning ? '扫描中...' : '开始采集' }}
           </button>
           <button class="fm-btn fm-btn-md fm-btn-outline" @click="handleStop">停止</button>
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

    <!-- 底部日志控制台 -->
    <div class="fm-log-console" v-if="!isCollapsed">
         <!-- 顶部醒目提示 -->
         <div class="fm-log-hero" v-if="activeLogMsg">
             <span style="flex:1;">{{ activeLogMsg }}</span>
             <span class="fm-log-close-hero" @click="removeLog">×</span>
         </div>
         
         <!-- 历史日志列表 -->
         <div class="fm-log-list custom-scrollbar" ref="logListRef">
             <div class="fm-log-item" v-if="logs.length === 0">
                 <span style="color: #64748b;">等待操作...</span>
             </div>
             <div class="fm-log-item" v-for="log in logs" :key="log.id">
                 <span :style="{
                     color: log.type === 'error' ? '#ef4444' : (log.type === 'success' ? '#4ade80' : (log.type === 'loading' ? '#fbbf24' : '#94a3b8'))
                 }">
                     {{ log.message }}
                 </span>
                 <span style="font-size: 10px; color: #475569;">{{ new Date(log.id).toTimeString().slice(0,8) }}</span>
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
         <div v-if="isLoadingDetail" style="padding: 40px; text-align: center; color: #94a3b8;">
            加载中...
         </div>
         <div v-else class="fm-list-item" v-for="product in collectedProducts" :key="product.id">
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
         <button class="fm-btn fm-btn-sm fm-btn-primary" 
                 :disabled="loading || collectedProducts.length === 0" 
                 :style="{ opacity: (loading || collectedProducts.length === 0) ? 0.5 : 1, cursor: (loading || collectedProducts.length === 0) ? 'not-allowed' : 'pointer' }"
                 @click="handleExportExcel">
             {{ loading ? '加载中...' : '导出所选' }}
         </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fm-btn-icon {
  background: transparent;
  border: none;
  padding: 4px;
  cursor: pointer;
  color: #94a3b8;
  transition: color 0.2s;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.fm-btn-icon:hover:not(:disabled) {
  color: #3b82f6;
}

.rotating {
  animation: rotate 1s linear infinite;
}

@keyframes rotate {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
