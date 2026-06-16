<script setup>
import { computed } from 'vue';
import { Check, Minus, Plus, ShieldCheck, Sparkles } from 'lucide-vue-next';

const props = defineProps({
    extras: Array,
    selectedExtras: Object,
    isFavrica: Boolean,
    isXDrive: Boolean,
    isEmr: Boolean,
    isSicilyByCar: Boolean,
    isRecordGo: Boolean,
    isLocautoRent: Boolean,
    locautoAssistanceItems: { type: Array, default: () => [] },
    formatRentalPrice: Function,
    formatPrice: Function,
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
const maxQuantity = (extra) => Math.max(parseInt(extra.numberAllowed || extra.maxQuantity || extra.max_quantity || 1), 1);
const visibleExtras = computed(() => (props.extras || []).filter(extra => !extra.isHidden));
const visibleAssistanceItems = computed(() => props.isLocautoRent ? (props.locautoAssistanceItems || []) : []);
const selectedCount = computed(() => visibleExtras.value.reduce((sum, extra) => sum + (isSelected(extra) ? selectedQuantity(extra) : 0), 0));
const serviceTitle = (extra) => props.getProviderExtraLabel(extra);
const servicePrice = (extra) => {
    if (extra.isIncluded) return 0;
    if (props.isLocautoRent) {
        const pricingType = `${extra.pricing_type || ''}`.toLowerCase();
        const total = parseFloat(extra.total_for_booking ?? extra.price);
        if ((pricingType === 'per_rental' || pricingType === 'per_booking') && Number.isFinite(total)) {
            return total;
        }

        const daily = parseFloat(extra.daily_rate);
        if (Number.isFinite(daily) && daily > 0) return daily;
        if (Number.isFinite(total) && props.numberOfDays > 0) return total / props.numberOfDays;
        return extra.daily_rate ?? 0;
    }
    if (props.isSicilyByCar) return props.getExtraPerDay(extra);
    return extra.total_for_booking != null ? extra.total_for_booking : (extra.daily_rate != null ? extra.daily_rate : (extra.price / props.numberOfDays));
};
const serviceSuffix = (extra) => {
    if (extra.isIncluded) return '';
    if (props.isLocautoRent) {
        const pricingType = `${extra.pricing_type || ''}`.toLowerCase();
        return pricingType === 'per_rental' || pricingType === 'per_booking' ? '/booking' : '/day';
    }
    return props.isSicilyByCar || extra.total_for_booking == null ? '/day' : '/booking';
};
const formatServicePrice = (extra) => {
    const formatter = props.isLocautoRent
        ? (props.formatPrice || props.formatRentalPrice)
        : props.formatRentalPrice;
    return formatter(servicePrice(extra));
};
const locautoGroupMeta = {
    safety_assistance: {
        title: 'Safety and assistance',
        description: "Included with Don't Worry protection.",
        icon: ShieldCheck,
    },
    extra_optionals: {
        title: 'Extra optionals',
        description: 'Practical equipment for the journey.',
        icon: Sparkles,
    },
    extra_services: {
        title: 'Extra Services',
        description: 'Add flexibility for drivers and return timing.',
        icon: Plus,
    },
    other: {
        title: 'Other extras',
        description: 'Additional supplier options.',
        icon: Plus,
    },
};
const locautoGroupedExtras = computed(() => {
    if (!props.isLocautoRent) return [];

    const buckets = {
        safety_assistance: [...visibleAssistanceItems.value],
        extra_optionals: [],
        extra_services: [],
        other: [],
    };

    visibleExtras.value.forEach((extra) => {
        const key = extra.category_key || 'other';
        if (!buckets[key]) buckets.other.push(extra);
        else buckets[key].push(extra);
    });

    return ['safety_assistance', 'extra_optionals', 'extra_services', 'other']
        .map(key => ({
            key,
            ...locautoGroupMeta[key],
            extras: buckets[key],
        }))
        .filter(group => group.extras.length > 0);
});
</script>

<template>
    <section v-if="visibleExtras.length > 0 || visibleAssistanceItems.length > 0" class="bg-white rounded-2xl border border-[#153b4f]/10 shadow-[0_18px_42px_rgba(21,59,79,0.08)] overflow-hidden fade-in-up" style="animation-delay:0.5s">
        <div class="px-5 py-4 border-b border-[#153b4f]/10 bg-gradient-to-r from-[#f0f8fc] via-white to-[#f8fafc]">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.18em] text-[#0891b2]">
                        <Plus class="w-4 h-4" />
                        {{ isLocautoRent ? 'Services and extras' : ((isFavrica || isXDrive || isEmr) ? 'Additional services' : 'Optional extras') }}
                    </div>
                    <h3 class="mt-1 text-lg font-bold text-[#153b4f]">
                        {{ isLocautoRent ? 'Make the rental fit your trip' : ((isFavrica || isXDrive || isEmr) ? 'Add useful services' : 'Tailor the rental to your trip') }}
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

        <div v-if="isLocautoRent" class="p-5 space-y-6">
            <div v-for="group in locautoGroupedExtras" :key="group.key" class="space-y-3">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-[#f0f8fc] text-[#0891b2] ring-1 ring-[#153b4f]/10">
                                <component :is="group.icon" class="h-4 w-4" />
                            </span>
                            <div>
                                <h4 class="text-sm font-extrabold text-[#153b4f]">{{ group.title }}</h4>
                                <p class="text-xs text-slate-500">{{ group.description }}</p>
                            </div>
                        </div>
                    </div>
                    <span class="rounded-full bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-500 ring-1 ring-slate-200">
                        {{ group.extras.length }} {{ group.extras.length === 1 ? 'item' : 'items' }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                    <article
                        v-for="extra in group.extras"
                        :key="extra.id"
                        :role="extra.isIncluded ? undefined : 'button'"
                        :tabindex="extra.isIncluded ? undefined : 0"
                        @click="!extra.isIncluded && emit('toggle-extra', extra)"
                        @keydown.enter.prevent="!extra.isIncluded && emit('toggle-extra', extra)"
                        @keydown.space.prevent="!extra.isIncluded && emit('toggle-extra', extra)"
                        class="card-hover group rounded-2xl border-2 p-4 transition-all"
                        :class="[
                            extra.isIncluded
                                ? 'border-emerald-200 bg-emerald-50/60 cursor-default'
                                : 'cursor-pointer',
                            !extra.isIncluded && isSelected(extra)
                                ? 'border-[#2d5a8f] bg-[#f0f8fc] shadow-[0_10px_24px_rgba(30,58,95,0.18)]'
                                : (!extra.isIncluded ? 'border-slate-200 bg-white hover:border-[#2d5a8f]' : '')
                        ]">
                        <div class="flex items-start gap-3">
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0"
                                :class="extra.isIncluded || isSelected(extra) ? 'bg-gradient-to-r from-[#153b4f] to-[#0891b2]' : getIconBackgroundClass(serviceTitle(extra))">
                                <Check v-if="extra.isIncluded" class="w-5 h-5 text-white" />
                                <component v-else :is="getExtraIcon(serviceTitle(extra))" class="w-5 h-5"
                                    :class="isSelected(extra) ? 'text-white' : getIconColorClass(serviceTitle(extra))" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <h4 class="text-sm font-bold text-slate-950 leading-snug">{{ serviceTitle(extra) }}</h4>
                                    <span v-if="extra.isIncluded" class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700 shrink-0">Included</span>
                                    <span v-else-if="isSelected(extra)" class="rounded-full bg-[#153b4f]/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-[#153b4f] shrink-0">Added</span>
                                </div>
                                <p v-if="extra.description" class="mt-1 text-xs leading-5 text-slate-500">{{ extra.description }}</p>
                            </div>
                        </div>

                        <div class="mt-4 flex items-end justify-between gap-3 border-t border-slate-100 pt-4">
                            <div>
                                <div class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ extra.isIncluded ? 'Status' : 'Price' }}</div>
                                <div class="text-lg font-bold" :class="extra.isIncluded ? 'text-emerald-700' : 'text-[#153b4f]'">
                                    <template v-if="extra.isIncluded">Free</template>
                                    <template v-else>
                                        {{ formatServicePrice(extra) }}<span class="text-xs font-medium text-slate-500">{{ serviceSuffix(extra) }}</span>
                                    </template>
                                </div>
                            </div>

                            <span v-if="extra.isIncluded" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-600 px-4 py-2 text-xs font-bold text-white">
                                <Check class="w-3.5 h-3.5" />
                                Included
                            </span>
                            <button v-else-if="!isSelected(extra)" type="button"
                                class="rounded-full bg-gradient-to-r from-[#1e3a5f] to-[#2d5a8f] px-4 py-2 text-xs font-bold text-white border border-transparent shadow-[0_6px_14px_rgba(30,58,95,0.24)] hover:shadow-md hover:shadow-[#1e3a5f]/25 transition-all"
                                @click.stop="emit('toggle-extra', extra)">
                                Add
                            </button>
                            <span v-else class="inline-flex items-center gap-1.5 rounded-full bg-[#0b2230] px-4 py-2 text-xs font-bold text-white">
                                <Check class="w-3.5 h-3.5" />
                                Added
                            </span>
                        </div>
                    </article>
                </div>
            </div>
        </div>

        <div v-else class="p-5 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <template v-for="extra in visibleExtras" :key="extra.id">
                <article
                    role="button"
                    tabindex="0"
                    @click="emit('toggle-extra', extra)"
                    @keydown.enter.prevent="emit('toggle-extra', extra)"
                    @keydown.space.prevent="emit('toggle-extra', extra)"
                    class="card-hover group rounded-2xl border-2 p-4 bg-white cursor-pointer transition-all"
                    :class="isSelected(extra) ? 'border-[#2d5a8f] bg-[#f0f8fc] shadow-[0_10px_24px_rgba(30,58,95,0.18)]' : 'border-slate-200 hover:border-[#2d5a8f]'">
                    <div class="flex items-start gap-3">
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0"
                            :class="isSelected(extra) ? 'bg-gradient-to-r from-[#1e3a5f] to-[#2d5a8f]' : getIconBackgroundClass(serviceTitle(extra))">
                            <component :is="getExtraIcon(serviceTitle(extra))" class="w-5 h-5"
                                :class="isSelected(extra) ? 'text-white' : getIconColorClass(serviceTitle(extra))" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <h4 class="text-sm font-bold text-slate-950 leading-snug">{{ serviceTitle(extra) }}</h4>
                                <span v-if="extra.required" class="rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-600 shrink-0">Required</span>
                                <span v-else-if="isSelected(extra)" class="rounded-full bg-[#153b4f]/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-[#153b4f] shrink-0">Added</span>
                            </div>
                            <p v-if="(isSicilyByCar || isRecordGo) && extra.description" class="mt-1 text-xs text-slate-500 line-clamp-2">{{ extra.description }}</p>
                        </div>
                    </div>

                    <div class="mt-4 flex items-end justify-between gap-3 border-t border-slate-100 pt-4">
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Price</div>
                            <div class="text-lg font-bold text-[#153b4f]">
                                {{ formatServicePrice(extra) }}<span class="text-xs font-medium text-slate-500">{{ serviceSuffix(extra) }}</span>
                            </div>
                        </div>

                        <div v-if="maxQuantity(extra) > 1" class="flex items-center gap-2" @click.stop>
                            <button type="button" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center text-slate-600 transition-colors"
                                @click.stop="emit('update-extra-quantity', extra, -1)"
                                :disabled="selectedQuantity(extra) <= (extra.required ? 1 : 0)">
                                <Minus class="w-4 h-4" />
                            </button>
                            <span class="w-7 text-center text-sm font-bold text-slate-900">{{ selectedQuantity(extra) }}</span>
                            <button type="button" class="w-8 h-8 rounded-full bg-gradient-to-r from-[#1e3a5f] to-[#2d5a8f] text-white flex items-center justify-center hover:shadow-md hover:shadow-[#1e3a5f]/20 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                                @click.stop="emit('update-extra-quantity', extra, 1)"
                                :disabled="selectedQuantity(extra) >= maxQuantity(extra)">
                                <Plus class="w-4 h-4" />
                            </button>
                        </div>

                        <button v-else-if="!isSelected(extra)" type="button"
                            class="rounded-full bg-gradient-to-r from-[#1e3a5f] to-[#2d5a8f] px-4 py-2 text-xs font-bold text-white border border-transparent shadow-[0_6px_14px_rgba(30,58,95,0.24)] hover:shadow-md hover:shadow-[#1e3a5f]/25 transition-all"
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
