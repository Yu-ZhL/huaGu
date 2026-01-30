import { apiRequest } from './api.js'

export function createProductUI(product) {
    const container = document.createElement('div')

    // 容器类名 (CSS中定义样式)
    container.className = 'fm-ui fm-card-injected'
    container.setAttribute('data-product-id', product.productId)
    container.setAttribute('data-fm-host', '1')

    // 关键修复：阻止事件冒泡，防止点击插件误触商品详情跳转
    container.addEventListener('click', (e) => {
        e.preventDefault()
        e.stopPropagation()
        return false
    })

    // 阻止鼠标按下事件，防止拖拽等其他意外行为
    container.addEventListener('mousedown', (e) => {
        e.stopPropagation()
    })

    container.innerHTML = `
        <!-- 第一行：所有指标在一行 -->
        <div class="fm-row-metrics">
            <span class="fm-metric">重量: <b class="fm-val-red" data-fm="weight">--</b></span>
            <span class="fm-metric">运费: <b class="fm-val-red" data-fm="freight">--</b></span>
            <span class="fm-metric">品牌: <b class="fm-val-red" data-fm="brand">否</b></span>
            <span class="fm-metric">利润: <b class="fm-val-green" data-fm="profit">--</b></span>
        </div>

        <!-- 第二行：AI按钮 靠右 -->
        <div class="fm-row-action">
            <button data-fm="aiBtn" class="fm-btn-ai">AI计算</button>
        </div>

        <!-- 第三行：货源选择框 (淡蓝背景) -->
        <div class="fm-row-source">
            <!-- 未选择状态 -->
            <div data-fm="sourcePlaceholder" class="fm-source-empty">
                <span class="fm-text-placeholder">未选择货源</span>
                <button data-fm="chooseBtn" class="fm-btn-choose">选择货源</button>
            </div>

            <!-- 加载状态 -->
            <div data-fm="sourceLoading" class="hidden" style="color: #3b82f6; font-size: 12px; padding: 4px;">
                正在搜索货源...
            </div>

            <!-- 已选择状态 (结构隐藏，选中后显示) -->
            <div data-fm="sourceContent" class="hidden fm-source-content">
                <div class="fm-thumb-wrapper">
                     <img data-fm="sourceImgThumb" class="fm-img-sm">
                     <div class="fm-img-hover"><img data-fm="sourceImgLarge"></div>
                </div>
                <div class="fm-source-details">
                    <div data-fm="sourceTitle" class="fm-text-title"></div>
                    <div data-fm="sourcePrice" class="fm-text-price"></div>
                </div>
                <button data-fm="chooseBtn" class="fm-btn-choose">重选</button>
            </div>
        </div>
    `

    return container
}

export function updateProductData(container, data) {
    const setVal = (sel, val, colorClass) => {
        const el = container.querySelector(sel)
        if (el) {
            el.textContent = val
            // Reset classes
            el.className = ''
            if (colorClass) el.classList.add(colorClass)
        }
    }

    setVal('[data-fm="weight"]', data.weight ? `${data.weight}kg` : '--', 'fm-val-red')
    setVal('[data-fm="freight"]', data.freight ? `¥${data.freight}` : '--', 'fm-val-red')
    setVal('[data-fm="brand"]', data.brand || '否', 'fm-val-red')

    // Profit logic
    const pEl = container.querySelector('[data-fm="profit"]')
    if (pEl) {
        if (data.profit && data.profit !== '--') {
            pEl.textContent = `¥${data.profit}`
            if (Number(data.profit) > 0) {
                pEl.className = 'fm-val-green' // Green for profit
            } else {
                pEl.className = 'fm-val-red'   // Red for loss
            }
        } else {
            pEl.textContent = '--'
            pEl.className = 'fm-val-green'
        }
    }
}

export function updateSourceDisplay(container, source) {
    const loading = container.querySelector('[data-fm="sourceLoading"]')
    const placeholder = container.querySelector('[data-fm="sourcePlaceholder"]')
    const content = container.querySelector('[data-fm="sourceContent"]')
    const title = container.querySelector('[data-fm="sourceTitle"]')
    const price = container.querySelector('[data-fm="sourcePrice"]')
    const thumb = container.querySelector('[data-fm="sourceImgThumb"]')
    const large = container.querySelector('[data-fm="sourceImgLarge"]')

    if (loading) loading.classList.add('hidden')

    if (!source) {
        if (placeholder) placeholder.style.display = 'flex'
        if (content) content.classList.add('hidden')
        return
    }

    if (placeholder) placeholder.style.display = 'none'
    if (content) {
        content.classList.remove('hidden')
        content.style.display = 'flex'
    }

    if (title) {
        title.textContent = source.title || '未命名'
        title.title = source.title || ''
    }

    if (price) price.textContent = source.price ? `¥${source.price}` : ''

    if (source.image) {
        let url = source.image.startsWith('http:') ? source.image.replace('http:', 'https:') : (source.image.startsWith('//') ? 'https:' + source.image : source.image)
        if (thumb) thumb.src = url
        if (large) large.src = url
    }
}
