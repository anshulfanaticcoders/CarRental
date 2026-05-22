<script setup>
import { computed } from 'vue';
import {
    CheckCircle2,
    Clock,
    Info,
    Mail,
    MapPin,
    ParkingCircle,
    Phone,
    Route,
} from 'lucide-vue-next';

const props = defineProps({
    locationDisplay: {
        type: Object,
        default: () => ({
            pickup: {},
            dropoff: {},
            hasAnyDetails: false,
        }),
    },
    hasLocationHours: Boolean,
});

defineEmits(['show-location-hours']);

const pickup = computed(() => props.locationDisplay?.pickup || {});
const dropoff = computed(() => props.locationDisplay?.dropoff || {});

const contactItems = (point) => [
    { key: 'phone', value: point?.contact?.phone, icon: Phone },
    { key: 'email', value: point?.contact?.email, icon: Mail },
].filter((item) => item.value);

const pickupContactItems = computed(() => contactItems(pickup.value));
const dropoffContactItems = computed(() => contactItems(dropoff.value));

const hasPickupMeta = computed(() => Boolean(
    pickup.value?.addressLines?.length
    || pickup.value?.parkingAddress
    || pickup.value?.instructions
    || pickupContactItems.value.length
    || props.hasLocationHours
));

const hasDropoffGuidance = computed(() => Boolean(
    dropoff.value?.instructions
    || dropoffContactItems.value.length
    || dropoff.value?.parkingAddress
    || dropoff.value?.addressLines?.length
));

const hasSeparateDropoffPanel = computed(() => Boolean(
    !dropoff.value?.isSameAsPickup
    && (dropoff.value?.label || hasDropoffGuidance.value)
));

const returnLocationLabel = computed(() => (
    dropoff.value?.label
    || pickup.value?.label
    || 'Pickup location'
));
</script>

