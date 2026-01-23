<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import {
    HomeIcon,
    CubeIcon,
    Cog6ToothIcon,
    ArrowLeftOnRectangleIcon
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: true
    }
})

const router = useRouter()
const authStore = useAuthStore()

const navigation = [
    { name: '商品库', href: '/', icon: CubeIcon, current: true },
    { name: '仪表盘', href: '/dashboard', icon: HomeIcon, current: false },
    { name: '设置', href: '/settings', icon: Cog6ToothIcon, current: false },
]

const handleLogout = () => {
    authStore.logout()
    router.push('/login')
}
</script>

<template>
    <div
        class="relative z-30 transition-all duration-300 ease-in-out"
        :class="[
      isOpen ? 'w-64' : 'w-20',
      'bg-blue-50 border-r border-gray-200 h-full flex flex-col'
    ]"
    >
        <div class="p-4 border-b border-gray-200">
            <div class="flex items-center space-x-2">
                <img src="/images/logo.png" alt="Logo" class="h-8 w-8" />
                <transition name="fade" mode="out-in">
                    <span v-if="isOpen" class="text-xl font-bold">飞猫选品</span>
                </transition>
            </div>
        </div>

        <nav class="flex-1 px-2 py-4 overflow-y-auto">
            <div class="space-y-1">
                <a
                    v-for="(item, index) in navigation"
                    :key="index"
                    :href="item.href"
                    @click.prevent="router.push(item.href)"
                    class="group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors"
                    :class="[
            item.current
              ? 'bg-blue-100 text-blue-700'
              : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
          ]"
                >
                    <component
                        :is="item.icon"
                        class="h-5 w-5 text-gray-500 group-hover:text-gray-600"
                        :class="item.current ? 'text-blue-500' : ''"
                    />
                    <span class="ml-3 transition-opacity duration-300" :class="{ 'opacity-0': !isOpen }">
            {{ item.name }}
          </span>
                </a>
            </div>
        </nav>

        <div class="p-4 border-t border-gray-200">
            <button
                @click="handleLogout"
                class="flex items-center w-full px-3 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-100 hover:text-gray-900 transition-colors"
            >
                <ArrowLeftOnRectangleIcon class="h-5 w-5 text-gray-500" />
                <span class="ml-3 transition-opacity duration-300" :class="{ 'opacity-0': !isOpen }">
          退出登录
        </span>
            </button>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
