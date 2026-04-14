<template>
    <div class="space-y-4">
        <div class="rounded-xl border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
                <div>
                    <div class="text-sm font-semibold text-slate-900">How do you want to add this location?</div>
                    <div class="text-xs text-slate-600">Map search is faster. Manual entry stays available for edge cases.</div>
                </div>
                <div class="entry-toggle">
                    <button
                        type="button"
                        class="entry-toggle-button"
                        :class="{ active: entryMode === 'map' }"
                        :aria-pressed="entryMode === 'map'"
                        @click="entryMode = 'map'"
                    >
                        <span class="entry-toggle-indicator"></span>
                        Search on map
                    </button>
                    <button
                        type="button"
                        class="entry-toggle-button"
                        :class="{ active: entryMode === 'manual' }"
                        :aria-pressed="entryMode === 'manual'"
                        @click="entryMode = 'manual'"
                    >
                        <span class="entry-toggle-indicator"></span>
                        Enter manually
                    </button>
                </div>
            </div>

            <div v-if="entryMode === 'map'" class="space-y-3 px-4 py-4">
                <p class="text-sm text-slate-600">
                    Search for the office, airport, terminal, or landmark to pin the exact coordinates only. Enter the office name, address, city, and country manually below in the format your platform uses.
                </p>
                <LocationPicker
                    :on-location-select="applyLocationSelection"
                    :show-selected-summary="false"
                    label="Pin coordinates on map"
                    placeholder="Dubai Airport, Terminal 1, hotel, or landmark"
                />
                <div class="rounded-lg border border-cyan-100 bg-cyan-50 px-3 py-2 text-xs text-cyan-900">
                    <span class="font-semibold">Map usage:</span> coordinates only.
                    <span v-if="hasCoordinates" class="ml-1">
                        Selected: {{ form.latitude }}, {{ form.longitude }}
                    </span>
                </div>
                <p class="text-xs text-slate-500">
                    The map does not set the office name or business-facing location details. Those must be entered manually below.
                </p>
            </div>
        </div>

        <div class="vln-grid">
            <div class="vln-field full rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                Use the name and address exactly as you want them to appear in search, APIs, and partner feeds. Do not rely on map suggestions for office naming.
            </div>
            <div class="vln-field">
                <label class="vln-label">Name <span class="req">*</span></label>
                <input v-model="form.name" class="vln-input" type="text" placeholder="Dubai Airport" />
                <span v-if="errors.name" class="vln-error">{{ errors.name }}</span>
            </div>
            <div class="vln-field">
                <label class="vln-label">Type <span class="req">*</span></label>
                <select v-model="form.location_type" class="vln-select-native">
                    <option value="airport">Airport</option>
                    <option value="downtown">Downtown</option>
                    <option value="terminal">Terminal</option>
                    <option value="bus stop">Bus Stop</option>
                    <option value="railway station">Railway Station</option>
                    <option value="industrial">Industrial</option>
                </select>
            </div>
            <div class="vln-field">
                <label class="vln-label">IATA</label>
                <input v-model="form.iata_code" class="vln-input" type="text" maxlength="3" placeholder="DXB" />
                <span v-if="errors.iata_code" class="vln-error">{{ errors.iata_code }}</span>
            </div>
            <div class="vln-field">
                <label class="vln-label">Phone</label>
                <input v-model="form.phone" class="vln-input" type="text" placeholder="+971..." />
            </div>
            <div class="vln-field full">
                <label class="vln-label">Address <span class="req">*</span></label>
                <input v-model="form.address_line_1" class="vln-input" type="text" placeholder="Terminal 1 arrivals, rental desk" />
                <span v-if="errors.address_line_1" class="vln-error">{{ errors.address_line_1 }}</span>
            </div>
            <div class="vln-field">
                <label class="vln-label">City <span class="req">*</span></label>
                <input v-model="form.city" class="vln-input" type="text" placeholder="Dubai" />
                <span v-if="errors.city" class="vln-error">{{ errors.city }}</span>
            </div>
            <div class="vln-field">
                <label class="vln-label">State</label>
                <input v-model="form.state" class="vln-input" type="text" placeholder="Dubai" />
            </div>
            <div class="vln-field">
                <label class="vln-label">Country <span class="req">*</span></label>
                <input v-model="form.country" class="vln-input" type="text" placeholder="United Arab Emirates" />
                <span v-if="errors.country" class="vln-error">{{ errors.country }}</span>
            </div>
            <div class="vln-field">
                <label class="vln-label">Country Code <span class="req">*</span></label>
                <input v-model="form.country_code" class="vln-input" type="text" maxlength="2" placeholder="AE" />
                <span v-if="errors.country_code" class="vln-error">{{ errors.country_code }}</span>
            </div>
            <div class="vln-field">
                <label class="vln-label">Latitude <span class="req">*</span></label>
                <input v-model="form.latitude" class="vln-input" type="number" step="0.000001" />
                <span v-if="errors.latitude" class="vln-error">{{ errors.latitude }}</span>
            </div>
            <div class="vln-field">
                <label class="vln-label">Longitude <span class="req">*</span></label>
                <input v-model="form.longitude" class="vln-input" type="number" step="0.000001" />
                <span v-if="errors.longitude" class="vln-error">{{ errors.longitude }}</span>
            </div>
            <div class="vln-field full">
                <label class="vln-label">Pickup Instructions</label>
                <textarea v-model="form.pickup_instructions" class="vln-textarea" rows="3" placeholder="Collect the vehicle from the rental desk in arrivals."></textarea>
            </div>
            <div class="vln-field full">
                <label class="vln-label">Dropoff Instructions</label>
                <textarea v-model="form.dropoff_instructions" class="vln-textarea" rows="3" placeholder="Return the vehicle to the signed rental return lane."></textarea>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from "vue";
