<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import { ref, watch, computed, onMounted } from "vue";
import { Button } from "@/Components/ui/button";
import { Toaster } from "@/Components/ui/sonner";
import { toast } from "vue-sonner";
import registerBg from "../../../assets/registerbgimage.jpg";
import {
    Stepper,
    StepperItem,
    StepperTrigger,
    StepperSeparator,
    StepperTitle,
    StepperDescription,
} from "@/Components/ui/stepper";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";

import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const stepIndex = ref(1);
const showPassword = ref(false);
const showconfirmPassword = ref(false);
const isRegistering = ref(false);
const isValidating = ref(false);
const page = usePage();

const _t = (section, key) => {
    const { props } = page;
    if (props.translations && props.translations[section] && props.translations[section][key]) {
        return props.translations[section][key];
    }
    return key; // Return key as fallback
};

const steps = [
    {
        step: 1,
        title: "Personal Information", // Will be translated in the template
        description: "First & Last Name",
        fields: ["first_name", "last_name", "date_of_birth"],
    },
    {
        step: 2,
        title: "Contact Details",
        description: "Phone & Email",
        fields: ["phone", "email"],
    },
    {
        step: 3,
        title: "Address",
        description: "Location Information",
        fields: ["address", "postcode", "city", "country"],
    },
    {
        step: 4,
        title: "Create Password",
        description: "Setup Account",
        fields: ["password", "password_confirmation"],
    },
];

const form = useForm({
    first_name: "",
    last_name: "",
    date_of_birth: "",
    phone: "",
    phone_code: "",
    email: "",
    address: "",
    postcode: "",
    city: "",
    country: "",
    password: "",
    password_confirmation: "",
    currency: "EUR",
});

const isPasswordValid = computed(() => {
    return form.password.length >= 8;
});

const passwordsMatch = computed(() => {
    return (
        form.password === form.password_confirmation &&
        form.password_confirmation.length > 0
    );
});

const dateOfBirth = ref(null);

const currentStepTitle = computed(() => {
    return _t('registerUser', `step${stepIndex.value}_title`);
});

const isStepValid = computed(() => {
    const currentStepFields = steps[stepIndex.value - 1].fields;
    return currentStepFields.every((field) => {
        const value = form[field];
        if (field === "phone") {
            return (
                value !== null &&
                value !== undefined &&
                value.trim() !== "" &&
                value.length >= 7
            );
        }
        if (field === "phone_code") {
            return value !== null && value !== undefined && value.trim() !== "";
        }
        if (field === "password") {
            return (
                value !== null &&
                value !== undefined &&
                value.trim() !== "" &&
                value.length >= 8
            );
        }
        if (field === "password_confirmation") {
            return (
                value !== null &&
                value !== undefined &&
                value.trim() !== "" &&
                value === form.password
            );
        }
        if (field === "date_of_birth") {
            return dateOfBirth.value !== null;
        }
        return value !== null && value !== undefined && value.trim() !== "";
    });
});

