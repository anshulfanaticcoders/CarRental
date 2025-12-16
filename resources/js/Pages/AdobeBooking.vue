<script setup>
import { Link, usePage, Head, router } from "@inertiajs/vue3";
import { computed, onMounted, ref, watch, nextTick, onBeforeUnmount } from "vue";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import Footer from "@/Components/Footer.vue";
import { useToast } from 'vue-toastification';
import { useCurrency } from '@/composables/useCurrency';

const props = defineProps({
    vehicle: Object,
    searchParams: Object,
    locale: String,
    auth: Object,
});

const page = usePage();
const toast = useToast();

// UI State
const currentStep = ref(1);
const isFormValid = ref(false);
const showMobileBookingSummary = ref(false);
const showSummaryModal = ref(false);
const isSubmitting = ref(false);
const isMobile = ref(false);
const selectedProtections = ref({});
const selectedExtras = ref({});

// Form field errors
const formErrors = ref({});

const { selectedCurrency, supportedCurrencies, changeCurrency } = useCurrency();

// Currency conversion for Adobe
const currencySymbols = ref({});
const exchangeRates = ref(null);
import axios from 'axios';

const paymentPercentage = ref(0.00);

const symbolToCodeMap = {
    '$': 'USD',
    '€': 'EUR',
    '£': 'GBP',
    '¥': 'JPY',
    'A$': 'AUD',
    'C$': 'CAD',
    'Fr': 'CHF',
    'HK$': 'HKD',
    'S$': 'SGD',
    'kr': 'SEK',
    '₩': 'KRW',
    'kr': 'NOK',
    'NZ$': 'NZD',
    '₹': 'INR',
    'Mex$': 'MXN',
    'R': 'ZAR',
    'AED': 'AED'
};

const fetchExchangeRates = async () => {
    try {
        const response = await fetch(`${import.meta.env.VITE_EXCHANGERATE_API_BASE_URL}/v6/${import.meta.env.VITE_EXCHANGERATE_API_KEY}/latest/USD`);
        const data = await response.json();
        if (data.result === 'success') {
            exchangeRates.value = data.conversion_rates;
        } else {
            console.error('Failed to fetch exchange rates:', data['error-type']);
        }
    } catch (error) {
        console.error('Error fetching exchange rates:', error);
    }
};

const convertCurrency = (price, fromCurrency = 'USD') => {
    const numericPrice = parseFloat(price);
    if (isNaN(numericPrice)) {
        return 0;
    }

    let fromCurrencyCode = fromCurrency;
    if (symbolToCodeMap[fromCurrency]) {
        fromCurrencyCode = symbolToCodeMap[fromCurrency];
    }

    if (!exchangeRates.value || !fromCurrencyCode || !selectedCurrency.value) {
        return numericPrice;
    }
    const rateFrom = exchangeRates.value[fromCurrencyCode];
    const rateTo = exchangeRates.value[selectedCurrency.value];
    if (rateFrom && rateTo) {
        return (numericPrice / rateFrom) * rateTo;
    }
    return numericPrice;
};

onMounted(async () => {
    // Initialize mobile detection
    checkMobile();
    window.addEventListener('resize', checkMobile);

    fetchExchangeRates();

    try {
        const response = await fetch('/currency.json');
        const data = await response.json();
        currencySymbols.value = data.reduce((acc, curr) => {
            acc[curr.code] = curr.symbol;
            return acc;
        }, {});
    } catch (error) {
        console.error("Error loading currency symbols:", error);
    }

    // Fetch payment percentage
    try {
        const response = await axios.get('/api/payment-percentage');
        if (response.data && response.data.payment_percentage !== undefined) {
            paymentPercentage.value = Number(response.data.payment_percentage);
        }
    } catch (error) {
        console.error('Error fetching payment percentage:', error);
    }
});

// Cleanup resize listener
onBeforeUnmount(() => {
    window.removeEventListener('resize', checkMobile);
});

const getCurrencySymbol = (code) => {
    return currencySymbols.value[code] || '$';
};

const formatPrice = (price, fromCurrency = 'USD') => {
    const convertedPrice = convertCurrency(price, fromCurrency);
    const currencySymbol = getCurrencySymbol(selectedCurrency.value);
    return `${currencySymbol}${convertedPrice.toFixed(2)}`;
};

