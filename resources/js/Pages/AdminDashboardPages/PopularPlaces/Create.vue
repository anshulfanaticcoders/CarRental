<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">Create New Popular Place</span>
            </div>

            <div class="rounded-md border p-5 bg-[#153B4F0D]">
                <form @submit.prevent="submitForm" class="space-y-4">
                    <div>
                        <InputLabel for="location" value="Search Location *" />
                        <div class="relative">
                            <Input
                                v-model="mapform.location"
                                @input="handleSearchInput"
                                @blur="handleBlur"
                                placeholder="Search for a location"
                            />
                            <div
                                v-if="searchResults.length"
                                class="absolute z-10 bg-white border rounded mt-1 max-h-60 overflow-y-auto"
                            >
                                <div
                                    v-for="result in searchResults"
                                    :key="result.properties.id"
                                    @click="selectLocation(result)"
                                    class="p-2 hover:bg-gray-100 cursor-pointer"
                                >
                                    {{ result.properties.label }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="place_name" value="Place Name *" />
                            <Input
                                v-model="form.place_name"
                                required
                                :class="{ 'border-yellow-400 bg-yellow-50': isUnknownLocation }"
                            />
                        </div>
                        <div>
                            <InputLabel for="city" value="City *" />
                            <Input
                                v-model="form.city"
                                required
                                :class="{ 'border-yellow-400 bg-yellow-50': isUnknownLocation }"
                            />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="state" value="State *" />
                            <Input
                                v-model="form.state"
                                required
                                :class="{ 'border-yellow-400 bg-yellow-50': isUnknownLocation }"
                            />
                        </div>
                        <div>
                            <InputLabel for="country" value="Country *" />
                            <Input
                                v-model="form.country"
                                required
                                :class="{ 'border-yellow-400 bg-yellow-50': isUnknownLocation }"
                            />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="latitude" value="Latitude" />
                            <Input v-model="form.latitude" type="text" readonly />
                        </div>
                        <div>
                            <InputLabel for="longitude" value="Longitude" />
                            <Input v-model="form.longitude" type="text" readonly />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="image" value="Place Image *" />
                        <Input type="file" @change="handleFileUpload" accept="image/*" />
                        <p v-if="imageError" class="text-red-500 text-sm">{{ imageError }}</p>
                    </div>

                    <div class="relative">
                        <div id="map" class="h-[400px] w-full rounded-lg"></div>
                        <div
                            class="absolute top-2 right-2 bg-white p-2 rounded shadow-md text-sm text-gray-600 z-[1000]"
                        >
                            Drag the marker to set the location
                        </div>
                        <Button
                            @click="locateMe"
                            class="absolute bottom-2 right-2 bg-blue-500 text-white p-2 rounded-full flex items-center gap-1 z-[1000]"
                            :disabled="isLocating"
                        >
                            <svg
                                class="h-5 w-5"
                                :class="{ 'text-gray-400': isLocating }"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1 12-2.5 0 1.38 1.12 2.5 2.5 2.5z"
                                />
                            </svg>
                            <span>{{ isLocating ? 'Locating...' : 'Locate Me' }}</span>
                        </Button>
                    </div>

                    <div
                        v-if="isUnknownLocation && !showManualDialog"
                        class="p-3 bg-yellow-100 border border-yellow-400 text-yellow-700 rounded-lg flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.908 10.498c.762 1.356-.189 2.903-1.743 2.903H4.092c-1.554 0-2.505-1.547-1.743-2.903L8.257 3.099zM11 14a1 1 0 11-2 0 1 1 0 012 0zm-1-2a1 1 0 01-1-1V7a1 1 0 112 0v4a1 1 0 01-1 1z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <span>Please verify or enter address details manually.</span>
                        <Button
                            @click="showManualDialog = true"
                            class="ml-2 px-2 py-1 bg-yellow-500 text-white rounded text-sm"
                        >
                            Enter Address
                        </Button>
                    </div>

                    <!-- Manual Address Dialog -->
                    <div
                        v-if="showManualDialog"
                        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 !mt-0"
                    >
                        <div class="bg-white rounded-lg p-6 w-full max-w-md">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-medium">Enter Place Details</h3>
                                <button
                                    @click="showManualDialog = false"
                                    class="text-gray-500 hover:text-gray-700"
                                >
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M18 6L6 18M6 6l12 12"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                        />
                                    </svg>
                                </button>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <InputLabel for="temp_place_name" value="Place Name *" />
                                    <Input
                                        v-model="temp_place_name"
                                        required
                                        placeholder="Place name"
                                    />
                                    <p
                                        v-if="placeNameError"
                                        class="text-red-500 text-sm mt-1"
                                    >
                                        {{ placeNameError }}
                                    </p>
                                </div>
                                <div>
                                    <InputLabel for="temp_city" value="City *" />
                                    <Input v-model="temp_city" required placeholder="City" />
                                </div>
                                <div>
                                    <InputLabel for="temp_state" value="State *" />
                                    <Input
                                        v-model="temp_state"
                                        required
                                        placeholder="State"
                                    />
                                </div>
                                <div>
                                    <InputLabel for="temp_country" value="Country *" />
                                    <Input
                                        v-model="temp_country"
                                        required
                                        placeholder="Country"
                                    />
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end space-x-3">
                                <Button
                                    @click="saveManualAddress"
                                    class="bg-blue-500 text-white"
                                >
                                    Save
                                </Button>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                    <Button type="submit">Create Place</Button>
                        <Link
                            :href="route('popular-places.index')"
                            class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-300"
                        >
                            Cancel
                        </Link>
                      
                    </div>
                </form>
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import axios from 'axios';
import InputLabel from '@/Components/InputLabel.vue';
import Input from '@/Components/ui/input/Input.vue';
import Button from '@/Components/ui/button/Button.vue';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { useToast } from 'vue-toastification';
const toast = useToast();

const form = ref({
    place_name: '',
    city: '',
    state: '',
    country: '',
    latitude: null,
    longitude: null,
    image: null,
});

const mapform = ref({
    location: '',
});

const searchResults = ref([]);
const isUnknownLocation = ref(false);
const showManualDialog = ref(false);
const isLocating = ref(false);
const imageError = ref('');
const placeNameError = ref('');

const temp_place_name = ref('');
const temp_city = ref('');
const temp_state = ref('');
const temp_country = ref('');

let map = null;
let marker = null;

onMounted(() => {
    map = L.map('map', {
        scrollWheelZoom: true,
        zoomControl: true,
    }).setView([20, 78], 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution:
            '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 20,
    }).addTo(map);

    map.on('click', async (e) => {
        const { lat, lng } = e.latlng;
        await reverseGeocode(lat, lng);
    });
});

