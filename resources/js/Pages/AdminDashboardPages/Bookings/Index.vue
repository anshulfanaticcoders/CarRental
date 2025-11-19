<template>
    <AdminDashboardLayout>
        <div class="w-[80%] p-6 space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold tracking-tight">All Bookings</h1>
                <div class="flex items-center gap-4">
                    <Input v-model="search" placeholder="Search bookings..." class="w-80" @input="handleSearch" />
                    <div class="flex gap-2">
                        <Button :variant="currentStatus === 'all' ? 'default' : 'outline'"
                            @click="navigateTo('all')">All</Button>
                        <Button :variant="currentStatus === 'pending' ? 'secondary' : 'outline'"
                            @click="navigateTo('pending')">Pending</Button>
                        <Button :variant="currentStatus === 'confirmed' ? 'default' : 'outline'"
                            @click="navigateTo('confirmed')">Confirmed</Button>
                        <Button :variant="currentStatus === 'completed' ? 'outline' : 'secondary'"
                            @click="navigateTo('completed')">Completed</Button>
                        <Button :variant="currentStatus === 'cancelled' ? 'destructive' : 'outline'"
                            @click="navigateTo('cancelled')">Cancelled</Button>
                    </div>
                </div>
            </div>

    
            <div v-if="users.data.length > 0" class="rounded-lg border bg-card shadow-sm w-full overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="whitespace-nowrap p-3">ID</TableHead>
                                <TableHead class="whitespace-nowrap p-3">Booking Number</TableHead>
                                <TableHead class="whitespace-nowrap p-3">Plan</TableHead>
                                <TableHead class="whitespace-nowrap p-3">Name</TableHead>
                                <TableHead class="whitespace-nowrap p-3">Email</TableHead>
                                <TableHead class="whitespace-nowrap p-3">Pickup & Return Location</TableHead>
                                <TableHead class="whitespace-nowrap p-3">Brand</TableHead>
                                <TableHead class="whitespace-nowrap p-3">Date</TableHead>
                                <TableHead class="whitespace-nowrap p-3">Total Days</TableHead>
                                <TableHead class="whitespace-nowrap p-3">Currency</TableHead>
                                <TableHead class="whitespace-nowrap p-3">Total Amount</TableHead>
                                <TableHead class="whitespace-nowrap p-3">Amount Paid</TableHead>
                                <TableHead class="whitespace-nowrap p-3">Pending Amount</TableHead>
                                <TableHead class="whitespace-nowrap p-3">Payment Status</TableHead>
                                <TableHead class="whitespace-nowrap p-3">Booking Status</TableHead>
                              </TableRow>
                        </TableHeader>
                    <TableBody>
                        <TableRow v-for="(user, index) in users.data" :key="user.id">
                            <TableCell class="whitespace-nowrap p-3">{{ (users.current_page - 1) * users.per_page + index + 1 }}</TableCell>
                            <TableCell class="whitespace-nowrap p-3">{{ user.booking_number }}</TableCell>
                            <TableCell class="whitespace-nowrap p-3">{{ user.plan }}</TableCell>
                            <TableCell class="whitespace-nowrap p-3">{{ user.customer.first_name }} {{ user.customer.last_name }}</TableCell>
                            <TableCell class="whitespace-nowrap p-3">{{ user.customer.email }}</TableCell>
                            <TableCell class="whitespace-nowrap p-3">{{ user.pickup_location }}</TableCell>
                            <TableCell class="whitespace-nowrap p-3">{{ user.vehicle.brand }}</TableCell>
                            <TableCell class="whitespace-nowrap p-3">{{ formatDate(user.vehicle.created_at) }}</TableCell>
                            <TableCell class="whitespace-nowrap p-3">{{ user.total_days }}</TableCell>
                            <TableCell>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                    :class="getCurrencyBadgeClass(user.booking_currency || 'USD')">
                                    {{ user.booking_currency || 'USD' }}
                                </span>
                            </TableCell>
                            <TableCell class="whitespace-nowrap p-3">{{ formatCurrency(user.total_amount, user.booking_currency || 'USD') }}</TableCell>
                            <TableCell class="whitespace-nowrap p-3 text-green-700 font-medium">{{ formatCurrency(user.amount_paid || 0, user.booking_currency || 'USD') }}</TableCell>
                            <TableCell class="whitespace-nowrap p-3 text-yellow-700 font-medium">{{ formatCurrency(user.pending_amount || 0, user.booking_currency || 'USD') }}</TableCell>
                            <TableCell class="whitespace-nowrap p-3">
                                <Badge v-if="user.payments?.length > 0" :variant="user.payments[0].payment_status === 'succeeded' ? 'default' : user.payments[0].payment_status === 'pending' ? 'secondary' : 'destructive'">
                                    {{ user.payments[0].payment_status }}
                                </Badge>
                                <Badge v-else variant="outline">
                                    No Payment
                                </Badge>
                            </TableCell>

                            <TableCell class="whitespace-nowrap">
                                <Badge :variant="getStatusBadgeBooking(user.booking_status)">
                                    {{ user.booking_status }}
                                </Badge>
                            </TableCell>
                            </TableRow>
                    </TableBody>
                </Table>
        </div>
        <div v-else class="rounded-lg border bg-card p-8 text-center">
            No bookings found.
        </div>
        <div class="flex justify-end pt-4">
            <Pagination :current-page="users.current_page" :total-pages="users.last_page"
                @page-change="handlePageChange" />
        </div>

        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from "@/Components/ui/table";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Input } from "@/Components/ui/input";
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import Pagination from '@/Components/ReusableComponents/Pagination.vue';


const props = defineProps({
    users: Object,
    filters: Object,
    currentStatus: String,
});

const search = ref(props.filters.search || '');
const currentStatus = ref(props.currentStatus || 'all');

const handleSearch = () => {
    router.get('/customer-bookings', { search: search.value }, {
        preserveState: true,
        replace: true,
    });
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


