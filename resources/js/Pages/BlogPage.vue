<template>
    <SeoHead :seo="seo" />
    <AuthenticatedHeaderLayout />

    <!-- ======================== -->
    <!-- HERO SECTION             -->
    <!-- ======================== -->
    <section class="blog-hero flex items-end">
        <div class="relative z-10 w-full">
            <div class="full-w-container mx-auto pb-12">
                <!-- Breadcrumb on hero -->
                <nav class="mb-5" aria-label="Breadcrumb">
                    <ol class="flex items-center text-sm text-white/60">
                        <li>
                            <Link
                                :href="route('welcome', { locale: currentLocale })"
                                class="hover:text-white transition-colors"
                            >Home</Link>
                        </li>
                        <li class="mx-1.5 text-white/30 select-none">/</li>
                        <li class="text-white font-medium">Blog</li>
                    </ol>
                </nav>
                <h1 class="text-white text-[clamp(2rem,5vw,3.25rem)] font-extrabold leading-tight tracking-tight max-w-2xl blog-title">
                    Stories, Tips &amp; Guides
                </h1>
                <p class="text-white/70 text-lg mt-3 max-w-lg font-normal blog-subtitle">
                    Insights to help you make the most of your car rental experience across Europe and beyond.
                </p>
            </div>
        </div>
    </section>

    <!-- ======================== -->
    <!-- TAG FILTER BAR           -->
    <!-- ======================== -->
    <section v-if="tags && tags.length > 0" class="border-b border-gray-100 bg-white sticky top-0 z-30">
        <div class="full-w-container mx-auto">
            <div class="flex items-center gap-3 py-4 tags-scroll overflow-x-auto">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 whitespace-nowrap mr-1">Filter:</span>

                <button
                    class="tag-pill inline-flex items-center px-4 py-2 text-sm font-medium rounded-full border whitespace-nowrap transition-all duration-200"
                    :class="!activeTag
                        ? 'bg-[#153b4f] text-white border-[#153b4f] shadow-sm'
                        : 'text-gray-600 bg-gray-50 border-gray-200 hover:border-brand-200 hover:text-[#153b4f]'"
                    @click="filterByTag('')"
                >
                    All Posts
                </button>
                <button
                    v-for="tag in tags"
                    :key="tag.slug"
                    class="tag-pill inline-flex items-center px-4 py-2 text-sm font-medium rounded-full border whitespace-nowrap transition-all duration-200"
                    :class="activeTag === tag.slug
                        ? 'bg-[#153b4f] text-white border-[#153b4f] shadow-sm'
                        : 'text-gray-600 bg-gray-50 border-gray-200 hover:border-brand-200 hover:text-[#153b4f]'"
                    @click="filterByTag(tag.slug)"
                >
                    {{ tag.name }}
                </button>
            </div>
        </div>
    </section>

    <!-- ======================== -->
    <!-- MAIN CONTENT + SIDEBAR   -->
    <!-- ======================== -->
    <main class="full-w-container mx-auto py-12">
        <div class="flex gap-10 lg:gap-12">

            <!-- LEFT: Blog Grid Column -->
            <div id="blog-list-container" class="flex-1 min-w-0">

                <!-- Results count -->
                <div class="flex items-center justify-between mb-8">
                    <p class="text-sm text-gray-500">
                        Showing <span class="font-semibold text-gray-700">{{ blogs.data.length }}</span>
                        of <span class="font-semibold text-gray-700">{{ blogs.total }}</span> articles
                    </p>
                </div>

                <!-- Loading skeleton -->
                <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-7">
                    <div v-for="n in 6" :key="n" class="rounded-2xl border border-gray-100 overflow-hidden">
                        <Skeleton class="w-full aspect-[16/10]" />
                        <div class="p-5 pb-6">
                            <Skeleton class="h-5 w-20 mb-3 rounded-full" />
                            <Skeleton class="h-6 w-full mb-2" />
                            <Skeleton class="h-6 w-3/4 mb-3" />
                            <Skeleton class="h-4 w-full mb-1" />
                            <Skeleton class="h-4 w-2/3 mb-4" />
                            <Skeleton class="h-3 w-1/3" />
                        </div>
                    </div>
                </div>

                <!-- Blog grid -->
                <div v-else-if="blogs.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-7">
                    <article
                        v-for="blog in blogs.data"
                        :key="blog.id"
                        class="blog-card bg-white rounded-2xl border border-gray-100 overflow-hidden cursor-pointer group"
                    >
                        <Link :href="route('blog.show', { locale: currentLocale, country: blog.canonical_country || currentCountry, blog: blog.translated_slug })">
                            <div class="card-image aspect-[16/10] overflow-hidden">
                                <img
                                    :src="blog.image"
                                    :alt="blog.title"
                                    class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.06]"
                                    loading="lazy"
                                >
                            </div>
                            <div class="p-5 pb-6">
                                <div v-if="blog.tags && blog.tags.length > 0" class="flex items-center gap-2 mb-3">
                                    <span class="inline-block text-[11px] font-semibold uppercase tracking-wider text-cyan-700 bg-cyan-50 border border-cyan-200 px-2.5 py-0.5 rounded-full">
                                        {{ blog.tags[0].name }}
                                    </span>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 leading-snug mb-2 group-hover:text-[#1c4d66] transition-colors line-clamp-2">
                                    {{ blog.title }}
                                </h3>
                                <p class="text-sm text-gray-500 leading-relaxed line-clamp-2 mb-4">
                                    {{ getExcerptText(blog) }}
                                </p>
                                <div class="flex items-center text-xs text-gray-400">
                                    <span>{{ formatDate(blog.created_at) }}</span>
                                    <span class="meta-dot">{{ getReadingTime(blog.content) }} min read</span>
                                </div>
                            </div>
                        </Link>
                    </article>
                </div>

                <!-- Empty state -->
                <div v-else class="text-center py-16">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                        </svg>
                    </div>
                    <p class="text-lg font-semibold text-gray-700 mb-1">No articles found</p>
                    <p class="text-sm text-gray-500">
                        <template v-if="activeTag">
                            No articles match this tag.
                            <button @click="filterByTag('')" class="text-cyan-600 hover:underline">View all posts</button>
                        </template>
                        <template v-else>Coming Soon</template>
                    </p>
                </div>

                <!-- Pagination -->
                <div class="mt-10 flex justify-center">
                    <Pagination
                        :currentPage="blogs.current_page"
                        :totalPages="blogs.last_page"
                        @page-change="handlePageChange"
                        class="justify-center flex-wrap"
                    />
                </div>
            </div>

            <!-- RIGHT: Sticky Sidebar (desktop only) -->
            <aside class="hidden lg:block w-[300px] xl:w-[340px] flex-shrink-0">
                <div class="sticky top-20 space-y-8">

                    <!-- Recent Posts -->
                    <div class="bg-gray-50/80 border border-gray-100 rounded-2xl p-6">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-5 flex items-center gap-2">
                            <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Recent Posts
                        </h3>

                        <!-- Skeleton -->
                        <div v-if="isRecentLoading" class="space-y-5">
                            <div v-for="n in 4" :key="n" class="flex gap-3.5">
                                <Skeleton class="w-[72px] h-[54px] rounded-xl flex-shrink-0" />
                                <div class="flex-1">
                                    <Skeleton class="h-4 w-full mb-2" />
                                    <Skeleton class="h-3 w-1/2" />
                                </div>
                            </div>
                        </div>

                        <!-- Recent list -->
                        <ul v-else-if="recentBlogs.length > 0" class="space-y-5">
                            <li v-for="recentBlog in recentBlogs.slice(0, 4)" :key="recentBlog.id">
                                <Link
                                    :href="route('blog.show', { locale: currentLocale, country: currentCountry, blog: recentBlog.translated_slug })"
                                    class="flex gap-3.5 group"
                                >
                                    <div class="w-[72px] h-[54px] rounded-xl overflow-hidden flex-shrink-0 bg-gray-200">
                                        <img
                                            v-if="recentBlog.image"
                                            :src="recentBlog.image"
                                            :alt="recentBlog.title"
                                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                            loading="lazy"
                                        >
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h4 class="text-[13px] font-semibold text-gray-800 leading-tight group-hover:text-cyan-700 transition-colors line-clamp-2">
                                            {{ recentBlog.title }}
                                        </h4>
                                        <p class="text-[11px] text-gray-400 mt-1.5 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            {{ formatDate(recentBlog.created_at) }}
                                        </p>
                                    </div>
                                </Link>
                            </li>
                        </ul>

                        <p v-else class="text-sm text-gray-500">No recent posts</p>
                    </div>

                    <!-- Popular Tags -->
                    <div v-if="tags && tags.length > 0" class="bg-gray-50/80 border border-gray-100 rounded-2xl p-6">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            Popular Tags
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="tag in tags"
                                :key="tag.slug"
                                @click="filterByTag(tag.slug)"
                                class="text-xs font-medium text-[#153b4f] bg-white border border-gray-200 hover:border-cyan-300 hover:bg-cyan-50 hover:text-cyan-700 px-3 py-1.5 rounded-full transition-colors"
                                :class="activeTag === tag.slug ? 'border-cyan-400 bg-cyan-50 text-cyan-700' : ''"
                            >
                                {{ tag.name }}
                            </button>
                        </div>
                    </div>

                </div>
            </aside>

        </div>
    </main>

    <Footer />
</template>

<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import Footer from '@/Components/Footer.vue';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';
import { Skeleton } from '@/Components/ui/skeleton';
import SeoHead from '@/Components/SeoHead.vue';
import blogbgimage from '../../assets/blogpagebgimage.jpg';
import { ref, onMounted, nextTick, computed } from 'vue';

const props = defineProps({
    blogs: Object,
    seo: Object,
    locale: String,
    country: String,
    tags: Array,
    activeTag: String,
});

const page = usePage();
const recentBlogs = ref([]);
const isLoading = ref(true);
const isRecentLoading = ref(true);

const currentLocale = computed(() => props.locale || page.props.locale || 'en');
const currentCountry = computed(() => props.country || page.props.country || 'us');

onMounted(async () => {
    setTimeout(() => {
        isLoading.value = false;
    }, 400);

    try {
        const response = await axios.get(`/api/recent-blogs?locale=${currentLocale.value}&country=${currentCountry.value}`);
        recentBlogs.value = response.data;
    } catch (error) {
        console.error('Error fetching recent blogs:', error);
    } finally {
        isRecentLoading.value = false;
    }
});

const formatDate = (date) => {
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
};

const getExcerptText = (blog) => {
    if (blog.excerpt) return blog.excerpt;
    if (!blog.content) return '';
    // Strip HTML and take first 160 chars
    const text = blog.content.replace(/<[^>]*>/g, '').trim();
    return text.length > 160 ? text.substring(0, 160) + '...' : text;
};

const getReadingTime = (content) => {
    if (!content) return 1;
    const text = content.replace(/<[^>]*>/g, '');
    const wordCount = text.split(/\s+/).filter(Boolean).length;
    return Math.max(1, Math.ceil(wordCount / 200));
};

const filterByTag = (tagSlug) => {
    const params = {
        locale: currentLocale.value,
        country: currentCountry.value,
    };

    const queryParams = {};
    if (tagSlug) {
        queryParams.tag = tagSlug;
    }

    router.visit(route('blog', params), {
        data: queryParams,
        preserveState: true,
        preserveScroll: false,
        onSuccess: () => {
            isLoading.value = true;
            setTimeout(() => {
                isLoading.value = false;
            }, 300);
        },
    });
};

const handlePageChange = (pageNumber) => {
    const params = {
        locale: currentLocale.value,
        country: currentCountry.value,
        page: pageNumber,
    };

    if (props.activeTag) {
        params.tag = props.activeTag;
    }

    router.visit(route('blog', { locale: currentLocale.value, country: currentCountry.value }), {
        data: { page: pageNumber, ...(props.activeTag ? { tag: props.activeTag } : {}) },
        preserveState: true,
        onSuccess: () => {
            nextTick(() => {
                setTimeout(() => {
                    const container = document.getElementById('blog-list-container');
                    if (container) {
                        const offset = 45;
                        const topPosition = container.offsetTop - offset;
                        window.scrollTo({
                            top: topPosition >= 0 ? topPosition : 0,
                            behavior: 'smooth',
                        });
                    }
                }, 50);
            });
        },
        onError: (errors) => console.error('Inertia visit error:', errors),
    });
};
</script>

<style scoped>
/* Hero section */
.blog-hero {
    position: relative;
    height: 340px;
    background-image: v-bind("'url(' + blogbgimage + ')'");
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}

.blog-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(10,29,40,0.88) 0%, rgba(21,59,79,0.75) 50%, rgba(6,182,212,0.35) 100%);
}

.blog-hero::after {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--grain-texture, none);
    opacity: 0.04;
    pointer-events: none;
}

/* Typography */
.blog-title {
    font-family: 'Plus Jakarta Sans', 'Inter', system-ui, sans-serif;
    font-weight: 800;
}

.blog-subtitle {
    font-family: 'IBM Plex Serif', Georgia, serif;
}

/* Tag pills scroll */
.tags-scroll {
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.tags-scroll::-webkit-scrollbar {
    display: none;
}

.tag-pill:hover {
    transform: translateY(-1px);
}

/* Blog cards */
.blog-card {
    transition: all 0.35s cubic-bezier(0.22, 1, 0.36, 1);
}

.blog-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 40px rgba(21,59,79,0.12), 0 8px 16px rgba(21,59,79,0.06);
}

/* Reading time dot separator */
.meta-dot::before {
    content: '';
    display: inline-block;
    width: 3px;
    height: 3px;
    background: #94a3b8;
    border-radius: 50%;
    margin: 0 8px;
    vertical-align: middle;
}

/* Line clamp */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Mobile responsive */
@media screen and (max-width: 768px) {
    .blog-hero {
        height: 280px;
    }
}
</style>
