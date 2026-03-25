<script setup>
const props = defineProps({
    vehicleImage: String,
    displayVehicleName: String,
    providerBadge: Object,
    vehicleSpecs: Object,
    pickupDate: String,
    pickupTime: String,
    dropoffDate: String,
    dropoffTime: String,
    pickupLocation: String,
    dropoffLocation: String,
    locationName: String,
    currentPackageLabel: String,
    isLocautoRent: Boolean,
    isOkMobility: Boolean,
    isAdobeCars: Boolean,
    locautoBaseTotal: Number,
    okMobilityBaseTotal: Number,
    currentProduct: Object,
    adobeMandatoryProtection: Number,
    getSelectedExtrasDetails: Array,
    formatRentalPrice: Function,
    formatPrice: Function,
    ratesReady: Boolean,
    grandTotal: [String, Number],
    payableAmount: [String, Number],
    pendingAmount: [String, Number],
    effectivePaymentPercentage: Number,
    availableDepositTypes: Array,
    selectedDepositType: String,
});

const emit = defineEmits(['show-details-modal', 'proceed-to-checkout', 'back']);
</script>

<template>
    <div class="sticky-summary space-y-4">
        <!-- ═══ BOOKING SUMMARY CARD ═══ -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-[#1e3a5f] to-[#2d5a8f] p-4 flex items-center justify-between gap-3">
                <h3 class="text-white font-bold text-base">Booking Summary</h3>
                <span v-if="providerBadge" class="inline-flex items-center gap-1.5 text-[11px] font-bold px-3 py-1 rounded-full bg-white/20 text-white border border-white/20 backdrop-blur-sm tracking-wide">
                    <span class="w-1.5 h-1.5 rounded-full bg-white/70 flex-shrink-0"></span>
                    {{ providerBadge.label }}
                </span>
            </div>

            <div class="p-5 space-y-5">
                <!-- Vehicle mini card -->
                <div class="flex items-center gap-3">
                    <div class="w-20 h-14 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center flex-shrink-0 overflow-hidden">
                        <img v-if="vehicleImage" :src="vehicleImage" alt="" class="w-full h-full object-contain p-1" />
                        <svg v-else class="w-8 h-8 text-gray-300" viewBox="0 0 24 24" fill="currentColor"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-gray-900 truncate">{{ displayVehicleName }}</p>
                    </div>
                </div>

                <!-- Trip timeline -->
                <div class="space-y-3">
                    <div class="flex items-start gap-3">
                        <div class="w-2 h-2 rounded-full bg-emerald-500 mt-1.5 flex-shrink-0 ring-4 ring-emerald-50"></div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ pickupDate }} &middot; {{ pickupTime }}</p>
                            <p class="text-xs text-gray-500">{{ pickupLocation || locationName }}</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-2 h-2 rounded-full bg-rose-500 mt-1.5 flex-shrink-0 ring-4 ring-rose-50"></div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ dropoffDate }} &middot; {{ dropoffTime }}</p>
                            <p class="text-xs text-gray-500">{{ dropoffLocation || pickupLocation || locationName }}</p>
                        </div>
                    </div>
                </div>

                <!-- Specs chips -->
                <div class="flex flex-wrap gap-1.5">
                    <span v-if="vehicleSpecs.passengers" class="text-xs font-medium text-gray-600 bg-gray-50 px-2.5 py-1 rounded-md border border-gray-100">{{ vehicleSpecs.passengers }} Seats</span>
                    <span v-if="vehicleSpecs.transmission" class="text-xs font-medium text-gray-600 bg-gray-50 px-2.5 py-1 rounded-md border border-gray-100">{{ vehicleSpecs.transmission }}</span>
                    <span v-if="vehicleSpecs.airConditioning" class="text-xs font-medium text-gray-600 bg-gray-50 px-2.5 py-1 rounded-md border border-gray-100">AC</span>
                    <span v-if="vehicleSpecs.doors" class="text-xs font-medium text-gray-600 bg-gray-50 px-2.5 py-1 rounded-md border border-gray-100">{{ vehicleSpecs.doors }}</span>
                </div>

                <!-- Divider -->
                <div class="border-t border-gray-100"></div>

                <!-- Price breakdown -->
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Car rental ({{ currentPackageLabel }})</span>
                        <span class="text-base font-semibold text-gray-900" v-if="ratesReady">{{ formatRentalPrice(isLocautoRent ? locautoBaseTotal : (isOkMobility ? (currentProduct?.total || okMobilityBaseTotal) : (currentProduct?.total || 0))) }}</span>
                        <span class="price-skeleton price-skeleton-sm" v-else></span>
                    </div>
                    <div v-if="isAdobeCars && adobeMandatoryProtection > 0" class="flex items-center justify-between text-amber-600">
                        <span class="text-sm">Mandatory Liability (PLI)</span>
                        <span class="text-base font-semibold" v-if="ratesReady">+{{ formatRentalPrice(adobeMandatoryProtection) }}</span>
                        <span class="price-skeleton price-skeleton-sm" v-else></span>
                    </div>
                    <div v-for="item in getSelectedExtrasDetails" :key="item.id" class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ item.name }}<span v-if="item.qty > 1"> &times; {{ item.qty }}</span></span>
                        <span class="text-base font-semibold" :class="item.isFree ? 'text-emerald-600' : 'text-gray-900'">
                            <span v-if="item.isFree">Free</span>
                            <template v-else>
                                <span v-if="ratesReady">+{{ formatRentalPrice(item.total) }}</span>
                                <span class="price-skeleton price-skeleton-sm" v-else></span>
                            </template>
                        </span>
                    </div>
                </div>

                <!-- Dashed divider -->
                <div class="border-t border-dashed border-gray-200"></div>

                <!-- Grand total -->
                <div class="flex items-center justify-between">
                    <span class="text-lg font-bold text-gray-900">Grand Total</span>
                    <span class="text-2xl font-extrabold text-[#1e3a5f]" v-if="ratesReady">{{ formatPrice(grandTotal) }}</span>
                    <span class="price-skeleton price-skeleton-lg" v-else></span>
                </div>

                <!-- Payment split -->
                <div v-if="effectivePaymentPercentage > 0" class="bg-emerald-50 rounded-xl p-4 border border-emerald-100 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-emerald-800">Pay Now ({{ effectivePaymentPercentage }}%)</span>
                        <span class="text-base font-bold text-emerald-700" v-if="ratesReady">{{ formatPrice(payableAmount) }}</span>
                        <span class="price-skeleton price-skeleton-sm" v-else></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-semibold text-gray-600">Pay On Arrival</span>
                        <span class="text-base font-semibold text-gray-700" v-if="ratesReady">{{ formatPrice(pendingAmount) }}</span>
                        <span class="price-skeleton price-skeleton-sm" v-else></span>
                    </div>
                </div>

                <!-- Marquee -->
                <div v-if="effectivePaymentPercentage > 0" class="overflow-hidden rounded-lg bg-[#1e3a5f]/5 py-1.5">
                    <div class="marquee-track flex whitespace-nowrap gap-8 text-[10px] font-semibold text-[#1e3a5f]/70">
                        <span>Secure Checkout &bull; Best Price Guarantee &bull; Free Cancellation &bull; 24/7 Support &bull;</span>
                        <span>Secure Checkout &bull; Best Price Guarantee &bull; Free Cancellation &bull; 24/7 Support &bull;</span>
                    </div>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="p-5 pt-0 space-y-3">
                <!-- View Details -->
                <button @click="emit('show-details-modal')"
                    class="w-full text-sm py-2.5 rounded-xl border border-gray-200 font-semibold text-gray-600 hover:bg-gray-50 transition-colors flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    View Booking Details
                </button>
                <!-- Proceed -->
                <button @click="emit('proceed-to-checkout')"
                    class="btn-cta w-full py-3.5 rounded-xl bg-gradient-to-r from-[#1e3a5f] to-[#2d5a8f] text-white font-bold text-sm shadow-lg shadow-[#1e3a5f]/20 hover:shadow-xl hover:shadow-[#1e3a5f]/30 hover:-translate-y-0.5 transition-all duration-300 active:scale-[0.98]"
                    :disabled="!ratesReady || (availableDepositTypes.length > 1 && !selectedDepositType)" :class="{ 'opacity-60 cursor-not-allowed': !ratesReady }">
                    Proceed to Booking
                </button>
                <!-- Back -->
                <button @click="emit('back')"
                    class="w-full py-3 rounded-xl border border-gray-200 text-gray-600 font-semibold text-sm hover:bg-gray-50 hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back to Results
                </button>
            </div>
        </div>

        <!-- ═══ TRUST INDICATORS ═══ -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <div class="grid grid-cols-2 gap-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <span class="text-[11px] font-semibold text-gray-600 leading-tight">Secure<br/>Checkout</span>
                </div>
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <span class="text-[11px] font-semibold text-gray-600 leading-tight">Best Price<br/>Guarantee</span>
                </div>
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <span class="text-[11px] font-semibold text-gray-600 leading-tight">Visa, MC<br/>Amex</span>
                </div>
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-[11px] font-semibold text-gray-600 leading-tight">24/7<br/>Support</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* ── Sticky Summary ── */
.sticky-summary {
    position: sticky;
    top: 5rem;
    transition: all 0.3s ease;
}
@media (max-width: 1024px) {
    .sticky-summary {
        position: relative;
    }
}

/* ── Marquee ── */
@keyframes marquee {
    0%   { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
.marquee-track {
    animation: marquee 22s linear infinite;
}

/* ── CTA shimmer ── */
@keyframes shimmer {
    0%   { left: -100%; }
    100% { left: 200%; }
}
.btn-cta {
    position: relative;
    overflow: hidden;
}
.btn-cta::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 50%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
    animation: shimmer 3s ease-in-out infinite;
}

/* ── Price skeleton ── */
.price-skeleton {
    display: inline-block;
    height: 16px;
    border-radius: 999px;
    background: linear-gradient(90deg, #f1f5f9 0%, #e2e8f0 50%, #f1f5f9 100%);
    background-size: 200% 100%;
    animation: shimmer-bg 1.4s ease-in-out infinite;
}
.price-skeleton-sm { width: 90px; }
.price-skeleton-lg { width: 160px; height: 26px; }

@keyframes shimmer-bg {
    0%   { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
</style>
