<template>
    <AuthenticatedHeaderLayout/>
    <section class="blog-single min-h-screen flex flex-col gap-8 items-center py-16 max-[480px]:py-0 max-[480px]:pb-8">
        <div class="w-full full-w-container max-[480px]:w-full">
            <img :src="blog.image" :alt="blog.title" class="w-full h-[500px] max-[480px]:h-[350px] object-cover rounded-lg shadow-lg max-[480px]:rounded-none">
            <div class="max-[480px]:px-[1.5rem]">
                <h1 class="text-4xl font-bold text-customDarkBlackColor mt-6 max-[480px]:text-[1.2rem] max-[480px]:leading-7">{{ blog.title }}</h1>
            <p class="text-gray-500 mt-2 max-[480px]:text-[0.95rem]">Published on {{ formatDate(blog.created_at) }}</p>

            <div class="mt-6 text-lg leading-relaxed text-gray-700">
                <p v-html="blog.content" class="max-[480px]:text-[0.875rem]"></p>
            </div>
            </div>
        </div>
    </section>

    <Footer/>
</template>

<script setup>
import Footer from '@/Components/Footer.vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import { defineProps } from 'vue';

const props = defineProps({
    blog: Object
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};
</script>
