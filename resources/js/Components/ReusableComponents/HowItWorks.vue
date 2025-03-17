<script setup>
import gridLines from "../../../assets/gridLines.png";
import gridLinesForegroundImage from "../../../assets/gridLineFoegroundimage.jpeg";
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from "@/Components/ui/accordion";

const defaultValue = "item-1";

const accordionItems = [
    {
        value: "item-1",
        title: "Browse And Select",
        content:
            "Explore our diverse selection of high-end vehicles, choose your preferred pickup and return dates, and select a location that best fits your needs",
    },
    {
        value: "item-2",
        title: "Book And Confirm",
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
    <section
        class="how-it-works bg-contain bg-no-repeat mt-[4rem] max-[768px]:mt-0"
        :style="{ backgroundImage: `url(${gridLines})` }"
    >
        <div
            class="full-w-container flex justify-between gap-20 h-[100vh] py-16 items-center
            max-[768px]:flex-col max-[768px]:h-auto max-[768px]:pb-0"
        >
            <div
                class="column bg-cover bg-center rounded-[200px] h-full w-[35%]
                max-[768px]:w-full max-[768px]:h-[450px]"
                :style="{ backgroundImage: `url(${gridLinesForegroundImage})` }"
            ></div>
            <div class="column flex flex-col gap-10 w-[55%]
            max-[768px]:w-full">
                <div class="col flex flex-col gap-5">
                    <span class="text-cutomPrimaryColor text-[1.25rem]  max-[768px]:text-[1rem]">-How it Works -</span>
                    <h3 class="">Treamlined processes for a hassle-free experience</h3>
                    <p class='text-[1.25rem] text-customLightGrayColor  max-[768px]:text-[1rem]'>
                        Our streamlined process ensures a seamless car rental
                        experience from start to finish. With easy online
                        booking, flexible pick-up and drop-off options.
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
                            <AccordionTrigger class="text-[1.5rem] text-customPrimaryColor max-[768px]:mb-2 max-[768px]:text-[1rem]">{{
                                item.title
                            }}</AccordionTrigger>
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

<style></style>
