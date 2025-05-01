<template>
    <div class="max-w-2xl mx-auto p-4">
      <h2 class="text-2xl font-bold mb-4">Edit Documents</h2>
      <form @submit.prevent="submit" enctype="multipart/form-data">
        <div class="grid grid-cols-1 gap-5">
          <!-- Driving License Front -->
          <div class="flex flex-col gap-2">
            <label class="block text-sm font-medium">Driving License Front</label>
            <div
              @click="$refs.drivingLicenseFrontInput.click()"
              class="cursor-pointer border-2 border-dashed border-gray-400 p-4 rounded-lg text-center"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="w-10 h-10 mx-auto text-gray-400"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"
                />
              </svg>
              <p class="mt-2 text-sm text-gray-600">Click to select an image</p>
              <p class="text-xs text-gray-500 mt-1">JPG, PNG, or PDF up to 2MB</p>
            </div>
            <input
              type="file"
              ref="drivingLicenseFrontInput"
              class="hidden"
              accept="image/jpeg,image/png,application/pdf"
              @change="handleFileChange('driving_license_front', $event)"
            />
            <div v-if="filePreviews.driving_license_front || document.driving_license_front" class="relative w-[150px] mt-2">
              <img
                :src="filePreviews.driving_license_front || document.driving_license_front"
                alt="Preview"
                class="w-full h-[100px] object-cover rounded-md border shadow-md"
              />
              <button
                @click="removeFile('driving_license_front')"
                class="absolute top-1 right-1 bg-red-500 text-white w-5 h-5 rounded-full text-xs"
              >
                ✕
              </button>
            </div>
          </div>
  
          <!-- Driving License Back -->
          <div class="flex flex-col gap-2">
            <label class="block text-sm font-medium">Driving License Back</label>
            <div
              @click="$refs.drivingLicenseBackInput.click()"
              class="cursor-pointer border-2 border-dashed border-gray-400 p-4 rounded-lg text-center"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="w-10 h-10 mx-auto text-gray-400"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"
                />
              </svg>
              <p class="mt-2 text-sm text-gray-600">Click to select an image</p>
              <p class="text-xs text-gray-500 mt-1">JPG, PNG, or PDF up to 2MB</p>
            </div>
            <input
              type="file"
              ref="drivingLicenseBackInput"
              class="hidden"
              accept="image/jpeg,image/png,application/pdf"
              @change="handleFileChange('driving_license_back', $event)"
            />
            <div v-if="filePreviews.driving_license_back || document.driving_license_back" class="relative w-[150px] mt-2">
              <img
                :src="filePreviews.driving_license_back || document.driving_license_back"
                alt="Preview"
                class="w-full h-[100px] object-cover rounded-md border shadow-md"
              />
              <button
                @click="removeFile('driving_license_back')"
                class="absolute top-1 right-1 bg-red-500 text-white w-5 h-5 rounded-full text-xs"
              >
                ✕
              </button>
            </div>
          </div>
  
          <!-- Passport Front -->
          <div class="flex flex-col gap-2">
            <label class="block text-sm font-medium">Passport Front</label>
            <div
              @click="$refs.passportFrontInput.click()"
              class="cursor-pointer border-2 border-dashed border-gray-400 p-4 rounded-lg text-center"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="w-10 h-10 mx-auto text-gray-400"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"
                />
              </svg>
              <p class="mt-2 text-sm text-gray-600">Click to select an image</p>
              <p class="text-xs text-gray-500 mt-1">JPG, PNG, or PDF up to 2MB</p>
            </div>
            <input
              type="file"
              ref="passportFrontInput"
              class="hidden"
              accept="image/jpeg,image/png,application/pdf"
              @change="handleFileChange('passport_front', $event)"
            />
            <div v-if="filePreviews.passport_front || document.passport_front" class="relative w-[150px] mt-2">
              <img
                :src="filePreviews.passport_front || document.passport_front"
                alt="Preview"
                class="w-full h-[100px] object-cover rounded-md border shadow-md"
              />
              <button
                @click="removeFile('passport_front')"
                class="absolute top-1 right-1 bg-red-500 text-white w-5 h-5 rounded-full text-xs"
              >
                ✕
              </button>
            </div>
          </div>
  
          <!-- Passport Back -->
          <div class="flex flex-col gap-2">
            <label class="block text-sm font-medium">Passport Back</label>
            <div
              @click="$refs.passportBackInput.click()"
              class="cursor-pointer border-2 border-dashed border-gray-400 p-4 rounded-lg text-center"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="w-10 h-10 mx-auto text-gray-400"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"
                />
              </svg>
              <p class="mt-2 text-sm text-gray-600">Click to select an image</p>
              <p class="text-xs text-gray-500 mt-1">JPG, PNG, or PDF up to 2MB</p>
            </div>
            <input
              type="file"
              ref="passportBackInput"
              class="hidden"
              accept="image/jpeg,image/png,application/pdf"
              @change="handleFileChange('passport_back', $event)"
            />
            <div v-if="filePreviews.passport_back || document.passport_back" class="relative w-[150px] mt-2">
              <img
                :src="filePreviews.passport_back || document.passport_back"
                alt="Preview"
                class="w-full h-[100px] object-cover rounded-md border shadow-md"
              />
              <button
                @click="removeFile('passport_back')"
                class="absolute top-1 right-1 bg-red-500 text-white w-5 h-5 rounded-full text-xs"
              >
                ✕
              </button>
            </div>
          </div>
  
          <!-- Error Message -->
          <div v-if="errors.files" class="text-red-500 text-sm text-center">{{ errors.files }}</div>
  
          <!-- Buttons -->
          <div class="flex justify-end gap-4 mt-4">
            <button
              type="button"
              class="px-4 py-2 bg-gray-200 rounded"
              @click="$router.go(-1)"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="px-4 py-2 bg-blue-600 text-white rounded"
              :disabled="isLoading"
            >
              <span v-if="isLoading" class="flex items-center">
                <svg
                  class="animate-spin h-5 w-5 mr-2"
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
                Updating...
              </span>
              <span v-else>Update</span>
            </button>
          </div>
        </div>
      </form>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted } from 'vue';
  import { router } from '@inertiajs/vue3';
  
  defineProps({
    document: Object,
  });
  
  const isLoading = ref(false);
  const errors = ref({});
  const filePreviews = ref({
    driving_license_front: null,
    driving_license_back: null,
    passport_front: null,
    passport_back: null,
  });
  
  const form = ref({
    driving_license_front: null,
    driving_license_back: null,
    passport_front: null,
    passport_back: null,
  });
  
  const requiredFiles = [
    'driving_license_front',
    'driving_license_back',
    'passport_front',
    'passport_back',
  ];
  
  const handleFileChange = (field, event) => {
    const file = event.target.files[0];
    if (file) {
      form.value[field] = file;
      const reader = new FileReader();
      reader.onload = () => {
        filePreviews.value[field] = reader.result;
      };
      reader.readAsDataURL(file);
    }
  };
  
  const removeFile = (field) => {
    form.value[field] = null;
    filePreviews.value[field] = null;
  };
  
  const submit = () => {
    errors.value = {};
  
    // Validate that all required files are uploaded (or existing files are present)
    const allFilesSelected = requiredFiles.every(
      (field) => form.value[field] !== null || props.document[field]
    );
    if (!allFilesSelected) {
      errors.value.files = 'Please upload all required documents.';
      return;
    }
  
    isLoading.value = true;
    const formData = new FormData();
    requiredFiles.forEach((field) => {
      if (form.value[field]) {
        formData.append(field, form.value[field]);
      }
    });
  
    router.post(`/user/documents/${props.document.id}`, formData, {
      onSuccess: () => {
        isLoading.value = false;
      },
      onError: (err) => {
        errors.value = err;
        isLoading.value = false;
      },
    });
  };
  </script>
  
  <style scoped>
  .animate-spin {
    animation: spin 1s linear infinite;
  }
  
  @keyframes spin {
    0% {
      transform: rotate(0deg);
    }
    100% {
      transform: rotate(360deg);
    }
  }
  </style>