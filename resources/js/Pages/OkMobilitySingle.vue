<template>
  <div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">{{ vehicle.model }}</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <img :src="vehicle.image" alt="Vehicle Image" class="w-full h-auto rounded-lg">
      </div>
      <div>
        <p><strong>Brand:</strong> {{ vehicle.brand }}</p>
        <p><strong>Price per day:</strong> {{ vehicle.price_per_day }} {{ vehicle.currency }}</p>
        <p><strong>Transmission:</strong> {{ vehicle.transmission }}</p>
        <p><strong>Fuel:</strong> {{ vehicle.fuel }}</p>
        <p><strong>Seating Capacity:</strong> {{ vehicle.seating_capacity }}</p>
        <p><strong>Mileage:</strong> {{ vehicle.mileage }}</p>
        <div class="mt-4">
          <h2 class="text-xl font-semibold">Extras</h2>
          <ul class="list-disc list-inside">
            <li v-for="extra in vehicle.extras" :key="extra.extraID">
              {{ extra.extra }} - {{ extra.value }} {{ vehicle.currency }}
            </li>
          </ul>
        </div>
        <button @click="proceedToBooking" class="mt-4 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
          Proceed to Booking
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  vehicle: Object,
  searchParams: Object,
});

const proceedToBooking = () => {
  const bookingUrl = `/ok-mobility-booking/${props.vehicle.id}/checkout`;
  router.get(bookingUrl, {
    ...props.searchParams,
    vehicle: JSON.stringify(props.vehicle),
  });
};
</script>
