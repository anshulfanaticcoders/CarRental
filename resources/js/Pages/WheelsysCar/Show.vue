<script setup>
import { Link, Head, router, usePage } from "@inertiajs/vue3";
import { computed, onMounted, ref, watch } from "vue";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import Footer from "@/Components/Footer.vue";
import { useCurrency } from '@/composables/useCurrency';
import MapPin from "../../../assets/MapPin.svg";
import carIcon from "../../../assets/carIcon.svg";
import check from "../../../assets/Check.svg";
import doorIcon from "../../../assets/door.svg";
import luggageIcon from "../../../assets/luggage.svg";
import fuelIcon from "../../../assets/fuel.svg";
import peopleIcon from "../../../assets/people.svg";
import carbonIcon from "../../../assets/carbon-emmision.svg";
import { ChevronRight, ImageIcon, ZoomIn } from 'lucide-vue-next';
import Card from "@/Components/ui/card/Card.vue";
import CardContent from "@/Components/ui/card/CardContent.vue";
import CardHeader from "@/Components/ui/card/CardHeader.vue";
import CardTitle from "@/Components/ui/card/CardTitle.vue";
import { Button } from "@/Components/ui/button";

const props = defineProps({
    vehicle: Object,
    searchParams: Object,
    locale: String
});

const page = usePage();
const authUser = computed(() => page.props.auth.user);

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

// Booking methods
const proceedToBooking = () => {
    if (!authUser.value) {
        // If user is not logged in, redirect to login page
        router.visit(route('login', { locale: props.locale }));
        return;
    }

    const groupCode = props.vehicle?.group_code;
    if (!groupCode) {
        console.error('Cannot proceed to booking without a vehicle group code.');
        return;
    }

    // Pass search parameters directly to the booking page route
    const bookingUrl = route('wheelsys.booking.create', {
        locale: props.locale,
        groupCode: groupCode,
        ...props.searchParams // Spread the search params into the query string
    });

    router.get(bookingUrl);
};

onMounted(async () => {
    fetchExchangeRates();

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

    // Initialize map after DOM is ready
    setTimeout(() => {
        initMap();
    }, 100);
});

const getCurrencySymbol = (code) => {
    return currencySymbols.value[code] || '$';
};

const convertCurrency = (price, fromCurrency) => {
    const numericPrice = parseFloat(price);
    if (isNaN(numericPrice)) {
        return 0;
    }

    let fromCurrencyCode = fromCurrency;
    if (symbolToCodeMap[fromCurrency]) {
        fromCurrencyCode = symbolToCodeMap[fromCurrency];
    }

    if (!exchangeRates.value || !fromCurrencyCode || !selectedCurrency.value) {
        return numericPrice;
    }
    const rateFrom = exchangeRates.value[fromCurrencyCode];
    const rateTo = exchangeRates.value[selectedCurrency.value];
    if (rateFrom && rateTo) {
        return (numericPrice / rateFrom) * rateTo;
    }
    return numericPrice;
};

const formatPrice = (price, currency = 'USD') => {
    if (!price || price === 0) return 'Price on request';
    const originalCurrency = currency || 'USD';
    const convertedPrice = convertCurrency(price, originalCurrency);
    const currencySymbol = getCurrencySymbol(selectedCurrency.value);
    return `${currencySymbol}${convertedPrice.toFixed(2)}`;
};

const getFuelPolicyDisplay = (policy) => {
    if (!policy) return 'Standard';
    const policyMap = {
        'full_to_full': 'Full-to-Full',
        'same_to_same': 'Same-to-Same',
        'prepaid': 'Pre-paid Fuel'
    };
    return policyMap[policy] || policy.charAt(0).toUpperCase() + policy.slice(1).replace(/_/g, ' ');
};

const scrollToBooking = () => {
    const element = document.getElementById('booking-section');
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
};

// Map functionality
let map = null;
let marker = null;
const showMap = ref(true);

const isValidCoordinate = (coord) => {
    const num = parseFloat(coord);
    return !isNaN(num) && isFinite(num);
};

