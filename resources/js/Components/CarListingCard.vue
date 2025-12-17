<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useCurrency } from '@/composables/useCurrency';

// Icons
import carIcon from "../../assets/carIcon.svg";
import mileageIcon from "../../assets/mileageIcon.svg"; // Keeping for generic use if needed
import fuelIcon from "../../assets/fuel.svg";
import transmissionIcon from "../../assets/transmittionIcon.svg";
import seatingIcon from "../../assets/travellerIcon.svg";
import doorIcon from "../../assets/door.svg"; 
import check from "../../assets/Check.svg";
import Heart from "../../assets/Heart.svg";
import FilledHeart from "../../assets/FilledHeart.svg";
import acIcon from "../../assets/ac.svg";
// Note: If some icons are missing, I'll use text or generic replacements for now.

const props = defineProps({
    vehicle: Object,
    form: Object, // Needed for date/time/location params in links
    favoriteStatus: Boolean,
    popEffect: Boolean,
});

const emit = defineEmits(['toggleFavourite', 'saveSearchUrl']);

const { selectedCurrency } = useCurrency();
const page = usePage();

// --- Computed Properties ---

// Calculate Number of Rental Days
const numberOfRentalDays = computed(() => {
    if (props.form.date_from && props.form.date_to) {
        const start = new Date(props.form.date_from);
        const end = new Date(props.form.date_to);
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return diffDays > 0 ? diffDays : 1;
    }
    return 1;
});

// Image Handling
const getImageSource = (vehicle) => {
    if (vehicle.source === 'internal') {
        return vehicle.images?.find((image) => image.image_type === 'primary')?.image_url || '/default-image.png';
    }
    if (vehicle.image) return vehicle.image;
    if (vehicle.source === 'wheelsys') return '/wheelsys-placeholder.jpg';
    return '/images/dummyCarImaage.png';
};

const handleImageError = (event) => {
    if (event.target.src.includes('placeholder') || event.target.src.includes('no-vehicle') || event.target.onerror === null) return;
    if (event.target.dataset.imageUrl && event.target.dataset.imageUrl.includes('wheelsys')) {
        event.target.src = '/wheelsys-placeholder.jpg';
    } else {
        event.target.src = '/images/dummyCarImaage.png';
    }
    event.target.onerror = null;
};

// Route Generation
const getProviderRoute = (vehicle) => {
    const routes = {
        'greenmotion': 'green-motion-car.show',
        'okmobility': 'ok-mobility-car.show',
        'wheelsys': 'wheelsys-car.show',
        'adobe': 'adobe-car.show',
        'locauto_rent': 'locauto-rent-car.show'
    };
    return routes[vehicle.source] || 'green-motion-car.show';
};

// --- Specs & Features Logic ---

const vehicleSpecs = computed(() => {
    const v = props.vehicle;
    return {
        passengers: v.seating_capacity || v.passengers || v.adults,
        doors: v.doors,
        transmission: v.transmission, // 'Manual', 'Automatic'
        fuel: v.fuel, // 'Petrol', 'Diesel', etc.
        bagDisplay: (() => {
            // GreenMotion / USave: Return formatted string ONLY if non-zero total
            if (v.luggageLarge !== undefined || v.luggageMed !== undefined || v.luggageSmall !== undefined) {
                 const small = parseInt(v.luggageSmall || 0);
                 const med = parseInt(v.luggageMed || 0);
                 const large = parseInt(v.luggageLarge || 0);
                 if (small + med + large === 0) return null; // Don't show if all are 0
                 return `S:${small} M:${med} L:${large}`;
            }
            // Wheelsys / External: Sum of bags
            if (v.bags !== undefined || v.suitcases !== undefined) {
                const total = (parseInt(v.bags)||0) + (parseInt(v.suitcases)||0);
                return total > 0 ? total : null;
            }
            // Locauto / Internal / Adobe -> Return count if valid
            if (v.luggage || v.luggage_capacity) {
                 return v.luggage || v.luggage_capacity;
            }
            return null;
        })(),

        mpg: v.mpg,
        co2: v.co2,
        acriss: v.sipp_code || v.acriss_code || v.group_code || v.category,
        airConditioning: v.airConditioning === 'true' || v.airConditioning === true || (v.features && v.features.includes('Air Conditioning')),
    };
});

