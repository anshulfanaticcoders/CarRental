<template>
  <div class="flex flex-col items-center justify-center min-h-screen bg-gray-100">
    <div class="bg-white p-8 rounded-lg shadow-md text-center">
      <h1 class="text-2xl font-bold text-red-600 mb-4">GreenMotion Payment Cancelled</h1>
      <p class="text-gray-700 mb-6">Your GreenMotion payment was cancelled. Your booking has been saved with a pending status.</p>
      <div v-if="greenMotionBooking">
        <p class="text-gray-700 mb-2">Booking Reference: <span class="font-semibold">{{ greenMotionBooking.greenmotion_booking_ref || 'N/A' }}</span></p>
        <p class="text-gray-700 mb-6">Vehicle: <span class="font-semibold">{{ vehicle?.brand || 'N/A' }} {{ vehicle?.model || 'N/A' }}</span></p>
      </div>
      <div class="flex gap-4 items-center justify-center">
        <Link :href="route('profile.bookings.pending', { locale: $page.props.locale })" class="bg-customPrimaryColor text-white font-bold py-2 px-4 rounded">
        Go to bookings
      </Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
  bookingId: {
    type: Number,
    required: true,
  },
  booking: { // GreenMotionBooking model
    type: Object,
    default: null,
  },
  vehicle: {
    type: Object,
    default: null,
  },
  customer: {
    type: Object,
    default: null,
  },
});

const greenMotionBooking = ref(props.booking);
const vehicle = ref(props.vehicle);
const customer = ref(props.customer);

// Custom color variable to match the first code
const customPrimaryColor = '#153B4F';
</script>

<style scoped>
.bg-customPrimaryColor {
  background-color: v-bind(customPrimaryColor);
}
</style>
