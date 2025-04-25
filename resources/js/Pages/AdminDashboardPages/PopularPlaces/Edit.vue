<!-- resources/js/Pages/AdminDashboardPages/PopularPlaces/Edit.vue -->
<template>
    <AdminDashboardLayout>
        <!-- Loader -->
        <div v-if="isLoading"
            class="fixed z-50 h-full w-full top-0 left-0 bg-[#0000009e] flex items-center justify-center">
            <div class="flex flex-col items-center">
                <img :src="loader" alt="Loading..." class="w-[150px]" />
                <p class="text-white text-2xl">Updating...</p>
            </div>
        </div>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">Edit Popular Place</span>
                <Link :href="route('popular-places.index')"
                    class="px-4 py-2 bg-gray-800 text-white rounded hover:bg-gray-600">
                Back to List
                </Link>
            </div>

            <div class="rounded-md border p-5 mt-[1rem] bg-white form-container">
                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Search Location -->
                    <div>
                        <InputLabel for="location" value="Search Location" />
                        <div class="relative">
                            <Input v-model="mapform.location" @input="handleSearchInput" @blur="handleBlur"
                                placeholder="Search for a location" />
                            <div v-if="searchResults.length"
                                class="absolute z-10 bg-white border rounded mt-1 max-h-60 overflow-y-auto">
                                <div v-for="result in searchResults" :key="result.properties.id"
                                    @click="selectLocation(result)" class="p-2 hover:bg-gray-100 cursor-pointer">
                                    {{ result.properties.label }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Place Name -->
                    <div>
                        <InputLabel for="place_name" value="Place Name" />
                        <Input id="place_name" type="text" v-model="form.place_name" class="mt-1 block w-full" required
                            :class="{ 'input-unknown': isUnknownLocation }" />
                    </div>

                    <!-- City, State, Country -->
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <InputLabel for="city" value="City" />
                            <Input id="city" type="text" v-model="form.city" class="mt-1 block w-full" required
                                :class="{ 'input-unknown': isUnknownLocation }" />
                        </div>
                        <div>
                            <InputLabel for="state" value="State" />
                            <Input id="state" type="text" v-model="form.state" class="mt-1 block w-full" required
                                :class="{ 'input-unknown': isUnknownLocation }" />
                        </div>
                        <div>
                            <InputLabel for="country" value="Country" />
                            <Input id="country" type="text" v-model="form.country" class="mt-1 block w-full" required
                                :class="{ 'input-unknown': isUnknownLocation }" />
                        </div>
                    </div>

                    <!-- Latitude & Longitude -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="latitude" value="Latitude" />
                            <Input id="latitude" type="number" step="any" v-model="form.latitude"
                                class="mt-1 block w-full" required readonly />
                        </div>
                        <div>
                            <InputLabel for="longitude" value="Longitude" />
                            <Input id="longitude" type="number" step="any" v-model="form.longitude"
                                class="mt-1 block w-full" required readonly />
                        </div>
                    </div>

                    <!-- Image -->
                    <div>
                        <InputLabel for="image" value="Place Image" />
                        <div v-if="place.image" class="mt-2 mb-4">
                            <img :src="`${place.image}`" class="w-32 h-32 object-cover rounded"
                                :alt="place.place_name" />
                        </div>
                        <Input id="image" type="file" @input="form.image = $event.target.files[0]"
                            class="mt-1 block w-full" accept="image/*" />
                    </div>

                    <!-- Map and Controls -->
                    <div class="relative hidden">
                        <div id="map" class="h-[400px] w-full rounded-lg"></div>
                        <div
                            class="map-hint absolute top-2 right-2 bg-white p-2 rounded shadow-md text-sm text-gray-600">
                            Drag the marker to set the location
                        </div>
                        <Button @click="locateMe"
                            class="locate-button absolute bottom-2 right-2 bg-blue-500 text-white p-2 rounded-full flex items-center gap-1"
                            :disabled="isLocating">
                            <svg class="h-5 w-5" :class="{ 'text-gray-400': isLocating }" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5 0 1.38 1.12 2.5 2.5 2.5z" />
                            </svg>
                            <span>{{ isLocating ? 'Locating...' : 'Locate Me' }}</span>
                        </Button>
                    </div>

                    <!-- Warning Message -->
                    <div v-if="isUnknownLocation && !showManualDialog"
                        class="p-3 bg-yellow-100 border border-yellow-400 text-yellow-700 rounded-lg flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.908 10.498c.762 1.356-.189 2.903-1.743 2.903H4.092c-1.554 0-2.505-1.547-1.743-2.903L8.257 3.099zM11 14a1 1 0 11-2 0 1 1 0 012 0zm-1-2a1 1 0 01-1-1V7a1 1 0 112 0v4a1 1 0 01-1 1z"
                                clip-rule="evenodd" />
                        </svg>
                        <span>Please verify or enter address details manually.</span>
                        <Button @click="showManualDialog = true"
                            class="ml-2 px-2 py-1 bg-yellow-500 text-white rounded text-sm">
                            Enter Address
                        </Button>
                    </div>

                    <!-- Manual Address Dialog -->
                    <div v-if="showManualDialog"
                        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                        <div class="dialog-container">
                            <div class="dialog-content">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-medium">Enter Place Details</h3>
                                    <button @click="showManualDialog = false" class="text-gray-500 hover:text-gray-700">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="space-y-4">
                                    <div>
                                        <InputLabel for="place_name" value="Place Name" />
                                        <Input v-model="temp_place_name" placeholder="Place name" />
                                        <p v-if="placeNameError" class="text-red-500 text-sm mt-1">
                                            {{ placeNameError }}
                                        </p>
                                    </div>
                                    <div>
                                        <InputLabel for="city" value="City" />
                                        <Input v-model="temp_city" placeholder="City" />
                                    </div>
                                    <div>
                                        <InputLabel for="state" value="State" />
                                        <Input v-model="temp_state" placeholder="State" />
                                    </div>
                                    <div>
                                        <InputLabel for="country" value="Country" />
                                        <Input v-model="temp_country" placeholder="Country" />
                                    </div>
                                </div>
                                <div class="mt-6 flex justify-end space-x-3">
                                    <Button @click="saveManualAddress" class="bg-blue-500 text-white">
                                        Save
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2">
                        <Link :href="route('popular-places.index')"
                            class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                        Cancel
                        </Link>
                        <Button type="submit" class="bg-primary">
                            Update Place
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { onMounted, onUnmounted, ref, nextTick, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { useToast } from 'vue-toastification';
import loader from '../../../../assets/loader.gif';
import L from 'leaflet';
import axios from 'axios';

const toast = useToast();
const isLoading = ref(false);
const isUnknownLocation = ref(false);
const showManualDialog = ref(false);
const isLocating = ref(false);
const placeNameError = ref('');

const props = defineProps({
    place: {
        type: Object,
        required: true,
    },
});

const form = ref({
    place_name: props.place.place_name,
    city: props.place.city,
    state: props.place.state,
    country: props.place.country,
    latitude: props.place.latitude,
    longitude: props.place.longitude,
    image: null,
    _method: 'PUT',
});
const mapform = ref({
    location: props.place.place_name || '',
});
const searchResults = ref([]);
const temp_place_name = ref(props.place.place_name || '');
const temp_city = ref(props.place.city || '');
const temp_state = ref(props.place.state || '');
const temp_country = ref(props.place.country || '');

let map = null;
let marker = null;

onMounted(() => {
    // Initialize map
    map = L.map('map', {
        scrollWheelZoom: true,
        zoomControl: true,
    }).setView([props.place.latitude || 20, props.place.longitude || 78], 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution:
            '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 20,
    }).addTo(map);

    // Add click event
    map.on('click', async (e) => {
        const { lat, lng } = e.latlng;
        await reverseGeocode(lat, lng);
    });

    // Initialize marker with existing location
    if (props.place.latitude && props.place.longitude) {
        updateMarker(props.place.latitude, props.place.longitude);
    }

    // Watch for isLoading to invalidate size after loader disappears
    watch(isLoading, (newVal) => {
        if (!newVal) {
            nextTick(() => {
                map.invalidateSize();
            });
        }
    });

    // Initial invalidate to handle any layout shifts
    nextTick(() => {
        map.invalidateSize();
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

const submit = () => {
    isLoading.value = true;
    if (!form.value.place_name || !form.value.city || !form.value.state || !form.value.country) {
        toast.error('Please fill in all required address fields.');
        isLoading.value = false;
        return;
    }

    router.post(route('popular-places.update', props.place.id), form.value, {
        forceFormData: true,
        onSuccess: () => {
            toast.success('Popular place updated successfully!', {
                position: 'top-right',
                timeout: 3000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
        },
        onFinish: () => {
            isLoading.value = false;
        },
        onError: (errors) => {
            toast.error('Error updating popular place. Please check your inputs.', {
                position: 'top-right',
                timeout: 3000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
        },
    });
};
</script>

<style scoped>
#map {
    cursor: pointer;
    z-index: 0;
    width: 100%;
    height: 400px;
}

.fixed {
    position: fixed;
    inset: 0;
}

.dialog-container {
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 500px;
}

.dialog-content {
    background: white;
    border-radius: 0.5rem;
    padding: 1.5rem;
    width: 100%;
    max-width: 28rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.map-hint,
.locate-button {
    z-index: 1000;
}

.input-unknown {
    @apply border-yellow-400 bg-yellow-50;
    transition: all 0.3s ease;
}

.form-container {
    position: relative;
    z-index: 10;
}

@media (max-width: 640px) {

    .grid-cols-3,
    .grid-cols-2 {
        grid-template-columns: 1fr;
    }

    .dialog-content {
        max-width: 90%;
    }
}
</style>