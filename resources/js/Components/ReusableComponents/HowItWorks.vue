<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useScrollAnimation } from '@/composables/useScrollAnimation';
import { Search, CreditCard, CarFront } from 'lucide-vue-next';

const page = usePage();
const _p = (key, fallback = '') => {
    const t = page.props.translations?.homepage || {};
    return t[key] || fallback || key;
};

const steps = computed(() => [
    {
        value: 'item-1',
        icon: Search,
        title: _p('how_it_works_step_1_title', 'Search & Compare'),
        content: _p('how_it_works_step_1_content', 'Pick your destination and dates. We search across multiple trusted providers so you can compare prices and features side by side.'),
    },
    {
        value: 'item-2',
        icon: CreditCard,
        title: _p('how_it_works_step_2_title', 'Review & Book'),
        content: _p('how_it_works_step_2_content', 'Choose your extras, review full pricing upfront with no hidden fees, and pay securely — all on one page.'),
    },
    {
        value: 'item-3',
        icon: CarFront,
        title: _p('how_it_works_step_3_title', 'Pick Up & Drive'),
        content: _p('how_it_works_step_3_content', 'Show your booking confirmation at the desk, collect your keys, and hit the road.'),
    },
]);

useScrollAnimation('.how-section', '.how-header, .step-card', {
    y: 52,
    duration: 0.9,
    stagger: 0.12,
});
</script>

<template>
    <section id="how-it-works" class="how-section">
        <div class="how-glow"></div>
        <div class="full-w-container how-z">
            <div class="how-header sr-reveal">
                <span class="how-label">{{ _p('how_it_works_title', 'How It Works') }}</span>
                <h3 class="how-title">{{ _p('how_it_works_subtitle', 'Three steps to the open road.') }}</h3>
                <p class="how-desc">{{ _p('how_it_works_description', 'From search to steering wheel in minutes.') }}</p>
            </div>
            <div class="steps-grid">
                <div v-for="(s, i) in steps" :key="s.value" class="step-card sr-reveal">
                    <span class="step-backdrop" aria-hidden="true">{{ i + 1 }}</span>
                    <div class="step-icon-circle">
                        <component :is="s.icon" stroke-width="1.5" />
                    </div>
                    <div class="step-text">
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

.step-backdrop {
    position: absolute;
    left: -0.25rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: clamp(7rem, 10vw, 10rem);
    font-weight: 900;
    line-height: 1;
    color: rgba(34, 211, 238, 0.06);
    filter: blur(1.5px);
    pointer-events: none;
    user-select: none;
    z-index: 0;
    font-family: "Plus Jakarta Sans", sans-serif;
    letter-spacing: -0.04em;
}

.step-icon-circle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: rgba(34, 211, 238, 0.08);
    border: 1.5px solid rgba(34, 211, 238, 0.15);
    margin: 0 auto 1.5rem;
    position: relative;
    z-index: 1;
}

.step-icon-circle svg {
    width: 28px;
    height: 28px;
    color: #22d3ee;
}

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
    top: calc(2.5rem + 32px);
    left: calc(16.67% + 1rem);
    right: calc(16.67% + 1rem);
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.15), rgba(255, 255, 255, 0.15), transparent);
}

.step-card {
    text-align: center;
    padding: 2.5rem 2rem;
    position: relative;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.07);
    border-radius: 20px;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1),
                background 0.3s cubic-bezier(0.22, 1, 0.36, 1),
                border-color 0.3s cubic-bezier(0.22, 1, 0.36, 1),
                box-shadow 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}

.step-card:hover {
    transform: translateY(-4px);
    background: rgba(255, 255, 255, 0.07);
    border-color: rgba(255, 255, 255, 0.14);
    box-shadow: 0 12px 32px rgba(21, 59, 79, 0.12), 0 4px 8px rgba(21, 59, 79, 0.06);
}

.step-text {
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
        gap: 1rem;
    }

    .steps-grid::before {
        display: none;
    }

    .step-card {
        text-align: left;
        display: grid;
        grid-template-columns: 52px 1fr;
        gap: 0 1rem;
        align-items: center;
        padding: 1.5rem;
    }

    .step-backdrop {
        display: none;
    }

    .step-card {
        text-align: left;
        display: flex;
        gap: 1rem;
        align-items: center;
        padding: 1.25rem 1.5rem;
    }

    .step-icon-circle {
        width: 44px;
        height: 44px;
        margin: 0;
        flex-shrink: 0;
    }

    .step-icon-circle svg {
        width: 20px;
        height: 20px;
    }

    .step-text {
        flex: 1;
        min-width: 0;
    }

    .step-card h4 {
        margin-bottom: 0.25rem;
        font-size: 1rem;
    }

    .step-card p {
        max-width: none;
        margin: 0;
        font-size: 0.82rem;
        line-height: 1.5;
    }
}
</style>
