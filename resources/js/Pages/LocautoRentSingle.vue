<script setup>
import { Link, usePage, Head, router } from "@inertiajs/vue3";
import { computed, onMounted, ref, watch, nextTick, onBeforeUnmount } from "vue";
import Footer from "@/Components/Footer.vue";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import { ChevronRight, ImageIcon } from 'lucide-vue-next';
import Card from "@/Components/ui/card/Card.vue";
import CardContent from "@/Components/ui/card/CardContent.vue";
import { Button } from "@/Components/ui/button";
import { useCurrency } from '@/composables/useCurrency';
import { useToast } from 'vue-toastification';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

// Icons
import carIcon from "../../assets/carIcon.svg";
import luggageIcon from "../../assets/luggage.svg";
import transmisionIcon from "../../assets/transmision.svg";
import peopleIcon from "../../assets/people.svg";
import fuelIcon from "../../assets/fuel.svg";
import check from "../../assets/Check.svg";
import partnersIcon from "../../assets/partners.svg";
import pickupLocationIcon from "../../assets/pickupLocationIcon.svg";
import returnLocationIcon from "../../assets/returnLocationIcon.svg";
import locationPinIcon from "../../assets/locationPin.svg";

const props = defineProps({
    vehicle: Object,
    location: Object,
    dropoffLocation: Object,
    protectionPlans: Array,
    optionalExtras: Array,
    filters: Object,
    error: String
});

const page = usePage();
const toast = useToast();
const locale = page.props.locale || 'en';
const isBooking = ref(false);
const mapContainerRef = ref(null);
let map = ref(null);

const { selectedCurrency } = useCurrency();

// Check if vehicle exists
const hasVehicle = computed(() => props.vehicle && Object.keys(props.vehicle).length > 0);

// Vehicle data computed properties (with safe access)
const vehicleImage = computed(() => props.vehicle?.image || '/images/default-car.jpg');
const vehicleModel = computed(() => props.vehicle?.model || 'Locauto Vehicle');
const vehicleBrand = computed(() => props.vehicle?.brand || '');
const sippCode = computed(() => props.vehicle?.sipp_code || '');
const transmission = computed(() => {
    const t = (props.vehicle?.transmission || '').toLowerCase();
    return t === 'automatic' ? 'Automatic' : 'Manual';
});
const fuel = computed(() => {
    const f = (props.vehicle?.fuel || '').toLowerCase();
    const types = { 'petrol': 'Petrol', 'diesel': 'Diesel', 'electric': 'Electric', 'hybrid': 'Hybrid' };
    return types[f] || 'Petrol';
});
const seatingCapacity = computed(() => props.vehicle?.seating_capacity || null);
const doors = computed(() => props.vehicle?.doors || null);
const luggage = computed(() => props.vehicle?.luggage || null);
const totalAmount = computed(() => parseFloat(props.vehicle?.total_amount || 0));
const currency = computed(() => props.vehicle?.currency || 'EUR');

// Location and date info
const pickupDate = computed(() => props.vehicle?.date_from || props.filters?.start_date || '');
const returnDate = computed(() => props.vehicle?.date_to || props.filters?.end_date || '');
const pickupTime = computed(() => props.vehicle?.start_time || props.filters?.start_time || '09:00');
const returnTime = computed(() => props.vehicle?.end_time || props.filters?.end_time || '09:00');

const rentalDays = computed(() => {
    if (!pickupDate.value || !returnDate.value) return 1;
    const pickup = new Date(pickupDate.value);
    const returnD = new Date(returnDate.value);
    const diffTime = Math.abs(returnD.getTime() - pickup.getTime());
    return Math.max(1, Math.ceil(diffTime / (1000 * 60 * 60 * 24)));
});

