<template>
  <AuthenticatedHeaderLayout />
  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
        <!-- Success message if any -->
        <div v-if="successCount > 0" class="mb-6 bg-green-50 p-4 rounded-lg">
          <div class="flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500 mr-2" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span class="font-medium text-green-700">Success! {{ successCount }} vehicles were uploaded successfully.</span>
          </div>
        </div>
        
        <!-- Failed upload summary if any -->
        <div v-if="failedCount > 0" class="mb-6 bg-yellow-50 p-4 rounded-lg">
          <div class="flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-500 mr-2" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <span class="font-medium text-yellow-700">Note: {{ failedCount }} vehicles failed to upload. Check the errors below.</span>
          </div>
        </div>

        <div class="mb-8">
          <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Bulk Vehicle Upload
          </h2>
          <p class="text-gray-600 mt-2">
            Upload multiple vehicles at once using a CSV file.
          </p>
          <div class="mt-4">
            <Link
              :href="route('bulk.template.download')"
              class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
            >
              Download Template
            </Link>
          </div>
        </div>

        <div class="bg-gray-50 p-6 rounded-lg mb-6">
          <h3 class="font-semibold mb-2">CSV File Format Instructions</h3>
          <p class="mb-4">Please ensure your CSV file follows the required format:</p>
          <ul class="list-disc pl-5 space-y-1 text-sm">
            <li>All required fields must be completed</li>
            <li>For arrays like features, use JSON format: ["Feature 1", "Feature 2"]</li>
            <li>For payment methods, use JSON format: ["credit_card", "cash"]</li>
            <li>For plans and addons, use JSON format as shown in the template</li>
            <li>Times should be in 24-hour format (HH:MM)</li>
            <li>Dates should be in YYYY-MM-DD format</li>
            <li>Boolean values should be 0 (false) or 1 (true)</li>
            <li>Download the template for an example</li>
          </ul>
        </div>

        <div class="bg-gray-50 p-6 rounded-lg mb-6">
          <h3 class="font-semibold mb-2">Available Plans</h3>
          <ul class="list-disc pl-5 space-y-1 text-sm">
            <li v-for="plan in plans" :key="plan.id">
              ID: {{ plan.id }} - Type: {{ plan.plan_type }}
            </li>
          </ul>
          <h3 class="font-semibold mt-4 mb-2">Available Addons</h3>
          <ul class="list-disc pl-5 space-y-1 text-sm">
            <li v-for="addon in addons" :key="addon.id">
              ID: {{ addon.id }} - Name: {{ addon.extra_name }} (Type: {{ addon.extra_type }})
            </li>
          </ul>
        </div>

        <form @submit.prevent="submitForm" enctype="multipart/form-data">
          <div class="mb-6">
            <label for="csv_file" class="block text-sm font-medium text-gray-700">
              Upload CSV File
            </label>
            <input
              type="file"
              id="csv_file"
              ref="fileInput"
              @change="handleFileChange"
              accept=".csv, text/csv"
              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2"
            />
            <div v-if="form.errors.csv_file" class="text-red-500 text-sm mt-1">
              {{ form.errors.csv_file }}
            </div>
          </div>

          <div v-if="selectedFile" class="mb-6">
            <div class="bg-blue-50 p-4 rounded-lg">
              <div class="flex items-center">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-5 w-5 text-blue-500 mr-2"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                >
                  <path
                    fill-rule="evenodd"
                    d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                    clip-rule="evenodd"
                  />
                </svg>
                <span class="font-medium text-blue-700">{{ selectedFile.name }}</span>
              </div>
              <div class="text-sm text-blue-600 mt-1">
                Size: {{ formatFileSize(selectedFile.size) }}
              </div>
            </div>
          </div>

          <div class="flex justify-end">
            <button
              type="submit"
              class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
              :disabled="loading"
            >
              <svg
                v-if="loading"
                class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
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
              {{ loading ? 'Uploading...' : 'Upload Vehicles' }}
            </button>
          </div>
        </form>

        <!-- Error Display Section -->
        <div v-if="uploadErrors.length > 0" class="mt-8">
          <h3 class="font-semibold text-lg text-red-600 mb-2">Upload Errors</h3>
          <div class="bg-red-50 p-4 rounded-lg">
            <div v-for="(error, index) in uploadErrors" :key="index" class="mb-4">
              <h4 class="font-medium">Row {{ error.row }}: </h4>
              <ul class="list-disc pl-5 text-sm text-red-600">
                <template v-if="error.errors">
                  <li v-for="(fieldErrors, field) in error.errors" :key="field">
                    {{ field }}: {{ Array.isArray(fieldErrors) ? fieldErrors.join(', ') : fieldErrors }}
                  </li>
                </template>
                <li v-else-if="error.message">{{ error.message }}</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue'

// Form setup
const form = useForm({
  csv_file: null
})

// Refs for component state
const fileInput = ref(null)
const selectedFile = ref(null)
const loading = ref(false)
const uploadErrors = ref([])
const successCount = ref(0)
const failedCount = ref(0)
const plans = ref(usePage().props.plans || [])
const addons = ref(usePage().props.addons || [])

// Check for flash messages on mount
onMounted(() => {
  const flash = usePage().props.flash
  
  if (flash.success_count) {
    successCount.value = flash.success_count
  }
  
  if (flash.failed_count) {
    failedCount.value = flash.failed_count
  }
  
  if (flash.upload_errors && Array.isArray(flash.upload_errors)) {
    uploadErrors.value = flash.upload_errors
  }
})

// Handle file selection
const handleFileChange = (e) => {
  const file = e.target.files[0]
  if (file) {
    selectedFile.value = file
    form.csv_file = file
  }
}

// Format file size for display
const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)))
  return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i]
}

// Form submission
const submitForm = () => {
  if (!form.csv_file) {
    form.errors.csv_file = 'Please select a CSV file to upload'
    return
  }

  loading.value = true
  uploadErrors.value = []
  
  form.post(route('bulk.car_listing.store'), {
    preserveScroll: true,
    onSuccess: () => {
      loading.value = false
      selectedFile.value = null
      
      const flash = usePage().props.flash
      
      if (flash.success_count) {
        successCount.value = flash.success_count
      }
      
      if (flash.failed_count) {
        failedCount.value = flash.failed_count
      }
      
      if (flash.upload_errors && Array.isArray(flash.upload_errors)) {
        uploadErrors.value = flash.upload_errors
      }
      
      if (fileInput.value) {
        fileInput.value.value = ''
      }
    },
    onError: (errors) => {
      loading.value = false
      if (errors.csv_file) {
        form.errors.csv_file = errors.csv_file
      }
    }
  })
}
</script>