<script setup>
import { ref, computed, onBeforeUnmount } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useScrollAnimation } from '@/composables/useScrollAnimation';
import {
    Carousel,
    CarouselContent,
    CarouselItem,
    CarouselNext,
    CarouselPrevious,
} from '@/Components/ui/carousel';

const props = defineProps({
    popularPlaces: { type: Array, default: null },
});

const page = usePage();
const _p = (key, fallback = '') => {
    const t = page.props.translations?.homepage || {};
    return t[key] || fallback || key;
};

const emblaApi = ref(null);
const selectedIndex = ref(0);
const snapCount = ref(0);

const navigateToSearch = (place) => {
    const searchUrl = place?.search_url;

    if (searchUrl) {
        sessionStorage.setItem('searchurl', searchUrl);
        window.location.href = `/${page.props.locale}${searchUrl}`;
        return;
    }

    // Graceful fallback: keep user on locale home if we cannot resolve a searchable location.
    window.location.href = `/${page.props.locale}`;
};

const getDestinationHref = (place) => {
    const searchUrl = place?.search_url;
    return searchUrl ? `/${page.props.locale}${searchUrl}` : `/${page.props.locale}`;
};

const getDestinationMeta = (place) => {
    const city = `${place?.city || ''}`.trim();
    const country = `${place?.country || ''}`.trim();
    const name = `${place?.place_name || ''}`.trim().toLowerCase();

    if (city && city.toLowerCase() !== name) return city;
    if (country) return country;
    return _p('destination_card_fallback_meta', 'Destination');
};

