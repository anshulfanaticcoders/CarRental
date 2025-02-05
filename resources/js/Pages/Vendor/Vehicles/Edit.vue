<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";

import { computed, onMounted, ref } from "vue";

import axios from "axios";

import TextInput from "@/Components/TextInput.vue";

import L from 'leaflet'
import { useToast } from 'vue-toastification';
import InputLabel from "@/Components/InputLabel.vue";
import { Input } from "@/Components/ui/input";
import Select from "@/Components/ui/select/Select.vue";

const toast = useToast();
const form = useForm({
    category_id: null,
    brand: "",
    model: "",
    color: "",
    mileage: 0,
    transmission: "manual",
    fuel: "petrol",
    seating_capacity: 1,
    number_of_doors: 2,
    luggage_capacity: 0,
    horsepower: 0,
    co2: "",
    location: "",
    latitude: null,
    longitude: null,
    status: "available",
    features: [], // Ensure it's an array
    featured: false,
    security_deposit: 0,
    payment_method: "",
    price_per_day: 0,
    registration_number: "",
    registration_country: "",
    registration_date: "",
    gross_vehicle_mass: 0,
    vehicle_height: 0, // Ensure this is an integer
    dealer_cost: 0,
    phone_number: "",
    images: [],
    radius: 831867.4340914232,
});

const categories = ref([]);
const fetchCategories = async () => {
    try {
        const response = await axios.get("/api/vehicle-categories");
        categories.value = response.data;
    } catch (error) {
        console.error("Error fetching vehicle categories:", error);
    }
};

