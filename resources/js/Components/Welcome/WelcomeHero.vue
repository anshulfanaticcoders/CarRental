<script setup>
import SearchBar from '@/Components/SearchBar.vue';

defineProps({
    heroBadge: {
        type: String,
        default: '',
    },
    animatedTagline: {
        type: String,
        required: true,
    },
    heroSubtitle: {
        type: String,
        required: true,
    },
    displayedText: {
        type: String,
        required: true,
    },
    heroImage: {
        type: String,
        required: true,
    },
    trustItems: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <section class="hero hero_section relative z-20 overflow-visible bg-[#0e1b26]">
        <div class="absolute inset-0 overflow-hidden">
            <img :src="heroImage" alt="" aria-hidden="true" class="hero-image absolute inset-0 h-full w-full object-cover" />
            <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(10,22,32,0.78)_0%,rgba(10,22,32,0.52)_30%,rgba(10,22,32,0.18)_55%,rgba(10,22,32,0.05)_100%),linear-gradient(180deg,rgba(10,22,32,0.22)_0%,rgba(10,22,32,0.0)_35%,rgba(10,22,32,0.0)_65%,rgba(10,22,32,0.40)_100%)]"></div>
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_5%_50%,rgba(10,22,32,0.35),transparent_50%),radial-gradient(circle_at_80%_20%,rgba(255,180,80,0.06),transparent_50%)]"></div>
            <div class="hero-orb hero-orb-one"></div>
            <div class="hero-orb hero-orb-two"></div>
        </div>

        <div class="full-w-container relative z-10 w-full">
            <div class="hero-left">

                <h1
                    class="hero-title reveal-up d1"
                    v-html="animatedTagline"
                ></h1>

                <p class="hero-subtitle reveal-up d2">
                    {{ heroSubtitle }}
                </p>

                <div class="hero-typewriter reveal-left d3">
                    <span class="hero-dot"></span>
                    <span class="min-w-[20ch] whitespace-nowrap">{{ displayedText }}</span>
                    <span class="cursor-blink font-bold">|</span>
                </div>

                <div class="hero-trust reveal-fade d4">
                    <span
                        v-for="item in trustItems"
                        :key="item.id"
                        class="hero-trust-item"
                    >
                        <svg v-if="item.icon === 'star'" viewBox="0 0 24 24" aria-hidden="true">
                            <path
                                d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27z"
                                fill="currentColor"
                            />
                        </svg>
                        <svg v-else-if="item.icon === 'bag'" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M5 6h14l-1.2 12.5a2 2 0 0 1-2 1.8H8.2a2 2 0 0 1-2-1.8L5 6z" fill="currentColor" />
                            <path d="M9 6V5a3 3 0 0 1 6 0v1" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                        </svg>
                        <svg v-else-if="item.icon === 'clock'" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 2a9 9 0 0 1 9 9 9 9 0 0 1-9 9 9 9 0 0 1-9-9 9 9 0 0 1 9-9z" fill="none" stroke="currentColor" stroke-width="1.5" />
                            <path d="M12 6v5l3 2" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <svg v-else-if="item.icon === 'shield'" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 3l7 4v6c0 4.2-2.8 7.5-7 9-4.2-1.5-7-4.8-7-9V7l7-4z" fill="none" stroke="currentColor" stroke-width="1.5" />
                            <path d="M9 12l2 2 4-4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <svg v-else-if="item.icon === 'support'" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 7v5l3 2" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M12 2a10 10 0 1 0 10 10" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                            <path d="M21 2l-4 4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                        </svg>
                        <svg v-else viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 5v14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                            <path d="M6 9l6-4 6 4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M6 15l6 4 6-4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        {{ item.label }}
                    </span>
                </div>
            </div>
        </div>

        <div class="hero-search-shell reveal-scale d5 relative z-10 w-full">
            <SearchBar class="hero-searchbar" :simple="true" />
        </div>
    </section>
</template>

