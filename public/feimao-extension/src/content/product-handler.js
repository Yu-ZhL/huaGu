import { apiRequest } from './api.js'
import { showSourceDialog } from './source-dialog.js'
import { updateProductData, updateSourceDisplay } from './ui-components.js'

const collectingStates = {}

export async function handleChooseSource(productId, container) {
    const sourceLoading = container.querySelector('[data-fm="sourceLoading"]')
    const chooseBtns = container.querySelectorAll('[data-fm="chooseBtn"]')

    sourceLoading.style.display = 'inline'
    chooseBtns.forEach(btn => btn.disabled = true)

    // 1. 立即显示加载弹窗
    showSourceDialog({ product_id: productId }, null)

    // 强制 UI 渲染，防止请求卡顿导致弹窗无法显示
    await new Promise(resolve => setTimeout(resolve, 20))

    try {
        const temuProducts = await apiRequest('/temu/products')
        const temuProduct = temuProducts.data?.data?.find(p => p.product_id === productId)

        if (!temuProduct) {
            document.querySelector('.fm-mask')?.remove()
            alert('请先采集商品数据')
            return
        }

        const sources = await apiRequest(`/temu/products/${temuProduct.id}/sources`)

        if (!sources.data || sources.data.length === 0) {
            document.querySelector('.fm-mask')?.remove()
            const shouldCollect = confirm('暂无1688货源，是否开始采集？')
            if (shouldCollect) {
                await handleCollectSources(temuProduct.id, productId, container)
            }
        } else {
            showSourceDialog(temuProduct, sources.data, async (sourceId, dialog) => {
                await handleSetPrimarySource(temuProduct.id, sourceId, container, dialog)
            })
        }
    } catch (error) {
        document.querySelector('.fm-mask')?.remove()

        alert('获取货源失败')
    } finally {
        sourceLoading.style.display = 'none'
        chooseBtns.forEach(btn => btn.disabled = false)
    }
}

export async function handleCollectSources(temuProductId, productId, container) {
    if (collectingStates[productId]) {
        alert('正在采集中，请稍候')
        return
    }

    collectingStates[productId] = true
    const chooseBtns = container.querySelectorAll('[data-fm="chooseBtn"]')
    chooseBtns.forEach(btn => {
        btn.textContent = '采集中...'
        btn.disabled = true
    })

    try {
        const result = await apiRequest('/temu/products/collect-similar', {
            method: 'POST',
            body: JSON.stringify({
                product_id: temuProductId,
                search_method: 'image',
                max_count: 20
            })
        })

        if (result.success) {
            alert(`采集成功！共采集 ${result.data?.count || 0} 条同款`)
            const sources = await apiRequest(`/temu/products/${temuProductId}/sources`)
            showSourceDialog({ id: temuProductId, product_id: productId }, sources.data, async (sourceId, dialog) => {
                await handleSetPrimarySource(temuProductId, sourceId, container, dialog)
            })
        } else {
            alert(result.message || '采集失败')
        }
    } catch (error) {

        alert('采集失败')
    } finally {
        collectingStates[productId] = false
        chooseBtns.forEach(btn => {
            // 这里恢复文本需要根据当前显示的按钮来决定，或者简单统一
            // 实际上 ui-components.js 会根据是否有 source 重置 UI 结构
            // 如果采集成功，通常会更新 UI，按钮可能会变成“重选”
            // 简单起见，这里恢复为初始文本可能不太准确，但 safe bet 是恢复为“选择货源”或“重选”
            // 由于 updateSourceDisplay 会被调用（在 handleSetPrimarySource 中），UI 可能会被刷新
            // 但如果仅仅是采集成功但没有选择，UI 状态可能需要保持
            // 暂且恢复为之前的逻辑，但注意这里有两个不同文案的按钮
            // 更好的做法是只恢复 disabled 状态，因为文案是固定的（ui-components.js）
            // 但是上面的代码修改了 textContent，所以必须改回来。
            // 比较好的做法是重新读取原始文案或者按类区分，或者干脆重新 render
            // 简单处理：如果是 .fm-source-empty 下的，是“选择货源”，否则是“重选”
            if (btn.parentElement.classList.contains('fm-source-empty')) {
                btn.textContent = '选择货源'
            } else {
                btn.textContent = '重选'
            }
            btn.disabled = false
        })
    }
}

export async function handleSetPrimarySource(temuProductId, sourceId, container, dialog) {
    try {
        const result = await apiRequest('/temu/products/set-primary-source', {
            method: 'POST',
            body: JSON.stringify({
                product_id: temuProductId,
                source_id: parseInt(sourceId)
            })
        })

        if (result.success) {
            updateSourceDisplay(container, result.data)
            dialog.remove()
            alert('设置成功')
            await handleCalculateProfit(temuProductId, container, true)
        } else {
            alert(result.message || '设置失败')
        }
    } catch (error) {

        alert('设置失败')
    }
}

export async function handleCalculateProfit(productIdOrTemuId, container, isTemuId = false) {
    const aiBtn = container.querySelector('[data-fm="aiBtn"]')
    aiBtn.disabled = true
    aiBtn.textContent = '计算中...'

    try {
        let temuProductId = productIdOrTemuId

        if (!isTemuId) {
            const temuProducts = await apiRequest('/temu/products')
            const temuProduct = temuProducts.data?.data?.find(p => p.product_id === productIdOrTemuId)
            if (!temuProduct) {
                alert('请先采集商品数据')
                return
            }
            temuProductId = temuProduct.id
        }

        const result = await apiRequest('/temu/products/calculate-profit', {
            method: 'POST',
            body: JSON.stringify({
                product_id: temuProductId
            })
        })

        if (result.success) {
            updateProductData(container, result.data)
        } else {
            alert(result.message || '计算失败')
        }
    } catch (error) {

        alert('计算失败')
    } finally {
        aiBtn.disabled = false
        aiBtn.textContent = 'AI计算'
    }
}
