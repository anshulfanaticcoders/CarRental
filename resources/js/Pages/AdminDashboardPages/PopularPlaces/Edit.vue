<!-- resources/js/Pages/AdminDashboardPages/PopularPlaces/Edit.vue -->
<template>
    <AdminDashboardLayout>
         <!-- Loader -->
         <div v-if="isLoading" class="fixed z-50 h-full w-full top-0 left-0 bg-[#0000009e] flex items-center justify-center">
            <div class="flex flex-col items-center">
                <img :src="loader" alt="Loading..." class="w-[150px]" />
                <p class="text-white text-2xl">Updating...</p>
            </div>
        </div>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">Edit Popular Place</span>
                <Link 
                    :href="route('popular-places.index')" 
                    class="px-4 py-2 bg-gray-800 text-white rounded hover:bg-gray-600"
                >
                    Back to List
                </Link>
            </div>

            <div class="rounded-md border p-5 mt-[1rem] bg-white">
                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Place Name -->
                    <div>
                        <InputLabel for="place_name" value="Place Name" />
                        <Input v-model="mapform.location" @input="handleSearchInput"
                                placeholder="Search for a location" />
                            <div v-if="searchResults.length"
                                class="absolute z-10 bg-white border rounded mt-1 max-h-60 overflow-y-auto">
                                <div v-for="result in searchResults" :key="result.properties.id"
                                    @click="selectLocation(result)" class="p-2 hover:bg-gray-100 cursor-pointer">
                                    {{ result.properties.label }}
                                </div>
                            </div>
                    </div>

                    <!-- City, State, Country -->
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <InputLabel for="city" value="City" />
                            <Input
                                id="city"
                                type="text"
                                v-model="form.city"
                                class="mt-1 block w-full"
                                required
                            />
                        </div>
                        <div>
                            <InputLabel for="state" value="State" />
                            <Input
                                id="state"
                                type="text"
                                v-model="form.state"
                                class="mt-1 block w-full"
                                required
                            />
                        </div>
                        <div>
                            <InputLabel for="country" value="Country" />
                            <Input
                                id="country"
                                type="text"
                                v-model="form.country"
                                class="mt-1 block w-full"
                                required
                            />
                        </div>
                    </div>

                    <!-- Latitude & Longitude -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="latitude" value="Latitude" />
                            <Input
                                id="latitude"
                                type="number"
                                step="any"
                                v-model="form.latitude"
                                class="mt-1 block w-full"
                                required
                            />
                        </div>
                        <div>
                            <InputLabel for="longitude" value="Longitude" />
                            <Input
                                id="longitude"
                                type="number"
                                step="any"
                                v-model="form.longitude"
                                class="mt-1 block w-full"
                                required
                            />
                        </div>
                    </div>

                    <!-- Image -->
                    <div>
                        <InputLabel for="image" value="Place Image" />
                        <div v-if="place.image" class="mt-2 mb-4">
                            <img 
                                :src="`${place.image}`" 
                                class="w-32 h-32 object-cover rounded"
                                :alt="place.place_name"
                            />
                        </div>
                        <Input
                            id="image"
                            type="file"
                            @input="form.image = $event.target.files[0]"
                            class="mt-1 block w-full"
                            accept="image/*"
                        />
                    </div>
                    <div id="map" class="h-[400px] w-full"></div>
                    <div class="flex justify-end gap-2">
                        <Link
                            :href="route('popular-places.index')"
                            class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
                        >
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
import { onMounted, onUnmounted, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { useToast } from 'vue-toastification';
import loader from '../../../../assets/loader.gif';
import L from 'leaflet';

const toast = useToast();
const isLoading = ref(false);

const props = defineProps({
    place: {
        type: Object,
        required: true
    }
});

const form = ref({
    place_name: props.place.place_name,
    city: props.place.city,
    state: props.place.state,
    country: props.place.country,
    latitude: props.place.latitude,
    longitude: props.place.longitude,
    image: null,
    _method: 'PUT'
});
const mapform = ref({
    location: '',
    latitude: null,
    longitude: null
});

const searchResults = ref([]);
let map = null;
let marker = null;

onMounted(() => {
    map = L.map('map').setView([20, 78], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    map.on('click', (e) => updateMarkerAndForm(e.latlng.lat, e.latlng.lng));
});

onUnmounted(() => {
    if (map) {
        map.remove();
    }
});

const updateMarkerAndForm = (lat, lng) => {
    form.value.latitude = lat.toFixed(6);
    form.value.longitude = lng.toFixed(6);
    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        marker = L.marker([lat, lng]).addTo(map);
    }
    map.setView([lat, lng], 13);
};

const handleSearchInput = async () => {
    if (mapform.value.location.length < 3) {
        searchResults.value = [];
        return;
    }
    try {
        const response = await axios.get(`/api/geocoding/autocomplete?text=${encodeURIComponent(mapform.value.location)}`);
        searchResults.value = response.data.features;
    } catch (error) {
        console.error('Error fetching locations:', error);
    }
};

const selectLocation = (result) => {
    const [lng, lat] = result.geometry.coordinates;
    mapform.value.location = result.properties?.label || 'Unknown Location';
    form.value.place_name = result.properties?.name || result.properties?.label || '';
    form.value.city = result.properties?.city || result.properties?.municipality || '';
    form.value.state = result.properties?.state || result.properties?.region || '';
    form.value.country = result.properties?.country || '';
    form.value.latitude = lat.toFixed(6);
    form.value.longitude = lng.toFixed(6);
    searchResults.value = [];
    updateMarkerAndForm(lat, lng);
};

const submit = () => {
    isLoading.value = true;
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
            isLoading.value = false; // Hide loader after request completes
        },
        onError: (errors) => {
            toast.error('Error updating popular place. Please check your inputs.', {
                position: 'top-right',
                timeout: 3000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
        }
    });
};
</script>


<style>
#map{
    display: none;
}
</style>