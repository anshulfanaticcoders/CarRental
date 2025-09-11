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
import Clock from '../../assets/clock.svg';

// Icons
import doorIcon from "../../assets/door.svg";
import luggageIcon from "../../assets/luggage.svg";
import fuelIcon from "../../assets/fuel.svg";
import transmisionIcon from "../../assets/transmision.svg";
import peopleIcon from "../../assets/people.svg";
import carbonIcon from "../../assets/carbon-emmision.svg";
import MapPin from "../../assets/MapPin.svg";
import fullStar from "../../assets/fullstar.svg";
import halfStar from "../../assets/halfstar.svg";
import carguaranteeIcon from "../../assets/carguarantee.png";
import locationPinIcon from "../../assets/locationPin.svg";
import ShareIcon from "../../assets/ShareNetwork.svg";
import Heart from "../../assets/Heart.svg";
import FilledHeart from "../../assets/FilledHeart.svg";
import pickupLocationIcon from "../../assets/pickupLocationIcon.svg";
import returnLocationIcon from "../../assets/returnLocationIcon.svg";
import partnersIcon from "../../assets/partners.svg";
import offerIcon from "../../assets/percentage-tag.svg";

// UI components
import { Skeleton } from '@/Components/ui/skeleton';
import '@vuepic/vue-datepicker/dist/main.css';
import VueDatepicker from '@vuepic/vue-datepicker';
import { useToast } from 'vue-toastification';
import { Carousel, CarouselContent, CarouselItem, CarouselNext, CarouselPrevious } from "@/Components/ui/carousel";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/Components/ui/dialog";
import Card from "@/Components/ui/card/Card.vue";
import CardContent from "@/Components/ui/card/CardContent.vue";
import CardHeader from "@/Components/ui/card/CardHeader.vue";
import CardTitle from "@/Components/ui/card/CardTitle.vue";
import { ChevronRight, ImageIcon, ZoomIn } from 'lucide-vue-next';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import { Button } from "@/Components/ui/button";
import Lightbox from "@/Components/Lightbox.vue";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import { Vue3Lottie } from 'vue3-lottie';
import universalLoader from '../../../public/animations/universal-loader.json';

const isBooking = ref(false);
const currencySymbols = ref({});

onMounted(async () => {
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
});

const getCurrencySymbol = (code) => {
    return '$'; // Always return dollar for display on GreenMotionSingle page
};


const props = defineProps({
    vehicle: Object,
    location: Object,
    optionalExtras: Array,
    filters: Object,
    seoMeta: Object,
    error: String,
});

const page = usePage();
const toast = useToast();
const mapContainerRef = ref(null);
let map = ref(null);

// Image loading states
const imageLoading = ref(true);
const imageError = ref(false);

// SEO Computed Properties
const seoTranslation = computed(() => {
    if (!props.seoMeta || !props.seoMeta.translations) {
        return {};
    }
    return props.seoMeta.translations.find(t => t.locale === page.props.locale) || {};
});

const constructedLocalizedUrlSlug = computed(() => {
    return seoTranslation.value.url_slug || props.seoMeta?.url_slug || 'green-motion-car';
});

const currentUrl = computed(() => {
    return `${window.location.origin}/${page.props.locale}/${constructedLocalizedUrlSlug.value}`;
});

const canonicalUrl = computed(() => {
    return props.seoMeta?.canonical_url || currentUrl.value;
});

const seoTitle = computed(() => {
    return seoTranslation.value.seo_title || props.seoMeta?.seo_title || 'GreenMotion Vehicle Details';
});

const seoDescription = computed(() => {
    return seoTranslation.value.meta_description || props.seoMeta?.meta_description || '';
});

const seoKeywords = computed(() => {
    return seoTranslation.value.keywords || props.seoMeta?.keywords || '';
});

const seoImageUrl = computed(() => {
    return props.seoMeta?.seo_image_url || '';
});

const backToSearchUrl = computed(() => {
    return route('green-motion-cars', { locale: page.props.locale, ...props.filters });
});

const vehicleProduct = computed(() => {
    return props.vehicle?.products?.[0] || null;
});

const priceToDisplay = computed(() => {
    if (vehicleProduct.value?.total && vehicleProduct.value.total > 0) {
        const currencyCode = vehicleProduct.value.currency;
        const currencySymbol = getCurrencySymbol(currencyCode);
        return `${currencySymbol}${parseFloat(vehicleProduct.value.total).toFixed(2)}`;
    }
    return 'Price not available';
});

// Enhanced Image Handling
const primaryImage = computed(() => {
    const imageUrl = props.vehicle?.image || props.vehicle?.largeImage || '/default-car-image.jpg';
    imageLoading.value = false;
    return { 
        image_url: imageUrl,
        alt: `${props.vehicle?.name || 'Vehicle'} - Primary Image`
    };
});

