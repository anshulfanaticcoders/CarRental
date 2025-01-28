<template>
<MyProfileLayout>
    <div class="p-6">
      <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Payment History</h2>
        <p class="text-gray-600">View and manage all your booking payments</p>
      </div>
  
      <!-- No Payments -->
      <div v-if="!payments.length" class="bg-gray-50 p-8 text-center rounded-md">
        <p class="text-gray-600">No payment history found.</p>
      </div>
  
      <!-- Payments Table -->
      <div v-else class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Booking ID
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
            <tr v-for="payment in payments" :key="payment.id">
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm font-medium text-gray-900">#{{ payment.booking_id }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm text-gray-900 mr-2">{{ payment.booking?.customer?.first_name || 'N/A' }}</span> 
                <span class="text-sm text-gray-900">{{ payment.booking?.customer?.last_name || 'N/A' }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm text-gray-900">{{ payment.booking?.vehicle?.model || 'N/A' }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm font-medium text-gray-900">€{{ Number(payment.amount).toFixed(2) }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm text-gray-900 capitalize">{{ payment.payment_method }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span 
                  :class="{
                    'px-2 py-1 text-xs font-medium rounded-full': true,
                    'bg-green-100 text-green-800': payment.payment_status === 'succeeded',
                    'bg-yellow-100 text-yellow-800': payment.payment_status === 'pending',
                    'bg-red-100 text-red-800': payment.payment_status === 'failed'
                  }"
                >
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
    </div>
</MyProfileLayout>
  </template>
  
  <script setup>
  import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { defineProps } from 'vue'
  
  const props = defineProps({
    payments: {
      type: Array,
      default: () => []
    }
  })
  
  const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-GB', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  }
  </script>