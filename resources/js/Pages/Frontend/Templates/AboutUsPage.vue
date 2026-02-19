<script setup>
import Footer from '@/Components/Footer.vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { computed, onMounted, onUnmounted } from 'vue';

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

// Structured data from settings
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

// --- Scroll-reveal + counter animation ---
let revealObserver = null;
let statsObserver = null;

onMounted(() => {
    // Scroll reveal
    const revealOptions = {
        threshold: 0.12,
        rootMargin: '0px 0px -40px 0px',
    };

    revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                revealObserver.unobserve(entry.target);
            }
        });
    }, revealOptions);

    document.querySelectorAll('.reveal, .reveal-left, .reveal-right').forEach(el => {
        revealObserver.observe(el);
    });

    // Counter animation for stats
    const animateCounters = () => {
        const counters = document.querySelectorAll('.stat-number');
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

    const statsEl = document.querySelector('.stats-section');
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

    <div class="about-page text-gray-800">
        <!-- Hero Section -->
        <section class="hero-section relative overflow-hidden text-white">
            <div class="absolute inset-0 bg-gradient-to-br from-[#0a1d28] via-customPrimaryColor to-[#1a4d66]"></div>

            <!-- Background car image -->
            <div class="absolute inset-0 bg-cover bg-center opacity-10"
                 style="background-image: url('https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=1600&q=80')">
            </div>

            <!-- Decorative glows -->
            <div class="absolute -top-1/2 -right-[20%] w-[700px] h-[700px] rounded-full bg-[radial-gradient(circle,rgba(6,182,212,0.12)_0%,transparent_70%)] animate-glow"></div>
            <div class="absolute -bottom-[30%] -left-[10%] w-[500px] h-[500px] rounded-full bg-[radial-gradient(circle,rgba(6,182,212,0.08)_0%,transparent_70%)] animate-glow-delayed"></div>

            <!-- Grain -->
            <div class="absolute inset-0 opacity-[0.03] pointer-events-none bg-repeat"
                 style="background-image: url(&quot;data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E&quot;)">
            </div>

            <div class="container mx-auto px-6 py-24 md:py-32 relative z-10 text-center max-w-[720px]">
                <h1 class="text-4xl md:text-5xl lg:text-[56px] font-extrabold mb-5 leading-[1.12] tracking-[-1.5px] animate-hero-fade">
                    {{ page.title }}
                </h1>
                <p v-if="heroSection?.content"
                   class="text-base md:text-lg opacity-0 leading-relaxed font-light animate-hero-fade-delay"
                   v-html="heroSection.content">
                </p>
            </div>

            <!-- Wave -->
            <div class="absolute bottom-[-1px] left-0 w-full leading-[0]">
                <svg viewBox="0 0 1440 48" fill="none" preserveAspectRatio="none" class="w-full h-12">
                    <path d="M0 48h1440V20c-120 15-360 28-720 28S120 35 0 20v28z" fill="white"/>
                </svg>
            </div>
        </section>

        <!-- Story / Content Section -->
        <section v-if="contentSection?.content || page.content" class="py-16 md:py-20 bg-white">
            <div class="container mx-auto px-6">
                <div class="mission-card bg-white rounded-[20px] p-12 max-md:p-8 max-sm:p-6 shadow-md border border-slate-200 max-w-[880px] mx-auto transition-all duration-400 hover:-translate-y-1 hover:shadow-lg reveal">
                    <h2 v-if="contentSection?.title"
                        class="text-[30px] max-md:text-2xl font-extrabold text-customPrimaryColor mb-6 relative inline-block after:content-[''] after:absolute after:bottom-[-6px] after:left-0 after:w-12 after:h-[3px] after:bg-cyan-500 after:rounded">
                        {{ contentSection.title }}
                    </h2>
                    <div class="mission-text text-slate-500 text-[15.5px] leading-[1.9]"
                         v-html="contentSection?.content || page.content">
                    </div>
                </div>
            </div>
        </section>

        <!-- Company Bio + Team Image -->
        <section v-if="meta?.company_bio || meta?.team_image" class="pb-16 md:pb-20 bg-white">
            <div class="container mx-auto px-6">
                <div class="grid md:grid-cols-2 gap-14 max-md:gap-8 items-center">
                    <!-- Bio Content -->
                    <div class="reveal-left" v-if="meta?.company_bio">
                        <div class="bio-content text-slate-500 text-[15px] leading-[1.85]"
                             v-html="meta.company_bio">
                        </div>
                    </div>

                    <!-- Team Image -->
                    <div class="reveal-right" v-if="meta?.team_image">
                        <div class="rounded-[20px] overflow-hidden shadow-lg aspect-[4/3] bg-gradient-to-br from-slate-200 to-slate-100 transition-all duration-500 hover:scale-[1.02] hover:shadow-xl">
                            <img :src="meta.team_image"
                                 :alt="page.title"
                                 class="w-full h-full object-cover" />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Mission Statement -->
        <section v-if="meta?.mission_statement" class="py-20 md:py-[88px] bg-slate-50 relative">
            <!-- Top accent line -->
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[120px] h-[3px] bg-gradient-to-r from-cyan-500 to-customPrimaryColor rounded"></div>

            <div class="container mx-auto px-6">
                <div class="text-center max-w-[780px] mx-auto reveal">
                    <h2 class="text-3xl md:text-4xl font-extrabold text-customPrimaryColor mb-5 tracking-tight">
                        {{ _t('aboutus', 'commitment_to_excellence_title') || 'Our Commitment to Excellence' }}
                    </h2>
                    <div class="text-base md:text-[16.5px] text-slate-500 leading-[1.9]"
                         v-html="meta.mission_statement">
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Grid -->
        <section v-if="featuresItems.length > 0" class="py-16 md:py-20 bg-white">
            <div class="container mx-auto px-6">
                <h2 v-if="featuresSection?.title"
                    class="text-3xl md:text-4xl font-extrabold text-customPrimaryColor text-center mb-12 tracking-tight reveal">
                    {{ featuresSection.title }}
                    <span class="block w-14 h-[3px] bg-gradient-to-r from-cyan-500 to-customPrimaryColor rounded mx-auto mt-3"></span>
                </h2>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-7">
                    <div
                        v-for="(feature, fi) in featuresItems"
                        :key="fi"
                        class="feature-card bg-white border border-slate-200 rounded-[20px] p-9 max-md:p-7 text-center shadow-sm transition-all duration-400 relative overflow-hidden hover:-translate-y-2 hover:shadow-xl reveal"
                        :class="`reveal-delay-${fi % 6 + 1}`"
                    >
                        <div class="before:content-[''] before:absolute before:top-0 before:left-0 before:right-0 before:h-[3px] before:bg-gradient-to-r before:from-customPrimaryColor before:to-cyan-500 before:scale-x-0 before:origin-left before:transition-transform before:duration-400 hover:before:scale-x-100"></div>
                        <div class="w-[72px] h-[72px] bg-customLightPrimaryColor rounded-[18px] mx-auto mb-5 flex items-center justify-center text-3xl transition-all duration-400 feature-icon-wrap">
                            {{ feature.emoji || '✨' }}
                        </div>
                        <h3 class="text-[17px] font-bold text-customPrimaryColor mb-2">{{ feature.title }}</h3>
                        <p class="text-sm text-slate-500 leading-relaxed">{{ feature.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section v-if="statsItems.length > 0" class="px-6 pb-16 md:pb-20">
            <div class="stats-section max-w-[1200px] mx-auto bg-gradient-to-br from-customPrimaryColor to-[#1a5570] text-white py-[72px] px-10 max-md:py-12 max-md:px-6 rounded-[20px] text-center relative overflow-hidden">
                <div class="absolute -top-[40%] -right-[10%] w-[400px] h-[400px] rounded-full bg-[radial-gradient(circle,rgba(6,182,212,0.18)_0%,transparent_70%)]"></div>
                <div class="relative z-10">
                    <h2 v-if="statsSection?.title" class="text-3xl md:text-4xl font-extrabold mb-2">{{ statsSection.title }}</h2>
                    <p v-if="statsSubtitle" class="text-[17px] opacity-75 mb-10">{{ statsSubtitle }}</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                        <div v-for="(stat, si) in statsItems" :key="si" class="p-4">
                            <span class="stat-number block text-4xl md:text-5xl font-extrabold mb-1">{{ stat.number }}</span>
                            <span class="text-sm opacity-75 font-medium">{{ stat.label }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Split Layout Section -->
        <section v-if="splitSection" class="py-16 md:py-20 bg-white">
            <div class="container mx-auto px-6">
                <div class="grid md:grid-cols-2 gap-14 items-center reveal">
                    <div>
                        <h2 v-if="splitSection.title" class="text-[30px] max-md:text-2xl font-extrabold text-customPrimaryColor mb-5">{{ splitSection.title }}</h2>
                        <h3 v-if="splitSubtitle" class="text-[22px] max-md:text-lg font-bold text-slate-800 mb-4">{{ splitSubtitle }}</h3>
                        <div v-if="splitSection.content" class="text-[15px] text-slate-500 leading-[1.85] space-y-3" v-html="splitSection.content"></div>
                    </div>
                    <div v-if="splitImageUrl" class="rounded-[20px] overflow-hidden shadow-lg bg-slate-50 border border-slate-200">
                        <img :src="splitImageUrl" :alt="splitSection.title || ''" class="w-full h-auto object-cover" />
                    </div>
                    <div v-else class="rounded-[20px] bg-slate-50 border border-slate-200 p-10 flex items-center justify-center min-h-[240px]">
                        <div class="text-center text-slate-300">
                            <svg class="w-16 h-16 mx-auto mb-2" fill="none" stroke="currentColor" stroke-width="0.8" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                            <p class="text-sm">Image placeholder</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section v-if="ctaSection" class="cta-section relative overflow-hidden text-white py-20 md:py-[88px]">
            <div class="absolute inset-0 bg-gradient-to-br from-[#0a1d28] to-customPrimaryColor"></div>
            <div class="absolute -bottom-[30%] left-[20%] w-[400px] h-[400px] rounded-full bg-[radial-gradient(circle,rgba(6,182,212,0.12)_0%,transparent_70%)]"></div>

            <div class="container mx-auto px-6 relative z-10 text-center max-w-[600px] reveal">
                <h2 v-if="ctaSection.title" class="text-3xl md:text-[42px] font-extrabold mb-4 tracking-tight">{{ ctaSection.title }}</h2>
                <div v-if="ctaSection.content" class="text-[17px] opacity-80 mb-8 leading-relaxed" v-html="ctaSection.content"></div>
                <a v-if="ctaButtonUrl"
                   :href="ctaButtonUrl"
                   class="inline-block bg-cyan-500 text-white py-4 px-11 rounded-full font-bold text-base transition-all duration-300 hover:-translate-y-[3px] hover:shadow-[0_10px_32px_rgba(6,182,212,0.4)]">
                    {{ ctaButtonText }}
                </a>
            </div>
        </section>
    </div>

    <Footer />
</template>

<style scoped>
.hero-section {
    min-height: 50vh;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
}

/* Hero animations */
@keyframes heroFadeUp {
    from { opacity: 0; transform: translateY(24px); }
    to { opacity: 0.88; transform: translateY(0); }
}

.animate-hero-fade {
    opacity: 0;
    animation: heroFadeUp 0.9s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.animate-hero-fade-delay {
    animation: heroFadeUp 0.9s cubic-bezier(0.16, 1, 0.3, 1) 0.15s forwards;
}

/* Glow animation */
@keyframes pulseGlow {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.15); opacity: 0.7; }
}

.animate-glow {
    animation: pulseGlow 6s ease-in-out infinite;
}

.animate-glow-delayed {
    animation: pulseGlow 8s ease-in-out infinite 2s;
}

/* Scroll reveal */
.reveal {
    opacity: 0;
    transform: translateY(32px);
    transition: opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1),
                transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
}

.reveal.visible {
    opacity: 1;
    transform: translateY(0);
}

.reveal-left {
    opacity: 0;
    transform: translateX(-40px);
    transition: opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1),
                transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
}

.reveal-left.visible {
    opacity: 1;
    transform: translateX(0);
}

.reveal-right {
    opacity: 0;
    transform: translateX(40px);
    transition: opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1),
                transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
}

