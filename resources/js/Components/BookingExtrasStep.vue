<script setup>
import { ref, computed, watch, watchEffect } from "vue";
import check from "../../assets/Check.svg";
// Additional Icons
import carIcon from "../../assets/carIcon.svg";
import fuelIcon from "../../assets/fuel.svg";
import transmissionIcon from "../../assets/transmittionIcon.svg";
import seatingIcon from "../../assets/travellerIcon.svg";
import doorIcon from "../../assets/door.svg";
import acIcon from "../../assets/ac.svg";
import {
    MapPin,
    Wifi,
    Baby,
    Snowflake,
    UserPlus,
    Shield,
    Plus,
    Navigation,
    CircleDashed,
    Smartphone,
    Gauge,
    Leaf
} from "lucide-vue-next";

// Check if vehicle is Adobe Cars
const isAdobeCars = computed(() => {
    return props.vehicle?.source === 'adobe';
});

// Check if vehicle is LocautoRent
const isLocautoRent = computed(() => {
    return props.vehicle?.source === 'locauto_rent';
});

const props = defineProps({
    vehicle: Object,
    initialPackage: String,
    initialProtectionCode: String, // For LocautoRent: selected protection code from car card
    optionalExtras: {
        type: Array,
        default: () => []
    },
    currencySymbol: {
        type: String,
        default: '€'
    },
    locationName: String,
    pickupLocation: String,
    dropoffLocation: String,
    locationInstructions: String,
    pickupDate: String,
    pickupTime: String,
    dropoffDate: String,
    dropoffTime: String,
    numberOfDays: {
        type: Number,
        default: 1
    },
    paymentPercentage: {
        type: Number,
        default: 0
    }
});

const emit = defineEmits(['back', 'proceed-to-checkout']);

const currentPackage = ref(props.initialPackage || 'BAS');

// (Forcing PLI removed as per user request to show Basic first)

const selectedExtras = ref({});
const selectedExtrasOrder = ref([]); // Track selection order for "First 2 Free" logic
const showDetailsModal = ref(false);

// Watch for changes to initialPackage prop
watch(() => props.initialPackage, (newPackage) => {
    currentPackage.value = newPackage || 'BAS';
});

const packageOrder = ['BAS', 'PLU', 'PRE', 'PMP'];

// (Moved above)


// Get LocautoRent protection plans from extras (all 7 plans)
const locautoProtectionPlans = computed(() => {
    if (!isLocautoRent.value) return [];
    const protectionCodes = ['136', '147', '145', '140', '146', '6', '43'];
    const extras = props.vehicle?.extras || [];
    return extras.filter(extra =>
        protectionCodes.includes(extra.code) && extra.amount > 0
    );
});

// Selected LocautoRent protection plan (null = Basic)
// Initialize from prop if passed from car card
const selectedLocautoProtection = ref(props.initialProtectionCode || null);

// Watch for changes to initialProtectionCode prop
watch(() => props.initialProtectionCode, (newCode) => {
    selectedLocautoProtection.value = newCode || null;
});

// LocautoRent: Smart Cover plan (code 147)
const locautoSmartCoverPlan = computed(() => {
    return locautoProtectionPlans.value.find(p => p.code === '147') || null;
});

// LocautoRent: Don't Worry plan (code 136)
const locautoDontWorryPlan = computed(() => {
    return locautoProtectionPlans.value.find(p => p.code === '136') || null;
});

// (Moved above)


// Adobe Cars Data
const adobeBaseRate = computed(() => {
    if (!isAdobeCars.value || !props.vehicle?.tdr) return 0;
    return parseFloat(props.vehicle.tdr);
});

const adobeProtectionPlans = computed(() => {
    if (!isAdobeCars.value) return [];
    const protections = [];
    if (props.vehicle.pli !== undefined) protections.push({ code: 'PLI', name: 'Liability Protection', amount: parseFloat(props.vehicle.pli), required: true });
    if (props.vehicle.ldw !== undefined) protections.push({ code: 'LDW', name: 'Car Protection', amount: parseFloat(props.vehicle.ldw), required: false });
    if (props.vehicle.spp !== undefined) protections.push({ code: 'SPP', name: 'Extended Protection', amount: parseFloat(props.vehicle.spp), required: false });
    return protections;
});

// Selected Adobe Cars protection plan (managed via currentPackage for simplicity in template or separate ref?)
// Ideally we re-use currentPackage to store the "code" (BAS, PLI, LDW, etc.)
// But we need to handle the total calculation difference.

const adobePackages = computed(() => {
    if (!isAdobeCars.value) return [];

    // 1. Basic Package (Base Rate)
    const packages = [{
        type: 'BAS',
        name: 'Basic Rate',
        subtitle: 'Base Rental',
        total: adobeBaseRate.value,
        deposit: 0,
        benefits: ['Base rental rate only', 'Liability Protection (PLI) added separately'],
        isBestValue: false
    }];

    // 2. Protection Plans as Packages (Exclude PLI as it's mandatory)
    adobeProtectionPlans.value.filter(p => p.code !== 'PLI').forEach(plan => {
        packages.push({
            type: plan.code,
            name: plan.name,
            subtitle: 'Protection Plan',
            total: adobeBaseRate.value + plan.amount, // Sum of Totals
            deposit: 0,
            benefits: [
                'Includes Liability Protection (PLI)',
                plan.required ? 'Mandatory - Required by law' : 'Optional Protection'
            ],
            isBestValue: plan.code === 'LDW' // Just an example, can be adjusted
        });
    });

    return packages;
});

const adobeMandatoryProtection = computed(() => {
    if (!isAdobeCars.value) return 0;
    const pli = adobeProtectionPlans.value.find(p => p.code === 'PLI');
    return pli ? pli.amount : 0;
});

// Adobe Cars: Optional Extras
const adobeOptionalExtras = computed(() => {
    if (!isAdobeCars.value) return [];
    const extras = props.vehicle?.extras || [];
    return extras.map(extra => ({
        id: `adobe_extra_${extra.code}`,
        code: extra.code,
        name: extra.name || extra.displayName || extra.description || extra.code,
        description: extra.description || extra.displayDescription || extra.name,
        // Price in API is Total.
        daily_rate: parseFloat(extra.price || extra.amount || 0) / props.numberOfDays,
        price: parseFloat(extra.price || extra.amount || 0),
        amount: extra.price || extra.amount
    }));
});

