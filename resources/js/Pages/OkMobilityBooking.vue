<template>
  <div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Book {{ vehicle.model }}</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <img :src="vehicle.image" alt="Vehicle Image" class="w-full h-auto rounded-lg">
        <p class="mt-2"><strong>Price per day:</strong> {{ vehicle.price_per_day }} {{ vehicle.currency }}</p>
      </div>
      <div>
        <form @submit.prevent="submitBooking">
          <div class="mb-4">
            <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Name</label>
            <input type="text" id="name" v-model="form.name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
          </div>
          <div class="mb-4">
            <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email</label>
            <input type="email" id="email" v-model="form.email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
          </div>
          <div class="mb-4">
            <label for="phone" class="block text-gray-700 text-sm font-bold mb-2">Phone</label>
            <input type="tel" id="phone" v-model="form.phone" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
          </div>
          <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Confirm Booking
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  vehicle: Object,
  searchParams: Object,
});

const form = ref({
  name: '',
  email: '',
  phone: '',
});

const submitBooking = () => {
  const bookingData = {
    ...props.searchParams,
    vehicle: JSON.stringify(props.vehicle),
    customer_details: form.value,
  };

  router.post('/ok-mobility-booking/charge', bookingData);
};
</script>
