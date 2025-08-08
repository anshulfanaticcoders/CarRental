<script setup>
import { Link, usePage, Head, router } from "@inertiajs/vue3";
import { computed, onMounted, ref, watch, nextTick, onBeforeUnmount } from "vue";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import Footer from "@/Components/Footer.vue";
import { useToast } from 'vue-toastification';
import GreenMotionStripeCheckout from "@/Components/GreenMotionStripeCheckout.vue";

const props = defineProps({
    vehicle: Object,
    location: Object,
    optionalExtras: Array,
    filters: Object,
    locale: String,
    error: String,
    auth: Object,
});

const page = usePage();
const toast = useToast();

// UI State
const currentStep = ref(1);
const isFormValid = ref(false);
const showMobileBookingSummary = ref(false);
const isSubmitting = ref(false);

const currencySymbols = ref({});

onMounted(async () => {
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

const selectedPackage = ref(null);
const selectedOptionalExtras = ref([]);

const form = ref({
    location_id: props.filters.location_id || props.location?.id || 61627,
    start_date: props.filters.start_date || '2032-01-06',
    start_time: props.filters.start_time || '09:00',
    end_date: props.filters.end_date || '2032-01-08',
    end_time: props.filters.end_time || '09:00',
    age: props.filters.age || 35,
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
    currency: props.vehicle?.products?.[0]?.currency || '$',
    grand_total: 0,
    paymentHandlerRef: null,
    quoteid: props.filters.quoteid || props.vehicle?.products?.[0]?.quoteid || 'dummy_quote_id',
    payment_type: 'POA',
    remarks: null,
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

const calculateTotals = () => {
    let packagePrice = 0;
    if (selectedPackage.value) {
        packagePrice = parseFloat(selectedPackage.value.total);
        form.value.rentalCode = selectedPackage.value.type;
    }

    let extrasTotal = 0;
    selectedOptionalExtras.value.forEach(extra => {
        extrasTotal += parseFloat(extra.Total_for_this_booking || 0);
    });

    form.value.vehicle_total = packagePrice;
    form.value.grand_total = packagePrice + extrasTotal;

    form.value.extras = selectedOptionalExtras.value.map(extra => ({
        id: extra.optionID,
        option_qty: 1,
        option_total: parseFloat(extra.Total_for_this_booking || extra.Daily_rate * rentalDuration.value || 0),
        pre_pay: extra.Prepay_available === 'yes' ? 'Yes' : 'No',
    }));
};

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

const isLoading = ref(false);
const dialogErrorMessage = ref('');
const showErrorDialog = ref(false);

onMounted(() => {
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

    console.log("Quote ID in GreenMotionBooking.vue props.filters:", props.filters.quoteid);
    console.log("Quote ID in GreenMotionBooking.vue form.value:", form.value.quoteid);
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
        grand_total: form.value.grand_total,
        paymentHandlerRef: form.value.paymentHandlerRef,
        quoteid: form.value.quoteid,
        payment_type: form.value.payment_type,
        remarks: form.value.remarks,
        extras: form.value.extras,
        // Fields below are not directly validated by backend but might be useful for logging/storage
        pickup_location: props.location?.name || '',
        return_location: props.location?.name || '',
        total_days: rentalDuration.value,
        extra_charges: form.value.grand_total - form.value.vehicle_total,
        tax_amount: 0,
        discount_amount: 0,
        pending_amount: 0,
        amount_paid: form.value.grand_total,
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
    <section class="bg-primary py-16 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-primary to-primary-dark opacity-90"></div>
        <div class="container relative z-10">
            <div class="max-w-4xl mx-auto text-center text-white">
                <div class="inline-flex items-center px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full mb-6">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm font-medium">Secure Booking Process</span>
                </div>
                <h1 class="text-4xl md:text-5xl font-bold mb-4">Complete Your Reservation</h1>
                <p class="text-xl opacity-90">{{ vehicle?.name }} - {{ location?.name }}</p>
            </div>
        </div>
    </section>

    <div class="container mx-auto py-12">
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

        <div v-else-if="vehicle" class="max-w-7xl mx-auto py-12">
            <!-- Progress Steps -->
            <div class="mb-8">
                <div class="flex items-center justify-center space-x-8 mb-8">
                    <div v-for="step in 3" :key="step" 
                         class="flex items-center cursor-pointer transition-all duration-300"
                         @click="goToStep(step)">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center font-semibold transition-all duration-300"
                                 :class="step <= currentStep 
                                   ? 'bg-primary text-white shadow-lg' 
                                   : 'bg-gray-200 text-gray-600'">
                                <svg v-if="step < currentStep" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span v-else>{{ step }}</span>
                            </div>
                            <span class="ml-3 font-medium text-gray-700"
                                  :class="step <= currentStep ? 'text-primary' : 'text-gray-500'">
                                {{ step === 1 ? 'Select Package' : step === 2 ? 'Your Details' : 'Payment' }}
                            </span>
                        </div>
                        <div v-if="step < 3" class="w-20 h-0.5 ml-8"
                             :class="step < currentStep ? 'bg-primary' : 'bg-gray-200'"></div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-3">
                    <!-- Step 1: Package Selection -->
                    <div v-show="currentStep === 1" class="space-y-8">
                        <!-- Vehicle Summary Card -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="p-6 bg-gradient-to-r from-gray-50 to-white border-b">
                                <h2 class="text-2xl font-bold text-gray-900 mb-2">Your Selected Vehicle</h2>
                                <p class="text-gray-600">Review your vehicle and rental details</p>
                            </div>
                            <div class="p-6">
                                <div class="flex flex-col md:flex-row gap-6">
                                    <div class="md:w-1/3">
                                        <img :src="vehicle.image || '/default-car-image.jpg'" 
                                             :alt="vehicle.name"
                                             class="w-full h-48 md:h-32 object-cover rounded-xl shadow-sm" />
                                    </div>
                                    <div class="md:w-2/3 space-y-3">
                                        <div>
                                            <h3 class="text-xl font-bold text-gray-900">{{ vehicle.name }}</h3>
                                            <p class="text-gray-600">{{ vehicle.groupName }}</p>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="bg-gray-50 rounded-lg p-3">
                                                <p class="text-sm font-medium text-gray-500">Rental Duration</p>
                                                <p class="text-lg font-semibold text-gray-900">{{ rentalDuration }} days</p>
                                            </div>
                                            <div class="bg-gray-50 rounded-lg p-3">
                                                <p class="text-sm font-medium text-gray-500">Location</p>
                                                <p class="text-lg font-semibold text-gray-900">{{ location?.name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Package Selection -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                            <div class="p-6 border-b bg-gradient-to-r from-gray-50 to-white">
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Choose Your Rental Package</h3>
                                <p class="text-gray-600">Select the package that best suits your needs</p>
                            </div>
                            <div class="p-6">
                                <div v-if="formErrors.package" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                                    <p class="text-red-700 text-sm">{{ formErrors.package }}</p>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div v-for="pkg in availablePackages" :key="pkg.type"
                                         class="relative border-2 rounded-xl p-6 cursor-pointer transition-all duration-300 hover:shadow-lg"
                                         :class="selectedPackage?.type === pkg.type 
                                           ? 'border-primary bg-primary/5 shadow-md' 
                                           : 'border-gray-200 hover:border-gray-300'"
                                         @click="selectedPackage = pkg">
                                        <div class="flex items-start justify-between mb-4">
                                            <h4 class="text-lg font-bold text-gray-900">{{ pkg.type }}</h4>
                                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center"
                                                 :class="selectedPackage?.type === pkg.type 
                                                   ? 'border-primary bg-primary' 
                                                   : 'border-gray-300'">
                                                <div v-if="selectedPackage?.type === pkg.type" 
                                                     class="w-2 h-2 bg-white rounded-full"></div>
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Total Price</span>
                                                <span class="font-semibold">{{ getCurrencySymbol(pkg.currency) }}{{ pkg.total }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Deposit</span>
                                                <span class="font-semibold">{{ getCurrencySymbol(pkg.currency) }}{{ pkg.deposit }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Excess</span>
                                                <span class="font-semibold">{{ getCurrencySymbol(pkg.currency) }}{{ pkg.excess }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Fuel Policy</span>
                                                <span class="font-semibold">{{ pkg.fuelpolicy }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Optional Extras -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                            <div class="p-6 border-b bg-gradient-to-r from-gray-50 to-white">
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Optional Extras</h3>
                                <p class="text-gray-600">Enhance your rental experience</p>
                            </div>
                            <div class="p-6">
                                <div v-if="optionalExtras.length > 0" class="space-y-4">
                                    <div v-for="extra in optionalExtras" :key="extra.optionID || extra.Name"
                                         class="flex items-center justify-between p-4 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                                        <div class="flex items-start space-x-4">
                                            <div class="flex-shrink-0 w-12 h-12 bg-secondary/20 rounded-lg flex items-center justify-center">
                                                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <h4 class="font-semibold text-gray-900 text-[1.5rem]">{{ extra.Name }}</h4>
                                                <p class="text-gray-600 text-sm">{{ extra.Description }}</p>
                                                <p class="text-primary font-semibold mt-1">
                                                    {{ getCurrencySymbol(extra.Daily_rate_currency) }}{{ extra.Daily_rate }}/day
                                                </p>
                                            </div>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" :value="extra" v-model="selectedOptionalExtras" class="sr-only peer">
                                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
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
                    <div v-show="currentStep === 2" class="space-y-8">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                            <div class="p-6 border-b bg-gradient-to-r from-gray-50 to-white">
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Your Details</h3>
                                <p class="text-gray-600">Please provide your information to complete the booking</p>
                            </div>
                            <div class="p-6">
                                <form class="space-y-6">
                                    <!-- Personal Information -->
                                    <div>
                                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Personal Information</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label for="firstname" class="block text-sm font-medium text-gray-700 mb-2">
                                                    First Name <span class="text-red-500">*</span>
                                                </label>
                                                <input type="text" id="firstname" v-model="form.customer.firstname" 
                                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                                                       :class="{ 'border-red-300 bg-red-50': formErrors.firstname }"
                                                       placeholder="Enter your first name" />
                                                <p v-if="formErrors.firstname" class="mt-1 text-sm text-red-600">{{ formErrors.firstname }}</p>
                                            </div>
                                            <div>
                                                <label for="surname" class="block text-sm font-medium text-gray-700 mb-2">
                                                    Last Name <span class="text-red-500">*</span>
                                                </label>
                                                <input type="text" id="surname" v-model="form.customer.surname" 
                                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                                                       :class="{ 'border-red-300 bg-red-50': formErrors.surname }"
                                                       placeholder="Enter your last name" />
                                                <p v-if="formErrors.surname" class="mt-1 text-sm text-red-600">{{ formErrors.surname }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Contact Information -->
                                    <div>
                                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                                    Email Address <span class="text-red-500">*</span>
                                                </label>
                                                <input type="email" id="email" v-model="form.customer.email" 
                                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                                                       :class="{ 'border-red-300 bg-red-50': formErrors.email }"
                                                       placeholder="your.email@example.com" />
                                                <p v-if="formErrors.email" class="mt-1 text-sm text-red-600">{{ formErrors.email }}</p>
                                            </div>
                                            <div>
                                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                                    Phone Number <span class="text-red-500">*</span>
                                                </label>
                                                <input type="tel" id="phone" v-model="form.customer.phone" 
                                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                                                       :class="{ 'border-red-300 bg-red-50': formErrors.phone }"
                                                       placeholder="+1 (555) 123-4567" />
                                                <p v-if="formErrors.phone" class="mt-1 text-sm text-red-600">{{ formErrors.phone }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Address Information -->
                                    <div>
                                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Address Information</h4>
                                        <div class="space-y-6">
                                            <div>
                                                <label for="address1" class="block text-sm font-medium text-gray-700 mb-2">
                                                    Address Line 1 <span class="text-red-500">*</span>
                                                </label>
                                                <input type="text" id="address1" v-model="form.customer.address1" 
                                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                                                       :class="{ 'border-red-300 bg-red-50': formErrors.address1 }"
                                                       placeholder="Street address, P.O. box, company name" />
                                                <p v-if="formErrors.address1" class="mt-1 text-sm text-red-600">{{ formErrors.address1 }}</p>
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                                <div>
                                                    <label for="town" class="block text-sm font-medium text-gray-700 mb-2">
                                                        Town/City <span class="text-red-500">*</span>
                                                    </label>
                                                    <input type="text" id="town" v-model="form.customer.town" 
                                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                                                           :class="{ 'border-red-300 bg-red-50': formErrors.town }"
                                                           placeholder="City" />
                                                    <p v-if="formErrors.town" class="mt-1 text-sm text-red-600">{{ formErrors.town }}</p>
                                                </div>
                                                <div>
                                                    <label for="postcode" class="block text-sm font-medium text-gray-700 mb-2">
                                                        Postcode <span class="text-red-500">*</span>
                                                    </label>
                                                    <input type="text" id="postcode" v-model="form.customer.postcode" 
                                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                                                           :class="{ 'border-red-300 bg-red-50': formErrors.postcode }"
                                                           placeholder="12345" />
                                                    <p v-if="formErrors.postcode" class="mt-1 text-sm text-red-600">{{ formErrors.postcode }}</p>
                                                </div>
                                                <div>
                                                    <label for="country" class="block text-sm font-medium text-gray-700 mb-2">
                                                        Country <span class="text-red-500">*</span>
                                                    </label>
                                                    <input type="text" id="country" v-model="form.customer.country" 
                                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                                                           :class="{ 'border-red-300 bg-red-50': formErrors.country }"
                                                           placeholder="Country" />
                                                    <p v-if="formErrors.country" class="mt-1 text-sm text-red-600">{{ formErrors.country }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Driver Information -->
                                    <div>
                                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Driver Information</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label for="driver_licence_number" class="block text-sm font-medium text-gray-700 mb-2">
                                                    Driver's Licence Number <span class="text-red-500">*</span>
                                                </label>
                                                <input type="text" id="driver_licence_number" v-model="form.customer.driver_licence_number" 
                                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                                                       :class="{ 'border-red-300 bg-red-50': formErrors.driver_licence_number }"
                                                       placeholder="Licence number" />
                                                <p v-if="formErrors.driver_licence_number" class="mt-1 text-sm text-red-600">{{ formErrors.driver_licence_number }}</p>
                                            </div>
                                            <div>
                                                <label for="age" class="block text-sm font-medium text-gray-700 mb-2">
                                                    Age <span class="text-red-500">*</span>
                                                </label>
                                                <input type="number" id="age" v-model="form.age" 
                                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                                                       placeholder="25" min="18" max="99" />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Optional Information -->
                                    <div>
                                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Optional Information</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label for="flight_number" class="block text-sm font-medium text-gray-700 mb-2">
                                                    Flight Number
                                                </label>
                                                <input type="text" id="flight_number" v-model="form.customer.flight_number" 
                                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                                                       placeholder="BA123" />
                                            </div>
                                            <div>
                                                <label for="comments" class="block text-sm font-medium text-gray-700 mb-2">
                                                    Special Requests
                                                </label>
                                                <textarea id="comments" v-model="form.customer.comments" rows="3"
                                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                                                          placeholder="Any special requests or comments..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Payment -->
                    <div v-show="currentStep === 3" class="space-y-8">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                            <div class="p-6 border-b bg-gradient-to-r from-gray-50 to-white">
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Complete Your Payment</h3>
                                <p class="text-gray-600">Review your booking and complete the payment process</p>
                            </div>
                            <div class="p-6">
                                <GreenMotionStripeCheckout :booking-data="bookingDataForStripe" @error="handleStripeError" />
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex justify-between pt-8">
                        <button v-if="currentStep > 1" 
                                @click="prevStep"
                                class="inline-flex items-center px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Previous Step
                        </button>
                        <div v-else></div>

                        <button v-if="currentStep < 3" 
                                @click="nextStep"
                                class="inline-flex items-center px-6 py-3 bg-primary text-white font-medium rounded-lg hover:bg-primary-dark transition-colors">
                            Next Step
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Right Column: Booking Summary -->
                <div class="lg:col-span-2">
                    <!-- Mobile Summary Toggle -->
                    <button @click="showMobileBookingSummary = !showMobileBookingSummary"
                            class="lg:hidden w-full mb-4 px-4 py-3 bg-primary text-white font-medium rounded-lg">
                        <span v-if="!showMobileBookingSummary">Show Booking Summary</span>
                        <span v-else>Hide Booking Summary</span>
                    </button>

                    <div class="sticky top-4" :class="{ 'hidden lg:block': !showMobileBookingSummary }">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="p-6 bg-gradient-to-r from-primary to-primary-dark text-white">
                                <h3 class="text-xl font-bold mb-2">Booking Summary</h3>
                                <p class="opacity-90">Review your reservation details</p>
                            </div>
                            
                            <div class="p-6 space-y-6">
                                <!-- Vehicle Info -->
                                <div class="pb-6 border-b border-gray-100">
                                    <div class="flex gap-3 mb-4">
                                        <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center px-2">
                                            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-900 text-[1.5rem]">{{ vehicle?.name }}</h4>
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
                                            <p class="text-sm text-gray-500">{{ form.end_time }} at {{ location?.name }}</p>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">Duration</span>
                                        <span class="font-medium text-gray-900">{{ rentalDuration }} days</span>
                                    </div>
                                </div>

                                <!-- Package Details -->
                                <div class="pt-4 border-t border-gray-100">
                                    <h4 class="font-semibold text-gray-900 mb-3 text-[1.5rem]">Package Details</h4>
                                    <div v-if="selectedPackage" class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">{{ selectedPackage.type }}</span>
                                            <span class="font-medium">{{ getCurrencySymbol(selectedPackage.currency) }}{{ selectedPackage.total }}</span>
                                        </div>
                                    </div>
                                    <p v-else class="text-gray-500 italic">No package selected</p>
                                </div>

                                <!-- Extras -->
                                <div v-if="selectedOptionalExtras.length > 0" class="pt-4 border-t border-gray-100">
                                    <h4 class="font-semibold text-gray-900 mb-3 text-[1.5rem]">Selected Extras</h4>
                                    <div class="space-y-2">
                                        <div v-for="extra in selectedOptionalExtras" :key="extra.optionID" 
                                             class="flex justify-between text-sm">
                                            <span class="text-gray-600">{{ extra.Name }}</span>
                                            <span class="font-medium">{{ getCurrencySymbol(extra.Daily_rate_currency) }}{{ extra.Daily_rate }}/day</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Total -->
                                <div class="pt-4 border-t border-gray-200">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xl font-bold text-gray-900">Total</span>
                                        <span class="text-2xl font-bold text-primary">{{ getCurrencySymbol(form.currency) }}{{ form.grand_total.toFixed(2) }}</span>
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
    <div v-if="showErrorDialog" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl p-6 max-w-md w-full">
            <div class="text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Payment Error</h3>
                <p class="text-gray-600 mb-6">{{ dialogErrorMessage }}</p>
                <button @click="showErrorDialog = false" 
                        class="w-full px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

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
    max-width: 1500px;
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
</style>
