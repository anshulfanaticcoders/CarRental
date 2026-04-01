<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import { ref, watch, computed, onMounted } from "vue";
import { Toaster } from "@/Components/ui/sonner";
import { toast } from "vue-sonner";
import registerBg from "../../../assets/registerbgimage.jpg";
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
        title: "Personal Information",
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

const nextStep = () => {
    if (stepIndex.value < steps.length && isStepValid.value) {
        if (stepIndex.value === 2) {
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
                        if (error.response.data.errors.email) {
                            form.errors.email = error.response.data.errors.email[0];
                        }
                        if (error.response.data.errors.phone) {
                            form.errors.phone = error.response.data.errors.phone[0];
                        }
                        if (error.response.data.errors.phone_code) {
                            form.errors.phone_code = error.response.data.errors.phone_code[0];
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

    isRegistering.value = true;

    if (dateOfBirth.value) {
        const d = new Date(dateOfBirth.value);
        form.date_of_birth = d.toISOString().split('T')[0];
    }

    form.post(route("register"), {
        onSuccess: () => {
            toast.success(_t('registerUser', 'account_created_success') || 'Account created successfully');
            const returnToUrl = sessionStorage.getItem('returnToUrl');
            if (returnToUrl) {
                sessionStorage.removeItem('returnToUrl');
                window.location.href = returnToUrl;
            }
        },
        onFinish: () => {
            form.reset("password", "password_confirmation");
            isRegistering.value = false;
        },
        onError: (errors) => {
            Object.keys(errors).forEach((field) => {
                form.errors[field] = errors[field];
            });
            isRegistering.value = false;
            toast.error(_t('registerUser', 'registration_failed') || 'Registration failed. Please check the form.');
        },
    });
};

const countries = ref([]);
const currencies = ref([]);

const selectedCurrency = computed({
    get() { return form.currency || ''; },
    set(newValue) { form.currency = newValue; }
});

const selectedPhoneCode = ref("");
const phoneNumber = ref("");
const fullPhone = computed({
    get: () => `${form.phone_code} ${form.phone}`,
    set: () => {},
});
const selectedCountryCode = computed(() => {
    if (!form.phone_code || !countries.value || countries.value.length === 0) return null;
    const country = countries.value.find((c) => c.phone_code === form.phone_code);
    return country ? country.code : null;
});

watch(selectedPhoneCode, (newCode) => {
    form.phone_code = newCode;
});

const fetchCountries = async () => {
    try {
        const response = await fetch("/countries.json");
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

const maxDateOfBirth = computed(() => {
    const today = new Date();
    return new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
});

watch(dateOfBirth, (newValue) => {
    if (newValue) {
        const d = new Date(newValue);
        form.date_of_birth = d.toISOString().split('T')[0];
    } else {
        form.date_of_birth = '';
    }
});
</script>

<template>
    <Head>
        <meta name="robots" content="noindex, nofollow">
        <title>{{ _t('registerUser', 'register_title') || 'Create Account' }}</title>
    </Head>

    <Toaster position="bottom-right" :toastOptions="{ style: { background: 'black', color: 'white', border: '1px solid #333' } }" />

    <main class="register-page">
        <!-- Form Side -->
        <div class="form-side">
            <div class="logo-bar">
                <Link :href="route('welcome', { locale: page.props.locale })" class="logo-link">
                    <ApplicationLogo class="logo" />
                </Link>
            </div>

            <div class="form-center">
                <div class="form-card">
                    <!-- Step indicator -->
                    <div class="step-indicator">
                        <div class="step-number">{{ stepIndex }}</div>
                        <div class="step-meta">
                            <span class="step-label">{{ `${_t('registerUser', 'step_label') || 'Step'} ${stepIndex} / ${steps.length}` }}</span>
                            <span class="step-title">{{ _t('registerUser', `step${stepIndex}_title`) || steps[stepIndex - 1].title }}</span>
                        </div>
                    </div>

                    <!-- Progress bars -->
                    <div class="progress">
                        <div v-for="i in steps.length" :key="i" class="progress-bar" :class="{ filled: i <= stepIndex }"></div>
                    </div>

                    <!-- Step 1: Personal -->
                    <div v-if="stepIndex === 1" class="panel">
                        <div class="field-row">
                            <div class="field">
                                <InputLabel for="first_name" :value="_t('registerUser', 'first_name_label')" class="field-label" />
                                <TextInput id="first_name" class="field-input" v-model="form.first_name" required autofocus :placeholder="_t('registerUser', 'first_name_placeholder') || 'John'" />
                                <InputError class="field-error" :message="form.errors.first_name" />
                            </div>
                            <div class="field">
                                <InputLabel for="last_name" :value="_t('registerUser', 'last_name_label')" class="field-label" />
                                <TextInput id="last_name" class="field-input" v-model="form.last_name" required :placeholder="_t('registerUser', 'last_name_placeholder') || 'Doe'" />
                                <InputError class="field-error" :message="form.errors.last_name" />
                            </div>
                        </div>
                        <div class="field">
                            <InputLabel for="date_of_birth" :value="_t('registerUser', 'date_of_birth_label')" class="field-label" />
                            <VueDatePicker
                                v-model="dateOfBirth"
                                :enable-time-picker="false"
                                :teleport="true"
                                uid="register-date-of-birth"
                                auto-apply
                                :placeholder="_t('registerUser', 'date_of_birth_placeholder') || 'Select your date of birth'"
                                class="w-full"
                                :max-date="maxDateOfBirth"
                                :start-date="maxDateOfBirth"
                            />
                            <InputError class="field-error" :message="form.errors.date_of_birth" />
                        </div>

                        <div class="divider"><span>{{ _t('registerUser', 'social_divider') || 'or sign up with' }}</span></div>
                        <div class="social-btns">
                            <a :href="route('oauth.redirect.global', { locale: page.props.locale, provider: 'google' })" class="social-btn">
                                <svg viewBox="0 0 48 48" width="20" height="20"><path fill="#EA4335" d="M24 9.5c3.54 0 6.73 1.22 9.25 3.6l6.9-6.9C35.7 2.57 30.23 0 24 0 14.62 0 6.53 5.38 2.55 13.22l8.06 6.26C12.5 13.04 17.8 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.64-.15-3.22-.43-4.75H24v9.02h12.98c-.56 3.02-2.25 5.58-4.77 7.3l7.32 5.68c4.28-3.95 6.45-9.77 6.45-17.25z"/><path fill="#FBBC05" d="M10.61 28.74c-.48-1.45-.76-2.99-.76-4.74 0-1.75.27-3.29.76-4.74l-8.06-6.26C.92 16.24 0 19.9 0 24c0 4.1.92 7.76 2.55 11l8.06-6.26z"/><path fill="#34A853" d="M24 48c6.23 0 11.45-2.06 15.27-5.6l-7.32-5.68c-2.02 1.36-4.6 2.16-7.95 2.16-6.2 0-11.5-3.54-13.39-8.29l-8.06 6.26C6.53 42.62 14.62 48 24 48z"/></svg>
                                Google
                            </a>
                            <a :href="route('oauth.redirect.global', { locale: page.props.locale, provider: 'facebook' })" class="social-btn">
                                <svg viewBox="0 0 48 48" width="20" height="20"><path fill="#1877F2" d="M48 24c0 13.26-10.74 24-24 24S0 37.26 0 24 10.74 0 24 0s24 10.74 24 24z"/><path fill="#fff" d="M26.67 24.98h5.15l.81-5.3h-5.96v-3.44c0-1.53.75-3.02 3.17-3.02h2.45V8.7s-2.22-.38-4.35-.38c-4.44 0-7.34 2.69-7.34 7.56v3.8h-4.94v5.3h4.94V40h6.07V24.98z"/></svg>
                                Facebook
                            </a>
                        </div>

                        <div class="btn-row">
                            <button type="button" class="btn-next" :disabled="!isStepValid" @click="nextStep">{{ _t('registerUser', 'continue_button') }}</button>
                        </div>
                    </div>

                    <!-- Step 2: Contact -->
                    <div v-if="stepIndex === 2" class="panel">
                        <div class="field">
                            <InputLabel for="phone" :value="_t('registerUser', 'phone_number_label')" class="field-label" />
                            <div class="phone-row">
                                <Select v-model="selectedPhoneCode">
                                    <SelectTrigger class="field-input field-select"><SelectValue :placeholder="_t('registerUser', 'phone_code') || 'Code'" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem v-for="c in countries" :key="c.code" :value="c.phone_code">
                                                <span class="flex items-center gap-2">
                                                    <img :src="getFlagUrl(c.code)" :alt="c.name" class="w-5 h-4 rounded-sm object-cover" />
                                                    {{ c.phone_code }}
                                                </span>
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <TextInput id="phone" type="tel" class="field-input" v-model="form.phone" required :placeholder="_t('registerUser', 'phone_placeholder') || '612 345 678'" />
                            </div>
                            <InputError class="field-error" :message="form.errors.phone || form.errors.phone_code" />
                        </div>
                        <div class="field">
                            <InputLabel for="email" :value="_t('registerUser', 'email_label')" class="field-label" />
                            <TextInput id="email" type="email" class="field-input" v-model="form.email" required :placeholder="_t('registerUser', 'email_placeholder') || 'name@example.com'" />
                            <InputError class="field-error" :message="form.errors.email" />
                        </div>
                        <div class="btn-row">
                            <button type="button" class="btn-back" @click="prevStep">{{ _t('registerUser', 'back_button') }}</button>
                            <button type="button" class="btn-next" :disabled="!isStepValid || isValidating" @click="nextStep">
                                <span v-if="isValidating">{{ _t('registerUser', 'validating') || 'Validating...' }}</span>
                                <span v-else>{{ _t('registerUser', 'continue_button') }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Address -->
                    <div v-if="stepIndex === 3" class="panel">
                        <div class="field">
                            <InputLabel for="address" :value="_t('registerUser', 'address_label')" class="field-label" />
                            <TextInput id="address" class="field-input" v-model="form.address" required :placeholder="_t('registerUser', 'address_placeholder') || '123 Main Street'" />
                            <InputError class="field-error" :message="form.errors.address" />
                        </div>
                        <div class="field-row">
                            <div class="field">
                                <InputLabel for="postcode" :value="_t('registerUser', 'postcode_label')" class="field-label" />
                                <TextInput id="postcode" class="field-input" v-model="form.postcode" required :placeholder="_t('registerUser', 'postcode_placeholder') || '1012 AB'" />
                                <InputError class="field-error" :message="form.errors.postcode" />
                            </div>
                            <div class="field">
                                <InputLabel for="city" :value="_t('registerUser', 'city_label')" class="field-label" />
                                <TextInput id="city" class="field-input" v-model="form.city" required :placeholder="_t('registerUser', 'city_placeholder') || 'Amsterdam'" />
                                <InputError class="field-error" :message="form.errors.city" />
                            </div>
                        </div>
                        <div class="field-row">
                            <div class="field">
                                <InputLabel for="country" :value="_t('registerUser', 'country_label')" class="field-label" />
                                <Select v-model="form.country">
                                    <SelectTrigger class="field-input field-select"><SelectValue :placeholder="_t('registerUser', 'country_placeholder') || 'Select country'" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem v-for="c in countries" :key="c.code" :value="c.name">{{ c.name }}</SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <InputError class="field-error" :message="form.errors.country" />
                            </div>
                            <div class="field">
                                <InputLabel for="currency" :value="_t('common', 'currency') || 'Currency'" class="field-label" />
                                <Select v-model="selectedCurrency">
                                    <SelectTrigger class="field-input field-select"><SelectValue :placeholder="_t('registerUser', 'currency_placeholder') || 'Select currency'" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem v-for="c in currencies" :key="c.code" :value="c.code">{{ c.code }} ({{ c.symbol }})</SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        <div class="btn-row">
                            <button type="button" class="btn-back" @click="prevStep">{{ _t('registerUser', 'back_button') }}</button>
                            <button type="button" class="btn-next" :disabled="!isStepValid" @click="nextStep">{{ _t('registerUser', 'continue_button') }}</button>
                        </div>
                    </div>

                    <!-- Step 4: Password -->
                    <div v-if="stepIndex === 4" class="panel">
                        <div class="field">
                            <InputLabel for="password" :value="_t('registerUser', 'password_label')" class="field-label" />
                            <div class="pw-wrap">
                                <TextInput :type="showPassword ? 'text' : 'password'" id="password" class="field-input" v-model="form.password" required :placeholder="_t('registerUser', 'password_placeholder') || 'At least 8 characters'" />
                                <button type="button" @click="showPassword = !showPassword" class="pw-toggle">{{ showPassword ? _t('registerUser', 'hide_password') || 'Hide' : _t('registerUser', 'show_password') || 'Show' }}</button>
                            </div>
                            <div v-if="form.password.length > 0" class="pw-strength">
                                <div class="pw-bar" :class="{ ok: form.password.length >= 3, strong: form.password.length >= 12 }"></div>
                                <div class="pw-bar" :class="{ ok: form.password.length >= 6, strong: form.password.length >= 12 }"></div>
                                <div class="pw-bar" :class="{ ok: form.password.length >= 8, strong: form.password.length >= 12 }"></div>
                                <div class="pw-bar" :class="{ ok: form.password.length >= 10, strong: form.password.length >= 12 }"></div>
                            </div>
                            <InputError class="field-error" :message="form.errors.password" />
                        </div>
                        <div class="field">
                            <InputLabel for="password_confirmation" :value="_t('registerUser', 'confirm_password_label')" class="field-label" />
                            <div class="pw-wrap">
                                <TextInput :type="showconfirmPassword ? 'text' : 'password'" id="password_confirmation" class="field-input" v-model="form.password_confirmation" required :placeholder="_t('registerUser', 'confirm_password_placeholder') || 'Re-enter password'" />
                                <button type="button" @click="showconfirmPassword = !showconfirmPassword" class="pw-toggle">{{ showconfirmPassword ? _t('registerUser', 'hide_password') || 'Hide' : _t('registerUser', 'show_password') || 'Show' }}</button>
                            </div>
                            <p v-if="form.password_confirmation.length > 0" class="pw-match" :class="passwordsMatch ? 'ok' : 'no'">
                                {{ passwordsMatch ? (_t('registerUser', 'passwords_match')) : (_t('registerUser', 'passwords_do_not_match')) }}
                            </p>
                            <InputError class="field-error" :message="form.errors.password_confirmation" />
                        </div>
                        <div class="btn-row">
                            <button type="button" class="btn-back" @click="prevStep">{{ _t('registerUser', 'back_button') }}</button>
                            <button type="button" class="btn-next green" :disabled="!canNavigateNext || isRegistering" @click="submit">
                                <span v-if="isRegistering">{{ _t('registerUser', 'creating') || 'Creating...' }}</span>
                                <span v-else>{{ _t('registerUser', 'create_account') || 'Create Account' }}</span>
                            </button>
                        </div>
                    </div>

                    <p class="login-link">{{ _t('registerUser', 'already_registered_link') }} <Link :href="route('login', { locale: page.props.locale })" class="login-link-a">{{ _t('login', 'sign_in_button') }}</Link></p>
                </div>
            </div>
        </div>

        <!-- Image Side -->
        <div class="image-side">
            <img :src="registerBg" alt="Car rental" />
            <div class="image-overlay"></div>
            <div class="image-content">
                <h2>Join 50,000+ travelers who save on every rental.</h2>
                <p>Compare prices from 200+ trusted providers across Europe.</p>
                <div class="image-trust">
                    <span><i class="trust-dot"></i> Free cancellation</span>
                    <span><i class="trust-dot"></i> Best price</span>
                    <span><i class="trust-dot"></i> 24/7 support</span>
                </div>
            </div>
        </div>
    </main>

    <!-- Loader -->
    <div v-if="isRegistering" class="loader-overlay">
        <div class="loader-content"><div class="loader-spinner"></div><p>Creating your account...</p></div>
    </div>
</template>

<style scoped>
/* Layout - same as Login */
.register-page { display: flex; min-height: 100vh; --ease: cubic-bezier(0.22, 1, 0.36, 1); --dur: 0.3s; }

/* Form side */
.form-side { flex: 1; display: flex; flex-direction: column; background: linear-gradient(160deg, #f9fcfe, #f0f6fa 40%, #f8fafc); position: relative; overflow-y: auto; overflow-x: hidden; }
.form-side::before { content: ''; position: absolute; top: -200px; left: -200px; width: 600px; height: 600px; background: radial-gradient(circle, rgba(34,211,238,0.05), transparent 65%); pointer-events: none; }
.logo-bar { padding: 24px 40px; position: relative; z-index: 1; }
.logo-link { display: inline-block; width: 10rem; transition: opacity var(--dur) var(--ease); }
.logo-link:hover { opacity: 0.8; }
.logo { width: 100%; height: auto; }

/* Form card */
.form-center { flex: 1; display: flex; align-items: center; justify-content: center; padding: 16px 48px 48px; position: relative; z-index: 1; }
.form-card { width: 75%; background: rgba(255,255,255,0.92); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(176,212,230,0.45); border-radius: 28px; padding: 40px; box-shadow: 0 16px 48px rgba(21,59,79,0.1), 0 0 0 1px rgba(255,255,255,0.6) inset; position: relative; }
.form-card::before { content: ''; position: absolute; top: -60%; right: -40%; width: 300px; height: 300px; background: radial-gradient(circle, rgba(34,211,238,0.05), transparent 70%); pointer-events: none; }

/* Step indicator */
.step-indicator { display: flex; align-items: center; gap: 14px; margin-bottom: 20px; position: relative; z-index: 1; }
.step-number { width: 40px; height: 40px; border-radius: 12px; background: linear-gradient(135deg, #153b4f, #1c4d66); color: #fff; font-size: 0.9rem; font-weight: 800; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(21,59,79,0.2); flex-shrink: 0; }
.step-meta { display: flex; flex-direction: column; gap: 1px; }
.step-label { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.12em; color: #94a3b8; }
.step-title { font-size: 1.3rem; font-weight: 800; color: #0f172a; letter-spacing: -0.01em; }

/* Progress */
.progress { display: flex; gap: 6px; margin-bottom: 28px; position: relative; z-index: 1; }
.progress-bar { height: 4px; flex: 1; border-radius: 2px; background: #e2e8f0; transition: background 0.5s var(--ease); }
.progress-bar.filled { background: linear-gradient(90deg, #153b4f, #22d3ee); }

/* Fields - same as Login */
.field { margin-bottom: 18px; position: relative; z-index: 1; }
.field-label { font-size: 0.76rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: #475569; margin-bottom: 6px; }
.field-input { width: 100%; padding: 13px 16px; border: 1.5px solid #e2e8f0; border-radius: 14px; font-size: 0.92rem; font-family: inherit; color: #0f172a; background: #fff; outline: none; transition: border-color var(--dur) var(--ease), box-shadow var(--dur) var(--ease); }
.field-input:focus { border-color: #22d3ee; box-shadow: 0 0 0 4px rgba(34,211,238,0.12); }
.field-input::placeholder { color: #94a3b8; }
.field-error { margin-top: 5px; color: #ef4444; font-size: 0.76rem; font-weight: 500; }
.field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.phone-row { display: grid; grid-template-columns: 140px 1fr; gap: 10px; align-items: start; }
:deep(.phone-row .field-select) { height: auto; }
:deep(.phone-row [data-slot="select-trigger"]),
:deep(.phone-row button[role="combobox"]) { height: 48px; padding: 13px 16px; border: 1.5px solid #e2e8f0; border-radius: 14px; font-size: 0.92rem; background: #fff; }

/* Password */
.pw-wrap { position: relative; }
.pw-wrap .field-input { padding-right: 78px; }
.pw-toggle { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); font-size: 0.72rem; font-weight: 600; color: #153b4f; background: rgba(34,211,238,0.08); border: 1px solid rgba(34,211,238,0.2); padding: 4px 10px; border-radius: 999px; cursor: pointer; transition: background var(--dur) var(--ease); }
.pw-toggle:hover { background: rgba(34,211,238,0.15); }
.pw-strength { display: flex; gap: 4px; margin-top: 8px; }
.pw-bar { height: 3px; flex: 1; border-radius: 2px; background: #e2e8f0; transition: background 0.4s var(--ease); }
.pw-bar.ok { background: #22d3ee; }
.pw-bar.strong { background: #10b981; }
.pw-match { font-size: 0.76rem; margin-top: 6px; font-weight: 500; }
.pw-match.ok { color: #10b981; }
.pw-match.no { color: #ef4444; }

/* Buttons */
.btn-row { display: flex; gap: 12px; margin-top: 28px; position: relative; z-index: 1; }
.btn-back { padding: 14px 28px; border: 1.5px solid #e2e8f0; border-radius: 14px; font-size: 0.9rem; font-weight: 600; color: #475569; background: #fff; cursor: pointer; font-family: inherit; transition: border-color var(--dur) var(--ease), color var(--dur) var(--ease), background var(--dur) var(--ease), transform var(--dur) var(--ease); }
.btn-back:hover { border-color: #153b4f; color: #153b4f; background: #f0f8fc; transform: translateY(-1px); }
.btn-next { flex: 1; padding: 14px; border: none; border-radius: 14px; font-size: 0.95rem; font-weight: 700; color: #fff; background: linear-gradient(135deg, #153b4f, #1c4d66); cursor: pointer; font-family: inherit; box-shadow: 0 8px 24px rgba(21,59,79,0.2); transition: transform var(--dur) var(--ease), box-shadow var(--dur) var(--ease); position: relative; overflow: hidden; }
.btn-next::after { content: ''; position: absolute; inset: 0; background: linear-gradient(120deg, rgba(255,255,255,0.18), transparent 50%); opacity: 0; transition: opacity var(--dur); }
.btn-next:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 16px 40px rgba(21,59,79,0.25); }
.btn-next:hover:not(:disabled)::after { opacity: 1; }
.btn-next:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-next.green { background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 8px 24px rgba(16,185,129,0.2); }
.btn-next.green:hover:not(:disabled) { box-shadow: 0 16px 40px rgba(16,185,129,0.28); }

/* Social */
.divider { display: flex; align-items: center; gap: 14px; margin: 24px 0; color: #94a3b8; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.14em; font-weight: 600; position: relative; z-index: 1; }
.divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: linear-gradient(90deg, transparent, #e2e8f0, transparent); }
.social-btns { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; position: relative; z-index: 1; }
.social-btn { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 13px; border-radius: 14px; border: 1.5px solid #e2e8f0; background: #fff; font-size: 0.86rem; font-weight: 600; color: #0f172a; text-decoration: none; transition: border-color var(--dur) var(--ease), transform var(--dur) var(--ease), box-shadow var(--dur) var(--ease); }
.social-btn:hover { border-color: #cbd5e1; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(21,59,79,0.06); }

/* Login link */
.login-link { text-align: center; margin-top: 24px; font-size: 0.88rem; color: #64748b; position: relative; z-index: 1; }
.login-link-a { color: #153b4f; font-weight: 700; margin-left: 4px; transition: color var(--dur) var(--ease); }
.login-link-a:hover { color: #22d3ee; }

/* DOB trigger */
.dob-trigger { display: flex; align-items: center; gap: 10px; text-align: left; cursor: pointer; width: 100%; }
.dob-trigger.has-value { color: #0f172a; }
.dob-icon { color: #94a3b8; flex-shrink: 0; }
.dob-placeholder { color: #94a3b8; }

:deep(.dp__menu) {
    z-index: 9999 !important;
}

/* Panel animation */
.panel { animation: fadeUp 0.4s var(--ease); }
@keyframes fadeUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }

/* Image side - same as Login */
.image-side { flex: 0 0 50%; position: relative; overflow: hidden; }
.image-side img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.image-overlay { position: absolute; inset: 0; background: linear-gradient(160deg, rgba(10,29,40,0.25), rgba(10,29,40,0.6) 60%, rgba(10,29,40,0.85)); }
.image-content { position: absolute; bottom: 48px; left: 48px; right: 48px; z-index: 1; color: #fff; }
.image-content h2 { font-size: 2rem; font-weight: 800; line-height: 1.15; margin-bottom: 10px; }
.image-content p { font-size: 0.92rem; color: rgba(255,255,255,0.65); line-height: 1.6; }
.image-trust { display: flex; gap: 20px; margin-top: 20px; }
.image-trust span { display: flex; align-items: center; gap: 8px; font-size: 0.78rem; color: rgba(255,255,255,0.5); font-weight: 500; }
.trust-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: #22d3ee; box-shadow: 0 0 8px rgba(34,211,238,0.4); }

/* Loader */
.loader-overlay { position: fixed; inset: 0; background: rgba(10,22,32,0.55); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 9999; }
.loader-content { text-align: center; color: #fff; }
.loader-spinner { width: 48px; height: 48px; margin: 0 auto 1rem; border-radius: 50%; border: 3px solid rgba(255,255,255,0.2); border-top-color: #22d3ee; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Responsive */
@media (max-width: 1024px) { .image-side { display: none; } }
@media (max-width: 640px) {
    .logo-bar { padding: 20px 20px; }
    .form-center { padding: 12px 20px 32px; }
    .form-card { width: 100%; padding: 28px 22px; border-radius: 20px; }
    .step-title { font-size: 1.1rem; }
    .field-row, .phone-row { grid-template-columns: 1fr; }
    .social-btns { grid-template-columns: 1fr; }
    .btn-row { flex-direction: column-reverse; }
}
</style>
