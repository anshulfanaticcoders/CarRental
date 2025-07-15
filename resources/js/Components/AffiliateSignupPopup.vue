<script setup>
import { ref, onMounted } from 'vue';
import affiliateBg from "../../assets/affiliate.jpg"

const showPopup = ref(false);
const affiliateSignupUrl = 'https://vrooem.tapfiliate.com';

// Helper: Set a cookie
const setCookie = (name, value, days) => {
    const expires = new Date();
    expires.setTime(expires.getTime() + days * 24 * 60 * 60 * 1000);
    document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/`;
};

// Helper: Get a cookie
const getCookie = (name) => {
    const nameEQ = name + '=';
    const ca = document.cookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
};

const closePopup = () => {
    showPopup.value = false;
    setCookie('affiliate_popup_closed', 'true', 2); // Expires in 2 days
};

onMounted(() => {
    if (!getCookie('affiliate_popup_closed')) {
        setTimeout(() => {
            showPopup.value = true;
        }, 5000);
    }
});
</script>


<template>
    <div v-if="showPopup" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="tapCard bg-white rounded-lg shadow-xl p-6 w-[30rem] py-[5rem] relative">
            <button @click="closePopup" class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 text-xl">
                &times;
            </button>
            <h3 class="text-2xl font-bold text-center text-white mb-4">Become an Affiliate!</h3>
            <p class="text-gray-600 text-center mb-6">
                Join our affiliate program and start earning commissions today.
            </p>
            <div class="flex justify-center">
                <a :href="affiliateSignupUrl" target="_blank" @click="closePopup"
                   class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-customPrimaryColor focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Sign Up Now
                </a>
            </div>
        </div>
    </div>
</template>

<style>
.tapCard{
   background-image: radial-gradient( circle farthest-corner at 6.3% 21.8%,  rgba(236,6,117,1) 0%, rgba(13,32,67,1) 90% );
}
</style>
