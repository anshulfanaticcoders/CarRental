<script setup>
import { Link, useForm, usePage, router, Head } from "@inertiajs/vue3";
import { computed, onMounted, ref } from "vue";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import Footer from "@/Components/Footer.vue";
import SearchBar from "@/Components/SearchBar.vue";

const props = defineProps({
    vehicles: Array,
    filters: Object,
    location: Object,
    pickupDate: String,
    returnDate: String,
    pickupTime: String,
    returnTime: String,
    age: [String, Number]
});

const loading = ref(false);

const formatPrice = (price, currency = 'EUR') => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency
    }).format(price);
};

const getSIPPDescription = (sippCode) => {
    if (!sippCode || sippCode.length < 4) return sippCode;

    const sipp = sippCode.toUpperCase();
    const category = sipp[0];
    const type = sipp[1];
    const transmission = sipp[2];
    const fuel = sipp[3];

    let description = '';

    // Category
    const categories = {
        'M': 'Mini',
        'E': 'Economy',
        'C': 'Compact',
        'I': 'Intermediate',
        'S': 'Standard',
        'F': 'Full Size',
        'P': 'Premium',
        'L': 'Luxury',
        'X': 'Special'
    };

    if (categories[category]) {
        description += categories[category];
    }

    // Type
    const types = {
        'B': '2-door',
        'C': '4-door',
        'D': '5-door',
        'W': 'Wagon/Estate',
        'V': 'Passenger Van',
        'L': 'Limousine',
        'S': 'Sport',
        'R': 'Pickup Regular Cab',
        'T': 'Pickup Extended Cab',
        'Q': 'Pickup Crew Cab',
        'Z': 'Special Sport Car',
        'H': 'Motor Home',
        'Y': '2-wheel Vehicle',
        'N': 'Special',
        'E': 'Coupe',
        'F': 'Fun Car/Jeep'
    };

    if (types[type]) {
        description += ' ' + types[type];
    }

    // Transmission
    if (transmission === 'A') {
        description += ' Automatic';
    } else if (transmission === 'M') {
        description += ' Manual';
    }

    // Fuel
    const fuels = {
        'B': 'Petrol',
        'D': 'Diesel',
        'C': 'Hybrid',
        'E': 'Electric',
        'L': 'LPG/Gas',
        'R': 'Hybrid Electric',
        'S': 'Diesel Electric',
        'Z': 'Electric Hybrid'
    };

    if (fuels[fuel]) {
        description += ' ' + fuels[fuel];
    }

    return description || sippCode;
};
</script>

<template>
    <Head title="Locauto Rent Cars" />

    <AuthenticatedHeaderLayout>
        <div class="min-h-screen bg-gray-50">
            <!-- Header Section -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-12">
                <div class="container mx-auto px-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-4xl font-bold mb-4">Locauto Rent Cars</h1>
                            <p class="text-xl mb-2">Pay on Arrival - No Credit Card Fees</p>
                            <p class="text-lg opacity-90">Free Cancellation - 24/7 Customer Support</p>
                        </div>
                        <div class="text-right">
                            <img src="/assets/locauto-logo.png" alt="Locauto Rent" class="h-20 bg-white p-2 rounded-lg shadow-lg" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="bg-white shadow-md py-6">
                <div class="container mx-auto px-4">
                    <SearchBar :initialFilters="filters" />
                </div>
            </div>

            <!-- Results Header -->
            <div class="container mx-auto px-4 py-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-semibold text-gray-800">
                        Available Vehicles
                        <span v-if="vehicles?.length" class="text-lg text-gray-600 ml-2">({{ vehicles.length }} cars found)</span>
                    </h2>
                    <div v-if="location" class="text-gray-600">
                        <span class="font-medium">Pickup:</span> {{ location.name }}
                        <span class="mx-2">|</span>
                        <span class="font-medium">Dates:</span> {{ pickupDate }} - {{ returnDate }}
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="text-center py-12">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                    <p class="mt-4 text-gray-600">Loading vehicles...</p>
                </div>

                <!-- No Results -->
                <div v-else-if="!vehicles?.length" class="text-center py-12 bg-white rounded-lg shadow">
                    <div class="text-gray-400 mb-4">
                        <svg class="mx-auto h-24 w-24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No vehicles available</h3>
                    <p class="text-gray-600">Try adjusting your search dates or location</p>
                </div>

                <!-- Vehicle List -->
                <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div v-for="vehicle in vehicles" :key="vehicle.id"
                         class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-300">

                        <!-- Vehicle Image -->
                        <div class="relative">
                            <img :src="vehicle.image || '/default-car.jpg'"
                                 :alt="vehicle.brand + ' ' + vehicle.model"
                                 class="w-full h-48 object-cover rounded-t-lg">

                            <!-- Badges -->
                            <div class="absolute top-2 left-2 space-y-1">
                                <span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full">Pay on Arrival</span>
                                <span class="bg-blue-500 text-white text-xs px-2 py-1 rounded-full">No Credit Card Fees</span>
                            </div>
                        </div>

                        <!-- Vehicle Details -->
                        <div class="p-4">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">
                                {{ vehicle.brand }} {{ vehicle.model }}
                            </h3>

                            <!-- SIPP Code -->
                            <div v-if="vehicle.sipp_code" class="text-sm text-gray-600 mb-3">
                                <span class="font-medium">Category:</span>
                                <span class="ml-1">{{ getSIPPDescription(vehicle.sipp_code) }}</span>
                                <span class="text-xs text-gray-500 ml-1">({{ vehicle.sipp_code }})</span>
                            </div>

                            <!-- Features -->
                            <div class="flex flex-wrap gap-2 mb-3">
                                <span v-if="vehicle.transmission" class="text-xs bg-gray-100 px-2 py-1 rounded">
                                    {{ vehicle.transmission === 'automatic' ? 'Auto' : 'Manual' }}
                                </span>
                                <span v-if="vehicle.fuel" class="text-xs bg-gray-100 px-2 py-1 rounded">
                                    {{ vehicle.fuel }}
                                </span>
                                <span v-if="vehicle.seating_capacity" class="text-xs bg-gray-100 px-2 py-1 rounded">
                                    {{ vehicle.seating_capacity }} Seats
                                </span>
                                <span v-if="vehicle.doors" class="text-xs bg-gray-100 px-2 py-1 rounded">
                                    {{ vehicle.doors }} Doors
                                </span>
                            </div>

                            <!-- Price -->
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <div v-if="vehicle.total_amount" class="text-2xl font-bold text-blue-600">
                                        {{ formatPrice(vehicle.total_amount, vehicle.currency) }}
                                    </div>
                                    <div v-else-if="vehicle.price_per_day" class="text-2xl font-bold text-blue-600">
                                        {{ formatPrice(vehicle.price_per_day, vehicle.currency) }}<span class="text-sm font-normal">/day</span>
                                    </div>
                                </div>
                            </div>

                            <!-- CTA Button -->
                            <Link :href="route('locauto-rent-car.show', {
                                        locale: $page.props.locale || 'en',
                                        code: vehicle.sipp_code || vehicle.id
                                    })"
                                  class="w-full block text-center bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                                View Details
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <Footer />
        </div>
    </AuthenticatedHeaderLayout>
</template>

<style scoped>
.container {
    max-width: 1200px;
}
</style>