<template>
    <AuthenticatedHeaderLayout />
    <SchemaInjector v-if="props.schema" :schema="props.schema" />
    <section
        class="blog-single min-h-screen flex flex-col gap-8 items-center pb-16 py-10 max-[768px]:py-0 max-[768px]:pb-8">
        <!-- Breadcrumb -->
        <nav class="w-full full-w-container max-[768px]:px-0 py-4 max-[768px]:pb-0">
            <ul class="flex items-center text-gray-600 text-[1rem] max-[768px]:items-start">
                <li>
                    <Link :href="route('welcome')"
                        class="hover:underline text-customPrimaryColor max-[768px]:text-[0.75rem]">Home</Link>
                </li>
                <span class="mx-2 text-customPrimaryColor font-medium">></span>
                <li>
                    <Link :href="route('blog')"
                        class="hover:underline text-customPrimaryColor max-[768px]:text-[0.75rem]">Blog</Link>
                </li>
                <span class="mx-2 text-customPrimaryColor font-medium">></span>
                <li class="text-gray-900 font-semibold max-[768px]:text-[0.75rem]">{{ blog.title }}</li>
            </ul>
        </nav>
        <div class="w-full full-w-container max-[768px]:w-full">
            <div
                class="relative w-full h-[500px] max-[768px]:h-[350px] rounded-lg shadow-lg max-[768px]:rounded-none overflow-hidden">
                <div :style="{ backgroundImage: `url(${blog.image})` }"
                    class="absolute inset-0 w-full h-full bg-cover bg-center filter blur-lg scale-110"></div>
                <div class="absolute inset-0 bg-black bg-opacity-25"></div>
                <img :src="blog.image" :alt="blog.title" class="relative w-full h-full object-contain">
            </div>
            <div class="max-[768px]:px-[1.5rem]">
                <h1
                    class="text-4xl font-bold text-customDarkBlackColor mt-6 max-[768px]:text-[1.2rem] max-[768px]:leading-7">
                    {{ blog.title }}</h1>
                <p class="text-gray-500 mt-2 max-[768px]:text-[0.95rem]">Published on {{ formatDate(blog.created_at) }}
                </p>

                <div class="mt-6 text-lg leading-relaxed text-gray-700">
                    <!-- <div v-html="blog.content" class="blog-content-styles max-[768px]:text-[0.875rem] whitespace-break-spaces"></div> -->
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
import SchemaInjector from '@/Components/SchemaInjector.vue'; // Import SchemaInjector
import { Head, Link } from '@inertiajs/vue3';
import { defineProps } from 'vue';

const props = defineProps({
    blog: Object,
    schema: Object // Add schema prop
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};
</script>

<style scoped>
.blog-content-styles :deep(ul),
.blog-content-styles :deep(ol) {
    list-style: revert;
    padding-left: 1.5rem;
    /* Reduced padding for less space before bullet */
    margin-top: 0.5rem;
    /* Reduced space before the list */
    margin-bottom: 0.5rem;
    /* Reduced space after the list */
}

.blog-content-styles :deep(li) {
    list-style: revert;
    /* Ensures list items get their default markers like bullets or numbers */
    line-height: 0;
    /* Slightly reduced line height */
    margin-bottom: 0.25rem;
    /* Reduced space between list items */
    padding-left: 0.25rem;
    /* Reduced padding to bring text closer to bullet */
}

/* Optional: Style paragraphs within the v-html content if needed */
.blog-content-styles :deep(p) {
    margin-bottom: 0rem;
    /* Ensure paragraphs also have some bottom margin */
}

.prose ::v-deep h1 {
    font-size: 3rem !important;
}

.prose ::v-deep h2 {
    font-size: 2.5rem !important;
}

.prose ::v-deep h3 {
    font-size: 2rem !important;
}

.prose ::v-deep h4 {
    font-size: 1.5rem !important;
}

.prose ::v-deep h5 {
    font-weight: bold !important;
}

@media screen and (max-width:768px) {
    .prose ::v-deep p {
        font-size: 0.875rem;
    }

    .prose[data-v-5b7f4167] h3,
    .prose ::v-deep h4,
    .prose ::v-deep h3,
    .prose ::v-deep h2 {
        font-size: 1.25rem !important;
        margin: 1rem 0;
    }

    .prose ::v-deep h1 {
        font-size: 1.5rem !important;
        margin: 1rem 0;
    }

}
</style>
