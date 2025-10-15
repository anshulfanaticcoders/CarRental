<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import LocationPicker from '../../../Components/LocationPicker.vue';
import axios from 'axios';

const props = defineProps({
    business: {
        type: Object,
        required: true,
    },
    locations: {
        type: Array,
        default: () => [],
    },
    dashboardToken: {
        type: String,
        required: true,
    },
    locale: {
        type: String,
        required: true,
    },
});

const form = useForm({
    location_id: '',
    usage_limit: '',
    daily_usage_limit: '',
    monthly_usage_limit: '',
    valid_until: '',
    geo_restriction_enabled: false,
    max_distance_km: '1.00',
    customer_restriction: 'all',
    min_customer_age: '',
    allowed_countries: [],
    security_level: 'standard',
});

// State for new location creation
const showNewLocationForm = ref(false);
const newLocationForm = useForm({
    name: '',
    address_line_1: '',
    address_line_2: '',
    city: '',
    state: '',
    country: '',
    postal_code: '',
    latitude: '',
    longitude: '',
    location_code: '',
});

const selectedNewLocation = ref(null);
const isCreatingLocation = ref(false);

const submitForm = () => {
    form.post(`/${props.locale}/business/qr-codes/store/${props.dashboardToken}`, {
        onSuccess: () => {
            router.visit(`/${props.locale}/business/dashboard/${props.dashboardToken}`);
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors);
        },
    });
};

