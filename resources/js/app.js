import './bootstrap';
import '../css/app.css';
import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";


import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import ScrollToTop from '@/Components/ScrollToTop.vue';
import TranslationPlugin from '../js/plugins/translation';

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
            .use(TranslationPlugin)
            .mount(el);

        return vueApp;
    },
    progress: {
        color: '#4B5563',
    },
});
