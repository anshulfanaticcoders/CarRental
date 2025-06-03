<template>
    <AuthenticatedHeaderLayout/>
    <section class="blog-single min-h-screen flex flex-col gap-8 items-center pb-16 py-10 max-[768px]:py-0 max-[768px]:pb-8">
        <!-- Breadcrumb -->
        <nav class="w-full full-w-container max-[768px]:px-0 py-4 max-[768px]:pb-0">
            <ul class="flex items-center text-gray-600 text-[1rem] max-[768px]:items-start">
                <li>
                    <Link :href="route('welcome')" class="hover:underline text-customPrimaryColor max-[768px]:text-[0.75rem]">Home</Link>
                </li>
                <span class="mx-2 text-customPrimaryColor font-medium">></span>
                <li>
                    <Link :href="route('blog')" class="hover:underline text-customPrimaryColor max-[768px]:text-[0.75rem]">Blog</Link>
                </li>
                <span class="mx-2 text-customPrimaryColor font-medium">></span>
                <li class="text-gray-900 font-semibold max-[768px]:text-[0.75rem]">{{ blog.title }}</li>
            </ul>
        </nav>
        <div class="w-full full-w-container max-[768px]:w-full">
            <img :src="blog.image" :alt="blog.title" class="w-full h-[500px] max-[768px]:h-[350px] object-cover rounded-lg shadow-lg max-[768px]:rounded-none">
            <div class="max-[768px]:px-[1.5rem]">
                <h1 class="text-4xl font-bold text-customDarkBlackColor mt-6 max-[768px]:text-[1.2rem] max-[768px]:leading-7">{{ blog.title }}</h1>
            <p class="text-gray-500 mt-2 max-[768px]:text-[0.95rem]">Published on {{ formatDate(blog.created_at) }}</p>

            <div class="mt-6 text-lg leading-relaxed text-gray-700">
                <p v-html="blog.content" class="max-[768px]:text-[0.875rem] whitespace-break-spaces"></p>
            </div>
            </div>
        </div>
    </section>

    <Footer/>
</template>

<script setup>
import Footer from '@/Components/Footer.vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
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
