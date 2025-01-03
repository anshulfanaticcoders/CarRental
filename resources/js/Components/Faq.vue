<script setup lang="ts">
import {
    Accordion,
    AccordionContent,
    AccordionItem,
} from "@/Components/ui/accordion";
import AccordionTriggerVariant from "./ui/accordion/AccordionTriggerVariant.vue";

const defaultValue = "item-1";

const accordionItems = [
    {
        value: "item-1",
        title: "Why is Webflow the best nocode tool?",
        content:
            "Ut enim ad minim veniam quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat aute irure dolor",
    },
    {
        value: "item-2",
        title: "When did Webflow was founded?",
        content:
            "Explore our diverse selection of high-end vehicles, choose your preferred pickup and return dates, and select a location that best fits your needs",
    },
    {
        value: "item-3",
        title: "Book And Enjoy",
        content:
            "Explore our diverse selection of high-end vehicles, choose your preferred pickup and return dates, and select a location that best fits your needs",
    },
];
</script>

<template>
    <div class="container min-h-[80vh] bg-[#153B4F1A] rounded-[20px] py-customVerticalSpacing flex flex-col gap-16">
     <div class="flex  items-center justify-center ">
        <div class="column w-[573px] flex flex-col gap-5 text-center">
            <span class="text-customPrimaryColor text-[3rem] font-bold">FAQ's</span>
        <p class="text-customPrimaryColor text-[1.25rem]"> From luxury sedans to budget-friendly compacts, we've got something for every journey</p>
        </div>
     </div>
     <div class="column px-[4rem]">
        <Accordion
        type="single"
        class="w-full grid grid-cols-2 gap-10"
        collapsible
        :default-value="defaultValue"
    >
        <AccordionItem
        class="bg-white p-6 rounded-[16px]"
            v-for="item in accordionItems"
            :key="item.value"
            :value="item.value"
        >
            <AccordionTriggerVariant class="text-[1.35rem] text-customPrimaryColor">{{
                item.title
            }}</AccordionTriggerVariant>
            <AccordionContent
                class="text-[1.25rem] text-customMediumBlackColor"
            >
                {{ item.content }}
            </AccordionContent>
        </AccordionItem>
    </Accordion>
     </div>
    </div>
</template>

<style>

</style>
