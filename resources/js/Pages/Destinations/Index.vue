<script setup>
import { computed, onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import SeoHead from '@/Components/SeoHead.vue';
import SchemaInjector from '@/Components/SchemaInjector.vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import Footer from '@/Components/Footer.vue';
import { buildPopularPlaceSearchUrl } from '@/utils/popularPlaceSearch';

const props = defineProps({
    popularPlaces: { type: Array, default: () => [] },
    schema: { type: Array, default: () => [] },
    seo: { type: Object, required: true },
});

const page = usePage();
const unifiedLocations = ref([]);

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

const places = computed(() => props.popularPlaces?.length ? props.popularPlaces : staticPlaces);

onMounted(async () => {
    try {
        const response = await axios.get('/unified_locations.json');
        unifiedLocations.value = response.data;
    } catch (error) {
        // Keep page functional with fallback URLs when unified locations cannot be loaded.
    }
});

const navigateToSearch = (place) => {
    const searchUrl = buildPopularPlaceSearchUrl(place, unifiedLocations.value);

    if (searchUrl) {
        sessionStorage.setItem('searchurl', searchUrl);
        window.location.href = `/${page.props.locale}${searchUrl}`;
        return;
    }

    window.location.href = `/${page.props.locale}`;
};

const getDestinationHref = (place) => {
    const searchUrl = buildPopularPlaceSearchUrl(place, unifiedLocations.value);
    return searchUrl ? `/${page.props.locale}${searchUrl}` : `/${page.props.locale}`;
};
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
                        class="destination-card"
                        @click.prevent="navigateToSearch(place)"
                    >
                        <img :src="place.image" :alt="place.place_name" loading="lazy" />
                        <div class="destination-overlay">
                            <h2 class="destination-name">{{ place.place_name }}</h2>
                            <p class="destination-location">{{ place.city }}, {{ place.country }}</p>
                        </div>
                    </a>
                </div>
            </div>
        </section>
    </main>

    <Footer />
</template>

<style scoped>
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
