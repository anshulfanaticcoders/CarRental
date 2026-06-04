<script setup>
import AboutIcon from './AboutIcon.vue';

defineProps({
    promises: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <section class="about-promises">
        <div class="full-w-container">
            <div class="about-promises-head about-reveal">
                <p class="about-kicker">{{ promises.kicker }}</p>
                <h2 class="about-section-title">{{ promises.title }}</h2>
                <div class="about-section-copy" v-html="promises.content"></div>
            </div>

            <div class="about-promise-grid">
                <article
                    v-for="(item, index) in promises.items"
                    :key="`${item.title}-${index}`"
                    class="about-promise-card about-reveal"
                    :style="{ '--reveal-delay': `${index * 90}ms` }"
                    tabindex="0"
                >
                    <small>{{ item.kicker }}</small>
                    <div>
                        <span class="about-promise-icon">
                            <AboutIcon :name="item.icon" :size="23" />
                        </span>
                        <h3>{{ item.title }}</h3>
                        <p>{{ item.description }}</p>
                    </div>
                </article>
            </div>
        </div>
    </section>
</template>

<style scoped>
.about-promises {
    position: relative;
    padding: clamp(3rem, 5.7vw, 5.8rem) 0;
    color: #0f2936;
    background:
        radial-gradient(circle at 80% 8%, rgba(34, 211, 238, 0.08), transparent 24rem),
        linear-gradient(180deg, #f8fafc 0%, #ffffff 52%, #f1f5f9 100%);
}

.about-promises-head {
    max-width: 780px;
}

.about-kicker {
    display: inline-flex;
    align-items: center;
    gap: 0.65rem;
    margin: 0 0 0.75rem;
    color: #0891b2;
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
    color: #0f2936;
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
    color: rgba(15, 41, 54, 0.72);
    font-size: clamp(0.98rem, 1.16vw, 1.05rem);
    line-height: 1.68;
}

.about-section-copy :deep(p) {
    margin: 0;
}

.about-promise-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(245px, 1fr));
    gap: 1rem;
    margin-top: clamp(1.5rem, 3.5vw, 3rem);
}

.about-promise-card {
    min-width: 0;
    min-height: 228px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    gap: 1rem;
    padding: 1.15rem;
    overflow: hidden;
    border: 1px solid rgba(21, 59, 79, 0.13);
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.76);
    box-shadow: 0 18px 48px rgba(21, 59, 79, 0.1);
    transition: transform 300ms cubic-bezier(0.22, 1, 0.36, 1), border-color 300ms ease, background-color 300ms ease, box-shadow 300ms ease;
}

.about-promise-card:hover,
.about-promise-card:focus-visible {
    transform: translateY(-7px);
    border-color: rgba(34, 211, 238, 0.38);
    background: #f0f8fc;
    box-shadow: 0 28px 70px rgba(21, 59, 79, 0.16), 0 0 28px rgba(34, 211, 238, 0.18);
    outline: none;
}

.about-promise-card small {
    color: #0891b2;
    font-family: var(--jakarta-font-family, "Plus Jakarta Sans", sans-serif);
    font-size: 0.78rem;
    font-weight: 800;
    line-height: 1.2;
}

.about-promise-card > div {
    display: flex;
    min-width: 0;
    flex-direction: column;
    align-items: flex-start;
}

.about-promise-icon {
    display: grid;
    place-items: center;
    width: 48px;
    height: 48px;
    border-radius: 17px;
    background: linear-gradient(135deg, #22d3ee, #9df5ff);
    color: #06202f;
    box-shadow: 0 16px 34px rgba(34, 211, 238, 0.22);
}

.about-promise-card h3 {
    margin: 1.25rem 0 0.75rem;
    color: #0f2936;
    font-family: var(--jakarta-font-family, "Plus Jakarta Sans", sans-serif);
    font-size: 1.26rem;
    line-height: 1.2;
    text-wrap: balance;
}

.about-promise-card p {
    margin: 0;
    color: rgba(15, 41, 54, 0.72);
    font-size: 1rem;
    line-height: 1.65;
}

@media (max-width: 759px) {
    .about-promises {
        padding: clamp(2.7rem, 10vw, 4rem) 0;
    }

    .about-promise-card {
        min-height: 0;
    }
}

@media (prefers-reduced-motion: reduce) {
    .about-promise-card {
        transition: none;
    }

    .about-promise-card:hover,
    .about-promise-card:focus-visible {
        transform: none;
    }
}
</style>
