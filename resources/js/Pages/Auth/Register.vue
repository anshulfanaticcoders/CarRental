<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted } from 'vue';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import { Button } from '@/Components/ui/button';
import {
    Stepper,
    StepperItem,
    StepperTrigger,
    StepperSeparator,
    StepperTitle,
    StepperDescription
} from '@/Components/ui/stepper';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/Components/ui/select';

const stepIndex = ref(1);

const steps = [
    {
        step: 1,
        title: 'Personal Information',
        description: 'First & Last Name',
        fields: ['first_name', 'last_name', 'date_of_birth']
    },
    {
        step: 2,
        title: 'Contact Details',
        description: 'Phone & Email',
        fields: ['phone', 'email']
    },
    {
        step: 3,
        title: 'Address',
        description: 'Location Information',
        fields: ['address', 'postcode', 'city', 'country']
    },
    {
        step: 4,
        title: 'Create Password',
        description: 'Setup Account',
        fields: ['password', 'password_confirmation']
    }
];

const form = useForm({
    first_name: '',
    last_name: '',
    date_of_birth: '',
    phone: '',
    email: '',
    address: '',
    postcode: '',
    city: '',
    country: '',
    password: '',
    password_confirmation: '',
});

const isStepValid = computed(() => {
    const currentStepFields = steps[stepIndex.value - 1].fields;
    return currentStepFields.every(field => {
        const value = form[field];
        if (field === "phone") {
            return value !== null && value !== undefined && value.trim() !== "" && value.length >= 7;
        }
        return value !== null && value !== undefined && value.trim() !== '';
    });
});

const isPreviousStepsValid = computed(() => {
    for (let i = 1; i < stepIndex.value; i++) {
        const stepFields = steps[i - 1].fields;
        if (!stepFields.every(field => {
            const value = form[field];
            return value !== null && value !== undefined && value.trim() !== '';
        })) {
            return false;
        }
    }
    return true;
});

const canNavigateNext = computed(() => {
    return isPreviousStepsValid.value && isStepValid.value;
});

const canNavigateTo = (targetStep) => {
    if (targetStep < stepIndex.value) return true;

    for (let i = 1; i < targetStep; i++) {
        const stepFields = steps[i - 1].fields;
        if (!stepFields.every(field => {
            const value = form[field];
            return value !== null && value !== undefined && value.trim() !== '';
        })) {
            return false;
        }
    }
    return true;
};

const handleStepChange = (newStep) => {
    if (canNavigateTo(newStep)) {
        stepIndex.value = newStep;
    }
};

const nextStep = () => {
    if (stepIndex.value < steps.length && isStepValid.value) {
        // Perform backend validation for the email and phone fields on step 2
        if (stepIndex.value === 2) {
            axios.post(route('validate-contact'), { email: form.email, phone: form.phone })
                .then(() => {
                    stepIndex.value++;
                })
                .catch(error => {
                    if (error.response && error.response.data.errors) {
                        if (error.response.data.errors.email) {
                            form.errors.email = error.response.data.errors.email[0];
                        }
                        if (error.response.data.errors.phone) {
                            form.errors.phone = error.response.data.errors.phone[0];
                        }
                    }
                });
        } else {
            stepIndex.value++;
        }
    }
};

const prevStep = () => {
    if (stepIndex.value > 1) {
        stepIndex.value--;
    }
};

const submit = () => {
    if (!canNavigateNext.value) return;

    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
        onError: (errors) => {
            Object.keys(errors).forEach(field => {
                form.errors[field] = errors[field];
            });
        }
    });
};


const countries = ref([]);

const selectedPhoneCode = ref("");
const phoneNumber = ref("");


const fetchCountries = async () => {
    try {
        const response = await fetch('/countries.json'); // Ensure it's in /public
        countries.value = await response.json();
    } catch (error) {
        console.error("Error loading countries:", error);
    }
};

onMounted(fetchCountries);

