<script setup>
const props = defineProps({
    pickupLocation: String,
    dropoffLocation: String,
    locationName: String,
    vehicleLocationText: String,
    hasOneWayDropoff: Boolean,
    isAdobeCars: Boolean,
    vehicle: Object,
    locationDetailLines: { type: Array, default: () => [] },
    locationContact: Object,
    hasLocationHours: Boolean,
    isOkMobility: Boolean,
    okMobilityPickupStation: String,
    okMobilityPickupAddress: String,
    okMobilityDropoffStation: String,
    okMobilityDropoffAddress: String,
    isRenteon: Boolean,
    renteonPickupOffice: Object,
    renteonDropoffOffice: Object,
    renteonSameOffice: Boolean,
    renteonPickupLines: { type: Array, default: () => [] },
    renteonDropoffLines: { type: Array, default: () => [] },
    renteonPickupInstructions: String,
    renteonDropoffInstructions: String,
    adapterLocationData: { type: Object, default: null },
});

import { computed } from 'vue';

const vehicleAddress = computed(() => {
    const loc = props.adapterLocationData;
    if (!loc) return null;
    const parts = [loc.pickupStation, loc.pickupAddress, ...(loc.pickupLines || [])].filter(Boolean);
    return parts.length ? parts.join(', ') : null;
});

const vehiclePhone = computed(() => props.adapterLocationData?.pickupPhone || null);
const vehicleEmail = computed(() => props.adapterLocationData?.pickupEmail || null);
const pickupInstructions = computed(() => props.adapterLocationData?.pickupInstructions || null);
const dropoffInstructions = computed(() => props.adapterLocationData?.dropoffInstructions || null);

defineEmits(['show-location-hours']);
</script>

