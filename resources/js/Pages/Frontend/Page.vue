<template>
    <AboutUsPage
        v-if="page.slug === aboutUsTranslatedSlug"
        :page="page"
        :seo="seo"
        :locale="locale"
    />
    <div v-else>
        <SeoHead :seo="seo" />
        <AuthenticatedHeaderLayout />
        <div class="hero-section relative h-[500px] max-[768px]:h-[300px] flex items-center justify-center text-white"
             :style="{ backgroundImage: `url(${pageBg})`, backgroundSize: 'cover', backgroundPosition: 'bottom' }">
            <h1 class="text-5xl font-bold text-center">{{ page.title }}</h1>
        </div>
        <div class="max-w-[1500px] max-[768px]:max-w-full mx-auto py-customVerticalSpacing my-[2rem]
             px-[1.5rem] max-[768px]:my-0 max-[1230px]:shadow-none">
            <div class="prose max-w-none" v-html="page.content"></div>
        </div>
        <Footer/>
    </div>
</template>

<script setup>
import Footer from '@/Components/Footer.vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import pageBg from '../../../assets/pageBg.jpg';
import AboutUsPage from './AboutUsPage.vue'; // Import the new component

const props = defineProps({
    page: Object,
    seo: Object,
    locale: String,
    aboutUsTranslatedSlug: String, // New prop for dynamic About Us slug
});
</script>
<style scoped>
.prose :deep(h4) {
    font-size: 1.5rem !important;
    font-weight: bold !important;
}
.prose :deep(h5) {
    font-weight: bold !important;
}

@media screen and (max-width:768px) {
    .prose :deep(p) {
        font-size: 0.875rem;
    } 
    .prose :deep(h4) {
    font-size: 1.25rem !important;
}
}
</style>
