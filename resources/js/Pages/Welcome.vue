<script setup>
import SchemaInjector from '@/Components/SchemaInjector.vue';
import SeoHead from '@/Components/SeoHead.vue';
import heroImg from "../../assets/heroImage.jpg";
import Footer from '@/Components/Footer.vue';
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";

import WelcomeHero from '@/Components/Welcome/WelcomeHero.vue';
import TopDestinations from '@/Components/Welcome/TopDestinations.vue';
import WhyChooseUs from '@/Components/Welcome/WhyChooseUs.vue';
import EsimSection from '@/Components/EsimSection.vue';
import HowItWorks from "@/Components/ReusableComponents/HowItWorks.vue";
import BlogSection from '@/Components/Welcome/BlogSection.vue';
import TestimonialsSection from '@/Components/Welcome/TestimonialsSection.vue';
import FaqSection from '@/Components/Welcome/FaqSection.vue';
// Newsletter moved to Footer component
// import NewsletterSection from '@/Components/Welcome/NewsletterSection.vue';

import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
    blogs: Array,
    testimonials: Array,
    popularPlaces: Array,
    faqs: Array,
    schema: Array,
    seo: Object,
    pages: Object,
    heroImage: String,
});

const page = usePage();

const _t = (key) => page.props.translations?.homepage?.[key] || key;
const getLabel = (key, fallback) => { const l = _t(key); return l !== key ? l : fallback; };

const animatedTagline = computed(() => {
    const tagline = _t('tagline');
    if (!tagline) return '';
    const words = tagline.split(' ');
    if (words.length > 5) {
        words[2] = `<span class="anim-title-word">${words[2]}</span>`;
        words[5] = `<span class="anim-title-word">${words[5]}`;
        words[words.length - 1] = `${words[words.length - 1]}</span>`;
    }
    return words.join(' ').replace(/</g, '<').replace(/>/g, '>');
});

const heroBadge = computed(() => {
    const b = _t('hero_badge');
    return b !== 'hero_badge' ? b : 'Vrooem Car Rentals';
});

const heroSubtitle = computed(() => {
    const s = _t('hero_subtitle');
    return s !== 'hero_subtitle' ? s : 'Premium vehicles, curated routes, and concierge-level care for travelers who expect quiet elegance at every pickup.';
});

const heroTrustItems = computed(() => [
    { id: 't1', icon: 'star', label: getLabel('hero_trust_1', 'Free Internet Included') },
    { id: 't2', icon: 'bag', label: getLabel('hero_trust_2', 'Instant Replacement') },
    { id: 't3', icon: 'clock', label: getLabel('hero_trust_3', 'Damage Protection') },
    { id: 't4', icon: 'shield', label: getLabel('hero_trust_4', 'Best Price') },
    { id: 't5', icon: 'support', label: getLabel('hero_trust_5', '24/7 Support') },
]);

const translatedPhrases = computed(() => [_t('typewriter_text_1'), _t('typewriter_text_2'), _t('typewriter_text_3')]);

const displayedText = ref('');
let phraseIdx = 0, charIdx = 0, deleting = false, timer = null;
const typeWriter = () => {
    const phrase = translatedPhrases.value[phraseIdx];
    if (deleting) {
        if (charIdx <= 1) { deleting = false; phraseIdx = (phraseIdx + 1) % translatedPhrases.value.length; charIdx = 1; displayedText.value = translatedPhrases.value[phraseIdx].substring(0, 1); timer = setTimeout(typeWriter, 500); return; }
        displayedText.value = phrase.substring(0, --charIdx); timer = setTimeout(typeWriter, 40);
    } else {
        displayedText.value = phrase.substring(0, ++charIdx);
        if (charIdx === phrase.length) { deleting = true; timer = setTimeout(typeWriter, 2000); return; }
        timer = setTimeout(typeWriter, 70);
    }
};

const heroImageSource = computed(() => props.heroImage || heroImg);

onMounted(async () => {
    timer = setTimeout(typeWriter, 500);
});
onBeforeUnmount(() => { if (timer) clearTimeout(timer); });
</script>

<template>
    <SeoHead :seo="seo" />
    <template v-if="schema && schema.length">
        <SchemaInjector v-for="(s, i) in schema" :key="`schema-${i}`" :schema="s" />
    </template>

    <AuthenticatedHeaderLayout />

    <main class="overflow-x-hidden">

        <WelcomeHero :hero-badge="heroBadge" :animated-tagline="animatedTagline" :hero-subtitle="heroSubtitle" :displayed-text="displayedText" :hero-image="heroImageSource" :trust-items="heroTrustItems" />
        
        <TopDestinations :popular-places="popularPlaces" />
        
        <WhyChooseUs />
        
        <EsimSection />
        
        <HowItWorks />

        <!-- 6. TESTIMONIALS (LIGHT) -->
        <TestimonialsSection :initial-testimonials="testimonials" />

        <!-- 8. BLOG (DARK) -->
        <BlogSection :blogs="blogs" />

        <!-- 9. FAQ (LIGHT — dynamic from API) -->
        <FaqSection />

    </main>

    <Footer />
</template>
