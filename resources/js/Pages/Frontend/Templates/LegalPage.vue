<script setup>
import Footer from '@/Components/Footer.vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { computed } from 'vue';

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

const contentSection = computed(() => getSection('content'));

// Get the main content HTML (from content section or fallback to page.content)
const mainContent = computed(() => {
    return contentSection.value?.content || props.page?.content || '';
});

// --- Auto Table of Contents ---
const tableOfContents = computed(() => {
    if (!mainContent.value) return [];

    const tocItems = [];
    // Parse h2 and h3 headings from the HTML content
    const headingRegex = /<h([23])[^>]*>([^<]*(?:<[^/][^>]*>[^<]*<\/[^>]*>)*[^<]*)<\/h[23]>/gi;
    let match;
    let index = 0;

    while ((match = headingRegex.exec(mainContent.value)) !== null) {
        const level = parseInt(match[1]);
        // Strip inner HTML tags to get plain text
        const text = match[2].replace(/<[^>]*>/g, '').trim();
        if (text) {
            const id = 'section-' + text.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
            tocItems.push({
                id,
                text,
                level,
                index: index++,
            });
        }
    }

    return tocItems;
});

// Inject anchor IDs into the content HTML for the table of contents links
const processedContent = computed(() => {
    if (!mainContent.value || tableOfContents.value.length === 0) return mainContent.value;

    let html = mainContent.value;
    let tocIndex = 0;
    const headingRegex = /<h([23])([^>]*)>([^<]*(?:<[^/][^>]*>[^<]*<\/[^>]*>)*[^<]*)<\/h([23])>/gi;

    html = html.replace(headingRegex, (fullMatch, level, attrs, innerHtml, closingLevel) => {
        if (tocIndex < tableOfContents.value.length) {
            const tocItem = tableOfContents.value[tocIndex];
            tocIndex++;
            // Add id attribute to the heading
            if (attrs.includes('id=')) {
                return fullMatch;
            }
            return `<h${level}${attrs} id="${tocItem.id}">${innerHtml}</h${closingLevel}>`;
        }
        return fullMatch;
    });

    return html;
});

