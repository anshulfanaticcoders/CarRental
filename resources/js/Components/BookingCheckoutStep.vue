<script setup>
import { ref, computed, onMounted, unref } from 'vue';
import StripeCheckoutButton from './StripeCheckoutButton.vue';
import { usePage } from '@inertiajs/vue3';
import { useCurrencyConversion } from '@/composables/useCurrencyConversion';

const props = defineProps({
    vehicle: Object,
    package: String,
    protectionCode: String,
    protectionAmount: Number,
    extras: Object, // { extraId: qty }
    detailedExtras: Array, // Full extra objects with price/free status
    optionalExtras: Array, // Full extra objects
    pickupDate: String,
    pickupTime: String,
    dropoffDate: String,
    dropoffTime: String,
    pickupLocation: String,
    dropoffLocation: String,
    locationDetails: {
        type: Object,
        default: null
    },
    locationInstructions: String,
    driverRequirements: {
        type: Object,
        default: null
    },
    terms: {
        type: Array,
        default: null
    },
    numberOfDays: Number,
    currencySymbol: String,
    selectedCurrencyCode: String,
    paymentPercentage: Number,
    totals: Object, // { grandTotal, payableAmount, pendingAmount }
    vehicleTotal: [String, Number]
});

const emit = defineEmits(['back']);

const { convertPrice, getSelectedCurrencySymbol, fetchExchangeRates, selectedCurrency } = useCurrencyConversion();
const page = usePage();

// Pre-fill from auth user if available
const user = page.props.auth?.user || {};
const profile = user.profile || {};

const form = ref({
    name: user.name || [user.first_name, user.last_name].filter(Boolean).join(' ') || '',
    email: user.email || '',
    phone: user.phone || '',
    driver_age: '',
    driver_license_number: '',
    address: profile.address_line1 || '',
    city: profile.city || '',
    postal_code: profile.postal_code || '',
    country: profile.country || '',
    flight_number: '',
    preferred_day: '',
    notes: '',
});

const selectedPaymentMethod = ref('card');

const availablePaymentMethods = computed(() => {
    const currency = checkoutCurrency.value;
    const methods = [
        { id: 'card', name: 'Credit / Debit Card', icon: '💳', logos: ['visa', 'mastercard', 'amex', 'applepay', 'googlepay'] }
    ];

    if (currency === 'EUR') {
        methods.push({ id: 'bancontact', name: 'Bancontact', icon: '🇧🇪', logos: ['bancontact'] });
    }

    const klarnaCurrencies = ['EUR', 'USD', 'GBP', 'DKK', 'NOK', 'SEK', 'CHF'];
    if (klarnaCurrencies.includes(currency)) {
        methods.push({ id: 'klarna', name: 'Klarna', icon: '🎯', logos: ['klarna'] });
    }

    return methods;
});

const errors = ref({});

const validate = () => {
    errors.value = {};
    let isValid = true;

    if (!form.value.name.trim()) {
        errors.value.name = 'Full Name is required';
        isValid = false;
    }

    if (!form.value.email.trim()) {
        errors.value.email = 'Email is required';
        isValid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) {
        errors.value.email = 'Invalid email format';
        isValid = false;
    }

    if (!form.value.phone.trim()) {
        errors.value.phone = 'Phone Number is required';
        isValid = false;
    }

    if (!form.value.driver_age) {
        errors.value.driver_age = 'Driver Age is required';
        isValid = false;
    } else if (parseInt(form.value.driver_age) < 18) {
        errors.value.driver_age = 'Driver must be at least 18 years old';
        isValid = false;
    }

    if ((isOkMobility.value || isGreenMotion.value) && !form.value.driver_license_number.trim()) {
        errors.value.driver_license_number = 'Driver License Number is required for this provider';
        isValid = false;
    }

    if (isGreenMotion.value) {
        if (!form.value.address.trim()) {
            errors.value.address = 'Address is required for this provider';
            isValid = false;
        }
        if (!form.value.city.trim()) {
            errors.value.city = 'City is required for this provider';
            isValid = false;
        }
        if (!form.value.postal_code.trim()) {
            errors.value.postal_code = 'Postal code is required for this provider';
            isValid = false;
        }
        if (!form.value.country.trim()) {
            errors.value.country = 'Country is required for this provider';
            isValid = false;
        }
    }

    return isValid;
};

