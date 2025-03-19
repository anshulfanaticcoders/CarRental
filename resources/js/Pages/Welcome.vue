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
import calendarIcon from '../../assets/CalendarBlank.svg';
import whiteGoIcon from '../../assets/whiteGoIcon.svg';
import calendarWhiteIcon from '../../assets/CalendarWhite.svg';

const plugin = Autoplay({
    delay: 3000,
    stopOnMouseEnter: true,
    stopOnInteraction: false,
});
const categoryAutoplay = Autoplay({
    delay: 3000,
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
    blogs: Array
});


const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};
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
        <section class="hero_section max-[768px]:bg-customPrimaryColor">
            <div class="wrapper flex justify-between w-full
            max-[768px]:flex-col">
                <div
                    class="column bg-customPrimaryColor h-[65vh] w-full text-white flex flex-col items-end justify-center
                     max-[768px]:h-auto max-[768px]:px-[1.5rem] max-[768px]:py-[1.5rem]">
                    <div class="pl-[10%] max-[768px]:pl-[0]">
                        <h1>Hit the Road with the Perfect Ride</h1>
                        <p class="text-[1.25rem] mt-3 max-w-[450px] max-[768px]:text-[1rem]">
                            Get a car wherever and whenever you need it
                            with your iOS or Android device.
                        </p>
                    </div>
                </div>
                <div class="column h-[80vh] w-full relative max-[768px]:h-auto max-[768px]:pb-[2rem] max-[768px]:px-[1.5rem]">
                    <img class="rounded-bl-[20px] h-full w-full object-cover max-[768px]:rounded-[20px]" :src="heroImg" alt="" />
                    <div class="bg-customOverlayColor absolute top-0 w-full h-full rounded-bl-[20px]"></div>
                </div>
            </div>
        </section>


        <section class="mt-[-14rem] mb-[12rem] max-[768px]:mb-[0] max-[768px]:mt-[-1rem] max-[768px]:pt-[2rem] max-[768px]:bg-customPrimaryColor">
            <SearchBar />
        </section>

        <section
            class="ml-[5%] max-[768px]:ml-0 w-[105%] max-[768px]:w-full category-carousel mt-[8rem] min-h-[50vh] py-customVerticalSpacing overflow-hidden
            max-[768px]:mt-0 ">
            <div class="flex min-h-[inherit] items-center gap-24 
            max-[768px]:flex-col max-[768px]:gap-10 max-[768px]:items-start">
                <div class="column max-[768px]:px-[1.5rem]">
                    <h2>
                        Our Categories
                    </h2>
                </div>
                <div class="column carousel rounded-[20px] p-6 max-[768px]:rounded-none" style="
                        background: linear-gradient(
                            90deg,
                            rgba(21, 59, 79, 0.2) 0%,
                            rgba(21, 59, 79, 0) 94.4%
                        );
                    ">
                    <Carousel class="relative w-full max-[768px]:h-[20rem]" :opts="{ align: 'start' }"
                    :plugins="[categoryAutoplay]" @mouseenter="categoryAutoplay.stop" @mouseleave="[categoryAutoplay.reset(), categoryAutoplay.play()]">
                        <CarouselContent>
                            <CarouselItem v-for="category in categories" :key="category.id"
                                class="md:basis-1/2 lg:basis-1/3">
                                <div class="p-1">
                                    <Link :href="`/s?category_id=${encodeURIComponent(category.id)}`">
                                    <Card class="bg-transparent shadow-none border-none">
                                        <CardContent
                                            class="cardContent flex h-[515px] max-[768px]:h-[20rem] items-center justify-center p-6 relative">
                                            <img class="rounded-[20px] h-full w-full object-cover"
                                                :src="`${category.image}`" alt="" />
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
        <section class="flex flex-col gap-10 py-customVerticalSpacing popular-places max-[768px]:py-[1rem]">
            <div class="column ml-[5%]">
                <span class="text-[1.15rem] text-customPrimaryColor">-Top Destinations -</span>
                <h3 class="text-customDarkBlackColor mt-[1rem] max-[768px]:text-[1.75rem]">Popular places</h3>
            </div>
            <div class="column max-[768px]:px-[1.5rem]">
                <Carousel class="relative w-full" :plugins="[plugin]" @mouseenter="plugin.stop"
                    @mouseleave="[plugin.reset(), plugin.play(), console.log('Running')]">
                    <CarouselContent>
                        <CarouselItem v-for="place in popularPlaces" :key="place.id"
                            class="pl-1 md:basis-1/2 lg:basis-1/5">
                            <div class="p-1">
                                <Link
                                :href="`/s?where=${encodeURIComponent(`${place.place_name}, ${place.city}, ${place.country}`)}&latitude=${place.latitude}&longitude=${place.longitude}&radius=10000`">
                                <Card class="h-[18rem] border-0 rounded-[0.75rem]">
                                    <CardContent class="flex flex-col gap-2 justify-center px-1 h-full">
                                        <img :src="`${place.image}`" alt=""
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
                <div class="column grid grid-cols-3 gap-16
                max-[768px]:grid-cols-1">
                    <div class="col flex flex-col gap-10">
                        <div class="info-card flex gap-5 items-start">
                            <img :src="locationMapIcon" alt="" />
                            <div class="flex flex-col gap-3">
                                <span class="text-[1.5rem] text-customDarkBlackColor font-medium  max-[768px]:text-[1.25rem]">Convenient
                                    Locations</span>
                                <p class="text-customLightGrayColor text-[1.15rem]  max-[768px]:text-[0.95rem]">
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
                                <span class="text-[1.5rem] text-customDarkBlackColor font-medium  max-[768px]:text-[1.25rem]">Fast and Easy Booking
                                    Process</span>
                                <p class="text-customLightGrayColor text-[1.15rem]  max-[768px]:text-[0.95rem]">
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
                                <span class="text-[1.5rem] text-customDarkBlackColor font-medium  max-[768px]:text-[1.25rem]">Modern Fleet with the
                                    Latest
                                    Technology</span>
                                <p class="text-customLightGrayColor text-[1.15rem]  max-[768px]:text-[0.95rem]">
                                    Select your desired pickup and return dates,
                                    along with the time.
                                </p>
                            </div>
                        </div>
                        <div class="info-card flex gap-5 items-start">
                            <img :src="userCoverageIcon" alt="" />
                            <div class="flex flex-col gap-3 ">
                                <span class="text-[1.5rem] text-customDarkBlackColor font-medium  max-[768px]:text-[1.25rem]">Insurance
                                    Coverage</span>
                                <p class="text-customLightGrayColor text-[1.15rem]  max-[768px]:text-[0.95rem]">
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


        <!-- ------------------------Blogs Section-------------------------------- -->
        <!------------------------------ <Start>  -------------------------------------------------->
        <section class="blogs min-h-[80vh] flex flex-col gap-10 items-center py-customVerticalSpacing
        max-[768px]:py-0 max-[768px]:gap-0">
            <div class="column text-center flex flex-col items-center w-[650px] py-8 
            max-[768px]:py-0 max-[768px]:w-full max-[768px]:mb-10">
                <span class="text-[1.25rem] text-customPrimaryColor">-Latest Articles-</span>
                <h3 class="max-w-[883px] text-[3rem] font-bold text-customDarkBlackColor
                max-[768px]:max-w-full max-[768px]:text-[1.5rem]">
                    Stay informed and inspired for your next journey
                </h3>
            </div>

            <!-- Blog Section -->
            <div class="flex gap-6 w-full full-w-container
            max-[768px]:flex-col">
                <!-- First Blog (Large Left) -->
                <div v-if="blogs.length > 0" class="w-1/2 h-[574px] relative rounded-lg overflow-hidden shadow-md blog-container
                    max-[768px]:w-full max-[768px]:h-[380px]">
                    <img :src="blogs[0].image" :alt="blogs[0].title" class="w-full h-full object-cover rounded-lg">

                    <div class="absolute bottom-0 bg-gradient-to-t from-black/70 to-transparent p-4 text-white">
                        <p class="text-[1.25rem] flex items-center gap-1">
                            <img :src=calendarWhiteIcon alt=""> {{ formatDate(blogs[0].created_at) }}
                        </p>
                        <h4 class="font-semibold text-[2rem] max-[768px]:text-[1.25rem]">{{ blogs[0].title }}</h4>
                        <Link :href="route('blog.show', blogs[0].id)" class="inline-flex items-center mt-2 text-blue-400">
                        <img :src=whiteGoIcon alt="">
                        </Link>
                    </div>
                </div>

                <!-- Other Blogs (Stacked Right, Dividing Height) -->
                <div class="flex flex-col gap-6 w-1/2
                max-[768px]:w-full max-[768px]:gap-0">
                    <div v-for="(blog, index) in blogs.slice(1, 4)" :key="blog.id"
                        class="relative rounded-lg h-[175px] flex justify-between gap-5 items-center">

                        <Link :href="route('blog.show', blog.id)" class="w-[30%] h-full blog-container max-[768px]:w-[40%] max-[768px]:h-[120px]">
                            <img :src="blog.image" :alt="blog.title" class="w-full h-full object-cover rounded-lg">
                        </Link>

                        <div class="w-[70%]">
                            <p class="text-sm flex items-center gap-1 text-customLightGrayColor
                            ">
                                <img :src=calendarIcon alt=""> {{ formatDate(blog.created_at) }}
                            </p>
                            <h4 class="font-semibold text-[1.5rem] text-customDarkBlackColor max-[768px]:text-[1rem]">{{ blog.title }}</h4>
                            <Link :href="route('blog.show', blog.id)" class="inline-flex items-center mt-2 text-customPrimaryColor">
                                Read Story 
                                <img :src=goIcon alt="" class="w-[1.5rem] ml-[0.75rem]">
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <Link href="/blogs-page" class="button-secondary text-center w-[10rem] mt-6 hover:bg-customPrimaryColor hover:text-white">More</Link>
        </section>


        <!------------------------------ <Ends>  -------------------------------------------------->


        <!-- ------------------------FAQ Section-------------------------------- -->
        <!------------------------------ <Start>  -------------------------------------------------->
        <section class="my-customVerticalSpacing">
            <Faq />
        </section>
        <!-- ---------------------------<End>---------------------------------------------------->
    </main>

    <Footer/>
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

.popular-places button {
    display: none;
}
.blog-container > img {
    transition: transform 0.3s ease-in-out;
}

.blog-container:hover > img {
    transform: scale(1.1);
    cursor: pointer;
}

.category-carousel .disabled\:pointer-events-none:disabled{
 pointer-events: unset;
}

@media screen and (max-width:768px) {
    .category-carousel .next-btn{
        right: 10%!important;
        display: none;
        
    }
    .category-carousel .prev-btn{
        left: -4%!important;
        display: none;
    }
}
</style>
