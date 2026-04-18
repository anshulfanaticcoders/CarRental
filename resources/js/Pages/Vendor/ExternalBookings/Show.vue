<template>
    <MyProfileLayout>
        <div class="space-y-5">
            <!-- Back + Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <button
                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-[var(--gray-200)] bg-white hover:bg-[var(--gray-50)] transition-colors"
                        @click="goBack"
                    >
                        <ArrowLeft class="w-4 h-4 text-[var(--gray-600)]" />
                    </button>
                    <div>
                        <h1 class="text-lg font-bold text-[var(--gray-900)] flex items-center gap-2.5">
                            {{ booking.booking_number }}
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize"
                                :class="statusBadgeClass(booking.status)"
                            >
                                <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="statusDotClass(booking.status)"></span>
                                {{ booking.status }}
                            </span>
                        </h1>
                        <p class="text-xs text-[var(--gray-400)] mt-0.5">Booked on {{ formatDate(booking.created_at) }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        v-if="booking.status === 'pending'"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors"
                        @click="updateStatus('confirmed')"
                        :disabled="isLoading"
                    >
                        <CheckCircle class="w-4 h-4" />
                        Confirm Booking
                    </button>
                    <button
                        v-if="booking.status === 'confirmed'"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors"
                        @click="updateStatus('completed')"
                        :disabled="isLoading"
                    >
                        <CheckCircle class="w-4 h-4" />
                        Mark Complete
                    </button>
                </div>
            </div>

            <!-- Main Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                <!-- LEFT: Booking Info (2 cols) -->
                <div class="lg:col-span-2 space-y-5">

                    <!-- Trip Details Card -->
                    <div class="rounded-xl border border-[var(--gray-200)] bg-white shadow-sm overflow-hidden">
                        <div class="px-5 py-3.5 border-b border-[var(--gray-100)] bg-[var(--gray-50)]/60">
                            <h2 class="text-sm font-semibold text-[var(--gray-700)] flex items-center gap-2">
                                <Car class="w-4 h-4 text-[var(--primary-600)]" />
                                Trip Details
                            </h2>
                        </div>
                        <div class="p-5">
                            <!-- Vehicle -->
                            <div class="flex items-center gap-3 mb-5">
                                <div v-if="booking.vehicle_image" class="w-16 h-12 rounded-lg overflow-hidden bg-[var(--gray-100)] shrink-0">
                                    <img :src="booking.vehicle_image" :alt="booking.vehicle_name" class="w-full h-full object-cover" />
                                </div>
                                <div v-else class="w-10 h-10 rounded-lg bg-[var(--primary-50)] flex items-center justify-center shrink-0">
                                    <Car class="w-5 h-5 text-[var(--primary-600)]" />
                                </div>
                                <div>
                                    <p class="font-semibold text-[var(--gray-900)]">{{ booking.vehicle_name || (booking.vehicle?.brand + ' ' + booking.vehicle?.model) }}</p>
                                    <p class="text-xs text-[var(--gray-500)]">{{ booking.total_days }} day{{ booking.total_days !== 1 ? 's' : '' }}</p>
                                </div>
                            </div>

                            <!-- Pickup / Return Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="rounded-lg border border-[var(--gray-200)] p-4">
                                    <div class="flex items-center gap-2 mb-2.5">
                                        <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                        <span class="text-[11px] font-semibold text-[var(--gray-400)] uppercase tracking-wider">Pickup</span>
                                    </div>
                                    <p class="text-sm font-semibold text-[var(--gray-800)]">{{ formatDate(booking.pickup_date) }}</p>
                                    <p v-if="booking.pickup_time" class="text-xs text-[var(--gray-500)] mt-0.5">{{ booking.pickup_time }}</p>
                                </div>
                                <div class="rounded-lg border border-[var(--gray-200)] p-4">
                                    <div class="flex items-center gap-2 mb-2.5">
                                        <div class="w-2 h-2 rounded-full bg-red-500"></div>
                                        <span class="text-[11px] font-semibold text-[var(--gray-400)] uppercase tracking-wider">Return</span>
                                    </div>
                                    <p class="text-sm font-semibold text-[var(--gray-800)]">{{ formatDate(booking.return_date) }}</p>
                                    <p v-if="booking.return_time" class="text-xs text-[var(--gray-500)] mt-0.5">{{ booking.return_time }}</p>
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
                        </div>
                    </div>

                    <!-- Driver Details Card -->
                    <div class="rounded-xl border border-[var(--gray-200)] bg-white shadow-sm overflow-hidden">
                        <div class="px-5 py-3.5 border-b border-[var(--gray-100)] bg-[var(--gray-50)]/60">
                            <h2 class="text-sm font-semibold text-[var(--gray-700)] flex items-center gap-2">
                                <UserIcon class="w-4 h-4 text-[var(--primary-600)]" />
                                Driver Details
                            </h2>
                        </div>
                        <div class="p-5">
                            <div class="flex items-start gap-4">
                                <span class="inline-flex items-center justify-center w-11 h-11 rounded-full bg-[var(--primary-100)] text-[var(--primary-700)] text-sm font-bold shrink-0">
                                    {{ booking.driver_first_name?.[0] }}{{ booking.driver_last_name?.[0] }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-[var(--gray-900)]">
                                        {{ booking.driver_first_name }} {{ booking.driver_last_name }}
                                    </p>
                                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2">
                                        <div class="flex items-center gap-2 text-sm text-[var(--gray-600)]">
                                            <Mail class="w-3.5 h-3.5 text-[var(--gray-400)] shrink-0" />
                                            <span class="truncate">{{ booking.driver_email || 'N/A' }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-sm text-[var(--gray-600)]">
                                            <Phone class="w-3.5 h-3.5 text-[var(--gray-400)] shrink-0" />
                                            <span>{{ booking.driver_phone || 'N/A' }}</span>
                                        </div>
                                        <div v-if="booking.driver_age" class="flex items-center gap-2 text-sm text-[var(--gray-600)]">
                                            <UserIcon class="w-3.5 h-3.5 text-[var(--gray-400)] shrink-0" />
                                            <span>Age: {{ booking.driver_age }}</span>
                                        </div>
                                        <div v-if="booking.driver_license_number" class="flex items-center gap-2 text-sm text-[var(--gray-600)]">
                                            <FileText class="w-3.5 h-3.5 text-[var(--gray-400)] shrink-0" />
                                            <span>License: {{ booking.driver_license_number }}</span>
                                        </div>
                                        <div v-if="booking.driver_license_country" class="flex items-center gap-2 text-sm text-[var(--gray-600)]">
                                            <Globe class="w-3.5 h-3.5 text-[var(--gray-400)] shrink-0" />
                                            <span>License Country: {{ booking.driver_license_country }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- API Consumer Card -->
                    <div v-if="booking.consumer" class="rounded-xl border border-[var(--gray-200)] bg-white shadow-sm overflow-hidden">
                        <div class="px-5 py-3.5 border-b border-[var(--gray-100)] bg-[var(--gray-50)]/60">
                            <h2 class="text-sm font-semibold text-[var(--gray-700)] flex items-center gap-2">
                                <Globe class="w-4 h-4 text-[var(--primary-600)]" />
                                API Consumer
                            </h2>
                        </div>
                        <div class="p-5">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                                    <Globe class="w-5 h-5 text-blue-600" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-[var(--gray-900)]">{{ booking.consumer.name }}</p>
                                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2">
                                        <div v-if="booking.consumer.contact_name" class="flex items-center gap-2 text-sm text-[var(--gray-600)]">
                                            <UserIcon class="w-3.5 h-3.5 text-[var(--gray-400)] shrink-0" />
                                            <span>{{ booking.consumer.contact_name }}</span>
                                        </div>
                                        <div v-if="booking.consumer.contact_email" class="flex items-center gap-2 text-sm text-[var(--gray-600)]">
                                            <Mail class="w-3.5 h-3.5 text-[var(--gray-400)] shrink-0" />
                                            <span class="truncate">{{ booking.consumer.contact_email }}</span>
                                        </div>
                                        <div v-if="booking.consumer.contact_phone" class="flex items-center gap-2 text-sm text-[var(--gray-600)]">
                                            <Phone class="w-3.5 h-3.5 text-[var(--gray-400)] shrink-0" />
                                            <span>{{ booking.consumer.contact_phone }}</span>
                                        </div>
                                        <div v-if="booking.consumer.company_url" class="flex items-center gap-2 text-sm text-[var(--gray-600)]">
                                            <ExternalLink class="w-3.5 h-3.5 text-[var(--gray-400)] shrink-0" />
                                            <a :href="booking.consumer.company_url" target="_blank" rel="noopener" class="text-[var(--primary-600)] hover:underline truncate">
                                                {{ booking.consumer.company_url }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rental Policies Card -->
                    <div class="rounded-xl border border-[var(--gray-200)] bg-white shadow-sm overflow-hidden">
                        <div class="px-5 py-3.5 border-b border-[var(--gray-100)] bg-[var(--gray-50)]/60">
                            <h2 class="text-sm font-semibold text-[var(--gray-700)] flex items-center gap-2">
                                <Shield class="w-4 h-4 text-[var(--primary-600)]" />
                                Rental Policies
                            </h2>
                        </div>
                        <div class="p-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="rounded-lg border border-[var(--gray-200)] p-4">
                                    <p class="text-[11px] font-semibold text-[var(--gray-400)] uppercase tracking-wider mb-1">Security Deposit</p>
                                    <p class="text-sm font-semibold text-[var(--gray-900)]">
                                        {{ booking.vehicle?.security_deposit ? currSym + formatNumber(booking.vehicle.security_deposit) : 'None' }}
                                    </p>
                                </div>
                                <div class="rounded-lg border border-[var(--gray-200)] p-4">
                                    <p class="text-[11px] font-semibold text-[var(--gray-400)] uppercase tracking-wider mb-1">Fuel Policy</p>
                                    <p class="text-sm font-semibold text-[var(--gray-900)] capitalize">
                                        {{ formatFuelPolicy(booking.vehicle?.fuel_policy) }}
                                    </p>
                                </div>
                                <div class="rounded-lg border border-[var(--gray-200)] p-4">
                                    <p class="text-[11px] font-semibold text-[var(--gray-400)] uppercase tracking-wider mb-1">Mileage</p>
                                    <p class="text-sm font-semibold text-[var(--gray-900)]">
                                        <template v-if="booking.vehicle?.limited_km && parseFloat(booking.vehicle.mileage) > 0">
                                            {{ booking.vehicle.mileage }} km/day
                                            <span v-if="booking.vehicle.price_per_km" class="text-xs font-normal text-[var(--gray-500)]">
                                                ({{ currSym }}{{ formatNumber(booking.vehicle.price_per_km) }}/extra km)
                                            </span>
                                        </template>
                                        <template v-else>Unlimited</template>
                                    </p>
                                </div>
                                <div class="rounded-lg border border-[var(--gray-200)] p-4">
                                    <p class="text-[11px] font-semibold text-[var(--gray-400)] uppercase tracking-wider mb-1">Insurance Plan</p>
                                    <p class="text-sm font-semibold text-[var(--gray-900)]">
                                        {{ booking.insurance_id ? 'Selected (ID: ' + booking.insurance_id + ')' : 'No plan selected' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Info -->
                    <div v-if="booking.flight_number || booking.special_requests" class="rounded-xl border border-[var(--gray-200)] bg-white shadow-sm overflow-hidden">
                        <div class="px-5 py-3.5 border-b border-[var(--gray-100)] bg-[var(--gray-50)]/60">
                            <h2 class="text-sm font-semibold text-[var(--gray-700)] flex items-center gap-2">
                                <Info class="w-4 h-4 text-[var(--primary-600)]" />
                                Additional Information
                            </h2>
                        </div>
                        <div class="p-5 space-y-3">
                            <div v-if="booking.flight_number" class="flex items-start gap-3">
                                <Plane class="w-4 h-4 text-[var(--gray-400)] shrink-0 mt-0.5" />
                                <div>
                                    <p class="text-xs font-semibold text-[var(--gray-400)] uppercase tracking-wider mb-0.5">Flight Number</p>
                                    <p class="text-sm text-[var(--gray-700)]">{{ booking.flight_number }}</p>
                                </div>
                            </div>
                            <div v-if="booking.special_requests" class="flex items-start gap-3">
                                <Info class="w-4 h-4 text-[var(--gray-400)] shrink-0 mt-0.5" />
                                <div>
                                    <p class="text-xs font-semibold text-[var(--gray-400)] uppercase tracking-wider mb-0.5">Special Requests</p>
                                    <p class="text-sm text-[var(--gray-700)] whitespace-pre-line">{{ booking.special_requests }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cancellation Reason -->
                    <div v-if="booking.status === 'cancelled' && booking.cancellation_reason" class="rounded-xl border border-red-200 bg-red-50 p-5">
                        <h3 class="text-xs font-semibold text-red-500 uppercase tracking-wider mb-1.5">Cancellation Reason</h3>
                        <p class="text-sm text-red-700">{{ booking.cancellation_reason }}</p>
                    </div>
                </div>

                <!-- RIGHT: Pricing Sidebar (1 col) -->
                <div class="space-y-5">

                    <!-- Price Breakdown Card -->
                    <div class="rounded-xl border border-[var(--gray-200)] bg-white shadow-sm overflow-hidden">
                        <div class="px-5 py-3.5 border-b border-[var(--gray-100)] bg-[var(--gray-50)]/60">
                            <h2 class="text-sm font-semibold text-[var(--gray-700)] flex items-center gap-2">
                                <Receipt class="w-4 h-4 text-[var(--primary-600)]" />
                                Price Breakdown
                            </h2>
                        </div>
                        <div class="p-5 space-y-3">
                            <!-- Daily rate -->
                            <div class="flex justify-between text-sm">
                                <span class="text-[var(--gray-600)]">Daily Rate</span>
                                <span class="font-medium text-[var(--gray-800)]">{{ currSym }}{{ formatNumber(booking.daily_rate) }}</span>
                            </div>

                            <!-- Base price -->
                            <div class="flex justify-between text-sm">
                                <span class="text-[var(--gray-600)]">Base Price ({{ booking.total_days }}d)</span>
                                <span class="font-medium text-[var(--gray-800)]">{{ currSym }}{{ formatNumber(booking.base_price) }}</span>
                            </div>

                            <!-- Extras -->
                            <template v-if="booking.extras?.length">
                                <div v-for="extra in booking.extras" :key="extra.id" class="flex justify-between text-sm">
                                    <span class="text-[var(--gray-600)]">
                                        {{ extra.extra_name }}
                                        <span v-if="extra.quantity > 1" class="text-[var(--gray-400)]">&times;{{ extra.quantity }}</span>
                                    </span>
                                    <span class="font-medium text-[var(--gray-800)]">{{ currSym }}{{ formatNumber(extra.total_price) }}</span>
                                </div>
                            </template>
                            <div v-else-if="parseFloat(booking.extras_total) > 0" class="flex justify-between text-sm">
                                <span class="text-[var(--gray-600)]">Extras / Add-ons</span>
                                <span class="font-medium text-[var(--gray-800)]">{{ currSym }}{{ formatNumber(booking.extras_total) }}</span>
                            </div>

                            <!-- Divider -->
                            <div class="border-t border-dashed border-[var(--gray-200)] my-1"></div>

                            <!-- Total -->
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-bold text-[var(--gray-900)]">Total Amount</span>
                                <span class="text-lg font-bold text-[var(--primary-800)]">
                                    {{ currSym }}{{ formatNumber(booking.total_amount) }}
                                </span>
                            </div>

                            <p class="text-[11px] text-[var(--gray-400)] text-right">
                                {{ booking.currency }}
                            </p>
                        </div>
                    </div>

                    <!-- Status Actions Card -->
                    <div class="rounded-xl border border-[var(--gray-200)] bg-white shadow-sm overflow-hidden">
                        <div class="px-5 py-3.5 border-b border-[var(--gray-100)] bg-[var(--gray-50)]/60">
                            <h2 class="text-sm font-semibold text-[var(--gray-700)]">Booking Status</h2>
                        </div>
                        <div class="p-5 space-y-3">
                            <div class="flex items-center gap-2">
                                <span
                                    class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold capitalize"
                                    :class="statusBadgeClass(booking.status)"
                                >
                                    <span class="w-2 h-2 rounded-full mr-2" :class="statusDotClass(booking.status)"></span>
                                    {{ booking.status }}
                                </span>
                            </div>
                            <button
                                v-if="booking.status === 'pending'"
                                class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors"
                                @click="updateStatus('confirmed')"
                                :disabled="isLoading"
                            >
                                <CheckCircle class="w-4 h-4" />
                                Confirm Booking
                            </button>
                            <button
                                v-if="booking.status === 'confirmed'"
                                class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors"
                                @click="updateStatus('completed')"
                                :disabled="isLoading"
                            >
                                <CheckCircle class="w-4 h-4" />
                                Mark as Completed
                            </button>
                            <p v-if="booking.status === 'completed'" class="text-xs text-emerald-600 text-center font-medium">This booking has been completed.</p>
                            <p v-if="booking.status === 'cancelled'" class="text-xs text-red-500 text-center font-medium">This booking was cancelled.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MyProfileLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import BookingLocationBlock from '@/Components/Booking/BookingLocationBlock.vue';
import { router, usePage } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import {
    ArrowLeft, Car, MapPin, Mail, Phone, Plane,
    User as UserIcon, FileText, Receipt, Globe,
    ExternalLink, Info, CheckCircle, Shield,
} from 'lucide-vue-next';

const toast = useToast();

const props = defineProps({
    booking: { type: Object, required: true },
});

const isLoading = ref(false);

// --- Currency helpers ---
const getCurrencySymbol = (currency) => {
    const symbols = {
        'USD': '$', 'EUR': '€', 'GBP': '£', 'JPY': '¥',
        'AUD': 'A$', 'CAD': 'C$', 'CHF': 'Fr', 'HKD': 'HK$',
        'SGD': 'S$', 'SEK': 'kr', 'KRW': '₩', 'NOK': 'kr',
        'NZD': 'NZ$', 'INR': '₹', 'MXN': 'Mex$', 'ZAR': 'R',
        'AED': 'AED', 'MAD': 'MAD',
    };
    return symbols[currency] || currency || '$';
};

const currSym = computed(() => getCurrencySymbol(props.booking.currency));

const formatNumber = (number) => {
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(number);
};

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A';
    return new Date(dateStr).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatFuelPolicy = (policy) => {
    const labels = { 'full_to_full': 'Full to Full', 'full_to_empty': 'Full to Empty', 'same_to_same': 'Same to Same', 'free_tank': 'Free Tank' };
    return labels[policy] || policy || 'N/A';
};

// --- Status ---
const statusBadgeClass = (status) => ({
    confirmed: 'bg-blue-50 text-blue-700',
    completed: 'bg-emerald-50 text-emerald-700',
    pending: 'bg-amber-50 text-amber-700',
    cancelled: 'bg-red-50 text-red-600',
}[status] || 'bg-gray-50 text-gray-600');

const statusDotClass = (status) => ({
    confirmed: 'bg-blue-500',
    completed: 'bg-emerald-500',
    pending: 'bg-amber-500',
    cancelled: 'bg-red-500',
}[status] || 'bg-gray-400');

// --- Actions ---
const goBack = () => {
    router.get(route('vendor.external-bookings.index', { locale: usePage().props.locale }));
};

const updateStatus = (newStatus) => {
    if (!confirm(`Are you sure you want to mark this booking as "${newStatus}"?`)) return;

    isLoading.value = true;
    router.patch(
        route('vendor.external-bookings.update-status', { locale: usePage().props.locale, apiBooking: props.booking.id }),
        { status: newStatus },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(`Booking marked as ${newStatus}!`, { position: 'top-right', timeout: 3000 });
            },
            onError: () => {
                toast.error('Failed to update booking status.', { position: 'top-right', timeout: 3000 });
            },
            onFinish: () => {
                isLoading.value = false;
            },
        },
    );
};
</script>
