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

const goToDashboard = () => {
    window.location.href = route('affiliate.dashboard', {
        token: props.business.dashboard_access_token,
        locale: props.locale,
    });
};
</script>

<template>
    <Head title="Email Verified - Account Activated" />

    <div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto">
            <!-- Success Message -->
            <div class="text-center mb-8">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                    <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    Email Verified Successfully!
                </h1>
                <p class="text-lg text-gray-600">
                    Your account has been activated and is ready to use
                </p>
            </div>

            <!-- Activation Confirmation Card -->
            <div class="bg-white shadow-lg rounded-lg p-8 mb-8">
                <div class="border-b border-gray-200 pb-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Account Activation Details</h2>
                </div>

                <div class="space-y-4">
                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mt-0.5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <div>
                            <h3 class="font-medium text-gray-900">Business Email Verified</h3>
                            <p class="text-gray-600">{{ business.contact_email }}</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mt-0.5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <div>
                            <h3 class="font-medium text-gray-900">Account Status</h3>
                            <p class="text-gray-600">Active - Ready to generate QR codes</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mt-0.5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <div>
                            <h3 class="font-medium text-gray-900">Dashboard Access</h3>
                            <p class="text-gray-600">Available immediately</p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="font-medium text-blue-900 mb-2">Business Information</h4>
                        <div class="text-sm text-blue-800 space-y-1">
                            <p><strong>Name:</strong> {{ business.name }}</p>
                            <p><strong>Type:</strong> {{ business.business_type.replace('_', ' ').charAt(0).toUpperCase() + business.business_type.replace('_', ' ').slice(1) }}</p>
                            <p><strong>Location:</strong> {{ business.city }}, {{ business.country }}</p>
                            <p><strong>Verification Date:</strong> {{ new Date(business.verified_at).toLocaleDateString() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Next Steps Card -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-8 mb-8 text-white">
                <h2 class="text-2xl font-bold mb-4">Ready to Start Earning?</h2>
                <p class="text-blue-100 mb-6">
                    Your affiliate dashboard is now ready! Generate QR codes, track customer scans, and monitor your commissions in real-time.
                </p>

                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-blue-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="font-semibold">Generate QR Codes</h3>
                            <p class="text-blue-100 text-sm">Create unique QR codes for your business locations</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-blue-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="font-semibold">Track Performance</h3>
                            <p class="text-blue-100 text-sm">Monitor scans, bookings, and customer engagement</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-blue-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="font-semibold">Earn Commissions</h3>
                            <p class="text-blue-100 text-sm">Receive commissions on every completed booking</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Access Button -->
            <div class="text-center mb-8">
                <button
                    @click="goToDashboard"
                    class="inline-flex items-center px-8 py-4 border border-transparent text-lg font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200"
                >
                    <svg class="mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    Access Your Dashboard
                </button>
                <p class="mt-3 text-sm text-gray-500">
                    You'll be redirected to your affiliate dashboard securely
                </p>
            </div>

            <!-- Helpful Information -->
            <div class="bg-gray-100 rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Getting Started Tips</h3>
                <div class="space-y-3">
                    <div class="flex items-start">
                        <span class="flex-shrink-0 h-6 w-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-sm font-semibold">1</span>
                        <p class="ml-3 text-gray-700">Log in to your dashboard using the secure link we'll send to your email</p>
                    </div>
                    <div class="flex items-start">
                        <span class="flex-shrink-0 h-6 w-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-sm font-semibold">2</span>
                        <p class="ml-3 text-gray-700">Generate your first QR code for your main business location</p>
                    </div>
                    <div class="flex items-start">
                        <span class="flex-shrink-0 h-6 w-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-sm font-semibold">3</span>
                        <p class="ml-3 text-gray-700">Display the QR code where customers can easily scan it</p>
                    </div>
                    <div class="flex items-start">
                        <span class="flex-shrink-0 h-6 w-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-sm font-semibold">4</span>
                        <p class="ml-3 text-gray-700">Monitor your dashboard for real-time tracking and earnings</p>
                    </div>
                </div>
            </div>

            <!-- Support Section -->
            <div class="mt-8 text-center">
                <p class="text-gray-600 mb-4">
                    Need assistance? Our support team is here to help you succeed.
                </p>
                <div class="flex justify-center space-x-4">
                    <Link
                        :href="`/${locale}/contact-us`"
                        class="text-blue-600 hover:text-blue-800 font-medium"
                    >
                        Contact Support
                    </Link>
                    <span class="text-gray-400">•</span>
                    <Link
                        :href="route('faq.show', { locale })"
                        class="text-blue-600 hover:text-blue-800 font-medium"
                    >
                        View FAQ
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>