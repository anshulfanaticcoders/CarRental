<script setup>
import pickupLocationIcon from "../../assets/pickupLocationIcon.svg";
import returnLocationIcon from "../../assets/returnLocationIconLight.svg";
import infoIcon from "../../assets/WarningCircle.svg";
import { ref, computed, onMounted } from "vue";
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import InputError from '@/Components/InputError.vue';
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import walkIcon from '../../assets/walkingrayIcon.svg'
import InputLabel from "@/Components/InputLabel.vue";


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

// Update your total calculation
const calculateTotal = computed(() => {
    let total = 0;

    // Base price per day
    total += Number(vehicle.value?.price_per_day || 0);

    // Add plan value
    total += Number(selectedPlan.value?.plan_value || 0);

    // Add extras with quantity multiplication
    bookingExtras.value.forEach(extra => {
        if (extra.quantity > 0) {
            total += (Number(extra.price) * Number(extra.quantity));
        }
    });

    return total;
});



const form = useForm({
    first_name: '',
    last_name: '',
    driver_age: '',
    phone_number: '',
    email: '',
    flight_number: '',
});




// Multiple step form
const currentStep = ref(1);

const moveToNextStep = () => {
    currentStep.value++;
};


// Getting the vehicle data from api
const { props } = usePage();
const vehicle = ref(props.vehicle);


