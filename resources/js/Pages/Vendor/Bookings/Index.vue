<template>
    <MyProfileLayout>
        <div class="">
            <p class="text-[1.75rem] font-bold text-gray-800 bg-customLightPrimaryColor p-4 rounded-[12px] mb-[1rem]">Booking Details</p>

            <div v-if="bookings.length" class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-4 py-2 text-left text-sm font-bold">Booking ID</th>
                            <th class="px-4 py-2 text-left text-sm font-bold">Customer Name</th>
                            <th class="px-4 py-2 text-left text-sm font-bold">Vehicle</th>
                            <th class="px-4 py-2 text-left text-sm font-bold">Booking Date</th>
                            <th class="px-4 py-2 text-left text-sm font-bold">Return Date</th>
                            <th class="px-4 py-2 text-left text-sm font-bold">Payment Status</th>
                            <th class="px-4 py-2 text-left text-sm font-bold">Booking Status</th>
                            <th class="px-4 py-2 text-left text-sm font-bold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(booking, index) in bookings" :key="booking.id" class="border-b hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">{{ index + 1 }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ booking.booking_number }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700">
                                {{ booking.customer?.first_name }} {{ booking.customer?.last_name }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-700">
                                {{ booking.vehicle?.brand }} <span
                                    class="bg-customLightPrimaryColor ml-2 p-1 rounded-[12px]">{{ booking.vehicle?.model
                                    }}</span>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ formatDate(booking.pickup_date) }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ formatDate(booking.return_date) }}</td>
                            <td class="px-4 py-2 text-sm capitalize">
                                <span :class="{
                                    'text-green-600 font-semibold': booking.payments[0]?.payment_status === 'succeeded',
                                    'text-yellow-500 font-semibold': booking.payments[0]?.payment_status === 'pending',
                                    'text-red-500 font-semibold': booking.payments[0]?.payment_status === 'failed',
                                    'text-gray-500 font-semibold': !booking.payments.length
                                }">
                                    {{ booking.payments[0]?.payment_status || 'No Payment' }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-sm">
                                <select v-model="booking.booking_status" @change="updateStatus(booking)"
                                    class="w-full p-2 border rounded" :class="{
                                        'text-green-600 font-medium': booking.booking_status === 'completed' || booking.booking_status === 'confirmed',
                                        'text-yellow-500 font-medium': booking.booking_status === 'pending',
                                        'text-red-500 font-medium': booking.booking_status === 'cancelled'
                                    }">
                                    <option value="pending" class="text-yellow-500 font-medium">Pending</option>
                                    <option value="confirmed" class="text-green-600 font-medium">Confirmed</option>
                                    <option value="completed" class="text-green-600 font-medium">Completed</option>
                                    <option value="cancelled" class="text-red-500 font-medium">Cancelled</option>
                                </select>
                            </td>
                            <td class="px-4 py-2 text-sm">
                                <button v-if="booking.booking_status !== 'cancelled'"
                                    class="text-red-600 font-semibold hover:underline"
                                    @click="cancelBooking(booking.id)">
                                    Cancel
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="text-center py-6">
                <span class="text-gray-500">No bookings found.</span>
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
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import {router } from '@inertiajs/vue3';
import axios from 'axios';
import Pagination from './Pagination.vue';
import { useToast } from 'vue-toastification';
const toast = useToast();


const props = defineProps({
    bookings: {
        type: Array,
        filters: Object,
        required: true
    },
    pagination: { 
        type: Object,
        required: true
    }
});
const handlePageChange = (page) => {
    router.get(route('bookings.index'), { ...props.filters, page }, { preserveState: true, preserveScroll: true });

};
const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return `${String(date.getMonth() + 1).padStart(2, '0')}/${String(date.getDate()).padStart(2, '0')}/${date.getFullYear()}`;
};

const updateStatus = async (booking) => {
  try {
    await axios.put(`/bookings/${booking.id}`, {
      booking_status: booking.booking_status
    });
    
    const statusMessages = {
      pending: 'Status changed to Pending!',
      confirmed: 'Status changed to Confirmed!',
      completed: 'Status changed to Completed!',
      cancelled: 'Status changed to Cancelled!'
    };
    
    toast.success(statusMessages[booking.booking_status], {
      position: 'top-right',
      timeout: 3000,
      closeOnClick: true,
      pauseOnHover: true,
      draggable: true,
    });
    
    if (booking.booking_status === 'confirmed') {
      await axios.put(`/vehicles/${booking.vehicle_id}`, {
        status: 'rented'
      });
    }
    // Optionally refresh the page or show a success message
    router.reload();
  } catch (error) {
    console.error("Error updating status:", error);
    alert("Failed to update booking status. Please try again.");
    // Reset the status back in case of error
    router.reload();
  }
};

const cancelBooking = async (bookingId) => {
    if (confirm('Are you sure you want to cancel this booking?')) {
        try {
            await axios.post(`/api/bookings/${bookingId}/cancel`);
            router.reload();
        } catch (err) {
            console.error("Error canceling booking:", err);
            alert("Failed to cancel booking. Please try again.");
        }
    }
};
</script>