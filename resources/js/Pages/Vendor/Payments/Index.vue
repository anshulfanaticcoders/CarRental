<template>
  <MyProfileLayout>
    <div class="">
      <div class="mb-6">
        <p class="text-[1.75rem] font-bold text-gray-800 bg-customLightPrimaryColor p-4 rounded-[12px] mb-[1rem] max-[768px]:text-[1.2rem]">
          {{ _t('vendorprofilepages', 'payment_history_header') }}</p>
        <p class="text-gray-600">{{ _t('vendorprofilepages', 'payment_history_subheader') }}</p>
      </div>

      <!-- Search Bar -->
      <div class="mb-4">
        <input type="text" v-model="searchQuery" :placeholder="_t('vendorprofilepages', 'search_payments_placeholder')" class="px-4 py-2 border border-gray-300 rounded-md w-full" />
      </div>

      <!-- No Payments -->
      <div v-if="!filteredPayments.length" class="bg-gray-50 p-8 text-center rounded-md">
        <p class="text-gray-600">{{ _t('vendorprofilepages', 'no_payment_history_found_text') }}</p>
      </div>

      <!-- Payments Table -->
      <div v-else class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ _t('vendorprofilepages', 'table_id_header') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ _t('vendorprofilepages', 'table_transaction_id_header') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ _t('vendorprofilepages', 'table_customer_header') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ _t('vendorprofilepages', 'table_vehicle_header') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Amount Paid
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Total Amount
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Pending Amount
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ _t('vendorprofilepages', 'table_payment_method_header') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ _t('vendorprofilepages', 'status_table_header') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ _t('vendorprofilepages', 'table_date_header') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(payment, index) in filteredPayments" :key="payment.id">
              <td class="px-6 py-4 whitespace-nowrap">{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm font-medium text-gray-900">{{ payment.transaction_id }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm text-gray-900 mr-2">{{ payment.booking?.customer?.first_name || _t('vendorprofilepages', 'not_applicable_text') }}</span>
                <span class="text-sm text-gray-900">{{ payment.booking?.customer?.last_name || _t('vendorprofilepages', 'not_applicable_text') }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm text-gray-900">{{ payment.booking?.vehicle?.brand || _t('vendorprofilepages', 'not_applicable_text') }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm font-medium text-green-600">{{ getCurrencySymbol(payment.booking?.booking_currency || 'USD') }}{{ formatNumber(payment.booking?.amount_paid || 0) }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm font-medium text-gray-900">{{ getCurrencySymbol(payment.booking?.booking_currency || 'USD') }}{{ formatNumber(payment.booking?.total_amount || 0) }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm font-medium text-yellow-600">{{ getCurrencySymbol(payment.booking?.booking_currency || 'USD') }}{{ formatNumber(payment.booking?.pending_amount || 0) }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm text-gray-900 capitalize">{{ payment.payment_method }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="{
                  'px-2 py-1 text-xs font-medium rounded-full capitalize': true,
                  'bg-green-100 text-green-800': payment.payment_status === 'succeeded',
                  'bg-yellow-100 text-yellow-800': payment.payment_status === 'pending',
                  'bg-red-100 text-red-800': payment.payment_status === 'failed'
                }">
                  {{ payment.payment_status }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm text-gray-900">
                  {{ formatDate(payment.created_at) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <!-- Pagination -->
      <div class="mt-[1rem] flex justify-end">
        <Pagination :current-page="pagination.current_page" :total-pages="pagination.last_page"
          @page-change="handlePageChange" />

      </div>
    </div>
  </MyProfileLayout>
</template>

<script setup>
import { ref, computed, getCurrentInstance } from 'vue';
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { defineProps } from 'vue'
 import Pagination from '@/Components/ReusableComponents/Pagination.vue';
import { Link, router } from '@inertiajs/vue3';

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;

const props = defineProps({
  payments: {
    type: Array,
    required: true
  },
  pagination: {
    type: Object,
    required: true
  }
});

const searchQuery = ref('');

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-GB', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Get currency symbol
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

const formatNumber = (number) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(number);
};

const handlePageChange = (page) => {
  router.get(route('vendor.payments'), { ...props.filters, page }, { preserveState: true, preserveScroll: true });
};

const filteredPayments = computed(() => {
  const query = searchQuery.value.toLowerCase();
  return props.payments.filter(payment => {
    return (
      payment.transaction_id.toLowerCase().includes(query) ||
      (payment.booking?.customer?.first_name.toLowerCase().includes(query) || payment.booking?.customer?.last_name.toLowerCase().includes(query)) ||
      payment.booking?.vehicle?.brand.toLowerCase().includes(query) ||
      payment.amount.toString().includes(query) ||
      payment.payment_method.toLowerCase().includes(query) ||
      payment.payment_status.toLowerCase().includes(query) ||
      formatDate(payment.created_at).toLowerCase().includes(query)
    );
  });
});
</script>

<style scoped>
@media screen and (max-width:768px) {
    
    th{
        font-size: 0.75rem;
    }
    td{
        font-size: 0.75rem;
        text-wrap-mode: nowrap;
    }
}
</style>
