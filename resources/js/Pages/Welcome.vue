<script setup>
import { Head, Link } from "@inertiajs/vue3";
import SchemaInjector from '@/Components/SchemaInjector.vue'; // Import SchemaInjector
import heroImg from "../../assets/heroImage.jpg";
import FloatingBubbles from '@/Components/FloatingBubbles.vue';
import Footer from '@/Components/Footer.vue'
import locationMapIcon from "../../assets/location.svg";
import chipIcon from "../../assets/chip.svg";
import phoneIcon from "../../assets/phone.svg";
import userCoverageIcon from "../../assets/usercoverage.svg";
import carImage from "../../assets/carImagebgrmoved.jpeg";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import HowItWorks from "@/Components/ReusableComponents/HowItWorks.vue";
import EsimSection from "@/Components/EsimSection.vue";
import SearchBar from "@/Components/SearchBar.vue";
import goIcon from "../../assets/goIcon.svg";
import Autoplay from 'embla-carousel-autoplay';
import calendarIcon from '../../assets/CalendarBlank.svg';
import whiteGoIcon from '../../assets/whiteGoIcon.svg';
import calendarWhiteIcon from '../../assets/CalendarWhite.svg';
import { Skeleton } from '@/Components/ui/skeleton';

const plugin = Autoplay({
    delay: 4000,
    stopOnMouseEnter: true,
    stopOnInteraction: false,
});


const props = defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    blogs: Array,
    testimonials: Array,
    popularPlaces: Array,
    faqs: Array, // Added faqs prop
    schema: Array,
    seoMeta: Object,
    pages: Object,
    heroImage: String, // Dynamic hero image from backend
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
import { computed, onBeforeUnmount, onMounted, ref, defineAsyncComponent } from "vue";
import axios from 'axios';
import { useScrollAnimation } from '@/composables/useScrollAnimation';
import { usePage } from '@inertiajs/vue3';
import Card from "@/Components/ui/card/Card.vue";
import CardContent from "@/Components/ui/card/CardContent.vue";


const Testimonials = defineAsyncComponent(() => import('@/Components/Testimonials.vue'));
const Faq = defineAsyncComponent(() => import('@/Components/Faq.vue'));


// isLoading can be set based on the presence of props.blogs or other data.
const isLoading = ref(!props.blogs || props.blogs.length === 0);

onMounted(() => {
    // If blogs are passed via props, isLoading should be false.
    if (props.blogs && props.blogs.length > 0) {
        isLoading.value = false;
    }
});

const _t = (key) => {
    const { props } = usePage();
    if (props.translations && props.translations.homepage && props.translations.homepage[key]) {
        return props.translations.homepage[key];
    }
    return key;
};

const animatedTagline = computed(() => {
    const tagline = _t('tagline');
    if (!tagline) return '';

    const words = tagline.split(' ');

    if (words.length > 5) {
        words[2] = `<span class="anim-title-word">${words[2]}</span>`;
        words[5] = `<span class="anim-title-word">${words[5]}`;
        words[words.length - 1] = `${words[words.length - 1]}</span>`;
    }

    return words.join(' ').replace(/</g, '<').replace(/>/g, '>');
});

const translatedPhrases = computed(() => [
    _t('typewriter_text_1'),
    _t('typewriter_text_2'),
    _t('typewriter_text_3')
]);

const displayedText = ref('');
let currentPhraseIndex = 0;
let currentCharIndex = 0;
let isDeleting = false;
let timer = null;

const TYPE_SPEED = 70;       // Speed for typing
const DELETE_SPEED = 40;     // Speed for deleting
const DELAY_AFTER_TYPE = 2000;  // Delay after typing complete
const DELAY_AFTER_DELETE = 500; // Delay after deleting

