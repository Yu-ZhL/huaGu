<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { ArrowLeftOnRectangleIcon } from '@heroicons/vue/24/outline'

const router = useRouter()
const authStore = useAuthStore()
const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

const handleSubmit = async () => {
    try {
        loading.value = true
        error.value = ''

        await authStore.login({ email: email.value, password: password.value })

        // 登录成功，跳转到商品库
        router.push('/')
    } catch (err) {
        error.value = err.response?.data?.message || '登录失败，请检查用户名和密码'
    } finally {
        loading.value = false
    }
}

const handleDemoLogin = async () => {
    email.value = 'demo@example.com'
    password.value = 'password'
    await handleSubmit()
}
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-xl shadow-lg">
            <div>
                <div class="flex justify-center">
                    <img class="h-12 w-auto" src="/images/logo.png" alt="飞猫选品" />
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">飞猫选品</h2>
                <p class="mt-2 text-center text-sm text-gray-600">跨境选品工具</p>
            </div>

            <div v-if="error" class="p-3 bg-red-50 text-red-700 rounded-lg text-sm">
                {{ error }}
            </div>

            <form class="mt-8 space-y-6" @submit.prevent="handleSubmit">
                <div class="rounded-md shadow-sm space-y-4">
                    <div>
                        <label for="email-address" class="sr-only">邮箱地址</label>
                        <input
                            id="email-address"
                            name="email"
                            type="email"
                            autocomplete="email"
                            required
                            v-model="email"
                            class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            placeholder="邮箱地址"
                        />
                    </div>
                    <div>
                        <label for="password" class="sr-only">密码</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            required
                            v-model="password"
                            class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            placeholder="密码"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input
                            id="remember-me"
                            name="remember-me"
                            type="checkbox"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        />
                        <label for="remember-me" class="ml-2 block text-sm text-gray-900">记住我</label>
                    </div>
                    <div class="text-sm">
                        <a href="#" class="font-medium text-blue-600 hover:text-blue-500">忘记密码?</a>
                    </div>
                </div>

                <div>
                    <button
                        type="submit"
                        :disabled="loading"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors disabled:opacity-50"
                    >
                        <span v-if="!loading">登录</span>
                        <span v-else class="flex items-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              登录中...
            </span>
                    </button>
                </div>

                <div class="text-center">
                    <button
                        type="button"
                        @click="handleDemoLogin"
                        :disabled="loading"
                        class="text-sm text-blue-600 hover:text-blue-500 flex items-center mx-auto"
                    >
                        <ArrowLeftOnRectangleIcon class="h-4 w-4 mr-1" />
                        使用演示账号登录
                    </button>
                </div>
            </form>

            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">没有账号?</span>
                    </div>
                </div>

                <div class="mt-6">
                    <a
                        href="#"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-blue-600 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        立即注册
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
