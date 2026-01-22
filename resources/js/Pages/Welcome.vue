<script setup>
import { Head, Link } from "@inertiajs/vue3";
import SchemaInjector from '@/Components/SchemaInjector.vue'; // Import SchemaInjector
import heroImg from "../../assets/heroImage.jpg";
import FloatingBubbles from '@/Components/FloatingBubbles.vue';
import Footer from '@/Components/Footer.vue'
import wifiIcon from "../../assets/usb.svg";
import replacementIcon from "../../assets/carIcon.svg";
import protectionIcon from "../../assets/verification.svg";
import priceIcon from "../../assets/percentage-tag.svg";
import supportIcon from "../../assets/call.svg";
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

const heroBadge = computed(() => {
    const badge = _t('hero_badge');
    return badge && badge !== 'hero_badge' ? badge : 'Vrooem car Rentals';
});

const heroSubtitle = computed(() => {
    const subtitle = _t('hero_subtitle');
    return subtitle && subtitle !== 'hero_subtitle'
        ? subtitle
        : 'Premium vehicles, moonlit routes, and concierge-level care for travelers who expect quiet elegance at every pickup.';
});

const translatedPhrases = computed(() => [
    _t('typewriter_text_1'),
    _t('typewriter_text_2'),
    _t('typewriter_text_3')
]);

