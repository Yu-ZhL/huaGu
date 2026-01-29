import '../style.css'
import { apiRequest } from './api.js'
import { createProductUI } from './ui-components.js'
import { handleChooseSource, handleCalculateProfit } from './product-handler.js'

function isTemuProductPage() {
    return window.location.hostname.includes('temu.com')
}

function extractProductNodes() {
    const productNodes = document.querySelectorAll('[data-product-id], [data-goods-id]')
    const products = []

    productNodes.forEach(node => {
        const productId = node.getAttribute('data-product-id') || node.getAttribute('data-goods-id')
        if (productId && !node.querySelector('[data-fm-host="1"]')) {
            products.push({
                node,
                productId,
                data: {
                    productId,
                    title: node.querySelector('[class*="title"]')?.textContent?.trim(),
                    price: node.querySelector('[class*="price"]')?.textContent?.trim(),
                    image: node.querySelector('img')?.src,
                }
            })
        }
    })

    return products
}

async function submitProductsToAPI(products) {
    try {
        const productIds = products.map(p => p.productId)
        const siteUrl = window.location.href

        const result = await apiRequest('/feimao/products', {
            method: 'POST',
            body: JSON.stringify({
                productIds,
                site_url: siteUrl
            })
        })

        console.log('商品数据已提交', result)
    } catch (error) {
        console.error('提交商品数据失败', error)
    }
}

function injectUI() {
    if (!isTemuProductPage()) {
        return
    }

    const products = extractProductNodes()

    if (products.length > 0) {
        products.forEach(product => {
            const ui = createProductUI(product)

            const aiBtn = ui.querySelector('[data-fm="aiBtn"]')
            const chooseBtn = ui.querySelector('[data-fm="chooseBtn"]')

            aiBtn.addEventListener('click', () => handleCalculateProfit(product.productId, ui))
            chooseBtn.addEventListener('click', () => handleChooseSource(product.productId, ui))

            product.node.appendChild(ui)
        })

        submitProductsToAPI(products)
    }
}

let lastUrl = location.href
function observePageChanges() {
    const observer = new MutationObserver(() => {
        if (location.href !== lastUrl) {
            lastUrl = location.href
            setTimeout(injectUI, 1000)
        } else {
            injectUI()
        }
    })

    observer.observe(document.body, {
        childList: true,
        subtree: true
    })
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(injectUI, 1000)
        observePageChanges()
    })
} else {
    setTimeout(injectUI, 1000)
    observePageChanges()
}
