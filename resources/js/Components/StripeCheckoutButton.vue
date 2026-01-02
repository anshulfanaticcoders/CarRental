<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { loadStripe } from '@stripe/stripe-js';

const props = defineProps({
    bookingData: {
        type: Object,
        required: true
    },
    label: {
        type: String,
        default: 'Confirm & Pay'
    }
});

const isLoading = ref(false);
const error = ref(null);

const handleCheckout = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        // 1. Initialize Stripe
        const stripe = await loadStripe(import.meta.env.VITE_STRIPE_KEY);
        if (!stripe) throw new Error('Stripe failed to initialize.');

        // 2. Create Checkout Session
        const response = await axios.post(route('api.stripe.checkout'), props.bookingData);
        
        if (response.data.success && response.data.session_id) {
            // 3. Redirect to Checkout
            const result = await stripe.redirectToCheckout({
                sessionId: response.data.session_id
            });

            if (result.error) {
                throw new Error(result.error.message);
            }
        } else {
            throw new Error('Failed to create checkout session.');
        }

    } catch (err) {
        console.error('Checkout error:', err);
        error.value = err.response?.data?.error || err.message || 'Payment initialization failed.';
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <div class="w-full">
        <!-- Error Message -->
        <div v-if="error" class="mb-4 p-3 bg-red-100 border border-red-200 text-red-700 rounded-lg text-sm">
            {{ error }}
        </div>

        <!-- Checkout Button -->
        <button
            @click="handleCheckout"
            :disabled="isLoading"
            class="w-full bg-customPrimaryColor text-white py-4 rounded-xl font-bold text-lg hover:bg-opacity-90 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-75 disabled:cursor-not-allowed flex items-center justify-center gap-2"
        >
            <svg v-if="isLoading" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span v-else>{{ label }}</span>
        </button>
        
        <p class="text-center text-xs text-gray-400 mt-3 flex items-center justify-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            Secure Payment via Stripe
        </p>
    </div>
</template>
