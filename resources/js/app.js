import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { createPinia } from 'pinia';

import App from './App.vue'
import router from './router'
const app = createApp(App)
const pinia = createPinia()




app.use(pinia)
app.use(router)

app.mount('#app')
