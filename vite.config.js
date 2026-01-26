import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    server: {
        // 允许局域网
        host: '0.0.0.0',

        // HMR 热更�?指向虚拟域名
        hmr: {
            // host: 'huagu.test',
        },

        // 关闭轮询
        watch: {
            usePolling: false,
        }
    },
});
