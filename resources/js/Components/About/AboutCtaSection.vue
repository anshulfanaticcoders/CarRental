<script setup>
import { ArrowRight } from 'lucide-vue-next';
import { useSmoothAnchorScroll } from '@/composables/useSmoothAnchorScroll';

const { scrollToAnchor } = useSmoothAnchorScroll();

defineProps({
    cta: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <section class="about-cta">
        <div class="full-w-container">
            <div class="about-cta-card about-reveal">
                <div>
                    <p class="about-cta-kicker">{{ cta.kicker }}</p>
                    <h2>{{ cta.title }}</h2>
                    <div v-html="cta.content"></div>
                </div>
                <a
                    v-if="cta.buttonUrl"
                    class="about-cta-button"
                    :href="cta.buttonUrl"
                    @click="scrollToAnchor($event, cta.buttonUrl)"
                >
                    {{ cta.buttonText }}
                    <ArrowRight :size="18" aria-hidden="true" />
                </a>
            </div>
        </div>
    </section>
</template>

<style scoped>
.about-cta {
    position: relative;
    overflow: hidden;
    padding: clamp(4rem, 7vw, 7rem) 0;
    background:
        radial-gradient(circle at 14% 20%, rgba(34, 211, 238, 0.11), transparent 26rem),
        radial-gradient(circle at 84% 70%, rgba(255, 107, 90, 0.045), transparent 22rem),
        linear-gradient(135deg, #06131d 0%, #153b4f 48%, #07141e 100%);
}

.about-cta-card {
    position: relative;
    display: grid;
    gap: 1.5rem;
    align-items: end;
    padding: clamp(1.4rem, 4vw, 3rem);
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 32px;
    background:
        linear-gradient(145deg, rgba(255, 255, 255, 0.12), rgba(255, 255, 255, 0.045)),
        rgba(255, 255, 255, 0.04);
    box-shadow: 0 24px 70px rgba(3, 15, 24, 0.42);
    backdrop-filter: blur(22px) saturate(1.25);
    color: #ffffff;
}

.about-cta-kicker {
    display: inline-flex;
    align-items: center;
    gap: 0.65rem;
    margin: 0 0 0.75rem;
    color: #22d3ee;
    font-family: var(--jakarta-font-family, "Plus Jakarta Sans", sans-serif);
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.05em;
}

.about-cta-kicker::before {
    content: "";
    width: 34px;
    height: 1px;
    background: currentColor;
    box-shadow: 0 0 20px currentColor;
}

.about-cta-card h2 {
    max-width: 820px;
    margin: 0;
    color: #ffffff;
    font-family: var(--jakarta-font-family, "Plus Jakarta Sans", sans-serif);
    font-size: clamp(1.9rem, 3.9vw, 3.55rem);
    font-weight: 800;
    line-height: 1.08;
    letter-spacing: 0;
    text-wrap: balance;
}

.about-cta-card div :deep(p) {
    max-width: 62ch;
    margin: 1rem 0 0;
    color: rgba(255, 255, 255, 0.72);
    font-size: 1rem;
    line-height: 1.65;
}

.about-cta-button {
    position: relative;
    min-height: 46px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.55rem;
    padding: 0.82rem 1.2rem;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 999px;
    background: linear-gradient(135deg, #22d3ee, #9df5ff);
    color: #06202d;
    font-family: var(--jakarta-font-family, "Plus Jakarta Sans", sans-serif);
    font-size: 0.92rem;
    font-weight: 800;
    text-decoration: none;
    white-space: nowrap;
    box-shadow: 0 16px 40px rgba(34, 211, 238, 0.28);
    transition: transform 300ms cubic-bezier(0.22, 1, 0.36, 1), box-shadow 300ms ease;
}

.about-cta-button::before {
    content: "";
    position: absolute;
    inset: 0;
    opacity: 0;
    background: linear-gradient(110deg, transparent 20%, rgba(255, 255, 255, 0.55), transparent 78%);
    transform: translateX(-120%);
    transition: opacity 280ms cubic-bezier(0.22, 1, 0.36, 1), transform 640ms cubic-bezier(0.16, 1, 0.3, 1);
}

.about-cta-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 22px 58px rgba(34, 211, 238, 0.22);
}

.about-cta-button:hover::before {
    opacity: 0.58;
    transform: translateX(120%);
}

@media (min-width: 1024px) {
    .about-cta-card {
        grid-template-columns: minmax(0, 1fr) auto;
    }
}

@media (max-width: 759px) {
    .about-cta-card h2 {
        letter-spacing: 0;
    }

    .about-cta-button {
        width: 100%;
    }
}

@media (prefers-reduced-motion: reduce) {
    .about-cta-button {
        transition: none;
    }

    .about-cta-button:hover {
        transform: none;
    }

    .about-cta-button::before {
        display: none;
    }
}
</style>
