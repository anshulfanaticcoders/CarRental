<script setup>
import { Link, usePage, Head, router } from "@inertiajs/vue3";
import { computed, onMounted, ref, watch, nextTick, onBeforeUnmount } from "vue";
import Footer from "@/Components/Footer.vue";
import carIcon from "../../assets/carIcon.svg";
import mileageIcon from "../../assets/mileageIcon.svg";
import check from "../../assets/Check.svg";
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

// Icons
import doorIcon from "../../assets/door.svg";
import fuelIcon from "../../assets/fuel.svg";
import transmisionIcon from "../../assets/transmision.svg";
import peopleIcon from "../../assets/people.svg";
import MapPin from "../../assets/MapPin.svg";
import carguaranteeIcon from "../../assets/carguarantee.png";
import locationPinIcon from "../../assets/locationPin.svg";
import ShareIcon from "../../assets/ShareNetwork.svg";
import pickupLocationIcon from "../../assets/pickupLocationIcon.svg";
import partnersIcon from "../../assets/partners.svg";
import offerIcon from "../../assets/percentage-tag.svg";

// UI components
import VueDatepicker from '@vuepic/vue-datepicker';
import { useToast } from 'vue-toastification';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/Components/ui/dialog";
import Card from "@/Components/ui/card/Card.vue";
import CardContent from "@/Components/ui/card/CardContent.vue";
import { ChevronRight, ImageIcon, ZoomIn } from 'lucide-vue-next';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import { Button } from "@/Components/ui/button";
import Lightbox from "@/Components/Lightbox.vue";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import { Vue3Lottie } from 'vue3-lottie';
import universalLoader from '../../../public/animations/universal-loader.json';
import { useCurrency } from '@/composables/useCurrency';

const isBooking = ref(false);
const currencySymbols = ref({});
const exchangeRates = ref(null);
const { selectedCurrency, supportedCurrencies, changeCurrency } = useCurrency();

const symbolToCodeMap = {
    '$': 'USD',
    '€': 'EUR',
    '£': 'GBP',
    '¥': 'JPY',
    'A$': 'AUD',
    'C$': 'CAD',
    'Fr': 'CHF',
    'HK$': 'HKD',
    'S$': 'SGD',
    'kr': 'SEK',
    '₩': 'KRW',
    'kr': 'NOK',
    'NZ$': 'NZD',
    '₹': 'INR',
    'Mex$': 'MXN',
    'R': 'ZAR',
    'AED': 'AED'
};

const props = defineProps({
    vehicle: Object,
    locationInfo: Object,
    searchParams: Object,
    locale: String,
});

const page = usePage();
const toast = useToast();
const map = ref(null);
const mapContainerRef = ref(null);
const isLoading = ref(false);
const imageError = ref(false);

// SEO Meta
const seoTitle = computed(() => {
    const vehicleName = props.vehicle?.model || 'Adobe Vehicle';
    const location = props.locationInfo?.label || 'Adobe Car Rental';
    return `${vehicleName} - ${location} | Adobe Car Rental`;
});

const seoDescription = computed(() => {
    const vehicleName = props.vehicle?.model || 'Adobe Vehicle';
    const features = [];
    if (props.vehicle?.passengers) features.push(`${props.vehicle.passengers} seats`);
    if (props.vehicle?.type) features.push(props.vehicle.type);
    if (props.vehicle?.traction) features.push(props.vehicle.traction + ' drive');

    return `Rent ${vehicleName} from Adobe Car Rental. Features include ${features.join(', ')}. Best rates guaranteed.`;
});

const seoKeywords = computed(() => {
    return `${props.vehicle?.brand || 'Adobe'}, ${props.vehicle?.model || 'Vehicle'}, Adobe car rental, ${props.locationInfo?.label || 'Costa Rica'}, rent car, vehicle hire`;
});

const currentUrl = computed(() => page.props.ziggy?.location || window.location.href);
const canonicalUrl = computed(() => currentUrl.value);

