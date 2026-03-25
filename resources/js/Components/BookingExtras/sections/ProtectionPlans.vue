<script setup>
defineProps({
    plans: Array,
    selectedExtras: Object,
    isRenteon: Boolean,
    isSicilyByCar: Boolean,
    formatRentalPrice: Function,
    formatPrice: Function,
    getProviderExtraLabel: Function,
    getExtraPerDay: Function,
    numberOfDays: Number,
});

const emit = defineEmits(['toggle-extra', 'update-extra-quantity']);
</script>

<template>
    <!-- ═══ PROTECTION PLANS / INSURANCE ═══ -->
    <section v-if="plans.length > 0" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up" style="animation-delay:0.45s">
        <h3 class="text-lg font-bold text-[#1e3a5f] mb-2 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            Protection Plans
        </h3>
        <p class="text-sm text-gray-500 mb-5">Add insurance to reduce your liability</p>

        <div class="space-y-3">
            <label v-for="extra in plans" :key="extra.id"
                @click="emit('toggle-extra', extra)"
                class="plan-card flex items-start gap-4 rounded-2xl border-2 p-5 cursor-pointer"
                :class="selectedExtras[extra.id] ? 'selected' : 'border-gray-200 hover:border-gray-300 transition-colors'">
                <div class="flex-shrink-0 mt-0.5">
                    <div class="w-5 h-5 rounded border-2 flex items-center justify-center"
                        :class="selectedExtras[extra.id] ? 'border-[#1e3a5f] bg-[#1e3a5f]' : 'border-gray-300'">
                        <svg v-if="selectedExtras[extra.id]" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <h4 class="text-sm font-bold text-gray-900">{{ getProviderExtraLabel(extra) }}</h4>
                        <span class="text-sm font-bold text-[#1e3a5f] whitespace-nowrap">
                            <template v-if="isSicilyByCar">{{ formatRentalPrice(getExtraPerDay(extra)) }}/day</template>
                            <template v-else>{{ formatRentalPrice(extra.total_for_booking != null ? extra.total_for_booking : (extra.daily_rate != null ? extra.daily_rate : (extra.price / numberOfDays))) }}{{ extra.total_for_booking != null ? '' : '/day' }}</template>
                        </span>
                    </div>
                    <p v-if="extra.description" class="text-xs text-gray-500 mt-1 protection-desc" v-html="extra.description"></p>
                    <p v-if="extra.excess != null" class="text-xs text-gray-500 mt-0.5">Excess: <span class="font-semibold text-gray-700">{{ formatPrice(extra.excess) }}</span></p>
                    <div v-if="extra.numberAllowed && extra.numberAllowed > 1" class="flex items-center gap-2 mt-2" @click.stop>
                        <button type="button" class="w-7 h-7 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-500 transition-colors" @click.stop="emit('update-extra-quantity', extra, -1)" :disabled="selectedExtras[extra.id] <= (extra.required ? 1 : 0)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                        </button>
                        <span class="w-6 text-center text-sm font-bold text-gray-900">{{ selectedExtras[extra.id] || (extra.required ? 1 : 0) }}</span>
                        <button type="button" class="w-7 h-7 rounded-lg bg-[#1e3a5f] text-white flex items-center justify-center hover:bg-[#2d5a8f] transition-colors" @click.stop="emit('update-extra-quantity', extra, 1)" :disabled="selectedExtras[extra.id] >= extra.numberAllowed">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6"/></svg>
                        </button>
                    </div>
                </div>
            </label>
        </div>
    </section>
    <section v-else-if="isRenteon" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up">
        <h3 class="text-lg font-bold text-[#1e3a5f] mb-2">Protection Plans</h3>
        <p class="text-sm text-gray-500">No protection plans were provided by the supplier for this offer.</p>
    </section>
</template>
