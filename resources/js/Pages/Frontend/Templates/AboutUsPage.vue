<script setup>
import Footer from '@/Components/Footer.vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { computed, onMounted, onUnmounted } from 'vue';
import {
    Car, Wallet, Clock, MapPin, Wrench, HeartHandshake,
    Shield, Star, Headphones, Zap, Globe, Users, Award,
    ThumbsUp, CheckCircle, Sparkles
} from 'lucide-vue-next';

const props = defineProps({
    page: Object,
    meta: Object,
    sections: Array,
    seo: Object,
    locale: String,
    pages: Object,
});

// --- Section helpers ---
const getSection = (type) => props.sections?.find(s => s.type === type);

const heroSection = computed(() => getSection('hero'));
const contentSection = computed(() => getSection('content'));
const featuresSection = computed(() => getSection('features'));
const statsSection = computed(() => getSection('stats'));
const splitSection = computed(() => getSection('split'));
const ctaSection = computed(() => getSection('cta'));

const featuresItems = computed(() => {
    const items = featuresSection.value?.settings?.items;
    return Array.isArray(items) ? items : [];
});

const statsItems = computed(() => {
    const items = statsSection.value?.settings?.items;
    return Array.isArray(items) ? items : [];
});

const statsSubtitle = computed(() => statsSection.value?.settings?.subtitle || '');

const splitSubtitle = computed(() => splitSection.value?.settings?.subtitle || '');
const splitImageUrl = computed(() => splitSection.value?.settings?.image_url || '');

const ctaButtonText = computed(() => ctaSection.value?.settings?.button_text || 'Book Your Ride Today');
const ctaButtonUrl = computed(() => ctaSection.value?.settings?.button_url || '');

const heroImageUrl = computed(() => {
    return heroSection.value?.settings?.image_url
        || 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=1400&q=80';
});

// Map emoji or title keywords to Lucide icon components
const emojiIconMap = {
    '🚙': Car, '🚗': Car, '🏎': Car,
    '💰': Wallet, '💵': Wallet, '💲': Wallet,
    '⏰': Clock, '🕐': Clock, '⏱': Clock,
    '📍': MapPin, '📌': MapPin, '🗺': MapPin,
    '🔧': Wrench, '🛠': Wrench,
    '🤝': HeartHandshake, '👥': Users,
    '🛡': Shield, '🔒': Shield,
    '⭐': Star, '🌟': Star,
    '🎧': Headphones, '📞': Headphones,
    '⚡': Zap, '🌍': Globe, '🏆': Award,
    '👍': ThumbsUp, '✅': CheckCircle,
};

const titleKeywordMap = [
    [/fleet|car|vehicle/i, Car],
    [/pric|cost|money|budget|transparent/i, Wallet],
    [/time|hour|flexible|rental option/i, Clock],
    [/location|place|convenient|map/i, MapPin],
    [/maintain|repair|service|wrench/i, Wrench],
    [/support|customer|help|contact/i, HeartHandshake],
    [/safe|secur|protect|insur|shield/i, Shield],
    [/quality|star|premium|excel/i, Star],
    [/fast|speed|quick|instant/i, Zap],
    [/global|world|intern/i, Globe],
    [/team|people|staff/i, Users],
    [/award|best|trust/i, Award],
];

const getFeatureIcon = (feature) => {
    if (feature.emoji && emojiIconMap[feature.emoji]) {
        return emojiIconMap[feature.emoji];
    }
    const title = feature.title || '';
    for (const [regex, icon] of titleKeywordMap) {
        if (regex.test(title)) return icon;
    }
    return Sparkles;
};

// --- Scroll-reveal + counter animation ---
let revealObserver = null;
let statsObserver = null;

onMounted(() => {
    revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('au-visible');
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    document.querySelectorAll('.au-reveal').forEach(el => {
        revealObserver.observe(el);
    });

    // Counter animation for stats
    const animateCounters = () => {
        const counters = document.querySelectorAll('.au-stat-num');
        counters.forEach(counter => {
            const rawText = counter.textContent;
            const target = parseInt(rawText.replace(/[^\d]/g, ''));
            if (isNaN(target) || target === 0) return;

            const duration = 2000;
            const start = performance.now();

            const format = (n) => {
                if (rawText.includes('/')) return '24/7';
                if (rawText.includes('%')) return (n).toFixed(1) + '%';
                return Math.floor(n).toLocaleString() + '+';
            };

            const tick = (now) => {
                const progress = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                counter.textContent = format(target * eased);
                if (progress < 1) requestAnimationFrame(tick);
            };

            requestAnimationFrame(tick);
        });
    };

    statsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounters();
                statsObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.4 });

    const statsEl = document.querySelector('.au-stats-row');
    if (statsEl) statsObserver.observe(statsEl);
});