onUnmounted(() => {
    if (map) {
        map.remove();
        map = null;
    }
});

const handleSearchInput = async () => {
    if (mapform.value.location.length < 3) {
        searchResults.value = [];
        return;
    }
    try {
        const response = await axios.get(
            `/api/geocoding/autocomplete?text=${encodeURIComponent(mapform.value.location)}`
        );
        searchResults.value = response.data.features || [];
    } catch (error) {
        console.error('Error fetching locations:', error);
        searchResults.value = [];
    }
};

const handleBlur = () => {
    setTimeout(() => {
        searchResults.value = [];
    }, 200);
};

const selectLocation = async (result) => {
    const [lng, lat] = result.geometry.coordinates;
    mapform.value.location = result.properties?.label || '';
    temp_place_name.value = result.properties?.name || result.properties?.label || '';
    temp_city.value = result.properties?.city || result.properties?.municipality || '';
    temp_state.value = result.properties?.state || result.properties?.region || '';
    temp_country.value = result.properties?.country || '';
    form.value.latitude = lat.toFixed(6);
    form.value.longitude = lng.toFixed(6);
    isUnknownLocation.value = true;
    showManualDialog.value = true;
    searchResults.value = [];
    updateMarker(lat, lng);
};

const reverseGeocode = async (lat, lng) => {
    try {
        const response = await axios.get(`/api/geocoding/reverse?lat=${lat}&lon=${lng}`);
        const feature = response.data.features?.[0];
        const props = feature?.properties || {};

        temp_place_name.value = props.name || props.label || '';
        temp_city.value = props.city || props.municipality || '';
        temp_state.value = props.state || props.region || '';
        temp_country.value = props.country || '';
        form.value.latitude = lat.toFixed(6);
        form.value.longitude = lng.toFixed(6);
        isUnknownLocation.value = true;
        showManualDialog.value = true;
        updateMarker(lat, lng);
    } catch (error) {
        console.error('Reverse geocoding error:', error);
        temp_place_name.value = '';
        temp_city.value = '';
        temp_state.value = '';
        temp_country.value = '';
        form.value.latitude = lat.toFixed(6);
        form.value.longitude = lng.toFixed(6);
        isUnknownLocation.value = true;
        showManualDialog.value = true;
        updateMarker(lat, lng);
    }
};

