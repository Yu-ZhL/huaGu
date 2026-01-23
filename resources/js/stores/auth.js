import { defineStore } from 'pinia'
import axios from 'axios'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('auth_token') || null,
    }),
    getters: {
        isAuthenticated: (state) => !!state.token,
        currentUser: (state) => state.user
    },
    actions: {
        async login(credentials) {
            const response = await axios.post('/api/login', credentials)
            this.token = response.data.access_token
            this.user = response.data.user
            localStorage.setItem('auth_token', this.token)
            axios.defaults.headers.common['Authorization'] = `Bearer  $ {this.token}`
        },
        logout() {
            this.user = null
            this.token = null
            localStorage.removeItem('auth_token')
            delete axios.defaults.headers.common['Authorization']
        },
        initialize() {
            if (this.token) {
                axios.defaults.headers.common['Authorization'] = `Bearer  $ {this.token}`
            }
        }
    }
})