const isPreviousStepsValid = computed(() => {
    for (let i = 1; i < stepIndex.value; i++) {
        const stepFields = steps[i - 1].fields;
        if (
            !stepFields.every((field) => {
                const value = form[field];
                return (
                    value !== null && value !== undefined && value.trim() !== ""
                );
            })
        ) {
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
        if (
            !stepFields.every((field) => {
                const value = form[field];
                return (
                    value !== null && value !== undefined && value.trim() !== ""
                );
            })
        ) {
            return false;
        }
    }
    return true;
};

const isStepCompleted = (stepNumber) => {
    return stepNumber < stepIndex.value;
};

const handleStepChange = (newStep) => {
    if (canNavigateTo(newStep)) {
        stepIndex.value = newStep;
    }
};

// Update nextStep to send both fields
const nextStep = () => {
    if (stepIndex.value < steps.length && isStepValid.value) {
        if (stepIndex.value === 2) {
            // Clear existing errors before validation
            form.clearErrors();
            isValidating.value = true;

            axios
                .post(route("validate-contact"), {
                    email: form.email,
                    phone: form.phone,
                    phone_code: form.phone_code,
                })
                .then(() => {
                    stepIndex.value++;
                    isValidating.value = false;
                })
                .catch((error) => {
                    isValidating.value = false;
                    if (error.response && error.response.data.errors) {
                        // Only set errors if they come from the server
                        if (error.response.data.errors.email) {
                            form.errors.email =
                                error.response.data.errors.email[0];
                        }
                        if (error.response.data.errors.phone) {
                            form.errors.phone =
                                error.response.data.errors.phone[0];
                        }
                        if (error.response.data.errors.phone_code) {
                            form.errors.phone_code =
                                error.response.data.errors.phone_code[0];
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
        form.clearErrors();
        stepIndex.value--;
    }
};

const submit = () => {
    if (!canNavigateNext.value) return;

    isRegistering.value = true; // Set loading state

    if (dateOfBirth.value) {
        form.date_of_birth = dateOfBirth.value.toISOString().split('T')[0];
    }

    form.post(route("register"), {
        onSuccess: () => {
            // Show toast success message
            toast.success(_t('registerUser', 'account_created_success') || 'Account created successfully');

            const returnToUrl = sessionStorage.getItem('returnToUrl');
            if (returnToUrl) {
                sessionStorage.removeItem('returnToUrl');
                window.location.href = returnToUrl;
            }
        },
        onFinish: () => {
            form.reset("password", "password_confirmation");
            isRegistering.value = false; // Reset loading state
        },
        onError: (errors) => {
            Object.keys(errors).forEach((field) => {
                form.errors[field] = errors[field];
            });
            isRegistering.value = false; // Reset loading state on error
            // Show toast error message
            toast.error(_t('registerUser', 'registration_failed') || 'Registration failed. Please check the form.');
        },
    });
};

const countries = ref([]);
const currencies = ref([]);

const selectedCurrency = computed({
    get() {
        return form.currency || '';
    },
    set(newValue) {
        form.currency = newValue;
    }
});

const selectedPhoneCode = ref("");
const phoneNumber = ref("");
// Update the fullPhone computed property to combine code and number
const fullPhone = computed({
    get: () => `${form.phone_code} ${form.phone}`,
    set: (value) => {
        // This won't be needed for display-only purposes
    },
});
const selectedCountryCode = computed(() => {
    if (!form.phone_code || !countries.value || countries.value.length === 0)
        return null;
    const country = countries.value.find(
        (c) => c.phone_code === form.phone_code
    );
    return country ? country.code : null;
});

// Update watch to handle phone_code properly
watch(selectedPhoneCode, (newCode) => {
    form.phone_code = newCode;
});


const fetchCountries = async () => {
    try {
        const response = await fetch("/countries.json"); // Ensure it's in /public
        countries.value = await response.json();
    } catch (error) {
        console.error("Error loading countries:", error);
    }
};

const fetchCurrencies = async () => {
    try {
        const response = await fetch("/currency.json");
        currencies.value = await response.json();
    } catch (error) {
        console.error("Error loading currencies:", error);
    }
};

onMounted(fetchCountries);
onMounted(fetchCurrencies);

// Get flag URL
const getFlagUrl = (countryCode) => {
    return `https://flagcdn.com/w40/${countryCode.toLowerCase()}.png`;
};

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
    const eighteenYearsAgo = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
    return eighteenYearsAgo.toISOString().split('T')[0];
});

// Watch dateOfBirth to update form.date_of_birth
watch(dateOfBirth, (newValue) => {
    if (newValue) {
        form.date_of_birth = newValue.toISOString().split('T')[0];
    } else {
        form.date_of_birth = '';
    }
});
</script>

<template>

    <Head>
        <meta name="robots" content="noindex, nofollow">
        <title>Register</title>
    </Head>

    <div class="flex justify-center items-center register py-customVerticalSpacing min-h-[100vh]"
        :style="{ '--register-hero-image': `url('${registerBg}')` }">
        <div class="register-shell">
            <div class="register-left">
                <div class="register-card">
                    <Stepper v-model="stepIndex" class="block w-full">
                    <form @submit.prevent="submit">
                    <div
                        class="flex w-full flex-start gap-2 mb-[4rem] max-[768px]:mb-[2rem] max-[768px]:gap-1 max-[768px]:mt-[2rem] register-stepper-track">
                        <StepperItem v-for="(step, index) in steps" :key="step.step" v-slot="{ state }"
                            class="relative flex w-full flex-col items-center justify-center register-stepper-item" :step="step.step"
                            @click="handleStepChange(step.step)">
                            <StepperSeparator v-if="
                                step.step !== steps[steps.length - 1].step
                            "
                                class="absolute left-[calc(50%+20px)] right-[calc(-50%+10px)] top-5 max-[768px]:top-4 block h-0.5 shrink-0 rounded-full bg-muted group-data-[state=completed]:bg-primary max-[768px]:left-[calc(50%+15px)] max-[768px]:right-[calc(-50%+5px)]" />

                            <StepperTrigger as-child>
                                <Button :variant="state === 'completed' ||
                                    state === 'active'
                                    ? 'default'
                                    : 'outline'
                                    " size="icon"
                                    class="z-10 rounded-full shrink-0 max-[768px]:size-8 flex items-center justify-center relative stepper-button"
                                    :class="[
                                        state === 'active' &&
                                        'ring-2 ring-ring ring-offset-2 ring-offset-background stepper-active',
                                        !canNavigateTo(step.step) &&
                                        'opacity-50 cursor-not-allowed',
                                        isStepCompleted(step.step) && 'bg-green-500 text-white border-green-500',
                                        isStepCompleted(step.step) && state !== 'active' && '!bg-green-500'
                                    ]" :disabled="!canNavigateTo(step.step)">
                                    <!-- Show checkmark for completed steps -->
                                    <svg v-if="isStepCompleted(step.step) && state !== 'active'" class="w-5 h-5"
                                        fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <!-- Show number for active/inactive steps -->
                                    <span v-else class="text-sm font-medium">{{
                                        index + 1
                                        }}</span>
                                </Button>
                            </StepperTrigger>

                            <div class="mt-5 flex flex-col items-center text-center">
                                <StepperTitle :class="[
                                    state === 'active' && 'text-primary',
                                    state === 'active' && 'stepper-title-active'
                                ]" class="text-sm font-semibold transition lg:text-base max-[768px]:text-[0.65rem] stepper-title">
                                    {{ _t('registerUser', 'step' + (index + 1) + '_title') }}
                                </StepperTitle>
                                <!-- <StepperDescription :class="[
                                    state === 'active' && 'text-primary',
                                ]"
                                    class="sr-only text-xs text-muted-foreground transition md:not-sr-only lg:text-sm max-[768px]:sr-only">
                                    {{ _t('registerUser', 'step' + (index + 1) + '_description').split('.')[0] }}
                                </StepperDescription> -->
                            </div>
                        </StepperItem>
                    </div>
                    <div class="stepper-mobile-title">{{ currentStepTitle }}</div>

                    <!-- Personal Information -->
                    <div v-if="stepIndex === 1">
                        <span
                            class="text-[3rem] max-[768px]:text-[2rem] text-center block font-medium text-customDarkBlackColor">
                            {{ _t('registerUser', 'step1_title') }}
                        </span>
                        <p
                            class="text-center mb-[3rem] max-[768px]:mb-[1.5rem] text-customLightGrayColor font-medium max-[768px]:text-sm">
                            {{ _t('registerUser', 'step1_description') }}
                        </p>
                        <div class="grid grid-cols-2 max-[768px]:grid-cols-1 gap-5 max-[768px]:gap-3">
                            <div class="column w-full">
                                <InputLabel for="first_name" :value="_t('registerUser', 'first_name_label')" />
                                <TextInput id="first_name" type="text" v-model="form.first_name" required autofocus
                                    autocomplete="given-name" class="w-full" />
                                <InputError class="mt-2" :message="form.errors.first_name" />
                            </div>

                            <div class="column w-full">
                                <InputLabel for="last_name" :value="_t('registerUser', 'last_name_label')" />
                                <TextInput id="last_name" type="text" v-model="form.last_name" required
                                    autocomplete="family-name" class="w-full" />
                                <InputError class="mt-2" :message="form.errors.last_name" />
                            </div>

                            <div class="column w-full col-span-2 max-[768px]:col-span-1">
                                <InputLabel for="date_of_birth" :value="_t('registerUser', 'date_of_birth_label')" />
                                <VueDatePicker v-model="dateOfBirth" :enable-time-picker="false" uid="date-of-birth"
                                    auto-apply :placeholder="_t('registerUser', 'select_date_of_birth_placeholder')"
                                    class="w-full" :max-date="minimumDateOfBirth" :start-date="minimumDateOfBirth" />
                                <small class="text-gray-500 mt-1 block">{{ _t('registerUser', 'age_requirement')
                                    }}</small>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Details -->
                    <div v-if="stepIndex === 2">
                        <span
                            class="text-[3rem] max-[768px]:text-[2rem] text-center block font-medium text-customDarkBlackColor">
                            {{ _t('registerUser', 'step2_title') }}
                        </span>
                        <p
                            class="text-center mb-[3rem] max-[768px]:mb-[1.5rem] text-customLightGrayColor font-medium max-[768px]:text-sm">
                            {{ _t('registerUser', 'step2_description') }}
                        </p>
                        <div class="grid grid-cols-1 gap-5 max-[768px]:gap-3">
                            <div>
                                <div class="flex items-end">
                                    <div class="w-[8rem]">
                                        <InputLabel for="phone" class="whitespace-nowrap"
                                            :value="_t('registerUser', 'phone_number_label')" />
                                        <Select v-model="form.phone_code">
                                            <SelectTrigger
                                                class="w-full p-[1.75rem] border-customLightGrayColor bg-customPrimaryColor text-white rounded-[12px] !rounded-r-none border-r-0">
                                                <div class="flex items-center">
                                                    <img v-if="
                                                        selectedCountryCode
                                                    " :src="getFlagUrl(
                                                        selectedCountryCode
                                                    )
                                                        " alt="Country Flag" class="mr-2 w-6 h-4 rounded" />
                                                    <SelectValue placeholder="Select Code">
                                                        <!-- Show only the phone code when selected -->
                                                        {{ form.phone_code }}
                                                    </SelectValue>
                                                </div>
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectGroup>
                                                    <SelectLabel>Phone Code</SelectLabel>
                                                    <SelectItem v-for="country in countries" :key="country.phone_code
                                                        " :value="country.phone_code
                                                            " class="flex items-center">
                                                        <div class="flex items-center w-full">
                                                            <img :src="getFlagUrl(
                                                                country.code
                                                            )
                                                                " alt="Country Flag" class="mr-2 w-6 h-4 rounded" />
                                                            <span>{{
                                                                country.name
                                                            }}
                                                                ({{
                                                                    country.phone_code
                                                                }})</span>
                                                        </div>
                                                    </SelectItem>
                                                </SelectGroup>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div class="column w-full">
                                        <TextInput id="phone" type="text" v-model="form.phone" required
                                            class="w-full !rounded-l-none"
                                            :placeholder="_t('registerUser', 'enter_phone_number_placeholder')" />
                                    </div>
                                </div>
                                <!-- Display full phone number -->
                                <div class="mt-2 text-sm text-gray-500" v-if="form.phone_code && form.phone">
                                    {{ _t('registerUser', 'full_number_label') }}: {{ fullPhone }}
                                </div>
                                <InputError class="mt-2" :message="form.errors.phone" />
                                <InputError class="mt-2" :message="form.errors.phone_code" />
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
                            {{ _t('registerUser', 'step3_title') }}
                        </span>
                        <p
                            class="text-center mb-[3rem] max-[768px]:mb-[1.5rem] text-customLightGrayColor font-medium max-[768px]:text-sm">
                            {{ _t('registerUser', 'step3_description') }}
                        </p>
                        <div class="grid grid-cols-2 max-[768px]:grid-cols-1 gap-5 max-[768px]:gap-3">
                            <div class="column w-full col-span-2 max-[768px]:col-span-1">
                                <InputLabel for="address" :value="_t('registerUser', 'address_label')" />
                                <TextInput id="address" type="text" v-model="form.address" required class="w-full" />
                                <!-- <InputError class="mt-2" :message="form.errors.address" /> -->
                            </div>

                            <div class="column w-full">
                                <InputLabel for="postcode" :value="_t('registerUser', 'postcode_label')" />
                                <TextInput id="postcode" type="text" v-model="form.postcode" required class="w-full" />
                                <!-- <InputError class="mt-2" :message="form.errors.postcode" /> -->
                            </div>

                            <div class="column w-full">
                                <InputLabel for="city" :value="_t('registerUser', 'city_label')" />
                                <TextInput id="city" type="text" v-model="form.city" required class="w-full" />
                                <!-- <InputError class="mt-2" :message="form.errors.city" /> -->
                            </div>

                            <div class="column w-full col-span-2 max-[768px]:col-span-1 relative">
                                <InputLabel for="country" :value="_t('registerUser', 'country_label')" class="mb-1" />
                                <Select v-model="form.country">
                                    <SelectTrigger class="w-full p-[1.7rem] border-customLightGrayColor rounded-[12px]">
                                        <SelectValue :placeholder="_t('registerUser', 'select_country_placeholder')" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectLabel>{{ _t('registerUser', 'country_label') }}</SelectLabel>
                                            <SelectItem v-for="country in countries" :key="country.code"
                                                :value="country.code">
                                                <div class="flex items-center">
                                                    <img :src="getFlagUrl(
                                                        country.code
                                                    )
                                                        " alt="Country Flag" class="mr-2 w-6 h-4 rounded" />
                                                    {{ country.name }}
                                                </div>
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <!-- <InputError class="mt-2" :message="form.errors.country" /> -->

                                <!-- Dynamic Flag -->
                                <img v-if="form.country" :src="getFlagUrl(form.country)" alt="Country Flag"
                                    class="absolute right-3 top-1/2 transform translate-x-[0%] translate-y-[0%] w-[2.1rem] h-[1.5rem] rounded" />
                            </div>

                            <div class="column w-full col-span-2 max-[768px]:col-span-1">
                                <InputLabel for="currency" :value="_t('registerUser', 'currency_label')" class="mb-1" />
                                <Select v-model="selectedCurrency">
                                    <SelectTrigger class="w-full p-[1.7rem] border-customLightGrayColor rounded-[12px]">
                                        <SelectValue :placeholder="_t('registerUser', 'select_currency_placeholder')" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectLabel>{{ _t('registerUser', 'currency_label') }}</SelectLabel>
                                            <SelectItem v-for="currency in currencies" :key="currency.code" :value="currency.code">
                                                {{ currency.code }} ({{ currency.symbol }})
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </div>

                    <!-- Password -->
                    <div v-if="stepIndex === 4">
                        <span
                            class="text-[3rem] max-[768px]:text-[2rem] text-center block font-medium text-customDarkBlackColor">
                            {{ _t('registerUser', 'step4_title') }}
                        </span>
                        <p
                            class="text-center mb-[3rem] max-[768px]:mb-[1.5rem] text-customLightGrayColor font-medium max-[768px]:text-sm">
                            {{ _t('registerUser', 'step4_description') }}
                        </p>
                        <div class="grid grid-cols-1 gap-5 max-[768px]:gap-3">
                            <div class="column w-full relative">
                                <InputLabel for="password" :value="_t('registerUser', 'password_label')" />
                                <TextInput :type="showPassword ? 'text' : 'password'" id="password" type="password"
                                    v-model="form.password" required autocomplete="new-password" class="w-full" :class="{
                                        'border-red-500':
                                            form.password.length > 0 &&
                                            form.password.length < 8,
                                        'border-green-500':
                                            form.password.length >= 8,
                                    }" />
                                <button type="button" @click="showPassword = !showPassword"
                                    class="absolute right-[1rem] translate-y-[1rem]  font-medium text-customDarkBlackColor text-sm max-[768px]:text-white">
                                    {{ showPassword ? _t('registerUser', 'hide_password') : _t('registerUser',
                                        'show_password') }}
                                </button>
                                <p class="flex justify-end font-medium text-[0.75rem]">{{ _t('registerUser',
                                    'password_length_instruction') }}</p>
                                <p v-if="
                                    form.password.length > 0 &&
                                    form.password.length < 8
                                " class="text-red-500 text-sm mt-1">
                                    {{ _t('registerUser', 'password_length_instruction') }}
                                </p>
                                <p v-else-if="form.password.length >= 8" class="text-green-500 text-sm mt-1">
                                    {{ _t('registerUser', 'password_length_valid') }}
                                </p>
                                <!-- <InputError class="mt-2" :message="form.errors.password" /> -->
                            </div>

                            <div class="column w-full relative">
                                <InputLabel for="password_confirmation"
                                    :value="_t('registerUser', 'confirm_password_label')" />
                                <TextInput :type="showconfirmPassword ? 'text' : 'password'" id="password_confirmation"
                                    type="password" v-model="form.password_confirmation" required
                                    autocomplete="new-password" class="w-full" :class="{
                                        'border-red-500':
                                            form.password_confirmation.length >
                                            0 &&
                                            form.password !==
                                            form.password_confirmation,
                                        'border-green-500':
                                            form.password_confirmation.length >
                                            0 &&
                                            form.password ===
                                            form.password_confirmation,
                                    }" />
                                <button type="button" @click="showconfirmPassword = !showconfirmPassword"
                                    class="absolute right-[1rem] translate-y-[1rem] font-medium text-customDarkBlackColor text-sm max-[768px]:text-white">
                                    {{ showconfirmPassword ? _t('registerUser', 'hide_password') : _t('registerUser',
                                        'show_password') }}
                                </button>
                                <p v-if="
                                    form.password_confirmation.length > 0 &&
                                    form.password !==
                                    form.password_confirmation
                                " class="text-red-500 text-sm mt-1">
                                    {{ _t('registerUser', 'passwords_do_not_match') }}
                                </p>
                                <p v-else-if="
                                    form.password_confirmation.length > 0 &&
                                    form.password ===
                                    form.password_confirmation
                                " class="text-green-500 text-sm mt-1">
                                    {{ _t('registerUser', 'passwords_match') }}
                                </p>
                                <InputError class="mt-2" :message="form.errors.password_confirmation" />
                            </div>

                            <div class="flex items-center justify-end mt-4">
                                <Link :href="route('login')"
                                    class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    {{ _t('registerUser', 'already_registered_link') }}
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div
                        class="flex items-center justify-between mt-[3rem] max-[768px]:mt-[2rem] max-[768px]:flex-col max-[768px]:gap-3">
                        <Button v-if="stepIndex !== 1" variant="outline" size="lg"
                            class="w-[20%] max-[768px]:w-full max-[768px]:order-2 register-secondary-button" @click="prevStep">
                            {{ _t('registerUser', 'back_button') }}
                        </Button>
                        <div class="flex items-center gap-3 max-[768px]:w-full max-[768px]:order-1">
                            <Button v-if="stepIndex !== 4" size="lg" class="w-[100%] register-primary-button" @click="nextStep"
                                :disabled="!isStepValid || isValidating">
                                {{ stepIndex === 2 && isValidating ? 'Validating...' : _t('registerUser',
                                    'continue_button') }}
                            </Button>
                            <Button v-if="stepIndex === 4" class="w-[100%] register-primary-button" :disabled="isRegistering || !isStepValid"
                                @click="submit">
                                <span v-if="isRegistering" class="flex items-center gap-2">
                                    <div
                                        class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin">
                                    </div>
                                    Registering...
                                </span>
                                <span v-else>{{ _t('registerUser', 'register_button') }}</span>
                            </Button>
                        </div>
                    </div>
                    </form>
                </Stepper>
                </div>
            </div>
            <div class="register-divider" v-if="stepIndex === 1">
                <span class="register-divider-text">{{ _t('registerUser', 'register_divider_or') }}</span>
            </div>
            <div class="register-right">
                <transition name="panel-fade" mode="out-in">
                    <div v-if="stepIndex === 1" key="social" class="register-social-panel">
                        <span class="register-social-eyebrow">{{ _t('registerUser', 'register_social_eyebrow') }}</span>
                        <h2 class="register-social-title">{{ _t('registerUser', 'register_social_title') }}</h2>
                        <p class="register-social-subtitle">{{ _t('registerUser', 'register_social_subtitle') }}</p>
                        <div class="social-buttons">
                            <a :href="route('oauth.redirect.global', { locale: page.props.locale, provider: 'google' })"
                                class="social-button">
                                <span class="social-icon" aria-hidden="true">
                                    <svg viewBox="0 0 48 48" class="social-svg">
                                        <path fill="#EA4335" d="M24 9.5c3.54 0 6.73 1.22 9.25 3.6l6.9-6.9C35.7 2.57 30.23 0 24 0 14.62 0 6.53 5.38 2.55 13.22l8.06 6.26C12.5 13.04 17.8 9.5 24 9.5z"/>
                                        <path fill="#4285F4" d="M46.98 24.55c0-1.64-.15-3.22-.43-4.75H24v9.02h12.98c-.56 3.02-2.25 5.58-4.77 7.3l7.32 5.68c4.28-3.95 6.45-9.77 6.45-17.25z"/>
                                        <path fill="#FBBC05" d="M10.61 28.74c-.48-1.45-.76-2.99-.76-4.74 0-1.75.27-3.29.76-4.74l-8.06-6.26C.92 16.24 0 19.9 0 24c0 4.1.92 7.76 2.55 11l8.06-6.26z"/>
                                        <path fill="#34A853" d="M24 48c6.23 0 11.45-2.06 15.27-5.6l-7.32-5.68c-2.02 1.36-4.6 2.16-7.95 2.16-6.2 0-11.5-3.54-13.39-8.29l-8.06 6.26C6.53 42.62 14.62 48 24 48z"/>
                                        <path fill="none" d="M0 0h48v48H0z"/>
                                    </svg>
                                </span>
                                {{ _t('registerUser', 'register_social_google') }}
                            </a>
                            <a :href="route('oauth.redirect.global', { locale: page.props.locale, provider: 'facebook' })"
                                class="social-button">
                                <span class="social-icon" aria-hidden="true">
                                    <svg viewBox="0 0 48 48" class="social-svg">
                                        <path fill="#1877F2" d="M48 24c0 13.26-10.74 24-24 24S0 37.26 0 24 10.74 0 24 0s24 10.74 24 24z"/>
                                        <path fill="#fff" d="M26.67 24.98h5.15l.81-5.3h-5.96v-3.44c0-1.53.75-3.02 3.17-3.02h2.45V8.7s-2.22-.38-4.35-.38c-4.44 0-7.34 2.69-7.34 7.56v3.8h-4.94v5.3h4.94V40h6.07V24.98z"/>
                                    </svg>
                                </span>
                                {{ _t('registerUser', 'register_social_facebook') }}
                            </a>
                        </div>
                        <p class="register-social-note">{{ _t('registerUser', 'register_social_note') }}</p>
                    </div>
                    <div v-else key="info" class="register-info-panel">
                        <span class="register-social-eyebrow">{{ _t('registerUser', 'register_info_eyebrow') }}</span>
                        <h2 class="register-social-title">{{ _t('registerUser', 'register_info_title') }}</h2>
                        <p v-if="stepIndex === 2" class="register-info-text">{{ _t('registerUser', 'register_info_step2') }}</p>
                        <p v-else-if="stepIndex === 3" class="register-info-text">{{ _t('registerUser', 'register_info_step3') }}</p>
                        <p v-else class="register-info-text">{{ _t('registerUser', 'register_info_step4') }}</p>
                        <ul class="register-info-list">
                            <li>{{ _t('registerUser', 'register_info_list_1') }}</li>
                            <li>{{ _t('registerUser', 'register_info_list_2') }}</li>
                            <li>{{ _t('registerUser', 'register_info_list_3') }}</li>
                        </ul>
                    </div>
                </transition>
            </div>
        </div>
    </div>

    <Toaster position="bottom-right" :toastOptions="{
        style: { background: 'black', color: 'white', border: '1px solid #333' }
    }" />

