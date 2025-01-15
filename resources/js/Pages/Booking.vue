<script setup>
import pickupLocationIcon from "../../assets/pickupLocationIcon.svg";
import returnLocationIcon from "../../assets/returnLocationIconLight.svg";
import infoIcon from "../../assets/WarningCircle.svg";
import { ref, computed  } from "vue";
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import InputError from '@/Components/InputError.vue';
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";


// Plans script

const basicPlanItems = ref([
    "Limited Features",
    "Basic Support",
    "Trial for Premium Features",
    "Community Access",
    "No Commitment",
]);
const exclusivePlanItems = ref([
    "Premium Features",
    "Custom Integrations",
    "Personalised Onboarding",
    "Dedicated Account Manager",
    "Higher API Limits",
]);
// Plans script


//Additional Equipments Script

const additionalEquipmentOptions = ref([
    {
        name: "Infant Seat",
        description: "Suitable for infants up to age 1, 0-9kg in weight",
        price: 150,
        quantity: 0,
    },
    {
        name: "Booster Seat",
        description: "Suitable for infants up to age 4-7, 15-30kg in weight",
        price: 200,
        quantity: 0,
    },
    {
        name: "Additional Driver",
        description:
        "Add additional drivers to drive the rental car. All drivers need to provide valid documentation.",
        price: 300,
        quantity: 0,
    },
    {
        name: "GPS Navigation Device",
        description:
        "GPS navigation device interface and language are usually in the local language.",
        price: 200,
        quantity: 0,
    },
]);
//Additional Equipments Script



// Counter Script
const counterProps = defineProps({
    initialValue: {
        type: Number,
        default: 0,
    },
    min: {
        type: Number,
        default: 0,
    },
    max: {
        type: Number,
        default: 100,
    },
});

const emit = defineEmits(["update:quantity"]);

const quantity = ref(counterProps.initialValue);

const increment = () => {
    if (quantity.value < counterProps.max) {
        quantity.value++;
    }
};

const decrement = () => {
    if (quantity.value > counterProps.min) {
        quantity.value--;
    }
};
// Counter Script


const form = useForm({
  first_name: '',
  last_name: '',
  driver_age: '',
  phone_number: '',
  email: '',
  flight_number: '',
});


// Age selection 

// const driverAge = ref<number | null>(null); 

const ageOptions = computed(() => {
    return Array.from({ length: 100 }, (_, i) => i + 1);
});
// Age selection 


// Multiple step form
const currentStep = ref(1); 

const moveToNextStep = () => {
    currentStep.value++;
};
// Multiple step form

const { props } = usePage();
const vehicle = ref(props.vehicle);
</script>

