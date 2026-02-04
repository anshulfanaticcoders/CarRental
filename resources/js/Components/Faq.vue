<script setup>
import { ref, onMounted, watch } from "vue";
import axios from "axios";
import { usePage } from '@inertiajs/vue3'; // Added usePage
import {
    Accordion,
    AccordionContent,
    AccordionItem,
} from "@/Components/ui/accordion";
import AccordionTriggerVariant from "./ui/accordion/AccordionTriggerVariant.vue";
import { Skeleton } from '@/Components/ui/skeleton'; // Import Skeleton component

const faqs = ref([]);
const defaultValue = ref("item-1");
const isLoading = ref(true); // Loading state
const page = usePage(); // Get page instance

// Fetch FAQs from the backend
const fetchFaqs = async () => {
    try {
        const currentLocale = page.props.locale || 'en'; // Get current locale from Inertia props, fallback to 'en'
        const response = await axios.get(`/api/faqs?locale=${currentLocale}`);
        faqs.value = response.data;

        // Set the first item as the default open value if FAQs exist
        if (faqs.value.length > 0) {
            defaultValue.value = `item-${faqs.value[0].id}`;
        }
    } catch (error) {
        console.error("Error fetching FAQs:", error);
    } finally {
        isLoading.value = false; // Hide loader after fetching data
    }
};

// Load FAQs when component is mounted
onMounted(fetchFaqs);

// Watch for locale changes and re-fetch FAQs
watch(() => page.props.locale, (newLocale, oldLocale) => {
    if (newLocale !== oldLocale) {
        isLoading.value = true;
        fetchFaqs();
    }
});
</script>

<template>
    <section class="home-section home-section--light faq-section">
        <div class="full-w-container relative z-10 faq-split">
            <!-- Title Section -->
            <div class="faq-intro">
                <span class="faq-title">FAQ's</span>
                <p class="faq-subtitle">
                    {{ _t('common','faqs_description') }}
                </p>
            </div>

            <!-- FAQ Section -->
            <div class="faq-list">
                <template v-if="isLoading">
                    <!-- Skeleton loader for FAQs -->
                    <div class="w-full flex flex-col gap-6">
                        <div v-for="i in 4" :key="i" class="bg-white px-6 rounded-[16px] p-8 shadow-lg">
                            <Skeleton class="h-[2rem] w-full rounded-md mb-4" />
                            <Skeleton class="h-[1.5rem] w-full rounded-md mb-2" />
                            <Skeleton class="h-[1.5rem] w-3/4 rounded-md" />
                        </div>
                    </div>
                </template>

                <template v-else>
                    <Accordion type="single" class="w-full flex flex-col gap-6"
                        collapsible :default-value="defaultValue">
                        <AccordionItem
                            class="bg-white px-6 rounded-[16px] shadow-lg transition-transform duration-300 hover:-translate-y-1"
                            v-for="faq in faqs"
                            :key="faq.id"
                            :value="`item-${faq.id}`"
                        >
                            <AccordionTriggerVariant class="text-[1.35rem] text-customPrimaryColor max-[768px]:text-[1rem] max-[768px]:text-left">
                                {{ faq.question }}
                            </AccordionTriggerVariant>
                            <AccordionContent class="text-[1.25rem] text-customLightGrayColor max-[768px]:text-[0.95rem]">
                                {{ faq.answer }}
                            </AccordionContent>
                        </AccordionItem>
                    </Accordion>
                </template>
            </div>
        </div>
    </section>
</template>

<style scoped>
.faq-split {
    display: flex;
    gap: 3rem;
    align-items: flex-start;
}

.faq-intro {
    flex: 0 0 32%;
    display: flex;
    flex-direction: column;
    gap: 1.2rem;
    position: sticky;
    top: 120px;
}

.faq-title {
    font-size: clamp(2.2rem, 4vw, 3rem);
    font-weight: 700;
    color: #0f172a;
}

.faq-subtitle {
    font-size: 1.05rem;
    color: #64748b;
    line-height: 1.7;
}

.faq-list {
    flex: 1 1 68%;
}

@media (max-width: 1024px) {
    .faq-split {
        flex-direction: column;
    }

    .faq-intro {
        position: static;
        width: 100%;
    }
}

[data-state="closed"] {
  max-height: fit-content;
}
</style>
