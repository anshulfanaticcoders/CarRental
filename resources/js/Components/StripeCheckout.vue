<template>
    <div class="stripe-checkout">
      <div v-if="errorMessage" class="text-red-600 text-sm mb-4">
        {{ errorMessage }}
      </div>
      <PrimaryButton
        :disabled="isLoading"
        @click="initiateCheckout"
        class="w-[15rem] max-[768px]:text-[0.65rem]"
      >
        Book Now
      </PrimaryButton>
    </div>
  </template>
  
  <script setup>
  import { ref } from 'vue';
  import axios from 'axios';
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  
  const props = defineProps({
    bookingData: {
      type: Object,
      required: true,
    },
  });
  
  const isLoading = ref(false);
  const errorMessage = ref('');
  
  const initiateCheckout = async () => {
    isLoading.value = true;
    errorMessage.value = '';
  
    try {
      // Load Stripe.js dynamically
      const stripe = await loadStripe(import.meta.env.VITE_STRIPE_KEY);
      if (!stripe) {
        throw new Error('Failed to load Stripe.js');
      }
  
      // Generate a unique booking reference
      const bookingReference = generateRandomString(32);
  
      // Create Checkout Session
      const response = await axios.post('/payment/charge', {
        bookingData: {
          ...props.bookingData,
          booking_reference: bookingReference,
        },
      }, {
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
      });
  
      const { sessionId } = response.data;
      if (!sessionId) {
        throw new Error('Failed to create Checkout Session');
      }
  
      // Redirect to Stripe Checkout
      const { error } = await stripe.redirectToCheckout({ sessionId });
      if (error) {
        throw new Error(error.message);
      }
    } catch (err) {
      errorMessage.value = err.message || 'An error occurred. Please try again.';
    } finally {
      isLoading.value = false;
    }
  };
  
  async function loadStripe(key) {
    return new Promise((resolve) => {
      if (window.Stripe) {
        resolve(window.Stripe(key));
      } else {
        const script = document.createElement('script');
        script.src = 'https://js.stripe.com/v3/';
        script.onload = () => resolve(window.Stripe(key));
        document.head.appendChild(script);
      }
    });
  }
  
  function generateRandomString(length) {
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let result = '';
    for (let i = 0; i < length; i++) {
      result += characters.charAt(Math.floor(Math.random() * characters.length));
    }
    return result;
  }
  </script>
  
  <style scoped>
  .stripe-checkout {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  </style>