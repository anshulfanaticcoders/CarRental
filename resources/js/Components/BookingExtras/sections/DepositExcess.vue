<script setup>
const props = defineProps({
    vehicle: { type: Object, required: true },
    currentProduct: { type: Object, default: null },
    formatPrice: { type: Function, required: true },
    formatPaymentMethod: { type: Function, required: true },
    selectedDepositType: { type: String, default: '' },
    availableDepositTypes: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:selectedDepositType'])

const depositType = computed({
    get: () => props.selectedDepositType,
    set: (v) => emit('update:selectedDepositType', v),
})

import { computed } from 'vue'

const depositAmount = computed(() => props.currentProduct?.deposit ?? props.vehicle?.security_deposit ?? props.vehicle?.benefits?.deposit_amount ?? null)
const depositCurrency = computed(() => props.currentProduct?.deposit_currency ?? props.vehicle?.benefits?.deposit_currency ?? null)
const excessAmount = computed(() => props.currentProduct?.excess ?? props.vehicle?.benefits?.excess_amount ?? null)
const excessTheftAmount = computed(() => props.currentProduct?.excess_theft_amount ?? props.vehicle?.benefits?.excess_theft_amount ?? null)
</script>

<template>
    <section class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up" style="animation-delay:0.3s">
        <h3 class="text-base font-bold text-[#1e3a5f] mb-3 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            Deposit &amp; Excess
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div v-if="vehicle?.security_deposit > 0 && !currentProduct?.deposit" class="bg-amber-50/70 rounded-xl p-4 border border-amber-200/60">
                <p class="text-xs font-bold text-amber-700 uppercase tracking-wider">Security Deposit</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatPrice(vehicle?.security_deposit) }}</p>
                <p class="text-xs text-gray-500 mt-1">Blocked on credit card at pick-up</p>
            </div>
            <div v-else-if="depositAmount" class="bg-amber-50/70 rounded-xl p-4 border border-amber-200/60">
                <p class="text-xs font-bold text-amber-700 uppercase tracking-wider">Deposit Amount</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatPrice(depositAmount) }}</p>
                <p v-if="depositCurrency" class="text-xs text-gray-500 mt-1">Currency: {{ depositCurrency }}</p>
            </div>
            <div v-if="excessAmount" class="bg-orange-50/70 rounded-xl p-4 border border-orange-200/60">
                <p class="text-xs font-bold text-orange-700 uppercase tracking-wider">Excess Amount</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatPrice(excessAmount) }}</p>
                <p class="text-xs text-gray-500 mt-1">Maximum liability per incident</p>
            </div>
            <div v-if="excessTheftAmount" class="bg-orange-50/70 rounded-xl p-4 border border-orange-200/60">
                <p class="text-xs font-bold text-orange-700 uppercase tracking-wider">Theft Excess</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatPrice(excessTheftAmount) }}</p>
            </div>
        </div>

        <!-- Deposit Type Selector (internal only) -->
        <div v-if="availableDepositTypes.length > 1" class="mt-4 rounded-xl border-2 p-4 transition-colors"
            :class="depositType ? 'border-emerald-200 bg-emerald-50/30' : 'border-amber-300 bg-amber-50'">
            <div class="flex items-start gap-3 mb-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                    :class="depositType ? 'bg-emerald-100' : 'bg-amber-100'">
                    <svg v-if="depositType" class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    <svg v-else class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.834-1.964-.834-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                </div>
                <div>
                    <h5 class="text-sm font-bold text-gray-900">How would you like to pay the deposit at pickup?</h5>
                    <p class="text-xs text-gray-500 mt-0.5">The vendor requires a deposit of <strong>{{ formatPrice(depositAmount) }}</strong> when you collect the vehicle.</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <label v-for="dtype in availableDepositTypes" :key="dtype" class="relative cursor-pointer">
                    <input type="radio" :value="dtype" v-model="depositType" class="sr-only peer" />
                    <span class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold border-2 transition-all cursor-pointer peer-checked:border-[#1e3a5f] peer-checked:bg-[#1e3a5f] peer-checked:text-white peer-checked:shadow-lg border-gray-200 bg-white text-gray-700 hover:border-[#1e3a5f]/40 hover:shadow-sm">
                        <svg v-if="depositType === dtype" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        <span v-else class="w-4 h-4 rounded-full border-2 border-gray-300"></span>
                        {{ formatPaymentMethod(dtype) }}
                    </span>
                </label>
            </div>
        </div>
        <div v-else-if="availableDepositTypes.length === 1" class="mt-4 flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-50 border border-blue-100">
            <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
            <p class="text-sm text-blue-700">Deposit payable via <strong>{{ formatPaymentMethod(availableDepositTypes[0]) }}</strong> at pickup</p>
        </div>
    </section>
</template>
