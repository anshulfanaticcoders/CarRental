<script setup>
import { Link, usePage, Head, router } from "@inertiajs/vue3";
import { computed, onMounted, ref, watch, nextTick, onBeforeUnmount } from "vue";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import Footer from "@/Components/Footer.vue";
import { useToast } from 'vue-toastification';
import GreenMotionStripeCheckout from "@/Components/GreenMotionStripeCheckout.vue";
import { useCurrency } from '@/composables/useCurrency';

const props = defineProps({
    vehicle: Object,
    location: Object,
    dropoffLocation: Object,
    optionalExtras: Array,
    filters: Object,
    locale: String,
    error: String,
    auth: Object,
    affiliate_data: Object,
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

const currencySymbols = ref({});
const exchangeRates = ref(null);
const paymentPercentage = ref(0);
const { selectedCurrency, supportedCurrencies, changeCurrency } = useCurrency();
import axios from 'axios';

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
    // Add other symbol-to-code mappings as needed
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

const convertCurrency = (price, fromCurrency) => {
    const numericPrice = parseFloat(price);
    if (isNaN(numericPrice)) {
        return 0; // Return 0 if price is not a number
    }

    let fromCurrencyCode = fromCurrency;
    if (symbolToCodeMap[fromCurrency]) {
        fromCurrencyCode = symbolToCodeMap[fromCurrency];
    }

    if (!exchangeRates.value || !fromCurrencyCode || !selectedCurrency.value) {
        return numericPrice; // Return original price if rates not loaded or currencies are invalid
    }
    const rateFrom = exchangeRates.value[fromCurrencyCode];
    const rateTo = exchangeRates.value[selectedCurrency.value];
    if (rateFrom && rateTo) {
        return (numericPrice / rateFrom) * rateTo;
    }
    return numericPrice; // Fallback to original price if conversion is not possible
};

onMounted(async () => {
    fetchExchangeRates();

    // Fetch payment percentage
    try {
        const response = await axios.get('/api/payment-percentage');
        if (response.data && response.data.payment_percentage !== undefined) {
            paymentPercentage.value = Number(response.data.payment_percentage);
        }
    } catch (error) {
        console.error('Error fetching payment percentage:', error);
    }

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
});

const getCurrencySymbol = (code) => {
    return currencySymbols.value[code] || '$';
};

// Helper function to format converted prices
const formatPrice = (price, fromCurrency) => {
    const convertedPrice = convertCurrency(price, fromCurrency);
    const currencySymbol = getCurrencySymbol(selectedCurrency.value);
    return `${currencySymbol}${convertedPrice.toFixed(2)}`;
};

const getPackageFullName = (type) => {
    const names = {
        BAS: 'Basic',
        PLU: 'Plus',
        PRE: 'Premium',
        PMP: 'Premium Plus +',
        FF: 'Full',
    };
    return names[type] || type;
};

const getFuelPolicyName = (policy) => {
  const policies = {
    FF: "Full To Full",
    SL: "Same To Last",
    SS: "Same To Same",
    EF: "Empty To Full",
    // add other cases here
  };
  return policies[policy] || policy;
};


const selectedPackage = ref(null);
const selectedOptionalExtras = ref([]);

// Affiliate data
const affiliateData = ref(props.affiliate_data || null);

const form = ref({
    location_id: props.filters?.location_id || props.location?.id || 61627,
    start_date: props.filters?.start_date || '2032-01-06',
    start_time: props.filters?.start_time || '09:00',
    end_date: props.filters?.end_date || '2032-01-08',
    end_time: props.filters?.end_time || '09:00',
    age: props.filters?.age || 35,
    rentalCode: null,
    customer: {
        firstname: '',
        surname: '',
        email: '',
        phone: '',
        address1: '',
        address2: '',
        address3: '',
        town: '',
        postcode: '',
        country: '',
        driver_licence_number: '',
        flight_number: '',
        comments: '',
        title: '',
        bplace: '',
        bdate: '',
        idno: '',
        idplace: '',
        idissue: '',
        idexp: '',
        licissue: '',
        licplace: '',
        licexp: '',
        idurl: '',
        id_rear_url: '',
        licurl: '',
        lic_rear_url: '',
        verification_response: '',
        custimage: '',
        dvlacheckcode: '',
    },
    extras: [],
    vehicle_id: props.vehicle?.id,
    vehicle_total: 0,
    currency: props.filters?.currency || props.vehicle?.products?.[0]?.currency || '$',
    grand_total: 0,
    paymentHandlerRef: null,
    quoteid: props.filters?.quoteid || props.vehicle?.products?.[0]?.quoteid || 'dummy_quote_id',
    dropoff_location_id: props.filters?.dropoff_location_id || null,
    payment_type: 'POA',
    remarks: null,
    user_id: props.auth.user?.id || null, // Add user_id to form
    vehicle_location: props.location?.name || null, // Add vehicle_location to form
});

// Form validation
const formErrors = ref({});

const validateStep = (step) => {
    const errors = {};
    
    if (step === 1) {
        if (!selectedPackage.value) {
            errors.package = 'Please select a rental package';
        }
    }
    
    if (step === 2) {
        if (!form.value.customer.firstname.trim()) {
            errors.firstname = 'First name is required';
        }
        if (!form.value.customer.surname.trim()) {
            errors.surname = 'Last name is required';
        }
        if (!form.value.customer.email.trim()) {
            errors.email = 'Email is required';
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.customer.email)) {
            errors.email = 'Please enter a valid email address';
        }
        if (!form.value.customer.phone.trim()) {
            errors.phone = 'Phone number is required';
        }
        if (!form.value.customer.address1.trim()) {
            errors.address1 = 'Address is required';
        }
        if (!form.value.customer.town.trim()) {
            errors.town = 'Town/City is required';
        }
        if (!form.value.customer.postcode.trim()) {
            errors.postcode = 'Postcode is required';
        }
        if (!form.value.customer.country.trim()) {
            errors.country = 'Country is required';
        }
        if (!form.value.customer.driver_licence_number.trim()) {
            errors.driver_licence_number = 'Driver\'s licence number is required';
        }
    }
    
    formErrors.value = errors;
    return Object.keys(errors).length === 0;
};

const rentalDuration = computed(() => {
    if (!form.value.start_date || !form.value.end_date) return 0;
    const startDate = new Date(form.value.start_date);
    const endDate = new Date(form.value.end_date);
    const diffTime = Math.abs(endDate - startDate);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
});

const availablePackages = computed(() => {
    return props.vehicle?.products || [];
});

const freeExtras = computed(() => {
    if (selectedPackage.value && selectedPackage.value.type === 'PMP') {
        return [...selectedOptionalExtras.value]
            .sort((a, b) => parseFloat(b.Total_for_this_booking) - parseFloat(a.Total_for_this_booking))
            .slice(0, 2)
            .map(extra => extra.optionID);
    }
    return [];
});

const calculateTotals = () => {
    let packagePrice = 0;
    if (selectedPackage.value) {
        packagePrice = parseFloat(selectedPackage.value.total);
        form.value.rentalCode = selectedPackage.value.type;
    }

    let extrasTotal = 0;
    if (selectedPackage.value && selectedPackage.value.type === 'PMP') {
        const sortedExtras = [...selectedOptionalExtras.value].sort((a, b) => parseFloat(b.Total_for_this_booking) - parseFloat(a.Total_for_this_booking));
        sortedExtras.forEach((extra, index) => {
            if (index >= 2) {
                extrasTotal += parseFloat(extra.Total_for_this_booking || 0);
            }
        });
    } else {
        selectedOptionalExtras.value.forEach(extra => {
            extrasTotal += parseFloat(extra.Total_for_this_booking || 0);
        });
    }

    form.value.vehicle_total = packagePrice;
    form.value.grand_total = packagePrice + extrasTotal;

    form.value.extras = selectedOptionalExtras.value.map(extra => ({
        id: extra.optionID,
        option_qty: 1,
        option_total: parseFloat(extra.Total_for_this_booking || extra.Daily_rate * rentalDuration.value || 0),
        pre_pay: extra.Prepay_available === 'yes' ? 'Yes' : 'No',
    }));
};

const calculateAmountPaid = computed(() => {
    const total = hasAffiliateDiscount.value ? finalTotalPrice.value : form.value.grand_total;
    if (paymentPercentage.value > 0) {
        return total * (paymentPercentage.value / 100);
    }
    return total;
});

const calculatePendingAmount = computed(() => {
    const total = hasAffiliateDiscount.value ? finalTotalPrice.value : form.value.grand_total;
    if (paymentPercentage.value > 0) {
        return total - calculateAmountPaid.value;
    }
    return 0;
});

// Affiliate discount computed properties
const originalTotalPrice = computed(() => {
    return form.value.grand_total;
});

const affiliateDiscountAmount = computed(() => {
    if (!affiliateData.value || !affiliateData.value.discount_type) {
        return 0;
    }

    const originalPrice = originalTotalPrice.value;
    if (affiliateData.value.discount_type === 'percentage') {
        return originalPrice * (parseFloat(affiliateData.value.discount_value) / 100);
    } else if (affiliateData.value.discount_type === 'fixed') {
        return parseFloat(affiliateData.value.discount_value);
    }

    return 0;
});

const hasAffiliateDiscount = computed(() => {
    return affiliateData.value && affiliateData.value.discount_value && parseFloat(affiliateData.value.discount_value) > 0;
});

const finalTotalPrice = computed(() => {
    return originalTotalPrice.value - affiliateDiscountAmount.value;
});

watch(selectedPackage, calculateTotals, { deep: true });
watch(selectedOptionalExtras, calculateTotals, { deep: true });

// Step navigation
const nextStep = () => {
    if (validateStep(currentStep.value)) {
        if (currentStep.value < 3) {
            currentStep.value++;
        }
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const goToStep = (step) => {
    if (step <= currentStep.value || validateStep(currentStep.value)) {
        currentStep.value = step;
    }
};

// Mobile detection composable
const checkMobile = () => {
    isMobile.value = window.innerWidth < 1024; // lg breakpoint
};

const isLoading = ref(false);
const dialogErrorMessage = ref('');
const showErrorDialog = ref(false);

onMounted(() => {
    // Initialize mobile detection
    checkMobile();
    window.addEventListener('resize', checkMobile);

    if (availablePackages.value.length > 0) {
        selectedPackage.value = availablePackages.value[0];
    }
    calculateTotals();

    if (props.auth.user) {
        form.value.customer.firstname = props.auth.user.first_name || '';
        form.value.customer.surname = props.auth.user.last_name || '';
        form.value.customer.email = props.auth.user.email || '';
        form.value.customer.phone = props.auth.user.phone || '';
    }

    const savedFormData = sessionStorage.getItem('greenMotionBookingForm');
    if (savedFormData) {
        const parsedData = JSON.parse(savedFormData);
        Object.assign(form.value, parsedData);
        sessionStorage.removeItem('greenMotionBookingForm');
        sessionStorage.removeItem('greenMotionVehicleId');
        sessionStorage.removeItem('greenMotionLocationId');
    }

    // Handle affiliate data from session storage
    if (!affiliateData.value) {
        const sessionAffiliateData = sessionStorage.getItem('affiliate_data');
        if (sessionAffiliateData) {
            try {
                affiliateData.value = JSON.parse(sessionAffiliateData);
                console.log("Affiliate data loaded from session storage in GreenMotionBooking.vue");
            } catch (error) {
                console.error("Error parsing affiliate data from session storage:", error);
            }
        } else {
            console.log("No affiliate data in session storage in GreenMotionBooking.vue");
        }
    } else {
        console.log("Affiliate data from props in GreenMotionBooking.vue:", affiliateData.value);
    }

    console.log("Quote ID in GreenMotionBooking.vue props.filters:", props.filters?.quoteid);
    console.log("Quote ID in GreenMotionBooking.vue form.value:", form.value.quoteid);
});

// Cleanup resize listener
onBeforeUnmount(() => {
    window.removeEventListener('resize', checkMobile);
});

const handleStripeError = (message) => {
    dialogErrorMessage.value = message || "Something went wrong, please try again later.";
    showErrorDialog.value = true;
    isLoading.value = false;
};

const bookingDataForStripe = computed(() => {
    return {
        customer: form.value.customer,
        location_id: form.value.location_id,
        start_date: form.value.start_date,
        start_time: form.value.start_time,
        end_date: form.value.end_date,
        end_time: form.value.end_time,
        age: form.value.age,
        rentalCode: form.value.rentalCode,
        vehicle_id: form.value.vehicle_id,
        vehicle_total: form.value.vehicle_total,
        currency: form.value.currency,
        grand_total: hasAffiliateDiscount ? finalTotalPrice.value : form.value.grand_total,
        paymentHandlerRef: form.value.paymentHandlerRef,
        quoteid: form.value.quoteid,
        payment_type: form.value.payment_type,
        remarks: form.value.remarks,
        extras: form.value.extras,
        user_id: form.value.user_id, // Pass user_id
        vehicle_location: form.value.vehicle_location, // Pass vehicle_location
        dropoff_location_id: props.filters?.dropoff_location_id,
        provider: props.filters?.provider,
        // Fields below are not directly validated by backend but might be useful for logging/storage
        pickup_location: props.location?.name || '',
        return_location: props.dropoffLocation?.name || props.location?.name || '',
        total_days: rentalDuration.value,
        extra_charges: form.value.grand_total - form.value.vehicle_total,
        tax_amount: 0,
        discount_amount: hasAffiliateDiscount ? affiliateDiscountAmount.value : 0,
        discount_amount: hasAffiliateDiscount ? affiliateDiscountAmount.value : 0,
        pending_amount: calculatePendingAmount.value,
        amount_paid: calculateAmountPaid.value,
        plan: null,
        plan_price: 0,
        greenmotion_booking_ref: null,
        api_response: null,
    };
});
</script>

<template>
    <Head>
        <title>Complete Your Booking - GreenMotion</title>
    </Head>
    <AuthenticatedHeaderLayout />

    <!-- Hero Section -->
    <section class="bg-primary py-8 sm:py-12 md:py-16 relative overflow-hidden">
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
                <p class="text-base sm:text-lg md:text-xl opacity-90">{{ vehicle?.name }} - {{ location?.name }}</p>
            </div>
        </div>
    </section>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 md:py-12">
        <div v-if="error" class="max-w-2xl mx-auto">
            <div class="bg-red-50 border border-red-200 rounded-xl p-8 text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-red-900 mb-2">Booking Error</h3>
                <p class="text-red-700 mb-6">{{ error }}</p>
                <Link :href="route('green-motion-cars', { locale: locale, ...filters })" 
                      class="inline-flex items-center px-6 py-3 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Search Results
                </Link>
            </div>
        </div>

        <div v-else-if="vehicle" class=" mx-auto py-12">
            <!-- Progress Steps -->
            <div class="mb-6 sm:mb-8">
                <!-- Mobile: Vertical steps -->
                <div class="flex flex-col sm:hidden space-y-3 mb-6">
                    <div v-for="step in 3" :key="step"
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
                                    {{ step === 1 ? 'Select Package' : step === 2 ? 'Your Details' : 'Payment' }}
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ step === 1 ? 'Choose rental package' : step === 2 ? 'Enter your information' : 'Complete payment' }}
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
                    <div v-for="step in 3" :key="step"
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
                                {{ step === 1 ? 'Select Package' : step === 2 ? 'Your Details' : 'Payment' }}
                            </span>
                        </div>
                        <div v-if="step < 3" class="w-12 sm:w-16 md:w-20 h-0.5 ml-2 sm:ml-4 md:ml-8"
                             :class="step < currentStep ? 'bg-primary' : 'bg-gray-200'"></div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-5 gap-6 lg:gap-8">
                <!-- Main Content -->
                <div class="xl:col-span-3 lg:col-span-4" :class="{ 'mobile-content-padding': isMobile }">
                    <!-- Step 1: Package Selection -->
                    <div v-show="currentStep === 1" class="space-y-6 sm:space-y-8">
                        <!-- Vehicle Summary Card -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="p-4 sm:p-6 bg-gradient-to-r from-gray-50 to-white border-b">
                                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2">Your Selected Vehicle</h2>
                                <p class="text-sm sm:text-base text-gray-600">Review your vehicle and rental details</p>
                            </div>
                            <div class="p-4 sm:p-6">
                                <div class="flex flex-col md:flex-row gap-4 sm:gap-6">
                                    <div class="md:w-1/3">
                                        <img :src="vehicle.image || '/default-car-image.jpg'"
                                             :alt="vehicle.name"
                                             class="w-full h-40 sm:h-48 md:h-32 object-cover rounded-xl shadow-sm" />
                                    </div>
                                    <div class="md:w-2/3 space-y-3">
                                        <div>
                                            <h3 class="text-lg sm:text-xl font-bold text-gray-900">{{ vehicle.name }}</h3>
                                            <p class="text-sm sm:text-base text-gray-600">{{ vehicle.groupName }}</p>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                                            <div class="bg-gray-50 rounded-lg p-3">
                                                <p class="text-xs sm:text-sm font-medium text-gray-500">Rental Duration</p>
                                                <p class="text-base sm:text-lg font-semibold text-gray-900">{{ rentalDuration }} days</p>
                                            </div>
                                            <div class="bg-gray-50 rounded-lg p-3">
                                                <p class="text-xs sm:text-sm font-medium text-gray-500">Location</p>
                                                <p class="text-base sm:text-lg font-semibold text-gray-900 truncate">{{ location?.name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Package Selection -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                            <div class="p-6 border-b bg-gradient-to-r from-gray-50 to-white">
                                <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-2">Choose Your Rental Package</h3>
                                <p class="text-gray-600">Select the package that best suits your needs</p>
                            </div>
                            <div class="p-6">
                                <div v-if="formErrors.package" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                                    <p class="text-red-700 text-sm">{{ formErrors.package }}</p>
                                </div>
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                                    <div v-for="pkg in availablePackages" :key="pkg.type"
                                         class="relative border-2 rounded-xl p-4 sm:p-6 cursor-pointer transition-all duration-300 hover:shadow-lg"
                                         :class="selectedPackage?.type === pkg.type 
                                           ? 'border-primary bg-primary/5 shadow-md' 
                                           : 'border-gray-200 hover:border-gray-300'"
                                         @click="selectedPackage = pkg">
                                        <div class="flex items-start justify-between mb-4">
                                            <h4 :class="[
    'text-lg font-bold text-gray-900',
    getPackageFullName(pkg.type) === 'Premium Plus +' ? 'border-[2px] border-green-500 p-2 rounded' : ''
  ]" class="text-base sm:text-lg font-bold text-gray-900">{{ getPackageFullName(pkg.type) }}</h4>
                                            <div class="w-6 h-6 sm:w-5 sm:h-5 rounded-full border-2 flex items-center justify-center"
                                                 :class="selectedPackage?.type === pkg.type
                                                   ? 'border-primary bg-primary'
                                                   : 'border-gray-300'">
                                                <div v-if="selectedPackage?.type === pkg.type"
                                                     class="w-3 h-3 sm:w-2 sm:h-2 bg-white rounded-full"></div>
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <div class="flex justify-between">
                                                <span class="text-sm sm:text-base text-gray-600">Total Price</span>
                                                <span class="font-semibold text-sm sm:text-base">{{ formatPrice(pkg.total, pkg.currency) }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-sm sm:text-base text-gray-600">Deposit</span>
                                                <span class="font-semibold text-sm sm:text-base">{{ formatPrice(pkg.deposit, pkg.currency) }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-sm sm:text-base text-gray-600">Excess</span>
                                                <span class="font-semibold text-sm sm:text-base">{{ formatPrice(pkg.excess, pkg.currency) }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-sm sm:text-base text-gray-600">Fuel Policy</span>
                                                <span class="font-semibold text-sm sm:text-base">{{ pkg.fuelpolicy }} <span class="text-xs">({{ getFuelPolicyName(pkg.fuelpolicy) }})</span></span>
                                            </div>
                                        </div>
                                        <div v-if="pkg.benefits" class="mt-4 pt-4 border-t border-gray-200">
                                            <h5 class="text-sm font-semibold text-gray-800 mb-2">Package Includes:</h5>
                                            <ul class="space-y-1.5 sm:space-y-2">
                                                <li v-for="(value, key) in pkg.benefits" :key="key" class="flex items-start sm:items-center text-xs sm:text-sm text-gray-600">
                                                    <svg v-if="value" class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                    </svg>
                                                    <svg v-else class="w-4 h-4 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                    </svg>
                                                    <span>{{ key }}</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Optional Extras -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                            <div class="p-4 sm:p-6 border-b bg-gradient-to-r from-gray-50 to-white">
                                <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-2">Optional Extras</h3>
                                <p class="text-sm sm:text-base text-gray-600">Enhance your rental experience</p>
                            </div>
                            <div class="p-4 sm:p-6">
                                <div v-if="optionalExtras.length > 0" class="space-y-3 sm:space-y-4">
                                    <div v-for="extra in optionalExtras" :key="extra.optionID || extra.Name"
                                         class="relative flex items-center justify-between p-3 sm:p-4 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                                        <div v-if="freeExtras.includes(extra.optionID)" class="absolute top-0 right-0 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-bl-lg rounded-tr-lg">
                                            Free
                                        </div>
                                        <div class="flex items-start space-x-4">
                                            <div class="flex-shrink-0 w-12 h-12 bg-secondary/20 rounded-lg flex items-center justify-center">
                                                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <h4 class="font-semibold text-gray-900 text-base sm:text-lg">{{ extra.Name }}</h4>
                                                <p class="text-gray-600 text-sm">{{ extra.Description }}</p>
                                                <p class="text-primary font-semibold mt-1">
                                                    {{ formatPrice(extra.Daily_rate, extra.Daily_rate_currency) }}/day
                                                </p>
                                            </div>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer p-2 -m-2 rounded-lg">
                                            <input type="checkbox" :value="extra" v-model="selectedOptionalExtras" class="sr-only peer">
                                            <div class="relative w-12 h-7 sm:w-11 sm:h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                        </label>
                                    </div>
                                </div>
                                <div v-else class="text-center py-8">
                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                        </svg>
                                    </div>
                                    <p class="text-gray-500">No optional extras available for this vehicle.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Customer Information -->
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
                                                <input type="text" id="firstname" v-model="form.customer.firstname"
                                                       class="w-full px-4 py-3 sm:py-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors text-base sm:text-lg"
                                                       :class="{ 'border-red-300 bg-red-50': formErrors.firstname }"
                                                       placeholder="Enter your first name" />
                                                <p v-if="formErrors.firstname" class="mt-1 text-sm text-red-600">{{ formErrors.firstname }}</p>
                                            </div>
                                            <div>
                                                <label for="surname" class="block text-sm font-medium text-gray-700 mb-2">
                                                    Last Name <span class="text-red-500">*</span>
                                                </label>
                                                <input type="text" id="surname" v-model="form.customer.surname" 
                                                       class="w-full px-4 py-3 sm:py-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors text-base sm:text-lg"
                                                       :class="{ 'border-red-300 bg-red-50': formErrors.surname }"
                                                       placeholder="Enter your last name" />
                                                <p v-if="formErrors.surname" class="mt-1 text-sm text-red-600">{{ formErrors.surname }}</p>
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
                                                <input type="email" id="email" v-model="form.customer.email" readonly
                                                       class="w-full px-4 py-3 border border-gray-300 bg-gray-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                                                       :class="{ 'border-red-300 bg-red-50': formErrors.email }"
                                                       placeholder="your.email@example.com" />
                                                <p v-if="formErrors.email" class="mt-1 text-sm text-red-600">{{ formErrors.email }}</p>
                                            </div>
                                            <div>
                                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                                    Phone Number <span class="text-red-500">*</span>
                                                </label>
                                                <input type="tel" id="phone" v-model="form.customer.phone" 
                                                       class="w-full px-4 py-3 sm:py-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors text-base sm:text-lg"
                                                       :class="{ 'border-red-300 bg-red-50': formErrors.phone }"
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
                                                <input type="text" id="address1" v-model="form.customer.address1" 
                                                       class="w-full px-4 py-3 sm:py-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors text-base sm:text-lg"
                                                       :class="{ 'border-red-300 bg-red-50': formErrors.address1 }"
                                                       placeholder="Street address, P.O. box, company name" />
                                                <p v-if="formErrors.address1" class="mt-1 text-sm text-red-600">{{ formErrors.address1 }}</p>
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                                                <div>
                                                    <label for="town" class="block text-sm font-medium text-gray-700 mb-2">
                                                        Town/City <span class="text-red-500">*</span>
                                                    </label>
                                                    <input type="text" id="town" v-model="form.customer.town" 
                                                           class="w-full px-4 py-3 sm:py-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors text-base sm:text-lg"
                                                           :class="{ 'border-red-300 bg-red-50': formErrors.town }"
                                                           placeholder="City" />
                                                    <p v-if="formErrors.town" class="mt-1 text-sm text-red-600">{{ formErrors.town }}</p>
                                                </div>
                                                <div>
                                                    <label for="postcode" class="block text-sm font-medium text-gray-700 mb-2">
                                                        Postcode <span class="text-red-500">*</span>
                                                    </label>
                                                    <input type="text" id="postcode" v-model="form.customer.postcode" 
                                                           class="w-full px-4 py-3 sm:py-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors text-base sm:text-lg"
                                                           :class="{ 'border-red-300 bg-red-50': formErrors.postcode }"
                                                           placeholder="12345" />
                                                    <p v-if="formErrors.postcode" class="mt-1 text-sm text-red-600">{{ formErrors.postcode }}</p>
                                                </div>
                                                <div>
                                                    <label for="country" class="block text-sm font-medium text-gray-700 mb-2">
                                                        Country <span class="text-red-500">*</span>
                                                    </label>
                                                    <input type="text" id="country" v-model="form.customer.country" 
                                                           class="w-full px-4 py-3 sm:py-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors text-base sm:text-lg"
                                                           :class="{ 'border-red-300 bg-red-50': formErrors.country }"
                                                           placeholder="Country" />
                                                    <p v-if="formErrors.country" class="mt-1 text-sm text-red-600">{{ formErrors.country }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Driver Information -->
                                    <div>
                                        <h4 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">Driver Information</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                                            <div>
                                                <label for="driver_licence_number" class="block text-sm font-medium text-gray-700 mb-2">
                                                    Driver's Licence Number <span class="text-red-500">*</span>
                                                </label>
                                                <input type="text" id="driver_licence_number" v-model="form.customer.driver_licence_number" 
                                                       class="w-full px-4 py-3 sm:py-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors text-base sm:text-lg"
                                                       :class="{ 'border-red-300 bg-red-50': formErrors.driver_licence_number }"
                                                       placeholder="Licence number" />
                                                <p v-if="formErrors.driver_licence_number" class="mt-1 text-sm text-red-600">{{ formErrors.driver_licence_number }}</p>
                                            </div>
                                            <div>
                                                <label for="age" class="block text-sm font-medium text-gray-700 mb-2">
                                                    Age <span class="text-red-500">*</span>
                                                </label>
                                                <input type="number" id="age" v-model="form.age" 
                                                       class="w-full px-4 py-3 sm:py-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors text-base sm:text-lg"
                                                       placeholder="25" min="18" max="99" />
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
                                                <input type="text" id="flight_number" v-model="form.customer.flight_number" 
                                                       class="w-full px-4 py-3 sm:py-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors text-base sm:text-lg"
                                                       placeholder="BA123" />
                                            </div>
                                            <div>
                                                <label for="comments" class="block text-sm font-medium text-gray-700 mb-2">
                                                    Special Requests
                                                </label>
                                                <textarea id="comments" v-model="form.customer.comments" rows="3"
                                                          class="w-full px-4 py-3 sm:py-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors text-base sm:text-lg"
                                                          placeholder="Any special requests or comments..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Payment -->
                    <div v-show="currentStep === 3" class="space-y-6 sm:space-y-8">
                        <!-- Currency Selector -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-2 sm:gap-3 mb-4 sm:mb-0">
                            <label class="text-sm font-medium text-gray-700">Currency:</label>
                            <select v-model="selectedCurrency"
                                class="px-3 py-2 sm:py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-full sm:w-auto">
                                <option value="USD">USD ($)</option>
                                <option value="EUR">EUR (€)</option>
                                <option value="GBP">GBP (£)</option>
                                <option value="JPY">JPY (¥)</option>
                                <option value="AUD">AUD (A$)</option>
                                <option value="CAD">CAD (C$)</option>
                                <option value="CHF">CHF (Fr)</option>
                                <option value="HKD">HKD (HK$)</option>
                                <option value="SGD">SGD (S$)</option>
                                <option value="SEK">SEK (kr)</option>
                                <option value="NOK">NOK (kr)</option>
                                <option value="NZD">NZD (NZ$)</option>
                                <option value="INR">INR (₹)</option>
                                <option value="MXN">MXN (Mex$)</option>
                                <option value="ZAR">ZAR (R)</option>
                                <option value="AED">AED</option>
                            </select>
                        </div>
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                            <div class="p-4 sm:p-6 border-b bg-gradient-to-r from-gray-50 to-white">
                                <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-2">Complete Your Payment</h3>
                                <p class="text-sm sm:text-base text-gray-600">Review your booking and complete the payment process</p>
                            </div>
                            <div class="p-4 sm:p-6">
                                <GreenMotionStripeCheckout :booking-data="bookingDataForStripe" @error="handleStripeError" />
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

                        <button v-if="currentStep < 3"
                                @click="nextStep"
                                class="inline-flex items-center justify-center px-6 py-4 sm:py-3 bg-primary text-white font-medium rounded-lg hover:bg-primary-dark transition-colors text-base sm:text-lg w-full lg:w-auto min-h-[44px] shadow-lg">
                            Next Step
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Right Column: Booking Summary -->
                <div class="xl:col-span-2 lg:col-span-1">
                    <!-- Old Mobile Summary Toggle - Hidden -->
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

                            <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
                                <!-- Vehicle Info -->
                                <div class="pb-6 border-b border-gray-100">
                                    <div class="flex gap-3 mb-4">
                                        <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center px-2 flex-shrink-0">
                                            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <h4 class="font-bold text-gray-900 text-base sm:text-lg truncate">{{ vehicle?.name }}</h4>
                                            <p class="text-gray-600 text-sm">{{ vehicle?.groupName }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rental Details -->
                                <div class="space-y-4">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">Pickup</span>
                                        <div class="text-right">
                                            <p class="font-medium text-gray-900">{{ form.start_date }}</p>
                                            <p class="text-sm text-gray-500">{{ form.start_time }} at {{ location?.name }}</p>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">Return</span>
                                        <div class="text-right">
                                            <p class="font-medium text-gray-900">{{ form.end_date }}</p>
                                            <p class="text-sm text-gray-500">{{ form.end_time }} at {{ dropoffLocation?.name || location?.name }}</p>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">Duration</span>
                                        <span class="font-medium text-gray-900">{{ rentalDuration }} days</span>
                                    </div>
                                </div>

                                <!-- Package Details -->
                                <div class="pt-4 border-t border-gray-100">
                                    <h4 class="font-semibold text-gray-900 mb-3 text-base sm:text-lg">Package Details</h4>
                                    <div v-if="selectedPackage" class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">{{ selectedPackage.type }}</span>
                                            <span class="font-medium">{{ formatPrice(selectedPackage.total, selectedPackage.currency) }}</span>
                                        </div>
                                    </div>
                                    <p v-else class="text-gray-500 italic">No package selected</p>
                                </div>

                                <!-- Extras -->
                                <div v-if="selectedOptionalExtras.length > 0" class="pt-4 border-t border-gray-100">
                                    <h4 class="font-semibold text-gray-900 mb-3 text-base sm:text-lg">Selected Extras</h4>
                                    <div class="space-y-2">
                                        <div v-for="extra in selectedOptionalExtras" :key="extra.optionID" 
                                             class="flex justify-between text-sm">
                                            <span class="text-gray-600">{{ extra.Name }}</span>
                                            <span class="font-medium">{{ formatPrice(extra.Daily_rate, extra.Daily_rate_currency) }}/day</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Total -->
                                <div class="pt-4 border-t border-gray-200">
                                    <!-- Original Price with Discount -->
                                    <div v-if="hasAffiliateDiscount" class="flex justify-between items-center mb-2">
                                        <span class="text-lg text-gray-600">Original Price:</span>
                                        <span class="text-lg text-gray-500 line-through">{{ formatPrice(originalTotalPrice, form.currency) }}</span>
                                    </div>

                                    <!-- Affiliate Discount -->
                                    <div v-if="hasAffiliateDiscount" class="flex justify-between items-center mb-2">
                                        <span class="text-lg text-green-600">Affiliate Discount ({{ affiliateData.discount_type === 'percentage' ? affiliateData.discount_value + '%' : formatPrice(affiliateData.discount_value, form.currency) }}):</span>
                                        <span class="text-lg font-bold text-green-600">-{{ formatPrice(affiliateDiscountAmount, form.currency) }}</span>
                                    </div>

                                    <!-- Final Total -->
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-xl font-bold text-gray-900">Total</span>
                                        <span class="text-2xl font-bold text-primary">{{ formatPrice(hasAffiliateDiscount ? finalTotalPrice : form.grand_total, form.currency) }}</span>
                                    </div>

                                    <!-- Partial Payment Breakdown -->
                                    <div v-if="paymentPercentage > 0" class="pt-2 border-t border-gray-100 mt-2 space-y-2">
                                         <div class="flex justify-between items-center bg-green-50 p-2 rounded-lg">
                                            <span class="font-medium text-green-800">Pay Now ({{ paymentPercentage }}%)</span>
                                            <span class="font-bold text-green-800">{{ formatPrice(calculateAmountPaid, form.currency) }}</span>
                                        </div>
                                        <div class="flex justify-between items-center p-2">
                                            <span class="text-gray-600">Pay on Arrival</span>
                                            <span class="font-medium text-gray-900">{{ formatPrice(calculatePendingAmount, form.currency) }}</span>
                                        </div>
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
                </div>
            </div>
        </div>
    </div>

    <!-- Error Dialog -->
    <div v-if="showErrorDialog" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 sm:p-6">
        <div class="bg-white rounded-2xl p-6 sm:p-8 max-w-md w-full mx-4 sm:mx-auto">
            <div class="text-center">
                <div class="w-16 h-16 sm:w-20 sm:h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4 sm:mb-6">
                    <svg class="w-8 h-8 sm:w-10 sm:h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/>
                    </svg>
                </div>
                <h3 class="text-lg sm:text-xl font-semibold text-gray-900 mb-2 sm:mb-3">Payment Error</h3>
                <p class="text-gray-600 mb-6 sm:mb-8 text-sm sm:text-base">{{ dialogErrorMessage }}</p>
                <button @click="showErrorDialog = false"
                        class="w-full px-6 py-4 sm:py-3 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors text-base sm:text-lg min-h-[44px] shadow-lg">
                    Close
                </button>
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
                            <span class="text-lg font-bold text-primary">{{ formatPrice(hasAffiliateDiscount ? finalTotalPrice : form.grand_total, form.currency) }}</span>
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

                    <button v-if="currentStep < 3"
                            @click="nextStep"
                            class="px-3 py-2.5 bg-primary text-white font-medium rounded-lg hover:bg-primary-dark transition-colors min-h-[44px] flex items-center">
                        <span class="text-sm">{{ currentStep === 2 ? 'Pay Now' : 'Next' }}</span>
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
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
                                <h3 class="font-bold text-gray-900 text-base sm:text-lg truncate">{{ vehicle?.name }}</h3>
                                <p class="text-gray-600 text-sm">{{ vehicle?.groupName }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Rental Details -->
                    <div class="space-y-4 pb-6">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Pickup</span>
                            <div class="text-right">
                                <p class="font-medium text-gray-900 text-sm">{{ form.start_date }}</p>
                                <p class="text-xs text-gray-500">{{ form.start_time }} at {{ location?.name }}</p>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Return</span>
                            <div class="text-right">
                                <p class="font-medium text-gray-900 text-sm">{{ form.end_date }}</p>
                                <p class="text-xs text-gray-500">{{ form.end_time }} at {{ dropoffLocation?.name || location?.name }}</p>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Duration</span>
                            <span class="font-medium text-gray-900 text-sm">{{ rentalDuration }} days</span>
                        </div>
                    </div>

                    <!-- Package Details -->
                    <div class="pt-4 border-t border-gray-100 pb-6">
                        <h3 class="font-semibold text-gray-900 mb-3 text-base">Package Details</h3>
                        <div v-if="selectedPackage" class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600 text-sm">{{ getPackageFullName(selectedPackage.type) }}</span>
                                <span class="font-medium text-sm">{{ formatPrice(selectedPackage.total, selectedPackage.currency) }}</span>
                            </div>
                        </div>
                        <p v-else class="text-gray-500 italic text-sm">No package selected</p>
                    </div>

                    <!-- Extras -->
                    <div v-if="selectedOptionalExtras.length > 0" class="pt-4 border-t border-gray-100 pb-6">
                        <h3 class="font-semibold text-gray-900 mb-3 text-base">Selected Extras</h3>
                        <div class="space-y-2">
                            <div v-for="extra in selectedOptionalExtras" :key="extra.optionID"
                                 class="flex justify-between text-sm">
                                <span class="text-gray-600">{{ extra.Name }}</span>
                                <span class="font-medium">{{ formatPrice(extra.Daily_rate, extra.Daily_rate_currency) }}/day</span>
                            </div>
                        </div>
                    </div>

                    <!-- Total -->
                    <div class="pt-4 border-t border-gray-200">
                        <!-- Original Price with Discount -->
                        <div v-if="hasAffiliateDiscount" class="flex justify-between items-center mb-2">
                            <span class="text-sm text-gray-600">Original Price:</span>
                            <span class="text-sm text-gray-500 line-through">{{ formatPrice(originalTotalPrice, form.currency) }}</span>
                        </div>

                        <!-- Affiliate Discount -->
                        <div v-if="hasAffiliateDiscount" class="flex justify-between items-center mb-2">
                            <span class="text-sm text-green-600">Affiliate Discount ({{ affiliateData.discount_type === 'percentage' ? affiliateData.discount_value + '%' : formatPrice(affiliateData.discount_value, form.currency) }}):</span>
                            <span class="text-sm font-bold text-green-600">-{{ formatPrice(affiliateDiscountAmount, form.currency) }}</span>
                        </div>

                        <!-- Final Total -->
                        <div class="flex justify-between items-center bg-primary/5 -mx-4 px-4 py-3 rounded-lg">
                            <span class="text-lg font-bold text-gray-900">Total</span>
                            <span class="text-xl font-bold text-primary">{{ formatPrice(hasAffiliateDiscount ? finalTotalPrice : form.grand_total, form.currency) }}</span>
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
.primary {
    background-color: #153b4f;
}

.primary-dark {
    background-color: #0f2936;
}

.secondary {
    background-color: #153B4F1A;
}

.bg-primary {
    background-color: #153b4f;
}

.bg-primary-dark {
    background-color: #0f2936;
}

.bg-secondary {
    background-color: #153B4F1A;
}

.text-primary {
    color: #153b4f;
}

.text-primary-dark {
    color: #0f2936;
}

.border-primary {
    border-color: #153b4f;
}

.ring-primary\/20 {
    --tw-ring-color: rgba(21, 59, 79, 0.2);
}

.focus\:ring-primary\/20:focus {
    --tw-ring-color: rgba(21, 59, 79, 0.2);
}

.focus\:border-primary:focus {
    border-color: #153b4f;
}

.hover\:bg-primary:hover {
    background-color: #153b4f;
}

.hover\:bg-primary-dark:hover {
    background-color: #0f2936;
}

.peer-checked\:bg-primary {
    background-color: #d7d7d7;
}

.peer-focus\:ring-primary\/20 {
    --tw-ring-color: rgba(21, 59, 79, 0.2);
}

.container {
    max-width: 1600px;
    margin: 0 auto;
}

@media (min-width: 768px) {
    .container {
        padding: 0 2rem;
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
    background: #153b4f;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: #0f2936;
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
    box-shadow: 0 0 0 3px rgba(21, 59, 79, 0.1);
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

/* Gradient backgrounds */
.gradient-primary {
    background: linear-gradient(135deg, #153b4f 0%, #0f2936 100%);
}

.gradient-secondary {
    background: linear-gradient(135deg, #153B4F1A 0%, rgba(21, 59, 79, 0.05) 100%);
}

/* Mobile Modal Transitions - hardware accelerated for smoother performance */
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
.modal-slide-enter-active .fixed.bottom-0 {
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

/* Safe Area Support */
.safe-area-inset-bottom {
    padding-bottom: env(safe-area-inset-bottom);
}

/* Mobile Bottom Navigation */
.mobile-bottom-nav {
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

/* Ensure content doesn't get hidden behind fixed bottom nav */
.mobile-content-padding {
    padding-bottom: 80px; /* Height of fixed bottom nav */
}

@media (min-width: 1024px) {
    .mobile-content-padding {
        padding-bottom: 0;
    }
}
</style>
