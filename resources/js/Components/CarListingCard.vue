<script setup>
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useCurrencyConversion } from '@/composables/useCurrencyConversion';

// Icons
import carIcon from "../../assets/carIcon.svg";
import mileageIcon from "../../assets/mileageIcon.svg"; // Keeping for generic use if needed
import fuelIcon from "../../assets/fuel.svg";
import transmissionIcon from "../../assets/transmittionIcon.svg";
import seatingIcon from "../../assets/travellerIcon.svg";
import doorIcon from "../../assets/door.svg";
import check from "../../assets/Check.svg";
import acIcon from "../../assets/ac.svg";
import { Leaf, Heart } from "lucide-vue-next";

// Check if vehicle is LocautoRent
const isLocautoRent = computed(() => {
    return props.vehicle?.source === 'locauto_rent';
});

// Check if vehicle is Adobe Cars
const isAdobeCars = computed(() => {
    return props.vehicle?.source === 'adobe';
});

// Check if vehicle is Renteon
const isRenteon = computed(() => {
    return props.vehicle?.source === 'renteon';
});

// Check if vehicle is OK Mobility
const isOkMobility = computed(() => {
    return props.vehicle?.source === 'okmobility';
});

// Helper for highlighting benefits
const isKeyBenefit = (text) => {
    if (!text) return false;
    const lower = text.toLowerCase();
    return lower.includes('excess') ||
        lower.includes('deposit') ||
        lower.includes('free') ||
        lower.includes('unlimited');
};
// Note: If some icons are missing, I'll use text or generic replacements for now.

const props = defineProps({
    vehicle: Object,
    form: Object, // Needed for date/time/location params in links
    favoriteStatus: Boolean,
    favoriteLoading: {
        type: Boolean,
        default: false,
    },
    popEffect: Boolean,
    viewMode: {
        type: String,
        default: 'grid'
    }
});

const emit = defineEmits(['toggleFavourite', 'saveSearchUrl', 'select-package']);

const { selectedCurrency, convertPrice, getSelectedCurrencySymbol, getCurrencySymbol, fetchExchangeRates, exchangeRates, loading } = useCurrencyConversion();
const page = usePage();

// Fetch exchange rates on mount
onMounted(() => {
    fetchExchangeRates();
});

// State for GreenMotion/USave plan selection
const selectedPackage = ref('BAS'); // Default to Basic
const showAllPlans = ref(false);

const ratesReady = computed(() => !!exchangeRates.value && !loading.value);

const canConvertFrom = (fromCurrency) => {
    if (!fromCurrency || !selectedCurrency.value) return false;
    if (!exchangeRates.value || loading.value) return false;

    const fromCode = fromCurrency.toUpperCase();
    const toCode = selectedCurrency.value.toUpperCase();

    if (fromCode === toCode) return true;

    return Boolean(exchangeRates.value[fromCode] && exchangeRates.value[toCode]);
};

// --- Computed Properties ---

// Calculate Number of Rental Days
const numberOfRentalDays = computed(() => {
    if (props.form.date_from && props.form.date_to) {
        const startStr = `${props.form.date_from} ${props.form.start_time || '09:00'}`;
        const endStr = `${props.form.date_to} ${props.form.end_time || '09:00'}`;
        const start = new Date(startStr);
        const end = new Date(endStr);
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return diffDays > 0 ? diffDays : 1;
    }
    return 1;
});

// Check if vehicle is GreenMotion or USave
const isGreenMotionOrUSave = computed(() => {
    return props.vehicle.source === 'greenmotion' || props.vehicle.source === 'usave';
});

// (Moved above)


// Get Adobe Cars base daily rate (tdr)
const adobeBaseRate = computed(() => {
    if (!isAdobeCars.value) return 0;
    return parseFloat(props.vehicle.tdr) || 0;
});

// Get Adobe Cars protection plans (PLI, LDW, SPP)
const adobeProtectionPlans = computed(() => {
    if (!isAdobeCars.value) return [];
    const protections = [];

    // PLI - Liability Protection (required)
    if (props.vehicle.pli !== undefined) {
        protections.push({
            code: 'PLI',
            name: 'Liability Protection',
            amount: parseFloat(props.vehicle.pli),
            required: true
        });
    }

    // LDW - Car Protection
    if (props.vehicle.ldw !== undefined) {
        protections.push({
            code: 'LDW',
            name: 'Car Protection',
            amount: parseFloat(props.vehicle.ldw),
            required: false
        });
    }

    // SPP - Extended Protection
    if (props.vehicle.spp !== undefined) {
        protections.push({
            code: 'SPP',
            name: 'Extended Protection',
            amount: parseFloat(props.vehicle.spp),
            required: false
        });
    }

    return protections;
});

// Selected Adobe Cars protection plan
const selectedAdobeProtection = ref(null);

// (Default selection to PLI removed as per user request to show Basic first)


// Adobe Products Unified (for consistent UI with GreenMotion)
const adobeProducts = computed(() => {
    if (!isAdobeCars.value) return [];

    // 1. Basic Package (Base Rate)
    const products = [{
        type: 'BAS',
        name: 'Basic Rate',
        code: 'BAS', // Identifier
        total: adobeBaseRate.value, // tdr is Total
        benefits: ['Base rental rate only', 'Liability Protection (PLI) added separately'],
        is_basic: true,
        isSelected: !selectedAdobeProtection.value
    }];

    // 2. Protection Plans (Exclude PLI as it's mandatory and shown separately)
    adobeProtectionPlans.value.filter(p => p.code !== 'PLI').forEach(plan => {
        products.push({
            type: plan.code,
            name: plan.name,
            code: plan.code,
            total: adobeBaseRate.value + plan.amount, // Sum of Totals
            benefits: [
                'Includes Liability Protection (PLI)',
                plan.required ? 'Mandatory - Required by law' : 'Optional Protection'
            ],
            is_basic: false,
            original_plan: plan,
            isSelected: selectedAdobeProtection.value?.code === plan.code
        });
    });

    return products;
});

