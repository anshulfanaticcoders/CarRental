<script setup>
import { computed } from 'vue';
import { Check, Minus, Plus, Sparkles } from 'lucide-vue-next';

const props = defineProps({
    extras: Array,
    selectedExtras: Object,
    isFavrica: Boolean,
    isXDrive: Boolean,
    isEmr: Boolean,
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

const isSelected = (extra) => Boolean(props.selectedExtras?.[extra.id]);
const selectedQuantity = (extra) => props.selectedExtras?.[extra.id] || (extra.required ? 1 : 0);
const visibleExtras = computed(() => (props.extras || []).filter(extra => !extra.isHidden));
const selectedCount = computed(() => visibleExtras.value.reduce((sum, extra) => sum + (isSelected(extra) ? selectedQuantity(extra) : 0), 0));
const serviceTitle = (extra) => props.getProviderExtraLabel(extra);
const servicePrice = (extra) => {
    if (props.isSicilyByCar) return props.getExtraPerDay(extra);
    return extra.total_for_booking != null ? extra.total_for_booking : (extra.daily_rate != null ? extra.daily_rate : (extra.price / props.numberOfDays));
};
const serviceSuffix = (extra) => props.isSicilyByCar || extra.total_for_booking == null ? '/day' : '/booking';
</script>

<template>
    <section v-if="visibleExtras.length > 0" class="bg-white rounded-2xl border border-[#153b4f]/10 shadow-[0_18px_42px_rgba(21,59,79,0.08)] overflow-hidden fade-in-up" style="animation-delay:0.5s">
        <div class="px-5 py-4 border-b border-[#153b4f]/10 bg-gradient-to-r from-[#f0f8fc] via-white to-[#f8fafc]">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.18em] text-[#0891b2]">
                        <Plus class="w-4 h-4" />
                        {{ (isFavrica || isXDrive || isEmr) ? 'Additional services' : 'Optional extras' }}
                    </div>
                    <h3 class="mt-1 text-lg font-bold text-[#153b4f]">
                        {{ (isFavrica || isXDrive || isEmr) ? 'Add useful services' : 'Tailor the rental to your trip' }}
                    </h3>
                </div>
                <span class="hidden sm:inline-flex items-center gap-1.5 rounded-full border border-[#153b4f]/15 bg-white px-3 py-1.5 text-xs font-semibold text-[#153b4f]">
                    <template v-if="selectedCount > 0">
                        <Sparkles class="w-3.5 h-3.5 text-[#0891b2]" />
                        {{ selectedCount }} added
                    </template>
                    <template v-else>
                        {{ visibleExtras.length }} available
                    </template>
                </span>
            </div>
        </div>

        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <template v-for="extra in visibleExtras" :key="extra.id">
                <article
                    role="button"
                    tabindex="0"
                    @click="emit('toggle-extra', extra)"
                    @keydown.enter.prevent="emit('toggle-extra', extra)"
                    @keydown.space.prevent="emit('toggle-extra', extra)"
                    class="card-hover group rounded-2xl border-2 p-4 bg-white cursor-pointer transition-all"
                    :class="isSelected(extra) ? 'border-[#22d3ee] bg-[#f0fbfc] shadow-[0_10px_24px_rgba(34,211,238,0.18)]' : 'border-slate-200 hover:border-[#22d3ee]'">
                    <div class="flex items-start gap-3">
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0"
                            :class="getIconBackgroundClass(serviceTitle(extra))"
                            :style="isSelected(extra) ? { backgroundColor: '#22d3ee' } : null">
                            <component :is="getExtraIcon(serviceTitle(extra))" class="w-5 h-5"
                                :class="isSelected(extra) ? 'text-[#0b2230]' : getIconColorClass(serviceTitle(extra))" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <h4 class="text-sm font-bold text-slate-950 leading-snug">{{ serviceTitle(extra) }}</h4>
                                <span v-if="extra.required" class="rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-600 shrink-0">Required</span>
                                <span v-else-if="isSelected(extra)" class="rounded-full bg-[#22d3ee]/15 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-[#0b2230] shrink-0">Added</span>
                            </div>
                            <p v-if="(isSicilyByCar || isRecordGo) && extra.description" class="mt-1 text-xs text-slate-500 line-clamp-2">{{ extra.description }}</p>
                        </div>
                    </div>

                    <div class="mt-4 flex items-end justify-between gap-3 border-t border-slate-100 pt-4">
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Price</div>
                            <div class="text-lg font-bold text-[#153b4f]">
                                {{ formatRentalPrice(servicePrice(extra)) }}<span class="text-xs font-medium text-slate-500">{{ serviceSuffix(extra) }}</span>
                            </div>
                        </div>

                        <div v-if="extra.numberAllowed && extra.numberAllowed > 1" class="flex items-center gap-2" @click.stop>
                            <button type="button" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center text-slate-600 transition-colors"
                                @click.stop="emit('update-extra-quantity', extra, -1)"
                                :disabled="selectedQuantity(extra) <= (extra.required ? 1 : 0)">
                                <Minus class="w-4 h-4" />
                            </button>
                            <span class="w-7 text-center text-sm font-bold text-slate-900">{{ selectedQuantity(extra) }}</span>
                            <button type="button" class="w-8 h-8 rounded-full bg-[#22d3ee] text-[#0b2230] flex items-center justify-center hover:bg-[#67e8f9] disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                                @click.stop="emit('update-extra-quantity', extra, 1)"
                                :disabled="selectedQuantity(extra) >= extra.numberAllowed">
                                <Plus class="w-4 h-4" />
                            </button>
                        </div>

                        <button v-else-if="!isSelected(extra)" type="button"
                            class="rounded-full bg-[#22d3ee] px-4 py-2 text-xs font-bold text-[#0b2230] border border-transparent shadow-[0_6px_14px_rgba(34,211,238,0.24)] hover:bg-[#67e8f9] transition-colors"
                            @click.stop="emit('toggle-extra', extra)">
                            Add
                        </button>
                        <span v-else class="inline-flex items-center gap-1.5 rounded-full bg-[#0b2230] px-4 py-2 text-xs font-bold text-white">
                            <Check class="w-3.5 h-3.5" />
                            Added
                        </span>
                    </div>
                </article>
            </template>
        </div>
    </section>
</template>
