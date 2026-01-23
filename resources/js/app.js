import './bootstrap';
import '../css/app.css';
import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";


import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import TranslationPlugin from '../js/plugins/translation';
import AffiliateSignupPopup from './Components/AffiliateSignupPopup.vue'; // Import the new component
import { hideTawk, showTawk } from './lib/tawk';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({
            render: () => h(App, props),
        });

        vueApp.use(plugin)
            .use(Toast)
            .use(ZiggyVue)
            .use(TranslationPlugin);

        // Mount the main App component
        vueApp.mount(el);

        const updateTawkForPage = (page) => {
            if (page?.component === 'Welcome') {
                showTawk();
                return;
            }
            hideTawk();
        };

        // Initial render
        updateTawkForPage(props?.initialPage);

        // Hide early during transitions; show again only on Welcome.
        router.on('start', () => hideTawk());
        router.on('navigate', (event) => updateTawkForPage(event?.detail?.page));

        // Create a separate Vue app for the popup and mount it to a new div
        const popupDiv = document.createElement('div');
        document.body.appendChild(popupDiv);
        // createApp(AffiliateSignupPopup).mount(popupDiv);

        return vueApp;
    },
    progress: {
        color: '#ea3c3c',
        zIndex: 100000, // Higher than header's 99999
    },
});
