<script setup>
defineProps({
    unifiedTaxBreakdown: { type: Object, default: null },
    isOkMobility: { type: Boolean, default: false },
    isRenteon: { type: Boolean, default: false },
    okMobilityFuelPolicy: { type: String, default: '' },
    okMobilityCancellationSummary: { type: Object, default: null },
    renteonIncludedServices: { type: Array, default: () => [] },
    renteonDriverPolicy: { type: Object, default: null },
    isSicilyByCar: { type: Boolean, default: false },
    isRecordGo: { type: Boolean, default: false },
    sicilyByCarIncludedServices: { type: Array, default: () => [] },
    recordGoIncludedComplements: { type: Array, default: () => [] },
    recordGoAutomaticComplements: { type: Array, default: () => [] },
    formatPrice: { type: Function, required: true },
    formatRentalPrice: { type: Function, required: true },
    stripHtml: { type: Function, required: true },
})
</script>

<template>
    <!-- Taxes & Fees -->
    <section v-if="unifiedTaxBreakdown" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up" style="animation-delay:0.35s">
        <h3 class="text-base font-bold text-[#1e3a5f] mb-3 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2v16z"/></svg>
            Taxes &amp; Fees
        </h3>
        <div class="space-y-3">
            <div v-if="unifiedTaxBreakdown.net" class="flex items-center justify-between py-2 border-b border-gray-100">
                <span class="text-sm text-gray-600">Base Price (excl. tax)</span>
                <span class="text-sm font-semibold text-gray-900">{{ unifiedTaxBreakdown.type === 'renteon' ? formatPrice(unifiedTaxBreakdown.net) : formatRentalPrice(unifiedTaxBreakdown.net) }}</span>
            </div>
            <div v-if="unifiedTaxBreakdown.vat" class="flex items-center justify-between py-2 border-b border-gray-100">
                <span class="text-sm text-gray-600">VAT{{ unifiedTaxBreakdown.rate ? ` (${unifiedTaxBreakdown.rate}%)` : '' }}</span>
                <span class="text-sm font-semibold text-gray-900">{{ unifiedTaxBreakdown.type === 'renteon' ? formatPrice(unifiedTaxBreakdown.vat) : formatRentalPrice(unifiedTaxBreakdown.vat) }}</span>
            </div>
            <div v-if="unifiedTaxBreakdown.gross" class="flex items-center justify-between py-2">
                <span class="text-sm font-bold text-gray-900">Total (incl. tax)</span>
                <span class="text-sm font-bold text-[#1e3a5f]">{{ unifiedTaxBreakdown.type === 'renteon' ? formatPrice(unifiedTaxBreakdown.gross) : formatRentalPrice(unifiedTaxBreakdown.gross) }}</span>
            </div>
        </div>
        <p class="text-xs text-gray-500 mt-3">Supplier totals shown; your final price in the summary includes our booking fee.</p>
    </section>

    <!-- OkMobility Policies -->
    <section v-if="isOkMobility && (okMobilityFuelPolicy || okMobilityCancellationSummary)"
        class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up">
        <h3 class="text-base font-bold text-[#1e3a5f] mb-3 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Policies
        </h3>
        <div class="space-y-4">
            <div v-if="okMobilityFuelPolicy" class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">Fuel Policy</p>
                    <p class="text-xs text-gray-500">{{ okMobilityFuelPolicy }}</p>
                </div>
            </div>
            <div v-if="okMobilityCancellationSummary">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Cancellation</p>
                        <template v-if="!okMobilityCancellationSummary.available">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-100">Not available</span>
                        </template>
                        <template v-else>
                            <p class="text-xs text-gray-500">{{ okMobilityCancellationSummary.amount > 0 ? `Fee: ${formatRentalPrice(okMobilityCancellationSummary.amount)}` : 'Free cancellation' }}</p>
                            <p v-if="okMobilityCancellationSummary.deadline" class="text-xs text-gray-500">Cancel by {{ okMobilityCancellationSummary.deadline }}</p>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Renteon Highlights -->
    <section v-if="isRenteon && (renteonIncludedServices.length || renteonDriverPolicy)"
        class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up">
        <h3 class="text-base font-bold text-[#1e3a5f] mb-3 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Renteon Highlights
        </h3>
        <div class="space-y-4">
            <div v-if="renteonIncludedServices.length">
                <div class="flex flex-wrap gap-2">
                    <span v-for="service in renteonIncludedServices" :key="service.id"
                        class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                        {{ service.name }}<span v-if="service.quantity_included" class="ml-1">(x{{ service.quantity_included }})</span>
                    </span>
                </div>
            </div>
            <div v-if="renteonDriverPolicy" class="text-sm text-gray-600">
                <span v-if="renteonDriverPolicy.minAge">Min age: {{ renteonDriverPolicy.minAge }}</span>
                <span v-if="renteonDriverPolicy.maxAge" class="ml-3">Max age: {{ renteonDriverPolicy.maxAge }}</span>
                <span v-if="renteonDriverPolicy.youngFrom" class="ml-3">Young: {{ renteonDriverPolicy.youngFrom }}-{{ renteonDriverPolicy.youngTo || 'N/A' }}</span>
                <span v-if="renteonDriverPolicy.seniorFrom" class="ml-3">Senior: {{ renteonDriverPolicy.seniorFrom }}-{{ renteonDriverPolicy.seniorTo || 'N/A' }}</span>
            </div>
        </div>
    </section>

    <!-- Included Services (SicilyByCar) -->
    <section v-if="isSicilyByCar && sicilyByCarIncludedServices.length > 0" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up">
        <h3 class="text-base font-bold text-[#1e3a5f] mb-3 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Included Services
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div v-for="service in sicilyByCarIncludedServices" :key="service.id" class="flex items-start gap-3 p-3 rounded-xl bg-emerald-50/50 border border-emerald-100/60">
                <div class="check-icon bg-emerald-500 text-white mt-0.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ service.description || service.id }}</p>
                    <p v-if="service.excess != null" class="text-xs text-gray-500">Excess: {{ formatPrice(service.excess) }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Included Coverage (RecordGo) -->
    <section v-if="isRecordGo && recordGoIncludedComplements.length > 0" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up">
        <h3 class="text-base font-bold text-[#1e3a5f] mb-3 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Included Coverage
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div v-for="service in recordGoIncludedComplements" :key="service.complementId" class="flex items-start gap-3 p-3 rounded-xl bg-emerald-50/50 border border-emerald-100/60">
                <div class="check-icon bg-emerald-500 text-white mt-0.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ stripHtml(service.complementName) }}</p>
                    <p v-if="service.complementDescription" class="text-xs text-gray-500">{{ stripHtml(service.complementDescription) }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Automatic Supplements (RecordGo) -->
    <section v-if="isRecordGo && recordGoAutomaticComplements.length > 0" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up">
        <h3 class="text-base font-bold text-[#1e3a5f] mb-3 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Automatic Supplements
        </h3>
        <div class="space-y-3">
            <div v-for="service in recordGoAutomaticComplements" :key="service.complementId" class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ stripHtml(service.complementName) }}</p>
                    <p v-if="service.complementDescription" class="text-xs text-gray-500">{{ stripHtml(service.complementDescription) }}</p>
                </div>
                <div class="text-right">
                    <span class="text-sm font-bold text-gray-900">{{ formatRentalPrice(service.priceTaxIncDayDiscount ?? service.priceTaxIncDay ?? service.priceTaxIncComplement ?? 0) }}</span>
                    <p class="text-xs text-gray-400">Per Day</p>
                </div>
            </div>
        </div>
    </section>
</template>
