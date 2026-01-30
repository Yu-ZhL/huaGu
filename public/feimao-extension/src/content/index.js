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
    let priceElements = Array.from(document.querySelectorAll('[data-type="price"]'))

    if (priceElements.length === 0) {
        priceElements = Array.from(document.querySelectorAll('[class*="price"], [class*="Price"]'))
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

        // 如果没有提供DB ID，则需要先查询
        if (!targetDbId) {
            const temuProducts = await apiRequest('/temu/products')

            const productList = temuProducts?.data?.data || temuProducts?.data?.records || temuProducts?.data || []
            const temuProduct = productList.find(p => p.product_id === productId)

            if (!temuProduct) {
                return
            }
            targetDbId = temuProduct.id
        }

        const sourcesResponse = await apiRequest(`/temu/products/${targetDbId}/sources`)

        const sources = sourcesResponse?.data || []

        if (!Array.isArray(sources) || sources.length === 0) {
            updateSourceDisplay(container, null) // 确保重置为“未选择”
            return
        }

        // 直接委派给 ui-components 处理显示逻辑
        updateSourceDisplay(container, sources[0])

    } catch (error) {
        console.error(`[货源debug] ❌ 加载异常:`, error)
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
