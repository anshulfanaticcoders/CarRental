<template>
    <Head title="Bulk Vehicle Upload" />
    <AuthenticatedHeaderLayout/>
        <div class="full-w-container py-12 relative"> <!-- Added relative for positioning the popup -->
            <!-- "Copied!" Popup -->
            <div v-if="showCopyNotification" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
                <div class="bg-white p-4 rounded-lg shadow-xl text-center">
                    <p class="text-lg font-semibold text-green-600">Copied!</p>
                </div>
            </div>

            <div class="mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200 space-y-6">
                        <!-- Flash Messages for general page and CSV upload -->
                        <div v-if="$page.props.flash && $page.props.flash.message" class="mb-4">
                            <div :class="`p-4 rounded-md ${$page.props.flash.type === 'success' ? 'bg-green-100 text-green-700' : ($page.props.flash.type === 'warning' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700')}`">
                                <p v-html="$page.props.flash.message"></p>
                                <!-- Display details of failed image uploads if provided in flash -->
                                <ul v-if="$page.props.flash.failed_images_details && $page.props.flash.failed_images_details.length > 0" class="list-disc list-inside mt-1 text-sm">
                                    <li v-for="(detail, index) in $page.props.flash.failed_images_details" :key="`img-err-${index}`">{{ detail }}</li>
                                </ul>
                                <!-- Display structured CSV errors -->
                                <ul v-if="$page.props.flash.csv_errors && $page.props.flash.csv_errors.length > 0" class="list-disc list-inside mt-2 text-sm">
                                    <li v-for="(error, index) in $page.props.flash.csv_errors" :key="`csv-err-${index}`">
                                        Row {{ error.row }}: Column '{{ error.column }}' - {{ error.message }}
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Second Row: Two Columns -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Left Column: Upload CSV File -->
                            <div class="p-4 border border-gray-300 rounded-md space-y-4">
                                <h3 class="text-lg font-semibold text-gray-700">Upload CSV File</h3>
                                <form @submit.prevent="submitCsvForm">
                                    <div class="mb-4">
                                        <InputLabel for="csv_file" value="CSV File" />
                                        <input type="file" id="csv_file" @change="handleCsvFileChange" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" accept=".csv,.txt" required />
                                        <InputError class="mt-2" :message="csvForm.errors.csv_file" />
                                    </div>

                                    <div class="flex items-center justify-between mt-4">
                                        <a :href="route('vehicles.bulk-upload.template')" class="text-sm text-blue-600 hover:underline">
                                            Download CSV Template
                                        </a>
                                        <PrimaryButton :class="{ 'opacity-25': csvForm.processing }" :disabled="csvForm.processing">
                                            Upload Vehicles
                                        </PrimaryButton>
                                    </div>
                                </form>
                                <div v-if="csvForm.progress" class="mt-4">
                                    <progress :value="csvForm.progress.percentage" max="100" class="w-full h-2 rounded">
                                        {{ csvForm.progress.percentage }}%
                                    </progress>
                                </div>
                            </div>

                            <!-- Right Column: Manage Vehicle Images -->
                            <div class="p-4 border border-gray-300 rounded-md space-y-4">
                                <h3 class="text-lg font-semibold text-gray-700">Manage Vehicle Images</h3>
                                
                                <div v-if="showImageUploadTutorial && bulkImages.length === 0" class="p-3 bg-indigo-50 border border-indigo-200 rounded-md">
                                    <p class="text-sm text-indigo-700">
                                        Upload your vehicle images here first. You can then copy their URLs for your CSV file.
                                    </p>
                                </div>

                                <form @submit.prevent="uploadImage" class="space-y-3">
                                    <div>
                                        <InputLabel for="vehicle_images" value="Upload New Images (up to 50)" />
                                        <input type="file" id="vehicle_images" @change="handleImageFileChange" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" accept="image/*" multiple />
                                        <InputError class="mt-1" :message="imageUploadForm.errors.images" />
                                        <!-- Displaying specific errors for each file if validation fails for images.* -->
                                        <div v-for="(errorArray, key) in imageUploadForm.errors" :key="key">
                                            <template v-if="key.startsWith('images.')">
                                                <InputError v-for="(msg, i) in errorArray" :key="`${key}-${i}`" class="mt-1" :message="`File ${parseInt(key.split('.')[1]) + 1}: ${msg}`" />
                                            </template>
                                        </div>
                                    </div>
                                <PrimaryButton :class="{ 'opacity-25': imageUploadForm.processing }" :disabled="imageUploadForm.processing || selectedImageFiles.length === 0">
                                    Upload Selected Images
                                </PrimaryButton>
                            </form>
                            
                            <div v-if="imageUploadForm.progress" class="mt-2">
                                    <progress :value="imageUploadForm.progress.percentage" max="100" class="w-full h-2 rounded">
                                        {{ imageUploadForm.progress.percentage }}%
                                    </progress>
                                </div>
                                
                                <h4 class="text-md font-semibold text-gray-600">Your Uploaded Images:</h4>
                                <div v-if="loadingImages" class="text-sm text-gray-500">Loading images...</div>
                                <div v-else-if="paginatedImages.length === 0 && bulkImages.length === 0" class="text-sm text-gray-500">
                                    No images uploaded yet for bulk import.
                                </div>
                                <div v-else-if="paginatedImages.length === 0 && bulkImages.length > 0" class="text-sm text-gray-500">
                                    No images on this page.
                                </div>
                                <div v-else class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                                    <div v-for="image in paginatedImages" :key="image.id" class="relative group border rounded-md p-2 shadow">
                                        <img :src="image.url" :alt="image.original_name" class="w-full h-24 object-cover rounded-md mb-2">
                                        <p class="text-xs text-gray-600 truncate" :title="image.original_name">{{ image.original_name }}</p>
                                        <div class="mt-1 space-x-1 flex flex-wrap gap-1">
                                            <button @click="copyImageUrl(image.url)" class="text-xs bg-green-500 hover:bg-green-600 text-white py-1 px-2 rounded">Copy URL</button>
                                            <button @click="deleteImage(image.id)" class="text-xs bg-red-500 hover:bg-red-600 text-white py-1 px-2 rounded absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity">X</button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Pagination Controls -->
                                <div v-if="totalPages > 1" class="mt-4 flex justify-between items-center text-sm">
                                    <button @click="prevPage" :disabled="currentPage === 1" class="px-3 py-1 border rounded-md hover:bg-gray-100 disabled:opacity-50">Previous</button>
                                    <span>Page {{ currentPage }} of {{ totalPages }}</span>
                                    <button @click="nextPage" :disabled="currentPage === totalPages" class="px-3 py-1 border rounded-md hover:bg-gray-100 disabled:opacity-50">Next</button>
                                </div>
                            </div>
                        </div>

                        <!-- Top Row: Instructions -->
                        <div class="p-4 bg-blue-50 border border-blue-200 rounded-md mb-6">
                            <h3 class="text-lg font-medium text-blue-700 mb-2">Important Instructions for Bulk Upload:</h3>
                            <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                                <li>For fields requiring a Yes/No or True/False answer (e.g., 'is_available', 'has_feature_x'), please use <strong>1 for Yes/True</strong> and <strong>0 for No/False</strong> in your CSV.</li>
                                <li>Each vehicle can have a maximum of <strong>two pricing plans</strong>. Ensure your CSV reflects this.</li>
                                <li><strong>Vehicle Images:</strong> First, upload your vehicle images in the "Manage Vehicle Images" section on the right. After uploading, copy the generated image URL and paste it into the 'image_url' column in your CSV file.</li>
                            </ul>
                        </div>

                        <!-- Vehicle Categories Information -->
                        <div class="p-4 bg-green-50 border border-green-200 rounded-md mb-6">
                            <h3 class="text-lg font-medium text-green-700 mb-2">Available Vehicle Categories:</h3>
                            <p class="text-sm text-gray-700 mb-2">
                                When preparing your CSV, use the following Category IDs for the 'vehicle_category_id' column:
                            </p>
                            <!-- TODO: Fetch and display categories dynamically -->
                            <div v-if="vehicleCategories.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 text-sm">
                                <div v-for="category in vehicleCategories" :key="category.id" class="p-2 bg-white border rounded">
                                    <strong>{{ category.name }}</strong>: ID <code>{{ category.id }}</code>
                                </div>
                            </div>
                            <div v-else-if="loadingCategories" class="text-sm text-gray-500">
                                Loading categories...
                            </div>
                            <div v-else class="text-sm text-red-500">
                                Could not load vehicle categories. Please ensure they are set up in the admin panel.
                            </div>
                        </div>

                        <!-- Pricing Plans Information -->
                        <div class="p-4 bg-purple-50 border border-purple-200 rounded-md mb-6">
                            <h3 class="text-lg font-medium text-purple-700 mb-2">Pricing Plan Guidelines:</h3>
                            <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                                <li>You can add up to <strong>two pricing plans</strong> per vehicle.</li>
                                <li>For each plan, specify the plan name (e.g., "Daily", "Weekly").</li>
                                <li>List features for each plan in the designated columns (e.g., 'plan1_feature1', 'plan1_feature2', 'plan2_feature1').</li>
                                <li>Example Format for Plan Features in CSV:
                                    <ul class="list-circle list-inside ml-4">
                                        <li>plan1_name: Daily</li>
                                        <li>plan1_price: 50</li>
                                        <li>plan1_feature1: Unlimited Mileage</li>
                                        <li>plan1_feature2: Basic Insurance</li>
                                        <li>plan2_name: Weekly</li>
                                        <li>plan2_price: 300</li>
                                        <li>plan2_feature1: 1000km Limit</li>
                                        <li>plan2_feature2: Full Insurance</li>
                                    </ul>
                                </li>
                            </ul>
                        </div>

                        <!-- Extras Information -->
                        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-md mb-6">
                            <h3 class="text-lg font-medium text-yellow-700 mb-2">About Extras:</h3>
                            <p class="text-sm text-gray-700 mb-2">
                                Extras are additional services or items a customer can add to their booking (e.g., GPS, Child Seat).
                            </p>
                            <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                                <li>Define your available extras in the admin panel first.</li>
                                <li>In the CSV, you will use the ID of the extra. For example, if "GPS" has an ID of 1 and "Child Seat" has an ID of 2, you would enter these IDs.</li>
                                <li>The CSV template has columns like 'extra1_id', 'extra1_price', 'extra2_id', 'extra2_price', etc.</li>
                                <li>Ensure the Extra IDs you use in the CSV correspond to existing extras in the system.</li>
                            </ul>
                            <!-- TODO: Optionally, fetch and display available extras and their IDs here if helpful -->
                        </div>

                        <!-- Vehicle Features Information -->
                        <div class="p-4 bg-teal-50 border border-teal-200 rounded-md mb-6">
                            <h3 class="text-lg font-medium text-teal-700 mb-2">Available Vehicle Features:</h3>
                            <p class="text-sm text-gray-700 mb-3">
                                Below is a list of available features, grouped by vehicle category. Use the feature names in your CSV under columns like 'feature_1', 'feature_2', etc.
                                Ensure the feature name in the CSV exactly matches the name shown here.
                            </p>
                            <div v-if="loadingFeatures" class="text-sm text-gray-500">Loading features...</div>
                            <div v-else-if="Object.keys(groupedFeatures).length > 0">
                                <div v-for="(features, categoryName) in groupedFeatures" :key="categoryName" class="mb-3">
                                    <h4 class="text-md font-semibold text-teal-600 mb-1">{{ categoryName }}</h4>
                                    <ul class="list-disc list-inside text-sm text-gray-600 space-y-0.5 ml-4">
                                        <li v-for="feature in features" :key="feature.id">
                                            {{ feature.feature_name }}
                                            <span v-if="feature.icon_url" class="text-xs text-gray-400 ml-1">(has icon)</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div v-else class="text-sm text-red-500">
                                Could not load vehicle features or no features are defined. Please ensure they are set up in the admin panel.
                            </div>
                        </div>

                        
                    </div>
                </div>
            </div>
        </div>

</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';

const page = usePage();

// Vehicle Categories
const vehicleCategories = ref([]);
const loadingCategories = ref(true);

// Vehicle Features
const allFeatures = ref([]);
const loadingFeatures = ref(true);

const fetchVehicleCategories = async () => {
    loadingCategories.value = true;
    try {
        const response = await axios.get(route('api.vehicle-categories.index'));
        vehicleCategories.value = response.data;
    } catch (error) {
        console.error('Error fetching vehicle categories:', error);
    } finally {
        loadingCategories.value = false;
    }
};

const fetchVehicleFeatures = async () => {
    loadingFeatures.value = true;
    try {
        const response = await axios.get(route('api.vehicle-features.index'));
        allFeatures.value = response.data;
    } catch (error) {
        console.error('Error fetching vehicle features:', error);
    } finally {
        loadingFeatures.value = false;
    }
};

const groupedFeatures = computed(() => {
    const groups = {};
    allFeatures.value.forEach(feature => {
        const categoryName = feature.category ? feature.category.name : 'General Features';
        if (!groups[categoryName]) {
            groups[categoryName] = [];
        }
        groups[categoryName].push(feature);
    });
    // Sort features within each group alphabetically
    for (const categoryName in groups) {
        groups[categoryName].sort((a, b) => a.feature_name.localeCompare(b.feature_name));
    }
    // Sort categories alphabetically, keeping "General Features" first if it exists
    const sortedGroupNames = Object.keys(groups).sort((a, b) => {
        if (a === 'General Features') return -1;
        if (b === 'General Features') return 1;
        return a.localeCompare(b);
    });
    const sortedGroups = {};
    sortedGroupNames.forEach(name => {
        sortedGroups[name] = groups[name];
    });
    return sortedGroups;
});


// "Copied!" Notification
const showCopyNotification = ref(false);

// CSV Upload Form
const csvForm = useForm({
    csv_file: null,
});

const handleCsvFileChange = (event) => {
    csvForm.csv_file = event.target.files[0];
};

const submitCsvForm = () => {
    csvForm.post(route('vehicles.bulk-upload.store'), {
        preserveScroll: true,
        onSuccess: () => {
            csvForm.reset('csv_file');
            // Inertia flash message will be shown from $page.props.flash
        },
    });
};

// Bulk Image Management
const bulkImages = ref([]);
const loadingImages = ref(true);
const showImageUploadTutorial = ref(false);
const selectedImageFiles = ref([]);

const imageUploadForm = useForm({
    images: [],
});

const handleImageFileChange = (event) => {
    selectedImageFiles.value = Array.from(event.target.files);
    imageUploadForm.images = selectedImageFiles.value;
    imageUploadForm.clearErrors(); // Clear previous validation errors
};

const fetchBulkImages = async () => {
    loadingImages.value = true;
    try {
        const response = await axios.get(route('vendor.bulk-vehicle-images.index', { _t: new Date().getTime() }));
        bulkImages.value = response.data;
        if (bulkImages.value.length === 0) {
            showImageUploadTutorial.value = true;
        }
        currentPage.value = 1; // Reset to first page
    } catch (error) {
        console.error('Error fetching bulk images:', error);
        // Optionally set a general error message if needed, though Inertia flash should cover server errors
    } finally {
        loadingImages.value = false;
    }
};

// Pagination logic
const currentPage = ref(1);
const imagesPerPage = ref(9); // Display 9 images per page

const totalPages = computed(() => {
    return Math.ceil(bulkImages.value.length / imagesPerPage.value);
});

const paginatedImages = computed(() => {
    const start = (currentPage.value - 1) * imagesPerPage.value;
    const end = start + imagesPerPage.value;
    return bulkImages.value.slice(start, end);
});

const nextPage = () => {
    if (currentPage.value < totalPages.value) { // Corrected syntax error here
        currentPage.value++;
    }
};

const prevPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--;
    }
};

const uploadImage = () => {
    if (selectedImageFiles.value.length === 0) return;

    imageUploadForm.post(route('vendor.bulk-vehicle-images.store'), {
        preserveScroll: true,
        onSuccess: () => { 
            fetchBulkImages(); 
            selectedImageFiles.value = []; 
            imageUploadForm.reset('images');
            const fileInput = document.getElementById('vehicle_images');
            if (fileInput) fileInput.value = '';
            // Backend now sends Inertia flash messages, which are displayed by the global flash component.
            // $page.props.flash will be updated automatically by Inertia.
        },
        onError: (errors) => {
            // Inertia's useForm automatically populates imageUploadForm.errors.
            // These are displayed by the InputError components.
            // The global flash message component at the top will also display any general error message
            // set in $page.props.flash.message by the backend on error.
            console.error("Image upload errors:", errors); 
        }
    });
};

const deleteImage = async (imageId) => {
    if (!confirm('Are you sure you want to delete this image? This action cannot be undone.')) {
        return;
    }
    try {
        // The delete endpoint returns JSON, so use axios directly.
        await axios.delete(route('vendor.bulk-vehicle-images.destroy', imageId));
        fetchBulkImages(); // Refresh the list
        // Consider adding a success flash message here if desired, e.g., by making a separate Inertia visit or local notification
    } catch (error) {
        console.error('Error deleting image:', error);
        alert('Failed to delete image. Please try again.'); // Simple feedback for now
    }
};

const copyImageUrl = (url) => {
    navigator.clipboard.writeText(url).then(() => {
        showCopyNotification.value = true;
        setTimeout(() => {
            showCopyNotification.value = false;
        }, 1500);
    }).catch(err => {
        console.error('Failed to copy URL: ', err);
        alert('Failed to copy URL. Please try again.');
    });
};

onMounted(() => {
    fetchBulkImages();
    fetchVehicleCategories();
    fetchVehicleFeatures();
});

</script>
