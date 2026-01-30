// 货源自动加载：添加详细调试
async function autoLoadFirstSource(productId, container) {
    try {
        console.log(`[货源] ==== 商品 ${productId} 开始加载1688货源 ====`)

        // 步骤1: 查询商品记录
        console.log('[货源] 步骤1: 查询后端商品记录...')
        const temuProducts = await apiRequest('/temu/products')
        console.log('[货源] API响应:', temuProducts)

        const productList = temuProducts?.data?.data || temuProducts?.data?.records || temuProducts?.data || []
        console.log(`[货源] 解析到 ${productList.length} 条商品记录`)

        const temuProduct = productList.find(p => p.product_id === productId)

        if (!temuProduct) {
            console.log(`[货源] ❌ 商品 ${productId} 未在后端找到`)
            console.log('[货源] 原因: 该商品可能还未采集')
            return
        }

        console.log(`[货源] ✅ 找到商品记录, ID: ${temuProduct.id}`)

        // 步骤2: 获取货源列表
        console.log('[货源] 步骤2: 获取1688货源列表...')
        const sourcesResponse = await apiRequest(`/temu/products/${temuProduct.id}/sources`)
        console.log('[货源] 货源API响应:', sourcesResponse)

        const sources = sourcesResponse?.data || []

        if (!Array.isArray(sources) || sources.length === 0) {
            console.log(`[货源] ⚠️  商品暂无1688货源，需要手动采集`)
            return
        }

        console.log(`[货源] ✅ 找到 ${sources.length} 个1688货源`)

        // 步骤3: 显示第一个货源
        const firstSource = sources[0]
        console.log('[货源] 第一个货源:', {
            title: firstSource.title?.substring(0, 30),
            price: firstSource.price,
            hasImage: !!firstSource.image
        })

        // 更新UI
        const sourceText = container.querySelector('[data-fm="sourceText"]')
        const sourceImg = container.querySelector('[data-fm="sourceImg"]')

        if (sourceText && sourceImg) {
            sourceText.textContent = '已选货源'
            sourceText.style.color = 'rgb(22, 163, 74)'

            if (firstSource.image) {
                sourceImg.src = firstSource.image
                sourceImg.style.display = 'inline-block'
                console.log('[货源] ✅ UI更新成功')
            }
        } else {
            console.log('[货源] ⚠️ 未找到UI元素')
        }

        console.log(`[货源] ==== 商品 ${productId} 货源加载完成 ====\n`)

    } catch (error) {
        console.error(`[货源] ❌ 加载失败:`, error)
        console.error('[货源] 错误详情:', error.message)
    }
}

console.log('[Feimao] 货源加载函数已更新，包含详细调试信息')
