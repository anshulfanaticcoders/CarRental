<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Clock, AlertCircle, CheckCircle, XCircle } from 'lucide-vue-next';

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

const getStatusInfo = (status) => {
    switch (status) {
        case 'pending':
            return {
                icon: Clock,
                color: 'yellow',
                title: 'Awaiting Approval',
                description: 'Your business registration is being reviewed by our team.',
                message: 'We\'re reviewing your business information and will notify you once the approval process is complete.'
            };
        case 'inactive':
            return {
                icon: XCircle,
                color: 'red',
                title: 'Account Inactive',
                description: 'Your business account has been deactivated.',
                message: 'Your account is currently inactive. Please contact support for assistance.'
            };
        case 'suspended':
            return {
                icon: AlertCircle,
                color: 'red',
                title: 'Account Suspended',
                description: 'Your business account has been temporarily suspended.',
                message: 'Your account has been suspended due to policy violations. Please contact support to resolve this issue.'
            };
        default:
            return {
                icon: Clock,
                color: 'yellow',
                title: 'Account Under Review',
                description: 'Your account status is being reviewed.',
                message: 'Your account is currently under review. We\'ll notify you once there\'s an update.'
            };
    }
};

const statusInfo = getStatusInfo(props.business.status);
const formattedRegistrationDate = new Date(props.business.created_at).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
});
</script>

<template>
    <Head :title="statusInfo.title + ' - ' + business.name" />

    <div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto">
            <!-- Status Message -->
            <div class="text-center mb-8">
                <div :class="`mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-${statusInfo.color}-100 mb-4`">
                    <component :is="statusInfo.icon" :class="`h-8 w-8 text-${statusInfo.color}-600`" />
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    {{ statusInfo.title }}
                </h1>
                <p class="text-lg text-gray-600">
                    {{ statusInfo.description }}
                </p>
            </div>

            <!-- Status Card -->
            <div :class="`bg-white shadow-lg rounded-lg p-8 mb-8 border-l-4 border-${statusInfo.color}-500`">
                <div class="border-b border-gray-200 pb-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Account Status</h2>
                    <p class="text-sm text-gray-500 mt-1">Current Status: <span class="font-semibold capitalize">{{ business.status }}</span></p>
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
                            <p class="text-sm font-medium text-gray-500">Registration Date</p>
                            <p class="mt-1 text-lg text-gray-900">{{ formattedRegistrationDate }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Contact Email</p>
                            <p class="mt-1 text-lg text-gray-900">{{ business.contact_email }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Message Card -->
            <div :class="`bg-${statusInfo.color}-50 border border-${statusInfo.color}-200 rounded-lg p-8 mb-8`">
                <h2 :class="`text-xl font-semibold text-${statusInfo.color}-900 mb-4`">What This Means</h2>
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <component :is="statusInfo.icon" :class="`h-6 w-6 text-${statusInfo.color}-600`" />
                        </div>
                        <div class="ml-4">
                            <p :class="`text-${statusInfo.color}-900`">
                                {{ statusInfo.message }}
                            </p>
                        </div>
                    </div>

                    <div v-if="business.status === 'pending'" class="flex items-start">
                        <div class="flex-shrink-0">
                            <CheckCircle class="h-6 w-6 text-green-600" />
                        </div>
                        <div class="ml-4">
                            <p class="text-green-900">
                                <strong>Next Steps:</strong> Once your business is approved, you'll receive an email notification and can access your affiliate dashboard to generate QR codes and track performance.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="bg-white shadow-lg rounded-lg p-8 mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Registration Timeline</h2>
                <div class="space-y-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-green-600 text-white font-semibold text-sm">
                                <CheckCircle class="h-4 w-4" />
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Business Registration</h3>
                            <p class="mt-1 text-gray-600">
                                Your business registration was completed successfully.
                            </p>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ formattedRegistrationDate }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div :class="`flex items-center justify-center h-8 w-8 rounded-full ${business.verification_status === 'verified' ? 'bg-green-600 text-white' : 'bg-gray-300 text-gray-600'} font-semibold text-sm`">
                                <CheckCircle v-if="business.verification_status === 'verified'" class="h-4 w-4" />
                                <Clock v-else class="h-4 w-4" />
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Email Verification</h3>
                            <p class="mt-1 text-gray-600">
                                <span v-if="business.verification_status === 'verified'">
                                    Your email has been verified successfully.
                                </span>
                                <span v-else>
                                    Please check your email and click the verification link to verify your business email.
                                </span>
                            </p>
                            <p v-if="business.verified_at" class="mt-1 text-sm text-gray-500">
                                Verified on {{ new Date(business.verified_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div :class="`flex items-center justify-center h-8 w-8 rounded-full ${business.status === 'active' ? 'bg-green-600 text-white' : 'bg-gray-300 text-gray-600'} font-semibold text-sm`">
                                <CheckCircle v-if="business.status === 'active'" class="h-4 w-4" />
                                <Clock v-else class="h-4 w-4" />
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Business Approval</h3>
                            <p class="mt-1 text-gray-600">
                                <span v-if="business.status === 'active'">
                                    Your business has been approved and is now active.
                                </span>
                                <span v-else>
                                    Your business is awaiting approval from our admin team.
                                </span>
                            </p>
                            <p class="mt-1 text-sm text-gray-500">
                                Current status: <span class="font-semibold capitalize">{{ business.status }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-300 text-gray-600 font-semibold text-sm">
                                <component :is="statusInfo.icon" class="h-4 w-4" />
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Dashboard Access</h3>
                            <p class="mt-1 text-gray-600">
                                Access your affiliate dashboard once your business is approved.
                            </p>
                            <p class="mt-1 text-sm text-gray-500">
                                Available once business status is "active"
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Support -->
            <div class="bg-gray-100 rounded-lg p-6 mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Need Assistance?</h3>
                <p class="text-gray-600 mb-4">
                    If you have any questions about your account status or need help with the approval process, our support team is here to help.
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