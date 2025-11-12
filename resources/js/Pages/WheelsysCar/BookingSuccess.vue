<script setup>
import { Link, Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Separator } from '@/Components/ui/separator';
import { CheckCircle } from 'lucide-vue-next';

const props = defineProps({
    booking: Object,
});

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

const isConfirmed = computed(() => {
    return props.booking.booking_status === 'confirmed' || props.booking.stripe_payment_status === 'succeeded';
});
</script>

<template>
    <Head>
        <title>Booking Confirmed - {{ booking.vehicle_group_name }}</title>
    </Head>

    <AuthenticatedHeaderLayout>
        <div class="min-h-screen bg-gradient-to-br from-green-50 to-blue-50 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Success Header -->
                <div class="text-center mb-12">
                    <div class="flex justify-center mb-6">
                        <div class="bg-green-100 rounded-full p-6">
                            <CheckCircle class="w-16 h-16 text-green-600" />
                        </div>
                    </div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-4">
                        {{ isConfirmed ? 'Booking Confirmed!' : 'Booking Received!' }}
                    </h1>
                    <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                        Thank you for your booking! Your reservation has been {{ isConfirmed ? 'confirmed' : 'received' }}
                        and you'll receive a confirmation email shortly with all the details.
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Main Content (2 columns) -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Booking Details -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <CheckCircle class="w-5 h-5 text-green-600" />
                                    Booking Confirmation Details
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <!-- Reference Numbers -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="p-4 bg-blue-50 rounded-lg">
                                        <p class="text-sm text-blue-600 font-medium">Booking Reference</p>
                                        <p class="text-xl font-bold text-blue-900">#{{ booking.id }}</p>
                                    </div>
                                    <div class="p-4 bg-green-50 rounded-lg">
                                        <p class="text-sm text-green-600 font-medium">Wheelsys Reference</p>
                                        <p class="text-xl font-bold text-green-900">
                                            {{ booking.wheelsys_booking_ref || 'Processing...' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Vehicle Information -->
                                <div>
                                    <h3 class="font-semibold mb-3">Vehicle Information</h3>
                                    <div class="flex gap-4 p-4 bg-gray-50 rounded-lg">
                                        <img
                                            :src="booking.vehicle_image_url"
                                            :alt="booking.vehicle_group_name"
                                            class="w-24 h-20 object-cover rounded-lg"
                                            onerror="this.src='/images/placeholder-car.jpg'"
                                        />
                                        <div>
                                            <h4 class="font-semibold">{{ booking.vehicle_group_name }}</h4>
                                            <p class="text-gray-600">{{ booking.vehicle_category }}</p>
                                            <div class="flex gap-4 mt-2 text-sm text-gray-500">
                                                <span>{{ booking.vehicle_passengers }} Seats</span>
                                                <span>{{ booking.vehicle_doors }} Doors</span>
                                                <span>{{ booking.vehicle_bags + booking.vehicle_suitcases }} Luggage</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rental Details -->
                                <div>
                                    <h3 class="font-semibold mb-3">Rental Details</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="p-4 bg-green-50 rounded-lg">
                                            <p class="text-sm text-green-600 font-medium">Pickup</p>
                                            <p class="font-semibold text-green-900">{{ booking.pickup_station_name }}</p>
                                            <p class="text-sm text-green-700">{{ formatDate(booking.pickup_date) }} at {{ booking.pickup_time }}</p>
                                        </div>
                                        <div class="p-4 bg-blue-50 rounded-lg">
                                            <p class="text-sm text-blue-600 font-medium">Return</p>
                                            <p class="font-semibold text-blue-900">{{ booking.return_station_name }}</p>
                                            <p class="text-sm text-blue-700">{{ formatDate(booking.return_date) }} at {{ booking.return_time }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-4 text-center">
                                        <Badge variant="secondary" class="text-lg px-4 py-2">
                                            {{ booking.rental_duration_days }} {{ booking.rental_duration_days === 1 ? 'Day' : 'Days' }}
                                        </Badge>
                                    </div>
                                </div>

                                <!-- Customer Information -->
                                <div>
                                    <h3 class="font-semibold mb-3">Customer Information</h3>
                                    <div class="p-4 bg-gray-50 rounded-lg">
                                        <p class="font-medium">{{ booking.customer_full_name }}</p>
                                        <p class="text-gray-600">{{ booking.customer_email }}</p>
                                        <p class="text-gray-600">{{ booking.customer_phone }}</p>
                                        <p v-if="booking.customer_address" class="text-gray-600">{{ booking.customer_address }}</p>
                                    </div>
                                </div>

                                <!-- Selected Extras -->
                                <div v-if="booking.selected_extras && booking.selected_extras.length > 0">
                                    <h3 class="font-semibold mb-3">Selected Extras</h3>
                                    <div class="space-y-2">
                                        <div
                                            v-for="extra in booking.selected_extras"
                                            :key="extra.code"
                                            class="flex justify-between p-3 bg-gray-50 rounded-lg"
                                        >
                                            <span>{{ extra.name }}</span>
                                            <span class="font-medium">{{ formatPrice(extra.rate) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Additional Notes -->
                                <div v-if="booking.customer_notes">
                                    <h3 class="font-semibold mb-3">Additional Notes</h3>
                                    <div class="p-4 bg-gray-50 rounded-lg">
                                        <p class="text-gray-700">{{ booking.customer_notes }}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Important Information -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Important Information</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <div>
                                        <h4 class="font-medium mb-2">Pickup Instructions</h4>
                                        <ul class="space-y-1 text-sm text-gray-600">
                                            <li>• Please arrive 15 minutes before your scheduled pickup time</li>
                                            <li>• Bring a valid driver's license and credit card</li>
                                            <li>• Minimum driver age: {{ booking.customer_age }} years</li>
                                        </ul>
                                    </div>
                                    <div>
                                        <h4 class="font-medium mb-2">Payment Information</h4>
                                        <ul class="space-y-1 text-sm text-gray-600">
                                            <li>• Payment: {{ formatPrice(booking.grand_total) }} ({{ formatPrice(booking.amount_paid) }} paid)</li>
                                            <li>• Payment Method: Credit/Debit Card</li>
                                            <li>• Remaining balance: {{ formatPrice(booking.grand_total - booking.amount_paid) }}</li>
                                        </ul>
                                    </div>
                                    <div>
                                        <h4 class="font-medium mb-2">Cancellation Policy</h4>
                                        <p class="text-sm text-gray-600">
                                            Free cancellation up to 24 hours before pickup time.
                                            Late cancellations may incur charges.
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Sidebar (1 column) -->
                    <div class="space-y-6">
                        <!-- Payment Status -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Payment Status</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="text-center space-y-4">
                                    <div class="inline-flex items-center px-4 py-2 rounded-full"
                                         :class="isConfirmed ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'">
                                        <CheckCircle v-if="isConfirmed" class="w-5 h-5 mr-2" />
                                        {{ isConfirmed ? 'Payment Complete' : 'Payment Processing' }}
                                    </div>
                                    <div class="space-y-2">
                                        <p class="text-sm text-gray-600">Total Amount</p>
                                        <p class="text-2xl font-bold">{{ formatPrice(booking.grand_total) }}</p>
                                        <p class="text-sm text-green-600">{{ formatPrice(booking.amount_paid) }} paid</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Next Steps -->
                        <Card>
                            <CardHeader>
                                <CardTitle>What's Next?</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <div class="flex items-start gap-3">
                                        <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                            <span class="text-xs text-blue-600 font-bold">1</span>
                                        </div>
                                        <div>
                                            <p class="font-medium">Confirmation Email</p>
                                            <p class="text-sm text-gray-600">Check your email for booking confirmation</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                            <span class="text-xs text-blue-600 font-bold">2</span>
                                        </div>
                                        <div>
                                            <p class="font-medium">Prepare Documents</p>
                                            <p class="text-sm text-gray-600">Have license and credit card ready</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                            <span class="text-xs text-blue-600 font-bold">3</span>
                                        </div>
                                        <div>
                                            <p class="font-medium">Arrive on Time</p>
                                            <p class="text-sm text-gray-600">Pick up your vehicle at scheduled time</p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Contact Support -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Need Help?</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p class="text-sm text-gray-600 mb-4">
                                    Our customer support team is available 24/7 to assist you.
                                </p>
                                <div class="space-y-2 text-sm">
                                    <p><strong>Email:</strong> support@vrooem.com</p>
                                    <p><strong>Phone:</strong> +1 (555) 123-4567</p>
                                    <p><strong>Live Chat:</strong> Available on website</p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Action Buttons -->
                        <div class="space-y-4">
                            <Link :href="route('profile.bookings')">
                                <Button class="w-full">
                                    View My Bookings
                                </Button>
                            </Link>
                            <Link href="/">
                                <Button variant="outline" class="w-full">
                                    Back to Home
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedHeaderLayout>
</template>