// Function to handle the typing animation
const typeWriter = () => {
    const currentPhrase = translatedPhrases.value[currentPhraseIndex];
    if (isDeleting) {
        // Deleting characters
        displayedText.value = currentPhrase.substring(0, currentCharIndex - 1);
        currentCharIndex--;
        // If deletion is complete
        if (currentCharIndex === 0) {
            isDeleting = false;
            currentPhraseIndex = (currentPhraseIndex + 1) % translatedPhrases.value.length;
            timer = setTimeout(typeWriter, DELAY_AFTER_DELETE);
            return;
        }
        timer = setTimeout(typeWriter, DELETE_SPEED);
    } else {
        // Typing characters
        displayedText.value = currentPhrase.substring(0, currentCharIndex + 1);
        currentCharIndex++;
        // If typing is complete
        if (currentCharIndex === currentPhrase.length) {
            isDeleting = true;
            timer = setTimeout(typeWriter, DELAY_AFTER_TYPE);
            return;
        }
        timer = setTimeout(typeWriter, TYPE_SPEED);
    }
};

onMounted(() => {
    // Start the typing animation
    timer = setTimeout(typeWriter, 500);
});

onBeforeUnmount(() => {
    // Clean up the timer when component is destroyed
    if (timer) clearTimeout(timer);
});


import GreenMotionSearchComponent from "@/Components/GreenMotionSearchComponent.vue";
import AdvertisementSection from "@/Components/AdvertisementSection.vue";

const heroImageSource = computed(() => {
    return props.heroImage ? props.heroImage : heroImg;
});

const page = usePage();

const currentLocale = computed(() => page.props.locale || 'en');
const currentUrl = computed(() => window.location.href);

const seoTranslation = computed(() => {
    if (!props.seoMeta || !props.seoMeta.translations) {
        return {};
    }
    return props.seoMeta.translations.find(t => t.locale === currentLocale.value) || {};
});

const seoTitle = computed(() => {
    return seoTranslation.value.seo_title || props.seoMeta?.seo_title || 'Welcome';
});

const seoDescription = computed(() => {
    return seoTranslation.value.meta_description || props.seoMeta?.meta_description || '';
});

const seoKeywords = computed(() => {
    return seoTranslation.value.keywords || props.seoMeta?.keywords || '';
});

const canonicalUrl = computed(() => {
    return props.seoMeta?.canonical_url || window.location.href;
});

const seoImageUrl = computed(() => {
    return props.seoMeta?.seo_image_url || '';
});

const unifiedLocations = ref([]);

const advertisements = ref([]);

onMounted(async () => {
    // If blogs are passed via props, isLoading should be false.
    if (props.blogs && props.blogs.length > 0) {
        isLoading.value = false;
    }

    try {
        const response = await axios.get('/unified_locations.json');
        unifiedLocations.value = response.data;
    } catch (error) {
        console.error('Error fetching unified locations:', error);
    }

    // Fetch active advertisements via API
    try {
        const adResponse = await axios.get('/api/advertisement');
        if (adResponse.data) {
            // Ensure response is always an array
            advertisements.value = Array.isArray(adResponse.data) ? adResponse.data : [adResponse.data];
        }
    } catch (error) {
        console.error('Error fetching advertisement:', error);
    }
});

const navigateToSearch = (place) => {
    updateSearchUrl(place);
    const searchUrl = sessionStorage.getItem('searchurl');
    if (searchUrl) {
        window.location.href = `/${page.props.locale}${searchUrl}`;
    }
};

const updateSearchUrl = (place) => {
    const location = unifiedLocations.value.find(l => l.name === place.place_name);

    // Set default dates (tomorrow and day after)
    const today = new Date();
    const pickupDate = new Date(today);
    pickupDate.setDate(today.getDate() + 1);
    const returnDate = new Date(pickupDate);
    returnDate.setDate(pickupDate.getDate() + 1);

    // Format date as YYYY-MM-DD for URL params
    const formatUrlDate = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    const params = {
        date_from: formatUrlDate(pickupDate),
        date_to: formatUrlDate(returnDate),
        start_time: '09:00',
        end_time: '09:00',
        age: 35,
    };

    if (location && location.providers && location.providers.length > 0) {
        const provider = location.providers[0];

        // Add location-specific params
        Object.assign(params, {
            where: location.name,
            latitude: location.latitude,
            longitude: location.longitude,
            city: location.city,
            country: location.country,
            provider: provider.provider,
            provider_pickup_id: provider.pickup_id,
            dropoff_location_id: provider.pickup_id, // Same as pickup location
            dropoff_where: location.name, // Display text for dropoff
        });
    } else {
        // Fallback to place_name only
        Object.assign(params, {
            where: place.place_name,
            dropoff_where: place.place_name, // Ensure dropoff is also set
        });
    }

    const urlParams = new URLSearchParams(params).toString();
    sessionStorage.setItem('searchurl', `/s?${urlParams}`);
};