// Fetch exchange rates
const fetchExchangeRates = async () => {
    try {
        const response = await fetch(`https://v6.exchangerate-api.com/v6/01b88ff6c6507396d707e4b6/latest/USD`);
        const data = await response.json();
        if (data.result === 'success') {
            exchangeRates.value = data.conversion_rates;
        } else {
            console.error('Failed to fetch exchange rates:', data['error-type']);
        }
    } catch (error) {
        console.error('Error fetching exchange rates:', error);
    }
};

const convertCurrency = (price, fromCurrency) => {
    const numericPrice = parseFloat(price);
    if (isNaN(numericPrice)) {
        return 0; // Return 0 if price is not a number
    }

    let fromCurrencyCode = fromCurrency;
    if (symbolToCodeMap[fromCurrency]) {
        fromCurrencyCode = symbolToCodeMap[fromCurrency];
    }

    if (!exchangeRates.value || !fromCurrencyCode || !selectedCurrency.value) {
        return numericPrice; // Return original price if rates not loaded or currencies are invalid
    }
    const rateFrom = exchangeRates.value[fromCurrencyCode];
    const rateTo = exchangeRates.value[selectedCurrency.value];
    if (rateFrom && rateTo) {
        return (numericPrice / rateFrom) * rateTo;
    }
    return numericPrice; // Fallback to original price if conversion is not possible
};

const getCurrencySymbol = (code) => {
    return currencySymbols.value[code] || '$'; // Use fetched symbol or default to '$'
};

// Map initialization
onMounted(async () => {
    // Fetch exchange rates and currency symbols
    await fetchExchangeRates();

    try {
        const response = await fetch('/currency.json');
        const data = await response.json();
        currencySymbols.value = data.reduce((acc, curr) => {
            acc[curr.code] = curr.symbol;
            return acc;
        }, {});
    } catch (error) {
        console.error("Error loading currency symbols:", error);
    }

    if (props.locationInfo?.latitude && props.locationInfo?.longitude) {
        nextTick(() => {
            initMap();
        });
    }
});

onBeforeUnmount(() => {
    if (map.value) {
        map.value.remove();
        map.value = null;
    }
});

const initMap = () => {
    if (!props.locationInfo?.latitude || !props.locationInfo?.longitude || !mapContainerRef.value) {
        console.warn('Map initialization skipped: Missing location data or map container not ready.');
        return;
    }

    if (map.value) {
        map.value.remove();
    }

    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: markerIcon2x,
        iconUrl: markerIcon,
        shadowUrl: markerShadow,
    });

    map.value = L.map(mapContainerRef.value, {
        zoomControl: true,
        maxZoom: 18,
        minZoom: 4,
        preferCanvas: true
    }).setView([parseFloat(props.locationInfo.latitude), parseFloat(props.locationInfo.longitude)], 15);

    const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
        detectRetina: true
    }).addTo(map.value);

    const createColoredIcon = (color) => {
        return L.divIcon({
            className: 'custom-div-icon',
            html: `
                <div class="marker-pin" style="filter: drop-shadow(0 4px 3px rgba(0,0,0,0.2));">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="${color}" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                    </svg>
                </div>
            `,
            iconSize: [40, 40],
            iconAnchor: [20, 40],
            popupAnchor: [0, -40]
        });
    };

    const pickupIcon = createColoredIcon('#22c55e'); // Green

    const pickupLatLng = [
        parseFloat(props.locationInfo?.latitude || 9.9374),
        parseFloat(props.locationInfo?.longitude || -84.0893)
    ]; // Default to San Jose coordinates if location info is missing

    L.marker(pickupLatLng, { icon: pickupIcon })
        .bindPopup(`
            <div class="text-center">
                <p class="font-semibold">Pickup: ${props.locationInfo?.name || 'Adobe Location'}</p>
                <p>${props.locationInfo?.below_label || ''}</p>
                <p class="text-xs text-gray-500">${props.locationInfo?.city || ''}, ${props.locationInfo?.country || ''}</p>
            </div>
        `)
        .addTo(map.value);

    setTimeout(() => {
        map.value.invalidateSize();
    }, 100);
};

// Format currency
const formatCurrency = (amount, fromCurrency = 'USD') => {
    if (!amount) return '$0.00';
    const originalCurrency = fromCurrency || 'USD';
    const convertedPrice = convertCurrency(amount, originalCurrency);
    const currencySymbol = getCurrencySymbol(selectedCurrency.value);
    return `${currencySymbol}${convertedPrice.toFixed(2)}`;
};

