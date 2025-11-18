<script setup>
import { ref, onMounted, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { useToast } from 'vue-toastification';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/Components/ui/alert-dialog';
import { Toaster } from '@/Components/ui/toast';
import loaderVariant from '../../../../assets/loader-variant.svg';

const toast = useToast();
const autocompleteInputRef = ref(null);
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

const isDragging = ref(false);
const imagePreview = ref(null);
const selectedFileName = ref('');

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

        googleAutocompleteInstance = new placesLibrary.Autocomplete(inputElement, {
            fields: ["address_components", "geometry", "name", "formatted_address"],
            types: ["geocode", "establishment"],
        });
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
                case "locality":
                    city = longName;
                    break;
                case "administrative_area_level_1":
                    state = longName;
                    break;
                case "country":
                    country = longName;
                    break;
                case "postal_town":
                    if (!city) city = longName;
                    break;
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
        const actualInputElement =
            autocompleteInputRef.value.$el ||
            (autocompleteInputRef.value instanceof HTMLInputElement
                ? autocompleteInputRef.value
                : autocompleteInputRef.value.$refs?.input);

        if (actualInputElement instanceof HTMLInputElement) {
            await initAutocomplete(actualInputElement);
        } else {
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
            toast.success('Popular place created successfully!', {
                position: 'top-right',
                timeout: 3000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
            form.reset();
            if (autocompleteInputRef.value) {
                const inputEl = autocompleteInputRef.value.$el || autocompleteInputRef.value.$refs?.input;
                if (inputEl instanceof HTMLInputElement) {
                    inputEl.value = '';
                }
            }
        },
        onError: (errors) => {
            toast.error('Error creating popular place: ' + Object.values(errors).join(', '), {
                position: 'top-right',
                timeout: 7000,
            });
        },
    });
};

// Drag and drop handlers
const handleDragOver = (event) => {
    event.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = (event) => {
    event.preventDefault();
    isDragging.value = false;
};

const handleDrop = (event) => {
    event.preventDefault();
    isDragging.value = false;

    const files = event.dataTransfer.files;
    if (files.length > 0 && files[0].type.startsWith('image/')) {
        handleImageFile(files[0]);
    }
};

// File upload handler
const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        handleImageFile(file);
    }
};

