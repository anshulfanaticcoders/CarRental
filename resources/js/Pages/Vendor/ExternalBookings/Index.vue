<template>
    <MyProfileLayout>
        <div class="space-y-5">
            <!-- Header -->
            <div class="vr-phead">
                <div>
                    <span class="vr-eyebrow"><Globe /> {{ tt('vendorprofilepages', 'operations_eyebrow', 'Operations') }}</span>
                    <h2>{{ tt('vendorprofilepages', 'external_bookings_header', 'External Bookings') }}</h2>
                    <p class="vr-sub">{{ tt('vendorprofilepages', 'external_bookings_subtitle', 'Bookings received through the Provider API from external companies.') }}</p>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="vr-stat-grid c4">
                <div class="vr-stat">
                    <div class="vr-ic vr-ic-green"><TrendingUp /></div>
                    <div class="vr-v">{{ formatNumber(props.analytics.total_revenue) }}</div>
                    <div class="vr-l">Lifetime Revenue</div>
                </div>
                <div class="vr-stat">
                    <div class="vr-ic vr-ic-teal"><BarChart3 /></div>
                    <div class="vr-v">{{ formatNumber(props.analytics.this_month_revenue) }}</div>
                    <div class="vr-l">This Month</div>
                </div>
                <div class="vr-stat">
                    <div class="vr-ic vr-ic-amber"><Clock /></div>
                    <div class="vr-v">{{ props.analytics.pending_count }}</div>
                    <div class="vr-l">Pending Action</div>
                </div>
                <div class="vr-stat">
                    <div class="vr-ic vr-ic-violet"><CheckCircle2 /></div>
                    <div class="vr-v">{{ props.analytics.confirmed_count + props.analytics.completed_count }}</div>
                    <div class="vr-l">Confirmed / Completed</div>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="vr-toolbar">
                <label class="vr-search">
                    <Search />
                    <input v-model="searchQuery" type="text" placeholder="Search bookings..." />
                </label>
                <select v-model="statusFilter" style="padding:10px 14px;min-width:150px;font-size:0.84rem;font-weight:500">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <!-- Table Card -->
            <div class="vr-panel">
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
                                <th class="px-4 py-3 text-left text-xs font-semibold text-[var(--gray-500)] uppercase tracking-wider">Amount</th>
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
                                    <span class="vr-cust">
                                        <span class="vr-ava">{{ booking.driver_first_name?.[0] }}{{ booking.driver_last_name?.[0] }}</span>
                                        <span class="min-w-0">
                                            <span class="cell-strong truncate block max-w-[150px]">{{ booking.driver_first_name }} {{ booking.driver_last_name }}</span>
                                            <span class="vr-mut truncate block max-w-[150px]">{{ booking.driver_email }}</span>
                                        </span>
                                    </span>
                                </td>

                                <!-- Company -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="vr-chip blue"><Globe class="w-3 h-3" /> {{ booking.consumer?.name || 'N/A' }}</span>
                                </td>

                                <!-- Dates -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-[var(--gray-700)]">{{ formatDateCompact(booking.pickup_date) }}</span>
                                    <span class="text-[var(--gray-300)] mx-1">&rarr;</span>
                                    <span class="text-[var(--gray-700)]">{{ formatDateCompact(booking.return_date) }}</span>
                                    <span class="block text-[11px] text-[var(--gray-400)] mt-0.5">{{ booking.total_days }} day{{ booking.total_days !== 1 ? 's' : '' }}</span>
                                </td>

                                <!-- Amount -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="font-semibold text-[var(--gray-900)]">
                                        {{ getCurrencySymbol(booking.currency) }}{{ formatNumber(booking.total_amount) }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="px-4 py-3">
                                    <span class="vr-chip capitalize" :class="vrStatus(booking.status)">{{ booking.status }}</span>
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
                <div v-else class="vr-empty">
                    <div class="e-ic"><Globe /></div>
                    <h4>No external bookings found</h4>
                    <p>Bookings from external API consumers will appear here.</p>
                </div>

                <!-- Pagination -->
                <div v-if="props.bookings.length" class="vr-pager">
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
import { ref, watch } from 'vue';
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';
import { Tooltip, TooltipTrigger, TooltipContent, TooltipProvider } from '@/Components/ui/tooltip';
import { Search, Eye, Globe, TrendingUp, Clock, CheckCircle2, BarChart3 } from 'lucide-vue-next';
import { getCurrencySymbol as registryCurrencySymbol } from '@/utils/currencyRegistry';
import { getCurrentInstance } from 'vue';

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;
const tt = (group, key, fallback) => {
    const v = _t(group, key);
    return (!v || v === key) ? fallback : v;
};
const vrStatus = (status) => ({ confirmed: 'blue', completed: 'ok', pending: 'warn', cancelled: 'bad' }[status] || 'mut');

const props = defineProps({
    bookings: { type: Array, required: true },
    pagination: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    analytics: {
        type: Object,
        default: () => ({
            total_bookings: 0,
            pending_count: 0,
            confirmed_count: 0,
            completed_count: 0,
            cancelled_count: 0,
            total_revenue: 0,
            this_month_revenue: 0,
        }),
    },
});

const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

// --- Currency helpers ---
const getCurrencySymbol = (currency) => registryCurrencySymbol(currency);

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