const updateMarker = (lat, lng) => {
    const latLng = [lat, lng];
    map.setView(latLng, 13);

    if (marker) {
        marker.setLatLng(latLng);
    } else {
        marker = L.marker(latLng, { draggable: true }).addTo(map);
        marker.on('dragend', async (e) => {
            const { lat, lng } = e.target.getLatLng();
            await reverseGeocode(lat, lng);
        });
    }
};

const saveManualAddress = () => {
    placeNameError.value = '';
    if (!temp_place_name.value.trim()) {
        placeNameError.value = 'Place name is required';
        return;
    }

    form.value.place_name = temp_place_name.value;
    form.value.city = temp_city.value;
    form.value.state = temp_state.value;
    form.value.country = temp_country.value;
    isUnknownLocation.value = false;
    showManualDialog.value = false;
    mapform.value.location = `${temp_place_name.value}, ${temp_city.value}, ${temp_state.value}, ${temp_country.value}`;
};

const locateMe = () => {
    if (!navigator.geolocation) {
        toast.error('Geolocation is not supported by your browser');
        return;
    }

    isLocating.value = true;

    navigator.geolocation.getCurrentPosition(
        async (position) => {
            const { latitude, longitude } = position.coords;
            await reverseGeocode(latitude, longitude);
            isLocating.value = false;
        },
        (error) => {
            isLocating.value = false;
            let message = 'Unknown error';
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    message = 'Location permission denied';
                    break;
                case error.POSITION_UNAVAILABLE:
                    message = 'Location information unavailable';
                    break;
                case error.TIMEOUT:
                    message = 'Location request timed out';
                    break;
            }
            toast.error(`Error getting location: ${message}`);
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
};

const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.value.image = file;
        imageError.value = '';
    }
};

const submitForm = () => {
    if (!form.value.image) {
        imageError.value = 'Please upload an image.';
        toast.error('Please upload an image.');
        return;
    }

    if (!form.value.place_name || !form.value.city || !form.value.state || !form.value.country) {
        toast.error('Please fill in all required address fields.');
        return;
    }

    const formData = new FormData();
    Object.keys(form.value).forEach((key) => {
        if (form.value[key] !== null && form.value[key] !== undefined) {
            formData.append(key, form.value[key]);
        }
    });

    router.post('/popular-places', formData, {
        forceFormData: true,
        onSuccess: () => {
            toast.success('Popular place created successfully!');
            form.value = {
                place_name: '',
                city: '',
                state: '',
                country: '',
                latitude: null,
                longitude: null,
                image: null,
            };
            mapform.value.location = '';
            temp_place_name.value = '';
            temp_city.value = '';
            temp_state.value = '';
            temp_country.value = '';
            if (marker) {
                map.removeLayer(marker);
                marker = null;
            }
            map.setView([20, 78], 5);
        },
        onError: (errors) => {
            toast.error('Error creating popular place. Please check your inputs.');
        },
    });
};
</script>

<style scoped>
#map {
    cursor: pointer;
    z-index: 0; /* Ensure map is behind other interactive elements */
}

.fixed {
    position: fixed;
    inset: 0; /* Full-screen coverage for the modal backdrop */
}

.inset-0 {
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
}

/* Ensure dialog is above map and other content */
.dialog-container {
    z-index: 50; /* Higher than map and form */
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Style the dialog content */
.dialog-content {
    background: white;
    border-radius: 0.5rem;
    padding: 1.5rem;
    width: 100%;
    max-width: 28rem; /* Matches max-w-md in Tailwind */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

/* Ensure map hint and Locate Me button are above the map */
.map-hint,
.locate-button {
    z-index: 1000; /* Higher than map */
}

/* Adjust form field styling for unknown location */
.input-unknown {
    @apply border-yellow-400 bg-yellow-50;
    transition: all 0.3s ease;
}

/* Ensure form fields and map don't overlap */
.form-container {
    position: relative;
    z-index: 10; /* Above map but below dialog */
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .grid-cols-2 {
        grid-template-columns: 1fr; /* Stack fields on small screens */
    }
    .dialog-content {
        max-width: 90%; /* Adjust dialog width on mobile */
    }
}
</style>