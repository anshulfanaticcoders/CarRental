<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import Footer from '@/Components/Footer.vue';
import BookingOutcomePage from '@/Components/Booking/BookingOutcomePage.vue';
import { CreditCard } from 'lucide-vue-next';
import paymentCancelledIllustration from '../../../assets/booking-outcomes/payment-cancelled.webp';

defineProps({
    message: {
        type: String,
        default: 'Your payment was cancelled. You can try again anytime.'
    }
});

const goBack = () => {
    window.history.back();
};
</script>

<template>
    <Head title="Payment Cancelled" />
    <AuthenticatedHeaderLayout />

    <BookingOutcomePage
        title="Payment cancelled"
        :message="message"
        tone="neutral"
        :icon="CreditCard"
        :illustration="paymentCancelledIllustration"
        primary-label="Back to home"
        primary-href="/"
        secondary-label="Go back"
        :secondary-action="goBack"
    />

    <Footer />
</template>
