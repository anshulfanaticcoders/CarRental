<script setup>
import { ref, onMounted, watch, computed } from "vue";
import axios from "axios";
import { usePage } from '@inertiajs/vue3';
import { Skeleton } from '@/Components/ui/skeleton';

const faqs = ref([]);
const activeId = ref(null);
const isLoading = ref(true);
const page = usePage();

const _p = (key) => {
    const translations = page.props.translations?.homepage || {};
    return translations[key] || key;
};

// Pad number to 2 digits
const padNumber = (n) => String(n).padStart(2, '0');

// Toggle accordion
const toggle = (id) => {
    activeId.value = activeId.value === id ? null : id;
};

const isOpen = (id) => activeId.value === id;

// Fetch FAQs from the backend
const fetchFaqs = async () => {
    try {
        const currentLocale = page.props.locale || 'en';
        const response = await axios.get(`/api/faqs?locale=${currentLocale}`);
        faqs.value = response.data;
        if (response.data.length > 0) {
            activeId.value = response.data[0].id;
        }
    } catch (error) {
        console.error("Error fetching FAQs:", error);
    } finally {
        isLoading.value = false;
    }
};

onMounted(fetchFaqs);

watch(() => page.props.locale, (newLocale, oldLocale) => {
    if (newLocale !== oldLocale) {
        isLoading.value = true;
        activeId.value = null;
        fetchFaqs();
    }
});
</script>

<template>
    <section class="fq-section" aria-label="Frequently asked questions">
        <!-- Ambient orbs -->
        <div class="fq-orb fq-orb--1"></div>
        <div class="fq-orb fq-orb--2"></div>
        <div class="fq-orb fq-orb--3"></div>

        <div class="full-w-container fq-container">

            <!-- Header -->
            <div class="fq-header">
                <div class="fq-badge">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="12" cy="17" r="0.5"/></svg>
                    {{ _p('faqs_badge') }}
                </div>
                <h3 class="fq-heading">{{ _p('faqs_title') }}</h3>
                <p class="fq-lead">{{ _t('common','faqs_description') }}</p>
            </div>

            <!-- Loading -->
            <template v-if="isLoading">
                <div class="fq-grid">
                    <div v-for="i in 6" :key="i" class="fq-skeleton">
                        <Skeleton class="h-[1.1rem] w-3/4 rounded-md" />
                        <Skeleton class="h-[0.85rem] w-1/2 rounded-md mt-3" />
                    </div>
                </div>
            </template>

            <!-- FAQ Cards -->
            <template v-else>
                <div class="fq-grid">
                    <div
                        v-for="(faq, index) in faqs"
                        :key="faq.id"
                        class="fq-card"
                        :class="{ 'is-open': isOpen(faq.id) }"
                        @click="toggle(faq.id)"
                    >
                        <button class="fq-trigger" type="button">
                            <span class="fq-number">{{ padNumber(index + 1) }}</span>
                            <span class="fq-question">{{ faq.question }}</span>
                            <span class="fq-chevron">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                            </span>
                        </button>
                        <div class="fq-panel">
                            <div class="fq-panel-inner">
                                <div class="fq-divider"></div>
                                <div class="fq-answer">{{ faq.answer }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

        </div>
    </section>
</template>

<style scoped>
/* ════════════════════════════════════════
   SECTION — DARK THEME
   ════════════════════════════════════════ */
.fq-section {
    position: relative;
    overflow: hidden;
    isolation: isolate;
    padding: clamp(4rem, 8vw, 7rem) 0;
    background: linear-gradient(155deg, #0a1d28 0%, #153b4f 40%, #0f2936 70%, #0b1b26 100%);
    width: 100%;
}

.fq-section::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 600px 500px at 12% 15%, rgba(6, 182, 212, 0.14), transparent),
        radial-gradient(ellipse 500px 400px at 88% 80%, rgba(6, 182, 212, 0.07), transparent),
        radial-gradient(ellipse 900px 350px at 50% 50%, rgba(21, 59, 79, 0.5), transparent);
    pointer-events: none;
    z-index: 0;
}

.fq-section::after {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(6, 182, 212, 0.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(6, 182, 212, 0.025) 1px, transparent 1px);
    background-size: 60px 60px;
    mask-image: radial-gradient(ellipse 70% 60% at 50% 50%, black 20%, transparent 70%);
    -webkit-mask-image: radial-gradient(ellipse 70% 60% at 50% 50%, black 20%, transparent 70%);
    pointer-events: none;
    z-index: 0;
}