// Navigation methods
const startBooking = () => {
    isLoading.value = true;

    const bookingParams = new URLSearchParams({
        id: props.vehicle.id,
        ...props.searchParams
    });

    router.visit(route('adobe-booking.create', {
        locale: props.locale,
        id: props.vehicle.id
    }), {
        method: 'get',
        data: props.searchParams,
        onFinish: () => {
            isLoading.value = false;
        },
        onError: (errors) => {
            isLoading.value = false;
            toast.error('Unable to start booking. Please try again.');
        }
    });
};

// Share functionality
const shareVehicle = async () => {
    try {
        const shareData = {
            title: seoTitle.value,
            text: `Check out this Adobe vehicle: ${props.vehicle?.model}!`,
            url: currentUrl.value,
        };

        if (navigator.share) {
            await navigator.share(shareData);
        } else {
            const shareText = `${shareData.text}\n${shareData.url}`;
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(shareText);
                toast.success('Link copied to clipboard!');
            } else {
                window.prompt('Copy this link:', shareData.url);
            }
        }
    } catch (error) {
        console.error('Error sharing:', error);
        if (error.name !== 'AbortError') {
            toast.error('Failed to share. Please try another method.');
        }
    }
};

const handleImageError = () => {
    imageError.value = true;
};
</script>

