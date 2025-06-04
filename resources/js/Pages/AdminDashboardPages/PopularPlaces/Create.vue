<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">Create New Popular Place</span>
            </div>

            <div class="rounded-md border p-5 bg-[#153B4F0D]">
                <form @submit.prevent="submitForm" class="space-y-6">
                    <div>
                        <InputLabel for="locationSearch" value="Search Location to Auto-fill Details *" />
                        <Input
                            id="locationSearch"
                            ref="autocompleteInputRef"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Enter a location (e.g., Eiffel Tower, Paris)"
                        />
                        <p class="text-sm text-gray-600 mt-1">Selecting a location will auto-fill the fields below. You can then edit them if needed.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="place_name" value="Place Name *" />
                            <Input
                                id="place_name"
                                v-model="form.place_name"
                                required
                            />
                            <p v-if="form.errors.place_name" class="text-red-500 text-sm mt-1">{{ form.errors.place_name }}</p>
                        </div>
                        <div>
                            <InputLabel for="city" value="City *" />
                            <Input
                                id="city"
                                v-model="form.city"
                                required
                            />
                            <p v-if="form.errors.city" class="text-red-500 text-sm mt-1">{{ form.errors.city }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="state" value="State" />
                            <Input
                                id="state"
                                v-model="form.state"
                            />
                             <p v-if="form.errors.state" class="text-red-500 text-sm mt-1">{{ form.errors.state }}</p>
                        </div>
                        <div>
                            <InputLabel for="country" value="Country *" />
                            <Input
                                id="country"
                                v-model="form.country"
                                required
                            />
                            <p v-if="form.errors.country" class="text-red-500 text-sm mt-1">{{ form.errors.country }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="latitude" value="Latitude *" />
                            <Input 
                                id="latitude"
                                v-model="form.latitude" 
                                type="number"
                                step="any"
                                required 
                            />
                            <p v-if="form.errors.latitude" class="text-red-500 text-sm mt-1">{{ form.errors.latitude }}</p>
                        </div>
                        <div>
                            <InputLabel for="longitude" value="Longitude *" />
                            <Input 
                                id="longitude"
                                v-model="form.longitude" 
                                type="number"
                                step="any"
                                required 
                            />
                             <p v-if="form.errors.longitude" class="text-red-500 text-sm mt-1">{{ form.errors.longitude }}</p>
                        </div>
                    </div>

                    <div>
                        <InputLabel for="image" value="Place Image *" />
                        <Input 
                            id="image"
                            type="file" 
                            @input="form.image = $event.target.files[0]"
                            accept="image/*" 
                            class="mt-1 block w-full"
                        />
                        <p v-if="form.errors.image" class="text-red-500 text-sm mt-1">{{ form.errors.image }}</p>
                    </div>
                    
                    <div class="flex items-center gap-4 pt-4">
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Creating...' : 'Create Place' }}
                        </Button>
                        <Link
                            :href="route('popular-places.index')"
                            class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600"
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
import { ref, onMounted } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import Input from '@/Components/ui/input/Input.vue'; // Assuming this is your custom Input component
import Button from '@/Components/ui/button/Button.vue';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { useToast } from 'vue-toastification';

const toast = useToast();
const autocompleteInputRef = ref(null); // Ref for the <Input> component
let googleAutocompleteInstance = null;

const form = useForm({
    place_name: '',
    city: '',
    state: '',
    country: '',
    latitude: null,
    longitude: null,
    image: null,
});

const initAutocomplete = async (inputElement) => {
    if (!(inputElement instanceof HTMLInputElement)) {
        console.error("Provided element is not an HTMLInputElement:", inputElement);
        toast.error("Could not initialize location search: Invalid input element.");
        return;
    }

    if (typeof window.google === 'undefined' || typeof window.google.maps === 'undefined') {
        console.error("Google Maps API script not loaded.");
        toast.error("Mapping service not available.");
        return;
    }

    try {
        const placesLibrary = await google.maps.importLibrary("places");
        if (!placesLibrary || !placesLibrary.Autocomplete) {
            console.error("Failed to import Google Maps Places Autocomplete.");
            toast.error("Could not initialize location search (Places library missing).");
            return;
        }
        
        googleAutocompleteInstance = new placesLibrary.Autocomplete(
            inputElement, // Use the actual HTMLInputElement
            {
                fields: ["address_components", "geometry", "name", "formatted_address"],
                types: ["geocode", "establishment"],
            }
        );
        googleAutocompleteInstance.addListener("place_changed", fillInAddress);
        console.log("Google Places Autocomplete initialized on HTMLInputElement.");

    } catch (error) {
        console.error("Error initializing Google Places Autocomplete:", error);
        toast.error("Could not initialize location search. See console.");
    }
};

const fillInAddress = () => {
    if (!googleAutocompleteInstance) return;

    const place = googleAutocompleteInstance.getPlace();
    if (!place || !place.geometry || !place.geometry.location) {
        toast.warning("Could not get details for the selected location.");
        form.reset('place_name', 'city', 'state', 'country', 'latitude', 'longitude');
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
    if (autocompleteInputRef.value) {
        // Try to get the native HTML input element from the Vue component ref
        // This depends on how your <Input> component is structured.
        // If <Input> is a simple wrapper, $el might be the native input.
        // If <Input> uses <input> as its root, then autocompleteInputRef.value itself might be the component instance.
        const actualInputElement = autocompleteInputRef.value.$el || (autocompleteInputRef.value instanceof HTMLInputElement ? autocompleteInputRef.value : null);

        if (actualInputElement && actualInputElement instanceof HTMLInputElement) {
            await initAutocomplete(actualInputElement);
        } else if (autocompleteInputRef.value && typeof autocompleteInputRef.value.focus === 'function' && autocompleteInputRef.value.$refs && autocompleteInputRef.value.$refs.input) {
            // Common pattern if Input.vue exposes its internal input via $refs.input
            await initAutocomplete(autocompleteInputRef.value.$refs.input);
        }
        else {
            console.error("Could not get HTMLInputElement from ref 'autocompleteInputRef'. Value:", autocompleteInputRef.value);
            toast.error("Location search input element not found. Autocomplete disabled.");
        }
    } else {
        console.error("autocompleteInputRef is null in onMounted.");
        toast.error("Location search input ref missing. Autocomplete disabled.");
    }
});

const submitForm = () => {
    form.post(route('popular-places.store'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Popular place created successfully!');
            form.reset();
            if (autocompleteInputRef.value) {
                const inputEl = autocompleteInputRef.value.$el || autocompleteInputRef.value;
                if (inputEl && inputEl instanceof HTMLInputElement) {
                    inputEl.value = ''; 
                } else if (autocompleteInputRef.value && autocompleteInputRef.value.$refs && autocompleteInputRef.value.$refs.input) {
                     autocompleteInputRef.value.$refs.input.value = '';
                }
            }
        },
        onError: (errors) => {
            toast.error('Error creating popular place. Please check your inputs.');
        }
    });
};

</script>

<style scoped>
/* Add any specific styles if needed */
</style>
