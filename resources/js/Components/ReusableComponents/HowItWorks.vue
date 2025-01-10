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
        class="how-it-works bg-contain bg-no-repeat mt-[4rem]"
        :style="{ backgroundImage: `url(${gridLines})` }"
    >
        <div
            class="container grid grid-cols-2 gap-20 min-h-[100vh] py-16 items-center"
        >
            <div
                class="column bg-cover bg-center rounded-[200px] h-full"
                :style="{ backgroundImage: `url(${gridLinesForegroundImage})` }"
            ></div>
            <div class="column flex flex-col gap-10">
                <div class="col">
                    <span>-How it Works -</span>
                    <h3>Treamlined processes for a hassle-free experience</h3>
                    <p>
                        Our streamlined process ensures a seamless car rental
                        experience from start to finish. With easy online
                        booking, flexible pick-up and drop-off options.
                    </p>
                </div>
                <div class="col">
                    <Accordion
                        type="single"
                        class="w-full"
                        collapsible
                        :default-value="defaultValue"
                    >
                        <AccordionItem
                            v-for="item in accordionItems"
                            :key="item.value"
                            :value="item.value"
                        >
                            <AccordionTrigger>{{
                                item.title
                            }}</AccordionTrigger>
                            <AccordionContent>
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