// Internal Vehicle Vendor Plans
const internalVendorPlans = computed(() => {
    if (props.vehicle.source !== 'internal') return [];

    // Laravel returns relationship as camelCase 'vendorPlans' not 'vendor_plans'
    const vendorPlans = props.vehicle?.vendorPlans || props.vehicle?.vendor_plans || [];
    const products = [];

    // Always add Basic package (base price_per_day)
    products.push({
        type: 'BAS',
        name: 'Basic Rental',
        subtitle: 'Standard Package',
        total: (parseFloat(props.vehicle?.price_per_day) || 0) * numberOfRentalDays.value,
        price_per_day: parseFloat(props.vehicle?.price_per_day) || 0,
        deposit: parseFloat(props.vehicle?.security_deposit) || 0,
        benefits: ['Base rental rate', 'Standard coverage'],
        is_basic: true,
        isSelected: !selectedInternalPlan.value,
        currency: props.vehicle?.currency || 'USD'
    });

    // Add vendor plans if any exist
    vendorPlans.forEach((plan, index) => {
        const features = plan.features ? (typeof plan.features === 'string' ? JSON.parse(plan.features) : plan.features) : [];
        products.push({
            type: plan.plan_type || `PLAN_${index}`,
            name: plan.plan_type || 'Custom Plan',
            subtitle: plan.plan_description || 'Vendor Package',
            total: (parseFloat(plan.price) || 0) * numberOfRentalDays.value,
            price_per_day: parseFloat(plan.price) || 0,
            deposit: parseFloat(props.vehicle?.security_deposit) || 0,
            benefits: features.length > 0 ? features : ['Custom vendor package'],
            is_basic: false,
            isSelected: selectedInternalPlan.value?.id === plan.id,
            vendorPlanId: plan.id,
            currency: props.vehicle?.currency || 'USD'
        });
    });

    return products;
});

// Selected internal vendor plan (null = Basic)
const selectedInternalPlan = ref(null);

const handleAdobeSelection = (product) => {
    if (product.is_basic) {
        selectAdobeProtection(null);
    } else {
        selectAdobeProtection(product.original_plan);
    }
};

// Get LocautoRent protection plans from extras (all 7 plans)
const locautoProtectionPlans = computed(() => {
    if (!isLocautoRent.value) return [];
    const protectionCodes = ['136', '147', '145', '140', '146', '6', '43'];
    const extras = props.vehicle.extras || [];
    return extras.filter(extra =>
        protectionCodes.includes(extra.code) && extra.amount > 0
    );
});

// LocautoRent: Smart Cover plan (code 147)
const locautoSmartCoverPlan = computed(() => {
    return locautoProtectionPlans.value.find(p => p.code === '147') || null;
});

// LocautoRent: Don't Worry plan (code 136)
const locautoDontWorryPlan = computed(() => {
    return locautoProtectionPlans.value.find(p => p.code === '136') || null;
});

// Selected LocautoRent protection plan (null = Basic)
const selectedLocautoProtection = ref(null);

// Get sorted products (BAS, PLU, PRE, PMP order)
const sortedProducts = computed(() => {
    if (!isGreenMotionOrUSave.value) return [];
    const order = ['BAS', 'PLU', 'PRE', 'PMP'];
    const products = props.vehicle.products || [];
    return order.map(type => products.find(p => p.type === type)).filter(Boolean);
});

// Get daily price for selected package
const dailyPrice = computed(() => {
    if (!isGreenMotionOrUSave.value) return null;
    const product = sortedProducts.value.find(p => p.type === selectedPackage.value);
    if (!product) return '0.00';
    const originalPrice = parseFloat(product.total) / numberOfRentalDays.value;
    const originalCurrency = product.currency || props.vehicle.currency || 'USD';
    if (!canConvertFrom(originalCurrency)) return null;
    return convertPrice(originalPrice, originalCurrency).toFixed(2);
});

// Get Premium Plus daily price for display
const premiumPlusDailyPrice = computed(() => {
    if (!isGreenMotionOrUSave.value) return null;
    const product = sortedProducts.value.find(p => p.type === 'PMP');
    if (!product) return '0.00';
    const originalPrice = parseFloat(product.total) / numberOfRentalDays.value;
    const originalCurrency = product.currency || props.vehicle.currency || 'USD';
    if (!canConvertFrom(originalCurrency)) return null;
    return convertPrice(originalPrice, originalCurrency).toFixed(2);
});

// Get LocautoRent converted daily price
const locautoDailyPrice = computed(() => {
    if (!isLocautoRent.value) return '0.00';
    const basePrice = parseFloat(props.vehicle.price_per_day) || 0;
    const protectionPrice = selectedLocautoProtection.value ? parseFloat(selectedLocautoProtection.value.amount) : 0;
    const originalPrice = basePrice + protectionPrice;
    const originalCurrency = props.vehicle.currency || 'EUR';
    if (!canConvertFrom(originalCurrency)) return null;
    return convertPrice(originalPrice, originalCurrency).toFixed(2);
});

// Get Adobe converted daily price
const adobeDailyPrice = computed(() => {
    if (!isAdobeCars.value) return '0.00';
    const baseTotal = adobeBaseRate.value;
    const protectionTotal = selectedAdobeProtection.value ? parseFloat(selectedAdobeProtection.value.amount) : 0;
    const originalPrice = (baseTotal + protectionTotal) / numberOfRentalDays.value;
    if (!canConvertFrom('USD')) return null;
    return convertPrice(originalPrice, 'USD').toFixed(2);
});

// Get Renteon converted daily price
const renteonDailyPrice = computed(() => {
    if (!isRenteon.value) return '0.00';
    const originalPrice = parseFloat(props.vehicle?.price_per_day || 0);
    const originalCurrency = props.vehicle?.currency || 'EUR';
    if (!canConvertFrom(originalCurrency)) return null;
    return convertPrice(originalPrice, originalCurrency).toFixed(2);
});

