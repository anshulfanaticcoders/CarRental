<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">Popular Places Management</span>
                <Dialog>
                    <DialogTrigger as-child>
                        <Button>Create New Place</Button>
                    </DialogTrigger>
                    <DialogContent class="max-w-4xl">
                        <DialogHeader>
                            <DialogTitle>Create New Popular Place</DialogTitle>
                        </DialogHeader>
                        <form @submit.prevent="submitForm" class="space-y-4">
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
                            
                            <div>
                                <InputLabel for="location" value="Search Location *" />
                                <div class="relative">
                                    <Input 
                                        v-model="mapform.location" 
                                        @input="handleSearchInput"
                                        placeholder="Search for a location"
                                    />
                                    <div v-if="searchResults.length" class="absolute z-10 bg-white border rounded mt-1 max-h-60 overflow-y-auto">
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
                                    <InputLabel for="latitude" value="Latitude" />
                                    <Input 
                                        v-model="form.latitude" 
                                        type="text" 
                                        readonly 
                                        placeholder="Latitude will be auto-populated"
                                    />
                                </div>
                                <div>
                                    <InputLabel for="longitude" value="Longitude" />
                                    <Input 
                                        v-model="form.longitude" 
                                        type="text" 
                                        readonly 
                                        placeholder="Longitude will be auto-populated"
                                    />
                                </div>
                            </div>

                            <div>
                                <InputLabel for="image" value="Place Image" />
                                <Input 
                                    type="file" 
                                    @change="handleFileUpload"
                                    accept="image/*"
                                />
                            </div>

                            <div id="map" class="h-[400px] w-full"></div>

                            <DialogFooter>
                                <Button type="submit">Create Place</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- Places Table -->
            <div class="rounded-md border p-5 h-[80vh] mt-[1rem] bg-[#153B4F0D]">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Place Name</TableHead>
                            <TableHead>City</TableHead>
                            <TableHead>State</TableHead>
                            <TableHead>Country</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="place in places.data" :key="place.id">
                            <TableCell>{{ place.place_name }}</TableCell>
                            <TableCell>{{ place.city }}</TableCell>
                            <TableCell>{{ place.state }}</TableCell>
                            <TableCell>{{ place.country }}</TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button size="sm" variant="outline" @click="openEditDialog(place)">
                                        Edit
                                    </Button>
                                    <Button size="sm" variant="destructive" @click="deletePlace(place.id)">
                                        Delete
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
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

// Import UI components
import Table from "@/Components/ui/table/Table.vue";
import TableHeader from "@/Components/ui/table/TableHeader.vue";
import TableRow from "@/Components/ui/table/TableRow.vue";
import TableHead from "@/Components/ui/table/TableHead.vue";
import TableBody from "@/Components/ui/table/TableBody.vue";
import TableCell from "@/Components/ui/table/TableCell.vue";
import Button from "@/Components/ui/button/Button.vue";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogTrigger,
} from "@/Components/ui/dialog";
import Input from "@/Components/ui/input/Input.vue";
import InputLabel from "@/Components/InputLabel.vue";
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";

// Props
const props = defineProps({
    places: Array,
});

// State variables
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
const isEditDialogOpen = ref(false);
const editForm = ref({});

// Leaflet map variables
let map = null;


// Clean up map on unmount
onUnmounted(() => {
    if (map) {
        map.remove();
    }
});



// Update marker and form with selected coordinates
const updateMarkerAndForm = (lat, lng) => {
    form.value.latitude = lat.toFixed(6);
    form.value.longitude = lng.toFixed(6);
    mapform.value.latitude = lat;
    mapform.value.longitude = lng;

    // Update or add marker
    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        marker = L.marker([lat, lng]).addTo(map);
    }

    // Center map on marker
    map.setView([lat, lng], 13);
};

// Handle location search input
const handleSearchInput = async () => {
    if (mapform.value.location.length < 3) {
        searchResults.value = [];
        return;
    }

    try {
        const response = await axios.get(
            `/api/geocoding/autocomplete?text=${encodeURIComponent(mapform.value.location)}`
        );
        searchResults.value = response.data.features;
    } catch (error) {
        console.error('Error fetching locations:', error);
    }
};

// Select location from search results
const selectLocation = (result) => {
    const [lng, lat] = result.geometry.coordinates;
    
    mapform.value.location = result.properties?.label || 'Unknown Location';
    mapform.value.latitude = lat;
    mapform.value.longitude = lng;

    // Autofill form fields from geocoding result
    form.value.place_name = result.properties?.name || result.properties?.label || '';
    form.value.city = result.properties?.city || result.properties?.municipality || '';
    form.value.state = result.properties?.state || result.properties?.region || '';
    form.value.country = result.properties?.country || '';
    
    form.value.latitude = lat.toFixed(6);
    form.value.longitude = lng.toFixed(6);

    searchResults.value = [];

    // Update map
    updateMarkerAndForm(lat, lng);
};

// Handle file upload
const handleFileUpload = (event) => {
    form.value.image = event.target.files[0];
};

// Submit form
const submitForm = () => {
    const formData = new FormData();
    Object.keys(form.value).forEach(key => {
        if (form.value[key] !== null && form.value[key] !== undefined) {
            formData.append(key, form.value[key]);
        }
    });

    router.post("/popular-places", formData, {
        forceFormData: true,
        onSuccess: () => {
            // Reset form
            form.value = {
                place_name: '',
                city: '',
                state: '',
                country: '',
                latitude: null,
                longitude: null,
                image: null
            };
            mapform.value.location = '';
            
            // Remove marker if exists
            if (marker) {
                map.removeLayer(marker);
                marker = null;
            }
        },
    });
};

// Edit dialog handlers
const openEditDialog = (place) => {
    editForm.value = { ...place };
    isEditDialogOpen.value = true;
};

const deletePlace = (id) => {
    router.delete(`/popular-places/${id}`);
};
</script>