const cancel = () => {
    router.visit(`/${props.locale}/business/dashboard/${props.dashboardToken}`);
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

// Handle location selection from LocationPicker
const handleLocationSelect = (location) => {
    selectedNewLocation.value = location;
    newLocationForm.address_line_1 = location.address;
    newLocationForm.city = location.city;
    newLocationForm.state = location.state || '';
    newLocationForm.country = location.country;
    newLocationForm.latitude = location.latitude;
    newLocationForm.longitude = location.longitude;

    // Generate a location code from the name
    if (location.city) {
        newLocationForm.location_code = location.city.toUpperCase().replace(/\s+/g, '_');
    }
};

// Create new location
const createNewLocation = async () => {
    isCreatingLocation.value = true;

    try {
        const response = await axios.post(`/${props.locale}/business/locations/create/${props.dashboardToken}`, {
            name: newLocationForm.name,
            address_line_1: newLocationForm.address_line_1,
            address_line_2: newLocationForm.address_line_2,
            city: newLocationForm.city,
            state: newLocationForm.state,
            country: newLocationForm.country,
            postal_code: newLocationForm.postal_code,
            latitude: newLocationForm.latitude,
            longitude: newLocationForm.longitude,
            location_code: newLocationForm.location_code,
        });

        // Add the new location to the locations list
        const newLocation = response.data.location;
        props.locations.push(newLocation);

        // Select the newly created location
        form.location_id = newLocation.id;

        // Reset form and hide it
        newLocationForm.reset();
        selectedNewLocation.value = null;
        showNewLocationForm.value = false;

        // Show success message
        alert('Location created successfully!');

    } catch (error) {
        console.error('Error creating location:', error);
        alert('Failed to create location. Please try again.');
    } finally {
        isCreatingLocation.value = false;
    }
};

// Cancel new location creation
const cancelNewLocation = () => {
    showNewLocationForm.value = false;
    newLocationForm.reset();
    selectedNewLocation.value = null;
};

// Add business_id to form data
form.business_id = props.business.id;
</script>

<template>
    <Head :title="'Generate QR Code - ' + business.name" />

    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center space-x-4">
                        <Link :href="`/${locale}/business/dashboard/${dashboardToken}`" class="text-blue-600 hover:text-blue-800">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </Link>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Generate QR Code</h1>
                            <p class="text-sm text-gray-500">{{ business.name }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white shadow rounded-lg">
                <form @submit.prevent="submitForm" class="p-6 space-y-6">
                    <!-- Location Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Location (Optional)</label>
                        <select
                            v-model="form.location_id"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="">Select a location</option>
                            <option v-for="location in locations" :key="location.id" :value="location.id">
                                {{ location.name }} - {{ location.address_line_1 }}
                            </option>
                        </select>

                        <div class="mt-3 flex items-center justify-between">
                            <p class="text-sm text-gray-500">Assign this QR code to a specific business location</p>
                            <button
                                type="button"
                                @click="showNewLocationForm = true"
                                class="text-sm text-blue-600 hover:text-blue-800 font-medium"
                            >
                                + Add New Location
                            </button>
                        </div>
                    </div>

                    <!-- New Location Form -->
                    <div v-if="showNewLocationForm" class="border-t pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Add New Location</h3>

                        <div class="space-y-4">
                            <!-- Location Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Location Name</label>
                                <input
                                    type="text"
                                    v-model="newLocationForm.name"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="E.g., Main Office, Downtown Branch"
                                >
                            </div>

                            <!-- Address Details -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Street Address</label>
                                <input
                                    type="text"
                                    v-model="newLocationForm.address_line_1"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="123 Main Street"
                                    :disabled="!newLocationForm.address_line_1"
                                >
                                <input
                                    type="text"
                                    v-model="newLocationForm.address_line_2"
                                    class="mt-2 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Apartment, suite, unit, building, floor, etc."
                                >
                            </div>

                            <!-- City, State, Country, Postal Code -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">City</label>
                                    <input
                                        type="text"
                                        v-model="newLocationForm.city"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="City"
                                        :disabled="!newLocationForm.city"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">State/Province</label>
                                    <input
                                        type="text"
                                        v-model="newLocationForm.state"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="State"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Country</label>
                                    <input
                                        type="text"
                                        v-model="newLocationForm.country"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Country"
                                        :disabled="!newLocationForm.country"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Postal Code</label>
                                    <input
                                        type="text"
                                        v-model="newLocationForm.postal_code"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Postal Code"
                                    >
                                </div>
                            </div>

                            <!-- Location Picker for Google Maps -->
                            <div v-if="!selectedNewLocation" class="border-t pt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Find Location on Map</label>
                                <LocationPicker :onLocationSelect="handleLocationSelect" />
                            </div>

                            <!-- Selected Location Info -->
                            <div v-if="selectedNewLocation" class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">Selected Location:</h4>
                                <p class="text-sm text-gray-600">{{ selectedNewLocation.fullAddress }}</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Lat: {{ selectedNewLocation.latitude }}, Lng: {{ selectedNewLocation.longitude }}
                                </p>
                                <button
                                    type="button"
                                    @click="selectedNewLocation = null; newLocationForm.reset()"
                                    class="mt-2 text-xs text-red-600 hover:text-red-800"
                                >
                                    Clear Location
                                </button>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex justify-end space-x-3">
                                <button
                                    type="button"
                                    @click="cancelNewLocation"
                                    class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    @click="createNewLocation"
                                    :disabled="!newLocationForm.name || !newLocationForm.address_line_1 || !newLocationForm.city || !newLocationForm.country || isCreatingLocation"
                                    class="px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 disabled:opacity-50"
                                >
                                    <span v-if="isCreatingLocation">Creating...</span>
                                    <span v-else>Create Location</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Usage Limits -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Usage Limit</label>
                            <input
                                type="number"
                                v-model="form.usage_limit"
                                min="1"
                                max="100000"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Unlimited"
                            >
                            <p class="mt-1 text-sm text-gray-500">Total uses allowed</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Daily Limit</label>
                            <input
                                type="number"
                                v-model="form.daily_usage_limit"
                                min="1"
                                max="10000"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Unlimited"
                            >
                            <p class="mt-1 text-sm text-gray-500">Uses per day</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Monthly Limit</label>
                            <input
                                type="number"
                                v-model="form.monthly_usage_limit"
                                min="1"
                                max="100000"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Unlimited"
                            >
                            <p class="mt-1 text-sm text-gray-500">Uses per month</p>
                        </div>
                    </div>

                    <!-- Expiry Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Expiry Date (Optional)</label>
                        <input
                            type="date"
                            v-model="form.valid_until"
                            :min="new Date().toISOString().split('T')[0]"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        >
                        <p class="mt-1 text-sm text-gray-500">QR code will expire on this date</p>
                    </div>

                    <!-- Geo Restrictions -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Location Restrictions</h3>

                        <div class="space-y-4">
                            <div class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.geo_restriction_enabled"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                >
                                <label class="ml-2 block text-sm text-gray-700">
                                    Enable geographic restrictions
                                </label>
                            </div>

                            <div v-if="form.geo_restriction_enabled" class="ml-6 space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Maximum Distance (km)</label>
                                    <input
                                        type="number"
                                        v-model="form.max_distance_km"
                                        min="0.1"
                                        max="100"
                                        step="0.1"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    >
                                    <p class="mt-1 text-sm text-gray-500">Maximum distance from location to use QR code</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Restrictions -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Customer Restrictions</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Customer Type</label>
                                <select
                                    v-model="form.customer_restriction"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="all">All Customers</option>
                                    <option value="new_customers">New Customers Only</option>
                                    <option value="returning_customers">Returning Customers Only</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Minimum Age</label>
                                <input
                                    type="number"
                                    v-model="form.min_customer_age"
                                    min="18"
                                    max="100"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="No restriction"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Security Level -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Security Settings</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Security Level</label>
                            <select
                                v-model="form.security_level"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="basic">Basic - Standard tracking</option>
                                <option value="standard">Standard - Enhanced fraud detection</option>
                                <option value="high">High - Maximum security</option>
                            </select>
                            <p class="mt-1 text-sm text-gray-500">Higher levels provide better fraud protection but may affect user experience</p>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="border-t pt-6 flex justify-end space-x-4">
                        <button
                            type="button"
                            @click="cancel"
                            class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                        >
                            <span v-if="form.processing">Generating...</span>
                            <span v-else>Generate QR Code</span>
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</template>