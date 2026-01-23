<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import Sidebar from '@/components/Sidebar.vue'
import HeaderBar from '@/components/HeaderBar.vue'
import PlatformTabs from '@/components/PlatformTabs.vue'

const authStore = useAuthStore()
const isSidebarOpen = ref(true)

onMounted(() => {
    authStore.initialize()
})
</script>

<template>
    <div class="flex h-screen bg-gray-100">
        <!-- 侧边栏 -->
        <Sidebar :is-open="isSidebarOpen" />

        <!-- 主内容区 -->
        <div class="flex flex-col flex-1 overflow-hidden">
            <!-- 顶部导航 -->
            <HeaderBar @toggle-sidebar="isSidebarOpen = !isSidebarOpen" />

            <!-- 平台标签 -->
            <PlatformTabs />

            <!-- 页面内容 -->
            <main class="flex-1 overflow-y-auto p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
