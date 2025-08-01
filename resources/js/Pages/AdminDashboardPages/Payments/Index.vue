<template>
    <AdminDashboardLayout>
        <div class="p-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="text-gray-500 text-sm">Total Payments</h3>
                    <p class="text-2xl font-bold">{{ stats.total_payments }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="text-gray-500 text-sm">Successful Payments</h3>
                    <p class="text-2xl font-bold text-green-600">{{ stats.successful_payments }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="text-gray-500 text-sm">Pending Payments</h3>
                    <p class="text-2xl font-bold text-yellow-600">{{ stats.pending_payments }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="text-gray-500 text-sm">Total Amount</h3>
                    <p class="text-2xl font-bold">
                        <span v-if="stats.currency_symbol === 'Mixed'">{{ formatNumber(stats.total_amount) }} (Mixed)</span>
                        <span v-else>{{ stats.currency_symbol }}{{ formatNumber(stats.total_amount) }}</span>
                    </p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <input v-model="search" type="text" placeholder="Search payments..."
                            class="w-full px-4 py-2 border rounded-lg" @input="debouncedSearch" />
                    </div>
                    <select v-model="filters.status" class="px-4 py-2 border rounded-lg" @change="updateFilters">
                        <option :value="null">All Status</option>
                        <option value="succeeded">Completed</option>
                        <option value="pending">Pending</option>
                        <option value="failed">Failed</option>
                    </select>
                    <select v-model="filters.currency" class="px-4 py-2 border rounded-lg" @change="updateFilters">
                        <option value="all">All Currencies</option>
                        <option v-for="currency in availableCurrencies" :key="currency" :value="currency">
                            {{ currency }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Payments Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID  
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Transaction ID
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Amount
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Payment Method
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="(payment, index) in payments.data" :key="payment.id">
                            <td class="px-6 py-4 whitespace-nowrap">  
                                {{ (payments.current_page - 1) * payments.per_page + index + 1 }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ payment.transaction_id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ payment.currency }}{{ formatNumber(payment.amount) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ payment.payment_method }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="{
                                    'px-2 py-1 text-xs font-semibold rounded-full': true,
                                    'bg-green-100 text-green-800': payment.payment_status === 'succeeded',
                                    'bg-yellow-100 text-yellow-800': payment.payment_status === 'pending',
                                    'bg-red-100 text-red-800': payment.payment_status === 'failed'
                                }">
                                    {{ payment.payment_status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ formatDate(payment.created_at) }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Updated Pagination -->
                <div class="px-6 py-4 bg-gray-50 flex justify-end">
                    <Pagination :current-page="payments.current_page" :total-pages="payments.last_page"
                        @page-change="handlePageChange" />
                </div>
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import Pagination from '@/Components/ReusableComponents/Pagination.vue';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';

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

const props = defineProps({
    payments: Object,
    stats: Object,
    filters: Object,
    availableCurrencies: Array
})

const search = ref(props.filters.search)

// Initialize filters with proper currency value
const filters = ref({
    status: props.filters.status || null,
    currency: props.filters.currency || 'all'
})

const formatNumber = (number) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(number)
}

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
    )
}

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
    )
}, 300)

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
    )
}
</script>

<style scoped>
table th{
    font-size: 0.95rem;
}
table td{
    font-size: 0.875rem;
}
</style>
