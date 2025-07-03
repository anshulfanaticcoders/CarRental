<script setup>
import { ref, onMounted, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import Input from '@/Components/ui/input/Input.vue';
import Button from '@/Components/ui/button/Button.vue';
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

// Watch for form errors to log for debugging
watch(() => form.errors, (newErrors) => {
    if (Object.keys(newErrors).length > 0) {
        console.error('Form errors:', newErrors);
    }
});
</script>

<template>
  <AdminDashboardLayout>
    <div v-if="form.processing" class="fixed inset-0 z-50 flex items-center justify-center bg-white bg-opacity-70">
      <img :src="loaderVariant" alt="Loading..." class="h-20 w-20" />
    </div>

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

            <form @submit.prevent class="space-y-6">
              <div>
                <InputLabel for="locationSearch" value="Search Location to Auto-fill Details" class="block text-sm font-medium text-gray-700 mb-2">
                  <span class="text-red-600">*</span>
                </InputLabel>
                <Input
                  id="locationSearch"
                  ref="autocompleteInputRef"
                  type="text"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                  placeholder="Enter a location (e.g., Eiffel Tower, Paris)"
                  required
                  aria-required="true"
                />
                <p class="text-sm text-gray-600 mt-2">Selecting a location will auto-fill the fields below. You can then edit them if needed.</p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <InputLabel for="place_name" value="Place Name" class="block text-sm font-medium text-gray-700 mb-2">
                    <span class="text-red-600">*</span>
                  </InputLabel>
                  <Input
                    id="place_name"
                    v-model="form.place_name"
                    required
                    aria-required="true"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                  />
                  <p v-if="form.errors.place_name" class="mt-2 text-sm text-red-600">{{ form.errors.place_name }}</p>
                </div>
                <div>
                  <InputLabel for="city" value="City" class="block text-sm font-medium text-gray-700 mb-2">
                    <span class="text-red-600">*</span>
                  </InputLabel>
                  <Input
                    id="city"
                    v-model="form.city"
                    required
                    aria-required="true"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                  />
                  <p v-if="form.errors.city" class="mt-2 text-sm text-red-600">{{ form.errors.city }}</p>
                </div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <InputLabel for="state" value="State" class="block text-sm font-medium text-gray-700 mb-2" />
                  <Input
                    id="state"
                    v-model="form.state"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                  />
                  <p v-if="form.errors.state" class="mt-2 text-sm text-red-600">{{ form.errors.state }}</p>
                </div>
                <div>
                  <InputLabel for="country" value="Country" class="block text-sm font-medium text-gray-700 mb-2">
                    <span class="text-red-600">*</span>
                  </InputLabel>
                  <Input
                    id="country"
                    v-model="form.country"
                    required
                    aria-required="true"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                  />
                  <p v-if="form.errors.country" class="mt-2 text-sm text-red-600">{{ form.errors.country }}</p>
                </div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <InputLabel for="latitude" value="Latitude" class="block text-sm font-medium text-gray-700 mb-2">
                    <span class="text-red-600">*</span>
                  </InputLabel>
                  <Input
                    id="latitude"
                    v-model="form.latitude"
                    type="number"
                    step="any"
                    required
                    aria-required="true"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                  />
                  <p v-if="form.errors.latitude" class="mt-2 text-sm text-red-600">{{ form.errors.latitude }}</p>
                </div>
                <div>
                  <InputLabel for="longitude" value="Longitude" class="block text-sm font-medium text-gray-700 mb-2">
                    <span class="text-red-600">*</span>
                  </InputLabel>
                  <Input
                    id="longitude"
                    v-model="form.longitude"
                    type="number"
                    step="any"
                    required
                    aria-required="true"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                  />
                  <p v-if="form.errors.longitude" class="mt-2 text-sm text-red-600">{{ form.errors.longitude }}</p>
                </div>
              </div>

              <div>
                <InputLabel for="image" value="Place Image" class="block text-sm font-medium text-gray-700 mb-2">
                  <span class="text-red-600">*</span>
                </InputLabel>
                <Input
                  id="image"
                  type="file"
                  @input="form.image = $event.target.files[0]"
                  accept="image/*"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                />
                <p v-if="form.errors.image" class="mt-2 text-sm text-red-600">{{ form.errors.image }}</p>
              </div>

              <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <div class="flex items-center space-x-4">
                  <AlertDialog>
                    <AlertDialogTrigger as-child>
                      <Button
                        :disabled="form.processing"
                        class="inline-flex items-center px-6 py-3 bg-customPrimaryColor text-white rounded-lg hover:bg-customPrimaryColor/90 focus:outline-none focus:ring-2 focus:ring-customPrimaryColor focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 font-medium"
                      >
                        <svg
                          v-if="form.processing"
                          class="animate-spin -ml-1 mr-3 h-4 w-4 text-white"
                          xmlns="http://www.w3.org/2000/svg"
                          fill="none"
                          viewBox="0 0 24 24"
                        >
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                          ></path>
                        </svg>
                        <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        {{ form.processing ? 'Creating...' : 'Create Place' }}
                      </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                      <AlertDialogHeader>
                        <AlertDialogTitle>Create Popular Place?</AlertDialogTitle>
                        <AlertDialogDescription>
                          Are you sure you want to create this popular place?
                        </AlertDialogDescription>
                      </AlertDialogHeader>
                      <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction @click="submitForm">Continue</AlertDialogAction>
                      </AlertDialogFooter>
                    </AlertDialogContent>
                  </AlertDialog>

                  <Link
                    :href="route('popular-places.index')"
                    class="inline-flex items-center px-6 py-2 border border-gray-300 text-gray-700 bg-white rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-customPrimaryColor focus:ring-offset-2 transition-all duration-200 font-medium"
                  >
                    Cancel
                  </Link>
                </div>

                <div v-if="form.processing" class="flex items-center text-customPrimaryColor">
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