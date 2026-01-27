import { ref, reactive, watch, onMounted, onUnmounted } from 'vue'

export function useItemScanner() {
    const totalCount = ref(0)
    const filteredCount = ref(0)
    let observer = null

    // 筛选条件
    const filters = reactive({
        sales: { checked: false, op: '>', val: 100 },
        price: { checked: false, op: '>', val: 33 },
        shippment: 'all',
        brand: 'all'
    })

    const scanItems = () => {
        // 通用策略：查找含价格符号的元素
        let items = Array.from(document.querySelectorAll('[data-goods-id], [data-id]'))

        items = items.filter(el => {
            return el.querySelector('img') && (
                el.textContent.includes('$') ||
                el.textContent.includes('¥') ||
                el.textContent.includes('€') ||
                el.textContent.includes('£')
            )
        })

        if (items.length === 0) {
            // items = ... fallback strategy if needed
        }

        totalCount.value = items.length

        let matchCount = 0
        items.forEach(item => {
            const text = item.textContent

            // 提取价格
            const priceMatch = text.match(/[\$¥€£]\s*(\d+(\.\d+)?)/) || text.match(/(\d+(\.\d+)?)\s*[\$¥€£]/)
            const price = priceMatch ? parseFloat(priceMatch[1]) : 0

            // 提取销量
            let sales = 0
            if (text.includes('sold') || text.includes('已售')) {
                const salesMatch = text.match(/(\d+([\.,]\d+)?)[kKwW]?\+?\s*(sold|已售)/)
                if (salesMatch) sales = parseFloat(salesMatch[1].replace(',', ''))
            }

            // 提取包邮
            const isFreeShipping = text.toLowerCase().includes('free shipping') || text.includes('包邮')

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

            if (pass) matchCount++
        })

        filteredCount.value = matchCount
    }

    const startScanning = () => {
        scanItems()
        observer = new MutationObserver(() => {
            scanItems()
        })
        observer.observe(document.body, { childList: true, subtree: true })
    }

    const stopScanning = () => {
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
        stopScanning
    }
}
