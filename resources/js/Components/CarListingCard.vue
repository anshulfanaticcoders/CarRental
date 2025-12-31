<script setup>
import { computed, ref } from 'vue';
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

// State for GreenMotion/USave plan selection
const selectedPackage = ref('BAS'); // Default to Basic
const showAllPlans = ref(false);

// --- Computed Properties ---

// Calculate Number of Rental Days
const numberOfRentalDays = computed(() => {
    if (props.form.date_from && props.form.date_to) {
        const startStr = `${props.form.date_from} ${props.form.start_time || '09:00'}`;
        const endStr = `${props.form.date_to} ${props.form.end_time || '09:00'}`;
        const start = new Date(startStr);
        const end = new Date(endStr);
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return diffDays > 0 ? diffDays : 1;
    }
    return 1;
});

// Check if vehicle is GreenMotion or USave
const isGreenMotionOrUSave = computed(() => {
    return props.vehicle.source === 'greenmotion' || props.vehicle.source === 'usave';
});

// Get sorted products (BAS, PLU, PRE, PMP order)
const sortedProducts = computed(() => {
    if (!isGreenMotionOrUSave.value) return [];
    const order = ['BAS', 'PLU', 'PRE', 'PMP'];
    const products = props.vehicle.products || [];
    return order.map(type => products.find(p => p.type === type)).filter(Boolean);
});

// Get daily price for selected package
const dailyPrice = computed(() => {
    if (!isGreenMotionOrUSave.value) return null;
    const product = sortedProducts.value.find(p => p.type === selectedPackage.value);
    if (!product) return '0.00';
    return (parseFloat(product.total) / numberOfRentalDays.value).toFixed(2);
});

// Get Premium Plus daily price for display
const premiumPlusDailyPrice = computed(() => {
    if (!isGreenMotionOrUSave.value) return null;
    const product = sortedProducts.value.find(p => p.type === 'PMP');
    if (!product) return '0.00';
    return (parseFloat(product.total) / numberOfRentalDays.value).toFixed(2);
});

// Get package name from type
const getPackageName = (type) => {
    const names = {
        BAS: 'Basic',
        PLU: 'Plus',
        PRE: 'Premium',
        PMP: 'Premium Plus'
    };
    return names[type] || type;
};

// Get benefits for a product (semi-dynamic approach)
const getBenefits = (product) => {
    if (!product) return [];
    const benefits = [];
    const type = product.type;

    // Dynamic from API
    if (product.excess !== undefined && parseFloat(product.excess) === 0) {
        benefits.push('Glass and tyres covered');
    } else if (product.excess !== undefined) {
        benefits.push(`Excess: ${product.currency || ''}${product.excess}`);
    }

    if (product.debitcard === 'Y') {
        benefits.push('Debit Card Accepted');
    }

    if (product.fuelpolicy === 'FF') {
        benefits.push('Free Fuel / Full to Full');
    } else if (product.fuelpolicy === 'SL') {
        benefits.push('Like for Like fuel policy');
    }

    if (product.costperextradistance !== undefined && parseFloat(product.costperextradistance) === 0) {
        benefits.push('Unlimited mileage');
    }

    // Static based on type (only what's not in API)
    if (type === 'BAS') {
        benefits.push('Non-refundable');
        benefits.push('Non-amendable');
    }

    if (type === 'PMP') {
        benefits.push('Two free extras on collection');
    }

    if (type === 'PLU' || type === 'PRE' || type === 'PMP') {
        benefits.push('Cancellation in line with T&Cs');
    }

    return benefits;
};

// Format price with currency
const formatPrice = (price, currency) => {
    return `${currency || '€'}${parseFloat(price).toFixed(2)}`;
};

// Select a package
const selectPackage = (type) => {
    selectedPackage.value = type;
    showAllPlans.value = false;
};

// Close modal
const closeModal = () => {
    showAllPlans.value = false;
};

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

