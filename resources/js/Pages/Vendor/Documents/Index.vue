<template>
    <MyProfileLayout>
        <div class="container mx-auto p-6 space-y-6">
            <!-- Flash Message -->
            <div v-if="$page.props.flash.success" class="rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">
                {{ $page.props.flash.success }}
            </div>

            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold tracking-tight">{{ _t('vendorprofilepages', 'my_vendor_documents_header') }}</h1>
                <div class="flex items-center gap-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-700">
                        <FileText class="w-4 h-4 mr-1" />
                        {{ document ? 'Documents Uploaded' : 'No Documents' }}
                    </span>
                    <Button
                        v-if="document && document.vendor_profile?.status !== 'approved'"
                        @click="openEditDialog()"
                        class="flex items-center gap-2"
                    >
                        <Edit class="w-4 h-4" />
                        {{ _t('vendorprofilepages', 'edit_button') }}
                    </Button>
                </div>
            </div>

            <!-- Status Card -->
            <div v-if="document" class="relative bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-blue-500 bg-opacity-20 rounded-lg">
                        <Shield class="w-6 h-6 text-blue-600" />
                    </div>
                    <Badge :variant="getStatusBadgeVariant(document.vendor_profile?.status)" class="capitalize">
                        {{ document.vendor_profile?.status || 'Unknown' }}
                    </Badge>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-blue-900">Verification Status</p>
                    <p class="text-sm text-blue-700 mt-1">{{ getStatusMessage(document.vendor_profile?.status) }}</p>
                </div>
            </div>

            <!-- Edit Dialog -->
            <Dialog v-model:open="isEditDialogOpen">
                <EditDocument :document="document" @close="isEditDialogOpen = false" />
            </Dialog>

            <!-- Document Preview Dialog -->
            <Dialog v-model:open="isViewDialogOpen">
                <DialogContent class="sm:max-w-[700px] max-h-[90vh] overflow-auto">
                    <DialogHeader>
                        <DialogTitle class="flex items-center gap-2">
                            <Eye class="w-5 h-5" />
                            {{ _t('vendorprofilepages', 'document_preview_dialog_title') }}
                        </DialogTitle>
                    </DialogHeader>
                    <div class="flex justify-center items-center p-6">
                        <img
                            :src="selectedDocumentPath"
                            :alt="_t('vendorprofilepages', 'document_preview_dialog_title')"
                            class="max-w-full max-h-[70vh] object-contain rounded-lg border shadow-md"
                        />
                    </div>
                    <DialogFooter>
                        <Button @click="isViewDialogOpen = false">Close</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
  
        <!-- Enhanced Documents Table -->
            <div v-if="document" class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <div class="overflow-x-auto max-w-full">
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
            </div>

            <!-- Empty State -->
            <div v-else class="rounded-xl border bg-card p-12 text-center">
                <div class="flex flex-col items-center space-y-4">
                    <FileText class="w-16 h-16 text-muted-foreground" />
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold text-foreground">{{ _t('vendorprofilepages', 'no_vendor_documents_found_text') }}</h3>
                        <p class="text-muted-foreground">Your vendor documents will appear here once uploaded.</p>
                    </div>
                </div>
            </div>
  
      <!-- Enhanced Company Information Card -->
            <div v-if="document && document.vendor_profile" class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <div class="bg-muted/50 px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold flex items-center gap-2">
                        <Building class="w-5 h-5" />
                        {{ _t('vendorprofilepages', 'company_information_header') }}
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                <Building class="w-4 h-4" />
                                {{ _t('vendorprofilepages', 'company_name_label') }}
                            </div>
                            <p class="text-base font-medium">{{ document.vendor_profile.company_name || 'N/A' }}</p>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                <Phone class="w-4 h-4" />
                                {{ _t('vendorprofilepages', 'company_phone_label') }}
                            </div>
                            <p class="text-base font-medium">{{ document.vendor_profile.company_phone_number || 'N/A' }}</p>
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <div class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                <Mail class="w-4 h-4" />
                                {{ _t('vendorprofilepages', 'company_email_label') }}
                            </div>
                            <p class="text-base font-medium">{{ document.vendor_profile.company_email || 'N/A' }}</p>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                <FileText class="w-4 h-4" />
                                {{ _t('vendorprofilepages', 'company_vat_label') }}
                            </div>
                            <p class="text-base font-medium">{{ document.vendor_profile.company_gst_number || 'N/A' }}</p>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                <MapPin class="w-4 h-4" />
                                {{ _t('vendorprofilepages', 'company_address_label') }}
                            </div>
                            <p class="text-base font-medium">{{ document.vendor_profile.company_address || 'N/A' }}</p>
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
