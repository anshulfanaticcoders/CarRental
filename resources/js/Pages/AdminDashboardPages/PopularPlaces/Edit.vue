<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { useToast } from 'vue-toastification';
import { Toaster } from '@/Components/ui/toast';

const toast = useToast();
const locationSearchContainer = ref(null);
const locationSearch = ref('');
const locationResults = ref([]);
const showLocationResults = ref(false);
const searchingLocations = ref(false);
const selectedLocationHint = ref('');
let searchTimeout = null;

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
    unified_location_id: props.place.unified_location_id || null,
    image: null,
    _method: 'PUT',
});

const isDragging = ref(false);
const imagePreview = ref(null);
const selectedFileName = ref('');

const formatLocationLabel = (location) => {
    const name = location.name || 'Unnamed location';
    const city = location.city || '';
    const country = location.country || '';
    const parts = [city, country].filter(Boolean).join(', ');
    return parts ? `${name} (${parts})` : name;
};

const searchSystemLocations = async () => {
    const term = locationSearch.value.trim();
    if (term.length < 2) {
        locationResults.value = [];
        showLocationResults.value = false;
        return;
    }

    searchingLocations.value = true;
    try {
        const response = await axios.get('/api/unified-locations', {
            params: { search_term: term, limit: 20 },
        });
        locationResults.value = Array.isArray(response.data) ? response.data : [];
        showLocationResults.value = true;
    } catch (error) {
        locationResults.value = [];
        showLocationResults.value = false;
    } finally {
        searchingLocations.value = false;
    }
};

const handleLocationInput = () => {
    form.unified_location_id = null;
    selectedLocationHint.value = 'Select a location from the dropdown to update.';

    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(searchSystemLocations, 250);
};

const selectSystemLocation = (location) => {
    form.unified_location_id = location.unified_location_id;
    form.place_name = location.name || '';
    form.city = location.city || '';
    form.state = location.state || '';
    form.country = location.country || '';
    form.latitude = location.latitude ?? null;
    form.longitude = location.longitude ?? null;

    locationSearch.value = formatLocationLabel(location);
    selectedLocationHint.value = `Selected: ${location.name} (ID: ${location.unified_location_id})`;
    showLocationResults.value = false;
};

const closeLocationDropdown = (event) => {
    if (locationSearchContainer.value && !locationSearchContainer.value.contains(event.target)) {
        showLocationResults.value = false;
    }
};

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

const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        handleImageFile(file);
    }
};

const handleImageFile = (file) => {
    if (file.size > 10 * 1024 * 1024) {
        toast.error('Image size should not exceed 10MB');
        return;
    }

    if (!file.type.startsWith('image/')) {
        toast.error('Please select a valid image file');
        return;
    }

    form.image = file;
    selectedFileName.value = file.name;

    const reader = new FileReader();
    reader.onload = (e) => {
        imagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

const removeImage = () => {
    form.image = null;
    imagePreview.value = null;
    selectedFileName.value = '';
    if (document.querySelector('input[type="file"]')) {
        document.querySelector('input[type="file"]').value = '';
    }
};

const hydrateCurrentSystemLocation = async () => {
    if (!form.unified_location_id) {
        selectedLocationHint.value = 'Select a location from our provider-backed system list.';
        return;
    }

    try {
        const response = await axios.get('/api/unified-locations', {
            params: { unified_location_id: form.unified_location_id },
        });
        const location = Array.isArray(response.data) ? response.data[0] : null;
        if (location) {
            locationSearch.value = formatLocationLabel(location);
            selectedLocationHint.value = `Current: ${location.name} (ID: ${location.unified_location_id})`;
        } else {
            selectedLocationHint.value = 'Current unified location is not found in system data.';
        }
    } catch (error) {
        selectedLocationHint.value = 'Unable to load current unified location details.';
    }
};

const submit = () => {
    if (!form.unified_location_id) {
        toast.error('Please select a location from system locations.');
        return;
    }

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
            imagePreview.value = null;
            selectedFileName.value = '';
        },
        onError: (errors) => {
            toast.error('Error updating popular place: ' + Object.values(errors).join(', '), {
                position: 'top-right',
                timeout: 7000,
            });
        },
    });
};

onMounted(async () => {
    document.addEventListener('click', closeLocationDropdown);
    await hydrateCurrentSystemLocation();
});

onUnmounted(() => {
    document.removeEventListener('click', closeLocationDropdown);
    if (searchTimeout) clearTimeout(searchTimeout);
});

