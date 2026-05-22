<script setup>
import { computed } from 'vue';
import { Check, Minus, Plus, ShieldCheck } from 'lucide-vue-next';

const props = defineProps({
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

const isSelected = (extra) => Boolean(props.selectedExtras?.[extra.id]);
const selectedQuantity = (extra) => props.selectedExtras?.[extra.id] || (extra.required ? 1 : 0);
const planPrice = (extra) => {
    if (props.isSicilyByCar) return props.getExtraPerDay(extra);
    return extra.total_for_booking != null ? extra.total_for_booking : (extra.daily_rate != null ? extra.daily_rate : (extra.price / props.numberOfDays));
};
const selectedCount = computed(() => (props.plans || []).reduce((sum, extra) => sum + (isSelected(extra) ? selectedQuantity(extra) : 0), 0));
</script>

<template>
    <section v-if="(plans || []).length > 0" class="bg-white rounded-2xl border border-[#153b4f]/10 shadow-[0_18px_42px_rgba(21,59,79,0.08)] overflow-hidden fade-in-up" style="animation-delay:0.45s">
        <div class="px-5 py-4 border-b border-[#153b4f]/10 bg-gradient-to-r from-[#f0f8fc] via-white to-[#f8fafc]">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.18em] text-[#0891b2]">
                        <ShieldCheck class="w-4 h-4" />
                        Protection plans
                    </div>
                    <h3 class="mt-1 text-lg font-bold text-[#153b4f]">Reduce your rental liability</h3>
                </div>
                <span class="hidden sm:inline-flex items-center gap-1.5 rounded-full border border-[#153b4f]/15 bg-white px-3 py-1.5 text-xs font-semibold text-[#153b4f]">
                    <template v-if="selectedCount > 0">
                        <Check class="w-3.5 h-3.5 text-[#0891b2]" />
                        {{ selectedCount }} added
                    </template>
                    <template v-else>
                        Optional
                    </template>
                </span>
            </div>
        </div>

        <div class="p-5 space-y-3">
            <article v-for="extra in plans" :key="extra.id"
                role="button"
                tabindex="0"
                @click="emit('toggle-extra', extra)"
                @keydown.enter.prevent="emit('toggle-extra', extra)"
                @keydown.space.prevent="emit('toggle-extra', extra)"
                class="plan-card group rounded-2xl border-2 p-5 cursor-pointer"
                :class="isSelected(extra) ? 'selected' : 'border-slate-200 hover:border-[#2d5a8f]'">
                <div class="flex flex-col sm:flex-row sm:items-start gap-4">
                    <div class="flex items-start gap-3 flex-1 min-w-0">
                        <div class="mt-0.5 rounded-md border-2 p-1 shrink-0"
                            :class="isSelected(extra) ? 'border-[#2d5a8f] bg-gradient-to-r from-[#1e3a5f] to-[#2d5a8f]' : 'border-slate-300 bg-white group-hover:border-[#2d5a8f]'">
                            <Check v-if="isSelected(extra)" class="w-3.5 h-3.5 text-white" />
                            <div v-else class="w-3.5 h-3.5"></div>
                        </div>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="text-base font-bold text-slate-950">{{ getProviderExtraLabel(extra) }}</h4>
                                <span v-if="extra.required" class="rounded-full bg-rose-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-rose-600">Required</span>
                                <span v-if="isSelected(extra)" class="rounded-full bg-[#153b4f]/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-[#153b4f]">Added</span>
                            </div>
                            <p v-if="extra.description" class="mt-1 text-sm text-slate-500 protection-desc" v-html="extra.description"></p>
                            <p v-if="extra.excess != null" class="mt-2 text-xs text-slate-500">
                                Excess: <span class="font-semibold text-slate-800">{{ formatPrice(extra.excess) }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="sm:text-right shrink-0">
                        <div class="text-xl font-bold text-[#153b4f]">
                            {{ formatRentalPrice(planPrice(extra)) }}<span class="text-xs font-medium text-slate-500">{{ extra.total_for_booking != null ? '' : '/day' }}</span>
                        </div>
                        <div class="text-[11px] font-medium text-slate-400">Protection price</div>
                    </div>
                </div>

                <div v-if="extra.numberAllowed && extra.numberAllowed > 1" class="mt-4 flex items-center justify-end gap-2 border-t border-slate-100 pt-4" @click.stop>
                    <button type="button" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center text-slate-600 transition-colors"
                        @click.stop="emit('update-extra-quantity', extra, -1)"
                        :disabled="selectedQuantity(extra) <= (extra.required ? 1 : 0)">
                        <Minus class="w-4 h-4" />
                    </button>
                    <span class="w-8 text-center text-sm font-bold text-slate-900">{{ selectedQuantity(extra) }}</span>
                    <button type="button" class="w-8 h-8 rounded-full bg-gradient-to-r from-[#1e3a5f] to-[#2d5a8f] text-white flex items-center justify-center hover:shadow-md hover:shadow-[#1e3a5f]/20 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                        @click.stop="emit('update-extra-quantity', extra, 1)"
                        :disabled="selectedQuantity(extra) >= extra.numberAllowed">
                        <Plus class="w-4 h-4" />
                    </button>
                </div>
            </article>
        </div>
    </section>

    <section v-else-if="isRenteon" class="bg-white rounded-2xl border border-[#153b4f]/10 shadow-sm p-5 fade-in-up">
        <h3 class="text-lg font-bold text-[#153b4f] mb-2">Protection Plans</h3>
        <p class="text-sm text-slate-500">No protection plans were provided by the supplier for this offer.</p>
    </section>
</template>