const displayedText = ref('');
const isHeroMobile = ref(false);
const updateHeroMobile = () => {
    isHeroMobile.value = window.innerWidth <= 600;
};
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
    if (isHeroMobile.value) {
        // On mobile, we use CSS marquee, so just keep the timer alive to check for resize
        timer = setTimeout(typeWriter, 1000);
        return;
    }
    if (isDeleting) {
        // Deleting characters (stop before empty to avoid blank)
        if (currentCharIndex <= 1) {
            isDeleting = false;
            currentPhraseIndex = (currentPhraseIndex + 1) % translatedPhrases.value.length;
            currentCharIndex = 1;
            const nextPhrase = translatedPhrases.value[currentPhraseIndex];
            displayedText.value = nextPhrase.substring(0, currentCharIndex);
            timer = setTimeout(typeWriter, DELAY_AFTER_DELETE);
            return;
        }
        displayedText.value = currentPhrase.substring(0, currentCharIndex - 1);
        currentCharIndex--;
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

const scrollToSearch = () => {
    const searchSection = document.querySelector('.hero-search');
    if (searchSection) {
        // Offset for sticky header if needed
        const yOffset = -50;
        const y = searchSection.getBoundingClientRect().top + window.pageYOffset + yOffset;
        window.scrollTo({ top: y, behavior: 'smooth' });
    }
};

onMounted(() => {
    // Start the typing animation
    timer = setTimeout(typeWriter, 500);
    updateHeroMobile();
    window.addEventListener('resize', updateHeroMobile);
});

onBeforeUnmount(() => {
    // Clean up the timer when component is destroyed
    if (timer) clearTimeout(timer);
    window.removeEventListener('resize', updateHeroMobile);
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
    y: 30,
});

useScrollAnimation('.why-choose-us-trigger', '.why-choose-us-image', {
    opacity: 0,
    scale: 0.8,
});

useScrollAnimation('.why-choose-us-trigger', '.why-choose-us-card-right', {
    opacity: 0,
    y: 30,
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
        <section class="hero_section hero">
            <div class="hero-bg-image hero-image" :style="{ backgroundImage: `url(${heroImageSource})` }"></div>
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-bubble bubble-1"></div>
            <div class="hero-bubble bubble-2"></div>
            <div class="hero-bubble bubble-3"></div>
            <div class="hero-bubble bubble-4"></div>
            <div class="hero-bubble bubble-5"></div>
            <div class="hero-wrapper">
                <div class="hero-left">
                    <div class="hero-label">{{ heroBadge }}</div>
                    <h1 class="hero-title anim-title clip-path-anim" v-html="animatedTagline"></h1>
                    <p class="hero-subtitle">{{ heroSubtitle }}</p>
                    <!-- Desktop Typewriter -->
                    <div v-show="!isHeroMobile" class="hero-typewriter">
                        <span class="typewriter-text">{{ displayedText }}</span>
                        <span class="cursor-blink ml-1"></span>
                    </div>

                    <!-- Mobile Marquee -->
                    <div v-show="isHeroMobile" class="hero-marquee">
                        <div class="marquee-track">
                            <span v-for="(phrase, index) in translatedPhrases" :key="index" class="marquee-item">{{
                                phrase
                                }}</span>
                            <span v-for="(phrase, index) in translatedPhrases" :key="'dup-' + index"
                                class="marquee-item">{{
                                    phrase }}</span>
                        </div>
                    </div>
                    <div class="hero-trust">
                        <span>
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path
                                    d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27z"
                                    fill="currentColor" />
                            </svg>
                            Fast booking
                        </span>
                        <span>
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M5 6h14l-1.2 12.5a2 2 0 01-2 1.8H8.2a2 2 0 01-2-1.8L5 6z"
                                    fill="currentColor" />
                                <path d="M9 6V5a3 3 0 016 0v1" fill="none" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" />
                            </svg>
                            Transparent pricing
                        </span>
                        <span>
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 2a9 9 0 019 9 9 9 0 01-9 9 9 9 0 01-9-9 9 9 0 019-9z" fill="none"
                                    stroke="currentColor" stroke-width="1.5" />
                                <path d="M12 6v5l3 2" fill="none" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            Real-time support
                        </span>
                    </div>

                    <div v-show="isHeroMobile" class="mobile-scroll-btn" @click="scrollToSearch">
                        <span class="scroll-text">Let's Start Booking</span>
                        <div class="scroll-arrows">
                            <svg viewBox="0 0 24 24" class="scroll-arrow arrow-1">
                                <path d="M7 10l5 5 5-5" stroke="currentColor" stroke-width="2" fill="none" />
                            </svg>
                            <svg viewBox="0 0 24 24" class="scroll-arrow arrow-2">
                                <path d="M7 10l5 5 5-5" stroke="currentColor" stroke-width="2" fill="none" />
                            </svg>
                            <svg viewBox="0 0 24 24" class="scroll-arrow arrow-3">
                                <path d="M7 10l5 5 5-5" stroke="currentColor" stroke-width="2" fill="none" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            <div class="search-bar-section hero-search">
                <SearchBar class="search-bar-animation" :simple="true" />
            </div>
        </section>




        <!------------------------------- Advertisement Section -------------------------------------->
        <!------------------------------ <Start>  -------------------------------------------------->
        <!-- Advertisement Section -->
        <AdvertisementSection :advertisements="advertisements" :heroImage="heroImageSource" />
        <!------------------------------ <End>  -------------------------------------------------->


        <!------------------------------- Top Destination Places -------------------------------------->
        <section v-if="!props.popularPlaces || props.popularPlaces.length > 0"
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
                </Carousel>
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
        <section class="py-customVerticalSpacing why-choose-us-trigger why-choose-surface">
            <div class="full-w-container flex flex-col gap-16">
                <div class="column text-center flex flex-col gap-5 items-center why-choose-us-title">
                    <span class="text-[1.25rem] text-customPrimaryColor">-{{ _p('why_choose_us') }}-</span>
                    <h3 class="max-w-[883px] text-customDarkBlackColor">
                        {{ _p('why_subtitle') }}
                    </h3>
                    <p class="max-w-[720px] text-customLightGrayColor text-[1.1rem]">
                        Travel with the confidence of a premium concierge and the ease of a local expert.
                    </p>
                </div>
                <div class="column why-choose-grid">
                    <div class="why-choose-card why-choose-us-card-left">
                        <div class="why-choose-icon">
                            <img :src="wifiIcon" alt="Free internet included" />
                        </div>
                        <div class="flex flex-col gap-3">
                            <span class="why-choose-title">Free Internet Included</span>
                            <p class="why-choose-text">
                                Stay connected from arrival to drop-off with effortless, in-car access.
                            </p>
                        </div>
                    </div>
                    <div class="why-choose-card why-choose-us-card-left">
                        <div class="why-choose-icon">
                            <img :src="replacementIcon" alt="Instant replacement guarantee" />
                        </div>
                        <div class="flex flex-col gap-3">
                            <span class="why-choose-title">Instant Replacement Guarantee</span>
                            <p class="why-choose-text">
                                If your reserved vehicle is unavailable, we secure a replacement instantly.
                            </p>
                            <div class="why-choose-tag">
                                <img :src="priceIcon" alt="" />
                                <span>No extra cost, upgrades when possible.</span>
                            </div>
                        </div>
                    </div>
                    <div class="why-choose-card why-choose-us-card-left">
                        <div class="why-choose-icon">
                            <img :src="protectionIcon" alt="Fair damage protection" />
                        </div>
                        <div class="flex flex-col gap-3">
                            <span class="why-choose-title">Fair Damage Protection</span>
                            <p class="why-choose-text">
                                We protect you from excessive charges for minor scratches or normal wear.
                            </p>
                        </div>
                    </div>
                    <div class="why-choose-card why-choose-us-card-right">
                        <div class="why-choose-icon">
                            <img :src="priceIcon" alt="Best price guarantee" />
                        </div>
                        <div class="flex flex-col gap-3">
                            <span class="why-choose-title">Best Price Guarantee</span>
                            <p class="why-choose-text">
                                Transparent pricing with the lowest rate available for every trip.
                            </p>
                        </div>
                    </div>
                    <div class="why-choose-card why-choose-us-card-right">
                        <div class="why-choose-icon">
                            <img :src="supportIcon" alt="24/7 worldwide support" />
                        </div>
                        <div class="flex flex-col gap-3">
                            <span class="why-choose-title">24/7 Worldwide Support</span>
                            <p class="why-choose-text">
                                Wherever you are, Vrooem is ready to keep your journey effortless.
                            </p>
                        </div>
                    </div>
                    <div class="why-choose-card why-choose-us-card-right">
                        <div class="why-choose-icon">
                            <img :src="replacementIcon" alt="No rushing or waiting" />
                        </div>
                        <div class="flex flex-col gap-3">
                            <span class="why-choose-title">No Rushing, No Waiting</span>
                            <p class="why-choose-text">
                                Skip long lines and start your trip relaxed with fast, guided pickup.
                            </p>
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
        <section v-if="props.testimonials && props.testimonials.length" class="py-customVerticalSpacing">
            <Testimonials />
        </section>
        <!-- ---------------------------<End>---------------------------------------------------->


        <!-- ------------------------Blogs Section-------------------------------- -->
        <!------------------------------ <Start>  -------------------------------------------------->
        <section v-if="!isLoading && blogs && blogs.length"
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
        <section v-if="props.faqs && props.faqs.length" class="my-customVerticalSpacing">
            <!-- Pass the faqs prop to the Faq component -->
            <Faq :faqs="props.faqs" />
        </section>
        <!-- ---------------------------<End>---------------------------------------------------->
    </main>

    <Footer />
</template>

<style>
@import url("https://fonts.googleapis.com/css2?family=Fraunces:wght@400;600;700&family=Manrope:wght@300;400;500;600&display=swap");

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

.why-choose-surface {
    position: relative;
}

.why-choose-surface::before {
    content: "";
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at top, rgba(15, 23, 42, 0.05), transparent 65%);
    pointer-events: none;
}

.why-choose-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 2.5rem;
    position: relative;
    z-index: 1;
    align-items: stretch;
}

.why-choose-card {
    display: flex;
    gap: 1.5rem;
    align-items: flex-start;
    background: #ffffff;
    border-radius: 22px;
    padding: 1.75rem;
    border: 1px solid rgba(15, 23, 42, 0.08);
    box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
    transition: transform 0.35s ease, box-shadow 0.35s ease, border-color 0.35s ease;
    height: 100%;
}

.why-choose-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 22px 40px rgba(15, 23, 42, 0.12);
    border-color: rgba(15, 23, 42, 0.14);
}