const initMap = () => {
    if (map) {
        map.remove();
        map = null;
    }

    map = L.map("vehicle-map", {
        zoomControl: true,
        maxZoom: 18,
        minZoom: 3,
        zoomSnap: 0.25,
        markerZoomAnimation: false,
        preferCanvas: true,
    });

    const defaultView = [20, 0]; // Default to a global view
    const defaultZoom = 2;

    // Use vehicle location if available, otherwise default to Orlando Airport
    let vehicleCoords = [28.9313, -81.2790]; // Orlando Airport coordinates

    // Try to get coordinates from vehicle or search params
    const pickupLat = props.searchParams?.pickup_latitude || props.vehicle?.latitude || props.vehicle?.pickup_latitude;
    const pickupLng = props.searchParams?.pickup_longitude || props.vehicle?.longitude || props.vehicle?.pickup_longitude;

    if (pickupLat && pickupLng &&
        isValidCoordinate(pickupLat) && isValidCoordinate(pickupLng)) {
        vehicleCoords = [parseFloat(pickupLat), parseFloat(pickupLng)];
    }

    map.setView(vehicleCoords, 13);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "© OpenStreetMap contributors",
    }).addTo(map);

    map.createPane("markers");
    map.getPane("markers").style.zIndex = 1000;

    addVehicleMarker(vehicleCoords);

    setTimeout(() => {
        if (map) {
            map.invalidateSize();
        }
    }, 200);
};

const addVehicleMarker = (coords) => {
    if (marker) {
        marker.remove();
        marker = null;
    }

    const customIcon = L.divIcon({
        className: "custom-div-icon",
        html: `
            <div class="map-pin-wrapper">
                <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 2C12.268 2 6 8.268 6 16C6 25.5 20 38 20 38S34 25.5 34 16C34 8.268 27.732 2 20 2Z" fill="#1e40af" stroke="white" stroke-width="2"/>
                    <circle cx="20" cy="16" r="5" fill="white"/>
                </svg>
            </div>
        `,
        iconSize: [40, 40],
        iconAnchor: [20, 40],
        popupAnchor: [0, -40],
        pane: "markers",
    });

    marker = L.marker(coords, {
        icon: customIcon,
        pane: "markers",
    }).bindPopup(`
        <div class="text-center popup-content">
            <p class="font-semibold">${props.vehicle?.brand || 'Wheelsys'} ${props.vehicle?.model || 'Vehicle'}</p>
            <p class="text-sm">Pickup Location</p>
            <p class="text-xs text-gray-600">${props.vehicle?.full_vehicle_address || 'Orlando Airport'}</p>
        </div>
    `);

    map.addLayer(marker);
};

const handleMapToggle = (value) => {
    showMap.value = value;
    if (value && map) {
        setTimeout(() => {
            map.invalidateSize();
            let vehicleCoords = [28.9313, -81.2790]; // Orlando Airport coordinates

            // Try to get coordinates from vehicle or search params
            const pickupLat = props.searchParams?.pickup_latitude || props.vehicle?.latitude || props.vehicle?.pickup_latitude;
            const pickupLng = props.searchParams?.pickup_longitude || props.vehicle?.longitude || props.vehicle?.pickup_longitude;

            if (pickupLat && pickupLng &&
                isValidCoordinate(pickupLat) && isValidCoordinate(pickupLng)) {
                vehicleCoords = [parseFloat(pickupLat), parseFloat(pickupLng)];
            }

            map.setView(vehicleCoords, 13);
        }, 100);
    }
};
</script>

