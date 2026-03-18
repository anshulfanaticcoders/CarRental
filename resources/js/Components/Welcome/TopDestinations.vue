<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Skeleton } from '@/Components/ui/skeleton';
import axios from 'axios';
import { useScrollAnimation } from '@/composables/useScrollAnimation';

const props = defineProps({
    popularPlaces: { type: Array, default: null },
});

const page = usePage();
const _p = (key, fallback = '') => {
    const t = page.props.translations?.homepage || {};
    return t[key] || fallback || key;
};

const unifiedLocations = ref([]);

onMounted(async () => {
    try {
        const r = await axios.get('/unified_locations.json');
        unifiedLocations.value = r.data;
    } catch (e) { /* silent */ }
});

const navigateToSearch = (place) => {
    updateSearchUrl(place);
    const url = sessionStorage.getItem('searchurl');
    if (url) window.location.href = `/${page.props.locale}${url}`;
};

const updateSearchUrl = (place) => {
    const location = unifiedLocations.value.find(l => l.name === place.place_name);
    const today = new Date();
    const pickup = new Date(today); pickup.setDate(today.getDate() + 1);
    const ret = new Date(pickup); ret.setDate(pickup.getDate() + 1);
    const fmt = (d) => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
    const params = { date_from: fmt(pickup), date_to: fmt(ret), start_time: '09:00', end_time: '09:00', age: 35 };
    if (location?.providers?.length) {
        const p = location.providers[0];
        Object.assign(params, { where: location.name, latitude: location.latitude, longitude: location.longitude, city: location.city, country: location.country, provider: p.provider, provider_pickup_id: p.pickup_id, dropoff_location_id: p.pickup_id, dropoff_where: location.name });
    } else {
        Object.assign(params, { where: place.place_name, dropoff_where: place.place_name });
    }
    sessionStorage.setItem('searchurl', `/s?${new URLSearchParams(params).toString()}`);
};

// Static fallback destinations
const staticPlaces = [
    { id: 1, place_name: 'Barcelona', city: 'Barcelona', country: 'Spain', image: 'https://images.unsplash.com/photo-1523531294919-4bcd7c65e216?w=600&q=80' },
    { id: 2, place_name: 'Dubai', city: 'Dubai', country: 'UAE', image: 'https://images.unsplash.com/photo-1534351590666-13e3e96b5017?w=600&q=80' },
    { id: 3, place_name: 'Paris', city: 'Paris', country: 'France', image: 'https://images.unsplash.com/photo-1499856871958-5b9627545d1a?w=600&q=80' },
    { id: 4, place_name: 'Rome', city: 'Rome', country: 'Italy', image: 'https://images.unsplash.com/photo-1552832230-c0197dd311b5?w=600&q=80' },
    { id: 5, place_name: 'London', city: 'London', country: 'UK', image: 'https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?w=600&q=80' },
];

const places = computed(() => props.popularPlaces?.length ? props.popularPlaces.slice(0, 5) : staticPlaces);

useScrollAnimation('.dest-section', '.dest-header, .dest-card', {
    y: 48,
    duration: 0.9,
    stagger: 0.12,
});

</script>

<template>
    <section class="dest-section">
        <div class="full-w-container">
            <div class="dest-header sr-reveal">
                <div class="dest-header-text">
                    <span class="dest-label">{{ _p('top_destinations', 'Top Destinations') }}</span>
                    <h3 class="dest-title">{{ _p('popular_places', 'Where will you drive next?') }}</h3>
                    <p class="dest-sub">Most-booked locations by travelers this month.</p>
                </div>
                <a :href="`/${page.props.locale}/s`" class="dest-btn">View All Destinations</a>
            </div>

            <div class="dest-grid">
                <a v-for="p in places" :key="p.id"
                    :href="`/${page.props.locale}/s?where=${encodeURIComponent(p.place_name)}`"
                    @click.prevent="navigateToSearch(p)"
                    class="dest-card sr-reveal">
                    <img :src="p.image" :alt="p.place_name" loading="lazy" />
                    <div class="dest-info">
                        <div class="dest-name">{{ p.place_name }}</div>
                        <div class="dest-loc">{{ p.city }}, {{ p.country }}</div>
                    </div>
                </a>
            </div>
        </div>
    </section>
