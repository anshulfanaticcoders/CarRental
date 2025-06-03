<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">All Vehicles</span>
                <div class="flex items-center gap-4">
                    <Input v-model="search" placeholder="Search vehicles..." class="w-[300px]" @input="handleSearch" />
                </div>
            </div>

            <Dialog v-model:open="isEditVehicleDialogOpen">
                <EditVehicleDialog :vehicle="editVehicleForm" @close="isEditVehicleDialogOpen = false" />
            </Dialog>

            <Dialog v-model:open="isViewDialogOpen">
                <ViewUser :user="viewForm" @close="isViewDialogOpen = false"/>
            </Dialog>

            <div class="rounded-md border p-5  mt-[1rem] bg-[#153B4F0D]">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>ID</TableHead>
                            <TableHead>User Name</TableHead>
                            <TableHead>Vehicle Name</TableHead>
                            <TableHead>Location</TableHead>
                            <TableHead>City</TableHead>
                            <TableHead>State</TableHead>
                            <TableHead>Country</TableHead>
                            <TableHead>Price</TableHead>
                            <TableHead>Date Added</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(user, index) in users.data" :key="user.id">
                            <TableCell>{{ (users.current_page - 1) * users.per_page + index + 1 }}</TableCell>
                            <TableCell>{{ user.user.first_name }} {{ user.user.last_name }}</TableCell>
                            <TableCell>{{ user.brand }} {{ user.model }}</TableCell>
                            <TableCell>{{ user.full_vehicle_address }}</TableCell>
                            <TableCell>{{ user.city }}</TableCell>
                            <TableCell>{{ user.state }}</TableCell>
                            <TableCell>{{ user.country }}</TableCell>
                            <TableCell>
                                <template v-if="user.price_per_day || user.price_per_week || user.price_per_month">
                                    <span v-if="user.price_per_day">
                                        {{ user.vendor_profile.currency }}  {{ user.price_per_day }}/Day
                                    </span>
                                    <span v-if="user.price_per_day && (user.price_per_week || user.price_per_month)"> |
                                    </span>

                                    <span v-if="user.price_per_week">
                                        {{ user.vendor_profile.currency }}{{ user.price_per_week }}/Week
                                    </span>
                                    <span v-if="user.price_per_week && user.price_per_month"> | </span>

                                    <span v-if="user.price_per_month">
                                        {{ user.vendor_profile.currency }}{{ user.price_per_month }}/Month
                                    </span>
                                </template>
                                <span v-else>-</span> <!-- Placeholder if no prices are available -->
                            </TableCell>
                            <TableCell>{{ formatDate(user.created_at) }}</TableCell>

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
                                    <Button size="sm" variant="outline" @click="openEditVehicleDialog(user)">
                                        Edit
                                    </Button>
                                    <Button size="sm" variant="destructive" @click="openDeleteDialog(user.id)">Delete</Button>
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

            <!-- Alert Dialog for Delete Confirmation -->
            <AlertDialog v-model:open="isDeleteDialogOpen">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Do you really want to delete this vehicle? This action cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel @click="isDeleteDialogOpen = false">Cancel</AlertDialogCancel>
                        <AlertDialogAction @click="confirmDelete">Delete</AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref } from "vue";
import { router, Link } from "@inertiajs/vue3";
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
import EditVehicleDialog from "@/Pages/AdminDashboardPages/Vehicles/EditVehicleDialog.vue"; // Import the new dialog
import Pagination from "@/Pages/AdminDashboardPages/Vehicles/Pagination.vue";
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
} from '@/Components/ui/alert-dialog'

const props = defineProps({
    users: Object,
    filters: Object,
});

// Format date
const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const search = ref(props.filters.search || ''); // Initialize search with the filter value
const isViewDialogOpen = ref(false);
const isEditVehicleDialogOpen = ref(false); // State for the new edit dialog
const isDeleteDialogOpen = ref(false);
const viewForm = ref({});
const editVehicleForm = ref({}); // State for the vehicle being edited
const deleteUserId = ref(null);

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

const openEditVehicleDialog = (vehicle) => {
    editVehicleForm.value = { ...vehicle }; // Use a deep copy if vehicle object is complex
    isEditVehicleDialogOpen.value = true;
};

const openDeleteDialog = (id) => {
    deleteUserId.value = id;
    isDeleteDialogOpen.value = true;
};

const confirmDelete = () => {
    router.delete(`/vendor-vehicles/${deleteUserId.value}`, {
        onSuccess: () => {
            console.log('Vehicle deleted successfully');
            isDeleteDialogOpen.value = false;
        },
        onError: (errors) => {
            console.error(errors);
        }
    });
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
<style scoped>
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


table th{
    font-size: 0.95rem;
}
table td{
    font-size: 0.875rem;
}

</style>
