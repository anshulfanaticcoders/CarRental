<template>
    <Head :title="page.title" />
     <AuthenticatedHeaderLayout />
        <div class="max-w-[1200px] max-[768px]:max-w-full mx-auto py-customVerticalSpacing my-[2rem]
         px-[1.5rem] shadow-[0_3px_8px_rgba(0,0,0,0.24)] max-[768px]:shadow-none max-[768px]:my-0">
            <h1 class="text-3xl font-bold mb-6 max-[768px]:text-[1.2rem]">{{ page.title }}</h1>
            <div class="prose max-w-none" v-html="page.content"></div>
        </div>

        <Footer/>
</template>

<script setup>
import Footer from '@/Components/Footer.vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    page: Object
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