<template>
    <section class="bg-white rounded-2xl border border-[#153b4f]/10 shadow-[0_14px_34px_rgba(21,59,79,0.07)] overflow-hidden fade-in-up" style="animation-delay:0.2s">
        <div class="px-5 py-4 border-b border-[#153b4f]/10 bg-gradient-to-r from-[#f0f8fc] via-white to-[#f8fafc]">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.18em] text-[#0891b2]">
                        <Route class="w-4 h-4" />
                        Rental logistics
                    </div>
                    <h3 class="mt-1 text-lg font-bold text-[#153b4f]">Pickup & return details</h3>
                </div>
                <span class="hidden sm:inline-flex items-center gap-2 rounded-full border border-[#153b4f]/12 bg-white px-3 py-1.5 text-xs font-semibold text-[#153b4f]">
                    Supplier details
                </span>
            </div>
        </div>

        <div class="p-5">
            <div
                class="grid grid-cols-1 gap-4 lg:items-start"
                :class="hasSeparateDropoffPanel ? 'lg:grid-cols-[minmax(0,1fr)_minmax(260px,0.78fr)]' : ''"
            >
                <article class="rounded-xl border border-[#b0d4e6]/80 bg-[#f8fbfd] p-4 sm:p-5">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-r from-[#1e3a5f] to-[#2d5a8f] text-white flex items-center justify-center shadow-[0_8px_18px_rgba(30,58,95,0.28)]">
                            <CheckCircle2 class="w-5 h-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-[#0891b2]">Pickup location</div>
                            <p class="mt-1 text-base font-bold text-slate-950 leading-snug">{{ pickup.label || 'Pickup location' }}</p>
                        </div>
                    </div>

                    <div v-if="hasPickupMeta" class="mt-4 grid grid-cols-1 gap-3" :class="pickup.addressLines?.length && pickup.parkingAddress ? 'md:grid-cols-2' : ''">
                        <div v-if="pickup.addressLines?.length" class="rounded-lg bg-white border border-[#153b4f]/10 p-3">
                            <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.15em] text-[#153b4f]">
                                <MapPin class="w-3.5 h-3.5 text-[#0891b2]" />
                                Full address
                            </div>
                            <div class="mt-2 space-y-1">
                                <p v-for="line in pickup.addressLines" :key="line" class="text-sm text-slate-700 leading-relaxed">{{ line }}</p>
                            </div>
                        </div>

                        <div v-if="pickup.parkingAddress" class="rounded-lg bg-white border border-[#153b4f]/10 p-3">
                            <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.15em] text-[#153b4f]">
                                <ParkingCircle class="w-3.5 h-3.5 text-[#0891b2]" />
                                Vehicle / parking address
                            </div>
                            <p class="mt-2 text-sm text-slate-700 leading-relaxed">{{ pickup.parkingAddress }}</p>
                        </div>
                    </div>

                    <div v-if="pickup.instructions" class="mt-3 flex items-start gap-3 rounded-lg border border-[#153b4f]/12 bg-white p-3">
                        <div class="mt-0.5 w-7 h-7 rounded-full bg-[#f0f8fc] text-[#153b4f] flex items-center justify-center shrink-0">
                            <Info class="w-4 h-4" />
                        </div>
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-[0.15em] text-[#153b4f]">Pickup guidelines</div>
                            <p class="mt-1 text-sm leading-relaxed text-slate-700 whitespace-pre-line">{{ pickup.instructions }}</p>
                        </div>
                    </div>

                    <div v-if="pickupContactItems.length || hasLocationHours" class="mt-4 flex flex-wrap gap-2">
                        <div v-for="item in pickupContactItems" :key="item.key" class="inline-flex items-center gap-1.5 rounded-full border border-[#153b4f]/20 bg-white px-3 py-1.5 text-xs font-medium text-slate-700">
                            <component :is="item.icon" class="w-3.5 h-3.5 text-[#0891b2]" />
                            {{ item.value }}
                        </div>
                        <button v-if="hasLocationHours" type="button" @click="$emit('show-location-hours')"
                            class="inline-flex items-center gap-1.5 rounded-full border border-[#153b4f]/15 bg-white px-3 py-1.5 text-xs font-semibold text-[#153b4f] hover:border-[#153b4f]/35 hover:bg-[#f0f8fc] transition-colors">
                            <Clock class="w-3.5 h-3.5" />
                            Hours & policies
                        </button>
                    </div>

                    <div v-if="dropoff.isSameAsPickup" class="mt-4 flex flex-col gap-3 rounded-xl border border-[#153b4f]/10 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl bg-[#f0f8fc] text-[#153b4f] flex items-center justify-center shrink-0">
                                <Route class="w-4 h-4" />
                            </div>
                            <div class="min-w-0">
                                <div class="text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400">Return location</div>
                                <p class="text-sm font-bold text-slate-950">Same as pickup</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ returnLocationLabel }}</p>
                            </div>
                        </div>
                        <span class="inline-flex w-fit items-center rounded-full bg-[#153b4f]/10 px-3 py-1 text-xs font-bold text-[#153b4f]">
                            Return to same location
                        </span>
                    </div>
                </article>

                <article v-if="hasSeparateDropoffPanel" class="rounded-xl border border-rose-200/70 bg-white p-4 sm:p-5">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center">
                            <MapPin class="w-5 h-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-rose-600">Return location</div>
                            <p class="mt-1 text-base font-bold text-slate-950 leading-snug">{{ returnLocationLabel }}</p>
                        </div>
                    </div>

                    <div v-if="dropoff.addressLines?.length" class="mt-4 rounded-lg bg-rose-50/40 border border-rose-100 p-3">
                        <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.15em] text-rose-700">
                            <MapPin class="w-3.5 h-3.5" />
                            Full address
                        </div>
                        <div class="mt-2 space-y-1">
                            <p v-for="line in dropoff.addressLines" :key="line" class="text-sm text-slate-700 leading-relaxed">{{ line }}</p>
                        </div>
                    </div>

                    <div v-if="dropoff.parkingAddress" class="mt-3 rounded-lg bg-rose-50/40 border border-rose-100 p-3">
                        <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.15em] text-rose-700">
                            <ParkingCircle class="w-3.5 h-3.5" />
                            Return parking address
                        </div>
                        <p class="mt-2 text-sm text-slate-700 leading-relaxed">{{ dropoff.parkingAddress }}</p>
                    </div>

                    <div v-if="dropoff.instructions" class="mt-3 flex items-start gap-3 rounded-lg border border-rose-100 bg-rose-50/40 p-3">
                        <Info class="mt-0.5 w-4 h-4 text-rose-600 shrink-0" />
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-[0.15em] text-rose-700">Return guidelines</div>
                            <p class="mt-1 text-sm leading-relaxed text-slate-700 whitespace-pre-line">{{ dropoff.instructions }}</p>
                        </div>
                    </div>

                    <div v-if="dropoffContactItems.length" class="mt-4 flex flex-wrap gap-2">
                        <div v-for="item in dropoffContactItems" :key="item.key" class="inline-flex items-center gap-1.5 rounded-full border border-rose-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700">
                            <component :is="item.icon" class="w-3.5 h-3.5 text-rose-600" />
                            {{ item.value }}
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>
</template>
