
export function useCache() {
    // 清空插件缓存
    const handleClearExtensionCache = async () => {
        if (confirm('确定清空【Temu 采集助手】的本地采集明细缓存吗？')) {
            const keysToRemove = []
            const all = await chrome.storage.local.get(null)
            for (const key in all) {
                if (key !== 'token' && key !== 'user') {
                    keysToRemove.push(key)
                }
            }
            if (keysToRemove.length > 0) {
                await chrome.storage.local.remove(keysToRemove)
            }
            alert('插件缓存已清空')
        }
    }

    // 清除 Temu 缓存
    const handleClearTemuCache = async () => {
        if (confirm('将清除 Temu 本地缓存并刷新页面，是否继续？')) {
            chrome.runtime.sendMessage({ action: 'CLEAR_TEMU_COOKIES' }, (response) => {
                if (chrome.runtime.lastError) {
                    console.error('Clear cookie failed', chrome.runtime.lastError)
                }

                localStorage.clear()
                sessionStorage.clear()

                window.location.reload()
            })
        }
    }

    return {
        handleClearExtensionCache,
        handleClearTemuCache
    }
}
