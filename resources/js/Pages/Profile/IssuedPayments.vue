<template>
    <MyProfileLayout>
        <!-- Loader Overlay -->
        <div v-if="isLoading" class="fixed inset-0 z-50 flex items-center justify-center bg-white bg-opacity-70">
            <div class="loader h-12 w-12 border-4 border-gray-300 border-t-blue-600 rounded-full animate-spin"></div>
        </div>

        <div class="vr-phead">
            <div>
                <span class="vr-eyebrow"><CreditCard /> {{ tt('customerprofilepages', 'billing_label', 'Billing') }}</span>
                <h2>{{ tt('customerprofilepages', 'issued_payments_header', 'Issued Payments') }}</h2>
                <p class="vr-sub">{{ tt('customerprofilepages', 'issued_payments_subtitle', 'Track payments issued for your bookings.') }}</p>
            </div>
        </div>

        <div class="vr-toolbar">
            <label class="vr-search">
                <Search />
                <input type="text" v-model="searchQuery"
                    :placeholder="_t('customerprofilepages', 'search_payments_placeholder')" />
            </label>
        </div>

        <div v-if="filteredPayments.length" class="vr-panel">
            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th>{{ _t('customerprofilepages', 'table_header_id') }}</th>
                            <th>{{ _t('customerprofilepages', 'table_header_booking_id') }}</th>
                            <th>{{ _t('customerprofilepages', 'table_header_vehicle') }}</th>
                            <th>{{ _t('customerprofilepages', 'table_header_payment_date') }}</th>
                            <th>{{ _t('customerprofilepages', 'table_header_amount_paid') }}</th>
                            <th>{{ _t('customerprofilepages', 'table_header_payment_method') }}</th>
                            <th>{{ _t('customerprofilepages', 'table_header_payment_status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(payment, index) in filteredPayments" :key="payment.id">
                            <td>{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
                            <td class="cell-strong">{{ payment.booking?.booking_number || tt('customerprofilepages', 'not_applicable', 'N/A') }}</td>
                            <td>
                                <span class="vr-cust">
                                    <span class="vr-ava">{{ vehInitials(payment.booking?.vehicle_name) }}</span>
                                    <span class="cell-strong">{{ payment.booking?.vehicle_name || tt('customerprofilepages', 'not_applicable', 'N/A') }}</span>
                                </span>
                            </td>
                            <td>{{ formatDate(payment.created_at) }}</td>
                            <td class="text-emerald-600 font-semibold">
                                {{ getCurrencySymbol(getBookingCurrency(payment)) }} {{ formatNumber(getBookingAmount(payment, 'amount_paid')) }}
                            </td>
                            <td>
                                <span v-if="payment.payment_method" class="vr-vbadge">{{ payment.payment_method }}</span>
                                <span v-else>{{ _t('customerprofilepages', 'not_applicable') }}</span>
                            </td>
                            <td>
                                <span class="vr-chip" :class="{
                                    ok: payment.payment_status === 'succeeded',
                                    warn: payment.payment_status === 'pending',
                                    bad: payment.payment_status === 'failed',
                                    mut: !payment.payment_status
                                }">
                                    {{ payment.payment_status || tt('customerprofilepages', 'not_applicable', 'N/A') }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="pagination && pagination.last_page > 1" class="vr-pager">
                <span class="info">{{ tt('common', 'page_label', 'Page') }} {{ pagination.current_page }} / {{ pagination.last_page }}</span>
                <Pagination :current-page="pagination.current_page" :total-pages="pagination.last_page"
                    @page-change="handlePageChange" />
            </div>
        </div>

        <div v-else class="vr-panel">
            <div class="vr-empty">
                <div class="e-ic">
                    <Receipt />
                </div>
                <h4>{{ tt('customerprofilepages', 'no_payments_found_text', 'No payments found') }}</h4>
                <p>{{ tt('customerprofilepages', 'issued_payments_subtitle', 'Track payments issued for your bookings.') }}</p>
            </div>
        </div>
    </MyProfileLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch, getCurrentInstance } from 'vue';
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { router } from '@inertiajs/vue3';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';
import { Search, Receipt, CreditCard } from 'lucide-vue-next';
import { getCurrencySymbol as registryCurrencySymbol } from '@/utils/currencyRegistry';

const props = defineProps({
    payments: {
        type: Array,
        default: () => []
    },
    pagination: {
        type: Object,
        default: () => ({})
    },
    filters: {
        type: Object,
        default: () => ({})
    }
});

const searchQuery = ref(props.filters.search || '');
const isLoading = ref(false);

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;

// _t returns the raw key when a translation is missing — fall back to English.
const tt = (group, key, fallback) => {
    const v = _t(group, key);
    return (!v || v === key) ? fallback : v;
};

const vehInitials = (name) => (name || '?').trim().split(/\s+/).map(w => w[0]).join('').slice(0, 2).toUpperCase();

const handlePageChange = (page) => {
    router.get(route('profile.payments'), { ...props.filters, page, search: searchQuery.value }, { preserveState: true, preserveScroll: true });
};

const formatDate = (dateStr) => {
    if (!dateStr) return _t('customerprofilepages', 'not_applicable');
    const date = new Date(dateStr);
    return `${String(date.getMonth() + 1).padStart(2, '0')}/${String(date.getDate()).padStart(2, '0')}/${date.getFullYear()}`;
};

const formatNumber = (number) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(number);
};

const getCurrencySymbol = (currency) => registryCurrencySymbol(currency);

const getBookingCurrency = (payment) => {
    return payment.booking?.amounts?.booking_currency || payment.booking?.booking_currency || 'EUR';
};

const getBookingAmount = (payment, field) => {
    const bookingFieldMap = {
        amount_paid: 'booking_amount_paid',
        total_amount: 'booking_total_amount',
        pending_amount: 'booking_pending_amount',
    };

    const mappedField = bookingFieldMap[field];
    const amount = mappedField ? payment.booking?.amounts?.[mappedField] : payment.booking?.amounts?.[field];
    if (amount !== undefined && amount !== null) {
        return parseFloat(amount);
    }

    if (field === 'amount_paid') {
        return parseFloat(payment.amount || 0);
    }

    return parseFloat(payment.booking?.[field] || 0);
};

const filteredPayments = computed(() => {
    const query = searchQuery.value.toLowerCase();
    if (!query) {
        return props.payments;
    }
    return props.payments.filter(payment => {
        return (
            (payment.booking?.booking_number && payment.booking.booking_number.toLowerCase().includes(query)) ||
            (payment.booking?.vehicle?.brand && payment.booking.vehicle.brand.toLowerCase().includes(query)) ||
            (payment.booking?.vehicle?.model && payment.booking.vehicle.model.toLowerCase().includes(query)) ||
            (payment.payment_method && payment.payment_method.toLowerCase().includes(query)) ||
            (payment.payment_status && payment.payment_status.toLowerCase().includes(query)) ||
            (payment.amount && payment.amount.toString().toLowerCase().includes(query)) ||
            (payment.payment_date && formatDate(payment.payment_date).toLowerCase().includes(query))
        );
    });
});

// Watch for changes in searchQuery and fetch new results
let searchTimeout = null;
watch(searchQuery, (newQuery) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    searchTimeout = setTimeout(() => {
        router.get(
            route('profile.payments'),
            { search: newQuery, page: 1 }, // Reset to page 1 on new search
            { preserveState: true, preserveScroll: true, replace: true }
        );
    }, 300); // Debounce search
});

onMounted(() => {
    if (!props.payments.length && route().current('profile.payments')) {
        router.reload({ only: ['payments', 'pagination'] });
    }
});

</script>

<style scoped>
.loader {
    border-top-color: #3490dc;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@media screen and (max-width:768px) {
    th {
        font-size: 0.75rem;
    }

    td {
        font-size: 0.75rem;
    }
}
</style>
