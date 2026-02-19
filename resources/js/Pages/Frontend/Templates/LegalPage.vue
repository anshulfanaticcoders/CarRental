<script setup>
import Footer from '@/Components/Footer.vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { computed, ref, onMounted, onUnmounted, nextTick } from 'vue';

const props = defineProps({
    page: Object,
    meta: Object,
    sections: Array,
    seo: Object,
    locale: String,
    pages: Object,
});

const getSection = (type) => props.sections?.find(s => s.type === type);
const contentSection = computed(() => getSection('content'));
const mainContent = computed(() => contentSection.value?.content || props.page?.content || '');

// --- Auto Table of Contents ---
const tableOfContents = computed(() => {
    if (!mainContent.value) return [];
    const items = [];
    const regex = /<h([23])[^>]*>([^<]*(?:<[^/][^>]*>[^<]*<\/[^>]*>)*[^<]*)<\/h[23]>/gi;
    let match, idx = 0;
    while ((match = regex.exec(mainContent.value)) !== null) {
        const text = match[2].replace(/<[^>]*>/g, '').trim();
        if (text) {
            items.push({
                id: 'section-' + text.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''),
                text,
                level: parseInt(match[1]),
                index: idx++,
            });
        }
    }
    return items;
});

// Inject IDs into headings
const processedContent = computed(() => {
    if (!mainContent.value || tableOfContents.value.length === 0) return mainContent.value;
    let html = mainContent.value;
    let tocIdx = 0;
    const regex = /<h([23])([^>]*)>([^<]*(?:<[^/][^>]*>[^<]*<\/[^>]*>)*[^<]*)<\/h([23])>/gi;
    html = html.replace(regex, (full, level, attrs, inner, cl) => {
        if (tocIdx < tableOfContents.value.length && !attrs.includes('id=')) {
            const item = tableOfContents.value[tocIdx++];
            return `<h${level}${attrs} id="${item.id}">${inner}</h${cl}>`;
        }
        tocIdx++;
        return full;
    });
    return html;
});

// --- Date formatting ---
const formatDate = (dateStr) => {
    if (!dateStr) return '';
    try {
        return new Date(dateStr).toLocaleDateString(props.locale || 'en', { year: 'numeric', month: 'long', day: 'numeric' });
    } catch { return dateStr; }
};
const lastUpdated = computed(() => props.meta?.last_updated ? formatDate(props.meta.last_updated) : null);
const effectiveDate = computed(() => props.meta?.effective_date ? formatDate(props.meta.effective_date) : null);

// --- Active ToC tracking ---
const activeId = ref('');
let scrollObserver = null;

onMounted(() => {
    nextTick(() => {
        const headings = tableOfContents.value.map(t => document.getElementById(t.id)).filter(Boolean);
        if (headings.length === 0) return;

        const updateActive = () => {
            const scrollY = window.scrollY + 120;
            let current = headings[0];
            for (const h of headings) {
                if (h.offsetTop <= scrollY) current = h;
            }
            activeId.value = current?.id || '';
        };

        window.addEventListener('scroll', updateActive, { passive: true });
        scrollObserver = () => window.removeEventListener('scroll', updateActive);
        updateActive();
    });
});

onUnmounted(() => { if (scrollObserver) scrollObserver(); });

// --- Mobile ToC toggle ---
const mobileTocOpen = ref(false);
</script>