<template>
    <Head title="Booking" />
    <AuthenticatedHeaderLayout/>
    <main>
        <section>
            <div
                class="full-w-container flex justify-between py-customVerticalSpacing gap-5"
            >
                <div class="column w-[65%] flex flex-col gap-10" v-if="currentStep === 1">
                    <div
                        class="free_cancellation p-5 bg-[#0099001A] border-[#009900] rounded-[8px] border-[1px]"
                    >
                        <p class="text-[1.15rem] text-[#009900] font-medium">
                            Free Cancellation before pick-up (19Nov 2023,
                            12:00PM)
                        </p>
                    </div>

                    <div class="flex flex-col gap-10">
                        <h4 class="text-[2.5rem]">Protection Plan</h4>

                        <!-- Protection Plan -->
                        <div
                            class="protection_plan flex justify-between gap-5"
                        >
                            <div
                                class="col w-[50%] rounded-[20px] border-[1px] border-[#153B4F] p-5 flex flex-col gap-5"
                            >
                                <span class="text-[1.5rem] text-center"
                                    >Free Plan</span
                                >
                                <strong class="text-[3rem] text-center"
                                    >€0</strong
                                >
                                <p
                                    class="text-[1.25rem] text-[#2B2B2B] text-center"
                                >
                                    Access to basic features without any
                                    subscription fee.
                                </p>
                                <button class="button-primary px-5 py-2">
                                    Select
                                </button>
                                <div class="checklist features">
                                    <ul
                                        class="check-list text-center mt-[1rem] inline-flex flex-col items-center w-full gap-3"
                                    >
                                        <li
                                            v-for="(
                                                item, index
                                            ) in basicPlanItems"
                                            :key="index"
                                            class="checklist-item"
                                        >
                                            {{ item }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div
                                class="col w-[50%] rounded-[20px] border-[1px] border-[#153B4F] p-5 flex flex-col gap-5"
                            >
                                <span
                                    class="text-[1.5rem] text-center text-[#016501]"
                                    >Exclusive Plan</span
                                >
                                <strong class="text-[3rem] text-center"
                                    >€100</strong
                                >
                                <p
                                    class="text-[1.25rem] text-[#2B2B2B] text-center"
                                >
                                    Access to basic features without any
                                    subscription fee.
                                </p>
                                <button class="button-primary px-5 py-2">
                                    Select
                                </button>
                                <div class="checklist features">
                                    <ul
                                        class="check-list text-center mt-[1rem] inline-flex flex-col items-center w-full gap-3"
                                    >
                                        <li
                                            v-for="(
                                                item, index
                                            ) in exclusivePlanItems"
                                            :key="index"
                                            class="checklist-item"
                                        >
                                            {{ item }}
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
                                <div
                                    v-for="(
                                        item, index
                                    ) in additionalEquipmentOptions"
                                    :key="index"
                                    class="equipment-item flex justify-between items-center mt-[2rem] gap-4 p-5 border-[1px] rounded-[12px] border-customPrimaryColor"
                                >
                                    <div class="col flex-1">
                                        <span
                                            class="text-[1.25rem] text-customPrimaryColor font-bold"
                                            >{{ item.name }}</span
                                        >
                                        <p class="text-customLightGrayColor">
                                            {{ item.description }}
                                        </p>
                                    </div>
                                    <div class="col flex-[0.5]">
                                        <span
                                            class="text-[1.25rem] text-customPrimaryColor font-bold"
                                            >&euro;{{ item.price }} Per
                                            day</span
                                        >
                                    </div>
                                    <div class="col flex=[0.5]">
                                        <div class="quantity-counter">
                                            <button
                                                @click="item.quantity--"
                                                class="decrement"
                                                :disabled="item.quantity === 0"
                                            >
                                                -
                                            </button>
                                            <input
                                                type="number"
                                                v-model.number="item.quantity"
                                                class="value"
                                                min="0"
                                                max="10"
                                            />
                                            <button
                                                @click="item.quantity++"
                                                class="increment"
                                            >
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
                    <div
                        class="free_cancellation p-5 bg-[#0099001A] border-[#009900] rounded-[8px] border-[1px]"
                    >
                        <p class="text-[1.15rem] text-[#009900] font-medium">
                            Once your info is submitted, it cannot be changed. Please double-check before proceeding.
                        </p>
                    </div>

                    <div class="flex flex-col gap-10">
                        <form
                            method=""
                            class="booking_form flex flex-col justify-between gap-10"
                        >
                            <div class="col">
                                <h4 class="text-[2rem]">Driver Info</h4>
                                <div class="formfield mt-[1.5rem]">
                                    <div class="w-max">
                                        <label for="email">Email</label>
                                      <InputLabel for="email" value="Email" />
                                      <TextInput
                                        id="email"
                                        type="email"
                                        class="mt-1 block w-full"
                                        v-model="form.email"
                                        required
                                      />
                                      <InputError class="mt-2" :message="form.errors.email" />
                                    </div>
                                </div>
                                <div class="formfield mt-[1.5rem] flex justify-between gap-10">
                                    <div class="w-full">
                                        <label for="email">First Name</label>
                                      <InputLabel for="first_name" value="First Name" />
                                      <TextInput
                                        id="first_name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.first_name"
                                        required
                                      />
                                      <InputError class="mt-2" :message="form.errors.first_name" />
                                    </div>
                                    <div class="w-full">
                                        <label for="email">Last Name</label>
                                      <InputLabel for="last_name" value="Last Name" />
                                      <TextInput
                                        id="last_name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.last_name"
                                        required
                                      />
                                      <InputError class="mt-2" :message="form.errors.last_name" />
                                    </div>
                                </div>
                                <div class="formfield mt-[1.5rem]">
                                   <label for="driver_age">Driver Age</label>
                                   <Select id="driver_age" v-model="driverAge" class="mt-1 block w-full">
                                     <option v-for="age in ageOptions" :value="age">{{ age }}</option>
                                   </Select>
                                   <InputError class="mt-2" :message="form.errors.driver_age" />
                                 </div>
                                
                            </div>

                            <div class="col">
                                <h4 class="text-[2rem]">Contact Info</h4>
                                <div class="formfield mt-[1.5rem] flex justify-between gap-10">
                                    <div class="w-full">
                                        <label for="email">Phone Number</label>
                                      <InputLabel for="phone_number" value="Phone Number" />
                                      <TextInput
                                        id="phone_number"
                                        type="phone"
                                        class="mt-1 block w-full"
                                        v-model="form.phone_number"
                                        required
                                      />
                                      <InputError class="mt-2" :message="form.errors.phone_number" />
                                    </div>
                                    
                                    <div class="w-full">
                                        <label for="email">Email address</label>
                                      <InputLabel for="email" value="Email" />
                                      <TextInput
                                        id="email"
                                        type="email"
                                        class="mt-1 block w-full"
                                        v-model="form.email"
                                        required
                                      />
                                      <InputError class="mt-2" :message="form.errors.email" />
                                    </div>
                                </div>
                                
                            </div>

                            <div class="col">
                                <h4 class="text-[2rem]">Additonal Info</h4>
                                <p>In case of flight delay, we will hold your car reservation (subject to availability)</p>
                                <div class="formfield mt-[1.5rem] flex justify-between gap-10">
                                    <div class="w-full">
                                        <label for="flight_number">Flight Number</label>
                                      <InputLabel for="phone_number" value="Flight Number" />
                                      <TextInput
                                        id="flight_number"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.flight_number"
                                        required
                                      />
                                      <InputError class="mt-2" :message="form.errors.flight_number" />
                                    </div>
                                </div>
                                
                            </div>
                        </form>

                        <div class="flex justify-between gap-10">
                            <p>Your booking will be submitted once you go to payment. You can choose your payment method in the next step.</p>
                            <button class="button-primary p-5 w-full" @click="moveToNextStep">
                                Continue payment
                            </button>
                        </div>
                    </div>
                </div>

                <div class="column w-[65%] flex flex-col gap-10" v-if="currentStep === 3">
                    <h4 class="text-[2rem] font-medium">Payment Method</h4>
                    <div
                        class="free_cancellation p-5 bg-[#0099001A] border-[#009900] rounded-[8px] border-[1px]"
                    >
                        <p class="text-[1.15rem] text-[#009900] font-medium">
                            Free Cancellation before 48hours
                        </p>
                    </div>

                    <div>
                        <span class="text-[1.75rem] font-medium">Pay Now to Lock in this Deal</span>
                        <div class="flex justify-between items-center mt-[1.5rem]">
                            <span class="text-[1.25rem] font-medium">Pay Now</span>
                            <strong class="text-[1.25rem] ">€150</strong>
                        </div>
                        <div class="choose_card">
                            <span>Choose Card</span>
                        </div>
                    </div>
                </div>

                <div class="column w-[35%]">
                    <div
                        class="rounded-[12px] border-[1px] border-[#153B4F] p-5 sticky top-[2rem] bg-customPrimaryColor text-customPrimaryColor-foreground"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <h4>{{ vehicle?.brand }}</h4>
                            <span
                                class="bg-[#f5f5f5] inline-block px-8 py-2 text-center rounded-[40px] text-customPrimaryColor"
                                >SUV</span
                            >
                        </div>
                        <div class="">
                            <span
                                >Hosted by
                                <span class="vendorName uppercase"
                                    >{{ vehicle?.user.first_name }}
                                    {{ vehicle?.user.last_name }}</span
                                ></span
                            >
                        </div>
                        <div class="car_short_info mt-[1rem] flex gap-3">
                            <img :src="carIcon" alt="" />
                            <div class="features">
                                <span class="text-[1.15rem] capitalize"
                                    >
                                    {{ vehicle?.transmission }} .
                                        {{ vehicle?.fuel }} .
                                        {{ vehicle?.seating_capacity }} Seats
                                    </span
                                >
                            </div>
                        </div>
                        <div class="extra_details flex gap-5 mt-[1rem]">
                            <div class="col flex gap-3">
                                <img :src="walkIcon" alt="" /><span
                                    class="text-[1.15rem]"
                                    >9.3 KM Away</span
                                >
                            </div>
                        </div>

                        <div class="ratings"></div>

                        <div class="location mt-[2rem]">
                            <span
                                class="text-[1.5rem] font-medium mb-[1rem] inline-block"
                                >Location</span
                            >
                            <div class="col flex items-start gap-4">
                                <img :src="pickupLocationIcon" alt="" />
                                <div class="flex flex-col gap-1">
                                    <span class="text-[1.25rem] text-medium"
                                        >{{ vehicle?.location }}</span
                                    ><span class="">{{ vehicle?.created_at }}</span>
                                </div>
                            </div>
                            <div class="col flex items-start gap-4 mt-[2.5rem]">
                                <img :src="returnLocationIcon" alt="" />
                                <div class="flex flex-col gap-1">
                                    <span class="text-[1.25rem] text-medium"
                                        >{{ vehicle?.location }}</span
                                    ><span class="">{{ vehicle?.created_at }}</span>
                                </div>
                            </div>

                            <div class="pricing py-5 mt-[2rem]">
                                <div
                                    class="column flex flex-col justify-between gap-4"
                                >
                                    <span class="text-[1.5rem]"
                                        >Payment Details</span
                                    >

                                    <div
                                        class="flex justify-between items-center text-[1.15rem]"
                                    >
                                        <span>Price Per Day</span>
                                        <div>
                                            <strong
                                                class="text-[1.5rem] font-medium"
                                                >€{{
                                                    vehicle?.price_per_day
                                                }}</strong
                                            >
                                            <span>/day</span>
                                        </div>
                                    </div>
                                    <div
                                        class="flex justify-between items-center text-[1.15rem]"
                                    >
                                        <span>Infant Seat</span>
                                        <div>
                                            <strong
                                                class="text-[1.5rem] font-medium"
                                                >€50</strong
                                            >
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
                                    class="column flex justify-between bg-white text-customPrimaryColor p-4 mt-[2rem] rounded-[12px]"
                                >
                                    <p class="flex items-center text-[1.15rem]">Total Payment (incl. VAT) <img :src="infoIcon" alt="" /></p>
                                    <span class="text-[1.25rem] font-bold">€450</span>
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
.booking_form select{
    border: 1px solid #2B2B2B80;
    border-radius: 12px;
    padding: 0.75rem;
}
.booking_form label{
    color:#2B2B2BBF;
}
</style>
