import { ref, reactive } from 'vue'
import { requestApi } from './useApi'

export function useAuth() {
    const isLoggedIn = ref(false)
    const userInfo = ref(null)
    const loading = ref(false)

    // 登录表单
    const loginForm = reactive({
        account: '',
        password: ''
    })

    // 获取用户信息
    const fetchUserInfo = async () => {
        try {
            const res = await requestApi('GET', '/auth/me')
            if (res && res.success && res.data) {
                userInfo.value = res.data
                await chrome.storage.local.set({ user: res.data })
            } else {
                if (res && res.status === 401) {
                    handleLogout()
                }
            }
        } catch (err) {
            console.error('Fetch me failed', err)
        }
    }

    // 检查登录状态
    const checkLoginStatus = async () => {
        const token = await chrome.storage.local.get('token')
        if (token && token.token) {
            isLoggedIn.value = true
            const localUser = await chrome.storage.local.get('user')
            if (localUser && localUser.user) {
                userInfo.value = localUser.user
            }
            await fetchUserInfo()
        }
    }

    // 登录
    const handleLogin = async () => {
        if (!loginForm.account || !loginForm.password) {
            alert('请输入账号和密码')
            return
        }

        loading.value = true
        try {
            const res = await requestApi('POST', '/auth/login', {
                phone: loginForm.account,
                password: loginForm.password
            })

            if (res && res.data && res.data.token) {
                isLoggedIn.value = true
                userInfo.value = res.data.user || { phone: loginForm.account }

                await chrome.storage.local.set({
                    token: res.data.token,
                    user: userInfo.value
                })

                alert('登录成功！')
            } else {
                console.warn('Login API response structure might differ:', res)
                alert(res.message || '登录异常，请重试')
            }
        } catch (error) {
            console.error('Login failed:', error)
            alert('登录失败，请检查账号密码')
        } finally {
            loading.value = false
        }
    }

    // 退出
    const handleLogout = async () => {
        try {
            await requestApi('POST', '/auth/logout')
        } catch (e) {
            console.warn('Logout api failed', e)
        }

        isLoggedIn.value = false
        loginForm.password = ''
        userInfo.value = null
        await chrome.storage.local.remove(['token', 'user'])
    }

    return {
        isLoggedIn,
        userInfo,
        loading,
        loginForm,
        checkLoginStatus,
        handleLogin,
        handleLogout
    }
}
