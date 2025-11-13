<script setup>
import { Link, Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Checkbox } from '@/Components/ui/checkbox';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Separator } from '@/Components/ui/separator';
import { Badge } from '@/Components/ui/badge';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import { useCurrency } from '@/composables/useCurrency';

const props = defineProps({
    vehicle: Object,
    searchParams: Object,
    availableExtras: Array,
    pickupStation: Object,
    returnStation: Object,
    quoteResponse: Object,
    user: Object,
});

const page = usePage();
const processing = ref(false);
const selectedExtras = ref([]);

// Currency conversion setup
const currencySymbols = ref({});
const exchangeRates = ref(null);
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
    'R$': 'BRL',
    '₽': 'RUB',
    '₺': 'TRY',
    '฿': 'THB',
    '₡': 'CRC',
    '₦': 'NGN',
    '₨': 'PKR',
    '₪': 'ILS',
    '₫': 'LAK',
    '₸': 'KZT',
    '₼': 'AZN',
    '₴': 'UAH',
    '؋': 'LBP',
    '₦': 'NGN',
    '₨': 'LKR',
    '₡': 'SVC',
    '₲': 'PYG',
    '₱': 'PHP',
    '₽': 'RUB',
    '₪': 'ILS',
    '﷼': 'IRR',
    '₮': 'MNT',
    '₩': 'KRW',
    '円': 'JPY',
    '元': 'CNY',
    'ƒ': 'ANG',
    '₨': 'BDT',
    '₨': 'NPR',
    '₹': 'INR',
    '₨': 'PKR',
    '৳': 'BDT',
    '៛': 'KHR',
    '₭': 'LAK',
    '₦': 'NGN',
    '₨': 'PKR',
    '₨': 'LKR',
    '៛': 'KHR',
    '₭': 'LAK',
    '₦': 'NGN',
    '₨': 'PKR',
    '₨': 'LKR',
    '૱': 'INR',
    '௹': 'LKR',
    'রু': 'BDT',
    '៛': 'KHR',
    '₭': 'LAK',
    '₦': 'NGN',
    '₨': 'PKR',
    '₨': 'LKR',
    '₹': 'INR',
    '৳': 'BDT',
    '៛': 'KHR',
    '₭': 'LAK',
    '₦': 'NGN',
    '₨': 'PKR',
    '₨': 'LKR',
};

// Load exchange rates on mount
onMounted(async () => {
    try {
        const response = await fetch('/api/currency/rates');
        const data = await response.json();
        exchangeRates.value = data.rates;
        currencySymbols.value = data.symbols;
    } catch (error) {
        console.error('Failed to load currency data:', error);
    }
});

const getCurrencySymbol = (code) => {
    return currencySymbols.value[code] || '$';
};

