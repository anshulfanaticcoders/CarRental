<template>
  <MyProfileLayout>
    <div class="container mx-auto px-4 sm:px-0">
      <p class="text-2xl sm:text-xl text-customPrimaryColor font-bold mb-8 bg-gray-100 rounded-xl p-4">
        Green Motion Bookings
      </p>

      <div v-if="!bookings.data || bookings.data.length === 0" class="text-center text-gray-500">
        <div class="flex flex-col justify-center items-center">
          <img :src="bookingstatusIcon" alt="No bookings" class="w-full max-w-sm mx-auto">
          <p class="mt-4">No Green Motion bookings found.</p>
        </div>
      </div>

      <div v-else class="bg-white shadow-md rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking Reference</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle ID</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location ID</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle Location</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Time</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Date</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Time</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(booking, index) in bookings.data" :key="booking.id">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ bookings.from + index }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ booking.greenmotion_booking_ref }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ booking.vehicle_id }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ booking.location_id }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ booking.vehicle_location }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(booking.start_date) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ booking.start_time }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(booking.end_date) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ booking.end_time }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ booking.currency }} {{ booking.grand_total }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="bookings.data && bookings.data.length > 0" class="mt-4 flex justify-end">
        <Pagination :current-page="bookings.current_page" :total-pages="bookings.last_page" @page-change="handlePageChange" />
      </div>
    </div>
  </MyProfileLayout>
</template>

<script setup>
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import bookingstatusIcon from '../../../../assets/bookingstatusIcon.svg';
import { defineProps } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';

const props = defineProps({
    bookings: Object,
    filters: {
        type: Object,
        default: () => ({})
    }
});

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const handlePageChange = (page) => {
    router.get(route('profile.bookings.green-motion', { locale: usePage().props.locale }), {
        ...props.filters,
        page
    }, {
        preserveState: true,
        preserveScroll: true
    });
};
</script>