</template>

<style scoped>
.sr-reveal { visibility: hidden; }

.dest-section {
    padding: clamp(4rem, 8vw, 7rem) 0;
    background: linear-gradient(180deg, #f8fafc 0%, #fff 45%, #f8fafc 100%);
    color: #0f172a;
}

.dest-header {
    margin-bottom: 3rem;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 1rem;
}

.dest-header-text { max-width: 480px; }

.dest-label {
    display: inline-flex; align-items: center; gap: 0.5rem;
    font-size: 0.8rem; font-weight: 600; letter-spacing: 0.14em; text-transform: uppercase;
    color: #1c4d66;
}
.dest-label::before { content: ""; display: block; width: 24px; height: 1.5px; background: #1c4d66; }
.dest-title {
    font-size: clamp(2rem, 3.5vw, 3rem); font-weight: 700; line-height: 1.12;
    letter-spacing: -0.02em; color: #0a1d28; margin-top: 0.75rem;
}
.dest-sub { color: #64748b; margin-top: 0.75rem; font-size: 0.95rem; }
.dest-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.85rem 1.75rem;
    border-radius: 14px;
    border: 1.5px solid #153b4f;
    color: #153b4f;
    background: transparent;
    font-weight: 600;
    font-size: 0.92rem;
    transition: all 0.4s cubic-bezier(0.22, 1, 0.36, 1);
}

.dest-btn:hover {
    background: #153b4f;
    color: #fff;
    transform: translateY(-2px);
}

.dest-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem; }

.dest-card {
    position: relative; border-radius: 20px; overflow: hidden;
    aspect-ratio: 3/4; cursor: pointer; display: block;
    box-shadow: 0 4px 24px rgba(10,29,40,0.06);
    transition: transform 0.4s cubic-bezier(0.22,1,0.36,1), box-shadow 0.4s cubic-bezier(0.22,1,0.36,1);
}
.dest-card:hover { transform: translateY(-6px); box-shadow: 0 16px 48px rgba(10,29,40,0.12); }
.dest-card img { width:100%; height:100%; object-fit:cover; transition: transform 0.6s cubic-bezier(0.22,1,0.36,1); }
.dest-card:hover img { transform: scale(1.06); }
.dest-card::after {
    content: ""; position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(10,29,40,0.85) 0%, rgba(10,29,40,0.4) 35%, rgba(10,29,40,0.05) 60%, transparent 100%);
}
.dest-info { position: absolute; bottom: 0; left: 0; right: 0; padding: 1.25rem; z-index: 1; }
.dest-name { font-size: 1.05rem; font-weight: 700; color: #fff; text-shadow: 0 2px 8px rgba(0,0,0,0.4); }
.dest-loc { font-size: 0.78rem; color: rgba(255,255,255,0.7); text-shadow: 0 1px 4px rgba(0,0,0,0.3); }

@media (max-width: 1024px) {
    .dest-grid { grid-template-columns: repeat(3, 1fr); }
    .dest-grid .dest-card:nth-child(n+4) { display: none; }
}
@media (max-width: 768px) {
    .dest-header { flex-direction: column; align-items: flex-start; }
    .dest-grid { grid-template-columns: repeat(2, 1fr); }
    .dest-grid .dest-card:nth-child(n+3) { display: none; }
}
@media (max-width: 480px) {
    .dest-grid { grid-template-columns: 1fr; }
    .dest-grid .dest-card:nth-child(n+3) { display: block; }
    .dest-grid .dest-card:nth-child(n+4) { display: none; }
    .dest-card { aspect-ratio: 16/9; }
}
</style>
