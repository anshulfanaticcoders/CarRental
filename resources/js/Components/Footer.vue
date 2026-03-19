<script setup>
import ApplicationLogo from "./ApplicationLogo.vue";
import { Link } from "@inertiajs/vue3";
import paypalLogos from "../../assets/paymentIcons.svg";
import { onMounted, ref, computed } from "vue";
import { usePage } from '@inertiajs/vue3';
import { Phone, Mail, MapPin, Facebook, Instagram, Twitter, Linkedin } from 'lucide-vue-next';

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
    <footer class="footer-shell">
        <div class="full-w-container">
            <!-- Top: Brand + Links -->
            <div class="footer-top">
                <div class="footer-brand">
                    <Link href="/">
                        <ApplicationLogo logoColor="#FFFFFF" />
                    </Link>
                    <p class="footer-brand-desc">Compare and book rental cars across Europe's best providers. Best prices guaranteed, free cancellation on most bookings.</p>
                    <div class="footer-social">
                        <a href="#" class="footer-social-icon" aria-label="Facebook">
                            <Facebook :size="18" />
                        </a>
                        <a href="https://www.instagram.com/vrooemofficial?igsh=ZXZkMTdycmN6Mmhz" target="_blank" rel="noopener noreferrer" class="footer-social-icon" aria-label="Instagram">
                            <Instagram :size="18" />
                        </a>
                        <a href="#" class="footer-social-icon" aria-label="Twitter">
                            <Twitter :size="18" />
                        </a>
                        <a href="#" class="footer-social-icon" aria-label="LinkedIn">
                            <Linkedin :size="18" />
                        </a>
                    </div>
                </div>
                <div class="footer-links">
                    <div class="footer-col">
                        <div class="footer-col-title">Company</div>
                        <ul>
                            <li><Link :href="route('pages.show', { locale: page.props.locale, slug: getTranslatedSlug('about-us') })" class="footer-link">About Us</Link></li>
                            <li><Link :href="route('blog', { locale: page.props.locale, country: page.props.country || 'us' })" class="footer-link">Blogs</Link></li>
                            <li><Link :href="route('faq.show', { locale: page.props.locale })" class="footer-link">FAQ</Link></li>
                            <li><Link :href="`/${page.props.locale}/contact-us`" class="footer-link">Contact Us</Link></li>
                        </ul>
                    </div>
                    <div class="footer-col">
                        <div class="footer-col-title">Information</div>
                        <ul>
                            <li><Link :href="route('pages.show', { locale: page.props.locale, slug: getTranslatedSlug('privacy-policy') })" class="footer-link">Privacy Policy</Link></li>
                            <li><Link :href="route('pages.show', { locale: page.props.locale, slug: getTranslatedSlug('terms-and-conditions') })" class="footer-link">Terms & Conditions</Link></li>
                            <li><Link :href="route('affiliate.register', { locale: $page.props.locale })" class="footer-link">Business</Link></li>
                        </ul>
                    </div>
                    <div class="footer-col">
                        <div class="footer-col-title">Locations</div>
                        <ul>
                            <li v-for="place in footerPlaces" :key="place.id">
                                <a :href="`/${page.props.locale}/s?where=${encodeURIComponent(place.place_name)}`"
                                    @click.prevent="navigateToSearch(place)" class="footer-link">{{ place.place_name }}</a>
                            </li>
                            <li v-if="footerPlaces.length === 0">
                                <Link :href="route('welcome', { locale: page.props.locale })" class="footer-link">No locations available</Link>
                            </li>
                        </ul>
                    </div>
                    <div class="footer-col">
                        <div class="footer-col-title">Contact</div>
                        <ul>
                            <li v-if="footerContactInfo.phone_number" class="footer-contact-item">
                                <Phone :size="16" class="footer-contact-icon" />
                                <a :href="`tel:${footerContactInfo.phone_number}`" class="footer-link">{{ footerContactInfo.phone_number }}</a>
                            </li>
                            <li v-if="footerContactInfo.email" class="footer-contact-item">
                                <Mail :size="16" class="footer-contact-icon" />
                                <a :href="`mailto:${footerContactInfo.email}`" class="footer-link">{{ footerContactInfo.email }}</a>
                            </li>
                            <li v-if="footerContactInfo.address" class="footer-contact-item" style="align-items: flex-start;">
                                <MapPin :size="16" class="footer-contact-icon" style="margin-top: 3px;" />
                                <span class="whitespace-pre-line" style="color: rgba(226,232,240,0.7); font-size: 0.9rem; line-height: 1.5;">{{ footerContactInfo.address }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Newsletter Strip -->
            <div class="footer-newsletter">
                <div class="footer-nl-text">
                    <h3>Stay in the loop</h3>
                    <p>Get exclusive deals, travel tips, and new destination alerts.</p>
                </div>
                <div class="footer-nl-form-wrap">
                    <form class="footer-nl-form" @submit.prevent="submitNewsletter">
                        <input v-model="newsletterEmail" type="email" placeholder="Your email address" :disabled="newsletterLoading" />
                        <button type="submit" :disabled="newsletterLoading">
                            {{ newsletterLoading ? 'Sending...' : 'Subscribe' }}
                        </button>
                    </form>
                    <p v-if="newsletterError" class="footer-nl-hint is-error">{{ newsletterError }}</p>
                    <p v-if="newsletterSuccess" class="footer-nl-hint is-success">{{ newsletterSuccess }}</p>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="footer-bottom">
                <span class="footer-copyright">&copy; {{ new Date().getFullYear() }} Vrooem. All rights reserved.</span>
                <div class="footer-bottom-right">
                    <img :src="paypalLogos" alt="Payment methods" class="footer-payments" />
                </div>
            </div>
        </div>
    </footer>
</template>

<style>
.footer-shell {
    background: #0e2a3a;
    color: #e2e8f0;
    position: relative;
    overflow: hidden;
}

.footer-shell::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba(34, 211, 238, 0.08) 0%, transparent 70%);
    pointer-events: none;
}

