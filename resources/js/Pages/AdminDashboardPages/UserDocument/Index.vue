<template>
    <AdminDashboardLayout>
      <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
        <div class="flex items-center justify-between mt-[2rem]">
          <span class="text-[1.5rem] font-semibold">User Documents</span>
          <div class="flex items-center gap-4">
            <Input v-model="search" placeholder="Search by user name..." class="w-[300px]" @input="handleSearch" />
          </div>
        </div>
  
        <Dialog v-model:open="isEditDialogOpen">
          <EditDocument :document="editForm" @close="isEditDialogOpen = false" />
        </Dialog>
  
        <Dialog v-model:open="isViewDialogOpen">
          <ViewDocument :document="viewForm" @close="isViewDialogOpen = false" />
        </Dialog>
  
        <!-- Image Preview Dialog -->
        <Dialog v-model:open="isImageModalOpen">
          <DialogContent class="sm:max-w-[80%]">
            <div class="flex justify-center">
              <img :src="selectedImage" alt="Document preview" class="max-w-full max-h-[80vh] object-contain" />
            </div>
            <DialogFooter>
              <Button variant="outline" @click="isImageModalOpen = false">Close</Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
  
        <div class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D]">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>ID</TableHead>
                <TableHead>User Name</TableHead>
                <TableHead>Driving License Front</TableHead>
                <TableHead>Driving License Back</TableHead>
                <TableHead>Passport Front</TableHead>
                <TableHead>Passport Back</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Submitted At</TableHead>
                <TableHead>Verified At</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="(document, index) in documents.data" :key="document.id">
                <TableCell>{{ (documents.current_page - 1) * documents.per_page + index + 1 }}</TableCell>
                <TableCell>{{ document.user.first_name }} {{ document.user.last_name }}</TableCell>
                <TableCell>
                  <img
                    v-if="document.driving_license_front"
                    :src="document.driving_license_front"
                    alt="Driving License Front"
                    class="w-[100px] h-[80px] object-cover mb-2 cursor-pointer"
                    @click="openImageModal(document.driving_license_front)"
                  />
                  <span v-else>N/A</span>
                </TableCell>
                <TableCell>
                  <img
                    v-if="document.driving_license_back"
                    :src="document.driving_license_back"
                    alt="Driving License Back"
                    class="w-[100px] h-[80px] object-cover mb-2 cursor-pointer"
                    @click="openImageModal(document.driving_license_back)"
                  />
                  <span v-else>N/A</span>
                </TableCell>
                <TableCell>
                  <img
                    v-if="document.passport_front"
                    :src="document.passport_front"
                    alt="Passport Front"
                    class="w-[100px] h-[80px] object-cover mb-2 cursor-pointer"
                    @click="openImageModal(document.passport_front)"
                  />
                  <span v-else>N/A</span>
                </TableCell>
                <TableCell>
                  <img
                    v-if="document.passport_back"
                    :src="document.passport_back"
                    alt="Passport Back"
                    class="w-[100px] h-[80px] object-cover mb-2 cursor-pointer"
                    @click="openImageModal(document.passport_back)"
                  />
                  <span v-else>N/A</span>
                </TableCell>
                <TableCell>
                  <Badge :variant="getStatusBadgeVariant(document.verification_status)">
                    {{ document.verification_status }}
                  </Badge>
                </TableCell>
                <TableCell>
                  {{ formatDate(document.created_at) }}
                </TableCell>
                <TableCell>
                  {{ document.verified_at ? formatDate(document.verified_at) : 'N/A' }}
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end gap-2">
                    <Button size="sm" variant="outline" @click="openViewDialog(document)">
                      View
                    </Button>
                    <Button size="sm" variant="outline" @click="openEditDialog(document)">
                      Edit
                      <img :src="editIcon" alt="Edit" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
          <!-- Pagination -->
          <div class="mt-4 flex justify-end">
            <Pagination
              :current-page="documents.current_page"
              :total-pages="documents.last_page"
              @page-change="handlePageChange"
            />
          </div>
        </div>
      </div>
    </AdminDashboardLayout>
  </template>
  
  <script setup>
  import { ref } from 'vue';
  import { router } from '@inertiajs/vue3';
  import Table from '@/Components/ui/table/Table.vue';
  import TableHeader from '@/Components/ui/table/TableHeader.vue';
  import TableRow from '@/Components/ui/table/TableRow.vue';
  import TableHead from '@/Components/ui/table/TableHead.vue';
  import TableBody from '@/Components/ui/table/TableBody.vue';
  import TableCell from '@/Components/ui/table/TableCell.vue';
  import Button from '@/Components/ui/button/Button.vue';
  import Badge from '@/Components/ui/badge/Badge.vue';
  import { Input } from '@/Components/ui/input';
  import editIcon from '../../../../assets/Pencil.svg';
  import { Dialog, DialogContent, DialogFooter } from '@/Components/ui/dialog';
  import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
  import EditDocument from '@/Pages/AdminDashboardPages/UserDocument/Edit.vue';
  import ViewDocument from '@/Pages/AdminDashboardPages/UserDocument/View.vue';
  import Pagination from '@/Pages/AdminDashboardPages/Vendors/Pagination.vue';
  
  const props = defineProps({
    documents: Object,
    filters: Object,
  });
  
  const search = ref(props.filters?.search || '');
  const isEditDialogOpen = ref(false);
  const isViewDialogOpen = ref(false);
  const isImageModalOpen = ref(false);
  const selectedImage = ref('');
  const editForm = ref({});
  const viewForm = ref({});
  
  // Handle search input
  const handleSearch = () => {
    router.get(
      '/admin/user-documents',
      { search: search.value },
      {
        preserveState: true,
        replace: true,
      }
    );
  };
  
  const openEditDialog = (document) => {
    editForm.value = { ...document };
    isEditDialogOpen.value = true;
  };
  
  const openViewDialog = (document) => {
    viewForm.value = { ...document };
    isViewDialogOpen.value = true;
  };
  
  const openImageModal = (imageUrl) => {
    if (imageUrl) {
      selectedImage.value = imageUrl;
      isImageModalOpen.value = true;
    }
  };
  
  const handlePageChange = (page) => {
    router.get(`/admin/user-documents?page=${page}`, { search: search.value }, { preserveState: true });
  };
  
  const getStatusBadgeVariant = (status) => {
    switch (status) {
      case 'verified':
        return 'default';
      case 'pending':
        return 'secondary';
      case 'rejected':
        return 'destructive';
      default:
        return 'default';
    }
  };
  
  const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A';
    const date = new Date(dateStr);
    return `${String(date.getMonth() + 1).padStart(2, '0')}/${String(date.getDate()).padStart(2, '0')}/${date.getFullYear()}`;
  };
  </script>
  
  <style scoped>
  table th {
    font-size: 0.95rem;
  }
  table td {
    font-size: 0.875rem;
  }
  </style>