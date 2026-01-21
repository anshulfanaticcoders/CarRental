<template>
    <MyProfileLayout>
        <!-- Loader Overlay -->
        <div v-if="isLoading" class="fixed inset-0 z-50 flex items-center justify-center bg-white bg-opacity-70">
            <div class="loader h-12 w-12 border-4 border-gray-300 border-t-blue-600 rounded-full animate-spin"></div>
        </div>
        <div class="">
            <p
                class="text-[1.75rem] font-bold text-gray-800 bg-customLightPrimaryColor p-4 rounded-[12px] mb-[1rem] max-[768px]:text-[1.2rem]">
                {{ _t('customerprofilepages', 'issued_payments_header') }}</p>

            <div class="mb-4">
                <input type="text" v-model="searchQuery" :placeholder="_t('customerprofilepages', 'search_payments_placeholder')"
                    class="px-4 py-2 border border-gray-300 rounded-md w-full" />
            </div>

            <div v-if="filteredPayments.length" class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ _t('customerprofilepages', 'table_header_id') }}</th>
                            <th class="px-4 py-2 text-left text-sm font-medium tracking-wider whitespace-nowrap">{{ _t('customerprofilepages', 'table_header_booking_id') }}
                                </th>
                            <th class="px-4 py-2 text-left text-sm font-medium tracking-wider whitespace-nowrap">{{ _t('customerprofilepages', 'table_header_vehicle') }}
                            </th>
                            <th class="px-4 py-2 text-left text-sm font-medium tracking-wider whitespace-nowrap">{{ _t('customerprofilepages', 'table_header_payment_date') }}
                                </th>
                            <th class="px-4 py-2 text-left text-sm font-medium tracking-wider whitespace-nowrap">{{ _t('customerprofilepages', 'table_header_amount_paid') }}
                                </th>
                            <th class="px-4 py-2 text-left text-sm font-medium tracking-wider whitespace-nowrap">{{ _t('customerprofilepages', 'table_header_payment_method') }}
                                </th>
                            <th class="px-4 py-2 text-left text-sm font-medium tracking-wider whitespace-nowrap">{{ _t('customerprofilepages', 'table_header_payment_status') }}
                                </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(payment, index) in filteredPayments" :key="payment.id"
                            class="border-b hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">{{ (pagination.current_page - 1) *
                                pagination.per_page + index + 1 }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700 whitespace-nowrap">{{ payment.booking?.booking_number || _t('customerprofilepages', 'not_applicable') }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-700 whitespace-nowrap">
                                {{ payment.booking?.vehicle?.brand || _t('customerprofilepages', 'not_applicable') }} <span
                                    class="bg-customLightPrimaryColor ml-2 p-1 rounded-[12px]">{{ payment.booking?.vehicle?.model || _t('customerprofilepages', 'not_applicable')
                                    }}</span>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-700 whitespace-nowrap">{{
                                formatDate(payment.created_at) }}</td>
                            <td class="px-4 py-2 text-sm text-green-600 whitespace-nowrap font-medium">
                                {{ getCurrencySymbol(getBookingCurrency(payment)) }} {{ formatNumber(getBookingAmount(payment, 'amount_paid')) }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-700 whitespace-nowrap">{{ payment.payment_method || _t('customerprofilepages', 'not_applicable') }}
                            </td>
                            <td class="px-4 py-2 text-sm capitalize">
                                <span :class="{
                                    'text-green-600 font-semibold': payment.payment_status === 'succeeded',
                                    'text-yellow-500 font-semibold': payment.payment_status === 'pending',
                                    'text-red-500 font-semibold': payment.payment_status === 'failed',
                                    'text-gray-500 font-semibold': !payment.payment_status
                                }">
                                    {{ payment.payment_status || _t('customerprofilepages', 'not_applicable') }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="text-center py-6">
                <span class="text-gray-500">{{ _t('customerprofilepages', 'no_payments_found_text') }}</span>
            </div>
            <div v-if="pagination && pagination.last_page > 1" class="mt-[1rem] flex justify-end">
                <Pagination :current-page="pagination.current_page" :total-pages="pagination.last_page"
                    @page-change="handlePageChange" />
            </div>
        </div>
    </MyProfileLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch, getCurrentInstance } from 'vue';
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { router } from '@inertiajs/vue3';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';

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

const getCurrencySymbol = (currency) => {
    const symbols = {
        'USD': '$', 'EUR': '€', 'GBP': '£', 'JPY': '¥',
        'AUD': 'A$', 'CAD': 'C$', 'CHF': 'Fr', 'HKD': 'HK$',
        'SGD': 'S$', 'SEK': 'kr', 'KRW': '₩', 'NOK': 'kr',
        'NZD': 'NZ$', 'INR': '₹', 'MXN': 'Mex$', 'ZAR': 'R',
        'AED': 'AED', 'MAD': 'د.م.', 'TRY': '₺'
    };
    return symbols[currency] || '$';
};

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
