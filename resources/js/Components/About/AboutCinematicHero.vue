<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { ArrowRight, CheckCircle2 } from 'lucide-vue-next';
import AboutStatsBar from './AboutStatsBar.vue';
import { useSmoothAnchorScroll } from '@/composables/useSmoothAnchorScroll';

const props = defineProps({
    hero: {
        type: Object,
        required: true,
    },
    stats: {
        type: Array,
        default: () => [],
    },
});

const root = ref(null);
const { scrollToAnchor } = useSmoothAnchorScroll();
let removePointerListener = null;

const titleWords = computed(() => {
    let letterIndex = 0;

    return (props.hero.title || '').trim().split(/\s+/).filter(Boolean).map((word) => ({
        word,
        letters: Array.from(word).map((letter) => ({
            letter,
            index: letterIndex++,
        })),
    }));
});

onMounted(() => {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reducedMotion || !root.value) return;

    const onPointerMove = (event) => {
        const x = event.clientX / Math.max(window.innerWidth, 1) - 0.5;
        const y = event.clientY / Math.max(window.innerHeight, 1) - 0.5;
        root.value.style.setProperty('--mx', x.toFixed(3));
        root.value.style.setProperty('--my', y.toFixed(3));
    };

    window.addEventListener('pointermove', onPointerMove, { passive: true });
    removePointerListener = () => window.removeEventListener('pointermove', onPointerMove);
});

onUnmounted(() => {
    removePointerListener?.();
});
</script>

<template>
    <section id="top" ref="root" class="about-hero" aria-labelledby="about-hero-title">
        <div class="about-hero-bg" aria-hidden="true">
            <img :src="hero.imageUrl" :alt="hero.imageAlt" fetchpriority="high" decoding="async" />
            <div class="about-hero-shade"></div>
        </div>

        <div class="full-w-container about-hero-grid">
            <div class="about-hero-copy">
                <div class="about-badge about-reveal">
                    <span class="about-badge-dot" aria-hidden="true"></span>
                    {{ hero.badge }}
                </div>

                <h1 id="about-hero-title" class="about-hero-title" :aria-label="hero.title">
                    <span
                        v-for="(word, wordIndex) in titleWords"
                        :key="`${word.word}-${wordIndex}`"
                        class="about-word"
                        aria-hidden="true"
                    >
                        <span
                            v-for="letter in word.letters"
                            :key="`${letter.letter}-${letter.index}`"
                            class="about-letter"
                            :class="{ 'about-letter-accent': 'mile'.includes(letter.letter.toLowerCase()) }"
                            :style="{ '--i': letter.index }"
                        >
                            {{ letter.letter }}
                        </span>
                    </span>
                </h1>

                <div class="about-hero-lede about-reveal" v-html="hero.content"></div>

                <div class="about-hero-actions about-reveal" aria-label="About page actions">
                    <a
                        v-if="hero.primaryButtonUrl"
                        class="about-btn about-btn-primary"
                        :href="hero.primaryButtonUrl"
                        @click="scrollToAnchor($event, hero.primaryButtonUrl)"
                    >
                        {{ hero.primaryButtonText }}
                        <ArrowRight :size="18" aria-hidden="true" />
                    </a>
                    <a
                        v-if="hero.secondaryButtonUrl"
                        class="about-btn about-btn-secondary"
                        :href="hero.secondaryButtonUrl"
                        @click="scrollToAnchor($event, hero.secondaryButtonUrl)"
                    >
                        {{ hero.secondaryButtonText }}
                    </a>
                </div>
            </div>

            <div class="about-hero-deck" aria-label="Cinematic rental travel imagery">
                <div class="about-image-stack">
                    <figure class="about-float-card about-float-main">
                        <img :src="hero.panelImageUrl" :alt="hero.panelImageAlt" loading="eager" decoding="async" />
                        <figcaption class="about-float-caption">
                            <span>
                                <strong>{{ hero.panelTitle }}</strong>
                                <span>{{ hero.panelText }}</span>
                            </span>
                            <span class="about-caption-icon" aria-hidden="true">
                                <CheckCircle2 :size="19" />
                            </span>
                        </figcaption>
                    </figure>

                    <figure class="about-float-card about-float-side">
                        <img :src="hero.sideImageUrl" :alt="hero.sideImageAlt" loading="lazy" decoding="async" />
                    </figure>
                </div>
            </div>
        </div>

        <div class="full-w-container">
            <div class="about-hero-stats about-reveal">
                <AboutStatsBar :stats="stats" />
            </div>
        </div>
    </section>
