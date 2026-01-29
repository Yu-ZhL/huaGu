import { apiRequest } from './api.js'

export function showSourceDialog(temuProduct, sources, onSourceSelected) {
    const existingDialog = document.querySelector('.fm-mask')
    if (existingDialog) {
        existingDialog.remove()
    }

    const sourcesHtml = sources.map((source, index) => `
        <tr class="${source.is_primary ? 'chosen' : ''}" data-source-id="${source.id}">
            <td class="td-center">${index + 1}</td>
            <td>
                <div class="info">
                    <img class="thumb" src="${source.image || ''}" alt="">
                    <div class="meta">
                        <div class="title" title="${source.title || ''}">${source.title || ''}</div>
                    </div>
                </div>
            </td>
            <td class="price">
                <span class="yen">¥</span><span class="num">${source.price || 0}</span>
            </td>
            <td>
                <div class="tags">
                    ${(source.tags || []).map(tag => `<span class="tag">${tag}</span>`).join('')}
                </div>
            </td>
            <td>
                <div class="ops">
                    <button class="icon-btn" type="button" title="打开1688" onclick="window.open('${source.url}', '_blank')">🔗</button>
                    <button class="use-btn ${source.is_primary ? 'primary' : ''}" type="button" data-source-id="${source.id}">
                        ${source.is_primary ? '选用✓' : '选用'}
                    </button>
                </div>
            </td>
        </tr>
    `).join('')

    const dialog = document.createElement('div')
    dialog.className = 'fm-mask'
    dialog.innerHTML = `
        <div class="fm-dialog" role="dialog" aria-modal="true">
            <div class="fm-head">
                <div class="fm-head-left">
                    <div class="fm-head-title">
                        1688货源匹配
                        <span class="fm-badge">${sources.length}货源</span>
                        <span class="fm-gid">gid: ${temuProduct.product_id}</span>
                    </div>
                </div>
                <button class="fm-close-x" type="button" aria-label="close">×</button>
            </div>
            <div class="fm-body">
                <div class="fm-table-wrap">
                    <table class="fm-table">
                        <thead>
                            <tr>
                                <th class="col-idx">序号</th>
                                <th class="col-info">匹配商品信息</th>
                                <th class="col-price">商品价格</th>
                                <th class="col-tags">标签</th>
                                <th class="col-op">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${sourcesHtml}
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="fm-foot">
                <div class="fm-hint">提示：选用成功后会刷新当前商品数据，利润会同步更新。</div>
                <button class="fm-btn" type="button">关闭</button>
            </div>
        </div>
    `

    document.body.appendChild(dialog)

    dialog.querySelector('.fm-close-x').addEventListener('click', () => dialog.remove())
    dialog.querySelector('.fm-foot .fm-btn').addEventListener('click', () => dialog.remove())
    dialog.addEventListener('click', (e) => {
        if (e.target === dialog) dialog.remove()
    })

    dialog.querySelectorAll('.use-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            const sourceId = e.target.getAttribute('data-source-id')
            await onSourceSelected(sourceId, dialog)
        })
    })
}