// Build route params with selected package for GM/USave
const getRouteParams = (vehicle) => {
    const baseParams = {
        locale: page.props.locale,
        id: vehicle.id,
        provider: vehicle.source,
        location_id: vehicle.provider_pickup_id,
        start_date: props.form.date_from,
        end_date: props.form.date_to,
        start_time: props.form.start_time,
        end_time: props.form.end_time,
        dropoff_location_id: props.form.dropoff_location_id,
        rentalCode: props.form.rentalCode
    };

    // Add selected package for GM/USave
    if (isGreenMotionOrUSave.value) {
        baseParams.package = selectedPackage.value;
    }

    return baseParams;
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

</script>

<template>
    <div
        class="vehicle-card flex flex-col md:flex-row bg-white rounded-[20px] shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-300 transform hover:-translate-y-1 mb-6"
        :data-vehicle-id="vehicle.id"
    >
        <!-- 1. Left: Image Section -->
        <div class="w-full md:w-[35%] relative">
             <Link
                :href="vehicle.source !== 'internal'
                    ? route(getProviderRoute(vehicle), getRouteParams(vehicle))
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

        <!-- 2. Center: Details Section -->
        <div :class="['w-full flex flex-col justify-between p-5', isGreenMotionOrUSave ? 'md:w-[45%]' : 'md:w-[75%]']">
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

                <!-- Row 2: Vehicle Details (Icons) -->
                <div class="flex flex-wrap gap-4 mt-3 pb-3 border-b border-gray-100">
                    <!-- Luggage (Dynamic) -->
                    <div v-if="vehicleSpecs.bagDisplay" class="flex items-center gap-1.5" title="Luggage Capacity">
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
                    <!-- For GM/USave, price is handled in the plan panel on the right -->
                    <!-- For other providers, use the slot -->
                    <slot v-if="!isGreenMotionOrUSave" name="dailyPrice"></slot>
                    <div v-else class="text-sm text-gray-500">
                        from <span class="text-lg font-bold text-customPrimaryColor">{{ sortedProducts[0]?.currency || '€' }}{{ dailyPrice }}</span>/day
                    </div>
                </div>

                <!-- View Deal Button -->
                 <Link
                    :href="vehicle.source !== 'internal'
                        ? route(getProviderRoute(vehicle), getRouteParams(vehicle))
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

        <!-- 3. Right: Plan Selection Panel (GM/USave only) -->
        <div v-if="isGreenMotionOrUSave && sortedProducts.length > 0" class="hidden md:flex md:w-[25%] flex-col border-l border-gray-100 p-4 bg-gray-50/50 gap-3">
            <!-- Basic Package Card -->
            <div
                @click="selectPackage('BAS')"
                class="sidebar-package-card bg-white border rounded-lg cursor-pointer transition-all p-3 relative"
                :class="selectedPackage === 'BAS' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-blue-300'"
            >
                <!-- Package Header -->
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <span class="text-sm font-bold text-gray-800 block">Basic</span>
                        <span class="text-xs text-gray-500">BAS</span>
                    </div>
                    <div v-if="selectedPackage === 'BAS'" class="bg-blue-500 text-white rounded-full w-5 h-5 flex items-center justify-center flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>

                <!-- Price -->
                <div class="mb-2 pb-2 border-b border-gray-100">
                    <p class="text-base font-bold text-customPrimaryColor leading-tight">
                        {{ sortedProducts.find(p => p.type === 'BAS')?.currency || '€' }}{{ (parseFloat(sortedProducts.find(p => p.type === 'BAS')?.total || 0) / numberOfRentalDays).toFixed(2) }}
                        <span class="text-xs font-normal text-gray-500">/day</span>
                    </p>
                    <p class="text-xs text-gray-500">
                        Total: {{ sortedProducts.find(p => p.type === 'BAS')?.currency || '€' }}{{ parseFloat(sortedProducts.find(p => p.type === 'BAS')?.total || 0).toFixed(2) }}
                    </p>
                </div>

                <!-- Key Benefits -->
                <ul class="space-y-1">
                    <li v-for="benefit in getBenefits(sortedProducts.find(p => p.type === 'BAS')).slice(0, 2)" :key="benefit" class="text-xs flex items-start gap-1.5">
                        <img :src="check" class="w-3 h-3 mt-0.5 flex-shrink-0" alt="✓" />
                        <span class="text-gray-600 line-clamp-1">{{ benefit }}</span>
                    </li>
                    <li v-if="sortedProducts.find(p => p.type === 'BAS')?.deposit" class="text-xs flex items-start gap-1.5">
                        <span class="text-gray-500 text-[10px]">Deposit: {{ sortedProducts.find(p => p.type === 'BAS')?.currency || '€' }}{{ parseFloat(sortedProducts.find(p => p.type === 'BAS')?.deposit || 0).toFixed(0) }}</span>
                    </li>
                </ul>
            </div>

            <!-- Premium Plus Package Card -->
            <div
                @click="selectPackage('PMP')"
                class="sidebar-package-card bg-white border rounded-lg cursor-pointer transition-all p-3 relative border-l-4"
                :class="[
                    selectedPackage === 'PMP' ? 'border-blue-500 bg-blue-50 border-l-green-500' : 'border-gray-200 hover:border-blue-300 border-l-green-500'
                ]"
            >
                <!-- Package Header -->
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <span class="text-sm font-bold text-gray-800 block">Premium Plus</span>
                        <span class="text-xs text-gray-500">PMP</span>
                    </div>
                    <div v-if="selectedPackage === 'PMP'" class="bg-blue-500 text-white rounded-full w-5 h-5 flex items-center justify-center flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>

                <!-- Price -->
                <div class="mb-2 pb-2 border-b border-gray-100">
                    <p class="text-base font-bold text-customPrimaryColor leading-tight">
                        {{ sortedProducts.find(p => p.type === 'PMP')?.currency || '€' }}{{ premiumPlusDailyPrice }}
                        <span class="text-xs font-normal text-gray-500">/day</span>
                    </p>
                    <p class="text-xs text-gray-500">
                        Total: {{ sortedProducts.find(p => p.type === 'PMP')?.currency || '€' }}{{ parseFloat(sortedProducts.find(p => p.type === 'PMP')?.total || 0).toFixed(2) }}
                    </p>
                </div>

                <!-- Key Benefits -->
                <ul class="space-y-1">
                    <li v-for="benefit in getBenefits(sortedProducts.find(p => p.type === 'PMP')).slice(0, 2)" :key="benefit" class="text-xs flex items-start gap-1.5">
                        <img :src="check" class="w-3 h-3 mt-0.5 flex-shrink-0" alt="✓" />
                        <span class="text-gray-600 line-clamp-1">{{ benefit }}</span>
                    </li>
                    <li v-if="sortedProducts.find(p => p.type === 'PMP')?.deposit" class="text-xs flex items-start gap-1.5">
                        <span class="text-gray-500 text-[10px]">Deposit: {{ sortedProducts.find(p => p.type === 'PMP')?.currency || '€' }}{{ parseFloat(sortedProducts.find(p => p.type === 'PMP')?.deposit || 0).toFixed(0) }}</span>
                    </li>
                </ul>
            </div>

            <!-- View more plans button -->
            <button
                @click="showAllPlans = true"
                class="text-xs text-blue-600 font-medium hover:text-blue-800 transition-colors self-start flex items-center gap-1"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                View more plans
            </button>
        </div>

        <!-- Mobile: Show simple plan selector below details -->
        <div v-if="isGreenMotionOrUSave && sortedProducts.length > 0" class="md:hidden px-5 pb-4 bg-gray-50">
            <select
                v-model="selectedPackage"
                class="w-full p-2 rounded-lg border border-gray-200 text-sm"
            >
                <option v-for="product in sortedProducts" :key="product.type" :value="product.type">
                    {{ getPackageName(product.type) }} - {{ product.currency }}{{ (parseFloat(product.total) / numberOfRentalDays).toFixed(2) }}/day
                </option>
            </select>
        </div>
    </div>

    <!-- Modal: All 4 Plans -->
    <Teleport to="body">
        <div v-if="showAllPlans" class="plans-modal" @click.self="closeModal">
            <div class="plans-modal-content">
                <!-- Close button -->
                <button
                    @click="closeModal"
                    class="modal-close-btn hover:text-gray-900 transition-colors"
                    aria-label="Close modal"
                >
                    ✕
                </button>

                <!-- Header -->
                <div class="mb-4">
                    <h2 class="text-2xl font-bold text-gray-800">Select Your Package</h2>
                    <p class="text-sm text-gray-500">Choose the best option for your rental</p>
                </div>

                <!-- Plans Grid -->
                <div class="plans-grid">
                    <div
                        v-for="product in sortedProducts"
                        :key="product.type"
                        class="plan-card p-4 border rounded-lg cursor-pointer transition-all hover:shadow-lg"
                        :class="selectedPackage === product.type ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-blue-300'"
                        @click="selectPackage(product.type)"
                    >
                        <!-- Package Header -->
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h3 class="text-lg font-bold text-gray-800">{{ getPackageName(product.type) }}</h3>
                                <p class="text-xs text-gray-500">{{ product.type }}</p>
                            </div>
                            <div v-if="selectedPackage === product.type" class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center">
                                ✓
                            </div>
                        </div>

                        <!-- Price -->
                        <div class="mb-3 pb-3 border-b border-gray-200">
                            <p class="text-2xl font-bold text-customPrimaryColor">
                                {{ product.currency }}{{ (parseFloat(product.total) / numberOfRentalDays).toFixed(2) }}
                                <span class="text-sm font-normal text-gray-500">/day</span>
                            </p>
                            <p class="text-xs text-gray-500">
                                Total: {{ product.currency }}{{ parseFloat(product.total).toFixed(2) }} ({{ numberOfRentalDays }} days)
                            </p>
                        </div>

                        <!-- Benefits -->
                        <ul class="space-y-1">
                            <li v-for="benefit in getBenefits(product)" :key="benefit" class="text-xs flex items-start gap-2">
                                <img :src="check" class="w-3 h-3 mt-0.5 flex-shrink-0" alt="✓" />
                                <span>{{ benefit }}</span>
                            </li>
                            <li v-if="product.deposit" class="text-xs flex items-start gap-2">
                                <img :src="check" class="w-3 h-3 mt-0.5 flex-shrink-0" alt="✓" />
                                <span>Deposit: {{ product.currency }}{{ parseFloat(product.deposit).toFixed(2) }}</span>
                            </li>
                        </ul>

                        <!-- Select Button -->
                        <button
                            @click.stop="selectPackage(product.type)"
                            class="mt-4 w-full py-2 rounded-lg font-semibold transition-all"
                            :class="selectedPackage === product.type
                                ? 'bg-blue-500 text-white cursor-default'
                                : 'bg-gray-100 text-gray-700 hover:bg-blue-500 hover:text-white'"
                        >
                            {{ selectedPackage === product.type ? 'Selected' : 'Select' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
/* Sidebar Package Cards */
.sidebar-package-card {
    user-select: none;
}

.sidebar-package-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Modal overlay */
.plans-modal {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.plans-modal-content {
    position: relative;
    background: white;
    padding: 2rem;
    border-radius: 1rem;
    max-width: 800px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.plans-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

@media (max-width: 640px) {
    .plans-grid {
        grid-template-columns: 1fr;
    }
}

.plan-card {
    transition: all 0.2s ease;
}

.modal-close-btn {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6b7280;
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s;
}

.modal-close-btn:hover {
    background: #f3f4f6;
}
</style>
