<script setup>
const props = defineProps({
    isInternal: { type: Boolean, default: false },
    isRecordGo: { type: Boolean, default: false },
    vehicle: { type: Object, required: true },
    recordGoAutomaticComplements: { type: Array, default: () => [] },
    showProviderNotes: { type: Boolean, default: false },
})

const emit = defineEmits(['update:showProviderNotes'])

import { computed } from 'vue'

const notesVisible = computed({
    get: () => props.showProviderNotes,
    set: (v) => emit('update:showProviderNotes', v),
})

// Helper to get data from the legacy payload (internal vehicles store raw data here)
const legacy = computed(() => props.vehicle?.booking_context?.provider_payload || props.vehicle || {})

const fuelPolicyLabel = computed(() => {
    const fp = legacy.value?.fuel_policy || props.vehicle?.policies?.fuel_policy || props.vehicle?.fuel_policy;
    if (!fp || fp === 'unknown') return null;
    const map = { full_to_full: 'Full-to-Full', same_to_same: 'Same-to-Same', pre_purchase: 'Pre-Purchase', FF: 'Full-to-Full', SL: 'Same Level', FP: 'Free Fuel', PP: 'Prepaid' };
    return map[fp] || fp;
})

const fuelPolicyDescription = computed(() => {
    const fp = legacy.value?.fuel_policy || props.vehicle?.policies?.fuel_policy || props.vehicle?.fuel_policy;
    if (!fp) return '';
    const map = {
        full_to_full: 'Collect the vehicle with a full tank and return it full. If returned with less fuel, a refuelling charge will apply.',
        same_to_same: 'Return the vehicle with the same fuel level as when you picked it up.',
        pre_purchase: 'Fuel is pre-purchased. You can return the vehicle with any fuel level — no refund for unused fuel.',
    };
    return map[fp] || '';
})

const benefits = computed(() => legacy.value?.benefits || props.vehicle?.benefits || null)

const hasCancellationPolicy = computed(() => {
    const cancel = props.vehicle?.policies?.cancellation;
    if (cancel) return cancel.free_cancellation || cancel.available;
    return benefits.value?.cancellation_available_per_day;
})
const cancelBeforeDays = computed(() => {
    return props.vehicle?.policies?.cancellation?.days_before || benefits.value?.cancellation_available_per_day_date || 0;
})
const cancellationFee = computed(() => {
    return props.vehicle?.policies?.cancellation?.fee || benefits.value?.cancellation_fee_per_day || 0;
})

const securityDeposit = computed(() => {
    return props.vehicle?.pricing?.deposit_amount || legacy.value?.security_deposit || props.vehicle?.security_deposit || 0;
})

const depositCurrency = computed(() => {
    return props.vehicle?.pricing?.deposit_currency || props.vehicle?.pricing?.currency || legacy.value?.currency || 'EUR';
})

const dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']
const operatingHours = computed(() => {
    const hours = legacy.value?.operating_hours || props.vehicle?.operating_hours;
    if (!hours || !Array.isArray(hours) || hours.length === 0) return null;
    return hours;
})

const vehicleFeatures = computed(() => {
    const raw = legacy.value?.features || props.vehicle?.features;
    if (!raw) return [];
    if (Array.isArray(raw)) return raw.filter(f => f && typeof f === 'string');
    if (typeof raw === 'string') {
        try { return JSON.parse(raw).filter(f => f && typeof f === 'string'); } catch { return []; }
    }
    return [];
})

const pickupInstructions = computed(() => legacy.value?.pickup_instructions || props.vehicle?.pickup_instructions || null)
const dropoffInstructions = computed(() => legacy.value?.dropoff_instructions || props.vehicle?.dropoff_instructions || null)
const guidelines = computed(() => legacy.value?.guidelines || props.vehicle?.guidelines || null)
const rentalPolicy = computed(() => legacy.value?.rental_policy || props.vehicle?.rental_policy || null)
const termsPolicy = computed(() => legacy.value?.terms_policy || props.vehicle?.terms_policy || null)
const paymentMethods = computed(() => {
    const raw = legacy.value?.payment_method || props.vehicle?.payment_method;
    if (!raw) return [];
    let methods = [];
    if (typeof raw === 'string') {
        try { methods = JSON.parse(raw); } catch { methods = [raw]; }
    } else if (Array.isArray(raw)) {
        methods = raw;
    }
    return methods.filter(m => m && typeof m === 'string').map(m =>
        m.replace(/[_-]/g, ' ').split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()).join(' ')
    );
})

const vendorName = computed(() => {
    return legacy.value?.vendorProfileData?.company_name || legacy.value?.vendor_profile_data?.company_name || legacy.value?.vendor?.profile?.company_name || legacy.value?.vendorProfile?.company_name || null;
})
</script>