import LocationPicker from "@/Components/LocationPicker.vue";

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const entryMode = ref("map");
const hasCoordinates = computed(() => {
    return props.form.latitude !== "" && props.form.latitude !== null && props.form.longitude !== "" && props.form.longitude !== null;
});

const applyLocationSelection = (location) => {
    props.form.latitude = location.latitude ?? "";
    props.form.longitude = location.longitude ?? "";
};
</script>

<style scoped>
.entry-toggle {
    display: inline-flex;
    gap: 0.375rem;
    border-radius: 0.875rem;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    padding: 0.3rem;
}

.entry-toggle-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border-radius: 0.7rem;
    border: 1px solid transparent;
    padding: 0.65rem 0.95rem;
    font-size: 0.9rem;
    font-weight: 700;
    color: #475569;
    transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

.entry-toggle-button:hover {
    color: #0f172a;
}

.entry-toggle-button.active {
    border-color: #67e8f9;
    background: linear-gradient(180deg, #ecfeff 0%, #cffafe 100%);
    color: #0f766e;
    box-shadow: 0 4px 12px rgba(6, 182, 212, 0.12);
}

.entry-toggle-indicator {
    width: 0.55rem;
    height: 0.55rem;
    border-radius: 9999px;
    background: #cbd5e1;
    transition: background-color 0.2s ease, transform 0.2s ease;
}

.entry-toggle-button.active .entry-toggle-indicator {
    background: #06b6d4;
    transform: scale(1.08);
}

.vln-grid {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.vln-field {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.vln-field.full {
    grid-column: 1 / -1;
}

.vln-label {
    font-size: 0.82rem;
    font-weight: 700;
    color: #0f172a;
}

.req {
    color: #dc2626;
}

.vln-input,
.vln-textarea {
    width: 100%;
    border-radius: 0.875rem;
    border: 1px solid #cbd5e1;
    background: #fff;
    padding: 0.875rem 1rem;
    font-size: 0.95rem;
    color: #0f172a;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.vln-input:focus,
.vln-textarea:focus {
    outline: none;
    border-color: #06b6d4;
    box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.15);
}

.vln-textarea {
    min-height: 96px;
    resize: vertical;
}

.vln-error {
    font-size: 0.8rem;
    color: #dc2626;
}

.vln-select-native {
    width: 100%;
    border-radius: 0.875rem;
    border: 1px solid #cbd5e1;
    background: #fff;
    padding: 0.875rem 1rem;
    font-size: 0.95rem;
    color: #0f172a;
}

.vln-select-native:focus {
    outline: none;
    border-color: #06b6d4;
    box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.15);
}

@media (max-width: 767px) {
    .vln-grid {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