/* Floating orbs */
.fq-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.25;
    pointer-events: none;
    z-index: 0;
    animation: fqOrbFloat 14s ease-in-out infinite;
}
.fq-orb--1 { width: 280px; height: 280px; background: #06b6d4; top: 8%; left: -4%; }
.fq-orb--2 { width: 200px; height: 200px; background: #153b4f; bottom: 10%; right: -3%; animation-delay: -5s; }
.fq-orb--3 { width: 160px; height: 160px; background: #06b6d4; top: 60%; left: 50%; animation-delay: -9s; opacity: 0.12; }

@keyframes fqOrbFloat {
    0%, 100% { transform: translateY(0) scale(1); }
    50% { transform: translateY(-20px) scale(1.05); }
}

.fq-container {
    position: relative;
    z-index: 1;
}

/* ════════════════════════════════════════
   HEADER
   ════════════════════════════════════════ */
.fq-header {
    text-align: center;
    margin-bottom: 3.5rem;
}

.fq-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    color: #06b6d4;
    padding: 8px 20px;
    background: rgba(6, 182, 212, 0.1);
    border: 1px solid rgba(6, 182, 212, 0.15);
    border-radius: 100px;
    margin-bottom: 20px;
}

.fq-badge svg {
    width: 14px;
    height: 14px;
    fill: #06b6d4;
}

.fq-heading {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 800;
    line-height: 1.15;
    color: #ffffff;
    letter-spacing: -0.02em;
    margin-bottom: 0.75rem;
}

.fq-lead {
    font-size: 1.1rem;
    color: #94a3b8;
    max-width: 540px;
    margin: 0 auto;
    line-height: 1.65;
}

/* ════════════════════════════════════════
   TWO-COL GRID
   ════════════════════════════════════════ */
.fq-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    align-items: start;
}

/* ════════════════════════════════════════
   SKELETON
   ════════════════════════════════════════ */
.fq-skeleton {
    background: rgba(21, 59, 79, 0.22);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(6, 182, 212, 0.06);
    border-radius: 16px;
    padding: 1.25rem 1.35rem;
}

/* ════════════════════════════════════════
   CARD
   ════════════════════════════════════════ */
.fq-card {
    position: relative;
    background: rgba(21, 59, 79, 0.22);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(6, 182, 212, 0.06);
    border-radius: 16px;
    cursor: pointer;
    transition:
        border-color 0.5s cubic-bezier(0.22, 1, 0.36, 1),
        box-shadow 0.5s cubic-bezier(0.22, 1, 0.36, 1),
        background 0.5s cubic-bezier(0.22, 1, 0.36, 1);
}

.fq-card:hover {
    border-color: rgba(6, 182, 212, 0.12);
    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.2);
}

.fq-card.is-open {
    background: rgba(21, 59, 79, 0.35);
    border-color: rgba(6, 182, 212, 0.25);
    box-shadow:
        0 20px 60px rgba(0, 0, 0, 0.25),
        0 0 40px rgba(6, 182, 212, 0.06);
}

/* ════════════════════════════════════════
   TRIGGER
   ════════════════════════════════════════ */
.fq-trigger {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 1rem;
    width: 100%;
    padding: 1.25rem 1.35rem;
    background: none;
    border: none;
    cursor: pointer;
    text-align: left;
    font-family: inherit;
}

.fq-number {
    flex-shrink: 0;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.78rem;
    font-weight: 700;
    color: #64748b;
    min-width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(148, 163, 184, 0.08);
    border: 1px solid rgba(148, 163, 184, 0.1);
    border-radius: 10px;
    transition: all 0.5s cubic-bezier(0.22, 1, 0.36, 1);
}

.fq-card.is-open .fq-number {
    background: linear-gradient(135deg, #06b6d4, #0891b2);
    border-color: transparent;
    color: white;
    box-shadow: 0 4px 16px rgba(6, 182, 212, 0.35);
}

.fq-question {
    flex: 1;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1rem;
    font-weight: 600;
    color: #cbd5e1;
    line-height: 1.4;
    transition: color 0.4s ease;
}

.fq-card.is-open .fq-question {
    color: #f1f5f9;
}

.fq-chevron {
    flex-shrink: 0;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(148, 163, 184, 0.06);
    border: 1px solid rgba(148, 163, 184, 0.08);
    transition:
        background 0.5s cubic-bezier(0.22, 1, 0.36, 1),
        border-color 0.5s cubic-bezier(0.22, 1, 0.36, 1),
        transform 0.5s cubic-bezier(0.22, 1, 0.36, 1);
}

.fq-card.is-open .fq-chevron {
    background: rgba(6, 182, 212, 0.1);
    border-color: rgba(6, 182, 212, 0.2);
    transform: rotate(180deg);
}

.fq-chevron svg {
    width: 14px;
    height: 14px;
    color: #64748b;
    transition: color 0.4s ease;
}

.fq-card.is-open .fq-chevron svg {
    color: #22d3ee;
}

/* ════════════════════════════════════════
   PANEL — SINGLE ANIMATION SOURCE
   ════════════════════════════════════════ */
.fq-panel {
    display: grid;
    grid-template-rows: 0fr;
    transition: grid-template-rows 0.45s cubic-bezier(0.22, 1, 0.36, 1);
}

.fq-card.is-open .fq-panel {
    grid-template-rows: 1fr;
}

.fq-panel-inner {
    overflow: hidden;
    min-height: 0;
}

.fq-divider {
    height: 1px;
    margin: 0 1.35rem 0 4.35rem;
    background: linear-gradient(90deg,
        transparent,
        rgba(6, 182, 212, 0.15) 20%,
        rgba(6, 182, 212, 0.15) 80%,
        transparent);
}

.fq-answer {
    position: relative;
    z-index: 1;
    padding: 1rem 1.35rem 1.25rem 4.35rem;
    font-size: 0.95rem;
    color: #94a3b8;
    line-height: 1.8;
}

/* ════════════════════════════════════════
   RESPONSIVE
   ════════════════════════════════════════ */
@media (max-width: 768px) {
    .fq-header { margin-bottom: 2.5rem; }

    .fq-heading { font-size: 1.75rem; }

    .fq-grid { grid-template-columns: 1fr; }

    .fq-trigger {
        padding: 1.1rem 1rem;
        gap: 0.75rem;
    }

    .fq-question { font-size: 0.92rem; }

    .fq-divider {
        margin-left: 3.5rem;
        margin-right: 1rem;
    }

    .fq-answer {
        padding-left: 3.5rem;
        padding-right: 1rem;
        font-size: 0.9rem;
    }

    .fq-number {
        min-width: 28px;
        height: 28px;
        font-size: 0.72rem;
    }
}
</style>