</template>

<style scoped>
.register {
    --register-hero-image: none;
    position: relative;
    overflow: hidden;
    background: linear-gradient(160deg, #f3f6f9, #ffffff 70%);
}

.register::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: var(--register-hero-image);
    background-size: cover;
    background-position: center;
    filter: saturate(1.05) contrast(1.05);
    transform: scale(1.02);
}

.register::after {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 15% 20%, rgba(34, 211, 238, 0.12), transparent 55%),
        radial-gradient(circle at 85% 10%, rgba(124, 179, 204, 0.18), transparent 70%),
        linear-gradient(160deg, rgba(248, 251, 253, 0.85), rgba(255, 255, 255, 0.7) 70%);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
}

.register-shell {
    width: min(1440px, 100%);
    display: flex;
    gap: 2.5rem;
    padding: 0 2rem;
    align-items: stretch;
    position: relative;
    z-index: 1;
}

.register-divider {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1c4d66;
    font-weight: 600;
    letter-spacing: 0.18em;
    font-size: 0.75rem;
    text-transform: uppercase;
    position: relative;
}

.register-divider::before,
.register-divider::after {
    content: '';
    width: 1px;
    height: 140px;
    background: linear-gradient(180deg, transparent, rgba(21, 59, 79, 0.35), transparent);
}

