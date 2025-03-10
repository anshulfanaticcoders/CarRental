<script setup>
import pickupLocationIcon from "../../assets/pickupLocationIcon.svg";
import returnLocationIcon from "../../assets/returnLocationIconLight.svg";
import infoIcon from "../../assets/WarningCircle.svg";
import { ref, computed, onMounted, watch, nextTick } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import walkIcon from "../../assets/walkingrayIcon.svg";
import { Inertia } from '@inertiajs/inertia';
import paypal from '../../assets/paypal.svg';
import mastercard from '../../assets/mastercard.svg';
import axios from 'axios';
import { loadStripe } from '@stripe/stripe-js';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Footer from "@/Components/Footer.vue";
import loader from "../../assets/loader.gif";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/Components/ui/dialog'

// Add these methods to your script section
const incrementQuantity = (extra) => {
    if (extra.quantity < 2) {
        extra.quantity++;
    }
};

const decrementQuantity = (extra) => {
    if (extra.quantity > 0) {
        extra.quantity--;
    }
};

const validateQuantity = (extra) => {
    // Ensure quantity stays between 0 and 2
    if (extra.quantity > 2) {
        extra.quantity = 2;
    }
    if (extra.quantity < 0) {
        extra.quantity = 0;
    }
};

// Multiple step form
const currentStep = ref(1);
const validateSteps = () => {
    if (!selectedPlan.value) {
        alert("Please select a protection plan before proceeding.");
        return false;
    }

    if (currentStep.value === 2) {
        // Add any additional validation for Step 2 here
        if (!customer.value.first_name || !customer.value.last_name || !customer.value.email) {
            alert("Please fill in all required fields in Step 2.");
            return false;
        }
    }

    return true;
};

const moveToNextStep = () => {
    if (validateSteps()) {
        currentStep.value++;
    }
};
const moveToPrevStep = () => {
    currentStep.value--;
};

// Getting the vehicle data from api
const { props } = usePage();
const vehicle = ref(props.vehicle);
const user = ref(props.auth?.user || null);
const packageType = ref(props.query?.packageType || 'day');
const totalPrice = ref(Number(props.query?.totalPrice) || 0);
const discountAmount = ref(Number(props.query?.discountAmount) || 0);
const dateFrom = ref(props.query?.dateFrom || null);
const dateTo = ref(props.query?.dateTo || null);
const timeFrom = ref(props.query?.timeFrom || null);
const timeTo = ref(props.query?.timeTo || null);


// Calculate cancellation date based on package type
const getCancellationDate = computed(() => {
    if (!dateFrom.value) return null;

    let cancellationDays = 0;

    if (packageType.value === 'day' && vehicle.value?.benefits?.cancellation_available_per_day) {
        cancellationDays = vehicle.value?.benefits?.cancellation_available_per_day_date || 0;
    } else if (packageType.value === 'week' && vehicle.value?.benefits?.cancellation_available_per_week) {
        cancellationDays = vehicle.value?.benefits?.cancellation_available_per_week_date || 0;
    } else if (packageType.value === 'month' && vehicle.value?.benefits?.cancellation_available_per_month) {
        cancellationDays = vehicle.value?.benefits?.cancellation_available_per_month_date || 0;
    }

    // Create a new date object from dateFrom
    const bookingDate = new Date(dateFrom.value);
    // Subtract cancellation days
    bookingDate.setDate(bookingDate.getDate() - cancellationDays);

    // Format the date as YYYY-MM-DD
    return bookingDate.toISOString().split('T')[0];
});

// Format date function for display
const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);

    // Format as YYYY-MM-DD
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

// This is for protection Plans
const plans = ref([]);
const selectedPlan = ref(null);
const fetchPlans = async () => {
    try {
        const response = await axios.get("/api/plans");
        plans.value = response.data;
        // Select Free plan by default
        const freePlan = plans.value.find(
            (plan) => plan.plan_type === "Free plan"
        );
        if (freePlan) {
            selectedPlan.value = freePlan;
        }
    } catch (error) {
        console.error("Error fetching plans:", error);
    }
};

const selectPlan = (plan) => {
    selectedPlan.value = plan;
};

// This is for Extras
const bookingExtras = ref([]);

