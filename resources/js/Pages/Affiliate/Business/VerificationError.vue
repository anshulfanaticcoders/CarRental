<script setup>
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    message: {
        type: String,
        default: 'Invalid or expired verification link',
    },
    locale: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head title="Verification Failed - Vrooem Affiliate Program" />

    <div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto">
            <!-- Error Message -->
            <div class="text-center mb-8">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                    <svg class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    Verification Failed
                </h1>
                <p class="text-lg text-gray-600">
                    {{ message }}
                </p>
            </div>

            <!-- Error Details Card -->
            <div class="bg-white shadow-lg rounded-lg p-8 mb-8">
                <div class="border-b border-gray-200 pb-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">What Happened?</h2>
                </div>

                <div class="space-y-4">
                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-red-500 mt-0.5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <div>
                            <h3 class="font-medium text-gray-900">Invalid or Expired Link</h3>
                            <p class="text-gray-600">
                                The verification link you clicked is no longer valid. This could happen if:
                            </p>
                            <ul class="mt-2 text-sm text-gray-600 list-disc list-inside">
                                <li>The link has expired (verification links are valid for 7 days)</li>
                                <li>The business has already been verified</li>
                                <li>The link was corrupted or modified</li>
                                <li>The business registration was cancelled or rejected</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Solutions Card -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-8 mb-8">
                <h2 class="text-xl font-semibold text-blue-900 mb-4">How to Resolve This</h2>
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <span class="flex items-center justify-center h-8 w-8 rounded-full bg-blue-600 text-white font-semibold text-sm">1</span>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-blue-900">Request a New Verification Email</h3>
                            <p class="mt-1 text-blue-700">
                                Contact our support team to request a new verification email for your business.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <span class="flex items-center justify-center h-8 w-8 rounded-full bg-blue-600 text-white font-semibold text-sm">2</span>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-blue-900">Check Your Registration Status</h3>
                            <p class="mt-1 text-blue-700">
                                Our support team can verify if your business registration is still active and provide the current status.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <span class="flex items-center justify-center h-8 w-8 rounded-full bg-blue-600 text-white font-semibold text-sm">3</span>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-blue-900">Re-register if Necessary</h3>
                            <p class="mt-1 text-blue-700">
                                If your registration has expired, you may need to submit a new business registration.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Support Section -->
            <div class="bg-gray-100 rounded-lg p-6 mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Need Help Resolving This Issue?</h3>
                <p class="text-gray-600 mb-4">
                    Our support team is ready to help you complete your business verification. Please have your business registration details ready when you contact us.
                </p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <Link
                        :href="route('contact-us', { locale })"
                        class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Contact Support
                    </Link>
                    <a
                        href="mailto:support@vrooem.com"
                        class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Email Support
                    </a>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="bg-white shadow-lg rounded-lg p-6 mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Frequently Asked Questions</h3>
                <div class="space-y-4">
                    <details class="group">
                        <summary class="flex justify-between items-center cursor-pointer text-gray-900 font-medium">
                            Why did my verification link expire?
                            <svg class="w-5 h-5 text-gray-500 group-open:rotate-180 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-2 text-gray-600">
                            Verification links expire after 7 days for security reasons. This prevents unauthorized access to your business account.
                        </p>
                    </details>

                    <details class="group">
                        <summary class="flex justify-between items-center cursor-pointer text-gray-900 font-medium">
                            Can I get a new verification link?
                            <svg class="w-5 h-5 text-gray-500 group-open:rotate-180 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-2 text-gray-600">
                            Yes, you can request a new verification link by contacting our support team. They'll verify your business registration and send a fresh verification email.
                        </p>
                    </details>

                    <details class="group">
                        <summary class="flex justify-between items-center cursor-pointer text-gray-900 font-medium">
                            What information do I need to provide to support?
                            <svg class="w-5 h-5 text-gray-500 group-open:rotate-180 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-2 text-gray-600">
                            Please provide your business name, registered email address, and approximate registration date. This will help us locate your account quickly.
                        </p>
                    </details>
                </div>
            </div>

            <!-- Return to Homepage -->
            <div class="text-center">
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