<script setup>
import { Link } from "@inertiajs/vue3";
import SchemaInjector from '@/Components/SchemaInjector.vue'; // Import SchemaInjector
import SeoHead from '@/Components/SeoHead.vue';
import heroImg from "../../assets/heroImage.jpg";
import Footer from '@/Components/Footer.vue'
import NewsletterSection from '@/Components/NewsletterSection.vue'
import wifiIcon from "../../assets/usb.svg";
import replacementIcon from "../../assets/carIcon.svg";
import protectionIcon from "../../assets/verification.svg";
import priceIcon from "../../assets/percentage-tag.svg";
import supportIcon from "../../assets/call.svg";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import HowItWorks from "@/Components/ReusableComponents/HowItWorks.vue";
import EsimSection from "@/Components/EsimSection.vue";
import Autoplay from 'embla-carousel-autoplay';
import { Skeleton } from '@/Components/ui/skeleton';
import WelcomeHero from '@/Components/Welcome/WelcomeHero.vue';
import AdvertisementSection from "@/Components/AdvertisementSection.vue";

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

const heroTrustItems = computed(() => [
    {
        id: 'hero_trust_1',
        icon: 'star',
        label: getHomepageLabel('hero_trust_1', 'Free Internet Included'),
    },
    {
        id: 'hero_trust_2',
        icon: 'bag',
        label: getHomepageLabel('hero_trust_2', 'Instant Replacement Guarantee'),
    },
    {
        id: 'hero_trust_3',
        icon: 'clock',
        label: getHomepageLabel('hero_trust_3', 'Fair Damage Protection'),
    },
    {
        id: 'hero_trust_4',
        icon: 'shield',
        label: getHomepageLabel('hero_trust_4', 'Best Price Guarantee'),
    },
    {
        id: 'hero_trust_5',
        icon: 'support',
        label: getHomepageLabel('hero_trust_5', '24/7 Worldwide Support'),
    },
    {
        id: 'hero_trust_6',
        icon: 'layers',
        label: getHomepageLabel('hero_trust_6', 'No Rushing, No Waiting'),
    },
]);

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

onMounted(() => {
    // Start the typing animation
    timer = setTimeout(typeWriter, 500);
});

onBeforeUnmount(() => {
    // Clean up the timer when component is destroyed
    if (timer) clearTimeout(timer);
});

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

useScrollAnimation('.blogs-trigger', '.blog-featured', {
    opacity: 0,
    y: 40,
    duration: 1,
});

useScrollAnimation('.blogs-trigger', '.blog-stack', {
    opacity: 0,
    y: 30,
    duration: 1,
});