const isLocautoRent = computed(() => {
    return props.vehicle?.source === 'locauto_rent';
});

const isOkMobility = computed(() => {
    return props.vehicle?.source === 'okmobility';
});

const isGreenMotion = computed(() => {
    const source = props.vehicle?.source;
    return source === 'greenmotion' || source === 'usave';
});

const isRenteon = computed(() => {
    return props.vehicle?.source === 'renteon';
});

const providerMarkupRate = computed(() => {
    const rawRate = parseFloat(page.props.provider_markup_rate ?? '');
    if (Number.isFinite(rawRate) && rawRate >= 0) return rawRate;
    const rawPercent = parseFloat(page.props.provider_markup_percent ?? '');
    if (Number.isFinite(rawPercent) && rawPercent >= 0) return rawPercent / 100;
    return 0.15;
});

const effectivePaymentPercentage = computed(() => {
    if (isRenteon.value) return providerMarkupRate.value * 100;
    return props.paymentPercentage || 0;
});

const isInternal = computed(() => {
    return props.vehicle?.source === 'internal';
});

const displayVehicleName = computed(() => {
    if (isOkMobility.value) {
        return props.vehicle?.display_name || props.vehicle?.group_description || props.vehicle?.model || '';
    }
    const parts = [props.vehicle?.brand, props.vehicle?.model].filter(Boolean);
    return parts.join(' ');
});

const normalizeCurrencyCode = (currency) => {
    if (!currency) return 'EUR';
    const currencyMap = {
        '€': 'EUR',
        '$': 'USD',
        '£': 'GBP',
        '₹': 'INR',
        '₽': 'RUB',
        'A$': 'AUD',
        'C$': 'CAD',
        'د.إ': 'AED',
        '¥': 'JPY'
    };
    const trimmed = `${currency}`.trim();
    return (currencyMap[trimmed] || trimmed).toUpperCase();
};

const resolveVehicleCurrency = () => {
    return normalizeCurrencyCode(
        props.vehicle?.currency
        || props.vehicle?.vendor_profile?.currency
        || props.vehicle?.vendorProfile?.currency
        || props.vehicle?.benefits?.deposit_currency
        || 'EUR'
    );
};

const checkoutCurrency = computed(() => {
    const preferred = props.selectedCurrencyCode || selectedCurrency.value;
    return normalizeCurrencyCode(preferred || resolveVehicleCurrency());
});

const normalizeTotalValue = (value) => {
    const raw = unref(value);
    const numeric = parseFloat(raw ?? 0);
    return Number.isNaN(numeric) ? 0 : numeric;
};

const convertTotal = (value) => {
    const vehicleCurrency = resolveVehicleCurrency();
    return convertPrice(normalizeTotalValue(value), vehicleCurrency);
};

onMounted(() => {
    fetchExchangeRates();
});

// Get vehicle image (handles internal vehicles which use images array)
const vehicleImage = computed(() => {
    // Internal vehicles: find primary image from images array
    if (isInternal.value && props.vehicle?.images) {
        const primaryImg = props.vehicle.images.find(img => img.image_type === 'primary');
        if (primaryImg) return primaryImg.image_url;
        // Fallback to first gallery image
        const galleryImg = props.vehicle.images.find(img => img.image_type === 'gallery');
        if (galleryImg) return galleryImg.image_url;
    }
    // Other providers: use direct image property
    return props.vehicle?.image || props.vehicle?.largeImage || '/images/dummyCarImaage.png';
});

const bookingData = computed(() => {
    const pickupTime = isOkMobility.value
        ? (props.vehicle?.ok_mobility_pickup_time || props.pickupTime)
        : props.pickupTime;
    const dropoffTime = isOkMobility.value
        ? (props.vehicle?.ok_mobility_dropoff_time || props.dropoffTime)
        : props.dropoffTime;

    return {
        vehicle: props.vehicle,
        package: props.package,
        protection_code: props.protectionCode,
        protection_amount: props.protectionAmount || 0,
        extras: props.extras,
        detailed_extras: props.detailedExtras,
        optional_extras: props.optionalExtras || [],
        location_details: props.locationDetails || null,
        location_instructions: props.locationInstructions || null,
        driver_requirements: props.driverRequirements || null,
        terms: props.terms || null,
        customer: form.value,
        pickup_date: props.pickupDate,
        pickup_time: pickupTime,
        dropoff_date: props.dropoffDate,
        dropoff_time: dropoffTime,
        pickup_location: props.pickupLocation,
        dropoff_location: props.dropoffLocation,
        number_of_days: props.numberOfDays,
        total_amount: convertTotal(props.totals?.grandTotal),
        currency: checkoutCurrency.value,
        quoteid: props.vehicle.quoteid || null,
        rentalCode: props.vehicle.rentalCode || null,
        vehicle_total: convertTotal(props.vehicleTotal || 0),
        payment_method: selectedPaymentMethod.value
    };
});

