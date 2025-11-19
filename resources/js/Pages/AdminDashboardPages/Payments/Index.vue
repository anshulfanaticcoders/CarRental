<template>
    <AdminDashboardLayout>
        <div class="container mx-auto p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold tracking-tight">Payments Management</h1>
                <div class="flex items-center gap-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-700">
                        <CreditCard class="w-4 h-4 mr-1" />
                        All Payments
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
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Payments Card -->
                <div class="relative bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-500 bg-opacity-20 rounded-lg">
                            <CreditCard class="w-6 h-6 text-blue-600" />
                        </div>
                        <Badge variant="secondary" class="bg-blue-500 text-white">
                            Total
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-blue-900">{{ statusCounts?.total || 0 }}</p>
                        <p class="text-sm text-blue-700 mt-1">Payments</p>
                        <div class="mt-2 pt-2 border-t border-blue-200">
                            <p class="text-xl font-semibold text-blue-800">
                                <span v-if="stats.currency_symbol === 'Mixed'">{{ formatNumber(stats.total_amount) }} (Mixed)</span>
                                <span v-else>{{ formatCurrency(stats.total_amount, stats.currency_symbol) }}</span>
                            </p>
                            <p class="text-xs text-blue-600 mt-1">Total Amount</p>
                        </div>
                    </div>
                </div>

                <!-- Successful Payments Card -->
                <div class="relative bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-green-500 bg-opacity-20 rounded-lg">
                            <CheckCircle class="w-6 h-6 text-green-600" />
                        </div>
                        <Badge variant="secondary" class="bg-green-500 text-white">
                            Success
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-green-900">{{ statusCounts?.successful || 0 }}</p>
                        <p class="text-sm text-green-700 mt-1">Successful</p>
                    </div>
                </div>

                <!-- Pending Payments Card -->
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
                        <p class="text-sm text-yellow-700 mt-1">Pending</p>
                    </div>
                </div>

                <!-- Failed Payments Card -->
                <div class="relative bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-red-500 bg-opacity-20 rounded-lg">
                            <XCircle class="w-6 h-6 text-red-600" />
                        </div>
                        <Badge variant="secondary" class="bg-red-500 text-white">
                            Failed
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-red-900">{{ statusCounts?.failed || 0 }}</p>
                        <p class="text-sm text-red-700 mt-1">Failed</p>
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
                            placeholder="Search payments by transaction ID, method..."
                            class="pl-10 pr-4 h-12 text-base"
                        />
                    </div>
                </div>
                <div>
                    <Select v-model="filters.status">
                        <SelectTrigger class="w-40 h-12">
                            <SelectValue placeholder="All Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="null">All Status</SelectItem>
                            <SelectItem value="succeeded">Completed</SelectItem>
                            <SelectItem value="pending">Pending</SelectItem>
                            <SelectItem value="failed">Failed</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div>
                    <Select v-model="filters.currency">
                        <SelectTrigger class="w-40 h-12">
                            <SelectValue placeholder="All Currencies" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Currencies</SelectItem>
                            <SelectItem v-for="currency in availableCurrencies" :key="currency" :value="currency">
                                {{ currency }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Enhanced Payments Table -->
            <div v-if="payments.data.length > 0" class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <div class="overflow-x-auto max-w-full">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">ID</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Transaction ID</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Currency</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Total Amount</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Payment Amount</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Amount Paid</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Pending Amount</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Payment Method</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Status</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Date</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(payment, index) in payments.data" :key="payment.id" class="hover:bg-muted/25 transition-colors">
                                <TableCell class="whitespace-nowrap px-4 py-3 font-medium">
                                    {{ (payments.current_page - 1) * payments.per_page + index + 1 }}
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 font-mono text-sm">
                                    {{ payment.transaction_id }}
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Badge :class="getCurrencyBadgeClass(payment.currency)" class="text-xs">
                                        {{ payment.currency }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="font-medium">{{ formatCurrency(payment.booking?.total_amount || 0, payment.currency) }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="font-medium">{{ formatCurrency(payment.amount, payment.currency) }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="text-green-600 font-medium">{{ formatCurrency(payment.booking?.amount_paid || 0, payment.currency) }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="text-yellow-600 font-medium">{{ formatCurrency(payment.booking?.pending_amount || 0, payment.currency) }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm">{{ payment.payment_method }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Badge :variant="getPaymentStatusBadgeVariant(payment.payment_status)" class="capitalize">
                                        {{ payment.payment_status }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm">{{ formatDate(payment.created_at) }}</div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                <div class="flex justify-end pt-4 pr-2">
                    <Pagination :current-page="payments.current_page" :total-pages="payments.last_page"
                        @page-change="handlePageChange" />
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="rounded-xl border bg-card p-12 text-center">
                <div class="flex flex-col items-center space-y-4">
                    <CreditCard class="w-16 h-16 text-muted-foreground" />
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold text-foreground">No payments found</h3>
                        <p class="text-muted-foreground">No payments match your current search criteria.</p>
                    </div>
                </div>
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import {Table, TableHeader, TableRow, TableHead, TableBody, TableCell} from "@/Components/ui/table";
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
    CreditCard,
    CheckCircle,
    Clock,
    XCircle,
    Search
} from 'lucide-vue-next';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';

const props = defineProps({
    payments: Object,
    stats: Object,
    statusCounts: Object,
    availableCurrencies: Array,
    filters: Object,
    flash: Object,
});

// Custom debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

const search = ref(props.filters?.search || '');

// Initialize filters with proper currency value
const filters = ref({
    status: props.filters?.status || null,
    currency: props.filters?.currency || 'all'
});

// Watch for changes in search and filters
watch([search, filters], () => {
    debouncedSearch();
}, { deep: true });

const formatNumber = (number) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(number);
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

// Payment status badge variant
const getPaymentStatusBadgeVariant = (status) => {
    switch (status) {
        case 'succeeded':
            return 'default';
        case 'pending':
            return 'secondary';
        case 'failed':
            return 'destructive';
        default:
            return 'outline';
    }
};

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`;
};

const handlePageChange = (page) => {
    router.get(
        route('admin.payments.index'),
        {
            search: search.value,
            status: filters.value.status,
            currency: filters.value.currency,
            page
        },
        { preserveState: true, preserveScroll: true }
    );
};

const debouncedSearch = debounce(() => {
    router.get(
        route('admin.payments.index'),
        {
            search: search.value,
            status: filters.value.status,
            currency: filters.value.currency,
            page: 1
        },
        { preserveState: true, preserveScroll: true }
    );
}, 300);

const updateFilters = () => {
    router.get(
        route('admin.payments.index'),
        {
            search: search.value,
            status: filters.value.status,
            currency: filters.value.currency,
            page: 1
        },
        { preserveState: true, preserveScroll: true }
    );
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