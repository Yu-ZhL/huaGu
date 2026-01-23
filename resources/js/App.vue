<script setup>
import { ref, onMounted, watch } from 'vue'
import { useAuthStore } from './stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()
const isLoading = ref(true)
const hasError = ref(false)

onMounted(async () => {
    try {
        // 确保 authStore 初始化
        await authStore.initialize()
    } catch (error) {
        console.error('Auth store 初始化失败:', error)
        hasError.value = true
    } finally {
        isLoading.value = false
    }
})

// 监听路由错误
watch(
    () => router.currentRoute.value,
    (newRoute) => {
        if (newRoute.meta.loadError) {
            hasError.value = true
        }
    }
)
</script>

<template>
    <div id="app">
        <div v-if="isLoading" class="min-h-screen flex items-center justify-center">
            <div class="text-center">
                <div class="inline-block h-12 w-12 animate-spin rounded-full border-4 border-blue-500 border-t-transparent"></div>
                <p class="mt-4 text-gray-600">初始化应用...</p>
            </div>
        </div>

        <div v-else-if="hasError" class="min-h-screen flex items-center justify-center bg-gray-50">
            <div class="text-center p-8 bg-white rounded-lg shadow-md max-w-md mx-4">
                <div class="text-red-500 text-3xl mb-4">❌</div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">应用加载失败</h2>
                <p class="text-gray-600 mb-6">请尝试刷新页面或联系技术支持</p>
                <button
                    @click="window.location.reload()"
                    class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition"
                >
                    重新加载
                </button>
            </div>
        </div>

        <router-view v-else />
    </div>
</template>