// SIPP code decoder for vehicle category display
const sippInfo = computed(() => {
    const sipp = sippCode.value;
    if (!sipp || sipp.length < 4) return { description: '', category: '' };
    
    const codes1 = { 'M': 'Mini', 'E': 'Economy', 'C': 'Compact', 'I': 'Intermediate', 'S': 'Standard', 'F': 'Fullsize', 'P': 'Premium', 'L': 'Luxury', 'X': 'Special' };
    const codes2 = { 'B': '2 doors', 'C': '2/4 doors', 'D': '4/5 doors', 'W': 'Wagon', 'V': 'Van', 'S': 'Sport', 'T': 'Convertible', 'F': 'SUV' };
    const codes3 = { 'M': 'Manual', 'N': 'Manual', 'C': 'Automatic', 'A': 'Automatic' };
    
    let description = '';
    if (sipp[0] && codes1[sipp[0]]) description += codes1[sipp[0]];
    if (sipp[1] && codes2[sipp[1]]) description += ' • ' + codes2[sipp[1]];
    if (sipp[2] && codes3[sipp[2]]) description += ' • ' + codes3[sipp[2]];
    
    return { description, category: codes1[sipp[0]] || '' };
});

const backToSearchUrl = computed(() => {
    const searchUrl = typeof sessionStorage !== 'undefined' ? sessionStorage.getItem('searchurl') : null;
    if (searchUrl) return searchUrl;
    return `/${locale}/s`;
});

// Map initialization
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

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
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

    const pickupIcon = createColoredIcon('#22c55e');
    const dropoffIcon = createColoredIcon('#ef4444');

    const pickupLatLng = [parseFloat(props.location.latitude), parseFloat(props.location.longitude)];
    
    L.marker(pickupLatLng, { icon: pickupIcon })
        .bindPopup(`
            <div class="text-center">
                <p class="font-semibold">Pickup: ${props.location.name}</p>
                <p>${props.location.address_city}</p>
            </div>
        `)
        .addTo(map.value);

    let bounds = L.latLngBounds([pickupLatLng]);

    if (props.dropoffLocation && 
        (props.location.latitude !== props.dropoffLocation.latitude || props.location.longitude !== props.dropoffLocation.longitude)) {
        
        const dropoffLatLng = [parseFloat(props.dropoffLocation.latitude), parseFloat(props.dropoffLocation.longitude)];
        
        L.marker(dropoffLatLng, { icon: dropoffIcon })
            .bindPopup(`
                <div class="text-center">
                    <p class="font-semibold">Dropoff: ${props.dropoffLocation.name}</p>
                    <p>${props.dropoffLocation.address_city}</p>
                </div>
            `)
            .addTo(map.value);
        
        L.polyline([pickupLatLng, dropoffLatLng], {
            color: 'black',
            weight: 2,
            opacity: 0.7,
            dashArray: '5, 10'
        }).addTo(map.value);

        bounds.extend(dropoffLatLng);
        map.value.fitBounds(bounds, { padding: [50, 50] });
    }

    setTimeout(() => {
        map.value.invalidateSize();
    }, 100);
};

// Price formatting with currency conversion
const currencySymbols = ref({});
const exchangeRates = ref(null);

