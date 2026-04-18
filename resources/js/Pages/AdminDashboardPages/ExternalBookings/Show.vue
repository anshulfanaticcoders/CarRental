<template>
    <AdminDashboardLayout>
        <div class="p-6 lg:p-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <Link :href="route('admin.external-bookings.index')" class="text-xs text-gray-400 hover:text-gray-600 flex items-center gap-1 mb-2">
                        <ArrowLeft class="w-3 h-3" /> Back to bookings
                    </Link>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-lg font-bold text-gray-900 font-mono">{{ booking.booking_number }}</h1>
                        <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full" :class="statusCls(booking.status)">{{ booking.status }}</span>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Created {{ fmtDate(booking.created_at) }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <Button v-if="booking.status === 'pending'" size="sm" @click="confirmBooking" :disabled="busy" class="h-8 text-xs">Confirm</Button>
                    <Button v-if="booking.status === 'confirmed'" size="sm" class="h-8 text-xs bg-emerald-600 hover:bg-emerald-700" @click="completeBooking" :disabled="busy">Complete</Button>
                    <Button v-if="booking.status === 'pending' || booking.status === 'confirmed'" size="sm" variant="destructive" @click="showCancel = true" :disabled="busy" class="h-8 text-xs">Cancel</Button>
                </div>
            </div>

            <!-- Cancellation banner -->
            <div v-if="booking.status === 'cancelled' && booking.cancellation_reason" class="mb-5 rounded-xl bg-red-50 border border-red-200 px-5 py-3">
                <p class="text-xs font-bold text-red-700 uppercase tracking-wide mb-0.5">Cancelled</p>
                <p class="text-sm text-red-700">{{ booking.cancellation_reason }}</p>
                <p v-if="booking.cancelled_at" class="text-[11px] text-red-400 mt-1">{{ fmtDate(booking.cancelled_at) }}</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">
                <!-- Left -->
                <div class="lg:col-span-3 space-y-5">

                    <!-- Trip -->
                    <div class="bg-white rounded-xl border border-gray-200 p-5">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Trip Details</p>
                        <div class="flex items-center gap-3 mb-4">
                            <div v-if="booking.vehicle_image" class="w-16 h-11 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0">
                                <img :src="booking.vehicle_image" :alt="booking.vehicle_name" class="w-full h-full object-cover" />
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900">{{ booking.vehicle_name || 'Vehicle' }}</p>
                                <p class="text-xs text-gray-400">{{ booking.vehicle?.vendor?.name || '' }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Pickup</p>
                                <p class="text-sm font-semibold text-gray-900">{{ fmtDate(booking.pickup_date) }}</p>
                                <p class="text-xs text-gray-500">{{ booking.pickup_time || '' }}</p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Return</p>
                                <p class="text-sm font-semibold text-gray-900">{{ fmtDate(booking.return_date) }}</p>
                                <p class="text-xs text-gray-500">{{ booking.return_time || '' }}</p>
                            </div>
                        </div>
                        <BookingLocationBlock
                            class="mt-4"
                            :pickup-string="booking.pickup_location"
                            :return-string="booking.return_location"
                            :pickup-details="booking.provider_metadata?.pickup_location_details || null"
                            :dropoff-details="booking.provider_metadata?.dropoff_location_details || null"
                            compact
                        />
                        <p class="text-xs text-gray-400 mt-3">{{ booking.total_days }} day{{ booking.total_days !== 1 ? 's' : '' }}</p>
                    </div>

                    <!-- Driver -->
                    <div class="bg-white rounded-xl border border-gray-200 p-5">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Driver</p>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div><p class="text-[11px] text-gray-400">Name</p><p class="font-medium text-gray-900">{{ booking.driver_first_name }} {{ booking.driver_last_name }}</p></div>
                            <div><p class="text-[11px] text-gray-400">Email</p><p class="font-medium text-gray-900">{{ booking.driver_email || '—' }}</p></div>
                            <div><p class="text-[11px] text-gray-400">Phone</p><p class="font-medium text-gray-900">{{ booking.driver_phone || '—' }}</p></div>
                            <div><p class="text-[11px] text-gray-400">Age</p><p class="font-medium text-gray-900">{{ booking.driver_age || '—' }}</p></div>
                            <div><p class="text-[11px] text-gray-400">License</p><p class="font-medium text-gray-900">{{ booking.driver_license_number || '—' }}</p></div>
                            <div><p class="text-[11px] text-gray-400">License Country</p><p class="font-medium text-gray-900">{{ booking.driver_license_country || '—' }}</p></div>
                        </div>
                    </div>

                    <!-- API Consumer -->
                    <div v-if="booking.consumer" class="bg-white rounded-xl border border-gray-200 p-5">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">API Consumer</p>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div><p class="text-[11px] text-gray-400">Company</p><p class="font-medium text-gray-900">{{ booking.consumer.name }}</p></div>
                            <div><p class="text-[11px] text-gray-400">Contact</p><p class="font-medium text-gray-900">{{ booking.consumer.contact_name || '—' }}</p></div>
                            <div><p class="text-[11px] text-gray-400">Email</p><p class="font-medium text-gray-900">{{ booking.consumer.contact_email || '—' }}</p></div>
                            <div><p class="text-[11px] text-gray-400">Plan</p><span class="text-[10px] font-bold uppercase px-1.5 py-0.5 rounded" :class="planCls(booking.consumer.plan)">{{ booking.consumer.plan }}</span></div>
                        </div>
                    </div>

                    <!-- Additional -->
                    <div v-if="booking.flight_number || booking.special_requests" class="bg-white rounded-xl border border-gray-200 p-5">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Additional</p>
                        <div class="space-y-2 text-sm">
                            <div v-if="booking.flight_number"><p class="text-[11px] text-gray-400">Flight</p><p class="font-medium text-gray-900">{{ booking.flight_number }}</p></div>
                            <div v-if="booking.special_requests"><p class="text-[11px] text-gray-400">Special Requests</p><p class="font-medium text-gray-900 whitespace-pre-line">{{ booking.special_requests }}</p></div>
                        </div>
                    </div>
                </div>

                <!-- Right -->
                <div class="lg:col-span-2 space-y-5">
                    <!-- Pricing -->
                    <div class="bg-white rounded-xl border border-gray-200 p-5">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Price Breakdown</p>
                        <div class="space-y-2.5">
                            <div class="flex justify-between text-sm"><span class="text-gray-500">Daily Rate</span><span class="font-medium">{{ sym }}{{ fmt(booking.daily_rate) }}</span></div>
                            <div class="flex justify-between text-sm"><span class="text-gray-500">{{ booking.total_days }}d &times; {{ sym }}{{ fmt(booking.daily_rate) }}</span><span class="font-medium">{{ sym }}{{ fmt(booking.base_price) }}</span></div>
                            <div v-if="booking.extras_total > 0" class="flex justify-between text-sm"><span class="text-gray-500">Extras</span><span class="font-medium">{{ sym }}{{ fmt(booking.extras_total) }}</span></div>
                            <div class="border-t border-dashed border-gray-200 pt-2.5 flex justify-between items-center">
                                <span class="text-sm font-bold text-gray-900">Total</span>
                                <span class="text-base font-bold text-gray-900">{{ sym }}{{ fmt(booking.total_amount) }}</span>
                            </div>
                            <p class="text-[10px] text-gray-400 text-right uppercase">{{ booking.currency }}</p>
                        </div>
                    </div>

                    <!-- Extras -->
                    <div v-if="booking.extras?.length" class="bg-white rounded-xl border border-gray-200 p-5">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Extras</p>
                        <div class="space-y-2">
                            <div v-for="e in booking.extras" :key="e.id" class="flex justify-between text-sm">
                                <span class="text-gray-700">{{ e.extra_name }} <span class="text-gray-400">&times;{{ e.quantity }}</span></span>
                                <span class="font-medium">{{ sym }}{{ fmt(e.total_price) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Test badge -->
                    <div v-if="booking.is_test" class="bg-amber-50 rounded-xl border border-amber-200 px-4 py-3 text-center">
                        <p class="text-xs font-bold text-amber-700 uppercase">Test Booking</p>
                        <p class="text-[11px] text-amber-600 mt-0.5">Sandbox mode — not a real booking</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancel Dialog -->
        <Dialog v-model:open="showCancel">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Cancel Booking</DialogTitle>
                    <DialogDescription>Provide a reason for cancelling {{ booking.booking_number }}.</DialogDescription>
                </DialogHeader>
                <div class="py-4">
                    <Textarea v-model="reason" placeholder="Cancellation reason..." rows="3" class="w-full" />
                    <p v-if="reasonErr" class="text-xs text-red-600 mt-1.5">{{ reasonErr }}</p>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showCancel = false">Back</Button>
                    <Button variant="destructive" @click="cancelBooking" :disabled="busy">{{ busy ? 'Cancelling...' : 'Cancel Booking' }}</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import BookingLocationBlock from '@/Components/Booking/BookingLocationBlock.vue';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Textarea } from '@/Components/ui/textarea';
import { ArrowLeft } from 'lucide-vue-next';
import { useToast } from 'vue-toastification';

const toast = useToast();
const props = defineProps({ booking: { type: Object, required: true } });

const busy = ref(false);
const showCancel = ref(false);
const reason = ref('');
const reasonErr = ref('');

const sym = computed(() => ({ USD: '$', EUR: '\u20ac', GBP: '\u00a3', INR: '\u20b9', CHF: 'Fr', AED: 'AED', MAD: 'MAD' }[props.booking.currency] || props.booking.currency || ''));
const fmt = (n) => new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n || 0);
const fmtDate = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';

const statusCls = (s) => ({ pending: 'bg-amber-100 text-amber-700', confirmed: 'bg-blue-100 text-blue-700', completed: 'bg-emerald-100 text-emerald-700', cancelled: 'bg-red-100 text-red-700' }[s] || 'bg-gray-100 text-gray-600');
const planCls = (p) => ({ basic: 'bg-gray-100 text-gray-700', premium: 'bg-blue-100 text-blue-700', enterprise: 'bg-purple-100 text-purple-700' }[p] || 'bg-gray-100 text-gray-600');

const confirmBooking = () => { busy.value = true; router.post(route('admin.external-bookings.confirm', props.booking.id), {}, { preserveScroll: true, onSuccess: () => toast.success('Confirmed'), onFinish: () => busy.value = false }); };
const completeBooking = () => { busy.value = true; router.post(route('admin.external-bookings.complete', props.booking.id), {}, { preserveScroll: true, onSuccess: () => toast.success('Completed'), onFinish: () => busy.value = false }); };
const cancelBooking = () => { if (!reason.value.trim()) { reasonErr.value = 'Reason is required'; return; } reasonErr.value = ''; busy.value = true; router.post(route('admin.external-bookings.cancel', props.booking.id), { reason: reason.value }, { preserveScroll: true, onSuccess: () => { showCancel.value = false; reason.value = ''; toast.success('Cancelled'); }, onError: (e) => { reasonErr.value = e.reason || 'Failed'; }, onFinish: () => busy.value = false }); };
</script>
