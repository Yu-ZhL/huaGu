<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth.js'
import { ArrowPathIcon } from '@heroicons/vue/24/outline'
import { Link } from '@inertiajs/vue3'

const authStore = useAuthStore()
const isLoading = ref(false)
const products = ref([])
const selectedProducts = ref([])
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0
})
const itemsPerPage = ref(10)

const loadProducts = async () => {
    try {
        isLoading.value = true

        if (!authStore.isAuthenticated) {
            // 未登录状态直接返回，不请求接口
            return'';
        }

        // TODO: 对接后端 API 接口
        // const response = await axios.get('/api/products', { ... })



    } catch (error) {
        console.error('获取商品列表出错:', error)
    } finally {
        isLoading.value = false
    }
}

const toggleSelectAll = (checked) => {
    if (checked) {
        selectedProducts.value = products.value.map(p => p.id)
    } else {
        selectedProducts.value = []
    }
}

const goToPage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        pagination.value.current_page = page
        loadProducts()
    }
}

const totalPages = computed(() => {
    return Math.ceil(pagination.value.total / itemsPerPage.value)
})

onMounted(() => {
    loadProducts()
})

const pageRange = computed(() => {
    const range = []
    const start = Math.max(1, pagination.value.current_page - 2)
    const end = Math.min(totalPages.value, pagination.value.current_page + 2)

    for (let i = start; i <= end; i++) {
        range.push(i)
    }

    return range
})
</script>

<template>
    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        <div v-if="!authStore.isAuthenticated" class="py-12 text-center text-gray-500">
            <div class="text-lg font-medium mb-2">请先登录后使用商品库</div>
            <div class="text-gray-400 mb-6">登录后可查看列表、导出、估量、1688匹配等功能</div>
            <Link
                href="/login"
                as="button"
                class="px-6 py-3 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition flex items-center mx-auto"
            >
                <ArrowPathIcon class="h-4 w-4 mr-2" />
                去登录
            </Link>
        </div>

        <div v-else>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input
                                type="checkbox"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                @change="toggleSelectAll( $event.target.checked)"
                            />
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">商品信息</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">商品总销量</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">店铺</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">商品价格</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">商品评价</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">商品评分</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white" v-if="products.length > 0">
                    <tr v-for="product in products" :key="product.id">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input
                                type="checkbox"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                :checked="selectedProducts.includes(product.id)"
                                @change=" $event.target.checked ? selectedProducts.push(product.id) : selectedProducts = selectedProducts.filter(id => id !== product.id)"
                            />
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <img class="h-10 w-10 rounded-md object-cover" :src="product.image" :alt="product.name" />
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 line-clamp-2">{{ product.name }}</div>
                                    <div class="text-xs text-gray-500 mt-1 flex items-center">
                                        <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full text-[10px] mr-2">精选</span>
                                        {{ product.platform }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ product.sales }}</div>
                            <div class="text-xs text-gray-500">近30天</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ product.shop }}</div>
                            <div class="text-xs text-gray-500">{{ product.shopRating }}★</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"> $ {{ product.price }}</div>
                            <div class="text-xs text-gray-500">利润:  $ {{ product.profit }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ product.reviews }}</div>
                            <div class="text-xs text-gray-500">好评率: {{ product.positiveRate }}%</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="text-yellow-400 flex">
                                    <svg v-for="i in 5" :key="i" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                </div>
                                <span class="ml-1 text-sm font-medium text-gray-900">{{ product.rating }}/5</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button class="text-blue-600 hover:text-blue-900 mr-3">估价</button>
                            <button class="text-green-600 hover:text-green-900 mr-3">收藏</button>
                            <button class="text-gray-600 hover:text-gray-900">详情</button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="products.length === 0 && !isLoading" class="py-12 text-center text-gray-500">
                <div class="text-sm">暂无商品数据</div>
            </div>

            <div v-if="isLoading" class="py-12 text-center">
                <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-blue-500 border-t-transparent"></div>
            </div>

            <!-- 分页 -->
            <div class="flex items-center justify-between px-6 py-4 bg-gray-50 border-t">
                <div class="text-sm text-gray-700">
                    共 <span class="font-medium">{{ pagination.total }}</span> 条
                </div>
                <div class="flex items-center space-x-2">
                    <select
                        v-model="itemsPerPage"
                        @change="loadProducts"
                        class="text-sm border border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option :value="10">10条/页</option>
                        <option :value="20">20条/页</option>
                        <option :value="50">50条/页</option>
                    </select>
                    <button
                        class="px-2 py-1 text-gray-500 hover:text-gray-700 rounded hover:bg-gray-100"
                        :disabled="pagination.current_page === 1"
                        @click="goToPage(pagination.current_page - 1)"
                    >&lt;</button>

                    <template v-if="totalPages <= 7">
                        <button
                            v-for="page in totalPages"
                            :key="page"
                            @click="goToPage(page)"
                            class="px-3 py-1 rounded text-sm font-medium"
                            :class="[
                pagination.current_page === page
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-700 hover:bg-gray-100'
              ]"
                        >{{ page }}</button>
                    </template>
                    <template v-else>
                        <button
                            v-for="page in pageRange"
                            :key="page"
                            @click="goToPage(page)"
                            class="px-3 py-1 rounded text-sm font-medium"
                            :class="[
                pagination.current_page === page
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-700 hover:bg-gray-100'
              ]"
                        >{{ page }}</button>
                        <span v-if="pagination.current_page > 4" class="px-2">...</span>
                        <button
                            v-if="pagination.current_page < totalPages - 3"
                            @click="goToPage(totalPages)"
                            class="px-3 py-1 rounded text-sm font-medium text-gray-700 hover:bg-gray-100"
                        >{{ totalPages }}</button>
                    </template>

                    <button
                        class="px-2 py-1 text-gray-500 hover:text-gray-700 rounded hover:bg-gray-100"
                        :disabled="pagination.current_page === pagination.last_page"
                        @click="goToPage(pagination.current_page + 1)"
                    >&gt;</button>
                    <span class="text-sm text-gray-500">前往</span>
                    <input
                        type="number"
                        class="w-12 text-sm border border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        v-model.number="pagination.current_page"
                        @change="goToPage(Math.min(Math.max(1, pagination.current_page), pagination.last_page))"
                        min="1"
                        :max="pagination.last_page"
                    />
                    <span class="text-sm text-gray-500">页</span>
                </div>
            </div>
        </div>
    </div>
</template>


