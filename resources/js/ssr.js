import '../css/app.css';

import { createSSRApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { renderToString } from '@vue/server-renderer';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import TranslationPlugin from '../js/plugins/translation';
import * as Sentry from '@sentry/vue';

const appName = import.meta.env.VITE_APP_NAME || 'Vrooem';
const ssrPageModules = import.meta.glob([
    './Pages/Welcome.vue',
    './Pages/BlogPage.vue',
    './Pages/SingleBlog.vue',
    './Pages/Faq.vue',
    './Pages/ContactUs.vue',
    './Pages/Destinations/Index.vue',
    './Pages/Frontend/Templates/DefaultPage.vue',
    './Pages/Frontend/Templates/LegalPage.vue',
    './Pages/Frontend/Templates/AboutUsPage.vue',
    './Pages/Frontend/Templates/ContactUsPage.vue',
]);

createServer((page) =>
    createInertiaApp({
        page,
        render: renderToString,
        title: (title) => {
            const resolved = (title || '').trim();
            if (!resolved || resolved.toLowerCase() === appName.toLowerCase()) return appName;
            return resolved.toLowerCase().includes(appName.toLowerCase())
                ? resolved
                : `${resolved} - ${appName}`;
        },
        resolve: (name) =>
            resolvePageComponent(`./Pages/${name}.vue`, ssrPageModules),
        setup({ App, props, plugin }) {
            const app = createSSRApp({
                render: () => h(App, props),
            });

            if (import.meta.env.VITE_SENTRY_DSN) {
                Sentry.init({
                    app,
                    dsn: import.meta.env.VITE_SENTRY_DSN,
                    environment: import.meta.env.VITE_SENTRY_ENVIRONMENT || 'production',
                });
            }

            app.use(plugin).use(TranslationPlugin);

            const ziggy = page?.props?.ziggy;
            if (ziggy) {
                app.use(ZiggyVue, {
                    ...ziggy,
                    location: new URL(ziggy.location),
                });
            }

            return app;
        },
    })
);