.register-divider-text {
    padding: 0 0.75rem;
}

.register-left {
    flex: 1;
}

.register-right {
    flex: 0 0 36%;
    border-radius: 24px;
    background: linear-gradient(160deg, #0f2936, #153b4f 55%, #1c4d66);
    color: #ffffff;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2.5rem 2rem;
    box-shadow: 0 24px 48px rgba(15, 41, 54, 0.25);
}

.register-stepper-track {
    overflow: visible;
}

.stepper-mobile-title {
    display: none;
    text-align: center;
    font-size: 0.85rem;
    font-weight: 600;
    color: #153b4f;
    margin-top: -1.75rem;
    margin-bottom: 2.5rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
}

.register-right::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 20% 20%, rgba(6, 182, 212, 0.3), transparent 55%);
    opacity: 0.6;
}

.register-social-panel {
    position: relative;
    z-index: 1;
    text-align: center;
}

.register-social-eyebrow {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.2em;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.7);
}

.register-social-title {
    margin-top: 0.75rem;
    font-size: 1.8rem;
    font-weight: 600;
    color: #ffffff;
}

.register-social-subtitle {
    margin-top: 0.75rem;
    color: rgba(255, 255, 255, 0.75);
    font-size: 0.95rem;
}

.register-social-note {
    margin-top: 1.5rem;
    color: rgba(255, 255, 255, 0.65);
    font-size: 0.85rem;
}

