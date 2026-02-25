<template>
    <MyProfileLayout>
        <!-- Loader Overlay -->
        <div v-if="isLoading" class="fixed inset-0 z-50 flex items-center justify-center bg-white/70 backdrop-blur-sm">
            <img :src="loaderVariant" alt="Loading..." class="h-20 w-20" />
        </div>

        <div class="space-y-5">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-[var(--gray-900)]">
                        {{ _t('vendorprofilepages', 'booking_details_header') }}
                    </h1>
                    <p class="text-sm text-[var(--gray-500)] mt-0.5">
                        {{ _t('vendorprofilepages', 'booking_details_subtitle') || 'Manage and update booking records.' }}
                    </p>
                </div>
                <div class="relative w-full sm:w-72">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--gray-400)]" />
                    <Input
                        v-model="searchQuery"
                        type="text"
                        :placeholder="_t('vendorprofilepages', 'search_bookings_placeholder')"
                        class="pl-9 w-full h-10 text-sm"
                    />
                </div>
            </div>

            <!-- Table Card -->
            <div class="rounded-xl border border-[var(--gray-200)] bg-white shadow-sm overflow-hidden">
                <div v-if="filteredBookings.length" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[var(--gray-200)] bg-[var(--gray-50)]">
                                <th class="px-4 py-3 text-left text-xs font-semibold text-[var(--gray-500)] uppercase tracking-wider w-[44px]">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-[var(--gray-500)] uppercase tracking-wider">Booking</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-[var(--gray-500)] uppercase tracking-wider">Customer</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-[var(--gray-500)] uppercase tracking-wider">Vehicle</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-[var(--gray-500)] uppercase tracking-wider">Dates</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-[var(--gray-500)] uppercase tracking-wider">Total</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-[var(--gray-500)] uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-[var(--gray-500)] uppercase tracking-wider w-[110px]">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--gray-100)]">
                            <tr
                                v-for="(booking, index) in filteredBookings"
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

                                <!-- Customer -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-[var(--primary-100)] text-[var(--primary-700)] text-[10px] font-bold shrink-0">
                                            {{ booking.customer?.first_name?.[0] }}{{ booking.customer?.last_name?.[0] }}
                                        </span>
                                        <span class="font-medium text-[var(--gray-800)] truncate max-w-[130px]">
                                            {{ booking.customer?.first_name }} {{ booking.customer?.last_name }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Vehicle -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="font-medium text-[var(--gray-800)]">{{ booking.vehicle?.brand }}</span>
                                    <span class="inline-block ml-1.5 px-2 py-0.5 rounded-full bg-[var(--primary-50)] text-[var(--primary-700)] text-[11px] font-medium">{{ booking.vehicle?.model }}</span>
                                </td>

                                <!-- Dates -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-[var(--gray-700)]">{{ formatDateCompact(booking.pickup_date) }}</span>
                                    <span class="text-[var(--gray-300)] mx-1">&rarr;</span>
                                    <span class="text-[var(--gray-700)]">{{ formatDateCompact(booking.return_date) }}</span>
                                    <span class="block text-[11px] text-[var(--gray-400)] mt-0.5">{{ booking.total_days }} day{{ booking.total_days !== 1 ? 's' : '' }}</span>
                                </td>

                                <!-- Total -->
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <span class="font-semibold text-[var(--gray-900)]">
                                        {{ getCurrencySymbol(getVendorCurrency(booking)) }}{{ formatNumber(getVendorAmount(booking, 'total_amount')) }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold capitalize"
                                        :class="statusBadgeClass(booking.booking_status)"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="statusDotClass(booking.booking_status)"></span>
                                        {{ booking.booking_status }}
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

                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <button
                                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-[var(--accent-600)] hover:bg-[var(--accent-100)] hover:text-[var(--accent-600)] transition-colors"
                                                        @click="goToDamageProtection(booking.id)"
                                                    >
                                                        <ShieldCheck class="w-4 h-4" />
                                                    </button>
                                                </TooltipTrigger>
                                                <TooltipContent side="top"><p>Damage Protection</p></TooltipContent>
                                            </Tooltip>

                                            <Tooltip v-if="booking.booking_status !== 'cancelled'">
                                                <TooltipTrigger as-child>
                                                    <button
                                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-400 hover:bg-red-50 hover:text-red-600 transition-colors"
                                                        @click="cancelBooking(booking.id)"
                                                    >
                                                        <Ban class="w-4 h-4" />
                                                    </button>
                                                </TooltipTrigger>
                                                <TooltipContent side="top"><p>Cancel Booking</p></TooltipContent>
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
                        <CalendarX2 class="w-6 h-6 text-[var(--gray-400)]" />
                    </div>
                    <p class="text-sm font-medium text-[var(--gray-500)]">{{ _t('vendorprofilepages', 'no_bookings_found_text') }}</p>
                    <p class="text-xs text-[var(--gray-400)] mt-1">Try adjusting your search or filters</p>
                </div>

                <!-- Pagination -->
                <div v-if="filteredBookings.length" class="flex items-center justify-between px-4 py-3 border-t border-[var(--gray-200)] bg-[var(--gray-50)]/50">
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
import { ref, computed, watch, getCurrentInstance } from 'vue';
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';
import { Input } from '@/Components/ui/input';
import { Tooltip, TooltipTrigger, TooltipContent, TooltipProvider } from '@/Components/ui/tooltip';
import { useToast } from 'vue-toastification';
import loaderVariant from '../../../../assets/loader-variant.svg';
import { Search, Eye, ShieldCheck, Ban, CalendarX2 } from 'lucide-vue-next';

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;
const toast = useToast();

const props = defineProps({
    bookings: { type: Array, required: true },
    pagination: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const searchQuery = ref('');
const isLoading = ref(false);

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

const getVendorCurrency = (booking) => {
    return booking.amounts?.vendor_currency
        || booking.vendor_profile?.currency
        || booking.booking_currency
        || 'EUR';
};

const getVendorAmount = (booking, field) => {
    const vendorFieldMap = {
        total_amount: 'vendor_total_amount',
        amount_paid: 'vendor_paid_amount',
        pending_amount: 'vendor_pending_amount',
    };
    const mappedField = vendorFieldMap[field];
    if (mappedField && booking.amounts?.[mappedField] != null) {
        return parseFloat(booking.amounts[mappedField]);
    }
    if (booking.amounts?.[field] != null) {
        return parseFloat(booking.amounts[field]);
    }
    return parseFloat(booking[field] || 0);
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
    confirmed: 'bg-emerald-50 text-emerald-700',
    completed: 'bg-emerald-50 text-emerald-700',
    pending: 'bg-amber-50 text-amber-700',
    cancelled: 'bg-red-50 text-red-600',
}[status] || 'bg-gray-50 text-gray-600');

const statusDotClass = (status) => ({
    confirmed: 'bg-emerald-500',
    completed: 'bg-emerald-500',
    pending: 'bg-amber-500',
    cancelled: 'bg-red-500',
}[status] || 'bg-gray-400');

// --- Actions ---
const viewBooking = (bookingId) => {
    router.get(route('bookings.show', { locale: usePage().props.locale, booking: bookingId }));
};

const goToDamageProtection = (bookingId) => {
    router.get(route('vendor.damage-protection.index', { locale: usePage().props.locale, booking: bookingId }));
};

const cancelBooking = async (bookingId) => {
    if (confirm(_t('vendorprofilepages', 'confirm_cancel_booking_message'))) {
        try {
            await axios.post(route('bookings.cancel', { locale: usePage().props.locale, booking: bookingId }));
            router.reload();
        } catch (err) {
            console.error('Error canceling booking:', err);
            alert(_t('vendorprofilepages', 'alert_failed_to_cancel_booking'));
        }
    }
};

const handlePageChange = (page) => {
    router.get(route('bookings.index', { locale: usePage().props.locale }), { ...props.filters, page }, { preserveState: true, preserveScroll: true });
};

// --- Search ---
const filteredBookings = computed(() => {
    const query = searchQuery.value.toLowerCase();
    if (!query) return props.bookings;
    return props.bookings.filter(b =>
        b.booking_number?.toLowerCase().includes(query) ||
        b.customer?.first_name?.toLowerCase().includes(query) ||
        b.customer?.last_name?.toLowerCase().includes(query) ||
        b.vehicle?.brand?.toLowerCase().includes(query) ||
        b.vehicle?.model?.toLowerCase().includes(query) ||
        b.booking_status?.toLowerCase().includes(query)
    );
});

watch(searchQuery, (newQuery) => {
    router.get(
        route('bookings.index', { locale: usePage().props.locale }),
        { search: newQuery },
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
