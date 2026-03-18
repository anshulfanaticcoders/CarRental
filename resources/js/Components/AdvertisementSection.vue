<script setup>
import { onMounted, ref, computed, onUnmounted, watch } from 'vue';
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
const isPaused = ref(false);
const progressKey = ref(0);
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

const prevAd = () => {
    if (props.advertisements.length > 1) {
        currentIndex.value = (currentIndex.value - 1 + props.advertisements.length) % props.advertisements.length;
    }
};

const setAd = (index) => {
    currentIndex.value = index;
    restartTimer();
};

const startTimer = () => {
    isPaused.value = false;
    if (props.advertisements.length > 1) {
        intervalId = setInterval(nextAd, 7000);
    }
};

const stopTimer = () => {
    isPaused.value = true;
    if (intervalId) clearInterval(intervalId);
};

const restartTimer = () => {
    stopTimer();
    progressKey.value++;
    startTimer();
};

// Reset progress animation on index change
watch(currentIndex, () => {
    progressKey.value++;
});


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
        <div class="full-w-container ad-content-wrapper sr-reveal">
            <!-- Section Header -->
            <div class="mb-6 sm:mb-8 lg:mb-10">
                <span class="text-[1rem] sm:text-[1.15rem] text-customPrimaryColor tracking-[0.2em] uppercase">Exclusive Offers</span>
                <h3 class="text-customDarkBlackColor text-2xl sm:text-3xl lg:text-[2.5rem] font-bold mt-2 max-[768px]:text-[1.75rem] max-[768px]:mt-[1rem]">
                    Don't Miss Out on These Deals
                </h3>
            </div>

            <!-- Main Ad Card -->
            <div class="ad-showcase" @mouseenter="stopTimer" @mouseleave="startTimer">
                <Transition name="slide" mode="out-in">
                    <div :key="currentAd.id" class="ad-card">
                        <!-- Image Side -->
                        <div class="ad-visual">
                            <img
                                :src="currentAd.image_path || heroImage"
                                :alt="currentAd.title || 'Advertisement'"
                                class="ad-visual-img"
                            />
                            <div class="ad-visual-overlay"></div>

                            <!-- Promo Badge -->
                            <div v-if="currentAd.is_promo && currentAd.discount_percentage > 0"
                                class="ad-promo-badge">
                                <span class="ad-promo-amount">{{ Math.round(currentAd.discount_percentage) }}%</span>
                                <span class="ad-promo-label">OFF</span>
                            </div>
                        </div>

                        <!-- Content Side -->
                        <div class="ad-content">
                            <div class="ad-content-inner">
                                <!-- Tag -->
                                <div class="ad-tag-row">
                                    <span class="ad-tag">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                        </svg>
                                        {{ currentAd.offer_type || 'Exclusive' }}
                                    </span>
                                    <span class="ad-source">
                                        {{ currentAd.is_external ? 'Partner' : 'VROOEM' }}
                                    </span>
                                </div>

                                <!-- Title -->
                                <h2 class="ad-title">{{ currentAd.title }}</h2>

                                <!-- Description -->
                                <p class="ad-description">{{ currentAd.description }}</p>

                                <!-- CTA -->
                                <a v-if="currentAd.is_external"
                                    :href="currentAd.button_link"
                                    target="_blank"
                                    class="ad-cta group">
                                    <span>{{ currentAd.button_text || 'View Offer' }}</span>
                                    <svg class="w-5 h-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                                <a v-else
                                    :href="`/${page.props.locale}${currentAd.button_link || ''}`"
                                    class="ad-cta group">
                                    <span>{{ currentAd.button_text || 'View Offer' }}</span>
                                    <svg class="w-5 h-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </a>
                            </div>

                            <!-- Nav Arrows (inside content, bottom) -->
                            <div v-if="advertisements.length > 1" class="ad-nav">
                                <button @click="prevAd" class="ad-nav-btn" aria-label="Previous offer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                </button>
                                <div class="ad-progress-group">
                                    <button
                                        v-for="(ad, index) in advertisements"
                                        :key="ad.id"
                                        @click="setAd(index)"
                                        class="ad-progress-track"
                                        :aria-label="`Go to offer ${index + 1}`"
                                    >
                                        <span
                                            class="ad-progress-fill"
                                            :class="{
                                                'ad-progress-active': currentIndex === index && !isPaused,
                                                'ad-progress-done': index < currentIndex || (currentIndex === index && isPaused),
                                                'ad-progress-idle': index > currentIndex
                                            }"
                                            :key="currentIndex === index ? progressKey : index"
                                        ></span>
                                    </button>
                                </div>
                                <button @click="nextAd" class="ad-nav-btn" aria-label="Next offer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>
    </section>
</template>

<style scoped>
.sr-reveal { visibility: hidden; }

/* ─── Main Showcase Container ─── */
.ad-showcase {
    position: relative;
}

/* ─── Card Layout ─── */
.ad-card {
    display: grid;
    grid-template-columns: 1.1fr 0.9fr;
    border-radius: 24px;
    overflow: hidden;
    background: #0c2233;
    box-shadow:
        0 25px 60px rgba(12, 34, 51, 0.25),
        0 0 0 1px rgba(255, 255, 255, 0.05);
    min-height: 420px;
}

/* ─── Image / Visual ─── */
.ad-visual {
    position: relative;
    overflow: hidden;
}

.ad-visual-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.7s cubic-bezier(0.33, 1, 0.68, 1);
}

.ad-card:hover .ad-visual-img {
    transform: scale(1.04);
}

