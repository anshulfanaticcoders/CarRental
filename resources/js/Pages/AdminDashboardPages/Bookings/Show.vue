<template>
    <AdminDashboardLayout>
        <div class="space-y-6 p-6">
            <div v-if="flash?.success" class="rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-green-800">
                {{ flash.success }}
            </div>
            <div v-if="flash?.error" class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-red-800">
                {{ flash.error }}
            </div>

            <!-- Header -->
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link :href="route('customer-bookings.index')">
                        <Button variant="outline" size="sm" class="flex items-center gap-1">
                            <ArrowLeft class="h-4 w-4" />
                            Back to bookings
                        </Button>
                    </Link>
                    <div>
                        <h1 class="font-mono text-2xl font-bold">{{ booking.booking_number }}</h1>
                        <p class="text-sm text-muted-foreground">Created {{ formatDate(booking.created_at) }}</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Badge :variant="getStatusBadgeBooking(booking.booking_status)" class="capitalize">
                        {{ formatStatusLabel(booking.booking_status) }}
                    </Badge>
                    <Badge v-if="booking.payments?.length" :variant="getPaymentBadgeVariant(booking.payments[0].payment_status)">
                        {{ booking.payments[0].payment_status }}
                    </Badge>
                    <Badge variant="outline" class="capitalize">{{ booking.plan }}</Badge>
                </div>
            </div>

            <!-- Problems banner -->
            <div v-if="getProblemInfo(booking).length || booking.notes" class="rounded-xl border border-amber-300 bg-amber-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-800">Problems & history</p>
                <dl class="mt-3 space-y-2 text-sm">
                    <div v-for="item in getProblemInfo(booking)" :key="item.label">
                        <dt class="text-xs font-semibold text-amber-800">{{ item.label }}</dt>
                        <dd class="text-amber-900">{{ item.value }}</dd>
                    </div>
                    <div v-if="booking.notes">
                        <dt class="text-xs font-semibold text-amber-800">Notes</dt>
                        <dd class="whitespace-pre-line text-amber-900">{{ booking.notes }}</dd>
                    </div>
                </dl>
            </div>

            <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
                <!-- Booking -->
                <div class="rounded-xl border bg-card p-5 shadow">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Booking</p>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-muted-foreground">Booking number</dt>
                            <dd class="font-mono font-medium">{{ booking.booking_number }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-muted-foreground">Plan</dt>
                            <dd class="font-medium capitalize">{{ booking.plan }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-muted-foreground">Total days</dt>
                            <dd class="font-medium">{{ booking.total_days || 0 }} days</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-muted-foreground">Trip from</dt>
                            <dd class="font-medium">{{ formatTripDate(booking.trip_from_date) }} {{ booking.trip_from_time || '' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-muted-foreground">Trip to</dt>
                            <dd class="font-medium">{{ formatTripDate(booking.trip_to_date) }} {{ booking.trip_to_time || '' }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Pickup location</dt>
                            <dd class="font-medium" :class="isPlaceholderValue(booking.pickup_location) ? 'italic text-amber-700' : ''">
                                {{ booking.pickup_location || 'N/A' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Return location</dt>
                            <dd class="font-medium" :class="isPlaceholderValue(booking.return_location || booking.pickup_location) ? 'italic text-amber-700' : ''">
                                {{ booking.return_location || booking.pickup_location || 'N/A' }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Customer -->
                <div class="rounded-xl border bg-card p-5 shadow">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Customer</p>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-muted-foreground">Name</dt>
                            <dd class="font-medium">{{ booking.customer?.first_name }} {{ booking.customer?.last_name }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-muted-foreground">Email</dt>
                            <dd class="break-all font-medium">{{ booking.customer?.email || 'N/A' }}</dd>
                        </div>
                        <div v-if="booking.customer?.phone" class="flex items-start justify-between gap-3">
                            <dt class="text-muted-foreground">Phone</dt>
                            <dd class="font-medium">{{ booking.customer.phone }}</dd>
                        </div>
                        <div v-if="booking.customer_address" class="flex items-start justify-between gap-3">
                            <dt class="text-muted-foreground">Address</dt>
                            <dd class="text-right font-medium">{{ booking.customer_address }}</dd>
                        </div>
                        <div v-if="booking.customer_city || booking.customer_country" class="flex items-start justify-between gap-3">
                            <dt class="text-muted-foreground">City / Country</dt>
                            <dd class="font-medium">{{ [booking.customer_city, booking.customer_country].filter(Boolean).join(', ') }}</dd>
                        </div>
                        <div v-if="booking.flight_number" class="flex items-start justify-between gap-3">
                            <dt class="text-muted-foreground">Flight number</dt>
                            <dd class="font-medium">{{ booking.flight_number }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Vehicle & Provider -->
                <div class="rounded-xl border bg-card p-5 shadow">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Vehicle & Provider</p>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="text-muted-foreground">Vehicle</dt>
                            <dd class="font-medium">{{ getVehicleName(booking) }}</dd>
                            <dd class="text-xs text-muted-foreground">{{ getVehicleMeta(booking) }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-muted-foreground">Provider</dt>
                            <dd class="font-medium">{{ getProviderName(booking) }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Provider reference</dt>
                            <dd class="font-mono font-medium">{{ getProviderRef(booking) }}</dd>
                        </div>
                        <div v-if="getGatewayBookingId(booking)">
                            <dt class="text-muted-foreground">Gateway booking id</dt>
                            <dd class="break-all font-mono font-medium">{{ getGatewayBookingId(booking) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Payment -->
                <div class="rounded-xl border bg-card p-5 shadow">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Payment</p>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-muted-foreground">Currency</dt>
                            <dd class="font-medium">{{ getAdminAmounts(booking).currency }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-muted-foreground">Total</dt>
                            <dd class="font-medium">{{ formatCurrency(getAdminAmounts(booking).total, getAdminAmounts(booking).currency) }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-muted-foreground">Collected</dt>
                            <dd class="font-medium text-green-600">{{ formatCurrency(getAdminAmounts(booking).paid, getAdminAmounts(booking).currency) }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-muted-foreground">Pending</dt>
                            <dd class="font-medium text-yellow-600">{{ formatCurrency(getAdminAmounts(booking).pending, getAdminAmounts(booking).currency) }}</dd>
                        </div>
                        <div v-if="booking.stripe_payment_intent_id">
                            <dt class="text-muted-foreground">Stripe payment intent</dt>
                            <dd class="select-all break-all font-mono text-xs font-medium">{{ booking.stripe_payment_intent_id }}</dd>
                        </div>
                        <div v-if="booking.stripe_session_id">
                            <dt class="text-muted-foreground">Stripe session</dt>
                            <dd class="select-all break-all font-mono text-xs font-medium">{{ booking.stripe_session_id }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Payment records -->
                <div v-if="booking.payments?.length" class="rounded-xl border bg-card p-5 shadow">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Payment records</p>
                    <div class="mt-4 space-y-3">
                        <div v-for="payment in booking.payments" :key="payment.id" class="rounded-lg border bg-background/40 p-3 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-medium">{{ formatCurrency(Number(payment.amount || 0), payment.currency) }}</span>
                                <Badge :variant="getPaymentBadgeVariant(payment.payment_status)">{{ payment.payment_status }}</Badge>
                            </div>
                            <div class="mt-1 text-xs text-muted-foreground">{{ formatDate(payment.payment_date || payment.created_at) }} · {{ payment.payment_method }}</div>
                            <div v-if="payment.transaction_id" class="mt-1 select-all break-all font-mono text-xs">{{ payment.transaction_id }}</div>
                        </div>
                    </div>
                </div>

                <!-- Extras -->
                <div v-if="booking.extras?.length" class="rounded-xl border bg-card p-5 shadow">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Extras</p>
                    <div class="mt-4 space-y-2 text-sm">
                        <div v-for="extra in booking.extras" :key="extra.id" class="flex items-center justify-between gap-3">
                            <span>{{ extra.extra_name || extra.name }} <span v-if="extra.quantity > 1" class="text-xs text-muted-foreground">× {{ extra.quantity }}</span></span>
                            <span class="font-medium">{{ formatCurrency(Number(extra.price || 0), getAdminAmounts(booking).currency) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Link } from '@inertiajs/vue3';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { ArrowLeft } from 'lucide-vue-next';
import { getCurrencySymbol as registryCurrencySymbol } from '@/utils/currencyRegistry';

defineProps({
    booking: Object,
    flash: Object,
});

const getStatusBadgeBooking = (status) => {
    switch (status) {
        case 'pending': return 'secondary';
        case 'cancelled':
        case 'reservation_failed':
        case 'rejected': return 'destructive';
        case 'expired': return 'outline';
        default: return 'default';
    }
};

const formatStatusLabel = (status) => String(status || '').replace(/_/g, ' ');

const isPlaceholderValue = (value) => String(value || '').toLowerCase().includes('needs correction');

const getProblemInfo = (booking) => {
    const pm = booking?.provider_metadata || {};
    const items = [];

    const supplierError = pm.reservation_final_error || pm.gateway_error || pm.reservation_last_error;
    if (supplierError) items.push({ label: 'Supplier error', value: supplierError });
    if (pm.needs_correction) items.push({ label: 'Needs correction', value: pm.needs_correction_reason || 'Stored with placeholder data from a degraded Stripe session.' });
    if (pm.manual_refund_required && !pm.refund_recorded_at) items.push({ label: 'Manual refund required', value: 'Refund this payment in the Stripe dashboard — the refund will be recorded here automatically.' });
    if (pm.refund_recorded_at) items.push({ label: 'Refund recorded', value: `${((pm.refund_amount_minor || 0) / 100).toFixed(2)} ${pm.refund_currency || ''} (${pm.fully_refunded ? 'full' : 'partial'}) on ${formatDate(pm.refund_recorded_at)}` });
    if (pm.dispute_opened_at) items.push({ label: 'Dispute opened', value: `${pm.dispute_reason || 'no reason given'} — respond in the Stripe dashboard before the deadline.` });
    if (pm.reservation_manual_check) items.push({ label: 'Manual check', value: pm.rescue_gave_up_reason || 'Automatic retries stopped — needs a human decision.' });
    if (pm.rescue_attempts) items.push({ label: 'Rescue attempts', value: `${pm.rescue_attempts}${pm.rescue_last_attempt_at ? ' (last: ' + formatDate(pm.rescue_last_attempt_at) + ')' : ''}` });
    if (pm.amount_mismatch) items.push({ label: 'Amount mismatch', value: `Stripe captured ${((pm.amount_mismatch.charged_minor || 0) / 100).toFixed(2)} ${pm.amount_mismatch.charged_currency || ''} but checkout expected ${pm.amount_mismatch.expected_amount} ${pm.amount_mismatch.expected_currency || ''}` });
    if (pm.manual_retry_at) items.push({ label: 'Manual retry', value: `Queued ${formatDate(pm.manual_retry_at)}` });
    if (booking?.cancellation_reason) items.push({ label: 'Cancellation reason', value: booking.cancellation_reason });

    return items;
};

const providerNames = {
    adobe_car: 'Adobe Car', click2rent: 'Click2Rent', easirent: 'Easirent', emr: 'EMR',
    favrica: 'Favrica', green_motion: 'Green Motion', greenmotion: 'Green Motion',
    internal: 'Internal Fleet', locauto_rent: 'Locauto', ok_mobility: 'OK Mobility',
    recordgo: 'Record Go', renteon: 'Renteon', sicily_by_car: 'Sicily by Car',
    surprice: 'Surprice', usave: 'U-Save', wheelsys: 'Wheelsys', xdrive: 'XDrive',
};

const getProviderName = (booking) => {
    const source = String(booking?.provider_source || '').trim().toLowerCase();
    if (!source) return 'Unknown provider';

    return providerNames[source] || source
        .split(/[_-]+/)
        .filter(Boolean)
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
};

const getProviderRef = (booking) => {
    if (booking?.provider_source === 'internal') {
        return booking.booking_reference || `Internal #${booking.booking_number}`;
    }

    return booking?.provider_booking_ref || 'Reference missing';
};

const getGatewayBookingId = (booking) => {
    return booking?.provider_metadata?.gateway_booking_id
        || booking?.provider_metadata?.manual_recovery?.gateway_booking_id
        || null;
};

const getVehicleName = (booking) => {
    const localVehicle = `${booking?.vehicle?.brand || ''} ${booking?.vehicle?.model || ''}`.trim();
    if (localVehicle) return localVehicle;

    return booking?.vehicle_name || booking?.provider_metadata?.vehicle_name || 'Vehicle name missing';
};

const getVehicleMeta = (booking) => {
    if (booking?.vehicle?.color) return booking.vehicle.color;

    const sipp = booking?.provider_metadata?.surprice?.acriss_code
        || booking?.provider_metadata?.recordgo?.acriss_code
        || booking?.provider_metadata?.sbc?.vehicle_id
        || booking?.provider_vehicle_id;

    if (sipp) return `Class ${sipp}`;

    return booking?.provider_source === 'internal' ? 'Internal vehicle' : 'Gateway vehicle';
};

const getPaymentBadgeVariant = (paymentStatus) => {
    switch (paymentStatus) {
        case 'paid': return 'default';
        case 'pending': return 'secondary';
        case 'failed':
        case 'refund_pending': return 'destructive';
        default: return 'outline';
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A';
    const date = new Date(dateStr);
    if (Number.isNaN(date.getTime())) return 'N/A';
    return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`;
};

const formatTripDate = (dateStr) => {
    if (!dateStr) return 'N/A';

    const match = String(dateStr).match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (match) return `${match[3]}/${match[2]}/${match[1]}`;

    return formatDate(dateStr);
};

const formatCurrency = (amount, currency) => {
    const formatted = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount);
    return `${registryCurrencySymbol(currency)}${formatted}`;
};

const getAdminAmounts = (booking) => {
    const amounts = booking.amounts || null;
    if (amounts && amounts.admin_currency) {
        return {
            currency: amounts.admin_currency,
            total: Number(amounts.admin_total_amount || 0),
            paid: Number(amounts.admin_paid_amount || 0),
            pending: Number(amounts.admin_pending_amount || 0),
        };
    }

    return {
        currency: booking.booking_currency || 'USD',
        total: Number(booking.total_amount || 0),
        paid: Number(booking.amount_paid || 0),
        pending: Number(booking.pending_amount || 0),
    };
};
</script>
