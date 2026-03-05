<template>
    <AffiliateHeader currentPage="qr-codes" />
        <!-- Pending Approval Banner -->
        <div v-if="!isVerified" class="bg-amber-50 border-b border-amber-200">
            <div class="max-w-[min(92%,1200px)] mx-auto py-3 flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-amber-400 text-white flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01" />
                    </svg>
                </div>
                <div>
                    <span class="text-sm font-bold text-amber-800">Account Pending Approval</span>
                    <span class="text-xs text-amber-600 ml-1">— Your partner account is being reviewed. You'll be notified by email once approved. Some features are limited.</span>
                </div>
            </div>
        </div>

        <!-- Dark Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-br from-[#0a1d28] to-[#153b4f]">
            <div class="absolute top-[5%] left-[-3%] w-[220px] h-[220px] rounded-full bg-cyan-500 opacity-20 blur-[80px] pointer-events-none animate-float"></div>

            <div class="relative z-10 max-w-[min(92%,1200px)] mx-auto py-4 md:py-6">
                <div class="flex flex-col md:flex-row justify-between md:items-center gap-3">
                    <div>
                        <h1 class="text-xl md:text-[1.75rem] font-[800] text-white mb-0.5">QR Codes</h1>
                        <p class="text-[0.85rem] text-slate-400">Manage your location QR codes and view performance.</p>
                    </div>
                    <button @click="isVerified && (showCreateForm = !showCreateForm)"
                        :disabled="!isVerified"
                        :class="[
                            'inline-flex items-center gap-1.5 px-4 py-2.5 text-[0.8rem] font-bold text-white rounded-[10px] bg-gradient-to-br from-cyan-500 to-cyan-600 shadow-[0_4px_14px_rgba(6,182,212,0.25)] transition-all duration-250',
                            isVerified ? 'hover:-translate-y-0.5 hover:shadow-[0_8px_24px_rgba(6,182,212,0.35)] cursor-pointer' : 'opacity-50 cursor-not-allowed'
                        ]">
                        + New QR
                    </button>
                </div>
            </div>
        </section>

        <!-- QR Code Grid -->
        <section class="bg-gradient-to-b from-slate-50 to-white py-4 md:py-6">
            <div class="max-w-[min(92%,1200px)] mx-auto">

                <!-- Empty State -->
                <div v-if="!qrCodes.length && !showCreateForm" class="text-center py-8">
                    <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-700 mb-1">No QR codes yet</h3>
                    <p class="text-sm text-slate-400 mb-4">Create your first QR code to start tracking customer scans and earning commissions.</p>
                    <button @click="isVerified && (showCreateForm = true)"
                        :disabled="!isVerified"
                        :class="[
                            'inline-flex items-center gap-2 px-5 py-3 text-sm font-bold text-white rounded-xl bg-gradient-to-br from-[#153b4f] to-[#2ea7ad] shadow-[0_4px_14px_rgba(21,59,79,0.18)] transition-all',
                            isVerified ? 'hover:-translate-y-0.5 cursor-pointer' : 'opacity-50 cursor-not-allowed'
                        ]">
                        Create QR Code
                    </button>
                    <p v-if="!isVerified" class="text-xs text-amber-600 mt-2">Your account must be approved before you can create QR codes.</p>
                </div>

                <!-- QR Cards Grid -->
                <div v-if="qrCodes.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="qr in qrCodes" :key="qr.id"
                        class="bg-white border border-[rgba(15,23,42,0.07)] rounded-[20px] shadow-[0_1px_2px_rgba(21,59,79,0.03),0_8px_24px_rgba(21,59,79,0.06)] transition-all duration-400 hover:shadow-[0_1px_2px_rgba(21,59,79,0.03),0_12px_32px_rgba(21,59,79,0.08)] hover:border-[rgba(15,23,42,0.1)] overflow-hidden">

                        <!-- QR Preview Area -->
                        <div class="bg-gradient-to-br from-[#f0f8fc] to-[#e0f7fa] p-4 flex items-center justify-center relative rounded-t-[20px]">
                            <div class="w-[90px] h-[90px] bg-white rounded-xl shadow-[0_4px_16px_rgba(21,59,79,0.1)] grid grid-cols-7 gap-[1.5px] p-2.5">
                                <div v-for="(cell, ci) in getQrPattern(qr.id)" :key="ci"
                                    :class="['rounded-[1px]', cell ? 'bg-[#153b4f]' : 'bg-transparent']">
                                </div>
                            </div>
                            <div class="absolute top-2 right-2">
                                <span :class="getStatusBadgeClass(qr.status)"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[0.65rem] font-semibold uppercase tracking-wide">
                                    {{ qr.status || 'Active' }}
                                </span>
                            </div>
                        </div>

                        <!-- QR Card Body -->
                        <div class="p-4">
                            <h3 class="font-bold text-[0.9rem] text-[#153b4f] mb-0.5">{{ qr.label || qr.short_code }}</h3>
                            <div class="text-[0.75rem] text-slate-500 mb-3.5 flex items-center gap-1">
                                <span>&#x1F4CD;</span>
                                {{ qr.location ? [qr.location.name, qr.location.city].filter(Boolean).join(', ') : 'No location' }}
                            </div>

                            <!-- Mini Stats -->
                            <div class="grid grid-cols-3 gap-1 mb-3.5">
                                <div class="text-center py-1.5 bg-slate-50 rounded-lg">
                                    <div class="font-bold text-[0.88rem] text-[#153b4f]">{{ formatNumber(qr.customer_scans_count || 0) }}</div>
                                    <div class="text-[0.6rem] text-slate-400 uppercase tracking-wider">Scans</div>
                                </div>
                                <div class="text-center py-1.5 bg-slate-50 rounded-lg">
                                    <div class="font-bold text-[0.88rem] text-[#153b4f]">{{ formatNumber(qr.bookings_count || 0) }}</div>
                                    <div class="text-[0.6rem] text-slate-400 uppercase tracking-wider">Bookings</div>
                                </div>
                                <div class="text-center py-1.5 bg-slate-50 rounded-lg">
                                    <div class="font-bold text-[0.88rem] text-[#153b4f]">&euro;{{ formatNumber(qr.revenue || 0) }}</div>
                                    <div class="text-[0.6rem] text-slate-400 uppercase tracking-wider">Revenue</div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-1.5 pt-3 border-t border-slate-100">
                                <a v-if="qr.download_url" :href="qr.download_url" target="_blank"
                                    :download="(qr.label || qr.short_code) + '-qr.png'"
                                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 text-[0.8rem] font-bold text-[#153b4f] bg-transparent border-[1.5px] border-[rgba(15,23,42,0.12)] rounded-[10px] transition-all hover:border-[#153b4f] hover:bg-[rgba(21,59,79,0.03)]">
                                    &#x2B07; Download
                                </a>
                                <span v-else class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 text-[0.8rem] font-bold text-slate-300 border-[1.5px] border-slate-100 rounded-[10px]">
                                    &#x2B07; No Image
                                </span>
                                <button @click="toggleQrStats(qr.id)"
                                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 text-[0.8rem] font-bold text-[#153b4f] bg-transparent border-[1.5px] border-[rgba(15,23,42,0.12)] rounded-[10px] transition-all hover:border-[#153b4f] hover:bg-[rgba(21,59,79,0.03)]">
                                    &#x1F4CA; Details
                                </button>
                            </div>

                            <!-- Expanded Details -->
                            <div v-if="expandedQr === qr.id" class="mt-3 pt-3 border-t border-slate-100 text-[0.78rem] space-y-1.5">
                                <div class="flex justify-between"><span class="text-slate-400">Location</span><span class="text-[#153b4f] font-medium">{{ qr.location?.name || '-' }}</span></div>
                                <div class="flex justify-between"><span class="text-slate-400">Address</span><span class="text-[#153b4f] font-medium text-right max-w-[60%] truncate">{{ qr.location_address || '-' }}</span></div>
                                <div class="flex justify-between"><span class="text-slate-400">Short Code</span><span class="text-[#153b4f] font-mono font-medium">{{ qr.short_code }}</span></div>
                                <div class="flex justify-between"><span class="text-slate-400">Status</span><span class="font-medium" :class="qr.status === 'active' ? 'text-emerald-600' : 'text-red-500'">{{ qr.status || 'Active' }}</span></div>
                                <div class="flex justify-between"><span class="text-slate-400">Created</span><span class="text-[#153b4f] font-medium">{{ new Date(qr.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) }}</span></div>
                                <div v-if="qr.last_scanned_at" class="flex justify-between"><span class="text-slate-400">Last Scan</span><span class="text-[#153b4f] font-medium">{{ new Date(qr.last_scanned_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) }}</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Create QR Section -->
        <section v-if="showCreateForm && isVerified" class="bg-white pb-4">
            <div class="max-w-[min(92%,1200px)] mx-auto">
                <div class="mb-3">
                    <span class="text-[0.76rem] font-bold tracking-[0.12em] uppercase text-cyan-500">New QR Code</span>
                    <h2 class="text-lg font-[800] text-[#153b4f]">
                        Create a QR code for <span class="bg-gradient-to-br from-[#153b4f] to-[#2ea7ad] bg-clip-text text-transparent">a location.</span>
                    </h2>
                </div>

                <!-- Info Box -->
                <div class="max-w-[620px] mb-3 flex items-start gap-2.5 px-3.5 py-2.5 bg-sky-50 border border-sky-200 rounded-xl text-[0.78rem] text-sky-700">
                    <svg class="w-4 h-4 text-sky-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>Each QR code is linked to one location. Place it where customers can scan it (reception desk, menu, counter). When scanned, customers get a discount and you earn a commission on their booking.</span>
                </div>

                <div class="max-w-[620px] bg-white border border-[rgba(15,23,42,0.07)] rounded-[20px] shadow-[0_1px_2px_rgba(21,59,79,0.03),0_8px_24px_rgba(21,59,79,0.06)] p-5">
                    <!-- Location Picker -->
                    <div class="mb-3.5">
                        <label class="block text-[0.7rem] font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Select Location</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div v-for="loc in locations" :key="loc.id"
                                @click="selectedLocation = loc.id"
                                :class="[
                                    'p-3.5 border-[1.5px] rounded-xl cursor-pointer transition-all duration-250 bg-white',
                                    selectedLocation === loc.id
                                        ? 'border-[#2ea7ad] bg-emerald-50/50 shadow-[0_2px_8px_rgba(46,167,173,0.1)]'
                                        : 'border-slate-200 hover:border-[rgba(46,167,173,0.4)]'
                                ]">
                                <h4 class="font-semibold text-[0.82rem] text-[#153b4f]">{{ loc.name }}</h4>
                                <p class="text-[0.72rem] text-slate-500">{{ [loc.address_line_1, loc.city, loc.country].filter(Boolean).join(', ') || 'No address' }}</p>
                            </div>
                            <div @click="selectedLocation = 'new'"
                                :class="[
                                    'p-3.5 border-[1.5px] rounded-xl cursor-pointer transition-all duration-250 bg-white',
                                    selectedLocation === 'new'
                                        ? 'border-[#2ea7ad] bg-emerald-50/50 shadow-[0_2px_8px_rgba(46,167,173,0.1)]'
                                        : 'border-slate-200 hover:border-[rgba(46,167,173,0.4)]'
                                ]">
                                <h4 class="font-semibold text-[0.82rem] text-[#153b4f]">&#x2795; New Location</h4>
                                <p class="text-[0.72rem] text-slate-500">Add a new location</p>
                            </div>
                        </div>
                    </div>

                    <!-- Existing Location Summary -->
                    <div v-if="selectedExistingLocation" class="mb-3.5 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                        <div class="text-[0.7rem] font-semibold text-slate-500 uppercase tracking-wide mb-1">Location Details</div>
                        <div class="text-sm text-[#153b4f] font-medium">{{ selectedExistingLocation.name }}</div>
                        <div class="text-[0.78rem] text-slate-500 mt-0.5">{{ [selectedExistingLocation.address_line_1, selectedExistingLocation.city, selectedExistingLocation.state, selectedExistingLocation.country].filter(Boolean).join(', ') }}</div>
                        <div v-if="selectedExistingLocation.latitude" class="text-[0.68rem] text-slate-400 mt-0.5">{{ selectedExistingLocation.latitude }}, {{ selectedExistingLocation.longitude }}</div>
                    </div>

                    <!-- New Location: Google Places Search -->
                    <div v-if="selectedLocation === 'new'" class="mb-3.5 space-y-3">
                        <div>
                            <label class="block text-[0.7rem] font-semibold text-slate-500 uppercase tracking-wide mb-1">Location Name</label>
                            <input v-model="qrForm.location_name" type="text" placeholder="e.g. Hotel Lobby"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white" />
                            <p v-if="qrForm.errors.location_name" class="text-red-500 text-xs mt-1">{{ qrForm.errors.location_name }}</p>
                        </div>

                        <div>
                            <label class="block text-[0.7rem] font-semibold text-slate-500 uppercase tracking-wide mb-1">Search Address</label>
                            <input ref="placeInputRef" type="text" placeholder="Start typing an address..."
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white" />
                            <span class="text-[0.68rem] text-slate-400 mt-0.5 block">Powered by Google Places</span>
                        </div>

                        <!-- Auto-filled Address Fields -->
                        <div v-if="addressConfirmed" class="space-y-2.5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block text-[0.7rem] font-semibold text-slate-500 uppercase tracking-wide mb-1">Address</label>
                                    <input v-model="qrForm.address_line_1" type="text"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white" />
                                    <p v-if="qrForm.errors.address_line_1" class="text-red-500 text-xs mt-1">{{ qrForm.errors.address_line_1 }}</p>
                                </div>
                                <div>
                                    <label class="block text-[0.7rem] font-semibold text-slate-500 uppercase tracking-wide mb-1">City</label>
                                    <input v-model="qrForm.city" type="text"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white" />
                                    <p v-if="qrForm.errors.city" class="text-red-500 text-xs mt-1">{{ qrForm.errors.city }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                <div>
                                    <label class="block text-[0.7rem] font-semibold text-slate-500 uppercase tracking-wide mb-1">State</label>
                                    <input v-model="qrForm.state" type="text"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white" />
                                </div>
                                <div>
                                    <label class="block text-[0.7rem] font-semibold text-slate-500 uppercase tracking-wide mb-1">Country</label>
                                    <input v-model="qrForm.country" type="text"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white" />
                                    <p v-if="qrForm.errors.country" class="text-red-500 text-xs mt-1">{{ qrForm.errors.country }}</p>
                                </div>
                                <div>
                                    <label class="block text-[0.7rem] font-semibold text-slate-500 uppercase tracking-wide mb-1">Postal Code</label>
                                    <input v-model="qrForm.postal_code" type="text"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white" />
                                </div>
                            </div>

                            <!-- Leaflet Map -->
                            <div>
                                <label class="block text-[0.7rem] font-semibold text-slate-500 uppercase tracking-wide mb-1">Pin Location</label>
                                <div ref="mapContainerRef" class="w-full h-[200px] rounded-xl border border-slate-200 overflow-hidden z-0"></div>
                                <span class="text-[0.68rem] text-slate-400 mt-1 block">Drag the marker to adjust the exact position.</span>
                            </div>
                        </div>
                    </div>

                    <!-- QR Code Label -->
                    <div class="mb-3.5">
                        <label class="block text-[0.7rem] font-semibold text-slate-500 uppercase tracking-wide mb-1">QR Code Label</label>
                        <input v-model="qrForm.label" type="text" placeholder="e.g. Restaurant Terrace"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none transition-all hover:border-[#153b4f] focus:ring-2 focus:ring-[#2ea7ad] focus:border-transparent focus:bg-white" />
                        <p v-if="qrForm.errors.label" class="text-red-500 text-xs mt-1">{{ qrForm.errors.label }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2 mt-2">
                        <button @click="submitQrForm" :disabled="qrForm.processing"
                            class="inline-flex items-center justify-center gap-2 px-5 py-3 text-[0.88rem] font-bold text-white rounded-xl bg-gradient-to-br from-[#153b4f] to-[#2ea7ad] shadow-[0_4px_14px_rgba(21,59,79,0.18)] transition-all hover:-translate-y-0.5 hover:shadow-[0_8px_24px_rgba(21,59,79,0.22)] disabled:opacity-50">
                            <span v-if="qrForm.processing">Creating...</span>
                            <span v-else>Generate QR Code</span>
                        </button>
                        <button @click="showCreateForm = false"
                            class="inline-flex items-center justify-center gap-2 px-5 py-3 text-[0.88rem] font-bold text-[#153b4f] bg-transparent border-[1.5px] border-[rgba(15,23,42,0.12)] rounded-xl transition-all hover:border-[#153b4f] hover:bg-[rgba(21,59,79,0.03)]">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <Toaster position="bottom-right" />
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Toaster } from '@/Components/ui/sonner';
import AffiliateHeader from '@/Layouts/AffiliateHeader.vue';

import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const page = usePage();
const locale = computed(() => page.props.locale || 'en');
const isVerified = computed(() => page.props.affiliateVerificationStatus === 'verified');

const props = defineProps({
    business: Object,
    qrCodes: Array,
    locations: Array,
});

const showCreateForm = ref(false);
const selectedLocation = ref(null);
const expandedQr = ref(null);

function toggleQrStats(id) {
    expandedQr.value = expandedQr.value === id ? null : id;
}

const qrForm = useForm({
    label: '',
    location_id: null,
    location_name: '',
    address_line_1: '',
    city: '',
    state: '',
    country: '',
    postal_code: '',
    latitude: null,
    longitude: null,
});

// Google Places autocomplete
const placeInputRef = ref(null);
let autocompleteInstance = null;
const addressConfirmed = ref(false);

// Leaflet map
const mapContainerRef = ref(null);
let mapInstance = null;
let markerInstance = null;

const selectedExistingLocation = computed(() => {
    if (!selectedLocation.value || selectedLocation.value === 'new') return null;
    return props.locations?.find(l => l.id === selectedLocation.value) || null;
});

watch(selectedLocation, (val) => {
    if (val === 'new') {
        qrForm.location_id = 'new';
        qrForm.location_name = '';
        qrForm.address_line_1 = '';
        qrForm.city = '';
        qrForm.state = '';
        qrForm.country = '';
        qrForm.postal_code = '';
        qrForm.latitude = null;
        qrForm.longitude = null;
        addressConfirmed.value = false;
        nextTick(() => initAutocomplete());
    } else {
        qrForm.location_id = val;
        qrForm.location_name = '';
        addressConfirmed.value = false;
        destroyMap();
    }
});

function initAutocomplete() {
    if (!placeInputRef.value || autocompleteInstance) return;
    if (typeof google === 'undefined' || !google.maps?.places) return;

    autocompleteInstance = new google.maps.places.Autocomplete(placeInputRef.value, {
        fields: ['address_components', 'geometry', 'name', 'formatted_address'],
    });

    autocompleteInstance.addListener('place_changed', () => {
        const place = autocompleteInstance.getPlace();
        if (!place.geometry?.location) return;

        const lat = parseFloat(place.geometry.location.lat().toFixed(6));
        const lng = parseFloat(place.geometry.location.lng().toFixed(6));

        let streetNumber = '', routeName = '', locality = '', adminArea = '', countryName = '', postalCode = '';
        (place.address_components || []).forEach(c => {
            if (c.types.includes('street_number')) streetNumber = c.long_name;
            if (c.types.includes('route')) routeName = c.long_name;
            if (c.types.includes('locality')) locality = c.long_name;
            if (c.types.includes('postal_town') && !locality) locality = c.long_name;
            if (c.types.includes('administrative_area_level_1')) adminArea = c.long_name;
            if (c.types.includes('country')) countryName = c.long_name;
            if (c.types.includes('postal_code')) postalCode = c.long_name;
        });

        qrForm.address_line_1 = `${streetNumber} ${routeName}`.trim() || place.formatted_address || '';
        qrForm.city = locality || '';
        qrForm.state = adminArea || '';
        qrForm.country = countryName || '';
        qrForm.postal_code = postalCode || '';
        qrForm.latitude = lat;
        qrForm.longitude = lng;

        if (!qrForm.location_name) {
            qrForm.location_name = place.name || locality || qrForm.address_line_1;
        }

        addressConfirmed.value = true;
        nextTick(() => initMap(lat, lng));
    });
}

function initMap(lat, lng) {
    if (!mapContainerRef.value) return;

    if (mapInstance) {
        mapInstance.setView([lat, lng], 15);
        updateMarker(lat, lng);
        return;
    }

    mapInstance = L.map(mapContainerRef.value, { scrollWheelZoom: true, zoomControl: true })
        .setView([lat, lng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(mapInstance);

    updateMarker(lat, lng);
}

function updateMarker(lat, lng) {
    if (!mapInstance) return;
    if (markerInstance) {
        markerInstance.setLatLng([lat, lng]);
    } else {
        markerInstance = L.marker([lat, lng], { draggable: true }).addTo(mapInstance);
        markerInstance.on('dragend', async (e) => {
            const { lat: newLat, lng: newLng } = e.target.getLatLng();
            qrForm.latitude = parseFloat(newLat.toFixed(6));
            qrForm.longitude = parseFloat(newLng.toFixed(6));
            await reverseGeocode(qrForm.latitude, qrForm.longitude);
        });
    }
}

async function reverseGeocode(lat, lng) {
    if (typeof google === 'undefined' || !google.maps?.Geocoder) return;
    try {
        const geocoder = new google.maps.Geocoder();
        const result = await geocoder.geocode({ location: { lat, lng } });
        const geo = result?.results?.[0];
        if (!geo) return;

        let streetNumber = '', routeName = '', locality = '', adminArea = '', countryName = '', postalCode = '';
        (geo.address_components || []).forEach(c => {
            if (c.types.includes('street_number')) streetNumber = c.long_name;
            if (c.types.includes('route')) routeName = c.long_name;
            if (c.types.includes('locality')) locality = c.long_name;
            if (c.types.includes('postal_town') && !locality) locality = c.long_name;
            if (c.types.includes('administrative_area_level_1')) adminArea = c.long_name;
            if (c.types.includes('country')) countryName = c.long_name;
            if (c.types.includes('postal_code')) postalCode = c.long_name;
        });

        qrForm.address_line_1 = `${streetNumber} ${routeName}`.trim() || geo.formatted_address || '';
        qrForm.city = locality || '';
        qrForm.state = adminArea || '';
        qrForm.country = countryName || '';
        qrForm.postal_code = postalCode || '';
    } catch (e) {
        console.error('Reverse geocode error:', e);
    }
}

function destroyMap() {
    if (mapInstance) {
        mapInstance.remove();
        mapInstance = null;
        markerInstance = null;
    }
}

function destroyAutocomplete() {
    if (autocompleteInstance) {
        google.maps.event.clearInstanceListeners(autocompleteInstance);
        autocompleteInstance = null;
    }
}

onMounted(async () => {
    if (window.googleMapsReady) {
        await window.googleMapsReady;
        await google.maps.importLibrary('places');
    }
});

onUnmounted(() => {
    destroyMap();
    destroyAutocomplete();
});

const submitQrForm = () => {
    qrForm.post(route('affiliate.qr-codes.store', { locale: locale.value }), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateForm.value = false;
            qrForm.reset();
            selectedLocation.value = null;
            addressConfirmed.value = false;
            destroyMap();
            destroyAutocomplete();
            toast.success('QR code created successfully!');
        },
        onError: () => {
            toast.error('Failed to create QR code. Please check the form.');
        },
    });
};

function getQrPattern(id) {
    const base = [1,1,1,0,1,1,1,1,0,1,1,1,0,1,1,1,1,0,1,1,1,0,1,0,1,0,1,0,1,1,1,0,1,1,1,1,0,1,1,1,0,1,1,1,1,0,1,1,1];
    const seed = typeof id === 'number' ? id : 0;
    return base.map((v, i) => {
        const shifted = (i + seed * 3) % 3 === 0 ? !v : v;
        return shifted ? 1 : 0;
    });
}

function getStatusBadgeClass(status) {
    const s = (status || 'active').toLowerCase();
    const map = {
        active: 'bg-emerald-100 text-emerald-800',
        expiring: 'bg-amber-100 text-amber-800',
        expired: 'bg-red-100 text-red-800',
        revoked: 'bg-slate-100 text-slate-600',
    };
    return map[s] || 'bg-emerald-100 text-emerald-800';
}

function formatNumber(val) {
    const num = parseInt(val || 0);
    return num >= 1000 ? (num / 1000).toFixed(1) + 'k' : num.toString();
}
</script>

<style scoped>
@keyframes float {
    0%, 100% { transform: translateY(0) scale(1); }
    50% { transform: translateY(-14px) scale(1.04); }
}
.animate-float { animation: float 14s ease-in-out infinite; }
</style>
