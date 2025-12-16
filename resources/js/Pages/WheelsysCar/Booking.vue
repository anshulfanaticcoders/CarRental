<script setup>
import { Link, Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onBeforeUnmount, watch } from 'vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import Footer from '@/Components/Footer.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Checkbox } from '@/Components/ui/checkbox';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Separator } from '@/Components/ui/separator';
import { Badge } from '@/Components/ui/badge';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import { useCurrency } from '@/composables/useCurrency';
import WheelsysStripeCheckout from '@/Components/WheelsysStripeCheckout.vue';
import axios from 'axios';

const paymentPercentage = ref(0.00);

const props = defineProps({
    vehicle: Object,
    searchParams: Object,
    availableExtras: Array,
    pickupStation: Object,
    returnStation: Object,
    user: Object,
    locale: String,
});

const page = usePage();
const currentStep = ref(1);
const formErrors = ref({});
const showSummaryModal = ref(false);
const isMobile = ref(false);

// Currency conversion setup
const currencySymbols = ref({});
const exchangeRates = ref(null);
const { selectedCurrency } = useCurrency();

onMounted(async () => {
    // Initialize mobile detection
    checkMobile();
    window.addEventListener('resize', checkMobile);

    try {
        const ratesResponse = await fetch(`${import.meta.env.VITE_EXCHANGERATE_API_BASE_URL}/v6/${import.meta.env.VITE_EXCHANGERATE_API_KEY}/latest/USD`);
        const ratesData = await ratesResponse.json();
        if (ratesData.result === 'success') {
            exchangeRates.value = ratesData.conversion_rates;
        }
        const symbolsResponse = await fetch('/currency.json');
        const symbolsData = await symbolsResponse.json();
        currencySymbols.value = symbolsData.reduce((acc, curr) => {
            acc[curr.code] = curr.symbol;
            return acc;
        }, {});
    } catch (error) {
        console.error('Failed to load currency data:', error);
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

const getCurrencySymbol = (code) => currencySymbols.value[code] || '$';

const convertCurrency = (price, fromCurrency) => {
    const numericPrice = parseFloat(price);
    if (isNaN(numericPrice)) return 0;
    if (!exchangeRates.value || !fromCurrency || !selectedCurrency.value) return numericPrice;
    const rateFrom = exchangeRates.value[fromCurrency];
    const rateTo = exchangeRates.value[selectedCurrency.value];
    return rateFrom && rateTo ? (numericPrice / rateFrom) * rateTo : numericPrice;
};

const formatPrice = (price, currency = 'USD') => {
    if (!price || price === 0) return 'Free';
    const convertedPrice = convertCurrency(price, currency);
    const currencySymbol = getCurrencySymbol(selectedCurrency.value);
    return `${currencySymbol}${convertedPrice.toFixed(2)}`;
};

const selectedExtras = ref([]);

const form = useForm({
    vehicle_group_code: props.vehicle?.group_code || '',
    vehicle_group_name: `${props.vehicle?.brand || 'Wheelsys'} ${props.vehicle?.model || 'Vehicle'}`,
    pickup_date: props.searchParams?.date_from || '',
    pickup_time: props.searchParams?.time_from || '12:00',
    return_date: props.searchParams?.date_to || '',
    return_time: props.searchParams?.time_to || '12:00',
    pickup_station_code: props.searchParams?.pickup_station || 'MAIN',
    pickup_station_name: props.pickupStation?.Name || 'Main Location',
    return_station_code: props.searchParams?.return_station || 'MAIN',
    return_station_name: props.returnStation?.Name || 'Main Location',
    customer_first_name: props.user?.first_name || '',
    customer_last_name: props.user?.last_name || '',
    customer_email: props.user?.email || '',
    customer_phone: props.user?.phone || '',
    customer_age: 25,
    customer_address: '',
    customer_driver_licence: '',
    selected_extras: [],
    customer_notes: '',
    affiliate_code: page.props.affiliate_data?.code || null,
    amount_paid: 0,
    pending_amount: 0,
});

const rentalDays = computed(() => {
    const parseDate = (dateStr) => {
        if (!dateStr) return new Date();
        const parts = dateStr.split('/');
        return parts.length === 3 ? new Date(parts[2], parts[1] - 1, parts[0]) : new Date(dateStr);
    };
    const pickup = parseDate(props.searchParams?.date_from);
    const returnDate = parseDate(props.searchParams?.date_to);
    const days = Math.ceil((returnDate - pickup) / (1000 * 60 * 60 * 24));
    return Math.max(1, days);
});

const baseTotal = computed(() => (props.vehicle?.price_per_day || 0) * rentalDays.value);
const extrasTotal = computed(() => selectedExtras.value.reduce((total, extra) => total + (extra.rate * rentalDays.value), 0));
const taxesTotal = computed(() => (baseTotal.value + extrasTotal.value) * 0.065);
const grandTotal = computed(() => baseTotal.value + extrasTotal.value + taxesTotal.value);

const calculateAmountPaid = computed(() => {
    const total = grandTotal.value;
    const effectivePercentage = paymentPercentage.value === 0 ? 100 : paymentPercentage.value;
    return Number((total * (effectivePercentage / 100)).toFixed(2));
});

const calculatePendingAmount = computed(() => {
    const total = grandTotal.value;
    const effectivePercentage = paymentPercentage.value === 0 ? 100 : paymentPercentage.value;
    return Number((total * (1 - (effectivePercentage / 100))).toFixed(2));
});

// Update form with partial payment data
watch([calculateAmountPaid, calculatePendingAmount], () => {
    form.amount_paid = calculateAmountPaid.value;
    form.pending_amount = calculatePendingAmount.value;
}, { immediate: true });

const toggleExtra = (extra) => {
    const index = selectedExtras.value.findIndex(e => e.code === extra.code);
    if (index > -1) {
        selectedExtras.value.splice(index, 1);
    } else {
        selectedExtras.value.push({ ...extra, quantity: 1 });
    }
    form.selected_extras = selectedExtras.value;
};

const validateStep = (step) => {
    const errors = {};
    if (step === 2) {
        if (!form.customer_first_name.trim()) errors.firstname = 'First name is required';
        if (!form.customer_last_name.trim()) errors.surname = 'Last name is required';
        if (!form.customer_email.trim()) errors.email = 'Email is required';
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.customer_email)) errors.email = 'Please enter a valid email address';
        if (!form.customer_phone.trim()) errors.phone = 'Phone number is required';
    }
    formErrors.value = errors;
    return Object.keys(errors).length === 0;
};

const nextStep = () => {
    if (validateStep(currentStep.value)) {
        if (currentStep.value < 3) currentStep.value++;
    }
};
const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--;
};
const goToStep = (step) => {
    if (step < currentStep.value || validateStep(currentStep.value)) {
        currentStep.value = step;
    }
};

