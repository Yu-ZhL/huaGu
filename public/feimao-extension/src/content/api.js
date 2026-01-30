const API_BASE_URL = 'http://103.214.173.247:3019/api'

// 获取 Token (从 chrome.storage 获取)
async function getAuthToken() {
    return new Promise((resolve) => {
        chrome.storage.local.get(['token'], (result) => {
            resolve(result.token || null)
        })
    })
}

// 统一 API 请求函数 (通过 Background 转发，解决 Mixed Content 问题)
export async function apiRequest(endpoint, options = {}) {
    const url = `${API_BASE_URL}${endpoint}`

    // 兼容 background 之前可能期待完整的 URL
    // 但 background.js 里的 API_REQUEST 只是 fetch(url)，所以这里必须给完整 URL。
    // 如果 endpoint 已经以 http 开头，就不再拼接。
    const fullUrl = endpoint.startsWith('http') ? endpoint : url;

    const token = await getAuthToken()
    const headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        ...options.headers
    }

    if (token) {
        headers['Authorization'] = `Bearer ${token}`
    }

    // 解析 body
    let bodyData = undefined
    if (options.body) {
        try {
            // 如果是字符串则解析为对象，background 负责 stringify
            bodyData = typeof options.body === 'string' ? JSON.parse(options.body) : options.body
        } catch (e) {
            console.warn('[Feimao] Body parse failed, use as is', e)
            bodyData = options.body
        }
    }

    return new Promise((resolve, reject) => {
        chrome.runtime.sendMessage({
            action: 'API_REQUEST',
            payload: {
                method: options.method || 'GET',
                url: fullUrl,
                data: bodyData,
                headers: headers
            }
        }, (response) => {
            if (chrome.runtime.lastError) {
                console.error('[Feimao] Runtime Error:', chrome.runtime.lastError)
                return reject(chrome.runtime.lastError)
            }

            // 为了直接兼容之前的 await apiRequest() 返回的数据结构
            // 如果成功，我们直接返回 response.data
            // 之前的 apiRequest 是 fetch(...).json()，返回 JSON 对象
            // 现在 background 返回的是 { success: true, data: { ... } }
            // 所以 response.data 应该就是之前 fetch(...).json() 的结果

            if (response && response.success) {
                // 如果后端返回的就是 API 响应体 (例如 { code:200, data: .... })
                // 那么 response.data 就是这个响应体
                resolve(response.data)
            } else {
                console.error('[Feimao] API Error:', response?.error, endpoint)
                reject(new Error(response?.error || `Request failed: ${endpoint}`))
            }
        })
    })
}

// 兼容导出
export { getAuthToken }