const fetchBookingExtras = async () => {
    try {
        const response = await axios.get("/api/booking-addons");
        bookingExtras.value = response.data;
    } catch (error) {
        console.error("Error fetching booking extras:", error);
    }
};

onMounted(() => {
    fetchBookingExtras();
    fetchPlans();
});

// Store the selected plan and booking extras in sessionStorage
const storeSelectionData = () => {
    const selectionData = {
        selectedPlan: selectedPlan.value,
        extras: bookingExtras.value.map(extra => ({ id: extra.id, quantity: extra.quantity, price: extra.price * extra.quantity, extra_name: extra.extra_name, extra_type: extra.extra_type }))
    };
    sessionStorage.setItem("selectionData", JSON.stringify(selectionData));
};

// Load saved data from sessionStorage
const loadSelectionData = () => {
    const savedSelectionData = sessionStorage.getItem("selectionData");
    if (savedSelectionData) {
        const parsedData = JSON.parse(savedSelectionData);
        selectedPlan.value = parsedData.selectedPlan;
        parsedData.extras.forEach(savedExtra => {
            const extra = bookingExtras.value.find(extra => extra.id === savedExtra.id);
            if (extra) {
                extra.quantity = savedExtra.quantity;
            }
        });
    }
};

// Watch for changes in selected plan and booking extras and automatically store them
watch([selectedPlan, bookingExtras], storeSelectionData, { deep: true });

// Driver Contact info
const customer = ref({
    first_name: user.value?.first_name || "",
    last_name: user.value?.last_name || "",
    email: user.value?.email || "",
    phone: user.value?.phone || "",
    flight_number: "",
    driver_age: null,
});


// Example driver age range
const ageRange = Array.from({ length: 80 - 21 + 1 }, (_, i) => 21 + i);

const isFormSaved = ref(false);

const storeFormData = () => {
    sessionStorage.setItem("driverInfo", JSON.stringify(customer.value));
    isFormSaved.value = true;
};

const loadSavedDriverInfo = () => {
    const savedDriverInfo = sessionStorage.getItem("driverInfo");
    if (savedDriverInfo) {
        customer.value = JSON.parse(savedDriverInfo);
        isFormSaved.value = true;
    }
};

onMounted(() => {
    loadSavedDriverInfo();
    loadSelectionData();  // Load selection data when component mounts
});

// stripe payment
const stripePromise = loadStripe('pk_test_51KNyHdSDGjAmjQxjhTSx7hrI5uBOE75wqXRZNFdw83XSTU8gvwpFusz2W8FAy7PkV8K0aGHqhC620kbMWTg32ZpS00p3ktAi86'); // Replace with your Stripe publishable key
let stripe;
let elements;
let cardNumber;
let cardExpiry;
let cardCvc;

const pickupDate = ref('');
const returnDate = ref('');
const pickupTime = ref('');
const returnTime = ref('');
const extras = ref([]);

const loadSessionData = () => {
    // Load driver info
    const driverInfo = JSON.parse(sessionStorage.getItem('driverInfo'));
    if (driverInfo) {
        customer.value = driverInfo;
    }

    // Load rental dates
    const rentalDates = JSON.parse(sessionStorage.getItem('rentalDates'));
    if (rentalDates) {
        pickupDate.value = rentalDates.date_from;
        returnDate.value = rentalDates.date_to;
        pickupTime.value = rentalDates.time_from;
        returnTime.value = rentalDates.time_to;
    }

    // Load plan and extras
    const selectionData = JSON.parse(sessionStorage.getItem('selectionData'));
    if (selectionData) {
        selectedPlan.value = selectionData.selectedPlan;
        extras.value = selectionData.extras;

        // Initialize extraCharges with the plan value
        let extraCharges = Number(selectedPlan.value?.plan_value ?? 0);

        // Add the extra charges from the selected extras
        selectionData.extras.forEach((extra) => {
            if (extra.quantity > 0) {
                extraCharges += Number(extra.price) * extra.quantity;  // Add price * quantity for each selected extra
            }
        });

        console.log("Total Extra Charges (including plan and extras): ", extraCharges);
    }
};

const formatPrice = (price) => {
    const currencySymbol = vehicle.value.vendor_profile.currency;
    return `${currencySymbol}${price}`;
};


