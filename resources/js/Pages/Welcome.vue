<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="华谷" />

    <div class="min-h-screen bg-gray-100 flex flex-col items-center justify-center">
        <el-card class="w-full max-w-md text-center shadow-lg">
            <template #header>
                <h1 class="text-2xl font-bold text-blue-600">华谷系统</h1>
            </template>

            <div class="py-8">
                <p class="text-gray-600 mb-6">测试提交</p>

                <div v-if="canLogin" class="space-y-4">
                    <div v-if="$page.props.auth.user">
                        <Link :href="route('dashboard')">
                            <el-button type="primary" size="large" class="w-full">
                                进入控制台 (Dashboard)
                            </el-button>
                        </Link>
                    </div>

                    <div v-else class="flex flex-col gap-3">
                        <Link :href="route('login')">
                            <el-button type="primary" size="large" class="w-full">
                                立即登录
                            </el-button>
                        </Link>

                        <div v-if="canRegister">
                            <Link :href="route('register')">
                                <el-button size="large" class="w-full">
                                    注册新账号
                                </el-button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </el-card>

        <div class="mt-8 text-sm text-gray-500">
            &copy; 2026 HuaGu Logistics. All rights reserved.
        </div>
    </div>
</template>

<style scoped>
</style>
