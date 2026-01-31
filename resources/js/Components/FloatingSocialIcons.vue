<script setup>
import { ref, onMounted, computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import axios from 'axios';
import facebookLogo from "../../assets/Facebook.svg";
import twitterLogo from "../../assets/Twitter.svg";
import instagramLogo from "../../assets/Instagram.svg";
import whatsappIcon from "../../assets/whatsapp.svg";
import carIcon from "../../assets/carIcon.svg";
import { X } from 'lucide-vue-next';

const isVisible = ref(true);
const page = usePage();
const socialLinks = ref({
    facebook: '',
    instagram: '',
    twitter: ''
});

// WhatsApp Link
const whatsappLink = computed(() => {
    const phone = '+32493000000';
    const message = "Hello, I would like to inquire about renting a car. Could you please assist me?";
    const cleanPhone = phone.replace(/[^0-9]/g, '');
    return `https://wa.me/${cleanPhone}?text=${encodeURIComponent(message)}`;
});

const closeSidebar = () => {
    isVisible.value = false;
};

const fetchSocialLinks = async () => {
    try {
        const response = await axios.get('/api/footer-contact-info');
        if (response.data) {
            socialLinks.value = {
                facebook: response.data.facebook_url || '',
                instagram: response.data.instagram_url || 'https://www.instagram.com/vrooemofficial?igsh=ZXZkMTdycmN6Mmhz',
                twitter: response.data.twitter_url || ''
            };
        }
    } catch (error) {
        console.error("Error fetching social links:", error);
    }
};

onMounted(() => {
    fetchSocialLinks();
});
</script>

<template>
    <div v-if="isVisible"
        class="fixed right-0 top-1/2 transform -translate-y-1/2 z-[100] flex flex-col gap-3 p-2 transition-all duration-300">
        <!-- Close Button -->
        <button @click="closeSidebar"
            class="absolute -top-1 -left-1 bg-white text-customPrimaryColor hover:bg-gray-100 rounded-full p-1 shadow-md border border-gray-200 flex items-center justify-center w-6 h-6 z-[101]">
            <X :size="14" />
        </button>

        <!-- Search / Rent A Car -->
        <Link :href="route('search', {
            locale: page.props.locale,
            date_from: new Date().toISOString().split('T')[0],
            date_to: new Date(Date.now() + 3 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
            start_time: '10:00',
            end_time: '10:00'
        })"
            class="block relative w-9 h-9 md:w-10 md:h-10 bg-white rounded-full shadow-lg border border-gray-100 flex items-center justify-center transition-transform hover:scale-110">
            <img :src="carIcon" alt="Rent A Car" class="w-5 h-5 object-contain primary-filter" />
        </Link>

        <!-- WhatsApp -->
        <a :href="whatsappLink" target="_blank"
            class="block relative w-9 h-9 md:w-10 md:h-10 bg-white rounded-full shadow-lg border border-gray-100 flex items-center justify-center transition-transform hover:scale-110">
            <img :src="whatsappIcon" alt="WhatsApp" class="w-5 h-5 object-contain primary-filter" />
        </a>

        <!-- Facebook -->
        <a v-if="socialLinks.facebook" :href="socialLinks.facebook" target="_blank"
            class="block relative w-9 h-9 md:w-10 md:h-10 bg-white rounded-full shadow-lg border border-gray-100 flex items-center justify-center transition-transform hover:scale-110">
            <img :src="facebookLogo" alt="Facebook" class="w-5 h-5 object-contain primary-filter" />
        </a>

        <!-- Instagram -->
        <a v-if="socialLinks.instagram" :href="socialLinks.instagram" target="_blank"
            class="block relative w-9 h-9 md:w-10 md:h-10 bg-white rounded-full shadow-lg border border-gray-100 flex items-center justify-center transition-transform hover:scale-110">
            <img :src="instagramLogo" alt="Instagram" class="w-5 h-5 object-contain primary-filter" />
        </a>

        <!-- Twitter / X -->
        <a v-if="socialLinks.twitter" :href="socialLinks.twitter" target="_blank"
            class="block relative w-9 h-9 md:w-10 md:h-10 bg-white rounded-full shadow-lg border border-gray-100 flex items-center justify-center transition-transform hover:scale-110">
            <img :src="twitterLogo" alt="Twitter" class="w-5 h-5 object-contain primary-filter" />
        </a>
    </div>
</template>

<style scoped>
.primary-filter {
    filter: brightness(0) saturate(100%) invert(18%) sepia(13%) saturate(3203%) hue-rotate(171deg) brightness(97%) contrast(94%);
}
</style>
