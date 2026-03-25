<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import SeoHead from '@/Components/SeoHead.vue';
import SchemaInjector from '@/Components/SchemaInjector.vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import Footer from '@/Components/Footer.vue';
import { useScrollAnimation } from '@/composables/useScrollAnimation';

const props = defineProps({
    popularPlaces: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
            last_page: 1,
        }),
    },
    schema: { type: Array, default: () => [] },
    seo: { type: Object, required: true },
});

const page = usePage();

const _p = (key, fallback = '') => {
    const t = page.props.translations?.homepage || {};
    return t[key] || fallback || key;
};

const staticPlaces = [
    { id: 1, place_name: 'Barcelona', city: 'Barcelona', country: 'Spain', image: 'https://images.unsplash.com/photo-1523531294919-4bcd7c65e216?w=900&q=80' },
    { id: 2, place_name: 'Dubai', city: 'Dubai', country: 'UAE', image: 'https://images.unsplash.com/photo-1534351590666-13e3e96b5017?w=900&q=80' },
    { id: 3, place_name: 'Paris', city: 'Paris', country: 'France', image: 'https://images.unsplash.com/photo-1499856871958-5b9627545d1a?w=900&q=80' },
    { id: 4, place_name: 'Rome', city: 'Rome', country: 'Italy', image: 'https://images.unsplash.com/photo-1552832230-c0197dd311b5?w=900&q=80' },
    { id: 5, place_name: 'London', city: 'London', country: 'UK', image: 'https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?w=900&q=80' },
];

const places = computed(() => props.popularPlaces?.data?.length ? props.popularPlaces.data : staticPlaces);
const paginationLinks = computed(() => {
    if ((props.popularPlaces?.last_page ?? 1) <= 1) return [];
    return Array.isArray(props.popularPlaces?.links) ? props.popularPlaces.links : [];
});

const normalizePageLabel = (label) => String(label ?? '')
    .replace(/<[^>]*>/g, '')
    .replace(/&laquo;/g, '«')
    .replace(/&raquo;/g, '»')
    .trim();

const goToPage = (url) => {
    if (!url) return;

    router.visit(url, {
        preserveState: false,
        preserveScroll: true,
        onSuccess: () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
    });
};

const navigateToSearch = (place) => {
    const searchUrl = place?.search_url;

    if (searchUrl) {
        sessionStorage.setItem('searchurl', searchUrl);
        window.location.href = `/${page.props.locale}${searchUrl}`;
        return;
    }

    window.location.href = `/${page.props.locale}`;
};

const getDestinationHref = (place) => {
    const searchUrl = place?.search_url;
    return searchUrl ? `/${page.props.locale}${searchUrl}` : `/${page.props.locale}`;
};

useScrollAnimation('.destinations-grid-section', '.destination-card, .destinations-pagination', {
    y: 44,
    duration: 0.9,
    stagger: 0.08,
});
</script>

<template>
    <SeoHead :seo="seo" />
    <template v-if="schema && schema.length">
        <SchemaInjector v-for="(item, index) in schema" :key="`destinations-schema-${index}`" :schema="item" />
    </template>

    <AuthenticatedHeaderLayout />

    <main class="destinations-page">
        <section class="destinations-hero">
            <div class="full-w-container">
                <span class="destinations-badge">{{ _p('top_destinations', 'Top Destinations') }}</span>
                <h1 class="destinations-title">{{ _p('all_destinations_title', 'All Destinations') }}</h1>
                <p class="destinations-subtitle">
                    {{ _p('all_destinations_subtitle', 'Pick a destination and instantly see available vehicles for your dates.') }}
                </p>
            </div>
        </section>

        <section class="destinations-grid-section">
            <div class="full-w-container">
                <div class="destinations-grid">
                    <a
                        v-for="place in places"
                        :key="place.id"
                        :href="getDestinationHref(place)"
                        class="destination-card sr-reveal"
                        @click.prevent="navigateToSearch(place)"
                    >
                        <img :src="place.image" :alt="place.place_name" loading="lazy" />
                        <div class="destination-overlay">
                            <h2 class="destination-name">{{ place.place_name }}</h2>
                            <p class="destination-location">{{ place.city }}, {{ place.country }}</p>
                        </div>
                    </a>
                </div>

                <nav v-if="paginationLinks.length > 1" class="destinations-pagination sr-reveal" aria-label="Destinations pagination">
                    <button
                        v-for="(link, index) in paginationLinks"
                        :key="`destination-page-${index}`"
                        type="button"
                        :class="[
                            'destination-page-link',
                            { 'is-active': link.active, 'is-disabled': !link.url },
                        ]"
                        :disabled="!link.url"
                        @click="goToPage(link.url)"
                    >
                        {{ normalizePageLabel(link.label) }}
                    </button>
                </nav>
            </div>
        </section>
    </main>

    <Footer />
