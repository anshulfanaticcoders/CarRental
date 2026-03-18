<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const _p = (key, fallback = '') => {
    const t = page.props.translations?.homepage || {};
    return t[key] || fallback || key;
};

const cards = computed(() => [
    { icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>', title: _p('why_card_1_title', 'Free Internet Included'), text: _p('why_card_1_text', 'Stay connected from arrival to drop-off with effortless, in-car access.') },
    { icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>', title: _p('why_card_2_title', 'Instant Replacement Guarantee'), text: _p('why_card_2_text', 'If your reserved vehicle is unavailable, we secure a replacement instantly.'), tag: _p('why_card_2_tag', 'No extra cost, upgrades when possible') },
    { icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>', title: _p('why_card_3_title', 'Fair Damage Protection'), text: _p('why_card_3_text', 'We protect you from excessive charges for minor scratches or normal wear.') },
    { icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>', title: _p('why_card_4_title', 'Best Price Guarantee'), text: _p('why_card_4_text', 'Transparent pricing with the lowest rate available for every trip.') },
    { icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M15.05 5A5 5 0 0 1 19 8.95M15.05 1A9 9 0 0 1 23 8.94M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 3.06 5.18 2 2 0 0 1 5 3h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 11.8a16 16 0 0 0 6.11 6.11l2.16-2.16a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>', title: _p('why_card_5_title', '24/7 Worldwide Support'), text: _p('why_card_5_text', 'Wherever you are, Vrooem is ready to keep your journey effortless.') },
    { icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>', title: _p('why_card_6_title', 'No Rushing, No Waiting'), text: _p('why_card_6_text', 'Skip long lines and start your trip relaxed with fast, guided pickup.') },
]);
</script>

<template>
    <section class="why-section">
        <div class="why-glow"></div>
        <div class="full-w-container why-z">
            <div class="why-head">
                <span class="why-label">{{ _p('why_choose_us', 'Why Vrooem') }}</span>
                <h3 class="why-title">{{ _p('why_subtitle', 'Built for travelers who refuse to compromise.') }}</h3>
                <p class="why-lead">{{ _p('why_lead', 'Travel with the confidence of a premium concierge and the ease of a local expert.') }}</p>
            </div>
            <div class="why-grid">
                <div v-for="(c, i) in cards" :key="i" class="why-card">
                    <div class="why-icon" v-html="c.icon"></div>
                    <h4>{{ c.title }}</h4>
                    <p>{{ c.text }}</p>
                    <span v-if="c.tag" class="why-tag">{{ c.tag }}</span>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.why-section {
    padding: clamp(4rem, 8vw, 7rem) 0;
    background: linear-gradient(160deg, #0a1d28 0%, #153b4f 45%, #0c2535 100%);
    color: #fff;
    position: relative;
    overflow: hidden;
}
.why-glow {
    position: absolute; inset: 0; pointer-events: none;
    background: radial-gradient(circle at 18% 12%, rgba(34,211,238,0.08), transparent 45%),
                radial-gradient(circle at 80% 78%, rgba(10,29,40,0.3), transparent 55%);
}
.why-z { position: relative; z-index: 1; }

.why-head { text-align: center; margin-bottom: 4rem; }
.why-label {
    display: inline-flex; align-items: center; gap: 0.5rem;
    font-size: 0.8rem; font-weight: 600; letter-spacing: 0.14em; text-transform: uppercase;
    color: #22d3ee;
}
.why-label::before { content: ""; display: block; width: 24px; height: 1.5px; background: #22d3ee; }
.why-title {
    font-size: clamp(2rem, 3.5vw, 3rem); font-weight: 700; line-height: 1.12;
    letter-spacing: -0.02em; color: #fff; margin-top: 0.75rem;
}
.why-lead { margin-top: 1rem; color: rgba(255,255,255,0.5); max-width: 520px; margin-inline: auto; font-size: 1rem; }

.why-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; }

.why-card {
    padding: 2rem; border-radius: 20px;
    background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.07);
    transition: all 0.4s cubic-bezier(0.22,1,0.36,1);
}
.why-card:hover { background: rgba(255,255,255,0.07); border-color: rgba(255,255,255,0.12); transform: translateY(-4px); }

.why-icon {
    width: 44px; height: 44px; border-radius: 12px;
    background: rgba(34,211,238,0.1); border: 1px solid rgba(34,211,238,0.15);
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 1.25rem;
}
.why-icon :deep(svg) { width: 20px; height: 20px; color: #22d3ee; }

.why-card h4 { font-size: 1.1rem; font-weight: 700; margin-bottom: 0.6rem; color: #fff; }
.why-card p { font-size: 0.9rem; line-height: 1.6; color: rgba(255,255,255,0.5); }

.why-tag {
    display: inline-flex; align-items: center; gap: 0.4rem; margin-top: 0.75rem;
    padding: 0.3rem 0.65rem; border-radius: 999px;
    background: rgba(34,211,238,0.08); border: 1px solid rgba(34,211,238,0.12);
    font-size: 0.76rem; color: #22d3ee; font-weight: 500;
}

@media (max-width: 1024px) { .why-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px) { .why-grid { grid-template-columns: 1fr; } .why-card { padding: 1.5rem; } }
</style>