// LocautoRent: Optional extras (non-protection extras like GPS, child seat, etc.)
const locautoOptionalExtras = computed(() => {
    if (!isLocautoRent.value) return [];
    const protectionCodes = ['136', '147', '145', '140', '146', '6', '43'];
    const extras = props.vehicle?.extras || [];
    return extras.filter(extra =>
        !protectionCodes.includes(extra.code)
    ).map((extra, index) => ({
        id: `locauto_extra_${extra.code}`,
        code: extra.code,
        name: extra.description,
        description: extra.description,
        price: parseFloat(extra.amount) * props.numberOfDays,
        daily_rate: parseFloat(extra.amount),
        amount: extra.amount
    }));
});

const availablePackages = computed(() => {
    if (isAdobeCars.value) {
        return adobePackages.value;
    }
    if (!props.vehicle || !props.vehicle.products) return [];
    return packageOrder
        .map(type => props.vehicle.products.find(p => p.type === type))
        .filter(Boolean);
});

const currentProduct = computed(() => {
    if (isAdobeCars.value) {
        return adobePackages.value.find(p => p.type === currentPackage.value);
    }
    return availablePackages.value.find(p => p.type === currentPackage.value);
});

const formatPrice = (val) => {
    return `${props.currencySymbol}${parseFloat(val).toFixed(2)}`;
};

const getBenefits = (product) => {
    if (!product) return [];

    // Return pre-calculated benefits (e.g. for AdobeCars)
    if (product.benefits && Array.isArray(product.benefits)) return product.benefits;

    const benefits = [];
    const type = product.type;

    // Dynamic from API
    if (product.excess !== undefined && parseFloat(product.excess) === 0) {
        benefits.push('Glass and tyres covered');
    } else if (product.excess !== undefined) {
        benefits.push(`Excess: ${props.currencySymbol}${product.excess}`);
    }

    if (product.debitcard === 'Y') {
        benefits.push('Debit Card Accepted');
    }

    if (product.fuelpolicy === 'FF') {
        benefits.push('Free Fuel / Full to Full');
    } else if (product.fuelpolicy === 'SL') {
        benefits.push('Like for Like fuel policy');
    }

    if (product.costperextradistance !== undefined && parseFloat(product.costperextradistance) === 0) {
        benefits.push('Unlimited mileage');
    } else if (product.mileage && product.mileage !== 'Unlimited' && product.mileage !== 'unlimited') {
        benefits.push(`Mileage: ${product.mileage}`);
    } else if (product.mileage === 'Unlimited' || product.mileage === 'unlimited') {
        // Fallback if costperextradistance logic doesn't catch it but string says separate
        benefits.push('Unlimited mileage');
    }


    // Static based on type (only what's not in API)
    if (type === 'BAS') {
        benefits.push('Non-refundable');
        benefits.push('Non-amendable');
    }

    if (type === 'PMP') {
        benefits.push('Two free extras on collection');
    }

    if (type === 'PLU' || type === 'PRE' || type === 'PMP') {
        benefits.push('Cancellation in line with T&Cs');
    }

    return benefits;
};

const toggleExtra = (extra) => {
    const id = extra.id;
    if (selectedExtras.value[id]) {
        delete selectedExtras.value[id];
        selectedExtrasOrder.value = selectedExtrasOrder.value.filter(item => item !== id);
    } else {
        selectedExtras.value[id] = 1;
        selectedExtrasOrder.value.push(id);
    }
};

const isExtraFree = (extraId) => {
    if (currentPackage.value !== 'PMP') return false;
    // The first 2 items in the selection order are free
    const index = selectedExtrasOrder.value.indexOf(extraId);
    return index !== -1 && index < 2;
};

const extrasTotal = computed(() => {
    let total = 0;
    for (const [id, qty] of Object.entries(selectedExtras.value)) {
        // Find extra from the correct source
        let extra = null;
        if (isLocautoRent.value) {
            extra = locautoOptionalExtras.value.find(e => e.id === id);
        } else if (isAdobeCars.value) {
            extra = adobeOptionalExtras.value.find(e => e.id === id);
        } else {
            extra = props.optionalExtras.find(e => e.id === id);
        }

        if (extra) {
            if (isExtraFree(id)) {
                continue; // It's free
            }
            // Calculate total: daily_rate * days * quantity
            // Fallback to extra.price if daily_rate is missing (though it should be there)
            const dailyRate = extra.daily_rate !== undefined ? parseFloat(extra.daily_rate) : (parseFloat(extra.price) / props.numberOfDays);
            total += dailyRate * props.numberOfDays * qty;
        }
    }
    return total;
});

const grandTotal = computed(() => {
    if (isLocautoRent.value) {
        // For LocautoRent: base price + selected protection + extras
        const basePrice = parseFloat(props.vehicle?.total_price || 0);
        const protectionAmount = selectedLocautoProtection.value
            ? parseFloat(locautoProtectionPlans.value.find(p => p.code === selectedLocautoProtection.value)?.amount || 0) * props.numberOfDays
            : 0;
        return (basePrice + protectionAmount + extrasTotal.value).toFixed(2);
    }
    // For GreenMotion/USave and Adobe
    const pkgPrice = parseFloat(currentProduct.value?.total || 0);
    const mandatoryExtra = isAdobeCars.value ? adobeMandatoryProtection.value : 0;
    return (pkgPrice + mandatoryExtra + extrasTotal.value).toFixed(2);
});

const payableAmount = computed(() => {
    if (!props.paymentPercentage || props.paymentPercentage <= 0) return 0;
    return (parseFloat(grandTotal.value) * (props.paymentPercentage / 100)).toFixed(2);
});

const pendingAmount = computed(() => {
    return (parseFloat(grandTotal.value) - parseFloat(payableAmount.value)).toFixed(2);
});

const selectPackage = (pkgType) => {
    currentPackage.value = pkgType;
    // Re-evaluate free extras is automatic via computed/isExtraFree
};