// Handle image file
const handleImageFile = (file) => {
    if (file.size > 10 * 1024 * 1024) { // 10MB limit
        toast.error('Image size should not exceed 10MB');
        return;
    }

    if (!file.type.startsWith('image/')) {
        toast.error('Please select a valid image file');
        return;
    }

    form.image = file;
    selectedFileName.value = file.name;

    // Create preview
    const reader = new FileReader();
    reader.onload = (e) => {
        imagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

// Remove image
const removeImage = () => {
    form.image = null;
    imagePreview.value = null;
    selectedFileName.value = '';
    // Reset file input
    if (document.querySelector('input[type="file"]')) {
        document.querySelector('input[type="file"]').value = '';
    }
};

// Watch for form errors to log for debugging
watch(() => form.errors, (newErrors) => {
    if (Object.keys(newErrors).length > 0) {
        console.error('Form errors:', newErrors);
    }
});
</script>

<template>
  <AdminDashboardLayout>
    <div class="py-12 px-4 sm:px-6 lg:px-8">
      <div class=" mx-auto">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="p-8">
            <div class="flex items-center space-x-4 mb-8">
              <div class="w-12 h-12 bg-gradient-to-br from-customPrimaryColor to-customLightPrimaryColor rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
              </div>
              <div>
                <h1 class="text-2xl font-bold text-gray-900">Create New Popular Place</h1>
                <p class="text-gray-600">Add a new popular place with location details</p>
              </div>
            </div>

            <form @submit.prevent="submitForm" class="space-y-6">
              <div>
                <label for="locationSearch" class="text-sm font-medium text-gray-700 mb-2 block">Search Location to Auto-fill Details <span class="text-red-600">*</span></label>
                <Input
                  id="locationSearch"
                  ref="autocompleteInputRef"
                  type="text"
                  placeholder="Enter a location (e.g., Eiffel Tower, Paris)"
                  required
                  aria-required="true"
                />
                <p class="text-xs text-gray-500 mt-1">Selecting a location will auto-fill the fields below. You can then edit them if needed.</p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label for="place_name" class="text-sm font-medium text-gray-700 mb-1 block">Place Name <span class="text-red-600">*</span></label>
                  <Input id="place_name" type="text" v-model="form.place_name" required />
                </div>
                <div>
                  <label for="city" class="text-sm font-medium text-gray-700 mb-1 block">City <span class="text-red-600">*</span></label>
                  <Input id="city" type="text" v-model="form.city" required />
                </div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label for="state" class="text-sm font-medium text-gray-700 mb-1 block">State <span class="text-red-600">*</span></label>
                  <Input id="state" type="text" v-model="form.state" required />
                </div>
                <div>
                  <label for="country" class="text-sm font-medium text-gray-700 mb-1 block">Country <span class="text-red-600">*</span></label>
                  <Input id="country" type="text" v-model="form.country" required />
                </div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label for="latitude" class="text-sm font-medium text-gray-700 mb-1 block">Latitude <span class="text-red-600">*</span></label>
                  <Input id="latitude" type="number" step="any" v-model="form.latitude" required />
                </div>
                <div>
                  <label for="longitude" class="text-sm font-medium text-gray-700 mb-1 block">Longitude <span class="text-red-600">*</span></label>
                  <Input id="longitude" type="number" step="any" v-model="form.longitude" required />
                </div>
              </div>

              <div>
                <label class="text-sm font-medium text-gray-700 mb-2 block">Place Image</label>
                <div
                    class="relative border-2 border-dashed border-gray-300 rounded-lg p-6 hover:border-gray-400 transition-colors cursor-pointer w-1/2"
                    :class="{ 'border-blue-500 bg-blue-50': isDragging }"
                    @drop="handleDrop"
                    @dragover.prevent="handleDragOver"
                    @dragleave.prevent="handleDragLeave"
                    @click="$refs.fileInput.click()"
                >
                    <input
                        ref="fileInput"
                        type="file"
                        class="hidden"
                        @change="handleFileUpload"
                        accept="image/jpeg,image/png,image/jpg,image/gif"
                    >

                    <!-- Image Preview -->
                    <div v-if="imagePreview" class="space-y-4">
                        <div class="relative">
                            <img :src="imagePreview" alt="Preview" class="mx-auto h-48 w-full object-cover rounded-lg">
                            <button
                                type="button"
                                @click.stop="removeImage"
                                class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="text-center text-sm text-gray-600">
                            <p class="font-medium">{{ selectedFileName }}</p>
                            <p>Click or drag to replace image</p>
                        </div>
                    </div>

                    <!-- Upload Instructions -->
                    <div v-else class="text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <div class="mt-4">
                            <p class="text-sm text-gray-600">Drag and drop an image here, or click to select</p>
                            <p class="text-xs text-gray-500 mt-1">PNG, JPG, GIF up to 10MB</p>
                        </div>
                    </div>
                </div>
              </div>

              <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <div class="flex items-center gap-4">
                  <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Creating...' : 'Create Place' }}
                  </Button>
                  <Link :href="route('popular-places.index')">
                    <Button type="button" variant="outline">
                      Cancel
                    </Button>
                  </Link>
                </div>

                <div v-if="form.processing" class="flex items-center text-blue-600">
                  <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path
                      class="opacity-75"
                      fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                  </svg>
                  <span class="text-sm font-medium">Creating popular place...</span>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <Toaster />
  </AdminDashboardLayout>
</template>

<style>
.text-customPrimaryColor {
  color: var(--custom-primary);
}

.bg-customPrimaryColor {
  background-color: var(--custom-primary);
}

.border-customPrimaryColor {
  border-color: var(--custom-primary);
}

.ring-customPrimaryColor {
  --tw-ring-color: var(--custom-primary);
}

.focus\:ring-customPrimaryColor:focus {
  --tw-ring-color: var(--custom-primary);
}

.focus\:border-customPrimaryColor:focus {
  --tw-border-opacity: 1;
  border-color: var(--custom-primary);
}

.hover\:bg-customPrimaryColor\/90:hover {
  background-color: color-mix(in srgb, var(--custom-primary) 90%, transparent);
}

.bg-customLightPrimaryColor {
  background-color: var(--custom-light-primary);
}

.hover\:bg-customLightPrimaryColor\/10:hover {
  background-color: color-mix(in srgb, var(--custom-light-primary) 10%, transparent);
}

.ring-customLightPrimaryColor\/20 {
  --tw-ring-color: color-mix(in srgb, var(--custom-light-primary) 20%, transparent);
}
</style>