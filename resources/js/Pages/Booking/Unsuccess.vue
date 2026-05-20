<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import Footer from '@/Components/Footer.vue';
import BookingOutcomePage from '@/Components/Booking/BookingOutcomePage.vue';
import { AlertCircle } from 'lucide-vue-next';
import paymentNotCompletedIllustration from '../../../assets/booking-outcomes/payment-not-completed.webp';

const retryPayment = () => {
    window.history.back();
};
</script>

<template>
    <Head title="Payment Not Completed" />
    <AuthenticatedHeaderLayout />

    <BookingOutcomePage
        title="Payment not completed"
        message="Your payment was not completed. Please return to the booking step and try again, or contact support if the issue continues."
        tone="danger"
        :icon="AlertCircle"
        :illustration="paymentNotCompletedIllustration"
        primary-label="Try again"
        :primary-action="retryPayment"
        secondary-label="Back to home"
        secondary-href="/"
    />

    <Footer />
</template>
