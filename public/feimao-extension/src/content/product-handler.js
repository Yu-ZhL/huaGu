import { apiRequest } from './api.js'
import { showSourceDialog } from './source-dialog.js'
import { updateProductData, updateSourceDisplay } from './ui-components.js'

const collectingStates = {}

export async function handleChooseSource(productId, container) {
    const sourceLoading = container.querySelector('[data-fm="sourceLoading"]')
    const chooseBtn = container.querySelector('[data-fm="chooseBtn"]')

    sourceLoading.style.display = 'inline'
    chooseBtn.disabled = true

    try {
        const temuProducts = await apiRequest('/temu/products')
        const temuProduct = temuProducts.data?.data?.find(p => p.product_id === productId)

        if (!temuProduct) {
            alert('请先采集商品数据')
            return
        }

        const sources = await apiRequest(`/temu/products/${temuProduct.id}/sources`)

        if (!sources.data || sources.data.length === 0) {
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
        console.error('获取货源失败', error)
        alert('获取货源失败')
    } finally {
        sourceLoading.style.display = 'none'
        chooseBtn.disabled = false
    }
}

export async function handleCollectSources(temuProductId, productId, container) {
    if (collectingStates[productId]) {
        alert('正在采集中，请稍候')
        return
    }

    collectingStates[productId] = true
    const chooseBtn = container.querySelector('[data-fm="chooseBtn"]')
    chooseBtn.textContent = '采集中...'
    chooseBtn.disabled = true

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
        console.error('采集失败', error)
        alert('采集失败')
    } finally {
        collectingStates[productId] = false
        chooseBtn.textContent = '选择货源'
        chooseBtn.disabled = false
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
        console.error('设置主货源失败', error)
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
        console.error('计算利润失败', error)
        alert('计算失败')
    } finally {
        aiBtn.disabled = false
        aiBtn.textContent = 'AI计算'
    }
}
