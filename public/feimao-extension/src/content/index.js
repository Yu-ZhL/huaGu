import { createApp } from 'vue'
import '../style.css'
import App from '../App.vue'
import { apiRequest } from './api.js'
import { createProductUI, updateSourceDisplay } from './ui-components.js'
import { handleChooseSource, handleCalculateProfit } from './product-handler.js'



// ==================== 1. 挂载浮动卡片 ====================
function mountFloatingCard() {
    if (document.querySelector('#feimao-extension-root')) {
        return
    }

    try {
        const container = document.createElement('div')
        container.id = 'feimao-extension-root'
        document.body.appendChild(container)
        createApp(App).mount(container)
    } catch (error) {
        console.error('%c[Feimao] ❌ FloatingCard挂载失败', 'color: #ef4444', error)
    }
}

// ==================== 2. 商品ID提取 (超详细调试) ====================
function extractProductId(node, index) {
    const attrNames = ['data-product-id', 'data-goods-id', 'data-id', 'product-id', 'goods-id']

    // 从属性提取
    for (const attrName of attrNames) {
        const val = node.getAttribute(attrName)
        if (val) return val
    }

    // 从链接提取
    const links = node.querySelectorAll('a')
    const patterns = [
        /goods\.html\?goods_id=(\w+)/,
        /goods_id[=:](\w+)/,
        /\/g\/(\w+)/,
        /\/goods\/(\w+)/,
        /goodsId[=:](\w+)/i,
        /product_id[=:](\w+)/i
    ]

    for (const link of links) {
        for (const regex of patterns) {
            const match = link.href.match(regex)
            if (match) return match[1]
        }
    }

    // 检查父节点
    let parent = node.parentElement
    for (let level = 0; level < 3 && parent; level++) {
        for (const attrName of attrNames) {
            const val = parent.getAttribute(attrName)
            if (val) return val
        }
        parent = parent.parentElement
    }

    // 生成临时ID
    return 'temp_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5)
}

// ==================== 3. 商品节点提取 (超详细调试) ====================
function extractProductNodes() {
    console.log('%c[Feimao] ========== 开始提取商品节点 ==========', 'color: #fb923c; font-weight: bold')

    // 步骤1: 查找价格元素
    // 策略A: 标准属性
    let priceElements = Array.from(document.querySelectorAll('[data-type="price"]'))

    // 策略B: 常用Class和属性模糊匹配
    if (priceElements.length === 0) {
        priceElements = Array.from(document.querySelectorAll(
            '[class*="price"], [class*="Price"], [aria-label*="Price"], [aria-label*="price"]'
        ))
    }

    // 策略C: 文本内容嗅探 (仅在列表页尝试)
    if (priceElements.length === 0 && window.location.href.includes('temu.com')) {
        const potentialPrices = Array.from(document.querySelectorAll('span, div'))
            .filter(el => {
                const txt = el.textContent.trim()
                // 匹配 $1.99, ¥10.00 等格式，且不包含太多其他字符
                return /^[\$¥€£]\s*\d+(\.\d+)?$/.test(txt) && el.children.length === 0
            })
        if (potentialPrices.length > 0) {
            priceElements = potentialPrices
        }
    }

    if (priceElements.length === 0) {
        return []
    }

    // 步骤2: 从价格元素向上查找商品卡片
    const items = []

    priceElements.forEach((priceEl) => {
        let card = priceEl.parentElement
        let found = false

        for (let i = 0; i < 5; i++) {
            if (!card) break
            const hasImg = card.querySelector('img')
            if (hasImg) {
                items.push({ card, priceEl })
                found = true
                break
            }
            card = card.parentElement
        }

        if (!found && priceEl.parentElement) {
            const fallback = priceEl.parentElement.parentElement || priceEl.parentElement
            items.push({ card: fallback, priceEl })
        }
    })

    // 步骤3: 去重
    const uniqueItems = []
    const seenCards = new Set()
    items.forEach(item => {
        if (!seenCards.has(item.card)) {
            seenCards.add(item.card)
            uniqueItems.push(item)
        }
    })

    // 步骤4: 提取商品信息
    const productNodes = []

    uniqueItems.forEach((item, index) => {
        if (item.card.querySelector('[data-fm-host="1"]')) return

        // 提取ID（带详细调试）
        const productId = extractProductId(item.card, index)



        // 提取其他信息
        const title = item.card.querySelector('[class*="title"]')?.textContent?.trim()
        const image = item.card.querySelector('img')?.src
        const price = item.priceEl.textContent?.trim()



        productNodes.push({
            node: item.card,
            productId,
            data: {
                productId,
                title,
                price,
                image
            }
        })
    })



    return productNodes
}