useScrollAnimation('.blogs-trigger', '.blog-cta', {
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
        <WelcomeHero
            :hero-badge="heroBadge"
            :animated-tagline="animatedTagline"
            :hero-subtitle="heroSubtitle"
            :displayed-text="displayedText"
            :hero-image="heroImageSource"
            :trust-items="heroTrustItems"
        />




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
            class="home-section home-section--light blogs blogs-trigger">
            <div class="full-w-container blog-section-inner">

                <!-- Header -->
                <div class="blog-title-section text-center flex flex-col items-center py-8 max-[768px]:py-0 max-[768px]:mb-6 mb-12">
                    <span class="text-[1.25rem] text-customPrimaryColor">-{{ _p('blogs_title') }}-</span>
                    <h3 class="max-w-[883px] text-customDarkBlackColor max-[768px]:max-w-full">
                        {{ _p('blogs_subtitle') }}
                    </h3>
                </div>

                <!-- Blog Grid -->
                <div class="blog-grid">

                    <!-- Featured Hero (Left) -->
                    <Link
                        :href="route('blog.show', { locale: page.props.locale, country: blogs[0].canonical_country || (page.props.country || 'us'), blog: blogs[0].translated_slug })"
                        class="blog-featured">
                        <div class="blog-featured-img">
                            <img :src="blogs[0].image" :alt="blogs[0].title">
                        </div>
                        <div class="blog-featured-overlay"></div>
                        <div class="blog-featured-content">
                            <div class="blog-meta">
                                <span class="blog-date blog-date--light">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    {{ formatDate(blogs[0].created_at) }}
                                </span>
                            </div>
                            <h4 class="blog-featured-title">{{ blogs[0].title }}</h4>
                            <p v-if="blogs[0].excerpt" class="blog-featured-excerpt">{{ blogs[0].excerpt }}</p>
                            <span class="blog-read-link">
                                Read Article
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                            </span>
                        </div>
                    </Link>

                    <!-- Stacked Cards (Right) -->
                    <div class="blog-stack">
                        <Link
                            v-for="index in Math.min(3, blogs.length - 1)"
                            :key="index"
                            :href="route('blog.show', { locale: page.props.locale, country: blogs[index].canonical_country || (page.props.country || 'us'), blog: blogs[index].translated_slug })"
                            class="blog-card">
                            <div class="blog-card-img">
                                <img :src="blogs[index].image" :alt="blogs[index].title">
                            </div>
                            <div class="blog-card-body">
                                <span class="blog-date blog-date--dark">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    {{ formatDate(blogs[index].created_at) }}
                                </span>
                                <h4 class="blog-card-title">{{ blogs[index].title }}</h4>
                                <p v-if="blogs[index].excerpt" class="blog-card-excerpt">{{ blogs[index].excerpt }}</p>
                                <span class="blog-card-read">
                                    Read Story
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                </span>
                            </div>
                        </Link>
                    </div>

                </div>

                <!-- CTA -->
                <div class="blog-cta-wrap blog-cta">
                    <Link :href="route('blog', { locale: page.props.locale, country: page.props.country || 'us' })"
                        class="button-secondary text-center w-[10rem] hover:bg-customPrimaryColor hover:text-white">
                        {{ _p('more_blogs') }}
                    </Link>
                </div>

            </div>
        </section>


        <!------------------------------ <Ends>  -------------------------------------------------->


        <!-- ------------------------FAQ Section-------------------------------- -->
        <!------------------------------ <Start>  -------------------------------------------------->
        <section id="faq" v-if="props.faqs && props.faqs.length">
            <!-- Pass the faqs prop to the Faq component -->
            <Faq :faqs="props.faqs" />
        </section>
        <!-- ---------------------------<End>---------------------------------------------------->

        <NewsletterSection />
    </main>

    <Footer />
</template>

<style scoped>

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

.carousel :deep(.cardContent) {
    padding: 0rem;
}

.category-carousel :deep(.next-btn) {
    right: 15% !important;
}

.popular-places :deep(button) {
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
.blog-featured,
.blog-stack,
.blog-cta {
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

/* ========================================
   BLOG SECTION — Magazine Editorial
   ======================================== */
.blog-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    align-items: stretch;
}

.blog-featured {
    position: relative;
    border-radius: 0.75rem;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    min-height: 574px;
    cursor: pointer;
    background: #0a1d28;
}

.blog-featured-img {
    position: absolute;
    inset: 0;
    overflow: hidden;
}

.blog-featured-img img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    transition: transform 0.7s cubic-bezier(0.22, 1, 0.36, 1), filter 0.7s cubic-bezier(0.22, 1, 0.36, 1);
}

.blog-featured:hover .blog-featured-img img {
    transform: scale(1.05);
    filter: brightness(0.8);
}

.blog-featured-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(10, 29, 40, 0.9) 0%, rgba(10, 29, 40, 0.55) 35%, rgba(10, 29, 40, 0.08) 65%, transparent 100%);
    z-index: 1;
}

.blog-featured-content {
    position: relative;
    z-index: 2;
    margin-top: auto;
    padding: 36px 32px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.blog-meta {
    display: flex;
    align-items: center;
    gap: 12px;
}

.blog-date {
    font-size: 0.82rem;
    font-weight: 400;
    display: flex;
    align-items: center;
    gap: 6px;
}

.blog-date svg {
    width: 14px;
    height: 14px;
}

.blog-date--light {
    color: #cbd5e1;
}

.blog-date--dark {
    color: rgba(43, 43, 43, 0.6);
}

.blog-featured-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 2rem;
    font-weight: 700;
    line-height: 1.25;
    color: #ffffff;
    letter-spacing: -0.015em;
    transition: color 0.3s ease;
}

.blog-featured:hover .blog-featured-title {
    color: #dceef6;
}