// Get package name from type
const getPackageName = (type) => {
    const names = {
        BAS: 'Basic',
        PLU: 'Plus',
        PRE: 'Premium',
        PMP: 'Premium Plus'
    };
    return names[type] || type;
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

// Get benefits for a product (semi-dynamic approach)
const getBenefits = (product) => {
    if (!product) return [];
    const benefits = [];
    const type = product.type;
    const originalCurrency = product.currency || props.vehicle.currency || 'USD';

    // Dynamic from API
    if (product.excess !== undefined && parseFloat(product.excess) === 0) {
        benefits.push('Glass and tyres covered');
    } else if (product.excess !== undefined) {
        const convertedExcess = convertPrice(product.excess, originalCurrency);
        benefits.push(`Excess: ${getSelectedCurrencySymbol()}${convertedExcess.toFixed(2)}`);
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
    } else if (product.mileage !== undefined && product.mileage !== null && `${product.mileage}`.trim() !== '') {
        benefits.push(`Mileage included: ${product.mileage}`);
        if (product.costperextradistance !== undefined && parseFloat(product.costperextradistance) > 0) {
            const convertedExtra = convertPrice(product.costperextradistance, originalCurrency);
            benefits.push(`Extra distance charge: ${getSelectedCurrencySymbol()}${convertedExtra.toFixed(2)}`);
        }
    } else if (product.costperextradistance !== undefined && parseFloat(product.costperextradistance) > 0) {
        const convertedExtra = convertPrice(product.costperextradistance, originalCurrency);
        benefits.push(`Extra distance charge: ${getSelectedCurrencySymbol()}${convertedExtra.toFixed(2)}`);
    }

    // Static based on type (only what's not in API)
    if (type === 'BAS') {
        benefits.push('Non-refundable');
        benefits.push('Non-amendable');
    }

    if (type === 'PMP') {
    }

    if (type === 'PLU' || type === 'PRE' || type === 'PMP') {
        benefits.push('Cancellation in line with T&Cs');
    }

    if (product.minage !== undefined && product.minage !== null && `${product.minage}`.trim() !== '') {
        benefits.push(`Minimum driver age: ${product.minage}`);
    }

    return benefits;
};

// Format price with currency (converted)
const formatPrice = (price, currency) => {
    const converted = convertPrice(price, currency);
    return `${getSelectedCurrencySymbol()}${converted.toFixed(2)}`;
};

// Select a package
const selectPackage = (type) => {
    selectedPackage.value = type;
    showAllPlans.value = false;
    emit('select-package', { vehicle: props.vehicle, package: type });
};

const selectRenteonPackage = () => {
    emit('select-package', {
        vehicle: props.vehicle,
        package: 'BAS',
    });
};

const selectOkMobilityPackage = () => {
    emit('select-package', {
        vehicle: props.vehicle,
        package: 'BAS'
    });
};

// Select LocautoRent protection plan (null = Basic, no extra protection)
const selectLocautoProtection = (protection) => {
    selectedLocautoProtection.value = protection;
    showAllPlans.value = false;
    emit('select-package', {
        vehicle: props.vehicle,
        package: protection ? 'POA' : 'BAS',
        protection_code: protection?.code || null,
        protection_amount: protection?.amount || 0
    });
};

// Select Adobe Cars protection plan (null = Basic rate only, no additional protection)
const selectAdobeProtection = (protection) => {
    selectedAdobeProtection.value = protection;
    showAllPlans.value = false;
    // Calculate total: base rate (Total) + selected protection amount (Total)
    const totalAmount = adobeBaseRate.value + (protection?.amount || 0);
    emit('select-package', {
        vehicle: props.vehicle,
        package: protection ? protection.code : 'BAS',
        protection_code: protection?.code || null,
        protection_amount: protection?.amount || 0,
        total_price: totalAmount
    });
};

// Close modal
const closeModal = () => {
    showAllPlans.value = false;
};

// Select Internal Vehicle Package (emits for inline BookingExtrasStep)
const selectInternalPackage = () => {
    const selectedPlan = selectedInternalPlan.value;
    emit('select-package', {
        vehicle: props.vehicle,
        package: selectedPlan?.plan_type || 'BAS',
        protection_code: selectedPlan?.id?.toString() || null,
        protection_amount: selectedPlan?.price || 0,
        vendor_plan_id: selectedPlan?.id || null,
        // Pass vendorPlans and addons for BookingExtrasStep to use
        vendorPlans: props.vehicle.vendor_plans || [],
        addons: props.vehicle.addons || []
    });
};

// Select internal plan from modal
const selectInternalVendorPlan = (product) => {
    if (product.is_basic) {
        selectedInternalPlan.value = null;
    } else {
        selectedInternalPlan.value = {
            id: product.vendorPlanId,
            plan_type: product.type,
            price: product.price_per_day
        };
    }
    showAllPlans.value = false;
    selectInternalPackage();
};

// Image Handling
const getImageSource = (vehicle) => {
    if (vehicle.source === 'internal') {
        return vehicle.images?.find((image) => image.image_type === 'primary')?.image_url || '/default-image.png';
    }
    if (vehicle.image) return vehicle.image;
    if (vehicle.source === 'wheelsys') return '/wheelsys-placeholder.jpg';
    return '/images/dummyCarImaage.png';
};

const handleImageError = (event) => {
    if (event.target.src.includes('placeholder') || event.target.src.includes('no-vehicle') || event.target.onerror === null) return;
    if (event.target.dataset.imageUrl && event.target.dataset.imageUrl.includes('wheelsys')) {
        event.target.src = '/wheelsys-placeholder.jpg';
    } else {
        event.target.src = '/images/dummyCarImaage.png';
    }
    event.target.onerror = null;
};

// Route Generation
const getProviderRoute = (vehicle) => {
    const routes = {
        'greenmotion': 'green-motion-car.show',
        'okmobility': 'ok-mobility-car.show',
        'wheelsys': 'wheelsys-car.show',
        'adobe': 'adobe-car.show',
        'locauto_rent': 'locauto-rent-car.show'
    };
    return routes[vehicle.source] || 'green-motion-car.show';
};

// Build route params with selected package for GM/USave
const getRouteParams = (vehicle) => {
    const baseParams = {
        locale: page.props.locale,
        id: vehicle.id,
        provider: vehicle.source,
        location_id: vehicle.provider_pickup_id,
        start_date: props.form.date_from,
        end_date: props.form.date_to,
        start_time: props.form.start_time,
        end_time: props.form.end_time,
        dropoff_location_id: props.form.dropoff_location_id,
        rentalCode: props.form.rentalCode
    };

    // Add selected package for GM/USave
    if (isGreenMotionOrUSave.value) {
        baseParams.package = selectedPackage.value;
    }

    // Add selected protection for LocautoRent
    if (isLocautoRent.value && selectedLocautoProtection.value) {
        baseParams.protection_code = selectedLocautoProtection.value.code;
    }

    return baseParams;
};

// --- Specs & Features Logic ---

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
// --- Image Slider Logic (Internal Vehicles Only) ---
const currentImageIndex = ref(0);
const isHovered = ref(false);
let sliderInterval = null;

const allImages = computed(() => {
    if (props.vehicle.source !== 'internal') return [];

    const primary = props.vehicle.images?.find(img => img.image_type === 'primary');
    const gallery = props.vehicle.images?.filter(img => img.image_type === 'gallery') || [];

    const images = [];
    if (primary) images.push(primary.image_url);
    gallery.forEach(img => images.push(img.image_url));

    return images.length > 0 ? images : ['/default-image.png'];
});

const nextImage = () => {
    if (allImages.value.length <= 1) return;
    currentImageIndex.value = (currentImageIndex.value + 1) % allImages.value.length;
};

const prevImage = () => {
    if (allImages.value.length <= 1) return;
    currentImageIndex.value = (currentImageIndex.value - 1 + allImages.value.length) % allImages.value.length;
};

const goToImage = (index) => {
    currentImageIndex.value = index;
};

const startSlider = () => {
    if (allImages.value.length <= 1) return;
    if (sliderInterval) return; // Already running
    sliderInterval = setInterval(nextImage, 3000); // 3 seconds per slide
};

const stopSlider = () => {
    if (sliderInterval) {
        clearInterval(sliderInterval);
        sliderInterval = null;
    }
};

onMounted(() => {
    startSlider();
});

onUnmounted(() => {
    stopSlider();
});

</script>

<template>
    <div class="car-card group vehicle-card" :class="[viewMode === 'list' ? 'list-view' : '']"
        :data-vehicle-id="vehicle.id">
        <!-- Image Section -->
        <div class="car-image" @mouseenter="isHovered = true" @mouseleave="isHovered = false">
            <template v-if="vehicle.source === 'internal' && allImages.length > 0">
                <div class="slider-container h-full">
                    <div class="block h-full relative">
                        <div v-for="(img, index) in allImages" :key="index"
                            class="absolute inset-0 transition-opacity duration-700 ease-in-out"
                            :class="currentImageIndex === index ? 'opacity-100' : 'opacity-0 pointer-events-none'">
                            <img :src="img" :alt="`${vehicle.brand} ${vehicle.model} - Image ${index + 1}`"
                                @error="handleImageError" class="w-full h-full object-cover" loading="lazy" />
                        </div>
                    </div>

                    <!-- Slider Controls (Visible on Hover) -->
                    <div class="slider-controls" v-if="allImages.length > 1">
                        <button @click.prevent.stop="prevImage" class="slider-arrow prev" aria-label="Previous image">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="m15 18-6-6 6-6" />
                            </svg>
                        </button>
                        <button @click.prevent.stop="nextImage" class="slider-arrow next" aria-label="Next image">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="m9 18 6-6-6-6" />
                            </svg>
                        </button>
                    </div>

                    <!-- Pagination Dots -->
                    <div class="slider-dots" v-if="allImages.length > 1">
                        <button v-for="(_, index) in allImages" :key="index" @click.prevent.stop="goToImage(index)"
                            class="slider-dot" :class="{ 'active': currentImageIndex === index }"
                            :aria-label="`Go to image ${index + 1}`">
                        </button>
                    </div>
                </div>
            </template>
            <div v-else class="block h-full">
                <img :src="getImageSource(vehicle)" :alt="`${vehicle.brand} ${vehicle.model}`" @error="handleImageError"
                    loading="lazy" />
            </div>

            <div class="car-badges">
                <span v-if="vehicle.category" class="car-badge category">
                    {{ vehicle.category }}
                </span>
                <span v-if="vehicle.special_offer || isKeyBenefit(vehicle.special_offer)" class="car-badge deal">
                    {{ vehicle.special_offer || 'Best Deal' }}
                </span>
            </div>

            <button class="car-favorite" :class="{ 'is-active': favoriteStatus, 'is-loading': favoriteLoading }"
                @click.prevent="$emit('toggleFavourite', vehicle)" :disabled="favoriteLoading"
                :aria-label="favoriteStatus ? 'Remove from favorites' : 'Add to favorites'"
                :aria-busy="favoriteLoading ? 'true' : 'false'">
                <Heart class="w-5 h-5 transition-all duration-300"
                    :class="{ 'fill-red-500 stroke-red-500 scale-110': favoriteStatus }" />
            </button>
        </div>

        <!-- Content Section -->
        <div class="car-content">
            <!-- Header -->
            <div class="car-header">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="car-name">{{ vehicle?.brand }} {{ vehicle?.model }}</h3>
                        <p class="car-class">{{ vehicleSpecs.acriss || vehicle?.sipp_code_one_letter || 'Car' }}</p>
                    </div>
                </div>
            </div>

            <!-- Specs -->
            <div class="car-specs">
                <!-- Transmission -->
                <div class="spec-tag">
                    <img :src="transmissionIcon" class="w-3.5 h-3.5 opacity-60" alt="" />
                    <span>{{ vehicleSpecs.transmission || 'Auto' }}</span>
                </div>

                <!-- Fuel Type -->
                <div class="spec-tag" v-if="vehicleSpecs.fuel">
                    <img :src="fuelIcon" class="w-3.5 h-3.5 opacity-60" alt="" />
                    <span>{{ vehicleSpecs.fuel }}</span>
                </div>

                <!-- Seats (Passengers) -->
                <div class="spec-tag" v-if="vehicleSpecs.passengers">
                    <img :src="seatingIcon" class="w-3.5 h-3.5 opacity-60" alt="" />
                    <span>{{ vehicleSpecs.passengers }} Seats</span>
                </div>

                <!-- Doors -->
                <div class="spec-tag" v-if="vehicleSpecs.doors">
                    <img :src="doorIcon" class="w-3.5 h-3.5 opacity-60" alt="" />
                    <span>{{ vehicleSpecs.doors }} Doors</span>
                </div>

                <!-- Luggage (Added back) -->
                <div class="spec-tag" v-if="vehicleSpecs.bagDisplay">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-500" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <span>{{ vehicleSpecs.bagDisplay }}</span>
                </div>

                <!-- A/C -->
                <div class="spec-tag" v-if="vehicleSpecs.airConditioning">
                    <img :src="acIcon" class="w-3.5 h-3.5 opacity-60" alt="" />
                    <span>A/C</span>
                </div>
            </div>

            <!-- Features -->
            <div class="car-features">
                <!-- CO2 -->
                <span v-if="vehicleSpecs.co2" class="feature-tag info">
                    <component :is="Leaf" class="w-3 h-3" />
                    {{ vehicleSpecs.co2 }} g/km
                </span>

                <!-- Free Cancellation -->
                <span v-if="vehicle.benefits?.cancellation_available_per_day || vehicle.source === 'wheelsys'"
                    class="feature-tag included">
                    <img :src="check" class="w-3 h-3 text-green-600" alt="" />
                    Free Cancellation
                </span>

                <!-- Unlimited Mileage -->
                <span v-if="vehicle.mileage === 'Unlimited' || vehicle.benefits?.limited_km_per_day === false"
                    class="feature-tag included">
                    <img :src="check" class="w-3 h-3 text-green-600" alt="" />
                    Unlimited km
                </span>

                <!-- Fuel Policy -->
                <span v-if="vehicle.fuel_policy" class="feature-tag info">
                    {{ vehicle.fuel_policy }}
                </span>
            </div>

            <!-- GreenMotion/USave/Locauto/Adobe/Internal View Plans Actions -->
            <div v-if="(isGreenMotionOrUSave && sortedProducts.length > 0) || (isLocautoRent && locautoProtectionPlans.length > 0) || (isAdobeCars && adobeProtectionPlans.length > 0) || (vehicle.source === 'internal' && internalVendorPlans.length > 0)"
                class="mb-4">
                <button @click="showAllPlans = true"
                    class="w-full text-center text-xs font-semibold py-2.5 border border-dashed border-primary-200 bg-primary-50 text-primary-700 rounded-lg hover:bg-primary-100 hover:border-primary-300 transition-all flex items-center justify-center gap-2">
                    <span>View Protection Plans</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            <!-- Footer -->
            <div class="car-footer">
                <div class="car-pricing">
                    <div class="car-total">
                        <!-- GreenMotion/USave Price -->
                        <template v-if="isGreenMotionOrUSave">
                            <span class="car-price" v-if="dailyPrice !== null">
                                {{ getSelectedCurrencySymbol() }}{{ dailyPrice }}
                            </span>
                            <span class="price-skeleton" v-else></span>
                        </template>

                        <!-- Locauto Price -->
                        <template v-else-if="isLocautoRent">
                            <span class="car-price" v-if="locautoDailyPrice !== null">
                                {{ getSelectedCurrencySymbol() }}{{ locautoDailyPrice }}
                            </span>
                            <span class="price-skeleton" v-else></span>
                        </template>

                        <!-- Adobe Price -->
                        <template v-else-if="isAdobeCars">
                            <span class="car-price" v-if="adobeDailyPrice !== null">
                                {{ getSelectedCurrencySymbol() }}{{ adobeDailyPrice }}
                            </span>
                            <span class="price-skeleton" v-else></span>
                        </template>

                        <!-- Renteon Price -->
                        <template v-else-if="isRenteon">
                            <span class="car-price" v-if="renteonDailyPrice !== null">
                                {{ getSelectedCurrencySymbol() }}{{ renteonDailyPrice }}
                            </span>
                            <span class="price-skeleton" v-else></span>
                        </template>

                        <!-- Standard Price (Slot) -->
                        <template v-else>
                            <span class="car-price" v-if="ratesReady">
                                <slot name="dailyPrice"></slot>
                            </span>
                            <span class="price-skeleton" v-else></span>
                        </template>
                    </div>
                    <span class="car-currency">per day</span>
                </div>

                <!-- Book Buttons -->
                <!-- GreenMotion -->
                <button v-if="isGreenMotionOrUSave" @click="selectPackage(selectedPackage)" class="header-btn primary"
                    :disabled="!ratesReady" :class="{ 'is-loading': !ratesReady }">
                    Book Deal
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>

                <!-- Locauto -->
                <button v-else-if="isLocautoRent" @click="selectLocautoProtection(selectedLocautoProtection)"
                    class="header-btn primary" :disabled="!ratesReady" :class="{ 'is-loading': !ratesReady }">
                    Book Deal
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>

                <!-- Adobe -->
                <button v-else-if="isAdobeCars" @click="selectAdobeProtection(selectedAdobeProtection)"
                    class="header-btn primary" :disabled="!ratesReady" :class="{ 'is-loading': !ratesReady }">
                    Book Deal
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>

                <!-- Internal Vehicle: Emit select-package for inline BookingExtrasStep -->
                <button v-else-if="vehicle.source === 'internal'" @click="selectInternalPackage"
                    class="header-btn primary" :disabled="!ratesReady" :class="{ 'is-loading': !ratesReady }">
                    Book Deal
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>

                <button v-else-if="isRenteon" @click="selectRenteonPackage" class="header-btn primary"
                    :disabled="!ratesReady" :class="{ 'is-loading': !ratesReady }">
                    Book Deal
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>

                <button v-else-if="isOkMobility" @click="selectOkMobilityPackage" class="header-btn primary"
                    :disabled="!ratesReady" :class="{ 'is-loading': !ratesReady }">
                    Book Deal
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>

                <!-- Standard Link for other external providers -->
                <Link v-else :href="route(getProviderRoute(vehicle), getRouteParams(vehicle))"
                    class="header-btn primary" @click="(event) => { if (!ratesReady) { event.preventDefault(); return; } $emit('saveSearchUrl'); }"
                    :class="{ 'is-loading': !ratesReady }">
                    Book Deal
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </Link>
            </div>
        </div>
    </div>

    <!-- Modal: All 4 Plans -->
    <Teleport to="body">
        <div v-if="showAllPlans" class="plans-modal" @click.self="closeModal">
            <div class="plans-modal-content">
                <button @click="closeModal" class="modal-close-btn" aria-label="Close modal">✕</button>

                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 font-['Outfit']">Select Your Protection Package</h2>
                    <p class="text-gray-500 mt-1">Choose the best coverage option for your trip</p>
                </div>

                <!-- Plans Grid -->
                <div class="plans-grid">
                    <!-- GreenMotion/USave Plans -->
                    <template v-if="isGreenMotionOrUSave">
                        <div v-for="product in sortedProducts" :key="product.type" class="plan-card"
                            :class="{ 'selected': selectedPackage === product.type }"
                            @click="selectPackage(product.type)">
                            <div class="plan-header">
                                <div>
                                    <h3 class="plan-name">{{ getPackageName(product.type) }}</h3>
                                    <p class="plan-type">{{ product.type }}</p>
                                </div>
                                <div class="plan-check" v-if="selectedPackage === product.type">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                        class="w-4 h-4 text-white">
                                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                                    </svg>
                                </div>
                            </div>

                            <div class="plan-price-box">
                                <div class="plan-daily-price">
                                    {{ getSelectedCurrencySymbol() }}{{ convertPrice(parseFloat(product.total) /
                                        numberOfRentalDays, product.currency || props.vehicle.currency || 'USD').toFixed(2)
                                    }}
                                    <span>/day</span>
                                </div>
                                <div class="plan-total-price">
                                    Total: {{ getSelectedCurrencySymbol() }}{{
                                        convertPrice(parseFloat(product.total), product.currency || props.vehicle.currency
                                            || 'USD').toFixed(2) }}
                                </div>
                            </div>

                            <ul class="plan-features">
                                <li v-for="benefit in getBenefits(product)" :key="benefit">
                                    <img :src="check" class="w-4 h-4 opacity-100" />
                                    <span :class="{ 'font-semibold text-gray-900': isKeyBenefit(benefit) }">{{ benefit
                                        }}</span>
                                </li>
                                <li v-if="product.deposit">
                                    <img :src="check" class="w-4 h-4 opacity-100" />
                                    <span :class="{ 'font-semibold text-gray-900': isKeyBenefit('Deposit') }">Deposit:
                                        {{
                                            getSelectedCurrencySymbol() }}{{ convertPrice(parseFloat(product.deposit),
                                            product.currency || props.vehicle.currency || 'USD').toFixed(2)
                                        }}</span>
                                </li>
                            </ul>

                            <button class="plan-select-btn">
                                {{ selectedPackage === product.type ? 'Selected' : 'Select Package' }}
                            </button>
                        </div>
                    </template>

                    <!-- LocautoRent Protection Plans -->
                    <template v-else-if="isLocautoRent">
                        <!-- Basic Plan item -->
                        <div class="plan-card" :class="{ 'selected': !selectedLocautoProtection }"
                            @click="selectLocautoProtection(null)">
                            <div class="plan-header">
                                <div>
                                    <h3 class="plan-name">Basic Coverage</h3>
                                    <p class="plan-type">Standard</p>
                                </div>
                                <div class="plan-check" v-if="!selectedLocautoProtection"><svg
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                        class="w-4 h-4 text-white">
                                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                                    </svg></div>
                            </div>
                            <div class="plan-price-box">
                                <div class="plan-daily-price">{{ getSelectedCurrencySymbol() }}{{
                                    convertPrice(parseFloat(vehicle.price_per_day), vehicle.currency ||
                                        'EUR').toFixed(2) }} <span>/day</span></div>
                                <div class="plan-total-price">Total: {{ getSelectedCurrencySymbol() }}{{
                                    convertPrice(parseFloat(vehicle.total_price), vehicle.currency || 'EUR').toFixed(2)
                                }}</div>
                            </div>
                            <ul class="plan-features">
                                <li><img :src="check" class="w-4 h-4" /> <span>Standard protection included</span></li>
                            </ul>
                            <button class="plan-select-btn">{{ !selectedLocautoProtection ? 'Selected' : 'SelectPackage'
                            }}</button>
                        </div>
                        <!-- Other items -->
                        <div v-for="protection in locautoProtectionPlans" :key="protection.code" class="plan-card"
                            :class="{ 'selected': selectedLocautoProtection?.code === protection.code }"
                            @click="selectLocautoProtection(protection)">
                            <div class="plan-header">
                                <div>
                                    <h3 class="plan-name">{{ getShortProtectionName(protection.description) }}</h3>
                                    <p class="plan-type">{{ protection.code }}</p>
                                </div>
                                <div class="plan-check" v-if="selectedLocautoProtection?.code === protection.code"><svg
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                        class="w-4 h-4 text-white">
                                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                                    </svg></div>
                            </div>
                            <div class="plan-price-box">
                                <div class="plan-daily-price">{{ getSelectedCurrencySymbol() }}{{
                                    convertPrice(parseFloat(vehicle.price_per_day) + parseFloat(protection.amount),
                                        vehicle.currency || 'EUR').toFixed(2) }}
                                    <span>/day</span>
                                </div>
                                <div class="plan-total-price">Total: {{ getSelectedCurrencySymbol() }}{{
                                    convertPrice(parseFloat(vehicle.total_price) + (parseFloat(protection.amount) *
                                        numberOfRentalDays), vehicle.currency || 'EUR').toFixed(2) }}</div>
                            </div>
                            <ul class="plan-features">
                                <li><img :src="check" class="w-4 h-4" /> <span>{{ protection.description }}</span></li>
                            </ul>
                            <button class="plan-select-btn">{{ selectedLocautoProtection?.code === protection.code ?
                                'Selected' : 'Select Package' }}</button>
                        </div>
                    </template>

                    <!-- Adobe Cars Protection Plans -->
                    <template v-else-if="isAdobeCars">
                        <div v-for="product in adobeProducts" :key="product.code" class="plan-card"
                            :class="{ 'selected': product.isSelected }" @click="handleAdobeSelection(product)">
                            <div class="plan-header">
                                <div>
                                    <h3 class="plan-name">{{ product.name }}</h3>
                                    <p class="plan-type">{{ product.code }}</p>
                                </div>
                                <div class="plan-check" v-if="product.isSelected">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                        class="w-4 h-4 text-white">
                                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="plan-price-box">
                                <div class="plan-daily-price">
                                    {{ getSelectedCurrencySymbol() }}{{ convertPrice(product.total / numberOfRentalDays,
                                        'USD').toFixed(2) }}
                                    <span>/day</span>
                                </div>
                                <div class="plan-total-price">
                                    Total: {{ getSelectedCurrencySymbol() }}{{ convertPrice(product.total,
                                        'USD').toFixed(2) }}
                                </div>
                            </div>
                            <ul class="plan-features">
                                <li v-for="benefit in product.benefits" :key="benefit">
                                    <img :src="check" class="w-4 h-4 opacity-100" />
                                    <span :class="{ 'font-semibold text-gray-900': isKeyBenefit(benefit) }">{{ benefit
                                    }}</span>
                                </li>
                            </ul>
                            <button class="plan-select-btn">
                                {{ product.isSelected ? 'Selected' : 'Select Package' }}
                            </button>
                        </div>

                    </template>

                    <!-- Internal Vehicles Vendor Plans -->
                    <template v-if="vehicle.source === 'internal'">
                        <div v-for="product in internalVendorPlans" :key="product.type" class="plan-card"
                            :class="{ 'selected': product.isSelected }" @click="selectInternalVendorPlan(product)">
                            <div class="plan-header">
                                <div>
                                    <h3 class="plan-name">{{ product.name }}</h3>
                                    <p class="plan-type">{{ product.subtitle }}</p>
                                </div>
                                <div class="plan-check" v-if="product.isSelected">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                        class="w-4 h-4 text-white">
                                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="plan-price-box">
                                <div class="plan-daily-price">
                                    {{ getSelectedCurrencySymbol() }}{{ convertPrice(product.price_per_day,
                                        product.currency).toFixed(2) }}
                                    <span>/day</span>
                                </div>
                                <div class="plan-total-price">
                                    Total: {{ getSelectedCurrencySymbol() }}{{ convertPrice(product.total,
                                        product.currency).toFixed(2) }}
                                </div>
                            </div>
                            <ul class="plan-features">
                                <li v-for="benefit in product.benefits" :key="benefit">
                                    <img :src="check" class="w-4 h-4 opacity-100" />
                                    <span :class="{ 'font-semibold text-gray-900': isKeyBenefit(benefit) }">{{ benefit
                                    }}</span>
                                </li>
                                <li v-if="product.deposit">
                                    <img :src="check" class="w-4 h-4 opacity-100" />
                                    <span :class="{ 'font-semibold text-gray-900': isKeyBenefit('Deposit') }">Deposit:
                                        {{ getSelectedCurrencySymbol() }}{{ convertPrice(product.deposit,
                                            product.currency).toFixed(2) }}
                                    </span>
                                </li>
                            </ul>
                            <button class="plan-select-btn">
                                {{ product.isSelected ? 'Selected' : 'Select Package' }}
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
/* ============================================
   VROOEM DESIGN SYSTEM - Car Cards
   ============================================ */

.car-card {
    background: var(--white);
    border-radius: var(--radius-xl);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    transition: all var(--duration-base) var(--ease-out);
    border: 1px solid var(--gray-100);
    display: flex;
    flex-direction: column;
}

.car-card.list-view {
    flex-direction: row;
}

@media (max-width: 640px) {
    .car-card.list-view {
        flex-direction: column;
    }
}

.car-card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-4px);
    border-color: var(--primary-100);
}