<style scoped>
/* Hero section — no centering, content flows from top */
.hero {
    min-height: 100vh;
    padding-top: 8rem;
    padding-bottom: 5rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.hero-image {
    object-position: center 55%;
    filter: saturate(1.08) brightness(0.72);
    animation: hero-bg-zoom 20s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

.hero-orb {
    position: absolute;
    z-index: 1;
    border-radius: 9999px;
    opacity: 0.4;
    mix-blend-mode: screen;
    pointer-events: none;
}

.hero-orb-one {
    top: -4.5rem;
    left: 6%;
    width: 17.5rem;
    height: 17.5rem;
    background: radial-gradient(circle, rgba(46, 167, 173, 0.22), transparent 70%);
}

.hero-orb-two {
    right: 11%;
    bottom: 9rem;
    width: 12.5rem;
    height: 12.5rem;
    background: radial-gradient(circle, rgba(255, 200, 120, 0.15), transparent 70%);
}

.hero-left {
    padding: 0;
    max-width: 680px;
    width: 100%;
}

.hero-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: clamp(2.7rem, 4.2vw, 4rem);
    font-weight: 700;
    line-height: 1.08;
    color: #ffffff;
    margin-bottom: 1.6rem;
    letter-spacing: -0.01em;
    text-shadow: 0 24px 60px rgba(2, 10, 16, 0.45);
}

.hero-title :deep(.anim-title-word) {
    color: #22d3ee;
}

.hero-subtitle {
    font-size: 1.08rem;
    color: rgba(255, 255, 255, 0.75);
    max-width: 28rem;
    line-height: 1.65;
    margin-bottom: 1.9rem;
}

.hero-typewriter {
    display: inline-flex;
    align-items: center;
    gap: 1rem;
    padding: 0.8rem 1.35rem;
    border-radius: 999px;
    background: rgba(8, 22, 32, 0.72);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: rgba(255, 255, 255, 0.85);
    font-size: 0.98rem;
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.08), 0 12px 24px rgba(5, 14, 22, 0.28);
    min-height: 2.6rem;
}

.hero-trust {
    display: flex;
    gap: 1.35rem;
    flex-wrap: wrap;
    margin-top: 2.6rem;
    color: rgba(255, 255, 255, 0.65);
    font-size: 0.93rem;
}

.hero-trust-item {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.hero-trust-item svg {
    width: 18px;
    height: 18px;
    color: rgba(255, 255, 255, 0.7);
    flex-shrink: 0;
}

.hero-dot {
    width: 9px;
    height: 9px;
    flex-shrink: 0;
    border-radius: 9999px;
    background: #22d3ee;
    box-shadow: 0 0 0 6px rgba(34, 211, 238, 0.25);
    animation: premium-pulse 2.4s ease-in-out infinite;
}

.cursor-blink {
    animation: hero-blink 0.7s infinite;
}

.reveal-up,
.reveal-left,
.reveal-fade,
.reveal-scale {
    animation-fill-mode: forwards;
}

.reveal-up {
    opacity: 0;
    transform: translateY(32px);
    animation-name: reveal-up;
    animation-duration: 1s;
    animation-timing-function: cubic-bezier(0.22, 1, 0.36, 1);
}

.reveal-left {
    opacity: 0;
    transform: translateX(-40px);
    animation-name: reveal-left;
    animation-duration: 1s;
    animation-timing-function: cubic-bezier(0.22, 1, 0.36, 1);
}

.reveal-fade {
    opacity: 0;
    animation-name: reveal-fade;
    animation-duration: 1.2s;
    animation-timing-function: ease;
}

.reveal-scale {
    opacity: 0;
    transform: scale(0.92);
    animation-name: reveal-scale;
    animation-duration: 1s;
    animation-timing-function: cubic-bezier(0.22, 1, 0.36, 1);
}

.d1 {
    animation-delay: 0.3s;
}

.d2 {
    animation-delay: 0.55s;
}

.d3 {
    animation-delay: 0.8s;
}

.d4 {
    animation-delay: 1.05s;
}

.d5 {
    animation-delay: 1.3s;
}

.hero-search-shell {
    z-index: 20;
    margin-top: 6rem;
}

.hero-searchbar :deep(.full-w-container) {
    padding-bottom: 0 !important;
}

.hero-searchbar :deep(.search_bar) {
    border-radius: 24px !important;
    box-shadow: 0 28px 80px rgba(6, 24, 38, 0.28) !important;
}

.hero-searchbar :deep(.search_bar::before) {
    content: none !important;
}

.hero-searchbar :deep(.search_bar form) {
    box-shadow: none !important;
}

.hero-searchbar :deep(.search-results) {
    top: calc(100% + 16px) !important;
    left: 0 !important;
    width: 100% !important;
    border-radius: 18px !important;
}

.hero-searchbar :deep(.dp__menu) {
    z-index: 99999 !important;
}

@keyframes hero-bg-zoom {
    from {
        transform: scale(1.1);
    }

    to {
        transform: scale(1);
    }
}

@keyframes premium-pulse {
    0%,
    100% {
        background: #22d3ee;
        box-shadow: 0 0 0 6px rgba(34, 211, 238, 0.25);
    }

    50% {
        background: #2ea7ad;
        box-shadow: 0 0 0 8px rgba(46, 167, 173, 0.25);
    }
}

@keyframes hero-blink {
    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: 0;
    }
}

