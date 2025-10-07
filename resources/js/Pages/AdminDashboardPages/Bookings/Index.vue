<template>
    <AdminDashboardLayout>
        <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
            <div class="flex items-center justify-between mt-[2rem]">
                <span class="text-[1.5rem] font-semibold">All Bookings</span>
                <div class="flex items-center gap-4">
                    <Input v-model="search" placeholder="Search bookings..." class="w-[300px]" @input="handleSearch" />
                    <div class="flex gap-2">
                        <Button class="" :variant="currentStatus === 'all' ? 'primary' : 'secondary'"
                            @click="navigateTo('all')">All</Button>
                        <Button class="bg-[#FFC633]" :variant="currentStatus === 'pending' ? 'primary' : 'secondary'"
                            @click="navigateTo('pending')">Pending</Button>
                        <Button class="bg-[#0099001A]"
                            :variant="currentStatus === 'confirmed' ? 'primary' : 'secondary'"
                            @click="navigateTo('confirmed')">Confirmed</Button>
                        <Button class="bg-[#009900]" :variant="currentStatus === 'completed' ? 'primary' : 'secondary'"
                            @click="navigateTo('completed')">Completed</Button>
                        <Button class="bg-[#EA3C3C]" :variant="currentStatus === 'cancelled' ? 'primary' : 'secondary'"
                            @click="navigateTo('cancelled')">Cancelled</Button>

                    </div>
                </div>
            </div>

            <Dialog v-model:open="isEditDialogOpen">
                <EditUser :user="editForm" @close="isEditDialogOpen = false" />
            </Dialog>

            <Dialog v-model:open="isViewDialogOpen">
                <ViewUser :user="viewForm" @close="isViewDialogOpen = false" />
            </Dialog>

            <div v-if="users.data.length > 0" class="rounded-md border p-5 mt-[1rem] bg-[#153B4F0D]">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>ID</TableHead>
                            <TableHead>Booking Number</TableHead>
                            <TableHead>Plan</TableHead>
                            <TableHead>Name</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Pickup & Return Location</TableHead>
                            <TableHead>Brand</TableHead>
                            <TableHead>Date</TableHead>
                            <TableHead>Total Days</TableHead>
                            <TableHead>Currency</TableHead>
                            <TableHead>Total Amount</TableHead>
                            <TableHead>Amount Paid</TableHead>
                            <TableHead>Pending Amount</TableHead>
                            <TableHead>Payment Status</TableHead>
                            <TableHead>Booking Status</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(user, index) in users.data" :key="user.id">
                            <TableCell>{{ (users.current_page - 1) * users.per_page + index + 1 }}</TableCell>
                            <TableCell>{{ user.booking_number }}</TableCell>
                            <TableCell>{{ user.plan }}</TableCell>
                            <TableCell>{{ user.customer.first_name }} {{ user.customer.last_name }}</TableCell>
                            <TableCell>{{ user.customer.email }}</TableCell>
                            <TableCell>{{ user.pickup_location }}</TableCell>
                            <TableCell>{{ user.vehicle.brand }}</TableCell>
                            <TableCell>{{ formatDate(user.vehicle.created_at) }}</TableCell>
                            <TableCell>{{ user.total_days }}</TableCell>
                            <TableCell>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                    :class="getCurrencyBadgeClass(user.booking_currency || 'USD')">
                                    {{ user.booking_currency || 'USD' }}
                                </span>
                            </TableCell>
                            <TableCell>{{ formatCurrency(user.total_amount, user.booking_currency || 'USD') }}</TableCell>
                            <TableCell class="text-green-600 font-medium">{{ formatCurrency(user.amount_paid || 0, user.booking_currency || 'USD') }}</TableCell>
                            <TableCell class="text-yellow-600 font-medium">{{ formatCurrency(user.pending_amount || 0, user.booking_currency || 'USD') }}</TableCell>
                            <TableCell>
                                <span v-if="user.payments?.length > 0" :class="{
                                    'px-2 py-1 text-xs font-semibold rounded-full': true,
                                    'bg-green-100 text-green-800': user.payments[0].payment_status === 'succeeded',
                                    'bg-yellow-100 text-yellow-800': user.payments[0].payment_status === 'pending',
                                    'bg-red-100 text-red-800': user.payments[0].payment_status === 'failed'
                                }">
                                    {{ user.payments[0].payment_status }}
                                </span>
                                <span v-else
                                    class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                    No Payment
                                </span>
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

                                    <!-- <Button size="sm" variant="destructive" @click="deleteUser(user.id)">Delete</Button> -->
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
import { Dialog } from "@/Components/ui/dialog";
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import ViewUser from "@/Pages/AdminDashboardPages/Bookings/ViewUser.vue";
import Pagination from '@/Components/ReusableComponents/Pagination.vue';


