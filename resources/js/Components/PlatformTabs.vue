<script setup>
import { ref } from 'vue'
import {
    CubeIcon,
    ArrowDownTrayIcon,
    CalculatorIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline'

const activePlatform = ref('tiktok')
const platforms = [
    { id: 'tiktok', name: 'TikTok', icon: CubeIcon, active: true },
    { id: 'temu', name: 'TEMU', icon: CubeIcon, active: false },
    { id: 'aliexpress', name: 'AliExpress', icon: CubeIcon, active: false, dev: true },
    { id: 'shein', name: 'SHEIN', icon: CubeIcon, active: false, dev: true },
    { id: 'amazon', name: 'Amazon', icon: CubeIcon, active: false, dev: true },
    { id: 'walmart', name: 'Walmart', icon: CubeIcon, active: false, dev: true },
]

const actions = [
    { id: 'shipping', name: '运费定价', icon: ArrowDownTrayIcon },
    { id: 'ai-calc', name: '批量AI计算', icon: CalculatorIcon },
    { id: 'cancel-fav', name: '批量取消收藏', icon: XMarkIcon },
    { id: 'export', name: '导出表格', icon: ArrowDownTrayIcon },
]
</script>

<template>
    <div class="bg-blue-600 text-white py-3 px-6 sticky top-16 z-10">
        <div class="flex flex-wrap gap-2 mb-3">
            <button
                v-for="platform in platforms"
                :key="platform.id"
                @click="activePlatform = platform.id"
                class="px-4 py-2 rounded-lg text-sm font-medium flex items-center space-x-1 transition-colors"
                :class="[
          activePlatform === platform.id
            ? 'bg-white text-blue-600'
            : 'bg-blue-700 text-white hover:bg-blue-800'
        ]"
            >
                <component :is="platform.icon" class="h-4 w-4" />
                <span>{{ platform.name }}</span>
                <span
                    v-if="platform.dev"
                    class="text-xs bg-yellow-500 text-black px-1 rounded ml-1"
                >开发中</span>
            </button>
        </div>

        <div class="flex justify-end space-x-2">
            <button
                v-for="action in actions"
                :key="action.id"
                class="px-4 py-2 bg-white text-blue-600 rounded-lg text-sm font-medium hover:bg-blue-50 transition flex items-center space-x-1"
            >
                <component :is="action.icon" class="h-4 w-4" />
                <span>{{ action.name }}</span>
            </button>
        </div>
    </div>
</template>
