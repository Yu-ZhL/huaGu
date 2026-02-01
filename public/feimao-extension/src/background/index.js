console.log('Feimao Extension Background Service Worker Loaded')

// 监听安装事件
chrome.runtime.onInstalled.addListener(() => {
    console.log('Feimao Extension installed')
})

// 监听来自 Content Script / Popup 的消息，用于转发 API 请求
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === 'CLEAR_TEMU_COOKIES') {
        const domains = ['.temu.com', '.temu.net', 'www.temu.com', 'www.temu.net'];

        chrome.cookies.getAll({}, (cookies) => {
            let pending = 0;
            cookies.forEach(cookie => {
                if (domains.some(domain => cookie.domain.endsWith(domain))) {
                    pending++;
                    const protocol = cookie.secure ? 'https:' : 'http:';
                    const url = `${protocol}//${cookie.domain}${cookie.path}`;
                    chrome.cookies.remove({
                        url: url,
                        name: cookie.name,
                        storeId: cookie.storeId
                    }, () => {
                        pending--;
                        if (pending <= 0) {
                            sendResponse({ success: true });
                        }
                    });
                }
            });
            if (pending === 0) {
                sendResponse({ success: true });
            }
        });
        return true;
    }

    if (request.action === 'FETCH_IMAGE_BASE64') {
        fetch(request.url)
            .then(response => response.blob())
            .then(blob => {
                const reader = new FileReader();
                reader.onloadend = () => sendResponse({ success: true, data: reader.result });
                reader.readAsDataURL(blob);
            })
            .catch(error => sendResponse({ success: false, error: error.message }));
        return true;
    }

    if (request.action === 'API_REQUEST') {
        const { method, url, data, headers } = request.payload;
        const options = {
            method: method || 'GET',
            headers: {
                'Content-Type': 'application/json',
                ...(headers || {})
            },
            body: (method === 'POST' || method === 'PUT') ? JSON.stringify(data) : undefined
        };

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
        return true;
    }

    if (request.action === 'CAPTURE_VISIBLE_TAB') {
        chrome.tabs.captureVisibleTab(null, { format: 'jpeg', quality: 90 }, (dataUrl) => {
            if (chrome.runtime.lastError) {
                sendResponse({ success: false, error: chrome.runtime.lastError.message });
            } else {
                sendResponse({ success: true, data: dataUrl });
            }
        });
        return true;
    }
});
