<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';

const showPopup = ref(false);
const affiliateSignupUrl = 'https://vrooem.tapfiliate.com'; // Provided by the user

const closePopup = () => {
    showPopup.value = false;
    // Optionally, set a cookie or local storage item to prevent showing again
    localStorage.setItem('affiliate_popup_closed', 'true');
};

onMounted(() => {
    // Only show if it hasn't been closed before
    if (!localStorage.getItem('affiliate_popup_closed')) {
        // Example: show after 5 seconds, or based on user interaction
        setTimeout(() => {
            showPopup.value = true;
        }, 5000); // Adjust delay as needed
    }
});

// Optional: Hide popup on route change if it's not meant to persist
// import { usePage } from '@inertiajs/vue3';
// const page = usePage();
// watch(() => page.url, () => {
//     showPopup.value = false;
// });
</script>

<template>
    <div v-if="showPopup" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl p-6 w-[30rem] relative">
            <button @click="closePopup" class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 text-xl">
                &times;
            </button>
            <h3 class="text-2xl font-bold text-center text-gray-800 mb-4">Become an Affiliate!</h3>
            <p class="text-gray-600 text-center mb-6">
                Join our affiliate program and start earning commissions today.
            </p>
            <div class="flex justify-center">
                <a :href="affiliateSignupUrl" target="_blank" @click="closePopup"
                   class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Sign Up Now
                </a>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Add any specific styles for the popup here if needed */
</style>