// Get flag URL
const getFlagUrl = (countryCode) => {
    return `https://flagcdn.com/w40/${countryCode.toLowerCase()}.png`;
};

watch(selectedPhoneCode, (newCode) => {
    form.phone = newCode;
});


onMounted(async () => {
    try {
        const response = await fetch("/countries.json");
        const data = await response.json();
        countries.value = data;
        selectedPhoneCode.value = data[0]?.phone_code || "";
    } catch (error) {
        console.error("Error loading countries:", error);
    }
});


const minimumDateOfBirth = computed(() => {
    const today = new Date();
    const eighteenYearsAgo = new Date(
        today.getFullYear() - 18,
        today.getMonth(),
        today.getDate()
    );

    // Format the date as YYYY-MM-DD for the max attribute
    return eighteenYearsAgo.toISOString().split('T')[0];
});
</script>

<template>
    <AuthenticatedHeaderLayout />

    <Head title="Register" />

    <div class="flex justify-center items-center register py-customVerticalSpacing">
        <div class="w-[55rem] max-w-full mx-auto px-4 max-[768px]:px-[1.5rem]">
            <Stepper v-model="stepIndex" class="block w-full">
                <form @submit.prevent="submit">
                    <div
                        class="flex w-full flex-start gap-2 mb-[4rem] max-[768px]:mb-[2rem] max-[768px]:gap-1 max-[768px]:mt-[2rem]">
                        <StepperItem v-for="(step, index) in steps" :key="step.step" v-slot="{ state }"
                            class="relative flex w-full flex-col items-center justify-center" :step="step.step"
                            @click="handleStepChange(step.step)">
                            <StepperSeparator v-if="step.step !== steps[steps.length - 1].step"
                                class="absolute left-[calc(50%+20px)] right-[calc(-50%+10px)] top-5 max-[768px]:top-4 block h-0.5 shrink-0 rounded-full bg-muted group-data-[state=completed]:bg-primary max-[768px]:left-[calc(50%+15px)] max-[768px]:right-[calc(-50%+5px)]" />

                            <StepperTrigger as-child>
                                <Button :variant="state === 'completed' || state === 'active' ? 'default' : 'outline'"
                                    size="icon"
                                    class="z-10 rounded-full shrink-0 max-[768px]:size-8 flex items-center justify-center"
                                    :class="[
                                        state === 'active' && 'ring-2 ring-ring ring-offset-2 ring-offset-background',
                                        !canNavigateTo(step.step) && 'opacity-50 cursor-not-allowed'
                                    ]" :disabled="!canNavigateTo(step.step)">
                                    <!-- Replace icons with numbers -->
                                    <span class="text-sm font-medium">{{ index + 1 }}</span>
                                </Button>
                            </StepperTrigger>

                            <div class="mt-5 flex flex-col items-center text-center">
                                <StepperTitle :class="[state === 'active' && 'text-primary']"
                                    class="text-sm font-semibold transition lg:text-base max-[768px]:text-[0.65rem]">
                                    {{ step.title }}
                                </StepperTitle>
                                <StepperDescription :class="[state === 'active' && 'text-primary']"
                                    class="sr-only text-xs text-muted-foreground transition md:not-sr-only lg:text-sm max-[768px]:sr-only">
                                    {{ step.description }}
                                </StepperDescription>
                            </div>
                        </StepperItem>
                    </div>

                    <!-- Personal Information -->
                    <div v-if="stepIndex === 1">
                        <span
                            class="text-[3rem] max-[768px]:text-[2rem] text-center block font-medium text-customDarkBlackColor">
                            Personal Information
                        </span>
                        <p
                            class="text-center mb-[3rem] max-[768px]:mb-[1.5rem] text-customLightGrayColor font-medium max-[768px]:text-sm">
                            To get started, tell us about yourself.
                        </p>
                        <div class="grid grid-cols-2 max-[768px]:grid-cols-1 gap-5 max-[768px]:gap-3">
                            <div class="column w-full">
                                <InputLabel for="first_name" value="First Name" />
                                <TextInput id="first_name" type="text" v-model="form.first_name" required autofocus
                                    autocomplete="given-name" class="w-full" />
                                <InputError class="mt-2" :message="form.errors.first_name" />
                            </div>

                            <div class="column w-full">
                                <InputLabel for="last_name" value="Last Name" />
                                <TextInput id="last_name" type="text" v-model="form.last_name" required
                                    autocomplete="family-name" class="w-full" />
                                <InputError class="mt-2" :message="form.errors.last_name" />
                            </div>

                            <div class="column w-full col-span-2 max-[768px]:col-span-1">
                                <InputLabel for="date_of_birth" value="Date of Birth" />
                                <input id="date_of_birth" type="date" v-model="form.date_of_birth" required
                                    :max="minimumDateOfBirth" class="w-full" />
                                <small class="text-gray-500 mt-1 block">You must be at least 18 years old</small>
                                <InputError class="mt-2" :message="form.errors.date_of_birth" />
                            </div>
                        </div>
                    </div>

                    <!-- Contact Details -->
                    <div v-if="stepIndex === 2">
                        <span
                            class="text-[3rem] max-[768px]:text-[2rem] text-center block font-medium text-customDarkBlackColor">
                            Contact Details
                        </span>
                        <p
                            class="text-center mb-[3rem] max-[768px]:mb-[1.5rem] text-customLightGrayColor font-medium max-[768px]:text-sm">
                            We'll send you relevant info about your bookings.
                        </p>
                        <div class="grid grid-cols-1 gap-5 max-[768px]:gap-3">

                            <div>
                            <div class="flex items-end">
                                <div class="w-[8rem]">
                                    <InputLabel for="phone" value="Phone Number" />
                                    <Select v-model="selectedPhoneCode">
                                        <SelectTrigger
                                            class="w-full p-[1.75rem] border-customLightGrayColor bg-customPrimaryColor text-white rounded-[12px] !rounded-r-none border-r-0">
                                            <SelectValue placeholder="Select Code" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectLabel>Phone Code</SelectLabel>
                                                <SelectItem v-for="country in countries" :key="country.phone_code"
                                                    :value="country.phone_code">
                                                    {{ country.phone_code }}
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="column w-full">
                                    <TextInput id="phone" type="text" v-model="form.phone" required
                                        class="w-full !rounded-l-none" />
                                </div>
                            </div>
                            <InputError class="mt-2" :message="form.errors.phone" />
                        </div>

                            <div class="column w-full">
                                <InputLabel for="email" value="Email" />
                                <TextInput id="email" type="email" v-model="form.email" required class="w-full" />
                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>
                        </div>
                    </div>

                    <!-- Address -->
                    <div v-if="stepIndex === 3">
                        <span
                            class="text-[3rem] max-[768px]:text-[2rem] text-center block font-medium text-customDarkBlackColor">
                            Address
                        </span>
                        <p
                            class="text-center mb-[3rem] max-[768px]:mb-[1.5rem] text-customLightGrayColor font-medium max-[768px]:text-sm">
                            We'll use it for billing purposes
                        </p>
                        <div class="grid grid-cols-2 max-[768px]:grid-cols-1 gap-5 max-[768px]:gap-3">
                            <div class="column w-full col-span-2 max-[768px]:col-span-1">
                                <InputLabel for="address" value="Address" />
                                <TextInput id="address" type="text" v-model="form.address" required class="w-full" />
                                <InputError class="mt-2" :message="form.errors.address" />
                            </div>

                            <div class="column w-full">
                                <InputLabel for="postcode" value="Postcode" />
                                <TextInput id="postcode" type="text" v-model="form.postcode" required class="w-full" />
                                <InputError class="mt-2" :message="form.errors.postcode" />
                            </div>

                            <div class="column w-full">
                                <InputLabel for="city" value="City" />
                                <TextInput id="city" type="text" v-model="form.city" required class="w-full" />
                                <InputError class="mt-2" :message="form.errors.city" />
                            </div>

                            <div class="column w-full col-span-2 max-[768px]:col-span-1 relative">
                                <InputLabel for="country" value="Country" class="mb-1" />
                                <Select v-model="form.country">
                                    <SelectTrigger class="w-full p-[1.7rem] border-customLightGrayColor rounded-[12px]">
                                        <SelectValue placeholder="Select Country" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectLabel>Country</SelectLabel>
                                            <SelectItem v-for="country in countries" :key="country.code"
                                                :value="country.code">
                                                {{ country.name }}
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <InputError class="mt-2" :message="form.errors.country" />

                                <!-- Dynamic Flag -->
                                <img v-if="form.country" :src="getFlagUrl(form.country)" alt="Country Flag"
                                    class="absolute right-3 top-1/2 transform translate-x-[0%] translate-y-[-50%] w-[2.1rem] h-[1.5rem] rounded" />
                            </div>
                        </div>
                    </div>

                    <!-- Password -->
                    <div v-if="stepIndex === 4">
                        <span
                            class="text-[3rem] max-[768px]:text-[2rem] text-center block font-medium text-customDarkBlackColor">
                            Create Password
                        </span>
                        <p
                            class="text-center mb-[3rem] max-[768px]:mb-[1.5rem] text-customLightGrayColor font-medium max-[768px]:text-sm">
                            Secure your account
                        </p>
                        <div class="grid grid-cols-1 gap-5 max-[768px]:gap-3">
                            <div class="column w-full">
                                <InputLabel for="password" value="Password" />
                                <TextInput id="password" type="password" v-model="form.password" required
                                    autocomplete="new-password" class="w-full" />
                                <InputError class="mt-2" :message="form.errors.password" />
                            </div>

                            <div class="column w-full">
                                <InputLabel for="password_confirmation" value="Confirm Password" />
                                <TextInput id="password_confirmation" type="password"
                                    v-model="form.password_confirmation" required autocomplete="new-password"
                                    class="w-full" />
                                <InputError class="mt-2" :message="form.errors.password_confirmation" />
                            </div>

                            <div class="flex items-center justify-end mt-4">
                                <Link :href="route('login')"
                                    class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Already registered?
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div
                        class="flex items-center justify-between mt-[3rem] max-[768px]:mt-[2rem] max-[768px]:flex-col max-[768px]:gap-3">
                        <Button v-if="stepIndex !== 1" variant="outline" size="lg"
                            class="w-[30%] max-[768px]:w-full max-[768px]:order-2" @click="prevStep">
                            Back
                        </Button>
                        <div class="flex items-center gap-3 max-[768px]:w-full max-[768px]:order-1">
                            <Button v-if="stepIndex !== 4" size="lg" class="w-[100%]" @click="nextStep"
                                :disabled="!isStepValid">
                                Continue
                            </Button>
                            <PrimaryButton v-if="stepIndex === 4" class="w-[100%]"
                                :disabled="form.processing || !isStepValid" @click="submit">
                                Register
                            </PrimaryButton>
                        </div>
                    </div>
                </form>
            </Stepper>
        </div>
    </div>
</template>

<style>
.register label {
    margin-bottom: 0.5rem;
    color: #2B2B2BBF;
}

.register input {
    border: 1px solid #2B2B2B80;
    border-radius: 12px;
    padding: 1rem;
}

.register .button-secondary {
    background-color: transparent;
    border: 1px solid #2B2B2B80;
    color: #2B2B2B;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
}

.register .bg-muted {
    background-color: rgb(221, 221, 221);
    height: 0.132em;
}

.register .disabled\:opacity-50:disabled {
    opacity: 0.7;
}
</style>