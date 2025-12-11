<script setup>
import ApplicationLogo from "./ApplicationLogo.vue";
import { Link } from "@inertiajs/vue3";
import facebookLogo from "../../assets/Facebook.svg";
import facebookLogoColored from "../../assets/facebookColored.png";
import twitterLogo from "../../assets/Twitter.svg";
import twitterLogoColored from "../../assets/twitterColored.png";
import instagramLogo from "../../assets/Instagram.svg";
import instagramLogoColored from "../../assets/instagramColored.png";
import paypalLogos from "../../assets/paymentIcons.svg";
import { onMounted, ref, computed } from "vue";
import { usePage } from '@inertiajs/vue3';
import { Phone, Mail, MapPin } from 'lucide-vue-next';

const isFacebookHovered = ref(false);
const isTwitterHovered = ref(false);
const isInstagramHovered = ref(false);

const page = usePage();
const pages = computed(() => page.props.pages);

const props = defineProps({
    locale: {
        type: String,
        default: 'en',
    },
    country: {
        type: String,
        default: 'us',
    },
});


const getTranslatedSlug = (pageSlug) => {
    let targetPage = null;
    const currentLocale = page.props.locale || 'en';
    const defaultLocale = 'en'; // Assuming 'en' is the default/main locale for the base slug

    // Iterate through the values of the pages object to find the target page
    // The keys of pages.value are translated slugs, but the input pageSlug is the main slug (e.g., 'about-us')
    // So, we need to find the page whose 'en' translation slug matches the input pageSlug
    if (pages.value) {
        for (const key in pages.value) {
            const pageItem = pages.value[key];
            if (pageItem && pageItem.translations && Array.isArray(pageItem.translations)) {
                const defaultTranslation = pageItem.translations.find(t => t.locale === defaultLocale && t.slug === pageSlug);
                if (defaultTranslation) {
                    targetPage = pageItem;
                    break;
                }
            }
        }
    }

    // If targetPage is not found or translations are not available, return the original slug
    if (!targetPage || !targetPage.translations || !Array.isArray(targetPage.translations)) {
        return pageSlug;
    }

    // Find the translation for the current locale
    const translation = targetPage.translations.find(t => t.locale === currentLocale);

    // Return the translated slug if found, otherwise return the original slug
    return translation ? translation.slug : pageSlug;
};

// Fetch footer places and categories data
const footerPlaces = ref([]);
const footerContactInfo = ref({
    phone_number: '',
    email: '',
    address: ''
});

import { defineExpose } from 'vue';


const unifiedLocations = ref([]);

const navigateToSearch = (place) => {
    updateSearchUrl(place);
    const searchUrl = sessionStorage.getItem('searchurl');
    if (searchUrl) {
        window.location.href = `/${page.props.locale}${searchUrl}`;
    }
};

const updateSearchUrl = (place) => {
    const location = unifiedLocations.value.find(l => l.name === place.place_name);

    if (location && location.providers && location.providers.length > 0) {
        const provider = location.providers[0];

        const today = new Date();
        const pickupDate = new Date(today);
        pickupDate.setDate(today.getDate() + 1);
        const returnDate = new Date(pickupDate);
        returnDate.setDate(pickupDate.getDate() + 1);

        const formatDate = (date) => date.toISOString().split('T')[0];

        const params = {
            where: location.name,
            latitude: location.latitude,
            longitude: location.longitude,
            city: location.city,
            country: location.country,
            provider: provider.provider,
            provider_pickup_id: provider.pickup_id,
            date_from: formatDate(pickupDate),
            date_to: formatDate(returnDate),
            start_time: '09:00',
            end_time: '09:00',
            age: 35,
        };
        const urlParams = new URLSearchParams(params).toString();
        sessionStorage.setItem('searchurl', `/s?${urlParams}`);
    } else {
        const urlParams = new URLSearchParams({
            where: place.place_name,
        }).toString();
        sessionStorage.setItem('searchurl', `/s?${urlParams}`);
    }
};



defineExpose({
    updateSearchUrl,

    navigateToSearch
});

