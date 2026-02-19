<template>
    <SeoHead :seo="seo" />
    <AuthenticatedHeaderLayout />
    <SchemaInjector v-if="props.schema" :schema="props.schema" />

    <!-- Reading progress bar (top of page) -->
    <div class="fixed top-0 left-0 w-full h-[3px] z-50">
        <div
            class="h-full bg-cyan-500 transition-all duration-150 ease-out"
            :style="{ width: readingProgress + '%' }"
        ></div>
    </div>

    <article class="single-blog-page min-h-screen bg-white">
        <!-- Breadcrumb -->
        <nav class="full-w-container mx-auto pt-6 pb-2 mb-4" aria-label="Breadcrumb">
            <ol class="flex items-center flex-wrap gap-1 text-sm text-gray-500">
                <li>
                    <Link
                        :href="route('welcome', { locale: currentLocale })"
                        class="hover:text-[#153b4f] transition-colors"
                    >Home</Link>
                </li>
                <li class="mx-1.5 text-gray-400 select-none">/</li>
                <li>
                    <Link
                        :href="route('blog', { locale: currentLocale, country: currentCountry })"
                        class="hover:text-[#153b4f] transition-colors"
                    >Blog</Link>
                </li>
                <li class="mx-1.5 text-gray-400 select-none">/</li>
                <li class="text-gray-800 font-medium truncate max-w-[240px] md:max-w-none">
                    {{ blog.title }}
                </li>
            </ol>
        </nav>

        <!-- Two-column layout -->
        <div class="full-w-container mx-auto pb-16">
            <div class="flex gap-10 lg:gap-12 justify-between">

                <!-- Main Article Column -->
                <div class="w-full md:w-[85%] lg:w-[70%]">

                    <!-- Article Header -->
                    <header class="mb-8">
                        <!-- Category/Tag badge -->
                        <div class="mb-4">
                            <span
                                v-if="tags && tags.length > 0"
                                class="inline-block text-xs font-semibold uppercase tracking-wider text-cyan-700 bg-cyan-50 border border-cyan-200 px-3 py-1 rounded-full"
                            >
                                {{ tags[0].name }}
                            </span>
                            <span
                                v-else
                                class="inline-block text-xs font-semibold uppercase tracking-wider text-cyan-700 bg-cyan-50 border border-cyan-200 px-3 py-1 rounded-full"
                            >
                                Blog
                            </span>
                        </div>

                        <!-- Title -->
                        <h1 class="blog-title text-[clamp(1.75rem,4vw,2.5rem)] font-extrabold leading-tight text-gray-900 mb-4">
                            {{ blog.title }}
                        </h1>

                        <!-- Excerpt -->
                        <p
                            v-if="blog.excerpt"
                            class="text-lg leading-relaxed text-gray-600 italic border-l-4 border-cyan-500 pl-5 my-5"
                            style="font-family: 'IBM Plex Serif', Georgia, serif;"
                        >
                            {{ blog.excerpt }}
                        </p>

                        <!-- Meta bar -->
                        <div class="flex items-center flex-wrap gap-3 text-sm text-gray-500 mt-5">
                            <!-- Author avatar -->
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-[#153b4f] flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                    VT
                                </div>
                                <span class="font-medium text-gray-700">Vrooem Team</span>
                            </div>
                            <span class="text-gray-300">|</span>
                            <!-- Date -->
                            <span>{{ formatDate(blog.created_at) }}</span>
                            <span class="text-gray-300">|</span>
                            <!-- Reading time -->
                            <span>{{ readingTime }} min read</span>
                        </div>
                    </header>

                    <!-- Mobile Table of Contents (collapsible) -->
                    <div v-if="tocItems.length > 0" class="block lg:hidden mb-6">
                        <button
                            @click="mobileTocsOpen = !mobileTocsOpen"
                            class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors"
                        >
                            <span>Table of Contents</span>
                            <svg
                                class="w-4 h-4 transition-transform duration-200"
                                :class="{ 'rotate-180': mobileTocsOpen }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <nav
                            v-show="mobileTocsOpen"
                            class="mt-2 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg"
                        >
                            <ul class="space-y-2">
                                <li
                                    v-for="item in tocItems"
                                    :key="item.id"
                                    :class="item.level === 3 ? 'pl-4' : ''"
                                >
                                    <a
                                        :href="'#' + item.id"
                                        class="text-sm hover:text-cyan-600 transition-colors"
                                        :class="activeHeadingId === item.id ? 'text-cyan-600 font-semibold' : 'text-gray-600'"
                                        @click="mobileTocsOpen = false"
                                    >
                                        {{ item.text }}
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>

                    <!-- Featured Image -->
                    <div v-if="blog.image" class="mb-10">
                        <div class="relative overflow-hidden rounded-[20px] shadow-lg group aspect-video">
                            <img
                                :src="blog.image"
                                :alt="blog.title"
                                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                loading="eager"
                            />
                        </div>
                    </div>

                    <!-- Article Body (v-html) -->
                    <div
                        ref="articleBodyRef"
                        class="article-body prose prose-lg max-w-none"
                        v-html="processedContent"
                    ></div>

                    <!-- Tags Section -->
                    <div v-if="tags && tags.length > 0" class="mt-12 pt-8 border-t border-gray-200">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-3">Tags</h3>
                        <div class="flex flex-wrap gap-2">
                            <a
                                v-for="tag in tags"
                                :key="tag.slug"
                                :href="`/${currentLocale}/${currentCountry}/blog?tag=${tag.slug}`"
                                class="inline-block text-sm font-medium text-[#153b4f] bg-gray-100 hover:bg-cyan-50 hover:text-cyan-700 border border-gray-200 hover:border-cyan-200 px-4 py-1.5 rounded-full transition-colors"
                            >
                                {{ tag.name }}
                            </a>
                        </div>
                    </div>

                    <!-- Share Bar -->
                    <div class="mt-10 pt-8 border-t border-gray-200">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-4">Share this article</h3>
                        <div class="flex flex-wrap gap-3">
                            <!-- Facebook -->
                            <a
                                :href="`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(currentUrl)}`"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#1877F2] hover:bg-[#166FE5] text-white text-sm font-medium rounded-lg transition-colors"
                                aria-label="Share on Facebook"
                            >
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                Facebook
                            </a>
                            <!-- Twitter/X -->
                            <a
                                :href="`https://twitter.com/intent/tweet?url=${encodeURIComponent(currentUrl)}&text=${encodeURIComponent(blog.title)}`"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-2 px-4 py-2.5 bg-black hover:bg-gray-800 text-white text-sm font-medium rounded-lg transition-colors"
                                aria-label="Share on X"
                            >
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                X / Twitter
                            </a>
                            <!-- LinkedIn -->
                            <a
                                :href="`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(currentUrl)}`"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#0A66C2] hover:bg-[#004182] text-white text-sm font-medium rounded-lg transition-colors"
                                aria-label="Share on LinkedIn"
                            >
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                LinkedIn
                            </a>
                            <!-- Copy Link -->
                            <button
                                @click="copyLink"
                                class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors border border-gray-200"
                                aria-label="Copy link"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                {{ linkCopied ? 'Copied!' : 'Copy Link' }}
                            </button>
                        </div>
                    </div>

                    <!-- Author Card -->
                    <div class="mt-10 p-6 bg-gray-50 rounded-2xl border border-gray-200">
                        <div class="flex items-start gap-4">
                            <div class="w-14 h-14 rounded-full bg-[#153b4f] flex items-center justify-center text-white text-lg font-bold flex-shrink-0">
                                VT
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Written by</p>
                                <h4 class="text-lg font-bold text-gray-900">Vrooem Team</h4>
                                <p class="text-sm text-gray-600 mt-1 leading-relaxed">
                                    The Vrooem editorial team shares insights, tips, and guides to help you make the most of your car rental experience across Europe and beyond.
                                </p>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Sticky Sidebar (desktop only) -->
                <aside class="hidden lg:block">
                    <div class="sticky top-8 space-y-8">

                        <!-- Reading Progress -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Reading Progress</span>
                                <span class="text-xs font-bold text-cyan-600">{{ Math.round(readingProgress) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-1.5">
                                <div
                                    class="bg-cyan-500 h-1.5 rounded-full transition-all duration-150 ease-out"
                                    :style="{ width: readingProgress + '%' }"
                                ></div>
                            </div>
                        </div>

                        <!-- Table of Contents -->
                        <div v-if="tocItems.length > 0">
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">In This Article</h3>
                            <nav>
                                <ul class="space-y-1.5">
                                    <li
                                        v-for="item in tocItems"
                                        :key="item.id"
                                        :class="item.level === 3 ? 'pl-3' : ''"
                                    >
                                        <a
                                            :href="'#' + item.id"
                                            class="block text-[13px] leading-snug py-1 border-l-2 pl-3 transition-all duration-200"
                                            :class="
                                                activeHeadingId === item.id
                                                    ? 'border-cyan-500 text-cyan-700 font-semibold'
                                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                            "
                                        >
                                            {{ item.text }}
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>

                        <!-- Share (compact) -->
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Share</h3>
                            <div class="flex gap-2">
                                <a
                                    :href="`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(currentUrl)}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="w-9 h-9 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-[#1877F2] hover:text-white text-gray-500 transition-colors"
                                    aria-label="Share on Facebook"
                                >
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                </a>
                                <a
                                    :href="`https://twitter.com/intent/tweet?url=${encodeURIComponent(currentUrl)}&text=${encodeURIComponent(blog.title)}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="w-9 h-9 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-black hover:text-white text-gray-500 transition-colors"
                                    aria-label="Share on X"
                                >
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                </a>
                                <a
                                    :href="`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(currentUrl)}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="w-9 h-9 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-[#0A66C2] hover:text-white text-gray-500 transition-colors"
                                    aria-label="Share on LinkedIn"
                                >
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                </a>
                                <button
                                    @click="copyLink"
                                    class="w-9 h-9 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-500 transition-colors"
                                    :class="{ 'bg-green-100 text-green-600': linkCopied }"
                                    aria-label="Copy link"
                                >
                                    <svg v-if="!linkCopied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Related Articles -->
                        <div v-if="relatedBlogs && relatedBlogs.length > 0">
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Related Articles</h3>
                            <div class="space-y-4">
                                <a
                                    v-for="related in relatedBlogs.slice(0, 3)"
                                    :key="related.slug"
                                    :href="`/${currentLocale}/${currentCountry}/blog/${related.slug}`"
                                    class="flex gap-3 group"
                                >
                                    <div class="w-16 h-12 rounded-lg overflow-hidden flex-shrink-0 bg-gray-100">
                                        <img
                                            v-if="related.image"
                                            :src="related.image"
                                            :alt="related.title"
                                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                            loading="lazy"
                                        />
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="text-sm font-medium text-gray-800 group-hover:text-cyan-700 transition-colors leading-tight line-clamp-2">
                                            {{ related.title }}
                                        </h4>
                                        <p class="text-xs text-gray-400 mt-1">{{ formatDate(related.date) }}</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </article>

    <Footer />
</template>

<script setup>
import Footer from '@/Components/Footer.vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import SchemaInjector from '@/Components/SchemaInjector.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { Link } from '@inertiajs/vue3';
import { defineProps, computed, ref, onMounted, onUnmounted, nextTick } from 'vue';

const props = defineProps({
    blog: Object,
    schema: Object,
    seo: Object,
    locale: String,
    country: String,
    tags: Array,
    relatedBlogs: Array,
    readingTime: Number,
});

const currentLocale = computed(() => props.locale || 'en');
const currentCountry = computed(() => props.country || 'us');
const currentUrl = computed(() => typeof window !== 'undefined' ? window.location.href : '');

// --- ToC generation ---
const tocItems = ref([]);
const activeHeadingId = ref('');
const mobileTocsOpen = ref(false);
const articleBodyRef = ref(null);

const processedContent = computed(() => {
    if (!props.blog?.content) return '';
    let content = props.blog.content;
    let headingIndex = 0;
    // Inject IDs into h2 and h3 tags for anchor linking
    content = content.replace(/<(h[23])([^>]*)>(.*?)<\/\1>/gi, (match, tag, attrs, text) => {
        const plainText = text.replace(/<[^>]*>/g, '').trim();
        const id = 'heading-' + headingIndex++;
        // Preserve existing attributes, add id
        if (attrs.includes('id=')) {
            return match;
        }
        return `<${tag}${attrs} id="${id}">${text}</${tag}>`;
    });
    return content;
});

function buildToc() {
    if (!articleBodyRef.value) return;
    const headings = articleBodyRef.value.querySelectorAll('h2, h3');
    const items = [];
    headings.forEach((el) => {
        if (!el.id) {
            el.id = 'heading-auto-' + items.length;
        }
        items.push({
            id: el.id,
            text: el.textContent.trim(),
            level: el.tagName === 'H3' ? 3 : 2,
        });
    });
    tocItems.value = items;
}

// --- Reading progress ---
const readingProgress = ref(0);

function updateReadingProgress() {
    if (!articleBodyRef.value) return;
    const rect = articleBodyRef.value.getBoundingClientRect();
    const windowHeight = window.innerHeight;
    const articleTop = rect.top + window.scrollY;
    const articleHeight = rect.height;
    if (articleHeight <= 0) {
        readingProgress.value = 0;
        return;
    }
    const scrolled = window.scrollY - articleTop + windowHeight * 0.3;
    const progress = Math.max(0, Math.min(100, (scrolled / articleHeight) * 100));
    readingProgress.value = progress;
}

// --- Scroll spy for active heading ---
function updateActiveHeading() {
    if (tocItems.value.length === 0) return;
    const scrollY = window.scrollY;
    let current = '';
    for (const item of tocItems.value) {
        const el = document.getElementById(item.id);
        if (el) {
            const rect = el.getBoundingClientRect();
            if (rect.top <= 120) {
                current = item.id;
            }
        }
    }
    activeHeadingId.value = current;
}

function onScroll() {
    updateReadingProgress();
    updateActiveHeading();
}

// --- Copy link ---
const linkCopied = ref(false);
let copyTimeout = null;

function copyLink() {
    if (typeof navigator !== 'undefined' && navigator.clipboard) {
        navigator.clipboard.writeText(window.location.href).then(() => {
            linkCopied.value = true;
            if (copyTimeout) clearTimeout(copyTimeout);
            copyTimeout = setTimeout(() => {
                linkCopied.value = false;
            }, 2000);
        });
    }
}

// --- Date formatting ---
function formatDate(date) {
    if (!date) return '';
    try {
        return new Date(date).toLocaleDateString(props.locale || 'en', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    } catch {
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    }
}

// --- Lifecycle ---
onMounted(() => {
    nextTick(() => {
        buildToc();
        updateReadingProgress();
        updateActiveHeading();
    });
    window.addEventListener('scroll', onScroll, { passive: true });
});

onUnmounted(() => {
    window.removeEventListener('scroll', onScroll);
    if (copyTimeout) clearTimeout(copyTimeout);
});
</script>

<style scoped>
/* Blog title font */
.blog-title {
    font-family: 'Plus Jakarta Sans', 'Inter', system-ui, sans-serif;
    font-weight: 800;
}

/* line-clamp utility */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* ============================
   Article body :deep() styles
   ============================ */

/* Base text */
.article-body {
    font-size: 17px;
    line-height: 1.9;
    color: #374151;
}

/* Headings */
.article-body :deep(h2) {
    font-size: 1.65rem;
    font-weight: 700;
    color: #111827;
    margin-top: 2.5rem;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #f3f4f6;
    scroll-margin-top: 80px;
}

.article-body :deep(h3) {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1f2937;
    margin-top: 2rem;
    margin-bottom: 0.75rem;
    scroll-margin-top: 80px;
}

.article-body :deep(h4) {
    font-size: 1.1rem;
    font-weight: 600;
    color: #374151;
    margin-top: 1.5rem;
    margin-bottom: 0.5rem;
}

/* Paragraphs */
.article-body :deep(p) {
    margin-bottom: 1.25rem;
}

/* Links */
.article-body :deep(a) {
    color: #06b6d4;
    text-decoration: underline;
    text-underline-offset: 2px;
    transition: color 0.2s;
}

.article-body :deep(a:hover) {
    color: #0891b2;
}

/* Strong text */
.article-body :deep(strong) {
    font-weight: 700;
    color: #111827;
}

/* Blockquotes */
.article-body :deep(blockquote) {
    border-left: 4px solid #06b6d4;
    padding-left: 1.25rem;
    margin: 1.5rem 0;
    font-style: italic;
    color: #4b5563;
    background-color: #f0fdfa;
    padding: 1rem 1.25rem;
    border-radius: 0 0.5rem 0.5rem 0;
}

/* Lists */
.article-body :deep(ul) {
    list-style: disc;
    padding-left: 1.75rem;
    margin-bottom: 1.25rem;
}

.article-body :deep(ol) {
    list-style: decimal;
    padding-left: 1.75rem;
    margin-bottom: 1.25rem;
}

.article-body :deep(li) {
    margin-bottom: 0.4rem;
    line-height: 1.8;
}

.article-body :deep(li > ul),
.article-body :deep(li > ol) {
    margin-top: 0.4rem;
    margin-bottom: 0;
}

/* Images inside article */
.article-body :deep(img) {
    max-width: 100%;
    height: auto;
    border-radius: 12px;
    margin: 1.5rem 0;
}

/* Tables */
.article-body :deep(table) {
    width: 100%;
    border-collapse: collapse;
    margin: 1.5rem 0;
    font-size: 0.95rem;
}

.article-body :deep(th),
.article-body :deep(td) {
    padding: 0.75rem 1rem;
    border: 1px solid #e5e7eb;
    text-align: left;
}

.article-body :deep(th) {
    background-color: #f9fafb;
    font-weight: 600;
}

/* Code */
.article-body :deep(code) {
    background-color: #f3f4f6;
    padding: 0.15rem 0.4rem;
    border-radius: 4px;
    font-size: 0.9em;
}

.article-body :deep(pre) {
    background-color: #1f2937;
    color: #e5e7eb;
    padding: 1.25rem;
    border-radius: 12px;
    overflow-x: auto;
    margin: 1.5rem 0;
}

.article-body :deep(pre code) {
    background: transparent;
    padding: 0;
    color: inherit;
}

/* Horizontal rule */
.article-body :deep(hr) {
    border: none;
    border-top: 2px solid #f3f4f6;
    margin: 2rem 0;
}

/* =============================
   Mobile responsive overrides
   ============================= */
@media screen and (max-width: 768px) {
    .article-body {
        font-size: 15px;
        line-height: 1.8;
    }

    .article-body :deep(h2) {
        font-size: 1.35rem;
        margin-top: 2rem;
    }

    .article-body :deep(h3) {
        font-size: 1.15rem;
        margin-top: 1.5rem;
    }

    .article-body :deep(blockquote) {
        padding: 0.75rem 1rem;
    }
}
</style>
