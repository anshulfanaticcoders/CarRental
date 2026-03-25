<script setup>
defineProps({
    availablePackages: Array,
    selectedPackageType: String,
    isLocautoRent: Boolean,
    isAdobeCars: Boolean,
    isOkMobility: Boolean,
    locautoProtectionPlans: Array,
    selectedLocautoProtections: Array,
    formatRentalPrice: Function,
    formatPrice: Function,
    getPackageDisplayName: Function,
    getPackageSubtitle: Function,
    getBenefits: Function,
    getShortProtectionName: Function,
    isAdobeProtectionSelected: Function,
    numberOfDays: Number,
});

const emit = defineEmits(['select-package', 'toggle-adobe-protection', 'toggle-locauto-protection']);
</script>

<template>
    <!-- ═══ PACKAGE / RATE SELECTION ═══ -->
    <section v-if="!isLocautoRent && availablePackages.length > 0" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up" style="animation-delay:0.4s" id="extras-package-section">
        <h3 class="text-lg font-bold text-[#1e3a5f] mb-2 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            Choose Your Package
        </h3>
        <p class="text-sm text-gray-500 mb-5">Select the rental package that best fits your needs</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="pkg in availablePackages" :key="pkg.type"
                @click="isAdobeCars && pkg.isAddOn ? emit('toggle-adobe-protection', pkg) : emit('select-package', pkg.type)"
                class="plan-card rounded-2xl border-2 p-5 relative cursor-pointer"
                :class="(isAdobeCars && pkg.isAddOn ? isAdobeProtectionSelected(pkg) : selectedPackageType === pkg.type) ? 'selected' : 'border-gray-200 hover:border-gray-300 transition-colors'">
                <!-- Radio/Check at top-right -->
                <div class="absolute top-3 right-3">
                    <template v-if="isAdobeCars && pkg.isAddOn">
                        <div class="w-5 h-5 rounded border-2 flex items-center justify-center"
                            :class="isAdobeProtectionSelected(pkg) ? 'border-[#1e3a5f] bg-[#1e3a5f]' : 'border-gray-300'">
                            <svg v-if="isAdobeProtectionSelected(pkg)" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        </div>
                    </template>
                    <template v-else>
                        <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center"
                            :class="selectedPackageType === pkg.type ? 'border-[#1e3a5f]' : 'border-gray-300'">
                            <div v-if="selectedPackageType === pkg.type" class="w-2.5 h-2.5 rounded-full bg-[#1e3a5f] radio-dot"></div>
                        </div>
                    </template>
                </div>
                <!-- Popular badge -->
                <div v-if="pkg.type === 'PMP' || pkg.isBestValue" class="absolute top-3 left-4">
                    <span class="bg-gradient-to-r from-amber-400 to-amber-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full uppercase">{{ pkg.isBestValue ? 'Recommended' : 'Popular' }}</span>
                </div>
                <h4 class="text-base font-bold text-gray-900" :class="{'mt-4': pkg.type === 'PMP' || pkg.isBestValue}">{{ pkg.name || getPackageDisplayName(pkg.type) }}</h4>
                <p class="text-xs text-gray-500 mt-1">{{ pkg.subtitle || getPackageSubtitle(pkg.type) }}</p>
                <!-- Cover features (OKMobility style: included/excluded) -->
                <ul v-if="pkg.coverFeatures?.length" class="mt-3 space-y-1.5">
                    <li v-for="(feature, idx) in pkg.coverFeatures" :key="idx" class="flex items-center gap-2 text-xs" :class="feature.included ? 'text-gray-700' : 'text-gray-400'">
                        <svg v-if="feature.included" class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        <svg v-else class="w-3.5 h-3.5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span :class="{ 'line-through': !feature.included }">{{ feature.label }}</span>
                    </li>
                </ul>
                <!-- Standard benefits list -->
                <ul v-else-if="getBenefits(pkg).length || pkg.deposit || pkg.excess" class="mt-3 space-y-1.5">
                    <li v-for="(benefit, idx) in getBenefits(pkg)" :key="idx" class="flex items-center gap-2 text-xs text-gray-600">
                        <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        {{ benefit }}
                    </li>
                    <li v-if="pkg.deposit" class="flex items-center gap-2 text-xs text-gray-600">
                        <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        Deposit: {{ formatPrice(pkg.deposit) }}
                    </li>
                    <li v-if="pkg.excess" class="flex items-center gap-2 text-xs text-gray-600">
                        <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        Excess: {{ formatPrice(pkg.excess) }}
                    </li>
                </ul>
                <div class="mt-4 pt-3 border-t border-gray-200">
                    <span class="text-xl font-bold text-[#1e3a5f]">{{ formatRentalPrice(pkg.total) }}</span>
                    <span class="text-xs text-gray-500 ml-1">total</span>
                </div>
            </div>
        </div>
    </section>

    <!-- LocautoRent Protection Plans -->
    <section v-if="isLocautoRent && locautoProtectionPlans.length > 0" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up" id="extras-package-section">
        <h3 class="text-lg font-bold text-[#1e3a5f] mb-2 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            Protection Plans
        </h3>
        <p class="text-sm text-gray-500 mb-5">Add protection to reduce your liability — select as many as you need</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Protection Plans (multi-select) -->
            <div v-for="protection in locautoProtectionPlans" :key="protection.code"
                @click="emit('toggle-locauto-protection', protection.code)"
                class="plan-card rounded-2xl border-2 p-5 relative cursor-pointer"
                :class="selectedLocautoProtections.includes(protection.code) ? 'selected' : 'border-gray-200 hover:border-gray-300 transition-colors'">
                <div class="absolute top-3 right-3">
                    <div class="w-5 h-5 rounded-md border-2 flex items-center justify-center transition-all duration-200"
                        :class="selectedLocautoProtections.includes(protection.code) ? 'border-[#1e3a5f] bg-[#1e3a5f]' : 'border-gray-300'">
                        <svg v-if="selectedLocautoProtections.includes(protection.code)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                </div>
                <h4 class="text-base font-bold text-gray-900">{{ getShortProtectionName(protection.description) }}</h4>
                <p class="text-xs text-gray-500 mt-1">{{ protection.description }}</p>
                <div class="mt-4 pt-3 border-t border-gray-200 flex items-baseline gap-1">
                    <span class="text-lg font-bold text-[#1e3a5f]">{{ formatRentalPrice(protection.amount) }}</span>
                    <span class="text-xs text-gray-500">/day</span>
                    <span class="text-xs text-gray-400 ml-auto">{{ formatRentalPrice(protection.amount * numberOfDays) }} total</span>
                </div>
            </div>
        </div>
    </section>
</template>
