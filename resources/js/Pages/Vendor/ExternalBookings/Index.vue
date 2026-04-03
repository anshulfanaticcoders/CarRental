<template>
    <MyProfileLayout>
        <div class="space-y-5">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-[var(--gray-900)]">External Bookings</h1>
                    <p class="text-sm text-[var(--gray-500)] mt-0.5">Bookings received through the Provider API from external companies.</p>
                </div>
                <div class="flex items-center gap-3">
                    <select
                        v-model="statusFilter"
                        class="h-10 px-3 text-sm border border-[var(--gray-200)] rounded-lg bg-white focus:ring-2 focus:ring-[var(--primary-400)] focus:border-[var(--primary-400)] outline-none transition-colors"
                    >
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <div class="relative w-full sm:w-72">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--gray-400)]" />
                        <Input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search bookings..."
                            class="pl-9 w-full h-10 text-sm"
                        />
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="rounded-xl border border-[var(--gray-200)] bg-white shadow-sm overflow-hidden">
                <div v-if="props.bookings.length" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[var(--gray-200)] bg-[var(--gray-50)]">
                                <th class="px-4 py-3 text-left text-xs font-semibold text-[var(--gray-500)] uppercase tracking-wider w-[44px]">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-[var(--gray-500)] uppercase tracking-wider">Booking</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-[var(--gray-500)] uppercase tracking-wider">Vehicle</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-[var(--gray-500)] uppercase tracking-wider">Driver</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-[var(--gray-500)] uppercase tracking-wider">Company</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-[var(--gray-500)] uppercase tracking-wider">Dates</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-[var(--gray-500)] uppercase tracking-wider">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-[var(--gray-500)] uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-[var(--gray-500)] uppercase tracking-wider w-[80px]">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--gray-100)]">
                            <tr
                                v-for="(booking, index) in props.bookings"
                                :key="booking.id"
                                class="hover:bg-[var(--primary-50)]/50 transition-colors"
                            >
                                <!-- # -->
                                <td class="px-4 py-3 text-[var(--gray-400)] tabular-nums">
                                    {{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}
                                </td>

                                <!-- Booking -->
                                <td class="px-4 py-3">
                                    <span class="font-mono text-xs font-semibold text-[var(--primary-700)]">{{ booking.booking_number }}</span>
                                    <span class="block text-[11px] text-[var(--gray-400)] mt-0.5">{{ formatDateShort(booking.created_at) }}</span>
                                </td>

                                <!-- Vehicle -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="font-medium text-[var(--gray-800)]">{{ booking.vehicle_name || booking.vehicle?.brand + ' ' + booking.vehicle?.model }}</span>
                                </td>

                                <!-- Driver -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-[var(--primary-100)] text-[var(--primary-700)] text-[10px] font-bold shrink-0">
                                            {{ booking.driver_first_name?.[0] }}{{ booking.driver_last_name?.[0] }}
                                        </span>
                                        <div class="min-w-0">
                                            <span class="font-medium text-[var(--gray-800)] truncate block max-w-[130px]">
                                                {{ booking.driver_first_name }} {{ booking.driver_last_name }}
                                            </span>
                                            <span class="text-[11px] text-[var(--gray-400)] truncate block max-w-[130px]">{{ booking.driver_email }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Company -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-[11px] font-medium">
                                        <Globe class="w-3 h-3 mr-1" />
                                        {{ booking.consumer?.name || 'N/A' }}
                                    </span>
                                </td>

                                <!-- Dates -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-[var(--gray-700)]">{{ formatDateCompact(booking.pickup_date) }}</span>
                                    <span class="text-[var(--gray-300)] mx-1">&rarr;</span>
                                    <span class="text-[var(--gray-700)]">{{ formatDateCompact(booking.return_date) }}</span>
                                    <span class="block text-[11px] text-[var(--gray-400)] mt-0.5">{{ booking.total_days }} day{{ booking.total_days !== 1 ? 's' : '' }}</span>
                                </td>

                                <!-- Amount -->
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <span class="font-semibold text-[var(--gray-900)]">
                                        {{ getCurrencySymbol(booking.currency) }}{{ formatNumber(booking.total_amount) }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold capitalize"
                                        :class="statusBadgeClass(booking.status)"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="statusDotClass(booking.status)"></span>
                                        {{ booking.status }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-3">
                                    <TooltipProvider>
                                        <div class="flex items-center justify-center gap-1">
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <button
                                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-[var(--primary-600)] hover:bg-[var(--primary-50)] hover:text-[var(--primary-800)] transition-colors"
                                                        @click="viewBooking(booking.id)"
                                                    >
                                                        <Eye class="w-4 h-4" />
                                                    </button>
                                                </TooltipTrigger>
                                                <TooltipContent side="top"><p>View Details</p></TooltipContent>
                                            </Tooltip>
                                        </div>
                                    </TooltipProvider>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-else class="px-6 py-20 text-center">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-[var(--gray-100)] mb-4">
                        <Globe class="w-6 h-6 text-[var(--gray-400)]" />
                    </div>
                    <p class="text-sm font-medium text-[var(--gray-500)]">No external bookings found</p>
                    <p class="text-xs text-[var(--gray-400)] mt-1">Bookings from external API consumers will appear here.</p>
                </div>

                <!-- Pagination -->
                <div v-if="props.bookings.length" class="flex items-center justify-between px-4 py-3 border-t border-[var(--gray-200)] bg-[var(--gray-50)]/50">
                    <span class="text-xs text-[var(--gray-500)]">
                        Showing {{ (pagination.current_page - 1) * pagination.per_page + 1 }}&ndash;{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} of {{ pagination.total }}
                    </span>
                    <Pagination
                        :current-page="pagination.current_page"
                        :total-pages="pagination.last_page"
                        @page-change="handlePageChange"
                    />
                </div>
            </div>
        </div>
    </MyProfileLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';
import { Input } from '@/Components/ui/input';
import { Tooltip, TooltipTrigger, TooltipContent, TooltipProvider } from '@/Components/ui/tooltip';
import { Search, Eye, Globe } from 'lucide-vue-next';

const props = defineProps({
    bookings: { type: Array, required: true },
    pagination: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

// --- Currency helpers ---
const getCurrencySymbol = (currency) => {
    const symbols = {
        'USD': '$', 'EUR': '€', 'GBP': '£', 'JPY': '¥',
        'AUD': 'A$', 'CAD': 'C$', 'CHF': 'Fr', 'HKD': 'HK$',
        'SGD': 'S$', 'SEK': 'kr', 'KRW': '₩', 'NOK': 'kr',
        'NZD': 'NZ$', 'INR': '₹', 'MXN': 'Mex$', 'ZAR': 'R',
        'AED': 'AED', 'MAD': 'MAD',
    };
    return symbols[currency] || currency || '$';
};

const formatNumber = (number) => {
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(number);
};

// --- Date helpers ---
const formatDateCompact = (dateStr) => {
    if (!dateStr) return 'N/A';
    const d = new Date(dateStr);
    return `${String(d.getDate()).padStart(2, '0')} ${d.toLocaleString('en-GB', { month: 'short' })}`;
};

const formatDateShort = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

// --- Status helpers ---
const statusBadgeClass = (status) => ({
    confirmed: 'bg-blue-50 text-blue-700',
    completed: 'bg-emerald-50 text-emerald-700',
    pending: 'bg-amber-50 text-amber-700',
    cancelled: 'bg-red-50 text-red-600',
}[status] || 'bg-gray-50 text-gray-600');

const statusDotClass = (status) => ({
    confirmed: 'bg-blue-500',
    completed: 'bg-emerald-500',
    pending: 'bg-amber-500',
    cancelled: 'bg-red-500',
}[status] || 'bg-gray-400');

// --- Actions ---
const viewBooking = (bookingId) => {
    router.get(route('vendor.external-bookings.show', { locale: usePage().props.locale, apiBooking: bookingId }));
};

const handlePageChange = (page) => {
    router.get(
        route('vendor.external-bookings.index', { locale: usePage().props.locale }),
        { search: searchQuery.value, status: statusFilter.value, page },
        { preserveState: true, preserveScroll: true },
    );
};

// --- Search & Filter watchers ---
let searchTimeout = null;
watch(searchQuery, (newQuery) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            route('vendor.external-bookings.index', { locale: usePage().props.locale }),
            { search: newQuery, status: statusFilter.value },
            { preserveState: true, preserveScroll: true },
        );
    }, 300);
});

watch(statusFilter, (newStatus) => {
    router.get(
        route('vendor.external-bookings.index', { locale: usePage().props.locale }),
        { search: searchQuery.value, status: newStatus },
        { preserveState: true, preserveScroll: true },
    );
});
</script>

<style scoped>
@media screen and (max-width: 640px) {
    th, td { font-size: 0.7rem; }
    th { white-space: nowrap; }
}
</style>
