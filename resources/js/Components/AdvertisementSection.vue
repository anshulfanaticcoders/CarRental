<script setup>
import { onMounted, ref, computed, onUnmounted } from 'vue';
import { useScrollAnimation } from '@/composables/useScrollAnimation';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const props = defineProps({
    heroImage: String,
    advertisements: {
        type: Array,
        default: () => []
    },
});

const currentIndex = ref(0);
let intervalId = null;

const currentAd = computed(() => {
    if (!props.advertisements || props.advertisements.length === 0) return null;
    return props.advertisements[currentIndex.value];
});

const nextAd = () => {
    if (props.advertisements.length > 1) {
        currentIndex.value = (currentIndex.value + 1) % props.advertisements.length;
    }
};

const setAd = (index) => {
    currentIndex.value = index;
    restartTimer();
};

const startTimer = () => {
    if (props.advertisements.length > 1) {
        intervalId = setInterval(nextAd, 7000);
    }
};

const stopTimer = () => {
    if (intervalId) clearInterval(intervalId);
};

const restartTimer = () => {
    stopTimer();
    startTimer();
};

// Animation setup (triggers once when section enters view)
useScrollAnimation('.ad-section-trigger', '.ad-content-wrapper', {
    opacity: 0,
    y: 20,
    duration: 1,
    ease: 'power3.out'
});

onMounted(() => {
    startTimer();
});

onUnmounted(() => {
    stopTimer();
});
</script>

<template>
    <section v-if="currentAd"
        class="w-full ad-section-trigger overflow-hidden home-section home-section--light">
        <div class="full-w-container ad-content-wrapper">
            <!-- Section Header -->
            <div class="mb-6 sm:mb-8 lg:mb-10">
                <span class="text-[1rem] sm:text-[1.15rem] text-customPrimaryColor tracking-[0.2em] uppercase">Exclusive Offers</span>
                <h3 class="text-customDarkBlackColor text-2xl sm:text-3xl lg:text-[2.5rem] font-bold mt-2 max-[768px]:text-[1.75rem] max-[768px]:mt-[1rem]">
                    Don't Miss Out on These Deals
                </h3>
            </div>

            <div class="relative" @mouseenter="stopTimer" @mouseleave="startTimer">
                <Transition name="fade" mode="out-in">
                    <div :key="currentAd.id" class="ad-grid">
                        <div class="ad-copy">
                            <span class="ad-pill">{{ currentAd.offer_type || 'Exclusive' }}</span>
                            <h2 class="ad-title">
                                {{ currentAd.title }}
                            </h2>
                            <p class="ad-description">
                                {{ currentAd.description }}
                            </p>
                            <div class="ad-meta">
                                <span>{{ currentAd.offer_type || 'Limited offer' }}</span>
                                <span>{{ currentAd.is_external ? 'Partner deal' : 'VROOEM deal' }}</span>
                            </div>

                            <a v-if="currentAd.is_external" :href="currentAd.button_link" target="_blank" class="ad-cta">
                                {{ currentAd.button_text || 'View offer' }}
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                            <a v-else :href="`/${page.props.locale}${currentAd.button_link || ''}`" class="ad-cta">
                                {{ currentAd.button_text || 'View offer' }}
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </a>
                        </div>

                        <div class="ad-image"
                            :style="{ backgroundImage: `url(${currentAd.image_path || heroImage})` }"
                            :aria-label="currentAd.title || 'Advertisement'" role="img"></div>
                    </div>
                </Transition>

                <!-- Dot Indicators (Only show if multiple ads) -->
                <div v-if="advertisements.length > 1" class="flex justify-center gap-2 mt-6 sm:mt-8">
                    <button v-for="(ad, index) in advertisements" :key="ad.id" @click="setAd(index)"
                        class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full transition-all duration-300 focus:outline-none"
                        :class="[
                            currentIndex === index
                                ? 'bg-customPrimaryColor scale-110 w-6 sm:w-8'
                                : 'bg-gray-300 hover:bg-gray-400'
                        ]" :aria-label="`Go to slide ${index + 1}`"></button>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.ad-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.15fr) minmax(0, 0.85fr);
    gap: 2rem;
    align-items: stretch;
}


.ad-copy {
    background: #ffffff;
    border-radius: 26px;
    padding: 2.4rem;
    border: 1px solid rgba(148, 163, 184, 0.24);
    box-shadow: 0 20px 44px rgba(15, 23, 42, 0.08);
    display: flex;
    flex-direction: column;
    gap: 1.2rem;
}

.ad-pill {
    display: inline-flex;
    align-items: center;
    padding: 0.35rem 0.85rem;
    border-radius: 999px;
    background: rgba(21, 59, 79, 0.08);
    border: 1px solid rgba(21, 59, 79, 0.2);
    font-size: 0.75rem;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    width: fit-content;
    color: #153b4f;
}

.ad-title {
    font-size: clamp(1.75rem, 3vw, 2.8rem);
    font-weight: 700;
    line-height: 1.1;
    margin: 0;
    color: #0f172a;
}

.ad-description {
    font-size: 1rem;
    color: #475569;
    margin: 0;
    line-height: 1.6;
}

.ad-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: #64748b;
}

.ad-cta {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.85rem 1.6rem;
    border-radius: 999px;
    background: #153b4f;
    color: #ffffff;
    font-weight: 600;
    font-size: 0.95rem;
    box-shadow: 0 16px 30px rgba(21, 59, 79, 0.22);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    width: fit-content;
}

.ad-cta:hover {
    transform: translateY(-2px);
    box-shadow: 0 22px 42px rgba(21, 59, 79, 0.35);
}


.ad-image {
    border-radius: 26px;
    overflow: hidden;
    border: 1px solid rgba(148, 163, 184, 0.24);
    box-shadow: 0 20px 44px rgba(15, 23, 42, 0.14);
    background: #f8fafc;
    background-size: cover;
    background-position: center;
    min-height: 320px;
    aspect-ratio: 4 / 3;
}

/* Glassmorphism utility */
.backdrop-blur-md {
    backdrop-filter: blur(12px);
}

/* Fade Transition */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

@media (max-width: 1024px) {
    .ad-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .ad-copy {
        padding: 2rem 1.5rem;
    }

    .ad-image {
        aspect-ratio: 3 / 4;
    }
}
</style>
