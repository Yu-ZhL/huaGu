console.log('Feimao Extension Background Service Worker Loaded')

// 监听安装事件
chrome.runtime.onInstalled.addListener(() => {
    console.log('Feimao Extension installed')
})

// 监听来自 Content Script / Popup 的消息，用于转发 API 请求
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === 'API_REQUEST') {
        const { method, url, data, headers } = request.payload;

        // 构建 fetch 选项
        const options = {
            method: method || 'GET',
            headers: {
                'Content-Type': 'application/json',
                ...(headers || {})
            },
            body: (method === 'POST' || method === 'PUT') ? JSON.stringify(data) : undefined
        };

        // 发起请求
        fetch(url, options)
            .then(async response => {
                let responseData;
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    responseData = await response.json();
                } else {
                    responseData = await response.text();
                }

                if (!response.ok) {
                    sendResponse({ 
                        success: false, 
                        error: responseData.message || response.statusText || 'Request failed',
                        status: response.status
                    });
                } else {
                    sendResponse({ success: true, data: responseData });
                }
            })
            .catch(error => {
                sendResponse({ success: false, error: error.message });
            });

        // 返回 true 表示我们会异步发送响应
        return true; 
    }
});
