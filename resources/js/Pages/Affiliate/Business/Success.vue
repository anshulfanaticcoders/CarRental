<script setup>
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    business: {
        type: Object,
        required: true,
    },
    locale: {
        type: String,
        required: true,
    },
});

const formattedRegistrationDate = new Date(props.business.created_at).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
});
</script>

<template>
    <Head :title="'Registration Successful - ' + business.name" />

    <div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto">
            <!-- Success Message -->
            <div class="text-center mb-8">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                    <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    Registration Successful!
                </h1>
                <p class="text-lg text-gray-600">
                    Your business has been registered successfully
                </p>
            </div>

            <!-- Business Information Card -->
            <div class="bg-white shadow-lg rounded-lg p-8 mb-8">
                <div class="border-b border-gray-200 pb-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Business Information</h2>
                    <p class="text-sm text-gray-500 mt-1">Registration ID: {{ business.uuid }}</p>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Business Name</p>
                            <p class="mt-1 text-lg text-gray-900">{{ business.name }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Business Type</p>
                            <p class="mt-1 text-lg text-gray-900 capitalize">{{ business.business_type.replace('_', ' ') }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Contact Email</p>
                            <p class="mt-1 text-lg text-gray-900">{{ business.contact_email }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Contact Phone</p>
                            <p class="mt-1 text-lg text-gray-900">{{ business.contact_phone }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">City</p>
                            <p class="mt-1 text-lg text-gray-900">{{ business.city }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Country</p>
                            <p class="mt-1 text-lg text-gray-900">{{ business.country }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Registration Date</p>
                            <p class="mt-1 text-lg text-gray-900">{{ formattedRegistrationDate }}</p>
                        </div>
                    </div>

                    <div v-if="business.website" class="col-span-full">
                        <p class="text-sm font-medium text-gray-500">Website</p>
                        <p class="mt-1 text-lg text-gray-900">
                            <a :href="business.website" target="_blank" class="text-blue-600 hover:text-blue-800">
                                {{ business.website }}
                            </a>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Next Steps Card -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-8 mb-8">
                <h2 class="text-xl font-semibold text-blue-900 mb-4">What Happens Next?</h2>
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <span class="flex items-center justify-center h-8 w-8 rounded-full bg-blue-600 text-white font-semibold text-sm">1</span>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-blue-900">Email Verification</h3>
                            <p class="mt-1 text-blue-700">
                                We've sent a verification email to <strong>{{ business.contact_email }}</strong>.
                                Please check your inbox and click the verification link to activate your account.
                            </p>
                            <p class="mt-1 text-sm text-blue-600">
                                The verification link is valid for 7 days. If you don't receive the email, please check your spam folder.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <span class="flex items-center justify-center h-8 w-8 rounded-full bg-blue-600 text-white font-semibold text-sm">2</span>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-blue-900">Account Activation</h3>
                            <p class="mt-1 text-blue-700">
                                Once verified, your account will be activated and you'll receive access to your affiliate dashboard.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <span class="flex items-center justify-center h-8 w-8 rounded-full bg-blue-600 text-white font-semibold text-sm">3</span>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-blue-900">Generate QR Codes</h3>
                            <p class="mt-1 text-blue-700">
                                Access your dashboard to generate unique QR codes for your business locations and start tracking customer engagement.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <span class="flex items-center justify-center h-8 w-8 rounded-full bg-blue-600 text-white font-semibold text-sm">4</span>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-blue-900">Track Performance</h3>
                            <p class="mt-1 text-blue-700">
                                Monitor scans, bookings, and commissions in real-time through your affiliate dashboard.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Important Information -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-8">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Important Information</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li>Please keep your registration email safe as it contains your dashboard access link</li>
                                <li>Your dashboard access will be available after email verification</li>
                                <li>Make sure to whitelist our email domain to receive important updates</li>
                                <li>Contact our support team if you have any questions or issues</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Support -->
            <div class="bg-gray-100 rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Need Help?</h3>
                <p class="text-gray-600 mb-4">
                    Our support team is here to assist you with any questions about your affiliate partnership.
                </p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <Link
                        :href="route('contact-us', { locale })"
                        class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Contact Support
                    </Link>
                    <Link
                        :href="route('faq.show', { locale })"
                        class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        View FAQ
                    </Link>
                </div>
            </div>

            <!-- Return to Homepage -->
            <div class="mt-8 text-center">
                <Link
                    :href="route('welcome', { locale })"
                    class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium"
                >
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Return to Homepage
                </Link>
            </div>
        </div>
    </div>
</template>