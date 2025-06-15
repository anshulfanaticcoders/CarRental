<script setup>
import gridLines from "../../../assets/gridLines.png";
import gridLinesForegroundImage from "../../../assets/gridLineFoegroundimage.jpeg";
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from "@/Components/ui/accordion";
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

// Access page props
const page = usePage();

// Translation helpers
const __ = (key, replacements = {}) => {
    const translations = page.props.translations?.messages || {};
    let translation = translations[key] || key;
    
    // Replace placeholders if any
    Object.keys(replacements).forEach(k => {
        translation = translation.replace(`:${k}`, replacements[k]);
    });
    
    return translation;
};

const _p = (key, replacements = {}) => {
    const translations = page.props.translations?.homepage || {};
    let translation = translations[key] || key;
    
    // Replace placeholders if any
    Object.keys(replacements).forEach(k => {
        translation = translation.replace(`:${k}`, replacements[k]);
    });
    
    return translation;
};

const defaultValue = "item-1";

const accordionItems = computed(() => [
    {
        value: "item-1",
        title: _p('how_it_works_step_1_title'), // Use translation key
        content: _p('how_it_works_step_1_content'),
    },
    {
        value: "item-2",
        title: _p('how_it_works_step_2_title'),
        content: _p('how_it_works_step_2_content'),
    },
    {
        value: "item-3",
        title: _p('how_it_works_step_3_title'),
        content: _p('how_it_works_step_3_content'),
    },
]);
</script>

<template>
    <section
        class="how-it-works bg-contain bg-no-repeat mt-[4rem] max-[768px]:mt-0"
        :style="{ backgroundImage: `url(${gridLines})` }"
    >
        <div
            class="full-w-container flex justify-between gap-20 h-[100vh] py-16 items-center
            max-[768px]:flex-col max-[768px]:h-auto max-[768px]:pb-0 max-[768px]:py-0 max-[768px]:gap-10"
        >
            <div
                class="column bg-cover bg-center rounded-[200px] h-full w-[35%]
                max-[768px]:w-full max-[768px]:h-[450px]"
                :style="{ backgroundImage: `url(${gridLinesForegroundImage})` }"
            ></div>
            <div class="column flex flex-col gap-10 w-[55%]
            max-[768px]:w-full">
                <div class="col flex flex-col gap-5">
                    <span class="text-cutomPrimaryColor text-[1.25rem] max-[768px]:text-[1rem]">- {{ _p('how_it_works_title') }} -</span>
                    <h3>{{ _p('how_it_works_subtitle') }}</h3>
                    <p class="text-[1.25rem] text-customLightGrayColor max-[768px]:text-[1rem]">
                        {{ _p('how_it_works_description') }}
                    </p>
                </div>
                <div class="col">
                    <Accordion
                        type="single"
                        class="w-full flex flex-col gap-5"
                        collapsible
                        :default-value="defaultValue"
                    >
                        <AccordionItem
                            v-for="item in accordionItems"
                            :key="item.value"
                            :value="item.value"
                        >
                            <AccordionTrigger class="text-[1.5rem] text-customPrimaryColor max-[768px]:mb-2 max-[768px]:text-[1rem]">
                                {{ item.title }}
                            </AccordionTrigger>
                            <AccordionContent class="text-[1.25rem] text-customLightGrayColor max-[768px]:text-[0.95rem] max-[768px]:leading-6">
                                {{ item.content }}
                            </AccordionContent>
                        </AccordionItem>
                    </Accordion>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
@media screen and (max-width: 768px) {
    .how-it-works {
        background-image: none !important;
    }
}
</style>
