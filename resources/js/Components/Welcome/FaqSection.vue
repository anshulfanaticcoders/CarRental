<script setup>
import { computed, ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { Link, usePage } from '@inertiajs/vue3';
import { useScrollAnimation } from '@/composables/useScrollAnimation';

const page = usePage();
const _p = (key, fallback = '') => (page.props.translations?.homepage?.[key] || fallback || key);
const contactSupportHref = computed(() => `/${page.props.locale || 'en'}/contact-us`);

const faqs = ref([]);
const activeId = ref(null);
const loading = ref(true);

const toggle = (id) => { activeId.value = activeId.value === id ? null : id; };

onMounted(async () => {
    try {
        const locale = page.props.locale || 'en';
        const r = await axios.get(`/api/faqs?locale=${locale}`);
        faqs.value = r.data;
        if (r.data.length) activeId.value = r.data[0].id;
    } catch (e) { /* silent */ }
    finally { loading.value = false; }
});

watch(() => page.props.locale, async (newL, oldL) => {
    if (newL !== oldL) {
        loading.value = true; activeId.value = null;
        try { const r = await axios.get(`/api/faqs?locale=${newL}`); faqs.value = r.data; if (r.data.length) activeId.value = r.data[0].id; }
        catch(e) { /* silent */ } finally { loading.value = false; }
    }
});

useScrollAnimation('.faq-section', '.faq-left, .faq-item', {
    y: 48,
    duration: 0.9,
    stagger: 0.08,
});
</script>

<template>
    <section id="faq" class="faq-section">
        <div class="full-w-container">
            <div class="faq-layout">
                <div class="faq-left sr-reveal">
                    <span class="faq-label">{{ _p('faqs_badge', 'FAQ') }}</span>
                    <h3 class="faq-title">{{ _p('faqs_title', 'Questions we hear most.') }}</h3>
                    <p class="faq-lead">Can't find what you're looking for? Our support team is available 24/7.</p>
                    <Link :href="contactSupportHref" class="faq-btn">Contact Support</Link>
                </div>
                <div class="faq-list">
                    <div v-for="faq in faqs" :key="faq.id" class="faq-item" :class="{ active: activeId === faq.id }">
                        <button class="faq-question" @click="toggle(faq.id)">
                            {{ faq.question }}
                            <span class="faq-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            </span>
                        </button>
                        <div class="faq-answer-wrap">
                            <div class="faq-answer">
                                <div class="faq-answer-inner">{{ faq.answer }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.sr-reveal { visibility: hidden; }

.faq-section {
    padding: clamp(4rem, 8vw, 7rem) 0;
    background: linear-gradient(180deg, #f8fafc 0%, #fff 45%, #f8fafc 100%);
    color: #0f172a;
}

.faq-layout { display: grid; grid-template-columns: 1fr 1.5fr; gap: 4rem; align-items: start; }

.faq-label {
    display: inline-flex; align-items: center; gap: 0.5rem;
    font-size: 0.8rem; font-weight: 600; letter-spacing: 0.14em; text-transform: uppercase;
    color: #1c4d66;
}
.faq-label::before { content: ""; display: block; width: 24px; height: 1.5px; background: #1c4d66; }
.faq-title { font-size: clamp(2rem, 3.5vw, 3rem); font-weight: 700; line-height: 1.12; letter-spacing: -0.02em; color: #0a1d28; margin-top: 0.75rem; }
.faq-lead { color: #64748b; margin-top: 1rem; font-size: 0.95rem; line-height: 1.7; }
.faq-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-top: 1.5rem;
    padding: 0.85rem 1.75rem;
    border-radius: 14px;
    background: #153b4f;
    color: #fff;
    border: 1px solid #153b4f;
    font-size: 0.92rem;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}

.faq-btn:hover {
    background: #1c4d66;
    border-color: #1c4d66;
    transform: translateY(-2px);
}

.faq-list { display: flex; flex-direction: column; }
.faq-item { border-bottom: 1px solid #e2e8f0; }

.faq-question {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1.25rem 0; font-size: 1rem; font-weight: 600; color: #0a1d28;
    cursor: pointer; background: none; border: none; width: 100%;
    font-family: 'Plus Jakarta Sans', sans-serif; text-align: left; gap: 1rem;
    transition: color 0.2s;
}
.faq-question:hover { color: #1c4d66; }

.faq-icon {
    width: 28px; height: 28px; border-radius: 50%; background: #f1f5f9;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    transition: all 0.4s cubic-bezier(0.22,1,0.36,1);
}
.faq-icon svg { width: 14px; height: 14px; color: #64748b; transition: transform 0.4s cubic-bezier(0.22,1,0.36,1), color 0.3s; }
.faq-item.active .faq-icon { background: #153b4f; }
.faq-item.active .faq-icon svg { transform: rotate(45deg); color: #fff; }

.faq-answer-wrap { display: grid; grid-template-rows: 0fr; transition: grid-template-rows 0.45s cubic-bezier(0.22,1,0.36,1); }
.faq-item.active .faq-answer-wrap { grid-template-rows: 1fr; }
.faq-answer { overflow: hidden; }
.faq-answer-inner {
    padding-bottom: 1.25rem; font-size: 0.92rem; color: #64748b; line-height: 1.7;
    opacity: 0; transform: translateY(-8px);
    transition: opacity 0.35s cubic-bezier(0.22,1,0.36,1) 0.1s, transform 0.35s cubic-bezier(0.22,1,0.36,1) 0.1s;
}
.faq-item.active .faq-answer-inner { opacity: 1; transform: translateY(0); }

@media (max-width: 1024px) { .faq-layout { grid-template-columns: 1fr; gap: 2rem; } }
@media (max-width: 768px) { .faq-layout { gap: 1.5rem; } }
</style>