.register-info-panel {
    text-align: left;
}

.register-info-text {
    margin-top: 1rem;
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.95rem;
    line-height: 1.6;
}

.register-info-list {
    margin-top: 1.75rem;
    display: grid;
    gap: 0.75rem;
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
}

.register-info-list li {
    position: relative;
    padding-left: 1.25rem;
}

.register-info-list li::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0.55rem;
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 999px;
    background: rgba(6, 182, 212, 0.7);
}

.panel-fade-enter-active,
.panel-fade-leave-active {
    transition: opacity 200ms ease, transform 200ms ease;
}

.panel-fade-enter-from,
.panel-fade-leave-to {
    opacity: 0;
    transform: translateY(10px);
}

.register-card {
    width: 100%;
    padding: 3rem 3.5rem 3.5rem;
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.86);
    border: 1px solid rgba(176, 212, 230, 0.6);
    box-shadow: 0 18px 36px rgba(21, 59, 79, 0.12);
    backdrop-filter: blur(12px);
}

.register-primary-button {
    background: linear-gradient(135deg, #153b4f, #245f7d);
    color: #ffffff;
    border: none;
    border-radius: 14px;
    box-shadow: 0 12px 24px rgba(21, 59, 79, 0.2);
    transition: all 150ms ease;
}

.register-primary-button:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 18px 32px rgba(21, 59, 79, 0.25);
}