.price-skeleton {
    display: inline-block;
    width: 74px;
    height: 18px;
    border-radius: 999px;
    background: linear-gradient(90deg, #f1f5f9 0%, #e2e8f0 50%, #f1f5f9 100%);
    background-size: 200% 100%;
    animation: shimmer 1.4s ease-in-out infinite;
}

.header-btn.primary.is-loading {
    opacity: 0.6;
    cursor: not-allowed;
    pointer-events: none;
}

@keyframes shimmer {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}

/* Card Image */
.car-image {
    position: relative;
    height: 180px;
    background: linear-gradient(135deg, var(--gray-100) 0%, var(--gray-50) 100%);
    overflow: hidden;
    flex-shrink: 0;
}

.car-card.list-view .car-image {
    width: 300px;
    height: auto;
    min-height: 200px;
}

@media (max-width: 640px) {
    .car-card.list-view .car-image {
        width: 100%;
        height: 180px;
    }
}

.car-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform var(--duration-base) var(--ease-out);
}

.car-card:hover .car-image img {
    transform: scale(1.05);
}

.car-badges {
    position: absolute;
    top: var(--space-3);
    left: var(--space-3);
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    z-index: 2;
}

.car-badge {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
    padding: 4px 10px;
    border-radius: var(--radius-full);
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

.car-badge.category {
    background: rgba(255, 255, 255, 0.95);
    color: var(--primary-700);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    backdrop-filter: blur(4px);
}

.car-badge.deal {
    background: var(--success-500);
    color: var(--white);
    box-shadow: 0 2px 4px rgba(22, 163, 74, 0.2);
}

.car-favorite {
    position: absolute;
    top: var(--space-3);
    right: var(--space-3);
    width: 36px;
    height: 36px;
    background: rgba(255, 255, 255, 0.95);
    border: none;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    color: var(--gray-400);
    transition: all var(--duration-fast) var(--ease-out);
    z-index: 2;
    backdrop-filter: blur(4px);
}

.car-favorite:hover {
    color: #ef4444;
    transform: scale(1.1);
    background: #fff;
    box-shadow: 0 6px 16px rgba(239, 68, 68, 0.15);
}

.car-favorite.is-active {
    color: #ef4444;
    background: #fff;
}

.car-favorite.is-loading {
    cursor: wait;
}

.car-favorite.is-loading::after {
    content: "";
    position: absolute;
    inset: -5px;
    border-radius: 999px;
    border: 2px solid rgba(15, 23, 42, 0.18);
    border-top-color: rgba(15, 23, 42, 0.7);
    animation: favorite-spin 0.8s linear infinite;
}

@keyframes favorite-spin {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

/* Slider Styles */
.slider-container {
    position: relative;
    width: 100%;
    height: 100%;
}

.slider-controls {
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    transform: translateY(-50%);
    display: flex;
    justify-content: space-between;
    padding: 0 var(--space-2);
    opacity: 0;
    transition: opacity var(--duration-base);
    pointer-events: none;
    z-index: 5;
}

.car-image:hover .slider-controls {
    opacity: 1;
}

.slider-arrow {
    width: 32px;
    height: 32px;
    background: rgba(255, 255, 255, 0.9);
    border: none;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
    color: var(--primary-700);
    transition: all var(--duration-fast);
    pointer-events: auto;
}

.slider-arrow:hover {
    background: var(--white);
    color: var(--primary-500);
    transform: scale(1.1);
}

.slider-arrow:active {
    transform: scale(0.95);
}

.slider-dots {
    position: absolute;
    bottom: var(--space-3);
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 6px;
    z-index: 5;
    padding: 4px 8px;
    background: rgba(0, 0, 0, 0.3);
    border-radius: var(--radius-full);
    backdrop-filter: blur(4px);
}

.slider-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.5);
    border: none;
    padding: 0;
    cursor: pointer;
    transition: all var(--duration-base);
}