.why-choose-icon {
    width: 3.1rem;
    height: 3.1rem;
    border-radius: 16px;
    background: rgba(15, 23, 42, 0.04);
    border: 1px solid rgba(15, 23, 42, 0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
    flex-shrink: 0;
}

.why-choose-icon img {
    width: 1.55rem;
    height: 1.55rem;
    filter: none;
}

.why-choose-title {
    font-size: 1.35rem;
    font-weight: 600;
    color: #111827;
}

.why-choose-text {
    font-size: 1.02rem;
    color: #6b7280;
}

.why-choose-tag {
    margin-top: 0.5rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.35rem 0.75rem;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.04);
    border: 1px solid rgba(15, 23, 42, 0.08);
    font-size: 0.85rem;
    color: #4b5563;
}

.why-choose-tag img {
    width: 0.85rem;
    height: 0.85rem;
}

@media screen and (max-width: 1024px) {
    .why-choose-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1.5rem;
    }

    .why-choose-card {
        padding: 1.5rem;
    }
}

@media screen and (max-width: 768px) {
    .why-choose-grid {
        grid-template-columns: 1fr;
        gap: 1.25rem;
    }

    .why-choose-card {
        flex-direction: column;
        gap: 1rem;
    }

    .why-choose-title {
        font-size: 1.2rem;
    }

    .why-choose-text {
        font-size: 0.95rem;
    }
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

.hero {
    position: relative;
    overflow: visible;
    padding: 5rem 4vw 7rem;
    background: radial-gradient(circle at 20% 20%, rgba(46, 167, 173, 0.2), transparent 55%),
        radial-gradient(circle at 80% 10%, rgba(255, 255, 255, 0.08), transparent 40%),
        linear-gradient(125deg, #081824 0%, #0b2f3f 45%, #08141d 100%);
}

.hero::after {
    content: "";
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle at 78% 18%, rgba(255, 255, 255, 0.08), transparent 45%),
        url("data:image/svg+xml,%3Csvg width='200' height='200' viewBox='0 0 200 200' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 120C40 120 60 90 100 90C140 90 160 120 200 120' stroke='rgba(255,255,255,0.06)' stroke-width='1'/%3E%3Cpath d='M0 150C40 150 60 120 100 120C140 120 160 150 200 150' stroke='rgba(255,255,255,0.04)' stroke-width='1'/%3E%3Cpath d='M0 60C40 60 60 30 100 30C140 30 160 60 200 60' stroke='rgba(255,255,255,0.05)' stroke-width='1'/%3E%3C/svg%3E");
    opacity: 0.55;
    pointer-events: none;
}

.hero-bg-image {
    position: absolute;
    top: -5%;
    right: -3%;
    width: 55%;
    height: 120%;
    background-size: cover;
    background-position: center right;
    opacity: 0.35;
    filter: saturate(1.1);
    mix-blend-mode: screen;
    pointer-events: none;
}

.hero-bg-image::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, rgba(8, 20, 29, 0.95) 0%, rgba(8, 20, 29, 0.35) 45%, rgba(8, 20, 29, 0) 100%),
        linear-gradient(180deg, rgba(8, 20, 29, 0.85) 0%, rgba(8, 20, 29, 0) 35%, rgba(8, 20, 29, 0) 65%, rgba(8, 20, 29, 0.85) 100%);
}

