<script setup>
import { Link, usePage, Head, router } from "@inertiajs/vue3";
import { computed, onMounted, ref, watch, nextTick, onBeforeUnmount } from "vue";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import Footer from "@/Components/Footer.vue";
import { useToast } from 'vue-toastification';
import OkMobilityStripeCheckout from "@/Components/OkMobilityStripeCheckout.vue";
import { useCurrency } from '@/composables/useCurrency';
import axios from 'axios';

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
  };
  return policies[policy] || policy;
};


const selectedPackage = ref(null);
const selectedOptionalExtras = ref([]);

// Affiliate data
const affiliateData = ref(props.affiliate_data || null);

const form = ref({
    pickup_station_id: props.filters?.location_id || props.location?.id,
    dropoff_station_id: props.filters?.dropoff_location_id || null,
    start_date: props.filters?.start_date,
    start_time: props.filters?.start_time,
    end_date: props.filters?.end_date,
    end_time: props.filters?.end_time,
    age: props.filters?.age,
    ok_mobility_group_id: null,
    ok_mobility_token: null,
    rentalCode: null, // Keep if needed for internal logic, but primary is group_id/token
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
    payment_type: 'POA',
    remarks: null,
    user_id: props.auth.user?.id || null,
    vehicle_location: props.location?.name || null,
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
        // Assuming group_id and token are available in the product object
        form.value.ok_mobility_group_id = selectedPackage.value.group_id || selectedPackage.value.groupId || selectedPackage.value.ok_mobility_group_id;
        form.value.ok_mobility_token = selectedPackage.value.token || selectedPackage.value.ok_mobility_token;
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

    const savedFormData = sessionStorage.getItem('okMobilityBookingForm');
    if (savedFormData) {
        const parsedData = JSON.parse(savedFormData);
        Object.assign(form.value, parsedData);
        sessionStorage.removeItem('okMobilityBookingForm');
        sessionStorage.removeItem('okMobilityVehicleId');
        sessionStorage.removeItem('okMobilityLocationId');
    }

    // Handle affiliate data from session storage
    if (!affiliateData.value) {
        const sessionAffiliateData = sessionStorage.getItem('affiliate_data');
        if (sessionAffiliateData) {
            try {
                affiliateData.value = JSON.parse(sessionAffiliateData);
            } catch (error) {
                console.error("Error parsing affiliate data from session storage:", error);
            }
        }
    }
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
        pickup_station_id: form.value.pickup_station_id,
        dropoff_station_id: form.value.dropoff_station_id,
        start_date: form.value.start_date,
        start_time: form.value.start_time,
        end_date: form.value.end_date,
        end_time: form.value.end_time,
        age: form.value.age,
        ok_mobility_group_id: form.value.ok_mobility_group_id,
        ok_mobility_token: form.value.ok_mobility_token,
        vehicle_id: form.value.vehicle_id,
        vehicle_total: form.value.vehicle_total,
        currency: form.value.currency,
        grand_total: hasAffiliateDiscount.value ? finalTotalPrice.value : form.value.grand_total,
        paymentHandlerRef: form.value.paymentHandlerRef,
        payment_type: form.value.payment_type,
        remarks: form.value.remarks,
        extras: form.value.extras,
        user_id: form.value.user_id,
        vehicle_location: form.value.vehicle_location,
        provider: props.filters?.provider,
        pickup_location: props.location?.name || '',
        return_location: props.dropoffLocation?.name || props.location?.name || '',
        total_days: rentalDuration.value,
        extra_charges: form.value.grand_total - form.value.vehicle_total,
        tax_amount: 0,
        discount_amount: hasAffiliateDiscount.value ? affiliateDiscountAmount.value : 0,
        pending_amount: calculatePendingAmount.value,
        amount_paid: calculateAmountPaid.value,
        amount_due_on_arrival: calculatePendingAmount.value, // Alias for consistency if needed
        plan: null,
        plan_price: 0,
    };
});
</script>

