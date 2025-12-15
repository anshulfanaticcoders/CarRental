<template>
  <div class="locauto-stripe-checkout">
    <!-- Loader Overlay -->
    <div v-if="isLoading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 flex flex-col items-center gap-4 shadow-lg">
        <div class="loader border-t-4 border-customPrimaryColor rounded-full w-8 h-8 animate-spin"></div>
        <p class="text-customPrimaryColor text-lg font-medium max-[768px]:text-base">
          Processing payment...
        </p>
      </div>
    </div>

    <button
      :disabled="isLoading"
      @click="initiateCheckout"
      class="w-full bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white py-4 font-semibold text-lg rounded-xl shadow-lg hover:shadow-xl transition-all duration-300"
    >
      <div class="flex items-center justify-center gap-2">
        <span v-if="isLoading">Processing...</span>
        <span v-else>Confirm & Pay Now</span>
        <svg v-if="!isLoading" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="m9 18 6-6-6-6"/></svg>
      </div>
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  bookingData: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['error', 'success']);

const isLoading = ref(false);
const page = usePage();

const initiateCheckout = async () => {
  isLoading.value = true;

  try {
    console.log('🚀 Starting Locauto checkout process...');
    console.log('📋 Booking Data:', JSON.stringify(props.bookingData, null, 2));

    // Load Stripe.js
    const stripe = await loadStripe(import.meta.env.VITE_STRIPE_KEY);
    if (!stripe) {
      throw new Error('Failed to load Stripe.js');
    }
    console.log('✅ Stripe loaded successfully');

    // Validate required fields
    const requiredFields = ['customer', 'vehicle_code', 'pickup_location_code', 'pickup_date', 'return_date', 'grand_total'];
    const missingFields = requiredFields.filter(field => !props.bookingData[field]);
    if (missingFields.length > 0) {
      throw new Error(`Missing required fields: ${missingFields.join(', ')}`);
    }

    // Validate customer sub-fields
    if (!props.bookingData.customer.email || !props.bookingData.customer.first_name || !props.bookingData.customer.last_name) {
      throw new Error('Customer information is incomplete (email, first name, and last name are required)');
    }

    // Validate amount
    if (props.bookingData.grand_total <= 0) {
      throw new Error('Total amount must be greater than 0');
    }

    console.log('✅ Booking data validation passed');

    // Get the current locale from the Inertia page props
    const currentLocale = page.props.locale || 'en';

    // Call backend to create Stripe checkout session
    const response = await axios.post(`/${currentLocale}/locauto-rent-booking/charge`, props.bookingData);

    console.log('📨 Payment response:', response.data);
    const { sessionId } = response.data;

    if (!sessionId) {
      throw new Error('Failed to create Locauto Checkout Session');
    }
    console.log('✅ Checkout session created:', sessionId);

    // Redirect to Stripe Checkout
    console.log('🔄 Redirecting to Stripe Checkout...');
    const { error } = await stripe.redirectToCheckout({ sessionId });
    
    if (error) {
      throw new Error(error.message);
    }
  } catch (err) {
    console.error('❌ Checkout error:', err);
    console.error('❌ Error details:', {
      message: err.message,
      response: err.response?.data,
      bookingData: props.bookingData
    });
    emit('error', err.response?.data?.error || err.message || 'An error occurred. Please try again.');
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
.locauto-stripe-checkout {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  gap: 1rem;
}

/* Loader styles */
.loader {
  border: 4px solid #f3f3f3;
  border-top-color: #16a34a; /* Green color for Locauto */
  border-radius: 50%;
  width: 32px;
  height: 32px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.text-customPrimaryColor {
  color: #16a34a;
}
</style>