.slider-dot.active {
    background: var(--white);
    width: 14px;
}

/* Card Content */
.car-content {
    padding: var(--space-5);
    flex: 1;
    display: flex;
    flex-direction: column;
}

.car-header {
    margin-bottom: var(--space-4);
}

.car-name {
    font-family: 'Outfit', sans-serif;
    font-size: 18px;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 2px;
    line-height: 1.3;
}

.car-class {
    font-size: 13px;
    font-weight: 500;
    color: var(--gray-500);
    text-transform: capitalize;
}

/* Specs */
.car-specs {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: var(--space-4);
    padding-bottom: var(--space-4);
    border-bottom: 1px solid var(--gray-100);
}

.spec-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    background: var(--gray-50);
    border: 1px solid var(--gray-100);
    border-radius: 8px;
    font-size: 12px;
    font-weight: 500;
    color: var(--gray-700);
}

/* Features */
.car-features {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: auto;
    /* Push footer down */
    padding-bottom: var(--space-5);
}

.feature-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 8px;
    font-size: 11px;
    font-weight: 600;
    border-radius: 6px;
}

.feature-tag.included {
    background: #f0fdf4;
    color: #16a34a;
}

.feature-tag.info {
    background: #f0f9ff;
    color: #0284c7;
}

/* Card Footer */
.car-footer {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    margin-top: var(--space-2);
    padding-top: var(--space-4);
    border-top: 1px solid var(--gray-100);
}

