<template>
  <div class="stripe-checkout">
    <!-- Loader Overlay -->
    <div v-if="isLoading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 flex flex-col items-center gap-4 shadow-lg">
        <div class="loader border-t-4 border-customPrimaryColor rounded-full w-8 h-8 animate-spin"></div>
        <p class="text-customPrimaryColor text-lg font-medium max-[768px]:text-base">
          Processing payment...
        </p>
      </div>
    </div>

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

    // Save important data to session storage before redirecting to Stripe
    if (window.sessionStorage) {
      sessionStorage.setItem('pendingBookingData', JSON.stringify(props.bookingData));
    }

    // Create Checkout Session
    const response = await axios.post('/payment/charge', {
      bookingData: props.bookingData,
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
</script>

<style scoped>
.stripe-checkout {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 1rem;
}

/* Loader styles */
.loader {
  border: 4px solid #f3f3f3;
  border-top-color: #153B4F; /* Matches text-customPrimaryColor */
  border-radius: 50%;
  width: 32px;
  height: 32px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Custom color for consistency with previous code */
.text-customPrimaryColor {
  color: #153B4F;
}
</style>