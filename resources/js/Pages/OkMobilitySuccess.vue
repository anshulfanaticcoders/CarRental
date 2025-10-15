<template>
  <div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4 text-green-500">Booking Successful!</h1>
    <p>Your booking has been confirmed.</p>
    <div v-if="booking" class="mt-4">
      <p><strong>Booking Reference:</strong> {{ booking.ok_mobility_booking_ref }}</p>
      <p><strong>Vehicle:</strong> {{ booking.vehicle_id }}</p>
      <p><strong>Pickup:</strong> {{ booking.start_date }} at {{ booking.start_time }}</p>
      <p><strong>Dropoff:</strong> {{ booking.end_date }} at {{ booking.end_time }}</p>
    </div>
  </div>
</template>

<script setup>
import { defineProps } from 'vue';

const props = defineProps({
  booking: Object,
});
</script>