@keyframes reveal-up {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes reveal-left {
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes reveal-fade {
    to {
        opacity: 1;
    }
}

@keyframes reveal-scale {
    to {
        opacity: 1;
        transform: scale(1);
    }
}

@media (max-width: 1023px) {
    .hero {
        min-height: auto;
        padding-top: 6rem;
        padding-bottom: 2rem;
    }

    .hero-left {
        padding: 0;
    }

    .hero-title {
        font-size: clamp(2.2rem, 5vw, 3rem);
    }

    .hero-subtitle {
        max-width: 100%;
        font-size: 1rem;
    }

    .hero-trust {
        gap: 1rem;
        font-size: 0.88rem;
    }

    .hero-search-shell {
        margin-top: 1.5rem;
    }

    .hero-orb-one {
        width: 15rem;
        height: 15rem;
    }

    .hero-orb-two {
        right: 2rem;
        bottom: 18rem;
        width: 11rem;
        height: 11rem;
    }
}

@media (max-width: 767px) {
    .hero {
        padding-top: 8rem;
        padding-bottom: 2.5rem;
    }

    .hero-left {
        padding: 0;
        max-width: none;
    }

    .hero-title {
        font-size: clamp(2rem, 6vw, 2.6rem);
    }

    .hero-subtitle {
        font-size: 0.95rem;
    }

    .hero-typewriter {
        font-size: 0.85rem;
    }

    .hero-trust {
        gap: 0.75rem 1.25rem;
        font-size: 0.85rem;
    }

    .hero-trust-item {
        flex: 0 0 calc(50% - 0.625rem);
    }

    .hero-orb-one {
        width: 12.5rem;
        height: 12.5rem;
    }

    .hero-orb-two {
        width: 9rem;
        height: 9rem;
    }

    .hero-searchbar :deep(.search-results) {
        top: calc(100% + 10px) !important;
    }
}

@media (max-width: 480px) {
    .hero-left {
        padding: 0;
    }

    .hero-title {
        font-size: clamp(1.75rem, 7vw, 2.2rem);
        line-height: 1.15;
    }

    .hero-subtitle {
        font-size: 0.9rem;
        line-height: 1.55;
        margin-bottom: 1.25rem;
    }

    .hero-typewriter {
        font-size: 0.8rem;
        padding: 0.6rem 1rem;
        gap: 0.6rem;
    }

    .hero-trust {
        gap: 0.6rem 0.75rem;
        font-size: 0.8rem;
        margin-top: 1.5rem;
    }

    .hero-trust-item {
        flex: 0 0 calc(50% - 0.375rem);
    }

    .hero-trust-item svg {
        width: 15px;
        height: 15px;
    }
}
</style>
