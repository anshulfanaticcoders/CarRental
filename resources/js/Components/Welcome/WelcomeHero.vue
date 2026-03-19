<script setup>
import SearchBar from '@/Components/SearchBar.vue';

defineProps({
    heroBadge: { type: String, default: '' },
    animatedTagline: { type: String, required: true },
    heroSubtitle: { type: String, required: true },
    displayedText: { type: String, required: true },
    heroImage: { type: String, required: true },
    trustItems: { type: Array, default: () => [] },
});

const trustStats = [
    { id: 'providers', value: '800', suffix: '+', label: 'Providers Worldwide' },
    { id: 'countries', value: '180', suffix: '+', label: 'Countries Covered' },
    { id: 'rating', value: '4.8', suffix: '/5', label: 'Customer Rating' },
    { id: 'trips', value: '250K', suffix: '+', label: 'Trips Completed' },
];
</script>

<template>
    <section class="hero">
        <div class="hero-bg">
            <img :src="heroImage" alt="" aria-hidden="true" />
            <div class="hero-overlay"></div>
        </div>

        <div class="full-w-container hero-z">
            <div class="hero-content">
                <div v-if="heroBadge" class="hero-badge hero-fade hd1">
                    <span class="hero-badge-dot"></span>
                    {{ heroBadge }}
                </div>

                <h1 class="hero-title hero-fade hd2" v-html="animatedTagline"></h1>

                <p class="hero-subtitle hero-fade hd3">{{ heroSubtitle }}</p>

                <div class="hero-typewriter hero-fade hd3">
                    <span class="typewriter-dot"></span>
                    <span class="hero-typewriter-text">{{ displayedText }}</span>
                    <span class="cursor-blink">|</span>
                </div>

                <div class="hero-trust hero-fade hd4">
                    <span v-for="item in trustItems" :key="item.id" class="hero-trust-chip">
                        <svg v-if="item.icon === 'star'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                        </svg>
                        <svg v-else-if="item.icon === 'bag'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" />
                            <line x1="1" y1="10" x2="23" y2="10" />
                        </svg>
                        <svg v-else-if="item.icon === 'shield'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                        </svg>
                        <svg v-else-if="item.icon === 'support'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M15.05 5A5 5 0 0 1 19 8.95M15.05 1A9 9 0 0 1 23 8.94" />
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 3.06 5.18 2 2 0 0 1 5 3h3" />
                        </svg>
                        <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23" />
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                        </svg>
                        {{ item.label }}
                    </span>
                </div>
            </div>
        </div>

        <div class="hero-search hero-fade hd5 hero-z">
            <SearchBar class="hero-searchbar" :simple="true" />
        </div>
    </section>

    <div class="trust-bar">
        <div class="full-w-container">
            <div class="trust-bar-inner">
                <template v-for="(stat, index) in trustStats" :key="stat.id">
                    <div class="trust-stat trust-fade" :style="{ animationDelay: `${1.05 + (index * 0.12)}s` }">
                        <div class="trust-stat-number">
                            {{ stat.value }}<span>{{ stat.suffix }}</span>
                        </div>
                        <div class="trust-stat-label">{{ stat.label }}</div>
                    </div>
                    <div v-if="index < trustStats.length - 1" class="trust-divider"></div>
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped>
.hero {
    position: relative;
    z-index: 20;
    min-height: 100vh;
    padding-top: 8rem;
    padding-bottom: 5rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
    background: #0a1d28;
}

.hero-z {
    position: relative;
    z-index: 10;
}

.hero-bg {
    position: absolute;
    inset: 0;
    overflow: hidden;
}

.hero-bg img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center 40%;
    filter: saturate(1.05) brightness(0.5);
    animation: hero-zoom 20s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

.hero-overlay {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(100deg, rgba(10, 29, 40, 0.92) 0%, rgba(10, 29, 40, 0.65) 30%, rgba(10, 29, 40, 0.25) 55%, rgba(10, 29, 40, 0.1) 100%),
        linear-gradient(to bottom, rgba(10, 29, 40, 0.35) 0%, transparent 25%, transparent 75%, rgba(10, 29, 40, 0.6) 100%);
}

.hero-content {
    max-width: 640px;
    width: 100%;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.4rem 0.9rem 0.4rem 0.5rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.07);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #22d3ee;
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    margin-bottom: 1.75rem;
    backdrop-filter: blur(8px);
}

.hero-badge-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #22d3ee;
    box-shadow: 0 0 0 4px rgba(34, 211, 238, 0.2);
    animation: pulse-dot 2.4s ease-in-out infinite;
}

.hero-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: clamp(2.8rem, 4.5vw, 4.2rem);
    font-weight: 800;
    line-height: 1.06;
    color: #fff;
    letter-spacing: -0.025em;
    margin-bottom: 1.5rem;
    text-shadow: 0 4px 32px rgba(0, 0, 0, 0.3);
}

.hero-title :deep(.anim-title-word) {
    color: #22d3ee;
}

