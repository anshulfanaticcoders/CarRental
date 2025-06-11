<template>
  <div class="flex flex-col items-center justify-center min-h-screen bg-gray-100">
    <div class="bg-white p-8 rounded-lg shadow-md text-center">
      <h1 class="text-2xl font-bold text-red-600 mb-4">Payment Cancelled</h1>
      <p class="text-gray-700 mb-6">Your payment was cancelled. Your booking has been saved with a pending status.</p>
      <button @click="retryPayment" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
        Retry Payment
      </button>
    </div>
  </div>
</template>

<script setup>
import { defineProps } from 'vue';
import { loadStripe } from '@stripe/stripe-js';
import axios from 'axios';

const props = defineProps({
  bookingId: {
    type: Number,
    required: true,
  },
});

const retryPayment = async () => {
  try {
    const response = await axios.post(route('payment.retry'), { booking_id: props.bookingId });
    
    if (response.data.sessionId) {
      const stripe = await loadStripe(import.meta.env.VITE_STRIPE_KEY);
      await stripe.redirectToCheckout({ sessionId: response.data.sessionId });
    }
  } catch (error) {
    console.error('Error retrying payment:', error);
    // Optionally, display an error message to the user
    alert('Failed to retry payment. Please try again later.');
  }
};
</script>
