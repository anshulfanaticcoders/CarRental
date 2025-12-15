<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue'
import Footer from '@/Components/Footer.vue'

const props = defineProps({
  message: String
})
</script>

<template>
  <Head title="Payment Cancelled - Locauto Rent" />
  
  <AuthenticatedHeaderLayout />

  <div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
        <!-- Cancel Icon -->
        <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6">
          <svg class="w-10 h-10 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"/>
          </svg>
        </div>

        <h1 class="text-2xl font-bold text-gray-900 mb-4">Payment Cancelled</h1>
        
        <p class="text-gray-600 mb-8">
          {{ message || 'Your payment was cancelled. No charges have been made. You can try booking again when you are ready.' }}
        </p>

        <div class="space-y-4">
          <Link href="/" class="inline-flex items-center justify-center w-full px-6 py-3 bg-customPrimaryColor text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors">
            Return to Home
          </Link>
          
          <button @click="$router.back()" class="inline-flex items-center justify-center w-full px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition-colors">
            Go Back
          </button>
        </div>
      </div>
    </div>
  </div>

  <Footer />
</template>

<style scoped>
.bg-customPrimaryColor {
  background-color: #153b4f;
}
</style>