<template>
    <Head>
        <title>{{ seoTitle }}</title>
        <meta name="description" :content="seoDescription" />
        <meta name="keywords" :content="seoKeywords" />
        <meta property="og:title" :content="seoTitle" />
        <meta property="og:description" :content="seoDescription" />
        <meta property="og:url" :content="currentUrl" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="seoTitle" />
        <meta name="twitter:description" :content="seoDescription" />
    </Head>

    <AuthenticatedHeaderLayout />

    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-customPrimaryColor to-blue-700 py-8 md:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-white text-2xl md:text-4xl font-bold mb-2">{{ props.vehicle?.model || 'Adobe Vehicle' }}</h1>
            <p class="text-blue-100 text-sm md:text-base mb-4">Quality rental with Adobe Car Rental</p>
            <div class="flex flex-wrap items-center justify-center gap-2">
                <div class="bg-white/20 backdrop-blur-sm rounded-full px-3 py-1 text-white text-sm">
                    <span class="font-medium">{{ props.vehicle?.type || 'Vehicle' }}</span>
                </div>
                <div class="bg-green-500/20 backdrop-blur-sm rounded-full px-3 py-1 text-white text-sm">
                    <span class="font-medium">{{ props.vehicle?.category?.toUpperCase() }}</span>
                </div>
                <div class="bg-blue-500/20 backdrop-blur-sm rounded-full px-3 py-1 text-white text-sm">
                    <span class="font-medium">Adobe Rental</span>
                </div>
            </div>
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 py-4 md:py-6">
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-1 text-xs sm:text-sm mb-4 p-2 sm:p-3 bg-gray-50 rounded-lg">
            <Link :href="`/${page.props.locale}`" class="text-customPrimaryColor hover:underline font-medium">Home</Link>
            <ChevronRight class="h-3 w-3 sm:h-4 sm:w-4 text-gray-400" />
            <Link :href="route('search', { locale: page.props.locale })" class="text-customPrimaryColor hover:underline font-medium">Vehicles</Link>
            <ChevronRight class="h-3 w-3 sm:h-4 sm:w-4 text-gray-400" />
            <span class="text-gray-600 truncate max-w-[150px] sm:max-w-none">{{ props.vehicle?.model }}</span>
        </nav>

        <!-- Vehicle Header Info -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 sm:mb-6 gap-3">
            <div class="flex-1 min-w-0">
                <h2 class="text-lg sm:text-xl md:text-2xl font-bold text-gray-900 mb-2 truncate">{{ props.vehicle?.model }}</h2>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                        {{ props.vehicle?.type }}
                    </span>
                    <span class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                        </svg>
                        Adobe Rental
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-2 text-gray-600 flex-shrink-0">
                <img :src="locationPinIcon" alt="Location" class="w-3 h-3 sm:w-4 sm:h-4" loading="lazy" />
                <span class="text-xs sm:text-sm font-medium truncate max-w-[120px] sm:max-w-none">{{ props.locationInfo?.label || 'Adobe Location' }}</span>
            </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
            <!-- Left Column: Vehicle Details -->
            <div class="lg:col-span-2 space-y-4 sm:space-y-6">
                <!-- Enhanced Image Section -->
                <div class="mb-6">
                    <div class="relative group rounded-2xl overflow-hidden shadow-2xl bg-gradient-to-br from-gray-100 to-gray-200">
                        <div class="aspect-w-16 aspect-h-9 h-[600px] relative">
                            <div v-if="props.vehicle?.image && !imageError" class="w-full h-full">
                                <img
                                    :src="props.vehicle.image"
                                    :alt="`${props.vehicle?.model} - Primary Image`"
                                    class="w-full h-full object-cover transition-all duration-500 group-hover:scale-105"
                                    loading="lazy"
                                    @error="handleImageError"
                                />
                            </div>
                            <div v-else class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                                <div class="text-center p-8">
                                    <ImageIcon class="w-32 h-32 text-gray-400 mx-auto mb-6" />
                                    <h3 class="text-2xl font-bold text-gray-600 mb-2">{{ props.vehicle?.model }}</h3>
                                    <p class="text-gray-500 mb-4">High-quality image coming soon</p>
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 max-w-md mx-auto">
                                        <p class="text-blue-800 text-sm font-medium">Professional vehicle photos are being prepared</p>
                                    </div>
                                </div>
                            </div>
                            <div class="absolute bottom-6 left-6 right-6">
                                <div class="bg-white/90 backdrop-blur-sm rounded-xl p-4 transform translate-y-4 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300">
                                    <h3 class="font-bold text-lg text-gray-900 mb-1">{{ props.vehicle?.model }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vehicle Features -->
                <div class="bg-white rounded-xl sm:rounded-2xl shadow-lg p-4 sm:p-6">
                    <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center gap-3">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <img :src="carIcon" alt="Car" class="w-4 h-4 sm:w-6 sm:h-6" loading="lazy" />
                        </div>
                        Vehicle Specifications
                    </h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 gap-3 sm:gap-4">
                        <!-- Passengers -->
                        <div v-if="props.vehicle?.passengers" class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <img :src="peopleIcon" alt="People" class="w-6 h-6" loading="lazy" />
                            </div>
                            <div>
                                <span class="text-sm text-gray-500 font-medium">Passengers</span>
                                <p class="font-bold text-lg text-gray-900">{{ props.vehicle.passengers }}</p>
                            </div>
                        </div>

                        <!-- Doors -->
                        <div v-if="props.vehicle?.doors" class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                                <img :src="doorIcon" alt="Doors" class="w-6 h-6" loading="lazy" />
                            </div>
                            <div>
                                <span class="text-sm text-gray-500 font-medium">Doors</span>
                                <p class="font-bold text-lg text-gray-900">{{ props.vehicle.doors }}</p>
                            </div>
                        </div>

                        <!-- Transmission -->
                        <div v-if="props.vehicle?.manual !== undefined" class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                                <img :src="transmisionIcon" alt="Transmission" class="w-6 h-6" loading="lazy" />
                            </div>
                            <div>
                                <span class="text-sm text-gray-500 font-medium">Transmission</span>
                                <p class="font-bold text-lg text-gray-900 capitalize">{{ props.vehicle.manual ? 'Manual' : 'Automatic' }}</p>
                            </div>
                        </div>

                        <!-- Vehicle Type -->
                        <div v-if="props.vehicle?.type" class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                                <img :src="carIcon" alt="Type" class="w-6 h-6" loading="lazy" />
                            </div>
                            <div>
                                <span class="text-sm text-gray-500 font-medium">Vehicle Type</span>
                                <p class="font-bold text-lg text-gray-900">{{ props.vehicle.type }}</p>
                            </div>
                        </div>

                        <!-- Traction -->
                        <div v-if="props.vehicle?.traction" class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                <img :src="mileageIcon" alt="Traction" class="w-6 h-6" loading="lazy" />
                            </div>
                            <div>
                                <span class="text-sm text-gray-500 font-medium">Traction</span>
                                <p class="font-bold text-lg text-gray-900">{{ props.vehicle.traction }}</p>
                            </div>
                        </div>

                        <!-- Category -->
                        <div v-if="props.vehicle?.category" class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <img :src="check" alt="Category" class="w-6 h-6" loading="lazy" />
                            </div>
                            <div>
                                <span class="text-sm text-gray-500 font-medium">Category</span>
                                <p class="font-bold text-lg text-gray-900">{{ props.vehicle.category.toUpperCase() }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Adobe Pricing Details -->
                <div class="bg-white rounded-xl sm:rounded-2xl shadow-lg p-4 sm:p-6">
                    <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center gap-3">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 sm:w-6 sm:h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        Adobe Pricing Details
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-3 sm:gap-4">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-3 sm:p-4 rounded-lg border border-blue-200">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-6 h-6 sm:w-8 sm:h-8 bg-blue-500 rounded-lg flex items-center justify-center">
                                    <span class="text-white font-bold text-xs sm:text-sm">PLI</span>
                                </div>
                                <h4 class="font-semibold text-blue-900 text-sm">Basic Rate</h4>
                            </div>
                            <p class="text-blue-700 text-xs mb-2 line-clamp-2">Daily rental rate including basic liability insurance</p>
                            <p class="text-blue-900 font-bold text-sm sm:text-base">{{ formatCurrency(props.vehicle.pli) }}<span class="text-xs font-normal">/day</span></p>
                        </div>
                        <div class="bg-gradient-to-br from-green-50 to-green-100 p-3 sm:p-4 rounded-lg border border-green-200">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-6 h-6 sm:w-8 sm:h-8 bg-green-500 rounded-lg flex items-center justify-center">
                                    <span class="text-white font-bold text-xs sm:text-sm">LDW</span>
                                </div>
                                <h4 class="font-semibold text-green-900 text-sm">Car Protection</h4>
                            </div>
                            <p class="text-green-700 text-xs mb-2 line-clamp-2">Waivers financial responsibility for car damage with deductible</p>
                            <p class="text-green-900 font-bold text-sm sm:text-base">{{ formatCurrency(props.vehicle.ldw) }}<span class="text-xs font-normal">/day</span></p>
                        </div>
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-3 sm:p-4 rounded-lg border border-purple-200">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-6 h-6 sm:w-8 sm:h-8 bg-purple-500 rounded-lg flex items-center justify-center">
                                    <span class="text-white font-bold text-xs sm:text-sm">SPP</span>
                                </div>
                                <h4 class="font-semibold text-purple-900 text-sm">Extended Protection</h4>
                            </div>
                            <p class="text-purple-700 text-xs mb-2 line-clamp-2">Covers deductible, vandalism, tires, windows & roadside assistance</p>
                            <p class="text-purple-900 font-bold text-sm sm:text-base">{{ formatCurrency(props.vehicle.spp) }}<span class="text-xs font-normal">/day</span></p>
                        </div>
                        <div v-if="props.vehicle.tdr > 0" class="bg-gradient-to-br from-orange-50 to-orange-100 p-3 sm:p-4 rounded-lg border border-orange-200">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-6 h-6 sm:w-8 sm:h-8 bg-orange-500 rounded-lg flex items-center justify-center">
                                    <span class="text-white font-bold text-xs sm:text-sm">TDR</span>
                                </div>
                                <h4 class="font-semibold text-orange-900 text-sm">Total Daily Rate</h4>
                            </div>
                            <p class="text-orange-700 text-xs mb-2 line-clamp-2">Complete daily rate including all applicable charges</p>
                            <p class="text-orange-900 font-bold text-sm sm:text-base">{{ formatCurrency(props.vehicle.tdr) }}<span class="text-xs font-normal">/day</span></p>
                        </div>
                        <div v-if="props.vehicle.dro > 0" class="bg-gradient-to-br from-red-50 to-red-100 p-3 sm:p-4 rounded-lg border border-red-200">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-6 h-6 sm:w-8 sm:h-8 bg-red-500 rounded-lg flex items-center justify-center">
                                    <span class="text-white font-bold text-xs sm:text-sm">DRO</span>
                                </div>
                                <h4 class="font-semibold text-red-900 text-sm">Drop-off Fee</h4>
                            </div>
                            <p class="text-red-700 text-xs mb-2 line-clamp-2">One-way rental fee for different return location</p>
                            <p class="text-red-900 font-bold text-sm sm:text-base">{{ formatCurrency(props.vehicle.dro) }}<span class="text-xs font-normal">/rental</span></p>
                        </div>
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 p-3 sm:p-4 rounded-lg border border-gray-200">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h4 class="font-semibold text-gray-900 text-sm">Coverage Details</h4>
                            </div>
                            <p class="text-gray-700 text-xs mb-2">All rates include mandatory liability protection with US$1,130 deductible</p>
                            <div class="text-xs text-gray-600">
                                <p>• Third-party coverage up to US$100,000</p>
                                <p>• 24/7 roadside assistance available</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Adobe Protections -->
                <div v-if="props.vehicle?.protections && props.vehicle.protections.length > 0" class="bg-white rounded-xl sm:rounded-2xl shadow-lg p-4 sm:p-6">
                    <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center gap-3">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <img :src="carguaranteeIcon" alt="Insurance" class="w-4 h-4 sm:w-6 sm:h-6" loading="lazy" />
                        </div>
                        Protection Options
                    </h2>
                    <div class="space-y-3 sm:space-y-4">
                        <div v-for="protection in props.vehicle.protections" :key="protection.code"
                             class="p-3 sm:p-4 bg-blue-50 rounded-lg border border-blue-200 hover:bg-blue-100 transition-colors">
                            <div class="flex items-start gap-3">
                                <img :src="check" alt="Protection" class="w-4 h-4 sm:w-5 sm:h-5 text-blue-600 mt-0.5 flex-shrink-0" loading="lazy" />
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
                                        <h4 class="font-semibold text-blue-900 text-sm truncate">{{ protection.displayName || protection.name || protection.code }}</h4>
                                        <div class="flex items-center gap-2 flex-shrink-0">
                                            <span v-if="protection.required" class="bg-red-100 px-2 py-0.5 rounded text-xs text-red-700 font-medium">Required</span>
                                        </div>
                                    </div>
                                    <p class="text-blue-700 text-xs mb-2 line-clamp-2">{{ protection.displayDescription || protection.description }}</p>
                                    <div class="flex items-center justify-between">
                                        <span class="text-blue-600 font-medium text-xs">Price:</span>
                                        <span class="text-blue-900 font-bold text-sm">{{ formatCurrency(protection.price || protection.total) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Adobe Extras -->
                <div v-if="props.vehicle?.extras && props.vehicle.extras.length > 0" class="bg-white rounded-xl sm:rounded-2xl shadow-lg p-4 sm:p-6">
                    <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center gap-3">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 bg-purple-100 rounded-full flex items-center justify-center">
                            <img :src="offerIcon" alt="Extras" class="w-4 h-4 sm:w-6 sm:h-6" loading="lazy" />
                        </div>
                        Optional Extras
                    </h2>
                    <div class="space-y-3 sm:space-y-4">
                        <div v-for="extra in props.vehicle.extras" :key="extra.code"
                             class="p-3 sm:p-4 bg-purple-50 rounded-lg border border-purple-200 hover:bg-purple-100 transition-colors">
                            <div class="flex items-start gap-3">
                                <img :src="check" alt="Extra" class="w-4 h-4 sm:w-5 sm:h-5 text-purple-600 mt-0.5 flex-shrink-0" loading="lazy" />
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
                                        <h4 class="font-semibold text-purple-900 text-sm truncate">{{ extra.displayName || extra.name || extra.code }}</h4>
                                        <div class="flex items-center gap-2 flex-shrink-0">
                                            <span v-if="extra.required" class="bg-red-100 px-2 py-0.5 rounded text-xs text-red-700 font-medium">Required</span>
                                        </div>
                                    </div>
                                    <p class="text-purple-700 text-xs mb-2 line-clamp-2">{{ extra.displayDescription || extra.description }}</p>
                                    <div class="flex items-center justify-between">
                                        <span class="text-purple-600 font-medium text-xs">Price:</span>
                                        <span class="text-purple-900 font-bold text-sm">{{ formatCurrency(extra.price || extra.total) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Location -->
                <div v-if="props.locationInfo" class="bg-white rounded-2xl shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                            <img :src="locationPinIcon" alt="Location" class="w-6 h-6" loading="lazy" />
                        </div>
                        Pickup Location
                    </h2>
                    <div class="mb-6 p-4 bg-red-50 rounded-xl border border-red-200">
                        <div class="flex items-start gap-3">
                            <img :src="locationPinIcon" alt="Location" class="w-6 h-6 text-red-600 mt-3" loading="lazy" />
                            <div>
                                <h5 class="font-semibold text-red-900">{{ props.locationInfo.label }}</h5>
                                <p class="text-red-800">{{ props.locationInfo.address || 'Adobe Car Rental Location' }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-if="props.locationInfo.latitude && props.locationInfo.longitude" class="rounded-xl h-[350px] w-full bg-gray-100 shadow-inner" ref="mapContainerRef">
                        <div v-if="!map" class="w-full h-full flex items-center justify-center">
                            <div class="text-center">
                                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-customPrimaryColor mx-auto mb-4"></div>
                                <p class="text-gray-600">Loading interactive map...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Provider -->
                <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-2xl shadow-lg p-8 border border-green-200">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-3">
                        About Adobe Car Rental
                    </h2>
                    <div class="flex items-start gap-6">
                        <div class="w-20 h-20 bg-gradient-to-br from-green-400 to-blue-500 rounded-2xl flex items-center justify-center shadow-lg">
                            <span class="text-white font-bold text-2xl">AD</span>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-2xl font-bold text-customPrimaryColor mb-2">Adobe Car Rental</h4>
                            <p class="text-gray-600 mb-4">Your trusted car rental company committed to providing quality vehicles and excellent service throughout Costa Rica.</p>
                            <div class="flex items-center gap-4 text-sm">
                                <div class="flex items-center gap-2 bg-green-100 px-3 py-1 rounded-full">
                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    <span class="text-green-800 font-medium">Quality Service</span>
                                </div>
                                <div class="flex items-center gap-2 bg-blue-100 px-3 py-1 rounded-full">
                                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                    <span class="text-blue-800 font-medium">Trusted Partner</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Booking Card -->
            <div class="lg:col-span-1">
                <Card class="sticky top-4 shadow-2xl border-0 overflow-hidden">
                    <div class="bg-gradient-to-r from-customPrimaryColor to-blue-700 p-6 text-white">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-xl font-bold truncate">{{ props.vehicle?.model }}</h3>
                                <span class="inline-block px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm mt-2">{{ props.vehicle?.type }}</span>
                            </div>
                            <div class="flex gap-2 ml-4">
                                <button @click="shareVehicle" class="p-2 bg-white/20 rounded-full transition-colors">
                                    <img :src="ShareIcon" alt="Share" class="w-5 h-5" loading="lazy" />
                                </button>
                            </div>
                        </div>
                        <p class="text-blue-100 text-sm">Powered by <span class="font-semibold text-white">Adobe Car Rental</span></p>
                    </div>

                    <CardContent class="p-6">
                        <div class="space-y-6">
                            <!-- Vehicle Summary -->
                            <div class="space-y-4">
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                    <img :src="carIcon" alt="Car" class="w-5 h-5" loading="lazy" />
                                    <span class="text-sm text-gray-700">
                                        {{ props.vehicle?.manual ? 'Manual' : 'Automatic' }} • {{ props.vehicle?.type || 'Vehicle' }} • {{ props.vehicle?.passengers || 'N/A' }} Seats
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                    <img :src="mileageIcon" alt="Mileage" class="w-5 h-5" loading="lazy" />
                                    <span class="text-sm text-gray-700">{{ props.vehicle?.traction || 'Standard Drive' }} • {{ props.vehicle?.doors || 'N/A' }} Doors</span>
                                </div>
                            </div>

                            <!-- Location Info -->
                            <div class="space-y-4">
                                <div class="flex items-start gap-3 p-4 bg-green-50 rounded-lg border border-green-200">
                                    <img :src="pickupLocationIcon" alt="Pickup" class="w-5 h-5 mt-1" loading="lazy" />
                                    <div class="flex-1">
                                        <span class="text-sm font-medium text-green-800">Pickup Location</span>
                                        <p class="font-semibold text-green-900">{{ props.locationInfo?.label || 'Adobe Location' }}</p>
                                        <p class="text-sm text-green-700">{{ props.locationInfo?.address || 'Adobe Car Rental Location' }}</p>
                                        <p class="text-sm text-green-600 mt-1">{{ props.searchParams?.pickup_datetime || 'Select dates' }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Price Summary -->
                            <div v-if="props.vehicle?.tdr" class="p-6 bg-gradient-to-r from-green-50 to-blue-50 rounded-xl border border-green-200">
                                <div class="text-center">
                                    <p class="text-3xl font-bold text-gray-900 mb-1">{{ formatCurrency(props.vehicle.tdr) }}</p>
                                    <p class="text-sm text-gray-600 mb-3">Total for rental period</p>
                                    <div class="flex items-center justify-center gap-2 text-xs text-green-700">
                                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                        <span>Best rate guaranteed</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Book Button -->
                            <Button @click="startBooking" :disabled="isLoading" class="w-full bg-gradient-to-r from-customPrimaryColor to-blue-700 hover:from-customPrimaryColor/90 hover:to-blue-700/90 text-white py-4 font-semibold text-lg rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                                <div class="flex items-center justify-center gap-2">
                                    <span>Reserve Now</span>
                                    <ChevronRight class="w-5 h-5" />
                                </div>
                            </Button>

                            <!-- Security Badge -->
                            <div class="text-center">
                                <div class="flex flex-col items-center justify-center gap-3 p-3 bg-gray-50 rounded-lg">
                                    <img :src="partnersIcon" alt="Security" class="" loading="lazy" />
                                    <p class="text-sm text-gray-600 font-medium">Secure & Protected Booking</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>

    <!-- Loader Overlay -->
    <div v-if="isLoading" class="loader-overlay">
        <Vue3Lottie :animationData="universalLoader" :height="200" :width="200" />
    </div>

    <Footer />
</template>

<style scoped>
.loader-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

.bg-customPrimaryColor {
    background-color: #153b4f;
}

.text-customPrimaryColor {
    color: #153b4f;
}

.shadow-2xl {
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.rounded-2xl {
    border-radius: 1rem;
}

.marker-pin {
    width: 50px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.custom-div-icon {
    background: none;
    border: none;
}

@keyframes pop {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.pop-animation {
    animation: pop 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

/* Enhanced hover effects */
.group:hover .group-hover\:scale-105 {
    transform: scale(1.05);
}

.group:hover .group-hover\:scale-110 {
    transform: scale(1.1);
}

/* Responsive improvements */
@media (max-width: 768px) {
    .text-4xl { font-size: 2.25rem; }
    .text-3xl { font-size: 1.875rem; }
    .text-2xl { font-size: 1.5rem; }
    .text-xl { font-size: 1.25rem; }
    .h-\[600px\] { height: 400px; }
    .p-8 { padding: 1.5rem; }
    .p-6 { padding: 1rem; }
    .gap-12 { gap: 2rem; }
    .gap-8 { gap: 1.5rem; }
    .gap-6 { gap: 1rem; }
}

@media (max-width: 640px) {
    .text-4xl { font-size: 2rem; }
    .text-3xl { font-size: 1.75rem; }
    .text-2xl { font-size: 1.375rem; }
    .h-\[600px\] { height: 300px; }
    .p-8 { padding: 1rem; }
    .p-6 { padding: 0.75rem; }
}

/* Focus styles for accessibility */
button:focus,
select:focus,
input:focus {
    outline: 2px solid #153b4f;
    outline-offset: 2px;
}

/* Enhanced button hover effects */
.transform {
    transform: translateX(var(--tw-translate-x)) translateY(var(--tw-translate-y)) rotate(var(--tw-rotate)) skewX(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y));
}

.hover\:scale-105:hover {
    --tw-scale-x: 1.05;
    --tw-scale-y: 1.05;
}

.hover\:scale-110:hover {
    --tw-scale-x: 1.1;
    --tw-scale-y: 1.1;
}
</style>