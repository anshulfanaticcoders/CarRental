<template>
     <AuthenticatedHeaderLayout />
        <div class="full-w-container py-customVerticalSpacing">
            <h1 class="text-3xl font-bold mb-6 max-[768px]:text-[1.2rem]">{{ page.title }}</h1>
            <div class="prose max-w-none" v-html="page.content"></div>
        </div>

        <Footer/>
</template>

<script setup>
import Footer from '@/Components/Footer.vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';

defineProps({
    page: Object
});
</script>
<style scoped>

</style>