const submit = () => {
    form.post(route("current-vendor-vehicles.store"), {
        onSuccess: () => {
            toast.success('Vendor registration completed successfully! Wait for confirmation', {
                position: 'top-right',
                timeout: 3000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
        },
        onError: (errors) => {
            toast.error('Something went wrong. Please check your inputs.', {
                position: 'top-right',
                timeout: 3000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
            console.error(errors);
        },
    });
};

const tooltipPosition = computed(() => ({
    left: `${(form.price_per_day / 70) * 100}%`,
}));

const handleFileUpload = (event) => {
    const files = Array.from(event.target.files);
    form.images = files;
};

const features = ref([]);
const fetchFeatures = async () => {
    try {
        const response = await axios.get("/api/vehicle-features");
        features.value = response.data;
    } catch (error) {
        console.error("Error fetching vehicle features:", error);
    }
};

onMounted(() => {
    fetchCategories();
    fetchFeatures();
});

const currentStep = ref(0);
const nextStep = () => {
    let isValid = true;
    switch (currentStep.value) {
        case 1:
            if (!form.category_id || !form.brand || !form.model || !form.color || !form.mileage || !form.horsepower || !form.co2) {
                isValid = false;
                alert('Please fill in all vehicle details');
            }
            break;
        case 2:
            if (!form.registration_number || !form.registration_country || !form.registration_date || !form.gross_vehicle_mass || !form.vehicle_height || !form.dealer_cost || !form.phone_number) {
                isValid = false;
                alert('Please fill in all technical specification details');
            }
            break;
        case 3:
            if (!form.location || !form.latitude || !form.longitude) {
                isValid = false;
                alert('Please select a valid location');
            }
            break;
        case 4:
            if (form.price_per_day <= 0 || !form.security_deposit || !form.payment_method) {
                isValid = false;
                alert('Please fill in all pricing details');
            }
            break;
        case 5:
            if (form.images.length < 5) {
                isValid = false;
                alert('Please upload at least 5 images');
            }
            break;
    }
    if (isValid) {
        if (currentStep.value < 5) {
            currentStep.value++;
            if (currentStep.value === 3) {
                initializeMap();
            }
        }
    }
};

const prevStep = () => {
    if (currentStep.value > 0) {
        currentStep.value--;
    }
};

const selectLocation = (location) => {
    form.location = location.address;
    form.latitude = location.latitude;
    form.longitude = location.longitude;
};

const initializeMap = () => {
    if (map) {
        map.remove();
    }
    map = L.map("map").setView([20.5937, 78.9629], 5);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "&copy; OpenStreetMap contributors",
    }).addTo(map);
};
</script>

<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">Edit Vehicle</span>
                <Link 
                    :href="route('current-vendor-vehicles.index')" 
                    class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
                >
                    Back to List
                </Link>
            </div>

            <div class="rounded-md border p-5 mt-[1rem] bg-white">
                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Category -->
                    <div>
                        <InputLabel for="category_id" value="Category" />
                        <select
                            id="category_id"
                            v-model="form.category_id"
                            class="mt-1 block w-full"
                            required
                        >
                            <option value="">Select Category</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Brand, Model, Color -->
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <InputLabel for="brand" value="Brand" />
                            <Input
                                id="brand"
                                type="text"
                                v-model="form.brand"
                                class="mt-1 block w-full"
                                required
                            />
                        </div>
                        <div>
                            <InputLabel for="model" value="Model" />
                            <Input
                                id="model"
                                type="text"
                                v-model="form.model"
                                class="mt-1 block w-full"
                                required
                            />
                        </div>
                        <div>
                            <InputLabel for="color" value="Color" />
                            <Input
                                id="color"
                                type="text"
                                v-model="form.color"
                                class="mt-1 block w-full"
                                required
                            />
                        </div>
                    </div>

                    <!-- Mileage, Transmission, Fuel Type -->
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <InputLabel for="mileage" value="Mileage" />
                            <Input
                                id="mileage"
                                type="number"
                                v-model="form.mileage"
                                class="mt-1 block w-full"
                                required
                            />
                        </div>
                        <div>
                            <InputLabel for="transmission" value="Transmission" />
                            <select
                                id="transmission"
                                v-model="form.transmission"
                                class="mt-1 block w-full"
                                required
                            >
                                <option value="manual">Manual</option>
                                <option value="automatic">Automatic</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="fuel" value="Fuel Type" />
                            <Select
                                id="fuel"
                                v-model="form.fuel"
                                class="mt-1 block w-full"
                                required
                            >
                                <optio value="petrol">Petrol</optio>
                                <option value="diesel">Diesel</option>
                                <option value="electric">Electric</option>
                            </Select>
                        </div>
                    </div>

                    <!-- Seating Capacity, Number of Doors, Luggage Capacity -->
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <InputLabel for="seating_capacity" value="Seating Capacity" />
                            <Input
                                id="seating_capacity"
                                type="number"
                                v-model="form.seating_capacity"
                                class="mt-1 block w-full"
                                required
                            />
                        </div>
                        <div>
                            <InputLabel for="number_of_doors" value="Number of Doors" />
                            <Input
                                id="number_of_doors"
                                type="number"
                                v-model="form.number_of_doors"
                                class="mt-1 block w-full"
                                required
                            />
                        </div>
                        <div>
                            <InputLabel for="luggage_capacity" value="Luggage Capacity" />
                            <Input
                                id="luggage_capacity"
                                type="number"
                                v-model="form.luggage_capacity"
                                class="mt-1 block w-full"
                                required
                            />
                        </div>
                    </div>

                    <!-- Horsepower, CO2 Emissions -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="horsepower" value="Horsepower" />
                            <Input
                                id="horsepower"
                                type="number"
                                v-model="form.horsepower"
                                class="mt-1 block w-full"
                                required
                            />
                        </div>
                        <div>
                            <InputLabel for="co2" value="CO2 Emissions" />
                            <Input
                                id="co2"
                                type="text"
                                v-model="form.co2"
                                class="mt-1 block w-full"
                                required
                            />
                        </div>
                    </div>

                    <!-- Location -->
                    <div>
                        <InputLabel for="location" value="Location" />
                        <Input
                            id="location"
                            type="text"
                            v-model="form.location"
                            class="mt-1 block w-full"
                            required
                        />
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

                    <!-- Pricing and Deposit -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="price_per_day" value="Price Per Day" />
                            <Input
                                id="price_per_day"
                                type="number"
                                v-model="form.price_per_day"
                                class="mt-1 block w-full"
                                required
                            />
                        </div>
                        <div>
                            <InputLabel for="security_deposit" value="Security Deposit" />
                            <Input
                                id="security_deposit"
                                type="number"
                                v-model="form.security_deposit"
                                class="mt-1 block w-full"
                                required
                            />
                        </div>
                    </div>

                    <!-- Registration Details -->
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <InputLabel for="registration_number" value="Registration Number" />
                            <Input
                                id="registration_number"
                                type="text"
                                v-model="form.registration_number"
                                class="mt-1 block w-full"
                                required
                            />
                        </div>
                        <div>
                            <InputLabel for="registration_country" value="Registration Country" />
                            <Input
                                id="registration_country"
                                type="text"
                                v-model="form.registration_country"
                                class="mt-1 block w-full"
                                required
                            />
                        </div>
                        <div>
                            <InputLabel for="registration_date" value="Registration Date" />
                            <Input
                                id="registration_date"
                                type="date"
                                v-model="form.registration_date"
                                class="mt-1 block w-full"
                                required
                            />
                        </div>
                    </div>

                    <!-- Vehicle Features -->
                    <div>
                        <InputLabel for="features" value="Features" />
                        <select
                            id="features"
                            v-model="form.features"
                            multiple
                            class="mt-1 block w-full"
                        >
                            <option v-for="feature in features" :key="feature.id" :value="feature.id">
                                {{ feature.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Featured -->
                    <div>
                        <InputLabel for="featured" value="Featured" />
                        <select
                            id="featured"
                            v-model="form.featured"
                            class="mt-1 block w-full"
                        >
                            <option value="false">No</option>
                            <option value="true">Yes</option>
                        </select>
                    </div>

                    <!-- Images -->
                    <div>
                        <InputLabel for="images" value="Vehicle Images" />
                        <Input
                            id="images"
                            type="file"
                            @input="handleFileUpload"
                            class="mt-1 block w-full"
                            accept="image/*"
                            multiple
                        />
                    </div>

                    <div class="flex justify-end gap-2">
                        <Link
                            :href="route('current-vendor-vehicles.index')"
                            class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
                        >
                            Cancel
                        </Link>
                        <Button type="submit" class="bg-primary">
                            Update Vehicle
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AdminDashboardLayout>
</template>