watch(() => form.errors, (newErrors) => {
    if (Object.keys(newErrors).length > 0) {
        console.error('Form errors:', newErrors);
    }
});
</script>

<template>
  <AdminDashboardLayout>
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

            <form @submit.prevent="submit" class="space-y-6">
              <div ref="locationSearchContainer" class="relative">
                <label for="locationSearchEdit" class="text-sm font-medium text-gray-700 mb-2 block">Search System Location <span class="text-red-600">*</span></label>
                <Input
                  id="locationSearchEdit"
                  v-model="locationSearch"
                  type="text"
                  placeholder="Search from unified/provider locations"
                  autocomplete="off"
                  @input="handleLocationInput"
                  @focus="handleLocationInput"
                />
                <p class="text-xs text-gray-500 mt-1">{{ selectedLocationHint }}</p>

                <div
                  v-if="showLocationResults"
                  class="absolute z-20 mt-2 w-full rounded-lg border border-gray-200 bg-white shadow-xl max-h-64 overflow-y-auto"
                >
                  <div v-if="searchingLocations" class="p-3 text-sm text-gray-500">Searching...</div>
                  <button
                    v-for="location in locationResults"
                    :key="location.unified_location_id"
                    type="button"
                    class="w-full text-left px-3 py-2 hover:bg-gray-50 border-b border-gray-100 last:border-b-0"
                    @click="selectSystemLocation(location)"
                  >
                    <div class="text-sm font-medium text-gray-900">{{ location.name }}</div>
                    <div class="text-xs text-gray-500">
                      {{ [location.city, location.country].filter(Boolean).join(', ') }}
                      <span class="ml-1">• ID {{ location.unified_location_id }}</span>
                    </div>
                  </button>
                  <div v-if="!searchingLocations && !locationResults.length" class="p-3 text-sm text-gray-500">
                    No system locations found.
                  </div>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label for="place_name" class="text-sm font-medium text-gray-700 mb-1 block">Place Name <span class="text-red-600">*</span></label>
                  <Input id="place_name" type="text" v-model="form.place_name" readonly />
                </div>
                <div>
                  <label for="city" class="text-sm font-medium text-gray-700 mb-1 block">City <span class="text-red-600">*</span></label>
                  <Input id="city" type="text" v-model="form.city" readonly />
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label for="state" class="text-sm font-medium text-gray-700 mb-1 block">State <span class="text-red-600">*</span></label>
                  <Input id="state" type="text" v-model="form.state" readonly />
                </div>
                <div>
                  <label for="country" class="text-sm font-medium text-gray-700 mb-1 block">Country <span class="text-red-600">*</span></label>
                  <Input id="country" type="text" v-model="form.country" readonly />
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label for="latitude" class="text-sm font-medium text-gray-700 mb-1 block">Latitude <span class="text-red-600">*</span></label>
                  <Input id="latitude" type="number" step="any" v-model="form.latitude" readonly />
                </div>
                <div>
                  <label for="longitude" class="text-sm font-medium text-gray-700 mb-1 block">Longitude <span class="text-red-600">*</span></label>
                  <Input id="longitude" type="number" step="any" v-model="form.longitude" readonly />
                </div>
              </div>
              <div>
                <label for="unified_location_id" class="text-sm font-medium text-gray-700 mb-1 block">Unified Location ID</label>
                <Input id="unified_location_id" type="number" min="1" v-model="form.unified_location_id" readonly />
                <p class="text-xs text-gray-500 mt-1">This comes from your system/provider locations only.</p>
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

                    <!-- Existing Image Display -->
                    <div v-else-if="props.place.image && !form.image" class="space-y-4">
                        <div class="relative">
                            <img :src="props.place.image" :alt="props.place.place_name" class="mx-auto h-48 w-full object-cover rounded-lg border-2 border-gray-200">
                            <div class="absolute top-2 left-2 bg-blue-500 text-white px-2 py-1 rounded text-xs">Current Image</div>
                        </div>
                        <div class="text-center text-sm text-gray-600">
                            <p>Click or drag to replace current image</p>
                        </div>
                    </div>

                    <!-- Upload Instructions -->
                    <div v-else class="text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <div class="mt-4">
                            <p class="text-sm text-gray-600">Drag and drop a new image here, or click to select</p>
                            <p class="text-xs text-gray-500 mt-1">PNG, JPG, GIF up to 10MB</p>
                        </div>
                    </div>
                </div>
              </div>

              <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <div class="flex items-center gap-4">
                  <Button type="submit" :disabled="form.processing || !form.unified_location_id">
                    {{ form.processing ? 'Updating...' : 'Update Place' }}
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