// Currency Conversion (Simplified for this component - ideally imported)
// NOTE: Ideally this should come from the composable or props, but duplicating logic for independence if composable doesn't export raw rate map.
// Assuming parent provides converted values or we use the composable's full power.
// For now, I'll rely on the existing layout's logic which passed *raw* data and handled conversion in template.
// I will implement a basic helper check or assume `convertCurrency` is available globally or injected.
// Since I can't easily inject the complex `convertCurrency` from SearchResults (it depends on local state `exchangeRates`),
// I will assume `CarCard` receives the `exchangeRates` or `convertCurrency` function as a prop would be cleaner,
// BUT to avoid massive refactor of `SearchResults`, I will copy the minimal conversion logic if `useCurrency` doesn't provide it.
// Checking `useCurrency.js`... it usually just handles selection.
// I'll add `convertCurrency` as a prop to this component from `SearchResults` to ensure consistent rates.

</script>

<script>
// Separate script block for non-setup exports or mixins if needed
</script>

<template>
    <div
        class="vehicle-card flex flex-col md:flex-row bg-white rounded-[20px] shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-300 transform hover:-translate-y-1 mb-6 md:h-[260px]"
        :data-vehicle-id="vehicle.id"
    >
        <!-- 1. Left: Image Section -->
        <div class="w-full md:w-[35%] relative">
             <Link
                :href="vehicle.source !== 'internal' 
                    ? route(getProviderRoute(vehicle), { locale: page.props.locale, id: vehicle.id, provider: vehicle.source, location_id: vehicle.provider_pickup_id, start_date: form.date_from, end_date: form.date_to, start_time: form.start_time, end_time: form.end_time, dropoff_location_id: form.dropoff_location_id, rentalCode: form.rentalCode }) 
                    : route('vehicle.show', { locale: page.props.locale, id: vehicle.id, package: form.package_type, pickup_date: form.date_from, return_date: form.date_to })"
                class="block h-full"
                @click="$emit('saveSearchUrl')"
            >
                <!-- Image Container -->
                <div class="relative w-full h-[200px] md:h-full">
                    <img 
                        :src="getImageSource(vehicle)" 
                        @error="handleImageError"
                        :data-image-url="getImageSource(vehicle)"
                        alt="Vehicle Image" 
                        class="w-full h-full object-cover object-center"
                        loading="lazy" 
                    />
                    
                    <!-- Category/SIPP Badge -->
                    <div class="absolute top-3 left-3 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full text-xs font-semibold text-customPrimaryColor shadow-sm z-10">
                        {{ vehicle.category || vehicleSpecs.acriss || vehicle.model }}
                    </div>
                </div>

                <!-- Favorite Button -->
                <button 
                    v-if="(!$page.props.auth?.user || page.props.auth?.user?.role === 'customer') && vehicle.source === 'internal'" 
                    @click.stop="$emit('toggleFavourite', vehicle)"
                    class="absolute top-3 right-3 bg-white p-2 rounded-full shadow-md transition-transform active:scale-95"
                    :class="{ 'text-red-500': favoriteStatus }"
                >
                    <img :src="favoriteStatus ? FilledHeart : Heart" alt="Favorite" class="w-5 h-5" />
                </button>
            </Link>
        </div>

        <!-- 2. Right: Details Section -->
        <div class="w-full md:w-[65%] p-5 flex flex-col justify-between">
            <div class="flex flex-col gap-2">
                <!-- Row 1: Location & Title -->
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800 leading-tight mb-1" :title="vehicle.brand + ' ' + vehicle.model">{{ vehicle.brand }} {{ vehicle.model }}</h3>
                        <div class="flex items-center text-sm text-gray-500 gap-1">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-customPrimaryColor" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="truncate max-w-[200px]">{{ vehicle.full_vehicle_address || vehicle.pickup_location_name || 'Location Available' }}</span>
                        </div>
                    </div>
                     <!-- Rating (Internal Only) -->
                    <div v-if="vehicle.source === 'internal' && vehicle.review_count > 0" class="flex items-center bg-yellow-50 px-2 py-1 rounded-lg">
                        <span class="text-yellow-500 font-bold text-sm mr-1">★</span>
                        <span class="text-xs font-semibold text-gray-700">{{ vehicle.average_rating.toFixed(1) }}</span>
                        <span class="text-xs text-gray-400 ml-1">({{ vehicle.review_count }})</span>
                    </div>
                </div>

                <!-- Row 2: Veheicle Details (Icons) -->
                <div class="flex flex-wrap gap-4 mt-3 pb-3 border-b border-gray-100">
                    <!-- Luggage (Dynamic) -->
                    <div v-if="vehicleSpecs.bagDisplay" class="flex items-center gap-1.5" title="Luggage Capacity">
                        <!-- Use an inline SVG or import the icon similar to others if available -->
                         <svg class="w-4 h-4 opacity-70 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <span class="text-sm font-medium text-gray-700 whitespace-nowrap">{{ vehicleSpecs.bagDisplay }}</span>
                    </div>

                    <!-- Passengers -->
                    <div class="flex items-center gap-1.5" title="Passengers">
                        <img :src="seatingIcon" class="w-4 h-4 opacity-70" alt="Seats" />
                         <span class="text-sm font-medium text-gray-700">{{ vehicleSpecs.passengers || 4 }}</span>
                    </div>
                    <!-- Transmission -->
                    <div class="flex items-center gap-1.5" title="Transmission">
                         <img :src="transmissionIcon" class="w-4 h-4 opacity-70" alt="Transmission" />
                         <span class="text-sm font-medium text-gray-700 capitalize">{{ vehicleSpecs.transmission || 'Auto' }}</span>
                    </div>
                     <!-- Fuel -->
                    <div class="flex items-center gap-1.5" title="Fuel Type">
                        <img :src="fuelIcon" class="w-4 h-4 opacity-70" alt="Fuel" />
                        <span class="text-sm font-medium text-gray-700 capitalize">{{ vehicleSpecs.fuel || 'Petrol' }}</span>
                    </div>
                     <!-- Doors -->
                     <div v-if="vehicleSpecs.doors" class="flex items-center gap-1.5" title="Doors">
                         <img :src="doorIcon" class="w-4 h-4 opacity-70" alt="Doors" />
                         <span class="text-sm font-medium text-gray-700">{{ vehicleSpecs.doors }} Door</span>
                    </div>
                     <!-- Bags -->
                     <div v-if="vehicleSpecs.bagLarge || vehicleSpecs.bagSmall" class="flex items-center gap-1.5" title="Luggage">
                         <span class="text-sm font-medium text-gray-700 flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                            {{ (parseInt(vehicleSpecs.bagLarge) || 0) + (parseInt(vehicleSpecs.bagSmall) || 0) }}
                         </span>
                    </div>
                </div>

                <!-- Row 3: Specs / Features -->
                <div class="flex flex-wrap gap-2 mt-1">
                    <span v-if="vehicleSpecs.airConditioning" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 gap-1">
                        <img :src="acIcon" class="w-3 h-3" alt="AC" />
                        AC
                    </span>
                    <span v-if="vehicle.features && vehicle.features.includes('Bluetooth')" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                        Bluetooth
                    </span>
                     <!-- Free Cancellation Tag -->
                    <span v-if="vehicle.benefits?.cancellation_available_per_day || vehicle.source === 'wheelsys'" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-50 text-green-700">
                        Free Cancellation
                    </span>
                     <span v-if="vehicle.mileage === 'Unlimited' || vehicle.benefits?.limited_km_per_day === false" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-50 text-purple-700">
                        Unlimited Mileage
                    </span>
                </div>
            </div>

            <!-- Row 4: Price & Action -->
            <div class="flex justify-between items-end mt-4 pt-2">
                 <!-- Price -->
                <div class="flex flex-col">
                    <span class="text-xs text-gray-500">Total Price</span>
                     <!-- We need to slot this or use a prop function because conversion depends on parent state. 
                          For now, I will use a SLOT for the price to allow parent to control formatting/currency. -->
                    <slot name="price"></slot>
                </div>
                
                <!-- View Deal Button -->
                 <Link
                    :href="vehicle.source !== 'internal' 
                        ? route(getProviderRoute(vehicle), { locale: page.props.locale, id: vehicle.id, provider: vehicle.source, location_id: vehicle.provider_pickup_id, start_date: form.date_from, end_date: form.date_to, start_time: form.start_time, end_time: form.end_time, dropoff_location_id: form.dropoff_location_id, rentalCode: form.rentalCode }) 
                        : route('vehicle.show', { locale: page.props.locale, id: vehicle.id, package: form.package_type, pickup_date: form.date_from, return_date: form.date_to })"
                    class="bg-customPrimaryColor text-white px-6 py-2 rounded-lg font-semibold hover:bg-opacity-90 transition-all shadow-md flex items-center gap-2"
                    @click="$emit('saveSearchUrl')"
                >
                    View Deal
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </Link>
            </div>
        </div>
    </div>
</template>
