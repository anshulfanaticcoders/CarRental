<script setup>
import { Head, Link } from "@inertiajs/vue3";
import heroImg from "../../assets/heroImage.jpeg";
import Footer from '@/Components/Footer.vue'
import locationMapIcon from "../../assets/location.svg";
import chipIcon from "../../assets/chip.svg";
import phoneIcon from "../../assets/phone.svg";
import userCoverageIcon from "../../assets/usercoverage.svg";
import carImage from "../../assets/carImagebgrmoved.jpeg";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import HowItWorks from "@/Components/ReusableComponents/HowItWorks.vue";
import SearchBar from "@/Components/SearchBar.vue";
import goIcon from "../../assets/goIcon.svg";
import Autoplay from 'embla-carousel-autoplay';
const plugin = Autoplay({
    delay: 2000,
    stopOnMouseEnter: true,
    stopOnInteraction: false,
});

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    categories: {
        type: Array,
        default: () => []
    },
});
import {
    Carousel,
    CarouselContent,
    CarouselItem,
    CarouselNext,
    CarouselPrevious,
} from "@/Components/ui/carousel";
import { onMounted, ref } from "vue";
import Card from "@/Components/ui/card/Card.vue";
import CardContent from "@/Components/ui/card/CardContent.vue";
import Testimonials from "@/Components/Testimonials.vue";
import Faq from "@/Components/Faq.vue";


// Category Carousel
const categories = ref([]);
const fetchCategories = async () => {
    try {
        const response = await axios.get("/api/vehicle-categories");
        categories.value = response.data; // Store the fetched categories
    } catch (error) {
        console.error("Error fetching vehicle categories:", error);
    }
};
// Fetch categories on component mount
onMounted(() => {
    fetchCategories();
});


// Popular Places data fetch
const popularPlaces = ref([]);
const fetchPopularPlaces = async () => {
    try {
        const response = await axios.get("/api/popular-places"); // Adjust the API route as needed
        popularPlaces.value = response.data;
    } catch (error) {
        console.error("Error fetching popular places:", error);
    }
};

onMounted(() => {
    fetchPopularPlaces();
});
</script>

