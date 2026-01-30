import { ref, reactive, watch } from 'vue'

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
        // 查找价格元素
        let priceElements = Array.from(document.querySelectorAll('[data-type="price"]'))

        if (priceElements.length === 0) {
            priceElements = Array.from(document.querySelectorAll('[class*="price"], [class*="Price"]'))
        }

        // 如果还是没有找到，重置计数
        if (priceElements.length === 0) {
            totalCount.value = 0
            filteredCount.value = 0
            return
        }

        const items = []

        priceElements.forEach((priceEl) => {
            // 向上找商品卡片
            let card = priceEl.parentElement
            let found = false

            for (let i = 0; i < 5; i++) {
                if (!card) break
                if (card.querySelector('img')) {
                    items.push({ card, priceEl })
                    found = true
                    break
                }
                card = card.parentElement
            }

            if (!found && priceEl.parentElement) {
                items.push({ card: priceEl.parentElement.parentElement || priceEl.parentElement, priceEl })
            }
        })

        // 去重
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
            const priceMatch = priceText.match(/[\$¥€£]\s*(\d+(\.\d+)?)/) || priceText.match(/(\d+(\.\d+)?)\s*[\$¥€£]/)
            const price = priceMatch ? parseFloat(priceMatch[1]) : 0

            // 提取销量
            let sales = 0
            if (cardText.includes('sold') || cardText.includes('已售')) {
                const salesMatch = cardText.match(/(\d+([\.,]\d+)?)[kKwW]?\+?\s*(sold|已售)/)
                if (salesMatch) sales = parseFloat(salesMatch[1].replace(',', ''))
            } else if (cardText.includes('Sold')) {
                const salesMatch = cardText.match(/(\d+([\.,]\d+)?)[kKwW]?\+?\s*Sold/)
                if (salesMatch) sales = parseFloat(salesMatch[1].replace(',', ''))
            }

            // 包邮判断
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

            // 品牌筛选
            const isBrand = cardText.includes('Brand') || cardText.includes('Official') // 简单判断
            if (filters.brand !== 'all') {
                if (filters.brand === 'brand' && !isBrand) pass = false
                if (filters.brand === 'no_brand' && isBrand) pass = false
            }

            if (pass) matchCount++
        })

        filteredCount.value = matchCount
    }

    const startScanning = () => {
        isScanning.value = true

        // 首次扫描
        scanItems()

        // 监听DOM变化
        observer = new MutationObserver(() => {
            scanItems()
        })
        observer.observe(document.body, { childList: true, subtree: true })
    }

    const stopScanning = () => {
        isScanning.value = false
        if (observer) {
            observer.disconnect()
        }
    }

    watch(filters, () => {
        scanItems()
    }, { deep: true })

    // 初始扫描一次
    scanItems()

    return {
        totalCount,
        filteredCount,
        filters,
        startScanning,
        stopScanning,
        isScanning,
        scanItems
    }
}
