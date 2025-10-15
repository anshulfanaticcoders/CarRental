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
    new_location_data: null,
});

// State for new location creation
const showNewLocationForm = ref(false);
const googleLocationSelected = ref(false);
const showRemainingFields = ref(false);
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
    // Clear previous errors
    form.clearErrors();

    // Validate that we have new location data
    if (!form.new_location_data) {
        form.setError('new_location_data', 'Please create a new location for the QR code');
        return;
    }

    form.post(`/${props.locale}/business/qr-codes/store/${props.dashboardToken}`, {
        onSuccess: () => {
            router.visit(`/${props.locale}/business/dashboard/${props.dashboardToken}`);
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors);
            // Scroll to first error
            const firstErrorField = Object.keys(errors)[0];
            const errorElement = document.querySelector(`[name="${firstErrorField}"]`);
            if (errorElement) {
                errorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                errorElement.focus();
            }
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
    googleLocationSelected.value = true;

    // Fill form with Google Places data
    newLocationForm.address_line_1 = location.address;
    newLocationForm.city = location.city;
    newLocationForm.state = location.state || '';
    newLocationForm.country = location.country;
    newLocationForm.latitude = location.latitude;
    newLocationForm.longitude = location.longitude;

    // Generate a location code from the city name
    if (location.city) {
        newLocationForm.location_code = location.city.toUpperCase().replace(/\s+/g, '_');
    }

    // Check if required fields are still empty
    const requiredFields = ['name', 'address_line_1', 'city', 'country'];
    const missingFields = requiredFields.filter(field => !newLocationForm[field]);

    if (missingFields.length > 0) {
        showRemainingFields.value = true;
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

        // Store the new location data in the form for QR code creation
        const newLocation = response.data.location;
        form.new_location_data = newLocation;

        // Add the new location to the locations list for display
        props.locations.push(newLocation);

        // Reset form and hide it
        newLocationForm.reset();
        selectedNewLocation.value = null;
        showNewLocationForm.value = false;

        // Show success message
        alert('Location created successfully! QR code will be generated for this location.');

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
    googleLocationSelected.value = false;
    showRemainingFields.value = false;
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

            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <!-- Error Summary -->
                <div v-if="Object.keys(form.errors).length > 0" class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Please fix the following errors:</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc list-inside space-y-1">
                                    <li v-for="(error, field) in form.errors" :key="field">{{ error }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submitForm" class="p-8 space-y-6">
                    <!-- QR Code Details -->
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 mb-6">QR Code Details</h2>

                        <!-- Existing Locations Reference -->
                        <div v-if="locations.length > 0" class="mb-6">
                            <h3 class="text-sm font-medium text-gray-700 mb-3">Existing Locations (Reference Only)</h3>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div v-for="location in locations" :key="location.id" class="bg-white p-3 rounded border border-gray-200">
                                        <div class="flex items-start">
                                            <svg class="w-4 h-4 text-gray-400 mt-0.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-gray-900">{{ location.name }}</p>
                                                <p class="text-xs text-gray-500">{{ location.address_line_1 }}</p>
                                                <p class="text-xs text-gray-400">{{ location.city }}, {{ location.country }}</p>
                                            </div>
                                            <span class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded">QR Code Generated</span>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-3 text-center">
                                    ⚠️ Note: Each location can only have one QR code. Please create a new location below.
                                </p>
                            </div>
                        </div>

                        <!-- Location Selection Status -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                            <div v-if="!form.new_location_data" class="text-center">
                                <svg class="w-12 h-12 text-blue-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <h3 class="text-lg font-medium text-blue-900 mb-2">No Location Selected</h3>
                                <p class="text-sm text-blue-700 mb-4">You must create a new location to generate a QR code.</p>
                                <p class="text-sm text-blue-700">
                                    <strong>Required:</strong> Please create a new location using the form below to generate a QR code.
                                </p>
                            </div>

                            <div v-else class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-green-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Location Ready</p>
                                        <p class="text-xs text-gray-500">{{ form.new_location_data.name }} - {{ form.new_location_data.address_line_1 }}</p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="form.new_location_data = null"
                                    class="text-sm text-red-600 hover:text-red-800"
                                >
                                    Change Location
                                </button>
                            </div>
                        </div>

                        <p v-if="form.errors.new_location_data" class="mt-2 text-sm text-red-600">{{ form.errors.new_location_data }}</p>
                    </div>

                    <!-- New Location Creation (Required) -->
                    <div class="border-t pt-6">
                        <div v-if="!showNewLocationForm" class="text-center">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Create New Location <span class="text-red-500">*</span></h3>
                            <p class="text-sm text-gray-600 mb-6">Each QR code requires a unique location. Click below to add a new location.</p>

                            <button
                                type="button"
                                @click="showNewLocationForm = true"
                                class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add New Location
                            </button>
                        </div>

                        <div v-else class="space-y-6">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900">Create New Location <span class="text-red-500">*</span></h3>
                                <button
                                    type="button"
                                    @click="cancelNewLocation"
                                    class="text-sm text-gray-500 hover:text-gray-700"
                                >
                                    Cancel
                                </button>
                            </div>

                            <!-- Step 1: Google Places Search -->
                            <div v-if="!googleLocationSelected" class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                                <div class="text-center">
                                    <svg class="w-8 h-8 text-blue-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <h4 class="text-lg font-medium text-blue-900 mb-2">Step 1: Find Location</h4>
                                    <p class="text-sm text-blue-700 mb-4">Search for your location using Google Maps</p>

                                    <div class="bg-white rounded-lg p-4 border border-blue-300">
                                        <LocationPicker :onLocationSelect="handleLocationSelect" />
                                    </div>
                                </div>
                            </div>

                            <!-- Step 2: Check Required Fields -->
                            <div v-else>
                                <!-- Google Location Selected Confirmation -->
                                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-green-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <div>
                                            <p class="text-sm font-medium text-green-900">Location Found</p>
                                            <p class="text-xs text-green-700">{{ selectedNewLocation.fullAddress }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Missing Fields Alert -->
                                <div v-if="showRemainingFields" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                                    <div class="flex">
                                        <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z" />
                                        </svg>
                                        <div>
                                            <p class="text-sm font-medium text-yellow-800">Please fill the remaining fields</p>
                                            <p class="text-xs text-yellow-700 mt-1">Some required information is missing to complete the location.</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Location Name (Always Required) -->
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Location Name <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        v-model="newLocationForm.name"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="E.g., Main Office, Downtown Branch"
                                    >
                                    <p class="mt-1 text-xs text-gray-500">A descriptive name for this location</p>
                                </div>

                                <!-- Remaining Fields (Only show if needed) -->
                                <div v-if="showRemainingFields" class="space-y-4">
                                    <div class="border-t pt-4">
                                        <h4 class="text-sm font-medium text-gray-700 mb-4">Complete Location Details</h4>

                                        <!-- Address Details -->
                                        <div class="space-y-4">
                                            <div v-if="!newLocationForm.address_line_1">
                                                <label class="block text-sm font-medium text-gray-700">Street Address <span class="text-red-500">*</span></label>
                                                <input
                                                    type="text"
                                                    v-model="newLocationForm.address_line_1"
                                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                                    placeholder="123 Main Street"
                                                >
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Address Line 2</label>
                                                <input
                                                    type="text"
                                                    v-model="newLocationForm.address_line_2"
                                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                                    placeholder="Apartment, suite, unit, building, floor, etc."
                                                >
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div v-if="!newLocationForm.city">
                                                    <label class="block text-sm font-medium text-gray-700">City <span class="text-red-500">*</span></label>
                                                    <input
                                                        type="text"
                                                        v-model="newLocationForm.city"
                                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                                        placeholder="City"
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

                                                <div v-if="!newLocationForm.country">
                                                    <label class="block text-sm font-medium text-gray-700">Country <span class="text-red-500">*</span></label>
                                                    <input
                                                        type="text"
                                                        v-model="newLocationForm.country"
                                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                                        placeholder="Country"
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
                                        </div>
                                    </div>
                                </div>

                                <!-- Create Location Button -->
                                <div class="flex justify-end">
                                    <button
                                        type="button"
                                        @click="createNewLocation"
                                        :disabled="!newLocationForm.name || !newLocationForm.address_line_1 || !newLocationForm.city || !newLocationForm.country || isCreatingLocation"
                                        class="px-6 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                    >
                                        <svg v-if="isCreatingLocation" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <svg v-else class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span v-if="isCreatingLocation">Creating Location...</span>
                                        <span v-else>Create Location</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                      <!-- Form Actions -->
                    <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                        <button
                            type="button"
                            @click="cancel"
                            class="px-6 py-3 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing || !form.new_location_data"
                            class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                            </svg>
                            <span v-if="form.processing">Generating QR Code...</span>
                            <span v-else>Generate QR Code</span>
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</template>