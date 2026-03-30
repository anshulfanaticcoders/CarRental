<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import axios from 'axios';
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
    initialTestimonials: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const _p = (key, fallback = '') => (page.props.translations?.homepage?.[key] || fallback || key);

const clampRating = (value) => {
    const number = Number(value);
    if (Number.isNaN(number)) return 5;
    return Math.min(5, Math.max(1, Math.round(number)));
};

const normalizeTestimonial = (testimonial, index) => ({
    id: testimonial?.id ?? `item-${index}`,
    rating: clampRating(testimonial?.rating ?? testimonial?.ratings),
    content: testimonial?.content ?? testimonial?.message ?? testimonial?.comment ?? testimonial?.review ?? '',
    author: testimonial?.author ?? testimonial?.name ?? '',
    role: testimonial?.location ?? testimonial?.designation ?? testimonial?.title ?? 'Vrooem Traveler',
});

const normalizeList = (items) => (Array.isArray(items) ? items.map(normalizeTestimonial) : []);

const testimonials = ref(normalizeList(props.initialTestimonials));
const getInitials = (name) => (name ? name.split(' ').map((w) => w[0]).join('').toUpperCase().slice(0, 2) : '?');
const emblaApi = ref(null);
const selectedIndex = ref(0);
const snapCount = ref(0);

onMounted(async () => {
    try {
        const r = await axios.get('/api/testimonials/frontend');
        const apiItems = r?.data?.testimonials ?? r?.data;
        const normalized = normalizeList(apiItems);
        if (normalized.length) {
            testimonials.value = normalized;
        }
    } catch (e) {
        // Keep server-provided testimonials if API request fails.
        console.error('Failed to fetch frontend testimonials:', e);
    }
});

const fallbackTestimonials = [
    {
        id: 'fallback-1',
        rating: 5,
        content: 'The whole experience was seamless. From booking to pickup, everything was exactly as promised. The free internet was a bonus I did not expect.',
        author: 'Anna Laurent',
        role: 'Traveled to Barcelona',
    },
    {
        id: 'fallback-2',
        rating: 5,
        content: 'Best price I found anywhere. I checked three other comparison sites and Vrooem consistently beat them. The damage protection gave me real peace of mind.',
        author: 'Marcus Klein',
        role: 'Traveled to Dubai',
    },
    {
        id: 'fallback-3',
        rating: 5,
        content: 'Our car broke down and within 30 minutes they had a replacement delivered. Upgraded too. Their 24/7 support actually means 24/7.',
        author: 'Sofia Rodriguez',
        role: 'Traveled to Rome',
    },
];

const cards = computed(() => {
    if (!testimonials.value.length) return fallbackTestimonials;

    return testimonials.value.map((t, index) => ({
        id: t.id || `api-${index}`,
        rating: clampRating(t.rating),
        content: t.content || fallbackTestimonials[index % 3].content,
        author: t.author || fallbackTestimonials[index % 3].author,
        role: t.role || fallbackTestimonials[index % 3].role,
    }));
});

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

useScrollAnimation('.testi-section', '.testi-header, .testi-carousel, .testi-dots', {
    y: 46,
    duration: 0.9,
    stagger: 0.14,
});
</script>

<template>
    <section id="testimonials" class="testi-section">
        <div class="full-w-container">
            <div class="testi-header sr-reveal">
                <span class="testi-label">{{ _p('testimonials_title', 'What Travelers Say') }}</span>
                <h3 class="testi-title">{{ _p('testimonials_subtitle', 'Stories from the road.') }}</h3>
            </div>

            <Carousel
                class="testi-carousel sr-reveal"
                :opts="{ align: 'start', loop: cards.length > 3 }"
                @init-api="onInitApi"
            >
                <CarouselContent>
                    <CarouselItem
                        v-for="card in cards"
                        :key="card.id"
                        class="md:basis-1/2 lg:basis-1/4"
                    >
                        <div class="testi-card">
                            <div class="testi-stars">
                                <svg v-for="s in card.rating" :key="`star-${card.id}-${s}`" viewBox="0 0 24 24">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                </svg>
                            </div>
                            <p class="testi-quote">"{{ card.content }}"</p>
                            <div class="testi-author">
                                <div class="testi-avatar">{{ getInitials(card.author) }}</div>
                                <div>
                                    <div class="testi-name">{{ card.author }}</div>
                                    <div class="testi-role">{{ card.role }}</div>
                                </div>
                            </div>
                        </div>
                    </CarouselItem>
                </CarouselContent>
                <CarouselPrevious class="testi-nav testi-prev" />
                <CarouselNext class="testi-nav testi-next" />
            </Carousel>

            <div v-if="snapCount > 1" class="testi-dots">
                <button
                    v-for="(_, i) in snapCount"
                    :key="`dot-${i}`"
                    type="button"
                    class="testi-dot"
                    :class="{ 'is-active': i === selectedIndex }"
                    @click="scrollTo(i)"
                    :aria-label="`Go to testimonial slide ${i + 1}`"
                ></button>
            </div>
        </div>
    </section>
