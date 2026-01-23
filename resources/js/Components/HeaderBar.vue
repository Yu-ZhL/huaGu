<script setup>
import { ref } from 'vue'
import { MagnifyingGlassIcon, BellIcon, Cog6ToothIcon } from '@heroicons/vue/24/outline'

defineEmits(['toggleSidebar'])
</script>

<template>
    <header class="bg-white shadow-sm border-b sticky top-0 z-20">
        <div class="flex items-center justify-between px-6 py-3">
            <div class="flex items-center">
                <button @click=" $ emit('toggleSidebar')" class="mr-4 text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <h1 class="text-lg font-semibold text-gray-900">商品库</h1>
            </div>

            <div class="flex items-center space-x-4">
                <div class="relative">
                    <MagnifyingGlassIcon class="h-5 w-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" />
                    <input
                        type="text"
                        placeholder="搜索商品..."
                        class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>

                <button class="px-3 py-1 text-xs bg-gray-200 hover:bg-gray-300 rounded-full transition">专属客服</button>
                <button class="px-3 py-1 text-xs bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-full transition">使用教程</button>
                <button class="px-3 py-1 text-xs bg-purple-600 hover:bg-purple-700 text-white rounded-full transition">插件下载</button>

                <div class="flex items-center space-x-2">
                    <span class="text-xs text-gray-500">当前版本 v1.3</span>
                </div>

                <div class="flex items-center space-x-3">
                    <button class="relative text-gray-500 hover:text-gray-700">
                        <BellIcon class="h-5 w-5" />
                        <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-500"></span>
                    </button>
                    <button class="relative text-gray-500 hover:text-gray-700">
                        <Cog6ToothIcon class="h-5 w-5" />
                    </button>
                    <div class="flex items-center space-x-2 ml-2">
                        <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center text-gray-600 font-medium">
                            U
                        </div>
                        <span class="text-sm font-medium">未登录</span>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>
