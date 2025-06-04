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

            <div class="rounded-md border p-5 mt-[1rem] bg-white">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <InputLabel for="locationSearchEdit" value="Search New Location to Update Details" />
                        <Input
                            id="locationSearchEdit"
                            ref="autocompleteInputRefEdit"
                            type="text"
                            class="mt-1 block w-full"
                            :placeholder="`Current: ${form.place_name}`"
                        />
                        <p class="text-sm text-gray-600 mt-1">Selecting a new location will auto-fill the fields below. You can then edit them.</p>
                    </div>

                    <!-- Place Name -->
                    <div>
                        <InputLabel for="place_name" value="Place Name *" />
                        <Input id="place_name" type="text" v-model="form.place_name" class="mt-1 block w-full" required />
                        <p v-if="form.errors.place_name" class="text-red-500 text-sm mt-1">{{ form.errors.place_name }}</p>
                    </div>

                    <!-- City, State, Country -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <InputLabel for="city" value="City *" />
                            <Input id="city" type="text" v-model="form.city" class="mt-1 block w-full" required />
                            <p v-if="form.errors.city" class="text-red-500 text-sm mt-1">{{ form.errors.city }}</p>
                        </div>
                        <div>
                            <InputLabel for="state" value="State" />
                            <Input id="state" type="text" v-model="form.state" class="mt-1 block w-full" />
                            <p v-if="form.errors.state" class="text-red-500 text-sm mt-1">{{ form.errors.state }}</p>
                        </div>
                        <div>
                            <InputLabel for="country" value="Country *" />
                            <Input id="country" type="text" v-model="form.country" class="mt-1 block w-full" required />
                            <p v-if="form.errors.country" class="text-red-500 text-sm mt-1">{{ form.errors.country }}</p>
                        </div>
                    </div>

                    <!-- Latitude & Longitude -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="latitude" value="Latitude *" />
                            <Input id="latitude" type="number" step="any" v-model="form.latitude"
                                class="mt-1 block w-full" required />
                            <p v-if="form.errors.latitude" class="text-red-500 text-sm mt-1">{{ form.errors.latitude }}</p>
                        </div>
                        <div>
                            <InputLabel for="longitude" value="Longitude *" />
                            <Input id="longitude" type="number" step="any" v-model="form.longitude"
                                class="mt-1 block w-full" required />
                            <p v-if="form.errors.longitude" class="text-red-500 text-sm mt-1">{{ form.errors.longitude }}</p>
                        </div>
                    </div>

                    <!-- Image -->
                    <div>
                        <InputLabel for="image" value="New Place Image (Optional)" />
                        <div v-if="props.place.image && !form.image" class="mt-2 mb-4">
                            <p class="text-sm text-gray-500 mb-1">Current Image:</p>
                            <img :src="`${props.place.image}`" class="w-32 h-32 object-cover rounded"
                                :alt="props.place.place_name" />
                        </div>
                         <div v-if="form.image && typeof form.image === 'object'" class="mt-2 mb-4">
                            <p class="text-sm text-gray-500 mb-1">New Image Preview:</p>
                            <img :src="previewImageUrl" class="w-32 h-32 object-cover rounded" alt="New image preview" />
                        </div>
                        <Input id="image" type="file" @input="handleFileChange"
                            class="mt-1 block w-full" accept="image/*" />
                        <p v-if="form.errors.image" class="text-red-500 text-sm mt-1">{{ form.errors.image }}</p>
                    </div>
                   
                    <div class="flex justify-end gap-4 pt-4">
                        <Link :href="route('popular-places.index')"
                            class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">
                        Cancel
                        </Link>
                        <Button type="submit" class="bg-primary" :disabled="form.processing">
                            {{ form.processing ? 'Updating...' : 'Update Place' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Input } from '@/Components/ui/input'; // Assuming this is your custom Input component
import { Button } from '@/Components/ui/button';
import { useToast } from 'vue-toastification';
import loader from '../../../../assets/loader.gif'; // Ensure this path is correct

const toast = useToast();
const isLoading = ref(false); // For general page loading/submission visual feedback
const autocompleteInputRefEdit = ref(null);
let googleAutocompleteInstanceEdit = null;

const props = defineProps({
    place: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    place_name: props.place.place_name,
    city: props.place.city,
    state: props.place.state || '', // Ensure state is at least an empty string
    country: props.place.country,
    latitude: props.place.latitude,
    longitude: props.place.longitude,
    image: null, // Will hold the new image file if selected, otherwise backend keeps old one
    _method: 'PUT', // For Laravel to recognize it as an update
});

const previewImageUrl = computed(() => {
    if (form.image && typeof form.image === 'object') {
        return URL.createObjectURL(form.image);
    }
    return null;
});


const initAutocompleteEdit = async (inputElement) => {
    if (!(inputElement instanceof HTMLInputElement)) {
        console.error("Edit: Provided element is not an HTMLInputElement:", inputElement);
        toast.error("Edit: Could not initialize location search: Invalid input element.");
        return;
    }
     if (typeof window.google === 'undefined' || typeof window.google.maps === 'undefined') {
        console.error("Edit: Google Maps API script not loaded.");
        toast.error("Edit: Mapping service not available.");
        return;
    }

    try {
        const placesLibrary = await google.maps.importLibrary("places");
        if (!placesLibrary || !placesLibrary.Autocomplete) {
            console.error("Edit: Failed to import Google Maps Places Autocomplete.");
            toast.error("Edit: Could not initialize location search (Places library missing).");
            return;
        }
        
        googleAutocompleteInstanceEdit = new placesLibrary.Autocomplete(
            inputElement,
            {
                fields: ["address_components", "geometry", "name", "formatted_address"],
                types: ["geocode", "establishment"],
            }
        );
        googleAutocompleteInstanceEdit.addListener("place_changed", fillInAddressEdit);
        console.log("Edit: Google Places Autocomplete initialized.");

    } catch (error) {
        console.error("Edit: Error initializing Google Places Autocomplete:", error);
        toast.error("Edit: Could not initialize location search. See console.");
    }
};

const fillInAddressEdit = () => {
    if (!googleAutocompleteInstanceEdit) return;
    const place = googleAutocompleteInstanceEdit.getPlace();

    if (!place || !place.geometry || !place.geometry.location) {
        toast.warning("Edit: Could not get details for the selected location.");
        // Optionally, you might not want to reset fields here if user is just trying out search
        return;
    }

    let placeName = place.name || '';
    let city = '';
    let state = '';
    let country = '';

    if (place.address_components) {
        for (const component of place.address_components) {
            const componentType = component.types && component.types[0];
            const longName = component.long_name;
            if (!componentType || !longName) continue;

            switch (componentType) {
                case "locality": city = longName; break;
                case "administrative_area_level_1": state = longName; break;
                case "country": country = longName; break;
                case "postal_town": if (!city) city = longName; break;
            }
        }
    }
    
    if (placeName === city && place.formatted_address) {
        const firstPartOfAddress = place.formatted_address.split(',')[0];
        if (firstPartOfAddress && firstPartOfAddress.toLowerCase() !== city.toLowerCase()) {
            placeName = firstPartOfAddress;
        }
    }
     if (!placeName && place.formatted_address) {
         placeName = place.formatted_address.split(',')[0];
    }

    form.place_name = placeName;
    form.city = city;
    form.state = state;
    form.country = country;
    form.latitude = place.geometry.location.lat();
    form.longitude = place.geometry.location.lng();
};

onMounted(async () => {
    if (autocompleteInputRefEdit.value) {
        const actualInputElement = autocompleteInputRefEdit.value.$el || (autocompleteInputRefEdit.value instanceof HTMLInputElement ? autocompleteInputRefEdit.value : null);
        if (actualInputElement && actualInputElement instanceof HTMLInputElement) {
            await initAutocompleteEdit(actualInputElement);
        } else if (autocompleteInputRefEdit.value && typeof autocompleteInputRefEdit.value.focus === 'function' && autocompleteInputRefEdit.value.$refs && autocompleteInputRefEdit.value.$refs.input) {
            await initAutocompleteEdit(autocompleteInputRefEdit.value.$refs.input);
        } else {
            console.error("Edit: Could not get HTMLInputElement from ref 'autocompleteInputRefEdit'. Value:", autocompleteInputRefEdit.value);
        }
    } else {
         console.error("Edit: autocompleteInputRefEdit is null in onMounted.");
    }
});

const handleFileChange = (event) => {
    form.image = event.target.files[0];
};

const submit = () => {
    isLoading.value = true; // Show loader
    // For 'PUT' with FormData, Inertia requires you to use router.post and include _method: 'PUT'
    form.post(route('popular-places.update', props.place.id), {
        forceFormData: true, // Important for file uploads with PUT/PATCH
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Popular place updated successfully!');
            // form.image = null; // Clear the file input after successful upload
        },
        onError: (errors) => {
            toast.error('Error updating popular place. Please check your inputs.');
            console.error("Update errors:", errors);
        },
        onFinish: () => {
            isLoading.value = false; // Hide loader
        }
    });
};
</script>

<style scoped>
/* Add any specific styles if needed */
</style>