// --- Date formatting ---
const formatDate = (dateStr) => {
    if (!dateStr) return '';
    try {
        const date = new Date(dateStr);
        return date.toLocaleDateString(props.locale || 'en', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    } catch {
        return dateStr;
    }
};

const lastUpdated = computed(() => props.meta?.last_updated ? formatDate(props.meta.last_updated) : null);
const effectiveDate = computed(() => props.meta?.effective_date ? formatDate(props.meta.effective_date) : null);
</script>

<template>
    <SeoHead :seo="seo" />
    <AuthenticatedHeaderLayout />

    <div class="legal-page text-gray-800">
        <!-- Hero Section (compact) -->
        <section class="hero-section relative overflow-hidden text-white">
            <div class="absolute inset-0 bg-gradient-to-br from-[#0a1d28] via-customPrimaryColor to-[#1a4d66]"></div>

            <!-- Subtle grain -->
            <div class="absolute inset-0 opacity-[0.03] pointer-events-none bg-repeat"
                 style="background-image: url(&quot;data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E&quot;)">
            </div>

            <div class="container mx-auto px-6 py-[60px] max-md:py-12 relative z-10 text-center max-w-[720px]">
                <h1 class="text-3xl md:text-4xl lg:text-[44px] font-extrabold mb-4 leading-[1.15] tracking-[-1px] animate-hero-fade">
                    {{ page.title }}
                </h1>
                <p v-if="lastUpdated"
                   class="text-sm md:text-base opacity-70 font-light animate-hero-fade-delay">
                    Last updated: {{ lastUpdated }}
                </p>
            </div>
        </section>

        <!-- Date Info Bar -->
        <div v-if="effectiveDate || lastUpdated"
             class="bg-slate-50 border-b border-slate-200">
            <div class="max-w-[800px] mx-auto px-6 py-3 flex flex-wrap items-center gap-4 text-sm text-slate-500">
                <div v-if="effectiveDate" class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <span>Effective: <strong class="text-slate-700">{{ effectiveDate }}</strong></span>
                </div>
                <div v-if="lastUpdated" class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <span>Last updated: <strong class="text-slate-700">{{ lastUpdated }}</strong></span>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <section class="py-12 md:py-16 bg-white">
            <div class="max-w-[800px] mx-auto px-6">

                <!-- Table of Contents -->
                <nav v-if="tableOfContents.length > 2"
                     class="toc-nav mb-10 p-6 bg-slate-50 border border-slate-200 rounded-2xl">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4">Table of Contents</h2>
                    <ol class="space-y-1.5">
                        <li v-for="item in tableOfContents" :key="item.id"
                            :class="{ 'pl-5': item.level === 3 }">
                            <a :href="`#${item.id}`"
                               class="toc-link text-sm text-slate-600 hover:text-customPrimaryColor transition-colors duration-200 flex items-start gap-2 py-0.5">
                                <span class="text-slate-300 font-mono text-xs mt-px leading-5">{{ item.index + 1 }}.</span>
                                <span>{{ item.text }}</span>
                            </a>
                        </li>
                    </ol>
                </nav>

                <!-- Legal Content -->
                <div class="legal-content prose-legal"
                     v-html="processedContent">
                </div>
            </div>
        </section>
    </div>

    <Footer />
</template>

<style scoped>
/* Hero (compact) */
.hero-section {
    position: relative;
}

@keyframes heroFadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-hero-fade {
    opacity: 0;
    animation: heroFadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.animate-hero-fade-delay {
    opacity: 0;
    animation: heroFadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.12s forwards;
}

/* Table of contents */
.toc-nav {
    position: relative;
}

.toc-nav::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #153b4f, #06b6d4);
    border-radius: 2px 2px 0 0;
}

.toc-link:hover {
    padding-left: 4px;
}

/* Legal content typography - clean, readable, like Stripe/Notion */
.prose-legal {
    font-size: 15.5px;
    line-height: 1.85;
    color: #334155;
}

.prose-legal :deep(h1) {
    font-size: 2rem;
    font-weight: 800;
    color: #153b4f;
    margin-top: 2.5rem;
    margin-bottom: 1rem;
    line-height: 1.25;
    letter-spacing: -0.5px;
}

.prose-legal :deep(h2) {
    font-size: 1.5rem;
    font-weight: 700;
    color: #153b4f;
    margin-top: 2.5rem;
    margin-bottom: 0.875rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e2e8f0;
    line-height: 1.3;
    scroll-margin-top: 100px;
}

.prose-legal :deep(h3) {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
    margin-top: 2rem;
    margin-bottom: 0.75rem;
    line-height: 1.35;
    scroll-margin-top: 100px;
}

.prose-legal :deep(h4) {
    font-size: 1.1rem;
    font-weight: 600;
    color: #334155;
    margin-top: 1.5rem;
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.prose-legal :deep(p) {
    margin-bottom: 1.125rem;
    line-height: 1.85;
}

.prose-legal :deep(p:last-child) {
    margin-bottom: 0;
}

.prose-legal :deep(a) {
    color: #06b6d4;
    text-decoration: none;
    font-weight: 500;
    border-bottom: 1px solid rgba(6, 182, 212, 0.3);
    transition: color 0.2s, border-color 0.2s;
}

.prose-legal :deep(a:hover) {
    color: #153b4f;
    border-bottom-color: #153b4f;
}

.prose-legal :deep(ul) {
    list-style: disc;
    padding-left: 1.75rem;
    margin-bottom: 1.125rem;
}

.prose-legal :deep(ol) {
    list-style: decimal;
    padding-left: 1.75rem;
    margin-bottom: 1.125rem;
}

.prose-legal :deep(li) {
    margin-bottom: 0.5rem;
    line-height: 1.7;
    padding-left: 0.25rem;
}

.prose-legal :deep(li::marker) {
    color: #94a3b8;
}

.prose-legal :deep(ul ul),
.prose-legal :deep(ol ol),
.prose-legal :deep(ul ol),
.prose-legal :deep(ol ul) {
    margin-top: 0.5rem;
    margin-bottom: 0.5rem;
}

.prose-legal :deep(strong) {
    font-weight: 600;
    color: #1e293b;
}

.prose-legal :deep(em) {
    font-style: italic;
}

.prose-legal :deep(blockquote) {
    border-left: 3px solid #06b6d4;
    padding-left: 1.25rem;
    margin: 1.5rem 0;
    color: #475569;
    font-style: italic;
    background: #f8fafc;
    padding: 1rem 1.25rem;
    border-radius: 0 8px 8px 0;
}

.prose-legal :deep(table) {
    width: 100%;
    border-collapse: collapse;
    margin: 1.5rem 0;
    font-size: 14px;
}

.prose-legal :deep(th) {
    background: #f1f5f9;
    font-weight: 600;
    color: #1e293b;
    text-align: left;
    padding: 10px 14px;
    border: 1px solid #e2e8f0;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.prose-legal :deep(td) {
    padding: 10px 14px;
    border: 1px solid #e2e8f0;
    vertical-align: top;
}

.prose-legal :deep(tr:nth-child(even)) {
    background: #f8fafc;
}

.prose-legal :deep(code) {
    background: #f1f5f9;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.875em;
    color: #153b4f;
    font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Fira Mono', monospace;
}

.prose-legal :deep(pre) {
    background: #1e293b;
    color: #e2e8f0;
    padding: 1.25rem;
    border-radius: 12px;
    overflow-x: auto;
    margin: 1.5rem 0;
    font-size: 14px;
    line-height: 1.6;
}

.prose-legal :deep(pre code) {
    background: none;
    padding: 0;
    color: inherit;
}

.prose-legal :deep(hr) {
    border: none;
    height: 1px;
    background: #e2e8f0;
    margin: 2.5rem 0;
}

.prose-legal :deep(img) {
    max-width: 100%;
    height: auto;
    border-radius: 12px;
    margin: 1.5rem 0;
}

/* Responsive */
@media (max-width: 768px) {
    .prose-legal {
        font-size: 14.5px;
    }

    .prose-legal :deep(h1) {
        font-size: 1.625rem;
    }

    .prose-legal :deep(h2) {
        font-size: 1.3rem;
        margin-top: 2rem;
    }

    .prose-legal :deep(h3) {
        font-size: 1.1rem;
    }

    .prose-legal :deep(table) {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }

    .toc-nav {
        padding: 16px 18px;
    }
}

@media (max-width: 480px) {
    .prose-legal {
        font-size: 14px;
    }

    .prose-legal :deep(h1) {
        font-size: 1.5rem;
    }

    .prose-legal :deep(h2) {
        font-size: 1.2rem;
    }

    .prose-legal :deep(blockquote) {
        padding: 0.75rem 1rem;
    }
}
</style>
