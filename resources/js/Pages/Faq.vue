<script setup>
import Faq from '@/Components/Faq.vue';
import Footer from '@/Components/Footer.vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import SchemaInjector from '@/Components/SchemaInjector.vue'; // Import SchemaInjector
import { Head } from '@inertiajs/vue3';

defineProps({
    schema: Object // Add schema prop
});

</script>

<template>
    <Head>
        <meta name="robots" content="index, follow" />
        <title>FAQ</title>
    </Head>
    <SchemaInjector v-if="schema" :schema="schema" />
    <AuthenticatedHeaderLayout />

     <section class="py-customVerticalSpacing">
        <Faq/>
     </section>

    <Footer />
</template>

<style scoped></style>
