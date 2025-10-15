<script setup>
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const props = defineProps({
    locale: {
        type: String,
        required: true,
    },
});

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
    business_registration_number: '',
    tax_id: '',
    description: '',
    accept_terms: false,
});

const isSubmitting = ref(false);
const emailChecking = ref(false);
const emailExists = ref(false);
const emailInvalid = ref(false);
const emailError = ref('');

const businessTypes = [
    { value: 'hotel', label: 'Hotel' },
    { value: 'hotel_chain', label: 'Hotel Chain' },
    { value: 'travel_agent', label: 'Travel Agent' },
    { value: 'partner', label: 'Partner' },
    { value: 'corporate', label: 'Corporate' },
];

const countries = [
    { value: 'US', label: 'United States' },
    { value: 'CA', label: 'Canada' },
    { value: 'GB', label: 'United Kingdom' },
    { value: 'FR', label: 'France' },
    { value: 'DE', label: 'Germany' },
    { value: 'IT', label: 'Italy' },
    { value: 'ES', label: 'Spain' },
    { value: 'NL', label: 'Netherlands' },
    { value: 'BE', label: 'Belgium' },
    { value: 'CH', label: 'Switzerland' },
    { value: 'AT', label: 'Austria' },
    { value: 'IE', label: 'Ireland' },
    { value: 'PT', label: 'Portugal' },
    { value: 'SE', label: 'Sweden' },
    { value: 'NO', label: 'Norway' },
    { value: 'DK', label: 'Denmark' },
    { value: 'FI', label: 'Finland' },
    { value: 'PL', label: 'Poland' },
    { value: 'CZ', label: 'Czech Republic' },
    { value: 'HU', label: 'Hungary' },
];

const isHotelChain = computed(() => form.business_type === 'hotel_chain');

const isValidEmail = computed(() => {
    if (!form.contact_email) {
        console.log('📧 Email validation: empty email');
        return false;
    }

    // Basic format check - let Laravel handle strict validation
    const hasAtSymbol = form.contact_email.includes('@');
    const hasDot = form.contact_email.includes('.');
    const parts = form.contact_email.split('@');
    const hasValidStructure = hasAtSymbol && hasDot && parts.length === 2 && parts[0].length > 0 && parts[1].length > 0;

    console.log('📧 Email validation:', {
        email: form.contact_email,
        hasValidStructure: hasValidStructure,
        note: 'Laravel will handle strict validation on submit'
    });

    return hasValidStructure;
});

const emailErrorMessage = computed(() => {
    if (!form.contact_email) return '';
    if (!isValidEmail.value) return 'Please enter a valid email address';
    if (emailExists.value) return 'This email is already registered';
    if (emailInvalid.value) return 'Invalid email address';
    return '';
});

const checkEmailExists = async () => {
    console.log('🔍 Checking email:', form.contact_email);

    if (!form.contact_email || form.contact_email.length < 3) {
        console.log('❌ Email too short or empty');
        emailExists.value = false;
        emailInvalid.value = false;
        return;
    }

    // Use the same validation as isValidEmail
    if (!isValidEmail.value) {
        console.log('❌ Email format invalid:', form.contact_email);
        emailExists.value = false;
        emailInvalid.value = false;
        return;
    }

    console.log('✅ Email format valid, checking availability...');
    emailChecking.value = true;

    try {
        const apiUrl = `/${props.locale}/business/check-email`;
        console.log('📡 Making request to:', apiUrl);

        const response = await axios.post(apiUrl, {
            email: form.contact_email
        });

        console.log('📥 Response received:', response.data);

        if (response.data.valid === false) {
            console.log('❌ Laravel validation failed - invalid email');
            emailInvalid.value = true;
            emailExists.value = false;
        } else {
            emailInvalid.value = false;
            emailExists.value = response.data.exists;
            console.log('🎯 Email exists:', emailExists.value);
        }
    } catch (error) {
        console.error('💥 Error checking email:', error);
        console.error('Response:', error.response);
        emailExists.value = false;
        emailInvalid.value = false;
    } finally {
        emailChecking.value = false;
        console.log('✅ Email check completed');
    }
};

const resetEmailExists = () => {
    emailExists.value = false;
    emailInvalid.value = false;
};

const submit = () => {
    isSubmitting.value = true;
    form.post(route('affiliate.business.store'), {
        onFinish: () => {
            isSubmitting.value = false;
        },
        onError: (errors) => {
            console.error('Form errors:', errors);
        },
    });
};

