<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import Footer from '@/Components/Footer.vue';
import { XCircle } from 'lucide-vue-next';

defineProps({
    message: {
        type: String,
        default: 'Your payment was cancelled. You can try again anytime.'
    }
});
</script>

<template>
    <Head title="Payment Cancelled" />
    <AuthenticatedHeaderLayout />

    <div class="min-h-screen bg-gray-50 py-16">
        <div class="max-w-2xl mx-auto px-4">
            <div class="bg-white rounded-2xl shadow-lg p-8 text-center">
                <!-- Cancel Icon -->
                <div class="w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <XCircle class="w-12 h-12 text-amber-600" />
                </div>

                <!-- Cancel Message -->
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Payment Cancelled</h1>
                <p class="text-gray-600 mb-6">
                    {{ message }}
                </p>

                <!-- Info Box -->
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6 text-left">
                    <h3 class="font-semibold text-amber-800 mb-2">What happened?</h3>
                    <p class="text-sm text-amber-700">
                        Your payment session was cancelled or expired. No charges have been made to your card.
                        You can return to the search results and try booking again.
                    </p>
                </div>

                <!-- Actions -->
                <div class="space-y-3">
                    <Link 
                        href="/"
                        class="block w-full bg-customPrimaryColor text-white py-3 rounded-lg font-bold hover:bg-opacity-90 transition-all"
                    >
                        Back to Home
                    </Link>
                    <button 
                        @click="$router.go(-1)"
                        class="block w-full bg-white border border-gray-300 text-gray-700 py-3 rounded-lg font-medium hover:bg-gray-50 transition-all"
                    >
                        Go Back
                    </button>
                </div>
            </div>
        </div>
    </div>

    <Footer />
</template>
