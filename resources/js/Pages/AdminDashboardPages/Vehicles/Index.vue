<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">All Vehicles</span>
                <div class="flex items-center gap-4">
                    <Input v-model="search" placeholder="Search vehicles..." class="w-[300px]" @input="handleSearch" />
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
                            <TableHead>Model</TableHead>
                            <TableHead>Brand</TableHead>
                            <TableHead>Seating Capacity</TableHead>
                            <TableHead>Color</TableHead>
                            <TableHead>Location</TableHead>
                            <TableHead>Price per day</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(user,index) in users.data" :key="user.id">
                            <TableCell>{{ (users.current_page - 1) * users.per_page + index + 1 }}</TableCell>
                            <TableCell>{{ user.user.first_name }} {{ user.user.last_name }}</TableCell>
                            <TableCell>{{ user.model }}</TableCell>
                            <TableCell>{{ user.brand }}</TableCell>
                            <TableCell>{{ user.seating_capacity }}</TableCell>
                            <TableCell>{{ user.color }}</TableCell>
                            <TableCell>{{ user.location }}</TableCell>
                            <TableCell>{{ user.price_per_day }}</TableCell>
                            <TableCell>
                                <Badge :variant="getStatusBadgeVariant(user.vendor_profile?.status)">
                                    {{ user.status }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button size="sm" variant="outline" @click="openViewDialog(user)">
                                        View
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
import { Dialog, DialogTrigger } from "@/Components/ui/dialog";
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import ViewUser from "@/Pages/AdminDashboardPages/Vehicles/ViewUser.vue";
import Pagination from "@/Pages/AdminDashboardPages/Vehicles/Pagination.vue";


const props = defineProps({
    users: Object,
    filters: Object,
});

const search = ref(props.filters.search || ''); // Initialize search with the filter value
const isViewDialogOpen = ref(false);
const viewForm = ref({});

// Handle search input
const handleSearch = () => {
    router.get('/vendor-vehicles', { search: search.value }, {
        preserveState: true,
        replace: true,
    });
};

const openViewDialog = (user) => {
   
    viewForm.value = { ...user };
    isViewDialogOpen.value = true;
};

const deleteUser = (id) => {
    router.delete(`/vendor-vehicles/${id}`);
};

const handlePageChange = (page) => {
    router.get(`/vendor-vehicles?page=${page}`);
};
const getStatusBadgeVariant = (status) => {
    switch (status) {
      case 'available':
        return 'default';
      case 'rented':
        return 'secondary';
      case 'maintenance':
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