.hero-subtitle {
    font-size: 1.05rem;
    color: rgba(255, 255, 255, 0.65);
    max-width: 440px;
    line-height: 1.7;
    margin-bottom: 2rem;
    text-shadow: 0 2px 12px rgba(0, 0, 0, 0.2);
}

.hero-typewriter {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.65rem 1.25rem;
    border-radius: 999px;
    background: rgba(10, 29, 40, 0.65);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.85);
    font-size: 0.92rem;
    backdrop-filter: blur(12px);
    margin-bottom: 2.5rem;
}

.typewriter-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    flex-shrink: 0;
    background: #22d3ee;
}

.hero-typewriter-text {
    min-width: 20ch;
    white-space: nowrap;
}

.cursor-blink {
    animation: blink 0.7s infinite;
    font-weight: 700;
}

.hero-trust {
    display: flex;
    flex-wrap: wrap;
    gap: 0.6rem;
}

.hero-trust-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.85rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.08);
    color: rgba(255, 255, 255, 0.65);
    font-size: 0.8rem;
    font-weight: 500;
    backdrop-filter: blur(6px);
    transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}

.hero-trust-chip:hover {
    background: rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.85);
    border-color: rgba(255, 255, 255, 0.15);
}

.hero-trust-chip svg {
    width: 14px;
    height: 14px;
    color: #22d3ee;
    flex-shrink: 0;
}

.hero-search {
    z-index: 20;
    margin-top: 4rem;
}

.hero-searchbar :deep(.full-w-container) {
    padding-bottom: 0 !important;
}

.hero-searchbar :deep(.search_bar) {
    background: rgba(255, 255, 255, 0.08) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    border-radius: 28px !important;
    box-shadow: 0 32px 80px rgba(10, 29, 40, 0.35) !important;
}

.hero-searchbar :deep(.search_bar::before) {
    content: none !important;
}

.hero-searchbar :deep(.search_bar > .flex > .column:last-child form) {
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

.hero-fade {
    opacity: 0;
    transform: translateY(28px);
    animation: hero-enter 0.9s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

.hd1 {
    animation-delay: 0.2s;
}

.hd2 {
    animation-delay: 0.35s;
}

.hd3 {
    animation-delay: 0.5s;
}

.hd4 {
    animation-delay: 0.65s;
}

.hd5 {
    animation-delay: 0.85s;
}

.trust-bar {
    padding: 2.5rem 0;
    background: #0a1d28;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    position: relative;
    z-index: 5;
}

.trust-bar-inner {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 3.5rem;
    flex-wrap: wrap;
}

.trust-stat {
    text-align: center;
}

.trust-fade {
    opacity: 0;
    transform: translateY(24px);
    animation: hero-enter 0.75s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

.trust-stat-number {
    font-size: 2rem;
    font-weight: 800;
    color: #fff;
    letter-spacing: -0.03em;
    line-height: 1;
}

.trust-stat-number span {
    color: #22d3ee;
}

.trust-stat-label {
    font-size: 0.76rem;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.4);
    margin-top: 0.35rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.trust-divider {
    width: 1px;
    height: 36px;
    background: rgba(255, 255, 255, 0.08);
}

@keyframes hero-zoom {
    from {
        transform: scale(1.08);
    }

    to {
        transform: scale(1);
    }
}

@keyframes pulse-dot {
    0%,
    100% {
        box-shadow: 0 0 0 4px rgba(34, 211, 238, 0.2);
    }

    50% {
        box-shadow: 0 0 0 7px rgba(34, 211, 238, 0.06);
    }
}

@keyframes blink {
    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: 0;
    }
}

@keyframes hero-enter {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 1023px) {
    .trust-bar-inner {
        gap: 2rem;
    }

    .trust-stat-number {
        font-size: 1.65rem;
    }
}

@media (max-width: 767px) {
    .hero {
        min-height: auto;
        padding: 7rem 0 3rem;
    }

    .hero-title {
        font-size: clamp(2rem, 7vw, 2.8rem);
    }

    .hero-trust {
        gap: 0.4rem;
    }

    .hero-trust-chip {
        font-size: 0.74rem;
        padding: 0.3rem 0.65rem;
    }

    .hero-search {
        margin-top: 2rem;
    }

    .trust-bar-inner {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }

    .trust-stat-number {
        font-size: 1.4rem;
    }

    .trust-divider {
        display: none;
    }
}

@media (max-width: 480px) {
    .hero-title {
        font-size: clamp(1.7rem, 8vw, 2.2rem);
        line-height: 1.15;
    }

    .hero-subtitle {
        font-size: 0.92rem;
    }

    .hero-typewriter {
        font-size: 0.82rem;
    }

    .trust-bar-inner {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.25rem;
    }

    .trust-divider {
        display: none;
    }

    .trust-stat {
        text-align: center;
    }

    .trust-stat-number {
        font-size: 1.25rem;
    }

    .trust-stat-label {
        font-size: 0.68rem;
    }
}
</style>