<template>

    <Head title="Welcome" />

    <AuthenticatedHeaderLayout />

    <main class="overflow-x-hidden">
        <section class="hero_section">
            <div class="wrapper flex justify-between w-full">
                <div
                    class="column bg-customPrimaryColor h-[65vh] w-full text-white flex flex-col items-end justify-center">
                    <div class="pl-[10%]">
                        <h1>Hit the Road with the Perfect Ride</h1>
                        <p class="text-[1.25rem] mt-3">
                            Get a car wherever and whenever you need it <br />
                            with your iOS or Android device.
                        </p>
                    </div>
                </div>
                <div class="column h-[80vh] w-full relative">
                    <img class="rounded-bl-[20px] h-full w-full object-cover" :src="heroImg" alt="" />
                    <div class="bg-customOverlayColor absolute top-0 w-full h-full rounded-bl-[20px]"></div>
                </div>
            </div>
        </section>


        <section class="mt-[-14rem] mb-[12rem]">
            <SearchBar />
        </section>

        <section
            class="ml-[5%] w-[105%] category-carousel mt-[8rem] min-h-[50vh] py-customVerticalSpacing overflow-hidden">
            <div class="flex min-h-[inherit] items-center gap-24">
                <div class="column">
                    <h2>
                        Our <br />
                        Categories
                    </h2>
                </div>
                <div class="column carousel rounded-[20px] p-6" style="
                        background: linear-gradient(
                            90deg,
                            rgba(21, 59, 79, 0.2) 0%,
                            rgba(21, 59, 79, 0) 94.4%
                        );
                    ">
                    <Carousel class="relative w-full" :opts="{ align: 'start' }">
                        <CarouselContent>
                            <CarouselItem v-for="category in categories" :key="category.id"
                                class="md:basis-1/2 lg:basis-1/3">
                                <div class="p-1">
                                    <Link :href="`/categories/${category.id}`">
                                    <Card>
                                        <CardContent
                                            class="cardContent flex h-[515px] items-center justify-center p-6 relative">
                                            <img class="rounded-[20px] h-full w-full object-cover"
                                                :src="`/storage/${category.image}`" alt="" />
                                            <div
                                                class="category_name absolute bottom-10 left-0 flex justify-between w-full px-8">
                                                <span class="text-white text-[2rem] font-semibold">{{ category.name
                                                    }}</span>
                                                <img class="" :src="goIcon" alt="" />
                                            </div>
                                        </CardContent>
                                    </Card>
                                    </Link>
                                </div>
                            </CarouselItem>
                        </CarouselContent>
                        <CarouselPrevious />
                        <CarouselNext />
                    </Carousel>
                </div>
            </div>
        </section>

        <!------------------------------- Top Destination Places -------------------------------------->
        <section class="flex flex-col gap-10 py-customVerticalSpacing popular-places">
            <div class="column ml-[5%]">
                <span class="text-[1.15rem] text-customPrimaryColor">-Top Destinations -</span>
                <h3 class="text-customDarkBlackColor mt-[1rem]">Popular places</h3>
            </div>
            <div class="column">
                <Carousel class="relative w-full" :plugins="[plugin]" @mouseenter="plugin.stop"
                    @mouseleave="[plugin.reset(), plugin.play(), console.log('Running')]">
                    <CarouselContent>
                        <CarouselItem v-for="place in popularPlaces" :key="place.id"
                            class="pl-1 md:basis-1/2 lg:basis-1/5">
                            <div class="p-1">
                                <Link :href="`/s?where=${encodeURIComponent(place.place_name)}&latitude=${place.latitude}&longitude=${place.longitude}&radius=10000`">
                                <Card class="h-[18rem] border-0 rounded-[0.75rem]">
                                    <CardContent class="flex flex-col gap-2 justify-center px-1 h-full">
                                        <img :src="`/storage/${place.image}`" alt=""
                                            class="rounded-[0.75rem] h-[12rem] w-full object-cover mb-2" />
                                        <div class="px-3">
                                            <h3 class="text-lg font-medium">
                                                {{ place.place_name }}
                                            </h3>
                                            <p class="text-sm text-customDarkBlackColor">
                                                {{ place.city }}
                                            </p>
                                        </div>
                                    </CardContent>
                                </Card>
                            </Link>
                            </div>
                        </CarouselItem>
                    </CarouselContent>
                    <CarouselPrevious />
                    <CarouselNext />
                </Carousel>
            </div>
        </section>
        <!------------------------------ <Start>  -------------------------------------------------->
        <!------------------------------ <End>  -------------------------------------------------->




        <!------------------------------- WHY CHOOSE US -------------------------------------->
        <!------------------------------ <Start>  -------------------------------------------------->
        <section class="py-customVerticalSpacing">
            <div class="full-w-container flex flex-col gap-16">
                <div class="column text-center flex flex-col gap-5 items-center">
                    <span class="text-[1.25rem] text-customPrimaryColor">-Why Choose Us-</span>
                    <h3 class="max-w-[883px] text-customDarkBlackColor">
                        From luxury sedans to budget-friendly compacts, we've
                        got something for every journey
                    </h3>
                </div>
                <div class="column grid grid-cols-3 gap-16">
                    <div class="col flex flex-col gap-10">
                        <div class="info-card flex gap-5 items-start">
                            <img :src="locationMapIcon" alt="" />
                            <div class="flex flex-col gap-3">
                                <span class="text-[1.5rem] text-customDarkBlackColor font-medium">Convenient
                                    Locations</span>
                                <p class="text-customLightGrayColor text-[1.15rem]">
                                    With multiple rental locations at airports,
                                    city centers, and popular destinations,
                                    picking up and dropping off your rental is
                                    quick and easy.
                                </p>
                            </div>
                        </div>
                        <div class="info-card flex gap-5 items-start">
                            <img :src="phoneIcon" alt="" />
                            <div class=" flex flex-col gap-3">
                                <span class="text-[1.5rem] text-customDarkBlackColor font-medium">Fast and Easy Booking
                                    Process</span>
                                <p class="text-customLightGrayColor text-[1.15rem]">
                                    Select your desired pickup and return dates,
                                    along with the time.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col flex justify-center">
                        <img class="rounded-[20px] h-full object-cover" :src="carImage" alt=""
                            style="clip-path: polygon(30% 0%, 70% 0%, 100% 30%, 100% 70%, 70% 100%, 30% 100%, 0% 70%, 0% 30%);" />
                    </div>
                    <div class="col flex flex-col gap-10">
                        <div class="info-card flex gap-5 items-start">
                            <img :src="chipIcon" alt="" />
                            <div class=" flex flex-col gap-3">
                                <span class="text-[1.5rem] text-customDarkBlackColor font-medium">Modern Fleet with the
                                    Latest
                                    Technology</span>
                                <p class="text-customLightGrayColor text-[1.15rem]">
                                    Select your desired pickup and return dates,
                                    along with the time.
                                </p>
                            </div>
                        </div>
                        <div class="info-card flex gap-5 items-start">
                            <img :src="userCoverageIcon" alt="" />
                            <div class="flex flex-col gap-3 ">
                                <span class="text-[1.5rem] text-customDarkBlackColor font-medium">Insurance
                                    Coverage</span>
                                <p class="text-customLightGrayColor text-[1.15rem]">
                                    Select your desired pickup and return dates,
                                    along with the time.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!------------------------------ <End>  -------------------------------------------------->

        <!------------------------------- How It Works -------------------------------------->
        <!------------------------------ <Start>  -------------------------------------------------->
        <HowItWorks />
        <!------------------------------ <End>  -------------------------------------------------->


        <!-- ------------------------Testimonials Section-------------------------------- -->
        <!------------------------------ <Start>  -------------------------------------------------->
        <section class="py-customVerticalSpacing">
            <Testimonials />
        </section>
        <!-- ---------------------------<End>---------------------------------------------------->


        <!-- ------------------------FAQ Section-------------------------------- -->
        <!------------------------------ <Start>  -------------------------------------------------->
        <section class="my-customVerticalSpacing">
            <Faq />
        </section>
        <!-- ---------------------------<End>---------------------------------------------------->
    </main>

    <Footer />
</template>

<style>
.bg-dots-darker {
    background-image: url("data:image/svg+xml,%3Csvg width='30' height='30' viewBox='0 0 30 30' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1.22676 0C1.91374 0 2.45351 0.539773 2.45351 1.22676C2.45351 1.91374 1.91374 2.45351 1.22676 2.45351C0.539773 2.45351 0 1.91374 0 1.22676C0 0.539773 0.539773 0 1.22676 0Z' fill='rgba(0,0,0,0.07)'/%3E%3C/svg%3E");
}

@media (prefers-color-scheme: dark) {
    .dark\:bg-dots-lighter {
        background-image: url("data:image/svg+xml,%3Csvg width='30' height='30' viewBox='0 0 30 30' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1.22676 0C1.91374 0 2.45351 0.539773 2.45351 1.22676C2.45351 1.91374 1.91374 2.45351 1.22676 2.45351C0.539773 2.45351 0 1.91374 0 1.22676C0 0.539773 0.539773 0 1.22676 0Z' fill='rgba(255,255,255,0.07)'/%3E%3C/svg%3E");
    }
}

.carousel .cardContent {
    padding: 0rem;
}

.category-carousel .next-btn {
    right: 15% !important;
}
.popular-places button{
  display: none;
}
</style>
