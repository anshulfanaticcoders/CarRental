<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import { ref, watch, computed, onMounted } from "vue";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import { Button } from "@/Components/ui/button";
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
import loaderVariant from '../../../assets/loader-variant.svg';

const stepIndex = ref(1);
const showPassword = ref(false);
const showconfirmPassword = ref(false);
const _t = (section, key) => {
    const { props } = usePage();
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
    currency: "$",
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

            axios
                .post(route("validate-contact"), {
                    email: form.email,
                    phone: form.phone,
                    phone_code: form.phone_code,
                })
                .then(() => {
                    stepIndex.value++;
                })
                .catch((error) => {
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

    if (dateOfBirth.value) {
        form.date_of_birth = dateOfBirth.value.toISOString().split('T')[0];
    }

    form.post(route("register"), {
        onSuccess: () => {
            const returnToUrl = sessionStorage.getItem('returnToUrl');
            if (returnToUrl) {
                sessionStorage.removeItem('returnToUrl');
                window.location.href = returnToUrl;
            }
        },
        onFinish: () => {
            form.reset("password", "password_confirmation");
        },
        onError: (errors) => {
            Object.keys(errors).forEach((field) => {
                form.errors[field] = errors[field];
            });
        },
    });
};

const countries = ref([]);

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

onMounted(fetchCountries);

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
    <AuthenticatedHeaderLayout />

    <div class="flex justify-center items-center register py-customVerticalSpacing">
        <div class="w-[60rem] max-w-full mx-auto px-4 max-[768px]:px-[1.5rem]">
            <Stepper v-model="stepIndex" class="block w-full">
                <form @submit.prevent="submit">
                    <div
                        class="flex w-full flex-start gap-2 mb-[4rem] max-[768px]:mb-[2rem] max-[768px]:gap-1 max-[768px]:mt-[2rem]">
                        <StepperItem v-for="(step, index) in steps" :key="step.step" v-slot="{ state }"
                            class="relative flex w-full flex-col items-center justify-center" :step="step.step"
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
                                    class="z-10 rounded-full shrink-0 max-[768px]:size-8 flex items-center justify-center"
                                    :class="[
                                        state === 'active' &&
                                        'ring-2 ring-ring ring-offset-2 ring-offset-background',
                                        !canNavigateTo(step.step) &&
                                        'opacity-50 cursor-not-allowed',
                                    ]" :disabled="!canNavigateTo(step.step)">
                                    <!-- Replace icons with numbers -->
                                    <span class="text-sm font-medium">{{
                                        index + 1
                                    }}</span>
                                </Button>
                            </StepperTrigger>

                            <div class="mt-5 flex flex-col items-center text-center">
                                <StepperTitle :class="[
                                    state === 'active' && 'text-primary',
                                ]" class="text-sm font-semibold transition lg:text-base max-[768px]:text-[0.65rem]">
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
                                <VueDatePicker v-model="dateOfBirth" :enable-time-picker="false" uid="date-of-birth" auto-apply
                                    :placeholder="_t('registerUser', 'select_date_of_birth_placeholder')" class="w-full" :max-date="minimumDateOfBirth"
                                    :start-date="minimumDateOfBirth" />
                                <small class="text-gray-500 mt-1 block">{{ _t('registerUser', 'age_requirement') }}</small>
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
                                        <InputLabel for="phone" class="whitespace-nowrap" :value="_t('registerUser', 'phone_number_label')" />
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
                                            class="w-full !rounded-l-none" :placeholder="_t('registerUser', 'enter_phone_number_placeholder')"/>
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
                                    {{ showPassword ? _t('registerUser', 'hide_password') : _t('registerUser', 'show_password') }}
                                </button>
                                <p class="flex justify-end font-medium text-[0.75rem]">{{ _t('registerUser', 'password_length_instruction') }}</p>
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
                                <InputLabel for="password_confirmation" :value="_t('registerUser', 'confirm_password_label')" />
                                <TextInput :type="showconfirmPassword ? 'text' : 'password'" id="password_confirmation" type="password"
                                    v-model="form.password_confirmation" required autocomplete="new-password"
                                    class="w-full" :class="{
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
                                    {{ showconfirmPassword ? _t('registerUser', 'hide_password') : _t('registerUser', 'show_password') }}
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
                            class="w-[20%] max-[768px]:w-full max-[768px]:order-2" @click="prevStep">
                            {{ _t('registerUser', 'back_button') }}
                        </Button>
                        <div class="flex items-center gap-3 max-[768px]:w-full max-[768px]:order-1">
                            <Button v-if="stepIndex !== 4" size="lg" class="w-[100%]" @click="nextStep"
                                :disabled="!isStepValid">
                                {{ _t('registerUser', 'continue_button') }}
                            </Button>
                            <PrimaryButton v-if="stepIndex === 4" class="w-[100%]"
                                :disabled="form.processing || !isStepValid" @click="submit">
                                {{ _t('registerUser', 'register_button') }}
                            </PrimaryButton>
                        </div>
                    </div>
                </form>
            </Stepper>
        </div>
    </div>
    <div v-if="form.processing" class="fixed inset-0 z-50 flex items-center justify-center bg-white bg-opacity-70">
        <img :src="loaderVariant" alt="Loading..." class="h-20 w-20" />
    </div>
</template>

<style scoped>
.register label {
    margin-bottom: 0.5rem;
    color: #2b2b2bbf;
}

.register input {
    border: 1px solid #2b2b2b80;
    border-radius: 12px;
    padding: 1rem;
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
    border-radius: 12px; 
    border: 1px solid #2b2b2b99;
    width: 100%; 
}

:deep(.dp__menu) {
    border-radius: 0.5rem;
    padding: 0.5rem;
}

:deep(.border-customLightGrayColor svg) {
    display: none !important;
}


@media screen and (max-width:768px) {
    :deep(.dp__input){
        font-size: 0.75rem;
    }
}
</style>
