<template>
  <MyProfileLayout>
    <div class="">
      <div class="mb-6">
        <p class="text-[1.75rem] font-bold text-gray-800 bg-customLightPrimaryColor p-4 rounded-[12px] mb-[1rem] max-[768px]:text-[1.2rem]">
          Payment History</p>
        <p class="text-gray-600">View and manage all your booking payments</p>
      </div>

      <!-- Search Bar -->
      <div class="mb-4">
        <input type="text" v-model="searchQuery" placeholder="Search payments..." class="px-4 py-2 border border-gray-300 rounded-md w-full" />
      </div>

      <!-- No Payments -->
      <div v-if="!filteredPayments.length" class="bg-gray-50 p-8 text-center rounded-md">
        <p class="text-gray-600">No payment history found.</p>
      </div>

      <!-- Payments Table -->
      <div v-else class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Transaction ID
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Customer
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Vehicle
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
            <tr v-for="(payment, index) in filteredPayments" :key="payment.id">
              <td class="px-6 py-4 whitespace-nowrap">{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm font-medium text-gray-900">{{ payment.transaction_id }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm text-gray-900 mr-2">{{ payment.booking?.customer?.first_name || 'N/A' }}</span>
                <span class="text-sm text-gray-900">{{ payment.booking?.customer?.last_name || 'N/A' }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm text-gray-900">{{ payment.booking?.vehicle?.brand || 'N/A' }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm font-medium text-gray-900">€{{ Number(payment.amount).toFixed(2) }}</span>
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
import { ref, computed } from 'vue';
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { defineProps } from 'vue'
import Pagination from './Pagination.vue';
import { Link, router } from '@inertiajs/vue3';

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