// this is for protection Plans
const plans = ref([]);
const selectedPlan = ref(null);
const fetchPlans = async () => {
    try {
        const response = await axios.get("/api/plans");
        plans.value = response.data;
        // Select Free plan by default
        const freePlan = plans.value.find(plan => plan.plan_type === 'Free plan');
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

// this is for Extras
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


// Age selection
const ageRange = computed(() =>
    Array.from({ length: 90 - 21 + 1 }, (_, i) => i + 21)
);



// stripe payment 
</script>

<template>

    <Head title="Booking" />
    <AuthenticatedHeaderLayout />
    <main>
        <section>
            <div class="full-w-container flex justify-between py-customVerticalSpacing gap-5">
                <div class="column w-[65%] flex flex-col gap-10" v-if="currentStep === 1">
                    <div class="free_cancellation p-5 bg-[#0099001A] border-[#009900] rounded-[8px] border-[1px]">
                        <p class="text-[1.15rem] text-[#009900] font-medium">
                            Free Cancellation before pick-up (19Nov 2023,
                            12:00PM)
                        </p>
                    </div>

                    <div class="flex flex-col gap-10">
                        <h4 class="text-[2.5rem]">Protection Plan</h4>

                        <!-- Protection Plan -->
                        <div class="protection_plan flex justify-between gap-5">
                            <div v-for="plan in plans" :key="plan.id"
                                class="col w-[50%] rounded-[20px] border-[1px] border-[#153B4F] p-5 flex flex-col gap-5"
                                :class="{
                                    'border-[#153B4F]': selectedPlan?.id === plan.id,
                                    'bg-[#153B4F0D]': selectedPlan?.id === plan.id
                                }">
                                <span class="text-[1.5rem] text-center"
                                    :class="{ 'text-[#016501]': plan.plan_type === 'Exclusive plan' }">
                                    {{ plan.plan_type }}
                                </span>
                                <strong class="text-[3rem] font-medium text-center">
                                    €{{ plan.plan_value }}
                                </strong>
                                <p class="text-[1.25rem] text-[#2B2B2B] text-center">
                                    Access to basic features without any subscription fee.
                                </p>
                                <button class="button-primary px-5 py-2" @click="selectPlan(plan)"
                                    :class="{ 'bg-[#016501]': selectedPlan?.id === plan.id }">
                                    {{ selectedPlan?.id === plan.id ? 'Selected' : 'Select' }}
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
                                Please note these additional extras are payable
                                locally and do not form part of the rental price
                                shown. Prices are displayed by pressing the
                                title of each extra.
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
                                            €{{ extra.price }} Per day
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

                        <div class="max-w-[300px]">
                            <button class="button-primary p-5 w-full" @click="moveToNextStep">
                                Continue payment
                            </button>
                        </div>
                    </div>
                </div>


                <div class="column w-[65%] flex flex-col gap-10" v-if="currentStep === 2">
                    <h4 class="text-[2rem] font-medium">Driver Info</h4>
                    <div class="free_cancellation p-5 bg-[#0099001A] border-[#009900] rounded-[8px] border-[1px]">
                        <p class="text-[1.15rem] text-[#009900] font-medium">
                            Once your info is submitted, it cannot be changed. Please double-check before proceeding.
                        </p>
                    </div>

                    <div class="flex flex-col gap-10">
                        <form method="" class="booking_form flex flex-col justify-between gap-10">
                            <div class="col">
                                <h4 class="text-[2rem] mb-[1.5rem]">Contact Info</h4>
                                <div class="grid grid-cols-2 gap-[1.5rem]">
                                    <div class="w-full">
                                        <InputLabel for="email" value="Email" />
                                        <TextInput id="email" type="email" class="mt-1 block w-full"
                                            v-model="form.email" required />
                                        <InputError class="mt-2" :message="form.errors.email" />
                                    </div>
                                    <div class="w-full">
                                        <InputLabel for="phone" value="Phone number" />
                                        <TextInput id="phone" type="phone" class="mt-1 block w-full"
                                            v-model="form.phone" required />
                                        <InputError class="mt-2" :message="form.errors.phone" />
                                    </div>

                                    <div class="w-full">
                                        <InputLabel for="first_name" value="First Name" />
                                        <TextInput id="first_name" type="text" class="mt-1 block w-full"
                                            v-model="form.first_name" required />
                                        <InputError class="mt-2" :message="form.errors.first_name" />
                                    </div>
                                    <div class="w-full">
                                        <InputLabel for="last_name" value="Last Name" />
                                        <TextInput id="last_name" type="text" class="mt-1 block w-full"
                                            v-model="form.last_name" required />
                                        <InputError class="mt-2" :message="form.errors.last_name" />
                                    </div>

                                    <div class="w-full">
                                        <InputLabel for="driver_age" value="Driver Age" />
                                        <Select id="driver_age" v-model="driverAge" class="mt-1 block w-full">
                                            <option value="" disabled>Select Age</option>
                                            <option v-for="age in ageRange" :key="age" :value="age">{{ age }}</option>
                                        </Select>
                                        <InputError class="mt-2" :message="form.errors.driver_age" />
                                    </div>
                                </div>

                            </div>

                            <div class="col">
                                <h4 class="text-[2rem]">Additonal Info</h4>
                                <p>In case of flight delay, we will hold your car reservation (subject to availability)
                                </p>
                                <div class="formfield mt-[1.5rem] flex justify-between gap-10">
                                    <div class="w-full">
                                        <InputLabel for="phone_number" value="Flight Number" />
                                        <TextInput id="flight_number" type="text" class="mt-1 block w-full"
                                            v-model="form.flight_number" required />
                                        <InputError class="mt-2" :message="form.errors.flight_number" />
                                    </div>
                                </div>

                            </div>
                        </form>

                        <div class="flex justify-between gap-10">
                            <p>Your booking will be submitted once you go to payment. You can choose your payment method
                                in the next step.</p>
                            <button class="button-primary p-5 w-full" @click="moveToNextStep">
                                Continue payment
                            </button>
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

                    <form @submit.prevent="submitBooking">
      <!-- Other form fields -->
  
      <!-- Stripe Payment Form -->
      <div class="stripe-card">
        <div id="card-number" class="stripe-element"></div>
        <div id="card-expiry" class="stripe-element"></div>
        <div id="card-cvc" class="stripe-element"></div>
      </div>
      <div id="card-errors" role="alert" class="stripe-card-errors"></div>
  
      <button type="submit">Book Now</button>
    </form>

                </div>

                <div class="column w-[35%]">
                    <div
                        class="rounded-[12px] border-[1px] border-[#153B4F] p-5 sticky top-[2rem] bg-customPrimaryColor text-customPrimaryColor-foreground">
                        <div class="flex items-center justify-between gap-3">
                            <h4>{{ vehicle?.brand }}</h4>
                            <span
                                class="bg-[#f5f5f5] inline-block px-8 py-2 text-center rounded-[40px] text-customPrimaryColor">SUV</span>
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
                                    <span class="text-[1.25rem] text-medium">{{ vehicle?.location }}</span><span
                                        class="">{{ vehicle?.created_at }}</span>
                                </div>
                            </div>
                            <div class="col flex items-start gap-4 mt-[2.5rem]">
                                <img :src="returnLocationIcon" alt="" />
                                <div class="flex flex-col gap-1">
                                    <span class="text-[1.25rem] text-medium">{{ vehicle?.location }}</span><span
                                        class="">{{ vehicle?.created_at }}</span>
                                </div>
                            </div>

                            <div class="pricing py-5 mt-[2rem]">
                                <div class="column flex flex-col justify-between gap-4">
                                    <span class="text-[1.5rem]">Payment Details</span>

                                    <div class="flex justify-between items-center text-[1.15rem]">
                                        <span>Price Per Day</span>
                                        <div>
                                            <strong class="text-[1.5rem] font-medium">€{{
                                                vehicle?.price_per_day
                                            }}</strong>
                                            <span>/day</span>
                                        </div>
                                    </div>
                                    <!-- Selected Plan -->
                                    <div v-if="selectedPlan" class="flex justify-between items-center text-[1.15rem]">
                                        <span>{{ selectedPlan.plan_type }}</span>
                                        <div>
                                            <strong class="text-[1.5rem] font-medium">€{{ selectedPlan.plan_value
                                                }}</strong>
                                            <span>/day</span>
                                        </div>
                                    </div>
                                    <!-- Selected Extras -->
                                    <!-- In the pricing section -->
                                    <div v-for="extra in bookingExtras" :key="extra.id" v-show="extra.quantity > 0"
                                        class="flex justify-between items-center text-[1.15rem]">
                                        <span>{{ extra.extra_name }} {{ extra.quantity > 1 ? `(x${extra.quantity})` : ''
                                            }}</span>
                                        <div>
                                            <strong class="text-[1.5rem] font-medium">€{{ extra.price * extra.quantity
                                                }}</strong>
                                            <span>/day</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="column mt-[2rem]">
                                    <Link href="" class="underline">
                                    View Pricing details
                                    </Link>
                                </div>
                                <div
                                    class="column flex justify-between bg-white text-customPrimaryColor p-4 mt-[2rem] rounded-[12px]">
                                    <p class="flex items-center text-[1.15rem]">
                                        Total Payment (incl. VAT)
                                        <img :src="infoIcon" alt="" />
                                    </p>
                                    <span class="text-[1.25rem] font-bold">€{{ calculateTotal }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
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
    border: 1px solid #2B2B2B80;
    border-radius: 12px;
    padding: 0.75rem;
}

.booking_form label {
    color: #2B2B2BBF;
}
</style>
