<script setup>
import { Link, Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Alert, AlertDescription } from '@/Components/ui/alert';

const props = defineProps({
    booking: Object,
    stripe_key: String,
});

const processing = ref(false);
const paymentError = ref('');
const cardElement = ref(null);
const stripe = ref(null);
const elements = ref(null);

onMounted(() => {
    // Initialize Stripe
    if (props.stripe_key) {
        stripe.value = Stripe(props.stripe_key);
        elements.value = stripe.value.elements();

        // Create card element
        cardElement.value = elements.value.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#424770',
                    '::placeholder': {
                        color: '#aab7c4',
                    },
                },
            },
        });

        cardElement.value.mount('#card-element');
    }
});

const processPayment = async () => {
    if (!stripe.value || !cardElement.value) {
        paymentError.value = 'Payment system not initialized';
        return;
    }

    processing.value = true;
    paymentError.value = '';

    try {
        // Create payment method
        const { error, paymentMethod } = await stripe.value.createPaymentMethod({
            type: 'card',
            card: cardElement.value,
            billing_details: {
                name: props.booking.customer_full_name,
                email: props.booking.customer_email,
            },
        });

        if (error) {
            paymentError.value = error.message;
            processing.value = false;
            return;
        }

        // Process payment via backend
        const response = await fetch(`/wheelsys-booking/process-payment/${props.booking.id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({
                payment_method_id: paymentMethod.id,
            }),
        });

        const result = await response.json();

        if (result.success) {
            // Redirect to success page
            window.location.href = result.redirect_url;
        } else if (result.requires_action) {
            // Handle 3D Secure
            const { error: confirmError, paymentIntent } = await stripe.value.confirmCardPayment(
                result.payment_intent_client_secret
            );

            if (confirmError) {
                paymentError.value = confirmError.message;
                processing.value = false;
            } else {
                window.location.href = `/wheelsys-booking/success/${props.booking.id}`;
            }
        } else {
            paymentError.value = result.error || 'Payment failed';
            processing.value = false;
        }
    } catch (error) {
        paymentError.value = 'Payment processing failed. Please try again.';
        processing.value = false;
    }
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(price);
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
</script>

<template>
    <Head>
        <title>Payment - {{ booking.vehicle_group_name }}</title>
        <script src="https://js.stripe.com/v3/"></script>
    </Head>

    <AuthenticatedHeaderLayout>
        <div class="min-h-screen bg-gray-50 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Complete Payment</h1>
                    <p class="text-gray-600 mt-2">Securely complete your booking payment</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Main Content (2 columns) -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Payment Form -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Payment Information</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <!-- Error Alert -->
                                <Alert v-if="paymentError" variant="destructive">
                                    <AlertDescription>{{ paymentError }}</AlertDescription>
                                </Alert>

                                <!-- Card Element -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Credit or Debit Card
                                    </label>
                                    <div id="card-element" class="p-4 border border-gray-300 rounded-lg"></div>
                                </div>

                                <!-- Processing Overlay -->
                                <div v-if="processing" class="text-center py-8">
                                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                                    <p class="mt-4 text-gray-600">Processing payment...</p>
                                </div>

                                <!-- Submit Button -->
                                <Button
                                    v-if="!processing"
                                    @click="processPayment"
                                    class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white py-3 text-lg font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300"
                                >
                                    Pay {{ formatPrice(booking.grand_total) }}
                                </Button>

                                <!-- Security Notice -->
                                <Alert>
                                    <AlertDescription>
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span>Your payment information is encrypted and secure. We never store your card details.</span>
                                        </div>
                                    </AlertDescription>
                                </Alert>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Sidebar (1 column) -->
                    <div class="space-y-6">
                        <!-- Booking Summary -->
                        <Card class="sticky top-4">
                            <CardHeader>
                                <CardTitle>Booking Summary</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <!-- Vehicle Details -->
                                <div>
                                    <h4 class="font-semibold">{{ booking.vehicle_group_name }}</h4>
                                    <p class="text-sm text-gray-600">{{ booking.vehicle_category }}</p>
                                </div>

                                <!-- Rental Details -->
                                <div class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span>Pickup:</span>
                                        <span class="font-medium">{{ formatDate(booking.pickup_date) }} at {{ booking.pickup_time }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span>Return:</span>
                                        <span class="font-medium">{{ formatDate(booking.return_date) }} at {{ booking.return_time }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span>Duration:</span>
                                        <Badge variant="secondary">{{ booking.rental_duration_days }} days</Badge>
                                    </div>
                                </div>

                                <!-- Customer Details -->
                                <div>
                                    <h5 class="font-medium mb-2">Customer Information</h5>
                                    <div class="space-y-1 text-sm">
                                        <p>{{ booking.customer_full_name }}</p>
                                        <p class="text-gray-600">{{ booking.customer_email }}</p>
                                        <p class="text-gray-600">{{ booking.customer_phone }}</p>
                                    </div>
                                </div>

                                <!-- Extras -->
                                <div v-if="booking.selected_extras && booking.selected_extras.length > 0">
                                    <h5 class="font-medium mb-2">Selected Extras</h5>
                                    <div class="space-y-1 text-sm">
                                        <div v-for="extra in booking.selected_extras" :key="extra.code" class="flex justify-between">
                                            <span>{{ extra.name }}</span>
                                            <span>{{ formatPrice(extra.rate) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Price Breakdown -->
                                <div class="border-t pt-4">
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span>Vehicle Rental:</span>
                                            <span>{{ formatPrice(booking.base_rate_total) }}</span>
                                        </div>
                                        <div v-if="booking.extras_total > 0" class="flex justify-between">
                                            <span>Extras:</span>
                                            <span>{{ formatPrice(booking.extras_total) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>Taxes & Fees:</span>
                                            <span>{{ formatPrice(booking.taxes_total) }}</span>
                                        </div>
                                        <div class="flex justify-between text-lg font-bold border-t pt-2">
                                            <span>Total:</span>
                                            <span>{{ formatPrice(booking.grand_total) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Booking Reference -->
                                <div class="text-center p-3 bg-gray-50 rounded-lg">
                                    <p class="text-sm text-gray-600">Booking Reference</p>
                                    <p class="font-mono font-semibold">#{{ booking.id }}</p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Support -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="text-lg">Need Help?</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p class="text-sm text-gray-600 mb-4">
                                    If you have any questions about your booking or payment, our customer support team is here to help.
                                </p>
                                <div class="space-y-2 text-sm">
                                    <p><strong>Email:</strong> support@vrooem.com</p>
                                    <p><strong>Phone:</strong> +1 (555) 123-4567</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedHeaderLayout>
</template>