</template>

<style scoped>
.sr-reveal { visibility: hidden; }

.testi-section {
    padding: clamp(4rem, 8vw, 7rem) 0;
    background: linear-gradient(180deg, #f8fafc 0%, #fff 45%, #f8fafc 100%);
    color: #0f172a;
}

.testi-header { text-align: center; margin-bottom: 3.5rem; }

.testi-label {
    display: inline-flex; align-items: center; gap: 0.5rem;
    font-size: 0.8rem; font-weight: 600; letter-spacing: 0.14em; text-transform: uppercase;
    color: #1c4d66;
}

.testi-label::before { content: ""; display: block; width: 24px; height: 1.5px; background: #1c4d66; }

.testi-title {
    font-size: clamp(2rem, 3.5vw, 3rem); font-weight: 700; line-height: 1.12;
    letter-spacing: -0.02em; color: #0a1d28; margin-top: 0.75rem;
}

.testi-carousel {
    position: relative;
    padding-inline: 1.25rem;
}

.testi-carousel :deep(.testi-prev) {
    left: -0.85rem !important;
}

.testi-carousel :deep(.testi-next) {
    right: -0.85rem !important;
}

.testi-carousel :deep(.testi-nav) {
    border: 1px solid #b0d4e6 !important;
    background: #fff !important;
    box-shadow: 0 8px 24px rgba(10, 29, 40, 0.15);
}

.testi-carousel :deep(.testi-nav svg) {
    color: #153b4f !important;
}

.testi-card {
    padding: 2rem; border-radius: 20px; background: #fff;
    border: 1px solid #e2e8f0; display: flex; flex-direction: column;
    box-shadow: 0 4px 24px rgba(10, 29, 40, 0.06);
    transition: all 0.4s cubic-bezier(0.22, 1, 0.36, 1);
    height: 100%;
}

.testi-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 48px rgba(10, 29, 40, 0.12);
    border-color: #b0d4e6;
}

.testi-stars { display: flex; gap: 2px; margin-bottom: 1rem; }
.testi-stars svg { width: 16px; height: 16px; color: #e4b96a; fill: #e4b96a; }

.testi-quote { font-size: 0.95rem; line-height: 1.7; color: #475569; flex: 1; margin-bottom: 1.5rem; }

.testi-author { display: flex; align-items: center; gap: 0.75rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; }

.testi-avatar {
    width: 40px; height: 40px; border-radius: 50%;
    background: linear-gradient(135deg, #245f7d, #06b6d4);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.8rem; font-weight: 700; color: #fff; flex-shrink: 0;
}

.testi-name { font-size: 0.88rem; font-weight: 600; color: #0a1d28; }
.testi-role { font-size: 0.76rem; color: #94a3b8; }

.testi-dots {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1.6rem;
}

.testi-dot {
    width: 9px;
    height: 9px;
    border-radius: 999px;
    border: none;
    background: #cbd5e1;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}

.testi-dot.is-active {
    width: 22px;
    background: #153b4f;
}

@media (max-width: 1024px) {
    .testi-carousel {
        padding-inline: 0.6rem;
    }

    .testi-carousel :deep(.testi-prev),
    .testi-carousel :deep(.testi-next) {
        display: none !important;
    }
}

@media (max-width: 768px) {
    .testi-card {
        padding: 1.5rem;
    }
}
</style>
