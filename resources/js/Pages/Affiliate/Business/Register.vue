<script setup>
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ref, computed, nextTick, onMounted, watch } from 'vue';
import axios from 'axios';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import SeoHead from '@/Components/SeoHead.vue';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue
} from '@/Components/ui/select';

const props = defineProps({
    seo: {
        type: Object,
        required: true,
    },
    locale: {
        type: String,
        required: true,
    },
});

// Tab management
const activeTab = ref('register');

const form = useForm({
    business_type: '',
    name: '',
    contact_email: '',
    contact_phone: '',
    website: '',
    legal_address: '',
    billing_address: '',
    city: '',
    state: '',
    country: '',
    postal_code: '',
    currency: '',
    business_registration_number: '',
    tax_id: '',
    description: '',
    accept_terms: false,
});

// Login form data
const loginForm = useForm({
    email: '',
    dashboard_token: '',
});

const forgotEmailForm = useForm({
    email: '',
});

// Login form states
const isSubmittingLogin = ref(false);
const isSubmittingForgot = ref(false);
const loginError = ref('');
const forgotSuccess = ref('');
const forgotError = ref('');
const showForgotForm = ref(false);

const isSubmitting = ref(false);
const emailChecking = ref(false);
const emailExists = ref(false);
const emailInvalid = ref(false);
const focusedField = ref('');
const fieldErrors = ref({});

// Currency loading and selection
const currencies = ref([]);
const selectedCurrency = computed({
    get() {
        const currency = currencies.value.find(c => c.symbol === form.currency);
        return currency ? currency.code : '';
    },
    set(newValue) {
        const currency = currencies.value.find(c => c.code === newValue);
        form.currency = currency ? currency.symbol : '';
    }
});

const fetchCurrencies = async () => {
    try {
        const response = await fetch('/currency.json'); // Ensure it's in /public
        currencies.value = await response.json();
    } catch (error) {
        console.error("Error loading currencies:", error);
    }
};

onMounted(fetchCurrencies);

// Enhanced business types with descriptions
const businessTypes = [
    { value: 'hotel', label: 'Hotel', description: 'Individual hotel or accommodation' },
    { value: 'hotel_chain', label: 'Hotel Chain', description: 'Multiple hotel locations' },
    { value: 'travel_agent', label: 'Travel Agent', description: 'Travel agency or tour operator' },
    { value: 'partner', label: 'Partner', description: 'Business partner or affiliate' },
    { value: 'corporate', label: 'Corporate', description: 'Corporate travel program' },
    { value: 'rental_company', label: 'Rental Company', description: 'Vehicle rental business' },
    { value: 'tourism_board', label: 'Tourism Board', description: 'Regional tourism authority' },
];

const countries = ref([]);
const phoneNumberOnly = ref('');

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

// Get country code from countries data
const getCountryCode = (countryCode) => {
    const country = countries.value.find(c => c.code === countryCode);
    return country ? country.phone_code : '+1';
};

// Watch for country changes and update phone number
watch(() => form.country, (newCountry) => {
    if (newCountry) {
        const countryCode = getCountryCode(form.country);

        // Update phone number with new country code
        if (phoneNumberOnly.value) {
            form.contact_phone = countryCode + phoneNumberOnly.value;
        } else {
            form.contact_phone = countryCode;
        }

        // Initialize phone number only if empty
        if (!phoneNumberOnly.value) {
            phoneNumberOnly.value = '';
        }
    }
});

// Update contact phone when phone number only changes
const updateContactPhone = () => {
    if (form.country) {
        const countryCode = getCountryCode(form.country);
        form.contact_phone = countryCode + phoneNumberOnly.value;
    }
};


