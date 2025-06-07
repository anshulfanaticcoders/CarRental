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
import { router } from '@inertiajs/vue3'; // Import router for event listening

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Helper function to set or create a meta tag
function setMetaTag(attributes, content) {
    let element = null;
    if (attributes.name) {
        element = document.querySelector(`meta[name="${attributes.name}"]`);
    } else if (attributes.property) {
        element = document.querySelector(`meta[property="${attributes.property}"]`);
    }

    if (!element) {
        element = document.createElement('meta');
        if (attributes.name) element.setAttribute('name', attributes.name);
        if (attributes.property) element.setAttribute('property', attributes.property);
        document.head.appendChild(element);
    }
    element.setAttribute('content', content);
}

// Helper function to set or create a link tag
function setLinkTag(rel, href) {
    let element = document.querySelector(`link[rel="${rel}"]`);
    if (!element) {
        element = document.createElement('link');
        element.setAttribute('rel', rel);
        document.head.appendChild(element);
    }
    element.setAttribute('href', href);
}

// Function to fetch SEO data and update head
async function updateSeoMeta(urlPath) {
    // Normalize slug for API: ensure leading slash, handle homepage
    let slug = urlPath;
    if (!slug || slug === '') slug = '/'; // Default to homepage
    if (slug !== '/' && !slug.startsWith('/')) slug = '/' + slug;

    // Get current locale from HTML lang attribute or a global variable
    const currentLocale = document.documentElement.lang || 'en'; // Fallback to 'en' if not set

    try {
        const response = await fetch(`/api/seo-meta?slug=${encodeURIComponent(slug)}&locale=${encodeURIComponent(currentLocale)}`);

        if (response.status === 204) {
            // No specific SEO content from API (204 No Content).
            // Do nothing further; existing static tags or Inertia's default title handling will remain.
            return;
        }

        if (!response.ok) {
            // Handle other errors (4xx, 5xx) that are not 204
            console.error('Failed to fetch SEO meta:', response.status, await response.text().catch(() => ''));
            // Optionally set default titles on error, or let Inertia's default title apply
            // document.title = `Error Loading Page - ${appName}`; // Example: set error title
            return;
        }
        
        const seoData = await response.json(); // Only parse if response is OK and not 204

        if (seoData.seo_title) {
            document.title = seoData.seo_title;
        } else {
            // Fallback if API doesn't provide title, use Inertia's default mechanism if possible
            // Or set a generic default
            // For now, let Inertia's title potentially override if seoData.seo_title is missing
        }

        if (seoData.meta_description) {
            setMetaTag({ name: 'description' }, seoData.meta_description);
        }
        if (seoData.keywords) {
            setMetaTag({ name: 'keywords' }, seoData.keywords);
        }
        if (seoData.canonical_url) {
            setLinkTag('canonical', seoData.canonical_url);
        } else {
            // Fallback to current URL if no canonical is provided by API
            setLinkTag('canonical', window.location.href);
        }
        if (seoData.seo_image_url) {
            setMetaTag({ property: 'og:image' }, seoData.seo_image_url);
            setMetaTag({ name: 'twitter:image' }, seoData.seo_image_url);
        }

        // Add other OG/Twitter tags as needed, using seoData
        setMetaTag({ property: 'og:title' }, seoData.seo_title || document.title);
        setMetaTag({ property: 'og:description' }, seoData.meta_description || '');
        setMetaTag({ property: 'og:url' }, seoData.canonical_url || window.location.href);
        setMetaTag({ property: 'og:type' }, 'website'); // Or make dynamic if needed
        setMetaTag({ name: 'twitter:card' }, 'summary_large_image'); // Or make dynamic

    } catch (error) {
        console.error('Error fetching or applying SEO meta:', error);
        // Optionally set default titles on error
         document.title = `Page - ${appName}`; // Generic fallback
    }
}


createInertiaApp({
    title: (title) => title ? `${title} - ${appName}` : appName, // Modified to handle API-driven title
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

        // Initial SEO meta load
        if (props.initialPage && props.initialPage.url) {
            updateSeoMeta(props.initialPage.url);
        }


        // Listen for Inertia navigation events to update SEO meta
        router.on('success', (event) => {
            if (event.detail && event.detail.page && event.detail.page.url) {
                updateSeoMeta(event.detail.page.url);
            }
        });
        
        // Handle cases where Inertia's title might be set by the page component
        // This is a bit tricky as our API call is async.
        // The `title` callback in `createInertiaApp` might run before or after our API call.
        // For simplicity, we let `updateSeoMeta` set `document.title` directly.
        // The `title` callback above is a fallback.

        return vueApp;
    },
    progress: {
        color: '#153b4f',
    },
});