const isFormValid = computed(() => {
    return form.business_type &&
           form.name.trim() &&
           form.contact_email.trim() &&
           isValidEmail.value &&
           !emailExists.value &&
           !emailInvalid.value &&
           form.contact_phone.trim() &&
           form.legal_address.trim() &&
           form.city.trim() &&
           form.country.trim() &&
           form.postal_code.trim() &&
           form.accept_terms;
});
</script>

<template>
    <Head title="Business Registration - Vrooem Affiliate Program" />

    <AuthenticatedHeaderLayout/>

    <div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-8">
                <Link :href="route('welcome', { locale })" class="inline-block">
                    <ApplicationLogo class="mx-auto h-12 w-auto" />
                </Link>
                <h1 class="mt-6 text-3xl font-bold text-gray-900">
                    Join the Vrooem Affiliate Program
                </h1>
                <p class="mt-2 text-gray-600">
                    Register your business to offer exclusive car rental discounts to your customers
                </p>
            </div>

            <div class="bg-white shadow-lg rounded-lg p-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Business Type -->
                    <div>
                        <label for="business_type" class="block text-sm font-medium text-gray-700">
                            Business Type <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="business_type"
                            v-model="form.business_type"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            required
                        >
                            <option value="">Select a business type</option>
                            <option v-for="type in businessTypes" :key="type.value" :value="type.value">
                                {{ type.label }}
                            </option>
                        </select>
                        <p v-if="form.errors.business_type" class="mt-1 text-sm text-red-600">
                            {{ form.errors.business_type }}
                        </p>
                    </div>

                    <!-- Business Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">
                            Business Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Your business name"
                            required
                        />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- Contact Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="contact_email" class="block text-sm font-medium text-gray-700">
                                Contact Email <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="contact_email"
                                v-model="form.contact_email"
                                type="email"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                :class="{
                                    'border-red-500': emailErrorMessage,
                                    'border-green-500': form.contact_email && isValidEmail.value && !emailExists.value
                                }"
                                placeholder="contact@yourbusiness.com"
                                @blur="checkEmailExists"
                                @input="resetEmailExists"
                                required
                            />
                            <p v-if="emailChecking" class="mt-1 text-sm text-blue-600">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Checking email availability...
                            </p>
                            <p v-if="emailErrorMessage" class="mt-1 text-sm" :class="emailExists.value ? 'text-red-600' : 'text-red-500'">
                                {{ emailErrorMessage }}
                            </p>
                            <p v-else-if="form.contact_email && isValidEmail.value && !emailExists.value && !emailChecking.value" class="mt-1 text-sm text-green-600">
                                ✓ Email is available
                            </p>
                            <p v-if="form.errors.contact_email" class="mt-1 text-sm text-red-600">
                                {{ form.errors.contact_email }}
                            </p>
                        </div>

                        <div>
                            <label for="contact_phone" class="block text-sm font-medium text-gray-700">
                                Contact Phone <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="contact_phone"
                                v-model="form.contact_phone"
                                type="tel"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                placeholder="+1 (555) 123-4567"
                                required
                            />
                            <p v-if="form.errors.contact_phone" class="mt-1 text-sm text-red-600">
                                {{ form.errors.contact_phone }}
                            </p>
                        </div>
                    </div>

                    <!-- Website -->
                    <div>
                        <label for="website" class="block text-sm font-medium text-gray-700">
                            Website
                        </label>
                        <input
                            id="website"
                            v-model="form.website"
                            type="url"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            placeholder="https://yourbusiness.com"
                        />
                        <p v-if="form.errors.website" class="mt-1 text-sm text-red-600">
                            {{ form.errors.website }}
                        </p>
                    </div>

                    <!-- Address Information -->
                    <div>
                        <label for="legal_address" class="block text-sm font-medium text-gray-700">
                            Legal Address <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            id="legal_address"
                            v-model="form.legal_address"
                            rows="3"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            placeholder="123 Main Street, Suite 100"
                            required
                        ></textarea>
                        <p v-if="form.errors.legal_address" class="mt-1 text-sm text-red-600">
                            {{ form.errors.legal_address }}
                        </p>
                    </div>

                    <div>
                        <label for="billing_address" class="block text-sm font-medium text-gray-700">
                            Billing Address
                        </label>
                        <textarea
                            id="billing_address"
                            v-model="form.billing_address"
                            rows="3"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Leave empty if same as legal address"
                        ></textarea>
                        <p v-if="form.errors.billing_address" class="mt-1 text-sm text-red-600">
                            {{ form.errors.billing_address }}
                        </p>
                    </div>

                    <!-- Location Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="city" class="block text-sm font-medium text-gray-700">
                                City <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="city"
                                v-model="form.city"
                                type="text"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                placeholder="New York"
                                required
                            />
                            <p v-if="form.errors.city" class="mt-1 text-sm text-red-600">
                                {{ form.errors.city }}
                            </p>
                        </div>

                        <div>
                            <label for="state" class="block text-sm font-medium text-gray-700">
                                State/Province
                            </label>
                            <input
                                id="state"
                                v-model="form.state"
                                type="text"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                placeholder="NY"
                            />
                            <p v-if="form.errors.state" class="mt-1 text-sm text-red-600">
                                {{ form.errors.state }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="country" class="block text-sm font-medium text-gray-700">
                                Country <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="country"
                                v-model="form.country"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                required
                            >
                                <option value="">Select a country</option>
                                <option v-for="country in countries" :key="country.value" :value="country.value">
                                    {{ country.label }}
                                </option>
                            </select>
                            <p v-if="form.errors.country" class="mt-1 text-sm text-red-600">
                                {{ form.errors.country }}
                            </p>
                        </div>

                        <div>
                            <label for="postal_code" class="block text-sm font-medium text-gray-700">
                                Postal Code <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="postal_code"
                                v-model="form.postal_code"
                                type="text"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                placeholder="10001"
                                required
                            />
                            <p v-if="form.errors.postal_code" class="mt-1 text-sm text-red-600">
                                {{ form.errors.postal_code }}
                            </p>
                        </div>
                    </div>

                    <!-- Business Registration Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="business_registration_number" class="block text-sm font-medium text-gray-700">
                                Business Registration Number
                            </label>
                            <input
                                id="business_registration_number"
                                v-model="form.business_registration_number"
                                type="text"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                placeholder="123456789"
                            />
                            <p v-if="form.errors.business_registration_number" class="mt-1 text-sm text-red-600">
                                {{ form.errors.business_registration_number }}
                            </p>
                        </div>

                        <div>
                            <label for="tax_id" class="block text-sm font-medium text-gray-700">
                                Tax ID
                            </label>
                            <input
                                id="tax_id"
                                v-model="form.tax_id"
                                type="text"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                placeholder="TAX123456"
                            />
                            <p v-if="form.errors.tax_id" class="mt-1 text-sm text-red-600">
                                {{ form.errors.tax_id }}
                            </p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">
                            Business Description
                        </label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="4"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Tell us about your business and how you plan to promote our car rental services..."
                        ></textarea>
                        <p class="mt-1 text-sm text-gray-500">
                            Maximum 2000 characters
                        </p>
                        <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">
                            {{ form.errors.description }}
                        </p>
                    </div>

                    <!-- Terms and Conditions -->
                    <div>
                        <div class="flex items-start">
                            <input
                                id="accept_terms"
                                v-model="form.accept_terms"
                                type="checkbox"
                                class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                required
                            />
                            <label for="accept_terms" class="ml-2 text-sm text-gray-700">
                                I agree to the <Link :href="route('pages.show', { locale, slug: 'terms-and-conditions' })" class="text-blue-600 hover:text-blue-800">Terms and Conditions</Link> and <Link :href="route('pages.show', { locale, slug: 'privacy-policy' })" class="text-blue-600 hover:text-blue-800">Privacy Policy</Link> of the Vrooem Affiliate Program.
                            </label>
                        </div>
                        <p v-if="form.errors.accept_terms" class="mt-1 text-sm text-red-600">
                            {{ form.errors.accept_terms }}
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="!isFormValid || isSubmitting"
                            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ isSubmitting ? 'Submitting...' : 'Register Business' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Benefits Section -->
            <div class="mt-12 bg-white shadow-lg rounded-lg p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Why Join the Vrooem Affiliate Program?</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-medium text-gray-900">Earn Competitive Commissions</h3>
                            <p class="mt-1 text-gray-600">Generate revenue from every booking made through your QR codes.</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-medium text-gray-900">Offer Customer Discounts</h3>
                            <p class="mt-1 text-gray-600">Provide exclusive car rental deals to your customers.</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-medium text-gray-900">Easy Setup & Management</h3>
                            <p class="mt-1 text-gray-600">Generate QR codes instantly and track performance in real-time.</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-medium text-gray-900">Multi-Location Support</h3>
                            <p class="mt-1 text-gray-600">Perfect for hotel chains and businesses with multiple locations.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>