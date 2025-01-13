<script setup>
import { ref, onMounted } from "vue";
import { Card, CardContent } from "@/Components/ui/card";
import {
    Carousel,
    CarouselContent,
    CarouselItem,
    CarouselNext,
    CarouselPrevious,
} from "@/Components/ui/carousel";
import Autoplay from "embla-carousel-autoplay";
import avatarImage from "../../assets/avatar.jpg";
import starIcon from "../../assets/stars.svg";

// Data ref to hold testimonials
const testimonials = ref([
    {
        comment: "This is the best service I've ever used!",
        author: "John Carter",
        title: "CEO at Rento",
        rating: 4.5,
        avatar: avatarImage,
    },
    {
        comment: "Absolutely fantastic experience, highly recommend!",
        author: "Jane Doe",
        title: "Manager at Company",
        rating: 5,
        avatar: avatarImage,
    },
    {
        comment:
            "A game changer for my business! The support team is exceptional.",
        author: "Alice Smith",
        title: "Entrepreneur",
        rating: 4.8,
        avatar: avatarImage,
    },
    {
        comment:
            "I can't believe how easy this was! Will definitely use it again.",
        author: "Michael Brown",
        title: "Freelancer",
        rating: 4.7,
        avatar: avatarImage,
    },
    {
        comment:
            "Exceptional quality and support! Highly satisfied with my experience.",
        author: "Emily Johnson",
        title: "Product Manager",
        rating: 5,
        avatar: avatarImage,
    },
]);

const plugin = Autoplay({
    delay: 2000,
    stopOnMouseEnter: true,
    stopOnInteraction: false,
});
</script>

<template>
    <div
        class="testimonials bg-customPrimaryColor min-h-[90vh] flex flex-col gap-10 items-center py-customVerticalSpacing my-customVerticalSpacing"
    >
        <div class="column text-center flex flex-col items-center text-customPrimaryColor-foreground w-[573px] py-[2rem]">
            <span class="text-[1.25rem]">-Testimonials-</span>
            <h3 class="max-w-[883px]">
                What our customers are saying about us
            </h3>
        </div>
        <Carousel
            class="relative w-full full-w-container"
            :plugins="[plugin]"
            @mouseenter="plugin.stop"
            @mouseleave="
                [plugin.reset(), plugin.play(), console.log('Running')]
            "
            :slides-to-show="3"
        >
            <CarouselContent>
                <CarouselItem
                    v-for="(testimonial, index) in testimonials"
                    :key="index"
                    class="pl-1 md:basis-1/2 lg:basis-1/4"
                >
                    <div class="p-1">
                        <Card class='h-[19rem]'>
                            <CardContent
                                class="flex h-full flex-col aspect-square justify-center p-6 gap-10"
                            >
                                <!-- get testimonials values here -->
                                <div
                                    class="column p-4 border-b border-customMediumBlackColor"
                                >
                                <span v-for="star in Math.floor(testimonial.rating)" :key="star" class="text-yellow-500 text-[1.5rem]">★</span>
                                <span v-if="testimonial.rating % 1 !== 0" class="text-yellow-500 text-[1.5rem]">☆</span>
                                    <p class="text-[1.125rem]">
                                        {{ testimonial.comment }}
                                    </p>
                                </div>
                                <div class="column flex gap-4 items-center">
                                    <div class="col">
                                        <img
                                            :src="testimonial.avatar"
                                            alt=""
                                            class="h-16 w-16"
                                        />
                                    </div>
                                    <div class="col flex flex-col gap-1">
                                        <strong>{{
                                            testimonial.author
                                        }}</strong>
                                        <span>{{ testimonial.title }}</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </CarouselItem>
            </CarouselContent>
            <CarouselPrevious />
            <CarouselNext />
        </Carousel>
    </div>
</template>

<style>
.testimonials button{
 top: 130%!important;
}
.testimonials .prev-btn{
 left: 45%!important;
 background-color: white;

}
.testimonials .next-btn{
 right: 45%!important;
 background-color: white;
}
.testimonials .prev-btn svg,
.testimonials .next-btn svg{
    color: #2b2b2b!important;
}
.testimonials {
    background-image: url('../../assets/gridlinetestimonials.png');
    background-size: cover;
    /* background-position: center center; */
}
</style>
