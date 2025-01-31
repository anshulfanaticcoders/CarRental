<template>
    <AuthenticatedHeaderLayout/>
    <section class="blog-single min-h-screen flex flex-col gap-8 items-center py-16">
        <div class="w-full full-w-container">
            <img :src="blog.image" :alt="blog.title" class="w-full h-[500px] object-cover rounded-lg shadow-lg">
            <h1 class="text-4xl font-bold text-customDarkBlackColor mt-6">{{ blog.title }}</h1>
            <p class="text-gray-500 mt-2">Published on {{ formatDate(blog.created_at) }}</p>

            <div class="mt-6 text-lg leading-relaxed text-gray-700">
                <p v-html="blog.content"></p>
            </div>
        </div>
    </section>
</template>

<script setup>
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