<template>
    <SeoHead :seo="seo" />
    <AuthenticatedHeaderLayout />

    <div class="legal-page text-gray-800">
        <!-- Hero (compact) -->
        <section class="hero-section relative overflow-hidden text-white">
            <div class="absolute inset-0 bg-gradient-to-br from-[#0a1d28] via-customPrimaryColor to-[#1a4d66]"></div>
            <div class="hero-grain absolute inset-0 opacity-[0.03] pointer-events-none bg-repeat"></div>
            <div class="absolute top-[-40%] right-[-15%] w-[500px] h-[500px] rounded-full bg-[radial-gradient(circle,rgba(6,182,212,0.1)_0%,transparent_70%)]"></div>

            <div class="container mx-auto px-6 py-[60px] max-md:py-12 relative z-10 text-center max-w-[640px]">
                <h1 class="text-3xl md:text-4xl lg:text-[42px] font-extrabold mb-3 leading-[1.15] tracking-[-1px] animate-hero-fade">
                    {{ page.title }}
                </h1>
                <p v-if="lastUpdated" class="text-sm md:text-base opacity-70 font-light animate-hero-fade-delay">
                    Last updated: {{ lastUpdated }}
                </p>
            </div>

            <div class="absolute bottom-[-1px] left-0 w-full leading-[0]">
                <svg viewBox="0 0 1440 36" fill="none" preserveAspectRatio="none" class="w-full h-9">
                    <path d="M0 36h1440V14c-120 12-360 22-720 22S120 26 0 14v22z" fill="white"/>
                </svg>
            </div>
        </section>

        <!-- Date Bar -->
        <div v-if="effectiveDate || lastUpdated" class="bg-slate-50 border-b border-slate-200">
            <div class="max-w-[1200px] mx-auto px-6 py-3 flex flex-wrap items-center justify-center gap-6 max-sm:flex-col max-sm:gap-2 text-sm text-slate-500">
                <div v-if="effectiveDate" class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <span>Effective: <strong class="text-slate-700">{{ effectiveDate }}</strong></span>
                </div>
                <div v-if="lastUpdated" class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span>Last updated: <strong class="text-slate-700">{{ lastUpdated }}</strong></span>
                </div>
            </div>
        </div>

        <!-- Two-Column Layout: Content + Sticky ToC -->
        <section class="bg-white">
            <div class="legal-layout">
                <!-- Main Content (left) -->
                <div>
                    <!-- Mobile ToC toggle -->
                    <button
                        v-if="tableOfContents.length > 2"
                        class="toc-mobile-toggle"
                        :class="{ open: mobileTocOpen }"
                        @click="mobileTocOpen = !mobileTocOpen"
                    >
                        <span>
                            Table of Contents
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 9l-7 7-7-7"/></svg>
                        </span>
                    </button>
                    <div v-if="mobileTocOpen && tableOfContents.length > 2" class="toc-mobile-content">
                        <div class="toc-card">
                            <ol class="toc-list">
                                <li v-for="item in tableOfContents" :key="item.id">
                                    <a :href="`#${item.id}`" @click="mobileTocOpen = false" :class="{ 'pl-5': item.level === 3 }">
                                        {{ item.text }}
                                    </a>
                                </li>
                            </ol>
                        </div>
                    </div>

                    <!-- Legal Content -->
                    <div class="legal-content prose-legal" v-html="processedContent"></div>
                </div>

                <!-- Sticky ToC Sidebar (right, desktop only) -->
                <aside v-if="tableOfContents.length > 2" class="toc-sidebar">
                    <div class="toc-card">
                        <h3>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
                            On This Page
                        </h3>
                        <ol class="toc-list">
                            <li v-for="item in tableOfContents" :key="item.id">
                                <a
                                    :href="`#${item.id}`"
                                    :class="[
                                        item.level === 3 ? 'toc-sub' : '',
                                        activeId === item.id ? 'active' : ''
                                    ]"
                                >
                                    {{ item.text }}
                                </a>
                            </li>
                        </ol>
                    </div>
                </aside>
            </div>
        </section>
    </div>

    <Footer />
</template>

<style scoped>
/* ── Hero ── */
.hero-grain {
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
}

@keyframes heroFadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-hero-fade { opacity: 0; animation: heroFadeUp 0.8s cubic-bezier(0.16,1,0.3,1) forwards; }
.animate-hero-fade-delay { opacity: 0; animation: heroFadeUp 0.8s cubic-bezier(0.16,1,0.3,1) 0.12s forwards; }

/* ── Two-Column Layout ── */
.legal-layout {
    display: grid;
    grid-template-columns: 1fr 280px;
    gap: 48px;
    max-width: 1200px;
    margin: 0 auto;
    padding: 48px 24px 80px;
    align-items: start;
}

/* ── Sticky ToC Sidebar ── */
.toc-sidebar {
    position: sticky;
    top: 80px;
    max-height: calc(100vh - 104px);
    overflow-y: auto;
}
.toc-sidebar::-webkit-scrollbar { width: 4px; }
.toc-sidebar::-webkit-scrollbar-track { background: transparent; }
.toc-sidebar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }

.toc-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 20px 22px;
}
.toc-card h3 {
    font-size: 12px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 1.2px; color: #94a3b8; margin-bottom: 14px;
    display: flex; align-items: center; gap: 6px;
    font-family: 'Plus Jakarta Sans', sans-serif;
}
.toc-card h3 svg { width: 14px; height: 14px; stroke: #06b6d4; }

.toc-list { list-style: none; counter-reset: toc; }
.toc-list li { counter-increment: toc; margin-bottom: 2px; }
.toc-list li a {
    text-decoration: none; color: #64748b; font-size: 13px; font-weight: 500;
    transition: all 0.2s; display: block; padding: 5px 10px;
    border-radius: 6px; border-left: 2px solid transparent;
}
.toc-list li a::before {
    content: counter(toc, decimal-leading-zero) '  ';
    font-size: 11px; font-weight: 700; color: #06b6d4;
    font-family: 'Plus Jakarta Sans', sans-serif;
}
.toc-list li a:hover { color: #153b4f; background: rgba(21,59,79,0.06); }
.toc-list li a.active {
    color: #153b4f; background: rgba(21,59,79,0.06);
    border-left-color: #06b6d4; font-weight: 600;
}
.toc-list li a.toc-sub { padding-left: 28px; font-size: 12px; }
.toc-list li a.toc-sub::before { content: '— '; color: #cbd5e1; }

/* ── Mobile ToC ── */
.toc-mobile-toggle {
    display: none; width: 100%; padding: 14px 18px;
    background: #f8fafc; border: 1px solid #e2e8f0;
    border-radius: 14px; cursor: pointer; margin-bottom: 24px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 14px; font-weight: 600; color: #153b4f; text-align: left;
}
.toc-mobile-toggle span {
    display: flex; align-items: center; justify-content: space-between;
}
.toc-mobile-toggle svg {
    width: 18px; height: 18px; stroke: #64748b;
    transition: transform 0.3s;
}
.toc-mobile-toggle.open svg { transform: rotate(180deg); }
.toc-mobile-content { margin-bottom: 24px; }
.toc-mobile-content .toc-list li a { font-size: 14px; padding: 8px 12px; }

/* ── Legal Prose (same as before) ── */
.prose-legal { font-size: 15.5px; line-height: 1.85; color: #334155; }
.prose-legal :deep(h2) { font-size: 1.5rem; font-weight: 700; color: #153b4f; margin: 2.5rem 0 0.875rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0; line-height: 1.3; scroll-margin-top: 100px; }
.prose-legal :deep(h2:first-child) { margin-top: 0; }
.prose-legal :deep(h3) { font-size: 1.25rem; font-weight: 600; color: #1e293b; margin: 2rem 0 0.75rem; line-height: 1.35; scroll-margin-top: 100px; }
.prose-legal :deep(h4) { font-size: 1.1rem; font-weight: 600; color: #334155; margin: 1.5rem 0 0.5rem; }
.prose-legal :deep(p) { margin-bottom: 1.125rem; line-height: 1.85; }
.prose-legal :deep(p:last-child) { margin-bottom: 0; }
.prose-legal :deep(a) { color: #06b6d4; text-decoration: none; font-weight: 500; border-bottom: 1px solid rgba(6,182,212,0.3); transition: color 0.2s, border-color 0.2s; }
.prose-legal :deep(a:hover) { color: #153b4f; border-bottom-color: #153b4f; }
.prose-legal :deep(ul) { list-style: disc; padding-left: 1.75rem; margin-bottom: 1.125rem; }
.prose-legal :deep(ol) { list-style: decimal; padding-left: 1.75rem; margin-bottom: 1.125rem; }
.prose-legal :deep(li) { margin-bottom: 0.5rem; line-height: 1.7; padding-left: 0.25rem; }
.prose-legal :deep(li::marker) { color: #94a3b8; }
.prose-legal :deep(strong) { font-weight: 600; color: #1e293b; }
.prose-legal :deep(blockquote) { border-left: 3px solid #06b6d4; padding: 1rem 1.25rem; margin: 1.5rem 0; color: #475569; font-style: italic; background: #f8fafc; border-radius: 0 8px 8px 0; }
.prose-legal :deep(table) { width: 100%; border-collapse: collapse; margin: 1.5rem 0; font-size: 14px; }
.prose-legal :deep(th) { background: #153b4f; color: white; padding: 10px 14px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
.prose-legal :deep(td) { padding: 10px 14px; border-bottom: 1px solid #e2e8f0; }
.prose-legal :deep(tr:nth-child(even) td) { background: #f8fafc; }
.prose-legal :deep(code) { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.875em; color: #153b4f; }
.prose-legal :deep(hr) { border: none; height: 1px; background: #e2e8f0; margin: 2.5rem 0; }

/* ── Responsive ── */
@media (max-width: 1024px) {
    .legal-layout { grid-template-columns: 1fr 240px; gap: 32px; }
    .toc-list li a { font-size: 12px; }
}

@media (max-width: 768px) {
    .legal-layout { grid-template-columns: 1fr; gap: 0; padding: 32px 20px 56px; }
    .toc-sidebar { display: none; }
    .toc-mobile-toggle { display: block; }
    .prose-legal { font-size: 14.5px; }
    .prose-legal :deep(h2) { font-size: 1.3rem; margin-top: 2rem; }
    .prose-legal :deep(h3) { font-size: 1.1rem; }
    .prose-legal :deep(table) { display: block; overflow-x: auto; }
}

@media (max-width: 480px) {
    .legal-layout { padding: 24px 16px 48px; }
    .prose-legal { font-size: 14px; }
    .prose-legal :deep(h2) { font-size: 1.2rem; }
    .prose-legal :deep(blockquote) { padding: 0.75rem 1rem; }
}
</style>