const getSelectedExtrasDetails = computed(() => {
    const details = [];
    for (const [id, qty] of Object.entries(selectedExtras.value)) {
        // Find extra from the correct source
        let extra = null;
        if (isLocautoRent.value) {
            extra = locautoOptionalExtras.value.find(e => e.id === id);
        } else if (isAdobeCars.value) {
            extra = adobeOptionalExtras.value.find(e => e.id === id);
        } else {
            extra = props.optionalExtras.find(e => e.id === id);
        }

        if (extra) {
            const isFree = isExtraFree(id);
            const dailyRate = extra.daily_rate !== undefined ? parseFloat(extra.daily_rate) : (parseFloat(extra.price) / props.numberOfDays);
            const total = isFree ? 0 : (dailyRate * props.numberOfDays * qty);

            details.push({
                id: extra.id, // Good for key
                name: extra.name,
                qty,
                isFree,
                total
            });
        }
    }
    return details;
});

const getExtraIcon = (name) => {
    if (!name) return Plus;
    const lowerName = name.toLowerCase();

    if (lowerName.includes('gps') || lowerName.includes('nav') || lowerName.includes('sat')) return Navigation;
    if (lowerName.includes('mobile') || lowerName.includes('phone')) return Smartphone;
    if (lowerName.includes('wifi') || lowerName.includes('internet')) return Wifi;
    if (lowerName.includes('baby') || lowerName.includes('child') || lowerName.includes('booster') || lowerName.includes('infant')) return Baby;
    if (lowerName.includes('snow') || lowerName.includes('chain') || lowerName.includes('winter')) return Snowflake;
    if (lowerName.includes('driver') || lowerName.includes('additional')) return UserPlus;
    if (lowerName.includes('cover') || lowerName.includes('insurance') || lowerName.includes('protection') || lowerName.includes('waiver')) return Shield;
    if (lowerName.includes('tire') || lowerName.includes('tyre') || lowerName.includes('glass')) return CircleDashed;

    return Plus;
};

const vehicleSpecs = computed(() => {
    const v = props.vehicle;
    return {
        passengers: v.seating_capacity || v.passengers || v.adults,
        doors: v.doors,
        transmission: v.transmission, // 'Manual', 'Automatic'
        fuel: v.fuel, // 'Petrol', 'Diesel', etc.
        bagDisplay: (() => {
            // GreenMotion / USave: Return formatted string ONLY if non-zero total
            if (v.luggageLarge !== undefined || v.luggageMed !== undefined || v.luggageSmall !== undefined) {
                const small = parseInt(v.luggageSmall || 0);
                const med = parseInt(v.luggageMed || 0);
                const large = parseInt(v.luggageLarge || 0);
                if (small + med + large === 0) return null; // Don't show if all are 0
                return `S:${small} M:${med} L:${large}`;
            }
            // Wheelsys / External: Sum of bags
            if (v.bags !== undefined || v.suitcases !== undefined) {
                const total = (parseInt(v.bags) || 0) + (parseInt(v.suitcases) || 0);
                return total > 0 ? total : null;
            }
            // Locauto / Internal / Adobe -> Return count if valid
            if (v.luggage || v.luggage_capacity) {
                return v.luggage || v.luggage_capacity;
            }
            return null;
        })(),

        mpg: v.mpg,
        co2: v.co2,
        acriss: v.sipp_code || v.acriss_code || v.group_code || v.category,
        airConditioning: v.airConditioning === 'true' || v.airConditioning === true || (v.features && v.features.includes('Air Conditioning')),
    };
});

const isKeyBenefit = (text) => {
    if (!text) return false;
    const lower = text.toLowerCase();
    return lower.includes('excess') ||
        lower.includes('deposit') ||
        lower.includes('free') ||
        lower.includes('unlimited');
};

// Get short protection name for LocautoRent (extract English name from "English / Italian" format)
const getShortProtectionName = (description) => {
    if (!description) return '';
    // LocautoRent descriptions are like "Don't Worry" or "Roadside Plus / Assistenza Stradale"
    if (description.includes('/')) {
        return description.split('/')[0].trim();
    }
    return description;
};

// Get package display name
const getPackageDisplayName = (type) => {
    const names = {
        'BAS': 'Basic',
        'PLU': 'Plus',
        'PRE': 'Premium',
        'PMP': 'Premium Plus',
        // Adobe Codes
        'PLI': 'Liability Protection',
        'LDW': 'Car Protection',
        'SPP': 'Extended Protection'
    };
    return names[type] || type;
};

// Get package subtitle
const getPackageSubtitle = (type) => {
    const subtitles = {
        'BAS': 'Essential Cover',
        'PLU': 'Enhanced Cover',
        'PRE': 'Full Cover',
        'PMP': 'Ultimate Cover',
        // Adobe Codes
        'PLI': 'Essential Cover',
        'LDW': 'Standard Cover',
        'SPP': 'Maximum Cover'
    };
    return subtitles[type] || '';
};

// Get icon background class based on extra name
const getIconBackgroundClass = (name) => {
    if (!name) return 'icon-bg-gray';
    const lowerName = name.toLowerCase();

    if (lowerName.includes('gps') || lowerName.includes('nav')) return 'icon-bg-blue';
    if (lowerName.includes('baby') || lowerName.includes('child') || lowerName.includes('booster') || lowerName.includes('infant')) return 'icon-bg-pink';
    if (lowerName.includes('driver') || lowerName.includes('additional')) return 'icon-bg-green';
    if (lowerName.includes('wifi') || lowerName.includes('internet')) return 'icon-bg-purple';
    if (lowerName.includes('snow') || lowerName.includes('winter')) return 'icon-bg-blue';
    if (lowerName.includes('cover') || lowerName.includes('insurance') || lowerName.includes('protection')) return 'icon-bg-orange';

    return 'icon-bg-gray';
};

// Get icon color class based on extra name
const getIconColorClass = (name) => {
    if (!name) return 'icon-text-gray';
    const lowerName = name.toLowerCase();

    if (lowerName.includes('gps') || lowerName.includes('nav')) return 'icon-text-blue';
    if (lowerName.includes('baby') || lowerName.includes('child') || lowerName.includes('booster') || lowerName.includes('infant')) return 'icon-text-pink';
    if (lowerName.includes('driver') || lowerName.includes('additional')) return 'icon-text-green';
    if (lowerName.includes('wifi') || lowerName.includes('internet')) return 'icon-text-purple';
    if (lowerName.includes('snow') || lowerName.includes('winter')) return 'icon-text-blue';
    if (lowerName.includes('cover') || lowerName.includes('insurance') || lowerName.includes('protection')) return 'icon-text-orange';

    return 'icon-text-gray';
};
</script>

