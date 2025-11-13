<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import Footer from '@/Components/Footer.vue';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { CheckCircle } from 'lucide-vue-next';

const props = defineProps({
    booking: Object,
    locale: String,
});

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};
</script>

<template>
    <Head>
        <title>Booking Confirmed - Wheelsys</title>
    </Head>

    <AuthenticatedHeaderLayout />

    <div class="bg-gray-50 min-h-screen py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <Card class="overflow-hidden shadow-xl border-0">
                <div class="bg-gradient-to-r from-green-500 to-teal-500 p-8 text-white text-center">
                    <CheckCircle class="w-16 h-16 mx-auto mb-4" />
                    <h1 class="text-3xl font-bold">Booking Confirmed!</h1>
                    <p class="mt-2 text-lg">Thank you for choosing Wheelsys.</p>
                </div>

                <CardContent class="p-8 space-y-6">
                    <div class="text-center">
                        <p class="text-gray-600">Your reservation is complete. A confirmation email has been sent to <strong>{{ booking.customer_details.email }}</strong>.</p>
                        <p class="mt-2 text-lg font-semibold text-gray-800">Your Booking Reference: <span class="text-customPrimaryColor">{{ booking.wheelsys_booking_ref }}</span></p>
                    </div>

                    <div class="border-t border-b border-gray-200 divide-y divide-gray-200">
                        <div class="py-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <dt class="font-medium text-gray-500">Vehicle</dt>
                            <dd class="md:col-span-2 text-gray-900 font-semibold">{{ booking.vehicle_group_name }}</dd>
                        </div>
                        <div class="py-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <dt class="font-medium text-gray-500">Pickup</dt>
                            <dd class="md:col-span-2 text-gray-900">
                                <p>{{ booking.pickup_station_name }}</p>
                                <p class="text-sm text-gray-600">{{ formatDate(booking.pickup_date) }} at {{ booking.pickup_time }}</p>
                            </dd>
                        </div>
                        <div class="py-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <dt class="font-medium text-gray-500">Return</dt>
                            <dd class="md:col-span-2 text-gray-900">
                                <p>{{ booking.return_station_name }}</p>
                                <p class="text-sm text-gray-600">{{ formatDate(booking.return_date) }} at {{ booking.return_time }}</p>
                            </dd>
                        </div>
                        <div class="py-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <dt class="font-medium text-gray-500">Total Price</dt>
                            <dd class="md:col-span-2 text-gray-900 font-bold text-xl">{{ new Intl.NumberFormat('en-US', { style: 'currency', currency: booking.currency }).format(booking.grand_total) }}</dd>
                        </div>
                    </div>

                    <div class="text-center mt-8">
                        <Link :href="route('search', { locale: locale })">
                            <Button class="bg-customPrimaryColor text-white hover:bg-customPrimaryColor/90">
                                Back to Home
                            </Button>
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>

    <Footer />
</template>
