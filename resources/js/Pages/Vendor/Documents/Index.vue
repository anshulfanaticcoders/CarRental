<template>
    <MyProfileLayout>
      <div class="flex flex-col gap-4 w-[95%] max-[768px]:w-full max-[768px]:ml-0">
        <p
          class="text-[1.75rem] font-bold text-gray-800 bg-customLightPrimaryColor p-4 rounded-[12px] mb-[1rem] max-[768px]:text-[1.2rem]"
        >
          {{ _t('vendorprofilepages', 'my_vendor_documents_header') }}
        </p>
  
        <Dialog v-model:open="isEditDialogOpen">
          <EditDocument :document="document" @close="isEditDialogOpen = false" />
        </Dialog>
  
        <Dialog v-model:open="isViewDialogOpen">
          <DialogContent class="max-w-[90vw] max-h-[90vh] overflow-auto">
            <DialogHeader>
              <DialogTitle>{{ _t('vendorprofilepages', 'document_preview_dialog_title') }}</DialogTitle>
            </DialogHeader>
            <div class="flex justify-center items-center p-4">
              <img
                :src="selectedDocumentPath"
                :alt="_t('vendorprofilepages', 'document_preview_dialog_title')"
                class="max-w-full max-h-[70vh] object-contain rounded-md border"
              />
            </div>
          </DialogContent>
        </Dialog>
  
        <div class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D]">
          <div v-if="document">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead class="text-nowrap">{{ _t('vendorprofilepages', 'passport_front_table_header') }}</TableHead>
                  <TableHead class="text-nowrap">{{ _t('vendorprofilepages', 'passport_back_table_header') }}</TableHead>
                  <TableHead class="text-nowrap">{{ _t('vendorprofilepages', 'status_table_header') }}</TableHead>
                  <TableHead class="text-right">{{ _t('vendorprofilepages', 'actions_table_header') }}</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow>
                  <TableCell class="text-nowrap">
                    <img
                      v-if="document.passport_front"
                      :src="document.passport_front"
                      :alt="_t('vendorprofilepages', 'passport_front_table_header')"
                      class="w-[150px] h-[100px] mb-2 object-cover"
                    />
                    <Button
                      v-if="document.passport_front"
                      variant="outline"
                      size="sm"
                      @click="viewDocument(document.passport_front)"
                      class="w-[150px] max-[768px]:w-full"
                    >
                      {{ _t('vendorprofilepages', 'view_button') }}
                    </Button>
                    <span v-else>{{ _t('vendorprofilepages', 'not_uploaded_text') }}</span>
                  </TableCell>
                  <TableCell class="text-nowrap">
                    <img
                      v-if="document.passport_back"
                      :src="document.passport_back"
                      :alt="_t('vendorprofilepages', 'passport_back_table_header')"
                      class="w-[150px] h-[100px] mb-2 object-cover"
                    />
                    <Button
                      v-if="document.passport_back"
                      variant="outline"
                      size="sm"
                      @click="viewDocument(document.passport_back)"
                      class="w-[150px] max-[768px]:w-full"
                    >
                      {{ _t('vendorprofilepages', 'view_button') }}
                    </Button>
                    <span v-else>{{ _t('vendorprofilepages', 'not_uploaded_text') }}</span>
                  </TableCell>
                  <TableCell class="text-nowrap">
                    <Badge :variant="getStatusBadgeVariant(document.vendor_profile?.status)">
                      {{ document.vendor_profile?.status }}
                    </Badge>
                  </TableCell>
                  <TableCell class="text-right">
                    <div class="flex justify-end gap-2">
                      <Button
                        v-if="document.vendor_profile?.status !== 'approved'"
                        size="sm"
                        variant="outline"
                        @click="openEditDialog()"
                      >
                        {{ _t('vendorprofilepages', 'edit_button') }}
                        <img :src="editIcon" :alt="_t('vendorprofilepages', 'edit_button')" class="ml-2" />
                      </Button>
                      <span v-else class="text-sm text-gray-500">
                        {{ _t('vendorprofilepages', 'documents_cannot_be_edited_text') }}
                      </span>
                    </div>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
          <div v-else class="p-4 text-center">
            <p>{{ _t('vendorprofilepages', 'no_vendor_documents_found_text') }}</p>
          </div>
        </div>
  
        <div v-if="document && document.vendor_profile" class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D]">
          <h2 class="text-lg font-semibold mb-4">{{ _t('vendorprofilepages', 'company_information_header') }}</h2>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="font-medium text-sm">{{ _t('vendorprofilepages', 'company_name_label') }}</p>
              <p>{{ document.vendor_profile.company_name }}</p>
            </div>
            <div>
              <p class="font-medium text-sm">{{ _t('vendorprofilepages', 'company_phone_label') }}</p>
              <p>{{ document.vendor_profile.company_phone_number }}</p>
            </div>
            <div class="max-[768px]:col-span-2">
              <p class="font-medium text-sm">{{ _t('vendorprofilepages', 'company_email_label') }}</p>
              <p>{{ document.vendor_profile.company_email }}</p>
            </div>
            <div>
              <p class="font-medium text-sm">{{ _t('vendorprofilepages', 'company_vat_label') }}</p>
              <p>{{ document.vendor_profile.company_gst_number }}</p>
            </div>
            <div class="col-span-2">
              <p class="font-medium text-sm">{{ _t('vendorprofilepages', 'company_address_label') }}</p>
              <p>{{ document.vendor_profile.company_address }}</p>
            </div>
          </div>
        </div>
      </div>
    </MyProfileLayout>
  </template>
  
  <script setup>
  import { ref, getCurrentInstance } from 'vue';
  import Table from '@/Components/ui/table/Table.vue';
  import TableHeader from '@/Components/ui/table/TableHeader.vue';
  import TableRow from '@/Components/ui/table/TableRow.vue';
  import TableHead from '@/Components/ui/table/TableHead.vue';
  import TableBody from '@/Components/ui/table/TableBody.vue';
  import TableCell from '@/Components/ui/table/TableCell.vue';
  import Button from '@/Components/ui/button/Button.vue';
  import Badge from '@/Components/ui/badge/Badge.vue';
  import editIcon from '../../../../assets/Pencil.svg';
  import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
  import EditDocument from '@/Pages/Vendor/Documents/Edit.vue';
  import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
  
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
  </script>
  
  <style scoped>
  @media screen and (max-width: 768px) {
    th {
      font-size: 0.75rem;
    }
  
    td {
      padding: 0.5rem;
    }
  }
  </style>