const galleryImages = computed(() => {
    const images = [];
    
    // Add large image if different from primary
    if (props.vehicle?.largeImage && props.vehicle.largeImage !== props.vehicle?.image) {
        images.push({ 
            image_url: props.vehicle.largeImage,
            alt: `${props.vehicle?.name || 'Vehicle'} - Large View`
        });
    }
    
    // Add small image if available and different
    if (props.vehicle?.smallImage && 
        props.vehicle.smallImage !== props.vehicle?.image && 
        props.vehicle.smallImage !== props.vehicle?.largeImage) {
        images.push({ 
            image_url: props.vehicle.smallImage,
            alt: `${props.vehicle?.name || 'Vehicle'} - Small View`
        });
    }
    
    return images;
});

const allImages = computed(() => {
    const images = [primaryImage.value];
    images.push(...galleryImages.value);
    return images;
});

const hasMultipleImages = computed(() => {
    return allImages.value.length > 1;
});

// Image optimization function
const getOptimizedImageUrl = (url, width = 800, height = 800) => {
    if (!url || url.includes('default-car-image.jpg')) {
        return url;
    }
    
    // If the URL already has parameters, append with &, otherwise use ?
    const separator = url.includes('?') ? '&' : '?';
    return `${url}${separator}w=${width}&h=${height}&fit=crop&auto=format,compress&q=80`;
};

const handleImageLoad = () => {
    imageLoading.value = false;
    imageError.value = false;
};

const handleImageError = (error) => {
    console.error('Image loading error:', error);
    imageLoading.value = false;
    imageError.value = true;
};

// Map Initialization
const initMap = () => {
    if (!props.location || !props.location.latitude || !props.location.longitude || !mapContainerRef.value) {
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
    }).setView([parseFloat(props.location.latitude), parseFloat(props.location.longitude)], 15);

    const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
        detectRetina: true
    }).addTo(map.value);

    osmLayer.on('tileerror', (error) => {
        console.error('Tile loading error:', error);
    });

    const customIcon = L.divIcon({
        className: 'custom-div-icon',
        html: `
            <div class="marker-pin">
                <img src="${MapPin}" alt="Vehicle Location" loading="lazy" />
            </div>
        `,
        iconSize: [50, 30],
        iconAnchor: [25, 15],
        popupAnchor: [0, -15]
    });

    L.marker([parseFloat(props.location.latitude), parseFloat(props.location.longitude)], {
        icon: customIcon
    })
        .bindPopup(`
            <div class="text-center">
                <p class="font-semibold">${props.location.name}</p>
                <p>${props.location.address_city}</p>
            </div>
        `)
        .addTo(map.value);

    setTimeout(() => {
        map.value.invalidateSize();
    }, 100);
};

// Form and Booking Data
const form = ref({
    location_id: props.filters.location_id || props.location?.id || 61627,
    start_date: props.filters.start_date || '2032-01-06',
    start_time: props.filters.start_time || '09:00',
    end_date: props.filters.end_date || '2032-01-08',
    end_time: props.filters.end_time || '09:00',
    age: props.filters.age || 35,
    rentalCode: props.filters.rentalCode || null,
});

const selectedPackage = ref('day');
const rentalDuration = computed(() => {
    if (!form.value.start_date || !form.value.end_date) return 0;
    const startDate = new Date(form.value.start_date);
    const endDate = new Date(form.value.end_date);
    const diffTime = Math.abs(endDate - startDate);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
});

const calculateTotalPrice = computed(() => {
    return parseFloat(vehicleProduct.value?.total || 0);
});

const formatPrice = (price) => {
    const currencyCode = vehicleProduct.value?.currency;
    const currencySymbol = getCurrencySymbol(currencyCode);
    return `${currencySymbol}${parseFloat(price).toFixed(2)}`;
};

const timeOptions = Array.from({ length: 24 }, (_, i) => {
    const hour = String(i).padStart(2, '0');
    return [`${hour}:00`, `${hour}:30`];
}).flat();

const departureTimeOptions = computed(() => timeOptions);
const returnTimeOptions = computed(() => timeOptions);

const updateDateTimeSelection = () => {
    // No session storage for GreenMotion
};

const bookedDates = ref([]);
const blockedDates = ref([]);

const isDateBooked = (dateTr) => false;
const getDisabledDates = () => [];

const minDate = computed(() => new Date());
const maxPickupDate = computed(() => {
    const today = new Date();
    const futureDate = new Date(today);
    futureDate.setMonth(today.getMonth() + 3);
    return futureDate;
});
const minReturnDate = computed(() => {
    if (!form.value.start_date) return new Date();
    const pickupDate = new Date(form.value.start_date);
    const minDate = new Date(pickupDate);
    minDate.setDate(pickupDate.getDate() + 1);
    return minDate;
});
const maxReturnDate = computed(() => {
    if (!form.value.start_date) return null;
    const pickupDate = new Date(form.value.start_date);
    const maxDate = new Date(pickupDate);
    maxDate.setDate(pickupDate.getDate() + 30);
    return maxDate;
});

const handleDateFrom = (date) => {
    form.value.start_date = date ? date.toISOString().split('T')[0] : '';
    form.value.end_date = '';
};

const handleDateTo = (date) => {
    form.value.end_date = date ? date.toISOString().split('T')[0] : '';
};

