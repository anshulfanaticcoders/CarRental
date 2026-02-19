<template>
    <SeoHead :seo="seo" />

    <AuthenticatedHeaderLayout />
    <SchemaInjector v-if="props.schema" :schema="props.schema" />

    <section
        class="blog-single min-h-screen flex flex-col gap-8 items-center pb-16 py-10 max-[768px]:py-0 max-[768px]:pb-8"
    >
        <!-- Breadcrumb -->
        <nav class="w-full full-w-container max-[768px]:px-0 py-4 max-[768px]:pb-0" v-if="currentLocale && currentCountry">
            <ul class="flex items-center text-gray-600 text-[1rem]">
                <li>
                    <Link
                        :href="route('welcome', { locale: currentLocale.value || 'en' })"
                        class="hover:underline text-customPrimaryColor max-[768px]:text-[0.75rem]"
                    >Home</Link>
                </li>
                <span class="mx-2 text-customPrimaryColor font-medium">></span>
                <li>
                    <Link
                        :href="route('blog', { locale: currentLocale.value || 'en', country: currentCountry.value || 'us' })"
                        class="hover:underline text-customPrimaryColor max-[768px]:text-[0.75rem]"
                    >Blog</Link>
                </li>
                <span class="mx-2 text-customPrimaryColor font-medium">></span>
                <li class="text-gray-900 font-semibold max-[768px]:hidden">{{ blog.title }}</li>
                <li class="hidden text-gray-900 font-semibold max-[768px]:text-[0.75rem] max-[768px]:block">
                    {{ truncateWords(blog.title, 4) }}
                </li>
            </ul>
        </nav>

        <div class="w-full full-w-container max-[768px]:w-full">
            <div
                :style="{ backgroundImage: `url(${blog.image})` }"
                class="w-full h-[500px] max-[768px]:h-[350px] aspect-video bg-cover bg-center rounded-lg shadow-lg max-[768px]:rounded-none"
            ></div>
            <div class="max-[768px]:px-[1.5rem]">
                <h1
                    class="text-4xl font-bold text-customDarkBlackColor mt-6 max-[768px]:text-[1.2rem] max-[768px]:leading-7"
                >
                    {{ blog.title }}
                </h1>
                <p class="text-gray-500 mt-2 max-[768px]:text-[0.95rem]">
                    Published on {{ formatDate(blog.created_at) }}
                </p>

                <div class="mt-6 text-lg leading-relaxed text-gray-700">
                    <div class="prose max-w-none" v-html="blog.content"></div>
                </div>
            </div>
        </div>
    </section>

    <Footer />
</template>

<script setup>
import Footer from '@/Components/Footer.vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import SchemaInjector from '@/Components/SchemaInjector.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { defineProps, computed } from 'vue';

const props = defineProps({
    blog: Object,
    schema: Object,
    seo: Object,
    locale: String,
    country: String,
});

const currentLocale = computed(() => props.locale || 'en');
const currentCountry = computed(() => props.country || 'us');
console.log('Locale:', currentLocale.value, 'Country:', currentCountry.value);

const currentUrl = computed(() => window.location.href);

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const truncateWords = (text, limit = 4) => {
    if (!text) return '';
    const words = text.split(' ');
    if (words.length <= limit) return text;
    return words.slice(0, limit).join(' ') + '...';
};
</script>

<style scoped>
.blog-content-styles :deep(ul),
.blog-content-styles :deep(ol) {
    list-style: revert;
    padding-left: 1.5rem;
    margin-top: 0.5rem;
    margin-bottom: 0.5rem;
}

.blog-content-styles :deep(li) {
    list-style: revert;
    line-height: 0;
    margin-bottom: 0.25rem;
    padding-left: 0.25rem;
}

.blog-content-styles :deep(p) {
    margin-bottom: 0rem;
}

.prose :deep(h1) { font-size: 3rem !important; }
.prose :deep(h2) { font-size: 1.5rem !important; }
.prose :deep(h3) { font-size: 1.5rem !important; }
.prose :deep(h4) { font-size: 1.5rem !important; }
.prose :deep(h5) { font-weight: bold !important; }

@media screen and (max-width:768px) {
    .prose :deep(p) { font-size: 0.875rem; }
    .prose :deep(h1) { font-size: 1.5rem !important; margin: 1rem 0; }
    .prose :deep(h2),
    .prose :deep(h3),
    .prose :deep(h4) { font-size: 1.25rem !important; margin: 1rem 0; }
}
</style>