.reveal-right.visible {
    opacity: 1;
    transform: translateX(0);
}

.reveal-delay-1 { transition-delay: 0.1s; }
.reveal-delay-2 { transition-delay: 0.2s; }
.reveal-delay-3 { transition-delay: 0.3s; }

/* Mission card text */
.mission-text :deep(p) {
    margin-bottom: 16px;
    line-height: 1.9;
}

.mission-text :deep(p:last-child) {
    margin-bottom: 0;
}

/* Bio content styling */
.bio-content :deep(h2) {
    font-size: 30px;
    font-weight: 800;
    color: #153b4f;
    margin-bottom: 20px;
    line-height: 1.2;
}

.bio-content :deep(p) {
    margin-bottom: 16px;
    line-height: 1.85;
}

/* Features content: style v-html output to look like a card grid */
.features-content :deep(h2),
.features-content :deep(h3) {
    font-weight: 700;
    color: #153b4f;
    margin-bottom: 8px;
}

.features-content :deep(p) {
    color: #64748b;
    font-size: 14px;
    line-height: 1.65;
    margin-bottom: 12px;
}

/* Stats section inner v-html styling */
.stats-section :deep(h2) {
    font-size: clamp(28px, 4vw, 36px);
    font-weight: 800;
    margin-bottom: 8px;
}