<template>
    <Head>
        <title>{{ vehicle?.brand || 'Wheelsys' }} {{ vehicle?.model || 'Vehicle' }} - Wheelsys Car Rental</title>
        <meta
            name="description"
            :content="`Rent ${vehicle?.brand || 'Wheelsys'} ${vehicle?.model || 'Vehicle'} from Wheelsys. ${vehicle?.seating_capacity || 5} seats, ${vehicle?.doors || 4} doors, ${vehicle?.transmission || 'automatic'} transmission. Best rates guaranteed.`"
        />
    </Head>

    <AuthenticatedHeaderLayout />

    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-customPrimaryColor to-blue-700 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-white text-4xl md:text-5xl font-bold mb-4">{{ vehicle?.brand || 'Wheelsys' }} {{ vehicle?.model || 'Vehicle' }}</h1>
            <p class="text-blue-100 text-lg md:text-xl">Premium car rental with great service</p>
            <div class="mt-6 flex items-center justify-center gap-4">
                <div class="bg-white/20 backdrop-blur-sm rounded-full px-4 py-2 text-white">
                    <span class="font-medium">{{ vehicle?.category || 'Standard' }}</span>
                </div>
                <div class="bg-green-500/20 backdrop-blur-sm rounded-full px-4 py-2 text-white">
                    <span class="font-medium">Wheelsys</span>
                </div>
            </div>
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm mb-8 p-4 bg-gray-50 rounded-lg">
            <Link :href="route('search', locale)" class="text-customPrimaryColor hover:underline font-medium">Home</Link>
            <ChevronRight class="h-4 w-4 text-gray-400" />
            <Link :href="route('search', locale)" class="text-customPrimaryColor hover:underline font-medium">Vehicles</Link>
            <ChevronRight class="h-4 w-4 text-gray-400" />
            <span class="text-gray-600">{{ vehicle?.brand || 'Wheelsys' }} {{ vehicle?.model || 'Vehicle' }}</span>
        </nav>

        <!-- Map Toggle -->
        <div class="flex justify-end max-[768px]:hidden my-[2rem]">
            <div class="flex items-center space-x-2">
                <span class="text-customPrimaryColor font-medium">Map</span>
                <button
                    @click="handleMapToggle(!showMap)"
                    :class="[
                        'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                        showMap ? 'bg-customPrimaryColor' : 'bg-gray-200'
                    ]"
                >
                    <span
                        :class="[
                            'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                            showMap ? 'translate-x-6' : 'translate-x-1'
                        ]"
                    />
                </button>
            </div>
        </div>

        <!-- Vehicle Header Info -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h2 class="text-3xl font-bold text-gray-900 mb-2">{{ vehicle?.brand || 'Wheelsys' }} {{ vehicle?.model || 'Vehicle' }}</h2>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center px-3 py-1 bg-customPrimaryColor/10 text-customPrimaryColor rounded-full text-sm font-medium">
                        {{ vehicle?.category || 'Standard' }}
                    </span>
                    <span class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                        </svg>
                        Premium Service
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-2 text-gray-600">
                    <img :src="MapPin" alt="Map Pin Icon" class="w-5 h-5 mt-1" />
                    <span class="text-sm font-medium">{{ vehicle?.full_vehicle_address || 'Orlando Airport' }}</span>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Vehicle Details -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Enhanced Image Section -->
                <div class="relative group rounded-2xl overflow-hidden shadow-2xl bg-gradient-to-br from-gray-100 to-gray-200">
                    <!-- Wheelsys Corner Badge -->
                    <div class="wheelsys-corner-badge"></div>

                    <div class="aspect-w-16 aspect-h-9 h-[600px] relative">
                        <img
                            :src="vehicle.image"
                            :alt="`${vehicle.brand} ${vehicle.model}`"
                            class="w-full h-full object-cover transition-all duration-500 group-hover:scale-105"
                            loading="lazy"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <button
                            class="absolute top-6 right-6 bg-white/90 backdrop-blur-sm p-3 rounded-full shadow-lg opacity-0 group-hover:opacity-100 transition-all duration-300 hover:bg-white"
                        >
                            <ZoomIn class="w-6 h-6 text-gray-700" />
                        </button>
                    </div>
                    <div class="absolute bottom-6 left-6 right-6">
                        <div class="bg-white/95 backdrop-blur-sm rounded-xl p-4 transform translate-y-4 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300">
                            <h3 class="font-bold text-lg text-gray-900 mb-1">{{ vehicle?.brand || 'Wheelsys' }} {{ vehicle?.model || 'Vehicle' }}</h3>
                        </div>
                    </div>
                    <!-- Image Info Badge -->
                    <div class="absolute top-6 left-6 bg-white/95 backdrop-blur-sm rounded-xl px-4 py-2 shadow-lg">
                        <div class="flex items-center gap-2">
                            <ImageIcon class="w-5 h-5 text-customPrimaryColor" />
                            <span class="text-sm font-medium text-gray-900">Photos</span>
                        </div>
                    </div>
                </div>

                <!-- Vehicle Features -->
                <div class="bg-white rounded-2xl shadow-lg p-8 hover:shadow-xl transition-shadow duration-300">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-3">
                        <div class="w-10 h-10 bg-customPrimaryColor/10 rounded-full flex items-center justify-center">
                            <img :src="carIcon" alt="Car Icon" class="w-6 h-6" />
                        </div>
                        Vehicle Specifications
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <div class="w-12 h-12 bg-customPrimaryColor/10 rounded-full flex items-center justify-center">
                                <img :src="peopleIcon" alt="People Icon" class="w-6 h-6" />
                            </div>
                            <div>
                                <span class="text-sm text-gray-500 font-medium">People</span>
                                <p class="font-bold text-lg text-gray-900">{{ vehicle?.seating_capacity || 5 }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                                <img :src="doorIcon" alt="Door Icon" class="w-6 h-6" />
                            </div>
                            <div>
                                <span class="text-sm text-gray-500 font-medium">Doors</span>
                                <p class="font-bold text-lg text-gray-900">{{ vehicle?.doors || 4 }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                                <img :src="luggageIcon" alt="Luggage Icon" class="w-6 h-6" />
                            </div>
                            <div>
                                <span class="text-sm text-gray-500 font-medium">Luggage</span>
                                <p class="font-bold text-lg text-gray-900">{{ (vehicle?.bags || 0) }} bags, {{ (vehicle?.suitcases || 0) }} suitcases</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                                <img :src="fuelIcon" alt="Fuel Icon" class="w-6 h-6" />
                            </div>
                            <div>
                                <span class="text-sm text-gray-500 font-medium">Fuel</span>
                                <p class="font-bold text-lg text-gray-900 capitalize">{{ vehicle?.fuel || 'Petrol' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                <img :src="check" alt="Check Icon" class="w-5 h-5" />
                            </div>
                            <div>
                                <span class="text-sm text-gray-500 font-medium">Transmission</span>
                                <p class="font-bold text-lg text-gray-900 capitalize">{{ vehicle?.transmission || 'Automatic' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Benefits & Features -->
                    <div class="mt-6 space-y-3">
                        <div v-if="vehicle.benefits?.unlimited_mileage || vehicle.benefits?.included_km" class="flex items-center gap-3">
                            <span class="text-blue-600">✓</span>
                            <span class="text-gray-700">
                                {{ vehicle.benefits.unlimited_mileage ? 'Unlimited mileage' : `${vehicle.benefits.included_km} km included` }}
                            </span>
                        </div>
                        <div v-if="vehicle.benefits?.cancellation_available_per_day" class="flex items-center gap-3">
                            <span class="text-blue-600">✓</span>
                            <span class="text-gray-700">Free Cancellation</span>
                        </div>
                        <div v-if="vehicle.benefits?.fuel_policy" class="flex items-center gap-3">
                            <span class="text-blue-600">✓</span>
                            <span class="text-gray-700">
                                Fuel Policy: {{ getFuelPolicyDisplay(vehicle.benefits.fuel_policy) }}
                            </span>
                        </div>
                        <div v-if="vehicle.benefits?.minimum_driver_age" class="flex items-center gap-3">
                            <span class="text-blue-600">✓</span>
                            <span class="text-gray-700">Min Age: {{ vehicle.benefits.minimum_driver_age }} years</span>
                        </div>
                    </div>
                </div>

                <!-- Vehicle Location Map -->
                <div v-show="showMap" class="bg-white rounded-2xl shadow-lg p-8 hover:shadow-xl transition-shadow duration-300">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-3">
                        <div class="w-10 h-10 bg-customPrimaryColor/10 rounded-full flex items-center justify-center">
                            <img :src="MapPin" alt="Map Pin" class="w-6 h-6" />
                        </div>
                        Vehicle Location
                    </h2>
                    <div class="rounded-lg overflow-hidden" style="height: 400px;">
                        <div id="vehicle-map" class="h-full w-full"></div>
                    </div>
                    <div class="mt-4 p-4 bg-customPrimaryColor/5 rounded-lg border border-customPrimaryColor/20">
                        <div class="flex items-center gap-3">
                            <img :src="MapPin" alt="Map Pin" class="w-5 h-5 text-customPrimaryColor" />
                            <div>
                                <span class="text-sm font-medium text-customPrimaryColor">Pickup Location</span>
                                <p class="font-semibold text-customPrimaryColor">{{ vehicle?.full_vehicle_address || 'Orlando Airport' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Available Extras -->
                <div v-if="vehicle.extras && vehicle.extras.length > 0" class="bg-white rounded-2xl shadow-lg p-8 hover:shadow-xl transition-shadow duration-300">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-3">
                        <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                            <ImageIcon class="w-6 h-6" />
                        </div>
                        Available Extras
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="extra in vehicle.extras" :key="extra.code" class="border border-gray-200 rounded-lg p-4">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h3 class="text-sm font-medium text-gray-900">{{ extra.name }}</h3>
                                    <p class="text-xs text-gray-600 mt-1">{{ extra.description }}</p>
                                    <div v-if="extra.mandatory" class="inline-block bg-red-100 text-red-800 text-xs px-2 py-1 rounded mt-2">
                                        Required
                                    </div>
                                    <div v-else-if="extra.inclusive" class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded mt-2">
                                        Included
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-semibold text-gray-900">
                                    {{ formatPrice(extra.rate, vehicle.currency) }}
                                </div>
                                <div class="text-xs text-gray-500">{{ extra.charge_type || 'per day' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Booking Card -->
            <div class="lg:col-span-1">
                <Card class="sticky top-4 shadow-2xl border-0 overflow-hidden hover:shadow-3xl transition-shadow duration-300">
                    <div class="bg-gradient-to-r from-customPrimaryColor to-blue-700 p-6 text-white">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-xl font-bold truncate">{{ vehicle?.brand || 'Wheelsys' }} {{ vehicle?.model || 'Vehicle' }}</h3>
                                <span class="inline-block px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm mt-2">{{ vehicle?.category || 'Standard' }}</span>
                            </div>
                            <div class="flex gap-2 ml-4">
                                <Button class="p-2 bg-white/20 rounded-full transition-colors">
                                    <img :src="MapPin" alt="Map Pin" class="w-5 h-5" />
                                </Button>
                            </div>
                        </div>
                        <p class="text-blue-100 text-sm">Powered by <span class="font-semibold text-white">Wheelsys</span></p>
                    </div>

                    <CardContent class="p-6">
                        <div class="space-y-6">
                            <!-- Vehicle Summary -->
                            <div class="space-y-4">
                                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
                                    <img :src="carIcon" alt="Car Icon" class="w-5 h-5" />
                                    <span class="text-sm text-gray-700">
                                        {{ vehicle?.transmission || 'Automatic' }} • {{ vehicle?.fuel || 'Petrol' }} • {{ vehicle?.seating_capacity || 5 }} Seats
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
                                    <img :src="carbonIcon" alt="Carbon Icon" class="w-5 h-5" />
                                    <span class="text-sm text-gray-700">Great rates & service</span>
                                </div>
                            </div>

                            <!-- Location Info -->
                            <div class="space-y-4">
                                <div class="flex items-start gap-3 p-4 bg-green-50 rounded-lg border border-green-200">
                                    <img :src="MapPin" alt="Map Pin" class="w-5 h-5 mt-1" />
                                    <div class="flex-1">
                                        <span class="text-sm font-medium text-green-800">Pickup Location</span>
                                        <p class="font-semibold text-green-900">{{ searchParams?.pickup_station || 'Orlando Airport' }}</p>
                                        <p class="text-sm text-green-700">{{ vehicle?.full_vehicle_address || 'Orlando Airport' }}</p>
                                        <p class="text-sm text-green-600">{{ searchParams?.date_from }} {{ searchParams?.time_from || '10:00' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 p-4 bg-blue-50 rounded-lg border border-blue-200">
                                    <img :src="MapPin" alt="Map Pin" class="w-5 h-5 mt-1" />
                                    <div class="flex-1">
                                        <span class="text-sm font-medium text-customPrimaryColor">Return Location</span>
                                        <p class="font-semibold text-customPrimaryColor">{{ searchParams?.return_station || 'Orlando Airport' }}</p>
                                        <p class="text-sm text-customPrimaryColor/80">{{ searchParams?.date_to }} {{ searchParams?.time_to || '10:00' }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Price Summary -->
                            <div class="p-6 bg-gradient-to-r from-green-50 to-blue-50 rounded-xl border border-green-200">
                                <div class="text-center">
                                    <p class="text-3xl font-bold text-gray-900 mb-1">{{ formatPrice(vehicle.price_per_day, vehicle.currency) }}</p>
                                    <p class="text-sm text-gray-600 mb-3">Daily rate • From {{ vehicle?.currency || 'USD' }}{{ formatPrice(vehicle.price_per_day, vehicle.currency).split(getCurrencySymbol(selectedCurrency.value))[1] }}</p>
                                    <div class="flex items-center justify-center gap-2 text-xs text-green-700">
                                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                        <span>Great value!</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Book Button -->
                            <Button @click="proceedToBooking" class="w-full bg-gradient-to-r from-customPrimaryColor to-blue-700 hover:from-customPrimaryColor/90 hover:to-blue-700/90 text-white py-4 font-semibold text-lg rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                                <div class="flex items-center justify-center gap-2">
                                    <span>Reserve Now</span>
                                    <ChevronRight class="w-5 h-5" />
                                </div>
                            </Button>

                            <!-- Security Badge -->
                            <div class="text-center">
                                <div class="flex flex-col items-center justify-center gap-3 p-3 bg-gray-50 rounded-lg">
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                        <img :src="check" alt="Check Icon" class="w-5 h-5" />
                                    </div>
                                    <p class="text-sm text-gray-600 font-medium">Secure & Protected Booking</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Booking Section (Hidden by default) -->
        <div id="booking-section" class="hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="bg-white rounded-2xl shadow-md p-8 text-center">
                    <h2 class="text-2xl font-bold mb-4">Ready to Book?</h2>
                    <p class="text-gray-600 mb-6">
                        Complete your reservation for this {{ vehicle?.brand }} {{ vehicle?.model }}
                    </p>
                    <Button class="bg-blue-600 text-white py-3 px-8 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                        Continue Booking Process
                    </Button>
                </div>
            </div>
        </div>
    </div>

    <Footer />
</template>

<style scoped>
.wheelsys-corner-badge {
    position: absolute;
    top: 0;
    left: 0;
    width: 0;
    height: 0;
    border-top: 90px solid var(--custom-primary-color, #1e40af); /* Use custom primary color */
    border-right: 90px solid transparent;
    z-index: 10;
}

.wheelsys-corner-badge::after {
    content: "Wheelsys";
    position: absolute;
    top: -41px; /* Adjust as needed */
    left: 0px; /* Adjust as needed */
    color: white;
    font-size: 0.7rem;
    font-weight: bold;
    transform: rotate(-45deg);
    transform-origin: 0% 0%;
    white-space: nowrap;
}

/* Map Styles */
.map-pin-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
}

.custom-div-icon {
    background: none;
    border: none;
}

/* Leaflet pane z-index overrides */
.leaflet-pane.leaflet-marker-pane {
    z-index: 1000 !important;
}

.leaflet-pane.leaflet-popup-pane {
    z-index: 1001 !important;
}

.leaflet-pane.leaflet-tile-pane {
    z-index: 200;
}

.leaflet-pane.leaflet-overlay-pane {
    z-index: 400;
}

.leaflet-popup {
    z-index: 1001 !important;
}

.leaflet-container {
    z-index: 1;
}

.leaflet-control-container {
    z-index: 2000;
}

#vehicle-map {
    height: 100%;
    width: 100%;
}

.popup-image {
    width: 100%;
    height: 70px;
    object-fit: cover;
    border-top-left-radius: 0.5rem;
    border-top-right-radius: 0.5rem;
    margin-bottom: 5px;
}
</style>
