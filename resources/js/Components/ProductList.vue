<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="flex items-center justify-between px-6 py-3">
                <div class="flex items-center space-x-2">
                    <img src="/logo.png" alt="Logo" class="w-8 h-8" />
                    <span class="text-xl font-bold text-blue-600">飞猫选品</span>
                </div>

                <div class="flex items-center space-x-4">
                    <button class="px-3 py-1 text-sm rounded-full bg-gray-100 hover:bg-gray-200 transition">专属客服</button>
                    <button class="px-3 py-1 text-sm rounded-full bg-blue-500 text-white hover:bg-blue-600 transition">使用教程</button>
                    <button class="px-3 py-1 text-sm rounded-full bg-purple-500 text-white hover:bg-purple-600 transition flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 2a8 8 0 100 16 8 8 0 000-16zm0 14a6 6 0 110-12 6 6 0 010 12z" />
                            <path d="M10 6a2 2 0 100 4 2 2 0 000-4z" />
                        </svg>
                        插件下载
                    </button>
                    <div class="text-sm text-gray-600">当前版本 v1.3</div>
                    <div class="text-sm text-gray-600">未登录</div>
                    <button class="px-4 py-1 text-sm rounded-full bg-blue-600 text-white hover:bg-blue-700 transition">登录</button>
                </div>
            </div>
        </header>

        <!-- Sidebar -->
        <aside class="w-64 bg-blue-50 h-screen fixed left-0 top-16 border-r">
            <nav class="mt-6 px-4">
                <ul>
                    <li>
                        <a href="#" class="flex items-center px-4 py-3 text-blue-700 bg-blue-100 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />
                            </svg>
                            商品库
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="ml-64 pt-16">
            <div class="container mx-auto px-6 py-4">
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <!-- Top Tabs -->
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">商品库</h2>
                        <div class="flex space-x-2">
                            <button class="px-4 py-2 text-sm rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200 transition">运费定价</button>
                            <button class="px-4 py-2 text-sm rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200 transition">批量AI计算</button>
                            <button class="px-4 py-2 text-sm rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200 transition">批量取消收藏</button>
                            <button class="px-4 py-2 text-sm rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200 transition flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 2a8 8 0 100 16 8 8 0 000-16zm0 14a6 6 0 110-12 6 6 0 010 12z" />
                                </svg>
                                导出表格
                            </button>
                        </div>
                    </div>

                    <!-- Platform Tabs -->
                    <div class="flex space-x-2 mb-4">
                        <button class="px-4 py-2 text-sm rounded-full bg-blue-600 text-white">TikTok</button>
                        <button class="px-4 py-2 text-sm rounded-full bg-gray-200 text-gray-700 hover:bg-gray-300">TEMU</button>
                        <button class="px-4 py-2 text-sm rounded-full bg-gray-200 text-gray-700 hover:bg-gray-300">Aliexpress 开发中</button>
                        <button class="px-4 py-2 text-sm rounded-full bg-gray-200 text-gray-700 hover:bg-gray-300">SHEIN 开发中</button>
                        <button class="px-4 py-2 text-sm rounded-full bg-gray-200 text-gray-700 hover:bg-gray-300">Amazon 开发中</button>
                        <button class="px-4 py-2 text-sm rounded-full bg-gray-200 text-gray-700 hover:bg-gray-300">Walmart 开发中</button>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full table-auto">
                            <thead>
                            <tr class="bg-gray-50">
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">商品信息</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">商品总销量</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">店铺</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">商品价格</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">商品评价</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">商品评分</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                            <tr v-if="!isLoggedIn">
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                    <p class="text-sm font-medium">请先登录后使用商品库</p>
                                    <p class="text-xs mt-1 text-gray-400">登录后可查看列表、导出、估量、1688 匹配等功能</p>
                                    <button class="mt-3 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">去登录</button>
                                </td>
                            </tr>
                            <tr v-else v-for="item in products" :key="item.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <input type="checkbox" class="rounded text-blue-600 focus:ring-blue-500" />
                                </td>
                                <td class="px-4 py-3">{{ item.name }}</td>
                                <td class="px-4 py-3">{{ item.sales }}</td>
                                <td class="px-4 py-3">{{ item.store }}</td>
                                <td class="px-4 py-3"> $ {{ item.price }}</td>
                                <td class="px-4 py-3">{{ item.reviews }}</td>
                                <td class="px-4 py-3">{{ item.rating }}</td>
                                <td class="px-4 py-3">
                                    <button class="text-blue-600 hover:text-blue-800 text-sm">编辑</button>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="flex items-center justify-between mt-6">
                        <div class="text-sm text-gray-700">
                            共 {{ total }} 条
                        </div>
                        <div class="flex items-center space-x-2">
                            <select class="border px-2 py-1 text-sm rounded">
                                <option>10条/页</option>
                                <option>20条/页</option>
                            </select>
                            <button class="px-2 py-1 text-gray-500 hover:text-gray-700">‹</button>
                            <button class="px-3 py-1 bg-blue-600 text-white rounded">1</button>
                            <button class="px-2 py-1 text-gray-500 hover:text-gray-700">›</button>
                            <span class="text-sm text-gray-500">前往</span>
                            <input type="number" class="w-12 px-2 py-1 border rounded text-sm" value="1" />
                            <span class="text-sm text-gray-500">页</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref } from 'vue'

const isLoggedIn = ref(false)
const products = ref([])
const total = ref(0)

// 模拟数据
if (isLoggedIn.value) {
    products.value = [
        { id: 1, name: '手机壳', sales: '1000', store: 'TikTok Store', price: 9.99, reviews: '120', rating: 4.8 },
        { id: 2, name: '耳机', sales: '500', store: 'TEMU', price: 19.99, reviews: '80', rating: 4.5 }
    ]
    total.value = 2
}
</script>
