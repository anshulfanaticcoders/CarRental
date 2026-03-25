<script setup>
defineProps({
    extras: Array,
    selectedExtras: Object,
    isFavrica: Boolean,
    isXDrive: Boolean,
    isSicilyByCar: Boolean,
    isRecordGo: Boolean,
    formatRentalPrice: Function,
    getProviderExtraLabel: Function,
    getExtraPerDay: Function,
    getExtraIcon: Function,
    getIconBackgroundClass: Function,
    getIconColorClass: Function,
    numberOfDays: Number,
});

const emit = defineEmits(['toggle-extra', 'update-extra-quantity']);
</script>

<template>
    <!-- ═══ OPTIONAL EXTRAS ═══ -->
    <section v-if="extras.length > 0" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up" style="animation-delay:0.5s">
        <h3 class="text-lg font-bold text-[#1e3a5f] mb-2 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            {{ (isFavrica || isXDrive) ? 'Additional Services' : 'Optional Extras' }}
        </h3>
        <p class="text-sm text-gray-500 mb-5">{{ (isFavrica || isXDrive) ? 'Add helpful services to your booking' : 'Enhance your rental experience' }}</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <template v-for="extra in extras" :key="extra.id">
                <div v-if="!extra.isHidden" @click="emit('toggle-extra', extra)"
                    class="card-hover rounded-2xl border p-4 bg-white cursor-pointer transition-all"
                    :class="selectedExtras[extra.id] ? 'border-2 border-[#1e3a5f] bg-[#f0f4f8]' : 'border-gray-200'">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                            :class="getIconBackgroundClass(getProviderExtraLabel(extra))">
                            <component :is="getExtraIcon(getProviderExtraLabel(extra))" class="w-5 h-5"
                                :class="getIconColorClass(getProviderExtraLabel(extra))" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-semibold text-gray-900">{{ getProviderExtraLabel(extra) }}</h4>
                            <p v-if="(isSicilyByCar || isRecordGo) && extra.description" class="text-xs text-gray-500 truncate">{{ extra.description }}</p>
                        </div>
                        <span v-if="extra.required" class="text-[10px] uppercase tracking-wide font-semibold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full flex-shrink-0">Required</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-[#1e3a5f]">
                            <template v-if="isSicilyByCar">
                                {{ formatRentalPrice(getExtraPerDay(extra)) }}<span class="text-xs font-normal text-gray-500">/day</span>
                            </template>
                            <template v-else>
                                {{ formatRentalPrice(extra.total_for_booking != null ? extra.total_for_booking : (extra.daily_rate != null ? extra.daily_rate : (extra.price / numberOfDays))) }}<span class="text-xs font-normal text-gray-500">{{ extra.total_for_booking != null ? '/booking' : '/day' }}</span>
                            </template>
                        </span>
                        <!-- Quantity controls or Add button -->
                        <div v-if="extra.numberAllowed && extra.numberAllowed > 1" class="flex items-center gap-2" @click.stop>
                            <button type="button" class="w-7 h-7 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-500 transition-colors" @click.stop="emit('update-extra-quantity', extra, -1)" :disabled="selectedExtras[extra.id] <= (extra.required ? 1 : 0)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                            </button>
                            <span class="w-6 text-center text-sm font-bold text-gray-900">{{ selectedExtras[extra.id] || (extra.required ? 1 : 0) }}</span>
                            <button type="button" class="w-7 h-7 rounded-lg bg-[#1e3a5f] text-white flex items-center justify-center hover:bg-[#2d5a8f] transition-colors" @click.stop="emit('update-extra-quantity', extra, 1)" :disabled="selectedExtras[extra.id] >= extra.numberAllowed">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6"/></svg>
                            </button>
                        </div>
                        <button v-else-if="!selectedExtras[extra.id]" class="px-4 py-1.5 rounded-lg bg-[#1e3a5f]/5 border border-[#1e3a5f]/20 text-[#1e3a5f] text-xs font-semibold hover:bg-[#1e3a5f]/10 transition-colors" @click.stop="emit('toggle-extra', extra)">Add</button>
                        <span v-else class="px-4 py-1.5 rounded-lg bg-[#1e3a5f] text-white text-xs font-semibold">Added</span>
                    </div>
                </div>
            </template>
        </div>
    </section>
</template>
