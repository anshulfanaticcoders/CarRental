<template>
    <Head>
        <meta name="robots" content="index, follow" />
        <title>{{ seoTitle }}</title>
        <meta name="description" :content="seoDescription" />
        <meta name="keywords" :content="seoKeywords" />
        <link rel="canonical" :href="canonicalUrl" />
        <meta property="og:title" :content="seoTitle" />
        <meta property="og:description" :content="seoDescription" />
        <meta property="og:image" :content="seoImageUrl" />
        <meta property="og:url" :content="currentUrl" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="seoTitle" />
        <meta name="twitter:description" :content="seoDescription" />
        <meta name="twitter:image" :content="seoImageUrl" />
    </Head>
    <AuthenticatedHeaderLayout />
    <div class="h-[28rem] blog_header py-[2rem] bg-no-repeat bg-center relative flex justify-center items-center"
        :style="{ backgroundImage: `url(${blogbgimage})`, backgroundSize: 'cover' }">
        <div class="overlay absolute bg-[#0000002a] h-full w-full top-0"></div>
        <h2 class="text-white leading-tight tracking-wide text-shadow-md">Blogs</h2>
    </div>
    <div class="py-customVerticalSpacing full-w-container flex max-[768px]:flex-col">
        <div id="blog-list-container" class="w-3/4 pr-8 max-[768px]:w-full max-[768px]:pr-0">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <div v-for="blog in blogs.data" :key="blog.id" class="rounded-lg overflow-hidden shadow-md
                hover:scale-[1.03] transition-transform duration-300 ease-in-out ">
                    <Link :href="route('blog.show', { locale: page.props.locale, blog: blog.translated_slug })">

                    <img :src="blog.image" :alt="blog.title" class="w-full h-48 object-cover" loading="lazy">
                    <div class="p-6">
                        <p class="text-sm flex items-center gap-1 text-gray-500">
                            <img :src=calendarIcon alt="" loading="lazy"> {{ formatDate(blog.created_at) }}
                        </p>
                        <h4 class="font-semibold text-xl text-gray-800 max-[768px]:text-[1rem]">{{ blog.title }}</h4>
                        <p class="text-gray-600 text-[1rem] mt-2 line-clamp-3 max-[768px]:text-[0.875rem]" v-html="blog.content"></p>
                        <Link :href="route('blog.show', { locale: page.props.locale, blog: blog.translated_slug })" class="inline-flex items-center mt-4 text-customPrimaryColor hover:underline
                            max-[768px]:text-[0.875rem]">
                        Read More
                        <img :src=goIcon alt="" class="w-8 ml-2 max-[768px]:w-6" loading="lazy">
                        </Link>
                    </div>
                    </Link>
                </div>
            </div>

            <div class="mt-8 flex justify-center">
                <Pagination
                    :currentPage="blogs.current_page"
                    :totalPages="blogs.last_page"
                    @page-change="handlePageChange"
                />
            </div>
        </div>
        <aside class="w-1/3 max-[768px]:w-full max-[768px]:mt-10">
            <div class="sticky top-8 shadow-md rounded-sm p-4 max-[768px]:static">
            <h3 class="text-lg font-semibold mb-4">Recent Posts</h3>
            <ul class="space-y-4">
                <li v-for="recentBlog in recentBlogs" :key="recentBlog.id" class="flex items-start">
                    <img :src="recentBlog.image" :alt="recentBlog.title" class="w-20 h-20 object-cover rounded mr-4" loading="lazy">
                    <div>
                        <Link :href="route('blog.show', { locale: page.props.locale, blog: recentBlog.translated_slug })"
                            class="font-medium text-gray-800 hover:underline">
                        {{ recentBlog.title }}
                        </Link>
                        <p class="text-sm text-gray-500 mt-1 flex gap-2"> <img :src=calendarIcon alt="" loading="lazy">{{
                            formatDate(recentBlog.created_at) }}</p>
                    </div>
                </li>
            </ul>
        </div>
        </aside>
    </div>

    <Footer />
</template>


<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3'; // Added usePage and router
import goIcon from "../../assets/goIcon.svg";
import calendarIcon from '../../assets/CalendarBlank.svg';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import blogbgimage from '../../assets/blogpagebgimage.jpg'
import { ref, onMounted, nextTick } from 'vue';
import Footer from '@/Components/Footer.vue';
import Pagination from '@/Components/ReusableComponents/Pagination.vue'; // Import the new component
import { computed } from 'vue'; // Import computed

const props = defineProps({
    blogs: Object,
    seoMeta: Object, // Added seoMeta prop
    locale: String, // Added locale prop
});

const page = usePage(); // Get page instance
const recentBlogs = ref([]);
const currentUrl = computed(() => window.location.href); // Added currentUrl

const seoTranslation = computed(() => {
    if (!props.seoMeta || !props.seoMeta.translations) {
        return {};
    }
    return props.seoMeta.translations.find(t => t.locale === props.locale) || {};
});

const seoTitle = computed(() => {
    return seoTranslation.value.seo_title || props.seoMeta?.seo_title || 'Blog'; // Fallback to 'Blog'
});

const seoDescription = computed(() => {
    return seoTranslation.value.meta_description || props.seoMeta?.meta_description || '';
});

const seoKeywords = computed(() => {
    return seoTranslation.value.keywords || props.seoMeta?.keywords || '';
});

const canonicalUrl = computed(() => {
    return props.seoMeta?.canonical_url || window.location.href;
});

const seoImageUrl = computed(() => {
    return props.seoMeta?.seo_image_url || '';
});


onMounted(async () => {
    try {
        const currentLocale = page.props.locale || 'en'; // Get current locale from Inertia props, fallback to 'en'
        const response = await axios.get(`/api/recent-blogs?locale=${currentLocale}`);
        recentBlogs.value = response.data;
    } catch (error) {
        console.error("Error fetching recent blogs:", error);
    }
});


const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const handlePageChange = (page) => {
    router.visit(route('blog', { page: page }), {
        onSuccess: () => {
            nextTick(() => {
                setTimeout(() => {
                    const container = document.getElementById('blog-list-container');
                    if (container) {
                        const offset = 45; // adjust as needed
                        const topPosition = container.offsetTop - offset;
                        window.scrollTo({
                            top: topPosition >= 0 ? topPosition : 0,
                            behavior: 'smooth'
                        });
                    }
                }, 50); // small delay to ensure smooth scroll
            });
        },
        onError: (errors) => {
            console.error('Inertia visit error:', errors);
        }
    });
};

</script>