const truncateText = (text, maxLength) => {
    if (!text || text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
};

// Form data
const formData = ref({
    // Customer Information
    customer: {
        name: '',
        last_name: '',
        email: '',
        phone: '',
        address: '',
        city: '',
        postcode: '',
        country: '',
    },
    // Additional Information
    customer_comment: '',
    reference: '',
    flight_number: '',
    language: 'en',
    // Adobe pricing components required by backend
    tdr_total: 0,
    pli_total: 0,
    ldw_total: 0,
    spp_total: 0,
    dro_total: 0,
    base_rate: 0,
    vehicle_total: 0,
    grand_total: 0,
});

// Computed pricing - Use ONLY API data, no hardcoded values
const pricingBreakdown = computed(() => {
    const items = [];

    // Always include TDR (base rate) from API
    if (props.vehicle.tdr > 0) {
        items.push({
            label: 'Time & Distance Rate',
            amount: props.vehicle.tdr,
            description: 'Base rental charge',
            required: true
        });
    }

    // Add selected protections from API data ONLY
    if (selectedProtections.value) {
        Object.values(selectedProtections.value).forEach(protection => {
            if (protection && protection.selected) {
                items.push({
                    label: protection.displayName || protection.name || protection.code,
                    amount: protection.total || 0,
                    description: protection.displayDescription || protection.description || '',
                    required: protection.required || false
                });
            }
        });
    }

    // Add selected extras from API data ONLY
    if (selectedExtras.value) {
        Object.values(selectedExtras.value).forEach(extra => {
            if (extra && extra.selected && extra.quantity > 0) {
                items.push({
                    label: extra.displayName || extra.name || extra.code,
                    amount: (extra.total || 0) * extra.quantity,
                    description: extra.displayDescription || extra.description || '',
                    required: false
                });
            }
        });
    }

    return items;
});

const calculateTotal = computed(() => {
    const total = pricingBreakdown.value.reduce((total, item) => total + item.amount, 0);

    // Update form data fields required by backend
    formData.value.grand_total = total;
    formData.value.vehicle_total = total;
    formData.value.base_rate = props.vehicle.tdr || 0;

    // Calculate individual component totals
    formData.value.tdr_total = props.vehicle.tdr || 0;
    formData.value.pli_total = 0;
    formData.value.ldw_total = 0;
    formData.value.spp_total = 0;
    formData.value.dro_total = 0;

    // Sum up protection totals
    if (selectedProtections.value) {
        Object.values(selectedProtections.value).forEach(protection => {
            if (protection && protection.selected) {
                const code = protection.code.toLowerCase();
                if (code === 'pli') formData.value.pli_total = protection.total || 0;
                if (code === 'ldw') formData.value.ldw_total = protection.total || 0;
                if (code === 'spp') formData.value.spp_total = protection.total || 0;
                if (code === 'dro') formData.value.dro_total = protection.total || 0;
            }
        });
    }

    return total;
});

const calculateAmountPaid = computed(() => {
    const total = calculateTotal.value;
    const effectivePercentage = paymentPercentage.value === 0 ? 100 : paymentPercentage.value;
    return Number((total * (effectivePercentage / 100)).toFixed(2));
});

const calculatePendingAmount = computed(() => {
    const total = calculateTotal.value;
    const effectivePercentage = paymentPercentage.value === 0 ? 100 : paymentPercentage.value;
    return Number((total * (effectivePercentage / 100)) === total ? 0 : (total * (1 - (effectivePercentage / 100))).toFixed(2));
});

// Watch for protection/extras changes
watch([selectedProtections, selectedExtras], () => {
    formData.value.grand_total = calculateTotal.value;
    formData.value.vehicle_total = calculateTotal.value;
}, { deep: true });

// Initialize protections and extras
onMounted(() => {
    // Initialize protections - Adobe business logic: required items are pre-selected by backend
    if (props.vehicle.protections) {
        props.vehicle.protections.forEach(protection => {
            if (protection.type === 'Proteccion') {
                selectedProtections.value[protection.code] = {
                    ...protection,
                    quantity: 1
                };

                // Use the selected property from backend (based on Adobe business logic)
                // Required items like PLI will have selected=true, optional items like LDW will have selected=false
                // Pricing is now calculated dynamically, no need to set individual totals
            }
        });
    }

    // Initialize extras
    if (props.vehicle.extras) {
        props.vehicle.extras.forEach(extra => {
            if (extra.type !== 'Proteccion') {
                selectedExtras.value[extra.code] = {
                    ...extra,
                    quantity: extra.selected ? 1 : 0  // Start with quantity 1 if selected, 0 if not
                };
            }
        });
    }

    // Pre-fill user data if authenticated
    if (props.auth.user) {
        formData.value.customer.name = props.auth.user.first_name || '';
        formData.value.customer.last_name = props.auth.user.last_name || '';
        formData.value.customer.email = props.auth.user.email || '';
        formData.value.customer.phone = props.auth.user.phone || '';
        formData.value.customer.address = props.auth.user.address || '';
        formData.value.customer.city = props.auth.user.city || '';
        formData.value.customer.postcode = props.auth.user.postcode || '';
        formData.value.customer.country = props.auth.user.country || '';
    }

    // Calculate initial total
    formData.value.grand_total = calculateTotal.value;
    formData.value.vehicle_total = calculateTotal.value;

    // Fetch exchange rates for currency conversion
    fetchExchangeRates();
});

// Adobe rental duration calculation
const rentalDuration = computed(() => {
    if (!props.searchParams?.date_from || !props.searchParams?.date_to) return 1;
    const startDate = new Date(props.searchParams.date_from);
    const endDate = new Date(props.searchParams.date_to);
    const diffTime = Math.abs(endDate - startDate);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) || 1;
});

const validateStep = (step) => {
    // Clear previous errors
    formErrors.value = {};

    switch (step) {
        case 1: // Dates and basic info
            return true; // Already validated from search params

        case 2: // Customer information
            const customer = formData.value.customer;
            let isValid = true;

            // Validate each field and set specific errors
            if (!customer.name.trim()) {
                formErrors.value.name = 'First name is required';
                isValid = false;
            }

            if (!customer.last_name.trim()) {
                formErrors.value.last_name = 'Last name is required';
                isValid = false;
            }

            if (!customer.email.trim()) {
                formErrors.value.email = 'Email is required';
                isValid = false;
            }

            if (!customer.phone.trim()) {
                formErrors.value.phone = 'Phone number is required';
                isValid = false;
            }

            if (!customer.address.trim()) {
                formErrors.value.address = 'Address is required';
                isValid = false;
            }

            if (!customer.city.trim()) {
                formErrors.value.city = 'City is required';
                isValid = false;
            }

            if (!customer.postcode.trim()) {
                formErrors.value.postcode = 'Postcode is required';
                isValid = false;
            }

            if (!customer.country.trim()) {
                formErrors.value.country = 'Country is required';
                isValid = false;
            }

            return isValid;

        case 3: // Protections and extras
            return true; // Protections are handled by logic

        case 4: // Payment step - ready to submit
            return true;

        default:
            return false;
    }
};

const nextStep = () => {
    if (validateStep(currentStep.value)) {
        if (currentStep.value < 4) {
            currentStep.value++;
        }
    }
    // No toast error - field errors are shown inline
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const goToStep = (step) => {
    // Allow going back to previous steps
    if (step < currentStep.value) {
        currentStep.value = step;
        return;
    }

    // Moving forward - validate all steps up to the target
    for (let i = currentStep.value + 1; i <= step; i++) {
        if (!validateStep(i)) {
            currentStep.value = i; // Go to the failed step
            return;
        }
    }

    // If we reach here, all validations passed
    currentStep.value = step;
};

// Mobile detection composable
const checkMobile = () => {
    isMobile.value = window.innerWidth < 1024; // lg breakpoint
};

// Format date function
const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric'
    });
};

const toggleProtection = (protectionCode) => {
    const protection = selectedProtections.value[protectionCode];
    if (protection) {
        // Adobe business logic: required items cannot be deselected
        if (protection.required) {
            // Required protections (like PLI) cannot be toggled off
            return;
        }

        // Only allow toggling optional protections (like LDW, SPP)
        protection.selected = !protection.selected;

        // Pricing is now calculated dynamically from selected items, no need to update individual totals
    }
};

const updateExtraQuantity = (extraCode, quantity) => {
    const extra = selectedExtras.value[extraCode];
    if (extra) {
        extra.quantity = Math.max(0, parseInt(quantity) || 0);
        extra.selected = extra.quantity > 0;
    }
};

