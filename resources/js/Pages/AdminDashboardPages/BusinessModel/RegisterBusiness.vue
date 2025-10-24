<script setup>
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';

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
    verification_status: 'pending',
    status: 'pending',
    send_welcome_email: true,
});

const isSubmitting = ref(false);
const fieldErrors = ref({});
const showSuccess = ref(false);
const successMessage = ref('');

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
        const response = await fetch('/currency.json');
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
        const response = await fetch('/countries.json');
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
        if (phoneNumberOnly.value) {
            form.contact_phone = countryCode + phoneNumberOnly.value;
        } else {
            form.contact_phone = countryCode;
        }
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

// Validation rules
const validationRules = {
    name: {
        required: true,
        minLength: 2,
        maxLength: 100,
        message: 'Business name must be 2-100 characters'
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

// Focus management
const setFocus = (fieldName) => {
    // Clear field error when user starts typing
    if (fieldErrors.value[fieldName]) {
        delete fieldErrors.value[fieldName];
    }
};

const clearFocus = (fieldName) => {
    // Validate field on blur
    validateField(fieldName, form[fieldName]);
};

// Website formatting
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
    fieldErrors.value = {};

    try {
        const response = await form.post(route('admin.affiliate.business-register.store'), {
            onFinish: () => {
                isSubmitting.value = false;
            },
            onSuccess: (page) => {
                showSuccess.value = true;
                successMessage.value = page.props.flash?.success || 'Business registered successfully!';
                // Reset form
                form.reset();
                phoneNumberOnly.value = '';
                setTimeout(() => {
                    showSuccess.value = false;
                }, 5000);
            },
            onError: (errors) => {
                // Convert Laravel errors to our format
                Object.keys(errors).forEach(field => {
                    fieldErrors.value[field] = errors[field];
                });
                isSubmitting.value = false;
            },
        });
    } catch (error) {
        console.error('Submission error:', error);
        isSubmitting.value = false;
    }
};

// Enhanced form validation
const isFormValid = computed(() => {
    const requiredFields = ['business_type', 'name', 'contact_email', 'contact_phone', 'legal_address', 'city', 'country', 'postal_code', 'currency'];

    for (const field of requiredFields) {
        if (!form[field] || !form[field].trim()) {
            return false;
        }
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
        'legal_address', 'city', 'country', 'postal_code', 'currency'
    ];

    const filledFields = fields.filter(field => {
        return form[field] && form[field].trim().length > 0;
    });

    return Math.round((filledFields.length / fields.length) * 100);
});
</script>

<template>
    <Head title="Register Business - Admin Dashboard" />

    <AdminDashboardLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Page Header -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Register New Business</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Register a new business for the affiliate program. As an admin, you can instantly verify and activate businesses.
                    </p>
                </div>

                <!-- Success Alert -->
                <div v-if="showSuccess" class="mb-6 rounded-md bg-green-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">
                                {{ successMessage }}
                            </p>
                        </div>
                    </div>
                </div>

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

                <!-- Registration Form -->
                <div class="bg-white shadow-lg rounded-lg">
                    <form @submit.prevent="submit" class="p-6 space-y-8">
                        <!-- Admin Options Section -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.586-4H5a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V7.414z" />
                                </svg>
                                Admin Options
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Verification Status -->
                                <div>
                                    <label for="verification_status" class="block text-sm font-medium text-gray-700 mb-2">
                                        Verification Status
                                    </label>
                                    <select
                                        id="verification_status"
                                        v-model="form.verification_status"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                    >
                                        <option value="pending">Pending</option>
                                        <option value="verified">Verified</option>
                                        <option value="rejected">Rejected</option>
                                    </select>
                                </div>

                                <!-- Business Status -->
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                        Business Status
                                    </label>
                                    <select
                                        id="status"
                                        v-model="form.status"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                    >
                                        <option value="pending">Pending</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="suspended">Suspended</option>
                                    </select>
                                </div>

                                <!-- Send Welcome Email -->
                                <div class="md:col-span-2">
                                    <div class="flex items-center">
                                        <input
                                            id="send_welcome_email"
                                            v-model="form.send_welcome_email"
                                            type="checkbox"
                                            class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                        />
                                        <label for="send_welcome_email" class="ml-2 text-sm text-gray-700">
                                            Send welcome email to business with dashboard access details
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Business Information Section -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                Business Information
                            </h2>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <!-- Business Type -->
                                <div>
                                    <label for="business_type" class="block text-sm font-medium text-gray-700 mb-2">
                                        Business Type <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="business_type"
                                        v-model="form.business_type"
                                        @focus="setFocus('business_type')"
                                        @blur="clearFocus('business_type')"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        required
                                    >
                                        <option value="">Select business type</option>
                                        <option v-for="type in businessTypes" :key="type.value" :value="type.value">
                                            {{ type.label }} - {{ type.description }}
                                        </option>
                                    </select>
                                    <p v-if="fieldErrors.business_type" class="mt-1 text-sm text-red-600">
                                        {{ fieldErrors.business_type }}
                                    </p>
                                </div>

                                <!-- Business Name -->
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Business Name <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        @focus="setFocus('name')"
                                        @blur="clearFocus('name')"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Enter business name"
                                        required
                                    />
                                    <p v-if="fieldErrors.name" class="mt-1 text-sm text-red-600">
                                        {{ fieldErrors.name }}
                                    </p>
                                </div>

                                <!-- Currency Selection -->
                                <div>
                                    <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">
                                        Business Currency <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="selectedCurrency"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        required
                                    >
                                        <option value="">Select currency</option>
                                        <option v-for="currency in currencies" :key="currency.code" :value="currency.code">
                                            {{ currency.code }} ({{ currency.symbol }})
                                        </option>
                                    </select>
                                    <p v-if="fieldErrors.currency" class="mt-1 text-sm text-red-600">
                                        {{ fieldErrors.currency }}
                                    </p>
                                </div>

                                <!-- Contact Email -->
                                <div>
                                    <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-2">
                                        Contact Email <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="contact_email"
                                        v-model="form.contact_email"
                                        type="email"
                                        @focus="setFocus('contact_email')"
                                        @blur="clearFocus('contact_email')"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="contact@business.com"
                                        required
                                    />
                                    <p v-if="fieldErrors.contact_email" class="mt-1 text-sm text-red-600">
                                        {{ fieldErrors.contact_email }}
                                    </p>
                                </div>

                                <!-- Country Selection -->
                                <div>
                                    <label for="country" class="block text-sm font-medium text-gray-700 mb-2">
                                        Country <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.country"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        required
                                    >
                                        <option value="">Select a country</option>
                                        <option v-for="country in countries" :key="country.code" :value="country.code">
                                            {{ country.name }}
                                        </option>
                                    </select>
                                    <p v-if="fieldErrors.country" class="mt-1 text-sm text-red-600">
                                        {{ fieldErrors.country }}
                                    </p>
                                </div>

                                <!-- Contact Phone -->
                                <div>
                                    <label for="contact_phone" class="block text-sm font-medium text-gray-700 mb-2">
                                        Contact Phone <span class="text-red-500">*</span>
                                    </label>
                                    <div class="flex">
                                        <div class="inline-flex items-center px-3 border border-r-0 border-gray-300 bg-gray-50 text-gray-500 rounded-l-md">
                                            <span class="text-sm">{{ getCountryCode(form.country) }}</span>
                                        </div>
                                        <input
                                            type="tel"
                                            v-model="phoneNumberOnly"
                                            @focus="setFocus('contact_phone')"
                                            @blur="clearFocus('contact_phone')"
                                            @input="updateContactPhone"
                                            :disabled="!form.country"
                                            class="block w-full flex-1 px-3 py-2 border border-gray-300 rounded-r-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="1234567890"
                                            required
                                        />
                                    </div>
                                    <p v-if="!form.country" class="mt-1 text-sm text-gray-500">
                                        Please select a country first
                                    </p>
                                    <p v-if="fieldErrors.contact_phone" class="mt-1 text-sm text-red-600">
                                        {{ fieldErrors.contact_phone }}
                                    </p>
                                </div>

                                <!-- Website -->
                                <div class="lg:col-span-2">
                                    <label for="website" class="block text-sm font-medium text-gray-700 mb-2">
                                        Website
                                    </label>
                                    <input
                                        id="website"
                                        v-model="form.website"
                                        type="url"
                                        @focus="setFocus('website')"
                                        @blur="clearFocus('website')"
                                        @input="formatWebsite"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="https://business.com"
                                    />
                                    <p v-if="fieldErrors.website" class="mt-1 text-sm text-red-600">
                                        {{ fieldErrors.website }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Address Information Section -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Address Information
                            </h2>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <!-- Legal Address -->
                                <div class="lg:col-span-2">
                                    <label for="legal_address" class="block text-sm font-medium text-gray-700 mb-2">
                                        Legal Address <span class="text-red-500">*</span>
                                    </label>
                                    <textarea
                                        id="legal_address"
                                        v-model="form.legal_address"
                                        rows="3"
                                        @focus="setFocus('legal_address')"
                                        @blur="clearFocus('legal_address')"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="123 Main Street, Suite 100, City, Country"
                                        required
                                    ></textarea>
                                    <p v-if="fieldErrors.legal_address" class="mt-1 text-sm text-red-600">
                                        {{ fieldErrors.legal_address }}
                                    </p>
                                </div>

                                <!-- Billing Address -->
                                <div class="lg:col-span-2">
                                    <label for="billing_address" class="block text-sm font-medium text-gray-700 mb-2">
                                        Billing Address
                                    </label>
                                    <textarea
                                        id="billing_address"
                                        v-model="form.billing_address"
                                        rows="3"
                                        @focus="setFocus('billing_address')"
                                        @blur="clearFocus('billing_address')"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Leave empty if same as legal address"
                                    ></textarea>
                                    <p class="mt-1 text-sm text-gray-500">
                                        Leave empty if billing address is the same as legal address
                                    </p>
                                </div>

                                <!-- City -->
                                <div>
                                    <label for="city" class="block text-sm font-medium text-gray-700 mb-2">
                                        City <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="city"
                                        v-model="form.city"
                                        type="text"
                                        @focus="setFocus('city')"
                                        @blur="clearFocus('city')"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="City"
                                        required
                                    />
                                    <p v-if="fieldErrors.city" class="mt-1 text-sm text-red-600">
                                        {{ fieldErrors.city }}
                                    </p>
                                </div>

                                <!-- State/Province -->
                                <div>
                                    <label for="state" class="block text-sm font-medium text-gray-700 mb-2">
                                        State/Province
                                    </label>
                                    <input
                                        id="state"
                                        v-model="form.state"
                                        type="text"
                                        @focus="setFocus('state')"
                                        @blur="clearFocus('state')"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="State"
                                    />
                                    <p v-if="fieldErrors.state" class="mt-1 text-sm text-red-600">
                                        {{ fieldErrors.state }}
                                    </p>
                                </div>

                                <!-- Postal Code -->
                                <div>
                                    <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-2">
                                        Postal Code <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="postal_code"
                                        v-model="form.postal_code"
                                        type="text"
                                        @focus="setFocus('postal_code')"
                                        @blur="clearFocus('postal_code')"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Postal code"
                                        required
                                    />
                                    <p v-if="fieldErrors.postal_code" class="mt-1 text-sm text-red-600">
                                        {{ fieldErrors.postal_code }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Business Details Section -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.707.293H19a2 2 0 012 2v11a2 2 0 01-2 2h-1m-6 0a1 1 0 00-1 1v-5a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 001 1m-6 0h6" />
                                </svg>
                                Business Details
                            </h2>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <!-- Business Registration Number -->
                                <div>
                                    <label for="business_registration_number" class="block text-sm font-medium text-gray-700 mb-2">
                                        Business Registration Number
                                    </label>
                                    <input
                                        id="business_registration_number"
                                        v-model="form.business_registration_number"
                                        type="text"
                                        @focus="setFocus('business_registration_number')"
                                        @blur="clearFocus('business_registration_number')"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Registration number"
                                    />
                                    <p v-if="fieldErrors.business_registration_number" class="mt-1 text-sm text-red-600">
                                        {{ fieldErrors.business_registration_number }}
                                    </p>
                                </div>

                                <!-- Tax ID -->
                                <div>
                                    <label for="tax_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        Tax ID / VAT Number
                                    </label>
                                    <input
                                        id="tax_id"
                                        v-model="form.tax_id"
                                        type="text"
                                        @focus="setFocus('tax_id')"
                                        @blur="clearFocus('tax_id')"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Tax ID"
                                    />
                                    <p v-if="fieldErrors.tax_id" class="mt-1 text-sm text-red-600">
                                        {{ fieldErrors.tax_id }}
                                    </p>
                                </div>

                                <!-- Description -->
                                <div class="lg:col-span-2">
                                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                        Business Description
                                    </label>
                                    <textarea
                                        id="description"
                                        v-model="form.description"
                                        rows="4"
                                        @focus="setFocus('description')"
                                        @blur="clearFocus('description')"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Describe the business and how they plan to promote car rentals..."
                                    ></textarea>
                                    <div class="mt-2 flex items-center justify-between">
                                        <p class="text-sm text-gray-500">
                                            {{ form.description ? form.description.length : 0 }}/2000 characters
                                        </p>
                                    </div>
                                    <p v-if="fieldErrors.description" class="mt-1 text-sm text-red-600">
                                        {{ fieldErrors.description }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="border-t pt-6">
                            <div class="flex justify-end space-x-3">
                                <button
                                    type="button"
                                    @click="form.reset()"
                                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                >
                                    Reset Form
                                </button>
                                <button
                                    type="submit"
                                    :disabled="!isFormValid || isSubmitting"
                                    class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:bg-gray-400 disabled:cursor-not-allowed"
                                >
                                    <span v-if="isSubmitting">Registering...</span>
                                    <span v-else>Register Business</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        </AdminDashboardLayout>
</template>