.stats-section :deep(p) {
    font-size: 17px;
    opacity: 0.75;
    margin-bottom: 20px;
}

.stats-section :deep(.stat-number) {
    font-size: clamp(36px, 5vw, 48px);
    font-weight: 800;
    display: block;
    margin-bottom: 4px;
}

.stats-section :deep(.stat-label) {
    font-size: 14px;
    opacity: 0.75;
    font-weight: 500;
}

/* Split content styling */
.split-content :deep(h2) {
    font-size: 30px;
    font-weight: 800;
    color: #153b4f;
    margin-bottom: 20px;
}

.split-content :deep(h3) {
    font-size: 22px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 14px;
}

.split-content :deep(p) {
    font-size: 15px;
    color: #64748b;
    line-height: 1.85;
    margin-bottom: 12px;
}

.split-content :deep(img) {
    border-radius: 20px;
    max-width: 100%;
    height: auto;
}

/* CTA inner v-html styling */
.cta-section :deep(h2) {
    font-size: clamp(32px, 4.5vw, 42px);
    font-weight: 800;
    margin-bottom: 16px;
    letter-spacing: -0.5px;
}

.cta-section :deep(p) {
    font-size: 17px;
    opacity: 0.8;
    margin-bottom: 16px;
    line-height: 1.6;
}

/* Responsive */
@media (max-width: 768px) {
    .hero-section {
        min-height: auto;
    }

    .mission-card {
        padding: 32px 24px;
    }

    .stats-section :deep(h2) {
        font-size: 26px;
    }
}

@media (max-width: 480px) {
    .mission-card {
        padding: 24px 18px;
    }
}
</style>
