import { apiRequest } from './api.js'

export function showSourceDialog(temuProduct, sources, onSourceSelected) {
    const existingDialog = document.querySelector('.fm-mask')

    // 生成内容HTML
    let bodyContent = ''
    if (!sources) {
        // 加载中状态
        bodyContent = `
            <div style="padding: 60px 0; text-align: center; color: #94a3b8;">
                <div style="
                    width: 32px; height: 32px; 
                    border: 3px solid rgba(59, 130, 246, 0.2); 
                    border-top-color: #3b82f6; 
                    border-radius: 50%; 
                    margin: 0 auto 16px;
                    animation: fm-spin 0.8s linear infinite;
                "></div>
                <div style="font-size: 13px;">正在匹配1688优质货源...</div>
                <style>@keyframes fm-spin { to { transform: rotate(360deg); } }</style>
            </div>
        `
    } else if (sources.length === 0) {
        // 空状态
        bodyContent = `
            <div style="padding: 60px 0; text-align: center; color: #64748b;">
                <div style="font-size: 24px; margin-bottom: 10px;">📭</div>
                <div style="font-size: 13px;">暂无匹配的1688货源</div>
            </div>
        `
    } else {
        // 列表状态
        const rows = sources.map((source, index) => `
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

        bodyContent = `
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
                    <tbody>${rows}</tbody>
                </table>
            </div>
        `
    }

    // 如果弹窗已存在，直接更新内容
    if (existingDialog) {
        const bodyEl = existingDialog.querySelector('.fm-body')
        if (bodyEl) bodyEl.innerHTML = bodyContent

        // 更新头部信息
        const badgeEl = existingDialog.querySelector('.fm-badge')
        if (badgeEl && sources) badgeEl.textContent = `${sources.length}货源`

        // 重新绑定事件 (仅当有数据时)
        if (sources && sources.length > 0 && onSourceSelected) {
            // 需要克隆节点或重新查找来绑定事件吗？
            // 上面的innerHTML替换了bodyContent，所以之前的按钮都没了，需要重新绑定
            const newBodyEl = existingDialog.querySelector('.fm-body') // 重新获取因为innerHTML可能改变了引用? No, innerHTML changes content.
            // bodyEl is still valid reference to the element.
            bodyEl.querySelectorAll('.use-btn').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    const sourceId = e.target.getAttribute('data-source-id')
                    await onSourceSelected(sourceId, existingDialog)
                })
            })
        }
        return existingDialog
    }

    // 创建新弹窗
    const dialog = document.createElement('div')
    dialog.className = 'fm-mask'
    dialog.innerHTML = `
        <div class="fm-dialog" role="dialog" aria-modal="true">
            <div class="fm-head">
                <div class="fm-head-left">
                    <div class="fm-head-title">
                        1688货源匹配
                        <span class="fm-badge">${sources ? sources.length : '...'}货源</span>
                        <span class="fm-gid">gid: ${temuProduct.product_id}</span>
                    </div>
                </div>
                <button class="fm-close-x" type="button" aria-label="close">×</button>
            </div>
            <div class="fm-body">
                ${bodyContent}
            </div>
            <div class="fm-foot">
                <div class="fm-hint">提示：选用成功后会刷新当前商品数据，利润会同步更新。</div>
                <button class="fm-btn" type="button">关闭</button>
            </div>
        </div>
    `

    document.body.appendChild(dialog)

    // 绑定通用关闭事件
    const closeDialog = () => dialog.remove()
    dialog.querySelector('.fm-close-x').addEventListener('click', closeDialog)
    dialog.querySelector('.fm-foot .fm-btn').addEventListener('click', closeDialog)
    dialog.addEventListener('click', (e) => {
        if (e.target === dialog) closeDialog()
    })

    // 绑定选用事件 (仅当有数据时)
    if (sources && sources.length > 0 && onSourceSelected) {
        dialog.querySelectorAll('.use-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const sourceId = e.target.getAttribute('data-source-id')
                await onSourceSelected(sourceId, dialog)
            })
        })
    }

    return dialog
}