// Update your total calculation
const calculateTotal = computed(() => {
    let total = 0;

    total += Number(totalPrice.value);
    // Add plan value
    total += Number(selectedPlan.value?.plan_value || 0);

    // Add extras with quantity multiplication
    bookingExtras.value.forEach((extra) => {
        if (extra.quantity > 0) {
            total += Number(extra.price) * Number(extra.quantity);
        }
    });

    return total;
});

onMounted(async () => {
    stripe = await stripePromise;

    // Customize the appearance of the card elements
    const style = {
        base: {
            color: '#32325d',
            fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
            fontSmoothing: 'antialiased',
            fontSize: '16px',
            '::placeholder': {
                color: '#aab7c4',
            },
        },
        invalid: {
            color: '#fa755a',
            iconColor: '#fa755a',
        },
    };

    elements = stripe.elements();

    // Ensure the DOM elements are available
    await nextTick();

    // Retry logic for delayed DOM availability
    const mountElements = () => {
        const cardNumberElement = document.querySelector('#card-number');
        const cardExpiryElement = document.querySelector('#card-expiry');
        const cardCvcElement = document.querySelector('#card-cvc');

        if (cardNumberElement && cardExpiryElement && cardCvcElement) {
            // Create and mount the card elements
            cardNumber = elements.create('cardNumber', { style });
            cardExpiry = elements.create('cardExpiry', { style });
            cardCvc = elements.create('cardCvc', { style });

            cardNumber.mount('#card-number');
            cardExpiry.mount('#card-expiry');
            cardCvc.mount('#card-cvc');

            // Handle real-time validation errors
            const displayError = document.getElementById('card-errors');
            cardNumber.on('change', (event) => {
                displayError.textContent = event.error ? event.error.message : '';
            });
            cardExpiry.on('change', (event) => {
                displayError.textContent = event.error ? event.error.message : '';
            });
            cardCvc.on('change', (event) => {
                displayError.textContent = event.error ? event.error.message : '';
            });
        } else {
            setTimeout(mountElements, 100); // Retry after 100ms
        }
    };

    mountElements();
});

const error = ref([]);
const isLoading = ref(false);
watch(isLoading, (newValue) => {
    if (newValue) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});

const submitBooking = async () => {
    if (!validateSteps()) {
        return;
    }
    isLoading.value = true; // Show loader

    try {
        // Load session data
        loadSessionData();

        // Calculate the total days by comparing pickup and return dates
        const pickupDateObj = new Date(pickupDate.value);
        const returnDateObj = new Date(returnDate.value);
        const totalDays = Math.ceil((returnDateObj - pickupDateObj) / (1000 * 3600 * 24)); // Difference in days

        // Calculate extra charges (sum of all selected extras)
        let extraCharges = 0;
        bookingExtras.value.forEach((extra) => {
            if (extra.quantity > 0) {
                extraCharges += extra.price * extra.quantity;
            }
        });

        // Prepare the booking data from session storage and calculated values
        const bookingData = {
            customer: customer.value,
            pickup_date: pickupDate.value,
            return_date: returnDate.value,
            pickup_location: vehicle.value?.location,
            return_location: vehicle.value?.location,
            pickup_time: pickupTime.value,
            return_time: returnTime.value,
            total_days: totalDays,
            base_price: Number(totalPrice.value),
            preferred_day: packageType.value,
            extra_charges: extraCharges > 0 ? extraCharges : null,
            total_amount: calculateTotal.value,
            discount_amount: Number(discountAmount.value),
            plan: selectedPlan.value.plan_type,
            extras: extras.value,
            vehicle_id: vehicle.value?.id,
        };
        console.log("Vehicle ID:", vehicle.value?.id);
        try {
            // First, create the payment method from Stripe
            const { error, paymentMethod } = await stripe.createPaymentMethod({
                type: 'card',
                card: cardNumber,
                billing_details: {
                    name: `${customer.value.first_name} ${customer.value.last_name}`,
                    email: customer.value.email,
                    phone: customer.value.phone,
                    address: {
                        line1: 'United states',
                        country: 'US',
                        state: "California",
                    },
                },
            });

            if (error) {
                console.error(error);
                alert("Payment error: " + error.message);
                return;
            }

            // Now send the booking data along with the paymentMethod.id
            const response = await axios.post('/booking', {
                ...bookingData,
                payment_method_id: paymentMethod.id,  // Send the payment method ID to the backend
            });

            const clientSecret = response.data.clientSecret;
            // Log the Payment Intent before confirming it
            console.log('Client Secret:', clientSecret);

            // Retrieve the Payment Intent to check its status
            const { paymentIntent } = await stripe.retrievePaymentIntent(clientSecret);
            console.log('Payment Intent Status:', paymentIntent.status);

            if (paymentIntent.status === 'succeeded') {
                sessionStorage.clear();
                // Payment is already successful, no need to confirm again
                Inertia.visit(`/booking-success/details?payment_intent=${paymentIntent.id}`);
                return;
            }

            if (paymentIntent.status === 'requires_action') {
                const { error: actionError, paymentIntent: confirmedPaymentIntent } = await stripe.handleCardAction(clientSecret);

                if (actionError) {
                    console.error('Action Error:', actionError);
                    throw new Error(actionError.message);
                }

                console.log('Confirmed Payment Intent:', confirmedPaymentIntent);
                Inertia.visit(`/booking-success/details?payment_intent=${paymentIntent.id}`);
                return;
            }
        } catch (err) {
            error.value = err.message || 'An error occurred. Please try again.';
        }
    } finally {
        isLoading.value = false; // Hide loader
    }
};