// Mobile detection composable
const checkMobile = () => {
    isMobile.value = window.innerWidth < 1024; // lg breakpoint
};

const formatDate = (dateString) => {
    if (!dateString) return 'Invalid date';
    const parts = dateString.split('/');
    if (parts.length !== 3) return 'Invalid date';
    const date = new Date(parts[2], parts[1] - 1, parts[0]);
    return date.toLocaleDateString('en-US', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
};
</script>

<template>
    <Head>
        <title>Complete Booking - {{ vehicle?.brand }} {{ vehicle?.model }}</title>
    </Head>

    <AuthenticatedHeaderLayout />

    <section class="bg-primary py-12 sm:py-16 relative overflow-hidden">
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
                <p class="text-base sm:text-lg md:text-xl opacity-90">{{ vehicle?.brand }} {{ vehicle?.model }} - {{ pickupStation?.Name }}</p>
            </div>
        </div>
    </section>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-10 md:py-12">
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
                            <span>{{ step }}</span>
                        </div>
                        <div class="ml-4 flex-1">
                            <div class="font-medium text-sm"
                                 :class="step <= currentStep ? 'text-primary font-semibold' : 'text-gray-500'">
                                {{ step === 1 ? 'Vehicle & Extras' : step === 2 ? 'Your Details' : 'Payment' }}
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                {{ step === 1 ? 'Review vehicle and extras' : step === 2 ? 'Enter your information' : 'Complete payment' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Desktop: Horizontal steps -->
            <div class="hidden sm:flex items-center justify-center space-x-4 md:space-x-8 mb-6 sm:mb-8">
                <div v-for="step in 3" :key="step" @click="goToStep(step)" class="flex items-center cursor-pointer transition-all duration-300">
                    <div class="flex items-center">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center font-semibold transition-all duration-300"
                             :class="step <= currentStep ? 'bg-primary text-white shadow-lg' : 'bg-gray-200 text-gray-600'">
                            <span class="text-sm sm:text-base">{{ step }}</span>
                        </div>
                        <span class="ml-2 sm:ml-3 font-medium text-sm sm:text-base"
                              :class="step <= currentStep ? 'text-primary' : 'text-gray-500'">
                            {{ step === 1 ? 'Vehicle & Extras' : step === 2 ? 'Your Details' : 'Payment' }}
                        </span>
                    </div>
                    <div v-if="step < 3" class="w-16 sm:w-20 h-0.5 ml-2 sm:ml-8" :class="step < currentStep ? 'bg-primary' : 'bg-gray-200'"></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-5 gap-6 sm:gap-8">
            <div class="xl:col-span-3 space-y-6 sm:space-y-8" :class="{ 'mobile-content-padding': isMobile }">
                <!-- Step 1: Vehicle & Extras -->
                <div v-show="currentStep === 1" class="space-y-6 sm:space-y-8">
                    <Card class="shadow-lg hover:shadow-xl transition-shadow duration-300">
                        <CardHeader class="pb-4">
                            <CardTitle class="text-lg sm:text-xl font-bold text-gray-900">Your Selected Vehicle</CardTitle>
                        </CardHeader>
                        <CardContent class="p-4 sm:p-6">
                            <div class="flex flex-col sm:flex-row gap-4 sm:gap-6">
                                <img :src="vehicle?.image" :alt="`${vehicle?.brand} ${vehicle?.model}`"
                                     class="w-full sm:w-32 h-48 sm:h-24 object-cover rounded-lg" />
                                <div class="flex-1 text-center sm:text-left">
                                    <h3 class="text-lg sm:text-xl font-semibold text-gray-900 mb-2">{{ vehicle?.brand }} {{ vehicle?.model }}</h3>
                                    <p class="text-sm sm:text-base text-gray-600 mb-3">{{ vehicle?.category }}</p>
                                    <div class="flex flex-wrap justify-center sm:justify-start gap-3 sm:gap-4 text-xs sm:text-sm text-gray-500">
                                        <span class="px-2 py-1 bg-gray-100 rounded-full">{{ vehicle?.seating_capacity }} Seats</span>
                                        <span class="px-2 py-1 bg-gray-100 rounded-full">{{ vehicle?.doors }} Doors</span>
                                        <span class="px-2 py-1 bg-gray-100 rounded-full">{{ vehicle?.transmission }}</span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card v-if="availableExtras && availableExtras.length > 0" class="shadow-lg hover:shadow-xl transition-shadow duration-300">
                        <CardHeader class="pb-4">
                            <CardTitle class="text-lg sm:text-xl font-bold text-gray-900">Additional Extras</CardTitle>
                        </CardHeader>
                        <CardContent class="p-4 sm:p-6">
                            <div class="space-y-3 sm:space-y-4">
                                <div v-for="extra in availableExtras" :key="extra.code"
                                     class="flex flex-col sm:flex-row sm:items-center justify-between p-4 border-2 rounded-xl hover:bg-gray-50 hover:border-primary/20 transition-all duration-300 cursor-pointer"
                                     @click="toggleExtra(extra)">
                                    <div class="flex items-start sm:items-center space-x-3 mb-3 sm:mb-0">
                                        <Checkbox :id="extra.code" :checked="selectedExtras.some(e => e.code === extra.code)"
                                                  @update:checked="toggleExtra(extra)"
                                                  @click.stop
                                                  class="mt-1 sm:mt-0" />
                                        <div class="flex-1">
                                            <Label :for="extra.code" class="font-medium cursor-pointer text-sm sm:text-base text-gray-900 hover:text-primary transition-colors">
                                                {{ extra.name }}
                                            </Label>
                                            <p class="text-xs sm:text-sm text-gray-500 mt-1">{{ extra.description || 'Additional charge per day' }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right sm:text-left">
                                        <p class="font-semibold text-primary text-sm sm:text-base">{{ formatPrice(extra.rate, vehicle?.currency || 'USD') }}</p>
                                        <p class="text-xs text-gray-500">{{ extra.charge_type || 'per day' }}</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Step 2: Customer Information -->
                <div v-show="currentStep === 2" class="space-y-6 sm:space-y-8">
                    <Card class="shadow-lg hover:shadow-xl transition-shadow duration-300">
                        <CardHeader class="pb-4">
                            <CardTitle class="text-lg sm:text-xl font-bold text-gray-900">Your Details</CardTitle>
                        </CardHeader>
                        <CardContent class="p-4 sm:p-6 space-y-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                                <div>
                                    <Label for="first_name" class="text-sm sm:text-base font-medium text-gray-700 mb-2 block">First Name *</Label>
                                    <Input id="first_name" v-model="form.customer_first_name" type="text" required
                                           :class="{ 'border-red-500': formErrors.firstname }"
                                           class="w-full px-3 sm:px-4 py-3 sm:py-4 text-base sm:text-lg border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors min-h-[44px]" />
                                    <p v-if="formErrors.firstname" class="text-red-500 text-xs sm:text-sm mt-2">{{ formErrors.firstname }}</p>
                                </div>
                                <div>
                                    <Label for="last_name" class="text-sm sm:text-base font-medium text-gray-700 mb-2 block">Last Name *</Label>
                                    <Input id="last_name" v-model="form.customer_last_name" type="text" required
                                           :class="{ 'border-red-500': formErrors.surname }"
                                           class="w-full px-3 sm:px-4 py-3 sm:py-4 text-base sm:text-lg border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors min-h-[44px]" />
                                    <p v-if="formErrors.surname" class="text-red-500 text-xs sm:text-sm mt-2">{{ formErrors.surname }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                                <div>
                                    <Label for="email" class="text-sm sm:text-base font-medium text-gray-700 mb-2 block">Email Address *</Label>
                                    <Input id="email" v-model="form.customer_email" type="email" required
                                           :class="{ 'border-red-500': formErrors.email }"
                                           class="w-full px-3 sm:px-4 py-3 sm:py-4 text-base sm:text-lg border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors min-h-[44px]" />
                                    <p v-if="formErrors.email" class="text-red-500 text-xs sm:text-sm mt-2">{{ formErrors.email }}</p>
                                </div>
                                <div>
                                    <Label for="phone" class="text-sm sm:text-base font-medium text-gray-700 mb-2 block">Phone Number *</Label>
                                    <Input id="phone" v-model="form.customer_phone" type="tel" required
                                           :class="{ 'border-red-500': formErrors.phone }"
                                           class="w-full px-3 sm:px-4 py-3 sm:py-4 text-base sm:text-lg border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors min-h-[44px]" />
                                    <p v-if="formErrors.phone" class="text-red-500 text-xs sm:text-sm mt-2">{{ formErrors.phone }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Step 3: Payment -->
                <div v-show="currentStep === 3" class="space-y-6 sm:space-y-8">
                    <Card class="shadow-lg hover:shadow-xl transition-shadow duration-300">
                        <CardHeader class="pb-4">
                            <CardTitle class="text-lg sm:text-xl font-bold text-gray-900">Complete Your Payment</CardTitle>
                        </CardHeader>
                        <CardContent class="p-4 sm:p-6">
                            <WheelsysStripeCheckout :booking-data="form" />
                        </CardContent>
                    </Card>
                </div>

                <!-- Navigation Buttons - Hidden on mobile, shown on desktop -->
                <div class="hidden lg:flex flex-col lg:flex-row justify-between gap-4 pt-6 sm:pt-8">
                    <Button v-if="currentStep > 1" @click="prevStep" variant="outline"
                            class="w-full lg:w-auto min-h-[44px] px-6 py-3 text-base sm:text-lg font-medium border-2 rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Previous Step
                    </Button>
                    <div v-else class="hidden lg:block"></div>
                    <Button v-if="currentStep < 3" @click="nextStep"
                            class="w-full lg:w-auto min-h-[44px] px-6 py-3 text-base sm:text-lg font-medium bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors shadow-lg">
                        Next Step
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </Button>
                </div>
            </div>

            <!-- Right Column: Booking Summary -->
            <div class="xl:col-span-2">
                <!-- Mobile: Hidden booking summary (moved to fixed bottom nav) -->
                <!-- <div class="lg:hidden w-full mb-4 px-4 py-3 sm:py-4 bg-primary text-white font-medium rounded-lg flex items-center justify-between text-base sm:text-lg shadow-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        Booking Summary
                    </div>
                    <div class="font-bold">{{ formatPrice(grandTotal) }}</div>
                </div> -->

                <div class="sticky top-4">
                    <Card class="shadow-xl hover:shadow-2xl transition-all duration-300 hidden lg:block">
                        <CardHeader class="bg-gradient-to-r from-primary to-primary-dark text-white pb-4">
                            <CardTitle class="text-lg sm:text-xl font-bold">Booking Summary</CardTitle>
                        </CardHeader>
                        <CardContent class="p-4 sm:p-6 space-y-4 sm:space-y-6">
                            <div class="space-y-3 sm:space-y-4">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-0">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                        <span class="text-sm sm:text-base font-medium text-gray-700">Pickup</span>
                                    </div>
                                    <div class="text-right sm:text-left">
                                        <p class="text-sm sm:text-base font-medium text-gray-900">{{ formatDate(searchParams.date_from) }}</p>
                                        <p class="text-xs sm:text-sm text-gray-500">{{ searchParams.time_from }} at {{ pickupStation.Name }}</p>
                                    </div>
                                </div>
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-0">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                        <span class="text-sm sm:text-base font-medium text-gray-700">Return</span>
                                    </div>
                                    <div class="text-right sm:text-left">
                                        <p class="text-sm sm:text-base font-medium text-gray-900">{{ formatDate(searchParams.date_to) }}</p>
                                        <p class="text-xs sm:text-sm text-gray-500">{{ searchParams.time_to }} at {{ returnStation.Name }}</p>
                                    </div>
                                </div>
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-0">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                        <span class="text-sm sm:text-base font-medium text-gray-700">Duration</span>
                                    </div>
                                    <span class="text-sm sm:text-base font-medium text-gray-900">{{ rentalDays }} days</span>
                                </div>
                            </div>
                            <Separator />
                            <div class="space-y-3">
                                <div class="flex flex-col sm:flex-row sm:justify-between gap-1 sm:gap-0">
                                    <span class="text-sm sm:text-base text-gray-600">Vehicle Rental</span>
                                    <span class="text-sm sm:text-base font-medium text-gray-900">{{ formatPrice(baseTotal, vehicle.currency) }}</span>
                                </div>
                                <div v-if="extrasTotal > 0" class="flex flex-col sm:flex-row sm:justify-between gap-1 sm:gap-0">
                                    <span class="text-sm sm:text-base text-gray-600">Extras</span>
                                    <span class="text-sm sm:text-base font-medium text-gray-900">{{ formatPrice(extrasTotal, vehicle.currency) }}</span>
                                </div>
                                <div class="flex flex-col sm:flex-row sm:justify-between gap-1 sm:gap-0">
                                    <span class="text-sm sm:text-base text-gray-600">Taxes & Fees</span>
                                    <span class="text-sm sm:text-base font-medium text-gray-900">{{ formatPrice(taxesTotal, vehicle.currency) }}</span>
                                </div>
                            </div>
                            <Separator />
                            <div class="flex flex-col sm:flex-row sm:justify-between gap-2 sm:gap-0 p-4 bg-primary/5 rounded-lg">
                                <span class="text-lg sm:text-xl font-bold text-gray-900">Total Amount</span>
                                <span class="text-xl sm:text-2xl font-bold text-primary">{{ formatPrice(grandTotal, vehicle.currency) }}</span>
                            </div>
                            <div v-if="paymentPercentage > 0" class="pt-2 mt-2 border-t border-dashed">
                                <div class="flex justify-between font-medium text-green-600">
                                    <span>Pay {{ paymentPercentage }}% now</span>
                                    <span>{{ formatPrice(calculateAmountPaid, vehicle.currency) }}</span>
                                </div>
                                <div class="flex justify-between font-medium text-primary">
                                    <span>Rest pay on arrival</span>
                                    <span>{{ formatPrice(calculatePendingAmount, vehicle.currency) }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
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
                            <span class="text-lg font-bold text-primary">{{ formatPrice(grandTotal) }}</span>
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
                    <Button v-if="currentStep > 1" @click="prevStep" variant="outline"
                            class="px-3 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors min-h-[44px] flex items-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </Button>
                    <Button v-if="currentStep < 3" @click="nextStep"
                            class="px-3 py-2.5 bg-primary text-white font-medium rounded-lg hover:bg-primary/90 transition-colors min-h-[44px] flex items-center">
                        <span class="text-sm">{{ currentStep === 2 ? 'Pay Now' : 'Next' }}</span>
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </Button>
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
                                <h3 class="font-bold text-gray-900 text-base truncate">{{ vehicle?.brand }} {{ vehicle?.model }}</h3>
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
                                <p class="text-xs text-gray-500">{{ searchParams.time_from }} at {{ pickupStation.Name }}</p>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Return</span>
                            <div class="text-right">
                                <p class="font-medium text-gray-900 text-sm">{{ formatDate(searchParams.date_to) }}</p>
                                <p class="text-xs text-gray-500">{{ searchParams.time_to }} at {{ returnStation.Name }}</p>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Duration</span>
                            <span class="font-medium text-gray-900 text-sm">{{ rentalDays }} days</span>
                        </div>
                    </div>

                    <!-- Extras -->
                    <div v-if="selectedExtras.length > 0" class="pt-4 border-t border-gray-100 pb-6">
                        <h3 class="font-semibold text-gray-900 mb-3 text-base">Selected Extras</h3>
                        <div class="space-y-2">
                            <div v-for="extra in selectedExtras" :key="extra.code"
                                 class="flex justify-between text-sm">
                                <span class="text-gray-600">{{ extra.name }}</span>
                                <span class="font-medium">{{ formatPrice(extra.rate) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Total -->
                    <div class="pt-4 border-t border-gray-200">
                        <div class="space-y-2">
                            <div class="flex flex-col lg:flex-row lg:justify-between gap-1 lg:gap-0">
                                <span class="text-sm sm:text-base text-gray-600">Vehicle Rental</span>
                                <span class="text-sm sm:text-base font-medium text-gray-900">{{ formatPrice(baseTotal) }}</span>
                            </div>
                            <div v-if="extrasTotal > 0" class="flex flex-col lg:flex-row lg:justify-between gap-1 lg:gap-0">
                                <span class="text-sm sm:text-base text-gray-600">Extras</span>
                                <span class="text-sm sm:text-base font-medium text-gray-900">{{ formatPrice(extrasTotal) }}</span>
                            </div>
                            <div class="flex flex-col lg:flex-row lg:justify-between gap-1 lg:gap-0">
                                <span class="text-sm sm:text-base text-gray-600">Taxes & Fees</span>
                                <span class="text-sm sm:text-base font-medium text-gray-900">{{ formatPrice(taxesTotal) }}</span>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:justify-between gap-2 sm:gap-0 p-4 bg-primary/5 rounded-lg -mx-4 mt-4">
                            <span class="text-lg sm:text-xl font-bold text-gray-900">Total Amount</span>
                            <span class="text-xl sm:text-2xl font-bold text-primary">{{ formatPrice(grandTotal) }}</span>
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
.bg-primary { background-color: #153b4f; }
.text-primary { color: #153b4f; }
.border-primary { border-color: #153b4f; }

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
