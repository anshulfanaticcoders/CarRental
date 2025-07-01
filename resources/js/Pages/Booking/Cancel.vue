<template>
  <div class="flex flex-col items-center justify-center min-h-screen bg-gray-100">
    <div class="bg-white p-8 rounded-lg shadow-md text-center">
      <h1 class="text-2xl font-bold text-red-600 mb-4">Payment Cancelled</h1>
      <p class="text-gray-700 mb-6">Your payment was cancelled. Your booking has been saved with a pending status.</p>
      <div class="flex gap-4 items-center justify-center">
        <Link :href="route('profile.bookings.pending', { locale: $page.props.locale })" class="bg-customPrimaryColor text-white font-bold py-2 px-4 rounded">
        Go to bookings
      </Link>

      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps } from 'vue';
import { loadStripe } from '@stripe/stripe-js';
import axios from 'axios';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  bookingId: {
    type: Number,
    required: true,
  },
});
</script>