</script>


<template>

    <Head title="Booking" />
    <AuthenticatedHeaderLayout />
    <main>
        <section>
            <div class="full-w-container flex justify-between py-customVerticalSpacing gap-5">
                <div class="column w-[65%] flex flex-col gap-10" v-if="currentStep === 1">
                    <div class="free_cancellation p-5 bg-[#0099001A] border-[#009900] rounded-[8px] border-[1px]">

                        <!-- Cancellation Availability Display -->
                        <div v-if="vehicle?.benefits?.cancellation_available_per_day && packageType === 'day'"
                            class="flex items-center gap-1">
                            <p class="text-[1.2rem] text-[#009900] font-medium">
                                Free Cancellation before {{ formatDate(getCancellationDate) }}
                                ({{ vehicle?.benefits?.cancellation_available_per_day_date }} days prior to pickup)
                            </p>
                        </div>
                        <div v-if="vehicle?.benefits?.cancellation_available_per_week && packageType === 'week'"
                            class="flex items-center gap-1">
                            <p class="text-[1.2rem] text-[#009900] font-medium">
                                Free Cancellation before {{ formatDate(getCancellationDate) }}
                                ({{ vehicle?.benefits?.cancellation_available_per_week_date }} days prior to pickup)
                            </p>
                        </div>
                        <div v-if="vehicle?.benefits?.cancellation_available_per_month && packageType === 'month'"
                            class="flex items-center gap-1">
                            <p class="text-[1.2rem] text-[#009900] font-medium">
                                Free Cancellation before {{ formatDate(getCancellationDate) }}
                                ({{ vehicle?.benefits?.cancellation_available_per_month_date }} days prior to pickup)
                            </p>
                        </div>

                        <div v-else-if="!vehicle?.benefits?.cancellation_available_per_month">
                            <p class="text-[1.2rem] text-[#009900] font-medium">No Cancellation Available</p>
                        </div>


                    </div>

                    <div class="flex flex-col gap-10">
                        <h4 class="text-[2.5rem]">Protection Plan</h4>

                        <!-- Protection Plan -->
                        <div class="protection_plan flex gap-10">
                            <div v-for="plan in plans" :key="plan.id" class="cursor-pointer col w-[45%] rounded-[20px] border-[1px] border-[#153B4F] p-5 flex flex-col gap-5 
           transition-transform duration-300 ease-in-out" :class="{
            'hover:scale-105 scale-105': selectedPlan?.id === plan.id,  // Keeps the scale effect when selected
            'border-[#153B4F] bg-[#153B4F0D]': selectedPlan?.id === plan.id,
        }" @click="selectPlan(plan)">

                                <span class="text-[1.5rem] text-center" :class="{
                                    'text-[#016501]': plan.plan_type === 'Exclusive plan',
                                }">
                                    {{ plan.plan_type }}
                                </span>

                                <strong class="text-[3rem] font-medium text-center">
                                    {{ formatPrice(plan.plan_value) }}
                                </strong>

                                <p class="text-[1.25rem] text-[#2B2B2B] text-center">
                                    Access to basic features without any subscription fee.
                                </p>

                                <button class="button-primary px-5 py-2" @click="selectPlan(plan)" :class="{
                                    'bg-[#016501]': selectedPlan?.id === plan.id,
                                }">
                                    {{ selectedPlan?.id === plan.id ? "Selected" : "Select" }}
                                </button>

                                <div class="checklist features">
                                    <ul
                                        class="check-list text-center mt-[1rem] inline-flex flex-col items-center w-full gap-3">
                                        <li v-for="(feature, index) in plan.features" :key="index"
                                            class="checklist-item">
                                            {{ feature }}
                                        </li>
                                    </ul>
                                </div>
                            </div>

                        </div>

                        <!-- Additional Equipment -->
                        <div class="additional-equipment">
                            <h4 class="text-[2.5rem]">Additional Equipment</h4>
                            <p>
                                Please note these additional extras are payable locally and do not form part of the
                                rental price
                                shown. Prices are displayed by pressing the title of each extra.
                            </p>
                            <div class="equipment-list">
                                <div v-for="extra in bookingExtras" :key="extra.id"
                                    class="equipment-item flex justify-between items-center mt-[2rem] gap-4 p-5 border-[1px] rounded-[12px] border-customPrimaryColor">
                                    <div class="col flex-1">
                                        <span class="text-[1.25rem] text-customPrimaryColor font-bold">
                                            {{ extra.extra_name }}
                                        </span>
                                        <p class="text-customLightGrayColor">
                                            {{ extra.description }}
                                        </p>
                                    </div>
                                    <div class="col flex-[0.5]">
                                        <span class="text-[1.25rem] text-customPrimaryColor font-bold">
                                            {{ formatPrice(extra.price) }} Per day
                                        </span>
                                    </div>
                                    <div class="col flex=[0.5]">
                                        <div class="quantity-counter">
                                            <button @click="decrementQuantity(extra)" class="decrement"
                                                :disabled="extra.quantity === 0">
                                                -
                                            </button>
                                            <input type="number" v-model.number="extra.quantity" class="value" min="0"
                                                max="2" @input="validateQuantity(extra)" />
                                            <button @click="incrementQuantity(extra)" class="increment"
                                                :disabled="extra.quantity >= 2">
                                                +
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="max-w-[200px]">
                            <PrimaryButton class="button-primary py-5 w-full" @click="moveToNextStep">
                                Continue payment
                            </PrimaryButton>
                        </div>
                    </div>
                </div>


                <div class="column w-[65%] flex flex-col gap-10" v-if="currentStep === 2">
                    <h4 class="text-[2rem] font-medium">Driver Info</h4>
                    <div class="free_cancellation p-5 bg-[#0099001A] border-[#009900] rounded-[8px] border-[1px]">
                        <p class="text-[1.15rem] text-[#009900] font-medium">
                            Once your info is submitted, it cannot be changed.
                            Please double-check before proceeding.
                        </p>
                    </div>

                    <div class="flex flex-col gap-10">
                        <form @submit.prevent="storeFormData" class="booking_form flex flex-col justify-between gap-10">
                            <div class="col">
                                <h4 class="text-[2rem] mb-[1.5rem]">
                                    Contact Info
                                </h4>
                                <div class="grid grid-cols-2 gap-[1.5rem]">
                                    <div class="w-full">
                                        <label for="email">Email</label>
                                        <input id="email" type="email" v-model="customer.email" required disabled
                                            class="mt-1 block w-full" />
                                    </div>
                                    <div class="w-full">
                                        <label for="phone">Phone number</label>
                                        <input id="phone" type="phone" v-model="customer.phone" required
                                            class="mt-1 block w-full" />
                                    </div>

                                    <div class="w-full">
                                        <label for="first_name">First Name</label>
                                        <input id="first_name" type="text" v-model="customer.first_name" required
                                            class="mt-1 block w-full" />
                                    </div>
                                    <div class="w-full">
                                        <label for="last_name">Last Name</label>
                                        <input id="last_name" type="text" v-model="customer.last_name" required
                                            class="mt-1 block w-full" />
                                    </div>

                                    <div class="w-full">
                                        <label for="driver_age">Driver Age</label>
                                        <select v-model="customer.driver_age" id="driver_age" class="mt-1 block w-full">
                                            <option value="" disabled>
                                                Select Age
                                            </option>
                                            <option v-for="age in ageRange" :key="age" :value="age">
                                                {{ age }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col">
                                <h4 class="text-[2rem]">Additional Info</h4>
                                <p>
                                    In case of flight delay, we will hold your
                                    car reservation (subject to availability).
                                </p>
                                <div class="formfield mt-[1.5rem] flex justify-between gap-10">
                                    <div class="w-full">
                                        <label for="flight_number">Flight Number</label>
                                        <input id="flight_number" type="text" v-model="customer.flight_number" required
                                            class="mt-1 block w-full" />
                                    </div>
                                </div>
                            </div>

                        </form>
                        <div class="flex flex-col justify-between gap-10">
                            <p>
                                Your booking will be submitted once you go to
                                payment. You can choose your payment method in
                                the next step.
                            </p>
                            <div class="flex justify-between">
                                <button class="button-secondary py-4 w-[20%]" @click="moveToPrevStep">
                                    Back
                                </button>
                                <PrimaryButton type="submit" class="button-primary py-4 w-[20%]"
                                    @click="() => { storeFormData(); moveToNextStep(); }">
                                    Continue to payment
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="column w-[65%] flex flex-col gap-10" v-if="currentStep === 3">
                    <h4 class="text-[2rem] font-medium">Payment Method</h4>
                    <div class="free_cancellation p-5 bg-[#0099001A] border-[#009900] rounded-[8px] border-[1px]">
                        <p class="text-[1.15rem] text-[#009900] font-medium">
                            Free Cancellation before 48hours
                        </p>
                    </div>
                    <h3 class="text-[2rem] font-medium">Pay Now to Lock in this Deal</h3>

                    <div class="stripe-payment-form p-6 border rounded-lg">
                        <p class="text-[1.5rem] mb-6">Choose Card</p>

                        <!-- Payment Method Icons -->
                        <div class="flex gap-4 mb-6">
                            <div
                                class="payment-method-card flex justify-center items-center w-[8rem] h-[2.5rem] rounded-[99px] cursor-pointer">
                                <img :src="mastercard" alt="Mastercard" class="h-full w-full" />
                            </div>
                            <div
                                class="payment-method-card flex justify-center items-center w-[8rem] h-[2.5rem] rounded-[99px] cursor-pointer">
                                <img :src="paypal" alt="PayPal" class="h-full w-full" />
                            </div>

                        </div>

                        <form @submit.prevent="submitBooking" class="flex flex-col gap-6">
                            <!-- Card Number Field -->
                            <div class="w-[60%] flex flex-col gap-5">
                                <div class="form-group">
                                    <label class="block text-sm text-gray-600 mb-2">Card Number</label>
                                    <div id="card-number" class="stripe-element h-12 border rounded-lg px-4 py-2"></div>
                                </div>

                                <!-- Expiry and CVV -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label class="block text-sm text-gray-600 mb-2">Expire Date</label>
                                        <div id="card-expiry" class="stripe-element h-12 border rounded-lg px-4 py-2">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="block text-sm text-gray-600 mb-2">CVV</label>
                                        <div id="card-cvc" class="stripe-element h-12 border rounded-lg px-4 py-2">
                                        </div>
                                    </div>
                                </div>

                                <!-- Terms Checkbox -->
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" id="terms" class="rounded border-gray-300" required />
                                    <label for="terms" class="text-sm">
                                        I have read, understood, and accepted vroome.com
                                        <a href="#" class="text-customDarkBlackColor font-bold">Terms & Conditions</a>
                                        and
                                        <a href="#" class="text-customDarkBlackColor font-bold">Privacy Policy</a>.
                                    </label>
                                </div>

                                <p class="text-gray-600 text-sm">
                                    Your booking will be submitted once you go to payment. You can choose your payment
                                    method in the next step.
                                </p>
                            </div>

                            <!-- Button Group -->
                            <div class="flex justify-between gap-4 mt-4">
                                <button type="button" @click="moveToPrevStep" class="button-secondary w-[20%]">
                                    Back
                                </button>
                                <PrimaryButton type="submit" class=" w-[20%]">
                                    Book Now
                                </PrimaryButton>
                            </div>

                            <div id="card-errors" role="alert" class="text-red-600 text-sm"></div>
                        </form>
                    </div>
                </div>

                <div v-if="isLoading" class="fixed z-50 h-full w-full top-0 left-0 bg-[#0000009e]">
                    <div class="flex justify-center flex-col items-center h-full w-full">
                        <img :src=loader alt="" class="w-[200px]">
                        <p class="text-[white] text-[1.5rem]">Please do not refresh the page..waiting for payment</p>
                    </div>
                </div>

                <div class="column w-[35%]">
                    <div
                        class="rounded-[12px] border-[1px] border-[#153B4F] p-5 sticky top-[2rem] bg-customPrimaryColor text-customPrimaryColor-foreground">
                        <div class="flex items-center justify-between gap-3">
                            <h4>{{ vehicle?.brand }}</h4>
                            <span
                                class="bg-[#f5f5f5] inline-block px-8 py-2 text-center rounded-[40px] text-customPrimaryColor">{{
                                    vehicle?.category.name }}</span>
                        </div>
                        <div class="">
                            <span>Hosted by
                                <span class="vendorName uppercase">{{ vehicle?.user.first_name }}
                                    {{ vehicle?.user.last_name }}</span></span>
                        </div>
                        <div class="car_short_info mt-[1rem] flex gap-3">
                            <div class="features">
                                <span class="text-[1.15rem] capitalize">
                                    {{ vehicle?.transmission }} .
                                    {{ vehicle?.fuel }} .
                                    {{ vehicle?.seating_capacity }} Seats
                                </span>
                            </div>
                        </div>
                        <div class="extra_details flex gap-5 mt-[1rem]">
                            <div class="col flex gap-3">
                                <img :src="walkIcon" alt="" /><span class="text-[1.15rem]">9.3 KM Away</span>
                            </div>
                        </div>

                        <div class="ratings"></div>

                        <div class="location mt-[2rem]">
                            <span class="text-[1.5rem] font-medium mb-[1rem] inline-block">Location</span>
                            <div class="col flex items-start gap-4">
                                <img :src="pickupLocationIcon" alt="" />
                                <div class="flex flex-col gap-1">
                                    <span class="text-[1.25rem] text-medium">{{
                                        vehicle?.location
                                        }}</span><span class="">From: {{ dateFrom }} {{ timeFrom }}</span>
                                </div>
                            </div>
                            <div class="col flex items-start gap-4 mt-[2.5rem]">
                                <img :src="returnLocationIcon" alt="" />
                                <div class="flex flex-col gap-1">
                                    <span class="text-[1.25rem] text-medium">{{
                                        vehicle?.location
                                        }}</span><span class="">To: {{ dateTo }} {{ timeTo }}</span>
                                </div>
                            </div>

                            <div class="pricing py-5 mt-[2rem]">
                                <div class="column flex flex-col justify-between gap-4">
                                    <span class="text-[1.5rem]">Payment Details</span>

                                    <div class="flex justify-between items-center text-[1.15rem]">

                                        <span>Price</span>
                                        <strong class="text-[1.5rem] font-medium">
                                            {{ formatPrice(totalPrice) }}/{{ packageType }} </strong>

                                    </div>
                                    <!-- Selected Plan -->
                                    <div v-if="selectedPlan" class="flex justify-between items-center text-[1.15rem]">
                                        <span>{{
                                            selectedPlan.plan_type
                                            }}</span>
                                        <div>
                                            <strong class="text-[1.5rem] font-medium">{{
                                                formatPrice(selectedPlan.plan_value)
                                            }}</strong>

                                        </div>
                                    </div>
                                    <!-- Selected Extras -->
                                    <!-- In the pricing section -->
                                    <div v-for="extra in bookingExtras" :key="extra.id" v-show="extra.quantity > 0"
                                        class="flex justify-between items-center text-[1.15rem]">
                                        <span>{{ extra.extra_name }}
                                            {{
                                                extra.quantity > 1
                                                    ? `(x${extra.quantity})`
                                                    : ""
                                            }}</span>
                                        <div>
                                            <strong class="text-[1.5rem] font-medium">{{
                                                formatPrice(extra.price * extra.quantity)
                                            }}</strong>

                                        </div>
                                    </div>
                                </div>
                                <!-- Dialog Trigger -->
                                <Dialog>
                                    <DialogTrigger class="underline text-blue-600 mt-4 block cursor-pointer">
                                        View Pricing details
                                    </DialogTrigger>

                                    <DialogContent class="max-w-[600px]">
                                        <DialogHeader>
                                            <DialogTitle>Detailed Pricing Breakdown</DialogTitle>
                                            <DialogDescription>Here is the breakdown of your selected options.
                                            </DialogDescription>
                                        </DialogHeader>

                                        <!-- Pricing Breakdown inside Dialog -->
                                        <div class="flex flex-col gap-4">
                                            
                                            <div class="flex justify-between text-[1.15rem]">
                                                <span>Base Price</span>
                                                <p class="font-medium">{{ formatPrice(totalPrice+discountAmount) }}/{{ packageType }}</p>
                                            </div>

                                            <div v-if="selectedPlan" class="flex justify-between text-[1.15rem]">
                                                <span>Plan: {{ selectedPlan.plan_type }}</span>
                                                <p class="font-medium">{{ formatPrice(selectedPlan.plan_value) }}</p>
                                            </div>

                                            <div v-for="extra in bookingExtras" :key="extra.id"
                                                v-show="extra.quantity > 0" class="flex justify-between text-[1.15rem]">
                                                <span>{{ extra.extra_name }} {{ extra.quantity > 1 ?
                                                    `(x${extra.quantity})` : "" }}</span>
                                                <p class="font-medium">{{ formatPrice(extra.price * extra.quantity) }}</p>
                                            </div>

                                            <div
                                                class="flex justify-between text-[1.25rem] font-bold border-t pt-3 mt-3">
                                                <span>Total (incl. VAT)</span>
                                                <p class="font-medium">{{ formatPrice(calculateTotal+discountAmount) }}</p>
                                            </div>
                                            <div v-if="discountAmount" class="mt-[-1rem] flex justify-between text-[1.15rem]">
                                                <span>Discount</span>
                                                <div class="flex flex-col items-end">
                                                    <p class="border-b-2 mb-1 text-red-500 font-medium">-{{ formatPrice(discountAmount) }}</p>
                                                    <strong class="text-[1.3rem]">{{ formatPrice(calculateTotal) }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </DialogContent>
                                </Dialog>

                                <div
                                    class="column flex justify-between bg-white text-customPrimaryColor p-4 mt-[2rem] rounded-[12px]">
                                    <p class="flex items-center text-[1.15rem]">
                                        Total Payment (incl. VAT)
                                        <img :src="infoIcon" alt="" />
                                    </p>
                                    <span class="text-[1.25rem] font-bold">{{ formatPrice(calculateTotal) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <Footer />
</template>

<style>
.check-list li {
    position: relative;
    list-style-type: none;
    padding-left: 2.5rem;
    margin-bottom: 0.5rem;
    display: inline-block;
}

.check-list li:before {
    content: "";
    display: block;
    position: absolute;
    left: 0;
    top: 0px;
    width: 8px;
    height: 15px;
    border-width: 0 2px 2px 0;
    border-style: solid;
    border-color: #153b4f;
    transform-origin: bottom left;
    transform: rotate(45deg);
}

.quantity-counter {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quantity-counter .value {
    border: none;
    border-radius: 62px;
    margin: 2px 0;
    padding: 4px;
    text-align: center;
    height: 30px;
    width: 70px;
    background-color: #153b4f1a;
}

.quantity-counter .value::-webkit-outer-spin-button,
.quantity-counter .value::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.quantity-counter .increment,
.quantity-counter .decrement {
    border: 0;
    background: transparent;
    cursor: pointer;
    color: #31313b;
    width: 30px;
    font-size: 2rem;
}

.booking_form input,
.booking_form select {
    border: 1px solid #2b2b2b80;
    border-radius: 12px;
    padding: 0.75rem;
}

.booking_form label {
    color: #2b2b2bbf;
}

.stripe-element-container {
    background: white;
    padding: 12px;
    border-radius: 8px;
}

.stripe-card {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 16px;
}

.stripe-element {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 12px;
    background-color: #f9f9f9;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.stripe-card-errors {
    color: #ff3860;
    font-size: 14px;
    margin-bottom: 16px;
}

.stripe-element {
    background-color: white;
    border: 1px solid #E5E7EB;
}

.payment-method-card {
    transition: all 0.2s ease;
}

.payment-method-card:hover {
    border-color: #19304D;
}

/* Style for selected payment method */
.payment-method-card.selected {
    background-color: #19304D;
}
</style>
