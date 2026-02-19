<script setup>
import Faq from '@/Components/Faq.vue';
import Footer from '@/Components/Footer.vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import SchemaInjector from '@/Components/SchemaInjector.vue'; // Import SchemaInjector
import SeoHead from '@/Components/SeoHead.vue';

defineProps({
    schema: Object, // Add schema prop
    seo: Object,
});

</script>

<template>
    <SeoHead :seo="seo" />
    <SchemaInjector v-if="schema" :schema="schema" />
    <AuthenticatedHeaderLayout />

     <section class="py-customVerticalSpacing">
        <Faq/>
     </section>

    <Footer />
</template>

<style scoped></style>