onUnmounted(() => {
    if (revealObserver) revealObserver.disconnect();
    if (statsObserver) statsObserver.disconnect();
});
</script>

<template>
    <SeoHead :seo="seo" />
    <AuthenticatedHeaderLayout />

    <div class="au-page">

        <!-- Hero -->
        <section class="au-hero">
            <div class="au-hero-bg">
                <img :src="heroImageUrl" :alt="page.title" />
            </div>
            <div class="au-container">
                <div class="au-hero-content">
                    <div class="au-hero-badge">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                        {{ page.title }}
                    </div>
                    <h1>{{ page.title }}</h1>
                    <div v-if="heroSection?.content" class="au-hero-sub" v-html="heroSection.content"></div>
                </div>
            </div>
            <div class="au-wave">
                <svg viewBox="0 0 1440 40" fill="none" preserveAspectRatio="none">
                    <path d="M0 40h1440V16c-120 12-360 24-720 24S120 28 0 16v24z" fill="#ffffff"/>
                </svg>
            </div>
        </section>

        <!-- Story / Content -->
        <section v-if="contentSection?.content || page.content" class="au-story">
            <div class="au-container">
                <div class="au-story-grid au-reveal">
                    <div>
                        <div v-if="contentSection?.title" class="au-story-label">{{ contentSection.title }}</div>
                        <div class="au-story-text" v-html="contentSection?.content || page.content"></div>
                    </div>
                    <div v-if="meta?.team_image" class="au-story-img">
                        <img :src="meta.team_image" :alt="page.title" />
                    </div>
                </div>
            </div>
        </section>

        <!-- Company Bio + Team Image (only when no content section) -->
        <section v-if="!contentSection && (meta?.company_bio || meta?.team_image)" class="au-story au-story--flush">
            <div class="au-container">
                <div class="au-story-grid au-reveal">
                    <div v-if="meta?.company_bio" class="au-story-text" v-html="meta.company_bio"></div>
                    <div v-if="meta?.team_image" class="au-story-img">
                        <img :src="meta.team_image" :alt="page.title" />
                    </div>
                </div>
            </div>
        </section>

        <!-- Mission + Stats — Dark -->
        <section v-if="meta?.mission_statement || statsItems.length > 0" class="au-dark-band">
            <div class="au-dark-orb au-dark-orb--1"></div>
            <div class="au-dark-orb au-dark-orb--2"></div>
            <div class="au-container au-dark-inner">
                <div v-if="meta?.mission_statement" class="au-mission-block au-reveal">
                    <h2>{{ _t('aboutus', 'commitment_to_excellence_title') || 'Our Commitment to Excellence' }}</h2>
                    <div v-html="meta.mission_statement"></div>
                </div>
                <div v-if="statsItems.length > 0" class="au-stats-row">
                    <div v-for="(stat, si) in statsItems" :key="si" class="au-stat">
                        <div class="au-stat-num">{{ stat.number }}</div>
                        <div class="au-stat-lbl">{{ stat.label }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section v-if="featuresItems.length > 0" class="au-features">
            <div class="au-container">
                <div v-if="featuresSection?.title" class="au-features-head au-reveal">
                    <h2>{{ featuresSection.title }}</h2>
                </div>
                <div class="au-features-grid">
                    <div v-for="(feature, fi) in featuresItems" :key="fi" class="au-feat au-reveal">
                        <div class="au-feat-icon">
                            <component :is="getFeatureIcon(feature)" :size="22" :stroke-width="2" />
                        </div>
                        <div class="au-feat-body">
                            <h3>{{ feature.title }}</h3>
                            <p>{{ feature.description }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Split Layout -->
        <section v-if="splitSection" class="au-bio">
            <div class="au-container">
                <div class="au-bio-grid au-reveal">
                    <div v-if="splitImageUrl" class="au-bio-img">
                        <img :src="splitImageUrl" :alt="splitSection.title || ''" />
                    </div>
                    <div class="au-bio-content">
                        <h2 v-if="splitSection.title">{{ splitSection.title }}</h2>
                        <h3 v-if="splitSubtitle">{{ splitSubtitle }}</h3>
                        <div v-if="splitSection.content" v-html="splitSection.content"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section v-if="ctaSection" class="au-cta">
            <div class="au-container au-cta-inner au-reveal">
                <h2 v-if="ctaSection.title">{{ ctaSection.title }}</h2>
                <div v-if="ctaSection.content" class="au-cta-text" v-html="ctaSection.content"></div>
                <a v-if="ctaButtonUrl" :href="ctaButtonUrl" class="au-cta-btn">{{ ctaButtonText }}</a>
            </div>
        </section>

    </div>
    <Footer />
</template>

<style scoped>
/* ═══════════ HERO — FULL BG IMAGE ═══════════ */
.au-hero {
    position: relative;
    overflow: hidden;
    min-height: 420px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.au-hero-bg { position: absolute; inset: 0; z-index: 0; }
.au-hero-bg img { width: 100%; height: 100%; object-fit: cover; }
.au-hero-bg::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(160deg, rgba(10,29,40,0.88) 0%, rgba(21,59,79,0.75) 50%, rgba(15,41,54,0.85) 100%);
}
.au-hero-content {
    position: relative; z-index: 2; text-align: center;
    max-width: 680px; margin: 0 auto;
    padding: clamp(4rem,8vw,6rem) 1.5rem;
}
.au-hero-badge {
    display: inline-flex; align-items: center; gap: 7px;
    font-family: 'IBM Plex Sans', sans-serif;
    font-size: 0.75rem; font-weight: 600;
    letter-spacing: 0.14em; text-transform: uppercase;
    color: #06b6d4; padding: 6px 16px;
    background: rgba(6,182,212,0.12);
    border: 1px solid rgba(6,182,212,0.2);
    border-radius: 100px; margin-bottom: 1.25rem;
}
.au-hero-badge svg { width: 12px; height: 12px; }
.au-hero h1 {
    font-family: 'IBM Plex Sans', sans-serif;
    font-size: clamp(2.25rem,5.5vw,3.5rem);
    font-weight: 900; color: #fff; line-height: 1.12;
    letter-spacing: -0.03em; margin-bottom: 1rem;
    text-shadow: 0 2px 20px rgba(0,0,0,0.3);
}
.au-hero-sub { font-size: 1.08rem; color: rgba(255,255,255,0.75); line-height: 1.7; max-width: 540px; margin: 0 auto; }
.au-hero-sub :deep(p) { margin: 0; }
.au-wave { position: absolute; bottom: -1px; left: 0; width: 100%; line-height: 0; z-index: 3; }
.au-wave svg { width: 100%; height: 40px; }

/* ═══════════ CONTAINER ═══════════ */
.au-container { width: min(92%, 1200px); margin-inline: auto; }

/* ═══════════ STORY — LIGHT ═══════════ */
.au-story { padding: clamp(3rem,6vw,4.5rem) 0; background: #fff; }
.au-story--flush { padding-top: 0; }
.au-story-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2.5rem; align-items: center; }
.au-story-label {
    font-family: 'IBM Plex Sans', sans-serif;
    font-size: 0.76rem; font-weight: 700;
    letter-spacing: 0.12em; text-transform: uppercase;
    color: #06b6d4; margin-bottom: 0.6rem;
}
.au-story-text { font-size: 0.96rem; color: #64748b; line-height: 1.8; }
.au-story-text :deep(h2) {
    font-family: 'IBM Plex Sans', sans-serif;
    font-size: clamp(1.4rem,2.8vw,1.85rem); font-weight: 800;
    color: #153b4f; line-height: 1.25; margin-bottom: 1rem;
}
.au-story-text :deep(p) { margin-bottom: 0.75rem; line-height: 1.8; }
.au-story-text :deep(p:last-child) { margin-bottom: 0; }
.au-story-img {
    border-radius: 18px; overflow: hidden; aspect-ratio: 5/4;
    box-shadow: 0 10px 36px rgba(21,59,79,0.08);
    border: 1px solid #e2e8f0;
}
.au-story-img img { width: 100%; height: 100%; object-fit: cover; }

/* ═══════════ MISSION + STATS — DARK ═══════════ */
.au-dark-band {
    position: relative; overflow: hidden; isolation: isolate;
    padding: clamp(2.5rem,5vw,4rem) 0;
    background: linear-gradient(155deg, #0a1d28 0%, #153b4f 40%, #0f2936 70%, #0b1b26 100%);
}
.au-dark-band::before {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(ellipse 600px 400px at 30% 30%, rgba(6,182,212,0.1), transparent),
                radial-gradient(ellipse 400px 300px at 75% 75%, rgba(6,182,212,0.05), transparent);
    pointer-events: none; z-index: 0;
}
.au-dark-band::after {
    content: ''; position: absolute; inset: 0;
    background-image: linear-gradient(rgba(6,182,212,0.025) 1px, transparent 1px),
                      linear-gradient(90deg, rgba(6,182,212,0.025) 1px, transparent 1px);
    background-size: 60px 60px;
    mask-image: radial-gradient(ellipse 70% 60% at 50% 50%, black 20%, transparent 70%);
    -webkit-mask-image: radial-gradient(ellipse 70% 60% at 50% 50%, black 20%, transparent 70%);
    pointer-events: none; z-index: 0;
}
.au-dark-orb {
    position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.2;
    pointer-events: none; z-index: 0; animation: auFloat 14s ease-in-out infinite;
}
.au-dark-orb--1 { width: 220px; height: 220px; background: #06b6d4; top: 5%; left: -3%; }
.au-dark-orb--2 { width: 160px; height: 160px; background: #06b6d4; bottom: 5%; right: -2%; animation-delay: -7s; opacity: 0.12; }
@keyframes auFloat {
    0%, 100% { transform: translateY(0) scale(1); }
    50% { transform: translateY(-14px) scale(1.04); }
}
.au-dark-inner { position: relative; z-index: 1; }
.au-mission-block { text-align: center; max-width: 760px; margin: 0 auto 2.5rem; }
.au-mission-block h2 {
    font-family: 'IBM Plex Sans', sans-serif;
    font-size: clamp(1.4rem,3vw,2rem); font-weight: 800;
    color: #fff; letter-spacing: -0.02em; margin-bottom: 0.85rem;
}
.au-mission-block :deep(p) { font-size: 1rem; color: #94a3b8; line-height: 1.75; max-width: 640px; margin: 0 auto; }
.au-stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.85rem; }
.au-stat {
    background: rgba(21,59,79,0.28);
    backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(6,182,212,0.08); border-radius: 16px;
    padding: 1.5rem 1rem; text-align: center;
    transition: border-color 0.4s ease, box-shadow 0.4s ease;
}
.au-stat:hover { border-color: rgba(6,182,212,0.18); box-shadow: 0 10px 30px rgba(0,0,0,0.12); }
.au-stat-num {
    font-family: 'IBM Plex Sans', sans-serif;
    font-size: 2rem; font-weight: 800;
    background: linear-gradient(135deg, #22d3ee, #06b6d4);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text; line-height: 1.15; margin-bottom: 0.15rem;
}
.au-stat-lbl { font-size: 0.82rem; color: #64748b; font-weight: 500; }

/* ═══════════ FEATURES — LIGHT ═══════════ */
.au-features {
    padding: clamp(3rem,6vw,4.5rem) 0;
    background: linear-gradient(180deg, #f8fafc 0%, #fff 50%, #f8fafc 100%);
}
.au-features-head { text-align: center; margin-bottom: 2.25rem; }
.au-features-head h2 {
    font-family: 'IBM Plex Sans', sans-serif;
    font-size: clamp(1.4rem,3vw,2rem); font-weight: 800;
    color: #153b4f; letter-spacing: -0.02em;
}
.au-features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
.au-feat {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 16px;
    padding: 1.5rem 1.25rem; display: flex; align-items: flex-start; gap: 1rem;
    transition: border-color 0.4s ease, box-shadow 0.4s ease, transform 0.4s ease;
}
.au-feat:hover {
    border-color: rgba(6,182,212,0.3);
    box-shadow: 0 10px 30px rgba(21,59,79,0.07);
    transform: translateY(-2px);
}
.au-feat-icon {
    width: 50px; height: 50px; min-width: 50px; border-radius: 14px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #06b6d4, #0891b2);
    box-shadow: 0 4px 14px rgba(6,182,212,0.3);
    color: #fff;
    transition: box-shadow 0.4s ease, transform 0.4s ease;
}
.au-feat:hover .au-feat-icon {
    box-shadow: 0 6px 22px rgba(6,182,212,0.4);
    transform: scale(1.05);
}
.au-feat-body { flex: 1; min-width: 0; }
.au-feat h3 {
    font-family: 'IBM Plex Sans', sans-serif;
    font-size: 0.98rem; font-weight: 700; color: #153b4f;
    margin-bottom: 0.25rem; line-height: 1.3;
}
.au-feat p { font-size: 0.88rem; color: #64748b; line-height: 1.6; margin: 0; }

/* ═══════════ SPLIT / VISION — LIGHT ═══════════ */
.au-bio {
    padding: clamp(3rem,6vw,4.5rem) 0;
    background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
}
.au-bio-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2.5rem; align-items: center; }
.au-bio-img {
    border-radius: 18px; overflow: hidden; aspect-ratio: 5/4;
    box-shadow: 0 10px 36px rgba(21,59,79,0.08); border: 1px solid #e2e8f0;
}
.au-bio-img img { width: 100%; height: 100%; object-fit: cover; }
.au-bio-content h2 {
    font-family: 'IBM Plex Sans', sans-serif;
    font-size: clamp(1.4rem,2.8vw,1.85rem); font-weight: 800;
    color: #153b4f; line-height: 1.25; margin-bottom: 0.4rem;
}
.au-bio-content h3 {
    font-family: 'IBM Plex Sans', sans-serif;
    font-size: 1.08rem; font-weight: 600; color: #334155; margin-bottom: 0.85rem;
}
.au-bio-content :deep(p) { font-size: 0.96rem; color: #64748b; line-height: 1.8; margin-bottom: 0.65rem; }
.au-bio-content :deep(p:last-child) { margin-bottom: 0; }

/* ═══════════ CTA — DARK ═══════════ */
.au-cta {
    position: relative; overflow: hidden; isolation: isolate;
    padding: clamp(2.5rem,5vw,4rem) 0;
    background: linear-gradient(155deg, #0a1d28 0%, #153b4f 40%, #0f2936 70%, #0b1b26 100%);
}
.au-cta::before {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(ellipse 500px 300px at 50% 50%, rgba(6,182,212,0.1), transparent);
    pointer-events: none;
}
.au-cta-inner { position: relative; z-index: 1; text-align: center; max-width: 520px; margin: 0 auto; }
.au-cta h2 {
    font-family: 'IBM Plex Sans', sans-serif;
    font-size: clamp(1.5rem,3.5vw,2.25rem); font-weight: 800;
    color: #fff; margin-bottom: 0.6rem; letter-spacing: -0.02em;
}
.au-cta-text { margin-bottom: 1.5rem; }
.au-cta-text :deep(p) { font-size: 0.98rem; color: #94a3b8; line-height: 1.65; }
.au-cta-btn {
    display: inline-block; padding: 0.85rem 2.25rem;
    font-family: 'IBM Plex Sans', sans-serif;
    font-size: 0.92rem; font-weight: 700; color: #fff;
    background: linear-gradient(135deg, #06b6d4, #0891b2);
    border: none; border-radius: 100px; text-decoration: none; cursor: pointer;
    transition: all 0.4s cubic-bezier(0.22,1,0.36,1);
    box-shadow: 0 4px 18px rgba(6,182,212,0.3);
}
.au-cta-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 28px rgba(6,182,212,0.4); }

/* ═══════════ SCROLL REVEAL ═══════════ */
.au-reveal {
    opacity: 0; transform: translateY(28px);
    transition: opacity 0.7s cubic-bezier(0.16,1,0.3,1), transform 0.7s cubic-bezier(0.16,1,0.3,1);
}
.au-reveal.au-visible { opacity: 1; transform: translateY(0); }

/* ═══════════ RESPONSIVE ═══════════ */
@media (max-width: 1024px) {
    .au-features-grid { grid-template-columns: repeat(2, 1fr); }
    .au-stats-row { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .au-hero { min-height: 340px; }
    .au-hero h1 { font-size: 1.85rem; }
    .au-hero-sub { font-size: 0.95rem; }
    .au-hero-content { padding: 3rem 1rem; }
    .au-story-grid, .au-bio-grid { grid-template-columns: 1fr; gap: 1.75rem; }
    .au-story-img { order: -1; aspect-ratio: 16/9; }
    .au-bio-img { order: -1; aspect-ratio: 16/9; }
    .au-features-grid { grid-template-columns: 1fr; }
    .au-stats-row { grid-template-columns: repeat(2, 1fr); gap: 0.65rem; }
    .au-stat { padding: 1.15rem 0.75rem; }
    .au-stat-num { font-size: 1.6rem; }
}
@media (max-width: 480px) {
    .au-stats-row { grid-template-columns: 1fr 1fr; }
}
</style>
