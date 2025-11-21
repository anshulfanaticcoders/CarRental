<script setup>
import { Link, Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
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

// Currency conversion setup
const currencySymbols = ref({});
const exchangeRates = ref(null);
const { selectedCurrency } = useCurrency();

onMounted(async () => {
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

    <section class="bg-primary py-16 relative overflow-hidden">
        <div class="container relative z-10">
            <div class="max-w-4xl mx-auto text-center text-white">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">Complete Your Reservation</h1>
                <p class="text-xl opacity-90">{{ vehicle?.brand }} {{ vehicle?.model }} - {{ pickupStation?.Name }}</p>
            </div>
        </div>
    </section>

    <div class="container mx-auto py-12">
        <div class="mb-8">
            <div class="flex items-center justify-center space-x-8 mb-8">
                <div v-for="step in 3" :key="step" @click="goToStep(step)" class="flex items-center cursor-pointer transition-all duration-300">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-semibold transition-all duration-300" :class="step <= currentStep ? 'bg-primary text-white shadow-lg' : 'bg-gray-200 text-gray-600'">
                            <span>{{ step }}</span>
                        </div>
                        <span class="ml-3 font-medium" :class="step <= currentStep ? 'text-primary' : 'text-gray-500'">
                            {{ step === 1 ? 'Vehicle & Extras' : step === 2 ? 'Your Details' : 'Payment' }}
                        </span>
                    </div>
                    <div v-if="step < 3" class="w-20 h-0.5 ml-8" :class="step < currentStep ? 'bg-primary' : 'bg-gray-200'"></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
            <div class="lg:col-span-3">
                <!-- Step 1: Vehicle & Extras -->
                <div v-show="currentStep === 1" class="space-y-8">
                    <Card>
                        <CardHeader><CardTitle>Your Selected Vehicle</CardTitle></CardHeader>
                        <CardContent>
                            <div class="flex gap-6">
                                <img :src="vehicle?.image" :alt="`${vehicle?.brand} ${vehicle?.model}`" class="w-32 h-24 object-cover rounded-lg" />
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold">{{ vehicle?.brand }} {{ vehicle?.model }}</h3>
                                    <p class="text-gray-600">{{ vehicle?.category }}</p>
                                    <div class="flex gap-4 mt-2 text-sm text-gray-500">
                                        <span>{{ vehicle?.seating_capacity }} Seats</span>
                                        <span>{{ vehicle?.doors }} Doors</span>
                                        <span>{{ vehicle?.transmission }}</span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card v-if="availableExtras && availableExtras.length > 0">
                        <CardHeader><CardTitle>Additional Extras</CardTitle></CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div v-for="extra in availableExtras" :key="extra.code" class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50">
                                    <div class="flex items-center space-x-3">
                                        <Checkbox :id="extra.code" :checked="selectedExtras.some(e => e.code === extra.code)" @update:checked="toggleExtra(extra)" />
                                        <div>
                                            <Label :for="extra.code" class="font-medium cursor-pointer">{{ extra.name }}</Label>
                                            <p class="text-sm text-gray-500">{{ extra.description || 'Additional charge per day' }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold">{{ formatPrice(extra.rate, vehicle?.currency || 'USD') }}</p>
                                        <p class="text-xs text-gray-500">{{ extra.charge_type || 'per day' }}</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Step 2: Customer Information -->
                <div v-show="currentStep === 2" class="space-y-8">
                    <Card>
                        <CardHeader><CardTitle>Your Details</CardTitle></CardHeader>
                        <CardContent class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <Label for="first_name">First Name *</Label>
                                    <Input id="first_name" v-model="form.customer_first_name" type="text" required :class="{ 'border-red-500': formErrors.firstname }" />
                                    <p v-if="formErrors.firstname" class="text-red-500 text-sm mt-1">{{ formErrors.firstname }}</p>
                                </div>
                                <div>
                                    <Label for="last_name">Last Name *</Label>
                                    <Input id="last_name" v-model="form.customer_last_name" type="text" required :class="{ 'border-red-500': formErrors.surname }" />
                                    <p v-if="formErrors.surname" class="text-red-500 text-sm mt-1">{{ formErrors.surname }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <Label for="email">Email Address *</Label>
                                    <Input id="email" v-model="form.customer_email" type="email" required :class="{ 'border-red-500': formErrors.email }" />
                                    <p v-if="formErrors.email" class="text-red-500 text-sm mt-1">{{ formErrors.email }}</p>
                                </div>
                                <div>
                                    <Label for="phone">Phone Number *</Label>
                                    <Input id="phone" v-model="form.customer_phone" type="tel" required :class="{ 'border-red-500': formErrors.phone }" />
                                    <p v-if="formErrors.phone" class="text-red-500 text-sm mt-1">{{ formErrors.phone }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Step 3: Payment -->
                <div v-show="currentStep === 3" class="space-y-8">
                    <Card>
                        <CardHeader><CardTitle>Complete Your Payment</CardTitle></CardHeader>
                        <CardContent>
                            <WheelsysStripeCheckout :booking-data="form" />
                        </CardContent>
                    </Card>
                </div>

                <div class="flex justify-between pt-8">
                    <Button v-if="currentStep > 1" @click="prevStep" variant="outline">Previous Step</Button>
                    <div v-else></div>
                    <Button v-if="currentStep < 3" @click="nextStep">Next Step</Button>
                </div>
            </div>

            <!-- Right Column: Booking Summary -->
            <div class="lg:col-span-2">
                <div class="sticky top-4">
                    <Card>
                        <CardHeader class="bg-primary text-white"><CardTitle>Booking Summary</CardTitle></CardHeader>
                        <CardContent class="p-6 space-y-6">
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Pickup</span>
                                    <div class="text-right">
                                        <p class="font-medium text-gray-900">{{ formatDate(searchParams.date_from) }}</p>
                                        <p class="text-sm text-gray-500">{{ searchParams.time_from }} at {{ pickupStation.Name }}</p>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Return</span>
                                    <div class="text-right">
                                        <p class="font-medium text-gray-900">{{ formatDate(searchParams.date_to) }}</p>
                                        <p class="text-sm text-gray-500">{{ searchParams.time_to }} at {{ returnStation.Name }}</p>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Duration</span>
                                    <span class="font-medium text-gray-900">{{ rentalDays }} days</span>
                                </div>
                            </div>
                            <Separator />
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Vehicle Rental</span>
                                    <span class="font-medium">{{ formatPrice(baseTotal, vehicle.currency) }}</span>
                                </div>
                                <div v-if="extrasTotal > 0" class="flex justify-between">
                                    <span class="text-gray-600">Extras</span>
                                    <span class="font-medium">{{ formatPrice(extrasTotal, vehicle.currency) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Taxes & Fees</span>
                                    <span class="font-medium">{{ formatPrice(taxesTotal, vehicle.currency) }}</span>
                                </div>
                            </div>
                            <Separator />
                            <div class="flex justify-between items-center">
                                <span class="text-xl font-bold text-gray-900">Total</span>
                                <span class="text-2xl font-bold text-primary">{{ formatPrice(grandTotal, vehicle.currency) }}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </div>
    <Footer />
</template>

<style scoped>
.bg-primary { background-color: #153b4f; }
.text-primary { color: #153b4f; }
.border-primary { border-color: #153b4f; }
</style>