const pricingPackages = computed(() => [
    {
        id: 'day',
        label: 'Total Rental Price',
        description: 'Price for the selected rental period',
        price: vehicleProduct.value?.total,
        icon: Clock,
        priceLabel: '/rental'
    }
].filter(pkg => pkg.price));

// Lightbox for Gallery
const lightboxRef = ref(null);

const openLightbox = (index) => {
    if (lightboxRef.value) {
        lightboxRef.value.openLightbox(index);
    }
};

const searchUrl = computed(() => {
    if (typeof window !== 'undefined' && sessionStorage.getItem('searchurl')) {
        return sessionStorage.getItem('searchurl');
    }
    return '';
});

// Share
const shareVehicle = async () => {
    try {
        const shareData = {
            title: seoTitle.value,
            text: `Check out this GreenMotion vehicle: ${props.vehicle?.name}!`,
            url: canonicalUrl.value,
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

// Booking
const showWarningModal = ref(false);
const proceedToPayment = async () => {
    if (!form.value.location_id || !form.value.start_date || !form.value.start_time || !form.value.end_date || !form.value.end_time || !form.value.age) {
        toast.error("Please fill all required rental details (Location ID, Dates, Times, Age).");
        return;
    }
    isBooking.value = true;

    // Check if user is authenticated
    if (!page.props.auth.user) {
        sessionStorage.setItem('returnToUrl', window.location.href);
        // Store current form data in session storage to retrieve after login
        sessionStorage.setItem('greenMotionBookingForm', JSON.stringify(form.value));
        sessionStorage.setItem('greenMotionVehicleId', props.vehicle.id);
        sessionStorage.setItem('greenMotionLocationId', props.location.id);
        sessionStorage.setItem('currentLocale', page.props.locale); // Store the current locale explicitly

        // Redirect to login page
        router.visit(route('login', { locale: page.props.locale }));
        return;
    }

    router.visit(route('green-motion-booking.checkout', {
        locale: page.props.locale,
        id: props.vehicle.id,
        location_id: form.value.location_id,
        start_date: form.value.start_date,
        start_time: form.value.start_time,
        end_date: form.value.end_date,
        end_time: form.value.end_time,
        age: form.value.age,
        rentalCode: form.value.rentalCode,
    }), {
        onFinish: () => {
            isBooking.value = false;
        },
    });
};

// Lifecycle Hooks
onMounted(() => {
    nextTick(() => {
        initMap();
    });
});

watch([() => props.location, mapContainerRef], ([newLocation, newMapContainerRef]) => {
    if (newLocation && newMapContainerRef) {
        initMap();
    }
}, { immediate: true, deep: true });

onBeforeUnmount(() => {
    if (map.value) {
        map.value.remove();
        map.value = null;
    }
});
</script>

<template>
    <Head>
        <meta name="robots" content="noindex, nofollow" />
        <title>{{ seoTitle }}</title>
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

    <AuthenticatedHeaderLayout />

    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-customPrimaryColor to-blue-700 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-white text-4xl md:text-5xl font-bold mb-4">{{ vehicle?.name || 'GreenMotion Vehicle' }}</h1>
            <p class="text-blue-100 text-lg md:text-xl">Eco-friendly rental with premium comfort</p>
            <div class="mt-6 flex items-center justify-center gap-4">
                <div class="bg-white/20 backdrop-blur-sm rounded-full px-4 py-2 text-white">
                    <span class="font-medium">{{ vehicle?.groupName }}</span>
                </div>
                <div class="bg-green-500/20 backdrop-blur-sm rounded-full px-4 py-2 text-white">
                    <span class="font-medium">Eco-Friendly</span>
                </div>
            </div>
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div v-if="error" class="text-center text-red-500 text-xl py-8">
            {{ error }}
            <Link :href="backToSearchUrl" class="text-blue-500 hover:underline block mt-4">
                Back to Search Results
            </Link>
        </div>
        <div v-else-if="vehicle">
            <!-- Breadcrumb -->
            <nav class="flex items-center gap-2 text-sm mb-8 p-4 bg-gray-50 rounded-lg">
                <Link :href="`/${$page.props.locale}`" class="text-customPrimaryColor hover:underline font-medium">Home</Link>
                <ChevronRight class="h-4 w-4 text-gray-400" />
                <Link :href="backToSearchUrl" class="text-customPrimaryColor hover:underline font-medium">GreenMotion Vehicles</Link>
                <ChevronRight class="h-4 w-4 text-gray-400" />
                <span class="text-gray-600">{{ vehicle?.name }}</span>
            </nav>

            <!-- Vehicle Header Info -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">{{ vehicle?.name }}</h2>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                            {{ vehicle?.groupName }}
                        </span>
                        <span class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                            </svg>
                            Eco-Friendly
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-2 text-gray-600">
                        <img :src="locationPinIcon" alt="Location" class="w-4 h-4" loading="lazy" />
                        <span class="text-sm font-medium">{{ location?.address_city }}</span>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                <!-- Left Column: Vehicle Details -->
                <div class="lg:col-span-2 space-y-10">
                    <!-- Enhanced Image Section -->
                    <div class="mb-12">
                        <div v-if="hasMultipleImages" class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                            <!-- Main Image -->
                            <div class="lg:col-span-3">
                                <div class="relative group rounded-2xl overflow-hidden shadow-2xl bg-gradient-to-br from-gray-100 to-gray-200">
                                    <div class="aspect-w-16 aspect-h-9 h-[500px]">
                                        <Skeleton v-if="imageLoading" class="w-full h-full" />
                                        <div v-else-if="imageError" class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                                            <ImageIcon class="w-24 h-24 text-gray-400 mb-4" />
                                            <p class="text-gray-500 font-medium">Image not available</p>
                                            <p class="text-gray-400 text-sm">{{ vehicle?.name }}</p>
                                        </div>
                                        <img 
                                            v-else
                                            :src="getOptimizedImageUrl(primaryImage.image_url, 1200, 675)" 
                                            :alt="primaryImage.alt" 
                                            class="w-full h-full object-cover transition-all duration-500 group-hover:scale-105" 
                                            loading="lazy"
                                            @load="handleImageLoad"
                                            @error="handleImageError"
                                            @click="openLightbox(0)"
                                        />
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                        <button 
                                            class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm p-3 rounded-full shadow-lg opacity-0 group-hover:opacity-100 transition-all duration-300 hover:bg-white"
                                            @click="openLightbox(0)"
                                        >
                                            <ZoomIn class="w-5 h-5 text-gray-700" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Gallery Images -->
                            <div class="space-y-4">
                                <div v-for="(image, index) in galleryImages.slice(0, 2)" :key="index" 
                                     class="relative group rounded-xl overflow-hidden shadow-lg cursor-pointer bg-gradient-to-br from-gray-100 to-gray-200"
                                     @click="openLightbox(index + 1)">
                                    <div class="aspect-w-4 aspect-h-3 h-[240px]">
                                        <img 
                                            :src="getOptimizedImageUrl(image.image_url, 400, 300)" 
                                            :alt="image.alt" 
                                            class="w-full h-full object-cover transition-all duration-300 group-hover:scale-110"
                                            loading="lazy"
                                        />
                                        <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                        <button class="absolute top-2 right-2 bg-white/90 backdrop-blur-sm p-2 rounded-full shadow opacity-0 group-hover:opacity-100 transition-all duration-300">
                                            <ZoomIn class="w-4 h-4 text-gray-700" />
                                        </button>
                                    </div>
                                </div>
                                
                                <div v-if="galleryImages.length > 2" 
                                     class="relative group rounded-xl overflow-hidden shadow-lg cursor-pointer bg-gradient-to-br from-gray-100 to-gray-200" 
                                     @click="openLightbox(3)">
                                    <div class="aspect-w-4 aspect-h-3 h-[240px]">
                                        <img 
                                            :src="getOptimizedImageUrl(galleryImages[2].image_url, 400, 300)" 
                                            :alt="galleryImages[2].alt" 
                                            class="w-full h-full object-cover opacity-70" 
                                            loading="lazy" 
                                        />
                                        <div class="absolute inset-0 bg-black/60 flex items-center justify-center">
                                            <div class="text-center text-white">
                                                <ImageIcon class="w-8 h-8 mx-auto mb-2" />
                                                <span class="text-lg font-semibold">+{{ galleryImages.length - 2 }}</span>
                                                <p class="text-sm opacity-90">More Photos</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Single Image Layout -->
                        <div v-else class="relative group">
                            <div class="rounded-2xl overflow-hidden shadow-2xl bg-gradient-to-br from-gray-100 to-gray-200">
                                <div class="aspect-w-16 aspect-h-9 h-[600px] relative">
                                    <Skeleton v-if="imageLoading" class="w-full h-full" />
                                    <div v-else-if="imageError" class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                                        <div class="text-center p-8">
                                            <ImageIcon class="w-32 h-32 text-gray-400 mx-auto mb-6" />
                                            <h3 class="text-2xl font-bold text-gray-600 mb-2">{{ vehicle?.name }}</h3>
                                            <p class="text-gray-500 mb-4">High-quality image coming soon</p>
                                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 max-w-md mx-auto">
                                                <p class="text-blue-800 text-sm font-medium">Professional vehicle photos are being prepared</p>
                                            </div>
                                        </div>
                                    </div>
                                    <img 
                                        v-else
                                        :src="getOptimizedImageUrl(primaryImage.image_url, 1200, 675)" 
                                        :alt="primaryImage.alt" 
                                        class="w-full h-full object-cover transition-all duration-500 group-hover:scale-105" 
                                        loading="lazy"
                                        @load="handleImageLoad"
                                        @error="handleImageError"
                                        @click="openLightbox(0)"
                                    />
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                    <div class="absolute bottom-6 left-6 right-6">
                                        <div class="bg-white/90 backdrop-blur-sm rounded-xl p-4 transform translate-y-4 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300">
                                            <h3 class="font-bold text-lg text-gray-900 mb-1">{{ vehicle?.name }}</h3>
                                        </div>
                                    </div>
                                    <button 
                                        v-if="!imageError"
                                        class="absolute top-6 right-6 bg-white/90 backdrop-blur-sm p-3 rounded-full shadow-lg opacity-0 group-hover:opacity-100 transition-all duration-300 hover:bg-white hover:scale-110"
                                        @click="openLightbox(0)"
                                    >
                                        <ZoomIn class="w-6 h-6 text-gray-700" />
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Image Info Badge -->
                            <div class="absolute top-6 left-6 bg-white/95 backdrop-blur-sm rounded-xl px-4 py-2 shadow-lg">
                                <div class="flex items-center gap-2">
                                    <ImageIcon class="w-5 h-5 text-blue-600" />
                                    <span class="text-sm font-medium text-gray-900">Photos</span>
                                </div>
                            </div>
                        </div>
                        
                        <Lightbox ref="lightboxRef" :images="allImages.map(img => getOptimizedImageUrl(img.image_url, 1200, 800))" />
                    </div>
                    <!-- Vehicle Features -->
                    <div class="bg-white rounded-2xl shadow-lg p-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                <img :src="carIcon" alt="Car" class="w-6 h-6" loading="lazy" />
                            </div>
                            Vehicle Specifications
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                    <img :src="peopleIcon" alt="People" class="w-6 h-6" loading="lazy" />
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500 font-medium">People</span>
                                    <p class="font-bold text-lg text-gray-900">{{ (parseInt(vehicle?.adults) || 0) + (parseInt(vehicle?.children) || 0) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                                    <img :src="luggageIcon" alt="Luggage" class="w-6 h-6" loading="lazy" />
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500 font-medium">Luggage</span>
                                    <p class="font-bold text-sm text-gray-900">S:{{ vehicle?.luggageSmall || 0 }} M:{{ vehicle?.luggageMed || 0 }} L:{{ vehicle?.luggageLarge || 0 }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                                    <img :src="transmisionIcon" alt="Transmission" class="w-6 h-6" loading="lazy" />
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500 font-medium">Transmission</span>
                                    <p class="font-bold text-lg text-gray-900 capitalize">{{ vehicle?.transmission || 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                                    <img :src="fuelIcon" alt="Fuel Type" class="w-6 h-6" loading="lazy" />
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500 font-medium">Fuel Type</span>
                                    <p class="font-bold text-lg text-gray-900 capitalize">{{ vehicle?.fuel || 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                    <img :src="carbonIcon" alt="CO2 Emission" class="w-6 h-6" loading="lazy" />
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500 font-medium">CO2 Emission</span>
                                    <p class="font-bold text-lg text-gray-900">{{ vehicle?.co2 || 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                    <img :src="check" alt="Air Conditioning" class="w-6 h-6" loading="lazy" />
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500 font-medium">Air Conditioning</span>
                                    <p class="font-bold text-lg text-gray-900">{{ vehicle?.airConditioning || 'Standard' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rental Details -->
                    <div class="bg-white rounded-2xl shadow-lg p-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <img :src="check" alt="Check" class="w-6 h-6" loading="lazy" />
                            </div>
                            Rental Information
                        </h2>
                        <div v-if="vehicleProduct" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex items-center gap-3 p-4 bg-green-50 rounded-xl border border-green-200">
                                <img :src="check" alt="Check" class="w-5 h-5 text-green-600" loading="lazy" />
                                <div>
                                    <span class="text-sm font-medium text-green-800">Fuel Policy</span>
                                    <p class="text-green-900">{{ vehicleProduct.fuelpolicy }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-4 bg-purple-50 rounded-xl border border-purple-200">
                                <img :src="check" alt="Check" class="w-5 h-5 text-purple-600" loading="lazy" />
                                <div>
                                    <span class="text-sm font-medium text-purple-800">Min Age</span>
                                    <p class="text-purple-900">{{ vehicleProduct.minage }} years</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-4 bg-orange-50 rounded-xl border border-orange-200">
                                <img :src="check" alt="Check" class="w-5 h-5 text-orange-600" loading="lazy" />
                                <div>
                                    <span class="text-sm font-medium text-orange-800">Deposit</span>
                                    <p class="text-orange-900">{{ formatPrice(vehicleProduct.deposit) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-4 bg-red-50 rounded-xl border border-red-200">
                                <img :src="check" alt="Check" class="w-5 h-5 text-red-600" loading="lazy" />
                                <div>
                                    <span class="text-sm font-medium text-red-800">Excess</span>
                                    <p class="text-red-900">{{ formatPrice(vehicleProduct.excess) }}</p>
                                </div>
                            </div>
                            <div v-if="vehicle.driveandgo === 'Yes'" class="flex items-center gap-3 p-4 bg-green-50 rounded-xl border border-green-200">
                                <img :src="check" alt="Check" class="w-5 h-5 text-green-600" loading="lazy" />
                                <div>
                                    <span class="text-sm font-medium text-green-800">Drive N Go</span>
                                    <p class="text-green-900">Available</p>
                                </div>
                            </div>
                            <div v-if="vehicle.keyngo === 'Yes'" class="flex items-center gap-3 p-4 bg-blue-50 rounded-xl border border-blue-200">
                                <img :src="check" alt="Check" class="w-5 h-5 text-blue-600" loading="lazy" />
                                <div>
                                    <span class="text-sm font-medium text-blue-800">Key N Go</span>
                                    <p class="text-blue-900">Available</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center p-8 bg-gray-50 rounded-xl">
                            <p class="text-gray-500">No specific rental details available for this vehicle.</p>
                        </div>
                    </div>

                    <!-- Optional Extras -->
                    <div class="bg-white rounded-2xl shadow-lg p-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                <img :src="offerIcon" alt="Extras" class="w-6 h-6" loading="lazy" />
                            </div>
                            Optional Extras
                        </h2>
                        <div v-if="optionalExtras && optionalExtras.length > 0" class="space-y-4">
                            <div v-for="extra in optionalExtras" :key="extra.optionID || extra.Name" 
                                 class="flex items-start gap-4 p-4 bg-purple-50 rounded-xl border border-purple-200 hover:bg-purple-100 transition-colors">
                                <img :src="check" alt="Check" class="w-5 h-5 text-purple-600 mt-1" loading="lazy" />
                                <div class="flex-1">
                                    <h4 class="font-semibold text-purple-900 text-lg">{{ extra.Name }}</h4>
                                    <p class="text-purple-700 text-sm mb-2">{{ extra.Description }}</p>
                                    <div v-if="extra.Daily_rate" class="text-purple-800 font-medium">
                                        {{ formatPrice(extra.Daily_rate) }}/day
                                    </div>
                                    <div v-else-if="extra.options && extra.options.length > 0" class="space-y-1">
                                        <div v-for="subOption in extra.options" :key="subOption.optionID" 
                                             class="text-sm text-purple-700 pl-4 border-l border-purple-300">
                                            {{ subOption.Name }} - {{ formatPrice(subOption.Daily_rate) }}/day
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center p-8 bg-gray-50 rounded-xl">
                            <p class="text-gray-500">No optional extras available for this booking.</p>
                        </div>
                    </div>

                    <!-- Insurance Options -->
                    <div class="bg-white rounded-2xl shadow-lg p-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                <img :src="carguaranteeIcon" alt="Insurance" class="w-6 h-6" loading="lazy" />
                            </div>
                            Insurance Protection
                        </h2>
                        <div v-if="vehicle.insurance_options && vehicle.insurance_options.length > 0" class="space-y-4">
                            <div v-for="insurance in vehicle.insurance_options" :key="insurance.optionID" 
                                 class="p-6 bg-blue-50 rounded-xl border border-blue-200 hover:bg-blue-100 transition-colors">
                                <div class="flex items-start gap-4">
                                    <img :src="check" alt="Check" class="w-5 h-5 text-blue-600 mt-1" loading="lazy" />
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-blue-900 mb-2">{{ insurance.Name }}</h4>
                                        <p class="text-blue-700 text-sm mb-3">{{ insurance.Description }}</p>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                            <div class="bg-white/50 p-3 rounded-lg">
                                                <span class="text-blue-600 font-medium">Daily Rate</span>
                                                <p class="text-blue-900 font-bold">{{ formatPrice(insurance.Daily_rate) }}</p>
                                            </div>
                                            <div class="bg-white/50 p-3 rounded-lg">
                                                <span class="text-blue-600 font-medium">Damage Excess</span>
                                                <p class="text-blue-900 font-bold">{{ insurance.Damage_excess }}</p>
                                            </div>
                                            <div class="bg-white/50 p-3 rounded-lg">
                                                <span class="text-blue-600 font-medium">Deposit</span>
                                                <p class="text-blue-900 font-bold">{{ insurance.Deposit }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center p-8 bg-gray-50 rounded-xl">
                            <p class="text-gray-500">No insurance options available for this vehicle.</p>
                        </div>
                    </div>

                    <!-- Location -->
                    <div class="bg-white rounded-2xl shadow-lg p-8">
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
                                    <h4 class="font-semibold text-red-900">{{ location?.name }}</h4>
                                    <p class="text-red-800">{{ location?.address_1 }}, {{ location?.address_city }}, {{ location?.address_postcode }}</p>
                                </div>
                            </div>
                        </div>
                        <div id="map" ref="mapContainerRef" class="rounded-xl h-[350px] w-full bg-gray-100 shadow-inner" v-if="location">
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
                           
                            Our Rental Partner
                        </h2>
                        <div class="flex items-start gap-6">
                            <div class="w-20 h-20 bg-gradient-to-br from-green-400 to-blue-500 rounded-2xl flex items-center justify-center shadow-lg">
                                <span class="text-white font-bold text-2xl">GM</span>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-2xl font-bold text-customPrimaryColor mb-2">GreenMotion</h4>
                                <p class="text-gray-600 mb-4">Our trusted eco-friendly car rental partner committed to sustainable transportation solutions.</p>
                                <div class="flex items-center gap-4 text-sm">
                                    <div class="flex items-center gap-2 bg-green-100 px-3 py-1 rounded-full">
                                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                        <span class="text-green-800 font-medium">Eco-Certified</span>
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
                                    <h3 class="text-xl font-bold truncate">{{ vehicle?.name }}</h3>
                                    <span class="inline-block px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm mt-2">{{ vehicle?.groupName }}</span>
                                </div>
                                <div class="flex gap-2 ml-4">
                                    <button @click="shareVehicle" class="p-2 bg-white/20 rounded-full transition-colors">
                                        <img :src="ShareIcon" alt="Share" class="w-5 h-5" loading="lazy" />
                                    </button>
                                   
                                </div>
                            </div>
                            <p class="text-blue-100 text-sm">Powered by <span class="font-semibold text-white">GreenMotion</span></p>
                        </div>
                        
                        <CardContent class="p-6">
                            <div class="space-y-6">
                                <!-- Vehicle Summary -->
                                <div class="space-y-4">
                                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                        <img :src="carIcon" alt="Car" class="w-5 h-5" loading="lazy" />
                                        <span class="text-sm text-gray-700">
                                            {{ vehicle?.transmission }} • {{ vehicle?.fuel }} • {{ vehicle?.adults }}+{{ vehicle?.children }} Seats
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                        <img :src="mileageIcon" alt="Mileage" class="w-5 h-5" loading="lazy" />
                                        <span class="text-sm text-gray-700">{{ vehicle?.mpg }} MPG • Eco-Friendly</span>
                                    </div>
                                </div>

                                <!-- Location Info -->
                                <div class="space-y-4">
                                    <div class="flex items-start gap-3 p-4 bg-green-50 rounded-lg border border-green-200">
                                        <img :src="pickupLocationIcon" alt="Pickup" class="w-5 h-5 mt-1" loading="lazy" />
                                        <div class="flex-1">
                                            <span class="text-sm font-medium text-green-800">Pickup Location</span>
                                            <p class="font-semibold text-green-900">{{ location?.address_1 }}</p>
                                            <p class="text-sm text-green-700">{{ location?.address_city }}</p>
                                            <p class="text-sm text-green-600 mt-1">{{ form.start_date }} at {{ form.start_time }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3 p-4 bg-blue-50 rounded-lg border border-blue-200">
                                        <img :src="returnLocationIcon" alt="Return" class="w-5 h-5 mt-1" loading="lazy" />
                                        <div class="flex-1">
                                            <span class="text-sm font-medium text-blue-800">Return Location</span>
                                            <p class="font-semibold text-blue-900">{{ location?.address_1 }}</p>
                                            <p class="text-sm text-blue-700">{{ location?.address_city }}</p>
                                            <p class="text-sm text-blue-600 mt-1">{{ form.end_date }} at {{ form.end_time }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Guarantee Banner -->
                                <div class="relative h-12 bg-gradient-to-r from-blue-50 to-green-50 rounded-lg shadow-inner overflow-hidden border border-blue-200">
                                    <div class="absolute left-0 top-0 h-full w-8 bg-gradient-to-r from-blue-50 to-transparent z-10"></div>
                                    <div class="absolute right-0 top-0 h-full w-8 bg-gradient-to-l from-green-50 to-transparent z-10"></div>
                                    <div class="marquee-wrapper overflow-hidden relative w-full h-12 flex items-center">
                                        <div class="marquee-content flex absolute whitespace-nowrap animate-marquee">
                                            <div class="flex items-center gap-3 px-6">
                                                <img :src="carguaranteeIcon" alt="Guarantee" class="w-6 h-6 object-contain" loading="lazy" />
                                                <p class="text-sm text-gray-800 font-medium">
                                                    Vehicle Guarantee • <span class="text-blue-600 font-bold">Vrooem</span> ensures your reservation
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-3 px-6">
                                                <img :src="carguaranteeIcon" alt="Guarantee" class="w-6 h-6 object-contain" loading="lazy" />
                                                <p class="text-sm text-gray-800 font-medium">
                                                    Vehicle Guarantee • <span class="text-blue-600 font-bold">Vrooem</span> ensures your reservation
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Date Selection -->
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Pickup Date</label>
                                        <VueDatepicker 
                                            v-model="form.start_date" 
                                            :min-date="minDate"
                                            :max-date="maxPickupDate" 
                                            :day-class="isDateBooked"
                                            :disabled-dates="getDisabledDates()"
                                            @update:model-value="handleDateFrom"
                                            placeholder="Select pickup date" 
                                            class="w-full"
                                            :enable-time-picker="false" 
                                            :clearable="true"
                                            :format="'yyyy-MM-dd'" 
                                            auto-apply 
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Return Date</label>
                                        <VueDatepicker 
                                            v-model="form.end_date"
                                            :min-date="minReturnDate" 
                                            :max-date="maxReturnDate"
                                            :day-class="isDateBooked" 
                                            :disabled-dates="getDisabledDates()"
                                            @update:model-value="handleDateTo"
                                            placeholder="Select return date" 
                                            class="w-full"
                                            :enable-time-picker="false" 
                                            :clearable="true"
                                            :format="'yyyy-MM-dd'" 
                                            auto-apply 
                                        />
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Pickup Time</label>
                                            <select v-model="form.start_time" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-transparent">
                                                <option v-for="option in departureTimeOptions" :key="option" :value="option">
                                                    {{ option }}
                                                </option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Return Time</label>
                                            <select v-model="form.end_time" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-transparent">
                                                <option v-for="option in returnTimeOptions" :key="option" :value="option">
                                                    {{ option }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Price Summary -->
                                <div v-if="form.start_date && form.end_date" class="p-6 bg-gradient-to-r from-green-50 to-blue-50 rounded-xl border border-green-200">
                                    <div class="text-center">
                                        <p class="text-3xl font-bold text-gray-900 mb-1">{{ formatPrice(calculateTotalPrice) }}</p>
                                        <p class="text-sm text-gray-600 mb-3">Total for {{ rentalDuration }} {{ rentalDuration === 1 ? 'day' : 'days' }}</p>
                                        <div class="flex items-center justify-center gap-2 text-xs text-green-700">
                                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                            <span>Eco-friendly choice</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Book Button -->
                                <Button @click="proceedToPayment" :disabled="isBooking" class="w-full bg-gradient-to-r from-customPrimaryColor to-blue-700 hover:from-customPrimaryColor/90 hover:to-blue-700/90 text-white py-4 font-semibold text-lg rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
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
    </div>

    <!-- Warning Modal -->
    <Dialog v-model:open="showWarningModal">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="text-xl font-bold text-red-600">Access Restricted</DialogTitle>
                <DialogDescription class="text-gray-600 mt-2">
                    This booking interface is designed for customers. As a provider, you cannot proceed with vehicle reservations.
                </DialogDescription>
            </DialogHeader>
            <div class="flex justify-end mt-6">
                <Button @click="showWarningModal = false" class="bg-customPrimaryColor hover:bg-customPrimaryColor/90 text-white px-6 py-2 rounded-lg">
                    Understood
                </Button>
            </div>
        </DialogContent>
    </Dialog>

    <Footer />

    <!-- Loader Overlay -->
    <div v-if="isBooking" class="loader-overlay">
        <Vue3Lottie :animation-data="universalLoader" :height="200" :width="200" />
    </div>
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

@keyframes marquee {
    0% { transform: translateX(0%); }
    100% { transform: translateX(-50%); }
}

.marquee-content {
    animation: marquee 20s linear infinite;
    display: flex;
}

.marquee-wrapper:hover .marquee-content {
    animation-play-state: paused;
}

/* Enhanced hover effects */
.group:hover .group-hover\:scale-105 {
    transform: scale(1.05);
}

.group:hover .group-hover\:scale-110 {
    transform: scale(1.1);
}

/* Image loading skeleton animation */
@keyframes skeleton-loading {
    0% {
        background-position: -200px 0;
    }
    100% {
        background-position: calc(200px + 100%) 0;
    }
}

.skeleton-loading {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200px 100%;
    animation: skeleton-loading 1.5s infinite;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .text-4xl { font-size: 2.25rem; }
    .text-3xl { font-size: 1.875rem; }
    .text-2xl { font-size: 1.5rem; }
    .text-xl { font-size: 1.25rem; }
    .h-\[600px\] { height: 400px; }
    .h-\[500px\] { height: 350px; }
    .h-\[350px\] { height: 250px; }
    .h-\[240px\] { height: 180px; }
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
    .h-\[500px\] { height: 280px; }
    .h-\[350px\] { height: 200px; }
    .h-\[240px\] { height: 150px; }
    .p-8 { padding: 1rem; }
    .p-6 { padding: 0.75rem; }
}

/* Custom scrollbar for better UX */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Focus styles for accessibility */
button:focus,
select:focus,
input:focus {
    outline: 2px solid #153b4f;
    outline-offset: 2px;
}

/* Improved gradient backgrounds */
.bg-gradient-to-r {
    background-image: linear-gradient(to right, var(--tw-gradient-stops));
}

.from-customPrimaryColor {
    --tw-gradient-from: #153b4f;
    --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(21, 59, 79, 0));
}

.to-blue-700 {
    --tw-gradient-to: #1d4ed8;
}

.from-green-50 {
    --tw-gradient-from: #f0fdf4;
    --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(240, 253, 244, 0));
}

.to-blue-50 {
    --tw-gradient-to: #eff6ff;
}

/* Enhanced card shadows and interactions */
.shadow-lg {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.hover\:shadow-xl:hover {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Loading spinner animation */
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

/* Backdrop blur support */
.backdrop-blur-sm {
    backdrop-filter: blur(4px);
}

/* Enhanced transition effects */
.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 300ms;
}

.transition-colors {
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

.transition-transform {
    transition-property: transform;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

.transition-opacity {
    transition-property: opacity;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
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

/* Enhanced image optimization */
img {
    image-rendering: -webkit-optimize-contrast;
    image-rendering: crisp-edges;
}

/* Dark mode support (if needed in the future) */
@media (prefers-color-scheme: dark) {
    /* Add dark mode styles here if needed */
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .border {
        border-width: 2px;
    }
    
    .shadow-lg {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .marquee-content {
        animation: none;
    }
    
    .transition-all,
    .transition-colors,
    .transition-transform,
    .transition-opacity {
        transition: none;
    }
    
    .animate-spin {
        animation: none;
    }
}
</style>
