<template>
    <div class="wheelsys-stripe-checkout">
        <div v-if="isLoading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 flex flex-col items-center gap-4 shadow-lg">
                <div class="loader border-t-4 border-customPrimaryColor rounded-full w-8 h-8 animate-spin"></div>
                <p class="text-customPrimaryColor text-lg font-medium">Processing...</p>
            </div>
        </div>

        <Button
            @click="initiateCheckout"
            :disabled="isLoading"
            class="w-full bg-gradient-to-r from-customPrimaryColor to-blue-700 hover:from-customPrimaryColor/90 hover:to-blue-700/90 text-white py-3 text-lg font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300"
        >
            <span v-if="!isLoading">Proceed to Payment</span>
            <span v-else>Processing...</span>
        </Button>
        <p v-if="errorMessage" class="text-red-500 text-sm mt-2">{{ errorMessage }}</p>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import { Button } from '@/Components/ui/button';

const props = defineProps({
    bookingData: {
        type: Object,
        required: true,
    },
});

const isLoading = ref(false);
const errorMessage = ref('');
const page = usePage();

let stripe = null;

const loadStripe = async () => {
    if (!stripe) {
        const stripeJs = await import('@stripe/stripe-js');
        stripe = await stripeJs.loadStripe(import.meta.env.VITE_STRIPE_KEY);
    }
    return stripe;
};

const initiateCheckout = async () => {
    isLoading.value = true;
    errorMessage.value = '';

    try {
        const stripeInstance = await loadStripe();
        if (!stripeInstance) {
            throw new Error('Failed to load Stripe.js');
        }

        const response = await axios.post(route('wheelsys.booking.store', { locale: page.props.locale }), props.bookingData);

        const { sessionId } = response.data;

        if (!sessionId) {
            throw new Error('Failed to create Stripe Checkout Session.');
        }

        const { error } = await stripeInstance.redirectToCheckout({ sessionId });

        if (error) {
            throw new Error(error.message);
        }
    } catch (err) {
        errorMessage.value = err.response?.data?.error || err.message || 'An unexpected error occurred.';
        console.error(err);
    } finally {
        isLoading.value = false;
    }
};
</script>

<style scoped>
.loader {
  border: 4px solid #f3f3f3;
  border-top-color: #153B4F;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>
