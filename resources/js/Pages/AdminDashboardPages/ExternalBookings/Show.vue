<template>
    <AdminDashboardLayout>
        <div class="py-12 px-4 sm:px-6 lg:px-8">
            <div class="mx-auto">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div class="flex items-center gap-3">
                        <Link :href="route('admin.external-bookings.index')">
                            <Button variant="outline" size="sm"><ArrowLeft class="w-4 h-4" /></Button>
                        </Link>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                                {{ booking.booking_number }}
                                <Badge :class="statusBadgeClass(booking.status)">
                                    <span class="w-1.5 h-1.5 rounded-full mr-1.5 inline-block" :class="statusDotClass(booking.status)"></span>
                                    {{ booking.status }}
                                </Badge>
                            </h1>
                            <p class="text-sm text-gray-600 mt-1">Created {{ formatDate(booking.created_at) }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button v-if="booking.status === 'pending'" @click="confirmBooking" :disabled="isLoading">
                            <CheckCircle class="w-4 h-4 mr-2" />Confirm
                        </Button>
                        <Button v-if="booking.status === 'confirmed'" class="bg-emerald-600 hover:bg-emerald-700" @click="completeBooking" :disabled="isLoading">
                            <CheckCircle class="w-4 h-4 mr-2" />Mark Completed
                        </Button>
                        <Button
                            v-if="booking.status === 'pending' || booking.status === 'confirmed'"
                            variant="destructive"
                            @click="showCancelDialog = true"
                            :disabled="isLoading"
                        >
                            <XCircle class="w-4 h-4 mr-2" />Cancel
                        </Button>
                    </div>
                </div>

                <!-- Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Column -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Booking Info -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2"><CalendarDays class="w-5 h-5" /> Booking Info</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                                    <div>
                                        <dt class="text-gray-500">Booking Number</dt>
                                        <dd class="font-mono font-semibold text-gray-900 mt-0.5">{{ booking.booking_number }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500">Status</dt>
                                        <dd class="mt-0.5">
                                            <Badge :class="statusBadgeClass(booking.status)">{{ booking.status }}</Badge>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500">Pickup Date</dt>
                                        <dd class="font-medium text-gray-900 mt-0.5">{{ formatDate(booking.pickup_date) }} <span v-if="booking.pickup_time" class="text-gray-500">at {{ booking.pickup_time }}</span></dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500">Return Date</dt>
                                        <dd class="font-medium text-gray-900 mt-0.5">{{ formatDate(booking.return_date) }} <span v-if="booking.return_time" class="text-gray-500">at {{ booking.return_time }}</span></dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500">Pickup Location</dt>
                                        <dd class="font-medium text-gray-900 mt-0.5">{{ booking.pickup_location || '--' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500">Return Location</dt>
                                        <dd class="font-medium text-gray-900 mt-0.5">{{ booking.return_location || '--' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500">Total Days</dt>
                                        <dd class="font-medium text-gray-900 mt-0.5">{{ booking.total_days }}</dd>
                                    </div>
                                </dl>
                            </CardContent>
                        </Card>

                        <!-- Vehicle Info -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2"><Car class="w-5 h-5" /> Vehicle Info</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="flex items-center gap-4">
                                    <div v-if="booking.vehicle_image" class="w-20 h-14 rounded-lg overflow-hidden bg-gray-100 shrink-0">
                                        <img :src="booking.vehicle_image" :alt="booking.vehicle_name" class="w-full h-full object-cover" />
                                    </div>
                                    <div v-else class="w-14 h-14 rounded-lg bg-gray-100 flex items-center justify-center shrink-0">
                                        <Car class="w-6 h-6 text-gray-400" />
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ booking.vehicle_name || (booking.vehicle?.brand + ' ' + booking.vehicle?.model) || 'N/A' }}</p>
                                        <p class="text-sm text-gray-500 mt-0.5">Vendor: {{ booking.vehicle?.vendor?.name || 'N/A' }}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Driver Details -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2"><UserIcon class="w-5 h-5" /> Driver Details</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                                    <div>
                                        <dt class="text-gray-500">Full Name</dt>
                                        <dd class="font-medium text-gray-900 mt-0.5">{{ booking.driver_first_name }} {{ booking.driver_last_name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500">Email</dt>
                                        <dd class="font-medium text-gray-900 mt-0.5">{{ booking.driver_email || '--' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500">Phone</dt>
                                        <dd class="font-medium text-gray-900 mt-0.5">{{ booking.driver_phone || '--' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500">Age</dt>
                                        <dd class="font-medium text-gray-900 mt-0.5">{{ booking.driver_age || '--' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500">License Number</dt>
                                        <dd class="font-medium text-gray-900 mt-0.5">{{ booking.driver_license_number || '--' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500">License Country</dt>
                                        <dd class="font-medium text-gray-900 mt-0.5">{{ booking.driver_license_country || '--' }}</dd>
                                    </div>
                                </dl>
                            </CardContent>
                        </Card>

                        <!-- API Consumer -->
                        <Card v-if="booking.consumer">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2"><Globe class="w-5 h-5" /> API Consumer</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                                    <div>
                                        <dt class="text-gray-500">Company Name</dt>
                                        <dd class="font-medium text-gray-900 mt-0.5">{{ booking.consumer.name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500">Contact</dt>
                                        <dd class="font-medium text-gray-900 mt-0.5">{{ booking.consumer.contact_name || '--' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500">Email</dt>
                                        <dd class="font-medium text-gray-900 mt-0.5">{{ booking.consumer.contact_email || '--' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500">Plan</dt>
                                        <dd class="mt-0.5">
                                            <Badge :class="planBadgeClass(booking.consumer.plan)">{{ booking.consumer.plan }}</Badge>
                                        </dd>
                                    </div>
                                </dl>
                            </CardContent>
                        </Card>

                        <!-- Additional Info -->
                        <Card v-if="booking.flight_number || booking.special_requests">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2"><Info class="w-5 h-5" /> Additional Information</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <dl class="grid grid-cols-1 gap-y-4 text-sm">
                                    <div v-if="booking.flight_number">
                                        <dt class="text-gray-500">Flight Number</dt>
                                        <dd class="font-medium text-gray-900 mt-0.5">{{ booking.flight_number }}</dd>
                                    </div>
                                    <div v-if="booking.special_requests">
                                        <dt class="text-gray-500">Special Requests</dt>
                                        <dd class="font-medium text-gray-900 mt-0.5 whitespace-pre-line">{{ booking.special_requests }}</dd>
                                    </div>
                                </dl>
                            </CardContent>
                        </Card>

                        <!-- Cancellation -->
                        <Card v-if="booking.status === 'cancelled' && booking.cancellation_reason" class="border-red-200">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2 text-red-700"><XCircle class="w-5 h-5" /> Cancellation</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p class="text-sm text-red-700">{{ booking.cancellation_reason }}</p>
                                <p v-if="booking.cancelled_at" class="text-xs text-red-500 mt-2">Cancelled on {{ formatDate(booking.cancelled_at) }}</p>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Right Column: Pricing -->
                    <div class="space-y-6">
                        <!-- Price Breakdown -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2"><Receipt class="w-5 h-5" /> Pricing</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-3">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Daily Rate</span>
                                    <span class="font-medium text-gray-800">{{ currSym }}{{ formatNumber(booking.daily_rate) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Base Price ({{ booking.total_days }}d)</span>
                                    <span class="font-medium text-gray-800">{{ currSym }}{{ formatNumber(booking.base_price) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Extras Total</span>
                                    <span class="font-medium text-gray-800">{{ currSym }}{{ formatNumber(booking.extras_total) }}</span>
                                </div>
                                <div class="border-t border-dashed border-gray-200 my-1"></div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-bold text-gray-900">Total Amount</span>
                                    <span class="text-lg font-bold text-indigo-700">{{ currSym }}{{ formatNumber(booking.total_amount) }}</span>
                                </div>
                                <p class="text-xs text-gray-400 text-right">{{ booking.currency }}</p>
                            </CardContent>
                        </Card>

                        <!-- Extras List -->
                        <Card v-if="booking.extras?.length">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2"><Package class="w-5 h-5" /> Extras</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Extra</TableHead>
                                            <TableHead class="text-center">Qty</TableHead>
                                            <TableHead class="text-right">Price</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="extra in booking.extras" :key="extra.id">
                                            <TableCell class="text-sm">{{ extra.extra_name }}</TableCell>
                                            <TableCell class="text-center text-sm">{{ extra.quantity }}</TableCell>
                                            <TableCell class="text-right text-sm font-medium">{{ currSym }}{{ formatNumber(extra.total_price) }}</TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancel Dialog -->
        <Dialog v-model:open="showCancelDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Cancel Booking</DialogTitle>
                    <DialogDescription>Please provide a reason for cancelling booking {{ booking.booking_number }}. This action cannot be undone.</DialogDescription>
                </DialogHeader>
                <div class="py-4">
                    <Textarea v-model="cancelReason" placeholder="Enter cancellation reason..." rows="4" class="w-full" />
                    <p v-if="cancelError" class="text-sm text-red-600 mt-2">{{ cancelError }}</p>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showCancelDialog = false">Back</Button>
                    <Button variant="destructive" @click="cancelBooking" :disabled="isCancelling">
                        {{ isCancelling ? 'Cancelling...' : 'Cancel Booking' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Textarea } from '@/Components/ui/textarea';
import { ArrowLeft, CalendarDays, Car, UserIcon, Globe, Info, Receipt, Package, CheckCircle, XCircle } from 'lucide-vue-next';
import { useToast } from 'vue-toastification';

const toast = useToast();

const props = defineProps({
    booking: { type: Object, required: true },
});

const isLoading = ref(false);
const showCancelDialog = ref(false);
const cancelReason = ref('');
const cancelError = ref('');
const isCancelling = ref(false);

const currSym = computed(() => getCurrencySymbol(props.booking.currency));

const confirmBooking = () => {
    isLoading.value = true;
    router.post(route('admin.external-bookings.confirm', props.booking.id), {}, {
        preserveScroll: true,
        onSuccess: () => toast.success('Booking confirmed successfully!', { position: 'top-right', timeout: 3000 }),
        onError: () => toast.error('Failed to confirm booking.', { position: 'top-right', timeout: 5000 }),
        onFinish: () => { isLoading.value = false; },
    });
};

const completeBooking = () => {
    isLoading.value = true;
    router.post(route('admin.external-bookings.complete', props.booking.id), {}, {
        preserveScroll: true,
        onSuccess: () => toast.success('Booking marked as completed!', { position: 'top-right', timeout: 3000 }),
        onError: () => toast.error('Failed to complete booking.', { position: 'top-right', timeout: 5000 }),
        onFinish: () => { isLoading.value = false; },
    });
};

const cancelBooking = () => {
    if (!cancelReason.value.trim()) {
        cancelError.value = 'Please enter a cancellation reason.';
        return;
    }
    cancelError.value = '';
    isCancelling.value = true;
    router.post(route('admin.external-bookings.cancel', props.booking.id), { reason: cancelReason.value }, {
        preserveScroll: true,
        onSuccess: () => {
            showCancelDialog.value = false;
            cancelReason.value = '';
            toast.success('Booking cancelled successfully.', { position: 'top-right', timeout: 3000 });
        },
        onError: (errors) => {
            cancelError.value = errors.reason || 'Failed to cancel booking.';
        },
        onFinish: () => { isCancelling.value = false; },
    });
};

const getCurrencySymbol = (currency) => {
    const symbols = { 'USD': '$', 'EUR': '\u20ac', 'GBP': '\u00a3', 'CHF': 'Fr', 'AED': 'AED', 'MAD': 'MAD' };
    return symbols[currency] || currency || '$';
};

const formatNumber = (number) => new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(number);

const formatDate = (dateStr) => {
    if (!dateStr) return '--';
    return new Date(dateStr).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

const statusBadgeClass = (status) => ({
    'bg-amber-50 text-amber-700 border-amber-200': status === 'pending',
    'bg-blue-50 text-blue-700 border-blue-200': status === 'confirmed',
    'bg-green-50 text-green-700 border-green-200': status === 'completed',
    'bg-red-50 text-red-600 border-red-200': status === 'cancelled',
});

const statusDotClass = (status) => ({
    'bg-amber-500': status === 'pending',
    'bg-blue-500': status === 'confirmed',
    'bg-green-500': status === 'completed',
    'bg-red-500': status === 'cancelled',
}[status] || 'bg-gray-400');

const planBadgeClass = (plan) => ({
    'bg-gray-100 text-gray-700 border-gray-200': plan === 'basic',
    'bg-blue-100 text-blue-700 border-blue-200': plan === 'premium',
    'bg-purple-100 text-purple-700 border-purple-200': plan === 'enterprise',
});
</script>
