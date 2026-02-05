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
import axios from "axios";
import { usePage } from '@inertiajs/vue3';

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

// Data ref to hold testimonials
const testimonials = ref([]);
const loading = ref(true);
const error = ref(null);

const fetchTestimonials = async () => {
    try {
        loading.value = true;
        const response = await axios.get('/api/testimonials/frontend');
        testimonials.value = response.data.testimonials;
    } catch (err) {
        error.value = _p('testimonials_error'); // Use translation key
        console.error("Error fetching testimonials:", err);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchTestimonials();
});

const plugin = Autoplay({
    delay: 6000,
    stopOnMouseEnter: true,
    stopOnInteraction: false,
});
</script>

<template>
    <section
        class="testimonials home-section home-section--dark">
        <div class="full-w-container relative z-10 flex flex-col gap-10 items-center max-[768px]:px-[0.5rem] pb-16">
            <div class="column text-center flex flex-col items-center text-white w-[573px] py-[2rem]
         max-[768px]:w-full">
                <span class="text-[1.25rem] text-slate-200 max-[768px]:mb-5">- {{ _p('testimonials_title') }} -</span>
                <h3 class="max-w-[883px] max-[768px]:max-w-full">
                    {{ _p('testimonials_subtitle') }}
                </h3>
            </div>

            <!-- Loading state -->
            <div v-if="loading" class="flex justify-center items-center p-8">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-white"></div>
            </div>

            <!-- Error state -->
            <div v-else-if="error" class="text-red-500 p-8 text-center">
                {{ error }}
            </div>

            <!-- No testimonials -->
            <div v-else-if="testimonials.length === 0" class="text-white p-8 text-center">
                {{ _p('testimonials_no_data') }}
            </div>

            <!-- Testimonials carousel -->
            <Carousel v-else class="relative w-full" :plugins="[plugin]" 
                @mouseenter="plugin.stop" 
                @mouseleave="[plugin.reset(), plugin.play()]" 
                :slides-to-show="3">
                <CarouselContent class="max-[768px]:mx-0 px-[1rem] max-[768px]:px-0">
                    <CarouselItem v-for="(testimonial, index) in testimonials" :key="index"
                        class="pl-1 md:basis-1/2 lg:basis-1/4">
                        <div class="p-1">
                            <Card class='h-[19rem] shadow-xl'>
                                <CardContent class="flex h-full flex-col aspect-square justify-center p-6 gap-10 w-full">
                                    <div class="column p-4 border-b border-customMediumBlackColor">
                                        <span v-for="star in Math.floor(testimonial.rating)" :key="star"
                                            class="text-yellow-500 text-[1.5rem]">★</span>
                                        <span v-if="testimonial.rating % 1 !== 0"
                                            class="text-yellow-500 text-[1.5rem]">☆</span>
                                        <p class="text-[1.125rem]">
                                            {{ testimonial.comment }}
                                        </p>
                                    </div>
                                    <div class="column flex gap-4 items-center">
                                        <div class="col">
                                            <img :src="testimonial.avatar || '/assets/default-avatar.jpg'" alt="" class="h-16 w-16 rounded-full object-cover" />
                                        </div>
                                        <div class="col flex flex-col gap-1">
                                            <strong>{{ testimonial.author }}</strong>
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
    </section>
</template>

<style>
.testimonials button {
    top: 130% !important;
}

.testimonials .prev-btn {
    left: 45% !important;
    background-color: white;
}

.testimonials .next-btn {
    right: 45% !important;
    background-color: white;
}

.testimonials .prev-btn svg,
.testimonials .next-btn svg {
    color: #2b2b2b !important;
}

.testimonials {
    position: relative;
}

.testimonials::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image: linear-gradient(transparent 93%, rgba(235, 198, 122, 0.25) 94%),
        linear-gradient(90deg, transparent 93%, rgba(235, 198, 122, 0.2) 94%);
    background-size: 70px 70px;
    opacity: 0.35;
    pointer-events: none;
    mask-image: radial-gradient(circle at 50% 40%, rgba(0, 0, 0, 0.9), transparent 70%);
}

@media screen and (max-width:768px) {
    .testimonials button {
        top: 100% !important;
        margin-bottom: 16px;
    }

    .testimonials .prev-btn {
        left: 50% !important;
        transform: translateX(-56px);
        background-color: white;
    }

    .testimonials .next-btn {
        left: 50% !important;
        transform: translateX(16px);
        background-color: white;
    }
}
</style>