@media (max-width: 480px) {
    .car-footer {
        flex-direction: column;
        align-items: stretch;
        gap: var(--space-4);
    }

    .car-pricing {
        flex-direction: row;
        align-items: baseline;
        justify-content: space-between;
    }
}

.car-pricing {
    display: flex;
    flex-direction: column;
}

.car-total {
    display: flex;
    align-items: baseline;
    gap: 2px;
}

.car-price {
    font-family: 'Outfit', sans-serif;
    font-size: 22px;
    font-weight: 700;
    color: var(--gray-900);
    letter-spacing: -0.02em;
}

.car-currency {
    font-size: 13px;
    font-weight: 500;
    color: var(--gray-500);
}

.header-btn.primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    background: var(--primary-800);
    color: white;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s;
}

.header-btn.primary:hover {
    background: var(--primary-900);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(21, 59, 79, 0.2);
}

.header-btn.primary svg {
    width: 16px;
    height: 16px;
}

/* Modal Styling from Vrooem */
.plans-modal {
    position: fixed;
    inset: 0;
    z-index: 3000;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.plans-modal-content {
    background: white;
    width: 100%;
    max-width: 900px;
    max-height: 90vh;
    border-radius: 20px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    padding: 2rem;
    overflow-y: auto;
    position: relative;
}

@media (max-width: 640px) {
    .plans-modal-content {
        padding: 1.5rem;
        border-radius: 1.5rem 1.5rem 0 0;
        max-height: 85vh;
        position: absolute;
        bottom: 0;
    }

    .modal-close-btn {
        top: 1rem;
        right: 1rem;
    }
}

.modal-close-btn {
    position: absolute;
    top: 1.5rem;
    right: 1.5rem;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #f1f5f9;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.modal-close-btn:hover {
    background: #e2e8f0;
    color: #0f172a;
}

.plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.plan-card {
    border: 2px solid #e2e8f0;
    border-radius: 16px;
    padding: 1.5rem;
    cursor: pointer;
    transition: all 0.2s;
    background: white;
}

.plan-card:hover {
    border-color: var(--primary-300);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
}

.plan-card.selected {
    border-color: var(--primary-600);
    background: #f0f9ff;
    box-shadow: 0 0 0 2px rgba(21, 59, 79, 0.1);
}

.plan-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 1rem;
}

