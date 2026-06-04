<script setup>
import { computed } from 'vue';

const props = defineProps({
    coverage: {
        type: Object,
        required: true,
    },
});

const coverageHighlights = computed(() => (props.coverage.items || []).slice(0, 3));
</script>

<template>
    <section id="coverage" class="about-coverage">
        <div class="full-w-container about-coverage-board">
            <div class="about-coverage-head about-reveal">
                <p class="about-kicker">{{ coverage.kicker }}</p>
                <h2 class="about-section-title">{{ coverage.title }}</h2>
                <div class="about-section-copy" v-html="coverage.content"></div>
            </div>

            <aside v-if="coverageHighlights.length" class="about-coverage-notes about-reveal" aria-label="Coverage highlights">
                <span class="about-coverage-notes-label">Coverage includes</span>
                <div
                    v-for="(item, index) in coverageHighlights"
                    :key="`coverage-note-${item.title}-${index}`"
                    class="about-coverage-note"
                >
                    <span class="about-coverage-note-dot" aria-hidden="true"></span>
                    <span class="about-coverage-note-text">{{ item.title }}</span>
                </div>
            </aside>

            <div class="about-gallery-row">
                <figure
                    v-for="(item, index) in coverage.items"
                    :key="`${item.title}-${index}`"
                    class="about-gallery-item about-reveal"
                    :class="{
                        'about-gallery-item-featured': index === 0,
                        'about-gallery-item-secondary': index === 1,
                        'about-gallery-item-tertiary': index === 2,
                    }"
                    :style="{ '--reveal-delay': `${index * 100}ms` }"
                >
                    <img :src="item.imageUrl" :alt="item.imageAlt" loading="lazy" decoding="async" />
                    <figcaption>
                        <strong>{{ item.title }}</strong>
                        <span>{{ item.description }}</span>
                    </figcaption>
                </figure>
            </div>
        </div>
    </section>
</template>