onMounted(async () => {
    try {
        const [placesResponse, locationsResponse, contactInfoResponse] = await Promise.all([
            axios.get(`/${page.props.locale}/api/footer-places`),
            axios.get('/unified_locations.json'),
            axios.get('/api/footer-contact-info')
        ]);

        footerPlaces.value = placesResponse.data;
        footerContactInfo.value = contactInfoResponse.data;
        unifiedLocations.value = locationsResponse.data;
    } catch (error) {
        console.error('Failed to fetch footer data:', error);
    }
});
</script>

<template>
    <div class="bg-customPrimaryColor py-customVerticalSpacing text-customPrimaryColor-foreground
        max-[768px]:py-0">
        <div class="full-w-container">
            <div class="column py-[3rem] flex justify-between gap-6
            max-[768px]:flex-col">
                <div class="col w-[30%] flex flex-col gap-5
                max-[768px]:w-full">
                    <Link class="w-full" href="/">
                    <ApplicationLogo logoColor="#FFFFFF" />
                    </Link>
                    <div class="socialIcons flex gap-6">
                        <Link href="" @mouseenter="isFacebookHovered = true" @mouseleave="isFacebookHovered = false" class="relative">
                            <img :src="facebookLogo" alt="Facebook" class="absolute inset-0 transition-opacity duration-300" :class="{ 'opacity-0': isFacebookHovered }" />
                            <img :src="facebookLogoColored" alt="Facebook Colored" class="relative transition-opacity duration-300" :class="{ 'opacity-0': !isFacebookHovered }" />
                        </Link>
                        <a href="https://www.instagram.com/vrooemofficial?igsh=ZXZkMTdycmN6Mmhz" target="_blank"
                            rel="noopener noreferrer" @mouseenter="isInstagramHovered = true" @mouseleave="isInstagramHovered = false" class="relative">
                            <img :src="instagramLogo" alt="Instagram" class="absolute inset-0 transition-opacity duration-300" :class="{ 'opacity-0': isInstagramHovered }" />
                            <img :src="instagramLogoColored" alt="Instagram Colored" class="relative transition-opacity duration-300" :class="{ 'opacity-0': !isInstagramHovered }" />
                        </a>

                        <Link href="" @mouseenter="isTwitterHovered = true" @mouseleave="isTwitterHovered = false" class="relative">
                            <img :src="twitterLogo" alt="Twitter" class="absolute inset-0 transition-opacity duration-300" :class="{ 'opacity-0': isTwitterHovered }" />
                            <img :src="twitterLogoColored" alt="Twitter Colored" class="relative transition-opacity duration-300" :class="{ 'opacity-0': !isTwitterHovered }" />
                        </Link>
                    </div>
                    <div class="column flex flex-col gap-4 mt-[1rem]">
                        <span class="text-[1.5rem] max-[768px]:text-[1.2rem]">Subscribe to Newsletter</span>
                        <div class="max-w-[450px]">
                            <div class="relative">
                                <input type="text" placeholder="Email address"
                                    class="bg-transparent rounded-[100px] text-white p-3 border-[1px] w-full pl-4" />
                                <button
                                    class="h-full absolute right-0 button-tertiary w-[30%] max-[768px]:text-[0.875rem]">
                                    Subscribe
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col w-[50%] grid grid-cols-4 gap-4
                max-[768px]:grid max-[768px]:grid-cols-2 max-[768px]:w-full max-[768px]:mt-5">
                    <div class="col flex flex-col gap-8 max-[768px]:gap-4">
                        <label for="" class="text-[1.25rem] font-medium max-[768px]:text-[1rem]">Company</label>
                        <ul class="flex flex-col gap-4 max-[768px]:text-[0.875rem]">
                            <li class="relative group">
                                <Link
                                    :href="route('pages.show', { locale: page.props.locale, slug: getTranslatedSlug('about-us') })"
                                    class="footer-link-underline">
                                About Us</Link>
                            </li>
                            <li class="relative group">
                                <Link :href="route('blog', { locale: page.props.locale, country: page.props.country || 'us' })"
                                    class="footer-link-underline">Blogs</Link>
                            </li>
                            <li class="relative group">
                                <Link :href="route('faq.show', { locale: page.props.locale })"
                                    class="footer-link-underline">FAQ</Link>
                            </li>
                            <li class="relative group">
                                <Link :href="route('contact-us', { locale: page.props.locale })"
                                    class="footer-link-underline">Contact Us</Link>
                            </li>
                        </ul>
                    </div>
                    <div class="col flex flex-col gap-8 max-[768px]:gap-4">
                        <label for="" class="text-[1.25rem] font-medium max-[768px]:text-[1rem]">Information</label>
                        <ul class="flex flex-col gap-4 max-[768px]:text-[0.875rem]">
                            <li class="relative group">
                                <Link
                                    :href="route('pages.show', { locale: page.props.locale, slug: getTranslatedSlug('privacy-policy') })"
                                    class="footer-link-underline">
                                Privacy Policy</Link>
                            </li>
                            <li class="relative group">
                                <Link
                                    :href="route('pages.show', { locale: page.props.locale, slug: getTranslatedSlug('terms-and-conditions') })"
                                    class="footer-link-underline">
                                Terms & Conditions</Link>
                            </li>
                            <li class="relative group">
                                <Link :href="`/${$page.props.locale}/business/register`" class="footer-link-underline">Business</Link>
                            </li>
                            <li class="relative group">
                                <!-- <a href="https://vrooem.tapfiliate.com" class="footer-link-underline">Became a Affiliate</a> -->
                            </li>
                        </ul>
                    </div>
                    <div class="col flex flex-col gap-8 max-[768px]:gap-4">
                        <label for="" class="text-[1.25rem] font-medium max-[768px]:text-[1rem]">Location</label>
                        <ul class="flex flex-col gap-4 max-[768px]:text-[0.875rem]">
                            <li v-for="place in footerPlaces" :key="place.id" class="relative group">
                                <a
                                    :href="`/${page.props.locale}/s?where=${encodeURIComponent(place.place_name)}`"
                                    @click.prevent="navigateToSearch(place)"
                                    class="footer-link-underline">
                                {{ place.place_name }}
                                </a>
                            </li>
                            <!-- Fallback if no places are selected -->
                            <li v-if="footerPlaces.length === 0" class="relative group">
                                <Link :href="route('welcome', { locale: page.props.locale })"
                                    class="footer-link-underline">No locations available
                                </Link>
                            </li>
                        </ul>
                    </div>
                    <div class="col flex flex-col gap-8 max-[768px]:gap-4">
                        <label for="" class="text-[1.25rem] font-medium max-[768px]:text-[1rem]">Contact</label>
                        <ul class="flex flex-col gap-4 max-[768px]:text-[0.875rem]">
                            <li v-if="footerContactInfo.phone_number" class="flex gap-2 items-center">
                                <Phone :size="20" />
                                <a :href="`tel:${footerContactInfo.phone_number}`" class="footer-link-underline">{{ footerContactInfo.phone_number }}</a>
                            </li>
                            <li v-if="footerContactInfo.email" class="flex gap-2 items-center">
                                <Mail :size="20" />
                                <a :href="`mailto:${footerContactInfo.email}`" class="footer-link-underline">{{ footerContactInfo.email }}</a>
                            </li>
                            <li v-if="footerContactInfo.address" class="flex gap-2 items-start">
                                <MapPin :size="20" class="shrink-0 mt-1" />
                                <span class="whitespace-pre-line">{{ footerContactInfo.address }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-customDarkBlackColor py-[2rem] text-white">
        <div
            class="full-w-container flex justify-between items-center max-[768px]:flex-col max-[768px]:justify-center max-[768px]:gap-5">
            <div class="column max-[768px]:flex">
                <span
                    class="text-[1.25rem] max-[768px]:text-[0.95rem] max-[768px]:w-full max-[768px]:text-center">Copyright
                    @ 2025 Vrooem, All rights reserved.</span>
            </div>
            <div class="column">
                <img :src=paypalLogos alt="">
            </div>
        </div>
    </div>
</template>

<style>
.footer-link-underline {
    display: inline-block; /* Ensures the link wraps its content */
    position: relative;
}

.footer-link-underline::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    height: 2px;
    width: 0; /* Initial width */
    background-color: white;
    transition: width 0.3s ease-in-out;
}

.footer-link-underline:hover::after {
    width: 100%; /* Stretches to content width of the inline-block parent */
}
</style>