</template>

<style scoped>
.about-hero {
    --mx: 0;
    --my: 0;
    position: relative;
    isolation: isolate;
    min-height: 92svh;
    display: grid;
    align-items: end;
    padding: 7.8rem 0 clamp(2.25rem, 5vw, 4.2rem);
    overflow: hidden;
    color: #ffffff;
}

.about-hero-bg,
.about-hero-bg img,
.about-hero-shade {
    position: absolute;
    inset: 0;
}

.about-hero-bg {
    z-index: -2;
    overflow: hidden;
    background: #06131d;
}

.about-hero-bg img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    filter: brightness(0.56) saturate(1.1) contrast(1.06);
    transform: scale(1.08) translate(calc(var(--mx) * -10px), calc(var(--my) * -10px));
    animation: aboutHeroDrift 18s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    transition: transform 420ms cubic-bezier(0.22, 1, 0.36, 1);
}

.about-hero-shade {
    z-index: 1;
    background:
        linear-gradient(100deg, rgba(5, 17, 26, 0.94) 0%, rgba(8, 30, 44, 0.78) 34%, rgba(8, 30, 44, 0.2) 72%),
        linear-gradient(to top, rgba(5, 17, 26, 0.96), rgba(5, 17, 26, 0.16) 50%, rgba(5, 17, 26, 0.78));
}

.about-hero-grid {
    position: relative;
    z-index: 2;
    display: grid;
    gap: clamp(2rem, 5vw, 5rem);
    align-items: end;
}

.about-hero-copy {
    max-width: 840px;
    padding-bottom: 1rem;
}

.about-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.62rem;
    min-height: 38px;
    padding: 0.42rem 0.9rem 0.42rem 0.5rem;
    border: 1px solid rgba(255, 255, 255, 0.13);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.07);
    color: #22d3ee;
    font-family: var(--jakarta-font-family, "Plus Jakarta Sans", sans-serif);
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    backdrop-filter: blur(18px) saturate(1.3);
    box-shadow: 0 18px 42px rgba(2, 12, 20, 0.24);
}

.about-badge-dot {
    width: 9px;
    height: 9px;
    border-radius: 999px;
    background: #22d3ee;
    box-shadow: 0 0 0 6px rgba(34, 211, 238, 0.16), 0 0 28px rgba(34, 211, 238, 0.58);
}

.about-hero-title {
    max-width: 760px;
    margin: 1.15rem 0 1rem;
    color: #ffffff;
    font-family: var(--jakarta-font-family, "Plus Jakarta Sans", sans-serif);
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    column-gap: 0.24em;
    row-gap: 0.01em;
    font-size: clamp(2.35rem, 5.75vw, 4.25rem);
    font-weight: 800;
    line-height: 1.02;
    letter-spacing: 0;
    text-wrap: balance;
}

.about-word {
    display: inline-flex;
    white-space: nowrap;
}

.about-letter {
    display: inline-block;
    color: rgba(255, 255, 255, 0.98);
    opacity: 0;
    filter: blur(12px);
    transform: translate3d(0, 34px, 0) rotateX(34deg);
    animation: aboutLetterRise 820ms cubic-bezier(0.16, 1, 0.3, 1) forwards;
    animation-delay: calc(90ms + (var(--i) * 28ms));
    will-change: transform, opacity, filter;
}

.about-hero-title:hover .about-letter {
    color: #eaffff;
    transform: translate3d(0, -2px, 0);
    transition: color 300ms cubic-bezier(0.22, 1, 0.36, 1), transform 300ms cubic-bezier(0.22, 1, 0.36, 1);
}

.about-letter-accent {
    color: #22d3ee;
}

.about-hero-lede {
    max-width: 670px;
    margin: 0;
    color: rgba(255, 255, 255, 0.75);
    font-size: clamp(0.98rem, 1.25vw, 1.08rem);
    line-height: 1.68;
    text-wrap: pretty;
}

