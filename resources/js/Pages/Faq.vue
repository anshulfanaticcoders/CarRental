<script setup>
import Faq from '@/Components/Faq.vue';
import Footer from '@/Components/Footer.vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';


</script>

<template>
    <AuthenticatedHeaderLayout />

     <section class="py-customVerticalSpacing">
        <Faq/>
     </section>

    <Footer />
</template>

<style scoped></style>
