<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">All Bookings</span>
                <div class="flex items-center gap-4">
                    <Input v-model="search" placeholder="Search bookings..." class="w-[300px]" @input="handleSearch" />
                </div>
            </div>

            <div v-if="users.data.length > 0" class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D]">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>ID</TableHead>
                            <TableHead>Booking Number</TableHead>
                            <TableHead>Plan</TableHead>
                            <TableHead>Name</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Pickup Location</TableHead>
                            <TableHead>Return Location</TableHead>
                            <TableHead>Brand</TableHead>
                            <TableHead>Model</TableHead>
                            <TableHead>Total Days</TableHead>
                            <TableHead>Total Amount</TableHead>
                            <TableHead>Payment Status</TableHead>
                            <TableHead>Booking Status</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(user,index) in users.data" :key="user.id">
                            <TableCell>{{ (users.current_page - 1) * users.per_page + index + 1 }}</TableCell>
                            <TableCell>{{ user.booking_number }}</TableCell>
                            <TableCell>{{ user.plan }}</TableCell>
                            <TableCell>{{ user.customer.first_name }} {{ user.customer.last_name }}</TableCell>
                            <TableCell>{{ user.customer.email }}</TableCell>
                            <TableCell>{{ user.pickup_location }}</TableCell>
                            <TableCell>{{ user.return_location }}</TableCell>
                            <TableCell>{{ user.vehicle.brand }}</TableCell>
                            <TableCell>{{ user.vehicle.model }}</TableCell>
                            <TableCell>{{ user.total_days }}</TableCell>
                            <TableCell>{{ user.total_amount }}</TableCell>
                            <TableCell>
                                <Badge :variant="getStatusBadgeVariant(user.payment_status)">
                                    {{ user.payment_status }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="getStatusBadgeBooking(user.booking_status)">
                                    {{ user.booking_status }}
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
                 <div class="mt-4 flex justify-end">
                    <Pagination :current-page="users.current_page" :total-pages="users.last_page"
                        @page-change="handlePageChange" />
                </div>
            </div>
            <div v-else class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D] text-center">
                No bookings found.
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
import { Dialog} from "@/Components/ui/dialog";
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import ViewUser from "@/Pages/AdminDashboardPages/Bookings/ViewUser.vue";
import Pagination from "@/Pages/AdminDashboardPages/Bookings/Pagination.vue";


const props = defineProps({
    users: Object,
    filters: Object,
});

const search = ref(props.filters.search || ''); // Initialize search with the filter value
const isViewDialogOpen = ref(false);
const viewForm = ref({});

// Handle search input
const handleSearch = () => {
    router.get('/customer-bookings', { search: search.value }, {
        preserveState: true,
        replace: true,
    });
};

const openViewDialog = (user) => {

    viewForm.value = { ...user };
    isViewDialogOpen.value = true;
};

const deleteUser = (id) => {
    router.delete(`/customer-bookings/${id}`);
};

const handlePageChange = (page) => {
    router.get(`/customer-bookings?page=${page}`);
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
const getStatusBadgeBooking = (status) => {
    switch (status) {
        case 'completed':
            return 'default';
        case 'pending':
            return 'secondary';
        case 'failed':
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