.register-secondary-button {
    border: 1px solid rgba(21, 59, 79, 0.3);
    color: #153b4f;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.7);
}

.register-secondary-button:hover {
    background: rgba(21, 59, 79, 0.06);
}

.social-buttons {
    display: grid;
    gap: 0.9rem;
    margin-top: 2rem;
}

.social-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    padding: 0.85rem 1rem;
    border-radius: 14px;
    border: 1px solid rgba(255, 255, 255, 0.25);
    background: rgba(255, 255, 255, 0.08);
    color: #ffffff;
    font-weight: 600;
    transition: transform 150ms ease, box-shadow 150ms ease, border-color 150ms ease;
    cursor: pointer;
}

.social-button:hover {
    transform: translateY(-1px);
    border-color: rgba(255, 255, 255, 0.5);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2);
}

.social-button:focus-visible {
    outline: 2px solid rgba(255, 255, 255, 0.7);
    outline-offset: 2px;
}

.social-icon {
    width: 28px;
    height: 28px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #ffffff;
}

.social-svg {
    width: 18px;
    height: 18px;
    display: block;
}

.loader-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.register label {
    margin-bottom: 0.5rem;
    color: #1c4d66;
}

.register input {
    border: 1px solid rgba(21, 59, 79, 0.18);
    border-radius: 14px;
    padding: 1rem 1.1rem;
    background: rgba(255, 255, 255, 0.9);
    transition: all 150ms ease;
}

