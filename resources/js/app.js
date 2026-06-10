import './bootstrap';
import '../css/app.css';
import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";
import * as Sentry from '@sentry/vue';

import { createApp, h, ref, shallowRef } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import TranslationPlugin from '../js/plugins/translation';
// import { hideTawk, showTawk } from './lib/tawk';

const appName = import.meta.env.VITE_APP_NAME || 'Vrooem';

const SUPPORTED_LOCALES = new Set(['en', 'fr', 'nl', 'es', 'ar']);
const ADMIN_ROUTE_MIN_LOADING_MS = 650;
const ADMIN_ROUTE_ROOTS = new Set([
    'admin-dashboard',
    'users',
    'vendors',
    'vendor-vehicles',
    'vehicles-categories',
    'booking-addons',
    'popular-places',
    'customer-bookings',
    'pages',
    'blogs',
    'media',
    'testimonials',
    'api-consumers',
    'external-bookings',
    'damage-protection-records',
    'users-report',
    'vendors-report',
    'business-report',
    'contact-us-mails',
    'activity-logs',
    'radiuses',
]);

const pathFromUrl = (url) => {
    if (typeof window === 'undefined') {
        return String(url || '/').split('?')[0];
    }

    return new URL(url || window.location.pathname, window.location.origin).pathname;
};

const isAdminRouteUrl = (url) => {
    const path = pathFromUrl(url);

    if (path === '/admin-dashboard' || path.startsWith('/admin/')) {
        return true;
    }

    const firstSegment = path.split('/').filter(Boolean)[0];
    return ADMIN_ROUTE_ROOTS.has(firstSegment);
};

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
        const isAdminRouteLoading = ref(false);
        const AdminRouteLottie = shallowRef(null);
        const adminRouteLoaderAnimation = shallowRef(null);
        let adminRouteLoadingStartedAt = 0;
        let adminRouteLoadingTimer = null;
        let adminRouteLoaderAssetPromise = null;
        let adminRouteVisitTimer = null;
        let currentPageIsAdmin = isAdminRouteUrl(props.initialPage?.url);

        syncLocalizedRuntimeState(props.initialPage, ziggyConfig, el);

        const loadAdminRouteLoaderAssets = () => {
            if (AdminRouteLottie.value && adminRouteLoaderAnimation.value) {
                return Promise.resolve();
            }

            if (!adminRouteLoaderAssetPromise) {
                adminRouteLoaderAssetPromise = Promise.all([
                    import('vue3-lottie'),
                    import('../../public/animations/blue-car-loading.json'),
                ]).then(([lottieModule, animationModule]) => {
                    AdminRouteLottie.value = lottieModule.Vue3Lottie;
                    adminRouteLoaderAnimation.value = animationModule.default || animationModule;
                });
            }

            return adminRouteLoaderAssetPromise;
        };

        const clearAdminRouteLoadingTimer = () => {
            if (adminRouteLoadingTimer) {
                window.clearTimeout(adminRouteLoadingTimer);
                adminRouteLoadingTimer = null;
            }
        };

        const setAdminRouteLoading = (isLoading) => {
            isAdminRouteLoading.value = isLoading;
            document.body.classList.toggle('admin-route-loading', isLoading);
        };

        const startAdminRouteLoading = () => {
            if (!currentPageIsAdmin && !document.body.classList.contains('admin-dark-active')) {
                return;
            }

            loadAdminRouteLoaderAssets();
            clearAdminRouteLoadingTimer();
            adminRouteLoadingStartedAt = Date.now();
            setAdminRouteLoading(true);
        };

        if (currentPageIsAdmin) {
            loadAdminRouteLoaderAssets();
        }

        const finishAdminRouteLoading = () => {
            const elapsed = Date.now() - adminRouteLoadingStartedAt;
            const remaining = Math.max(0, ADMIN_ROUTE_MIN_LOADING_MS - elapsed);

            clearAdminRouteLoadingTimer();
            adminRouteLoadingTimer = window.setTimeout(() => {
                setAdminRouteLoading(false);
                adminRouteLoadingTimer = null;
            }, remaining);
        };

        const visitAdminRouteWithLoader = (targetUrl) => {
            startAdminRouteLoading();

            if (adminRouteVisitTimer) {
                window.clearTimeout(adminRouteVisitTimer);
            }

            adminRouteVisitTimer = window.setTimeout(() => {
                adminRouteVisitTimer = null;
                router.visit(targetUrl);
            }, 50);
        };

        const shouldHandleAdminRouteClick = (event) => {
            const clickButton = event.button ?? 0;

            if (event.defaultPrevented || clickButton !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return null;
            }

            const anchor = event.target?.closest?.('a[href]');

            if (!anchor || anchor.target === '_blank' || anchor.hasAttribute('download')) {
                return null;
            }

            const targetUrl = new URL(anchor.href, window.location.origin);

            if (targetUrl.origin !== window.location.origin || !isAdminRouteUrl(targetUrl.href)) {
                return null;
            }

            if (targetUrl.pathname === window.location.pathname && targetUrl.search === window.location.search) {
                return null;
            }

            return `${targetUrl.pathname}${targetUrl.search}${targetUrl.hash}`;
        };

        const handleAdminRouteLinkClick = (event) => {
            const targetUrl = shouldHandleAdminRouteClick(event);

            if (targetUrl) {
                event.preventDefault();
                visitAdminRouteWithLoader(targetUrl);
            }
        };

        const vueApp = createApp({
            render: () => h('div', [
                h(App, props),
                isAdminRouteLoading.value
                    ? h('div', {
                        class: 'admin-route-loading-panel',
                        role: 'status',
                        'aria-live': 'polite',
                        'aria-label': 'Loading',
                    }, [
                        h('div', { class: 'admin-route-loading-card' }, [
                            AdminRouteLottie.value && adminRouteLoaderAnimation.value
                                ? h(AdminRouteLottie.value, {
                                    class: 'admin-route-loading-lottie',
                                    animationData: adminRouteLoaderAnimation.value,
                                    height: 156,
                                    width: 156,
                                    loop: true,
                                    autoplay: true,
                                })
                                : h('div', { class: 'admin-route-loading-lottie admin-route-loading-fallback' }, [
                                    h('span'),
                                ]),
                            h('span', { class: 'admin-route-loading-text' }, [
                                'Loading',
                                h('span', { class: 'admin-route-loading-dots', 'aria-hidden': 'true' }, [
                                    h('span', '.'),
                                    h('span', '.'),
                                    h('span', '.'),
                                ]),
                            ]),
                        ]),
                    ])
                    : null,
            ]),
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
        document.addEventListener('click', handleAdminRouteLinkClick, true);
        window.addEventListener('admin-route-loading:start', startAdminRouteLoading);
        router.on('start', startAdminRouteLoading);
        router.on('finish', finishAdminRouteLoading);
        router.on('navigate', (event) => {
            syncLocalizedRuntimeState(event?.detail?.page, ziggyConfig, el);
            currentPageIsAdmin = isAdminRouteUrl(event?.detail?.page?.url);
        });

        return vueApp;
    },
    progress: {
        color: '#ea3c3c',
        zIndex: 100000, // Higher than header's 99999
    },
});
