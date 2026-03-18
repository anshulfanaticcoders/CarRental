<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useScrollAnimation } from '@/composables/useScrollAnimation';

const page = usePage();
const _p = (key, fallback = '') => {
    const t = page.props.translations?.homepage || {};
    return t[key] || fallback || key;
};

const steps = computed(() => [
    {
        value: 'item-1',
        title: _p('how_it_works_step_1_title', 'Search & Compare'),
        content: _p('how_it_works_step_1_content', 'Enter your destination and dates. We compare 800+ providers to find your perfect match.'),
    },
    {
        value: 'item-2',
        title: _p('how_it_works_step_2_title', 'Book Instantly'),
        content: _p('how_it_works_step_2_content', 'Reserve your vehicle in seconds with transparent pricing and no hidden fees.'),
    },
    {
        value: 'item-3',
        title: _p('how_it_works_step_3_title', 'Pick Up & Drive'),
        content: _p('how_it_works_step_3_content', 'Show your booking confirmation, collect your keys, and hit the road.'),
    },
]);

useScrollAnimation('.how-section', '.how-header, .step-card', {
    y: 52,
    duration: 0.9,
    stagger: 0.12,
});
</script>

<template>
    <section class="how-section">
        <div class="how-glow"></div>
        <div class="full-w-container how-z">
            <div class="how-header sr-reveal">
                <span class="how-label">{{ _p('how_it_works_title', 'How It Works') }}</span>
                <h3 class="how-title">{{ _p('how_it_works_subtitle', 'Three steps to the open road.') }}</h3>
                <p class="how-desc">{{ _p('how_it_works_description', 'From search to steering wheel in minutes.') }}</p>
            </div>
            <div class="steps-grid">
                <div v-for="(s, i) in steps" :key="s.value" class="step-card sr-reveal">
                    <div class="step-number">{{ String(i + 1).padStart(2, '0') }}</div>
                    <div>
                        <h4>{{ s.title }}</h4>
                        <p>{{ s.content }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.sr-reveal { visibility: hidden; }

.how-section {
    padding: clamp(4rem, 8vw, 7rem) 0;
    background: linear-gradient(160deg, #0a1d28 0%, #153b4f 45%, #0c2535 100%);
    color: #fff;
    position: relative;
    overflow: hidden;
}

.how-glow {
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: radial-gradient(circle at 18% 12%, rgba(34, 211, 238, 0.08), transparent 45%),
                radial-gradient(circle at 80% 78%, rgba(10, 29, 40, 0.3), transparent 55%);
}

.how-z {
    position: relative;
    z-index: 1;
}

.how-header {
    text-align: center;
    margin-bottom: 4rem;
}

.how-label {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: #22d3ee;
}

.how-label::before {
    content: "";
    display: block;
    width: 24px;
    height: 1.5px;
    background: #22d3ee;
}

.how-title {
    font-size: clamp(2rem, 3.5vw, 3rem);
    font-weight: 700;
    line-height: 1.12;
    letter-spacing: -0.02em;
    color: #fff;
    margin-top: 0.75rem;
}

.how-desc {
    margin-top: 1rem;
    color: rgba(255, 255, 255, 0.5);
    max-width: 480px;
    margin-inline: auto;
}

.steps-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    position: relative;
}

.steps-grid::before {
    content: "";
    position: absolute;
    top: 3.25rem;
    left: calc(16.67% + 1rem);
    right: calc(16.67% + 1rem);
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.15), rgba(255, 255, 255, 0.15), transparent);
}

.step-card {
    text-align: center;
    padding: 2rem 1.5rem;
    position: relative;
}

.step-number {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 52px;
    height: 52px;
    border-radius: 16px;
    background: rgba(34, 211, 238, 0.1);
    border: 2px solid rgba(34, 211, 238, 0.2);
    font-size: 1.15rem;
    font-weight: 800;
    color: #22d3ee;
    margin: 0 auto 1.5rem;
    position: relative;
    z-index: 1;
}

.step-card h4 {
    font-size: 1.15rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 0.6rem;
}

.step-card p {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.5);
    line-height: 1.65;
    max-width: 280px;
    margin-inline: auto;
}

@media (max-width: 1024px) {
    .steps-grid {
        grid-template-columns: 1fr;
        gap: 0;
    }

    .steps-grid::before {
        display: none;
    }

    .step-card {
        text-align: left;
        display: grid;
        grid-template-columns: 52px 1fr;
        gap: 1rem;
        align-items: start;
        padding: 1.25rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .step-card:last-child {
        border-bottom: none;
    }

    .step-number {
        margin: 0;
    }

    .step-card p {
        max-width: none;
        margin: 0;
    }
}
</style>
