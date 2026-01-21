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
        const vehicleSource = props.bookingData?.vehicle?.source;

        // 1. Initialize Stripe
        const stripe = await loadStripe(import.meta.env.VITE_STRIPE_KEY);
        if (!stripe) throw new Error('Stripe failed to initialize.');

        // 2. Create Checkout Session
        let response;
        if (vehicleSource === 'okmobility') {
            const customerName = `${props.bookingData.customer?.name || ''}`.trim();
            const nameParts = customerName.split(/\s+/).filter(Boolean);
            const firstname = nameParts[0] || 'Guest';
            const surname = nameParts.slice(1).join(' ') || 'Guest';
            const detailedExtras = props.bookingData.detailed_extras || [];
            const extrasPayload = detailedExtras.map((extra) => ({
                id: extra.code || extra.id,
                quantity: extra.qty || 1,
                option_total: extra.total || 0,
            }));

            response = await axios.post(route('okmobility.booking.charge'), {
                pickup_station_id: props.bookingData.vehicle?.provider_pickup_id || props.bookingData.vehicle?.provider_pickup_office_id,
                dropoff_station_id: props.bookingData.vehicle?.provider_dropoff_id || props.bookingData.vehicle?.provider_pickup_id || props.bookingData.vehicle?.provider_pickup_office_id,
                start_date: props.bookingData.pickup_date,
                start_time: props.bookingData.pickup_time,
                end_date: props.bookingData.dropoff_date,
                end_time: props.bookingData.dropoff_time,
                customer: {
                    firstname,
                    surname,
                    email: props.bookingData.customer?.email,
                    phone: props.bookingData.customer?.phone,
                    address1: props.bookingData.customer?.address,
                    address2: null,
                    address3: null,
                    town: props.bookingData.customer?.city,
                    postcode: props.bookingData.customer?.postal_code,
                    country: props.bookingData.customer?.country,
                    driver_licence_number: props.bookingData.customer?.driver_license_number,
                    flight_number: props.bookingData.customer?.flight_number,
                    comments: props.bookingData.customer?.notes,
                },
                extras: extrasPayload,
                vehicle_id: props.bookingData.vehicle?.id,
                vehicle_total: props.bookingData.vehicle_total || props.bookingData.total_amount,
                currency: props.bookingData.currency,
                grand_total: props.bookingData.totals?.grandTotal || props.bookingData.total_amount,
                user_id: props.bookingData.customer?.user_id || null,
                vehicle_location: props.bookingData.pickup_location,
                ok_mobility_group_id: props.bookingData.vehicle?.ok_mobility_group_id,
                ok_mobility_token: props.bookingData.vehicle?.ok_mobility_token,
                remarks: props.bookingData.customer?.notes || null,
            });
        } else {
            response = await axios.post(route('api.stripe.checkout'), props.bookingData);
        }
        
        const sessionId = response.data?.session_id || response.data?.sessionId;
        if ((response.data?.success || sessionId) && sessionId) {
            // 3. Redirect to Checkout
            const result = await stripe.redirectToCheckout({
                sessionId
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
