<template>
    <MyProfileLayout>
        <!-- Loader Overlay -->
        <div v-if="isLoading" class="fixed inset-0 z-50 flex items-center justify-center bg-white/70 backdrop-blur-sm">
            <img :src="loaderVariant" alt="Loading..." class="h-20 w-20" />
        </div>

        <div class="space-y-5">
            <!-- Header -->
            <div class="vr-phead">
                <div>
                    <span class="vr-eyebrow"><CalendarCheck /> {{ tt('vendorprofilepages', 'operations_eyebrow', 'Operations') }}</span>
                    <h2>{{ tt('vendorprofilepages', 'booking_details_header', 'Bookings') }}</h2>
                    <p class="vr-sub">{{ tt('vendorprofilepages', 'booking_details_subtitle', 'Manage and update booking records.') }}</p>
                </div>
            </div>

            <div class="vr-toolbar">
                <label class="vr-search">
                    <Search />
                    <input v-model="searchQuery" type="text"
                        :placeholder="_t('vendorprofilepages', 'search_bookings_placeholder')" />
                </label>
            </div>

            <!-- Table Card -->
            <div class="vr-panel">
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
                                    <span class="vr-chip capitalize" :class="vrStatus(booking.booking_status)">{{ booking.booking_status }}</span>
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
                <div v-else class="vr-empty">
                    <div class="e-ic">
                        <CalendarX2 />
                    </div>
                    <h4>{{ tt('vendorprofilepages', 'no_bookings_found_text', 'No bookings found') }}</h4>
                    <p>{{ tt('vendorprofilepages', 'no_bookings_sub', 'Try adjusting your search or filters.') }}</p>
                </div>

                <!-- Pagination -->
                <div v-if="filteredBookings.length" class="vr-pager">
                    <span class="info">
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
import { Tooltip, TooltipTrigger, TooltipContent, TooltipProvider } from '@/Components/ui/tooltip';
import { useToast } from 'vue-toastification';
import loaderVariant from '../../../../assets/loader-variant.svg';
import { Search, Eye, ShieldCheck, Ban, CalendarX2, CalendarCheck } from 'lucide-vue-next';
import { getCurrencySymbol as registryCurrencySymbol } from '@/utils/currencyRegistry';

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;
const tt = (group, key, fallback) => {
    const v = _t(group, key);
    return (!v || v === key) ? fallback : v;
};
const toast = useToast();

const props = defineProps({
    bookings: { type: Array, required: true },
    pagination: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const searchQuery = ref('');
const isLoading = ref(false);

// --- Currency helpers ---
const getCurrencySymbol = (currency) => registryCurrencySymbol(currency);

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
const vrStatus = (status) => ({
    confirmed: 'ok',
    completed: 'ok',
    pending: 'warn',
    cancelled: 'bad',
}[status] || 'mut');

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
