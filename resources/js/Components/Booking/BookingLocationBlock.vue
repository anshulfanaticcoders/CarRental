<script setup>
import { computed } from 'vue';

const props = defineProps({
    pickupLabel: { type: String, default: 'Pickup Location' },
    dropoffLabel: { type: String, default: 'Dropoff Location' },
    pickupString: { type: String, default: null },
    returnString: { type: String, default: null },
    pickupDetails: { type: Object, default: null },
    dropoffDetails: { type: Object, default: null },
    pickupInstructions: { type: String, default: null },
    dropoffInstructions: { type: String, default: null },
    // Compact = single row side-by-side, else stacked cards
    compact: { type: Boolean, default: false },
});

const pickupLines = computed(() => formatAddress(props.pickupDetails));
const dropoffLines = computed(() => formatAddress(props.dropoffDetails));

const pickupName = computed(
    () => props.pickupDetails?.name || props.pickupDetails?.location_name || props.pickupString || '—',
);
const dropoffName = computed(
    () => props.dropoffDetails?.name || props.dropoffDetails?.location_name || props.returnString || pickupName.value,
);

const isOneWay = computed(() => {
    const p = props.pickupDetails;
    const d = props.dropoffDetails;
    if (p && d) {
        if (p.latitude != null && p.longitude != null && d.latitude != null && d.longitude != null) {
            return Math.abs(p.latitude - d.latitude) > 0.0001 || Math.abs(p.longitude - d.longitude) > 0.0001;
        }
        const pn = (p.name || '').trim().toLowerCase();
        const dn = (d.name || '').trim().toLowerCase();
        if (pn && dn) return pn !== dn;
    }
    const ps = (props.pickupString || '').trim().toLowerCase();
    const rs = (props.returnString || '').trim().toLowerCase();
    return ps !== '' && rs !== '' && ps !== rs;
});

function formatAddress(details) {
    if (!details) return [];
    const lines = [
        details.address_1,
        details.address_2,
        details.address_3,
        [details.address_city, details.address_postcode].filter(Boolean).join(' '),
        details.address_county,
        details.address_country,
    ]
        .map((line) => (line || '').trim())
        .filter((line) => line.length > 0);
    return lines;
}
</script>

<template>
    <div :class="['booking-location-block', compact ? 'grid grid-cols-1 md:grid-cols-2 gap-4' : 'space-y-4']">
        <!-- Pickup -->
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="flex items-start justify-between gap-2 mb-2">
                <div class="text-[10px] font-semibold uppercase tracking-wider text-[#153b4f]">
                    {{ pickupLabel }}
                </div>
                <span v-if="isOneWay" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#22d3ee]/15 text-[#0b2230] border border-[#22d3ee]/40">
                    One-way
                </span>
            </div>
            <p class="font-semibold text-[var(--gray-800,#153b4f)] text-sm">{{ pickupName }}</p>
            <div v-if="pickupLines.length" class="text-xs text-gray-500 mt-1.5 space-y-0.5">
                <p v-for="(line, i) in pickupLines" :key="`pu-${i}`">{{ line }}</p>
            </div>
            <div v-if="pickupDetails?.telephone || pickupDetails?.email" class="text-xs text-gray-600 mt-2 space-y-0.5">
                <p v-if="pickupDetails?.telephone">Tel: {{ pickupDetails.telephone }}</p>
                <p v-if="pickupDetails?.email">{{ pickupDetails.email }}</p>
            </div>
            <p v-if="pickupInstructions || pickupDetails?.pickup_instructions"
               class="text-xs text-gray-600 mt-2 p-2 bg-amber-50 border border-amber-100 rounded-lg">
                {{ pickupInstructions || pickupDetails?.pickup_instructions }}
            </p>
        </div>

        <!-- Dropoff (only when distinct) -->
        <div v-if="isOneWay" class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="text-[10px] font-semibold uppercase tracking-wider text-[#153b4f] mb-2">
                {{ dropoffLabel }}
            </div>
            <p class="font-semibold text-[var(--gray-800,#153b4f)] text-sm">{{ dropoffName }}</p>
            <div v-if="dropoffLines.length" class="text-xs text-gray-500 mt-1.5 space-y-0.5">
                <p v-for="(line, i) in dropoffLines" :key="`do-${i}`">{{ line }}</p>
            </div>
            <div v-if="dropoffDetails?.telephone || dropoffDetails?.email" class="text-xs text-gray-600 mt-2 space-y-0.5">
                <p v-if="dropoffDetails?.telephone">Tel: {{ dropoffDetails.telephone }}</p>
                <p v-if="dropoffDetails?.email">{{ dropoffDetails.email }}</p>
            </div>
            <p v-if="dropoffInstructions || dropoffDetails?.dropoff_instructions"
               class="text-xs text-gray-600 mt-2 p-2 bg-amber-50 border border-amber-100 rounded-lg">
                {{ dropoffInstructions || dropoffDetails?.dropoff_instructions }}
            </p>
        </div>
    </div>
</template>