.about-hero-lede :deep(p) {
    margin: 0;
}

.about-hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.8rem;
    margin-top: 2rem;
}

.about-btn {
    position: relative;
    min-height: 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.65rem;
    padding: 0.86rem 1.25rem;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 999px;
    font-family: var(--jakarta-font-family, "Plus Jakarta Sans", sans-serif);
    font-size: 0.92rem;
    font-weight: 800;
    text-decoration: none;
    transition: border-color 300ms cubic-bezier(0.22, 1, 0.36, 1), background-color 300ms cubic-bezier(0.22, 1, 0.36, 1), box-shadow 300ms cubic-bezier(0.22, 1, 0.36, 1), color 300ms cubic-bezier(0.22, 1, 0.36, 1), transform 300ms cubic-bezier(0.22, 1, 0.36, 1);
}

.about-btn::before {
    content: "";
    position: absolute;
    inset: 0;
    opacity: 0;
    background: linear-gradient(110deg, transparent 20%, rgba(255, 255, 255, 0.55), transparent 78%);
    transform: translateX(-120%);
    transition: opacity 280ms cubic-bezier(0.22, 1, 0.36, 1), transform 640ms cubic-bezier(0.16, 1, 0.3, 1);
}

.about-btn-primary {
    color: #06202f;
    background: linear-gradient(135deg, #22d3ee, #9df5ff);
    box-shadow: 0 18px 44px rgba(34, 211, 238, 0.24);
}

.about-btn-secondary {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(14px);
}

.about-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 22px 58px rgba(34, 211, 238, 0.22);
}

.about-btn:hover::before {
    opacity: 0.58;
    transform: translateX(120%);
}

.about-hero-deck {
    display: grid;
    gap: 1rem;
    align-items: end;
}

.about-image-stack {
    position: relative;
    min-height: 430px;
    display: none;
    opacity: 0;
    filter: blur(14px) saturate(0.88);
    clip-path: inset(8% 0 10% 0 round 32px);
    transform: translate3d(24px, 32px, 0) scale(0.985);
    animation: aboutDeckEnter 960ms cubic-bezier(0.16, 1, 0.3, 1) 280ms forwards;
    will-change: opacity, filter, clip-path, transform;
}

.about-float-card {
    position: absolute;
    margin: 0;
    overflow: hidden;
    opacity: 0;
    filter: blur(10px) saturate(0.92);
    clip-path: inset(10% 10% 10% 10% round 28px);
    border: 1px solid rgba(255, 255, 255, 0.13);
    border-radius: 28px;
    background: rgba(255, 255, 255, 0.06);
    box-shadow: 0 24px 70px rgba(3, 15, 24, 0.42);
    backdrop-filter: blur(18px) saturate(1.25);
    transform:
        translate3d(calc(var(--mx) * var(--depth-x, 14px)), calc(var(--my) * var(--depth-y, 14px)), 0)
        rotate(var(--tilt, 0deg));
    animation: aboutCardReveal 820ms cubic-bezier(0.16, 1, 0.3, 1) 460ms forwards;
    transition: border-color 300ms cubic-bezier(0.22, 1, 0.36, 1), box-shadow 300ms cubic-bezier(0.22, 1, 0.36, 1), transform 500ms cubic-bezier(0.22, 1, 0.36, 1);
}

.about-float-card:hover {
    border-color: rgba(34, 211, 238, 0.42);
    box-shadow: 0 30px 90px rgba(3, 15, 24, 0.58), 0 0 32px rgba(34, 211, 238, 0.32);
}

.about-float-main {
    inset: 0 0 58px 12%;
    --depth-x: 20px;
    --depth-y: -16px;
}

.about-float-side {
    width: min(46%, 260px);
    height: 178px;
    right: 5%;
    bottom: 0;
    --tilt: -3deg;
    --depth-x: -16px;
    --depth-y: 18px;
    animation-delay: 660ms;
}

.about-float-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    filter: saturate(1.06) contrast(1.04);
    transition: filter 300ms cubic-bezier(0.22, 1, 0.36, 1), transform 700ms cubic-bezier(0.22, 1, 0.36, 1);
}

