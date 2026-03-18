<script setup>
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const _p = (key, fallback = '') => {
    const translations = page.props.translations?.homepage || {};
    return translations[key] || fallback || key;
};

const stats = [
    { number: '800', suffix: '+', label: 'Providers Worldwide' },
    { number: '180', suffix: '+', label: 'Countries Covered' },
    { number: '4.8', suffix: '/5', label: 'Customer Rating' },
    { number: '250K', suffix: '+', label: 'Trips Completed' },
];
</script>

<template>
    <div class="trust-bar">
        <div class="full-w-container">
            <div class="trust-bar-inner">
                <template v-for="(stat, i) in stats" :key="i">
                    <div class="trust-stat">
                        <div class="trust-stat-number">{{ stat.number }}<span>{{ stat.suffix }}</span></div>
                        <div class="trust-stat-label">{{ stat.label }}</div>
                    </div>
                    <div v-if="i < stats.length - 1" class="trust-divider"></div>
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped>
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

@media (max-width: 1024px) {
    .trust-bar-inner { gap: 2rem; }
    .trust-stat-number { font-size: 1.65rem; }
}

@media (max-width: 768px) {
    .trust-bar-inner { gap: 1.5rem; }
    .trust-stat-number { font-size: 1.4rem; }
    .trust-divider { height: 24px; }
}

@media (max-width: 480px) {
    .trust-bar-inner { gap: 1rem; }
    .trust-divider { display: none; }
    .trust-stat-number { font-size: 1.25rem; }
    .trust-stat-label { font-size: 0.68rem; }
}
</style>
