<script setup >
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
        title: "What documents do I need to rent a car?",
        content:
            "You will need a valid driver's license, a government-issued photo ID (e.g., passport or national ID), and a credit or debit card for the security deposit. International renters may also need an International Driving Permit (IDP).",
    },
    {
        value: "item-2",
        title: "Can I rent a car without a credit card?",
        content:
            "Yes, some locations allow debit cards, but additional requirements may apply, such as proof of insurance, return travel plans, or a larger deposit. Please check with the specific branch for details.",
    },
    {
        value: "item-3",
        title: "Is there a minimum or maximum age to rent a car?",
        content:
            "The minimum age is typically 21 years old, but this may vary by location and car type. Renters under 25 may incur a young driver surcharge. There is generally no maximum age, but some restrictions may apply for senior drivers in certain regions.",
    },
    {
        value: "item-4",
        title: "What happens if I return the car late?",
        content:
            "Returning the car late may result in additional charges. Typically, there is a grace period of 30 minutes to 1 hour. Beyond that, you may be charged for an extra day. Please review the terms of your rental agreement for specific policies",
    },
    {
        value: "item-5",
        title: "Are there mileage limits on rentals?",
        content:
            "Most rentals include unlimited mileage, but some car categories (e.g., luxury or specialty vehicles) may have mileage restrictions. Be sure to check your rental agreement for details on mileage limits and additional charges for exceeding them.",
    },
];
</script>

<template>
    <div class="full-w-container min-h-[80vh] bg-[#153B4F1A] rounded-[20px] py-customVerticalSpacing flex flex-col gap-16
    max-[480px]:w-full max-[480px]:rounded-none">
     <div class="flex  items-center justify-center ">
        <div class="column w-[573px] max-[480px]:text-[2rem] flex flex-col gap-5 text-center max-[480px]:px-[1.5rem]">
            <span class="text-customPrimaryColor text-[3rem] font-bold max-[480px]:text-[2rem]">FAQ's</span>
        <p class="text-customPrimaryColor text-[1.25rem] max-[480px]:text-[1rem]"> From luxury sedans to budget-friendly compacts, we've got something for every journey</p>
        </div>
     </div>
     <div class="column px-[4rem] max-[480px]:px-[1.5rem]">
        <Accordion
        type="single"
        class="w-full grid grid-cols-2 gap-10 max-[480px]:grid-cols-1"
        collapsible
        :default-value="defaultValue"
    >
        <AccordionItem
        class="bg-white px-6 rounded-[16px]"
            v-for="item in accordionItems"
            :key="item.value"
            :value="item.value"
        >
            <AccordionTriggerVariant class="text-[1.35rem] text-customPrimaryColor 
            max-[480px]:text-[1rem] max-[480px]:text-left">{{
                item.title
            }}</AccordionTriggerVariant>
            <AccordionContent
                class="text-[1.25rem] text-customLightGrayColor max-[480px]:text-[0.95rem]"
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