const submitBooking = async () => {
    console.log('Debug: submitBooking called');
    console.log('Debug: currentStep.value:', currentStep.value);
    console.log('Debug: isSubmitting.value:', isSubmitting.value);

    if (!validateStep(currentStep.value)) {
        console.log('Debug: validation failed');
        return;
    }

    console.log('Debug: validation passed');
    isSubmitting.value = true;

    try {
        // Prepare booking data
        const bookingData = {
            ...props.searchParams,
            ...formData.value,
            vehicle_id: props.vehicle.id,
            vehicle_category: props.vehicle.category,
            vehicle_model: props.vehicle.model,
            pickup_location_id: props.searchParams.pickup_location_id,
            dropoff_location_id: props.searchParams.dropoff_location_id,
            selected_protections: selectedProtections.value ? Object.values(selectedProtections.value).filter(p => p && p.selected) : [],
            selected_protections: selectedProtections.value ? Object.values(selectedProtections.value).filter(p => p && p.selected) : [],
            selected_extras: selectedExtras.value ? Object.values(selectedExtras.value).filter(e => e && e.selected && e.quantity > 0) : [],
            amount_paid: calculateAmountPaid.value,
            pending_amount: calculatePendingAmount.value,
        };

        // Create booking and get Stripe checkout
        const response = await fetch(route('adobe.booking.charge'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            },
            body: JSON.stringify(bookingData),
        });

        const result = await response.json();

        if (result.success) {
            // Redirect to Stripe checkout
            window.location.href = result.checkout_url;
        } else {
            toast.error(result.error || 'Failed to create booking');
        }
    } catch (error) {
        console.error('Booking error:', error);
        toast.error('An error occurred while processing your booking');
    } finally {
        isSubmitting.value = false;
    }
};

// Step components
const getStepTitle = (step) => {
    const titles = {
        1: 'Rental Details',
        2: 'Customer Information',
        3: 'Protection & Extras',
        4: 'Payment',
    };
    return titles[step] || '';
};

const isStepComplete = (step) => {
    return validateStep(step);
};
</script>

