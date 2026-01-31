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
        class="fixed right-0 top-1/2 transform -translate-y-1/2 z-[100] flex flex-col gap-2 p-2 bg-customPrimaryColor rounded-l-lg shadow-lg border-l border-t border-b border-white/20 transition-all duration-300">
        <!-- Close Button -->
        <button @click="closeSidebar"
            class="absolute -top-3 -left-3 bg-white text-customPrimaryColor hover:bg-gray-100 rounded-full p-1 shadow-md border border-gray-200 flex items-center justify-center w-6 h-6 z-[101]">
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
            class="block relative w-6 h-6 md:w-8 md:h-8 transition-transform hover:scale-110 flex items-center justify-center">
            <img :src="carIcon" alt="Rent A Car" class="w-full h-full object-contain brightness-0 invert" />
        </Link>

        <!-- WhatsApp -->
        <a :href="whatsappLink" target="_blank"
            class="block relative w-6 h-6 md:w-8 md:h-8 transition-transform hover:scale-110">
            <img :src="whatsappIcon" alt="WhatsApp" class="w-full h-full object-contain brightness-0 invert" />
        </a>

        <!-- Facebook -->
        <a v-if="socialLinks.facebook" :href="socialLinks.facebook" target="_blank"
            class="block relative w-6 h-6 md:w-8 md:h-8 transition-transform hover:scale-110">
            <img :src="facebookLogo" alt="Facebook" class="w-full h-full object-contain brightness-0 invert" />
        </a>

        <!-- Instagram -->
        <a v-if="socialLinks.instagram" :href="socialLinks.instagram" target="_blank"
            class="block relative w-6 h-6 md:w-8 md:h-8 transition-transform hover:scale-110">
            <img :src="instagramLogo" alt="Instagram" class="w-full h-full object-contain brightness-0 invert" />
        </a>

        <!-- Twitter / X -->
        <a v-if="socialLinks.twitter" :href="socialLinks.twitter" target="_blank"
            class="block relative w-6 h-6 md:w-8 md:h-8 transition-transform hover:scale-110">
            <img :src="twitterLogo" alt="Twitter" class="w-full h-full object-contain brightness-0 invert" />
        </a>
    </div>
</template>