<template>
    <section class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden fade-in-up" style="animation-delay:0.55s">
        <button @click="notesVisible = !notesVisible" class="w-full flex items-center justify-between p-6 text-left hover:bg-gray-50/50 transition-colors">
            <h3 class="text-lg font-bold text-[#1e3a5f] flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Rental Policies & Information
            </h3>
            <svg class="w-5 h-5 text-gray-400 transition-transform duration-300" :class="notesVisible ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div v-show="notesVisible" class="px-6 pb-6 space-y-3">
            <template v-if="isInternal">
                <!-- Vehicle Features -->
                <div v-if="vehicleFeatures.length > 0" class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                    <p class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Vehicle Features</p>
                    <div class="flex flex-wrap gap-2">
                        <span v-for="feature in vehicleFeatures" :key="feature" class="inline-flex items-center gap-1.5 px-3 py-1 bg-white border border-gray-200 rounded-full text-xs font-medium text-gray-700">
                            <svg class="w-3 h-3 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            {{ feature }}
                        </span>
                    </div>
                </div>

                <!-- Security Deposit & Payment Methods -->
                <div v-if="securityDeposit > 0" class="bg-orange-50 rounded-xl p-4 border border-orange-100">
                    <p class="text-xs font-bold text-orange-700 uppercase tracking-wider mb-1">Security Deposit</p>
                    <p class="text-sm text-gray-700">A security deposit of <span class="font-semibold">{{ depositCurrency }} {{ Number(securityDeposit).toLocaleString() }}</span> is required at pickup. This will be refunded upon safe return of the vehicle.</p>
                    <div v-if="paymentMethods.length > 0" class="mt-2 flex items-center gap-2 flex-wrap">
                        <span class="text-xs font-medium text-gray-500">Accepted:</span>
                        <span v-for="method in paymentMethods" :key="method" class="inline-flex items-center px-2.5 py-1 bg-white border border-orange-200 rounded-full text-xs font-medium text-gray-700">
                            {{ method }}
                        </span>
                    </div>
                </div>

                <!-- Fuel Policy -->
                <div v-if="fuelPolicyLabel" class="bg-sky-50 rounded-xl p-4 border border-sky-100">
                    <p class="text-xs font-bold text-sky-700 uppercase tracking-wider mb-1">Fuel Policy</p>
                    <p class="text-sm font-semibold text-gray-800">{{ fuelPolicyLabel }}</p>
                    <p v-if="fuelPolicyDescription" class="text-sm text-gray-600 mt-1">{{ fuelPolicyDescription }}</p>
                </div>

                <!-- Cancellation Policy -->
                <div class="rounded-xl p-4 border" :class="hasCancellationPolicy ? 'bg-emerald-50 border-emerald-100' : 'bg-red-50 border-red-100'">
                    <p class="text-xs font-bold uppercase tracking-wider mb-1" :class="hasCancellationPolicy ? 'text-emerald-700' : 'text-red-700'">Cancellation Policy</p>
                    <template v-if="hasCancellationPolicy">
                        <p class="text-sm text-gray-700">
                            <span class="font-semibold text-emerald-700">Free cancellation</span> if cancelled
                            <span class="font-semibold">{{ cancelBeforeDays }}+ days</span> before pickup.
                        </p>
                        <p v-if="cancellationFee > 0" class="text-sm text-gray-700 mt-1">
                            Late cancellation fee: <span class="font-semibold text-amber-700">{{ depositCurrency }} {{ cancellationFee }}</span>
                        </p>
                        <p v-else class="text-sm text-gray-700 mt-1">
                            Late cancellation: <span class="font-semibold text-red-600">No refund</span>
                        </p>
                    </template>
                    <p v-else class="text-sm text-gray-700">
                        <span class="font-semibold text-red-600">Non-refundable.</span> This booking cannot be cancelled once confirmed.
                    </p>
                </div>

                <!-- Pickup Instructions -->
                <div v-if="pickupInstructions" class="bg-teal-50 rounded-xl p-4 border border-teal-100">
                    <p class="text-xs font-bold text-teal-700 uppercase tracking-wider mb-1">Pickup Instructions</p>
                    <p class="text-sm text-gray-700 whitespace-pre-line">{{ pickupInstructions }}</p>
                </div>

                <!-- Dropoff Instructions -->
                <div v-if="dropoffInstructions" class="bg-teal-50 rounded-xl p-4 border border-teal-100">
                    <p class="text-xs font-bold text-teal-700 uppercase tracking-wider mb-1">Return Instructions</p>
                    <p class="text-sm text-gray-700 whitespace-pre-line">{{ dropoffInstructions }}</p>
                </div>

                <!-- Operating Hours -->
                <div v-if="operatingHours" class="bg-indigo-50 rounded-xl p-4 border border-indigo-100">
                    <p class="text-xs font-bold text-indigo-700 uppercase tracking-wider mb-2">Operating Hours</p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <div v-for="h in operatingHours" :key="h.day" class="text-sm">
                            <span class="font-medium text-gray-800">{{ h.day_name || dayNames[h.day] || 'Day ' + h.day }}</span>
                            <span v-if="h.is_open" class="text-gray-600 ml-1">{{ h.open_time }}–{{ h.close_time }}</span>
                            <span v-else class="text-gray-400 ml-1 italic">Closed</span>
                        </div>
                    </div>
                </div>

                <!-- Vendor Info -->
                <div v-if="vendorName" class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                    <p class="text-xs font-bold text-blue-700 uppercase tracking-wider mb-1">Vendor Information</p>
                    <p class="text-sm text-gray-700">{{ vendorName }}</p>
                </div>

                <!-- Guidelines -->
                <div v-if="guidelines" class="bg-amber-50 rounded-xl p-4 border border-amber-100">
                    <p class="text-xs font-bold text-amber-700 uppercase tracking-wider mb-1">Guidelines for Customer</p>
                    <p class="text-sm text-gray-700 whitespace-pre-line">{{ guidelines }}</p>
                </div>

                <!-- Rental Policy shown in checkout modal — not duplicated here -->
            </template>
            <template v-if="isRecordGo && recordGoAutomaticComplements.length > 0">
                <div class="bg-amber-50 rounded-xl p-4 border border-amber-100">
                    <p class="text-xs font-bold text-amber-700 uppercase tracking-wider mb-1">Automatic Charges</p>
                    <p class="text-sm text-gray-700">Some charges apply automatically based on booking conditions. See the Automatic Supplements section above for details.</p>
                </div>
            </template>
        </div>
    </section>
</template>