const props = defineProps({
    users: Object,
    filters: Object,
    currentStatus: String,
});

const search = ref(props.filters.search || '');
const currentStatus = ref(props.currentStatus || 'all');
const isViewDialogOpen = ref(false);
const viewForm = ref({});
const isEditDialogOpen = ref(false);
const editForm = ref({});

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
const status = ref(props.currentStatus || 'all');
const handlePageChange = (page) => {
    let routeName = 'customer-bookings.index'; // Default
    const params = { page: page, search: search.value }; // Common params

    if (status.value !== 'all') {
        routeName = `customer-bookings.${status.value}`;
    }

    router.get(route(routeName, params), { preserveState: true, replace: true });
};

const navigateTo = (newStatus) => {
    status.value = newStatus; // Update status ref
    let routeName = 'customer-bookings.index'; // Default
    const params = { search: search.value }; // Common params

    if (newStatus !== 'all') {
        routeName = `customer-bookings.${newStatus}`;
    }

    router.get(route(routeName, params), { preserveState: true, replace: true });
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
const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`;
};

// Currency symbol function
const getCurrencySymbol = (currency) => {
    const symbols = {
        'USD': '$',
        'EUR': '€',
        'GBP': '£',
        'JPY': '¥',
        'AUD': 'A$',
        'CAD': 'C$',
        'CHF': 'Fr',
        'HKD': 'HK$',
        'SGD': 'S$',
        'SEK': 'kr',
        'KRW': '₩',
        'NOK': 'kr',
        'NZD': 'NZ$',
        'INR': '₹',
        'MXN': 'Mex$',
        'ZAR': 'R',
        'AED': 'AED'
    };
    return symbols[currency] || '$';
};

// Format number function
const formatNumber = (number) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(number);
};

// Format currency with symbol
const formatCurrency = (amount, currency) => {
    return `${getCurrencySymbol(currency)}${formatNumber(amount)}`;
};

// Currency badge class
const getCurrencyBadgeClass = (currency) => {
    const classes = {
        'USD': 'bg-green-100 text-green-800',
        'EUR': 'bg-blue-100 text-blue-800',
        'GBP': 'bg-purple-100 text-purple-800',
        'JPY': 'bg-red-100 text-red-800',
        'AUD': 'bg-yellow-100 text-yellow-800',
        'CAD': 'bg-orange-100 text-orange-800',
        'CHF': 'bg-pink-100 text-pink-800',
        'HKD': 'bg-teal-100 text-teal-800',
        'SGD': 'bg-indigo-100 text-indigo-800',
        'SEK': 'bg-gray-100 text-gray-800',
        'KRW': 'bg-red-200 text-red-900',
        'NOK': 'bg-blue-200 text-blue-900',
        'NZD': 'bg-green-200 text-green-900',
        'INR': 'bg-orange-200 text-orange-900',
        'MXN': 'bg-yellow-200 text-yellow-900',
        'ZAR': 'bg-purple-200 text-purple-900',
        'AED': 'bg-cyan-100 text-cyan-900'
    };
    return classes[currency] || 'bg-gray-100 text-gray-800';
};
</script>


<style scoped>
table th{
    font-size: 0.95rem;
}
table td{
    font-size: 0.875rem;
}
</style>
