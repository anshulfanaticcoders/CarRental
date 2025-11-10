<script setup>
import { Link, usePage, Head, router } from "@inertiajs/vue3";
import { computed, onMounted, ref } from "vue";
import Footer from "@/Components/Footer.vue";
import carIcon from "../../assets/carIcon.svg";
import mileageIcon from "../../assets/mileageIcon.svg";
import check from "../../assets/Check.svg";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import { useToast } from 'vue-toastification';

const toast = useToast();
const page = usePage();

const props = defineProps({
    vehicle: Object,
    groupInfo: Object,
    options: Object,
    searchParams: Object,
    locale: String
});

const formatPrice = (price, currency = 'USD') => {
    if (!price || price === 0) return 'Price on request';

    const currencySymbols = {
        'USD': '$',
        'EUR': '€',
        'GBP': '£',
        'CAD': 'C$',
        'AUD': 'A$'
    };

    const symbol = currencySymbols[currency] || '$';
    return `${symbol}${parseFloat(price).toFixed(2)}`;
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
</script>

<template>
    <Head>
        <title>{{ vehicle.brand }} {{ vehicle.model }} - Wheelsys Car Rental</title>
        <meta name="description" :content="`Rent ${vehicle.brand} ${vehicle.model} from Wheelsys. ${vehicle.seating_capacity} seats, ${vehicle.doors} doors, ${vehicle.transmission} transmission. Best rates guaranteed.`">
    </Head>

    <AuthenticatedHeaderLayout>
        <div class="min-h-screen bg-gray-50">
            <!-- Hero Section -->
            <div class="bg-white shadow-sm">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <nav class="flex text-sm text-gray-500 mb-4">
                                <Link :href="route('search', locale)" class="hover:text-gray-700">Search</Link>
                                <span class="mx-2">/</span>
                                <span class="text-gray-900">Wheelsys Vehicle Details</span>
                            </nav>
                            <h1 class="text-3xl font-bold text-gray-900">{{ vehicle.brand }} {{ vehicle.model }}</h1>
                            <p class="text-lg text-gray-600 mt-2">{{ vehicle.category || 'Standard Rental Car' }}</p>
                        </div>
                        <div class="text-right">
                            <div class="bg-green-100 text-green-800 px-4 py-2 rounded-lg inline-block">
                                <span class="text-sm font-medium">Available</span>
                            </div>
                            <p class="text-sm text-gray-500 mt-2">{{ vehicle.full_vehicle_address }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Image Gallery -->
                        <div class="bg-white rounded-xl shadow-md overflow-hidden">
                            <img
                                :src="vehicle.image"
                                :alt="`${vehicle.brand} ${vehicle.model}`"
                                class="w-full h-96 object-cover"
                            />
                            <div class="p-4 bg-gray-50">
                                <p class="text-center text-sm text-gray-600">
                                    {{ vehicle.acriss_code || vehicle.group_code }} - {{ vehicle.category }}
                                </p>
                            </div>
                        </div>

                        <!-- Vehicle Specifications -->
                        <div class="bg-white rounded-xl shadow-md p-6">
                            <h2 class="text-xl font-semibold mb-6">Vehicle Specifications</h2>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="text-center p-4 bg-gray-50 rounded-lg">
                                    <div class="text-2xl font-bold text-blue-600">{{ vehicle.seating_capacity }}</div>
                                    <div class="text-sm text-gray-600">Seats</div>
                                </div>
                                <div class="text-center p-4 bg-gray-50 rounded-lg">
                                    <div class="text-2xl font-bold text-blue-600">{{ vehicle.doors }}</div>
                                    <div class="text-sm text-gray-600">Doors</div>
                                </div>
                                <div class="text-center p-4 bg-gray-50 rounded-lg">
                                    <div class="text-2xl font-bold text-blue-600 capitalize">{{ vehicle.transmission }}</div>
                                    <div class="text-sm text-gray-600">Transmission</div>
                                </div>
                                <div class="text-center p-4 bg-gray-50 rounded-lg">
                                    <div class="text-2xl font-bold text-blue-600 capitalize">{{ vehicle.fuel }}</div>
                                    <div class="text-sm text-gray-600">Fuel Type</div>
                                </div>
                            </div>

                            <div class="mt-6 space-y-3">
                                <div v-if="vehicle.bags || vehicle.suitcases" class="flex items-center gap-3">
                                    <img :src="mileageIcon" alt="Luggage" class="w-5 h-5" />
                                    <span class="text-gray-700">
                                        Luggage: {{ vehicle.bags || 0 }} bags, {{ vehicle.suitcases || 0 }} suitcases
                                    </span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <img :src="carIcon" alt="Category" class="w-5 h-5" />
                                    <span class="text-gray-700">Category: {{ vehicle.category }}</span>
                                </div>
                                <div v-if="vehicle.group_code" class="flex items-center gap-3">
                                    <img :src="check" alt="Group Code" class="w-5 h-5" />
                                    <span class="text-gray-700">Group Code: {{ vehicle.group_code }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Benefits & Features -->
                        <div class="bg-white rounded-xl shadow-md p-6">
                            <h2 class="text-xl font-semibold mb-6">Benefits & Features</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-if="vehicle.benefits?.unlimited_mileage || vehicle.benefits?.included_km" class="flex items-center gap-3">
                                    <img :src="check" alt="Mileage" class="w-5 h-5 text-green-500" />
                                    <span class="text-gray-700">
                                        {{ vehicle.benefits.unlimited_mileage ? 'Unlimited mileage' : `${vehicle.benefits.included_km} km included` }}
                                    </span>
                                </div>
                                <div v-if="vehicle.benefits?.cancellation_available_per_day" class="flex items-center gap-3">
                                    <img :src="check" alt="Cancellation" class="w-5 h-5 text-green-500" />
                                    <span class="text-gray-700">Free Cancellation</span>
                                </div>
                                <div v-if="vehicle.benefits?.fuel_policy" class="flex items-center gap-3">
                                    <img :src="check" alt="Fuel Policy" class="w-5 h-5 text-green-500" />
                                    <span class="text-gray-700">
                                        Fuel Policy: {{ getFuelPolicyDisplay(vehicle.benefits.fuel_policy) }}
                                    </span>
                                </div>
                                <div v-if="vehicle.benefits?.minimum_driver_age" class="flex items-center gap-3">
                                    <img :src="check" alt="Driver Age" class="w-5 h-5 text-green-500" />
                                    <span class="text-gray-700">Min Age: {{ vehicle.benefits.minimum_driver_age }} years</span>
                                </div>
                                <div v-if="vehicle.benefits?.tax_inclusive" class="flex items-center gap-3">
                                    <img :src="check" alt="Tax" class="w-5 h-5 text-green-500" />
                                    <span class="text-gray-700">Tax Inclusive</span>
                                </div>
                            </div>
                        </div>

                        <!-- Available Options -->
                        <div v-if="vehicle.extras && vehicle.extras.length > 0" class="bg-white rounded-xl shadow-md p-6">
                            <h2 class="text-xl font-semibold mb-6">Available Extras</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="extra in vehicle.extras" :key="extra.code" class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="font-medium text-gray-900">{{ extra.name }}</h3>
                                            <p class="text-sm text-gray-600 mt-1">{{ extra.description }}</p>
                                            <div v-if="extra.mandatory" class="inline-block bg-red-100 text-red-800 text-xs px-2 py-1 rounded mt-2">
                                                Required
                                            </div>
                                            <div v-else-if="extra.inclusive" class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded mt-2">
                                                Included
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-semibold text-gray-900">
                                                {{ formatPrice(extra.rate, vehicle.currency) }}
                                            </div>
                                            <div class="text-xs text-gray-500">{{ extra.charge_type || 'per day' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-xl shadow-md p-6 sticky top-6">
                            <h2 class="text-xl font-semibold mb-4">Rental Details</h2>

                            <!-- Pricing -->
                            <div class="border-b border-gray-200 pb-4 mb-4">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-gray-600">Daily Rate</span>
                                    <span class="text-xl font-bold text-blue-600">
                                        {{ formatPrice(vehicle.price_per_day, vehicle.currency) }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Weekly Rate</span>
                                    <span class="font-semibold">
                                        {{ formatPrice(vehicle.price_per_week, vehicle.currency) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Pickup & Return Info -->
                            <div class="border-b border-gray-200 pb-4 mb-4">
                                <h3 class="font-medium mb-2">Pickup</h3>
                                <p class="text-sm text-gray-600">{{ searchParams.date_from }} {{ searchParams.time_from }}</p>
                                <p class="text-sm text-gray-900 font-medium">{{ vehicle.full_vehicle_address }}</p>

                                <h3 class="font-medium mt-3 mb-2">Return</h3>
                                <p class="text-sm text-gray-600">{{ searchParams.date_to }} {{ searchParams.time_to }}</p>
                                <p class="text-sm text-gray-900 font-medium">{{ vehicle.full_vehicle_address }}</p>
                            </div>

                            <!-- Book Now Button -->
                            <button
                                @click="scrollToBooking"
                                class="w-full bg-blue-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-blue-700 transition-colors"
                            >
                                Continue to Booking
                            </button>

                            <div class="mt-4 text-center">
                                <p class="text-sm text-gray-500">
                                    Free cancellation up to 24 hours before pickup
                                </p>
                            </div>

                            <!-- Provider Info -->
                            <div class="mt-6 pt-4 border-t border-gray-200">
                                <div class="flex items-center gap-3">
                                    <div class="bg-blue-100 text-blue-600 px-3 py-1 rounded text-sm font-medium">
                                        Wheelsys
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        Professional car rental service
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking Section (Hidden by default) -->
            <div id="booking-section" class="hidden">
                <!-- This would be implemented with actual booking form -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div class="bg-white rounded-xl shadow-md p-8 text-center">
                        <h2 class="text-2xl font-bold mb-4">Ready to Book?</h2>
                        <p class="text-gray-600 mb-6">
                            Complete your reservation for this {{ vehicle.brand }} {{ vehicle.model }}
                        </p>
                        <button class="bg-blue-600 text-white py-3 px-8 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                            Continue Booking Process
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedHeaderLayout>
</template>