.hero-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(0px);
    opacity: 0.7;
    mix-blend-mode: screen;
    pointer-events: none;
}

.hero-orb.orb-1 {
    width: 260px;
    height: 260px;
    background: radial-gradient(circle, rgba(46, 167, 173, 0.4), transparent 70%);
    top: -60px;
    left: 10%;
}

.hero-orb.orb-2 {
    width: 180px;
    height: 180px;
    background: radial-gradient(circle, rgba(255, 211, 155, 0.25), transparent 70%);
    bottom: 10%;
    right: 18%;
}

.hero-bubble {
    position: absolute;
    border-radius: 50%;
    background: radial-gradient(circle at 30% 30%, rgb(255 255 255 / 8%), rgba(46, 167, 173, 0.05));
    border: 1px solid rgb(255 255 255 / 9%);
    -webkit-backdrop-filter: blur(10px);
    backdrop-filter: blur(10px);
    box-shadow: 0 10px 22px rgba(5, 15, 24, 0.28);
    pointer-events: none;
    animation: floatBubble 10s ease-in-out infinite;
}

.hero-bubble.bubble-1 {
    width: 120px;
    height: 120px;
    top: 18%;
    right: 16%;
    animation-delay: 0.2s;
}

.hero-bubble.bubble-2 {
    width: 90px;
    height: 90px;
    bottom: 14%;
    left: 8%;
    animation-delay: 1s;
}

.hero-bubble.bubble-3 {
    width: 70px;
    height: 70px;
    top: 55%;
    right: 6%;
    animation-delay: 1.6s;
}

.hero-bubble.bubble-4 {
    width: 100px;
    height: 100px;
    top: 8%;
    left: 22%;
    animation-delay: 0.6s;
}

.hero-bubble.bubble-5 {
    width: 80px;
    height: 80px;
    bottom: 26%;
    right: 28%;
    animation-delay: 2.2s;
}

@keyframes floatBubble {

    0%,
    100% {
        transform: translateY(0) translateX(0);
    }

    50% {
        transform: translateY(-14px) translateX(8px);
    }
}

.hero-wrapper {
    max-width: 1280px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 2.5rem;
    position: relative;
    z-index: 1;
    align-items: stretch;
}

.hero-left {
    padding: 3.2rem 2.6rem 3.2rem 3.2rem;
    background: linear-gradient(160deg, rgba(255, 255, 255, 0.08), rgba(9, 27, 36, 0.75));
    border-radius: 30px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    position: relative;
    overflow: hidden;
    box-shadow: 0 30px 70px rgba(6, 18, 27, 0.45);
    font-family: "Manrope", sans-serif;
}

