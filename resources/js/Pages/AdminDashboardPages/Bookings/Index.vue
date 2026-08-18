<template>
    <AdminDashboardLayout>
        <div class="container mx-auto p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold tracking-tight">Bookings Management</h1>
                <div class="flex items-center gap-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-700">
                        <Calendar class="w-4 h-4 mr-1" />
                        All Bookings
                    </span>
                </div>
            </div>

            <!-- Flash Messages -->
            <div v-if="flash?.success" class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ flash.success }}
            </div>
            <div v-if="flash?.error" class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                {{ flash.error }}
            </div>

            <!-- Enhanced Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6 gap-4">
                <!-- Total Bookings Card -->
                <div class="relative bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-500 bg-opacity-20 rounded-lg">
                            <Calendar class="w-6 h-6 text-blue-600" />
                        </div>
                        <Badge variant="secondary" class="bg-blue-500 text-white">
                            Total
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-blue-900">{{ statusCounts?.total || 0 }}</p>
                        <p class="text-sm text-blue-700 mt-1">Total Bookings</p>
                    </div>
                </div>

                <!-- Pending Bookings Card -->
                <div class="relative bg-gradient-to-br from-yellow-50 to-yellow-100 border border-yellow-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-yellow-500 bg-opacity-20 rounded-lg">
                            <Clock class="w-6 h-6 text-yellow-600" />
                        </div>
                        <Badge variant="secondary" class="bg-yellow-500 text-white">
                            Pending
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-yellow-900">{{ statusCounts?.pending || 0 }}</p>
                        <p class="text-sm text-yellow-700 mt-1">Pending Bookings</p>
                    </div>
                </div>

                <!-- Confirmed Bookings Card -->
                <div class="relative bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-green-500 bg-opacity-20 rounded-lg">
                            <CheckCircle class="w-6 h-6 text-green-600" />
                        </div>
                        <Badge variant="secondary" class="bg-green-500 text-white">
                            Confirmed
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-green-900">{{ statusCounts?.confirmed || 0 }}</p>
                        <p class="text-sm text-green-700 mt-1">Confirmed Bookings</p>
                    </div>
                </div>

                <!-- Completed Bookings Card -->
                <div class="relative bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-500 bg-opacity-20 rounded-lg">
                            <CheckSquare class="w-6 h-6 text-blue-600" />
                        </div>
                        <Badge variant="secondary" class="bg-blue-500 text-white">
                            Completed
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-blue-900">{{ statusCounts?.completed || 0 }}</p>
                        <p class="text-sm text-blue-700 mt-1">Completed Bookings</p>
                    </div>
                </div>

                <!-- Cancelled Bookings Card -->
                <div class="relative bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-red-500 bg-opacity-20 rounded-lg">
                            <XCircle class="w-6 h-6 text-red-600" />
                        </div>
                        <Badge variant="secondary" class="bg-red-500 text-white">
                            Cancelled
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-red-900">{{ statusCounts?.cancelled || 0 }}</p>
                        <p class="text-sm text-red-700 mt-1">Cancelled Bookings</p>
                    </div>
                </div>

                <!-- Needs Attention Card: reservation_failed + rejected + expired.
                     A div like its sibling cards — the admin theme's global button
                     styles break the card layout on a bare <button>. -->
                <div
                    role="button"
                    tabindex="0"
                    data-admin-enhance="off"
                    class="relative bg-gradient-to-br from-amber-50 to-amber-100 border border-amber-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02] cursor-pointer"
                    @click="statusFilter = 'reservation_failed'"
                    @keydown.enter="statusFilter = 'reservation_failed'"
                >
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-amber-500 bg-opacity-20 rounded-lg">
                            <AlertTriangle class="w-6 h-6 text-amber-600" />
                        </div>
                        <Badge variant="secondary" class="bg-amber-500 text-white">
                            Attention
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-amber-900">{{ problemStatusTotal }}</p>
                        <p class="text-sm text-amber-700 mt-1">Failed / Rejected / Expired</p>
                    </div>
                </div>
            </div>

            <!-- Enhanced Search & Filter -->
            <div class="flex flex-col md:flex-row gap-4 items-center justify-center">
                <div class="flex-1 w-full md:w-auto">
                    <div class="relative w-full max-w-md">
                        <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="search"
                            placeholder="Search bookings by number, customer, vehicle, provider ref..."
                            class="pl-10 pr-4 h-12 text-base"
                        />
                    </div>
                </div>
                <div>
                    <Select v-model="statusFilter">
                        <SelectTrigger class="w-40 h-12">
                            <SelectValue placeholder="All Statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Statuses</SelectItem>
                            <SelectItem value="rescue">Rescue Queue</SelectItem>
                            <SelectItem value="pending">Pending</SelectItem>
                            <SelectItem value="confirmed">Confirmed</SelectItem>
                            <SelectItem value="completed">Completed</SelectItem>
                            <SelectItem value="cancelled">Cancelled</SelectItem>
                            <SelectItem value="reservation_failed">Reservation Failed</SelectItem>
                            <SelectItem value="rejected">Rejected</SelectItem>
                            <SelectItem value="expired">Expired</SelectItem>
                            <SelectItem value="refund_pending">Refund Pending</SelectItem>
                            <SelectItem value="provider_pending">Provider Pending</SelectItem>
                            <SelectItem value="needs_correction">Needs Correction</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <div
                v-if="rescueQueueTotal > 0"
                class="mb-4 rounded-xl border border-amber-400/30 bg-gradient-to-r from-amber-500/10 via-transparent to-transparent p-4"
            >
                <div class="flex items-center gap-2">
                    <AlertTriangle class="h-4 w-4 shrink-0 text-amber-400" />
                    <span class="text-sm font-semibold">
                        Booking rescue queue — {{ rescueQueueTotal }} booking{{ rescueQueueTotal === 1 ? '' : 's' }} need attention
                    </span>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button
                        v-for="chip in rescueChips"
                        :key="chip.status"
                        data-admin-enhance="off"
                        class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-semibold transition-[color,background-color,border-color] duration-200"
                        :class="statusFilter === chip.status
                            ? 'border-amber-400 bg-amber-400/20 text-amber-200'
                            : chip.count
                                ? 'border-amber-400/30 bg-amber-400/5 text-amber-300/90 hover:border-amber-400/60 hover:bg-amber-400/10'
                                : 'border-white/10 text-muted-foreground opacity-60 hover:opacity-90'"
                        @click="statusFilter = chip.status"
                    >
                        {{ chip.label }}
                        <span class="rounded-full bg-black/25 px-1.5 py-0.5 font-mono text-[11px]">{{ chip.count }}</span>
                    </button>
                </div>
            </div>

    
            <!-- Enhanced Bookings Table -->
            <div v-if="users.data.length > 0" class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <div class="overflow-x-auto max-w-full">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">ID</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Booking #</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Customer</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Vehicle</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Provider</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Trip</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Commission</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Payment</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Status</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <template v-for="(booking,index) in users.data" :key="booking.id">
                            <TableRow class="hover:bg-muted/25 transition-colors">
                                <TableCell class="whitespace-nowrap px-4 py-3 font-medium">
                                    {{ (users.current_page - 1) * users.per_page + index + 1 }}
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="font-mono font-medium">{{ booking.booking_number }}</div>
                                    <Badge variant="outline" class="mt-1 capitalize">
                                        {{ booking.plan }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="px-4 py-3">
                                    <div class="max-w-[180px]">
                                        <div class="font-medium truncate">{{ booking.customer?.first_name }} {{ booking.customer?.last_name }}</div>
                                        <div class="text-xs text-muted-foreground truncate">{{ booking.customer?.email }}</div>
                                    </div>
                                </TableCell>
                                <TableCell class="px-4 py-3">
                                    <div class="max-w-[180px] font-medium leading-snug line-clamp-2">{{ getVehicleName(booking) }}</div>
                                </TableCell>
                                <TableCell class="px-4 py-3">
                                    <div class="inline-flex max-w-[150px] items-center gap-1.5 rounded-full border border-[#22d3ee]/35 bg-[#22d3ee]/10 px-2.5 py-1 text-xs font-semibold text-[#22d3ee]">
                                        <Building2 class="h-3.5 w-3.5 shrink-0" />
                                        <span class="truncate">{{ getProviderName(booking) }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm font-medium">{{ formatTripDate(booking.trip_from_date || booking.pickup_date) }}</div>
                                    <div class="text-xs text-muted-foreground">→ {{ formatTripDate(booking.trip_to_date || booking.return_date) }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm font-medium">
                                        {{ formatCurrency(getAdminAmounts(booking).total, getAdminAmounts(booking).currency) }}
                                    </div>
                                    <div class="text-green-600 text-xs">
                                        {{ formatCurrency(getAdminAmounts(booking).paid, getAdminAmounts(booking).currency) }} collected
                                    </div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Badge v-if="booking.payments?.length > 0"
                                          :variant="getPaymentBadgeVariant(booking.payments[0].payment_status)">
                                        {{ booking.payments[0].payment_status }}
                                    </Badge>
                                    <Badge v-else variant="outline">
                                        No Payment
                                    </Badge>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Badge :variant="getStatusBadgeBooking(booking.booking_status)" class="capitalize">
                                        {{ formatStatusLabel(booking.booking_status) }}
                                    </Badge>
                                    <div v-if="isProviderPending(booking)" class="mt-1 text-xs font-semibold text-amber-700">
                                        Provider ref missing
                                    </div>
                                    <div v-if="booking.payment_status === 'refund_pending'" class="mt-1 text-xs font-semibold text-red-700">
                                        Refund pending
                                    </div>
                                    <div v-if="needsCorrection(booking)" class="mt-1 text-xs font-semibold text-amber-700">
                                        Needs correction
                                    </div>
                                    <div v-if="booking.provider_metadata?.dispute_opened_at" class="mt-1 text-xs font-semibold text-red-700">
                                        Dispute opened
                                    </div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 text-right">
                                    <div class="flex flex-wrap justify-end gap-2">
                                    <Link :href="route('customer-bookings.show', booking.id)">
                                        <Button size="sm" variant="outline" class="flex items-center gap-1">
                                            <Eye class="w-3 h-3" />
                                            Details
                                        </Button>
                                    </Link>
                                    <Button
                                        v-if="canRetryReservation(booking)"
                                        size="sm"
                                        variant="outline"
                                        :disabled="retryingId === booking.id"
                                        @click="retryReservation(booking)"
                                    >
                                        <RefreshCw class="w-3 h-3 mr-1" :class="retryingId === booking.id ? 'animate-spin' : ''" />
                                        Retry reservation
                                    </Button>
                                    <Button
                                        v-if="canCancelBooking(booking)"
                                        variant="destructive"
                                        size="sm"
                                        @click="openCancelModal(booking)"
                                    >
                                        <XCircle class="w-4 h-4 mr-1" />
                                        Cancel
                                    </Button>
                                    <span v-else class="text-sm text-muted-foreground">--</span>
                                    </div>
                                </TableCell>
                            </TableRow>
                            </template>
                        </TableBody>
                    </Table>
                </div>
                <div class="flex justify-end pt-4 pr-2">
                    <Pagination :current-page="users.current_page" :total-pages="users.last_page"
                        @page-change="handlePageChange" />
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="rounded-xl border bg-card p-12 text-center">
                <div class="flex flex-col items-center space-y-4">
                    <Calendar class="w-16 h-16 text-muted-foreground" />
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold text-foreground">No bookings found</h3>
                        <p class="text-muted-foreground">No bookings match your current search criteria.</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- Cancel Booking Confirmation Dialog -->
        <Dialog :open="showCancelModal" @update:open="showCancelModal = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Cancel Booking</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to cancel booking
                        <span class="font-semibold">#{{ cancelTarget?.booking_number }}</span>?
                        This action will notify the customer, vendor, and attempt to cancel with the provider if applicable.
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-4 py-4">
                    <div>
                        <label class="text-sm font-medium mb-1.5 block">Cancellation Reason</label>
                        <Textarea
                            v-model="cancelReason"
                            placeholder="Enter the reason for cancellation (min 3 characters)..."
                            rows="3"
                        />
                        <p v-if="cancelError" class="text-sm text-red-500 mt-1">{{ cancelError }}</p>
                    </div>
                </div>
                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="closeCancelModal">
                        Go Back
                    </Button>
                    <Button
                        variant="destructive"
                        :disabled="cancelling"
                        @click="submitCancel"
                    >
                        <template v-if="cancelling">Cancelling...</template>
                        <template v-else>Confirm Cancellation</template>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { router, Link } from "@inertiajs/vue3";
import {Table, TableHeader, TableRow, TableHead, TableBody, TableCell} from "@/Components/ui/table";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import { getCurrencySymbol as registryCurrencySymbol } from '@/utils/currencyRegistry';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';
import {
  Calendar,
  Clock,
  CheckCircle,
  CheckSquare,
  XCircle,
  Search,
  Eye,
  Building2,
  AlertTriangle,
  RefreshCw
} from 'lucide-vue-next';
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import Pagination from '@/Components/ReusableComponents/Pagination.vue';

const props = defineProps({
    users: Object,
    statusCounts: Object,
    filters: Object,
    currentStatus: String,
    flash: Object,
});

const search = ref(props.filters.search || '');
const statusFilter = ref(props.currentStatus || 'all');

const rescueQueueTotal = computed(() => {
    const c = props.statusCounts || {};
    return c.rescue_total || 0;
});

const rescueChips = computed(() => {
    const c = props.statusCounts || {};
    return [
        { status: 'provider_pending', label: 'Provider pending', count: c.provider_pending || 0 },
        { status: 'reservation_failed', label: 'Reservation failed', count: c.reservation_failed || 0 },
        { status: 'refund_pending', label: 'Refund pending', count: c.refund_pending || 0 },
        { status: 'needs_correction', label: 'Needs correction', count: c.needs_correction || 0 },
        { status: 'rejected', label: 'Rejected', count: c.rejected || 0 },
    ];
});

// The four status cards deliberately exclude problem states; this card is the
// residual so the arithmetic closes and failures stay visible.
const problemStatusTotal = computed(() => {
    const c = props.statusCounts || {};
    return (c.reservation_failed || 0) + (c.rejected || 0) + (c.expired || 0);
});
// Cancel modal state
const showCancelModal = ref(false);
const cancelTarget = ref(null);
const cancelReason = ref('');
const cancelError = ref('');
const cancelling = ref(false);

const openCancelModal = (booking) => {
    cancelTarget.value = booking;
    cancelReason.value = '';
    cancelError.value = '';
    showCancelModal.value = true;
};

const closeCancelModal = () => {
    showCancelModal.value = false;
    cancelTarget.value = null;
    cancelReason.value = '';
    cancelError.value = '';
};

const submitCancel = () => {
    if (cancelReason.value.trim().length < 3) {
        cancelError.value = 'Cancellation reason must be at least 3 characters.';
        return;
    }
    cancelling.value = true;
    cancelError.value = '';

    router.post(`/customer-bookings/${cancelTarget.value.id}/cancel`, {
        cancellation_reason: cancelReason.value.trim(),
    }, {
        preserveScroll: true,
        onSuccess: () => {
            closeCancelModal();
            cancelling.value = false;
        },
        onError: (errors) => {
            cancelError.value = errors.cancellation_reason || 'An error occurred.';
            cancelling.value = false;
        },
    });
};

// Watch for changes in search and status filter
watch([search, statusFilter], () => {
    handleFilterChange();
});

const handleFilterChange = () => {
    router.get('/customer-bookings', {
        search: search.value,
        status: statusFilter.value
    }, {
        preserveState: true,
        replace: true,
    });
};

const handleSearch = () => {
    handleFilterChange();
};

// One route + a status query param. The old per-status named routes only
// existed for four statuses, so paginating a rescue filter either silently
// dropped the filter (stale status ref) or threw on a missing route name.
const handlePageChange = (page) => {
    router.get('/customer-bookings', {
        page,
        search: search.value,
        status: statusFilter.value,
    }, { preserveState: true, replace: true });
};

// Manual reservation retry for rows stuck without a supplier reservation.
const retryingId = ref(null);

const canRetryReservation = (booking) => {
    return booking?.provider_source
        && booking.provider_source !== 'internal'
        && !booking.provider_booking_ref
        && ['partial', 'paid'].includes(booking.payment_status)
        && ['pending', 'confirmed', 'reservation_failed'].includes(booking.booking_status);
};

const canCancelBooking = (booking) => (
    !['cancelled', 'completed', 'rejected', 'expired'].includes(booking?.booking_status)
);

const retryReservation = (booking) => {
    // Unknown outcome = the supplier may ALREADY hold this reservation. The
    // backend refuses a blind retry; the admin must confirm they checked the
    // supplier portal first.
    const outcomeUnknown = !!(booking.provider_metadata?.reservation_manual_check
        || booking.provider_metadata?.reservation_unknown_at);
    if (outcomeUnknown && !window.confirm(
        'The supplier may ALREADY hold this reservation — its outcome was unknown when the confirmation timed out. '
        + 'Retrying without checking would book a SECOND car.\n\n'
        + 'Only continue if you verified in the supplier portal that no reservation exists. Retry now?'
    )) {
        return;
    }

    retryingId.value = booking.id;
    router.post(`/customer-bookings/${booking.id}/retry-reservation`, { supplier_checked: outcomeUnknown }, {
        preserveScroll: true,
        onFinish: () => { retryingId.value = null; },
    });
};


const getStatusBadgeBooking = (status) => {
    switch (status) {
        case 'completed':
            return 'default';
        case 'pending':
            return 'secondary';
        case 'confirmed':
            return 'default';
        case 'cancelled':
            return 'destructive';
        case 'reservation_failed':
        case 'rejected':
            return 'destructive';
        case 'expired':
            return 'outline';
        default:
            return 'default';
    }
};

const formatStatusLabel = (status) => {
    return String(status || '').replace(/_/g, ' ');
};

const isProviderPending = (booking) => {
    return booking?.provider_source
        && booking.provider_source !== 'internal'
        && !booking.provider_booking_ref
        && ['pending', 'confirmed'].includes(booking.booking_status);
};

const needsCorrection = (booking) => !!booking?.provider_metadata?.needs_correction;


const providerNames = {
    adobe_car: 'Adobe Car',
    click2rent: 'Click2Rent',
    easirent: 'Easirent',
    emr: 'EMR',
    favrica: 'Favrica',
    green_motion: 'Green Motion',
    greenmotion: 'Green Motion',
    internal: 'Internal Fleet',
    locauto_rent: 'Locauto',
    ok_mobility: 'OK Mobility',
    recordgo: 'Record Go',
    renteon: 'Renteon',
    sicily_by_car: 'Sicily by Car',
    surprice: 'Surprice',
    usave: 'U-Save',
    wheelsys: 'Wheelsys',
    xdrive: 'XDrive',
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
        case 'paid':
            return 'default';
        case 'pending':
            return 'secondary';
        case 'failed':
            return 'destructive';
        case 'refund_pending':
            return 'destructive';
        case 'refunded':
            return 'outline';
        default:
            return 'outline';
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

    const value = String(dateStr);
    const match = value.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (match) {
        return `${match[3]}/${match[2]}/${match[1]}`;
    }

    return formatDate(value);
};

// Currency symbol function
const getCurrencySymbol = (currency) => registryCurrencySymbol(currency);

// Format number function
const formatNumber = (number) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(number);
};

// Format currency with symbol
const formatCurrency = (amount, currency) => {
    return `${getCurrencySymbol(currency)}${formatNumber(amount)}`;
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

// Currency badge class
const getCurrencyBadgeClass = (currency) => {
    const classes = {
        'USD': 'bg-green-100 text-green-800',
        'EUR': 'bg-blue-100 text-blue-800',
        'GBP': 'bg-purple-100 text-purple-800',
        'JPY': 'bg-red-100 text-red-800',
        'AUD': 'bg-yellow-100 text-yellow-800',
        'CAD': 'bg-orange-100 text-orange-800',
        'CHF': 'bg-pink-100 text-pink-800',
        'HKD': 'bg-teal-100 text-teal-800',
        'SGD': 'bg-indigo-100 text-indigo-800',
        'SEK': 'bg-gray-100 text-gray-800',
        'KRW': 'bg-red-200 text-red-900',
        'NOK': 'bg-blue-200 text-blue-900',
        'NZD': 'bg-green-200 text-green-900',
        'INR': 'bg-orange-200 text-orange-900',
        'MXN': 'bg-yellow-200 text-yellow-900',
        'ZAR': 'bg-purple-200 text-purple-900',
        'AED': 'bg-cyan-100 text-cyan-900'
    };
    return classes[currency] || 'bg-gray-100 text-gray-800';
};

// Flash message handling
onMounted(() => {
    if (props.flash?.success) {
        setTimeout(() => {
            router.clearFlashMessages();
        }, 3000);
    }
});
</script>