.ad-visual-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to right,
        transparent 50%,
        rgba(12, 34, 51, 0.6) 85%,
        rgba(12, 34, 51, 0.95) 100%
    );
    pointer-events: none;
}

/* ─── Promo Badge ─── */
.ad-promo-badge {
    position: absolute;
    top: 1.25rem;
    left: 1.25rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: linear-gradient(135deg, #dc2626, #ef4444);
    box-shadow: 0 8px 24px rgba(220, 38, 38, 0.45);
    z-index: 2;
    animation: badge-pop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) 0.3s both;
}

.ad-promo-amount {
    font-size: 1.25rem;
    font-weight: 800;
    color: #fff;
    line-height: 1;
    letter-spacing: -0.02em;
}

.ad-promo-label {
    font-size: 0.6rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.85);
    letter-spacing: 0.15em;
    text-transform: uppercase;
    line-height: 1;
    margin-top: 1px;
}

@keyframes badge-pop {
    from { transform: scale(0); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

/* ─── Content Side ─── */
.ad-content {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 2.5rem;
    position: relative;
}

.ad-content::before {
    content: '';
    position: absolute;
    top: 10%;
    right: 60%;
    width: 200px;
    height: 200px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(46, 167, 173, 0.12), transparent 70%);
    pointer-events: none;
}

.ad-content-inner {
    display: flex;
    flex-direction: column;
    gap: 1.15rem;
    flex: 1;
    justify-content: center;
}

/* ─── Tag / Source ─── */
.ad-tag-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.ad-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.3rem 0.75rem;
    border-radius: 999px;
    background: rgba(46, 167, 173, 0.15);
    border: 1px solid rgba(46, 167, 173, 0.3);
    color: #5dd8d0;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.ad-source {
    font-size: 0.68rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.35);
}

/* ─── Title ─── */
.ad-title {
    font-size: clamp(1.6rem, 2.5vw, 2.4rem);
    font-weight: 700;
    line-height: 1.15;
    color: #ffffff;
    margin: 0;
    letter-spacing: -0.01em;
}

/* ─── Description ─── */
.ad-description {
    font-size: 0.95rem;
    line-height: 1.65;
    color: rgba(255, 255, 255, 0.55);
    margin: 0;
    max-width: 38ch;
}

/* ─── CTA Button ─── */
.ad-cta {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.8rem 1.6rem;
    border-radius: 12px;
    background: linear-gradient(135deg, #2ea7ad, #3bc0c8);
    color: #ffffff;
    font-weight: 600;
    font-size: 0.9rem;
    letter-spacing: 0.01em;
    transition: all 0.3s cubic-bezier(0.33, 1, 0.68, 1);
    width: fit-content;
    box-shadow: 0 8px 24px rgba(46, 167, 173, 0.3);
    margin-top: 0.5rem;
}

.ad-cta:hover {
    background: linear-gradient(135deg, #34b8bf, #45d4dc);
    box-shadow: 0 12px 32px rgba(46, 167, 173, 0.45);
    transform: translateY(-2px);
}

/* ─── Navigation ─── */
.ad-nav {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-top: 1.75rem;
    padding-top: 1.25rem;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.ad-nav-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    transition: all 0.25s ease;
    flex-shrink: 0;
}

.ad-nav-btn:hover {
    background: rgba(255, 255, 255, 0.12);
    color: #fff;
    border-color: rgba(255, 255, 255, 0.2);
}

/* ─── Progress Bars ─── */
.ad-progress-group {
    display: flex;
    gap: 6px;
    flex: 1;
}

.ad-progress-track {
    flex: 1;
    height: 3px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.1);
    overflow: hidden;
    cursor: pointer;
    transition: background 0.2s ease;
    padding: 0;
    border: none;
}

.ad-progress-track:hover {
    background: rgba(255, 255, 255, 0.18);
}

.ad-progress-fill {
    display: block;
    height: 100%;
    border-radius: 999px;
    background: #2ea7ad;
    transform-origin: left;
}

.ad-progress-active {
    animation: progress-sweep 7s linear forwards;
}

.ad-progress-done {
    width: 100%;
    background: rgba(46, 167, 173, 0.6);
}

.ad-progress-idle {
    width: 0;
}

@keyframes progress-sweep {
    from { width: 0; }
    to { width: 100%; }
}

/* ─── Slide Transition ─── */
.slide-enter-active {
    transition: all 0.5s cubic-bezier(0.33, 1, 0.68, 1);
}
.slide-leave-active {
    transition: all 0.35s cubic-bezier(0.33, 1, 0.68, 1);
}
.slide-enter-from {
    opacity: 0;
    transform: translateX(30px);
}
.slide-leave-to {
    opacity: 0;
    transform: translateX(-30px);
}

/* ─── Responsive ─── */
@media (max-width: 1024px) {
    .ad-card {
        grid-template-columns: 1fr;
        min-height: auto;
    }

    .ad-visual {
        height: 280px;
    }

    .ad-visual-overlay {
        background: linear-gradient(
            to bottom,
            transparent 40%,
            rgba(12, 34, 51, 0.8) 100%
        );
    }

    .ad-content {
        padding: 2rem;
    }
}

@media (max-width: 640px) {
    .ad-visual {
        height: 220px;
    }

    .ad-content {
        padding: 1.5rem;
    }

    .ad-title {
        font-size: 1.4rem;
    }

    .ad-promo-badge {
        width: 60px;
        height: 60px;
        top: 1rem;
        left: 1rem;
    }

    .ad-promo-amount {
        font-size: 1.05rem;
    }
}
</style>
