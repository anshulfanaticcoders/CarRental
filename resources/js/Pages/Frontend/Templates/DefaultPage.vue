<script setup>
import Footer from '@/Components/Footer.vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { computed } from 'vue';

const props = defineProps({
    page: Object,
    meta: Object,
    sections: Array,
    seo: Object,
    locale: String,
    pages: Object,
});

const getSection = (type) => props.sections?.find(s => s.type === type);

const heroSection = computed(() => getSection('hero'));

const heroSubtitle = computed(() => {
    if (heroSection.value?.content) return heroSection.value.content;
    if (props.sections?.length > 0) return props.sections[0].content;
    return null;
});

const contentSections = computed(() => {
    return props.sections?.filter(s => s.type !== 'hero') || [];
});
</script>

<template>
    <SeoHead :seo="seo" />
    <AuthenticatedHeaderLayout />

    <div class="default-page text-gray-800">
        <!-- Hero Section -->
        <section class="hero-section relative overflow-hidden bg-customPrimaryColor text-white">
            <div class="absolute inset-0 bg-gradient-to-br from-[#0a1d28] via-customPrimaryColor to-[#1a4d66]"></div>
            <!-- Decorative glow -->
            <div class="absolute -top-1/2 -right-1/5 w-[700px] h-[700px] rounded-full bg-[radial-gradient(circle,rgba(6,182,212,0.12)_0%,transparent_70%)] animate-pulse"></div>
            <div class="absolute -bottom-1/3 -left-[10%] w-[500px] h-[500px] rounded-full bg-[radial-gradient(circle,rgba(6,182,212,0.08)_0%,transparent_70%)] animate-pulse delay-1000"></div>

            <div class="container mx-auto px-6 py-20 md:py-32 relative z-10 text-center max-w-3xl">
                <h1 class="text-4xl md:text-5xl lg:text-[56px] font-extrabold mb-5 leading-tight tracking-tight animate-fade-in-up">
                    {{ page.title }}
                </h1>
                <p v-if="heroSubtitle"
                   class="text-lg md:text-xl opacity-90 leading-relaxed font-light animate-fade-in-up-delay"
                   v-html="heroSubtitle">
                </p>
            </div>

            <!-- Wave -->
            <div class="absolute bottom-[-1px] left-0 w-full leading-[0]">
                <svg viewBox="0 0 1440 48" fill="none" preserveAspectRatio="none" class="w-full h-12">
                    <path d="M0 48h1440V20c-120 15-360 28-720 28S120 35 0 20v28z" fill="white"/>
                </svg>
            </div>
        </section>

        <!-- Main Content -->
        <section class="py-12 md:py-20 bg-white">
            <div class="container mx-auto px-6">
                <div v-if="page.content"
                     class="prose prose-lg max-w-4xl mx-auto prose-headings:text-customPrimaryColor prose-headings:font-bold prose-a:text-cyan-600 prose-a:no-underline hover:prose-a:underline"
                     v-html="page.content">
                </div>
            </div>
        </section>

        <!-- Additional Sections -->
        <template v-for="(section, index) in contentSections" :key="index">
            <section class="py-10 md:py-16" :class="index % 2 === 0 ? 'bg-slate-50' : 'bg-white'">
                <div class="container mx-auto px-6">
                    <div class="max-w-4xl mx-auto">
                        <h2 v-if="section.title"
                            class="text-2xl md:text-3xl font-bold text-customPrimaryColor mb-6 text-center">
                            {{ section.title }}
                        </h2>
                        <div v-if="section.content"
                             class="prose prose-lg max-w-none prose-headings:text-customPrimaryColor prose-headings:font-bold prose-a:text-cyan-600"
                             v-html="section.content">
                        </div>
                    </div>
                </div>
            </section>
        </template>
    </div>

    <Footer />
</template>

<style scoped>
.hero-section {
    min-height: 50vh;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(24px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-up {
    animation: fadeInUp 0.9s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.animate-fade-in-up-delay {
    opacity: 0;
    animation: fadeInUp 0.9s cubic-bezier(0.16, 1, 0.3, 1) 0.15s forwards;
}

/* Prose overrides for v-html content */
.prose :deep(h2) {
    font-size: 1.75rem;
    margin-top: 2rem;
    margin-bottom: 1rem;
}

.prose :deep(h3) {
    font-size: 1.375rem;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
}

.prose :deep(h4) {
    font-size: 1.25rem;
    font-weight: bold;
}

.prose :deep(p) {
    margin-bottom: 1rem;
    line-height: 1.8;
}

.prose :deep(ul),
.prose :deep(ol) {
    padding-left: 1.5rem;
    margin-bottom: 1rem;
}

.prose :deep(li) {
    margin-bottom: 0.5rem;
}

@media (max-width: 768px) {
    .hero-section {
        min-height: 40vh;
    }

    .prose :deep(h2) {
        font-size: 1.5rem;
    }

    .prose :deep(p) {
        font-size: 0.9375rem;
    }
}
</style>