.blog-featured-excerpt {
    font-size: 0.95rem;
    font-weight: 300;
    line-height: 1.65;
    color: #cbd5e1;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.blog-read-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.85rem;
    font-weight: 600;
    color: #dceef6;
    margin-top: 4px;
}

.blog-read-link svg {
    width: 18px;
    height: 18px;
    transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1);
}

.blog-featured:hover .blog-read-link svg {
    transform: translateX(6px);
}

/* Stacked Cards */
.blog-stack {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.blog-card {
    --blog-card-media-width: 200px;
    --blog-card-height: 184px;
    display: grid;
    grid-template-columns: var(--blog-card-media-width) minmax(0, 1fr);
    gap: 0;
    background: #ffffff;
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.22, 1, 0.36, 1);
    position: relative;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(21, 59, 79, 0.04);
    border: 1px solid transparent;
    min-height: var(--blog-card-height);
    height: var(--blog-card-height);
}

.blog-card:hover {
    box-shadow: 0 12px 32px rgba(21, 59, 79, 0.08), 0 2px 8px rgba(21, 59, 79, 0.04);
    transform: translateY(-3px);
    border-color: #dceef6;
}

.blog-card::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: #153b4f;
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.5s cubic-bezier(0.22, 1, 0.36, 1);
    border-radius: 0 0 0.75rem 0.75rem;
}

.blog-card:hover::after {
    transform: scaleX(1);
}

.blog-card-img {
    position: relative;
    width: 100%;
    height: 100%;
    overflow: hidden;
    background: #eef4f7;
}

.blog-card-img img {
    position: absolute;
    inset: 0;
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    transition: transform 0.6s cubic-bezier(0.22, 1, 0.36, 1);
}

.blog-card:hover .blog-card-img img {
    transform: scale(1.08);
}

.blog-card-body {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 10px;
    min-width: 0;
    overflow: hidden;
    padding: 20px;
}

.blog-card-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.1rem;
    font-weight: 700;
    line-height: 1.35;
    color: #2b2b2b;
    letter-spacing: -0.01em;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    transition: color 0.3s ease;
}

.blog-card:hover .blog-card-title {
    color: #153b4f;
}

.blog-card-excerpt {
    font-size: 0.88rem;
    font-weight: 300;
    line-height: 1.55;
    color: #64748b;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.blog-card-read {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.82rem;
    font-weight: 600;
    color: #153b4f;
    margin-top: 2px;
}

.blog-card-read svg {
    width: 1.25rem;
    height: 1.25rem;
    transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1);
}

.blog-card:hover .blog-card-read svg {
    transform: translateX(4px);
}

.blog-cta-wrap {
    display: flex;
    justify-content: center;
    margin-top: 48px;
}

/* Blog Responsive */
@media (max-width: 1024px) {
    .blog-card {
        --blog-card-media-width: 160px;
        --blog-card-height: 168px;
    }

    .blog-card-img {
        width: 100%;
    }

    .blog-card-title {
        font-size: 1rem;
        padding-bottom:3em;
    }

    .blog-card-excerpt {
        display: none;
    }
}

@media (max-width: 768px) {
    .blog-grid {
        grid-template-columns: 1fr;
        gap: 24px;
    }
    .blog-featured {
        min-height: 380px;
    }
    .blog-featured-content {
        padding: 24px 20px;
    }
    .blog-featured-title {
        font-size: 1.25rem;
    }
    .blog-featured-excerpt {
        display: none;
    }

    .blog-card {
        --blog-card-media-width: 40%;
        --blog-card-height: 152px;
    }

    .blog-card-img {
        width: 100%;
        min-height: 0;
    }

    .blog-card-body {
        padding: 16px;
    }
    .blog-card-title {
        font-size: 1rem;
    }
    .blog-card-excerpt {
        display: none;
    }
    .blog-cta-wrap {
        margin-top: 32px;
    }
}

@media (max-width: 480px) {
    .blog-featured {
        min-height: 320px;
    }

    .blog-card {
        --blog-card-media-width: 35%;
        --blog-card-height: 132px;
    }

    .blog-card-img {
        width: 100%;
    }
}

.category-carousel :deep(.disabled\:pointer-events-none:disabled) {
    pointer-events: unset;
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
    .category-carousel :deep(.next-btn) {
        right: 10% !important;
        display: none;

    }

    .category-carousel :deep(.prev-btn) {
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
</style>
