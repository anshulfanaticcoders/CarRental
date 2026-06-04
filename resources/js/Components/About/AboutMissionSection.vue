<script setup>
import AboutIcon from './AboutIcon.vue';

defineProps({
    mission: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <section id="story" class="about-mission">
        <div class="full-w-container about-mission-grid">
            <div class="about-mission-copy about-reveal">
                <p class="about-kicker">{{ mission.kicker }}</p>
                <h2 class="about-section-title">{{ mission.title }}</h2>
                <div class="about-section-copy" v-html="mission.content"></div>

                <div class="about-proof-list" aria-label="Vrooem trust points">
                    <article
                        v-for="(item, index) in mission.proofItems"
                        :key="`${item.title}-${index}`"
                        class="about-proof-chip about-reveal"
                        :style="{ '--reveal-delay': `${120 + (index * 80)}ms` }"
                    >
                        <span class="about-proof-icon">
                            <AboutIcon :name="item.icon" :size="21" />
                        </span>
                        <span class="about-proof-copy">
                            <strong>{{ item.title }}</strong>
                            <span>{{ item.description }}</span>
                        </span>
                    </article>
                </div>
            </div>

            <div class="about-mission-board about-reveal" aria-label="Rental problems Vrooem makes clearer">
                <article
                    v-for="(line, index) in mission.missionLines"
                    :key="`${line.title}-${index}`"
                    class="about-mission-line about-reveal"
                    :style="{ '--reveal-delay': `${index * 90}ms` }"
                >
                    <div>
                        <span class="about-mission-label">{{ line.label }}</span>
                        <strong>{{ line.title }}</strong>
                    </div>
                    <p>{{ line.description }}</p>
                </article>
            </div>
        </div>
    </section>
</template>

<style scoped>
.about-mission {
    position: relative;
    padding: clamp(3rem, 5.7vw, 5.8rem) 0;
    color: #0f2936;
    background:
        radial-gradient(circle at 80% 8%, rgba(34, 211, 238, 0.08), transparent 24rem),
        linear-gradient(180deg, #f8fafc 0%, #ffffff 52%, #f1f5f9 100%);
}

.about-mission-grid {
    display: grid;
    gap: clamp(1.4rem, 3vw, 2.4rem);
    align-items: stretch;
}

.about-mission-copy {
    align-self: center;
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
    max-width: 840px;
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
    max-width: 720px;
    margin-top: 0.9rem;
    color: rgba(15, 41, 54, 0.72);
    font-size: clamp(0.98rem, 1.16vw, 1.05rem);
    line-height: 1.68;
    text-wrap: pretty;
}

.about-section-copy :deep(p) {
    margin: 0 0 0.7rem;
}

.about-section-copy :deep(p:last-child) {
    margin-bottom: 0;
}

.about-proof-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 0.75rem;
    margin-top: clamp(1rem, 2.6vw, 1.8rem);
}

.about-proof-chip {
    min-height: 82px;
    display: flex;
    align-items: center;
    gap: 0.8rem;
    padding: 0.9rem;
    border: 1px solid rgba(21, 59, 79, 0.11);
    border-radius: 20px;
    background: #ffffff;
    box-shadow: 0 14px 34px rgba(21, 59, 79, 0.08);
    transition: transform 240ms cubic-bezier(0.22, 1, 0.36, 1), border-color 240ms ease, box-shadow 240ms ease;
}

.about-proof-chip:hover {
    transform: translateY(-4px);
    border-color: rgba(34, 211, 238, 0.32);
    box-shadow: 0 20px 46px rgba(21, 59, 79, 0.12);
}

.about-proof-icon {
    display: grid;
    place-items: center;
    width: 42px;
    height: 42px;
    flex: 0 0 auto;
    border-radius: 15px;
    background: linear-gradient(135deg, rgba(34, 211, 238, 0.95), rgba(157, 245, 255, 0.95));
    color: #06202f;
    box-shadow: 0 12px 26px rgba(34, 211, 238, 0.2);
    transition: transform 300ms cubic-bezier(0.22, 1, 0.36, 1), box-shadow 300ms cubic-bezier(0.22, 1, 0.36, 1);
}

.about-proof-chip:hover .about-proof-icon {
    transform: translateY(-1px);
    box-shadow: 0 16px 32px rgba(34, 211, 238, 0.28);
}

.about-proof-copy strong,
.about-proof-copy span {
    display: block;
}

.about-proof-copy strong {
    color: #0f2936;
    font-family: var(--jakarta-font-family, "Plus Jakarta Sans", sans-serif);
    font-size: 1rem;
}

.about-proof-copy span {
    margin-top: 0.1rem;
    color: rgba(15, 41, 54, 0.63);
    font-size: 0.9rem;
    line-height: 1.42;
}

.about-mission-board {
    position: relative;
    overflow: hidden;
    display: grid;
    gap: 0.75rem;
    padding: clamp(1rem, 2.4vw, 1.6rem);
    border: 1px solid rgba(21, 59, 79, 0.12);
    border-radius: 28px;
    background:
        linear-gradient(145deg, rgba(255, 255, 255, 0.92), rgba(240, 248, 252, 0.88)),
        #ffffff;
    box-shadow: 0 22px 56px rgba(21, 59, 79, 0.1);
}

.about-mission-board::before {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: linear-gradient(120deg, transparent 0%, rgba(34, 211, 238, 0.08) 48%, transparent 78%);
}

.about-mission-line {
    position: relative;
    display: grid;
    gap: 0.9rem;
    align-items: center;
    padding: 0.95rem;
    border: 1px solid rgba(21, 59, 79, 0.1);
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.78);
    transition: transform 300ms cubic-bezier(0.22, 1, 0.36, 1), border-color 300ms ease, background-color 300ms ease, box-shadow 300ms ease;
}

.about-mission-line:hover {
    transform: translateY(-3px);
    border-color: rgba(34, 211, 238, 0.34);
    background: #ffffff;
    box-shadow: 0 18px 44px rgba(21, 59, 79, 0.12);
}

.about-mission-label {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    margin-bottom: 0;
    color: #1c4d66;
    font-family: var(--jakarta-font-family, "Plus Jakarta Sans", sans-serif);
    font-size: 0.78rem;
    font-weight: 800;
}

.about-mission-label::before {
    content: "";
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: #22d3ee;
    box-shadow: 0 0 18px rgba(34, 211, 238, 0.45);
}

.about-mission-line strong {
    display: block;
    margin-top: 0.28rem;
    color: #0f2936;
    font-family: var(--jakarta-font-family, "Plus Jakarta Sans", sans-serif);
    font-size: clamp(1rem, 1.35vw, 1.18rem);
    line-height: 1.3;
}

.about-mission-line p {
    margin: 0;
    color: rgba(15, 41, 54, 0.68);
    line-height: 1.55;
}

@media (min-width: 1024px) {
    .about-mission-grid {
        grid-template-columns: minmax(0, 0.82fr) minmax(420px, 1fr);
    }

    .about-mission-line {
        grid-template-columns: minmax(150px, 0.75fr) 1fr;
    }
}

@media (max-width: 759px) {
    .about-mission {
        padding: clamp(2.7rem, 10vw, 4rem) 0;
    }
}

@media (prefers-reduced-motion: reduce) {
    .about-proof-chip,
    .about-proof-icon,
    .about-mission-line {
        transition: none;
    }

    .about-proof-chip:hover,
    .about-proof-chip:hover .about-proof-icon,
    .about-mission-line:hover {
        transform: none;
    }
}
</style>
