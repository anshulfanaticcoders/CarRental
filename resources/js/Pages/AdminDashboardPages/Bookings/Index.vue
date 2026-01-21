<template>
    <AdminDashboardLayout>
        <div class="container mx-auto p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold tracking-tight">Bookings Management</h1>
                <div class="flex items-center gap-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-700">
                        <Calendar class="w-4 h-4 mr-1" />
                        All Bookings
                    </span>
                </div>
            </div>

            <!-- Flash Messages -->
            <div v-if="flash?.success" class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ flash.success }}
            </div>
            <div v-if="flash?.error" class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                {{ flash.error }}
            </div>

            <!-- Enhanced Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                <!-- Total Bookings Card -->
                <div class="relative bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-500 bg-opacity-20 rounded-lg">
                            <Calendar class="w-6 h-6 text-blue-600" />
                        </div>
                        <Badge variant="secondary" class="bg-blue-500 text-white">
                            Total
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-blue-900">{{ statusCounts?.total || 0 }}</p>
                        <p class="text-sm text-blue-700 mt-1">Total Bookings</p>
                    </div>
                </div>

                <!-- Pending Bookings Card -->
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
                        <p class="text-4xl font-bold text-yellow-900">{{ statusCounts?.pending || 0 }}</p>
                        <p class="text-sm text-yellow-700 mt-1">Pending Bookings</p>
                    </div>
                </div>

                <!-- Confirmed Bookings Card -->
                <div class="relative bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-green-500 bg-opacity-20 rounded-lg">
                            <CheckCircle class="w-6 h-6 text-green-600" />
                        </div>
                        <Badge variant="secondary" class="bg-green-500 text-white">
                            Confirmed
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-green-900">{{ statusCounts?.confirmed || 0 }}</p>
                        <p class="text-sm text-green-700 mt-1">Confirmed Bookings</p>
                    </div>
                </div>

                <!-- Completed Bookings Card -->
                <div class="relative bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-500 bg-opacity-20 rounded-lg">
                            <CheckSquare class="w-6 h-6 text-blue-600" />
                        </div>
                        <Badge variant="secondary" class="bg-blue-500 text-white">
                            Completed
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-blue-900">{{ statusCounts?.completed || 0 }}</p>
                        <p class="text-sm text-blue-700 mt-1">Completed Bookings</p>
                    </div>
                </div>

                <!-- Cancelled Bookings Card -->
                <div class="relative bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-red-500 bg-opacity-20 rounded-lg">
                            <XCircle class="w-6 h-6 text-red-600" />
                        </div>
                        <Badge variant="secondary" class="bg-red-500 text-white">
                            Cancelled
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-red-900">{{ statusCounts?.cancelled || 0 }}</p>
                        <p class="text-sm text-red-700 mt-1">Cancelled Bookings</p>
                    </div>
                </div>
            </div>

            <!-- Enhanced Search & Filter -->
            <div class="flex flex-col md:flex-row gap-4 items-center justify-center">
                <div class="flex-1 w-full md:w-auto">
                    <div class="relative w-full max-w-md">
                        <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="search"
                            placeholder="Search bookings by number, customer, vehicle..."
                            class="pl-10 pr-4 h-12 text-base"
                        />
                    </div>
                </div>
                <div>
                    <Select v-model="statusFilter">
                        <SelectTrigger class="w-40 h-12">
                            <SelectValue placeholder="All Statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Statuses</SelectItem>
                            <SelectItem value="pending">Pending</SelectItem>
                            <SelectItem value="confirmed">Confirmed</SelectItem>
                            <SelectItem value="completed">Completed</SelectItem>
                            <SelectItem value="cancelled">Cancelled</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

    
            <!-- Enhanced Bookings Table -->
            <div v-if="users.data.length > 0" class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <div class="overflow-x-auto max-w-full">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">ID</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Booking #</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Plan</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Customer</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Vehicle</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Dates</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Total Days</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Amount</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Payment</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(booking,index) in users.data" :key="booking.id" class="hover:bg-muted/25 transition-colors">
                                <TableCell class="whitespace-nowrap px-4 py-3 font-medium">
                                    {{ (users.current_page - 1) * users.per_page + index + 1 }}
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 font-mono">
                                    {{ booking.booking_number }}
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Badge variant="outline" class="capitalize">
                                        {{ booking.plan }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="font-medium">{{ booking.customer?.first_name }} {{ booking.customer?.last_name }}</div>
                                    <div class="text-sm text-muted-foreground">{{ booking.customer?.email }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="font-medium">{{ booking.vehicle?.brand }} {{ booking.vehicle?.model }}</div>
                                    <div class="text-sm text-muted-foreground">{{ booking.vehicle?.color || 'N/A' }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm">
                                        <div class="font-medium">{{ formatDate(booking.created_at) }}</div>
                                        <div class="text-muted-foreground text-xs">{{ booking.pickup_location }}</div>
                                    </div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="font-medium">{{ booking.total_days || 0 }} days</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm">
                                        <div class="font-medium">
                                            {{ formatCurrency(getAdminAmounts(booking).total, getAdminAmounts(booking).currency) }}
                                        </div>
                                        <div class="text-green-600 text-xs">
                                            Paid: {{ formatCurrency(getAdminAmounts(booking).paid, getAdminAmounts(booking).currency) }}
                                        </div>
                                        <div class="text-yellow-600 text-xs">
                                            Pending: {{ formatCurrency(getAdminAmounts(booking).pending, getAdminAmounts(booking).currency) }}
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Badge v-if="booking.payments?.length > 0"
                                          :variant="getPaymentBadgeVariant(booking.payments[0].payment_status)">
                                        {{ booking.payments[0].payment_status }}
                                    </Badge>
                                    <Badge v-else variant="outline">
                                        No Payment
                                    </Badge>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Badge :variant="getStatusBadgeBooking(booking.booking_status)" class="capitalize">
                                        {{ booking.booking_status }}
                                    </Badge>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                <div class="flex justify-end pt-4 pr-2">
                    <Pagination :current-page="users.current_page" :total-pages="users.last_page"
                        @page-change="handlePageChange" />
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="rounded-xl border bg-card p-12 text-center">
                <div class="flex flex-col items-center space-y-4">
                    <Calendar class="w-16 h-16 text-muted-foreground" />
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold text-foreground">No bookings found</h3>
                        <p class="text-muted-foreground">No bookings match your current search criteria.</p>
                    </div>
                </div>
            </div>

        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, watch, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import {Table, TableHeader, TableRow, TableHead, TableBody, TableCell} from "@/Components/ui/table";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Input } from "@/Components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';
import {
  Calendar,
  Clock,
  CheckCircle,
  CheckSquare,
  XCircle,
  Search
} from 'lucide-vue-next';
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import Pagination from '@/Components/ReusableComponents/Pagination.vue';

const props = defineProps({
    users: Object,
    statusCounts: Object,
    filters: Object,
    currentStatus: String,
    flash: Object,
});

const search = ref(props.filters.search || '');
const statusFilter = ref(props.currentStatus || 'all');

// Watch for changes in search and status filter
watch([search, statusFilter], () => {
    handleFilterChange();
});

const handleFilterChange = () => {
    router.get('/customer-bookings', {
        search: search.value,
        status: statusFilter.value
    }, {
        preserveState: true,
        replace: true,
    });
};

const handleSearch = () => {
    handleFilterChange();
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
        case 'confirmed':
            return 'default';
        case 'cancelled':
            return 'destructive';
        case 'failed':
            return 'destructive';
        default:
            return 'default';
    }
};

const getPaymentBadgeVariant = (paymentStatus) => {
    switch (paymentStatus) {
        case 'paid':
            return 'default';
        case 'pending':
            return 'secondary';
        case 'failed':
            return 'destructive';
        case 'refunded':
            return 'outline';
        default:
            return 'outline';
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

const getAdminAmounts = (booking) => {
    const amounts = booking.amounts || null;
    if (amounts && amounts.admin_currency) {
        return {
            currency: amounts.admin_currency,
            total: Number(amounts.admin_total_amount || 0),
            paid: Number(amounts.admin_paid_amount || 0),
            pending: Number(amounts.admin_pending_amount || 0),
        };
    }

    return {
        currency: booking.booking_currency || 'USD',
        total: Number(booking.total_amount || 0),
        paid: Number(booking.amount_paid || 0),
        pending: Number(booking.pending_amount || 0),
    };
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

// Flash message handling
onMounted(() => {
    if (props.flash?.success) {
        setTimeout(() => {
            router.clearFlashMessages();
        }, 3000);
    }
});
</script>
