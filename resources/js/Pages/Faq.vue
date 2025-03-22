<script setup>
import Faq from '@/Components/Faq.vue';
import Footer from '@/Components/Footer.vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import { Head } from '@inertiajs/vue3';


</script>

<template>
    <Head title="FAQ"/>
    <AuthenticatedHeaderLayout />

     <section class="py-customVerticalSpacing">
        <Faq/>
     </section>

    <Footer />
</template>

<style scoped></style>