</template>

<style scoped>
.sr-reveal {
    visibility: hidden;
}

.destinations-page {
    background: #f8fafc;
    color: #0f172a;
}

.destinations-hero {
    padding: clamp(2.5rem, 5vw, 4rem) 0 clamp(1.5rem, 3vw, 2rem);
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.destinations-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: #1c4d66;
}

.destinations-badge::before {
    content: '';
    width: 22px;
    height: 2px;
    background: #1c4d66;
}

.destinations-title {
    margin-top: 0.85rem;
    font-size: clamp(2rem, 3vw, 2.7rem);
    line-height: 1.12;
    letter-spacing: -0.02em;
    font-weight: 700;
    color: #0a1d28;
}

.destinations-subtitle {
    margin-top: 0.8rem;
    max-width: 58ch;
    font-size: 0.98rem;
    color: #64748b;
}

.destinations-grid-section {
    padding: 0 0 clamp(3.5rem, 7vw, 6rem);
}

.destinations-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1.1rem;
}

.destination-card {
    position: relative;
    display: block;
    border-radius: 18px;
    overflow: hidden;
    aspect-ratio: 3 / 4;
    box-shadow: 0 8px 24px rgba(10, 29, 40, 0.08);
    transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.35s cubic-bezier(0.22, 1, 0.36, 1);
}

.destination-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s cubic-bezier(0.22, 1, 0.36, 1);
}

.destination-card::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(10, 29, 40, 0.88) 0%, rgba(10, 29, 40, 0.42) 45%, rgba(10, 29, 40, 0.08) 72%, transparent 100%);
}

.destination-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 14px 36px rgba(10, 29, 40, 0.16);
}

.destination-card:hover img {
    transform: scale(1.05);
}

.destination-overlay {
    position: absolute;
    z-index: 1;
    inset: auto 0 0;
    padding: 1.15rem;
}

.destination-name {
    font-size: 1.05rem;
    line-height: 1.2;
    font-weight: 700;
    color: #fff;
}

.destination-location {
    margin-top: 0.2rem;
    font-size: 0.83rem;
    color: rgba(255, 255, 255, 0.78);
}

.destinations-pagination {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 2rem;
}

.destination-page-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2.3rem;
    height: 2.3rem;
    padding: 0 0.8rem;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    color: #153b4f;
    background: #fff;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.25s ease;
    text-decoration: none;
}

.destination-page-link:hover {
    border-color: #153b4f;
    transform: translateY(-1px);
}

.destination-page-link.is-active {
    background: #153b4f;
    color: #fff;
    border-color: #153b4f;
}

.destination-page-link.is-disabled {
    opacity: 0.5;
    pointer-events: none;
}

@media (max-width: 1200px) {
    .destinations-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 900px) {
    .destinations-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 560px) {
    .destinations-grid {
        grid-template-columns: 1fr;
    }

    .destination-card {
        aspect-ratio: 16 / 10;
    }
}
</style>
