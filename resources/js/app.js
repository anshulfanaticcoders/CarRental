import './bootstrap';
import '../css/app.css';
import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";
import * as Sentry from '@sentry/vue';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import TranslationPlugin from '../js/plugins/translation';
// import { hideTawk, showTawk } from './lib/tawk';

const appName = import.meta.env.VITE_APP_NAME || 'Vrooem';

const SUPPORTED_LOCALES = new Set(['en', 'fr', 'nl', 'es', 'ar']);

const localeFromPage = (page) => {
    const propLocale = page?.props?.locale;

    if (SUPPORTED_LOCALES.has(propLocale)) {
        return propLocale;
    }

    const path = page?.url || (typeof window !== 'undefined' ? window.location.pathname : '/');
    const firstSegment = String(path)
        .split('?')[0]
        .split('/')
        .filter(Boolean)[0];

    return SUPPORTED_LOCALES.has(firstSegment) ? firstSegment : 'en';
};

const absolutePageUrl = (page) => {
    if (typeof window === 'undefined') {
        return page?.props?.ziggy?.location || page?.url || '/';
    }

    return new URL(page?.url || window.location.pathname, window.location.origin).href;
};

const syncLocalizedRuntimeState = (page, ziggyConfig, rootEl = null) => {
    const locale = localeFromPage(page);

    if (typeof document !== 'undefined') {
        document.documentElement.lang = locale;
        document.documentElement.dir = locale === 'ar' ? 'rtl' : 'ltr';
    }

    if (ziggyConfig) {
        Object.assign(ziggyConfig, page?.props?.ziggy || {});
        ziggyConfig.location = absolutePageUrl(page);
    }

    if (rootEl && page) {
        rootEl.dataset.page = JSON.stringify(page);
    }
};

createInertiaApp({
    // Title resolution rules:
    //  - Empty / fallback title → use brand alone (avoids "Laravel - Laravel" or
    //    "Vrooem - Vrooem" when a page has no SEO row).
    //  - Title that already contains the brand → keep as-is (no dup suffix).
    //  - Otherwise → append " - {brand}".
    title: (title) => {
        const resolved = (title || '').trim();
        if (!resolved || resolved.toLowerCase() === appName.toLowerCase()) return appName;
        return resolved.toLowerCase().includes(appName.toLowerCase())
            ? resolved
            : `${resolved} - ${appName}`;
    },
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const ziggyConfig = {
            ...(props.initialPage?.props?.ziggy || globalThis.Ziggy || {}),
            location: absolutePageUrl(props.initialPage),
        };

        syncLocalizedRuntimeState(props.initialPage, ziggyConfig, el);

        const vueApp = createApp({
            render: () => h(App, props),
        });

        if (import.meta.env.VITE_SENTRY_DSN) {
            Sentry.init({
                app: vueApp,
                dsn: import.meta.env.VITE_SENTRY_DSN,
                environment: import.meta.env.VITE_SENTRY_ENVIRONMENT || 'production',
                integrations: [
                    Sentry.browserTracingIntegration(),
                    Sentry.replayIntegration(),
                ],
                tracesSampleRate: 0.2,
                replaysSessionSampleRate: 0.1,
                replaysOnErrorSampleRate: 1.0,
            });

            const page = props.initialPage;
            if (page?.props?.auth?.user) {
                Sentry.setUser({
                    id: page.props.auth.user.id,
                    email: page.props.auth.user.email,
                    username: page.props.auth.user.name,
                });
            }
        }

        vueApp.use(plugin)
            .use(Toast)
            .use(ZiggyVue, ziggyConfig)
            .use(TranslationPlugin);

        // Mount the main App component
        vueApp.mount(el);
        el.classList.add('hydrated');

        // const updateTawkForPage = (page) => {
        //     if (page?.component === 'Welcome') {
        //         showTawk();
        //         return;
        //     }
        //     hideTawk();
        // };

        // // Initial render
        // updateTawkForPage(props?.initialPage);

        // // Hide early during transitions; show again only on Welcome.
        // router.on('start', () => hideTawk());
        // router.on('navigate', (event) => updateTawkForPage(event?.detail?.page));
        router.on('navigate', (event) => {
            syncLocalizedRuntimeState(event?.detail?.page, ziggyConfig, el);
        });

        return vueApp;
    },
    progress: {
        color: '#ea3c3c',
        zIndex: 100000, // Higher than header's 99999
    },
});
