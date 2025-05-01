<template>
    <MyProfileLayout>
      <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem] max-[768px]:w-full max-[768px]:ml-0">
        <div class="flex items-center justify-between mt-[2rem]">
          <span class="text-[1.5rem] font-semibold max-[768px]:text-[1.2rem]">My Documents</span>
          <Button @click="openUploadDialog" :disabled="document && document.verification_status === 'verified'">
            {{ document ? 'Edit Documents' : 'Upload Documents' }}
          </Button>
        </div>
  
        <!-- Upload/Edit Document Dialog -->
        <Dialog v-model:open="isDialogOpen">
          <DialogContent class="sm:max-w-[600px] h-[60vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>{{ document ? 'Edit Documents' : 'Upload Documents' }}</DialogTitle>
            </DialogHeader>
            <form @submit.prevent="submitDocuments">
              <div class="grid grid-cols-2 gap-4">
                <!-- Driving License Front -->
                <div class="flex flex-col gap-2">
                  <Label class="block text-sm font-medium">Driving License Front</Label>
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
                    <p class="mt-2 text-sm text-gray-600">Click to select a file</p>
                    <p class="text-xs text-gray-500 mt-1">JPG, PNG, or PDF up to 2MB</p>
                  </div>
                  <input
                    type="file"
                    ref="drivingLicenseFrontInput"
                    class="hidden"
                    accept=".jpg,.jpeg,.png,.pdf"
                    @change="handleFileChange('driving_license_front', $event)"
                  />
                  <div v-if="filePreviews.driving_license_front || (document && document.driving_license_front)" class="relative w-[150px] mt-2">
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
                  <Label class="block text-sm font-medium">Driving License Back</Label>
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
                    <p class="mt-2 text-sm text-gray-600">Click to select a file</p>
                    <p class="text-xs text-gray-500 mt-1">JPG, PNG, or PDF up to 2MB</p>
                  </div>
                  <input
                    type="file"
                    ref="drivingLicenseBackInput"
                    class="hidden"
                    accept=".jpg,.jpeg,.png,.pdf"
                    @change="handleFileChange('driving_license_back', $event)"
                  />
                  <div v-if="filePreviews.driving_license_back || (document && document.driving_license_back)" class="relative w-[150px] mt-2">
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
                  <Label class="block text-sm font-medium">Passport Front</Label>
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
                    <p class="mt-2 text-sm text-gray-600">Click to select a file</p>
                    <p class="text-xs text-gray-500 mt-1">JPG, PNG, or PDF up to 2MB</p>
                  </div>
                  <input
                    type="file"
                    ref="passportFrontInput"
                    class="hidden"
                    accept=".jpg,.jpeg,.png,.pdf"
                    @change="handleFileChange('passport_front', $event)"
                  />
                  <div v-if="filePreviews.passport_front || (document && document.passport_front)" class="relative w-[150px] mt-2">
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
                  <Label class="block text-sm font-medium">Passport Back</Label>
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
                    <p class="mt-2 text-sm text-gray-600">Click to select a file</p>
                    <p class="text-xs text-gray-500 mt-1">JPG, PNG, or PDF up to 2MB</p>
                  </div>
                  <input
                    type="file"
                    ref="passportBackInput"
                    class="hidden"
                    accept=".jpg,.jpeg,.png,.pdf"
                    @change="handleFileChange('passport_back', $event)"
                  />
                  <div v-if="filePreviews.passport_back || (document && document.passport_back)" class="relative w-[150px] mt-2">
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
              </div>
              <DialogFooter class="mt-4">
                <Button type="button" variant="outline" @click="isDialogOpen = false">Cancel</Button>
                <Button type="submit" :disabled="isLoading">
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
                    {{ document ? 'Updating...' : 'Uploading...' }}
                  </span>
                  <span v-else>{{ document ? 'Update' : 'Upload' }}</span>
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
  
        <!-- Document Display -->
        <div class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D]">
          <Table v-if="document">
            <TableHeader>
              <TableRow>
                <TableHead>Document Type</TableHead>
                <TableHead>Document Image</TableHead>
                <TableHead>Status</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="field in documentFields" :key="field.key">
                <TableCell>{{ field.label }}</TableCell>
                <TableCell>
                  <img
                    v-if="document[field.key]"
                    :src="document[field.key]"
                    :alt="field.label"
                    class="h-20 w-[150px] object-cover max-[768px]:w-[60px] max-[768px]:h-10"
                  />
                  <span v-else>No file uploaded</span>
                </TableCell>
                <TableCell>
                  <Badge :variant="getStatusBadgeVariant(document.verification_status)">
                    {{ document.verification_status }}
                  </Badge>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
          <div v-else class="p-4 text-center">
            <p>No documents uploaded yet.</p>
          </div>
        </div>
      </div>
    </MyProfileLayout>
  </template>
  
  <script setup>
  import { ref, computed } from 'vue';
  import { usePage, router } from '@inertiajs/vue3';
  import {
    Table,
    TableHeader,
    TableRow,
    TableHead,
    TableBody,
    TableCell,
  } from '@/Components/ui/table';
  import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
  import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
  import { Input } from '@/Components/ui/input';
  import Badge from '@/Components/ui/badge/Badge.vue';
  import { Button } from '@/Components/ui/button';
  import { Label } from '@/Components/ui/label';
  
  const document = ref(usePage().props.document);
  const isDialogOpen = ref(false);
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
  
  const documentFields = [
    { key: 'driving_license_front', label: 'Driving License Front' },
    { key: 'driving_license_back', label: 'Driving License Back' },
    { key: 'passport_front', label: 'Passport Front' },
    { key: 'passport_back', label: 'Passport Back' },
  ];
  
  const requiredFields = ['driving_license_front', 'driving_license_back', 'passport_front', 'passport_back'];
  
  const openUploadDialog = () => {
    isDialogOpen.value = true;
    // Reset form and previews
    form.value = {
      driving_license_front: null,
      driving_license_back: null,
      passport_front: null,
      passport_back: null,
    };
    filePreviews.value = {
      driving_license_front: null,
      driving_license_back: null,
      passport_front: null,
      passport_back: null,
    };
  };
  
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
  
  const submitDocuments = () => {
    errors.value = {};
  
    // Validate required fields
    const allFilesSelected = document.value
      ? requiredFields.every((field) => form.value[field] || document.value[field])
      : requiredFields.every((field) => form.value[field]);
  
    if (!allFilesSelected) {
      errors.value.files = 'Please upload all required documents.';
      return;
    }
  
    isLoading.value = true;
    const formData = new FormData();
    requiredFields.forEach((field) => {
      if (form.value[field]) {
        formData.append(field, form.value[field]);
      }
    });
  
    const route = document.value ? `/user/documents/${document.value.id}` : '/user/documents';
    const method = document.value ? 'post' : 'post';
  
    router[method](route, formData, {
      onSuccess: (page) => {
        isDialogOpen.value = false;
        isLoading.value = false;
        document.value = page.props.document;
        // Reset form
        form.value = {
          driving_license_front: null,
          driving_license_back: null,
          passport_front: null,
          passport_back: null,
        };
        filePreviews.value = {
          driving_license_front: null,
          driving_license_back: null,
          passport_front: null,
          passport_back: null,
        };
      },
      onError: (err) => {
        errors.value = err;
        isLoading.value = false;
      },
    });
  };
  
  const getStatusBadgeVariant = (status) => {
    return status === 'verified' ? 'default' : status === 'pending' ? 'secondary' : 'destructive';
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
  
  @media screen and (max-width: 768px) {
    th,
    td {
      font-size: 0.75rem;
    }
  }
  </style>