// Animations
useScrollAnimation('.hero_section', '.hero-content', {
    opacity: 0,
    y: -50,
    duration: 1.2,
});

useScrollAnimation('.hero_section', '.anim-title-word', {
    y: 150,
    opacity: 0,
    stagger: 0.05,
    duration: 1,
});

useScrollAnimation('.hero_section', '.hero-image', {
    opacity: 0,
    duration: 1.5,
});

useScrollAnimation('.search-bar-section', '.search-bar-animation', {
    opacity: 0,
    y: 50,
    duration: 1,
});

useScrollAnimation('.why-choose-us-trigger', '.why-choose-us-title', {
    opacity: 0,
    y: 30,
});

useScrollAnimation('.why-choose-us-trigger', '.why-choose-us-card-left', {
    opacity: 0,
    x: -50,
});

useScrollAnimation('.why-choose-us-trigger', '.why-choose-us-image', {
    opacity: 0,
    scale: 0.8,
});

useScrollAnimation('.why-choose-us-trigger', '.why-choose-us-card-right', {
    opacity: 0,
    x: 50,
});

useScrollAnimation('.popular-places-trigger', '.popular-place-card', {
    opacity: 0,
    y: 50,
    stagger: 0.5,
});

// Blog Section Animations
useScrollAnimation('.blogs-trigger', '.blog-title-section', {
    opacity: 0,
    y: 30,
    duration: 1,
});

useScrollAnimation('.blogs-trigger', '.blog-main-image', {
    opacity: 0,
    x: -50,
    duration: 1.2,
});

useScrollAnimation('.blogs-trigger', '.blog-item', {
    opacity: 0,
    x: 50,
    duration: 0.8,
    stagger: 0.2,
});

useScrollAnimation('.blogs-trigger', '.more-button', {
    opacity: 0,
    y: 20,
    duration: 0.6,
});
</script>