<template>
    <Head>
        <title>Complete Your Booking - OK Mobility</title>
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
                <Link :href="route('search', { locale: locale, ...filters })" 
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
            <!-- ... (Keeping existing step navigation as it's generic enough) ... -->
             <div class="mb-6 sm:mb-8">
                 <div class="flex flex-col sm:hidden space-y-3 mb-6">
                    <div v-for="step in 3" :key="step"
                         class="flex items-center cursor-pointer transition-all duration-300 p-3 rounded-lg"
                         :class="step <= currentStep ? 'bg-primary/5' : 'bg-gray-50'"
                         @click="goToStep(step)">
                        <div class="flex items-center flex-1">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center font-semibold transition-all duration-300"
                                 :class="step <= currentStep ? 'bg-primary text-white shadow-lg' : 'bg-gray-200 text-gray-600'">
                                <span class="text-lg">{{ step }}</span>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="font-medium text-gray-900 text-sm" :class="step <= currentStep ? 'text-primary font-semibold' : 'text-gray-500'">
                                    {{ step === 1 ? 'Select Package' : step === 2 ? 'Your Details' : 'Payment' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="hidden sm:flex items-center justify-center space-x-4 md:space-x-8 mb-6 sm:mb-8">
                     <div v-for="step in 3" :key="step"
                         class="flex items-center cursor-pointer transition-all duration-300"
                         @click="goToStep(step)">
                        <div class="flex items-center">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center font-semibold transition-all duration-300"
                                 :class="step <= currentStep ? 'bg-primary text-white shadow-lg' : 'bg-gray-200 text-gray-600'">
                                <span class="text-sm sm:text-base">{{ step }}</span>
                            </div>
                            <span class="ml-2 sm:ml-3 font-medium text-gray-700 text-sm sm:text-base"
                                  :class="step <= currentStep ? 'text-primary' : 'text-gray-500'">
                                {{ step === 1 ? 'Select Package' : step === 2 ? 'Your Details' : 'Payment' }}
                            </span>
                        </div>
                         <div v-if="step < 3" class="w-12 sm:w-16 md:w-20 h-0.5 ml-2 sm:ml-4 md:ml-8" :class="step < currentStep ? 'bg-primary' : 'bg-gray-200'"></div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-5 gap-6 lg:gap-8">
                <!-- Main Content -->
                <div class="xl:col-span-3 lg:col-span-4">
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
                                        <img :src="vehicle.image || '/default-car-image.jpg'" :alt="vehicle.name" class="w-full h-40 sm:h-48 md:h-32 object-cover rounded-xl shadow-sm" />
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
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                                    <div v-for="pkg in availablePackages" :key="pkg.type"
                                         class="relative border-2 rounded-xl p-4 sm:p-6 cursor-pointer transition-all duration-300 hover:shadow-lg"
                                         :class="selectedPackage?.type === pkg.type ? 'border-primary bg-primary/5 shadow-md' : 'border-gray-200 hover:border-gray-300'"
                                         @click="selectedPackage = pkg">
                                         <div class="flex items-start justify-between mb-4">
                                            <h4 class="text-base sm:text-lg font-bold text-gray-900">{{ getPackageFullName(pkg.type) }}</h4>
                                            <div class="w-6 h-6 sm:w-5 sm:h-5 rounded-full border-2 flex items-center justify-center"
                                                 :class="selectedPackage?.type === pkg.type ? 'border-primary bg-primary' : 'border-gray-300'">
                                                <div v-if="selectedPackage?.type === pkg.type" class="w-3 h-3 sm:w-2 sm:h-2 bg-white rounded-full"></div>
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <div class="flex justify-between"><span class="text-sm sm:text-base text-gray-600">Total Price</span><span class="font-semibold text-sm sm:text-base">{{ formatPrice(pkg.total, pkg.currency) }}</span></div>
                                            <div class="flex justify-between"><span class="text-sm sm:text-base text-gray-600">Deposit</span><span class="font-semibold text-sm sm:text-base">{{ formatPrice(pkg.deposit, pkg.currency) }}</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                         <!-- Optional Extras -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                            <div class="p-4 sm:p-6 border-b bg-gradient-to-r from-gray-50 to-white">
                                <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-2">Optional Extras</h3>
                            </div>
                            <div class="p-4 sm:p-6">
                                <div v-if="optionalExtras.length > 0" class="space-y-3 sm:space-y-4">
                                     <div v-for="extra in optionalExtras" :key="extra.optionID || extra.Name" class="relative flex items-center justify-between p-3 sm:p-4 border border-gray-200 rounded-xl hover:bg-gray-50">
                                         <div class="flex items-start space-x-4">
                                             <div>
                                                 <h4 class="font-semibold text-gray-900 text-base sm:text-lg">{{ extra.Name }}</h4>
                                                 <p class="text-primary font-semibold mt-1">{{ formatPrice(extra.Daily_rate, extra.Daily_rate_currency) }}/day</p>
                                             </div>
                                         </div>
                                         <label class="relative inline-flex items-center cursor-pointer p-2"><input type="checkbox" :value="extra" v-model="selectedOptionalExtras" class="sr-only peer"><div class="relative w-12 h-7 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div></label>
                                     </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Step 2: Customer Information -->
                    <div v-show="currentStep === 2" class="space-y-6 sm:space-y-8">
                         <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                            <div class="p-4 sm:p-6 border-b bg-gradient-to-r from-gray-50 to-white">
                                <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-2">Your Details</h3>
                            </div>
                            <div class="p-4 sm:p-6">
                                <form class="space-y-4 sm:space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                                        <div><label class="block text-sm font-medium text-gray-700 mb-2">First Name</label><input type="text" v-model="form.customer.firstname" class="w-full px-4 py-3 border border-gray-300 rounded-lg"></div>
                                        <div><label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label><input type="text" v-model="form.customer.surname" class="w-full px-4 py-3 border border-gray-300 rounded-lg"></div>
                                        <div><label class="block text-sm font-medium text-gray-700 mb-2">Email</label><input type="email" v-model="form.customer.email" class="w-full px-4 py-3 border border-gray-300 rounded-lg"></div>
                                        <div><label class="block text-sm font-medium text-gray-700 mb-2">Phone</label><input type="text" v-model="form.customer.phone" class="w-full px-4 py-3 border border-gray-300 rounded-lg"></div>
                                        <div><label class="block text-sm font-medium text-gray-700 mb-2">Address</label><input type="text" v-model="form.customer.address1" class="w-full px-4 py-3 border border-gray-300 rounded-lg"></div>
                                        <div><label class="block text-sm font-medium text-gray-700 mb-2">Town/City</label><input type="text" v-model="form.customer.town" class="w-full px-4 py-3 border border-gray-300 rounded-lg"></div>
                                        <div><label class="block text-sm font-medium text-gray-700 mb-2">Postcode</label><input type="text" v-model="form.customer.postcode" class="w-full px-4 py-3 border border-gray-300 rounded-lg"></div>
                                        <div><label class="block text-sm font-medium text-gray-700 mb-2">Country</label><input type="text" v-model="form.customer.country" class="w-full px-4 py-3 border border-gray-300 rounded-lg"></div>
                                        <div><label class="block text-sm font-medium text-gray-700 mb-2">Driver License</label><input type="text" v-model="form.customer.driver_licence_number" class="w-full px-4 py-3 border border-gray-300 rounded-lg"></div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Payment -->
                    <div v-show="currentStep === 3">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-6">Payment</h3>
                            <OkMobilityStripeCheckout :booking-data="bookingDataForStripe" @error="handleStripeError" />
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                     <div class="mt-8 flex justify-between">
                        <button v-if="currentStep > 1" @click="prevStep" class="px-6 py-3 border border-gray-300 rounded-xl font-semibold text-gray-700 hover:bg-gray-50">Back</button>
                        <button v-if="currentStep < 3" @click="nextStep" class="ml-auto px-6 py-3 bg-primary text-white rounded-xl font-semibold hover:bg-primary-dark">Continue</button>
                    </div>

                </div>

                <!-- Right Column: Summary -->
                 <div class="xl:col-span-2 lg:col-span-1 hidden lg:block">
                     <div class="sticky top-4 space-y-6">
                         <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                             <div class="p-4 bg-gray-50 border-b">
                                <h3 class="font-bold text-gray-900">Booking Summary</h3>
                             </div>
                             <div class="p-6 space-y-4">
                                 <div class="flex justify-between"><span class="text-gray-600">Vehicle Total</span><span class="font-semibold">{{ formatPrice(form.vehicle_total) }}</span></div>
                                 <div v-for="extra in selectedOptionalExtras" :key="extra.optionID" class="flex justify-between text-sm">
                                     <span class="text-gray-600">{{ extra.Name }}</span><span>{{ formatPrice(extra.Total_for_this_booking || 0) }}</span>
                                 </div>
                                 <div v-if="hasAffiliateDiscount" class="flex justify-between text-green-600 font-semibold border-t pt-2">
                                     <span>Discount</span><span>-{{ formatPrice(affiliateDiscountAmount) }}</span>
                                 </div>
                                 
                                 <!-- Partial Payment Breakdown -->
                                <div class="border-t border-gray-200 pt-4 mt-4 space-y-2">
                                     <div class="flex justify-between items-center text-lg font-bold text-gray-900">
                                        <span>Total</span>
                                        <span>{{ formatPrice(hasAffiliateDiscount ? finalTotalPrice : form.grand_total) }}</span>
                                    </div>

                                    <div v-if="paymentPercentage > 0" class="mt-4 p-4 bg-yellow-50 rounded-lg border border-yellow-100 space-y-3">
                                        <div class="flex justify-between text-green-700 font-semibold">
                                            <span>Pay Now ({{ paymentPercentage }}%)</span>
                                            <span>{{ formatPrice(calculateAmountPaid) }}</span>
                                        </div>
                                        <div class="flex justify-between text-gray-600">
                                            <span>Pay on Arrival</span>
                                            <span>{{ formatPrice(calculatePendingAmount) }}</span>
                                        </div>
                                    </div>
                                    
                                    <div v-else class="mt-4 p-4 bg-gray-50 rounded-lg">
                                        <div class="flex justify-between text-gray-900 font-semibold">
                                            <span>Pay Now</span>
                                            <span>{{ formatPrice(hasAffiliateDiscount ? finalTotalPrice : form.grand_total) }}</span>
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
</template>

<style scoped>
.bg-primary { background-color: #153b4f; }
.bg-primary-dark { background-color: #0f2a38; }
.text-primary { color: #153b4f; }
</style>
