<template>
     <AuthenticatedHeaderLayout />
        <div class="full-w-container py-customVerticalSpacing">
            <h1 class="text-3xl font-bold mb-6">{{ page.title }}</h1>
            <div class="prose max-w-none" v-html="page.content"></div>
        </div>
</template>

<script setup>
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    page: Object
});
</script>