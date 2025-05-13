import './bootstrap';
import '../css/app.css';
import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";
import axios from 'axios';

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import ScrollToTop from '@/Components/ScrollToTop.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({
            render: () => h('div', [
                h(App, props),
                h(ScrollToTop), 
            ]),
            components: { ScrollToTop }
        });

        vueApp.use(plugin)
            .use(Toast)
            .use(ZiggyVue)
            .mount(el);

        // applyGlobalValidation(); // Call validation function globally

        return vueApp;
    },
    progress: {
        color: '#4B5563',
    },
});
