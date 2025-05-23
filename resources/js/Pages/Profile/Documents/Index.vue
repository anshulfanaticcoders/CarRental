<template>
    <MyProfileLayout>
      <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem] max-[768px]:w-full max-[768px]:ml-0">
        <div class="flex items-center justify-between mt-[2rem]">
          <span class="text-[1.5rem] font-semibold max-[768px]:text-[1.2rem]">{{ _t('customerprofilepages', 'my_documents_header') }}</span>
          <Button @click="openUploadDialog" :disabled="document && document.verification_status === 'verified'">
            {{ document ? _t('customerprofilepages', 'edit_documents_button') : _t('customerprofilepages', 'upload_documents_button') }}
          </Button>
        </div>
  
        <!-- Upload/Edit Document Dialog -->
        <Dialog v-model:open="isDialogOpen">
          <DialogContent class="sm:max-w-[600px] h-[60vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>{{ document ? _t('customerprofilepages', 'edit_documents_dialog_title') : _t('customerprofilepages', 'upload_documents_dialog_title') }}</DialogTitle>
            </DialogHeader>
            <form @submit.prevent="submitDocuments">
              <div class="grid grid-cols-2 gap-4">
                <!-- Driving License Front -->
                <div class="flex flex-col gap-2">
                  <Label class="block text-sm font-medium">{{ _t('customerprofilepages', 'driving_license_front_label') }}</Label>
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
                    <p class="mt-2 text-sm text-gray-600">{{ _t('customerprofilepages', 'click_to_select_file') }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ _t('customerprofilepages', 'file_type_hint') }}</p>
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
                  <Label class="block text-sm font-medium">{{ _t('customerprofilepages', 'driving_license_back_label') }}</Label>
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
                    <p class="mt-2 text-sm text-gray-600">{{ _t('customerprofilepages', 'click_to_select_file') }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ _t('customerprofilepages', 'file_type_hint') }}</p>
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
                  <Label class="block text-sm font-medium">{{ _t('customerprofilepages', 'passport_front_label') }}</Label>
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
                    <p class="mt-2 text-sm text-gray-600">{{ _t('customerprofilepages', 'click_to_select_file') }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ _t('customerprofilepages', 'file_type_hint') }}</p>
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
                  <Label class="block text-sm font-medium">{{ _t('customerprofilepages', 'passport_back_label') }}</Label>
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
                    <p class="mt-2 text-sm text-gray-600">{{ _t('customerprofilepages', 'click_to_select_file') }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ _t('customerprofilepages', 'file_type_hint') }}</p>
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
                <div v-if="errors.files" class="text-red-500 text-sm text-center">{{ _t('customerprofilepages', 'error_all_files_required') }}</div>
              </div>
              <DialogFooter class="mt-4">
                <Button type="button" variant="outline" @click="isDialogOpen = false">{{ _t('customerprofilepages', 'dialog_cancel_button') }}</Button>
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
                    {{ document ? _t('customerprofilepages', 'updating_button_text') : _t('customerprofilepages', 'uploading_button_text') }}
                  </span>
                  <span v-else>{{ document ? _t('customerprofilepages', 'update_button_text') : _t('customerprofilepages', 'upload_button_text') }}</span>
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
                <TableHead>{{ _t('customerprofilepages', 'table_header_doc_type') }}</TableHead>
                <TableHead>{{ _t('customerprofilepages', 'table_header_doc_image') }}</TableHead>
                <TableHead>{{ _t('customerprofilepages', 'table_header_status') }}</TableHead>
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
                  <span v-else>{{ _t('customerprofilepages', 'no_file_uploaded_text') }}</span>
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
            <p>{{ _t('customerprofilepages', 'no_documents_uploaded_yet_text') }}</p>
          </div>
        </div>
      </div>
    </MyProfileLayout>
  </template>
  
  <script setup>
  import { ref, computed, getCurrentInstance } from 'vue';
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
  
  const { appContext } = getCurrentInstance();
  const _t = appContext.config.globalProperties._t;
  
  const document = ref(usePage().props.document);
  const isDialogOpen = ref(false);
  const isLoading = ref(false);
  const errors = ref({});
  
  // Initialize with empty objects, will be populated in openUploadDialog
  const filePreviews = ref({});
  const form = ref({});
  const filesToRemove = ref({}); // Tracks fields explicitly marked for removal
  
  const documentFields = [
    { key: 'driving_license_front', label: _t('customerprofilepages', 'driving_license_front_label') },
    { key: 'driving_license_back', label: _t('customerprofilepages', 'driving_license_back_label') },
    { key: 'passport_front', label: _t('customerprofilepages', 'passport_front_label') },
    { key: 'passport_back', label: _t('customerprofilepages', 'passport_back_label') },
  ];
  
  const requiredFields = ['driving_license_front', 'driving_license_back', 'passport_front', 'passport_back'];
  
  const openUploadDialog = () => {
    isDialogOpen.value = true;
    filesToRemove.value = {}; // Reset explicit removals

    // Initialize form and previews based on existing document or nulls
    const currentDoc = document.value;
    form.value = {
      driving_license_front: currentDoc?.driving_license_front || null,
      driving_license_back: currentDoc?.driving_license_back || null,
      passport_front: currentDoc?.passport_front || null,
      passport_back: currentDoc?.passport_back || null,
    };
    filePreviews.value = {
      driving_license_front: currentDoc?.driving_license_front || null,
      driving_license_back: currentDoc?.driving_license_back || null,
      passport_front: currentDoc?.passport_front || null,
      passport_back: currentDoc?.passport_back || null,
    };
     // If a field in form.value is a URL, it means it's an existing file, not a File object.
     // We need to ensure that if the user doesn't change it, it's not treated as a new File.
     // And if they remove it, `filesToRemove` will handle it.
     // If they select a new file, `handleFileChange` will replace the URL with a File object.
  };
  
  const handleFileChange = (field, event) => {
    const file = event.target.files[0];
    if (file) {
      form.value[field] = file; // This is now a File object
      filesToRemove.value[field] = false; // Not removing if a new file is chosen
      const reader = new FileReader();
      reader.onload = () => {
        filePreviews.value[field] = reader.result; // Update preview
      };
      reader.readAsDataURL(file);
    }
  };
  
  const removeFile = (field) => {
    // If there was an existing file (identified by its URL in the initial form.value state or document.value)
    // then mark it for removal.
    if (document.value && document.value[field]) {
        filesToRemove.value[field] = true;
    }
    form.value[field] = null; // Clear from form (will prevent it from being re-uploaded if it was a File object)
    filePreviews.value[field] = null; // Clear preview
  };
  
  const submitDocuments = () => {
    errors.value = {};
  
    // Validate required fields
    const allFilesSelected = document.value
      ? requiredFields.every((field) => form.value[field] || document.value[field])
      : requiredFields.every((field) => form.value[field]);
  
    if (!allFilesSelected) {
      errors.value.files = _t('customerprofilepages', 'error_all_files_required');
      return;
    }
  
    isLoading.value = true;
    const formData = new FormData();
    requiredFields.forEach((field) => {
      if (form.value[field] instanceof File) {
        formData.append(field, form.value[field]);
      } else if (filesToRemove.value[field]) {
        // Marked for explicit removal by user clicking "✕"
        formData.append(field, ''); // Signal removal to backend
      }
      // If form.value[field] is a string (URL of existing file not touched) or null (and not in filesToRemove),
      // do not append to formData. Backend will retain existing if field not present in request.
    });
  
    let submissionRoute = '/user/documents';
    let submissionMethod = 'post';
  
    if (document.value) {
      submissionRoute = `/user/documents/${document.value.id}`;
      // For FormData with file uploads, Laravel expects POST with _method spoofing for PATCH/PUT
      formData.append('_method', 'PATCH');
      // submissionMethod remains 'post' for Inertia to handle FormData correctly
    }
  
    router.post(submissionRoute, formData, { // Always use router.post for FormData
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