<template>

    <Head>
        <title>{{ seoTitle }}</title>
        <meta name="robots" content="index, follow" />
        <meta name="description" :content="seoDescription" />
        <meta name="keywords" :content="seoKeywords" />
        <link rel="canonical" :href="canonicalUrl" />
        <meta property="og:title" :content="seoTitle" />
        <meta property="og:description" :content="seoDescription" />
        <meta property="og:image" :content="seoImageUrl" />
        <meta property="og:url" :content="currentUrl" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="seoTitle" />
        <meta name="twitter:description" :content="seoDescription" />
        <meta name="twitter:image" :content="seoImageUrl" />
    </Head>
    <!-- Inject all schemas passed in the 'schema' array prop -->
    <template v-if="schema && schema.length">
        <SchemaInjector v-for="(individualSchema, index) in schema" :key="`schema-${index}`"
            :schema="individualSchema" />
    </template>

    <!-- Schema for Organization (globally shared, if it exists) -->
    <SchemaInjector v-if="$page.props.organizationSchema" :schema="$page.props.organizationSchema" />

    <AuthenticatedHeaderLayout />

    <main class="overflow-x-hidden">
        <section class="hero_section max-[768px]:bg-customPrimaryColor relative">
            <div class="wrapper flex justify-between w-full
            max-[768px]:flex-col">
                <div class="column bg-customPrimaryColor h-[38rem] w-full text-white flex flex-col items-end justify-center
                     max-[768px]:h-auto max-[768px]:px-[1.5rem] max-[768px]:py-[1.5rem] relative">
                    <FloatingBubbles />
                    <div class="pl-[10%] max-[768px]:pl-0 hero-content relative z-10">
                        <h1 class="anim-title clip-path-anim" v-html="animatedTagline"></h1>
                        <div class="h-16 mt-3 max-[768px]:h-20 flex">
                            <!-- Typewriter text container -->
                            <p class="text-[1.25rem]  max-[768px]:text-[1rem] flex items-center">
                                <span class="typewriter-text">{{ displayedText }}</span>
                                <span class="cursor-blink ml-1"></span>
                            </p>
                        </div>
                    </div>
                </div>
                <div
                    class="column h-[46rem] w-full relative max-[768px]:h-auto max-[768px]:pb-[2rem] max-[768px]:px-[1.5rem] hero-image">
                    <img class="rounded-bl-[20px] h-full w-full object-cover max-[768px]:rounded-[20px]"
                        :src="heroImageSource" alt="Hero Image" />
                    <div class="bg-customOverlayColor absolute top-0 w-full h-full rounded-bl-[20px]"></div>
                </div>
            </div>
        </section>


        <section
            class="mt-[-14rem] mb-[6rem] max-[768px]:mb-[0] max-[768px]:mt-[-1rem] max-[768px]:px-[1.5rem] max-[768px]:pt-[2rem] max-[768px]:bg-customPrimaryColor relative z-10 search-bar-section">
            <SearchBar class="search-bar-animation" :simple="true" />
        </section>


        <!------------------------------- Advertisement Section -------------------------------------->
        <!------------------------------ <Start>  -------------------------------------------------->
        <!-- Advertisement Section -->
        <AdvertisementSection :advertisements="advertisements" :heroImage="heroImageSource" />
        <!------------------------------ <End>  -------------------------------------------------->


        <!------------------------------- Top Destination Places -------------------------------------->
        <section
            class="flex flex-col gap-2 py-customVerticalSpacing popular-places max-[768px]:py-[1rem] max-[768px]:gap-8 popular-places-trigger">
            <div class="column ml-[2%]">
                <span class="text-[1.15rem] text-customPrimaryColor">-{{ _p('top_destinations') }} -</span>
                <h3 class="text-customDarkBlackColor max-[768px]:text-[1.75rem] max-[768px]:mt-[1rem]">{{
                    _p('popular_places') }}</h3>
            </div>
            <div class="column"
                :class="{ 'max-[768px]:px-[1.5rem]': !props.popularPlaces || props.popularPlaces.length > 0 }">
                <Carousel v-if="!props.popularPlaces || props.popularPlaces.length > 0" class="relative w-full"
                    :plugins="[plugin]">
                    <CarouselContent class="pl-10 pt-[2rem] max-[768px]:pr-10 max-[768px]:pl-2 max-[768px]:pt-0">
                        <!-- Show actual places when data is loaded from props -->
                        <template v-if="props.popularPlaces && props.popularPlaces.length > 0">
                            <CarouselItem v-for="place in props.popularPlaces" :key="place.id"
                                class="pl-1 md:basis-1/2 lg:basis-1/5 popular-place-card">
                                <div class="p-1">
                                    <a :href="`/${page.props.locale}/s?where=${encodeURIComponent(place.place_name)}`"
                                        @click.prevent="navigateToSearch(place)">
                                        <Card
                                            class="h-[18rem] border-0 rounded-[0.75rem] transition-all duration-300 hover:mt-[-1rem] max-[768px]:hover:mt-0">
                                            <CardContent class="flex flex-col gap-2 justify-center px-1 h-full">
                                                <img :src="`${place.image}`" :alt="place.place_name"
                                                    class="rounded-[0.75rem] h-[12rem] w-full object-cover mb-2" />
                                                <div class="px-3">
                                                    <h3 class="text-lg font-medium">{{ place.place_name }}</h3>
                                                    <p class="text-sm text-customDarkBlackColor">{{ place.city }}, {{
                                                        place.country }}</p>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    </a>
                                </div>
                            </CarouselItem>
                        </template>



                        <!-- Show skeleton loaders when data is loading (null/undefined) -->
                        <template v-else>
                            <CarouselItem v-for="index in 5" :key="index" class="pl-1 md:basis-1/2 lg:basis-1/5">
                                <div class="p-1">
                                    <Card class="h-[18rem] border-0 rounded-[0.75rem]">
                                        <CardContent class="flex flex-col gap-2 justify-center px-1 h-full">
                                            <Skeleton class="h-[12rem] w-full rounded-xl" />
                                            <div class="space-y-2 px-3">
                                                <Skeleton class="h-4 w-[70%]" />
                                                <Skeleton class="h-4 w-[50%]" />
                                            </div>
                                        </CardContent>
                                    </Card>
                                </div>
                            </CarouselItem>
                        </template>
                    </CarouselContent>

                    <CarouselPrevious v-if="props.popularPlaces && props.popularPlaces.length > 0" />
                    <CarouselNext v-if="props.popularPlaces && props.popularPlaces.length > 0" />
                </Carousel>

                <!-- Full-width "COMING SOON" fallback (outside Carousel) -->
                <div v-else-if="props.popularPlaces && props.popularPlaces.length === 0"
                    class="w-screen relative left-1/2 -ml-[50vw] h-[150px] md:h-[200px] flex items-center justify-center bg-white border-y border-gray-100 overflow-hidden my-0">
                    <h4
                        class="torch-text text-xl md:text-3xl font-black uppercase tracking-[1.5rem] md:tracking-[2.5rem] text-center w-full transform translate-x-[0.75rem] md:translate-x-[1.25rem]">
                        Coming Soon
                    </h4>
                </div>


            </div>
        </section>




        <!------------------------------ <Start>  -------------------------------------------------->
        <!------------------------------ <End>  -------------------------------------------------->




        <!------------------------------- eSIM Section -------------------------------------->
        <!------------------------------ <Start>  -------------------------------------------------->
        <EsimSection />
        <!------------------------------ <End>  -------------------------------------------------->

        <!------------------------------- WHY CHOOSE US -------------------------------------->
        <!------------------------------ <Start>  -------------------------------------------------->
        <section class="py-customVerticalSpacing why-choose-us-trigger">
            <div class="full-w-container flex flex-col gap-16">
                <div class="column text-center flex flex-col gap-5 items-center why-choose-us-title">
                    <span class="text-[1.25rem] text-customPrimaryColor">-{{ _p('why_choose_us') }}-</span>
                    <h3 class="max-w-[883px] text-customDarkBlackColor">
                        {{ _p('why_subtitle') }}
                    </h3>
                </div>
                <div class="column grid grid-cols-3 gap-16
                max-[768px]:grid-cols-1">
                    <div class="col flex flex-col gap-10">
                        <div class="info-card flex gap-5 items-start why-choose-us-card-left">
                            <img :src="locationMapIcon" alt="" />
                            <div class="flex flex-col gap-3">
                                <span
                                    class="text-[1.5rem] text-customDarkBlackColor font-medium  max-[768px]:text-[1.25rem]">{{
                                        _p('convenient_locations') }}</span>
                                <p class="text-customLightGrayColor text-[1.15rem]  max-[768px]:text-[0.95rem]">
                                    {{ _p('convenient_locations_text') }}
                                </p>
                            </div>
                        </div>
                        <div class="info-card flex gap-5 items-start why-choose-us-card-left">
                            <img :src="phoneIcon" alt="" />
                            <div class=" flex flex-col gap-3">
                                <span
                                    class="text-[1.5rem] text-customDarkBlackColor font-medium  max-[768px]:text-[1.25rem]">{{
                                        _p('fast_booking') }}</span>
                                <p class="text-customLightGrayColor text-[1.15rem]  max-[768px]:text-[0.95rem]">
                                    {{ _p('fast_booking_text') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col flex justify-center why-choose-us-image">
                        <img class="rounded-[20px] h-full object-cover" :src="carImage" alt=""
                            style="clip-path: polygon(30% 0%, 70% 0%, 100% 30%, 100% 70%, 70% 100%, 30% 100%, 0% 70%, 0% 30%);" />
                    </div>
                    <div class="col flex flex-col gap-10">
                        <div class="info-card flex gap-5 items-start why-choose-us-card-right">
                            <img :src="chipIcon" alt="" />
                            <div class=" flex flex-col gap-3">
                                <span
                                    class="text-[1.5rem] text-customDarkBlackColor font-medium  max-[768px]:text-[1.25rem]">{{
                                        _p('modern_fleet') }}</span>
                                <p class="text-customLightGrayColor text-[1.15rem]  max-[768px]:text-[0.95rem]">
                                    {{ _p('modern_fleet_text') }}
                                </p>
                            </div>
                        </div>
                        <div class="info-card flex gap-5 items-start why-choose-us-card-right">
                            <img :src="userCoverageIcon" alt="" />
                            <div class="flex flex-col gap-3 ">
                                <span
                                    class="text-[1.5rem] text-customDarkBlackColor font-medium  max-[768px]:text-[1.25rem]">{{
                                        _p('insurance_coverage') }}</span>
                                <p class="text-customLightGrayColor text-[1.15rem]  max-[768px]:text-[0.95rem]">
                                    {{ _p('insurance_coverage_text') }}
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
        <section
            class="blogs min-h-[80vh] flex flex-col gap-10 items-center py-customVerticalSpacing max-[768px]:py-0 max-[768px]:gap-0 blogs-trigger">
            <div
                class="column text-center flex flex-col items-center w-[650px] py-8 max-[768px]:py-0 max-[768px]:w-full max-[768px]:mb-10 blog-title-section">
                <span class="text-[1.25rem] text-customPrimaryColor">-{{ _p('blogs_title') }}-</span>
                <h3
                    class="max-w-[883px] text-[3rem] font-bold text-customDarkBlackColor max-[768px]:max-w-full max-[768px]:text-[1.5rem]">
                    {{ _p('blogs_subtitle') }}
                </h3>
            </div>

            <!-- Blog Section -->
            <div class="flex gap-6 w-full full-w-container max-[768px]:flex-col blog-main-container">
                <!-- First Blog (Large Left) -->
                <Link
                    :href="route('blog.show', { locale: page.props.locale, country: page.props.country || 'us', blog: blogs[0].translated_slug })"
                    v-if="!isLoading && blogs.length > 0"
                    class="w-1/2 h-[574px] relative rounded-lg overflow-hidden shadow-md blog-container blog-main-image max-[768px]:w-full max-[768px]:h-[380px]">
                    <img :src="blogs[0].image" :alt="blogs[0].title" class="w-full h-full object-cover rounded-lg">

                    <div class="absolute bottom-0 bg-gradient-to-t from-black/70 to-transparent p-4 text-white w-full">
                        <p class="text-[1.25rem] flex items-center gap-1">
                            <img :src=calendarWhiteIcon alt=""> {{ formatDate(blogs[0].created_at) }}
                        </p>
                        <h4 class="font-semibold text-[2rem] max-[768px]:text-[1.25rem]">{{ blogs[0].title }}</h4>
                        <Link
                            :href="route('blog.show', { locale: page.props.locale, country: page.props.country || 'us', blog: blogs[0].translated_slug })"
                            class="inline-flex items-center mt-2 text-blue-400">
                            <img :src=whiteGoIcon alt="">
                        </Link>
                    </div>
                </Link>

                <div v-else
                    class="w-1/2 h-[574px] relative rounded-lg overflow-hidden shadow-md blog-container max-[768px]:w-full max-[768px]:h-[380px]">
                    <Skeleton class="h-full w-full rounded-lg" />
                </div>

                <!-- Other Blogs (Stacked Right, Dividing Height) -->
                <div class="flex flex-col gap-6 w-1/2 max-[768px]:w-full max-[768px]:gap-0 blog-list-container">
                    <div v-for="index in 3" :key="index"
                        class="relative rounded-lg h-[175px] flex justify-between gap-5 items-center blog-item">
                        <div v-if="!isLoading && blogs.length > index"
                            class="w-[30%] h-full blog-container max-[768px]:w-[40%] max-[768px]:h-[120px]">
                            <Link
                                :href="route('blog.show', { locale: page.props.locale, country: page.props.country || 'us', blog: blogs[index].translated_slug })">
                                <img :src="blogs[index].image" :alt="blogs[index].title"
                                    class="w-full h-full object-cover rounded-lg transform transition-transform duration-300 ease-in-out hover:scale-105">
                            </Link>
                        </div>
                        <div v-else class="w-[30%] h-full blog-container max-[768px]:w-[40%] max-[768px]:h-[120px]">
                            <Skeleton class="h-full w-full rounded-lg" />
                        </div>

                        <div class="w-[70%]">
                            <p v-if="!isLoading && blogs.length > index"
                                class="text-sm flex items-center gap-1 text-customLightGrayColor">
                                <img :src=calendarIcon alt=""> {{ formatDate(blogs[index].created_at) }}
                            </p>
                            <h4 v-if="!isLoading && blogs.length > index"
                                class="font-semibold text-[1.5rem] text-customDarkBlackColor max-[768px]:text-[1rem]">{{
                                    blogs[index].title }}</h4>
                            <Link v-if="!isLoading && blogs.length > index"
                                :href="route('blog.show', { locale: page.props.locale, country: page.props.country || 'us', blog: blogs[index].translated_slug })"
                                class="inline-flex items-center mt-2 text-customPrimaryColor read-story">
                                Read Story
                                <img :src=goIcon alt="" class="w-[1.5rem]">
                            </Link>
                            <div v-else class="space-y-2">
                                <Skeleton class="h-4 w-[70%]" />
                                <Skeleton class="h-4 w-[50%]" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <Link :href="route('blog', { locale: page.props.locale, country: page.props.country || 'us' })"
                class="button-secondary more-button text-center w-[10rem] mt-6 hover:bg-customPrimaryColor hover:text-white">
                {{ _p('more_blogs') }}</Link>
        </section>


        <!------------------------------ <Ends>  -------------------------------------------------->


        <!-- ------------------------FAQ Section-------------------------------- -->
        <!------------------------------ <Start>  -------------------------------------------------->
        <section class="my-customVerticalSpacing">
            <!-- Pass the faqs prop to the Faq component -->
            <Faq :faqs="props.faqs" />
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

.popular-places button {
    display: none;
}

.hero-content,
.hero-image,
.search-bar-animation,
.why-choose-us-title,
.why-choose-us-card-left,
.why-choose-us-image,
.why-choose-us-card-right,
.popular-place-card,
.blog-title-section,
.blog-main-image,
.blog-item,
.more-button {
    will-change: transform, opacity;
}

.blog-container>img {
    transition: transform 0.3s ease-in-out;
}

.blog-container:hover>img {
    transform: scale(1.1);
    cursor: pointer;
}

.category-carousel .disabled\:pointer-events-none:disabled {
    pointer-events: unset;
}

.read-story img {
    margin-left: 0.75rem;
}

.cursor-blink {
    animation: blink 0.7s infinite;
    font-weight: bold;
}

@keyframes blink {

    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: 0;
    }
}

@media screen and (max-width:768px) {
    .category-carousel .next-btn {
        right: 10% !important;
        display: none;

    }

    .category-carousel .prev-btn {
        left: -4% !important;
        display: none;
    }
}

/* Fade transition styles */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.clip-path-anim {
    clip-path: polygon(0 0, 100% 0, 100% 100%, 0% 100%);
}

.anim-title-word {
    display: inline-block;
}

.category-card-hover {
    display: block;
    transition: all 0.3s ease-in-out;
}

.category-card-hover:hover {
    transform: scale(1.01);
    filter: brightness(0.8);
}

.torch-text {
    position: relative;
    background: linear-gradient(to right,
            rgba(0, 0, 0, 0.05) 0%,
            rgba(0, 0, 0, 1) 50%,
            rgba(0, 0, 0, 0.05) 100%);
    background-size: 300% 100%;
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: torch-sweep 8s linear infinite;
    display: inline-block;
    white-space: nowrap;
    filter: drop-shadow(2px 2px 4px rgba(0, 0, 0, 0.15));
}

@keyframes torch-sweep {
    0% {
        background-position: -200% 0;
    }

    100% {
        background-position: 200% 0;
    }
}
</style>
