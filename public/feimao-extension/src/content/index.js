import { createApp } from 'vue'
import '../style.css'
import App from '../App.vue'
import { apiRequest } from './api.js'
import { createProductUI } from './ui-components.js'
import { handleChooseSource, handleCalculateProfit } from './product-handler.js'

console.log('%c[Feimao] Content script loading...', 'color: #4a78f5; font-weight: bold')

// ==================== 1. 挂载浮动卡片 ====================
function mountFloatingCard() {
    console.log('[Feimao] 挂载FloatingCard...')

    if (document.querySelector('#feimao-extension-root')) {
        console.log('[Feimao] FloatingCard已存在')
        return
    }

    try {
        const container = document.createElement('div')
        container.id = 'feimao-extension-root'
        document.body.appendChild(container)
        createApp(App).mount(container)
        console.log('%c[Feimao] ✅ FloatingCard挂载成功', 'color: #10b981; font-weight: bold')
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
    let priceElements = Array.from(document.querySelectorAll('[data-type="price"]'))
    console.log(`[Feimao] 找到 ${priceElements.length} 个 [data-type="price"] 元素`)

    if (priceElements.length === 0) {
        console.log('[Feimao] 尝试回退策略...')
        priceElements = Array.from(document.querySelectorAll('[class*="price"], [class*="Price"]'))
        console.log(`[Feimao] 回退策略找到 ${priceElements.length} 个价格元素`)
    }

    if (priceElements.length === 0) {
        console.warn('%c[Feimao] ⚠️ 未找到价格元素', 'color: #fb923c')
        console.log('[Feimao] DOM信息:')
        console.log('  - body子元素:', document.body.children.length)
        console.log('  - 所有a标签:', document.querySelectorAll('a').length)
        console.log('  - 所有img标签:', document.querySelectorAll('img').length)
        return []
    }

    // 步骤2: 从价格元素向上查找商品卡片
    console.log('[Feimao] 开始从价格元素向上查找商品卡片...')
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

    console.log(`[Feimao] 初步找到 ${items.length} 个商品容器`)

    // 步骤3: 去重
    const uniqueItems = []
    const seenCards = new Set()
    items.forEach(item => {
        if (!seenCards.has(item.card)) {
            seenCards.add(item.card)
            uniqueItems.push(item)
        }
    })

    console.log(`[Feimao] 去重后: ${uniqueItems.length} 个唯一商品卡片`)

    // 步骤4: 提取商品信息
    console.log('[Feimao] 开始提取商品信息...')
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

    console.log(`%c[Feimao] ✅ 最终提取 ${productNodes.length} 个商品节点`, 'color: #10b981; font-weight: bold')
    console.log('%c[Feimao] ========== 商品节点提取结束 ==========', 'color: #fb923c; font-weight: bold')

    return productNodes
}

// ==================== 3.5. 自动加载第一个1688货源 ====================
async function autoLoadFirstSource(productId, container) {
    try {
        console.log(`[货源] 加载商品 ${productId} 的1688货源...`)

        const temuProducts = await apiRequest('/temu/products')
        console.log('[货源] 查询商品API响应:', temuProducts)

        const productList = temuProducts?.data?.data || temuProducts?.data?.records || temuProducts?.data || []
        const temuProduct = productList.find(p => p.product_id === productId)

        if (!temuProduct) {
            console.log(`[货源] 商品 ${productId} 未找到`)
            return
        }

        console.log(`[货源] 找到商品 ID: ${temuProduct.id}`)

        const sourcesResponse = await apiRequest(`/temu/products/${temuProduct.id}/sources`)
        console.log('[货源] 获取货源API响应:', sourcesResponse)

        const sources = sourcesResponse?.data || []

        if (!Array.isArray(sources) || sources.length === 0) {
            console.log(`[货源] 暂无1688货源`)
            return
        }

        console.log(`[货源] 找到 ${sources.length} 个货源`)

        const firstSource = sources[0]
        const sourceText = container.querySelector('[data-fm="sourceText"]')
        const sourceImg = container.querySelector('[data-fm="sourceImg"]')

        if (sourceText && sourceImg) {
            sourceText.textContent = '已选货源'
            sourceText.style.color = 'rgb(22, 163, 74)'

            if (firstSource.image) {
                sourceImg.src = firstSource.image
                sourceImg.style.display = 'inline-block'
                console.log('[货源] ✅ 已显示货源')
            }
        }

    } catch (error) {
        console.log(`[货源] 加载失败:`, error.message)
    }
}

// ==================== 4. 注入商品UI ====================
function injectProductUI() {
    if (!window.location.hostname.includes('temu.com')) {
        return
    }

    console.log('%c[Feimao] 开始注入商品UI...', 'color: #8b5cf6; font-weight: bold')
    const products = extractProductNodes()

    if (products.length > 0) {
        console.log(`[Feimao] 为 ${products.length} 个商品注入UI按钮`)

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
                        console.log('[Feimao] AI按钮点击，商品ID:', product.productId)
                        handleCalculateProfit(product.productId, ui)
                    })
                }
                if (chooseBtn) {
                    chooseBtn.addEventListener('click', (e) => {
                        e.preventDefault()
                        e.stopPropagation()
                        console.log('[Feimao] 选货源按钮点击，商品ID:', product.productId)
                        handleChooseSource(product.productId, ui)
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

        console.log(`%c[Feimao] UI注入完成: ${successCount}成功, ${failCount}失败`, 'color: #10b981; font-weight: bold')

        // 提交到后端
        submitProductsToAPI(products)
    } else {
        console.log('%c[Feimao] 没有商品可注入', 'color: #fb923c')
    }
}

async function submitProductsToAPI(products) {
    try {
        console.log('[Feimao] 提交商品到后端API...')
        const productIds = products.map(p => p.productId)
        console.log('[Feimao] 商品ID列表:', productIds.slice(0, 5), productIds.length > 5 ? `...等${productIds.length}个` : '')

        const result = await apiRequest('/feimao/products', {
            method: 'POST',
            body: JSON.stringify({
                productIds,
                site_url: window.location.href
            })
        })
        console.log('[Feimao] ✅ 商品数据已提交:', result)
    } catch (error) {
        console.error('[Feimao] ❌ 提交商品数据失败:', error)
    }
}

// ==================== 5. 实时监控 ====================
let lastUrl = location.href
let mutationCount = 0

function observePageChanges() {
    console.log('[Feimao] 启动实时监控 (MutationObserver)...')

    const observer = new MutationObserver(() => {
        mutationCount++

        if (location.href !== lastUrl) {
            lastUrl = location.href
            console.log('%c[Feimao] 🔄 URL变化，重新注入', 'color: #f59e0b', location.href)
            setTimeout(injectProductUI, 1000)
        }
        else if (mutationCount % 30 === 0) {
            console.log(`[Feimao] DOM变化第${mutationCount}次，检查新商品`)
            injectProductUI()
        }
    })

    observer.observe(document.body, {
        childList: true,
        subtree: true
    })

    console.log('[Feimao] ✅ 实时监控已启动 (每30次mutation检查一次)')
}

// ==================== 6. 初始化 ====================
function init() {
    console.log('%c========================================', 'color: #4a78f5; font-weight: bold; font-size: 16px')
    console.log('%c🚀 飞猫选品采集助手 v1.3.0', 'color: #4a78f5; font-weight: bold; font-size: 16px')
    console.log('%c========================================', 'color: #4a78f5; font-weight: bold; font-size: 16px')
    console.log('[Feimao] URL:', window.location.href)
    console.log('[Feimao] 时间:', new Date().toLocaleString())

    // 1. 挂载浮动卡片
    mountFloatingCard()

    // 2. Temu页面功能
    if (window.location.hostname.includes('temu.com')) {
        console.log('[Feimao] 检测到Temu页面，启动商品UI功能')

        setTimeout(() => {
            console.log('[Feimao] 延迟2秒后开始首次注入...')
            injectProductUI()
        }, 2000)

        observePageChanges()
    } else {
        console.log('[Feimao] 非Temu页面，只挂载FloatingCard')
    }

    // 监听货源更新事件
    document.addEventListener('feimao:sources-updated', async () => {
        console.log('[Feimao] 接收到货源更新事件，开始刷新UI...')

        // 查找所有已注入的UI
        const injectedUIs = document.querySelectorAll('[data-fm-host="1"]')
        console.log(`[Feimao] 找到 ${injectedUIs.length} 个UI，开始刷新货源`)

        for (const ui of injectedUIs) {
            const productId = ui.getAttribute('data-product-id')
            if (productId) {
                await autoLoadFirstSource(productId, ui)
            }
        }

        console.log('[Feimao] ✅ 货源UI刷新完成')
    })

    console.log('%c[Feimao] ✅ 初始化完成', 'color: #10b981; font-weight: bold; font-size: 14px')
}

// 启动
if (document.readyState === 'loading') {
    console.log('[Feimao] 等待DOMContentLoaded...')
    document.addEventListener('DOMContentLoaded', init)
} else {
    init()
}