.register input:focus {
    outline: none;
    border-color: #06b6d4;
    box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.2);
    background: #ffffff;
}

.register .button-secondary {
    background-color: transparent;
    border: 1px solid #2b2b2b80;
    color: #2b2b2b;
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

:deep(.dp__input) {
    padding: 1rem 1rem 1rem 2.5rem;
    border-radius: 14px;
    border: 1px solid rgba(21, 59, 79, 0.18);
    width: 100%;
    background: rgba(255, 255, 255, 0.9);
}

:deep(.dp__menu) {
    border-radius: 0.5rem;
    padding: 0.5rem;
    z-index: 50;
}

:deep(.border-customLightGrayColor svg) {
    display: none !important;
}

/* Ripple effect for active stepper button - positioned outside */
.stepper-button {
    position: relative;
}

.stepper-active::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(21, 59, 79, 0.2);
    transform: translate(-50%, -50%);
    animation: ripple 2s ease-out infinite;
    z-index: -1;
}

@media screen and (max-width: 1024px) {
    .register-shell {
        flex-direction: column;
        padding: 0 1.5rem;
    }

    .register-divider {
        order: 2;
        width: 100%;
        margin: 1.5rem 0;
    }

    .register-divider::before,
    .register-divider::after {
        width: 120px;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(21, 59, 79, 0.35), transparent);
    }

    .register-left {
        order: 1;
    }

    .register-right {
        order: 3;
    }

    .register-right {
        flex: 0 0 auto;
        width: 100%;
        order: 2;
    }

    .register-left {
        order: 1;
    }

    .register-card {
        padding: 2.5rem 2.5rem 3rem;
    }
}