.hero-left::before {
    content: "";
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 12% 18%, rgba(46, 167, 173, 0.18), transparent 60%);
    pointer-events: none;
}

.hero-label {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 999px;
    padding: 0.45rem 0.95rem;
    font-size: 0.8rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #e8dccf;
    font-weight: 600;
    margin-bottom: 1.6rem;
    position: relative;
    z-index: 1;
}

.hero-title {
    font-family: "Fraunces", serif;
    font-size: clamp(2.7rem, 4.2vw, 4rem);
    line-height: 1.08;
    color: #ffffff;
    margin-bottom: 1.6rem;
    position: relative;
    z-index: 1;
}

.hero-title .anim-title-word {
    color: #e8dccf;
}

.hero-subtitle {
    font-size: 1.08rem;
    color: rgba(255, 255, 255, 0.75);
    max-width: 28rem;
    line-height: 1.65;
    margin-bottom: 1.9rem;
    position: relative;
    z-index: 1;
}

.hero-typewriter {
    display: inline-flex;
    align-items: center;
    gap: 1rem;
    padding: 0.8rem 1.35rem;
    border-radius: 999px;
    background: rgba(6, 19, 28, 0.65);
    border: 1px solid rgba(255, 255, 255, 0.22);
    color: #e8dccf;
    font-size: 0.98rem;
    position: relative;
    z-index: 1;
    box-shadow: inset 0 0 0 1px rgba(46, 167, 173, 0.2);
    min-height: 2.6rem;
}

.hero-typewriter::before {
    content: "";
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: #2ea7ad;
    box-shadow: 0 0 0 6px rgba(46, 167, 173, 0.35);
    position: relative;
    z-index: 1;
    animation: premiumPulse 2.4s ease-in-out infinite;
}

.hero-typewriter::after {
    content: none;
}

@keyframes premiumPulse {
    0% {
        background: #2ea7ad;
        box-shadow: 0 0 0 6px rgba(46, 167, 173, 0.25);
        opacity: 0.9;
    }

    50% {
        background: #e8dccf;
        box-shadow: 0 0 0 8px rgba(232, 220, 207, 0.2);
        opacity: 1;
    }

    100% {
        background: #2ea7ad;
        box-shadow: 0 0 0 6px rgba(46, 167, 173, 0.25);
        opacity: 0.9;
    }
}

.hero-typewriter .typewriter-text {
    display: inline-block;
    min-width: 24ch;
    white-space: nowrap;
}

.hero-trust {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
    margin-top: 2.6rem;
    color: rgba(255, 255, 255, 0.65);
    font-size: 0.95rem;
    position: relative;
    z-index: 1;
}