// Enhanced validation rules
const validationRules = {
    name: {
        required: true,
        minLength: 2,
        maxLength: 100,
        pattern: /^[a-zA-Z0-9\s&\-',.]+$/,
        message: 'Business name must be 2-100 characters and contain only letters, numbers, spaces, and basic punctuation'
    },
    contact_email: {
        required: true,
        pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
        message: 'Please enter a valid email address'
    },
    contact_phone: {
        required: true,
        pattern: /^[\+]?[1-9][\d]{0,15}$/,
        message: 'Please enter a valid phone number with country code'
    },
    website: {
        pattern: /^https?:\/\/.+/,
        message: 'Please enter a valid website URL (e.g., https://example.com)'
    },
    city: {
        required: true,
        minLength: 2,
        maxLength: 50,
        message: 'City must be 2-50 characters'
    },
    postal_code: {
        required: true,
        minLength: 3,
        maxLength: 20,
        message: 'Postal code must be 3-20 characters'
    },
    description: {
        maxLength: 2000,
        message: 'Description must not exceed 2000 characters'
    }
};

// Enhanced email validation
const isValidEmail = computed(() => {
    if (!form.contact_email) return false;

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const parts = form.contact_email.split('@');

    return emailRegex.test(form.contact_email) &&
           parts.length === 2 &&
           parts[0].length > 0 &&
           parts[1].length > 2 &&
           parts[1].includes('.');
});

const emailErrorMessage = computed(() => {
    if (!form.contact_email) return '';
    if (!isValidEmail.value) return validationRules.contact_email.message;
    if (emailExists.value) return 'This email is already registered';
    if (emailInvalid.value) return 'Invalid email address';
    return '';
});

// Field validation
const validateField = (fieldName, value) => {
    const rule = validationRules[fieldName];
    if (!rule) return true;

    if (rule.required && (!value || !value.trim())) {
        fieldErrors.value[fieldName] = `${fieldName.charAt(0).toUpperCase() + fieldName.slice(1).replace(/_/g, ' ')} is required`;
        return false;
    }

    if (rule.minLength && value.trim().length < rule.minLength) {
        fieldErrors.value[fieldName] = rule.message;
        return false;
    }

    if (rule.maxLength && value.trim().length > rule.maxLength) {
        fieldErrors.value[fieldName] = rule.message;
        return false;
    }

    if (rule.pattern && !rule.pattern.test(value)) {
        fieldErrors.value[fieldName] = rule.message;
        return false;
    }

    delete fieldErrors.value[fieldName];
    return true;
};

// Enhanced email checking
const checkEmailExists = async () => {
    if (!form.contact_email || !isValidEmail.value) {
        emailExists.value = false;
        emailInvalid.value = false;
        return;
    }

    emailChecking.value = true;

    try {
        const response = await axios.post(`/${props.locale}/business/check-email`, {
            email: form.contact_email
        });

        if (response.data.valid === false) {
            emailInvalid.value = true;
            emailExists.value = false;
        } else {
            emailInvalid.value = false;
            emailExists.value = response.data.exists;
        }
    } catch (error) {
        console.error('Email check failed:', error);
        emailExists.value = false;
        emailInvalid.value = false;
    } finally {
        emailChecking.value = false;
    }
};

const resetEmailExists = () => {
    emailExists.value = false;
    emailInvalid.value = false;
};

// Focus management
const setFocus = (fieldName) => {
    focusedField.value = fieldName;
    // Clear field error when user starts typing
    if (fieldErrors.value[fieldName]) {
        delete fieldErrors.value[fieldName];
    }
};

const clearFocus = (fieldName) => {
    focusedField.value = '';
    // Validate field on blur
    if (fieldName === 'contact_email') {
        checkEmailExists();
    } else {
        validateField(fieldName, form[fieldName]);
    }
};

// Enhanced phone formatting - now handled by updateContactPhone function

// Enhanced website formatting
const formatWebsite = (event) => {
    let value = event.target.value.trim();
    if (value && !value.match(/^https?:\/\//)) {
        value = 'https://' + value;
    }
    form.website = value;
};

// Enhanced submit with validation
const submit = async () => {
    // Validate all fields before submission
    let isValid = true;

    Object.keys(validationRules).forEach(field => {
        if (!validateField(field, form[field])) {
            isValid = false;
        }
    });

    if (!isValid) {
        return;
    }

    isSubmitting.value = true;

    form.post(route('affiliate.business.store'), {
        onFinish: () => {
            isSubmitting.value = false;
        },
        onError: (errors) => {
            // Convert Laravel errors to our format
            Object.keys(errors).forEach(field => {
                fieldErrors.value[field] = errors[field];
            });
            isSubmitting.value = false;
        },
    });
};

// Login submit function
const submitLogin = async () => {
    loginError.value = '';

    // Basic validation
    if (!loginForm.email || !loginForm.dashboard_token) {
        loginError.value = 'Please fill in all required fields';
        return;
    }

    isSubmittingLogin.value = true;

    try {
        const response = await axios.post(`/${props.locale}/business/login`, {
            email: loginForm.email,
            dashboard_token: loginForm.dashboard_token,
        });

        if (response.data.success) {
            // Redirect to dashboard
            window.location.href = response.data.redirect_url;
        } else {
            loginError.value = response.data.message || 'Invalid credentials';
        }
    } catch (error) {
        console.error('Login failed:', error);
        loginError.value = error.response?.data?.message || 'Login failed. Please check your credentials and try again.';
    } finally {
        isSubmittingLogin.value = false;
    }
};

// Forgot access key function
const submitForgotAccess = async () => {
    forgotError.value = '';
    forgotSuccess.value = '';

    // Basic validation
    if (!forgotEmailForm.email) {
        forgotError.value = 'Please enter your email address';
        return;
    }

    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(forgotEmailForm.email)) {
        forgotError.value = 'Please enter a valid email address';
        return;
    }

    isSubmittingForgot.value = true;

    try {
        const response = await axios.post(`/${props.locale}/business/refresh-access`, {
            email: forgotEmailForm.email,
        });

        if (response.data.success) {
            forgotSuccess.value = 'New dashboard access link has been sent to your email address';
            forgotEmailForm.email = '';
            showForgotForm.value = false;
        } else {
            forgotError.value = response.data.message || 'Failed to send access link';
        }
    } catch (error) {
        console.error('Forgot access failed:', error);
        forgotError.value = error.response?.data?.message || 'Failed to send access link. Please try again.';
    } finally {
        isSubmittingForgot.value = false;
    }
};

// Reset login errors when user starts typing
const resetLoginError = () => {
    loginError.value = '';
};

// Reset forgot errors when user starts typing
const resetForgotError = () => {
    forgotError.value = '';
    forgotSuccess.value = '';
};

// Show forgot form
const showForgotAccessForm = () => {
    showForgotForm.value = true;
    forgotEmailForm.email = loginForm.email;
    loginError.value = '';
    forgotError.value = '';
    forgotSuccess.value = '';
};

// Show login form (back from forgot)
const showLoginForm = () => {
    showForgotForm.value = false;
    forgotSuccess.value = '';
    forgotEmailForm.email = '';
    forgotError.value = '';
};

// Enhanced form validation
const isFormValid = computed(() => {
    // Check all required fields
    const requiredFields = ['business_type', 'name', 'contact_email', 'contact_phone', 'legal_address', 'city', 'country', 'postal_code', 'currency'];

    for (const field of requiredFields) {
        if (!form[field] || !form[field].trim()) {
            return false;
        }
    }

    // Check email validity
    if (!isValidEmail.value || emailExists.value || emailInvalid.value) {
        return false;
    }

    // Check terms acceptance
    if (!form.accept_terms) {
        return false;
    }

    // Check field errors
    if (Object.keys(fieldErrors.value).length > 0) {
        return false;
    }

    return true;
});

// Progress tracking
const formProgress = computed(() => {
    const fields = [
        'business_type', 'name', 'contact_email', 'contact_phone',
        'legal_address', 'city', 'country', 'postal_code', 'currency', 'accept_terms'
    ];

    const filledFields = fields.filter(field => {
        if (field === 'accept_terms') {
            return form[field] === true;
        }
        return form[field] && form[field].trim().length > 0;
    });

    return Math.round((filledFields.length / fields.length) * 100);
});
</script>

<template>
    <SeoHead :seo="seo" />

    <AuthenticatedHeaderLayout/>

    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50">
        <!-- Header -->
        <!-- <div class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex items-center justify-between">
                    <Link :href="route('welcome', { locale })" class="flex items-center space-x-3">
                        <ApplicationLogo class="h-10 w-auto" />
                        <span class="text-xl font-bold text-gray-900">Vrooem</span>
                    </Link>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-500">
                            Already have an account?
                        </span>
                        <Link
                            :href="route('login')"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-blue-600 bg-blue-50 hover:bg-blue-100 transition-colors"
                        >
                            Sign In
                        </Link>
                    </div>
                </div>
            </div>
        </div> -->

        <!-- Main Content -->
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Progress Bar -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Registration Progress</span>
                    <span class="text-sm font-medium text-blue-600">{{ formProgress }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div
                        class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full transition-all duration-300 ease-out"
                        :style="{ width: formProgress + '%' }"
                    ></div>
                </div>
            </div>

            <!-- Registration/Login Form -->
            <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
                <!-- Form Header with Tabs -->
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-8 py-6">
                    <div class="text-center mb-6">
                        <h1 class="text-3xl font-bold text-white">
                            {{ activeTab === 'register' ? 'Join the Vrooem Affiliate Program' : 'Access Your Dashboard' }}
                        </h1>
                        <p class="mt-2 text-blue-100 max-w-2xl mx-auto">
                            {{ activeTab === 'register'
                                ? 'Register your business to offer exclusive car rental discounts to your customers and earn competitive commissions'
                                : 'Access your affiliate dashboard to manage QR codes and track your earnings'
                            }}
                        </p>
                    </div>

                    <!-- Tab Navigation -->
                    <div class="flex justify-center space-x-4">
                        <button
                            @click="activeTab = 'register'"
                            :class="[
                                'px-6 py-2 rounded-lg font-medium transition-all duration-200',
                                activeTab === 'register'
                                    ? 'bg-white text-blue-600 shadow-lg'
                                    : 'bg-blue-500 bg-opacity-20 text-blue-100 hover:bg-opacity-30'
                            ]"
                        >
                            Register
                        </button>
                        <button
                            @click="activeTab = 'login'"
                            :class="[
                                'px-6 py-2 rounded-lg font-medium transition-all duration-200',
                                activeTab === 'login'
                                    ? 'bg-white text-blue-600 shadow-lg'
                                    : 'bg-blue-500 bg-opacity-20 text-blue-100 hover:bg-opacity-30'
                            ]"
                        >
                            Login
                        </button>
                    </div>
                </div>

                <!-- Register Form -->
                <form v-if="activeTab === 'register'" @submit.prevent="submit" class="p-8">
                    <!-- Business Information Section -->
                    <div class="mb-10">
                        <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            Business Information
                        </h2>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Business Type -->
                            <div>
                                <label for="business_type" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Business Type <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="business_type"
                                    v-model="form.business_type"
                                    @focus="setFocus('business_type')"
                                    @blur="clearFocus('business_type')"
                                    :class="[
                                        'w-full px-4 py-3 border-2 rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                                        focusedField === 'business_type'
                                            ? 'border-blue-500 bg-blue-50'
                                            : fieldErrors.business_type
                                                ? 'border-red-500 bg-red-50'
                                                : 'border-gray-300 hover:border-gray-400'
                                    ]"
                                    required
                                >
                                    <option value="">Select your business type</option>
                                    <option v-for="type in businessTypes" :key="type.value" :value="type.value">
                                        {{ type.label }} - {{ type.description }}
                                    </option>
                                </select>
                                <p v-if="fieldErrors.business_type" class="mt-2 text-sm text-red-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    {{ fieldErrors.business_type }}
                                </p>
                            </div>

                            <!-- Business Name -->
                            <div>
                                <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Business Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    @focus="setFocus('name')"
                                    @blur="clearFocus('name')"
                                    :class="[
                                        'w-full px-4 py-3 border-2 rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                                        focusedField === 'name'
                                            ? 'border-blue-500 bg-blue-50'
                                            : fieldErrors.name
                                                ? 'border-red-500 bg-red-50'
                                                : 'border-gray-300 hover:border-gray-400'
                                    ]"
                                    placeholder="Enter your business name"
                                    required
                                />
                                <p v-if="fieldErrors.name" class="mt-2 text-sm text-red-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    {{ fieldErrors.name }}
                                </p>
                            </div>

                            <!-- Currency Selection -->
                            <div>
                                <label for="currency" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Business Currency <span class="text-red-500">*</span>
                                </label>
                                <Select v-model="selectedCurrency">
                                    <SelectTrigger class="w-full p-[1.7rem] border-2 rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        <SelectValue placeholder="Select your preferred currency" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectLabel>Business Currency</SelectLabel>
                                            <SelectItem v-for="currency in currencies" :key="currency.code" :value="currency.code">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-medium">{{ currency.code }}</span>
                                                    <span class="text-gray-500">({{ currency.symbol }})</span>
                                                </div>
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <p class="mt-2 text-sm text-gray-500">
                                    Select the currency you want to use for commission payments and financial reporting
                                </p>
                                <p v-if="fieldErrors.currency" class="mt-2 text-sm text-red-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    {{ fieldErrors.currency }}
                                </p>
                            </div>

                            <!-- Contact Email -->
                            <div>
                                <label for="contact_email" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Contact Email <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <input
                                        id="contact_email"
                                        v-model="form.contact_email"
                                        type="email"
                                        @focus="setFocus('contact_email')"
                                        @blur="clearFocus('contact_email')"
                                        @input="resetEmailExists"
                                        :class="[
                                            'w-full pl-10 pr-10 py-3 border-2 rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                                            focusedField === 'contact_email'
                                                ? 'border-blue-500 bg-blue-50'
                                                : emailErrorMessage
                                                    ? 'border-red-500 bg-red-50'
                                                    : form.contact_email && isValidEmail.value && !emailExists.value && !emailChecking.value
                                                        ? 'border-green-500 bg-green-50'
                                                        : 'border-gray-300 hover:border-gray-400'
                                        ]"
                                        placeholder="contact@yourbusiness.com"
                                        required
                                    />
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <svg v-if="emailChecking" class="animate-spin h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <svg v-else-if="form.contact_email && isValidEmail.value && !emailExists.value && !emailChecking.value" class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <svg v-else-if="emailErrorMessage" class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </div>
                                </div>
                                <p v-if="emailChecking" class="mt-2 text-sm text-blue-600">
                                    Checking email availability...
                                </p>
                                <p v-else-if="emailErrorMessage" class="mt-2 text-sm text-red-600">
                                    {{ emailErrorMessage }}
                                </p>
                                <p v-else-if="form.contact_email && isValidEmail.value && !emailExists.value && !emailChecking.value" class="mt-2 text-sm text-green-600">
                                    ✓ Email is available
                                </p>
                            </div>

                            <!-- Country Selection -->
                            <div>
                                <label for="country" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Country <span class="text-red-500">*</span>
                                </label>
                                <Select v-model="form.country">
                                    <SelectTrigger class="w-full p-[1.7rem] border-2 rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        <SelectValue placeholder="Select a country" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectLabel>Country</SelectLabel>
                                            <SelectItem v-for="country in countries" :key="country.code" :value="country.code">
                                                <div class="flex items-center gap-2">
                                                    <img :src="getFlagUrl(country.code)" :alt="`${country.name} flag`"
                                                         class="w-[1.5rem] h-[1rem] rounded-sm" />
                                                    {{ country.name }}
                                                </div>
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <p v-if="fieldErrors.country" class="mt-2 text-sm text-red-600">
                                    {{ fieldErrors.country }}
                                </p>
                            </div>

                            <!-- Contact Phone with Country Code -->
                            <div>
                                <label for="contact_phone" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Contact Phone <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="flex">
                                        <!-- Country Code Prefix (Non-editable) -->
                                        <div class="inline-flex items-center px-3 border border-r-0 border-gray-300 bg-gray-50 text-gray-500 rounded-l-md">
                                            <span class="text-sm font-medium">{{ getCountryCode(form.country) }}</span>
                                        </div>
                                        <!-- Phone Number Input -->
                                        <input
                                            type="tel"
                                            v-model="phoneNumberOnly"
                                            @focus="setFocus('contact_phone')"
                                            @blur="clearFocus('contact_phone')"
                                            @input="updateContactPhone"
                                            :disabled="!form.country"
                                            :class="[
                                                'block w-full flex-1 px-3 py-3 border-2 rounded-r-md transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                                                focusedField === 'contact_phone'
                                                    ? 'border-blue-500 bg-blue-50'
                                                    : fieldErrors.contact_phone
                                                        ? 'border-red-500 bg-red-50'
                                                        : 'border-gray-300 hover:border-gray-400',
                                                !form.country ? 'bg-gray-100 cursor-not-allowed' : ''
                                            ]"
                                            placeholder="1234567890"
                                            required
                                        />
                                    </div>
                                    <p v-if="!form.country" class="mt-1 text-sm text-gray-500">
                                        Please select a country first
                                    </p>
                                    <p v-if="fieldErrors.contact_phone" class="mt-2 text-sm text-red-600 flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        {{ fieldErrors.contact_phone }}
                                    </p>
                                </div>
                            </div>

                            <!-- Website -->
                            <div class="lg:col-span-2">
                                <label for="website" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Website
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 9c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                        </svg>
                                    </div>
                                    <input
                                        id="website"
                                        v-model="form.website"
                                        type="url"
                                        @focus="setFocus('website')"
                                        @blur="clearFocus('website')"
                                        @input="formatWebsite"
                                        :class="[
                                            'w-full pl-10 py-3 border-2 rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                                            focusedField === 'website'
                                                ? 'border-blue-500 bg-blue-50'
                                                : fieldErrors.website
                                                    ? 'border-red-500 bg-red-50'
                                                    : 'border-gray-300 hover:border-gray-400'
                                        ]"
                                        placeholder="https://yourbusiness.com"
                                    />
                                </div>
                                <p v-if="fieldErrors.website" class="mt-2 text-sm text-red-600">
                                    {{ fieldErrors.website }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Address Information Section -->
                    <div class="mb-10">
                        <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Address Information
                        </h2>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Legal Address -->
                            <div class="lg:col-span-2">
                                <label for="legal_address" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Legal Address <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    id="legal_address"
                                    v-model="form.legal_address"
                                    rows="3"
                                    @focus="setFocus('legal_address')"
                                    @blur="clearFocus('legal_address')"
                                    :class="[
                                        'w-full px-4 py-3 border-2 rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none',
                                        focusedField === 'legal_address'
                                            ? 'border-blue-500 bg-blue-50'
                                            : fieldErrors.legal_address
                                                ? 'border-red-500 bg-red-50'
                                                : 'border-gray-300 hover:border-gray-400'
                                    ]"
                                    placeholder="123 Main Street, Suite 100, New York, NY 10001"
                                    required
                                ></textarea>
                                <p v-if="fieldErrors.legal_address" class="mt-2 text-sm text-red-600">
                                    {{ fieldErrors.legal_address }}
                                </p>
                            </div>

                            <!-- Billing Address -->
                            <div class="lg:col-span-2">
                                <label for="billing_address" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Billing Address
                                </label>
                                <textarea
                                    id="billing_address"
                                    v-model="form.billing_address"
                                    rows="3"
                                    @focus="setFocus('billing_address')"
                                    @blur="clearFocus('billing_address')"
                                    :class="[
                                        'w-full px-4 py-3 border-2 rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none',
                                        focusedField === 'billing_address'
                                            ? 'border-blue-500 bg-blue-50'
                                            : fieldErrors.billing_address
                                                ? 'border-red-500 bg-red-50'
                                                : 'border-gray-300 hover:border-gray-400'
                                    ]"
                                    placeholder="Leave empty if same as legal address"
                                ></textarea>
                                <p class="mt-1 text-sm text-gray-500">
                                    Leave empty if billing address is the same as legal address
                                </p>
                                <p v-if="fieldErrors.billing_address" class="mt-2 text-sm text-red-600">
                                    {{ fieldErrors.billing_address }}
                                </p>
                            </div>

                            <!-- City -->
                            <div>
                                <label for="city" class="block text-sm font-semibold text-gray-700 mb-2">
                                    City <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="city"
                                    v-model="form.city"
                                    type="text"
                                    @focus="setFocus('city')"
                                    @blur="clearFocus('city')"
                                    :class="[
                                        'w-full px-4 py-3 border-2 rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                                        focusedField === 'city'
                                            ? 'border-blue-500 bg-blue-50'
                                            : fieldErrors.city
                                                ? 'border-red-500 bg-red-50'
                                                : 'border-gray-300 hover:border-gray-400'
                                    ]"
                                    placeholder="New York"
                                    required
                                />
                                <p v-if="fieldErrors.city" class="mt-2 text-sm text-red-600">
                                    {{ fieldErrors.city }}
                                </p>
                            </div>

                            <!-- State/Province -->
                            <div>
                                <label for="state" class="block text-sm font-semibold text-gray-700 mb-2">
                                    State/Province
                                </label>
                                <input
                                    id="state"
                                    v-model="form.state"
                                    type="text"
                                    @focus="setFocus('state')"
                                    @blur="clearFocus('state')"
                                    :class="[
                                        'w-full px-4 py-3 border-2 rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                                        focusedField === 'state'
                                            ? 'border-blue-500 bg-blue-50'
                                            : fieldErrors.state
                                                ? 'border-red-500 bg-red-50'
                                                : 'border-gray-300 hover:border-gray-400'
                                    ]"
                                    placeholder="NY"
                                />
                                <p v-if="fieldErrors.state" class="mt-2 text-sm text-red-600">
                                    {{ fieldErrors.state }}
                                </p>
                            </div>

  
                            <!-- Postal Code -->
                            <div>
                                <label for="postal_code" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Postal Code <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="postal_code"
                                    v-model="form.postal_code"
                                    type="text"
                                    @focus="setFocus('postal_code')"
                                    @blur="clearFocus('postal_code')"
                                    :class="[
                                        'w-full px-4 py-3 border-2 rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                                        focusedField === 'postal_code'
                                            ? 'border-blue-500 bg-blue-50'
                                            : fieldErrors.postal_code
                                                ? 'border-red-500 bg-red-50'
                                                : 'border-gray-300 hover:border-gray-400'
                                    ]"
                                    placeholder="10001"
                                    required
                                />
                                <p v-if="fieldErrors.postal_code" class="mt-2 text-sm text-red-600">
                                    {{ fieldErrors.postal_code }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Business Details Section -->
                    <div class="mb-10">
                        <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.707.293H19a2 2 0 012 2v11a2 2 0 01-2 2h-1m-6 0a1 1 0 00-1 1v-5a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Business Details
                        </h2>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Business Registration Number -->
                            <div>
                                <label for="business_registration_number" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Business Registration Number
                                </label>
                                <input
                                    id="business_registration_number"
                                    v-model="form.business_registration_number"
                                    type="text"
                                    @focus="setFocus('business_registration_number')"
                                    @blur="clearFocus('business_registration_number')"
                                    :class="[
                                        'w-full px-4 py-3 border-2 rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                                        focusedField === 'business_registration_number'
                                            ? 'border-blue-500 bg-blue-50'
                                            : fieldErrors.business_registration_number
                                                ? 'border-red-500 bg-red-50'
                                                : 'border-gray-300 hover:border-gray-400'
                                    ]"
                                    placeholder="123456789"
                                />
                                <p v-if="fieldErrors.business_registration_number" class="mt-2 text-sm text-red-600">
                                    {{ fieldErrors.business_registration_number }}
                                </p>
                            </div>

                            <!-- Tax ID -->
                            <div>
                                <label for="tax_id" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Tax ID / VAT Number
                                </label>
                                <input
                                    id="tax_id"
                                    v-model="form.tax_id"
                                    type="text"
                                    @focus="setFocus('tax_id')"
                                    @blur="clearFocus('tax_id')"
                                    :class="[
                                        'w-full px-4 py-3 border-2 rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                                        focusedField === 'tax_id'
                                            ? 'border-blue-500 bg-blue-50'
                                            : fieldErrors.tax_id
                                                ? 'border-red-500 bg-red-50'
                                                : 'border-gray-300 hover:border-gray-400'
                                    ]"
                                    placeholder="TAX123456"
                                />
                                <p v-if="fieldErrors.tax_id" class="mt-2 text-sm text-red-600">
                                    {{ fieldErrors.tax_id }}
                                </p>
                            </div>

                            <!-- Description -->
                            <div class="lg:col-span-2">
                                <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Business Description
                                </label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="4"
                                    @focus="setFocus('description')"
                                    @blur="clearFocus('description')"
                                    :class="[
                                        'w-full px-4 py-3 border-2 rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none',
                                        focusedField === 'description'
                                            ? 'border-blue-500 bg-blue-50'
                                            : fieldErrors.description
                                                ? 'border-red-500 bg-red-50'
                                                : 'border-gray-300 hover:border-gray-400'
                                    ]"
                                    placeholder="Tell us about your business, your target audience, and how you plan to promote our car rental services to generate commissions..."
                                ></textarea>
                                <div class="mt-2 flex items-center justify-between">
                                    <p class="text-sm text-gray-500">
                                        {{ form.description ? form.description.length : 0 }}/2000 characters
                                    </p>
                                    <div class="w-32 bg-gray-200 rounded-full h-2">
                                        <div
                                            class="bg-blue-600 h-2 rounded-full transition-all duration-200"
                                            :style="{ width: Math.min((form.description?.length || 0) / 2000 * 100, 100) + '%' }"
                                        ></div>
                                    </div>
                                </div>
                                <p v-if="fieldErrors.description" class="mt-2 text-sm text-red-600">
                                    {{ fieldErrors.description }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Terms and Submit -->
                    <div class="border-t pt-8">
                        <div class="mb-6">
                            <div class="flex items-start">
                                <input
                                    id="accept_terms"
                                    v-model="form.accept_terms"
                                    type="checkbox"
                                    class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
                                    required
                                />
                                <label for="accept_terms" class="ml-3 text-sm text-gray-700 leading-relaxed">
                                    I agree to the
                                    <Link :href="route('pages.show', { locale, slug: 'terms-and-conditions' })" class="text-blue-600 hover:text-blue-800 underline font-medium">
                                        Terms and Conditions
                                    </Link>
                                    and
                                    <Link :href="route('pages.show', { locale, slug: 'privacy-policy' })" class="text-blue-600 hover:text-blue-800 underline font-medium">
                                        Privacy Policy
                                    </Link>
                                    of the Vrooem Affiliate Program. I understand that my business will be subject to verification.
                                </label>
                            </div>
                            <p v-if="fieldErrors.accept_terms" class="mt-2 text-sm text-red-600">
                                {{ fieldErrors.accept_terms }}
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-center">
                            <button
                                type="submit"
                                :disabled="!isFormValid || isSubmitting"
                                :class="[
                                    'inline-flex items-center px-8 py-4 border border-transparent text-lg font-semibold rounded-lg text-white transition-all duration-200',
                                    isFormValid && !isSubmitting
                                        ? 'bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 transform hover:scale-105 shadow-lg'
                                        : 'bg-gray-400 cursor-not-allowed'
                                ]"
                            >
                                <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ isSubmitting ? 'Registering Business...' : 'Register Business' }}
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Login Form -->
                <div v-else-if="activeTab === 'login'" class="p-8">
                    <!-- Error/Success Messages -->
                    <div v-if="loginError" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-red-700">{{ loginError }}</span>
                        </div>
                    </div>

                    <div v-if="forgotSuccess" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-green-700">{{ forgotSuccess }}</span>
                        </div>
                    </div>

                    <!-- Login Form -->
                    <form v-if="!forgotSuccess && !showForgotForm" @submit.prevent="submitLogin">
                        <div class="mb-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                </svg>
                                Dashboard Access
                            </h2>

                            <div class="space-y-6">
                                <!-- Email Field -->
                                <div>
                                    <label for="login_email" class="block text-sm font-semibold text-gray-700 mb-2">
                                        Email Address <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <input
                                            id="login_email"
                                            v-model="loginForm.email"
                                            type="email"
                                            @input="resetLoginError"
                                            class="w-full pl-10 pr-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                                            placeholder="contact@yourbusiness.com"
                                            required
                                        />
                                    </div>
                                </div>

                                <!-- Dashboard Access Token Field -->
                                <div>
                                    <label for="dashboard_token" class="block text-sm font-semibold text-gray-700 mb-2">
                                        Dashboard Access Token <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                            </svg>
                                        </div>
                                        <input
                                            id="dashboard_token"
                                            v-model="loginForm.dashboard_token"
                                            type="text"
                                            @input="resetLoginError"
                                            class="w-full pl-10 pr-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 font-mono text-sm"
                                            placeholder="AFF-XXXXXXXX-XXXX"
                                            required
                                        />
                                    </div>
                                    <p class="mt-2 text-sm text-gray-500">
                                        Enter the dashboard access token you received in your email
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-center">
                            <button
                                type="submit"
                                :disabled="isSubmittingLogin || !loginForm.email || !loginForm.dashboard_token"
                                :class="[
                                    'inline-flex items-center px-8 py-4 border border-transparent text-lg font-semibold rounded-lg text-white transition-all duration-200',
                                    !isSubmittingLogin && loginForm.email && loginForm.dashboard_token
                                        ? 'bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 transform hover:scale-105 shadow-lg'
                                        : 'bg-gray-400 cursor-not-allowed'
                                ]"
                            >
                                <svg v-if="isSubmittingLogin" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                </svg>
                                {{ isSubmittingLogin ? 'Accessing Dashboard...' : 'Access Dashboard' }}
                            </button>
                        </div>

                        <!-- Forget Access Key Link -->
                        <div class="mt-6 text-center">
                            <button
                                type="button"
                                @click="showForgotAccessForm"
                                class="text-blue-600 hover:text-blue-800 text-sm font-medium underline"
                            >
                                Forgot or lost your access key?
                            </button>
                        </div>
                    </form>

                    <!-- Forgot Access Form -->
                    <div v-else-if="forgotSuccess || showForgotForm" class="space-y-6">
                        <div class="mb-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4A2 2 0 0111 4.07V7M3 19l6.75-3.75M3 19l18 0m-18 0l6.75 3.75M21 19V4.07a2 2 0 00-1.11-1.664l-7-4A2 2 0 009 0v7m0 0l6.75 3.75M21 19l-6.75 3.75M21 19l-6.75-3.75M9 7h6m0 0v4m0-4l3 3m-3-3L6 10" />
                                </svg>
                                Request New Access Key
                            </h2>
                            <p class="text-gray-600 mb-6">
                                Enter your registered email address and we'll send you a new dashboard access link.
                            </p>

                            <!-- Email Field for Forgot -->
                            <div>
                                <label for="forgot_email" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Registered Email Address <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <input
                                        id="forgot_email"
                                        v-model="forgotEmailForm.email"
                                        type="email"
                                        @input="resetForgotError"
                                        class="w-full pl-10 pr-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                                        placeholder="contact@yourbusiness.com"
                                        required
                                    />
                                </div>
                                <p v-if="forgotError" class="mt-2 text-sm text-red-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    {{ forgotError }}
                                </p>
                            </div>
                        </div>

                        <!-- Submit and Back Buttons -->
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <button
                                type="button"
                                @click="submitForgotAccess"
                                :disabled="isSubmittingForgot || !forgotEmailForm.email"
                                :class="[
                                    'inline-flex items-center px-6 py-3 border border-transparent text-base font-semibold rounded-lg text-white transition-all duration-200',
                                    !isSubmittingForgot && forgotEmailForm.email
                                        ? 'bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 transform hover:scale-105 shadow-lg'
                                        : 'bg-gray-400 cursor-not-allowed'
                                ]"
                            >
                                <svg v-if="isSubmittingForgot" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4A2 2 0 0111 4.07V7M3 19l6.75-3.75M3 19l18 0" />
                                </svg>
                                {{ isSubmittingForgot ? 'Sending...' : 'Send New Access Link' }}
                            </button>
                            <button
                                type="button"
                                @click="showLoginForm"
                                class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                            >
                                <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Back to Login
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Benefits Section -->
            <div class="mt-12 bg-white shadow-xl rounded-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-purple-600 to-pink-600 px-8 py-6">
                    <h2 class="text-2xl font-bold text-white text-center">
                        Why Join the Vrooem Affiliate Program?
                    </h2>
                </div>
                <div class="p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="flex items-start group">
                            <div class="flex-shrink-0 w-12 h-12 bg-green-100 rounded-full flex items-center justify-center group-hover:bg-green-200 transition-colors">
                                <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900 mb-1">Earn Competitive Commissions</h3>
                                <p class="text-gray-600">Generate up to 15% commission from every booking made through your QR codes with real-time tracking.</p>
                            </div>
                        </div>

                        <div class="flex items-start group">
                            <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                                <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2zm0 8c1.11 0 2.08.402 2.5 1m-4.5 2.5a4.5 4.5 0 009 0 4.5 4.5 0 00-9 0z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900 mb-1">Offer Customer Discounts</h3>
                                <p class="text-gray-600">Provide exclusive 5-20% car rental deals to your customers, making your services more attractive.</p>
                            </div>
                        </div>

                        <div class="flex items-start group">
                            <div class="flex-shrink-0 w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center group-hover:bg-purple-200 transition-colors">
                                <svg class="w-6 h-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V8a2 2 0 012-2h2a2 2 0 012 2v10a4 4 0 008 0M4 8h16" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900 mb-1">Easy Setup & Management</h3>
                                <p class="text-gray-600">Generate QR codes instantly, track performance in real-time, and manage multiple locations from one dashboard.</p>
                            </div>
                        </div>

                        <div class="flex items-start group">
                            <div class="flex-shrink-0 w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center group-hover:bg-yellow-200 transition-colors">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2v1a2 2 0 002 2v-1a2 2 0 012-2V12a2 2 0 012-2V8a2 2 0 00-2-2h-.97M10 16v4m0-4h3m-3 0h.5M13 19h6.5a.5.5 0 00.5-.5v-2.5a.5.5 0 00-.5-.5h-2.5a.5.5 0 00-.5.5v2.5a.5.5 0 00.5.5h2.5a.5.5 0 00.5-.5v-2.5z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900 mb-1">Multi-Location Support</h3>
                                <p class="text-gray-600">Perfect for hotel chains and businesses with multiple locations. Manage all your QR codes from one account.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
  
