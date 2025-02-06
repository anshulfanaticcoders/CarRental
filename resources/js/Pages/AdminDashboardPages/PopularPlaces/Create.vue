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
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="place_name" value="Place Name *" />
                            <Input v-model="form.place_name" required />
                        </div>
                        <div>
                            <InputLabel for="city" value="City *" />
                            <Input v-model="form.city" required />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="state" value="State *" />
                            <Input v-model="form.state" required />
                        </div>
                        <div>
                            <InputLabel for="country" value="Country *" />
                            <Input v-model="form.country" required />
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

                    <div id="map" class="h-[400px] w-full"></div>

                    <Button type="submit">Create Place</Button>
                </form>
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from "@inertiajs/vue3";
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import axios from 'axios';
import InputLabel from "@/Components/InputLabel.vue";
import Input from "@/Components/ui/input/Input.vue";
import Button from "@/Components/ui/button/Button.vue";
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import { useToast } from 'vue-toastification';
const toast = useToast();

const form = ref({
    place_name: '',
    city: '',
    state: '',
    country: '',
    latitude: null,
    longitude: null,
    image: null
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


const imageError = ref("");
const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.value.image = file;
        imageError.value = "";
    }
};

const submitForm = () => {
    if (!form.value.image) {
        imageError.value = "Please upload an image.";
        toast.error('Please upload an image.', {
            position: 'top-right',
            timeout: 3000,
            closeOnClick: true,
            pauseOnHover: true,
            draggable: true,
        });
        return;
    }

    const formData = new FormData();
    Object.keys(form.value).forEach(key => {
        if (form.value[key] !== null && form.value[key] !== undefined) {
            formData.append(key, form.value[key]);
        }
    });

    router.post("/popular-places", formData, {
        forceFormData: true,
        onSuccess: () => {
            toast.success('Popular place created successfully!', {
                position: 'top-right',
                timeout: 3000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
            form.value = { place_name: '', city: '', state: '', country: '', latitude: null, longitude: null, image: null };
            mapform.value.location = '';
        },
        onError: (errors) => {
            toast.error('Error creating popular place. Please check your inputs.', {
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