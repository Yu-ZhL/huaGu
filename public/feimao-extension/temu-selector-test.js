// ================================================
// 飞猫插件 - Temu商品选择器调试脚本
// 复制此代码到Temu页面的Console中运行
// ================================================

console.log('=== 开始测试Temu商品选择器 ===\n')

// 测试1: 基础属性选择器
console.log('测试1: data-product-id 和 data-goods-id')
const test1 = document.querySelectorAll('[data-product-id], [data-goods-id]')
console.log(`  结果: ${test1.length} 个元素`)
if (test1.length > 0) {
    console.log('  示例元素:', test1[0])
}

// 测试2: 商品链接
console.log('\n测试2: 商品链接 a[href*="/goods.html"]')
const test2 = document.querySelectorAll('a[href*="/goods.html"]')
console.log(`  结果: ${test2.length} 个元素`)
if (test2.length > 0) {
    console.log('  示例链接:', test2[0].href)
    console.log('  示例元素:', test2[0])
}

// 测试3: 价格元素
console.log('\n测试3: 价格元素 [data-type="price"]')
const test3 = document.querySelectorAll('[data-type="price"]')
console.log(`  结果: ${test3.length} 个元素`)
if (test3.length > 0) {
    console.log('  示例价格:', test3[0].textContent)
    console.log('  示例元素:', test3[0])
}

// 测试4: 通用类名
console.log('\n测试4: 类名包含 goods, product, item')
const test4a = document.querySelectorAll('[class*="goods-card"], [class*="GoodsCard"]')
const test4b = document.querySelectorAll('[class*="product-card"], [class*="ProductCard"]')
const test4c = document.querySelectorAll('[class*="item-card"], [class*="ItemCard"]')
console.log(`  goods-card: ${test4a.length} 个`)
console.log(`  product-card: ${test4b.length} 个`)
console.log(`  item-card: ${test4c.length} 个`)

// 测试5: 所有链接
console.log('\n测试5: 所有图片+链接组合 (常见商品结构)')
const allLinks = document.querySelectorAll('a')
let productLinks = []
allLinks.forEach(link => {
    const hasImage = link.querySelector('img')
    const hasPriceNearby = link.querySelector('[data-type="price"]') || link.querySelector('[class*="price"]')
    if (hasImage && hasPriceNearby) {
        productLinks.push(link)
    }
})
console.log(`  找到 ${productLinks.length} 个可能的商品链接`)
if (productLinks.length > 0) {
    console.log('  示例:', productLinks[0])
}

// 测试6: 查找所有可能的商品容器
console.log('\n测试6: 查看页面主要容器结构')
const containers = document.querySelectorAll('[class*="list"], [class*="grid"], [class*="container"]')
console.log(`  找到 ${containers.length} 个列表/网格容器`)

// 建议
console.log('\n=== 调试建议 ===')
console.log('请查看上面哪个测试返回了数量 > 0')
console.log('然后告诉我哪个测试有结果，我会更新选择器')
console.log('\n如果所有测试都是0，请：')
console.log('1. 确保页面已完全加载（滚动到底部等待）')
console.log('2. 检查是否在商品列表页面')
console.log('3. 右键点击一个商品卡片 -> 检查元素，告诉我它的DOM结构')
