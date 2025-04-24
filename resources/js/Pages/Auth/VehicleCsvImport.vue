<template>
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <!-- Page Header -->
            <div class="mb-6">
              <h2 class="text-xl font-semibold text-gray-900">Import Vehicles from CSV</h2>
              <p class="mt-1 text-sm text-gray-500">
                Upload a CSV file with vehicle data to import multiple vehicles at once.
              </p>
            </div>
  
            <!-- Success Message -->
            <div v-if="flash.message" :class="`mb-6 p-4 rounded-md ${messageClass}`">
              <p class="text-sm font-medium">{{ flash.message }}</p>
              <ul v-if="flash.errors && flash.errors.length" class="mt-2 list-disc list-inside">
                <li v-for="(error, index) in flash.errors" :key="index" class="text-sm">
                  {{ error }}
                </li>
              </ul>
            </div>
  
            <!-- Sample Template Download and Guide -->
            <div class="mb-6 p-4 bg-gray-50 rounded-md">
              <h3 class="text-md font-medium text-gray-700">Need a template?</h3>
              <p class="mt-1 text-sm text-gray-500">
                Download our sample CSV template to ensure your data is properly formatted.
              </p>
              <div class="mt-3">
                <a
                  :href="route('vehicles.csv.sample')"
                  class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                  Download Sample Template
                </a>
              </div>
              
              <!-- Reference information -->
              <div class="mt-4 border-t pt-4">
                <h4 class="font-medium text-gray-700">Available Plans</h4>
                <div class="mt-2 overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                      <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                      </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                      <tr v-for="plan in plans" :key="plan.id">
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">{{ plan.id }}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">{{ plan.name }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                
                <h4 class="font-medium text-gray-700 mt-4">Available Addons</h4>
                <div class="mt-2 overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                      <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                      </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                      <tr v-for="addon in addons" :key="addon.id">
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">{{ addon.id }}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">{{ addon.extra_name }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                
                <div class="mt-4">
                  <h4 class="font-medium text-gray-700">CSV Format Notes:</h4>
                  <ul class="mt-2 list-disc list-inside text-sm text-gray-600">
                    <li>For plans, use comma-separated values (e.g., "1,2,3")</li>
                    <li>For features, use JSON arrays as text (e.g., '["Feature 1", "Feature 2"]')</li>
                    <li>Make sure your plan_ids match available plans</li>
                    <li>Make sure your addon_ids match available addons</li>
                  </ul>
                </div>
              </div>
            </div>
  
            <!-- CSV Upload Form -->
            <form @submit.prevent="submitForm" class="space-y-6">
              <div>
                <label for="csv_file" class="block text-sm font-medium text-gray-700">
                  CSV File
                </label>
                <div
                  class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md"
                  :class="{ 'border-red-300': errors.csv_file }"
                >
                  <div class="space-y-1 text-center">
                    <svg
                      v-if="!selectedFile"
                      class="mx-auto h-12 w-12 text-gray-400"
                      stroke="currentColor"
                      fill="none"
                      viewBox="0 0 48 48"
                      aria-hidden="true"
                    >
                      <path
                        d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                      />
                    </svg>
                    <div v-else class="flex items-center justify-center">
                      <svg
                        class="h-6 w-6 text-green-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        ></path>
                      </svg>
                      <span class="ml-2 text-sm text-gray-600">{{ selectedFile.name }}</span>
                    </div>
                    <div class="flex text-sm text-gray-600">
                      <label
                        for="file-upload"
                        class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500"
                      >
                        <span>Upload a file</span>
                        <input
                          id="file-upload"
                          name="file-upload"
                          type="file"
                          class="sr-only"
                          @change="handleFileUpload"
                          accept=".csv"
                        />
                      </label>
                      <p class="pl-1">or drag and drop</p>
                    </div>
                    <p class="text-xs text-gray-500">CSV up to 2MB</p>
                  </div>
                </div>
                <p v-if="errors.csv_file" class="mt-2 text-sm text-red-600">
                  {{ errors.csv_file }}
                </p>
              </div>
  
              <!-- Submit Button -->
              <div class="flex justify-end">
                <button
                  type="submit"
                  class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                  :disabled="isSubmitting"
                >
                  <svg
                    v-if="isSubmitting"
                    class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                  >
                    <circle
                      class="opacity-25"
                      cx="12"
                      cy="12"
                      r="10"
                      stroke="currentColor"
                      stroke-width="4"
                    ></circle>
                    <path
                      class="opacity-75"
                      fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                  </svg>
                  {{ isSubmitting ? 'Importing...' : 'Import Vehicles' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, computed } from 'vue';
  import { useForm, usePage } from '@inertiajs/vue3';
  
  const props = defineProps({
    plans: {
      type: Array,
      default: () => []
    },
    addons: {
      type: Array,
      default: () => []
    }
  });
  
  const selectedFile = ref(null);
  const isSubmitting = ref(false);
  const flash = computed(() => usePage().props.flash || {});
  
  const messageClass = computed(() => {
    if (flash.value.type === 'success') return 'bg-green-50 text-green-800';
    if (flash.value.type === 'warning') return 'bg-yellow-50 text-yellow-800';
    if (flash.value.type === 'error') return 'bg-red-50 text-red-800';
    return 'bg-blue-50 text-blue-800';
  });
  
  const form = useForm({
    csv_file: null
  });
  
  const errors = computed(() => form.errors);
  
  const handleFileUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
      selectedFile.value = file;
      form.csv_file = file;
    }
  };
  
  const submitForm = () => {
    isSubmitting.value = true;
    form.post(route('vehicles.csv.import'), {
      onFinish: () => {
        isSubmitting.value = false;
        if (!form.hasErrors) {
          selectedFile.value = null;
          form.reset();
        }
      }
    });
  };
  </script>