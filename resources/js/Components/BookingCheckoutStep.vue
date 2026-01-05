<script setup>
import { ref, computed } from 'vue';
import StripeCheckoutButton from './StripeCheckoutButton.vue';
import { usePage } from '@inertiajs/vue3';

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
    numberOfDays: Number,
    currencySymbol: String,
    paymentPercentage: Number,
    totals: Object // { grandTotal, payableAmount, pendingAmount }
});

const emit = defineEmits(['back']);

// Pre-fill from auth user if available
const user = usePage().props.auth?.user || {};

const form = ref({
    name: user.name || '',
    email: user.email || '',
    phone: user.phone || '',
    driver_age: '',
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

    return isValid;
};

const isLocautoRent = computed(() => {
    return props.vehicle?.source === 'locauto_rent';
});

const bookingData = computed(() => {
    return {
        vehicle: props.vehicle,
        package: props.package,
        protection_code: props.protectionCode,
        protection_amount: props.protectionAmount || 0,
        extras: props.extras,
        detailed_extras: props.detailedExtras,
        customer: form.value,
        pickup_date: props.pickupDate,
        pickup_time: props.pickupTime,
        dropoff_date: props.dropoffDate,
        dropoff_time: props.dropoffTime,
        pickup_location: props.pickupLocation,
        dropoff_location: props.dropoffLocation,
        number_of_days: props.numberOfDays,
        total_amount: props.totals.grandTotal,
        currency: props.vehicle.currency || 'EUR'
    };
});

// Helper to format currency
const formatPrice = (val) => {
    return `${props.currencySymbol}${parseFloat(val).toFixed(2)}`;
};
</script>

<template>
    <div class="flex flex-col md:flex-row gap-6 p-4">
        <!-- Left Column: Customer Details -->
        <div class="flex-1 space-y-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-6">Driver Details</h3>
                
                <div class="space-y-4">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <input 
                            v-model="form.name"
                            type="text" 
                            class="w-full rounded-lg border-gray-300 focus:border-customPrimaryColor focus:ring-customPrimaryColor"
                            :class="{'border-red-500 rounded-lg': errors.name}"
                            placeholder="John Doe"
                        />
                        <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name }}</p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <input 
                            v-model="form.email"
                            type="email" 
                            class="w-full rounded-lg border-gray-300 focus:border-customPrimaryColor focus:ring-customPrimaryColor"
                            :class="{'border-red-500': errors.email}"
                            placeholder="john@example.com"
                        />
                        <p v-if="errors.email" class="text-red-500 text-xs mt-1">{{ errors.email }}</p>
                    </div>

                    <!-- Phone -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                        <input 
                            v-model="form.phone"
                            type="tel" 
                            class="w-full rounded-lg border-gray-300 focus:border-customPrimaryColor focus:ring-customPrimaryColor"
                            :class="{'border-red-500': errors.phone}"
                            placeholder="+1 234 567 8900"
                        />  
                        <p v-if="errors.phone" class="text-red-500 text-xs mt-1">{{ errors.phone }}</p>
                    </div>

                    <!-- Driver Age -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Driver Age</label>
                        <input 
                            v-model="form.driver_age"
                            type="number" 
                            class="w-full rounded-lg border-gray-300 focus:border-customPrimaryColor focus:ring-customPrimaryColor"
                            :class="{'border-red-500': errors.driver_age}"
                            placeholder="30"
                            min="18"
                        />
                        <p v-if="errors.driver_age" class="text-red-500 text-xs mt-1">{{ errors.driver_age }}</p>
                    </div>
                </div>
            </div>

            <!-- Payment Summary (Mobile only) -->
            <div class="md:hidden bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex justify-between items-center mb-4">
                    <span class="font-bold text-gray-800">Pay Now</span>
                    <span class="text-xl font-bold text-green-700">{{ formatPrice(totals.payableAmount) }}</span>
                </div>
                 <!-- Stripe Button (Mobile) -->
                <div v-if="validate()">
                     <StripeCheckoutButton 
                        :booking-data="bookingData"
                        :label="`Pay ${formatPrice(totals.payableAmount)}`"
                    />
                </div>
                <div v-else class="text-center text-sm text-gray-500">
                    Please fill driver details to proceed
                </div>
            </div>

             <button
                @click="$emit('back')"
                class="w-full md:w-auto px-6 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-all"
            >
                Back to Extras
            </button>
        </div>

        <!-- Right Column: Summary -->
        <div class="md:w-1/3 space-y-6">
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 sticky top-4">
                <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b">Booking Summary</h3>

                <!-- Vehicle -->
                <div class="flex items-start gap-4 mb-4">
                    <img :src="vehicle.image" alt="Car" class="w-20 h-14 object-cover rounded bg-gray-50" />
                    <div>
                        <div class="font-bold text-gray-800">{{ vehicle.brand }} {{ vehicle.model }}</div>
                        <div class="text-sm text-gray-500">{{ package }} Package</div>
                        <div class="text-xs text-gray-400 mt-1">{{ numberOfDays }} days</div>
                    </div>
                </div>

                <!-- Financials -->
                <div class="space-y-3 pt-4 border-t border-gray-100">
                    <div class="flex justify-between text-base">
                        <span class="text-gray-600">Total Amount</span>
                        <span class="font-bold text-gray-800">{{ formatPrice(totals.grandTotal) }}</span>
                    </div>
                    
                    <div class="bg-green-50 p-3 rounded-lg flex justify-between items-center">
                        <span class="text-sm font-semibold text-green-800">Pay Now</span>
                        <span class="text-lg font-bold text-green-700">{{ formatPrice(totals.payableAmount) }}</span>
                    </div>

                    <div class="flex justify-between text-sm text-gray-500 px-1">
                        <span>Pay on Arrival</span>
                        <span class="font-medium">{{ formatPrice(totals.pendingAmount) }}</span>
                    </div>
                </div>

                <!-- Stripe Button -->
                <div class="mt-6">
                     <div v-if="form.name && form.email && form.phone && form.driver_age"> <!-- basic check for reactivity -->
                         <StripeCheckoutButton 
                            v-if="!Object.keys(errors).length"
                            :booking-data="bookingData"
                            :label="`Pay ${formatPrice(totals.payableAmount)}`"
                        />
                         <button v-else @click="validate()" class="w-full bg-gray-300 text-gray-500 py-4 rounded-xl font-bold">
                            Fix Errors to Pay
                        </button>
                    </div>
                    <button v-else @click="validate()" class="w-full bg-gray-300 text-gray-500 py-4 rounded-xl font-bold">
                        Enter Details to Pay
                    </button>
                </div>
                
                <p class="text-xs text-center text-gray-400 mt-4 leading-relaxed">
                    By clicking "Pay", you agree to our Terms & Conditions and Privacy Policy.
                    You will be redirected to Stripe to complete your secure payment.
                </p>
            </div>
        </div>
    </div>
</template>
