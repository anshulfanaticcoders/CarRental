<script setup>
import { ref, computed, onMounted, unref, watch } from 'vue';
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
    vehicleTotal: [String, Number],
    searchSessionId: String, // For price verification
    selectedDepositType: { type: String, default: null },
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

// Clear individual field errors instantly when user types
watch(() => form.value.name, () => { if (errors.value.name) delete errors.value.name; });
watch(() => form.value.email, () => { if (errors.value.email) delete errors.value.email; });
watch(() => form.value.phone, () => { if (errors.value.phone) delete errors.value.phone; });
watch(() => form.value.driver_age, () => { if (errors.value.driver_age) delete errors.value.driver_age; });
watch(() => form.value.driver_license_number, () => { if (errors.value.driver_license_number) delete errors.value.driver_license_number; });
watch(() => form.value.address, () => { if (errors.value.address) delete errors.value.address; });
watch(() => form.value.city, () => { if (errors.value.city) delete errors.value.city; });
watch(() => form.value.postal_code, () => { if (errors.value.postal_code) delete errors.value.postal_code; });
watch(() => form.value.country, () => { if (errors.value.country) delete errors.value.country; });

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
    // Default to 15% if not provided, to prevent "Pay 0" bug
    return props.paymentPercentage || 15;
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
        payment_method: selectedPaymentMethod.value,
        search_session_id: props.searchSessionId, // For price verification
        selected_deposit_type: props.selectedDepositType || null,
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
    <div id="checkout-form-section" class="checkout-form font-['IBM_Plex_Sans',sans-serif]">
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Left Column: Form Fields -->
            <div class="flex-1">
                <!-- Driver Information -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden mb-6">
                    <div class="px-6 pt-6 pb-4 border-b border-gray-100 bg-gradient-to-r from-[#1e3a5f]/[0.03] to-transparent">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-[#1e3a5f]/10 flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#1e3a5f]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-[#1e3a5f]">Driver Information</h3>
                                <p class="text-xs text-gray-400 mt-0.5">Please fill in the driver's details</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-5 gap-y-4">
                            <!-- Full Name -->
                            <div class="md:col-span-2 form-field-group">
                                <label class="form-label">
                                    <svg class="form-label-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    Full Name <span class="text-red-400">*</span>
                                </label>
                                <div class="form-input-wrap" :class="{ 'has-error': errors.name, 'has-value': form.name }">
                                    <svg class="form-input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                                    <input v-model="form.name" type="text" class="form-input" placeholder="John Doe" />
                                </div>
                                <Transition name="field-error">
                                    <p v-if="errors.name" class="form-error">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                                        {{ errors.name }}
                                    </p>
                                </Transition>
                            </div>

                            <!-- Email -->
                            <div class="md:col-span-2 form-field-group">
                                <label class="form-label">
                                    <svg class="form-label-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
                                    Email Address <span class="text-red-400">*</span>
                                </label>
                                <div class="form-input-wrap" :class="{ 'has-error': errors.email, 'has-value': form.email }">
                                    <svg class="form-input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
                                    <input v-model="form.email" type="email" class="form-input" placeholder="john@example.com" />
                                </div>
                                <Transition name="field-error">
                                    <p v-if="errors.email" class="form-error">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                                        {{ errors.email }}
                                    </p>
                                </Transition>
                            </div>

                            <!-- Phone -->
                            <div class="form-field-group">
                                <label class="form-label">
                                    <svg class="form-label-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                                    Phone Number <span class="text-red-400">*</span>
                                </label>
                                <div class="form-input-wrap" :class="{ 'has-error': errors.phone, 'has-value': form.phone }">
                                    <svg class="form-input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                                    <input v-model="form.phone" type="tel" class="form-input" placeholder="+34 612 345 678" />
                                </div>
                                <Transition name="field-error">
                                    <p v-if="errors.phone" class="form-error">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                                        {{ errors.phone }}
                                    </p>
                                </Transition>
                            </div>

                            <!-- Driver Age -->
                            <div class="form-field-group">
                                <label class="form-label">
                                    <svg class="form-label-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                                    Driver Age <span class="text-red-400">*</span>
                                </label>
                                <div class="form-input-wrap" :class="{ 'has-error': errors.driver_age, 'has-value': form.driver_age }">
                                    <svg class="form-input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                                    <input v-model="form.driver_age" type="number" min="18" max="99" class="form-input" placeholder="30" />
                                </div>
                                <Transition name="field-error">
                                    <p v-if="errors.driver_age" class="form-error">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                                        {{ errors.driver_age }}
                                    </p>
                                </Transition>
                            </div>

                            <!-- Driver License Number -->
                            <div v-if="isOkMobility || isGreenMotion" class="form-field-group">
                                <label class="form-label">
                                    <svg class="form-label-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" /></svg>
                                    Driver License Number <span class="text-red-400">*</span>
                                </label>
                                <div class="form-input-wrap" :class="{ 'has-error': errors.driver_license_number, 'has-value': form.driver_license_number }">
                                    <svg class="form-input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" /></svg>
                                    <input v-model="form.driver_license_number" type="text" class="form-input" placeholder="License Number" />
                                </div>
                                <Transition name="field-error">
                                    <p v-if="errors.driver_license_number" class="form-error">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                                        {{ errors.driver_license_number }}
                                    </p>
                                </Transition>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Address Information -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden mb-6">
                    <div class="px-6 pt-6 pb-4 border-b border-gray-100 bg-gradient-to-r from-[#1e3a5f]/[0.03] to-transparent">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-[#1e3a5f]/10 flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#1e3a5f]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-[#1e3a5f]">Address & Travel Details</h3>
                                <p class="text-xs text-gray-400 mt-0.5">{{ isGreenMotion ? 'Required for this provider' : 'Optional but recommended' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-5 gap-y-4">
                            <!-- Address -->
                            <div class="md:col-span-2 form-field-group">
                                <label class="form-label">
                                    <svg class="form-label-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                                    Street Address <span v-if="isGreenMotion" class="text-red-400">*</span>
                                </label>
                                <div class="form-input-wrap" :class="{ 'has-error': errors.address, 'has-value': form.address }">
                                    <svg class="form-input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                                    <input v-model="form.address" type="text" class="form-input" placeholder="123 Main Street" />
                                </div>
                                <Transition name="field-error">
                                    <p v-if="errors.address" class="form-error">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                                        {{ errors.address }}
                                    </p>
                                </Transition>
                            </div>

                            <!-- City -->
                            <div class="form-field-group">
                                <label class="form-label">
                                    <svg class="form-label-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                                    City <span v-if="isGreenMotion" class="text-red-400">*</span>
                                </label>
                                <div class="form-input-wrap" :class="{ 'has-error': errors.city, 'has-value': form.city }">
                                    <svg class="form-input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                                    <input v-model="form.city" type="text" class="form-input" placeholder="Madrid" />
                                </div>
                                <Transition name="field-error">
                                    <p v-if="errors.city" class="form-error">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                                        {{ errors.city }}
                                    </p>
                                </Transition>
                            </div>

                            <!-- Postal Code -->
                            <div class="form-field-group">
                                <label class="form-label">
                                    <svg class="form-label-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 9v.906a2.25 2.25 0 01-1.183 1.981l-6.478 3.488M2.25 9v.906a2.25 2.25 0 001.183 1.981l6.478 3.488m8.839 2.51l-4.66-2.51m0 0l-1.023-.55a2.25 2.25 0 00-2.134 0l-1.022.55m0 0l-4.661 2.51m16.5 1.615a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V8.844a2.25 2.25 0 011.183-1.981l7.5-4.039a2.25 2.25 0 012.134 0l7.5 4.039a2.25 2.25 0 011.183 1.98V19.5z" /></svg>
                                    Postal Code <span v-if="isGreenMotion" class="text-red-400">*</span>
                                </label>
                                <div class="form-input-wrap" :class="{ 'has-error': errors.postal_code, 'has-value': form.postal_code }">
                                    <svg class="form-input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 9v.906a2.25 2.25 0 01-1.183 1.981l-6.478 3.488M2.25 9v.906a2.25 2.25 0 001.183 1.981l6.478 3.488m8.839 2.51l-4.66-2.51m0 0l-1.023-.55a2.25 2.25 0 00-2.134 0l-1.022.55m0 0l-4.661 2.51m16.5 1.615a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V8.844a2.25 2.25 0 011.183-1.981l7.5-4.039a2.25 2.25 0 012.134 0l7.5 4.039a2.25 2.25 0 011.183 1.98V19.5z" /></svg>
                                    <input v-model="form.postal_code" type="text" class="form-input" placeholder="28001" />
                                </div>
                                <Transition name="field-error">
                                    <p v-if="errors.postal_code" class="form-error">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                                        {{ errors.postal_code }}
                                    </p>
                                </Transition>
                            </div>

                            <!-- Country -->
                            <div class="md:col-span-2 form-field-group">
                                <label class="form-label">
                                    <svg class="form-label-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 003 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                                    Country <span v-if="isGreenMotion" class="text-red-400">*</span>
                                </label>
                                <div class="form-input-wrap" :class="{ 'has-error': errors.country, 'has-value': form.country }">
                                    <svg class="form-input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 003 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                                    <input v-model="form.country" type="text" class="form-input" placeholder="Spain" />
                                </div>
                                <Transition name="field-error">
                                    <p v-if="errors.country" class="form-error">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                                        {{ errors.country }}
                                    </p>
                                </Transition>
                            </div>

                            <!-- Flight Number -->
                            <div class="form-field-group">
                                <label class="form-label">
                                    <svg class="form-label-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
                                    Flight Number
                                </label>
                                <div class="form-input-wrap" :class="{ 'has-value': form.flight_number }">
                                    <svg class="form-input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
                                    <input v-model="form.flight_number" type="text" class="form-input" placeholder="FR1234" />
                                </div>
                                <p class="text-[11px] text-gray-400 mt-1 pl-1">For airport pickup tracking</p>
                            </div>

                            <!-- Preferred Day -->
                            <div class="form-field-group">
                                <label class="form-label">
                                    <svg class="form-label-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    Preferred Contact Day
                                </label>
                                <div class="form-input-wrap" :class="{ 'has-value': form.preferred_day }">
                                    <svg class="form-input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <select v-model="form.preferred_day" class="form-input form-select">
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
                            </div>

                            <!-- Notes -->
                            <div class="md:col-span-2 form-field-group">
                                <label class="form-label">
                                    <svg class="form-label-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                    Special Requests / Notes
                                </label>
                                <div class="form-input-wrap form-textarea-wrap" :class="{ 'has-value': form.notes }">
                                    <svg class="form-input-icon !top-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                    <textarea v-model="form.notes" rows="3" class="form-input resize-none" placeholder="Any special requests or additional information..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Method Selection -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden mb-6">
                    <div class="px-6 pt-6 pb-4 border-b border-gray-100 bg-gradient-to-r from-[#1e3a5f]/[0.03] to-transparent">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-[#1e3a5f]/10 flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#1e3a5f]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-[#1e3a5f]">Payment Method</h3>
                                <p class="text-xs text-gray-400 mt-0.5">Choose how you'd like to pay</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-3">
                        <!-- Credit / Debit Card -->
                        <div @click="selectedPaymentMethod = 'card'"
                            class="pay-method-card"
                            :class="selectedPaymentMethod === 'card' ? 'pay-method-active' : 'pay-method-idle'">
                            <!-- Selection indicator -->
                            <div class="pay-radio">
                                <div class="pay-radio-dot" :class="{ 'pay-radio-checked': selectedPaymentMethod === 'card' }">
                                    <Transition name="check-pop">
                                        <svg v-if="selectedPaymentMethod === 'card'" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    </Transition>
                                </div>
                            </div>
                            <!-- Icon -->
                            <div class="pay-icon-area" :class="selectedPaymentMethod === 'card' ? 'pay-icon-active' : ''">
                                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <rect x="2" y="5" width="20" height="14" rx="3" />
                                    <path d="M2 10h20" stroke-width="2" />
                                    <rect x="5" y="14" width="4" height="2" rx="0.5" opacity="0.5" />
                                    <rect x="11" y="14" width="3" height="2" rx="0.5" opacity="0.3" />
                                </svg>
                            </div>
                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-[15px] text-gray-900">Credit / Debit Card</div>
                                <div class="text-[11px] text-gray-400 mt-0.5">Visa, Mastercard, Amex & more</div>
                                <!-- Brand logos -->
                                <div class="flex items-center gap-1.5 mt-2">
                                    <!-- Visa -->
                                    <div class="pay-brand-chip bg-[#1a1f71]">
                                        <svg viewBox="0 0 48 16" class="h-3 w-auto"><path d="M19.4 1l-3.8 14h-3.1L16.3 1h3.1zm15.4 9l1.6-4.5.9 4.5h-2.5zm3.5 5l.4-2h3.2l.2 1.1.1.9h2.8L42.5 1h-2.8c-.6 0-1.1.4-1.3 1L33.8 15h3.1l.6-1.7h3.8l.3 1.7h2.7zm-8-9.2c0 2.7 3.8 2.8 3.8 4.2 0 .6-.5 1.1-1.5 1.1-1.3 0-2.3-.5-2.9-.9l-.5 2.4c.7.3 1.9.6 3.1.6 3 0 4.9-1.5 4.9-3.7 0-3.5-3.8-3.5-3.8-5 0-.5.4-1 1.4-1 1 0 1.9.4 2.4.7l.5-2.3c-.6-.3-1.6-.5-2.7-.5-2.8 0-4.7 1.5-4.7 3.4zM16 1l-5 14h-3.2L5 3.5C4.8 2.7 4.6 2.4 4 2c-1-.5-2.6-1-4-1.3l.1-.5h5c.7 0 1.3.5 1.4 1.2l1.2 6.6L11 1.2 16 1z" fill="white"/></svg>
                                    </div>
                                    <!-- Mastercard -->
                                    <div class="pay-brand-chip bg-[#252525]">
                                        <svg viewBox="0 0 40 24" class="h-3.5 w-auto"><circle cx="15" cy="12" r="7" fill="#EB001B"/><circle cx="25" cy="12" r="7" fill="#F79E1B"/><path d="M20 6.8a7 7 0 010 10.4 7 7 0 000-10.4z" fill="#FF5F00"/></svg>
                                    </div>
                                    <!-- Amex -->
                                    <div class="pay-brand-chip bg-[#006FCF]">
                                        <svg viewBox="0 0 40 14" class="h-2.5 w-auto"><text x="2" y="11" fill="white" font-family="Arial" font-weight="bold" font-size="10">AMEX</text></svg>
                                    </div>
                                    <!-- Apple Pay -->
                                    <div class="pay-brand-chip bg-black">
                                        <svg viewBox="0 0 50 20" class="h-3 w-auto"><path d="M9.2 3.2c-.5.6-1.3 1.1-2.1 1-.1-.8.3-1.6.7-2.1.5-.6 1.4-1 2-1 .1.8-.2 1.6-.6 2.1zM10.7 4.4c-1.2-.1-2.2.7-2.7.7s-1.4-.6-2.4-.6c-1.2 0-2.3.7-3 1.8-1.3 2.2-.3 5.5.9 7.3.6.9 1.4 1.9 2.3 1.8 1-.1 1.3-.6 2.4-.6s1.4.6 2.4.6c1 0 1.7-.9 2.3-1.8.7-1 1-2 1-2.1-1.2-.5-1.4-2.8-.2-4.3-.8-1-2-1.5-3-1.5-.2-.3.3-1 0-1.3z" fill="white"/><text x="16" y="13" fill="white" font-family="Arial" font-weight="600" font-size="9">Pay</text></svg>
                                    </div>
                                    <!-- Google Pay -->
                                    <div class="pay-brand-chip bg-white border border-gray-200">
                                        <svg viewBox="0 0 40 16" class="h-2.5 w-auto"><text x="1" y="12" fill="#5f6368" font-family="Arial" font-weight="500" font-size="9">G</text><text x="9" y="12" fill="#5f6368" font-family="Arial" font-weight="400" font-size="8">Pay</text></svg>
                                    </div>
                                </div>
                            </div>
                            <!-- Recommended badge -->
                            <div v-if="selectedPaymentMethod === 'card'" class="absolute -top-px -right-px">
                                <div class="bg-[#1e3a5f] text-white text-[9px] font-bold px-2.5 py-1 rounded-bl-lg rounded-tr-xl tracking-wide uppercase">Recommended</div>
                            </div>
                        </div>

                        <!-- Bancontact -->
                        <div v-if="availablePaymentMethods.find(m => m.id === 'bancontact')"
                            @click="selectedPaymentMethod = 'bancontact'"
                            class="pay-method-card"
                            :class="selectedPaymentMethod === 'bancontact' ? 'pay-method-active' : 'pay-method-idle'">
                            <div class="pay-radio">
                                <div class="pay-radio-dot" :class="{ 'pay-radio-checked': selectedPaymentMethod === 'bancontact' }">
                                    <Transition name="check-pop">
                                        <svg v-if="selectedPaymentMethod === 'bancontact'" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    </Transition>
                                </div>
                            </div>
                            <div class="pay-icon-area" :class="selectedPaymentMethod === 'bancontact' ? 'pay-icon-active' : ''">
                                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none">
                                    <rect x="2" y="4" width="20" height="16" rx="3" fill="#005498"/>
                                    <path d="M6 10h5l2 2-2 2H6v-4z" fill="#FFD800"/>
                                    <circle cx="16" cy="12" r="2.5" fill="white" opacity="0.9"/>
                                    <circle cx="16" cy="12" r="1.5" fill="#005498"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-[15px] text-gray-900">Bancontact</div>
                                <div class="text-[11px] text-gray-400 mt-0.5">Belgian bank payment</div>
                                <div class="flex items-center gap-1.5 mt-2">
                                    <div class="pay-brand-chip bg-[#005498]">
                                        <svg viewBox="0 0 60 14" class="h-2.5 w-auto"><text x="2" y="11" fill="white" font-family="Arial" font-weight="bold" font-size="9">Bancontact</text></svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Klarna -->
                        <div v-if="availablePaymentMethods.find(m => m.id === 'klarna')"
                            @click="selectedPaymentMethod = 'klarna'"
                            class="pay-method-card"
                            :class="selectedPaymentMethod === 'klarna' ? 'pay-method-active' : 'pay-method-idle'">
                            <div class="pay-radio">
                                <div class="pay-radio-dot" :class="{ 'pay-radio-checked': selectedPaymentMethod === 'klarna' }">
                                    <Transition name="check-pop">
                                        <svg v-if="selectedPaymentMethod === 'klarna'" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    </Transition>
                                </div>
                            </div>
                            <div class="pay-icon-area" :class="selectedPaymentMethod === 'klarna' ? 'pay-icon-active' : ''">
                                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none">
                                    <rect x="2" y="4" width="20" height="16" rx="3" fill="#FFB3C7"/>
                                    <text x="5" y="15" fill="#0A0B09" font-family="Arial" font-weight="900" font-size="8">K.</text>
                                    <circle cx="17" cy="14" r="2" fill="#0A0B09"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-[15px] text-gray-900">Klarna</div>
                                <div class="text-[11px] text-gray-400 mt-0.5">Buy now, pay later</div>
                                <div class="flex items-center gap-1.5 mt-2">
                                    <div class="pay-brand-chip bg-[#FFB3C7]">
                                        <svg viewBox="0 0 50 14" class="h-2.5 w-auto"><text x="2" y="11" fill="#0A0B09" font-family="Arial" font-weight="bold" font-size="10">Klarna</text></svg>
                                    </div>
                                    <span class="text-[10px] text-gray-400 ml-1">Pay in 3 installments</span>
                                </div>
                            </div>
                        </div>

                        <!-- Secure payment strip -->
                        <div class="flex items-center justify-between pt-3 mt-1 border-t border-gray-100">
                            <div class="flex items-center gap-1.5 text-gray-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                                <span class="text-[11px] font-medium">256-bit SSL encrypted</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="text-[11px] text-gray-400">Powered by</span>
                                <svg class="h-4 w-auto" viewBox="0 0 60 25" fill="none"><path d="M5 11.2C5 7.5 7.7 5 11.2 5c2 0 3.4.9 4.2 2l-1.8 1.3c-.5-.7-1.3-1.2-2.3-1.2-2.2 0-3.7 1.7-3.7 4 0 2.4 1.5 4.1 3.7 4.1 1.4 0 2.3-.7 2.7-1.5h-3v-2h5.5c.1.3.1.7.1 1.1 0 3.3-2.2 5.5-5.3 5.5C7.7 18.3 5 15.5 5 11.2z" fill="#635BFF"/><text x="18" y="16" fill="#635BFF" font-family="Arial" font-weight="700" font-size="12">Stripe</text></svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Back Button -->
                <button @click="$emit('back')"
                    class="w-full px-6 py-3.5 bg-white border-2 border-gray-200 text-gray-600 rounded-xl font-semibold hover:bg-gray-50 hover:border-[#1e3a5f]/20 hover:text-[#1e3a5f] transition-all duration-200 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                    Back to Extras
                </button>
            </div>

            <!-- Right Column: Summary -->
            <div class="lg:w-96">
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 sticky top-[5rem]">
                    <h3 class="text-lg font-bold text-[#1e3a5f] mb-4 pb-3 border-b border-gray-100">Booking Summary</h3>

                    <!-- Vehicle -->
                    <div class="flex items-start gap-4 mb-5 pb-4 border-b border-gray-100">
                        <img :src="vehicleImage" alt="Car"
                            class="w-24 h-16 object-cover rounded-xl bg-gray-50 shadow-sm" />
                        <div class="flex-1">
                            <div class="font-bold text-gray-900 text-[15px]">{{ displayVehicleName }}</div>
                            <div class="text-sm text-gray-500 mt-0.5">{{ package }} Package</div>
                            <div class="text-xs text-gray-400 mt-1">{{ numberOfDays }} days rental</div>
                        </div>
                    </div>

                    <!-- Dates -->
                    <div class="space-y-3 mb-5 pb-4 border-b border-gray-100">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-emerald-700 uppercase">Pickup</div>
                                <div class="text-sm font-medium text-gray-900">{{ pickupDate }} at {{ pickupTime }}</div>
                                <div class="text-xs text-gray-500">{{ pickupLocation }}</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-rose-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-rose-700 uppercase">Dropoff</div>
                                <div class="text-sm font-medium text-gray-900">{{ dropoffDate }} at {{ dropoffTime }}</div>
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
                                    <div class="text-sm font-semibold text-emerald-800">Pay Now ({{ effectivePaymentPercentage }}%)</div>
                                    <div class="text-xs text-emerald-600">Secure deposit</div>
                                </div>
                                <span class="text-2xl font-bold text-emerald-700">{{ formatPrice(totals.payableAmount) }}</span>
                            </div>
                        </div>

                        <div class="flex justify-between text-sm text-gray-500 px-1">
                            <span>Pay On Arrival</span>
                            <span class="font-semibold text-gray-700">{{ formatPrice(totals.pendingAmount) }}</span>
                        </div>
                    </div>

                    <!-- Stripe Button -->
                    <div class="space-y-3">
                        <div v-if="form.name && form.email && form.phone && form.driver_age">
                            <StripeCheckoutButton v-if="!Object.keys(errors).length" :booking-data="bookingData"
                                :label="`Pay ${formatPrice(totals.payableAmount)}`" />
                            <button v-else @click="validate()"
                                class="w-full bg-red-50 text-red-600 py-4 rounded-xl font-bold cursor-pointer border border-red-200 hover:bg-red-100 transition-colors">
                                Please Fix Errors Above
                            </button>
                        </div>
                        <button v-else @click="validate()"
                            class="w-full bg-gray-100 text-gray-500 py-4 rounded-xl font-bold cursor-pointer border border-gray-200 hover:bg-gray-200 transition-colors">
                            Complete All Required Fields
                        </button>

                        <!-- Trust indicators -->
                        <div class="flex items-center justify-center gap-4 pt-2">
                            <div class="flex items-center gap-1 text-gray-400">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                                <span class="text-[10px]">SSL Secure</span>
                            </div>
                            <div class="flex items-center gap-1 text-gray-400">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                                <span class="text-[10px]">Stripe Protected</span>
                            </div>
                        </div>

                        <p class="text-[10px] text-center text-gray-400 leading-relaxed">
                            By proceeding, you agree to our Terms & Conditions and Privacy Policy.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* === Form Field Styling === */
.form-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}

.form-label-icon {
    width: 16px;
    height: 16px;
    color: #1e3a5f;
    opacity: 0.6;
    flex-shrink: 0;
}

.form-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.form-textarea-wrap {
    align-items: flex-start;
}

.form-input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    color: #9ca3af;
    pointer-events: none;
    transition: color 0.2s ease;
    z-index: 1;
}

.form-textarea-wrap .form-input-icon {
    top: 14px;
    transform: none;
}

.form-input {
    width: 100%;
    padding: 10px 14px 10px 42px;
    border: 1.5px solid #e5e7eb;
    border-radius: 12px;
    font-size: 14px;
    font-family: 'IBM Plex Sans', sans-serif;
    color: #1f2937;
    background: #fafbfc;
    transition: all 0.2s ease;
    outline: none;
}

.form-input::placeholder {
    color: #c0c5cc;
    font-weight: 400;
}

.form-input:focus {
    border-color: #1e3a5f;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(30, 58, 95, 0.08);
}

.form-input-wrap:focus-within .form-input-icon {
    color: #1e3a5f;
}

.form-input-wrap.has-value .form-input-icon {
    color: #6b7280;
}

/* Error state */
.form-input-wrap.has-error .form-input {
    border-color: #ef4444;
    background: #fef2f2;
}

.form-input-wrap.has-error .form-input:focus {
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.form-input-wrap.has-error .form-input-icon {
    color: #ef4444;
}

/* Select styling */
.form-select {
    appearance: none;
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%239ca3af' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19.5 8.25l-7.5 7.5-7.5-7.5'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 16px;
    padding-right: 36px;
}

/* Error message */
.form-error {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 6px;
    padding: 4px 8px;
    font-size: 12px;
    font-weight: 500;
    color: #dc2626;
    background: #fef2f2;
    border-radius: 6px;
    border: 1px solid #fecaca;
}

/* Error animation */
.field-error-enter-active {
    animation: slideErrorIn 0.3s ease-out;
}

.field-error-leave-active {
    animation: slideErrorOut 0.2s ease-in;
}

@keyframes slideErrorIn {
    from {
        opacity: 0;
        transform: translateY(-6px);
        max-height: 0;
    }
    to {
        opacity: 1;
        transform: translateY(0);
        max-height: 40px;
    }
}

@keyframes slideErrorOut {
    from {
        opacity: 1;
        transform: translateY(0);
        max-height: 40px;
    }
    to {
        opacity: 0;
        transform: translateY(-4px);
        max-height: 0;
    }
}

/* Number input spinner removal */
.form-input[type="number"]::-webkit-inner-spin-button,
.form-input[type="number"]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
.form-input[type="number"] {
    -moz-appearance: textfield;
}

/* === Payment Method Cards === */
.pay-method-card {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 16px 18px;
    border: 2px solid transparent;
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}

.pay-method-idle {
    border-color: #e5e7eb;
    background: #fafbfc;
}

.pay-method-idle:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
    box-shadow: 0 2px 8px rgba(30, 58, 95, 0.06);
}

.pay-method-active {
    border-color: #1e3a5f;
    background: linear-gradient(135deg, rgba(30, 58, 95, 0.03) 0%, rgba(30, 58, 95, 0.01) 100%);
    box-shadow: 0 4px 16px rgba(30, 58, 95, 0.1), 0 0 0 1px rgba(30, 58, 95, 0.05);
}

/* Radio dot */
.pay-radio {
    flex-shrink: 0;
    padding-top: 2px;
}

.pay-radio-dot {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid #d1d5db;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    background: white;
}

.pay-radio-checked {
    background: #1e3a5f;
    border-color: #1e3a5f;
    box-shadow: 0 0 0 3px rgba(30, 58, 95, 0.15);
}

/* Icon area */
.pay-icon-area {
    flex-shrink: 0;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    transition: all 0.25s ease;
}

.pay-icon-active {
    background: #1e3a5f;
    color: white;
}

/* Brand chips */
.pay-brand-chip {
    height: 22px;
    padding: 0 6px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Check pop animation */
.check-pop-enter-active {
    animation: checkPop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.check-pop-leave-active {
    animation: checkPop 0.15s ease reverse;
}

@keyframes checkPop {
    0% {
        opacity: 0;
        transform: scale(0.3);
    }
    100% {
        opacity: 1;
        transform: scale(1);
    }
}
</style>
