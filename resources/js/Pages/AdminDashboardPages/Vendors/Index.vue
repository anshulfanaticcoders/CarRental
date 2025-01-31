<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">All Vendors</span>
                <div class="flex items-center gap-4">
                    <Input v-model="search" placeholder="Search vendor..." class="w-[300px]" @input="handleSearch" />
                </div>

            </div>

            <Dialog v-model:open="isEditDialogOpen">
                <EditUser :user="editForm" @close="isEditDialogOpen = false" />
            </Dialog>

            <Dialog v-model:open="isViewDialogOpen">
                <ViewUser :user="viewForm" @close="isViewDialogOpen = false" />
            </Dialog>

            <div class="rounded-md border p-5  mt-[1rem] bg-[#153B4F0D]">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>ID</TableHead>
                            <TableHead>User Name</TableHead>
                            <TableHead>Company Name</TableHead>
                            <TableHead>Company Email</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Driving License</TableHead>
                            <TableHead>Passport</TableHead>
                            <TableHead>Passport Photo</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(user,index) in users.data" :key="user.id">
                            <TableCell>{{ (users.current_page - 1) * users.per_page + index + 1 }}</TableCell>
                            <TableCell>{{ user.first_name }} {{ user.last_name }}</TableCell>
                            <TableCell>{{ user.vendor_profile?.company_name }}</TableCell>
                            <TableCell>{{ user.vendor_profile?.company_email }}</TableCell>
                            <TableCell>
                                <Badge :variant="getStatusBadgeVariant(user.vendor_profile?.status)">
                                    {{ user.vendor_profile?.status }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <img @click="openImageModal(`/storage/${user.vendor_document?.driving_license}`)"
                                    :src="`/storage/${user.vendor_document?.driving_license}`" alt="Driving License"
                                    class="w-[100px] h-[80px] object-cover mb-2 cursor-pointer" />
                            </TableCell>
                            <TableCell>
                                <img @click="openImageModal(`/storage/${user.vendor_document?.passport}`)"
                                    :src="`/storage/${user.vendor_document?.passport}`" alt="Passport"
                                    class="w-[100px] h-[80px] object-cover mb-2 cursor-pointer" />
                            </TableCell>
                            <TableCell>
                                <img @click="openImageModal(`/storage/${user.vendor_document?.passport_photo}`)"
                                    :src="`/storage/${user.vendor_document?.passport_photo}`" alt="Passport Photo"
                                    class="w-[100px] h-[80px] object-cover mb-2 cursor-pointer" />
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button size="sm" variant="outline" @click="openViewDialog(user)">
                                        View
                                    </Button>
                                    <Button size="sm" variant="outline" @click="openEditDialog(user)">
                                        Edit
                                        <img :src=editIcon alt="">
                                    </Button>
                                    <Button size="sm" variant="destructive" @click="deleteUser(user.id)">Delete</Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <!-- Pagination -->
                <div class="mt-4 flex justify-end">
                    <Pagination :current-page="users.current_page" :total-pages="users.last_page"
                        @page-change="handlePageChange" />
                </div>
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import Table from "@/Components/ui/table/Table.vue";
import TableHeader from "@/Components/ui/table/TableHeader.vue";
import TableRow from "@/Components/ui/table/TableRow.vue";
import TableHead from "@/Components/ui/table/TableHead.vue";
import TableBody from "@/Components/ui/table/TableBody.vue";
import TableCell from "@/Components/ui/table/TableCell.vue";
import Button from "@/Components/ui/button/Button.vue";
import Badge from "@/Components/ui/badge/Badge.vue";
import { Input } from "@/Components/ui/input";
import editIcon from "../../../../assets/Pencil.svg";
import { Dialog, DialogTrigger } from "@/Components/ui/dialog";
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import EditUser from "@/Pages/AdminDashboardPages/Vendors/EditUser.vue";
import ViewUser from "@/Pages/AdminDashboardPages/Vendors/ViewUser.vue";
import Pagination from "@/Pages/AdminDashboardPages/Vendors/Pagination.vue";


const props = defineProps({
    users: Object,
    filters: Object,
});
const search = ref(props.filters.search || ''); // Initialize search with the filter value
const isEditDialogOpen = ref(false);
const isViewDialogOpen = ref(false);
const editForm = ref({});
const viewForm = ref({});

// Handle search input
const handleSearch = () => {
    router.get('/vendors', { search: search.value }, {
        preserveState: true,
        replace: true,
    });
};

const openEditDialog = (user) => {
    editForm.value = { ...user };
    isEditDialogOpen.value = true;
};

const openViewDialog = (user) => {

    viewForm.value = { ...user };
    isViewDialogOpen.value = true;
};

const deleteUser = (id) => {
    router.delete(`/vendors/${id}`);
};

const handlePageChange = (page) => {
    router.get(`/vendors?page=${page}`);
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
        return 'default';
    }
  };
</script>
<style>
.search-box {
    width: 300px;
    padding: 0.5rem;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    outline: none;
}

.search-box:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}
</style>