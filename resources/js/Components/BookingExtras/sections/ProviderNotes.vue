<script setup>
const props = defineProps({
    isInternal: { type: Boolean, default: false },
    isRecordGo: { type: Boolean, default: false },
    vehicle: { type: Object, required: true },
    recordGoAutomaticComplements: { type: Array, default: () => [] },
    showProviderNotes: { type: Boolean, default: false },
})

const emit = defineEmits(['update:showProviderNotes'])

import { computed } from 'vue'

const notesVisible = computed({
    get: () => props.showProviderNotes,
    set: (v) => emit('update:showProviderNotes', v),
})
</script>

<template>
    <section class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden fade-in-up" style="animation-delay:0.55s">
        <button @click="notesVisible = !notesVisible" class="w-full flex items-center justify-between p-6 text-left hover:bg-gray-50/50 transition-colors">
            <h3 class="text-lg font-bold text-[#1e3a5f] flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Important Information
            </h3>
            <svg class="w-5 h-5 text-gray-400 transition-transform duration-300" :class="notesVisible ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div v-show="notesVisible" class="px-6 pb-6 space-y-3">
            <template v-if="isInternal">
                <div v-if="vehicle?.vendor?.profile || vehicle?.vendorProfile || vehicle?.vendor_profile" class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                    <p class="text-xs font-bold text-blue-700 uppercase tracking-wider mb-1">Vendor Information</p>
                    <p class="text-sm text-gray-700">{{ vehicle?.vendorProfileData?.company_name || vehicle?.vendor_profile_data?.company_name || vehicle?.vendor?.profile?.company_name || vehicle?.vendorProfile?.company_name || vehicle?.vendor_profile?.company_name || 'Vendor' }}{{ vehicle?.vendor?.profile?.about || vehicle?.vendorProfile?.about || vehicle?.vendor_profile?.about ? ' — ' + (vehicle?.vendor?.profile?.about || vehicle?.vendorProfile?.about || vehicle?.vendor_profile?.about) : '' }}</p>
                </div>
                <div v-if="vehicle?.guidelines" class="bg-amber-50 rounded-xl p-4 border border-amber-100">
                    <p class="text-xs font-bold text-amber-700 uppercase tracking-wider mb-1">Pick-up Guidelines</p>
                    <p class="text-sm text-gray-700 whitespace-pre-line">{{ vehicle?.guidelines }}</p>
                </div>
                <div v-if="vehicle?.terms_policy" class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                    <p class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Vendor Terms &amp; Conditions</p>
                    <p class="text-sm text-gray-700 whitespace-pre-line">{{ vehicle?.terms_policy }}</p>
                </div>
            </template>
            <template v-if="isRecordGo && recordGoAutomaticComplements.length > 0">
                <div class="bg-amber-50 rounded-xl p-4 border border-amber-100">
                    <p class="text-xs font-bold text-amber-700 uppercase tracking-wider mb-1">Automatic Charges</p>
                    <p class="text-sm text-gray-700">Some charges apply automatically based on booking conditions. See the Automatic Supplements section above for details.</p>
                </div>
            </template>
        </div>
    </section>
</template>
