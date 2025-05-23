<script setup>
import { ref, onMounted } from "vue";
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
</script>

<template>
    <div class="full-w-container min-h-[80vh] bg-[#153B4F1A] rounded-[20px] py-customVerticalSpacing flex flex-col gap-16
        max-[768px]:w-full max-[768px]:rounded-none">
        
        <!-- Title Section -->
        <div class="flex items-center justify-center">
            <div class="column w-[573px] max-[768px]:text-[2rem] flex flex-col gap-5 text-center max-[768px]:px-[1.5rem]">
                <span class="text-customPrimaryColor text-[3rem] font-bold max-[768px]:text-[2rem]">FAQ's</span>
                <p class="text-customPrimaryColor text-[1.25rem] max-[768px]:text-[1rem]">
                    {{ _t('common','faqs_description') }}
                </p>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="column px-[4rem] max-[768px]:px-[1.5rem]">
            <template v-if="isLoading">
                <!-- Skeleton loader for FAQs -->
                <div class="w-full grid grid-cols-2 gap-10 max-[768px]:grid-cols-1">
                    <div v-for="i in 4" :key="i" class="bg-white px-6 rounded-[16px] p-10">
                        <Skeleton class="h-[2rem] w-full rounded-md mb-4" />
                        <Skeleton class="h-[1.5rem] w-full rounded-md mb-2" />
                        <Skeleton class="h-[1.5rem] w-3/4 rounded-md" />
                    </div>
                </div>
            </template>

            <template v-else>
                <Accordion type="single" class="w-full grid grid-cols-2 gap-10 max-[768px]:grid-cols-1"
                    collapsible :default-value="defaultValue">
                    <AccordionItem
                        class="bg-white px-6 rounded-[16px]"
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
</template>

<style scoped>
[data-state="closed"] {
  max-height: fit-content;
}
</style>
