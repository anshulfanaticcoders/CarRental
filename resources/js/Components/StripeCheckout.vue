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
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
  bookingData: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['error']); // Define emits for the error event

const isLoading = ref(false);
const page = usePage();
// errorMessage is no longer needed here as it will be handled by the parent component
const initiateCheckout = async () => {
  isLoading.value = true;
  // errorMessage.value = ''; // No longer needed

  try {
    console.log('🚀 Starting checkout process...');
    console.log('📋 Booking Data:', JSON.stringify(props.bookingData, null, 2));

    // Load Stripe.js dynamically
    console.log('💳 Loading Stripe with key:', import.meta.env.VITE_STRIPE_KEY ? 'Key found' : 'Key missing');
    const stripe = await loadStripe(import.meta.env.VITE_STRIPE_KEY);
    if (!stripe) {
      throw new Error('Failed to load Stripe.js');
    }
    console.log('✅ Stripe loaded successfully');

    // Validate required booking data fields
    const requiredFields = ['customer', 'vehicle_id', 'pickup_date', 'return_date', 'total_amount'];
    const missingFields = requiredFields.filter(field => !props.bookingData[field]);
    if (missingFields.length > 0) {
      throw new Error(`Missing required fields: ${missingFields.join(', ')}`);
    }

    // Validate customer sub-fields
    if (!props.bookingData.customer.email || !props.bookingData.customer.first_name || !props.bookingData.customer.last_name) {
      throw new Error('Customer information is incomplete (email, first name, and last name are required)');
    }

    // Validate amounts
    if (props.bookingData.total_amount <= 0) {
      throw new Error('Total amount must be greater than 0');
    }

    if (props.bookingData.amount_paid <= 0) {
      throw new Error('Amount paid must be greater than 0');
    }

    // Validate dates
    const pickupDate = new Date(props.bookingData.pickup_date);
    const returnDate = new Date(props.bookingData.return_date);
    if (pickupDate >= returnDate) {
      throw new Error('Return date must be after pickup date');
    }

    console.log('✅ Booking data validation passed');

    // Save important data to session storage before redirecting to Stripe
    if (window.sessionStorage) {
      sessionStorage.setItem('pendingBookingData', JSON.stringify(props.bookingData));
      console.log('💾 Booking data saved to session storage');
    }

    const paymentUrl = `/${page.props.locale}/payment/charge`;
    console.log('🌐 Sending request to payment.charge endpoint:', paymentUrl);
    // Create Checkout Session
    const response = await axios.post(paymentUrl, {
      bookingData: props.bookingData,
    });

    console.log('📨 Payment response:', response.data);
    const { sessionId } = response.data;
    if (!sessionId) {
      throw new Error('Failed to create Checkout Session');
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
      stack: err.stack,
      bookingData: props.bookingData
    });
    // Emit the error to the parent component
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