.footer-shell > .full-w-container {
    position: relative;
    z-index: 1;
}

/* Top: Brand + Links */
.footer-top {
    display: flex;
    gap: 64px;
    padding: 64px 0 48px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.footer-brand {
    flex: 0 0 340px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.footer-brand-desc {
    font-size: 0.95rem;
    line-height: 1.7;
    color: rgba(226, 232, 240, 0.7);
    font-family: 'IBM Plex Sans', serif;
}

.footer-social {
    display: flex;
    gap: 10px;
    margin-top: 4px;
}

.footer-social-icon {
    position: relative;
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.1);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.25s cubic-bezier(0.22, 1, 0.36, 1);
}

.footer-social-icon:hover {
    background: rgba(34, 211, 238, 0.15);
    border-color: rgba(34, 211, 238, 0.3);
    transform: translateY(-2px);
}

/* Link Columns */
.footer-links {
    flex: 1;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 32px;
}

.footer-col ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-col li {
    margin-bottom: 14px;
}

.footer-col-title {
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    color: #22d3ee;
    font-weight: 700;
    margin-bottom: 20px;
}

.footer-link {
    color: rgba(226, 232, 240, 0.7);
    text-decoration: none;
    font-size: 0.9rem;
    transition: color 0.2s, padding-left 0.2s;
}

.footer-link:hover {
    color: #ffffff;
    padding-left: 4px;
}

.footer-contact-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.footer-contact-icon {
    color: rgba(34, 211, 238, 0.7);
    flex-shrink: 0;
}

/* Newsletter Strip */
.footer-newsletter {
    padding: 32px 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 32px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    flex-wrap: wrap;
}

.footer-nl-text h3 {
    font-size: 1.15rem;
    font-weight: 700;
    color: #fff;
    margin: 0 0 4px;
}

.footer-nl-text p {
    font-size: 0.85rem;
    color: rgba(226, 232, 240, 0.6);
    margin: 0;
}

.footer-nl-form {
    display: flex;
    gap: 8px;
}

.footer-nl-form-wrap {
    display: flex;
    flex-direction: column;
    gap: 8px;
    align-items: flex-start;
}

.footer-nl-form input {
    background: rgba(255, 255, 255, 0.07);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 12px;
    padding: 12px 18px;
    color: #fff;
    font-size: 0.9rem;
    width: 280px;
    outline: none;
    font-family: inherit;
    transition: border-color 0.2s;
}

.footer-nl-form input::placeholder {
    color: rgba(255, 255, 255, 0.4);
}

.footer-nl-form input:focus {
    border-color: rgba(34, 211, 238, 0.4);
}

.footer-nl-form input:disabled {
    opacity: 0.7;
}

.footer-nl-form button {
    background: linear-gradient(135deg, #22d3ee, #06b6d4);
    color: #0e2a3a;
    border: none;
    border-radius: 12px;
    padding: 12px 28px;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    font-family: inherit;
    white-space: nowrap;
    transition: all 0.2s;
}

.footer-nl-form button:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 24px rgba(34, 211, 238, 0.3);
}

.footer-nl-form button:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.footer-nl-hint {
    margin: 0;
    font-size: 0.85rem;
    line-height: 1.4;
}

.footer-nl-hint.is-error {
    color: rgba(248, 113, 113, 0.95);
}

.footer-nl-hint.is-success {
    color: rgba(134, 239, 172, 0.95);
}

/* Bottom Bar */
.footer-bottom {
    padding: 24px 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.footer-copyright {
    font-size: 0.82rem;
    color: rgba(226, 232, 240, 0.45);
}

.footer-bottom-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

.footer-payments {
    height: 24px;
    opacity: 0.6;
    transition: opacity 0.2s;
}

.footer-payments:hover {
    opacity: 0.9;
}

/* Responsive */
@media (max-width: 1024px) {
    .footer-top {
        flex-direction: column;
        gap: 40px;
    }

    .footer-brand {
        flex: none;
    }

    .footer-links {
        grid-template-columns: repeat(2, 1fr);
    }

    .footer-newsletter {
        flex-direction: column;
        text-align: center;
    }

    .footer-nl-form {
        width: 100%;
        justify-content: center;
    }

    .footer-nl-form-wrap {
        width: 100%;
        align-items: center;
    }
}

@media (max-width: 768px) {
    .footer-top {
        padding: 48px 0 36px;
    }

    .footer-links {
        grid-template-columns: repeat(2, 1fr);
        gap: 24px;
    }

    .footer-nl-form {
        flex-direction: column;
    }

    .footer-nl-form input {
        width: 100%;
    }

    .footer-nl-hint {
        width: 100%;
        text-align: center;
    }

    .footer-bottom {
        flex-direction: column;
        gap: 16px;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .footer-links {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
}
</style>
