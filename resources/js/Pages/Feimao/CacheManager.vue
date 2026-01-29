<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';

const props = defineProps({
    cacheItems: {
        type: Array,
        required: true
    }
});

const tableData = ref([]);
let timer = null;

// 初始化数据，附加响应式的 TTL 字段
const initData = () => {
    tableData.value = props.cacheItems.map(item => ({
        ...item,
        current_ttl: item.ttl
    }));
};

// 开启实时倒计时
const startTimer = () => {
    timer = setInterval(() => {
        tableData.value.forEach(item => {
            if (item.current_ttl > 0) {
                item.current_ttl--;
            }
        });
    }, 1000);
};

onMounted(() => {
    initData();
    startTimer();
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});

// TTL 格式化工具
const formatTTL = (ttl) => {
    if (ttl === -1) return '永久有效';
    if (ttl === -2) return '已过期';
    
    const hours = Math.floor(ttl / 3600);
    const minutes = Math.floor((ttl % 3600) / 60);
    const seconds = ttl % 60;
    
    let str = '';
    if (hours > 0) str += `${hours}小时 `;
    if (minutes > 0) str += `${minutes}分 `;
    str += `${seconds}秒`;
    return str;
};

const handleDelete = (row) => {
    ElMessageBox.confirm(
        '确定要删除该缓存吗？此操作无法撤销。',
        '警告',
        {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning',
        }
    ).then(() => {
        router.delete(route('feimao.cache.destroy'), {
            data: { key: row.key },
            onSuccess: () => {
                ElMessage.success('删除成功');
            }
        });
    }).catch(() => {});
};

const handleRefresh = (row) => {
    router.post(route('feimao.cache.refresh'), { key: row.key }, {
        onSuccess: () => {
            ElMessage.success('刷新成功');
        },
        onError: () => {
            ElMessage.error('刷新失败');
        }
    });
};

// 监听 props 变化（例如操作后后端返回新数据）
import { watch } from 'vue';
watch(() => props.cacheItems, (newVal) => {
    tableData.value = newVal.map(item => ({
        ...item,
        current_ttl: item.ttl
    }));
});

</script>

<template>
    <Head title="Feimao Cache Manager" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                飞猫接口缓存管理 (Feimao Cache Manager)
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg p-6">
                    
                    <div class="mb-4 flex justify-between items-center">
                        <span class="text-gray-500 text-sm">
                            实时监控中... (共 {{ tableData.length }} 项)
                        </span>
                        <el-button type="primary" @click="router.reload()">
                            手动刷新列表
                        </el-button>
                    </div>

                    <el-table :data="tableData" style="width: 100%" stripe border>
                        <el-table-column label="缓存类型" prop="type" width="180">
                            <template #default="scope">
                                <el-tag :type="scope.row.type === '身份令牌' ? 'warning' : 'success'">
                                    {{ scope.row.type }}
                                </el-tag>
                            </template>
                        </el-table-column>
                        
                        <el-table-column label="键名 (Redis Key)" prop="key" min-width="250">
                            <template #default="scope">
                                <span class="font-mono text-xs">{{ scope.row.key }}</span>
                            </template>
                        </el-table-column>

                        <el-table-column label="有效期 (TTL)" width="200">
                            <template #default="scope">
                                <div class="flex items-center">
                                    <span :class="{'text-red-500 font-bold': scope.row.current_ttl < 60 && scope.row.current_ttl > 0}">
                                        {{ formatTTL(scope.row.current_ttl) }}
                                    </span>
                                </div>
                            </template>
                        </el-table-column>

                        <el-table-column label="操作" width="200" align="right">
                            <template #default="scope">
                                <el-button 
                                    size="small" 
                                    type="primary" 
                                    @click="handleRefresh(scope.row)"
                                >
                                    刷新
                                </el-button>
                                <el-button 
                                    size="small" 
                                    type="danger" 
                                    @click="handleDelete(scope.row)"
                                >
                                    删除
                                </el-button>
                            </template>
                        </el-table-column>
                    </el-table>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