// Helper to format currency
const formatPrice = (val) => {
    const currencyCode = resolveVehicleCurrency();
    const converted = convertPrice(parseFloat(val), currencyCode);
    return `${getSelectedCurrencySymbol()}${converted.toFixed(2)}`;
};
</script>

<template>
    <div id="checkout-form-section" class="font-['Outfit',sans-serif]">
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Left Column: Form Fields -->
            <div class="flex-1">
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-6">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Driver Information</h3>

                    <!-- 2-Column Form Layout -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Full Name -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Full Name *</label>
                            <input v-model="form.name" type="text"
                                class="w-full rounded-xl border-2 px-4 py-2.5 focus:outline-none focus:border-[#1e3a5f] transition-colors"
                                :class="{ 'border-red-500 bg-red-50': errors.name, 'border-gray-200': !errors.name }"
                                placeholder="John Doe" />
                            <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name }}</p>
                        </div>

                        <!-- Email -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Email Address *</label>
                            <input v-model="form.email" type="email"
                                class="w-full rounded-xl border-2 px-4 py-2.5 focus:outline-none focus:border-[#1e3a5f] transition-colors"
                                :class="{ 'border-red-500 bg-red-50': errors.email, 'border-gray-200': !errors.email }"
                                placeholder="john@example.com" />
                            <p v-if="errors.email" class="text-red-500 text-xs mt-1">{{ errors.email }}</p>
                        </div>

                        <!-- Phone -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Phone Number *</label>
                            <input v-model="form.phone" type="tel"
                                class="w-full rounded-xl border-2 px-4 py-2.5 focus:outline-none focus:border-[#1e3a5f] transition-colors"
                                :class="{ 'border-red-500 bg-red-50': errors.phone, 'border-gray-200': !errors.phone }"
                                placeholder="+34 612 345 678" />
                            <p v-if="errors.phone" class="text-red-500 text-xs mt-1">{{ errors.phone }}</p>
                        </div>

                        <!-- Driver Age -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Driver Age *</label>
                            <input v-model="form.driver_age" type="number" min="18" max="99"
                                class="w-full rounded-xl border-2 px-4 py-2.5 focus:outline-none focus:border-[#1e3a5f] transition-colors"
                                :class="{ 'border-red-500 bg-red-50': errors.driver_age, 'border-gray-200': !errors.driver_age }"
                                placeholder="30" />
                            <p v-if="errors.driver_age" class="text-red-500 text-xs mt-1">{{ errors.driver_age }}</p>
                        </div>

                        <!-- Driver License Number (OK Mobility) -->
                        <div v-if="isOkMobility || isGreenMotion">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Driver License Number *</label>
                            <input v-model="form.driver_license_number" type="text"
                                class="w-full rounded-xl border-2 px-4 py-2.5 focus:outline-none focus:border-[#1e3a5f] transition-colors"
                                :class="{ 'border-red-500 bg-red-50': errors.driver_license_number, 'border-gray-200': !errors.driver_license_number }"
                                placeholder="License Number" />
                            <p v-if="errors.driver_license_number" class="text-red-500 text-xs mt-1">{{ errors.driver_license_number }}</p>
                        </div>

                        <!-- Address -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Address<span v-if="isGreenMotion"> *</span></label>
                            <input v-model="form.address" type="text"
                                class="w-full rounded-xl border-2 px-4 py-2.5 focus:outline-none focus:border-[#1e3a5f] transition-colors"
                                :class="{ 'border-red-500 bg-red-50': errors.address, 'border-gray-200': !errors.address }"
                                placeholder="Street Address" />
                            <p v-if="errors.address" class="text-red-500 text-xs mt-1">{{ errors.address }}</p>
                        </div>

                        <!-- City -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">City<span v-if="isGreenMotion"> *</span></label>
                            <input v-model="form.city" type="text"
                                class="w-full rounded-xl border-2 px-4 py-2.5 focus:outline-none focus:border-[#1e3a5f] transition-colors"
                                :class="{ 'border-red-500 bg-red-50': errors.city, 'border-gray-200': !errors.city }"
                                placeholder="Madrid" />
                            <p v-if="errors.city" class="text-red-500 text-xs mt-1">{{ errors.city }}</p>
                        </div>

                        <!-- Postal Code -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Postal Code<span v-if="isGreenMotion"> *</span></label>
                            <input v-model="form.postal_code" type="text"
                                class="w-full rounded-xl border-2 px-4 py-2.5 focus:outline-none focus:border-[#1e3a5f] transition-colors"
                                :class="{ 'border-red-500 bg-red-50': errors.postal_code, 'border-gray-200': !errors.postal_code }"
                                placeholder="28001" />
                            <p v-if="errors.postal_code" class="text-red-500 text-xs mt-1">{{ errors.postal_code }}</p>
                        </div>

                        <!-- Country -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Country<span v-if="isGreenMotion"> *</span></label>
                            <input v-model="form.country" type="text"
                                class="w-full rounded-xl border-2 px-4 py-2.5 focus:outline-none focus:border-[#1e3a5f] transition-colors"
                                :class="{ 'border-red-500 bg-red-50': errors.country, 'border-gray-200': !errors.country }"
                                placeholder="Spain" />
                            <p v-if="errors.country" class="text-red-500 text-xs mt-1">{{ errors.country }}</p>
                        </div>

                        <!-- Flight Number -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Flight Number</label>
                            <input v-model="form.flight_number" type="text"
                                class="w-full rounded-xl border-2 border-gray-200 px-4 py-2.5 focus:outline-none focus:border-[#1e3a5f] transition-colors"
                                placeholder="FR1234" />
                            <p class="text-xs text-gray-400 mt-1">For airport pickup</p>
                        </div>

                        <!-- Preferred Day -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Preferred Contact Day</label>
                            <select v-model="form.preferred_day"
                                class="w-full rounded-xl border-2 border-gray-200 px-4 py-2.5 focus:outline-none focus:border-[#1e3a5f] transition-colors bg-white">
                                <option value="">Select day</option>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                                <option value="Sunday">Sunday</option>
                            </select>
                        </div>

                        <!-- Notes -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Special Requests /
                                Notes</label>
                            <textarea v-model="form.notes" rows="3"
                                class="w-full rounded-xl border-2 border-gray-200 px-4 py-2.5 focus:outline-none focus:border-[#1e3a5f] transition-colors resize-none"
                                placeholder="Any special requests or additional information..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Payment Method Selection -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-6">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#1e3a5f]" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        Select Payment Method
                    </h3>

                    <div class="space-y-4">
                        <div v-for="method in availablePaymentMethods" :key="method.id"
                            @click="selectedPaymentMethod = method.id"
                            class="relative flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all hover:bg-gray-50 bg-white"
                            :class="selectedPaymentMethod === method.id ? 'border-[#1e3a5f] bg-blue-50/30' : 'border-gray-100'">

                            <div class="flex-1 flex items-center gap-4">
                                <div
                                    class="w-10 h-10 rounded-full flex items-center justify-center text-xl bg-gray-100 shadow-sm">
                                    {{ method.icon }}
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900">{{ method.name }}</div>
                                    <div class="flex gap-2 mt-1 opacity-70">
                                        <!-- Simple indicators for logos -->
                                        <div v-if="method.id === 'card'" class="flex gap-1.5">
                                            <div class="h-4 w-6 bg-blue-800 rounded-sm"></div>
                                            <div class="h-4 w-6 bg-red-600 rounded-sm"></div>
                                            <div class="h-4 w-6 bg-blue-400 rounded-sm"></div>
                                            <div class="h-4 w-6 bg-black rounded-sm"></div>
                                        </div>
                                        <div v-if="method.id === 'bancontact'"
                                            class="h-4 w-10 bg-yellow-400 rounded-sm"></div>
                                        <div v-if="method.id === 'klarna'" class="h-4 w-10 bg-pink-400 rounded-sm">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center">
                                <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-colors shadow-inner"
                                    :class="selectedPaymentMethod === method.id ? 'bg-[#1e3a5f] border-[#1e3a5f]' : 'bg-white border-gray-200'">
                                    <svg v-if="selectedPaymentMethod === method.id" class="w-4 h-4 text-white"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Selection Highlight Ripple-like effect -->
                            <div v-if="selectedPaymentMethod === method.id"
                                class="absolute inset-0 border-4 border-[#1e3a5f]/10 rounded-xl pointer-events-none">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Back Button -->
                <button @click="$emit('back')"
                    class="w-full px-6 py-3 bg-white border-2 border-gray-200 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 hover:border-gray-300 transition-all">
                    ← Back to Extras
                </button>
            </div>

            <!-- Right Column: Summary -->
            <div class="lg:w-96">
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 sticky top-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4 pb-3 border-b-2">Booking Summary</h3>

                    <!-- Vehicle -->
                    <div class="flex items-start gap-4 mb-5 pb-4 border-b border-gray-100">
                        <img :src="vehicleImage" alt="Car"
                            class="w-24 h-16 object-cover rounded-xl bg-gray-50 shadow-sm" />
                        <div class="flex-1">
                            <div class="font-bold text-gray-900">{{ displayVehicleName }}</div>
                            <div class="text-sm text-gray-500 mt-0.5">{{ package }} Package</div>
                            <div class="text-xs text-gray-400 mt-1">{{ numberOfDays }} days rental</div>
                        </div>
                    </div>

                    <!-- Dates -->
                    <div class="space-y-3 mb-5 pb-4 border-b border-gray-100">
                        <div class="flex items-start gap-3">
                            <div
                                class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-emerald-700 uppercase">Pickup</div>
                                <div class="text-sm font-medium text-gray-900">{{ pickupDate }} at {{ pickupTime }}
                                </div>
                                <div class="text-xs text-gray-500">{{ pickupLocation }}</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div
                                class="w-8 h-8 rounded-full bg-rose-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-rose-700 uppercase">Dropoff</div>
                                <div class="text-sm font-medium text-gray-900">{{ dropoffDate }} at {{ dropoffTime }}
                                </div>
                                <div class="text-xs text-gray-500">{{ dropoffLocation }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Financials -->
                    <div class="space-y-3 mb-5">
                        <div class="flex justify-between text-base">
                            <span class="text-gray-600 font-medium">Total Amount</span>
                            <span class="font-bold text-gray-900">{{ formatPrice(totals.grandTotal) }}</span>
                        </div>

                        <div class="bg-gradient-to-r from-emerald-50 to-teal-50 p-4 rounded-xl">
                            <div class="flex justify-between items-center">
                                <div>
                                    <div class="text-sm font-semibold text-emerald-800">Pay Now</div>
                                    <div class="text-xs text-emerald-600">{{ effectivePaymentPercentage }}% deposit</div>
                                </div>
                                <span class="text-2xl font-bold text-emerald-700">{{ formatPrice(totals.payableAmount)
                                }}</span>
                            </div>
                        </div>

                        <div class="flex justify-between text-sm text-gray-500 px-1">
                            <span>Pay on Arrival</span>
                            <span class="font-semibold text-gray-700">{{ formatPrice(totals.pendingAmount) }}</span>
                        </div>
                    </div>

                    <!-- Stripe Button -->
                    <div class="space-y-3">
                        <div v-if="form.name && form.email && form.phone && form.driver_age">
                            <StripeCheckoutButton v-if="!Object.keys(errors).length" :booking-data="bookingData"
                                :label="`Pay ${formatPrice(totals.payableAmount)}`" />
                            <button v-else @click="validate()"
                                class="w-full bg-gray-200 text-gray-500 py-4 rounded-xl font-bold cursor-pointer">
                                Please Fix Errors
                            </button>
                        </div>
                        <button v-else @click="validate()"
                            class="w-full bg-gray-200 text-gray-500 py-4 rounded-xl font-bold cursor-pointer">
                            Complete All Fields
                        </button>

                        <p class="text-xs text-center text-gray-400 leading-relaxed">
                            By proceeding, you agree to our Terms & Conditions and Privacy Policy.
                            Secure payment powered by Stripe.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
