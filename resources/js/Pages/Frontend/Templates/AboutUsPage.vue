<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import Footer from '@/Components/Footer.vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import AboutCinematicHero from '@/Components/About/AboutCinematicHero.vue';
import AboutMissionSection from '@/Components/About/AboutMissionSection.vue';
import AboutJourneySection from '@/Components/About/AboutJourneySection.vue';
import AboutRibbonSection from '@/Components/About/AboutRibbonSection.vue';
import AboutPromiseGrid from '@/Components/About/AboutPromiseGrid.vue';
import AboutCoverageGallery from '@/Components/About/AboutCoverageGallery.vue';
import AboutCtaSection from '@/Components/About/AboutCtaSection.vue';
import { useAboutPageSections } from '@/composables/useAboutPageSections';

const props = defineProps({
    page: Object,
    meta: Object,
    sections: Array,
    seo: Object,
    locale: String,
    pages: Object,
});

const about = useAboutPageSections(props);
const pageRoot = ref(null);
const isMotionReady = ref(false);
let revealObserver = null;
let revealFrame = null;

function showRevealItems(items) {
    items.forEach(item => item.classList.add('is-visible'));
}

onMounted(() => {
    if (!pageRoot.value) return;

    const revealItems = Array.from(pageRoot.value.querySelectorAll('.about-reveal'));
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (reducedMotion || !('IntersectionObserver' in window)) {
        showRevealItems(revealItems);
        return;
    }

    isMotionReady.value = true;

    revealFrame = window.requestAnimationFrame(() => {
        const viewportHeight = window.innerHeight || 0;

        revealItems.forEach((item, index) => {
            if (!item.style.getPropertyValue('--reveal-delay')) {
                item.style.setProperty('--reveal-delay', `${Math.min(index % 4, 3) * 70}ms`);
            }

            if (item.getBoundingClientRect().top < viewportHeight * 0.92) {
                item.classList.add('is-visible');
            }
        });

        revealObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-visible');
                revealObserver?.unobserve(entry.target);
            });
        }, { rootMargin: '0px 0px -10% 0px', threshold: 0.18 });

        revealItems
            .filter(item => !item.classList.contains('is-visible'))
            .forEach(item => revealObserver.observe(item));

        revealFrame = null;
    });
});

onUnmounted(() => {
    if (revealFrame !== null) {
        window.cancelAnimationFrame(revealFrame);
    }

    revealObserver?.disconnect();
});
</script>

<template>
    <SeoHead :seo="seo" />
    <AuthenticatedHeaderLayout />

    <main ref="pageRoot" class="about-page" :class="{ 'about-motion-ready': isMotionReady }">
        <AboutCinematicHero :hero="about.hero" :stats="about.stats" />
        <AboutMissionSection :mission="about.mission" />
        <AboutJourneySection :journey="about.journey" />
        <AboutRibbonSection :ribbon="about.ribbon" />
        <AboutPromiseGrid :promises="about.promises" />
        <AboutCoverageGallery :coverage="about.coverage" />
        <AboutCtaSection :cta="about.cta" />
    </main>

    <Footer />
</template>

<style scoped>
.about-page {
    --about-ink: #153b4f;
    --about-navy: #0a1d28;
    --about-cyan: #22d3ee;
    --about-muted: #475569;
    position: relative;
    isolation: isolate;
    overflow: hidden;
    background:
        radial-gradient(circle at 12% 5%, rgba(34, 211, 238, 0.09), transparent 30rem),
        linear-gradient(135deg, #06131d 0%, #0b2230 38%, #07141e 100%);
    color: #f8fafc;
    font-family: "IBM Plex Sans", sans-serif;
}

.about-page::before {
    content: "";
    position: fixed;
    inset: 0;
    z-index: -3;
    pointer-events: none;
    background:
        radial-gradient(circle at 50% 20%, rgba(34, 211, 238, 0.07), transparent 25rem),
        radial-gradient(circle at 80% 75%, rgba(255, 107, 90, 0.045), transparent 22rem);
}

.about-page::after {
    content: "";
    position: fixed;
    inset: 0;
    z-index: -2;
    pointer-events: none;
    opacity: 0.07;
    background-image:
        linear-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
    background-size: 96px 96px;
    mask-image: linear-gradient(to bottom, black 0%, transparent 52%);
}

.about-page :deep(.about-reveal) {
    opacity: 1;
    transform: none;
    filter: none;
}

.about-page.about-motion-ready :deep(.about-reveal) {
    opacity: 0;
    transform: translate3d(0, 20px, 0) scale(0.985);
    filter: blur(6px);
    transition:
        opacity 860ms cubic-bezier(0.16, 1, 0.3, 1),
        filter 860ms cubic-bezier(0.16, 1, 0.3, 1),
        transform 860ms cubic-bezier(0.16, 1, 0.3, 1);
    transition-delay: var(--reveal-delay, 0ms);
    will-change: opacity, filter, transform;
}

.about-page.about-motion-ready :deep(.about-reveal.is-visible) {
    opacity: 1;
    transform: translate3d(0, 0, 0);
    filter: blur(0);
}

@media (prefers-reduced-motion: reduce) {
    .about-page :deep(.about-reveal) {
        animation: none;
        opacity: 1;
        filter: none;
        transform: none;
        transition: none;
    }
}
</style>
