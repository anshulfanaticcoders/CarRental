<template>
    <MyProfileLayout>
        <div class="space-y-6">
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
            <div class="vr-phead">
                <div>
                    <span class="vr-eyebrow"><FileText /> {{ tt('vendorprofilepages', 'documents_eyebrow', 'Verification') }}</span>
                    <h2>{{ tt('vendorprofilepages', 'my_vendor_documents_header', 'Vendor Documents') }}</h2>
                    <p class="vr-sub">{{ tt('vendorprofilepages', 'my_vendor_documents_subtitle', 'Manage your vendor verification documents.') }}</p>
                </div>
                <div class="vr-phead-actions">
                    <span class="vr-chip" :class="document ? 'ok' : 'mut'">
                        <FileText class="w-3.5 h-3.5" />
                        {{ document ? tt('vendorprofilepages', 'documents_uploaded', 'Documents Uploaded') : tt('vendorprofilepages', 'no_documents', 'No Documents') }}
                    </span>
                    <Button v-if="document && document.vendor_profile?.status !== 'approved'" @click="openEditDialog()"
                        class="flex items-center justify-center gap-2" size="sm">
                        <Edit class="w-4 h-4" />
                        {{ _t('vendorprofilepages', 'edit_button') }}
                    </Button>
                </div>
            </div>

            <!-- Status Card -->
            <div v-if="document" class="doc-status">
                <div class="doc-status-top">
                    <div class="doc-status-ic"><Shield class="w-6 h-6" /></div>
                    <span class="vr-chip capitalize" :class="vrStatus(document.vendor_profile?.status)">{{ document.vendor_profile?.status || 'Unknown' }}</span>
                </div>
                <div class="text-center">
                    <p class="doc-status-title">{{ tt('vendorprofilepages', 'verification_status', 'Verification Status') }}</p>
                    <p class="doc-status-sub">{{ getStatusMessage(document.vendor_profile?.status) }}</p>
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
  
        <!-- Documents Section - Mobile Cards / Desktop Table -->
            <div v-if="document" class="vr-panel">
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
                                    <span class="vr-chip capitalize" :class="vrStatus(document.vendor_profile?.status)">{{ document.vendor_profile?.status || 'Unknown' }}</span>
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
                            <span class="vr-chip capitalize" :class="vrStatus(document.vendor_profile?.status)">{{ document.vendor_profile?.status || 'Unknown' }}</span>
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
            <div v-else class="vr-panel">
                <div class="vr-empty">
                    <div class="e-ic"><FileText /></div>
                    <h4>{{ tt('vendorprofilepages', 'no_vendor_documents_found_text', 'No vendor documents found') }}</h4>
                    <p>{{ tt('vendorprofilepages', 'no_vendor_documents_sub', 'Your vendor documents will appear here once uploaded.') }}</p>
                </div>
            </div>

      <!-- Company Information Card -->
            <div v-if="document && document.vendor_profile" class="vr-panel">
                <div class="vr-panel-head">
                    <h3><Building /> {{ _t('vendorprofilepages', 'company_information_header') }}</h3>
                </div>
                <div class="vr-panel-body">
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
  const tt = (group, key, fallback) => {
    const v = _t(group, key);
    return (!v || v === key) ? fallback : v;
  };
  const vrStatus = (status) => ({ approved: 'ok', pending: 'warn', rejected: 'bad' }[status] || 'mut');

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
.doc-status {
    background: linear-gradient(135deg, #f0f8fc, #ffffff);
    border: 1px solid rgba(21, 59, 79, 0.12);
    border-radius: 18px;
    padding: 24px;
    box-shadow: 0 2px 4px rgba(21, 59, 79, 0.06), 0 1px 2px rgba(21, 59, 79, 0.04);
}

.doc-status-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.doc-status-ic {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: grid;
    place-items: center;
    background: rgba(21, 59, 79, 0.1);
    color: #153b4f;
}

.doc-status-title {
    font-family: "Plus Jakarta Sans", sans-serif;
    font-size: 1.4rem;
    font-weight: 800;
    color: #0f172a;
}

.doc-status-sub {
    font-size: 0.86rem;
    color: #64748b;
    margin-top: 4px;
}

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
