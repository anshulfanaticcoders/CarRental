<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    stats: {
        type: Array,
        default: () => [],
    },
});

const root = ref(null);
const displayNumbers = ref([]);
let observer = null;

const normalizedStats = computed(() => props.stats.filter(stat => stat?.label));

watch(normalizedStats, (stats) => {
    displayNumbers.value = stats.map(stat => stat.number || '0');
}, { immediate: true });

function setFinalValues() {
    displayNumbers.value = normalizedStats.value.map(stat => stat.number || '0');
}

function animateStats() {
    const stats = normalizedStats.value;

    stats.forEach((stat, index) => {
        const target = Number(stat.target || stat.number || 0);
        if (!target) {
            displayNumbers.value[index] = stat.number || '0';
            return;
        }

        const start = performance.now();
        const duration = 1300;

        const tick = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 4);
            displayNumbers.value[index] = Math.round(target * eased).toLocaleString();

            if (progress < 1) {
                requestAnimationFrame(tick);
                return;
            }

            displayNumbers.value[index] = stat.number || String(target);
        };

        requestAnimationFrame(tick);
    });
}

onMounted(() => {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reducedMotion || !root.value || !('IntersectionObserver' in window)) {
        setFinalValues();
        return;
    }

    observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            animateStats();
            observer?.disconnect();
        });
    }, { threshold: 0.35 });

    observer.observe(root.value);
});

onUnmounted(() => {
    observer?.disconnect();
});
</script>

<template>
    <div v-if="normalizedStats.length" ref="root" class="about-stats" aria-label="Vrooem platform highlights">
        <div
            v-for="(stat, index) in normalizedStats"
            :key="`${stat.label}-${index}`"
            class="about-stat about-reveal"
            :style="{ '--reveal-delay': `${index * 70}ms` }"
        >
            <div class="about-stat-value">
                <span>{{ displayNumbers[index] || stat.number }}</span><span>{{ stat.suffix }}</span>
            </div>
            <div class="about-stat-label">{{ stat.label }}</div>
        </div>
    </div>
</template>

<style scoped>
.about-stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.8rem;
    width: 100%;
}

.about-stat {
    min-width: 0;
    min-height: 104px;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 1.15rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 22px;
    background: linear-gradient(145deg, rgba(255, 255, 255, 0.09), rgba(255, 255, 255, 0.035));
    backdrop-filter: blur(16px) saturate(1.25);
    box-shadow: 0 20px 48px rgba(2, 12, 20, 0.22);
    transition: transform 300ms cubic-bezier(0.22, 1, 0.36, 1), border-color 300ms ease, background-color 300ms ease, box-shadow 300ms ease;
}

.about-stat:hover {
    transform: translateY(-5px);
    border-color: rgba(34, 211, 238, 0.32);
    box-shadow: 0 28px 70px rgba(2, 12, 20, 0.34), 0 0 34px rgba(34, 211, 238, 0.13);
}

.about-stat-value {
    font-family: var(--jakarta-font-family, "Plus Jakarta Sans", sans-serif);
    font-size: clamp(1.7rem, 3.4vw, 2.55rem);
    font-weight: 800;
    line-height: 1;
    color: #ffffff;
    letter-spacing: 0;
}

.about-stat-value span:last-child {
    color: #22d3ee;
}

.about-stat-label {
    margin-top: 0.55rem;
    color: rgba(255, 255, 255, 0.62);
    font-size: 0.84rem;
    font-weight: 600;
    line-height: 1.35;
}

@media (min-width: 760px) {
    .about-stats {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

@media (max-width: 759px) {
    .about-stat {
        min-height: 106px;
    }
}

@media (max-width: 430px) {
    .about-stats {
        grid-template-columns: 1fr;
    }
}

@media (prefers-reduced-motion: reduce) {
    .about-stat {
        transition: none;
    }

    .about-stat:hover {
        transform: none;
    }
}
</style>
