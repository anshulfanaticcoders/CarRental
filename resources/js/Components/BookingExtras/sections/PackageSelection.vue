<script setup>
import { BadgeCheck, Check, ShieldCheck, Sparkles, X } from 'lucide-vue-next';

const props = defineProps({
    availablePackages: Array,
    selectedPackageType: String,
    isLocautoRent: Boolean,
    isAdobeCars: Boolean,
    isOkMobility: Boolean,
    locautoProtectionPlans: Array,
    locautoProtectionOptions: Array,
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

const isPackageSelected = (pkg) => props.isAdobeCars && pkg.isAddOn
    ? props.isAdobeProtectionSelected(pkg)
    : props.selectedPackageType === pkg.type;

const packageBenefits = (pkg) => props.getBenefits(pkg).slice(0, 5);

const selectPackage = (pkg) => {
    if (props.isAdobeCars && pkg.isAddOn) {
        emit('toggle-adobe-protection', pkg);
        return;
    }

    emit('select-package', pkg.type);
};

const locautoSelected = (code) => (props.selectedLocautoProtections || []).includes(code);
const locautoOptions = () => props.locautoProtectionOptions?.length
    ? props.locautoProtectionOptions
    : (props.locautoProtectionPlans || []);
const isLocautoOptionSelected = (code) => code ? locautoSelected(code) : (props.selectedLocautoProtections || []).length === 0;
const selectLocautoProtection = (protection) => emit('toggle-locauto-protection', protection.code || null);
const locautoFeatureValue = (feature) => {
    if (feature.value === null || feature.value === undefined) return '';
    const amount = parseFloat(feature.value);
    if (!Number.isFinite(amount)) return feature.value;
    return `${feature.prefix || ''} ${props.formatPrice(amount)}`.trim();
};
const locautoActionLabel = (protection) => {
    if (isLocautoOptionSelected(protection.code)) return 'Current choice';
    if (protection.total_for_booking > 0) return `${props.formatRentalPrice(protection.total_for_booking)} total`;
    return 'Choose Basic';
};
</script>

<template>
    <section v-if="!isLocautoRent && availablePackages.length > 0" class="bg-white rounded-2xl border border-[#153b4f]/10 shadow-[0_18px_42px_rgba(21,59,79,0.08)] overflow-hidden fade-in-up" style="animation-delay:0.4s" id="extras-package-section">
        <div class="px-5 py-4 border-b border-[#153b4f]/10 bg-gradient-to-r from-[#f0f8fc] via-white to-[#f8fafc]">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.18em] text-[#0891b2]">
                        <Sparkles class="w-4 h-4" />
                        Rental package
                    </div>
                    <h3 class="mt-1 text-lg font-bold text-[#153b4f]">Choose your cover level</h3>
                </div>
                <span class="hidden sm:inline-flex rounded-full border border-[#153b4f]/15 bg-white px-3 py-1.5 text-xs font-semibold text-[#153b4f]">
                    {{ availablePackages.length }} options
                </span>
            </div>
        </div>

        <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
            <button v-for="pkg in availablePackages" :key="pkg.type" type="button"
                @click="selectPackage(pkg)"
                class="plan-card group text-left rounded-2xl border-2 p-5 relative"
                :class="isPackageSelected(pkg) ? 'selected' : 'border-slate-200 hover:border-[#2d5a8f]'">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 class="text-base font-bold text-slate-950">{{ pkg.name || getPackageDisplayName(pkg.type) }}</h4>
                            <span v-if="isPackageSelected(pkg)" class="inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-[#1e3a5f] to-[#2d5a8f] px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-white">
                                <Check class="w-3 h-3" />
                                Selected
                            </span>
                            <span v-else-if="pkg.type === 'PMP' || pkg.isBestValue" class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-amber-700">
                                <BadgeCheck class="w-3 h-3" />
                                {{ pkg.isBestValue ? 'Recommended' : 'Popular' }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">{{ pkg.subtitle || getPackageSubtitle(pkg.type) }}</p>
                    </div>
                    <div class="shrink-0 rounded-full border-2 p-1"
                        :class="isPackageSelected(pkg) ? 'border-[#2d5a8f] bg-gradient-to-r from-[#1e3a5f] to-[#2d5a8f]' : 'border-slate-300 bg-white group-hover:border-[#2d5a8f]'">
                        <Check v-if="isPackageSelected(pkg)" class="w-3.5 h-3.5 text-white" />
                        <div v-else class="w-3.5 h-3.5 rounded-full"></div>
                    </div>
                </div>

                <div v-if="pkg.deposit || pkg.excess || pkg.mileage" class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <div v-if="pkg.deposit" class="rounded-lg bg-slate-50 border border-slate-100 px-3 py-2">
                        <div class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Deposit</div>
                        <div class="text-xs font-semibold text-slate-800">{{ formatPrice(pkg.deposit) }}</div>
                    </div>
                    <div v-if="pkg.excess" class="rounded-lg bg-slate-50 border border-slate-100 px-3 py-2">
                        <div class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Excess</div>
                        <div class="text-xs font-semibold text-slate-800">{{ formatPrice(pkg.excess) }}</div>
                    </div>
                    <div v-if="pkg.mileage" class="rounded-lg bg-slate-50 border border-slate-100 px-3 py-2">
                        <div class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Mileage</div>
                        <div class="text-xs font-semibold text-slate-800">{{ pkg.mileage }}</div>
                    </div>
                </div>

                <ul v-if="pkg.coverFeatures?.length" class="mt-4 space-y-2">
                    <li v-for="(feature, idx) in pkg.coverFeatures.slice(0, 5)" :key="idx" class="flex items-start gap-2 text-sm" :class="feature.included ? 'text-slate-700' : 'text-slate-400'">
                        <Check v-if="feature.included" class="mt-0.5 w-4 h-4 text-emerald-500 shrink-0" />
                        <X v-else class="mt-0.5 w-4 h-4 text-slate-300 shrink-0" />
                        <span :class="{ 'line-through': !feature.included }">{{ feature.label }}</span>
                    </li>
                </ul>

                <ul v-else-if="packageBenefits(pkg).length" class="mt-4 space-y-2">
                    <li v-for="benefit in packageBenefits(pkg)" :key="benefit" class="flex items-start gap-2 text-sm text-slate-700">
                        <Check class="mt-0.5 w-4 h-4 text-emerald-500 shrink-0" />
                        <span>{{ benefit }}</span>
                    </li>
                </ul>

                <div class="mt-5 flex items-end justify-between gap-3 border-t border-slate-100 pt-4">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Rental total</div>
                        <div class="text-2xl font-bold text-[#153b4f]">{{ formatRentalPrice(pkg.total) }}</div>
                    </div>
                    <span class="rounded-full px-4 py-2 text-xs font-bold"
                        :class="isPackageSelected(pkg) ? 'bg-gradient-to-r from-[#1e3a5f] to-[#2d5a8f] text-white' : 'bg-[#f0f8fc] text-[#153b4f] group-hover:bg-[#dceef6]'">
                        {{ isPackageSelected(pkg) ? 'Current choice' : 'Choose package' }}
                    </span>
                </div>
            </button>
        </div>
    </section>

    <section v-if="isLocautoRent && locautoOptions().length > 0" class="bg-white rounded-2xl border border-[#153b4f]/10 shadow-[0_18px_42px_rgba(21,59,79,0.08)] overflow-hidden fade-in-up" id="extras-package-section">
        <div class="px-5 py-4 border-b border-[#153b4f]/10 bg-gradient-to-r from-[#f0f8fc] via-white to-[#f8fafc]">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.18em] text-[#0891b2]">
                        <ShieldCheck class="w-4 h-4" />
                        Protection levels
                    </div>
                    <h3 class="mt-1 text-lg font-bold text-[#153b4f]">Choose one protection level</h3>
                </div>
                <span class="hidden sm:inline-flex rounded-full border border-[#153b4f]/15 bg-white px-3 py-1.5 text-xs font-semibold text-[#153b4f]">
                    {{ locautoOptions().length }} options
                </span>
            </div>
        </div>

        <div class="p-5 grid grid-cols-1 lg:grid-cols-3 gap-4">
            <button v-for="protection in locautoOptions()" :key="protection.code || protection.id" type="button"
                @click="selectLocautoProtection(protection)"
                class="plan-card group text-left rounded-2xl border-2 p-5 relative flex flex-col min-h-[360px]"
                :class="isLocautoOptionSelected(protection.code) ? 'selected' : 'border-slate-200 hover:border-[#2d5a8f]'">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 class="text-base font-bold text-slate-950">{{ protection.title || getShortProtectionName(protection.description) }}</h4>
                            <span
                                v-if="protection.badge"
                                class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide"
                                :class="isLocautoOptionSelected(protection.code) ? 'bg-[#153b4f]/10 text-[#153b4f]' : 'bg-cyan-50 text-[#0891b2]'"
                            >
                                {{ protection.badge }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">{{ protection.description }}</p>
                    </div>
                    <div class="shrink-0 rounded-full border-2 p-1"
                        :class="isLocautoOptionSelected(protection.code) ? 'border-[#22d3ee] bg-gradient-to-r from-[#153b4f] to-[#0891b2]' : 'border-slate-300 bg-white group-hover:border-[#0891b2]'">
                        <Check v-if="isLocautoOptionSelected(protection.code)" class="w-3.5 h-3.5 text-white" />
                        <div v-else class="w-3.5 h-3.5 rounded-full"></div>
                    </div>
                </div>

                <ul v-if="protection.features?.length" class="mt-5 space-y-2.5 flex-1">
                    <li v-for="feature in protection.features" :key="feature.label" class="flex items-start justify-between gap-3 text-sm">
                        <span class="flex min-w-0 items-start gap-2" :class="feature.included ? 'text-slate-700' : 'text-slate-400'">
                            <Check v-if="feature.included" class="mt-0.5 w-4 h-4 text-emerald-500 shrink-0" />
                            <X v-else class="mt-0.5 w-4 h-4 text-slate-300 shrink-0" />
                            <span :class="{ 'line-through': !feature.included }">{{ feature.label }}</span>
                        </span>
                        <span v-if="locautoFeatureValue(feature)" class="shrink-0 text-right text-xs font-bold text-[#153b4f]">
                            {{ locautoFeatureValue(feature) }}
                        </span>
                    </li>
                </ul>

                <div class="mt-5 flex items-end justify-between gap-3 border-t border-slate-100 pt-4">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ protection.amount > 0 ? 'Daily price' : 'Included' }}</div>
                        <div class="text-xl font-bold text-[#153b4f]">
                            {{ formatRentalPrice(protection.amount || 0) }}<span v-if="protection.amount > 0" class="text-xs font-medium text-slate-500">/day</span>
                        </div>
                    </div>
                    <span
                        class="rounded-full px-3 py-1.5 text-xs font-bold"
                        :class="isLocautoOptionSelected(protection.code) ? 'bg-[#153b4f] text-white' : 'bg-[#f0f8fc] text-[#153b4f] group-hover:bg-[#dceef6]'"
                    >
                        {{ locautoActionLabel(protection) }}
                    </span>
                </div>
            </button>
        </div>
    </section>
</template>
