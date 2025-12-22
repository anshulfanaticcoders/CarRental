<template>
  <div class="okmobility-stripe-checkout">
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
      class="w-full bg-gradient-to-r from-customPrimaryColor to-blue-700 hover:from-customPrimaryColor/90 hover:to-blue-700/90 text-white py-4 font-semibold text-lg rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105"
    >
      <div class="flex items-center justify-center gap-2">
        <span>Reserve Now</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right w-5 h-5"><path d="m9 18 6-6-6-6"/></svg>
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

const emit = defineEmits(['error']);

const isLoading = ref(false);
const page = usePage();

const initiateCheckout = async () => {
  isLoading.value = true;

  try {
    const stripe = await loadStripe(import.meta.env.VITE_STRIPE_KEY);
    if (!stripe) {
      throw new Error('Failed to load Stripe.js');
    }

    // Get the current locale from the Inertia page props
    const currentLocale = page.props.locale;

    // Log data for debugging (optional)
    console.log("Booking data sent to backend:", JSON.parse(JSON.stringify(props.bookingData)));
    console.log("Raw form data from parent:", JSON.parse(JSON.stringify(props.bookingData)));

    // Pass the locale to the route() helper
    const response = await axios.post(route('okmobility.booking.charge', { locale: currentLocale }), {
      ...props.bookingData, // Pass all booking data directly
    });

    const { sessionId } = response.data;
    if (!sessionId) {
      throw new Error('Failed to create OK Mobility Checkout Session');
    }

    const { error } = await stripe.redirectToCheckout({ sessionId });
    if (error) {
      throw new Error(error.message);
    }
  } catch (err) {
    emit('error', err.message || 'An error occurred. Please try again.');
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
.okmobility-stripe-checkout {
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

.bg-customPrimaryColor {
    background-color: #153b4f;
}

.to-blue-700 {
    --tw-gradient-to: #1d4ed8;
}

.hover\:from-customPrimaryColor\/90:hover {
    --tw-gradient-from: rgba(21, 59, 79, 0.9);
}

.hover\:to-blue-700\/90:hover {
    --tw-gradient-to: rgba(29, 78, 216, 0.9);
}

.shadow-lg {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.hover\:shadow-xl:hover {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 300ms;
}

.transform {
    transform: translateX(var(--tw-translate-x)) translateY(var(--tw-translate-y)) rotate(var(--tw-rotate)) skewX(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y));
}

.hover\:scale-105:hover {
    --tw-scale-x: 1.05;
    --tw-scale-y: 1.05;
}
</style>
