<script setup>
import { ref, computed } from "vue";
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

const props = defineProps({
    vehicle: Object,
    initialPackage: String,
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
const selectedExtras = ref({});
const selectedExtrasOrder = ref([]); // Track selection order for "First 2 Free" logic
const showDetailsModal = ref(false);

const packageOrder = ['BAS', 'PLU', 'PRE', 'PMP'];

const availablePackages = computed(() => {
    if (!props.vehicle || !props.vehicle.products) return [];
    return packageOrder
        .map(type => props.vehicle.products.find(p => p.type === type))
        .filter(Boolean);
});

const currentProduct = computed(() => {
    return availablePackages.value.find(p => p.type === currentPackage.value);
});

const formatPrice = (val) => {
    return `${props.currencySymbol}${parseFloat(val).toFixed(2)}`;
};

const getBenefits = (product) => {
    if (!product) return [];
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
        const extra = props.optionalExtras.find(e => e.id === id);
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
    const pkgPrice = parseFloat(currentProduct.value?.total || 0);
    return (pkgPrice + extrasTotal.value).toFixed(2);
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
        const extra = props.optionalExtras.find(e => e.id === id);
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
                const total = (parseInt(v.bags)||0) + (parseInt(v.suitcases)||0);
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
</script>

<template>
    <div class="flex flex-col md:flex-row gap-6 p-4">
        <!-- Left Column: Upgrades & Extras -->
        <div class="flex-1 space-y-8">
            <!-- Location Instructions -->
            <div v-if="locationInstructions" class="bg-customPrimaryColor rounded-lg p-4 flex items-start gap-3 shadow-sm">
                 <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <h4 class="font-bold text-white text-base mb-1">Pickup Instructions</h4>
                    <p class="text-sm text-white/95 leading-relaxed">{{ locationInstructions }}</p>
                </div>
            </div>

            <!-- 1. Package Upgrade Section -->
            <section>
                <h3 class="text-xl font-bold text-gray-800 mb-4">Choose Your Package</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div
                        v-for="pkg in availablePackages"
                        :key="pkg.type"
                        @click="selectPackage(pkg.type)"
                        class="border rounded-lg p-3 cursor-pointer transition-all flex flex-col relative bg-white hover:shadow-lg"
                        :class="currentPackage === pkg.type ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-500' : 'border-gray-200 hover:border-blue-300'"
                    >
                        <!-- Header -->
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <span class="text-sm font-bold text-gray-800 block">{{ pkg.type === 'PMP' ? 'Premium Plus' : (pkg.type === 'BAS' ? 'Basic' : (pkg.type === 'PLU' ? 'Plus' : (pkg.type === 'PRE' ? 'Premium' : pkg.type))) }}</span>
                                <span class="text-xs text-gray-500">{{ pkg.type }}</span>
                            </div>
                            <div v-if="currentPackage === pkg.type" class="bg-blue-500 text-white rounded-full w-5 h-5 flex items-center justify-center flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>

                        <!-- Price -->
                        <div class="mb-2 pb-2 border-b border-gray-100/50">
                            <div class="text-xl font-bold text-customPrimaryColor leading-tight">
                                {{ formatPrice(pkg.total / numberOfDays) }}
                                <span class="text-xs font-normal text-gray-500">/day</span>
                            </div>
                            <div class="text-sm font-semibold text-gray-500 mt-2 pt-2 border-t border-gray-100 flex justify-between items-center">
                                <span>Total:</span>
                                <span class="text-gray-900 text-base font-bold">{{ formatPrice(pkg.total) }}</span>
                            </div>
                        </div>

                        <!-- Benefits List -->
                        <ul class="space-y-2 mt-2 flex-1">
                            <li v-for="(benefit, idx) in getBenefits(pkg)" :key="idx" class="flex items-start gap-2.5 text-sm">
                                 <img :src="check" class="w-4 h-4 mt-0.5 flex-shrink-0 opacity-70" alt="✓" />
                                 <span 
                                    class="leading-tight"
                                    :class="isKeyBenefit(benefit) ? 'font-bold text-gray-900' : 'text-gray-600 font-medium'"
                                 >{{ benefit }}</span>
                            </li>
                             <li v-if="pkg.deposit" class="text-sm flex items-start gap-2.5">
                                <img :src="check" class="w-4 h-4 mt-0.5 flex-shrink-0 opacity-70" alt="✓" />
                                <span :class="isKeyBenefit('Deposit') ? 'font-bold text-gray-900' : 'text-gray-500'">Deposit: {{ formatPrice(pkg.deposit) }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- 2. Extras Section -->
            <section v-if="optionalExtras && optionalExtras.length > 0">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Optional Extras</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="extra in optionalExtras"
                        :key="extra.id"
                        class="border rounded-lg p-4 flex flex-col justify-between h-full bg-white transition-all shadow-sm hover:shadow-md"
                        :class="{'border-blue-500 ring-1 ring-blue-500 bg-blue-50': selectedExtras[extra.id]}"
                    >
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <div class="flex items-start gap-3">
                                <div class="p-2 bg-gray-50 rounded-lg text-customPrimaryColor">
                                    <component :is="getExtraIcon(extra.name)" class="w-5 h-5" />
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800 text-sm mb-1 leading-snug">{{ extra.name }}</h4>
                                    <p class="text-xs text-gray-500 line-clamp-2">{{ extra.description }}</p>
                                </div>
                            </div>
                            <input
                                type="checkbox"
                                :checked="!!selectedExtras[extra.id]"
                                @change="toggleExtra(extra)"
                                class="mt-0.5 w-5 h-5 text-customPrimaryColor rounded focus:ring-customPrimaryColor flex-shrink-0 cursor-pointer"
                            />
                        </div>
                        <div class="font-bold text-gray-700 text-sm text-right mt-auto">
                           <span v-if="isExtraFree(extra.id)" class="text-green-600">
                                Free
                                <span class="text-gray-400 line-through text-xs block font-normal">
                                    {{ formatPrice(extra.daily_rate !== undefined ? extra.daily_rate : (extra.price / numberOfDays)) }}/day
                                </span>
                           </span>
                           <span v-else>
                                {{ formatPrice(extra.daily_rate !== undefined ? extra.daily_rate : (extra.price / numberOfDays)) }}<span class="text-xs font-normal text-gray-500">/day</span>
                           </span>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Right Column: Sticky Summary -->
        <div class="md:w-1/3">
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 sticky top-4">
                <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b">Booking Summary</h3>

                <!-- Car Details -->
                <div class="flex flex-col gap-4 mb-6 pb-4 border-b border-gray-100">
                    <div class="flex items-start gap-4">
                        <img :src="vehicle.image" alt="Car" class="w-24 h-16 object-cover rounded bg-gray-50" />
                        <div class="flex-1">
                            <div class="font-bold text-gray-800 text-lg">{{ vehicle.brand }} {{ vehicle.model }}</div>
                            <div class="text-sm text-gray-500 mb-2">{{ vehicle.category }}</div>
                            
                            <!-- Location Info -->
                            <div class="mt-4 relative pb-2">
                                <!-- Animated Connector Line -->
                                <div class="absolute left-4 top-8 bottom-8 w-0.5 bg-gray-100 overflow-hidden z-0">
                                    <div class="w-full bg-gradient-to-b from-green-400 to-red-400 animate-flow-line h-full origin-top"></div>
                                </div>

                                <!-- Pickup -->
                                <div class="flex items-start gap-4 relative z-10 mb-8">
                                    <!-- Icon with Ripple -->
                                    <div class="relative flex-shrink-0 mt-1">
                                         <div class="absolute inset-0 bg-green-500 rounded-full animate-ping opacity-20"></div>
                                         <div class="relative w-8 h-8 bg-green-100 rounded-full flex items-center justify-center border border-green-200 shadow-sm animate-ripple-green">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                         </div>
                                    </div>
                                    
                                    <div class="flex-1">
                                        <span class="text-xs text-green-700 font-bold uppercase tracking-wider block mb-0.5">Pickup</span>
                                        <div class="font-bold text-gray-900 text-sm md:text-base leading-snug">
                                            {{ pickupLocation || locationName }}
                                        </div>
                                        <div class="text-xs text-gray-500 mt-0.5 font-medium flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            {{ pickupDate }} 
                                            <span v-if="pickupTime" class="ml-1 text-gray-400">|</span> 
                                            {{ pickupTime }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Dropoff -->
                                <div class="flex items-start gap-4 relative z-10">
                                    <!-- Icon with Ripple -->
                                     <div class="relative flex-shrink-0 mt-1">
                                         <div class="absolute inset-0 bg-red-500 rounded-full animate-ping opacity-20"></div>
                                         <div class="relative w-8 h-8 bg-red-100 rounded-full flex items-center justify-center border border-red-200 shadow-sm animate-ripple-red">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                         </div>
                                    </div>

                                    <div class="flex-1">
                                        <span class="text-xs text-red-700 font-bold uppercase tracking-wider block mb-0.5">Dropoff</span>
                                        <div class="font-bold text-gray-900 text-sm md:text-base leading-snug">
                                            {{ dropoffLocation || pickupLocation || locationName }}
                                        </div>
                                        <div class="text-xs text-gray-500 mt-0.5 font-medium flex items-center gap-1">
                                             <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            {{ dropoffDate }}
                                            <span v-if="dropoffTime" class="ml-1 text-gray-400">|</span>
                                            {{ dropoffTime }}
                                        </div>
                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>
                    <!-- Specs Icons -->
                    <div class="flex flex-wrap gap-3 pt-2 border-t border-gray-50">
                        <!-- Luggage -->
                        <div v-if="vehicleSpecs.bagDisplay" class="flex items-center gap-1.5" title="Luggage">
                            <svg class="w-4 h-4 opacity-70 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <span class="text-xs text-gray-600 font-medium">{{ vehicleSpecs.bagDisplay }}</span>
                        </div>
                        <!-- Passengers -->
                         <div v-if="vehicleSpecs.passengers" class="flex items-center gap-1.5" title="Passengers">
                            <img :src="seatingIcon" class="w-4 h-4 opacity-70" alt="Seats" />
                            <span class="text-xs text-gray-600 font-medium">{{ vehicleSpecs.passengers }}</span>
                        </div>
                         <!-- Transmission -->
                         <div v-if="vehicleSpecs.transmission" class="flex items-center gap-1.5" title="Transmission">
                            <img :src="transmissionIcon" class="w-4 h-4 opacity-70" alt="Trans" />
                            <span class="text-xs text-gray-600 font-medium whitespace-nowrap">{{ vehicleSpecs.transmission }}</span>
                        </div>
                        <!-- Fuel -->
                        <div v-if="vehicleSpecs.fuel" class="flex items-center gap-1.5" title="Fuel">
                            <img :src="fuelIcon" class="w-4 h-4 opacity-70" alt="Fuel" />
                            <span class="text-xs text-gray-600 font-medium">{{ vehicleSpecs.fuel }}</span>
                        </div>
                        <!-- AC -->
                        <div v-if="vehicleSpecs.airConditioning" class="flex items-center gap-1.5" title="Air Conditioning">
                            <img :src="acIcon" class="w-4 h-4 opacity-70" alt="AC" />
                            <span class="text-xs text-gray-600 font-medium">AC</span>
                        </div>
                         <!-- Doors -->
                        <div v-if="vehicleSpecs.doors" class="flex items-center gap-1.5" title="Doors">
                            <img :src="doorIcon" class="w-4 h-4 opacity-70" alt="Doors" />
                            <span class="text-xs text-gray-600 font-medium">{{ vehicleSpecs.doors }}</span>
                        </div>
                         <!-- Mileage (Dynamic based on selected plan) -->
                        <div v-if="currentProduct?.mileage" class="flex items-center gap-1.5" title="Mileage">
                            <component :is="Gauge" class="w-4 h-4 opacity-70 text-gray-700" />
                            <span class="text-xs text-gray-600 font-medium">
                            </span>
                        </div>
                        <!-- CO2 -->
                        <div v-if="vehicleSpecs.co2" class="flex items-center gap-1.5" title="CO2 Emissions">
                            <component :is="Leaf" class="w-4 h-4 opacity-70 text-green-600" />
                            <span class="text-xs text-green-700 font-medium">
                                {{ vehicleSpecs.co2 }} g/km
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Price Breakdown -->
                <div class="space-y-3 text-sm text-gray-700 mb-6">
                    <div class="flex justify-between">
                        <span>Car Package ({{ currentPackage }})</span>
                        <span class="font-medium">{{ formatPrice(currentProduct?.total || 0) }}</span>
                    </div>
                    <!-- Selected Extras List -->
                    <div v-for="item in getSelectedExtrasDetails" :key="item.id" class="flex justify-between text-blue-600">
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
                    <span class="text-2xl font-bold text-customPrimaryColor">{{ formatPrice(grandTotal) }}</span>
                </div>
                <!-- Payable Amount -->
                <div v-if="paymentPercentage > 0" class="flex justify-between items-center bg-green-50 border border-green-200 rounded-lg p-3 mb-3">
                    <span class="font-semibold text-green-800">Pay Now ({{ paymentPercentage }}%)</span>
                    <span class="text-xl font-bold text-green-700">{{ formatPrice(payableAmount) }}</span>
                </div>
                <!-- Running Text -->
                <div v-if="paymentPercentage > 0" class="overflow-hidden bg-customPrimaryColor/10 rounded-lg px-3 py-2 mb-6">
                    <p class="animate-marquee whitespace-nowrap text-sm font-medium text-customPrimaryColor">
                        💳 Pay {{ paymentPercentage }}% now and rest pay on arrival &nbsp;•&nbsp; 💳 Pay {{ paymentPercentage }}% now and rest pay on arrival &nbsp;•&nbsp;
                    </p>
                </div>

                <!-- View Booking Details Button -->
                <button 
                    v-if="paymentPercentage > 0"
                    @click="showDetailsModal = true" 
                    class="w-full text-sm text-customPrimaryColor font-semibold py-2 mb-4 border border-customPrimaryColor rounded-lg hover:bg-customPrimaryColor/10 transition-all flex items-center justify-center gap-2"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    View Booking Details
                </button>
                <div v-else class="mb-6"></div>

                <!-- Action Buttons -->
                <div class="space-y-3">
                    <button
                        @click="$emit('proceed-to-checkout', { package: currentPackage, extras: selectedExtras })"
                        class="w-full bg-customPrimaryColor text-white py-3 rounded-lg font-bold hover:bg-opacity-90 transition-all shadow-md"
                    >
                        Proceed to Booking
                    </button>
                    <button
                        @click="$emit('back')"
                        class="w-full bg-white border border-gray-300 text-gray-700 py-2 rounded-lg font-medium hover:bg-gray-50 transition-all"
                    >
                        Back to Results
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Details Modal -->
    <Teleport to="body">
        <div v-if="showDetailsModal" class="fixed inset-0 z-[100000] flex items-center justify-center p-4" @click.self="showDetailsModal = false">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
            <!-- Modal Content -->
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto">
                <!-- Header -->
                <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center rounded-t-2xl">
                    <h2 class="text-xl font-bold text-gray-800">Booking Details</h2>
                    <button @click="showDetailsModal = false" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-4">
                    <!-- Vehicle Info -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-500 mb-1">Vehicle</p>
                        <p class="font-bold text-gray-800">{{ vehicle.brand }} {{ vehicle.model }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ currentPackage }} Package</p>
                    </div>

                    <!-- Line Items -->
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Car Package ({{ currentPackage }})</span>
                            <span class="font-medium text-gray-800">{{ formatPrice(currentProduct?.total || 0) }}</span>
                        </div>
                        <div v-for="item in getSelectedExtrasDetails" :key="item.id" class="flex justify-between text-sm">
                            <span class="text-gray-600">{{ item.name }} <span v-if="item.qty > 1" class="text-xs text-gray-400">x{{ item.qty }}</span></span>
                            <span class="font-medium" :class="item.isFree ? 'text-green-600' : 'text-gray-800'">
                                {{ item.isFree ? 'FREE' : formatPrice(item.total) }}
                            </span>
                        </div>
                    </div>

                    <!-- Divider -->
                    <hr class="border-gray-200" />

                    <!-- Totals -->
                    <div class="space-y-2">
                        <div class="flex justify-between text-lg">
                            <span class="font-bold text-gray-800">Grand Total</span>
                            <span class="font-bold text-customPrimaryColor">{{ formatPrice(grandTotal) }}</span>
                        </div>
                        <div class="flex justify-between text-sm bg-green-50 p-3 rounded-lg">
                            <span class="font-semibold text-green-700">Pay Now ({{ paymentPercentage }}%)</span>
                            <span class="font-bold text-green-700">{{ formatPrice(payableAmount) }}</span>
                        </div>
                        <div class="flex justify-between text-sm bg-amber-50 p-3 rounded-lg">
                            <span class="font-semibold text-amber-700">Pay on Arrival</span>
                            <span class="font-bold text-amber-700">{{ formatPrice(pendingAmount) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="sticky bottom-0 bg-white border-t px-6 py-4 rounded-b-2xl">
                    <button 
                        @click="showDetailsModal = false" 
                        class="w-full bg-customPrimaryColor text-white py-3 rounded-lg font-bold hover:bg-opacity-90 transition-all"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
@keyframes ripple-green {
    0% {
        box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.4);
    }
    70% {
        box-shadow: 0 0 0 6px rgba(74, 222, 128, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(74, 222, 128, 0);
    }
}

@keyframes ripple-red {
    0% {
        box-shadow: 0 0 0 0 rgba(248, 113, 113, 0.4);
    }
    70% {
        box-shadow: 0 0 0 6px rgba(248, 113, 113, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(248, 113, 113, 0);
    }
}

.animate-ripple-green {
    animation: ripple-green 2s infinite;
}

.animate-ripple-red {
    animation: ripple-red 2s infinite;
}

@keyframes flow-line {
    0% {
        transform: scaleY(0);
        opacity: 0;
    }
    50% {
        opacity: 1;
    }
    100% {
        transform: scaleY(1);
        opacity: 0;
    }
}

.animate-flow-line {
    animation: flow-line 2s ease-in-out infinite;
}

@keyframes marquee {
    0% {
        transform: translateX(0%);
    }
    100% {
        transform: translateX(-50%);
    }
}

.animate-marquee {
    animation: marquee 12s linear infinite;
}
</style>