<template>
    <Head>
        <title>Complete Your Booking - Adobe Car Rental</title>
    </Head>
    <AuthenticatedHeaderLayout />

    <!-- Hero Section - Match GreenMotion Exactly -->
    <section class="bg-primary py-12 sm:py-12 md:py-16 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-primary to-primary-dark opacity-90"></div>
        <div class="container relative z-10 px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto text-center text-white">
                <div class="inline-flex items-center px-3 sm:px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full mb-4 sm:mb-6">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-xs sm:text-sm font-medium">Secure Booking Process</span>
                </div>
                <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold mb-3 sm:mb-4">Complete Your Reservation</h1>
                <p class="text-base sm:text-lg md:text-xl opacity-90">{{ vehicle?.model }} - Adobe Car Rental</p>
            </div>
        </div>
    </section>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 sm:py-10 py-12">
        <!-- Progress Steps - Match GreenMotion Exactly -->
        <div class="mb-6 sm:mb-8">
            <!-- Mobile: Vertical steps -->
            <div class="flex flex-col sm:hidden space-y-3 mb-6">
                <div v-for="step in 4" :key="step"
                     class="flex items-center cursor-pointer transition-all duration-300 p-3 rounded-lg"
                     :class="step <= currentStep ? 'bg-primary/5' : 'bg-gray-50'"
                     @click="goToStep(step)">
                    <div class="flex items-center flex-1">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center font-semibold transition-all duration-300"
                             :class="step <= currentStep
                               ? 'bg-primary text-white shadow-lg'
                               : 'bg-gray-200 text-gray-600'">
                            <svg v-if="step < currentStep" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span v-else class="text-lg">{{ step }}</span>
                        </div>
                        <div class="ml-4 flex-1">
                            <div class="font-medium text-gray-900 text-sm"
                                 :class="step <= currentStep ? 'text-primary font-semibold' : 'text-gray-500'">
                                {{ step === 1 ? 'Rental Details' : step === 2 ? 'Your Details' : step === 3 ? 'Protection & Extras' : 'Payment' }}
                            </div>
                            <div class="text-xs sm:text-sm text-gray-500 mt-1">
                                {{ step === 1 ? 'Review vehicle info' : step === 2 ? 'Enter your information' : step === 3 ? 'Select coverage' : 'Complete payment' }}
                            </div>
                        </div>
                    </div>
                    <div v-if="step < currentStep" class="ml-3">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Desktop/Tablet: Horizontal steps -->
            <div class="hidden sm:flex items-center justify-center space-x-4 md:space-x-8 mb-6 sm:mb-8">
                <div v-for="step in 4" :key="step"
                     class="flex items-center cursor-pointer transition-all duration-300"
                     @click="goToStep(step)">
                    <div class="flex items-center">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center font-semibold transition-all duration-300"
                             :class="step <= currentStep
                               ? 'bg-primary text-white shadow-lg'
                               : 'bg-gray-200 text-gray-600'">
                            <svg v-if="step < currentStep" class="w-5 h-5 sm:w-6 sm:h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span v-else class="text-sm sm:text-base">{{ step }}</span>
                        </div>
                        <span class="ml-2 sm:ml-3 font-medium text-gray-700 text-sm sm:text-base"
                              :class="step <= currentStep ? 'text-primary' : 'text-gray-500'">
                            {{ step === 1 ? 'Rental Details' : step === 2 ? 'Your Details' : step === 3 ? 'Protection & Extras' : 'Payment' }}
                        </span>
                    </div>
                    <div v-if="step < 4" class="w-12 sm:w-16 md:w-20 h-0.5 ml-2 sm:ml-4 md:ml-8"
                         :class="step < currentStep ? 'bg-primary' : 'bg-gray-200'"></div>
                </div>
            </div>
        </div>

        <!-- Main Grid: Content + Booking Summary -->
        <div class="grid grid-cols-1 xl:grid-cols-5 gap-6 lg:gap-8">
            <!-- Main Content -->
            <div class="xl:col-span-3 lg:col-span-4" :class="{ 'mobile-content-padding': isMobile }">
                    <!-- Step 1: Rental Details - Match GreenMotion Structure -->
                    <div v-show="currentStep === 1" class="space-y-6 sm:space-y-8">
                        <!-- Vehicle Summary Card - Exact Match -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="p-4 sm:p-6 bg-gradient-to-r from-gray-50 to-white border-b">
                                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2">Your Selected Vehicle</h2>
                                <p class="text-sm sm:text-base text-gray-600">Review your vehicle and rental details</p>
                            </div>
                            <div class="p-4 sm:p-6">
                                <div class="flex flex-col md:flex-row gap-4 sm:gap-6">
                                    <div class="md:w-1/3">
                                        <img :src="vehicle.image || '/images/adobe-placeholder.jpg'"
                                             :alt="vehicle.model"
                                             class="w-full h-40 sm:h-48 md:h-32 object-cover rounded-xl shadow-sm" />
                                    </div>
                                    <div class="md:w-2/3 space-y-3">
                                        <div>
                                            <h3 class="text-lg sm:text-xl font-bold text-gray-900">{{ vehicle?.model }}</h3>
                                            <p class="text-sm sm:text-base text-gray-600">{{ vehicle?.category?.toUpperCase() }} • Adobe Car Rental</p>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                                            <div class="bg-gray-50 rounded-lg p-3">
                                                <p class="text-xs sm:text-sm font-medium text-gray-500">Rental Duration</p>
                                                <p class="text-base sm:text-lg font-semibold text-gray-900">{{ rentalDuration }} days</p>
                                            </div>
                                            <div class="bg-gray-50 rounded-lg p-3">
                                                <p class="text-xs sm:text-sm font-medium text-gray-500">Total Rate</p>
                                                <p class="text-base sm:text-lg font-semibold text-primary">{{ formatPrice(vehicle.tdr || 0) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Adobe Pricing Details - Match GreenMotion Package Style -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                            <div class="p-4 sm:p-6 border-b bg-gradient-to-r from-gray-50 to-white">
                                <h3 class="text-lg sm:text-lg sm:text-xl font-bold text-gray-900 mb-2">Adobe Pricing Package</h3>
                                <p class="text-sm sm:text-base text-gray-600">Transparent pricing with no hidden fees</p>
                            </div>
                            <div class="p-4 sm:p-6">
                                <div class="space-y-3 sm:space-y-4">
                                    <div class="border-2 rounded-xl p-4 sm:p-6 cursor-pointer transition-all duration-300 hover:shadow-lg border-primary bg-primary/5 shadow-md">
                                        <div class="flex items-start justify-between mb-4">
                                            <h4 class="text-base sm:text-lg font-bold text-gray-900">Adobe Standard Package</h4>
                                            <div class="w-5 h-5 sm:w-4 sm:h-4 rounded-full border-2 flex items-center justify-center border-primary bg-primary">
                                                <div class="w-2 h-2 sm:w-1.5 sm:h-1.5 bg-white rounded-full"></div>
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <div class="flex justify-between">
                                                <span class="text-sm sm:text-base text-gray-600">Base Rate</span>
                                                <span class="font-semibold text-sm sm:text-base">{{ formatPrice(vehicle.tdr || 0) }}</span>
                                            </div>
                                            <!-- Show partial payment breakdown if applicable -->
                                            <div v-if="paymentPercentage > 0" class="flex justify-between text-green-600 font-medium">
                                                <span class="text-sm sm:text-base">Pay Now ({{ paymentPercentage }}%)</span>
                                                <span class="text-sm sm:text-base">{{ formatPrice(calculateAmountPaid) }}</span>
                                            </div>
                                            <!-- Show only selected protections from API data -->
                                            <template v-if="selectedProtections && Object.keys(selectedProtections).length > 0">
                                                <div v-for="protection in Object.values(selectedProtections)" :key="protection.code" v-show="protection && protection.selected" class="flex justify-between">
                                                    <span class="text-gray-600">{{ protection.displayName || protection.name || protection.code }}</span>
                                                    <span class="font-semibold">{{ formatPrice(protection.total || 0) }}</span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Customer Information - Match GreenMotion Exactly -->
                    <div v-show="currentStep === 2" class="space-y-6 sm:space-y-8">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                            <div class="p-4 sm:p-6 border-b bg-gradient-to-r from-gray-50 to-white">
                                <h3 class="text-lg sm:text-lg sm:text-xl font-bold text-gray-900 mb-2">Your Details</h3>
                                <p class="text-sm sm:text-base text-gray-600">Please provide your information to complete the booking</p>
                            </div>
                            <div class="p-4 sm:p-6">
                                <form class="space-y-4 sm:space-y-6">
                                    <!-- Personal Information -->
                                    <div>
                                        <h4 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">Personal Information</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                                            <div>
                                                <label for="firstname" class="block text-sm font-medium text-gray-700 mb-2">
                                                    First Name <span class="text-red-500">*</span>
                                                </label>
                                                <input type="text" id="firstname" v-model="formData.customer.name"
                                                       class="w-full px-4 py-3 sm:py-4 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors text-base sm:text-lg"
                                                       :class="formErrors.name ? 'border-red-500' : 'border-gray-300'"
                                                       placeholder="Enter your first name" />
                                                <p v-if="formErrors.name" class="mt-1 text-sm text-red-600">{{ formErrors.name }}</p>
                                            </div>
                                            <div>
                                                <label for="surname" class="block text-sm font-medium text-gray-700 mb-2">
                                                    Last Name <span class="text-red-500">*</span>
                                                </label>
                                                <input type="text" id="surname" v-model="formData.customer.last_name"
                                                       class="w-full px-4 py-3 sm:py-4 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors text-base sm:text-lg"
                                                       :class="formErrors.last_name ? 'border-red-500' : 'border-gray-300'"
                                                       placeholder="Enter your last name" />
                                                <p v-if="formErrors.last_name" class="mt-1 text-sm text-red-600">{{ formErrors.last_name }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Contact Information -->
                                    <div>
                                        <h4 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">Contact Information</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                                            <div>
                                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                                    Email Address <span class="text-red-500">*</span>
                                                </label>
                                                <input type="email" id="email" v-model="formData.customer.email" readonly
                                                       class="w-full px-3 sm:px-4 py-3 sm:py-4 border border-gray-300 rounded-lg bg-gray-50 text-gray-600 cursor-not-allowed text-base sm:text-lg"
                                                       placeholder="your.email@example.com" />
                                            </div>
                                            <div>
                                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                                    Phone Number <span class="text-red-500">*</span>
                                                </label>
                                                <input type="tel" id="phone" v-model="formData.customer.phone"
                                                       class="w-full px-4 py-3 sm:py-4 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors text-base sm:text-lg"
                                                       :class="formErrors.phone ? 'border-red-500' : 'border-gray-300'"
                                                       placeholder="+1 (555) 123-4567" />
                                                <p v-if="formErrors.phone" class="mt-1 text-sm text-red-600">{{ formErrors.phone }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Address Information -->
                                    <div>
                                        <h4 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">Address Information</h4>
                                        <div class="space-y-6">
                                            <div>
                                                <label for="address1" class="block text-sm font-medium text-gray-700 mb-2">
                                                    Address Line 1 <span class="text-red-500">*</span>
                                                </label>
                                                <input type="text" id="address1" v-model="formData.customer.address"
                                                       class="w-full px-4 py-3 sm:py-4 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors text-base sm:text-lg"
                                                       :class="formErrors.address ? 'border-red-500' : 'border-gray-300'"
                                                       placeholder="Street address, P.O. box, company name" />
                                            <p v-if="formErrors.address" class="mt-1 text-sm text-red-600">{{ formErrors.address }}</p>
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                                                <div>
                                                    <label for="town" class="block text-sm font-medium text-gray-700 mb-2">
                                                        Town/City <span class="text-red-500">*</span>
                                                    </label>
                                                    <input type="text" id="town" v-model="formData.customer.city"
                                                           class="w-full px-4 py-3 sm:py-4 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors text-base sm:text-lg"
                                                           :class="formErrors.city ? 'border-red-500' : 'border-gray-300'"
                                                           placeholder="City" />
                                                    <p v-if="formErrors.city" class="mt-1 text-sm text-red-600">{{ formErrors.city }}</p>
                                                </div>
                                                <div>
                                                    <label for="postcode" class="block text-sm font-medium text-gray-700 mb-2">
                                                        Postcode <span class="text-red-500">*</span>
                                                    </label>
                                                    <input type="text" id="postcode" v-model="formData.customer.postcode"
                                                           class="w-full px-4 py-3 sm:py-4 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors text-base sm:text-lg"
                                                           :class="formErrors.postcode ? 'border-red-500' : 'border-gray-300'"
                                                           placeholder="12345" />
                                                    <p v-if="formErrors.postcode" class="mt-1 text-sm text-red-600">{{ formErrors.postcode }}</p>
                                                </div>
                                                <div>
                                                    <label for="country" class="block text-sm font-medium text-gray-700 mb-2">
                                                        Country <span class="text-red-500">*</span>
                                                    </label>
                                                    <input type="text" id="country" v-model="formData.customer.country"
                                                           class="w-full px-4 py-3 sm:py-4 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors text-base sm:text-lg"
                                                           :class="formErrors.country ? 'border-red-500' : 'border-gray-300'"
                                                           placeholder="Country" />
                                                    <p v-if="formErrors.country" class="mt-1 text-sm text-red-600">{{ formErrors.country }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Optional Information -->
                                    <div>
                                        <h4 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">Optional Information</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                                            <div>
                                                <label for="flight_number" class="block text-sm font-medium text-gray-700 mb-2">
                                                    Flight Number
                                                </label>
                                                <input type="text" id="flight_number" v-model="formData.flight_number"
                                                       class="w-full px-3 sm:px-4 py-3 sm:py-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors text-base sm:text-lg"
                                                       placeholder="AA123" />
                                            </div>
                                            <div>
                                                <label for="reference" class="block text-sm font-medium text-gray-700 mb-2">
                                                    Reference
                                                </label>
                                                <input type="text" id="reference" v-model="formData.reference"
                                                       class="w-full px-3 sm:px-4 py-3 sm:py-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors text-base sm:text-lg"
                                                       placeholder="Business trip" />
                                            </div>
                                        </div>
                                        <div class="mt-6">
                                            <label for="customer_comment" class="block text-sm font-medium text-gray-700 mb-2">
                                                Special Requests
                                            </label>
                                            <textarea id="customer_comment" v-model="formData.customer_comment" rows="3"
                                                      class="w-full px-4 py-3 sm:py-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors text-base sm:text-lg"
                                                      placeholder="Any special requests or comments..."></textarea>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                         <!-- Step 3: Protection & Extras - Match GreenMotion Exactly -->
                    <div v-show="currentStep === 3" class="space-y-6 sm:space-y-8">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                            <div class="p-4 sm:p-6 border-b bg-gradient-to-r from-gray-50 to-white">
                                <h3 class="text-lg sm:text-lg sm:text-xl font-bold text-gray-900 mb-2">Protection & Extras</h3>
                                <p class="text-sm sm:text-base text-gray-600">Enhance your rental with additional coverage and extras</p>
                            </div>
                            <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
                                <!-- Protections Section -->
                                <div v-if="selectedProtections && Object.keys(selectedProtections).length > 0">
                                    <h4 class="text-base sm:text-base sm:text-lg font-semibold text-gray-900 mb-4">Protection Options</h4>
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-4">
                                        <div
                                            v-for="protection in (selectedProtections ? Object.values(selectedProtections) : [])"
                                            :key="protection.code"
                                            class="relative p-3 sm:p-4 border-2 rounded-xl transition-all duration-300"
                                            :class="{
                                                'border-primary bg-primary/5': protection.selected,
                                                'border-gray-200 hover:border-gray-300': !protection.selected && !protection.required,
                                                'border-green-200 bg-green-50': protection.selected && protection.required,
                                                'cursor-pointer': !protection.required,
                                                'cursor-not-allowed opacity-90': protection.required
                                            }"
                                            @click="!protection.required && toggleProtection(protection.code)">
                                            <div v-if="protection.required" class="absolute top-0 right-0 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-bl-lg rounded-tr-lg z-10">
                                                Required
                                            </div>
                                            <div v-else-if="protection.selected" class="absolute top-0 right-0 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-bl-lg rounded-tr-lg z-10">
                                                Selected
                                            </div>
                                            <div class="flex items-start justify-between w-full mb-3">
                                                <div class="flex items-start space-x-3">
                                                    <div class="mt-1">
                                                        <div class="w-5 h-5 sm:w-4 sm:h-4 rounded-full border-2 flex items-center justify-center"
                                                             :class="protection.selected
                                                                ? 'border-primary bg-primary'
                                                                : 'border-gray-300'">
                                                            <div v-if="protection.selected" class="w-2.5 h-2.5 sm:w-1.5 sm:h-1.5 bg-white rounded-full"></div>
                                                        </div>
                                                    </div>
                                                    <div class="flex-1">
                                                        <h5 class="text-sm sm:text-base font-semibold text-gray-900">{{ protection.displayName || protection.name || protection.code }}</h5>
                                                        <p class="text-xs sm:text-sm text-gray-600 mt-1">
                                                            <span v-if="!protection.expanded">{{ truncateText(protection.displayDescription || protection.description, 60) }}</span>
                                                            <span v-else>{{ protection.displayDescription || protection.description }}</span>
                                                            <button
                                                                v-if="(protection.displayDescription || protection.description || '').length > 60"
                                                                @click.stop="protection.expanded = !protection.expanded"
                                                                class="text-xs text-primary hover:underline ml-1">
                                                                {{ protection.expanded ? 'Show less' : 'Read more' }}
                                                            </button>
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <div class="text-base font-bold text-primary">{{ formatPrice(protection.total || 0) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Extras Section -->
                                <div v-if="selectedExtras && Object.keys(selectedExtras).length > 0">
                                    <h4 class="text-base sm:text-base sm:text-lg font-semibold text-gray-900 mb-4">Additional Extras</h4>
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-4">
                                        <div
                                            v-for="extra in (selectedExtras ? Object.values(selectedExtras) : [])"
                                            :key="extra.code"
                                            class="relative p-3 sm:p-4 border-2 rounded-xl cursor-pointer transition-all duration-300"
                                            :class="extra.selected
                                                ? 'border-primary bg-primary/5'
                                                : 'border-gray-200 hover:border-gray-300'"
                                            @click="extra.selected = !extra.selected; updateExtraQuantity(extra.code, extra.selected ? 1 : 0)">
                                            <div class="flex items-start justify-between w-full">
                                                <div class="flex items-start justify-between w-full mb-3">
                                                <div class="flex items-start space-x-3">
                                                    <div class="mt-1">
                                                        <div class="w-5 h-5 sm:w-4 sm:h-4 rounded-full border-2 flex items-center justify-center"
                                                             :class="extra.selected
                                                                ? 'border-primary bg-primary'
                                                                : 'border-gray-300'">
                                                            <div v-if="extra.selected" class="w-2.5 h-2.5 sm:w-1.5 sm:h-1.5 bg-white rounded-full"></div>
                                                        </div>
                                                    </div>
                                                    <div class="flex-1">
                                                        <h5 class="text-sm sm:text-base font-semibold text-gray-900">{{ extra.displayName || extra.name || extra.code }}</h5>
                                                        <p class="text-xs sm:text-sm text-gray-600 mt-1">
                                                            <span v-if="!extra.expanded">{{ truncateText(extra.displayDescription || extra.description, 50) }}</span>
                                                            <span v-else>{{ extra.displayDescription || extra.description }}</span>
                                                            <button
                                                                v-if="(extra.displayDescription || extra.description || '').length > 50"
                                                                @click.stop="extra.expanded = !extra.expanded"
                                                                class="text-xs text-primary hover:underline ml-1">
                                                                {{ extra.expanded ? 'Show less' : 'Read more' }}
                                                            </button>
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <div class="text-base font-bold text-primary">{{ formatPrice(extra.total || 0) }} each</div>
                                                    <div v-if="extra.selected" class="mt-1">
                                                        <input
                                                            type="number"
                                                            :value="extra.quantity"
                                                            @input="updateExtraQuantity(extra.code, $event.target.value)"
                                                            @click.stop
                                                            min="0"
                                                            max="10"
                                                            class="w-14 sm:w-16 px-2 py-1 sm:py-2 border border-gray-300 rounded text-xs text-center focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                        />
                                                        <p class="text-xs sm:text-sm text-gray-500 mt-1">Qty</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                 <!-- Step 4: Payment - Match GreenMotion Exactly -->
                    <div v-show="currentStep === 4" class="space-y-6 sm:space-y-8">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                            <div class="p-6 border-b bg-gradient-to-r from-gray-50 to-white">
                                <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-2">Complete Your Payment</h3>
                                <p class="text-gray-600">Review your booking and complete the payment process</p>
                            </div>
                            <div class="p-4 sm:p-6">
                                <div class="space-y-4">
                                    <div v-for="item in pricingBreakdown" :key="item.label" class="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
                                        <div>
                                            <div class="font-medium text-gray-900">{{ item.label }}</div>
                                            <div class="text-sm text-gray-600">{{ item.description }}</div>
                                        </div>
                                        <div class="text-lg font-bold text-primary">{{ formatPrice(item.amount) }}</div>
                                    </div>
                                    <div class="border-t pt-4 mt-4">
                                        <div class="flex justify-between text-xl font-bold">
                                            <span>Total</span>
                                            <span class="text-primary">{{ formatPrice(calculateTotal) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Booking Terms -->
                                <div class="mt-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                                    <h4 class="font-semibold text-gray-800 mb-2">Booking Terms & Conditions</h4>
                                    <ul class="text-sm text-gray-700 space-y-1">
                                        <li>• Free cancellation up to 24 hours before pickup</li>
                                        <li>• Driver must be at least 21 years old with valid license</li>
                                        <li>• Credit card required for security deposit</li>
                                        <li>• Full-to-full fuel policy</li>
                                    </ul>
                                </div>

                                <!-- Payment Button -->
                                <button
                                    @click="submitBooking"
                                    :disabled="isSubmitting"
                                    class="w-full mt-6 bg-primary text-white py-4 sm:py-5 px-6 rounded-xl font-semibold hover:bg-primary-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed text-base sm:text-lg min-h-[44px] shadow-lg">
                                    <span v-if="isSubmitting" class="flex items-center justify-center gap-2">
                                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Processing...
                                    </span>
                                    <span v-else>Complete Booking</span>
                                </button>
                            </div>
                        </div>
                    </div>
                <!-- Navigation Buttons - Hidden on mobile, shown on desktop -->
                <div class="hidden lg:flex flex-col lg:flex-row justify-between gap-4 pt-6 sm:pt-8">
                    <button v-if="currentStep > 1"
                            @click="prevStep"
                            class="inline-flex items-center justify-center px-6 py-4 sm:py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors text-base sm:text-lg w-full lg:w-auto min-h-[44px]">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Previous Step
                    </button>
                    <div v-else class="hidden lg:block"></div>

                    <button v-if="currentStep < 4"
                            @click="nextStep"
                            class="inline-flex items-center justify-center px-6 py-4 sm:py-3 bg-primary text-white font-medium rounded-lg hover:bg-primary-dark transition-colors text-base sm:text-lg w-full lg:w-auto min-h-[44px] shadow-lg">
                        <span v-if="currentStep < 3">Next Step</span>
                        <span v-else>Complete Booking</span>
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Right Column: Booking Summary - Match GreenMotion Exactly -->
            </div>
            <div class="xl:col-span-2 lg:col-span-1">
                <!-- Mobile Summary Toggle - Hidden (moved to fixed bottom nav) -->
                <!-- <button @click="showMobileBookingSummary = !showMobileBookingSummary"
                        class="lg:hidden w-full mb-4 px-4 py-4 sm:py-5 bg-primary text-white font-medium rounded-lg flex items-center justify-between text-base sm:text-lg shadow-lg">
                    <span class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        {{ !showMobileBookingSummary ? 'Show Booking Summary' : 'Hide Booking Summary' }}
                    </span>
                    <svg class="w-5 h-5 transform transition-transform duration-200" :class="{ 'rotate-180': showMobileBookingSummary }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button> -->

                <div class="sticky top-4 lg:sticky lg:top-4" :class="{ 'hidden lg:block': !showMobileBookingSummary }">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-4 sm:p-6 bg-gradient-to-r from-primary to-primary-dark text-white">
                            <h3 class="text-lg sm:text-xl font-bold mb-2">Booking Summary</h3>
                            <p class="text-sm sm:text-base opacity-90">Review your reservation details</p>
                        </div>

                        <div class="p-4 sm:p-6">
                            <!-- Vehicle Info -->
                            <div class="border-b border-gray-200 pb-6 mb-6">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                                    <div class="min-w-0 flex-1">
                                        <h4 class="font-bold text-gray-900 text-base sm:text-lg truncate">{{ vehicle?.model }}</h4>
                                        <p class="text-gray-600 text-sm">{{ vehicle?.category?.toUpperCase() }} • Adobe Car Rental</p>
                                    </div>
                                    <img :src="vehicle.image || '/images/adobe-placeholder.jpg'"
                                         :alt="vehicle.model"
                                         class="w-16 h-16 sm:w-20 sm:h-20 object-cover rounded-lg flex-shrink-0" />
                                </div>

                                <!-- Adobe Vehicle Features -->
                                <div class="grid grid-cols-3 gap-3 sm:gap-4 text-center">
                                    <div>
                                        <div class="flex items-center justify-center w-8 h-8 bg-gray-100 rounded-full mx-auto mb-1">
                                            <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                            </svg>
                                        </div>
                                        <p class="text-sm text-gray-600">{{ vehicle?.passengers || 4 }} Seats</p>
                                    </div>
                                    <div>
                                        <div class="flex items-center justify-center w-8 h-8 bg-gray-100 rounded-full mx-auto mb-1">
                                            <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3.293 1.293a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414L7.586 10 5.293 7.707a1 1 0 010-1.414zM11 12a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                        <p class="text-sm text-gray-600">{{ vehicle?.doors || 4 }} Doors</p>
                                    </div>
                                    <div>
                                        <div class="flex items-center justify-center w-8 h-8 bg-gray-100 rounded-full mx-auto mb-1">
                                            <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                        <p class="text-sm text-gray-600">{{ vehicle?.transmission || 'Automatic' }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Rental Details -->
                            <div class="space-y-4 mb-6">
                                <div class="flex justify-between items-center py-3 border-b border-gray-100">
                                    <span class="text-gray-600">Pickup</span>
                                    <div class="text-right">
                                        <p class="font-medium text-gray-900">{{ searchParams.date_from }} {{ searchParams.time_from }}</p>
                                        <p class="text-sm text-gray-500">{{ searchParams.pickup_location_id }}</p>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center py-3 border-b border-gray-100">
                                    <span class="text-gray-600">Dropoff</span>
                                    <div class="text-right">
                                        <p class="font-medium text-gray-900">{{ searchParams.date_to }} {{ searchParams.time_to }}</p>
                                        <p class="text-sm text-gray-500">{{ searchParams.dropoff_location_id || searchParams.pickup_location_id }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Pricing Breakdown -->
                            <div class="space-y-3 mb-6">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Base Rate ({{ rentalDuration }} days)</span>
                                    <span class="font-medium">{{ formatPrice((vehicle.tdr || vehicle.price_per_day) * rentalDuration) }}</span>
                                </div>
                                <div v-for="item in pricingBreakdown" :key="item.label" class="flex justify-between">
                                    <span class="text-gray-600">{{ item.label }}</span>
                                    <span class="font-medium">{{ formatPrice(item.amount) }}</span>
                                </div>
                            </div>

                            <!-- Total -->
                            <div class="border-t-2 border-gray-200 pt-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-xl font-bold text-gray-900">Total</span>
                                    <span class="text-2xl font-bold text-primary">{{ formatPrice(calculateTotal) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Trust Badges -->
                    <div class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                        <div class="flex items-center justify-center space-x-6 text-gray-600">
                            <div class="flex items-center text-sm">
                                <svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Secure Payment
                            </div>
                            <div class="flex items-center text-sm">
                                <svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                                </svg>
                                Insurance Included
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Fixed Bottom Navigation -->
    <div class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-40 safe-area-inset-bottom mobile-bottom-nav">
        <div class="container mx-auto px-4 py-3">
            <div class="flex items-center justify-between gap-3">
                <!-- Summary Preview -->
                <div class="flex-1">
                    <div class="flex flex-col items-start">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-600">Total</span>
                            <span class="text-lg font-bold text-primary">{{ formatPrice(formData.grand_total) }}</span>
                        </div>
                        <button @click="showSummaryModal = true"
                                class="flex items-center gap-1 text-primary hover:text-primary-dark transition-colors mt-1">
                            <span class="text-sm font-medium">Show Summary</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="flex gap-2">
                    <button v-if="currentStep > 1"
                            @click="prevStep"
                            class="px-3 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors min-h-[44px] flex items-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </button>
                    <button v-if="currentStep < 4"
                            @click="nextStep"
                            class="px-3 py-2.5 bg-primary text-white font-medium rounded-lg hover:bg-primary-dark transition-colors min-h-[44px] flex items-center">
                        <span class="text-sm">{{ currentStep === 3 ? 'Complete' : 'Next' }}</span>
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Summary Modal -->
    <Transition name="modal-slide">
        <div v-if="showSummaryModal" class="lg:hidden fixed inset-0 z-50">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black bg-opacity-50" @click="showSummaryModal = false"></div>

            <!-- Modal Content -->
            <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl max-h-[85vh] flex flex-col">
                <!-- Drag Indicator -->
                <div class="flex justify-center py-3">
                    <div class="w-12 h-1 bg-gray-300 rounded-full"></div>
                </div>

                <!-- Header -->
                <div class="px-4 pb-3 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold text-gray-900">Booking Summary</h2>
                        <button @click="showSummaryModal = false" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Scrollable Content -->
                <div class="flex-1 overflow-y-auto px-4 py-4">
                    <!-- Vehicle Info -->
                    <div class="pb-6 border-b border-gray-100">
                        <div class="flex gap-3 mb-4">
                            <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center px-2 flex-shrink-0">
                                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="font-bold text-gray-900 text-base truncate">{{ vehicle?.model }}</h3>
                                <p class="text-gray-600 text-sm">{{ vehicle?.category }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Rental Details -->
                    <div class="space-y-4 pb-6">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Pickup</span>
                            <div class="text-right">
                                <p class="font-medium text-gray-900 text-sm">{{ formatDate(searchParams.date_from) }}</p>
                                <p class="text-xs text-gray-500">{{ searchParams.time_from }}</p>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Return</span>
                            <div class="text-right">
                                <p class="font-medium text-gray-900 text-sm">{{ formatDate(searchParams.date_to) }}</p>
                                <p class="text-xs text-gray-500">{{ searchParams.time_to }}</p>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Duration</span>
                            <span class="font-medium text-gray-900 text-sm">{{ rentalDays }} days</span>
                        </div>
                    </div>

                    <!-- Package Details -->
                    <div class="pt-4 border-t border-gray-100 pb-6">
                        <h3 class="font-semibold text-gray-900 mb-3 text-base">Vehicle Package</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600 text-sm">Vehicle Rental</span>
                                <span class="font-medium text-sm">{{ formatPrice(formData.vehicle_total) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Protection -->
                    <div v-if="Object.keys(selectedProtections).length > 0" class="pt-4 border-t border-gray-100 pb-6">
                        <h3 class="font-semibold text-gray-900 mb-3 text-base">Protection Coverage</h3>
                        <div class="space-y-2">
                            <div v-for="(protection, code) in selectedProtections" :key="code"
                                 class="flex justify-between text-sm">
                                <span class="text-gray-600">{{ protection.displayName || protection.name || protection.code }}</span>
                                <span class="font-medium">{{ formatPrice(protection.total) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Extras -->
                    <div v-if="Object.keys(selectedExtras).length > 0" class="pt-4 border-t border-gray-100 pb-6">
                        <h3 class="font-semibold text-gray-900 mb-3 text-base">Additional Extras</h3>
                        <div class="space-y-2">
                            <div v-for="(extra, code) in selectedExtras" :key="code"
                                 class="flex justify-between text-sm">
                                <span class="text-gray-600">{{ extra.displayName || extra.name || extra.code }}</span>
                                <span class="font-medium">{{ formatPrice(extra.total) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Total -->
                    <div class="pt-4 border-t border-gray-200">
                        <div class="flex flex-col sm:flex-row sm:justify-between gap-2 sm:gap-0 p-4 bg-primary/5 rounded-lg -mx-4">
                            <span class="text-lg sm:text-xl font-bold text-gray-900">Total Amount</span>
                            <span class="text-xl sm:text-2xl font-bold text-primary">{{ formatPrice(formData.grand_total) }}</span>
                        </div>
                    </div>

                    <!-- Trust Badges -->
                    <div class="pt-4 border-t border-gray-100">
                        <div class="flex items-center justify-center space-x-4 text-gray-500">
                            <div class="flex items-center text-sm">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                </svg>
                                Secure Payment
                            </div>
                            <div class="flex items-center text-sm">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Instant Confirmation
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Transition>

    <Footer />
</template>

<style scoped>
/* Match GreenMotion's exact styling */
.container {
    max-width: 1200px;
    margin: 0 auto;
}

@media (max-width: 768px) {
    .container {
        padding: 2rem;
    }
}

/* Custom scrollbar for better UX */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: #2563eb;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: #1d4ed8;
}

/* Smooth transitions for all interactive elements */
* {
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

/* Focus styles for accessibility */
*:focus {
    outline: 2px solid transparent;
    outline-offset: 2px;
}

/* Enhanced form styling */
input:focus, textarea:focus, select:focus {
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Loading states */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

/* Card hover effects */
.card-hover:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Sticky booking summary */
.sticky {
    position: sticky;
    top: 2rem;
}

/* Mobile-specific styles */
@media (max-width: 1023px) {
    /* Mobile padding for main content */
    .mobile-content-padding {
        padding-bottom: 5rem; /* Space for fixed bottom navigation */
    }

    /* Modal slide transitions - hardware accelerated for smoother performance */
    .modal-slide-enter-active {
        transition: transform 0.4s cubic-bezier(0.32, 0.72, 0, 1),
                    opacity 0.4s cubic-bezier(0.32, 0.72, 0, 1);
    }

    .modal-slide-leave-active {
        transition: transform 0.3s cubic-bezier(0.4, 0, 1, 1),
                    opacity 0.3s cubic-bezier(0.4, 0, 1, 1);
    }

    .modal-slide-enter-from {
        transform: translateY(100%) translateZ(0);
        opacity: 0;
    }

    .modal-slide-leave-to {
        transform: translateY(100%) translateZ(0);
        opacity: 0;
    }

    /* Modal content animation for smoother entrance */
    .modal-slide-enter-active .absolute.bottom-0 {
        animation: modalContentSlideUp 0.4s cubic-bezier(0.32, 0.72, 0, 1) forwards;
    }

    @keyframes modalContentSlideUp {
        0% {
            transform: translateY(30px) translateZ(0);
            opacity: 0;
        }
        100% {
            transform: translateY(0) translateZ(0);
            opacity: 1;
        }
    }

    
    /* Safe area support for modern phones */
    .safe-area-bottom {
        padding-bottom: env(safe-area-inset-bottom);
    }

    /* Backdrop blur for modal */
    .backdrop-blur {
        backdrop-filter: blur(4px);
    }

    /* Hide elements on mobile */
    .mobile-hidden {
        display: none !important;
    }

    /* Show elements only on mobile */
    .mobile-only {
        display: block !important;
    }

    /* Flex column for mobile navigation */
    .mobile-flex-col {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }

    /* Custom scrollbar for mobile modal */
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f5f9;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #94a3b8;
        border-radius: 2px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #64748b;
    }
}
</style>