.hero-trust span {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.hero-trust svg {
    width: 18px;
    height: 18px;
    color: #d9cbb8;
    flex-shrink: 0;
}

.search-bar-section .search_bar {
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 18px 50px rgba(5, 15, 24, 0.45);
    background: rgba(10, 20, 26, 0.92);
    overflow: visible;
    position: relative;
    width: 100%;
    max-width: 1280px;
}

.search-bar-section .search_bar::before {
    content: "";
    position: absolute;
    top: 0.6rem;
    left: 1.2rem;
    right: 1.2rem;
    height: 2px;
    border-radius: 999px;
    background: linear-gradient(90deg, #2ea7ad, transparent);
    animation: none;
    opacity: 1;
    z-index: 0;
}

.search-bar-section .search_bar form {
    background: transparent !important;
    backdrop-filter: blur(14px);
    position: relative;
    z-index: 1;
}

.search-bar-section .search_bar .bg-gray-50 {
    background: rgba(255, 255, 255, 0.08) !important;
}

.search-bar-section .search_bar .border-gray-200 {
    border-color: rgba(255, 255, 255, 0.12) !important;
}

.search-bar-section .search_bar form .text-customDarkBlackColor {
    color: #f6f2ec !important;
}

.search-bar-section .search_bar form svg {
    color: #f6f2ec !important;
}

.search-bar-section .search_bar form .text-customLightGrayColor {
    color: rgba(230, 235, 238, 0.7) !important;
}

.search-bar-section .search_bar form input,
.search-bar-section .search_bar form .text-customPrimaryColor,
.search-bar-section .search_bar form .text-gray-400,
.search-bar-section .search_bar form .text-gray-500 {
    color: #f6f2ec !important;
}

.search-bar-section .search_bar form input::placeholder {
    color: rgba(230, 235, 238, 0.5) !important;
}

.search-bar-section .search-results {
    background: #ffffff !important;
    color: #0f172a !important;
}

.search-bar-section .search-results .text-gray-500,
.search-bar-section .search-results .text-gray-600,
.search-bar-section .search-results .text-customDarkBlackColor {
    color: #334155 !important;
}

.search-bar-section .search_bar button[type="submit"] {
    background: linear-gradient(135deg, #0d3342, #2ea7ad) !important;
    border-radius: 18px;
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.12);
}

.search-bar-section .search_bar button[type="submit"]:hover {
    box-shadow: 0 16px 30px rgba(12, 63, 78, 0.25);
}

.hero-search {
    max-width: 1280px;
    margin: 2.5rem auto 0;
    position: relative;
    z-index: 10;
}

@media (max-width: 768px) {
    .hero-search {
        margin-top: 4rem;
        padding: 0 1rem;
    }
}

@media (max-width: 900px) {
    .hero {
        padding: 0rem 0vw 1rem;
    }

    .hero-wrapper {
        max-width: 100%;
    }

    .hero-left {
        padding: 2.8rem 2.4rem 3rem;
        border-radius: 0;
    }

    .hero-title {
        font-size: clamp(2.2rem, 5vw, 3rem);
    }

    .hero-subtitle {
        max-width: 100%;
    }

    .hero-trust {
        gap: 1rem;
        font-size: 0.9rem;
    }

    .hero-bubble.bubble-1 {
        width: 100px;
        height: 100px;
        right: 10%;
    }

    .hero-bubble.bubble-4 {
        width: 85px;
        height: 85px;
        left: 12%;
    }

    .hero-bg-image {
        width: 100%;
        height: 70%;
        top: 30%;
        right: 0;
        opacity: 0.28;
    }


}

@media (max-width: 600px) {
    .hero-left {
        padding: 3.4rem 1.6rem;
    }

    .hero-trust {
        gap: 0.75rem;
    }

    .hero-title {
        font-size: clamp(2rem, 8vw, 2.6rem);
    }

    .hero-marquee {
        width: 100%;
        display: flex;
        align-items: center;
        min-height: 3.2rem;
        border-radius: 16px;
        padding: 0.8rem 1.1rem;
        overflow: hidden;
        background: rgba(6, 19, 28, 0.65);
        border: 1px solid rgba(255, 255, 255, 0.22);
        color: #e8dccf;
        box-shadow: inset 0 0 0 1px rgba(46, 167, 173, 0.2);
        position: relative;
        white-space: nowrap;
    }

    .marquee-track {
        display: flex;
        gap: 2rem;
        animation: marquee 30s linear infinite;
        width: max-content;
    }

    .mobile-scroll-btn {
        margin-top: 2.5rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
        cursor: pointer;
        color: #e8dccf;
        width: 100%;
    }

    .scroll-text {
        font-family: "Fraunces", serif;
        font-size: 1.2rem;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #2ea7ad;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .scroll-arrows {
        display: flex;
        flex-direction: column;
        align-items: center;
        height: 40px;
        margin-top: -10px;
    }

    .scroll-arrow {
        width: 24px;
        height: 24px;
        margin-top: -12px;
        /* Overlap them slightly */
        animation: arrowFade 2s infinite;
        opacity: 0;
    }

    .arrow-1 {
        animation-delay: 0s;
        width: 24px;
        height: 24px;
        opacity: 0.6;
    }

    .arrow-2 {
        animation-delay: 0.2s;
        width: 30px;
        height: 30px;
        margin-top: -12px;
        opacity: 0.8;
    }

    .arrow-3 {
        animation-delay: 0.4s;
        width: 36px;
        height: 36px;
        margin-top: -15px;
    }

    @keyframes arrowFade {
        0% {
            opacity: 0;
            transform: translateY(-5px);
        }

        50% {
            opacity: 1;
            transform: translateY(0);
        }

        100% {
            opacity: 0;
            transform: translateY(5px);
        }
    }

    .marquee-item {
        font-size: 1rem;
        color: #e8dccf;
        flex-shrink: 0;
    }

    @keyframes marquee {
        0% {
            transform: translateX(0);
        }

        100% {
            transform: translateX(-50%);
        }
    }

    .hero-bubble.bubble-2 {
        width: 70px;
        height: 70px;
        left: 4%;
    }

    .hero-bubble.bubble-3 {
        width: 60px;
        height: 60px;
        right: 4%;
    }
}
</style>