// ==================== 3.5. 自动加载第一个1688货源 ====================
async function autoLoadFirstSource(productId, container, dbId = null) {
    try {
        let targetDbId = dbId

        // 如果没有提供DB ID，或者提供的 ID 看起来像 Temu ID（长字符串/无法被转为有效数字），则需要通过接口查询真实 DB ID
        if (!targetDbId || String(targetDbId).length > 12) {
            // console.log(`[Feimao] 正在获取商品 ${productId} 的 ID...`)
            const temuProducts = await apiRequest('/temu/products?product_ids=' + productId)

            const productList = temuProducts?.data?.data || temuProducts?.data || []
            const temuProduct = productList.find(p => String(p.product_id) === String(productId))

            if (!temuProduct) {
                // 如果是刚扫描到的商品，后端可能还没同步完，这里就不打报警日志了，免得控制台太乱
                updateSourceDisplay(container, null)
                return
            }
            targetDbId = temuProduct.id
        }

        console.log(`[Feimao] UI更新: 正在为商品 ${productId} (DB_ID: ${targetDbId}) 加载货源...`)
        const sourcesResponse = await apiRequest(`/temu/products/${targetDbId}/sources`)

        const sources = sourcesResponse?.data || []

        if (!Array.isArray(sources) || sources.length === 0) {
            console.log(`[Feimao] UI更新: 商品 ${productId} 暂无1688货源`)
            updateSourceDisplay(container, null)

            // 额外修正：即使没找到货源，也要把 ID 存到 UI 上，方便手动点“重选”
            if (targetDbId) container.setAttribute('data-db-id', targetDbId)

            // 修改提示文字（可选，但需要操作 DOM）
            const placeholderText = container.querySelector('.fm-text-placeholder')
            if (placeholderText) placeholderText.textContent = '未找到匹配货源'
            return
        }

        // 将数据库 ID 存入 DOM，方便后续操作
        container.setAttribute('data-db-id', targetDbId)

        // 直接委派给 ui-components 处理显示逻辑
        updateSourceDisplay(container, sources[0])

        // 采集货源后，立即触发利润计算，完成正常业务闭环
        // 使用 targetDbId 并传入 true，避免内部重新请求列表导致“找不到商品”的错误弹窗
        if (targetDbId) {
            handleCalculateProfit(targetDbId, container, true)
        }

    } catch (error) {
        console.error(`[货源debug] ❌ 加载异常:`, error)
        updateSourceDisplay(container, null) // 发生任何异常都尝试切回初始状态
    }
}

// ==================== 4. 注入商品UI ====================
function injectProductUI() {
    if (!window.location.hostname.includes('temu.com')) {
        return
    }

    const products = extractProductNodes()

    if (products.length > 0) {
        let successCount = 0
        let failCount = 0

        products.forEach((product, index) => {
            try {
                const ui = createProductUI(product)
                const aiBtn = ui.querySelector('[data-fm="aiBtn"]')
                const chooseBtn = ui.querySelector('[data-fm="chooseBtn"]')

                if (aiBtn) {
                    aiBtn.addEventListener('click', (e) => {
                        e.preventDefault()
                        e.stopPropagation()
                        handleCalculateProfit(product.productId, ui)
                    })
                }
                const chooseBtns = ui.querySelectorAll('[data-fm="chooseBtn"]')

                if (chooseBtns.length > 0) {
                    chooseBtns.forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            e.preventDefault()
                            e.stopPropagation()
                            handleChooseSource(product.productId, ui)
                        })
                    })
                }

                product.node.appendChild(ui)
                successCount++


                // 自动加载第一个1688货源
                autoLoadFirstSource(product.productId, ui)
            } catch (error) {
                failCount++
                console.error(`  ❌ 商品 ${index + 1} UI注入失败:`, error)
            }
        })

        // 提交到后端
        submitProductsToAPI(products)
    } else {
    }
}

async function submitProductsToAPI(products) {
    try {
        const productIds = products.map(p => p.productId)

        const result = await apiRequest('/feimao/products', {
            method: 'POST',
            body: JSON.stringify({
                productIds,
                site_url: window.location.href
            })
        })

        // 自动刷新已有货源的UI
        if (result?.data?.saved_products) {
            result.data.saved_products.forEach(p => {
                if (p.sources1688_count > 0) {
                    document.dispatchEvent(new CustomEvent('feimao:sources-updated', {
                        detail: {
                            productId: p.product_id,
                            dbId: p.id
                        }
                    }))
                }
            })
        }
    } catch (error) {
        console.error('[Feimao] ❌ 提交商品数据失败:', error)
    }
}

// ==================== 5. 实时监控 ====================
let lastUrl = location.href
let mutationCount = 0

function observePageChanges() {
    const observer = new MutationObserver(() => {
        mutationCount++

        if (location.href !== lastUrl) {
            lastUrl = location.href
            setTimeout(injectProductUI, 1000)
        }
        else if (mutationCount % 30 === 0) {
            injectProductUI()
        }
    })

    observer.observe(document.body, {
        childList: true,
        subtree: true
    })
}

// ==================== 6. 初始化 ====================
function init() {
    // 1. 挂载浮动卡片
    mountFloatingCard()

    // 2. Temu页面功能
    if (window.location.hostname.includes('temu.com')) {
        setTimeout(() => {
            injectProductUI()
        }, 2000)

        observePageChanges()
    } else {
    }

    // 监听正在采集事件
    document.addEventListener('feimao:sources-collecting', (e) => {
        const productId = e.detail?.productId
        if (productId) {
            const ui = document.querySelector(`[data-fm-host="1"][data-product-id="${productId}"]`)
            if (ui) {
                const loading = ui.querySelector('[data-fm="sourceLoading"]')
                const placeholder = ui.querySelector('[data-fm="sourcePlaceholder"]')
                const content = ui.querySelector('[data-fm="sourceContent"]')
                if (loading) loading.classList.remove('hidden')
                if (placeholder) placeholder.style.display = 'none'
                if (content) content.classList.add('hidden')
            }
        }
    })

    // 监听货源更新事件
    document.addEventListener('feimao:sources-updated', async (e) => {
        const targetId = e.detail?.productId
        const targetDbId = e.detail?.dbId // 获取DB ID

        if (targetId) {
            const ui = document.querySelector(`[data-fm-host="1"][data-product-id="${targetId}"]`)
            if (ui) {
                await autoLoadFirstSource(targetId, ui, targetDbId)
            }
        } else {
            // 查找所有已注入的UI
            const injectedUIs = document.querySelectorAll('[data-fm-host="1"]')

            for (const ui of injectedUIs) {
                const productId = ui.getAttribute('data-product-id')
                if (productId) {
                    await autoLoadFirstSource(productId, ui)
                }
            }
        }
    })
}

// 启动
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init)
} else {
    init()
}
