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

const newsletterEmail = ref('');
const newsletterError = ref('');
const newsletterSuccess = ref('');
const newsletterLoading = ref(false);

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

const submitNewsletter = async () => {
    if (newsletterLoading.value) return;
    newsletterError.value = '';
    newsletterSuccess.value = '';

    if (!newsletterEmail.value) {
        newsletterError.value = 'Please enter your email.';
        return;
    }

    newsletterLoading.value = true;

    try {
        await axios.post('/api/newsletter/subscriptions', {
            email: newsletterEmail.value,
            source: 'footer',
            locale: page.props.locale,
        });
        newsletterSuccess.value = 'Check your inbox to confirm your subscription.';
        newsletterEmail.value = '';
    } catch (error) {
        if (error?.response?.status === 409) {
            newsletterError.value = error.response?.data?.message || 'This email is already subscribed.';
        } else if (error?.response?.status === 422) {
            const message = error.response?.data?.errors?.email?.[0];
            newsletterError.value = message || 'Please enter a valid email.';
        } else {
            newsletterError.value = 'Unable to subscribe right now. Please try again.';
        }
    } finally {
        newsletterLoading.value = false;
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
    <div class="footer-shell">
        <div class="full-w-container">
            <div class="footer-top">
                <div class="footer-brand">
                    <Link class="w-full" href="/">
                    <ApplicationLogo logoColor="#FFFFFF" />
                    </Link>
                    <div class="footer-social">
                        <Link href="" @mouseenter="isFacebookHovered = true" @mouseleave="isFacebookHovered = false" class="footer-social-icon">
                            <img :src="facebookLogo" alt="Facebook" class="absolute inset-0 transition-opacity duration-300" :class="{ 'opacity-0': isFacebookHovered }" />
                            <img :src="facebookLogoColored" alt="Facebook Colored" class="relative transition-opacity duration-300" :class="{ 'opacity-0': !isFacebookHovered }" />
                        </Link>
                        <a href="https://www.instagram.com/vrooemofficial?igsh=ZXZkMTdycmN6Mmhz" target="_blank"
                            rel="noopener noreferrer" @mouseenter="isInstagramHovered = true" @mouseleave="isInstagramHovered = false" class="footer-social-icon">
                            <img :src="instagramLogo" alt="Instagram" class="absolute inset-0 transition-opacity duration-300" :class="{ 'opacity-0': isInstagramHovered }" />
                            <img :src="instagramLogoColored" alt="Instagram Colored" class="relative transition-opacity duration-300" :class="{ 'opacity-0': !isInstagramHovered }" />
                        </a>

                        <Link href="" @mouseenter="isTwitterHovered = true" @mouseleave="isTwitterHovered = false" class="footer-social-icon">
                            <img :src="twitterLogo" alt="Twitter" class="absolute inset-0 transition-opacity duration-300" :class="{ 'opacity-0': isTwitterHovered }" />
                            <img :src="twitterLogoColored" alt="Twitter Colored" class="relative transition-opacity duration-300" :class="{ 'opacity-0': !isTwitterHovered }" />
                        </Link>
                    </div>
                    <div class="footer-newsletter">
                        <span class="footer-newsletter-title">Subscribe to Newsletter</span>
                        <form class="footer-newsletter-form" @submit.prevent="submitNewsletter">
                            <input
                                v-model="newsletterEmail"
                                type="email"
                                placeholder="Email address"
                                class="footer-input"
                                :disabled="newsletterLoading"
                            />
                            <button class="footer-subscribe" type="submit" :disabled="newsletterLoading">
                                {{ newsletterLoading ? 'Sending...' : 'Subscribe' }}
                            </button>
                        </form>
                        <p v-if="newsletterError" class="footer-newsletter-hint is-error">
                            {{ newsletterError }}
                        </p>
                        <p v-if="newsletterSuccess" class="footer-newsletter-hint is-success">
                            {{ newsletterSuccess }}
                        </p>
                    </div>
                </div>
                <div class="footer-links">
                    <div class="col flex flex-col gap-6 max-[768px]:gap-4">
                        <label for="" class="footer-heading">Company</label>
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
                    <div class="col flex flex-col gap-6 max-[768px]:gap-4">
                        <label for="" class="footer-heading">Information</label>
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
                    <div class="col flex flex-col gap-6 max-[768px]:gap-4">
                        <label for="" class="footer-heading">Location</label>
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
                    <div class="col flex flex-col gap-6 max-[768px]:gap-4">
                        <label for="" class="footer-heading">Contact</label>
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

    <div class="footer-bottom">
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
.footer-shell {
    background: linear-gradient(135deg, #0c1f2b 0%, #153b4f 45%, #0a1822 100%);
    color: #f8fafc;
    position: relative;
    overflow: hidden;
}

.footer-shell::before {
    content: "";
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 20% 20%, rgba(46, 167, 173, 0.25), transparent 45%),
        radial-gradient(circle at 80% 70%, rgba(21, 59, 79, 0.35), transparent 55%);
    opacity: 0.7;
    pointer-events: none;
}

.footer-top {
    position: relative;
    z-index: 1;
    padding: 3rem 0;
    display: flex;
    justify-content: space-between;
    gap: 3rem;
    flex-wrap: wrap;
}

.footer-brand {
    width: min(450px, 100%);
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.footer-social {
    display: flex;
    gap: 1rem;
}

.footer-social-icon {
    position: relative;
    width: 42px;
    height: 42px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.16);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.footer-social-icon:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(46, 167, 173, 0.25);
}

.footer-newsletter {
    background: rgba(11, 27, 38, 0.6);
    border-radius: 20px;
    padding: 1.5rem;
    border: 1px solid rgba(46, 167, 173, 0.2);
    backdrop-filter: blur(10px);
    box-shadow: 0 18px 36px rgba(15, 23, 42, 0.28);
}

.footer-newsletter-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    display: block;
}

.footer-newsletter-form {
    display: flex;
    gap: 0.6rem;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 999px;
    padding: 0.4rem;
    overflow: hidden;
    align-items: stretch;
}

.footer-input {
    flex: 1;
    background: transparent;
    border: none;
    color: #ffffff;
    padding: 0.7rem 1rem;
    outline: none;
    min-height: 46px;
    box-sizing: border-box;
}

.footer-input::placeholder {
    color: rgba(248, 250, 252, 0.6);
}

.footer-subscribe {
    background: linear-gradient(135deg, #153b4f, #2ea7ad);
    border-radius: 999px;
    color: white;
    padding: 0.7rem 1.6rem;
    font-weight: 600;
    white-space: nowrap;
    border: none;
    height: 100%;
    min-height: 46px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
}

.footer-subscribe:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.footer-input:disabled {
    opacity: 0.7;
}

.footer-newsletter-hint {
    margin-top: 0.6rem;
    font-size: 0.85rem;
    line-height: 1.4;
}

.footer-newsletter-hint.is-error {
    color: rgba(248, 113, 113, 0.95);
}

.footer-newsletter-hint.is-success {
    color: rgba(134, 239, 172, 0.95);
}

.footer-links {
    flex: 1;
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 2rem;
    min-width: min(680px, 100%);
}

.footer-heading {
    font-size: 0.95rem;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: rgba(248, 250, 252, 0.8);
}

.footer-link-underline {
    display: inline-block;
    position: relative;
    color: rgba(248, 250, 252, 0.85);
    transition: color 0.2s ease;
}

.footer-link-underline::after {
    content: '';
    position: absolute;
    bottom: -4px;
    left: 0;
    height: 2px;
    width: 0;
    background-color: rgba(46, 167, 173, 0.8);
    transition: width 0.3s ease-in-out;
}

.footer-link-underline:hover {
    color: #ffffff;
}

.footer-link-underline:hover::after {
    width: 100%;
}

.footer-bottom {
    background: rgba(7, 16, 24, 0.95);
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    color: rgba(248, 250, 252, 0.8);
    padding: 1.5rem 0;
}

@media (max-width: 1024px) {
    .footer-links {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 768px) {
    .footer-top {
        padding: 2.5rem 0;
    }

    .footer-links {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .footer-newsletter-form {
        flex-direction: column;
        align-items: stretch;
        border-radius: 16px;
    }

    .footer-subscribe {
        width: 100%;
    }
}
</style>
