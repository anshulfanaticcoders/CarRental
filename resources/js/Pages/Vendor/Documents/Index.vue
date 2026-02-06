<template>
    <MyProfileLayout>
        <div class="w-full mx-auto px-3 sm:px-4 lg:px-6 py-4 sm:py-6 space-y-4 sm:space-y-6">
            <!-- Flash Message -->
            <div v-if="$page.props.flash.success" class="rounded-lg border border-green-200 bg-green-50 p-3 sm:p-4 text-green-800 text-sm sm:text-base">
                <div class="flex items-center justify-between">
                    <span>{{ $page.props.flash.success }}</span>
                    <button @click="clearFlashManually" class="ml-4 text-green-600 hover:text-green-800">
                        <X class="w-4 h-4 sm:w-5 sm:h-5" />
                    </button>
                </div>
            </div>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        {{ _t('vendorprofilepages', 'my_vendor_documents_header') }}
                    </h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400 hidden sm:block">
                        Manage your vendor verification documents
                    </p>
                </div>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-4">
                    <span class="inline-flex items-center px-2 py-1 sm:px-2.5 sm:py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs sm:text-sm font-medium justify-center">
                        <FileText class="w-3 h-3 sm:w-4 sm:h-4 mr-1" />
                        <span class="hidden sm:inline">{{ document ? 'Documents Uploaded' : 'No Documents' }}</span>
                        <span class="sm:hidden">{{ document ? 'Uploaded' : 'None' }}</span>
                    </span>
                    <Button
                        v-if="document && document.vendor_profile?.status !== 'approved'"
                        @click="openEditDialog()"
                        class="flex items-center justify-center gap-2 w-full sm:w-auto"
                        size="sm"
                    >
                        <Edit class="w-3 h-3 sm:w-4 sm:h-4" />
                        <span class="hidden sm:inline">{{ _t('vendorprofilepages', 'edit_button') }}</span>
                        <span class="sm:hidden">Edit</span>
                    </Button>
                </div>
            </div>

            <!-- Status Card -->
            <div v-if="document" class="relative bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-4 sm:p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02] min-h-[120px] sm:min-h-[140px]">
                <div class="flex items-center justify-between mb-3 sm:mb-4">
                    <div class="p-2 sm:p-3 bg-blue-500 bg-opacity-20 rounded-lg">
                        <Shield class="w-5 h-5 sm:w-6 sm:h-6 text-blue-600" />
                    </div>
                    <Badge :variant="getStatusBadgeVariant(document.vendor_profile?.status)" class="capitalize text-xs px-2 py-1">
                        {{ document.vendor_profile?.status || 'Unknown' }}
                    </Badge>
                </div>
                <div class="text-center">
                    <p class="text-lg sm:text-xl sm:text-2xl font-bold text-blue-900">Verification Status</p>
                    <p class="text-xs sm:text-sm text-blue-700 mt-1 leading-relaxed">{{ getStatusMessage(document.vendor_profile?.status) }}</p>
                </div>
            </div>

            <!-- Edit Dialog -->
            <Dialog v-model:open="isEditDialogOpen">
                <EditDocument :document="document" @close="isEditDialogOpen = false" />
            </Dialog>

            <!-- Document Preview Dialog -->
            <Dialog v-model:open="isViewDialogOpen">
                <DialogContent class="w-[95vw] sm:max-w-[700px] max-h-[95vh] sm:max-h-[90vh] overflow-auto mx-auto">
                    <DialogHeader class="px-3 sm:px-6 pt-4 sm:pt-6 pb-2 sm:pb-4">
                        <DialogTitle class="flex items-center gap-2 text-base sm:text-lg">
                            <Eye class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" />
                            <span class="truncate">{{ _t('vendorprofilepages', 'document_preview_dialog_title') }}</span>
                        </DialogTitle>
                    </DialogHeader>
                    <div class="flex justify-center items-center p-3 sm:p-6">
                        <img
                            :src="selectedDocumentPath"
                            :alt="_t('vendorprofilepages', 'document_preview_dialog_title')"
                            class="max-w-full max-h-[60vh] sm:max-h-[70vh] w-auto h-auto object-contain rounded-lg border shadow-md"
                        />
                    </div>
                    <DialogFooter class="px-3 sm:px-6 pb-4 sm:pb-6 pt-2">
                        <Button @click="isViewDialogOpen = false" class="w-full sm:w-auto">Close</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
  
        <!-- Enhanced Documents Section - Mobile Cards / Desktop Table -->
            <div v-if="document" class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <!-- Desktop Table View -->
                <div class="hidden lg:block overflow-x-auto max-w-full">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">{{ _t('vendorprofilepages', 'passport_front_table_header') }}</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">{{ _t('vendorprofilepages', 'passport_back_table_header') }}</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">{{ _t('vendorprofilepages', 'status_table_header') }}</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold text-right">{{ _t('vendorprofilepages', 'actions_table_header') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow class="hover:bg-muted/25 transition-colors">
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div v-if="document.passport_front" class="space-y-3">
                                        <div class="relative group h-20 w-[150px]">
                                            <img
                                                :src="document.passport_front"
                                                :alt="_t('vendorprofilepages', 'passport_front_table_header')"
                                                class="h-20 w-[150px] object-cover rounded-md border shadow-sm cursor-pointer transition-all duration-200 hover:scale-105"
                                                @click="viewDocument(document.passport_front)"
                                            />
                                            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 rounded-md transition-all duration-200 flex items-center justify-center pointer-events-none">
                                                <Eye class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200" />
                                            </div>
                                        </div>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            @click="viewDocument(document.passport_front)"
                                            class="w-[150px] flex items-center gap-1"
                                        >
                                            <Eye class="w-3 h-3" />
                                            {{ _t('vendorprofilepages', 'view_button') }}
                                        </Button>
                                    </div>
                                    <div v-else class="flex items-center gap-2 text-muted-foreground">
                                        <FileX class="w-4 h-4" />
                                        <span class="text-sm">{{ _t('vendorprofilepages', 'not_uploaded_text') }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div v-if="document.passport_back" class="space-y-3">
                                        <div class="relative group h-20 w-[150px]">
                                            <img
                                                :src="document.passport_back"
                                                :alt="_t('vendorprofilepages', 'passport_back_table_header')"
                                                class="h-20 w-[150px] object-cover rounded-md border shadow-sm cursor-pointer transition-all duration-200 hover:scale-105"
                                                @click="viewDocument(document.passport_back)"
                                            />
                                            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 rounded-md transition-all duration-200 flex items-center justify-center pointer-events-none">
                                                <Eye class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200" />
                                            </div>
                                        </div>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            @click="viewDocument(document.passport_back)"
                                            class="w-[150px] flex items-center gap-1"
                                        >
                                            <Eye class="w-3 h-3" />
                                            {{ _t('vendorprofilepages', 'view_button') }}
                                        </Button>
                                    </div>
                                    <div v-else class="flex items-center gap-2 text-muted-foreground">
                                        <FileX class="w-4 h-4" />
                                        <span class="text-sm">{{ _t('vendorprofilepages', 'not_uploaded_text') }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-2 h-2 rounded-full"
                                            :class="{
                                                'bg-green-500': document.vendor_profile?.status === 'approved',
                                                'bg-yellow-500': document.vendor_profile?.status === 'pending',
                                                'bg-red-500': document.vendor_profile?.status === 'rejected'
                                            }"
                                        ></div>
                                        <Badge :variant="getStatusBadgeVariant(document.vendor_profile?.status)" class="capitalize">
                                            {{ document.vendor_profile?.status || 'Unknown' }}
                                        </Badge>
                                    </div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <Button
                                            v-if="document.vendor_profile?.status !== 'approved'"
                                            size="sm"
                                            variant="outline"
                                            @click="openEditDialog()"
                                            class="flex items-center gap-1"
                                        >
                                            <Edit class="w-3 h-3" />
                                            {{ _t('vendorprofilepages', 'edit_button') }}
                                        </Button>
                                        <div v-else class="flex items-center gap-2 text-sm text-muted-foreground">
                                            <Lock class="w-3 h-3" />
                                            {{ _t('vendorprofilepages', 'documents_cannot_be_edited_text') }}
                                        </div>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Mobile Card View -->
                <div class="lg:hidden p-4 sm:p-6 space-y-4">
                    <!-- Passport Front Card -->
                    <div class="border rounded-lg p-4 bg-muted/20 space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-sm sm:text-base flex items-center gap-2">
                                <FileText class="w-4 h-4" />
                                {{ _t('vendorprofilepages', 'passport_front_table_header') }}
                            </h3>
                            <div
                                class="w-2 h-2 rounded-full"
                                :class="{
                                    'bg-green-500': document.vendor_profile?.status === 'approved',
                                    'bg-yellow-500': document.vendor_profile?.status === 'pending',
                                    'bg-red-500': document.vendor_profile?.status === 'rejected'
                                }"
                            ></div>
                        </div>
                        <div v-if="document.passport_front" class="space-y-3">
                            <div class="relative group mx-auto w-full max-w-[200px] sm:max-w-[250px]">
                                <img
                                    :src="document.passport_front"
                                    :alt="_t('vendorprofilepages', 'passport_front_table_header')"
                                    class="w-full h-32 sm:h-40 object-cover rounded-md border shadow-sm cursor-pointer transition-all duration-200 hover:scale-105"
                                    @click="viewDocument(document.passport_front)"
                                />
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 rounded-md transition-all duration-200 flex items-center justify-center pointer-events-none">
                                    <Eye class="w-6 h-6 sm:w-8 sm:h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200" />
                                </div>
                            </div>
                            <Button
                                size="sm"
                                variant="outline"
                                @click="viewDocument(document.passport_front)"
                                class="w-full flex items-center justify-center gap-2"
                            >
                                <Eye class="w-4 h-4" />
                                {{ _t('vendorprofilepages', 'view_button') }}
                            </Button>
                        </div>
                        <div v-else class="flex items-center justify-center gap-2 text-muted-foreground py-4">
                            <FileX class="w-4 h-4 sm:w-5 sm:h-5" />
                            <span class="text-sm">{{ _t('vendorprofilepages', 'not_uploaded_text') }}</span>
                        </div>
                    </div>

                    <!-- Passport Back Card -->
                    <div class="border rounded-lg p-4 bg-muted/20 space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-sm sm:text-base flex items-center gap-2">
                                <FileText class="w-4 h-4" />
                                {{ _t('vendorprofilepages', 'passport_back_table_header') }}
                            </h3>
                            <Badge :variant="getStatusBadgeVariant(document.vendor_profile?.status)" class="capitalize text-xs">
                                {{ document.vendor_profile?.status || 'Unknown' }}
                            </Badge>
                        </div>
                        <div v-if="document.passport_back" class="space-y-3">
                            <div class="relative group mx-auto w-full max-w-[200px] sm:max-w-[250px]">
                                <img
                                    :src="document.passport_back"
                                    :alt="_t('vendorprofilepages', 'passport_back_table_header')"
                                    class="w-full h-32 sm:h-40 object-cover rounded-md border shadow-sm cursor-pointer transition-all duration-200 hover:scale-105"
                                    @click="viewDocument(document.passport_back)"
                                />
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 rounded-md transition-all duration-200 flex items-center justify-center pointer-events-none">
                                    <Eye class="w-6 h-6 sm:w-8 sm:h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200" />
                                </div>
                            </div>
                            <Button
                                size="sm"
                                variant="outline"
                                @click="viewDocument(document.passport_back)"
                                class="w-full flex items-center justify-center gap-2"
                            >
                                <Eye class="w-4 h-4" />
                                {{ _t('vendorprofilepages', 'view_button') }}
                            </Button>
                        </div>
                        <div v-else class="flex items-center justify-center gap-2 text-muted-foreground py-4">
                            <FileX class="w-4 h-4 sm:w-5 sm:h-5" />
                            <span class="text-sm">{{ _t('vendorprofilepages', 'not_uploaded_text') }}</span>
                        </div>
                    </div>

                    <!-- Actions Card -->
                    <div class="border rounded-lg p-4 bg-muted/20 space-y-3">
                        <h3 class="font-semibold text-sm sm:text-base flex items-center gap-2">
                            <Settings class="w-4 h-4" />
                            {{ _t('vendorprofilepages', 'actions_table_header') }}
                        </h3>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <Button
                                v-if="document.vendor_profile?.status !== 'approved'"
                                size="sm"
                                variant="outline"
                                @click="openEditDialog()"
                                class="flex items-center justify-center gap-2 flex-1"
                            >
                                <Edit class="w-4 h-4" />
                                {{ _t('vendorprofilepages', 'edit_button') }}
                            </Button>
                            <div v-else class="flex items-center justify-center gap-2 text-sm text-muted-foreground py-2 flex-1">
                                <Lock class="w-4 h-4" />
                                <span class="text-center text-xs">{{ _t('vendorprofilepages', 'documents_cannot_be_edited_text') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="rounded-xl border bg-card p-6 sm:p-12 text-center">
                <div class="flex flex-col items-center space-y-4">
                    <FileText class="w-12 h-12 sm:w-16 sm:h-16 text-muted-foreground" />
                    <div class="space-y-2 max-w-md">
                        <h3 class="text-lg sm:text-xl font-semibold text-foreground">{{ _t('vendorprofilepages', 'no_vendor_documents_found_text') }}</h3>
                        <p class="text-sm sm:text-base text-muted-foreground">Your vendor documents will appear here once uploaded.</p>
                    </div>
                </div>
            </div>

      <!-- Enhanced Company Information Card -->
            <div v-if="document && document.vendor_profile" class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <div class="bg-muted/50 px-4 py-3 sm:px-6 sm:py-4 border-b">
                    <h2 class="text-base sm:text-lg font-semibold flex items-center gap-2">
                        <Building class="w-4 h-4 sm:w-5 sm:h-5" />
                        {{ _t('vendorprofilepages', 'company_information_header') }}
                    </h2>
                </div>
                <div class="p-4 sm:p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-xs sm:text-sm font-medium text-muted-foreground">
                                <Building class="w-3 h-3 sm:w-4 sm:h-4" />
                                {{ _t('vendorprofilepages', 'company_name_label') }}
                            </div>
                            <p class="text-sm sm:text-base font-medium break-words">{{ document.vendor_profile.company_name || 'N/A' }}</p>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-xs sm:text-sm font-medium text-muted-foreground">
                                <Phone class="w-3 h-3 sm:w-4 sm:h-4" />
                                {{ _t('vendorprofilepages', 'company_phone_label') }}
                            </div>
                            <p class="text-sm sm:text-base font-medium break-words">{{ document.vendor_profile.company_phone_number || 'N/A' }}</p>
                        </div>

                        <div class="space-y-2 sm:col-span-2">
                            <div class="flex items-center gap-2 text-xs sm:text-sm font-medium text-muted-foreground">
                                <Mail class="w-3 h-3 sm:w-4 sm:h-4" />
                                {{ _t('vendorprofilepages', 'company_email_label') }}
                            </div>
                            <p class="text-sm sm:text-base font-medium break-words">{{ document.vendor_profile.company_email || 'N/A' }}</p>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-xs sm:text-sm font-medium text-muted-foreground">
                                <FileText class="w-3 h-3 sm:w-4 sm:h-4" />
                                {{ _t('vendorprofilepages', 'company_vat_label') }}
                            </div>
                            <p class="text-sm sm:text-base font-medium break-words">{{ document.vendor_profile.company_gst_number || 'N/A' }}</p>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-xs sm:text-sm font-medium text-muted-foreground">
                                <MapPin class="w-3 h-3 sm:w-4 sm:h-4" />
                                {{ _t('vendorprofilepages', 'company_address_label') }}
                            </div>
                            <p class="text-sm sm:text-base font-medium break-words">{{ document.vendor_profile.company_address || 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MyProfileLayout>
</template>
  
  <script setup>
  import { ref, getCurrentInstance } from 'vue';
  import { usePage, router } from '@inertiajs/vue3';
  import Table from '@/Components/ui/table/Table.vue';
  import TableHeader from '@/Components/ui/table/TableHeader.vue';
  import TableRow from '@/Components/ui/table/TableRow.vue';
  import TableHead from '@/Components/ui/table/TableHead.vue';
  import TableBody from '@/Components/ui/table/TableBody.vue';
  import TableCell from '@/Components/ui/table/TableCell.vue';
  import Button from '@/Components/ui/button/Button.vue';
  import Badge from '@/Components/ui/badge/Badge.vue';
  import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
  import EditDocument from '@/Pages/Vendor/Documents/Edit.vue';
  import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
  import {
    FileText,
    FileX,
    Eye,
    Edit,
    Shield,
    Lock,
    Building,
    Phone,
    Mail,
    MapPin,
    X,
    Settings,
  } from 'lucide-vue-next';
  
  const { appContext } = getCurrentInstance();
  const _t = appContext.config.globalProperties._t;
  
  const props = defineProps({
    document: Object,
  });
  
  const isEditDialogOpen = ref(false);
  const isViewDialogOpen = ref(false);
  const selectedDocumentPath = ref('');
  
  const openEditDialog = () => {
    if (props.document?.vendor_profile?.status === 'approved') {
      return; // Prevent opening dialog if status is approved
    }
    isEditDialogOpen.value = true;
  };
  
  const viewDocument = (path) => {
    selectedDocumentPath.value = path;
    isViewDialogOpen.value = true;
  };
  
  const getStatusBadgeVariant = (status) => {
    switch (status) {
        case 'approved':
            return 'default';
        case 'pending':
            return 'secondary';
        case 'rejected':
            return 'destructive';
        default:
            return 'outline';
    }
  };

  const getStatusMessage = (status) => {
    switch (status) {
        case 'approved':
            return 'Your documents have been approved and verified';
        case 'pending':
            return 'Your documents are currently under review';
        case 'rejected':
            return 'Your documents have been rejected. Please update them.';
        default:
            return 'Document status is unknown';
    }
  };

  // Clear flash message manually
  const clearFlashManually = () => {
    router.visit(window.location.pathname, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        data: { flash: null }
    });
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

<style scoped>
/* Mobile-specific adjustments */
@media (max-width: 640px) {
    .document-card {
        touch-action: manipulation;
    }

    .document-image {
        touch-action: manipulation;
        user-select: none;
        -webkit-user-select: none;
        -webkit-touch-callout: none;
    }

    /* Ensure buttons are properly sized for touch */
    button {
        min-height: 44px;
        min-width: 44px;
    }
}

/* Tablet-specific adjustments */
@media (min-width: 641px) and (max-width: 1024px) {
    .document-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Ensure images scale properly on all devices */
.document-image {
    max-width: 100%;
    height: auto;
    object-fit: cover;
}

/* Custom scrollbar for document preview */
.document-preview {
    scrollbar-width: thin;
    scrollbar-color: rgba(0, 0, 0, 0.3) transparent;
}

.document-preview::-webkit-scrollbar {
    width: 6px;
}

.document-preview::-webkit-scrollbar-track {
    background: transparent;
}

.document-preview::-webkit-scrollbar-thumb {
    background-color: rgba(0, 0, 0, 0.3);
    border-radius: 3px;
}

.document-preview::-webkit-scrollbar-thumb:hover {
    background-color: rgba(0, 0, 0, 0.5);
}

/* Responsive text handling */
.text-responsive {
    font-size: clamp(0.875rem, 2.5vw, 1rem);
    line-height: 1.5;
}

/* Mobile card hover effects */
@media (hover: none) and (pointer: coarse) {
    .document-card {
        transition: transform 0.2s ease;
    }

    .document-card:active {
        transform: scale(0.98);
    }
}

/* Dialog responsiveness */
@media (max-width: 640px) {
    .dialog-content {
        margin: 1rem;
        max-height: calc(100vh - 2rem);
    }
}

/* Company information card improvements */
.company-info-grid {
    display: grid;
    gap: 1rem;
}

@media (min-width: 640px) {
    .company-info-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Status badge improvements */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    white-space: nowrap;
}
</style>
