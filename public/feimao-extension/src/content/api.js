const API_BASE_URL = 'http://103.214.173.247:3019/api'

function getAuthToken() {
    return localStorage.getItem('feimao_auth_token')
}

function setAuthToken(token) {
    localStorage.setItem('feimao_auth_token', token)
}

export async function apiRequest(url, options = {}) {
    const token = getAuthToken()
    const headers = {
        'Content-Type': 'application/json',
        ...options.headers
    }

    if (token) {
        headers['Authorization'] = `Bearer ${token}`
    }

    const response = await fetch(`${API_BASE_URL}${url}`, {
        ...options,
        headers
    })

    return response.json()
}

export { getAuthToken, setAuthToken }