onMounted(async () => {
    try {
        const [currencyRes, ratesRes] = await Promise.all([
            fetch('/currency.json'),
            fetch(`${import.meta.env.VITE_EXCHANGERATE_API_BASE_URL}/v6/${import.meta.env.VITE_EXCHANGERATE_API_KEY}/latest/USD`)
        ]);
        
        const currencyData = await currencyRes.json();
        currencySymbols.value = currencyData.reduce((acc, curr) => {
            acc[curr.code] = curr.symbol;
            return acc;
        }, {});
        
        const ratesData = await ratesRes.json();
        if (ratesData.result === 'success') {
            exchangeRates.value = ratesData.conversion_rates;
        }
    } catch (error) {
        console.error("Error loading currency data:", error);
    }

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

const formatPrice = (amount, fromCurrency = 'EUR') => {
    const numericAmount = parseFloat(amount);
    if (isNaN(numericAmount)) return '€0.00';
    
    if (!exchangeRates.value || !selectedCurrency.value) {
        return `€${numericAmount.toFixed(2)}`;
    }
    
    const rateFrom = exchangeRates.value[fromCurrency] || 1;
    const rateTo = exchangeRates.value[selectedCurrency.value] || 1;
    const converted = (numericAmount / rateFrom) * rateTo;
    const symbol = currencySymbols.value[selectedCurrency.value] || '€';
    
    return `${symbol}${converted.toFixed(2)}`;
};

const formatDateTime = (date, time) => {
    if (!date) return '';
    try {
        const dateObj = new Date(date + 'T' + (time || '09:00'));
        return dateObj.toLocaleDateString('en-US', {
            weekday: 'short',
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        }) + ' at ' + (time || '09:00');
    } catch (e) {
        return date + ' ' + time;
    }
};

// Proceed to booking
const proceedToBooking = () => {
    if (!hasVehicle.value) return;
    isBooking.value = true;
    
    // Check if user is authenticated
    if (!page.props.auth?.user) {
        sessionStorage.setItem('returnToUrl', window.location.href);
        router.visit(`/${locale}/login`);
        return;
    }
    
    router.visit(`/${locale}/locauto-rent-booking/${props.vehicle.id}/checkout`, {
        data: {
            location_id: props.filters?.location_id,
            dropoff_location_id: props.filters?.dropoff_location_id,
            start_date: pickupDate.value,
            start_time: pickupTime.value,
            end_date: returnDate.value,
            end_time: returnTime.value
        },
        onFinish: () => {
            isBooking.value = false;
        }
    });
};
</script>

<template>
    <Head>
        <title>{{ vehicleModel }} - Locauto Rent</title>
        <meta name="description" :content="`Rent ${vehicleModel} from Locauto - Pay on Arrival`" />
    </Head>

    <AuthenticatedHeaderLayout />

    <!-- Hero Section - Blue like GreenMotion -->
    <section class="bg-gradient-to-r from-customPrimaryColor to-blue-700 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-white text-4xl md:text-5xl font-bold mb-4">{{ vehicleModel }}</h1>
            <p class="text-blue-100 text-lg md:text-xl">Italian quality rental with Pay on Arrival</p>
            <div class="mt-6 flex items-center justify-center gap-4 flex-wrap">
                <div v-if="sippInfo.category" class="bg-white/20 backdrop-blur-sm rounded-full px-4 py-2 text-white">
                    <span class="font-medium">{{ sippInfo.category }}</span>
                </div>
                <div class="bg-green-500/20 backdrop-blur-sm rounded-full px-4 py-2 text-white">
                    <span class="font-medium">Pay on Arrival</span>
                </div>
            </div>
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Error State -->
        <div v-if="error || !hasVehicle" class="text-center py-16">
            <div class="bg-red-50 border border-red-200 rounded-2xl p-8 max-w-md mx-auto">
                <p class="text-red-600 text-xl font-semibold mb-4">{{ error || 'Vehicle not found' }}</p>
                <Link :href="backToSearchUrl" class="inline-flex items-center gap-2 bg-customPrimaryColor text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                    Back to Search Results
                </Link>
            </div>
        </div>

        <div v-else>
            <!-- Breadcrumb -->
            <nav class="flex items-center gap-2 text-sm mb-8 p-4 bg-gray-50 rounded-lg">
                <Link :href="`/${locale}`" class="text-customPrimaryColor hover:underline font-medium">Home</Link>
                <ChevronRight class="h-4 w-4 text-gray-400" />
                <Link :href="backToSearchUrl" class="text-customPrimaryColor hover:underline font-medium">Vehicles</Link>
                <ChevronRight class="h-4 w-4 text-gray-400" />
                <span class="text-gray-600">{{ vehicleModel }}</span>
            </nav>

            <!-- Vehicle Header Info -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">{{ vehicleModel }}</h2>
                    <div class="flex items-center gap-3 flex-wrap">
                        <span v-if="sippCode" class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                            {{ sippCode }}
                        </span>
                        <span v-if="sippInfo.category" class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                            {{ sippInfo.category }}
                        </span>
                    </div>
                </div>
                <div v-if="location" class="flex items-center gap-2 text-gray-600">
                    <img :src="locationPinIcon" alt="Location" class="w-4 h-4" loading="lazy" />
                    <span class="text-sm font-medium">{{ location.address_city }}</span>
                </div>
            </div>

            <!-- Main Content -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                <!-- Left Column: Vehicle Details -->
                <div class="lg:col-span-2 space-y-10">
                    <!-- Image Section -->
                    <div class="mb-12">
                        <div class="relative group">
                            <div class="rounded-2xl overflow-hidden shadow-2xl bg-gradient-to-br from-gray-100 to-gray-200">
                                <div class="aspect-w-16 aspect-h-9 h-[400px] md:h-[500px] relative">
                                    <img 
                                        :src="vehicleImage" 
                                        :alt="vehicleModel" 
                                        class="w-full h-full object-cover" 
                                        loading="lazy"
                                        @error="(e) => e.target.src = '/images/default-car.jpg'"
                                    />
                                </div>
                            </div>
                        </div>
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
                            <!-- People - only show if available -->
                            <div v-if="seatingCapacity" class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                    <img :src="peopleIcon" alt="People" class="w-6 h-6" loading="lazy" />
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500 font-medium">Passengers</span>
                                    <p class="font-bold text-lg text-gray-900">{{ seatingCapacity }}</p>
                                </div>
                            </div>
                            
                            <!-- Luggage - only show if available -->
                            <div v-if="luggage" class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                                    <img :src="luggageIcon" alt="Luggage" class="w-6 h-6" loading="lazy" />
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500 font-medium">Luggage</span>
                                    <p class="font-bold text-lg text-gray-900">{{ luggage }} bags</p>
                                </div>
                            </div>
                            
                            <!-- Transmission - always show -->
                            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                                    <img :src="transmisionIcon" alt="Transmission" class="w-6 h-6" loading="lazy" />
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500 font-medium">Transmission</span>
                                    <p class="font-bold text-lg text-gray-900">{{ transmission }}</p>
                                </div>
                            </div>
                            
                            <!-- Fuel - always show -->
                            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                                    <img :src="fuelIcon" alt="Fuel" class="w-6 h-6" loading="lazy" />
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500 font-medium">Fuel Type</span>
                                    <p class="font-bold text-lg text-gray-900">{{ fuel }}</p>
                                </div>
                            </div>
                            
                            <!-- Doors - only show if available -->
                            <div v-if="doors" class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500 font-medium">Doors</span>
                                    <p class="font-bold text-lg text-gray-900">{{ doors }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pay on Arrival Info -->
                    <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-2xl shadow-lg p-8 border border-green-200">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <img :src="check" alt="Check" class="w-6 h-6" loading="lazy" />
                            </div>
                            Pay on Arrival
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex items-center gap-3 p-4 bg-white/50 rounded-xl">
                                <img :src="check" alt="Check" class="w-5 h-5" loading="lazy" />
                                <span class="text-green-800 font-medium">No upfront payment required</span>
                            </div>
                            <div class="flex items-center gap-3 p-4 bg-white/50 rounded-xl">
                                <img :src="check" alt="Check" class="w-5 h-5" loading="lazy" />
                                <span class="text-green-800 font-medium">Pay at pickup location</span>
                            </div>
                            <div class="flex items-center gap-3 p-4 bg-white/50 rounded-xl">
                                <img :src="check" alt="Check" class="w-5 h-5" loading="lazy" />
                                <span class="text-green-800 font-medium">Free cancellation</span>
                            </div>
                            <div class="flex items-center gap-3 p-4 bg-white/50 rounded-xl">
                                <img :src="check" alt="Check" class="w-5 h-5" loading="lazy" />
                                <span class="text-green-800 font-medium">Flexible booking</span>
                            </div>
                        </div>
                    </div>

                    <!-- Protection Plans Section -->
                    <div v-if="protectionPlans && protectionPlans.length > 0" class="bg-white rounded-2xl shadow-lg p-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            Protection Plans
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-for="plan in protectionPlans" :key="plan.code" 
                                 class="flex items-start gap-4 p-4 bg-blue-50 rounded-xl border border-blue-200 hover:bg-blue-100 transition-colors">
                                <img :src="check" alt="Check" class="w-5 h-5 mt-1" loading="lazy" />
                                <div class="flex-1">
                                    <h4 class="text-sm font-semibold text-blue-900">{{ plan.description }}</h4>
                                    <p class="text-xs text-blue-700 font-medium mt-1">{{ formatPrice(plan.amount, plan.currency) }}/rental</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Optional Extras Section -->
                    <div v-if="optionalExtras && optionalExtras.length > 0" class="bg-white rounded-2xl shadow-lg p-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                            </div>
                            Optional Extras
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-for="extra in optionalExtras" :key="extra.code" 
                                 class="flex items-start gap-4 p-4 bg-purple-50 rounded-xl border border-purple-200 hover:bg-purple-100 transition-colors">
                                <img :src="check" alt="Check" class="w-5 h-5 mt-1" loading="lazy" />
                                <div class="flex-1">
                                    <h4 class="text-sm font-semibold text-purple-900">{{ extra.description }}</h4>
                                    <p class="text-xs text-purple-700 font-medium mt-1">{{ formatPrice(extra.amount, extra.currency) }}/rental</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Location Map Section -->
                    <div v-if="location" class="bg-white rounded-2xl shadow-lg p-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-3">
                            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                                <img :src="locationPinIcon" alt="Location" class="w-6 h-6" loading="lazy" />
                            </div>
                            Pickup Location
                        </h2>
                        <div class="mb-6 p-4 bg-red-50 rounded-xl border border-red-200">
                            <div class="flex items-start gap-3">
                                <img :src="locationPinIcon" alt="Location" class="w-6 h-6 text-red-600 mt-1" loading="lazy" />
                                <div>
                                    <h5 class="font-semibold text-red-900">{{ location.name }}</h5>
                                    <p class="text-red-800">{{ location.address_city }}, Italy</p>
                                </div>
                            </div>
                        </div>
                        <div id="map" ref="mapContainerRef" class="rounded-xl h-[350px] w-full bg-gray-100 shadow-inner">
                            <div v-if="!map" class="w-full h-full flex items-center justify-center">
                                <div class="text-center">
                                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-customPrimaryColor mx-auto mb-4"></div>
                                    <p class="text-gray-600">Loading interactive map...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Provider Info -->
                    <div class="bg-gradient-to-r from-blue-50 to-green-50 rounded-2xl shadow-lg p-8 border border-blue-200">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-3">
                            About Locauto Rent
                        </h2>
                        <div class="flex items-start gap-6">
                            <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-green-500 rounded-2xl flex items-center justify-center shadow-lg">
                                <span class="text-white font-bold text-2xl">LR</span>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-2xl font-bold text-customPrimaryColor mb-2">Locauto Rent</h4>
                                <p class="text-gray-600 mb-4">Italian quality car rental with locations across Italy. Known for excellent service and competitive pricing.</p>
                                <div class="flex items-center gap-4 text-sm flex-wrap">
                                    <div class="flex items-center gap-2 bg-blue-100 px-3 py-1 rounded-full">
                                        <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                        <span class="text-blue-800 font-medium">Italian Quality</span>
                                    </div>
                                    <div class="flex items-center gap-2 bg-green-100 px-3 py-1 rounded-full">
                                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                        <span class="text-green-800 font-medium">Trusted Partner</span>
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
                                    <h3 class="text-xl font-bold truncate">{{ vehicleModel }}</h3>
                                    <span v-if="sippCode" class="inline-block px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm mt-2">{{ sippCode }}</span>
                                </div>
                            </div>
                            <p class="text-blue-100 text-sm">Powered by <span class="font-semibold text-white">Locauto Rent</span></p>
                        </div>
                        
                        <CardContent class="p-6">
                            <div class="space-y-6">
                                <!-- Vehicle Quick Info -->
                                <div class="space-y-4">
                                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                        <img :src="carIcon" alt="Car" class="w-5 h-5" loading="lazy" />
                                        <span class="text-sm text-gray-700">
                                            {{ transmission }} • {{ fuel }}
                                            <span v-if="seatingCapacity"> • {{ seatingCapacity }} Seats</span>
                                        </span>
                                    </div>
                                </div>

                                <!-- Location Info -->
                                <div class="space-y-4">
                                    <div class="flex items-start gap-3 p-4 bg-green-50 rounded-lg border border-green-200">
                                        <img :src="pickupLocationIcon" alt="Pickup" class="w-5 h-5 mt-1" loading="lazy" />
                                        <div class="flex-1">
                                            <span class="text-sm font-medium text-green-800">Pickup Location</span>
                                            <p class="font-semibold text-green-900">{{ location?.name || filters?.location_id }}</p>
                                            <p v-if="location?.address_city" class="text-sm text-green-700">{{ location.address_city }}</p>
                                            <p class="text-sm text-green-600 mt-1">{{ formatDateTime(pickupDate, pickupTime) }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3 p-4 bg-blue-50 rounded-lg border border-blue-200">
                                        <img :src="returnLocationIcon" alt="Return" class="w-5 h-5 mt-1" loading="lazy" />
                                        <div class="flex-1">
                                            <span class="text-sm font-medium text-blue-800">Return Location</span>
                                            <p class="font-semibold text-blue-900">{{ dropoffLocation?.name || location?.name || filters?.location_id }}</p>
                                            <p v-if="dropoffLocation?.address_city || location?.address_city" class="text-sm text-blue-700">{{ dropoffLocation?.address_city || location?.address_city }}</p>
                                            <p class="text-sm text-blue-600 mt-1">{{ formatDateTime(returnDate, returnTime) }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Price Summary -->
                                <div class="p-6 bg-gradient-to-r from-green-50 to-blue-50 rounded-xl border border-green-200">
                                    <div class="text-center">
                                        <p class="text-3xl font-bold text-gray-900 mb-1">{{ formatPrice(totalAmount, currency) }}</p>
                                        <p class="text-sm text-gray-600 mb-3">Total for {{ rentalDays }} {{ rentalDays === 1 ? 'day' : 'days' }}</p>
                                        <div class="flex items-center justify-center gap-2 text-xs text-green-700">
                                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                            <span>Pay on Arrival</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Book Button -->
                                <Button 
                                    @click="proceedToBooking" 
                                    :disabled="isBooking" 
                                    class="w-full bg-gradient-to-r from-customPrimaryColor to-blue-700 hover:from-customPrimaryColor/90 hover:to-blue-700/90 text-white py-4 font-semibold text-lg rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105"
                                >
                                    <div class="flex items-center justify-center gap-2">
                                        <span v-if="isBooking">Processing...</span>
                                        <span v-else>Reserve Now - Pay on Arrival</span>
                                        <ChevronRight v-if="!isBooking" class="w-5 h-5" />
                                    </div>
                                </Button>

                                <!-- Security Badge -->
                                <div class="text-center">
                                    <div class="flex flex-col items-center justify-center gap-3 p-3 bg-gray-50 rounded-lg">
                                        <img :src="partnersIcon" alt="Security" loading="lazy" />
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

    <Footer />
</template>