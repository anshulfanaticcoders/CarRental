<script setup>
import { Link } from "@inertiajs/vue3";
import SchemaInjector from '@/Components/SchemaInjector.vue'; // Import SchemaInjector
import SeoHead from '@/Components/SeoHead.vue';
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
    seo: Object,
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

const getHomepageLabel = (key, fallback) => {
    const label = _t(key);
    return label && label !== key ? label : fallback;
};

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

    <SeoHead :seo="seo" />
    <!-- Inject all schemas passed in the 'schema' array prop -->
    <template v-if="schema && schema.length">
        <SchemaInjector v-for="(individualSchema, index) in schema" :key="`schema-${index}`"
            :schema="individualSchema" />
    </template>

    <!-- Schema for Organization (globally shared, if it exists) -->
    <SchemaInjector v-if="$page.props.organizationSchema" :schema="$page.props.organizationSchema" />

    <AuthenticatedHeaderLayout />

    <div v-if="$page.props.flash?.success" class="newsletter-flash">
        <div class="full-w-container">
            <div class="newsletter-flash-card">
                <span class="newsletter-flash-text">{{ $page.props.flash.success }}</span>
            </div>
        </div>
    </div>

    <main class="overflow-x-hidden">
        <section class="hero_section hero" :style="{ '--hero-image': `url(${heroImageSource})` }">
            <div class="hero-bg-image hero-image" :style="{ backgroundImage: `url(${heroImageSource})` }"></div>
            <div class="hero-orb orb-1"></div>
            <div class="hero-orb orb-2"></div>
            <div class="hero-bubble bubble-1"></div>
            <div class="hero-bubble bubble-2"></div>
            <div class="hero-bubble bubble-3"></div>
            <div class="hero-bubble bubble-4"></div>
            <div class="hero-bubble bubble-5"></div>
            <div class="hero-wrapper full-w-container">
                <div class="hero-left">
                    <h1 class="hero-title anim-title clip-path-anim" v-html="animatedTagline"></h1>
                    <p class="hero-subtitle">{{ heroSubtitle }}</p>
                    <div class="hero-typewriter">
                        <span class="typewriter-text">{{ displayedText }}</span>
                        <span class="cursor-blink ml-1"></span>
                    </div>

                    <div class="hero-trust">
                        <span>
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path
                                    d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27z"
                                    fill="currentColor" />
                            </svg>
                            {{ getHomepageLabel('hero_trust_1', 'Free Internet Included') }}
                        </span>
                        <span>
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M5 6h14l-1.2 12.5a2 2 0 01-2 1.8H8.2a2 2 0 01-2-1.8L5 6z"
                                    fill="currentColor" />
                                <path d="M9 6V5a3 3 0 016 0v1" fill="none" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" />
                            </svg>
                            {{ getHomepageLabel('hero_trust_2', 'Instant Replacement Guarantee') }}
                        </span>
                        <span>
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 2a9 9 0 019 9 9 9 0 01-9 9 9 9 0 01-9-9 9 9 0 019-9z" fill="none"
                                    stroke="currentColor" stroke-width="1.5" />
                                <path d="M12 6v5l3 2" fill="none" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            {{ getHomepageLabel('hero_trust_3', 'Fair Damage Protection') }}
                        </span>
                        <span>
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 3l7 4v6c0 4.2-2.8 7.5-7 9-4.2-1.5-7-4.8-7-9V7l7-4z"
                                    fill="none" stroke="currentColor" stroke-width="1.5" />
                                <path d="M9 12l2 2 4-4" fill="none" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            {{ getHomepageLabel('hero_trust_4', 'Best Price Guarantee') }}
                        </span>
                        <span>
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 7v5l3 2" fill="none" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M12 2a10 10 0 1010 10" fill="none" stroke="currentColor"
                                    stroke-width="1.5" stroke-linecap="round" />
                                <path d="M21 2l-4 4" fill="none" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" />
                            </svg>
                            {{ getHomepageLabel('hero_trust_5', '24/7 Worldwide Support') }}
                        </span>
                        <span>
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 5v14" fill="none" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" />
                                <path d="M6 9l6-4 6 4" fill="none" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M6 15l6 4 6-4" fill="none" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            {{ getHomepageLabel('hero_trust_6', 'No Rushing, No Waiting') }}
                        </span>
                    </div>

                </div>
            </div>
            <div class="search-bar-section hero-search">
                <SearchBar class="searchbar-in-header search-bar-animation" :simple="true" />
            </div>
        </section>




        <!------------------------------- Advertisement Section -------------------------------------->
        <!------------------------------ <Start>  -------------------------------------------------->
        <!-- Advertisement Section -->
        <AdvertisementSection :advertisements="advertisements" :heroImage="heroImageSource" />
        <!------------------------------ <End>  -------------------------------------------------->


        <!------------------------------- Top Destination Places -------------------------------------->
        <section v-if="!props.popularPlaces || props.popularPlaces.length > 0"
            class="home-section home-section--dark popular-places popular-places-trigger">
            <div class="full-w-container flex flex-col gap-2 max-[768px]:gap-8">
                <div class="column">
                    <span class="text-[1.15rem] text-slate-100">-{{ _p('top_destinations') }} -</span>
                    <h3 class="text-slate-100 max-[768px]:text-[1.75rem] max-[768px]:mt-[1rem]">{{
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
                                            class="h-[18rem] border-0 rounded-[0.75rem] shadow-lg">
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
            </div>
        </section>




        <!------------------------------ <Start>  -------------------------------------------------->
        <!------------------------------ <End>  -------------------------------------------------->




        <!------------------------------- eSIM Section -------------------------------------->
        <!------------------------------ <Start>  -------------------------------------------------->
        <section id="esim">
            <EsimSection />
        </section>
        <!------------------------------ <End>  -------------------------------------------------->

        <!------------------------------- WHY CHOOSE US -------------------------------------->
        <!------------------------------ <Start>  -------------------------------------------------->
        <section class="home-section home-section--dark why-choose-us-trigger why-choose-surface">
            <div class="full-w-container flex flex-col gap-16">
                <div class="column text-center flex flex-col gap-5 items-center why-choose-us-title">
                    <span class="text-[1.25rem] text-slate-100">-{{ _p('why_choose_us') }}-</span>
                    <h3 class="max-w-[883px] text-white">
                        {{ _p('why_subtitle') }}
                    </h3>
                    <p class="max-w-[720px] text-slate-200 text-[1.1rem]">
                        {{ getHomepageLabel('why_lead', 'Travel with the confidence of a premium concierge and the ease of a local expert.') }}
                    </p>
                </div>
                <div class="column why-choose-grid">
                    <div class="why-choose-card why-choose-us-card-left">
                        <div class="why-choose-icon">
                            <img :src="wifiIcon" alt="Free internet included" />
                        </div>
                        <div class="flex flex-col gap-3">
                            <span class="why-choose-title">{{ getHomepageLabel('why_card_1_title', 'Free Internet Included') }}</span>
                            <p class="why-choose-text">
                                {{ getHomepageLabel('why_card_1_text', 'Stay connected from arrival to drop-off with effortless, in-car access.') }}
                            </p>
                        </div>
                    </div>
                    <div class="why-choose-card why-choose-us-card-left">
                        <div class="why-choose-icon">
                            <img :src="replacementIcon" alt="Instant replacement guarantee" />
                        </div>
                        <div class="flex flex-col gap-3">
                            <span class="why-choose-title">{{ getHomepageLabel('why_card_2_title', 'Instant Replacement Guarantee') }}</span>
                            <p class="why-choose-text">
                                {{ getHomepageLabel('why_card_2_text', 'If your reserved vehicle is unavailable, we secure a replacement instantly.') }}
                            </p>
                            <div class="why-choose-tag">
                                <img :src="priceIcon" alt="" />
                                <span>{{ getHomepageLabel('why_card_2_tag', 'No extra cost, upgrades when possible.') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="why-choose-card why-choose-us-card-left">
                        <div class="why-choose-icon">
                            <img :src="protectionIcon" alt="Fair damage protection" />
                        </div>
                        <div class="flex flex-col gap-3">
                            <span class="why-choose-title">{{ getHomepageLabel('why_card_3_title', 'Fair Damage Protection') }}</span>
                            <p class="why-choose-text">
                                {{ getHomepageLabel('why_card_3_text', 'We protect you from excessive charges for minor scratches or normal wear.') }}
                            </p>
                        </div>
                    </div>
                    <div class="why-choose-card why-choose-us-card-right">
                        <div class="why-choose-icon">
                            <img :src="priceIcon" alt="Best price guarantee" />
                        </div>
                        <div class="flex flex-col gap-3">
                            <span class="why-choose-title">{{ getHomepageLabel('why_card_4_title', 'Best Price Guarantee') }}</span>
                            <p class="why-choose-text">
                                {{ getHomepageLabel('why_card_4_text', 'Transparent pricing with the lowest rate available for every trip.') }}
                            </p>
                        </div>
                    </div>
                    <div class="why-choose-card why-choose-us-card-right">
                        <div class="why-choose-icon">
                            <img :src="supportIcon" alt="24/7 worldwide support" />
                        </div>
                        <div class="flex flex-col gap-3">
                            <span class="why-choose-title">{{ getHomepageLabel('why_card_5_title', '24/7 Worldwide Support') }}</span>
                            <p class="why-choose-text">
                                {{ getHomepageLabel('why_card_5_text', 'Wherever you are, Vrooem is ready to keep your journey effortless.') }}
                            </p>
                        </div>
                    </div>
                    <div class="why-choose-card why-choose-us-card-right">
                        <div class="why-choose-icon">
                            <img :src="replacementIcon" alt="No rushing or waiting" />
                        </div>
                        <div class="flex flex-col gap-3">
                            <span class="why-choose-title">{{ getHomepageLabel('why_card_6_title', 'No Rushing, No Waiting') }}</span>
                            <p class="why-choose-text">
                                {{ getHomepageLabel('why_card_6_text', 'Skip long lines and start your trip relaxed with fast, guided pickup.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!------------------------------ <End>  -------------------------------------------------->

        <!------------------------------- How It Works -------------------------------------->
        <!------------------------------ <Start>  -------------------------------------------------->
        <section id="how-it-works">
            <HowItWorks />
        </section>
        <!------------------------------ <End>  -------------------------------------------------->

        <!-- ------------------------Testimonials Section-------------------------------- -->
        <!------------------------------ <Start>  -------------------------------------------------->
        <section id="testimonials" v-if="props.testimonials && props.testimonials.length">
            <Testimonials />
        </section>
        <!-- ---------------------------<End>---------------------------------------------------->


        <!-- ------------------------Blogs Section-------------------------------- -->
        <!------------------------------ <Start>  -------------------------------------------------->
        <section id="blogs" v-if="!isLoading && blogs && blogs.length"
            class="home-section home-section--light blogs min-h-[80vh] flex flex-col gap-10 items-center blogs-trigger">
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
                    :href="route('blog.show', { locale: page.props.locale, country: blogs[0].canonical_country || (page.props.country || 'us'), blog: blogs[0].translated_slug })"
                    v-if="!isLoading && blogs.length > 0"
                    class="w-1/2 h-[574px] relative rounded-lg overflow-hidden shadow-md blog-container blog-main-image max-[768px]:w-full max-[768px]:h-[380px]">
                    <img :src="blogs[0].image" :alt="blogs[0].title" class="w-full h-full object-cover rounded-lg">

                    <div class="absolute bottom-0 bg-gradient-to-t from-black/70 to-transparent p-4 text-white w-full">
                        <p class="text-[1.25rem] flex items-center gap-1">
                            <img :src=calendarWhiteIcon alt=""> {{ formatDate(blogs[0].created_at) }}
                        </p>
                        <h4 class="font-semibold text-[2rem] max-[768px]:text-[1.25rem]">{{ blogs[0].title }}</h4>
                        <Link
                            :href="route('blog.show', { locale: page.props.locale, country: blogs[0].canonical_country || (page.props.country || 'us'), blog: blogs[0].translated_slug })"
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
                                :href="route('blog.show', { locale: page.props.locale, country: blogs[index].canonical_country || (page.props.country || 'us'), blog: blogs[index].translated_slug })">
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
                                :href="route('blog.show', { locale: page.props.locale, country: blogs[index].canonical_country || (page.props.country || 'us'), blog: blogs[index].translated_slug })"
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
        <section id="faq" v-if="props.faqs && props.faqs.length">
            <!-- Pass the faqs prop to the Faq component -->
            <Faq :faqs="props.faqs" />
        </section>
        <!-- ---------------------------<End>---------------------------------------------------->
    </main>

    <Footer />
</template>

<style>

.newsletter-flash {
    padding: 0.75rem 0;
    background: rgba(240, 253, 244, 0.9);
    border-bottom: 1px solid rgba(134, 239, 172, 0.4);
}

.newsletter-flash-card {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.65rem 0.9rem;
    border-radius: 12px;
    background: #ffffff;
    color: #14532d;
    font-weight: 600;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
}

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
    background: radial-gradient(circle at top, rgba(46, 167, 173, 0.2), transparent 65%);
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
    padding: 2.6rem 0 7.2rem;
    background: radial-gradient(circle at 14% 18%, rgba(46, 167, 173, 0.28), transparent 55%),
        radial-gradient(circle at 78% 12%, rgba(255, 236, 206, 0.26), transparent 45%),
        linear-gradient(132deg, #122a3a 0%, #1a3f53 48%, #102531 100%);
}

.hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 30% 12%, rgba(255, 255, 255, 0.16), transparent 48%),
        radial-gradient(circle at 70% 75%, rgba(46, 167, 173, 0.18), transparent 60%);
    opacity: 0.8;
    pointer-events: none;
}

.hero::after {
    content: "";
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle at 76% 18%, rgba(255, 255, 255, 0.16), transparent 48%),
        url("data:image/svg+xml,%3Csvg width='200' height='200' viewBox='0 0 200 200' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 120C40 120 60 90 100 90C140 90 160 120 200 120' stroke='rgba(255,255,255,0.05)' stroke-width='1'/%3E%3Cpath d='M0 150C40 150 60 120 100 120C140 120 160 150 200 150' stroke='rgba(255,255,255,0.03)' stroke-width='1'/%3E%3Cpath d='M0 60C40 60 60 30 100 30C140 30 160 60 200 60' stroke='rgba(255,255,255,0.04)' stroke-width='1'/%3E%3C/svg%3E");
    opacity: 0.3;
    pointer-events: none;
}

.hero-bg-image {
    position: absolute;
    top: -8%;
    right: -6%;
    width: 60%;
    height: 120%;
    background-size: cover;
    background-position: center right;
    opacity: 0.5;
    filter: saturate(1.08) contrast(1.02);
    mix-blend-mode: normal;
    pointer-events: none;
}

.hero-bg-image::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, rgba(10, 30, 42, 0.72) 0%, rgba(10, 30, 42, 0.28) 45%, rgba(10, 30, 42, 0) 100%),
        linear-gradient(180deg, rgba(10, 30, 42, 0.68) 0%, rgba(10, 30, 42, 0) 40%, rgba(10, 30, 42, 0) 60%, rgba(10, 30, 42, 0.68) 100%);
}

.hero-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(0px);
    opacity: 0.6;
    mix-blend-mode: screen;
    pointer-events: none;
}

.hero-orb.orb-1 {
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(46, 167, 173, 0.38), transparent 70%);
    top: -80px;
    left: 8%;
}

