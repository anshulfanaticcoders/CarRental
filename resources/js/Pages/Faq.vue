<script setup>
import { usePage } from '@inertiajs/vue3';
import Faq from '@/Components/Faq.vue';
import Footer from '@/Components/Footer.vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import SchemaInjector from '@/Components/SchemaInjector.vue';
import SeoHead from '@/Components/SeoHead.vue';

const props = defineProps({
    schema: Object,
    seo: Object,
});

// Page-level h1 — single main heading the crawler sees first.
const page = usePage();
const _tr = (group, key) => page.props.translations?.[group]?.[key] || null;
const pageHeading = _tr('faq', 'page_heading')
    || _tr('homepage', 'faqs_title')
    || props.seo?.title
    || 'Frequently Asked Questions';
const pageIntro = _tr('faq', 'page_intro')
    || _tr('common', 'faqs_description')
    || '';
</script>

<template>
    <SeoHead :seo="seo" />
    <SchemaInjector v-if="schema" :schema="schema" />
    <AuthenticatedHeaderLayout />

    <section class="faq-hero py-customVerticalSpacing">
        <div class="full-w-container">
            <h1 class="faq-hero-title">{{ pageHeading }}</h1>
            <p v-if="pageIntro" class="faq-hero-sub">{{ pageIntro }}</p>
        </div>
    </section>

    <section class="py-customVerticalSpacing">
        <Faq variant="light" :hide-header="true" />
    </section>

    <Footer />
</template>

<style scoped>
.faq-hero { text-align: center; padding-bottom: 1rem; }
.faq-hero-title {
    font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
    font-weight: 700;
    font-size: clamp(1.9rem, 4vw, 2.75rem);
    color: #153b4f;
    letter-spacing: -0.02em;
    margin: 0 0 0.75rem;
}
.faq-hero-sub {
    font-size: 1rem;
    color: #64748b;
    max-width: 640px;
    margin: 0 auto;
    line-height: 1.65;
}
</style>
