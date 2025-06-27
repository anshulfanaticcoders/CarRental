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
        <div class="max-w-[1200px] max-[768px]:max-w-full mx-auto py-customVerticalSpacing my-[2rem]
         px-[1.5rem] shadow-[0_3px_8px_rgba(0,0,0,0.24)] max-[768px]:my-0 max-[1230px]:shadow-none">
            <h1 class="text-3xl font-bold mb-6 max-[768px]:text-[1.2rem]">{{ page.title }}</h1>
            <div class="prose max-w-none" v-html="page.content"></div>
        </div>

        <Footer/>
</template>

<script setup>
import Footer from '@/Components/Footer.vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    page: Object,
    seoMeta: Object,
    locale: String,
});

const page_ = usePage();
const currentLocale = computed(() => props.locale || 'en');
const currentUrl = computed(() => window.location.href);

const seoTranslation = computed(() => {
    if (!props.seoMeta || !props.seoMeta.translations) {
        return {};
    }
    return props.seoMeta.translations.find(t => t.locale === currentLocale.value) || {};
});

const seoTitle = computed(() => {
    return seoTranslation.value.seo_title || props.seoMeta?.seo_title || props.page.title;
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
</script>
<style scoped>

.prose ::v-deep h4 {
    font-size: 1.5rem !important;
}
.prose ::v-deep h5 {
    font-weight: bold !important;
}

@media screen and (max-width:768px) {
    .prose ::v-deep p{
        font-size: 0.875rem;
    } 
    .prose ::v-deep h4 {
    font-size: 1.25rem !important;
}


}
</style>
