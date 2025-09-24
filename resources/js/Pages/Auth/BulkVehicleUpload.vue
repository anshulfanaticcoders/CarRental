<template>
    <Head title="Bulk Vehicle Upload" />
    <AuthenticatedHeaderLayout/>

    <div v-if="isSubmitting" class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-white/80 backdrop-blur-sm">
        <img :src="loaderVariant" alt="Processing..." class="h-20 w-20" />
        <p class="mt-2 text-lg font-semibold text-[#153b4f]">Processing your file...</p>
    </div>

    <transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0">
        <div v-if="showCopyNotification" class="fixed bottom-5 right-5 z-50 bg-green-600 text-white py-2 px-4 rounded-lg shadow-xl text-center">
            <p class="text-base font-semibold">✓ Copied to clipboard!</p>
        </div>
    </transition>

    <div class="full-w-container py-12 bg-slate-50">
        <div class="mx-auto sm:px-6 lg:px-8">
            <div class="space-y-8">

                <div class="text-center">
                    <h1 class="text-3xl md:text-4xl font-bold text-[#153b4f]">Bulk Vehicle Uploader</h1>
                    <p class="mt-2 text-base md:text-lg text-gray-600">Follow the steps below to efficiently upload multiple vehicles.</p>
                </div>

                <div v-if="$page.props.flash && $page.props.flash.message" class="mb-4">
                     <div :class="`p-4 rounded-md shadow-sm flex items-start ${flashMessageClass}`">
                        <div class="flex-shrink-0">
                           <svg v-if="$page.props.flash.type === 'success'" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                           <svg v-else class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.21 3.03-1.742 3.03H4.42c-1.532 0-2.492-1.696-1.742-3.03l5.58-9.92zM10 13a1 1 0 110-2 1 1 0 010 2zm-1-8a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd" /></svg>
                        </div>
                        <div class="ml-3">
                           <p class="font-medium" v-html="$page.props.flash.message"></p>
                           <ul v-if="$page.props.flash.failed_images_details && $page.props.flash.failed_images_details.length > 0" class="list-disc list-inside mt-1 text-sm">
                               <li v-for="(detail, index) in $page.props.flash.failed_images_details" :key="`img-err-${index}`">{{ detail }}</li>
                           </ul>
                           <ul v-if="$page.props.flash.csv_errors && $page.props.flash.csv_errors.length > 0" class="list-disc list-inside mt-2 text-sm">
                               <li v-for="(error, index) in $page.props.flash.csv_errors" :key="`csv-err-${index}`">
                                   Row {{ error.row }}: Column '{{ error.column }}' - {{ error.message }}
                               </li>
                           </ul>
                        </div>
                     </div>
                </div>


                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
                    <div class="bg-white p-6 border border-gray-200 rounded-lg shadow-sm space-y-6">
                        <div class="flex items-center gap-4">
                            <div class="bg-[#153b4f] text-white rounded-full h-10 w-10 flex items-center justify-center font-bold text-xl">1</div>
                            <h3 class="text-xl md:text-2xl font-semibold text-gray-800">Upload Vehicle Images</h3>
                        </div>
                        
                        <p class="text-gray-600">First, upload all your vehicle images here <span class="text-red-500 font-bold">(Each image size should be less than 2MB).</span> After uploading, you can copy their URLs to use in your CSV file.</p>
                        
                        <form @submit.prevent="uploadImage" class="space-y-4">
                            <div>
                                <InputLabel for="vehicle_images" value="Add New Images (up to 50)" class="mb-2" />
                                <div @dragover.prevent="onDragOver" @dragleave.prevent="onDragLeave" @drop.prevent="onDrop"
                                     :class="['border-2 border-dashed rounded-lg p-6 text-center transition-colors', isDragging ? 'border-[#153b4f] bg-blue-50' : 'border-gray-300']">
                                    <label for="vehicle_images" class="cursor-pointer">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true"><path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                        <p class="mt-1 text-sm text-gray-600">Drag & drop files here or <span class="font-semibold text-[#153b4f]">click to browse</span></p>
                                        <input type="file" id="vehicle_images" @change="handleImageFileChange" class="sr-only" accept="image/*" multiple />
                                    </label>
                                </div>
                                <div v-if="selectedImageFiles.length > 0" class="mt-2 text-sm text-gray-500">
                                    {{ selectedImageFiles.length }} file(s) selected.
                                </div>
                                <InputError class="mt-1" :message="imageUploadForm.errors.images" />
                                <div v-for="(errorArray, key) in imageUploadForm.errors" :key="key">
                                    <template v-if="key.startsWith('images.')">
                                        <InputError v-for="(msg, i) in errorArray" :key="`${key}-${i}`" class="mt-1" :message="`File ${parseInt(key.split('.')[1]) + 1}: ${msg}`" />
                                    </template>
                                </div>
                            </div>
                            <PrimaryButton :class="{ 'opacity-25': imageUploadForm.processing }" :disabled="imageUploadForm.processing || selectedImageFiles.length === 0">
                                Upload Selected Images
                            </PrimaryButton>
                            <div v-if="imageUploadForm.progress" class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-[#153b4f] h-2.5 rounded-full" :style="{ width: imageUploadForm.progress.percentage + '%' }"></div>
                            </div>
                        </form>

                        <div class="space-y-4">
                            <div class="flex justify-between items-center border-t border-gray-200 pt-4">
                                <h4 class="text-lg md:text-xl font-semibold text-gray-700">Your Uploaded Images</h4>
                                <div v-if="bulkImages.length > 0" class="flex items-center gap-4">
                                    <button @click="toggleSelectionMode" class="inline-flex items-center text-sm font-medium text-[#153b4f] hover:underline">
                                        <svg v-if="!selectionMode" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                        {{ selectionMode ? 'Cancel' : 'Select' }}
                                    </button>
                                    <button v-if="selectionMode" @click="deleteSelectedImages" class="inline-flex items-center text-sm font-medium text-red-600 hover:underline disabled:opacity-50 disabled:cursor-not-allowed" :disabled="selectedImageIds.length === 0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        Delete ({{ selectedImageIds.length }})
                                    </button>
                                </div>
                            </div>
                            <div v-if="loadingImages" class="text-center py-10 text-gray-500">Loading images...</div>
                            <div v-else-if="bulkImages.length === 0" class="text-center py-10 border-2 border-dashed rounded-lg text-gray-500">
                                No images have been uploaded yet.
                            </div>
                            <div v-else class="h-96 overflow-y-auto border rounded-md p-4 pr-2 custom-scrollbar">
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                                    <div v-for="image in bulkImages" :key="image.id" class="relative group" @click="selectionMode && toggleImageSelection(image.id)">
                                        <div :class="['rounded-lg overflow-hidden border transition-all duration-200', {'ring-4 ring-offset-2 ring-[#153b4f]': selectedImageIds.includes(image.id), 'cursor-pointer': selectionMode}]">
                                            <img :src="image.url" :alt="image.original_name" class="w-full aspect-square object-cover transition-transform group-hover:scale-105">
                                        </div>
                                        <div v-if="selectionMode" class="absolute inset-0 flex items-center justify-center bg-black/40 rounded-lg">
                                            <div class="h-8 w-8 rounded-full flex items-center justify-center" :class="selectedImageIds.includes(image.id) ? 'bg-[#153b4f]' : 'bg-white/50 border'">
                                                 <svg v-if="selectedImageIds.includes(image.id)" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                            </div>
                                        </div>
                                        <div v-if="!selectionMode" class="absolute bottom-2 left-2 right-2 flex justify-center transition-opacity">
                                            <button @click.stop="copyImageUrl(image.url)" class="text-xs bg-green-600 hover:bg-green-700 text-white py-1 px-3 rounded-full shadow-lg">Copy URL</button>
                                        </div>
                                         <button v-if="!selectionMode" @click.stop="deleteImage(image.id)" class="absolute top-2 right-2 text-xs bg-red-600 hover:bg-red-700 text-white h-6 w-6 rounded-full flex items-center justify-center transition-all transform group-hover:scale-110">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 border border-gray-200 rounded-lg shadow-sm space-y-6">
                        <div class="flex items-center gap-4">
                            <div class="bg-[#153b4f] text-white rounded-full h-10 w-10 flex items-center justify-center font-bold text-xl">2</div>
                            <h3 class="text-xl md:text-2xl font-semibold text-gray-800">Upload CSV File</h3>
                        </div>
                        
                        <p class="text-gray-600">Once your CSV is prepared with the correct image URLs and data, upload it here to create your vehicle listings.</p>

                        <form @submit.prevent="submitCsvForm" class="space-y-4">
                            <div>
                                <InputLabel for="csv_file" value="Vehicle Data File" class="mb-2" />
                                <input type="file" id="csv_file" @change="handleCsvFileChange" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-[#153b4f] hover:file:bg-blue-100" accept=".csv,.txt" required />
                                <InputError class="mt-2" :message="csvForm.errors.csv_file" />
                            </div>

                            <div v-if="csvForm.progress" class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-[#153b4f] h-2.5 rounded-full" :style="{ width: csvForm.progress.percentage + '%' }"></div>
                            </div>

                            <div class="flex items-center justify-between mt-4">
                               <a :href="route('vehicles.bulk-upload.template', { locale: usePage().props.locale })" class="inline-flex items-center text-sm font-medium text-[#153b4f] hover:underline">
                                    <svg class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                    Download CSV Template
                                </a>
                                <PrimaryButton :class="{ 'opacity-25': csvForm.processing }" :disabled="csvForm.processing || !csvForm.csv_file">
                                    Upload & Process Vehicles
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="bg-white p-6 border border-gray-200 rounded-lg shadow-sm">
                    <h3 class="text-xl md:text-2xl font-semibold text-gray-800 mb-4">Reference Guide</h3>
                    <div class="space-y-2">
                        <details class="group p-4 rounded-lg bg-slate-50">
                            <summary class="flex justify-between items-center font-medium cursor-pointer list-none">
                                <span class="text-base md:text-lg text-[#153b4f]">Important Instructions</span>
                                <span class="transition group-open:rotate-180">
                                    <svg fill="none" height="24" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24" width="24"><path d="M6 9l6 6 6-6"></path></svg>
                                </span>
                            </summary>
                            <div class="text-neutral-600 mt-3 group-open:animate-fadeIn">
                               <ul class="list-disc list-inside text-sm text-gray-700 space-y-2 pl-2">
                                    <li>For fields requiring a Yes/No or True/False answer (e.g., 'is_available'), please use <strong>1 for Yes/True</strong> and <strong>0 for No/False</strong>.</li>
                                    <li>Each vehicle can have a maximum of <strong>two pricing plans</strong>. Ensure your CSV reflects this.</li>
                                    <li><strong>Vehicle Images:</strong> Upload images in Step 1, copy the URL, and paste it into the 'image_url' column in your CSV.</li>
                                    <li>Ensure all data in the CSV matches the formats and IDs provided in the sections below. Errors will be reported if data is incorrect.</li>
                                </ul>
                            </div>
                        </details>
                        <details class="group p-4 rounded-lg bg-slate-50">
                            <summary class="flex justify-between items-center font-medium cursor-pointer list-none">
                                <span class="text-base md:text-lg text-[#153b4f]">Available Vehicle Categories</span>
                                <span class="transition group-open:rotate-180">
                                    <svg fill="none" height="24" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24" width="24"><path d="M6 9l6 6 6-6"></path></svg>
                                </span>
                            </summary>
                            <div class="text-neutral-600 mt-3 group-open:animate-fadeIn">
                               <p class="text-sm text-gray-700 mb-3">Use the following IDs for the 'vehicle_category_id' column in your CSV.</p>
                                <div v-if="loadingCategories" class="text-sm text-gray-500">Loading categories...</div>
                                <div v-else-if="vehicleCategories.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 text-sm">
                                    <div v-for="category in vehicleCategories" :key="category.id" class="p-2 bg-white border rounded">
                                        <strong>{{ category.name }}</strong>: ID <code>{{ category.id }}</code>
                                    </div>
                                </div>
                                <div v-else class="text-sm text-red-500">Could not load categories. Please set them up in the admin panel.</div>
                            </div>
                        </details>
                        <details class="group p-4 rounded-lg bg-slate-50">
                            <summary class="flex justify-between items-center font-medium cursor-pointer list-none">
                                <span class="text-base md:text-lg text-[#153b4f]">Available Vehicle Features</span>
                                <span class="transition group-open:rotate-180">
                                    <svg fill="none" height="24" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24" width="24"><path d="M6 9l6 6 6-6"></path></svg>
                                </span>
                            </summary>
                            <div class="text-neutral-600 mt-3 group-open:animate-fadeIn">
                                <p class="text-sm text-gray-700 mb-3">Use these exact feature names in your CSV columns ('feature_1', 'feature_2', etc.).</p>
                                <div v-if="loadingFeatures" class="text-sm text-gray-500">Loading features...</div>
                                <div v-else-if="Object.keys(groupedFeatures).length > 0">
                                    <div v-for="(features, categoryName) in groupedFeatures" :key="categoryName" class="mb-3">
                                        <h4 class="text-base md:text-lg font-semibold text-gray-700 mb-1">{{ categoryName }}</h4>
                                        <div class="flex flex-wrap gap-2 pl-4">
                                           <span v-for="feature in features" :key="feature.id" class="bg-gray-200 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                                {{ feature.feature_name }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                 <div v-else class="text-sm text-red-500">Could not load features. Please set them up in the admin panel.</div>
                            </div>
                        </details>
                         <details class="group p-4 rounded-lg bg-slate-50">
                            <summary class="flex justify-between items-center font-medium cursor-pointer list-none">
                                <span class="text-base md:text-lg text-[#153b4f]">Pricing Plans & Extras Guidelines</span>
                                <span class="transition group-open:rotate-180">
                                    <svg fill="none" height="24" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24" width="24"><path d="M6 9l6 6 6-6"></path></svg>
                                </span>
                            </summary>
                            <div class="text-neutral-600 mt-3 group-open:animate-fadeIn space-y-4">
                                <div class="prose prose-sm max-w-none">
                                    <h4>Pricing Plans</h4>
                                    <p>You can add up to <strong>two pricing plans</strong> per vehicle using the `plan1_*` and `plan2_*` columns in your CSV.</p>
                                    <h4>Extras</h4>
                                    <p>Extras (e.g., GPS, Child Seat) must be defined in the admin panel first. Use the corresponding Extra ID in the `extra1_id`, `extra2_id`, etc., columns.</p>
                                </div>
                            </div>
                        </details>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<style>
/* Custom scrollbar for a polished look */
.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #aaa;
}
/* Simple animation for accordion content */
.group-open\:animate-fadeIn {
    animation: fadeIn 0.5s ease-in-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>


<script setup>
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import loaderVariant from '../../../assets/loader-variant.svg';

const page = usePage();

// --- STATE MANAGEMENT ---
const vehicleCategories = ref([]);
const allFeatures = ref([]);
const bulkImages = ref([]);

// More granular loading states for better UX
const loadingCategories = ref(true);
const loadingFeatures = ref(true);
const loadingImages = ref(true);
const isSubmitting = ref(false); // For full-screen overlay on critical actions

// UI State
const showCopyNotification = ref(false);
const selectionMode = ref(false);
const selectedImageIds = ref([]);
const selectedImageFiles = ref([]);
const isDragging = ref(false); // For file dropzone UI

// --- FORMS ---
const csvForm = useForm({
    csv_file: null,
});

const imageUploadForm = useForm({
    images: [],
});


// --- COMPUTED PROPERTIES ---
const flashMessageClass = computed(() => {
    const type = page.props.flash?.type || 'error';
    switch (type) {
        case 'success': return 'bg-green-100 text-green-800';
        case 'warning': return 'bg-yellow-100 text-yellow-800';
        default: return 'bg-red-100 text-red-800';
    }
});

const groupedFeatures = computed(() => {
    // ... (logic is sound, no changes needed)
    const groups = {};
    allFeatures.value.forEach(feature => {
        const categoryName = feature.category ? feature.category.name : 'General Features';
        if (!groups[categoryName]) {
            groups[categoryName] = [];
        }
        groups[categoryName].push(feature);
    });
    for (const categoryName in groups) {
        groups[categoryName].sort((a, b) => a.feature_name.localeCompare(b.feature_name));
    }
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

// --- DATA FETCHING ---
const fetchInitialData = async () => {
    // Reset states
    loadingCategories.value = true;
    loadingFeatures.value = true;
    loadingImages.value = true;

    const locale = page.props.locale;
    const cacheBust = `_t=${new Date().getTime()}`;

    // Fetch all initial data concurrently
    try {
        const [categoriesRes, featuresRes, imagesRes] = await Promise.all([
            axios.get(route('api.vehicle-categories.index', { locale })),
            axios.get(route('api.vehicle-features.index', { locale })),
            axios.get(route('vendor.bulk-vehicle-images.index', { locale, [cacheBust]:'' }))
        ]);
        vehicleCategories.value = categoriesRes.data;
        allFeatures.value = featuresRes.data;
        bulkImages.value = imagesRes.data;
    } catch (error) {
        console.error('Failed to fetch initial page data:', error);
        // Optionally set a general page error message
    } finally {
        loadingCategories.value = false;
        loadingFeatures.value = false;
        loadingImages.value = false;
    }
};

// --- CSV FORM LOGIC ---
const handleCsvFileChange = (event) => {
    csvForm.csv_file = event.target.files[0];
};

const submitCsvForm = () => {
    isSubmitting.value = true;
    csvForm.post(route('vehicles.bulk-upload.store', { locale: page.props.locale }), {
        preserveScroll: true,
        onSuccess: () => {
            csvForm.reset('csv_file');
            document.getElementById('csv_file').value = '';
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};

// --- IMAGE MANAGEMENT LOGIC ---

// Drag and Drop Handlers
const onDragOver = () => isDragging.value = true;
const onDragLeave = () => isDragging.value = false;
const onDrop = (event) => {
    isDragging.value = false;
    const files = event.dataTransfer.files;
    document.getElementById('vehicle_images').files = files; // Assign files to the input
    handleImageFileChange({ target: { files } }); // Trigger the change handler
};

const handleImageFileChange = (event) => {
    const files = Array.from(event.target.files);
    if (files.length > 50) {
        alert("You can only select up to 50 images at a time.");
        selectedImageFiles.value = [];
        imageUploadForm.images = [];
        document.getElementById('vehicle_images').value = ''; // Reset file input
        return;
    }
    selectedImageFiles.value = files;
    imageUploadForm.images = selectedImageFiles.value;
    imageUploadForm.clearErrors();
};

const uploadImage = () => {
    if (selectedImageFiles.value.length === 0) return;

    imageUploadForm.post(route('vendor.bulk-vehicle-images.store', { locale: page.props.locale }), {
        preserveScroll: true,
        onSuccess: () => {
            fetchInitialData(); // Refresh everything to ensure consistency
            selectedImageFiles.value = [];
            imageUploadForm.reset('images');
            document.getElementById('vehicle_images').value = '';
        },
    });
};

const deleteImage = async (imageId) => {
    if (!confirm('Are you sure you want to delete this image? This cannot be undone.')) return;
    try {
        await axios.delete(route('vendor.bulk-vehicle-images.destroy', { locale: page.props.locale, 'image': imageId }));
        bulkImages.value = bulkImages.value.filter(img => img.id !== imageId); // Optimistic UI update
    } catch (error) {
        console.error('Error deleting image:', error);
        alert('Failed to delete image. Please try again.');
    }
};

const deleteSelectedImages = async () => {
    const count = selectedImageIds.value.length;
    if (count === 0) return;
    if (!confirm(`Are you sure you want to delete the ${count} selected images? This cannot be undone.`)) return;

    isSubmitting.value = true;
    try {
        await axios.post(route('vendor.bulk-vehicle-images.bulk-destroy', { locale: page.props.locale }), { ids: selectedImageIds.value });
        await fetchInitialData(); // Full refresh after bulk action
        selectionMode.value = false;
        selectedImageIds.value = [];
    } catch (error) {
        console.error('Error deleting selected images:', error);
        alert('Failed to delete selected images. Please try again.');
    } finally {
        isSubmitting.value = false;
    }
};

const copyImageUrl = (url) => {
    navigator.clipboard.writeText(url).then(() => {
        showCopyNotification.value = true;
        setTimeout(() => { showCopyNotification.value = false; }, 2000);
    }).catch(err => {
        console.error('Failed to copy URL: ', err);
        alert('Failed to copy URL.');
    });
};

const toggleSelectionMode = () => {
    selectionMode.value = !selectionMode.value;
    if (!selectionMode.value) {
        selectedImageIds.value = [];
    }
};

const toggleImageSelection = (imageId) => {
    const index = selectedImageIds.value.indexOf(imageId);
    if (index > -1) {
        selectedImageIds.value.splice(index, 1);
    } else {
        selectedImageIds.value.push(imageId);
    }
};

// --- LIFECYCLE HOOK ---
onMounted(() => {
    fetchInitialData();
});
</script>