.about-float-card:hover img {
    filter: saturate(1.18) contrast(1.08);
    transform: scale(1.045);
}

.about-float-caption {
    position: absolute;
    left: 1rem;
    right: 1rem;
    bottom: 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.88rem 1rem;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 18px;
    background: rgba(4, 18, 28, 0.72);
    color: #ffffff;
    backdrop-filter: blur(18px) saturate(1.3);
}

.about-float-caption strong,
.about-float-caption span span {
    display: block;
}

.about-float-caption strong {
    font-family: var(--jakarta-font-family, "Plus Jakarta Sans", sans-serif);
    font-size: 0.98rem;
}

.about-float-caption span span {
    margin-top: 0.2rem;
    color: rgba(255, 255, 255, 0.62);
    font-size: 0.8rem;
}

.about-caption-icon {
    display: grid;
    place-items: center;
    width: 42px;
    height: 42px;
    flex: 0 0 auto;
    border-radius: 999px;
    background: #22d3ee;
    color: #06202f;
    box-shadow: 0 0 0 8px rgba(34, 211, 238, 0.16), 0 0 34px rgba(34, 211, 238, 0.44);
    animation: aboutGlowPulse 2.8s cubic-bezier(0.22, 1, 0.36, 1) infinite;
}

.about-hero-stats {
    position: relative;
    z-index: 2;
    margin-top: clamp(1.5rem, 3.8vw, 3rem);
}

@keyframes aboutHeroDrift {
    from {
        transform: scale(1.1) translate3d(0, 0, 0);
    }
    to {
        transform: scale(1.02) translate3d(-1.5%, 0, 0);
    }
}

@keyframes aboutLetterRise {
    55% {
        opacity: 1;
        filter: blur(0);
    }
    100% {
        opacity: 1;
        filter: blur(0);
        transform: translate3d(0, 0, 0) rotateX(0);
    }
}

@keyframes aboutDeckEnter {
    60% {
        opacity: 1;
        filter: blur(0) saturate(1);
    }
    100% {
        opacity: 1;
        filter: blur(0) saturate(1);
        clip-path: inset(0 0 0 0 round 32px);
        transform: translate3d(0, 0, 0) scale(1);
    }
}

@keyframes aboutCardReveal {
    55% {
        opacity: 1;
        filter: blur(0) saturate(1);
    }
    100% {
        opacity: 1;
        filter: blur(0) saturate(1);
        clip-path: inset(0 0 0 0 round 28px);
    }
}

@keyframes aboutGlowPulse {
    0%,
    100% {
        box-shadow: 0 0 0 8px rgba(34, 211, 238, 0.16), 0 0 34px rgba(34, 211, 238, 0.44);
    }
    50% {
        box-shadow: 0 0 0 13px rgba(34, 211, 238, 0.06), 0 0 46px rgba(34, 211, 238, 0.62);
    }
}

@media (min-width: 1024px) {
    .about-hero-grid {
        grid-template-columns: minmax(0, 0.92fr) minmax(420px, 0.78fr);
    }

    .about-image-stack {
        display: block;
    }
}

@media (max-width: 759px) {
    .about-hero {
        min-height: auto;
        padding-top: 7rem;
    }

    .about-hero-title {
        font-size: clamp(2.2rem, 9.4vw, 3.05rem);
    }

    .about-hero-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .about-btn {
        width: 100%;
    }
}

@media (max-width: 430px) {
    .about-hero-title {
        font-size: clamp(1.95rem, 10.4vw, 2.55rem);
    }

    .about-hero-lede {
        font-size: 0.98rem;
    }
}

@media (hover: none) {
    .about-hero-title:hover .about-letter {
        color: rgba(255, 255, 255, 0.98);
        transform: translate3d(0, 0, 0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .about-hero-bg img,
    .about-image-stack,
    .about-float-card,
    .about-btn,
    .about-caption-icon {
        animation: none;
        transition: none;
        opacity: 1;
        filter: none;
        clip-path: none;
        transform: none;
    }

    .about-letter {
        opacity: 1;
        filter: none;
        transform: none;
        animation: none;
    }

    .about-btn:hover {
        transform: none;
    }
}
</style>