.hero-orb.orb-2 {
    width: 210px;
    height: 210px;
    background: radial-gradient(circle, rgba(255, 214, 168, 0.22), transparent 70%);
    bottom: 12%;
    right: 16%;
}

.hero-bubble {
    position: absolute;
    border-radius: 50%;
    background: radial-gradient(circle at 30% 30%, rgb(255 255 255 / 10%), rgba(46, 167, 173, 0.05));
    border: 1px solid rgba(255, 255, 255, 0.12);
    -webkit-backdrop-filter: blur(10px);
    backdrop-filter: blur(10px);
    box-shadow: 0 14px 30px rgba(5, 15, 24, 0.28);
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
    max-width: none;
    margin: 0 auto;
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 2.5rem;
    position: relative;
    z-index: 1;
    align-items: stretch;
    justify-items: start;
}

.hero-left {
    padding: 3.6rem 0 3.4rem;
    border-radius: 32px;
    border: none;
    position: relative;
    overflow: hidden;
    background: none;
    backdrop-filter: none;
    box-shadow: none;
    max-width: 680px;
    width: 100%;
}

.hero-label {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.26);
    border-radius: 999px;
    padding: 0.45rem 0.95rem;
    font-size: 0.8rem;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: #f1e7d6;
    font-weight: 600;
    margin-bottom: 1.6rem;
    position: relative;
    z-index: 1;
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.1);
}

