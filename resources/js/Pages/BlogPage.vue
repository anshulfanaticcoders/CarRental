<template>
    <SeoHead :seo="seo" />

    <AuthenticatedHeaderLayout />

    <div class="h-[28rem] blog_header py-[2rem] bg-no-repeat bg-center relative flex justify-center items-center"
        :style="{ backgroundImage: `url(${blogbgimage})`, backgroundSize: 'cover' }">
        <div class="overlay absolute bg-[#0000002a] h-full w-full top-0"></div>
        <h2 class="text-white leading-tight tracking-wide text-shadow-md">Blogs</h2>
    </div>

    <div class="py-customVerticalSpacing full-w-container flex max-[768px]:flex-col">
        <div id="blog-list-container" class="w-3/4 pr-8 max-[768px]:w-full max-[768px]:pr-0">
            <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="n in 6" :key="n" class="rounded-lg overflow-hidden shadow-md">
                    <Skeleton class="w-full h-48" />
                    <div class="p-6">
                        <Skeleton class="h-4 w-1/2 mb-2" />
                        <Skeleton class="h-6 w-3/4 mb-4" />
                        <Skeleton class="h-4 w-full mb-1" />
                        <Skeleton class="h-4 w-full mb-1" />
                        <Skeleton class="h-4 w-3/4 mb-4" />
                        <Skeleton class="h-8 w-1/3" />
                    </div>
                </div>
            </div>
            <div v-else-if="blogs.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="blog in blogs.data" :key="blog.id" class="rounded-lg overflow-hidden shadow-md hover:scale-[1.03] transition-transform duration-300 ease-in-out">
                    <Link :href="route('blog.show', { locale: page.props.locale, country: blog.canonical_country || (page.props.country || 'us'), blog: blog.translated_slug })">
                        <img :src="blog.image" :alt="blog.title" class="w-full h-48 object-cover" loading="lazy">
                        <div class="p-6">
                            <p class="text-sm flex items-center gap-1 text-gray-500">
                                <img :src=calendarIcon alt="" loading="lazy"> {{ formatDate(blog.created_at) }}
                            </p>
                            <h4 class="font-semibold text-xl text-gray-800 max-[768px]:text-[1rem]">{{ blog.title }}</h4>
                            <p class="text-gray-600 text-[1rem] mt-2 line-clamp-3 max-[768px]:text-[0.875rem]" v-html="blog.content"></p>
                            <div class="inline-flex items-center mt-4 text-customPrimaryColor hover:underline max-[768px]:text-[0.875rem]">
                                Read More
                                <img :src=goIcon alt="" class="w-8 ml-2 max-[768px]:w-6" loading="lazy">
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
            <div v-else class="text-center py-10">
                <p class="text-xl text-gray-500">Coming Soon</p>
            </div>

            <div class="mt-8 flex justify-center">
                <Pagination
                    :currentPage="blogs.current_page"
                    :totalPages="blogs.last_page"
                    @page-change="handlePageChange"
                    class="justify-center flex-wrap"
                />
            </div>
        </div>

        <aside class="w-1/3 max-[768px]:w-full max-[768px]:mt-10">
            <div class="sticky top-8 shadow-md rounded-sm p-4 max-[768px]:static">
                <h3 class="text-lg font-semibold mb-4">Recent Posts</h3>
                <div v-if="isRecentLoading" class="space-y-4">
                    <div v-for="n in 5" :key="n" class="flex items-start">
                        <Skeleton class="w-20 h-20 rounded mr-4" />
                        <div class="flex-1">
                            <Skeleton class="h-5 w-3/4 mb-2" />
                            <Skeleton class="h-4 w-1/2" />
                        </div>
                    </div>
                </div>
                <ul v-else-if="recentBlogs.length > 0" class="space-y-4">
                    <li v-for="recentBlog in recentBlogs" :key="recentBlog.id" class="flex items-start gap-4">
                        <Link :href="route('blog.show', { locale: page.props.locale, country: page.props.country || 'us', blog: recentBlog.translated_slug })" class="flex-shrink-0">
                            <img :src="recentBlog.image" :alt="recentBlog.title" class="w-20 h-20 object-cover rounded" loading="lazy">
                        </Link>
                        <div class="flex-grow min-w-0">
                            <Link :href="route('blog.show', { locale: page.props.locale, country: page.props.country || 'us', blog: recentBlog.translated_slug })"
                                class="font-medium text-gray-800 hover:underline">
                                {{ recentBlog.title }}
                            </Link>
                            <p class="text-sm text-gray-500 mt-1 flex items-center gap-2">
                                <img :src=calendarIcon alt="" class="w-4 h-4" loading="lazy">
                                {{ formatDate(recentBlog.created_at) }}
                            </p>
                        </div>
                    </li>
                </ul>
                <div v-else>
                    <p class="text-gray-500">No recent blogs</p>
                </div>
            </div>
        </aside>
    </div>

    <Footer />
</template>

<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import goIcon from "../../assets/goIcon.svg";
import calendarIcon from '../../assets/CalendarBlank.svg';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import blogbgimage from '../../assets/blogpagebgimage.jpg'
import { ref, onMounted, nextTick, computed } from 'vue';
import Footer from '@/Components/Footer.vue';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';
import { Skeleton } from '@/Components/ui/skeleton';
import SeoHead from '@/Components/SeoHead.vue';

const props = defineProps({
    blogs: Object,
    seo: Object,
    locale: String,
    country: String, // Backend se aa rahi prop
});

const page = usePage();
const recentBlogs = ref([]);
const isLoading = ref(true);
const isRecentLoading = ref(true);
const currentUrl = computed(() => window.location.href);

onMounted(async () => {
    // Simulate loading for the main blog list (as it's passed via props)
    setTimeout(() => {
        isLoading.value = false;
    }, 500); // Adjust timing as needed

    try {
        const currentLocale = page.props.locale || 'en';
        const currentCountry = page.props.country || 'us';
        const response = await axios.get(`/api/recent-blogs?locale=${currentLocale}&country=${currentCountry}`);
        recentBlogs.value = response.data;
    } catch (error) {
        console.error("Error fetching recent blogs:", error);
    } finally {
        isRecentLoading.value = false;
    }
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const handlePageChange = (pageNumber) => {
    router.visit(route('blog', { locale: props.locale, country: props.country, page: pageNumber }), {
        onSuccess: () => {
            nextTick(() => {
                setTimeout(() => {
                    const container = document.getElementById('blog-list-container');
                    if (container) {
                        const offset = 45;
                        const topPosition = container.offsetTop - offset;
                        window.scrollTo({
                            top: topPosition >= 0 ? topPosition : 0,
                            behavior: 'smooth'
                        });
                    }
                }, 50);
            });
        },
        onError: (errors) => console.error('Inertia visit error:', errors)
    });
};
</script>