<style scoped>
.about-coverage {
    position: relative;
    isolation: isolate;
    --about-ease-out: cubic-bezier(0.22, 1, 0.36, 1);
    --about-gallery-ease: cubic-bezier(0.16, 1, 0.3, 1);
    padding: clamp(3rem, 5.7vw, 5.8rem) 0;
    overflow: hidden;
    background:
        radial-gradient(circle at 12% 15%, rgba(34, 211, 238, 0.07), transparent 25rem),
        radial-gradient(circle at 85% 72%, rgba(244, 201, 93, 0.04), transparent 20rem),
        linear-gradient(135deg, #06131d 0%, #0b2230 48%, #07141e 100%);
    color: #ffffff;
}

.about-coverage-head {
    max-width: none;
}

.about-kicker {
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

.about-kicker::before {
    content: "";
    width: 34px;
    height: 1px;
    background: currentColor;
    box-shadow: 0 0 20px currentColor;
}

.about-section-title {
    margin: 0;
    color: #ffffff;
    font-family: var(--jakarta-font-family, "Plus Jakarta Sans", sans-serif);
    font-size: clamp(1.75rem, 3.55vw, 3.2rem);
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: 0;
    text-wrap: balance;
}

.about-section-copy {
    max-width: 68ch;
    margin-top: 0.9rem;
    color: rgba(248, 250, 252, 0.72);
    font-size: clamp(0.98rem, 1.16vw, 1.05rem);
    line-height: 1.68;
}

.about-section-copy :deep(p) {
    margin: 0;
}

.about-coverage-board {
    display: grid;
    gap: clamp(1rem, 2vw, 1.35rem);
    align-items: stretch;
}

.about-gallery-row {
    display: grid;
    grid-column: 1 / -1;
    grid-template-columns: minmax(0, 1fr);
    align-items: stretch;
    gap: clamp(1rem, 2vw, 1.35rem);
    min-width: 0;
}

.about-coverage-notes {
    display: grid;
    gap: 0.65rem;
    align-content: center;
    padding: clamp(1rem, 2.2vw, 1.4rem);
    border: 1px solid rgba(255, 255, 255, 0.13);
    border-radius: 24px;
    background:
        linear-gradient(145deg, rgba(255, 255, 255, 0.09), rgba(255, 255, 255, 0.035)),
        rgba(7, 20, 30, 0.56);
    box-shadow: 0 24px 60px rgba(2, 12, 20, 0.18);
    backdrop-filter: blur(16px) saturate(1.2);
}

.about-coverage-notes-label {
    margin-bottom: 0.15rem;
    color: #22d3ee;
    font-family: var(--jakarta-font-family, "Plus Jakarta Sans", sans-serif);
    font-size: 0.78rem;
    font-weight: 800;
}

.about-coverage-note {
    display: grid;
    grid-template-columns: 10px minmax(0, 1fr);
    gap: 0.75rem;
    align-items: center;
    padding-top: 0.65rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.about-coverage-note-dot {
    width: 9px;
    height: 9px;
    border-radius: 999px;
    background: #22d3ee;
    box-shadow: 0 0 18px rgba(34, 211, 238, 0.48);
}

.about-coverage-note-text {
    min-width: 0;
    color: rgba(255, 255, 255, 0.86);
    font-family: var(--jakarta-font-family, "Plus Jakarta Sans", sans-serif);
    font-size: 0.98rem;
    font-weight: 750;
    line-height: 1.25;
}

.about-gallery-item {
    position: relative;
    min-width: 0;
    min-height: 260px;
    margin: 0;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 28px;
    background: #071722;
    box-shadow: 0 24px 60px rgba(2, 12, 20, 0.24);
    transition:
        transform 300ms var(--about-ease-out),
        border-color 300ms var(--about-ease-out),
        box-shadow 300ms var(--about-ease-out);
}

.about-gallery-item:hover {
    transform: translateY(-6px);
    border-color: rgba(34, 211, 238, 0.38);
    box-shadow: 0 32px 82px rgba(2, 12, 20, 0.36), 0 0 30px rgba(34, 211, 238, 0.14);
}

.about-gallery-item img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    filter: brightness(0.72) saturate(1.05);
    transition:
        transform 620ms var(--about-ease-out),
        filter 620ms var(--about-ease-out);
}

.about-gallery-item:hover img {
    transform: scale(1.045);
    filter: brightness(0.84) saturate(1.16);
}

.about-gallery-item figcaption {
    position: absolute;
    left: 1rem;
    right: 1rem;
    bottom: 1rem;
    z-index: 2;
    overflow: hidden;
    padding: 1rem;
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 20px;
    background: rgba(6, 19, 29, 0.74);
    backdrop-filter: blur(18px) saturate(1.25);
}

.about-gallery-item strong,
.about-gallery-item span {
    display: block;
}

.about-gallery-item strong {
    font-family: var(--jakarta-font-family, "Plus Jakarta Sans", sans-serif);
    font-size: 1.15rem;
}

.about-gallery-item span {
    margin-top: 0.35rem;
    color: rgba(255, 255, 255, 0.67);
    line-height: 1.45;
}

@media (min-width: 760px) and (max-width: 1023px) {
    .about-coverage-board {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .about-gallery-row {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .about-coverage-head,
    .about-coverage-notes,
    .about-gallery-item-featured {
        grid-column: 1 / -1;
    }

    .about-gallery-item-featured {
        min-height: 360px;
    }
}

@media (min-width: 1024px) {
    .about-coverage-board {
        grid-template-columns: repeat(12, minmax(0, 1fr));
        grid-auto-flow: dense;
    }

    .about-coverage-head {
        grid-column: 1 / span 8;
        align-self: center;
        padding: clamp(0.5rem, 2vw, 1.25rem) clamp(1.25rem, 3vw, 2.4rem) clamp(0.5rem, 2vw, 1.25rem) 0;
    }

    .about-coverage-notes {
        grid-column: 9 / -1;
    }

    .about-gallery-row {
        grid-column: 1 / -1;
        grid-template-columns: minmax(0, 2fr) minmax(0, 1fr) minmax(0, 1fr);
        height: clamp(24rem, 27vw, 27rem);
        transition: grid-template-columns 900ms var(--about-gallery-ease);
    }

    .about-gallery-row:has(.about-gallery-item-secondary:hover) {
        grid-template-columns: minmax(0, 1fr) minmax(0, 2fr) minmax(0, 1fr);
    }

    .about-gallery-row:has(.about-gallery-item-tertiary:hover) {
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) minmax(0, 2fr);
    }

    .about-gallery-item figcaption {
        right: auto;
        width: clamp(13.5rem, 18vw, 18rem);
        height: 4.7rem;
        display: grid;
        align-content: start;
        transition:
            width 520ms var(--about-ease-out),
            height 520ms var(--about-ease-out),
            background 300ms var(--about-ease-out),
            border-color 300ms var(--about-ease-out);
    }

    .about-gallery-item-featured figcaption,
    .about-gallery-row:has(.about-gallery-item-secondary:hover) .about-gallery-item-secondary figcaption,
    .about-gallery-row:has(.about-gallery-item-tertiary:hover) .about-gallery-item-tertiary figcaption {
        width: clamp(20rem, 32vw, 30rem);
        height: 7.3rem;
    }

    .about-gallery-row:has(.about-gallery-item-secondary:hover) .about-gallery-item-featured figcaption,
    .about-gallery-row:has(.about-gallery-item-tertiary:hover) .about-gallery-item-featured figcaption {
        width: clamp(13.5rem, 18vw, 18rem);
        height: 4.7rem;
    }

    .about-gallery-item strong {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        line-height: 1.22;
    }

    .about-gallery-item span {
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        max-height: 2.9rem;
        margin-top: 0.35rem;
        opacity: 0;
        overflow: hidden;
        pointer-events: none;
        transform: translate3d(0, 0.35rem, 0);
        filter: blur(5px);
        transition:
            opacity 220ms var(--about-ease-out),
            transform 220ms var(--about-ease-out),
            filter 220ms var(--about-ease-out);
        transition-delay: 0ms;
        will-change: opacity, transform, filter;
    }

    .about-gallery-item-featured span,
    .about-gallery-row:has(.about-gallery-item-secondary:hover) .about-gallery-item-secondary span,
    .about-gallery-row:has(.about-gallery-item-tertiary:hover) .about-gallery-item-tertiary span {
        opacity: 1;
        transform: translate3d(0, 0, 0);
        filter: blur(0);
        transition-duration: 420ms;
        transition-delay: 420ms;
    }

    .about-gallery-row:has(.about-gallery-item-secondary:hover) .about-gallery-item-featured span,
    .about-gallery-row:has(.about-gallery-item-tertiary:hover) .about-gallery-item-featured span {
        opacity: 0;
        transform: translate3d(0, 0.35rem, 0);
        filter: blur(5px);
        transition-duration: 180ms;
        transition-delay: 0ms;
    }

    .about-gallery-item-featured {
        grid-column: auto;
        min-height: 0;
        height: 100%;
    }

    .about-gallery-item-secondary {
        grid-column: auto;
        min-height: 0;
        height: 100%;
    }

    .about-gallery-item-tertiary {
        grid-column: auto;
        min-height: 0;
        height: 100%;
    }

    .about-gallery-item:not(.about-gallery-item-featured):not(.about-gallery-item-secondary):not(.about-gallery-item-tertiary) {
        min-height: 320px;
    }
}

@media (max-width: 759px) {
    .about-coverage {
        padding: clamp(2.7rem, 10vw, 4rem) 0;
    }
}

@media (max-width: 430px) {
    .about-gallery-item {
        min-height: 245px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .about-gallery-row,
    .about-gallery-item,
    .about-gallery-item img,
    .about-gallery-item figcaption,
    .about-gallery-item span {
        transition: none;
    }

    .about-gallery-item:hover,
    .about-gallery-item:hover img,
    .about-gallery-item span {
        transform: none;
        filter: none;
    }
}
</style>