.hero-title {
    font-size: clamp(2.7rem, 4.2vw, 4rem);
    line-height: 1.08;
    color: #f9f4ec;
    margin-bottom: 1.6rem;
    position: relative;
    z-index: 1;
    letter-spacing: -0.01em;
    text-shadow: 0 24px 60px rgba(2, 10, 16, 0.45);
}

.hero-title .anim-title-word {
    color: #f0d7ad;
}

.hero-subtitle {
    font-size: 1.08rem;
    color: rgba(240, 234, 224, 0.82);
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
    background: rgba(8, 22, 32, 0.72);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #f1e7d6;
    font-size: 0.98rem;
    position: relative;
    z-index: 1;
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.08), 0 12px 24px rgba(5, 14, 22, 0.28);
    min-height: 2.6rem;
}

.hero-typewriter::before {
    content: "";
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: #f0d7ad;
    box-shadow: 0 0 0 6px rgba(240, 215, 173, 0.28);
    position: relative;
    z-index: 1;
    animation: premiumPulse 2.4s ease-in-out infinite;
}

.hero-typewriter::after {
    content: none;
}

@keyframes premiumPulse {
    0% {
        background: #f0d7ad;
        box-shadow: 0 0 0 6px rgba(240, 215, 173, 0.25);
        opacity: 0.9;
    }

    50% {
        background: #2ea7ad;
        box-shadow: 0 0 0 8px rgba(46, 167, 173, 0.25);
        opacity: 1;
    }

    100% {
        background: #f0d7ad;
        box-shadow: 0 0 0 6px rgba(240, 215, 173, 0.25);
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
    gap: 1.35rem;
    flex-wrap: wrap;
    margin-top: 2.6rem;
    color: rgba(229, 223, 212, 0.7);
    font-size: 0.93rem;
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
    color: #e8d7c0;
    flex-shrink: 0;
}

/* SearchBar in Hero (match SearchResults header style) */
.searchbar-in-header :deep(.full-w-container) {
    padding-bottom: 0 !important;
}

.searchbar-in-header :deep(.search_bar) {
    background: transparent !important;
    border: none !important;
    border-radius: 0 !important;
    padding: 0 !important;
}

.searchbar-in-header :deep(.search_bar > .flex > .column:first-child) {
    display: none !important;
}

.searchbar-in-header :deep(.search_bar > .flex > .column:last-child) {
    width: 100% !important;
    padding: 0 !important;
    border-radius: 0 !important;
}

.searchbar-in-header :deep(.search_bar form) {
    background: transparent !important;
    box-shadow: none !important;
    border-radius: 0 !important;
    padding: 0 !important;
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 1rem;
    align-items: end;
}

.searchbar-in-header :deep(.search_bar form input),
.searchbar-in-header :deep(.search_bar form select) {
    background: white !important;
}

.searchbar-in-header :deep(label) {
    display: none;
}

.hero-search {
    max-width: none;
    margin: 2.8rem auto 0;
    position: relative;
    z-index: 10;
}

@media (max-width: 768px) {
    .hero-search {
        margin-top: 4rem;
        padding: 0;
    }
    .hero-typewriter {
        font-size: 0.75em;
    }
}

@media (max-width: 768px) {
    .hero-search .search_bar,
    .hero-search :deep(.search_bar) {
        border-radius: 0 !important;
    }
}

@media (max-width: 900px) {
    .hero {
        padding: 1.5rem 0vw 2rem;
    }

    .hero-wrapper {
        max-width: 100%;
    }

    .hero-left {
        padding: 3rem 2.4rem 3rem;
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
        opacity: 0.22;
    }


}

@media (max-width: 600px) {
    .hero {
        background-image: linear-gradient(135deg, rgba(12, 40, 56, 0.82), rgba(12, 40, 56, 0.52)),
            var(--hero-image);
        background-size: cover;
        background-position: center;
    }

    .hero-left {
        padding: 3.4rem 0 0;
        max-width: none;
    }

    .hero-trust {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem 1rem;
    }

    .hero-trust span {
        flex: 0 0 calc(50% - 0.5rem);
    }

    .hero-title {
        font-size: clamp(2rem, 8vw, 2.6rem);
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
    .hero-bg-image {
        display: none;
    }
