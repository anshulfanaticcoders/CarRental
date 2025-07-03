<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
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
const autocompleteInputRefEdit = ref(null);
let googleAutocompleteInstanceEdit = null;

const props = defineProps({
    place: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    place_name: props.place.place_name || '',
    city: props.place.city || '',
    state: props.place.state || '',
    country: props.place.country || '',
    latitude: props.place.latitude || null,
    longitude: props.place.longitude || null,
    image: null,
    _method: 'PUT',
});

const locationSearchPlaceholder = computed(() => {
    return `Current: ${form.place_name || 'Enter a location'}`;
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

        googleAutocompleteInstanceEdit = new placesLibrary.Autocomplete(inputElement, {
            fields: ["address_components", "geometry", "name", "formatted_address"],
            types: ["geocode", "establishment"],
        });
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
    if (autocompleteInputRefEdit.value) {
        const actualInputElement =
            autocompleteInputRefEdit.value.$el ||
            (autocompleteInputRefEdit.value instanceof HTMLInputElement
                ? autocompleteInputRefEdit.value
                : autocompleteInputRefEdit.value.$refs?.input);

        if (actualInputElement instanceof HTMLInputElement) {
            await initAutocompleteEdit(actualInputElement);
        } else {
            console.error("Edit: Could not get HTMLInputElement from ref 'autocompleteInputRefEdit'. Value:", autocompleteInputRefEdit.value);
            toast.error("Edit: Location search input element not found. Autocomplete disabled.");
        }
    } else {
        console.error("Edit: autocompleteInputRefEdit is null in onMounted.");
        toast.error("Edit: Location search input ref missing. Autocomplete disabled.");
    }
});

const handleFileChange = (event) => {
    form.image = event.target.files[0];
};

const submit = () => {
    form.post(route('popular-places.update', props.place.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Popular place updated successfully!', {
                position: 'top-right',
                timeout: 3000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
            form.image = null;
            if (autocompleteInputRefEdit.value) {
                const inputEl = autocompleteInputRefEdit.value.$el || autocompleteInputRefEdit.value.$refs?.input;
                if (inputEl instanceof HTMLInputElement) {
                    inputEl.value = '';
                }
            }
        },
        onError: (errors) => {
            toast.error('Error updating popular place: ' + Object.values(errors).join(', '), {
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
      <div class="mx-auto">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="p-8">
            <div class="flex items-center space-x-4 mb-8">
              <div class="w-12 h-12 bg-gradient-to-br from-customPrimaryColor to-customLightPrimaryColor rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
              </div>
              <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Popular Place</h1>
                <p class="text-gray-600">Update location details for {{ form.place_name || 'this place' }}</p>
              </div>
            </div>

            <form @submit.prevent class="space-y-6">
              <div>
                <InputLabel for="locationSearchEdit" value="Search New Location to Update Details" class="block text-sm font-medium text-gray-700 mb-2">
                  <span class="text-red-600">*</span>
                </InputLabel>
                <Input
                  id="locationSearchEdit"
                  ref="autocompleteInputRefEdit"
                  type="text"
                  :placeholder="locationSearchPlaceholder"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                  required
                  aria-required="true"
                />
                <p class="text-sm text-gray-600 mt-2">Selecting a new location will auto-fill the fields below. You can then edit them.</p>
              </div>

              <div>
                <InputLabel for="place_name" value="Place Name" class="block text-sm font-medium text-gray-700 mb-2">
                  <span class="text-red-600">*</span>
                </InputLabel>
                <Input
                  id="place_name"
                  type="text"
                  v-model="form.place_name"
                  required
                  aria-required="true"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                />
                <p v-if="form.errors.place_name" class="mt-2 text-sm text-red-600">{{ form.errors.place_name }}</p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <InputLabel for="city" value="City" class="block text-sm font-medium text-gray-700 mb-2">
                    <span class="text-red-600">*</span>
                  </InputLabel>
                  <Input
                    id="city"
                    type="text"
                    v-model="form.city"
                    required
                    aria-required="true"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                  />
                  <p v-if="form.errors.city" class="mt-2 text-sm text-red-600">{{ form.errors.city }}</p>
                </div>
                <div>
                  <InputLabel for="state" value="State" class="block text-sm font-medium text-gray-700 mb-2" />
                  <Input
                    id="state"
                    type="text"
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
                    type="text"
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
                    type="number"
                    step="any"
                    v-model="form.latitude"
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
                    type="number"
                    step="any"
                    v-model="form.longitude"
                    required
                    aria-required="true"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-customPrimaryColor transition-colors duration-200 bg-gray-50 focus:bg-white"
                  />
                  <p v-if="form.errors.longitude" class="mt-2 text-sm text-red-600">{{ form.errors.longitude }}</p>
                </div>
              </div>

              <div>
                <InputLabel for="image" value="New Place Image (Optional)" class="block text-sm font-medium text-gray-700 mb-2" />
                <div v-if="props.place.image && !form.image" class="mt-2 mb-4">
                  <p class="text-sm text-gray-500 mb-1">Current Image:</p>
                  <img :src="props.place.image" class="w-32 h-32 object-cover rounded-lg" :alt="props.place.place_name" />
                </div>
                <div v-if="form.image && typeof form.image === 'object'" class="mt-2 mb-4">
                  <p class="text-sm text-gray-500 mb-1">New Image Preview:</p>
                  <img :src="previewImageUrl" class="w-32 h-32 object-cover rounded-lg" alt="New image preview" />
                </div>
                <Input
                  id="image"
                  type="file"
                  @input="handleFileChange"
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
                        {{ form.processing ? 'Updating...' : 'Update Place' }}
                      </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                      <AlertDialogHeader>
                        <AlertDialogTitle>Update Popular Place?</AlertDialogTitle>
                        <AlertDialogDescription>
                          Are you sure you want to update this popular place?
                        </AlertDialogDescription>
                      </AlertDialogHeader>
                      <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction @click="submit">Continue</AlertDialogAction>
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
                  <span class="text-sm font-medium">Updating popular place...</span>
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