.stepper-active::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(21, 59, 79, 0.1);
    transform: translate(-50%, -50%);
    animation: ripple 2s ease-out infinite 0.5s;
    z-index: -1;
}

@keyframes ripple {
    0% {
        width: 0;
        height: 0;
        opacity: 1;
    }

    100% {
        width: 120px;
        height: 120px;
        opacity: 0;
    }
}

@media screen and (max-width:768px) {
    .register {
        padding-top: 0;
        padding-bottom: 0;
    }
    .register-shell {
        gap: 1.5rem;
        padding: 0;
    }

    .register-right {
        padding: 2rem 1.5rem;
    }

    .register-left {
        position: relative;
        z-index: 2;
    }

    .register-right {
        position: relative;
        z-index: 1;
    }

    .register-right,
    .social-button {
        border-radius: 0;
    }

    .register-card {
        padding: 1.5rem;
        border-radius: 0;
    }

    :deep(.dp__input) {
        font-size: 0.75rem;
    }

    .register-stepper-track {
        justify-content: space-between;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
    }

    .register-stepper-item {
        flex: 1 1 0;
    }

    .stepper-button {
        width: 30px;
        height: 30px;
    }

    .register-stepper-track .bg-muted {
        display: none;
    }

    .stepper-title {
        display: none;
    }

    .stepper-mobile-title {
        display: block;
        margin-top: 0;
        margin-bottom: 1.5rem;
        font-size: 0.8rem;
    }

    /* Adjust ripple size for mobile */
    .stepper-active::before,
    .stepper-active::after {
        animation-duration: 1.5s;
    }

    @keyframes ripple {
        0% {
            width: 0;
            height: 0;
            opacity: 1;
        }

        100% {
            width: 80px;
            height: 80px;
            opacity: 0;
        }
    }
}
</style>