// Static fallback destinations
const staticPlaces = [
    { id: 1, place_name: 'Barcelona', city: 'Barcelona', country: 'Spain', image: 'https://images.unsplash.com/photo-1523531294919-4bcd7c65e216?w=600&q=80' },
    { id: 2, place_name: 'Dubai', city: 'Dubai', country: 'UAE', image: 'https://images.unsplash.com/photo-1534351590666-13e3e96b5017?w=600&q=80' },
    { id: 3, place_name: 'Paris', city: 'Paris', country: 'France', image: 'https://images.unsplash.com/photo-1499856871958-5b9627545d1a?w=600&q=80' },
    { id: 4, place_name: 'Rome', city: 'Rome', country: 'Italy', image: 'https://images.unsplash.com/photo-1552832230-c0197dd311b5?w=600&q=80' },
    { id: 5, place_name: 'London', city: 'London', country: 'UK', image: 'https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?w=600&q=80' },
    { id: 6, place_name: 'Marrakesh', city: 'Marrakesh', country: 'Morocco', image: 'https://images.unsplash.com/photo-1597212618440-806262de4f6b?w=600&q=80' },
    { id: 7, place_name: 'Amsterdam', city: 'Amsterdam', country: 'Netherlands', image: 'https://images.unsplash.com/photo-1512470876302-972faa2aa9a4?w=600&q=80' },
    { id: 8, place_name: 'Lisbon', city: 'Lisbon', country: 'Portugal', image: 'https://images.unsplash.com/photo-1558370781-d6196949e317?w=600&q=80' },
    { id: 9, place_name: 'Abu Dhabi', city: 'Abu Dhabi', country: 'UAE', image: 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?w=600&q=80' },
    { id: 10, place_name: 'Milan', city: 'Milan', country: 'Italy', image: 'https://images.unsplash.com/photo-1523906834658-6e24ef2386f9?w=600&q=80' },
];

const places = computed(() => props.popularPlaces?.length ? props.popularPlaces.slice(0, 10) : staticPlaces.slice(0, 10));
const showCarouselControls = computed(() => places.value.length > 5);

const updateCarouselState = (api = emblaApi.value) => {
    if (!api) return;
    selectedIndex.value = api.selectedScrollSnap();
    snapCount.value = api.scrollSnapList().length;
};

const onInitApi = (api) => {
    emblaApi.value = api;
    updateCarouselState(api);
    api.on('select', updateCarouselState);
    api.on('reInit', updateCarouselState);
};

const scrollTo = (index) => emblaApi.value?.scrollTo(index);

onBeforeUnmount(() => {
    if (!emblaApi.value) return;
    emblaApi.value.off('select', updateCarouselState);
    emblaApi.value.off('reInit', updateCarouselState);
});

useScrollAnimation('.dest-section', '.dest-header, .dest-carousel, .dest-dots', {
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
                <a :href="`/${page.props.locale}/destinations`" class="dest-btn">
                    {{ _p('view_all_destinations', 'View All Destinations') }}
                </a>
            </div>

            <Carousel
                class="dest-carousel sr-reveal"
                :opts="{ align: 'start', loop: showCarouselControls }"
                @init-api="onInitApi"
            >
                <CarouselContent>
                    <CarouselItem
                        v-for="p in places"
                        :key="p.id"
                        class="md:basis-1/2 lg:basis-1/4"
                    >
                        <a
                            :href="getDestinationHref(p)"
                            @click.prevent="navigateToSearch(p)"
                            class="dest-card"
                        >
                            <img :src="p.image" :alt="p.place_name" loading="lazy" />
                            <div class="dest-info">
                                <div class="dest-name">{{ p.place_name }}</div>
                                <div class="dest-loc">{{ getDestinationMeta(p) }}</div>
                            </div>
                        </a>
                    </CarouselItem>
                </CarouselContent>
                <CarouselPrevious v-if="showCarouselControls" class="dest-nav dest-prev" />
                <CarouselNext v-if="showCarouselControls" class="dest-nav dest-next" />
            </Carousel>

            <div v-if="showCarouselControls" class="dest-dots">
                <button
                    v-for="(_, i) in snapCount"
                    :key="`dest-dot-${i}`"
                    type="button"
                    class="dest-dot"
                    :class="{ 'is-active': i === selectedIndex }"
                    @click="scrollTo(i)"
                    :aria-label="`Go to destination slide ${i + 1}`"
                ></button>
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

.dest-carousel {
    position: relative;
    padding-inline: 1.25rem;
}

.dest-carousel :deep(.dest-prev) {
    left: -0.85rem !important;
}

.dest-carousel :deep(.dest-next) {
    right: -0.85rem !important;
}

.dest-carousel :deep(.dest-nav) {
    border: 1px solid #b0d4e6 !important;
    background: #fff !important;
    box-shadow: 0 8px 24px rgba(10, 29, 40, 0.15);
}

.dest-carousel :deep(.dest-nav svg) {
    color: #153b4f !important;
}

.dest-card {
    position: relative;
    display: block;
    height: 100%;
    border-radius: 18px;
    overflow: hidden;
    aspect-ratio: 3 / 4;
    cursor: pointer;
    box-shadow: 0 8px 24px rgba(10, 29, 40, 0.08);
    transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.35s cubic-bezier(0.22, 1, 0.36, 1);
}
.dest-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 14px 36px rgba(10, 29, 40, 0.16);
}

.dest-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s cubic-bezier(0.22, 1, 0.36, 1);
}

.dest-card::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(10, 29, 40, 0.88) 0%, rgba(10, 29, 40, 0.42) 45%, rgba(10, 29, 40, 0.08) 72%, transparent 100%);
}

.dest-card:hover img { transform: scale(1.05); }

.dest-info {
    position: absolute;
    z-index: 1;
    inset: auto 0 0;
    padding: 1.15rem;
}

.dest-name {
    font-size: 1.05rem;
    font-weight: 700;
    line-height: 1.2;
    color: #fff;
}

.dest-loc {
    margin-top: 0.2rem;
    font-size: 0.83rem;
    color: rgba(255, 255, 255, 0.78);
}

.dest-dots {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1.6rem;
}

.dest-dot {
    width: 9px;
    height: 9px;
    border-radius: 999px;
    border: none;
    background: #cbd5e1;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}

.dest-dot.is-active {
    width: 22px;
    background: #153b4f;
}

@media (max-width: 768px) {
    .dest-header { flex-direction: column; align-items: flex-start; }

    .dest-carousel {
        padding-inline: 0.6rem;
    }

    .dest-carousel :deep(.dest-prev),
    .dest-carousel :deep(.dest-next) {
        display: none !important;
    }

    .dest-card {
        aspect-ratio: 16 / 10;
    }
}

@media (max-width: 480px) {
    .dest-name {
        font-size: 1rem;
    }
}
</style>
