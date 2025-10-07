<template>
  <div class="flex flex-col items-center justify-center min-h-screen bg-gray-100">
    <div class="bg-white p-8 rounded-lg shadow-md text-center max-w-md">
      <h1 class="text-2xl font-bold text-red-600 mb-4">Payment Issue</h1>

      <!-- Show error message if available -->
      <div v-if="error" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-red-700 text-sm">{{ error }}</p>
      </div>

      <!-- Show different messages based on whether we have a booking -->
      <div v-if="bookingId">
        <p class="text-gray-700 mb-6">Your payment was cancelled or failed. Booking #{{ bookingId }} may be in pending status.</p>
      </div>
      <div v-else>
        <p class="text-gray-700 mb-6">The payment process was interrupted or cancelled. No booking was created.</p>
      </div>

      <div class="flex gap-4 items-center justify-center">
        <Link :href="route('home', { locale: $page.props.locale })" class="bg-gray-500 text-white font-bold py-2 px-4 rounded hover:bg-gray-600">
          Back to Home
        </Link>

        <Link v-if="bookingId" :href="route('profile.bookings.pending', { locale: $page.props.locale })" class="bg-customPrimaryColor text-white font-bold py-2 px-4 rounded hover:bg-customPrimaryColor-hover">
          View Bookings
        </Link>
        <Link v-else :href="route('vehicles.index', { locale: $page.props.locale })" class="bg-customPrimaryColor text-white font-bold py-2 px-4 rounded hover:bg-customPrimaryColor-hover">
          Browse Vehicles
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
    default: null,
  },
  error: {
    type: String,
    default: null,
  },
});
</script>