<template>
    <div class="flex flex-col lg:flex-row gap-8 p-4 md:p-6">
        <!-- Left Column: Upgrades & Extras -->
        <div class="flex-1 space-y-8">
            <!-- Location Instructions -->
            <div v-if="locationInstructions"
                class="info-card rounded-2xl p-6 flex items-start gap-4 shadow-lg relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-[#1e3a5f] to-[#2d5a8f]"></div>
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full -translate-y-32 translate-x-32">
                    </div>
                </div>
                <div
                    class="relative z-10 w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0 backdrop-blur-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="relative z-10 flex-1">
                    <h4 class="font-display text-xl font-bold mb-2 text-white">Pickup Instructions</h4>
                    <p class="text-sm text-white/90 leading-relaxed">{{ locationInstructions }}</p>
                </div>
            </div>

            <!-- 1. Package Upgrade Section -->
            <section>
                <!-- GreenMotion/USave: Package Selection -->
                <template v-if="!isLocautoRent">
                    <div class="mb-6">
                        <h2 class="font-display text-3xl font-bold text-gray-900 mb-2">Choose Your Package</h2>
                        <p class="text-gray-600">Select the perfect package for your journey</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div v-for="pkg in availablePackages" :key="pkg.type" @click="selectPackage(pkg.type)"
                            class="package-card bg-white rounded-2xl p-6 border-2 cursor-pointer transition-all relative"
                            :class="currentPackage === pkg.type ? 'selected border-[#1e3a5f] shadow-xl' : 'border-gray-200 hover:border-[#1e3a5f]/50 hover:shadow-lg'">
                            <!-- Popular Badge -->
                            <div v-if="pkg.type === 'PMP' || pkg.isBestValue"
                                class="absolute top-0 right-0 bg-gradient-to-r from-yellow-400 to-yellow-500 text-xs font-bold px-3 py-1 rounded-bl-xl rounded-tr-2xl text-white uppercase tracking-wide">
                                Popular
                            </div>

                            <!-- Header -->
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <span class="text-sm font-bold text-gray-900 block tracking-wide">{{
                                        getPackageDisplayName(pkg.type) }}</span>
                                    <span class="text-xs text-gray-400 uppercase tracking-wider">{{
                                        getPackageSubtitle(pkg.type) }}</span>
                                </div>
                                <div class="radio-custom" :class="{ selected: currentPackage === pkg.type }"></div>
                            </div>

                            <!-- Price -->
                            <div class="mb-4 pb-4 border-b border-gray-100">
                                <div class="flex items-baseline gap-1 mb-2">
                                    <span class="text-3xl font-bold"
                                        :class="currentPackage === pkg.type ? 'text-[#1e3a5f]' : 'text-gray-900'">
                                        {{ formatPrice(pkg.total / numberOfDays) }}
                                    </span>
                                    <span class="text-sm text-gray-500">/day</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Total:</span>
                                    <span class="text-lg font-bold"
                                        :class="currentPackage === pkg.type ? 'text-[#1e3a5f]' : 'text-gray-900'">
                                        {{ formatPrice(pkg.total) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Benefits List -->
                            <ul class="space-y-2.5">
                                <li v-for="(benefit, idx) in getBenefits(pkg)" :key="idx"
                                    class="benefit-item flex items-start gap-2.5 text-sm"
                                    :style="{ animationDelay: `${idx * 0.05}s` }">
                                    <img :src="check" class="w-4 h-4 mt-0.5 flex-shrink-0" alt="✓" />
                                    <span class="leading-snug"
                                        :class="isKeyBenefit(benefit) ? 'font-bold text-gray-900' : 'text-gray-600'">{{
                                            benefit }}</span>
                                </li>
                                <li v-if="pkg.deposit" class="benefit-item text-sm flex items-start gap-2.5">
                                    <img :src="check" class="w-4 h-4 mt-0.5 flex-shrink-0" alt="✓" />
                                    <span
                                        :class="isKeyBenefit('Deposit') ? 'font-bold text-gray-900' : 'text-gray-600'">Deposit:
                                        {{ formatPrice(pkg.deposit) }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </template>
            </section>

            <!-- LocautoRent: Protection Plans Section -->
            <section v-if="isLocautoRent && locautoProtectionPlans.length > 0">
                <div class="mb-6">
                    <h2 class="font-display text-3xl font-bold text-gray-900 mb-2">Choose Your Protection Plan</h2>
                    <p class="text-gray-600">Select the coverage that suits your needs</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Basic Plan -->
                    <div @click="selectedLocautoProtection = null"
                        class="package-card bg-white rounded-2xl p-6 border-2 cursor-pointer transition-all"
                        :class="!selectedLocautoProtection ? 'selected border-[#1e3a5f] shadow-xl' : 'border-gray-200 hover:border-[#1e3a5f]/50 hover:shadow-lg'">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <span class="text-sm font-bold text-gray-900 block tracking-wide">Basic</span>
                                <span class="text-xs text-gray-400 uppercase tracking-wider">Standard</span>
                            </div>
                            <div class="radio-custom" :class="{ selected: !selectedLocautoProtection }"></div>
                        </div>

                        <div class="mb-4 pb-4 border-b border-gray-100">
                            <div class="flex items-baseline gap-1 mb-2">
                                <span class="text-3xl font-bold"
                                    :class="!selectedLocautoProtection ? 'text-[#1e3a5f]' : 'text-gray-900'">
                                    {{ formatPrice(0) }}
                                </span>
                                <span class="text-sm text-gray-500">/day</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Total:</span>
                                <span class="text-lg font-bold"
                                    :class="!selectedLocautoProtection ? 'text-[#1e3a5f]' : 'text-gray-900'">
                                    {{ formatPrice(0) }}
                                </span>
                            </div>
                        </div>

                        <ul class="space-y-2.5">
                            <li class="benefit-item flex items-start gap-2.5 text-sm">
                                <img :src="check" class="w-4 h-4 mt-0.5 flex-shrink-0" alt="✓" />
                                <span class="leading-snug text-gray-600">Standard protection included</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Protection Plans -->
                    <div v-for="protection in locautoProtectionPlans" :key="protection.code"
                        @click="selectedLocautoProtection = protection.code"
                        class="package-card bg-white rounded-2xl p-6 border-2 cursor-pointer transition-all"
                        :class="selectedLocautoProtection === protection.code ? 'selected border-[#1e3a5f] shadow-xl' : 'border-gray-200 hover:border-[#1e3a5f]/50 hover:shadow-lg'">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <span class="text-sm font-bold text-gray-900 block tracking-wide">{{
                                    getShortProtectionName(protection.description) }}</span>
                                <span class="text-xs text-gray-400 uppercase tracking-wider">Protection</span>
                            </div>
                            <div class="radio-custom"
                                :class="{ selected: selectedLocautoProtection === protection.code }"></div>
                        </div>

                        <div class="mb-4 pb-4 border-b border-gray-100">
                            <div class="flex items-baseline gap-1 mb-2">
                                <span class="text-3xl font-bold"
                                    :class="selectedLocautoProtection === protection.code ? 'text-[#1e3a5f]' : 'text-gray-900'">
                                    {{ formatPrice(protection.amount) }}
                                </span>
                                <span class="text-sm text-gray-500">/day</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Total:</span>
                                <span class="text-lg font-bold"
                                    :class="selectedLocautoProtection === protection.code ? 'text-[#1e3a5f]' : 'text-gray-900'">
                                    {{ formatPrice(protection.amount * numberOfDays) }}
                                </span>
                            </div>
                        </div>

                        <ul class="space-y-2.5">
                            <li class="benefit-item flex items-start gap-2.5 text-sm">
                                <img :src="check" class="w-4 h-4 mt-0.5 flex-shrink-0" alt="✓" />
                                <span class="leading-snug text-gray-600">{{ protection.description }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- 2. Extras Section -->
            <section
                v-if="(optionalExtras && optionalExtras.length > 0) || (isLocautoRent && locautoOptionalExtras.length > 0) || (isAdobeCars && adobeOptionalExtras.length > 0)">
                <div class="mb-6">
                    <h2 class="font-display text-3xl font-bold text-gray-900 mb-2">Optional Extras</h2>
                    <p class="text-gray-600">Enhance your journey with these add-ons</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="extra in (isLocautoRent ? locautoOptionalExtras : (isAdobeCars ? adobeOptionalExtras : optionalExtras))"
                        :key="extra.id" @click="toggleExtra(extra)"
                        class="extra-card bg-white rounded-2xl p-4 border-2 cursor-pointer transition-all"
                        :class="{ 'selected border-[#1e3a5f] bg-gradient-to-br from-blue-50 to-blue-100': selectedExtras[extra.id], 'border-gray-200 hover:border-[#1e3a5f]/50 hover:shadow-lg': !selectedExtras[extra.id] }">
                        <div class="flex flex-col gap-3">
                            <!-- Top Row: Checkbox + Icon + Title -->
                            <div class="flex items-center gap-3">
                                <div class="checkbox-custom flex-shrink-0"
                                    :class="{ selected: !!selectedExtras[extra.id] }"></div>
                                <div class="w-11 h-11 rounded-full flex items-center justify-center flex-shrink-0"
                                    :class="getIconBackgroundClass(extra.name)">
                                    <component :is="getExtraIcon(extra.name)" class="w-5 h-5"
                                        :class="getIconColorClass(extra.name)" />
                                </div>
                                <h4 class="font-bold text-gray-900 text-[1rem] flex-1">{{ extra.name }}</h4>
                            </div>

                            <!-- Middle: Description (Removed as per request) -->
                            <!-- <p class="text-xs text-gray-500 pl-8">{{ extra.description }}</p> -->

                            <!-- Bottom: Price -->
                            <div class="flex justify-end pl-8 mt-auto">
                                <div v-if="isExtraFree(extra.id)" class="text-right">
                                    <span class="text-base font-bold text-green-600">Free</span>
                                    <span class="text-gray-400 line-through text-xs block">
                                        {{ formatPrice(extra.daily_rate !== undefined ? extra.daily_rate : (extra.price
                                            /
                                            numberOfDays)) }}/day
                                    </span>
                                </div>
                                <div v-else class="text-right">
                                    <span class="text-base font-bold text-gray-900">
                                        {{ formatPrice(extra.daily_rate !== undefined ? extra.daily_rate : (extra.price
                                            /
                                            numberOfDays)) }}
                                    </span>
                                    <span class="text-xs text-gray-500 block">/day</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Right Column: Sticky Summary -->
        <div class="lg:w-96 xl:w-[420px]">
            <div class="sticky-summary bg-white rounded-3xl shadow-2xl border border-gray-100 p-6">
                <h3 class="font-display text-2xl font-bold text-gray-900 mb-6 pb-4 border-b">Booking Summary</h3>

                <!-- Car Details -->
                <div class="flex flex-col gap-4 mb-6 pb-6 border-b border-gray-100">
                    <div class="flex items-start gap-4">
                        <div
                            class="w-28 h-20 bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl overflow-hidden flex-shrink-0">
                            <img v-if="vehicle.image" :src="vehicle.image" alt="Car"
                                class="w-full h-full object-cover" />
                            <svg v-else class="w-full h-full p-3 text-gray-400" viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="font-display font-bold text-gray-900 text-xl">{{ vehicle.brand }} {{
                                vehicle.model }}
                            </div>
                            <div class="text-sm text-gray-500 mb-3">{{ vehicle.category || 'Economy' }}</div>
                        </div>
                    </div>

                    <!-- Location Timeline -->
                    <div class="relative pl-4">
                        <!-- Animated Dotted Line -->
                        <div class="absolute left-[40px] top-10 bottom-10 w-0.5 flex flex-col">
                            <div class="flex-1 border-l-2 border-dotted border-gray-300"></div>
                            <div class="absolute top-0 left-0 w-full h-full overflow-hidden">
                                <div class="w-full h-2 animate-dashed-flow"></div>
                            </div>
                        </div>

                        <!-- Pickup -->
                        <div class="flex items-start gap-4 mb-8 relative">
                            <div class="relative flex-shrink-0">
                                <div class="w-12 h-12 bg-gradient-to-br from-emerald-50 to-teal-50 rounded-full flex items-center justify-center border-2 border-emerald-200 shadow-lg shadow-emerald-100/50 relative overflow-hidden ripple-icon"
                                    style="color: rgb(5, 150, 105);">
                                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-400/10 to-teal-400/10">
                                    </div>
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="w-6 h-6 text-emerald-600 relative z-10" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                            </div>

                            <div class="flex-1">
                                <span
                                    class="text-xs font-bold text-emerald-700 uppercase tracking-wider block mb-1">Pickup</span>
                                <div class="font-bold text-gray-900 text-sm md:text-base leading-snug">
                                    {{ pickupLocation || locationName }}
                                </div>
                                <div class="text-xs text-gray-500 mt-1.5 font-medium flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ pickupDate }}
                                    <span class="text-gray-300">|</span>
                                    {{ pickupTime }}
                                </div>
                            </div>
                        </div>

                        <!-- Dropoff -->
                        <div class="flex items-start gap-4 relative">
                            <div class="relative flex-shrink-0">
                                <div class="w-12 h-12 bg-gradient-to-br from-rose-50 to-pink-50 rounded-full flex items-center justify-center border-2 border-rose-200 shadow-lg shadow-rose-100/50 relative overflow-hidden ripple-icon"
                                    style="color: rgb(225, 29, 72);">
                                    <div class="absolute inset-0 bg-gradient-to-br from-rose-400/10 to-pink-400/10">
                                    </div>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-rose-600 relative z-10"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                            </div>

                            <div class="flex-1">
                                <span
                                    class="text-xs font-bold text-rose-700 uppercase tracking-wider block mb-1">Dropoff</span>
                                <div class="font-bold text-gray-900 text-sm md:text-base leading-snug">
                                    {{ dropoffLocation || pickupLocation || locationName }}
                                </div>
                                <div class="text-xs text-gray-500 mt-1.5 font-medium flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ dropoffDate }}
                                    <span class="text-gray-300">|</span>
                                    {{ dropoffTime }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Specs Icons -->
                <div class="flex flex-wrap gap-3 mb-6 pb-6 border-b border-gray-100">
                    <!-- Luggage -->
                    <div v-if="vehicleSpecs.bagDisplay"
                        class="spec-icon flex items-center gap-1.5 px-3 py-2 bg-gray-50 rounded-lg text-xs font-medium text-gray-700 transition-all hover:gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <span>{{ vehicleSpecs.bagDisplay }}</span>
                    </div>
                    <!-- Passengers -->
                    <div v-if="vehicleSpecs.passengers"
                        class="spec-icon flex items-center gap-1.5 px-3 py-2 bg-gray-50 rounded-lg text-xs font-medium text-gray-700 transition-all hover:gap-2">
                        <img :src="seatingIcon" class="w-4 h-4" alt="Seats" />
                        <span>{{ vehicleSpecs.passengers }}</span>
                    </div>
                    <!-- Transmission -->
                    <div v-if="vehicleSpecs.transmission"
                        class="spec-icon flex items-center gap-1.5 px-3 py-2 bg-gray-50 rounded-lg text-xs font-medium text-gray-700 transition-all hover:gap-2">
                        <img :src="transmissionIcon" class="w-4 h-4" alt="Trans" />
                        <span class="whitespace-nowrap">{{ vehicleSpecs.transmission }}</span>
                    </div>
                    <!-- Fuel -->
                    <div v-if="vehicleSpecs.fuel"
                        class="spec-icon flex items-center gap-1.5 px-3 py-2 bg-gray-50 rounded-lg text-xs font-medium text-gray-700 transition-all hover:gap-2">
                        <img :src="fuelIcon" class="w-4 h-4" alt="Fuel" />
                        <span>{{ vehicleSpecs.fuel }}</span>
                    </div>
                    <!-- AC -->
                    <div v-if="vehicleSpecs.airConditioning"
                        class="spec-icon flex items-center gap-1.5 px-3 py-2 bg-gray-50 rounded-lg text-xs font-medium text-gray-700 transition-all hover:gap-2">
                        <img :src="acIcon" class="w-4 h-4" alt="AC" />
                        <span>AC</span>
                    </div>
                    <!-- Doors -->
                    <div v-if="vehicleSpecs.doors"
                        class="spec-icon flex items-center gap-1.5 px-3 py-2 bg-gray-50 rounded-lg text-xs font-medium text-gray-700 transition-all hover:gap-2">
                        <img :src="doorIcon" class="w-4 h-4" alt="Doors" />
                        <span>{{ vehicleSpecs.doors }}</span>
                    </div>
                    <!-- Mileage -->
                    <div v-if="currentProduct?.mileage"
                        class="spec-icon flex items-center gap-1.5 px-3 py-2 bg-gray-50 rounded-lg text-xs font-medium text-gray-700 transition-all hover:gap-2">
                        <component :is="Gauge" class="w-4 h-4" />
                        <span>{{ currentProduct.mileage }}</span>
                    </div>
                    <!-- CO2 -->
                    <div v-if="vehicleSpecs.co2"
                        class="spec-icon flex items-center gap-1.5 px-3 py-2 bg-green-50 rounded-lg text-xs font-medium text-green-700 transition-all hover:gap-2">
                        <component :is="Leaf" class="w-4 h-4 text-green-600" />
                        <span>{{ vehicleSpecs.co2 }} g/km</span>
                    </div>
                </div>

                <!-- Price Breakdown -->
                <div class="space-y-3 text-sm text-gray-700 mb-6 pb-6 border-b border-gray-100">
                    <div class="flex justify-between">
                        <span>Car Package ({{ currentPackage }})</span>
                        <span class="font-medium">{{ formatPrice(currentProduct?.total || 0) }}</span>
                    </div>
                    <div v-if="isAdobeCars && adobeMandatoryProtection > 0" class="flex justify-between text-amber-600">
                        <span>Mandatory Liability (PLI)</span>
                        <span class="font-medium">+{{ formatPrice(adobeMandatoryProtection) }}</span>
                    </div>
                    <!-- Selected Extras List -->
                    <div v-for="item in getSelectedExtrasDetails" :key="item.id"
                        class="flex justify-between text-blue-600">
                        <span>{{ item.name }} <span v-if="item.qty > 1">x{{ item.qty }}</span></span>
                        <span class="font-medium">
                            <span v-if="item.isFree" class="text-green-600 font-bold">Free</span>
                            <span v-else>+{{ formatPrice(item.total) }}</span>
                        </span>
                    </div>
                </div>

                <!-- Total -->
                <div class="flex justify-between items-center border-t pt-4 mb-3">
                    <span class="text-lg font-bold text-gray-800">Total</span>
                    <span class="text-3xl font-bold text-[#1e3a5f]">{{ formatPrice(grandTotal) }}</span>
                </div>

                <!-- Payable Amount -->
                <div v-if="paymentPercentage > 0"
                    class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 rounded-2xl p-4 mb-3">
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-green-800">Pay Now ({{ paymentPercentage }}%)</span>
                        <span class="text-2xl font-bold text-green-700">{{ formatPrice(payableAmount) }}</span>
                    </div>
                </div>

                <!-- Running Text -->
                <div v-if="paymentPercentage > 0" class="overflow-hidden rounded-xl mb-6"
                    style="background: linear-gradient(135deg, #1e3a5f 0%, #2d5a8f 100%);">
                    <p class="py-3 whitespace-nowrap marquee-text text-sm font-medium text-white">
                        💳 Pay {{ paymentPercentage }}% now and rest pay on arrival &nbsp;•&nbsp; 💳 Pay {{
                            paymentPercentage
                        }}% now and rest pay on arrival &nbsp;•&nbsp; 💳 Pay {{ paymentPercentage }}% now and rest pay
                        on
                        arrival &nbsp;•&nbsp;
                    </p>
                </div>

                <!-- View Booking Details Button -->
                <button v-if="paymentPercentage > 0" @click="showDetailsModal = true"
                    class="w-full text-sm py-3 mb-4 rounded-xl border-2 font-semibold transition-all flex items-center justify-center gap-2 border-[#1e3a5f] text-[#1e3a5f] hover:bg-[#1e3a5f]/10">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    View Booking Details
                </button>
                <div v-else class="mb-6"></div>

                <!-- Action Buttons -->
                <div class="space-y-3">
                    <button @click="$emit('proceed-to-checkout', {
                        package: isLocautoRent ? (selectedLocautoProtection ? 'POA' : 'BAS') : currentPackage,
                        protection_code: isLocautoRent ? selectedLocautoProtection : null,
                        protection_amount: isLocautoRent && selectedLocautoProtection
                            ? locautoProtectionPlans.find(p => p.code === selectedLocautoProtection)?.amount || 0
                            : 0,
                        extras: selectedExtras,
                        detailedExtras: getSelectedExtrasDetails,
                        totals: {
                            grandTotal,
                            payableAmount,
                            pendingAmount
                        }
                    })" class="btn-primary w-full py-4 rounded-xl text-white font-bold text-lg shadow-lg">
                        Proceed to Booking
                    </button>
                    <button @click="$emit('back')"
                        class="btn-secondary w-full py-3 rounded-xl border-2 border-gray-300 text-gray-700 font-semibold hover:bg-gray-50 transition-all">
                        Back to Results
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Details Modal -->
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="showDetailsModal" class="fixed inset-0 z-[100000] flex items-center justify-center p-4"
                @click.self="showDetailsModal = false">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
                <!-- Modal Content -->
                <div
                    class="modal-content relative bg-white rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                    <!-- Header -->
                    <div
                        class="sticky top-0 bg-white border-b px-6 py-5 flex justify-between items-center rounded-t-3xl z-10">
                        <h2 class="font-display text-2xl font-bold text-gray-900">Booking Details</h2>
                        <button @click="showDetailsModal = false"
                            class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-6 space-y-5">
                        <!-- Vehicle Info -->
                        <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-2xl p-5">
                            <p class="text-sm text-gray-500 mb-2">Vehicle</p>
                            <p class="font-bold text-gray-900 text-lg">{{ vehicle.brand }} {{ vehicle.model }}</p>
                            <p class="text-sm text-gray-500 mt-1">{{ currentPackage }} Package</p>
                        </div>

                        <!-- Line Items -->
                        <div class="space-y-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Car Package ({{ currentPackage }})</span>
                                <span class="font-semibold text-gray-900">{{ formatPrice(currentProduct?.total || 0)
                                    }}</span>
                            </div>
                            <div v-if="isAdobeCars && adobeMandatoryProtection > 0"
                                class="flex justify-between text-sm">
                                <span class="text-amber-600">Mandatory Liability (PLI)</span>
                                <span class="font-semibold text-amber-600">+{{ formatPrice(adobeMandatoryProtection)
                                    }}</span>
                            </div>
                            <div v-for="item in getSelectedExtrasDetails" :key="item.id"
                                class="flex justify-between text-sm">
                                <span class="text-gray-600">{{ item.name }} <span v-if="item.qty > 1"
                                        class="text-xs text-gray-400">x{{ item.qty }}</span></span>
                                <span class="font-semibold" :class="item.isFree ? 'text-green-600' : 'text-gray-800'">
                                    {{ item.isFree ? 'FREE' : formatPrice(item.total) }}
                                </span>
                            </div>
                        </div>

                        <!-- Divider -->
                        <hr class="border-gray-200">

                        <!-- Totals -->
                        <div class="space-y-3">
                            <div class="flex justify-between text-lg">
                                <span class="font-bold text-gray-800">Grand Total</span>
                                <span class="font-bold text-[#1e3a5f]">{{ formatPrice(grandTotal) }}</span>
                            </div>
                            <div class="flex justify-between text-sm bg-green-50 p-4 rounded-xl">
                                <span class="font-semibold text-green-700">Pay Now ({{ paymentPercentage }}%)</span>
                                <span class="font-bold text-green-700">{{ formatPrice(payableAmount) }}</span>
                            </div>
                            <div class="flex justify-between text-sm bg-amber-50 p-4 rounded-xl">
                                <span class="font-semibold text-amber-700">Pay on Arrival</span>
                                <span class="font-bold text-amber-700">{{ formatPrice(pendingAmount) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="sticky bottom-0 bg-white border-t px-6 py-5 rounded-b-3xl">
                        <button @click="showDetailsModal = false"
                            class="btn-primary w-full py-4 rounded-xl text-white font-bold shadow-lg">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');

.font-display {
    font-family: 'Outfit', sans-serif;
}

/* Package Card Styles */
.package-card {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.package-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: #1e3a5f;
    transform: scaleX(0);
    transition: transform 0.4s ease;
}

.package-card:hover::before {
    transform: scaleX(1);
}

.package-card.selected::before {
    transform: scaleX(1);
}

/* Radio Button Custom */
.radio-custom {
    width: 24px;
    height: 24px;
    border: 2px solid #d1d5db;
    border-radius: 50%;
    position: relative;
    cursor: pointer;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.radio-custom.selected {
    border-color: #1e3a5f;
    background: #1e3a5f;
}

.radio-custom.selected::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 10px;
    height: 10px;
    background: white;
    border-radius: 50%;
    animation: radioPop 0.3s ease;
}

@keyframes radioPop {
    0% {
        transform: translate(-50%, -50%) scale(0);
    }

    50% {
        transform: translate(-50%, -50%) scale(1.2);
    }

    100% {
        transform: translate(-50%, -50%) scale(1);
    }
}


/* Marquee Animation */
@keyframes marquee {
    0% {
        transform: translateX(0);
    }

    100% {
        transform: translateX(-50%);
    }
}

.marquee-text {
    animation: marquee 20s linear infinite;
    display: inline-block;
    padding-left: 100%;
}

/* Sticky Summary */
.sticky-summary {
    position: sticky;
    top: 2rem;
    transition: all 0.3s ease;
}

/* Button Animations */
.btn-primary {
    background: linear-gradient(135deg, #1e3a5f 0%, #2d5a8f 100%);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn-primary::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.btn-primary:hover::before {
    left: 100%;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(30, 58, 95, 0.3);
}

.btn-secondary:hover {
    transform: translateY(-1px);
    background: #f3f4f6;
}

/* Benefit Item Animation */
.benefit-item {
    opacity: 0;
    animation: fadeInUp 0.4s ease forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Info Card */
.info-card {
    position: relative;
    overflow: hidden;
}

.info-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
    animation: float 6s ease-in-out infinite;
}

@keyframes float {

    0%,
    100% {
        transform: translate(0, 0);
    }

    50% {
        transform: translate(-10%, -10%);
    }
}

/* Spec Icons */
.spec-icon {
    transition: all 0.3s ease;
}

.spec-icon:hover {
    transform: scale(1.05);
    background: #f0f9ff;
}

/* Extra Card */
.extra-card {
    transition: all 0.3s ease;
}

.extra-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

/* Checkbox Custom */
.checkbox-custom {
    width: 20px;
    height: 20px;
    border: 2px solid #d1d5db;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    flex-shrink: 0;
}

.checkbox-custom.selected {
    background: #1e3a5f;
    border-color: #1e3a5f;
}

.checkbox-custom.selected::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 14px;
    font-weight: bold;
}

/* Modal Transitions */
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-active .modal-content,
.modal-leave-active .modal-content {
    transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from .modal-content,
.modal-leave-to .modal-content {
    opacity: 0;
    transform: scale(0.95) translateY(10px);
}

.modal-enter-to .modal-content,
.modal-leave-from .modal-content {
    opacity: 1;
    transform: scale(1) translateY(0);
}

/* Icon Color Helper Classes */
.icon-bg-blue {
    background: linear-gradient(135deg, rgb(219 234 254) 0%, rgb(191 219 254) 100%);
}

.icon-text-blue {
    color: rgb(37 99 235);
}

.icon-bg-pink {
    background: linear-gradient(135deg, rgb(252 231 243) 0%, rgb(251 207 232) 100%);
}

.icon-text-pink {
    color: rgb(219 39 119);
}

.icon-bg-green {
    background: linear-gradient(135deg, rgb(220 252 231) 0%, rgb(187 247 208) 100%);
}

.icon-text-green {
    color: rgb(22 163 74);
}

.icon-bg-purple {
    background: linear-gradient(135deg, rgb(243 232 255) 0%, rgb(233 213 255) 100%);
}

.icon-text-purple {
    color: rgb(147 51 234);
}

.icon-bg-orange {
    background: linear-gradient(135deg, rgb(255 237 213) 0%, rgb(254 215 170) 100%);
}

.icon-text-orange {
    color: rgb(249 115 22);
}

.icon-bg-gray {
    background: linear-gradient(135deg, rgb(243 244 246) 0%, rgb(229 231 235) 100%);
}

.icon-text-gray {
    color: rgb(71 85 105);
}

/* Dashed Line Flow Animation */
@keyframes dashed-flow {
    0% {
        transform: translateY(-100%);
        opacity: 0;
    }

    10% {
        opacity: 1;
    }

    90% {
        opacity: 1;
    }

    100% {
        transform: translateY(500%);
        opacity: 0;
    }
}

.animate-dashed-flow {
    position: relative;
}

.animate-dashed-flow::before {
    content: '';
    position: absolute;
    left: 0;
    width: 100%;
    height: 2px;
    background: linear-gradient(180deg, transparent, #1e3a5f, transparent);
    animation: dashed-flow 2s ease-in-out infinite;
}

/* Ripple Effect */
@keyframes ripple {
    0% {
        transform: scale(1);
        opacity: 0.6;
    }

    50% {
        transform: scale(1.3);
        opacity: 0.3;
    }

    100% {
        transform: scale(1.6);
        opacity: 0;
    }
}

.ripple-icon {
    position: relative;
}

.ripple-icon::after {
    content: '';
    position: absolute;
    inset: -4px;
    border-radius: inherit;
    border: 2px solid currentColor;
    animation: ripple 2s ease-out infinite;
}

.ripple-icon::before {
    content: '';
    position: absolute;
    inset: -4px;
    border-radius: inherit;
    border: 2px solid currentColor;
    animation: ripple 2s ease-out infinite;
    animation-delay: 1s;
}
</style>
