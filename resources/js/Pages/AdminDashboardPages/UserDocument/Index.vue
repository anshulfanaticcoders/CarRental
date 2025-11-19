<template>
    <AdminDashboardLayout>
        <div class="container mx-auto p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold tracking-tight">User Documents</h1>
                <div class="flex items-center gap-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-700">
                        <FileText class="w-4 h-4 mr-1" />
                        All Documents
                    </span>
                </div>
            </div>

            <!-- Enhanced Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Documents Card -->
                <div class="relative bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-500 bg-opacity-20 rounded-lg">
                            <FileText class="w-6 h-6 text-blue-600" />
                        </div>
                        <Badge variant="secondary" class="bg-blue-500 text-white">
                            Total
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-blue-900">{{ documents.total || 0 }}</p>
                        <p class="text-sm text-blue-700 mt-1">Total Documents</p>
                    </div>
                </div>

                <!-- Verified Documents Card -->
                <div class="relative bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-green-500 bg-opacity-20 rounded-lg">
                            <CheckCircle class="w-6 h-6 text-green-600" />
                        </div>
                        <Badge variant="secondary" class="bg-green-500 text-white">
                            Verified
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-green-900">{{ verifiedCount || 0 }}</p>
                        <p class="text-sm text-green-700 mt-1">Verified</p>
                    </div>
                </div>

                <!-- Pending Documents Card -->
                <div class="relative bg-gradient-to-br from-yellow-50 to-yellow-100 border border-yellow-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-yellow-500 bg-opacity-20 rounded-lg">
                            <Clock class="w-6 h-6 text-yellow-600" />
                        </div>
                        <Badge variant="secondary" class="bg-yellow-500 text-white">
                            Pending
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-yellow-900">{{ pendingCount || 0 }}</p>
                        <p class="text-sm text-yellow-700 mt-1">Pending</p>
                    </div>
                </div>

                <!-- Rejected Documents Card -->
                <div class="relative bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-red-500 bg-opacity-20 rounded-lg">
                            <XCircle class="w-6 h-6 text-red-600" />
                        </div>
                        <Badge variant="secondary" class="bg-red-500 text-white">
                            Rejected
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-red-900">{{ rejectedCount || 0 }}</p>
                        <p class="text-sm text-red-700 mt-1">Rejected</p>
                    </div>
                </div>
            </div>

            <!-- Enhanced Search Bar -->
            <div class="flex justify-center">
                <div class="relative w-full max-w-md">
                    <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="search"
                        placeholder="Search by user name..."
                        @input="handleSearch"
                        class="pl-10 pr-4 h-12 text-base"
                    />
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
          <DialogContent class="sm:max-w-[90vw] md:max-w-[80vw] lg:max-w-[70vw] xl:max-w-[60vw] max-h-[80vh] overflow-hidden">
            <DialogHeader>
              <DialogTitle class="text-xl font-semibold">Document Preview</DialogTitle>
            </DialogHeader>
            <div class="flex justify-center items-center py-4">
              <img
                :src="selectedImage"
                alt="Document preview"
                class="max-w-full max-h-[50vh] object-contain rounded-lg cursor-pointer"
                @click="isImageModalOpen = false"
              />
            </div>
            <DialogFooter>
              <Button variant="outline" @click="isImageModalOpen = false">Close</Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
  
        <!-- Enhanced Documents Table -->
        <div v-if="documents.data.length > 0" class="rounded-xl border bg-card shadow-sm overflow-hidden">
            <div class="overflow-x-auto max-w-full">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-muted/50">
                            <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">ID</TableHead>
                            <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">User Name</TableHead>
                            <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">License Front</TableHead>
                            <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">License Back</TableHead>
                            <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Passport Front</TableHead>
                            <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Passport Back</TableHead>
                            <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Status</TableHead>
                            <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Submitted</TableHead>
                            <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Verified</TableHead>
                            <TableHead class="whitespace-nowrap px-4 py-3 font-semibold text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(document, index) in documents.data" :key="document.id" class="hover:bg-muted/25 transition-colors">
                            <TableCell class="whitespace-nowrap px-4 py-3 font-medium">
                                {{ (documents.current_page - 1) * documents.per_page + index + 1 }}
                            </TableCell>
                            <TableCell class="whitespace-nowrap px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <Users class="w-4 h-4 text-muted-foreground" />
                                    <div>
                                        <div class="font-medium">{{ document.user.first_name }} {{ document.user.last_name }}</div>
                                        <div class="text-sm text-muted-foreground">ID: {{ document.user.id }}</div>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell class="whitespace-nowrap px-4 py-3">
                                <div v-if="document.driving_license_front" class="relative group cursor-pointer" @click="openImageModal(document.driving_license_front)">
                                    <img
                                        :src="document.driving_license_front"
                                        alt="Driving License Front"
                                        class="w-20 h-16 object-cover rounded-lg border border-gray-200 hover:border-blue-400 transition-all pointer-events-none"
                                    />
                                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 rounded-lg transition-all flex items-center justify-center">
                                        <Image class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition-opacity" />
                                    </div>
                                </div>
                                <div v-else class="w-20 h-16 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center">
                                    <span class="text-xs text-gray-400">N/A</span>
                                </div>
                            </TableCell>
                            <TableCell class="whitespace-nowrap px-4 py-3">
                                <div v-if="document.driving_license_back" class="relative group cursor-pointer" @click="openImageModal(document.driving_license_back)">
                                    <img
                                        :src="document.driving_license_back"
                                        alt="Driving License Back"
                                        class="w-20 h-16 object-cover rounded-lg border border-gray-200 hover:border-blue-400 transition-all pointer-events-none"
                                    />
                                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 rounded-lg transition-all flex items-center justify-center">
                                        <Image class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition-opacity" />
                                    </div>
                                </div>
                                <div v-else class="w-20 h-16 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center">
                                    <span class="text-xs text-gray-400">N/A</span>
                                </div>
                            </TableCell>
                            <TableCell class="whitespace-nowrap px-4 py-3">
                                <div v-if="document.passport_front" class="relative group cursor-pointer" @click="openImageModal(document.passport_front)">
                                    <img
                                        :src="document.passport_front"
                                        alt="Passport Front"
                                        class="w-20 h-16 object-cover rounded-lg border border-gray-200 hover:border-blue-400 transition-all pointer-events-none"
                                    />
                                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 rounded-lg transition-all flex items-center justify-center">
                                        <Image class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition-opacity" />
                                    </div>
                                </div>
                                <div v-else class="w-20 h-16 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center">
                                    <span class="text-xs text-gray-400">N/A</span>
                                </div>
                            </TableCell>
                            <TableCell class="whitespace-nowrap px-4 py-3">
                                <div v-if="document.passport_back" class="relative group cursor-pointer" @click="openImageModal(document.passport_back)">
                                    <img
                                        :src="document.passport_back"
                                        alt="Passport Back"
                                        class="w-20 h-16 object-cover rounded-lg border border-gray-200 hover:border-blue-400 transition-all pointer-events-none"
                                    />
                                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 rounded-lg transition-all flex items-center justify-center">
                                        <Image class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition-opacity" />
                                    </div>
                                </div>
                                <div v-else class="w-20 h-16 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center">
                                    <span class="text-xs text-gray-400">N/A</span>
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
                            <TableCell class="whitespace-nowrap px-4 py-3">{{ formatDate(document.created_at) }}</TableCell>
                            <TableCell class="whitespace-nowrap px-4 py-3">
                                {{ document.verified_at ? formatDate(document.verified_at) : 'N/A' }}
                            </TableCell>
                            <TableCell class="whitespace-nowrap px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <Button size="sm" variant="outline" @click="openViewDialog(document)" class="flex items-center gap-1">
                                        <Eye class="w-3 h-3" />
                                        View
                                    </Button>
                                    <Button size="sm" variant="outline" @click="openEditDialog(document)" class="flex items-center gap-1">
                                        <Edit class="w-3 h-3" />
                                        Edit
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
            <div class="flex justify-end pt-4 pr-2">
                <Pagination
                    :current-page="documents.current_page"
                    :total-pages="documents.last_page"
                    @page-change="handlePageChange"
                />
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="rounded-xl border bg-card p-12 text-center">
            <div class="flex flex-col items-center space-y-4">
                <FileText class="w-16 h-16 text-muted-foreground" />
                <div class="space-y-2">
                    <h3 class="text-xl font-semibold text-foreground">No documents found</h3>
                    <p class="text-muted-foreground">No user documents have been submitted yet.</p>
                </div>
            </div>
        </div>
      </div>
    </AdminDashboardLayout>
  </template>
  
  <script setup>
  import { ref, computed } from 'vue';
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
  import {
    FileText,
    CheckCircle,
    Clock,
    XCircle,
    Search,
    Eye,
    Edit,
    Image,
    Shield,
    Users
  } from 'lucide-vue-next';
  import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog';
  import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
  import EditDocument from '@/Pages/AdminDashboardPages/UserDocument/Edit.vue';
  import ViewDocument from '@/Pages/AdminDashboardPages/UserDocument/View.vue';
  import Pagination from '@/Components/ReusableComponents/Pagination.vue';
  
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
  
// Computed properties for statistics
const verifiedCount = computed(() => {
    return props.documents.data?.filter(doc => doc.verification_status === 'verified').length || 0;
});

const pendingCount = computed(() => {
    return props.documents.data?.filter(doc => doc.verification_status === 'pending').length || 0;
});

const rejectedCount = computed(() => {
    return props.documents.data?.filter(doc => doc.verification_status === 'rejected').length || 0;
});

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A';
    const date = new Date(dateStr);
    return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`;
};
  </script>
  
  