
// 通用 API 请求处理，通过 background script 转发
const apiBase = 'http://103.214.173.247:3019/api'

export const requestApi = (method, endpoint, data = null) => {
    return new Promise((resolve, reject) => {
        // 使用 runtime 在 background 发送请求，避免 CORS 问题
        chrome.runtime.sendMessage({
            action: 'API_REQUEST',
            payload: {
                method,
                url: `${apiBase}${endpoint}`,
                data
            }
        }, (response) => {
            if (chrome.runtime.lastError) {
                // 如果消息发送本身失败（例如扩展环境失效）
                return reject(chrome.runtime.lastError);
            }
            if (response && response.success) {
                resolve(response.data);
            } else {
                reject(response ? response.error : '未知错误');
            }
        });
    });
};