<template>
    <section class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up" style="animation-delay:0.2s">
        <h3 class="text-base font-bold text-[#1e3a5f] mb-3 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Location Details
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- Pickup -->
            <div class="bg-emerald-50/60 rounded-xl p-4 border border-emerald-100">
                <span class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Pick-up Location</span>
                <p class="text-sm font-semibold text-gray-900 mt-2">{{ pickupLocation || locationName }}</p>
                <p v-if="vehicleLocationText" class="text-xs text-gray-500 mt-1">{{ vehicleLocationText }}</p>
                <!-- Vehicle / Parking Address (all providers) -->
                <div v-if="vehicleAddress && !isAdobeCars && !isOkMobility && !isRenteon" class="mt-2 bg-white rounded-lg p-2.5 border border-emerald-200/60">
                    <span class="text-[10px] font-bold text-emerald-500 uppercase tracking-wider">Vehicle Address</span>
                    <p class="text-xs text-gray-700 mt-1">{{ vehicleAddress }}</p>
                    <div v-if="vehiclePhone || vehicleEmail" class="mt-2 pt-2 border-t border-emerald-100 space-y-1">
                        <div v-if="vehiclePhone" class="flex items-center gap-2 text-xs text-gray-600">
                            <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <span>{{ vehiclePhone }}</span>
                        </div>
                        <div v-if="vehicleEmail" class="flex items-center gap-2 text-xs text-gray-600">
                            <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span>{{ vehicleEmail }}</span>
                        </div>
                    </div>
                    <p v-if="pickupInstructions" class="text-xs text-gray-400 mt-2 italic">{{ pickupInstructions }}</p>
                </div>
                <!-- Adobe location details -->
                <template v-if="isAdobeCars">
                    <p v-if="vehicle.office_address" class="text-xs text-gray-500 mt-1">{{ vehicle.office_address }}</p>
                    <div class="mt-3 pt-3 border-t border-emerald-200/50 space-y-1.5">
                        <div v-if="vehicle.office_phone" class="flex items-center gap-2 text-xs text-gray-600">
                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <span>{{ vehicle.office_phone }}</span>
                        </div>
                        <div v-if="vehicle.office_schedule && vehicle.office_schedule.length === 2" class="flex items-center gap-2 text-xs text-gray-600">
                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Open {{ vehicle.office_schedule[0] }} – {{ vehicle.office_schedule[1] }}</span>
                        </div>
                        <div v-if="vehicle.at_airport" class="flex items-center gap-2 text-xs text-gray-600">
                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                            <span>Airport location</span>
                        </div>
                    </div>
                </template>
                <!-- Generic location details -->
                <template v-if="!isOkMobility && !isRenteon && !isAdobeCars">
                    <div v-if="locationDetailLines.length" class="mt-2 space-y-0.5">
                        <p v-for="(line, i) in locationDetailLines" :key="i" class="text-xs text-gray-500">{{ line }}</p>
                    </div>
                    <div v-if="locationContact.phone || locationContact.email" class="mt-3 pt-3 border-t border-emerald-200/50 space-y-1.5">
                        <div v-if="locationContact.phone" class="flex items-center gap-2 text-xs text-gray-600">
                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <span>{{ locationContact.phone }}</span>
                        </div>
                        <div v-if="locationContact.email" class="flex items-center gap-2 text-xs text-gray-600">
                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span>{{ locationContact.email }}</span>
                        </div>
                    </div>
                    <button v-if="hasLocationHours" @click="$emit('show-location-hours')"
                        class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-[#1e3a5f] hover:underline">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        View hours & policies
                    </button>
                </template>
                <!-- OkMobility details -->
                <template v-if="isOkMobility">
                    <p v-if="okMobilityPickupStation" class="text-xs text-gray-500 mt-1">{{ okMobilityPickupStation }}</p>
                    <p v-if="okMobilityPickupAddress" class="text-xs text-gray-500">{{ okMobilityPickupAddress }}</p>
                </template>
                <!-- Renteon details -->
                <template v-if="isRenteon && renteonPickupOffice">
                    <p v-if="renteonPickupOffice.name" class="text-xs text-gray-500 mt-1">{{ renteonPickupOffice.name }}</p>
                    <div v-if="renteonPickupLines.length" class="mt-1 space-y-0.5">
                        <p v-for="(line, i) in renteonPickupLines" :key="i" class="text-xs text-gray-500">{{ line }}</p>
                    </div>
                    <div v-if="renteonPickupOffice.phone || renteonPickupOffice.email" class="mt-3 pt-3 border-t border-emerald-200/50 space-y-1.5">
                        <div v-if="renteonPickupOffice.phone" class="flex items-center gap-2 text-xs text-gray-600">
                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <span>{{ renteonPickupOffice.phone }}</span>
                        </div>
                        <div v-if="renteonPickupOffice.email" class="flex items-center gap-2 text-xs text-gray-600">
                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span>{{ renteonPickupOffice.email }}</span>
                        </div>
                    </div>
                    <p v-if="renteonPickupInstructions" class="text-xs text-gray-400 mt-2 italic">{{ renteonPickupInstructions }}</p>
                </template>
            </div>
            <!-- Dropoff -->
            <div class="bg-rose-50/60 rounded-xl p-4 border border-rose-100">
                <span class="text-[11px] font-bold text-rose-600 uppercase tracking-wider">Drop-off Location</span>
                <template v-if="hasOneWayDropoff">
                    <p class="text-sm font-semibold text-gray-900 mt-2">{{ dropoffLocation }}</p>
                    <template v-if="isOkMobility">
                        <p v-if="okMobilityDropoffStation" class="text-xs text-gray-500 mt-1">{{ okMobilityDropoffStation }}</p>
                        <p v-if="okMobilityDropoffAddress" class="text-xs text-gray-500">{{ okMobilityDropoffAddress }}</p>
                    </template>
                    <template v-if="isRenteon && renteonDropoffOffice && !renteonSameOffice">
                        <p v-if="renteonDropoffOffice.name" class="text-xs text-gray-500 mt-1">{{ renteonDropoffOffice.name }}</p>
                        <div v-if="renteonDropoffLines.length" class="mt-1 space-y-0.5">
                            <p v-for="(line, i) in renteonDropoffLines" :key="i" class="text-xs text-gray-500">{{ line }}</p>
                        </div>
                        <div v-if="renteonDropoffOffice.phone || renteonDropoffOffice.email" class="mt-3 pt-3 border-t border-rose-200/50 space-y-1.5">
                            <div v-if="renteonDropoffOffice.phone" class="flex items-center gap-2 text-xs text-gray-600">
                                <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <span>{{ renteonDropoffOffice.phone }}</span>
                            </div>
                        </div>
                        <p v-if="renteonDropoffInstructions" class="text-xs text-gray-400 mt-2 italic">{{ renteonDropoffInstructions }}</p>
                    </template>
                </template>
                <template v-else>
                    <p class="text-sm font-semibold text-gray-900 mt-2">Same as pick-up location</p>
                </template>
            </div>
        </div>
    </section>
</template>