.plan-name {
    font-weight: 700;
    color: var(--gray-900);
    font-size: 1.125rem;
}

.plan-type {
    font-size: 0.875rem;
    color: var(--gray-500);
}

.plan-check {
    width: 24px;
    height: 24px;
    background: var(--primary-600);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.plan-price-box {
    padding-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
    margin-bottom: 1rem;
}

.plan-daily-price {
    font-weight: 700;
    font-size: 1.5rem;
    color: var(--primary-700);
}

.plan-daily-price span {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--gray-500);
}

.plan-total-price {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--gray-700);
    margin-top: 0.25rem;
}

.plan-features {
    list-style: none;
    padding: 0;
    margin: 0 0 1.5rem 0;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.plan-features li {
    display: flex;
    align-items: start;
    gap: 0.75rem;
    font-size: 0.875rem;
    color: var(--gray-600);
    margin-bottom: 0.5rem;
}

.plan-select-btn {
    width: 100%;
    padding: 0.75rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.2s;
}

.plan-card.selected .plan-select-btn {
    background: var(--primary-600);
    color: white;
}

.plan-card:not(.selected) .plan-select-btn {
    background: #f1f5f9;
    color: var(--gray-700);
}

.plan-card:not(.selected) .plan-select-btn:hover {
    background: #e2e8f0;
}
</style>
