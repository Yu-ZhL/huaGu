import { apiRequest } from './api.js'

export function createProductUI(product) {
    const container = document.createElement('div')
    container.setAttribute('data-fm-host', '1')
    container.setAttribute('data-product-id', product.productId)
    container.innerHTML = `
        <div class="fm-inline-box">
            <div class="fm-inline-kv">
                <span class="item"><span class="k">重量:</span> <span class="v" data-fm="weight">0.000kg</span></span>
                <span class="item"><span class="k">运费:</span> <span class="v" data-fm="freight">¥0.00</span></span>
                <span class="item"><span class="k">品牌:</span> <span class="v" data-fm="brand">否</span></span>
                <span class="item"><span class="k">利润:</span> <span class="v" data-fm="profit" style="color: rgb(107, 114, 128);">¥0.00</span></span>
                <button class="btn-mini" type="button" data-fm="aiBtn">AI计算</button>
            </div>
            <div class="fm-inline-row">
                <div class="left">
                    <span class="fm-src-loading" data-fm="sourceLoading" style="display:none">加载中...</span>
                    <img class="src-img" data-fm="sourceImg" style="display: none;">
                    <span data-fm="sourceText" style="color: rgb(107, 114, 128);">未选择货源</span>
                </div>
                <button class="btn" type="button" data-fm="chooseBtn">选择货源</button>
            </div>
        </div>
    `

    return container
}

export function updateProductData(container, data) {
    container.querySelector('[data-fm="weight"]').textContent = `${data.weight}kg`
    container.querySelector('[data-fm="freight"]').textContent = `¥${data.freight}`
    container.querySelector('[data-fm="brand"]').textContent = data.brand || '否'

    const profitEl = container.querySelector('[data-fm="profit"]')
    profitEl.textContent = `¥${data.profit}`
    profitEl.style.color = data.profit > 0 ? 'rgb(22, 163, 74)' : 'rgb(239, 68, 68)'
}

export function updateSourceDisplay(container, source) {
    const sourceText = container.querySelector('[data-fm="sourceText"]')
    const sourceImg = container.querySelector('[data-fm="sourceImg"]')

    sourceText.textContent = '已选择货源'
    sourceText.style.color = 'rgb(22, 163, 74)'

    if (source.image) {
        sourceImg.src = source.image
        sourceImg.style.display = 'inline-block'
    }
}
