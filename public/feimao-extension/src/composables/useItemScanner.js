import { ref, reactive, watch, onMounted, onUnmounted } from 'vue'

export function useItemScanner() {
    const totalCount = ref(0)
    const filteredCount = ref(0)
    const isScanning = ref(false)
    let observer = null

    // 筛选条件
    const filters = reactive({
        sales: { checked: false, op: '>', val: 100 },
        price: { checked: false, op: '>', val: 33 },
        shippment: 'all',
        brand: 'all'
    })

    const scanItems = () => {
        // 策略更新：根据 demo.html 分析，Temu 价格元素通常带有 data-type="price"
        // 这是一个非常强烈的特征
        let priceElements = Array.from(document.querySelectorAll('[data-type="price"]'))

        // 如果没有找到 data-type="price"，尝试回退到通用寻找
        if (priceElements.length === 0) {
            priceElements = Array.from(document.querySelectorAll('[class*="price"], [class*="Price"]'))
        }

        // 以价格元素为锚点，向上查找商品卡片
        // 通常价格是商品卡片的一部分
        const items = []

        priceElements.forEach(priceEl => {
            // 向上寻找合适的卡片容器
            // 启发式：向上找 3-5 层，或者找包含 img 的最近父级
            let card = priceEl.parentElement
            let found = false
            // 往上找5层
            for (let i = 0; i < 5; i++) {
                if (!card) break
                // 如果该容器包含 img，且不是 priceEl 自己，大概率是卡片
                if (card.querySelector('img')) {
                    items.push({ card, priceEl })
                    found = true
                    break
                }
                card = card.parentElement
            }
            // 如果没找到含图片的父级，就姑且把 priceEl 的几层父级当作卡片处理
            if (!found && priceEl.parentElement) {
                items.push({ card: priceEl.parentElement.parentElement || priceEl.parentElement, priceEl })
            }
        })

        // 去重 (可能多个价格对应同一个卡片)
        const uniqueItems = []
        const seenCards = new Set()
        items.forEach(item => {
            if (!seenCards.has(item.card)) {
                seenCards.add(item.card)
                uniqueItems.push(item)
            }
        })

        totalCount.value = uniqueItems.length

        let matchCount = 0
        uniqueItems.forEach(({ card, priceEl }) => {
            const cardText = card.textContent
            const priceText = priceEl.textContent

            // 提取价格
            // 优先从 priceEl提取，更精准
            const priceMatch = priceText.match(/[\$¥€£]\s*(\d+(\.\d+)?)/) || priceText.match(/(\d+(\.\d+)?)\s*[\$¥€£]/)
            const price = priceMatch ? parseFloat(priceMatch[1]) : 0

            // 提取销量 (e.g. "100+ sold", "1万+ 已售")
            // 在整个卡片文本里找
            let sales = 0
            if (cardText.includes('sold') || cardText.includes('已售')) {
                const salesMatch = cardText.match(/(\d+([\.,]\d+)?)[kKwW]?\+?\s*(sold|已售)/)
                if (salesMatch) sales = parseFloat(salesMatch[1].replace(',', ''))
            } else if (cardText.includes('Sold')) { // 处理大小写
                const salesMatch = cardText.match(/(\d+([\.,]\d+)?)[kKwW]?\+?\s*Sold/)
                if (salesMatch) sales = parseFloat(salesMatch[1].replace(',', ''))
            }

            // 提取包邮
            const isFreeShipping = cardText.toLowerCase().includes('free shipping') || cardText.includes('包邮')

            let pass = true

            // 价格筛选
            if (filters.price.checked) {
                const val = parseFloat(filters.price.val) || 0
                if (filters.price.op === '>') pass = pass && (price > val)
                else pass = pass && (price < val)
            }

            // 销量筛选
            if (filters.sales.checked) {
                const val = parseFloat(filters.sales.val) || 0
                if (filters.sales.op === '>') pass = pass && (sales > val)
                else pass = pass && (sales < val)
            }

            // 包邮筛选
            if (filters.shippment !== 'all') {
                if (filters.shippment === 'free' && !isFreeShipping) pass = false
                if (filters.shippment === 'paid' && isFreeShipping) pass = false
            }

            // 品牌筛选 (简单通过文本判断，不一定准)
            // 这里的逻辑通常需要特定 selector，现阶段只能略过或基于关键词

            if (pass) matchCount++
        })

        filteredCount.value = matchCount
    }

    const startScanning = () => {
        isScanning.value = true
        scanItems()
        observer = new MutationObserver(() => {
            scanItems()
        })
        observer.observe(document.body, { childList: true, subtree: true })
    }

    const stopScanning = () => {
        isScanning.value = false
        if (observer) observer.disconnect()
    }

    watch(filters, () => {
        scanItems()
    }, { deep: true })

    return {
        totalCount,
        filteredCount,
        filters,
        startScanning,
        stopScanning,
        isScanning
    }
}