const convertCurrency = (price, fromCurrency) => {
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

const formatPrice = (price, currency = 'USD') => {
    if (!price || price === 0) return 'Free';
    const originalCurrency = currency || 'USD';
    const convertedPrice = convertCurrency(price, originalCurrency);
    const currencySymbol = getCurrencySymbol(selectedCurrency.value);
    return `${currencySymbol}${convertedPrice.toFixed(2)}`;
};

// Calculate pricing with fallbacks
const baseRate = computed(() => {
    const rate = props.vehicle?.price_per_day;
    console.log('Base rate calculation:', {
        'vehicle exists': !!props.vehicle,
        'vehicle currency': props.vehicle?.currency,
        'vehicle_price_per_day': props.vehicle?.price_per_day,
        'rate value': rate,
        'isNan': isNaN(rate)
    });

    // Multiple fallback checks
    if (rate === null || rate === undefined || isNaN(rate) || rate === 0) {
        console.warn('Using fallback price due to invalid rate:', rate);
        return 50.0;
    }

    return parseFloat(rate);
});

const rentalDays = computed(() => {
    // Convert dd/mm/YYYY to mm/dd/YYYY for JavaScript Date parsing
    const formatDate = (dateStr) => {
        if (!dateStr) return new Date();
        const parts = dateStr.split('/');
        if (parts.length === 3) {
            // Convert dd/mm/YYYY to mm/dd/YYYY
            return new Date(`${parts[1]}/${parts[0]}/${parts[2]}`);
        }
        return new Date(dateStr);
    };

    const pickup = formatDate(props.searchParams?.date_from);
    const returnDate = formatDate(props.searchParams?.date_to);
    const days = Math.ceil((returnDate - pickup) / (1000 * 60 * 60 * 24));
    return Math.max(1, days);
});

const baseTotal = computed(() => baseRate.value * rentalDays.value);
const extrasTotal = computed(() => {
    return selectedExtras.value.reduce((total, extra) => {
        return total + (extra.rate * rentalDays.value);
    }, 0);
});

const taxesTotal = computed(() => {
    const subtotal = baseTotal.value + extrasTotal.value;
    return subtotal * 0.065; // 6.5% tax
});

const grandTotal = computed(() => baseTotal.value + extrasTotal.value + taxesTotal.value);

// Formatted pricing displays with currency conversion
const formattedBaseRate = computed(() => formatPrice(baseRate.value, props.vehicle?.currency || 'USD'));
const formattedBaseTotal = computed(() => formatPrice(baseTotal.value, props.vehicle?.currency || 'USD'));
const formattedExtrasTotal = computed(() => formatPrice(extrasTotal.value, props.vehicle?.currency || 'USD'));
const formattedTaxesTotal = computed(() => formatPrice(taxesTotal.value, props.vehicle?.currency || 'USD'));
const formattedGrandTotal = computed(() => formatPrice(grandTotal.value, props.vehicle?.currency || 'USD'));

// Debug log
console.log('Booking page data:', {
    vehicle: props.vehicle,
    searchParams: props.searchParams,
    baseRate: baseRate.value,
    rentalDays: rentalDays.value,
    baseTotal: baseTotal.value
});

// Form setup
const form = useForm({
    vehicle_group_code: props.vehicle?.group_code || '',
    vehicle_group_name: (props.vehicle?.brand || 'Wheelsys') + ' ' + (props.vehicle?.model || 'Vehicle'),
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

// Handle extras selection
const toggleExtra = (extra) => {
    const index = selectedExtras.value.findIndex(e => e.code === extra.Code);

    if (index > -1) {
        selectedExtras.value.splice(index, 1);
    } else {
        // Convert rate from cents to dollars (divide by 100)
        const rateInDollars = extra.Rate ? extra.Rate / 100 : 0;

        selectedExtras.value.push({
            code: extra.Code,
            name: extra.Name,
            rate: rateInDollars,
            quantity: 1,
            originalRate: extra.Rate,
            mandatory: extra.Mandatory || false
        });
    }

    form.selected_extras = selectedExtras.value;
};

// Submit booking
const submitBooking = () => {
    processing.value = true;

    form.selected_extras = selectedExtras.value;

    form.post(route('wheelsys.booking.store'), {
        onSuccess: () => {
            processing.value = false;
        },
        onError: (errors) => {
            processing.value = false;
            console.error('Booking errors:', errors);
        }
    });
};


// Format date
const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
</script>

<template>
    <Head>
        <title>Complete Booking - {{ vehicle?.brand }} {{ vehicle?.model }}</title>
    </Head>

    <AuthenticatedHeaderLayout/>
        <div class="min-h-screen bg-gray-50 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Complete Your Booking</h1>
                    <p class="text-gray-600 mt-2">Review your details and confirm your reservation</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Main Content (2 columns) -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Vehicle Summary -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Vehicle Details</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="flex gap-6">
                                    <img
                                        :src="vehicle?.image"
                                        :alt="`${vehicle?.brand} ${vehicle?.model}`"
                                        class="w-32 h-24 object-cover rounded-lg"
                                    />
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

                        <!-- Rental Details -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Rental Details</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <Label class="text-sm font-medium text-gray-700">Pickup</Label>
                                        <div class="mt-1 p-3 bg-green-50 rounded-lg">
                                            <p class="font-semibold text-green-900">{{ pickupStation?.Name }}</p>
                                            <p class="text-sm text-green-700">{{ formatDate(searchParams?.date_from) }} at {{ searchParams?.time_from }}</p>
                                        </div>
                                    </div>
                                    <div>
                                        <Label class="text-sm font-medium text-gray-700">Return</Label>
                                        <div class="mt-1 p-3 bg-blue-50 rounded-lg">
                                            <p class="font-semibold text-blue-900">{{ returnStation?.Name }}</p>
                                            <p class="text-sm text-blue-700">{{ formatDate(searchParams?.date_to) }} at {{ searchParams?.time_to }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center">
                                    <Badge variant="secondary" class="text-lg px-4 py-2">
                                        {{ rentalDays }} {{ rentalDays === 1 ? 'Day' : 'Days' }}
                                    </Badge>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Customer Information -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Customer Information</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <Label for="first_name">First Name *</Label>
                                        <Input
                                            id="first_name"
                                            v-model="form.customer_first_name"
                                            type="text"
                                            required
                                            :class="{ 'border-red-500': form.errors.customer_first_name }"
                                        />
                                        <p v-if="form.errors.customer_first_name" class="text-red-500 text-sm mt-1">{{ form.errors.customer_first_name }}</p>
                                    </div>
                                    <div>
                                        <Label for="last_name">Last Name *</Label>
                                        <Input
                                            id="last_name"
                                            v-model="form.customer_last_name"
                                            type="text"
                                            required
                                            :class="{ 'border-red-500': form.errors.customer_last_name }"
                                        />
                                        <p v-if="form.errors.customer_last_name" class="text-red-500 text-sm mt-1">{{ form.errors.customer_last_name }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <Label for="email">Email Address *</Label>
                                        <Input
                                            id="email"
                                            v-model="form.customer_email"
                                            type="email"
                                            required
                                            :class="{ 'border-red-500': form.errors.customer_email }"
                                        />
                                        <p v-if="form.errors.customer_email" class="text-red-500 text-sm mt-1">{{ form.errors.customer_email }}</p>
                                    </div>
                                    <div>
                                        <Label for="phone">Phone Number *</Label>
                                        <Input
                                            id="phone"
                                            v-model="form.customer_phone"
                                            type="tel"
                                            required
                                            :class="{ 'border-red-500': form.errors.customer_phone }"
                                        />
                                        <p v-if="form.errors.customer_phone" class="text-red-500 text-sm mt-1">{{ form.errors.customer_phone }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <Label for="age">Driver Age *</Label>
                                        <Input
                                            id="age"
                                            v-model="form.customer_age"
                                            type="number"
                                            min="18"
                                            max="99"
                                            required
                                            :class="{ 'border-red-500': form.errors.customer_age }"
                                        />
                                        <p v-if="form.errors.customer_age" class="text-red-500 text-sm mt-1">{{ form.errors.customer_age }}</p>
                                    </div>
                                    <div>
                                        <Label for="driver_licence">Driver's License</Label>
                                        <Input
                                            id="driver_licence"
                                            v-model="form.customer_driver_licence"
                                            type="text"
                                            placeholder="License number"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <Label for="address">Address</Label>
                                    <Textarea
                                        id="address"
                                        v-model="form.customer_address"
                                        placeholder="Full address"
                                        rows="3"
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Extras -->
                        <Card v-if="availableExtras && availableExtras.length > 0">
                            <CardHeader>
                                <CardTitle>Additional Extras</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-3">
                                    <div
                                        v-for="extra in availableExtras"
                                        :key="extra.Code"
                                        class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50"
                                    >
                                        <div class="flex items-center space-x-3">
                                            <Checkbox
                                                :id="extra.Code"
                                                :checked="selectedExtras.some(e => e.code === extra.Code)"
                                                @update:checked="toggleExtra(extra)"
                                            />
                                            <div>
                                                <Label :for="extra.Code" class="font-medium cursor-pointer">
                                                    {{ extra.Name }}
                                                </Label>
                                                <p class="text-sm text-gray-500">Additional charge per day</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-semibold">{{ formatPrice(extra.Rate ? extra.Rate / 100 : 0, props.vehicle?.currency || 'USD') }}</p>
                                            <p class="text-xs text-gray-500">per day</p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Additional Notes -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Additional Notes</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Textarea
                                    v-model="form.customer_notes"
                                    placeholder="Any special requests or notes for your rental..."
                                    rows="4"
                                />
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Sidebar (1 column) -->
                    <div class="space-y-6">
                        <!-- Price Summary -->
                        <Card class="sticky top-4">
                            <CardHeader>
                                <CardTitle>Price Summary</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="flex justify-between">
                                    <span>Vehicle Rental ({{ rentalDays }} days)</span>
                                    <span>{{ formattedBaseTotal }}</span>
                                </div>

                                <div v-if="extrasTotal > 0" class="flex justify-between">
                                    <span>Extras</span>
                                    <span>{{ formattedExtrasTotal }}</span>
                                </div>

                                <div class="flex justify-between">
                                    <span>Taxes & Fees</span>
                                    <span>{{ formattedTaxesTotal }}</span>
                                </div>

                                <Separator />

                                <div class="flex justify-between text-lg font-bold">
                                    <span>Total</span>
                                    <span>{{ formattedGrandTotal }}</span>
                                </div>

                                <Alert>
                                    <AlertDescription>
                                        <strong>Important:</strong> Payment will be processed securely via Stripe. Your booking will be confirmed immediately after successful payment.
                                    </AlertDescription>
                                </Alert>
                            </CardContent>
                        </Card>

                        <!-- Action Buttons -->
                        <div class="space-y-4">
                            <Button
                                @click="submitBooking"
                                :disabled="processing"
                                class="w-full bg-gradient-to-r from-customPrimaryColor to-blue-700 hover:from-customPrimaryColor/90 hover:to-blue-700/90 text-white py-3 text-lg font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300"
                            >
                                <span v-if="!processing">Proceed to Payment</span>
                                <span v-else>Processing...</span>
                            </Button>

                            <Link
                                :href="route('wheelsys-car.show', vehicle?.id)"
                                class="block w-full text-center"
                            >
                                <Button variant="outline" class="w-full">
                                    Back to Vehicle Details
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</template>