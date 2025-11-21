<template>
    <MyProfileLayout>
        <div class="container mx-auto p-4 sm:p-6 space-y-6">
            <!-- Flash Message -->
            <div v-if="$page.props.flash.success" class="rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">
                {{ $page.props.flash.success }}
            </div>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 sm:gap-0">
                <h1 class="text-3xl font-bold tracking-tight">{{ _t('customerprofilepages', 'my_documents_header') }}</h1>
                <div class="flex flex-wrap items-center gap-2 sm:gap-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-700">
                        <FileText class="w-4 h-4 mr-1" />
                        {{ document ? 'Documents Uploaded' : 'No Documents' }}
                    </span>
                    <Button @click="openUploadDialog" :disabled="document && document.verification_status === 'verified'" class="flex items-center gap-2">
                        <Upload class="w-4 h-4" />
                        {{ document ? _t('customerprofilepages', 'edit_documents_button') : _t('customerprofilepages', 'upload_documents_button') }}
                    </Button>
                    <Button v-if="document" @click="openDeleteConfirmDialog" variant="destructive" :disabled="document.verification_status === 'verified'" class="flex items-center gap-2">
                        <Trash2 class="w-4 h-4" />
                        {{ _t('customerprofilepages', 'delete_all_documents_button') }}
                    </Button>
                </div>
            </div>

            <!-- Enhanced Status Card -->
            <div v-if="document" class="relative bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-blue-500 bg-opacity-20 rounded-lg">
                        <Shield class="w-6 h-6 text-blue-600" />
                    </div>
                    <Badge :variant="getStatusBadgeVariant(document.verification_status)" class="capitalize">
                        {{ document.verification_status }}
                    </Badge>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-blue-900">Verification Status</p>
                    <p class="text-sm text-blue-700 mt-1">{{ document.verification_status === 'verified' ? 'Your documents have been verified' : 'Your documents are under review' }}</p>
                </div>
            </div>
  
            <!-- Alert Dialog for Delete Confirmation -->
            <AlertDialog v-model:open="isDeleteConfirmOpen">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                        <AlertDialogDescription>
                            {{ _t('customerprofilepages', 'delete_documents_confirm_message') }}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel @click="isDeleteConfirmOpen = false">{{ _t('customerprofilepages', 'dialog_cancel_button') }}</AlertDialogCancel>
                        <AlertDialogAction @click="deleteDocuments" :disabled="isLoading" class="flex items-center gap-2">
                            <Loader2 v-if="isLoading" class="w-4 h-4 animate-spin" />
                            {{ _t('customerprofilepages', 'delete_button_text') }}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
  
            <!-- Upload/Edit Document Dialog -->
            <Dialog v-model:open="isDialogOpen">
                <DialogContent class="max-w-[90vw] sm:max-w-[700px] max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle class="flex items-center gap-2">
                            <Upload class="w-5 h-5" />
                            {{ document ? _t('customerprofilepages', 'edit_documents_dialog_title') : _t('customerprofilepages', 'upload_documents_dialog_title') }}
                        </DialogTitle>
                    </DialogHeader>
                    <form @submit.prevent="submitDocuments" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Driving License Front -->
                            <div class="space-y-2">
                                <Label class="text-sm font-medium">{{ _t('customerprofilepages', 'driving_license_front_label') }}</Label>
                                <div
                                    @click="$refs.drivingLicenseFrontInput.click()"
                                    class="cursor-pointer border-2 border-dashed border-gray-300 p-6 rounded-lg text-center hover:border-gray-400 transition-colors"
                                >
                                    <Upload class="w-10 h-10 mx-auto text-gray-400" />
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
                                <div v-if="filePreviews.driving_license_front || (document && document.driving_license_front)" class="relative w-full mt-2">
                                    <img
                                        :src="filePreviews.driving_license_front || document.driving_license_front"
                                        alt="Preview"
                                        class="w-full h-[120px] object-cover rounded-md border shadow-md"
                                    />
                                    <Button
                                        @click="removeFile('driving_license_front')"
                                        size="sm"
                                        variant="destructive"
                                        class="absolute top-2 right-2 w-6 h-6 p-0"
                                    >
                                        <X class="w-3 h-3" />
                                    </Button>
                                </div>
                            </div>
  
                            <!-- Driving License Back -->
                            <div class="space-y-2">
                                <Label class="text-sm font-medium">{{ _t('customerprofilepages', 'driving_license_back_label') }}</Label>
                                <div
                                    @click="$refs.drivingLicenseBackInput.click()"
                                    class="cursor-pointer border-2 border-dashed border-gray-300 p-6 rounded-lg text-center hover:border-gray-400 transition-colors"
                                >
                                    <Upload class="w-10 h-10 mx-auto text-gray-400" />
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
                                <div v-if="filePreviews.driving_license_back || (document && document.driving_license_back)" class="relative w-full mt-2">
                                    <img
                                        :src="filePreviews.driving_license_back || document.driving_license_back"
                                        alt="Preview"
                                        class="w-full h-[120px] object-cover rounded-md border shadow-md"
                                    />
                                    <Button
                                        @click="removeFile('driving_license_back')"
                                        size="sm"
                                        variant="destructive"
                                        class="absolute top-2 right-2 w-6 h-6 p-0"
                                    >
                                        <X class="w-3 h-3" />
                                    </Button>
                                </div>
                            </div>

                            <!-- Passport Front -->
                            <div class="space-y-2">
                                <Label class="text-sm font-medium">{{ _t('customerprofilepages', 'passport_front_label') }}</Label>
                                <div
                                    @click="$refs.passportFrontInput.click()"
                                    class="cursor-pointer border-2 border-dashed border-gray-300 p-6 rounded-lg text-center hover:border-gray-400 transition-colors"
                                >
                                    <Upload class="w-10 h-10 mx-auto text-gray-400" />
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
                                <div v-if="filePreviews.passport_front || (document && document.passport_front)" class="relative w-full mt-2">
                                    <img
                                        :src="filePreviews.passport_front || document.passport_front"
                                        alt="Preview"
                                        class="w-full h-[120px] object-cover rounded-md border shadow-md"
                                    />
                                    <Button
                                        @click="removeFile('passport_front')"
                                        size="sm"
                                        variant="destructive"
                                        class="absolute top-2 right-2 w-6 h-6 p-0"
                                    >
                                        <X class="w-3 h-3" />
                                    </Button>
                                </div>
                            </div>

                            <!-- Passport Back -->
                            <div class="space-y-2">
                                <Label class="text-sm font-medium">{{ _t('customerprofilepages', 'passport_back_label') }}</Label>
                                <div
                                    @click="$refs.passportBackInput.click()"
                                    class="cursor-pointer border-2 border-dashed border-gray-300 p-6 rounded-lg text-center hover:border-gray-400 transition-colors"
                                >
                                    <Upload class="w-10 h-10 mx-auto text-gray-400" />
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
                                <div v-if="filePreviews.passport_back || (document && document.passport_back)" class="relative w-full mt-2">
                                    <img
                                        :src="filePreviews.passport_back || document.passport_back"
                                        alt="Preview"
                                        class="w-full h-[120px] object-cover rounded-md border shadow-md"
                                    />
                                    <Button
                                        @click="removeFile('passport_back')"
                                        size="sm"
                                        variant="destructive"
                                        class="absolute top-2 right-2 w-6 h-6 p-0"
                                    >
                                        <X class="w-3 h-3" />
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Error Message -->
                        <div v-if="errors.files" class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">
                            {{ _t('customerprofilepages', 'error_all_files_required') }}
                        </div>

                        <DialogFooter class="flex gap-2">
                            <Button type="button" variant="outline" @click="isDialogOpen = false">
                                {{ _t('customerprofilepages', 'dialog_cancel_button') }}
                            </Button>
                            <Button type="submit" :disabled="isLoading" class="flex items-center gap-2">
                                <Loader2 v-if="isLoading" class="w-4 h-4 animate-spin" />
                                {{ document ? _t('customerprofilepages', 'update_button_text') : _t('customerprofilepages', 'upload_button_text') }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
  
        <!-- Enhanced Document Table -->
            <div v-if="document" class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <div class="overflow-x-auto max-w-full">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">{{ _t('customerprofilepages', 'table_header_doc_type') }}</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">{{ _t('customerprofilepages', 'table_header_doc_image') }}</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">{{ _t('customerprofilepages', 'table_header_status') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="field in documentFields" :key="field.key" class="hover:bg-muted/25 transition-colors">
                                <TableCell class="whitespace-nowrap px-4 py-3 font-medium">
                                    <div class="flex items-center gap-2">
                                        <FileText class="w-4 h-4 text-muted-foreground" />
                                        {{ field.label }}
                                    </div>
                                </TableCell>
                                <TableCell class="px-4 py-3">
                                    <div v-if="document[field.key]" class="relative group h-20 w-32 md:w-[150px]">
                                        <img
                                            :src="document[field.key]"
                                            :alt="field.label"
                                            class="h-20 w-32 md:w-[150px] object-cover rounded-md border shadow-sm cursor-pointer transition-all duration-200 hover:scale-105"
                                            @click="openImagePreview(document[field.key], field.label)"
                                        />
                                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 rounded-md transition-all duration-200 flex items-center justify-center pointer-events-none">
                                            <Eye class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200" />
                                        </div>
                                    </div>
                                    <div v-else class="flex items-center gap-2 text-muted-foreground">
                                        <FileX class="w-4 h-4" />
                                        <span class="text-sm">{{ _t('customerprofilepages', 'no_file_uploaded_text') }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-2 h-2 rounded-full"
                                            :class="{
                                                'bg-green-500': document.verification_status === 'verified',
                                                'bg-yellow-500': document.verification_status === 'pending',
                                                'bg-red-500': document.verification_status === 'rejected'
                                            }"
                                        ></div>
                                        <Badge :variant="getStatusBadgeVariant(document.verification_status)" class="capitalize">
                                            {{ document.verification_status }}
                                        </Badge>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="rounded-xl border bg-card p-12 text-center">
                <div class="flex flex-col items-center space-y-4">
                    <FileText class="w-16 h-16 text-muted-foreground" />
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold text-foreground">{{ _t('customerprofilepages', 'no_documents_uploaded_yet_text') }}</h3>
                        <p class="text-muted-foreground">Get started by uploading your verification documents.</p>
                    </div>
                    <Button @click="openUploadDialog" class="flex items-center gap-2">
                        <Upload class="w-4 h-4" />
                        {{ _t('customerprofilepages', 'upload_documents_button') }}
                    </Button>
                </div>
            </div>

            <!-- Image Preview Dialog -->
            <Dialog v-model:open="isImagePreviewOpen">
                <DialogContent class="sm:max-w-[600px]">
                    <DialogHeader>
                        <DialogTitle>{{ imagePreviewTitle }}</DialogTitle>
                    </DialogHeader>
                    <div class="flex justify-center">
                        <img
                            :src="imagePreviewUrl"
                            :alt="imagePreviewTitle"
                            class="max-w-full h-auto rounded-lg border shadow-md"
                        />
                    </div>
                    <DialogFooter>
                        <Button @click="isImagePreviewOpen = false">Close</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
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
  import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/Components/ui/dialog';
  import { Input } from '@/Components/ui/input';
  import Badge from '@/Components/ui/badge/Badge.vue';
  import { Button } from '@/Components/ui/button';
  import { Label } from '@/Components/ui/label';
  import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
  } from '@/Components/ui/alert-dialog';
  import {
    FileText,
    FileX,
    Upload,
    Shield,
    Trash2,
    Eye,
    Loader2,
    X,
  } from 'lucide-vue-next';
  
  const { appContext } = getCurrentInstance();
  const _t = appContext.config.globalProperties._t;
  
  const document = ref(usePage().props.document);
  const isDialogOpen = ref(false);
  const isDeleteConfirmOpen = ref(false);
  const isImagePreviewOpen = ref(false);
  const isLoading = ref(false);
  const errors = ref({});
  const imagePreviewUrl = ref('');
  const imagePreviewTitle = ref('');

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

  const openDeleteConfirmDialog = () => {
    isDeleteConfirmOpen.value = true;
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
  
    const locale = usePage().props.locale || 'en';
    let submissionRoute;
  
    if (document.value) {
      submissionRoute = route('user.documents.update.post', { locale, document: document.value.id });
    } else {
      submissionRoute = route('user.documents.store', { locale });
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

  const deleteDocuments = () => {
    if (!document.value) return;
    isLoading.value = true;
    const locale = usePage().props.locale || 'en';
    const submissionRoute = route('user.documents.destroy', { locale, document: document.value.id });

    router.delete(submissionRoute, {
        onSuccess: () => {
            isDeleteConfirmOpen.value = false;
            isLoading.value = false;
            document.value = null; // Clear the document data on successful deletion
        },
        onError: (err) => {
            errors.value = err;
            isLoading.value = false;
            isDeleteConfirmOpen.value = false;
        },
    });
  };
  
  const getStatusBadgeVariant = (status) => {
    switch (status) {
        case 'verified': return 'default';
        case 'pending': return 'secondary';
        case 'rejected': return 'destructive';
        default: return 'secondary';
    }
  };

  const openImagePreview = (url, title) => {
    imagePreviewUrl.value = url;
    imagePreviewTitle.value = title;
    isImagePreviewOpen.value = true;
  };

  // Clear flash message after 3 seconds
  const clearFlash = () => {
    setTimeout(() => {
        router.visit(window.location.pathname, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            data: { flash: null }
        });
    }, 3000);
  };

  // Call clearFlash when flash message exists
  if (usePage().props.flash?.success) {
    clearFlash();
  }
  </script>
