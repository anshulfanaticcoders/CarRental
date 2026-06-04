<script setup>
defineProps({
    journey: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <section id="promise" class="about-journey">
        <div class="full-w-container about-journey-grid">
            <div class="about-route-panel about-reveal" aria-label="Animated rental journey map">
                <img :src="journey.imageUrl" :alt="journey.imageAlt" loading="lazy" decoding="async" />
                <svg class="about-route-map" viewBox="0 0 600 420" aria-hidden="true">
                    <path d="M55 310 C150 250, 165 140, 270 185 S420 250, 525 95" />
                </svg>
                <span class="about-route-pin about-pin-a" aria-hidden="true"></span>
                <span class="about-route-pin about-pin-b" aria-hidden="true"></span>
                <span class="about-route-pin about-pin-c" aria-hidden="true"></span>
                <div class="about-route-note">
                    <strong>{{ journey.routeNoteTitle }}</strong>
                    <span>{{ journey.routeNoteText }}</span>
                </div>
            </div>

            <div class="about-journey-copy about-reveal">
                <p class="about-kicker">{{ journey.kicker }}</p>
                <h2 class="about-section-title">{{ journey.title }}</h2>
                <div class="about-section-copy" v-html="journey.content"></div>

                <div class="about-process-list">
                    <article
                        v-for="(item, index) in journey.items"
                        :key="`${item.title}-${index}`"
                        class="about-process-card about-reveal"
                        :style="{ '--reveal-delay': `${120 + (index * 90)}ms` }"
                    >
                        <span class="about-process-index">{{ index + 1 }}</span>
                        <div>
                            <h3>{{ item.title }}</h3>
                            <p>{{ item.description }}</p>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.about-journey {
    position: relative;
    isolation: isolate;
    padding: clamp(3rem, 5.7vw, 5.8rem) 0;
    overflow: hidden;
    background:
        radial-gradient(circle at 12% 15%, rgba(34, 211, 238, 0.07), transparent 25rem),
        radial-gradient(circle at 85% 72%, rgba(244, 201, 93, 0.04), transparent 20rem),
        linear-gradient(135deg, #06131d 0%, #0b2230 48%, #07141e 100%);
    color: #ffffff;
}

.about-journey-grid {
    display: grid;
    gap: clamp(1.5rem, 3.8vw, 3rem);
    align-items: center;
}

.about-route-panel {
    position: relative;
    min-height: 430px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 34px;
    background:
        linear-gradient(145deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.03)),
        #071722;
    box-shadow: 0 24px 70px rgba(3, 15, 24, 0.42);
}

.about-route-panel img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    filter: brightness(0.52) saturate(1.06);
    transform: scale(1.04);
}

.about-route-panel::after {
    content: "";
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 55% 42%, rgba(34, 211, 238, 0.16), transparent 16rem),
        linear-gradient(to top, rgba(5, 17, 26, 0.92), rgba(5, 17, 26, 0.12));
}

.about-route-map {
    position: absolute;
    inset: 9% 8%;
    z-index: 2;
    color: #22d3ee;
}

.about-route-map path {
    fill: none;
    stroke: currentColor;
    stroke-width: 3;
    stroke-linecap: round;
    stroke-dasharray: 12 14;
    filter: drop-shadow(0 0 16px rgba(34, 211, 238, 0.8));
    animation: aboutRouteRun 10s linear infinite;
}

.about-route-pin {
    position: absolute;
    z-index: 3;
    width: 16px;
    height: 16px;
    border-radius: 999px;
    background: #22d3ee;
    box-shadow: 0 0 0 8px rgba(34, 211, 238, 0.14), 0 0 28px rgba(34, 211, 238, 0.72);
}

.about-pin-a {
    left: 15%;
    top: 62%;
}

.about-pin-b {
    left: 46%;
    top: 39%;
    background: #f4c95d;
    box-shadow: 0 0 0 8px rgba(244, 201, 93, 0.14), 0 0 28px rgba(244, 201, 93, 0.6);
}

.about-pin-c {
    right: 17%;
    top: 22%;
}

.about-route-note {
    position: absolute;
    z-index: 4;
    left: 1rem;
    right: 1rem;
    bottom: 1rem;
    display: grid;
    gap: 0.65rem;
    padding: 1rem;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 22px;
    background: rgba(4, 18, 28, 0.74);
    backdrop-filter: blur(18px) saturate(1.3);
}

.about-route-note strong {
    font-family: var(--jakarta-font-family, "Plus Jakarta Sans", sans-serif);
    font-size: 1rem;
}

.about-route-note span {
    color: rgba(255, 255, 255, 0.66);
    line-height: 1.5;
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
    max-width: 840px;
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
    max-width: 720px;
    margin-top: 0.9rem;
    color: rgba(248, 250, 252, 0.72);
    font-size: clamp(0.98rem, 1.16vw, 1.05rem);
    line-height: 1.68;
    text-wrap: pretty;
}

.about-section-copy :deep(p) {
    margin: 0;
}

.about-process-list {
    display: grid;
    gap: 0.85rem;
    margin-top: 2rem;
}

.about-process-card {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 1rem;
    align-items: start;
    padding: 1rem;
    border: 1px solid rgba(226, 232, 240, 0.92);
    border-radius: 20px;
    background: #ffffff;
    color: #0f2936;
    box-shadow: 0 16px 40px rgba(21, 59, 79, 0.1);
    transition: transform 300ms cubic-bezier(0.22, 1, 0.36, 1), border-color 300ms ease, box-shadow 300ms ease;
}

.about-process-card:hover {
    transform: translateX(4px);
    border-color: rgba(34, 211, 238, 0.46);
    box-shadow: 0 20px 50px rgba(21, 59, 79, 0.12);
}

.about-process-index {
    display: grid;
    place-items: center;
    width: 42px;
    height: 42px;
    border-radius: 15px;
    background: linear-gradient(135deg, #22d3ee, #b6f7ff);
    color: #06202f;
    font-family: var(--jakarta-font-family, "Plus Jakarta Sans", sans-serif);
    font-weight: 800;
}

.about-process-card h3 {
    margin: 0 0 0.3rem;
    color: #0f2936;
    font-family: var(--jakarta-font-family, "Plus Jakarta Sans", sans-serif);
    font-size: 1.05rem;
    line-height: 1.3;
}

.about-process-card p {
    margin: 0;
    color: rgba(15, 41, 54, 0.7);
    line-height: 1.6;
}

@keyframes aboutRouteRun {
    to {
        stroke-dashoffset: -220;
    }
}

@media (min-width: 1024px) {
    .about-journey-grid {
        grid-template-columns: 0.95fr 1.05fr;
    }
}

@media (max-width: 759px) {
    .about-journey {
        padding: clamp(2.7rem, 10vw, 4rem) 0;
    }

    .about-route-panel {
        min-height: 360px;
        border-radius: 24px;
    }

    .about-section-title {
        letter-spacing: 0;
    }
}

@media (prefers-reduced-motion: reduce) {
    .about-route-map path {
        animation: none;
    }

    .about-process-card {
        transition: none;
    }

    .about-process-card:hover {
        transform: none;
    }
}
</style>
