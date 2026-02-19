<script setup>
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    locale: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head title="Logged Out - Vrooem Affiliate Dashboard" />

    <div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto">
            <!-- Logout Success Message -->
            <div class="text-center mb-8">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                    <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    Logged Out Successfully
                </h1>
                <p class="text-lg text-gray-600">
                    You have been securely logged out of your affiliate dashboard
                </p>
            </div>

            <!-- Security Notice Card -->
            <div class="bg-white shadow-lg rounded-lg p-8 mb-8">
                <div class="border-b border-gray-200 pb-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Security Notice</h2>
                </div>

                <div class="space-y-4">
                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mt-0.5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <h3 class="font-medium text-gray-900">Session Terminated</h3>
                            <p class="text-gray-600">
                                Your dashboard session has been securely terminated and all active tokens have been invalidated.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-blue-500 mt-0.5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <div>
                            <h3 class="font-medium text-gray-900">Account Secured</h3>
                            <p class="text-gray-600">
                                Your account is now protected and will require a new login attempt to access the dashboard again.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">Security Reminder</h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>Always remember to:</p>
                                <ul class="list-disc list-inside mt-1 space-y-1">
                                    <li>Log out when using shared or public computers</li>
                                    <li>Keep your verification emails secure</li>
                                    <li>Never share your dashboard access links</li>
                                    <li>Contact support if you suspect unauthorized access</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Next Steps Card -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-8 mb-8">
                <h2 class="text-xl font-semibold text-blue-900 mb-4">Need to Access Your Dashboard Again?</h2>
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <span class="flex items-center justify-center h-8 w-8 rounded-full bg-blue-600 text-white font-semibold text-sm">1</span>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-blue-900">Check Your Email</h3>
                            <p class="mt-1 text-blue-700">
                                Look for the original dashboard access email sent to your registered business email address.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <span class="flex items-center justify-center h-8 w-8 rounded-full bg-blue-600 text-white font-semibold text-sm">2</span>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-blue-900">Click the Dashboard Link</h3>
                            <p class="mt-1 text-blue-700">
                                Use the secure link in your email to access your dashboard. A new session will be created automatically.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <span class="flex items-center justify-center h-8 w-8 rounded-full bg-blue-600 text-white font-semibold text-sm">3</span>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-blue-900">Request New Link if Needed</h3>
                            <p class="mt-1 text-blue-700">
                                If you can't find the email, contact our support team for a new dashboard access link.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white shadow-lg rounded-lg p-6 mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Link
                        :href="route('welcome', { locale })"
                        class="inline-flex items-center justify-center px-4 py-3 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Return to Homepage
                    </Link>

                    <Link
                        :href="`/${locale}/contact-us`"
                        class="inline-flex items-center justify-center px-4 py-3 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Contact Support
                    </Link>
                </div>
            </div>

            <!-- Security Information -->
            <div class="bg-gray-100 rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Account Security</h3>
                <p class="text-gray-600 mb-4">
                    The Vrooem Affiliate Program takes security seriously. Here are some ways we protect your account:
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mt-0.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="text-gray-700">Secure email-based authentication</span>
                    </div>
                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mt-0.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="text-gray-700">Automatic session expiration</span>
                    </div>
                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mt-0.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="text-gray-700">IP address and device tracking</span>
                    </div>
                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mt-0.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="text-gray-700">Secure dashboard tokens</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>