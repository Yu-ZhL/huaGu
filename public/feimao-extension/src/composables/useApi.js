
// 通用请求函数，转发到 Background
const apiBase = 'http://103.214.173.247:3019/api'

export const requestApi = (method, endpoint, data = null) => {
    return new Promise((resolve, reject) => {
        chrome.runtime.sendMessage({
            action: 'API_REQUEST',
            payload: {
                method,
                url: `${apiBase}${endpoint}`,
                data
            }
        }, (response) => {
            if (chrome.runtime.lastError) {
                return reject(chrome.runtime.lastError);
            }
            if (response && response.success) {
                resolve(response.data);
            } else {
                reject(response ? response.error : 'Unknown error');
            }
        });
    });
};
