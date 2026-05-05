<script setup>
import { ref, onMounted, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import facebookLogo from "../../assets/Facebook.svg";
import twitterLogo from "../../assets/Twitter.svg";
import instagramLogo from "../../assets/Instagram.svg";
import whatsappIcon from "../../assets/whatsapp.svg";
import { X, Phone } from 'lucide-vue-next';

const isVisible = ref(true);
const page = usePage();
const socialLinks = ref({
    facebook: '',
    instagram: '',
    twitter: ''
});
const phoneNumber = ref('');

const phoneHref = computed(() => {
    if (!phoneNumber.value) return '';
    return `tel:${phoneNumber.value.replace(/\s+/g, '')}`;
});

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
            phoneNumber.value = response.data.phone_number || '';
        }
    } catch (error) {
        console.error("Error fetching contact info:", error);
    }
};

onMounted(() => {
    fetchSocialLinks();
});
</script>

<template>
    <div v-if="isVisible" class="floating-widget" :class="{ 'is-visible': isVisible }">
        <!-- Close Button -->
        <button @click="closeSidebar" aria-label="Close" class="floating-close">
            <X :size="14" />
        </button>

        <!-- Call -->
        <a v-if="phoneNumber" :href="phoneHref" :aria-label="`Call ${phoneNumber}`" class="floating-icon floating-icon-stroke">
            <Phone :size="16" :stroke-width="2.2" />
        </a>

        <!-- WhatsApp -->
        <a :href="whatsappLink" target="_blank" aria-label="Chat on WhatsApp" class="floating-icon">
            <img :src="whatsappIcon" alt="WhatsApp" class="floating-icon-img" />
        </a>

        <!-- Facebook -->
        <a v-if="socialLinks.facebook" :href="socialLinks.facebook" target="_blank" aria-label="Facebook" class="floating-icon">
            <img :src="facebookLogo" alt="Facebook" class="floating-icon-img" />
        </a>

        <!-- Instagram -->
        <a v-if="socialLinks.instagram" :href="socialLinks.instagram" target="_blank" aria-label="Instagram" class="floating-icon">
            <img :src="instagramLogo" alt="Instagram" class="floating-icon-img" />
        </a>

        <!-- Twitter / X -->
        <a v-if="socialLinks.twitter" :href="socialLinks.twitter" target="_blank" aria-label="Twitter" class="floating-icon">
            <img :src="twitterLogo" alt="Twitter" class="floating-icon-img" />
        </a>
    </div>
</template>

<style scoped>
.floating-widget {
    position: fixed;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 100;
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 10px 7px;
    border-radius: 999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.55) 0%, rgba(255, 255, 255, 0.35) 100%);
    border: 1px solid rgba(34, 211, 238, 0.35);
    box-shadow:
        0 10px 30px rgba(21, 59, 79, 0.12),
        0 4px 12px rgba(34, 211, 238, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.6);
    backdrop-filter: blur(18px) saturate(1.5);
    -webkit-backdrop-filter: blur(18px) saturate(1.5);
    transition: opacity 0.4s cubic-bezier(0.22, 1, 0.36, 1),
                transform 0.4s cubic-bezier(0.22, 1, 0.36, 1);
}

.floating-close {
    position: absolute;
    top: -6px;
    left: -6px;
    width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: #ffffff;
    color: #153b4f;
    border: 1px solid rgba(21, 59, 79, 0.15);
    box-shadow: 0 2px 6px rgba(21, 59, 79, 0.18);
    z-index: 101;
    transition: background 0.2s ease, transform 0.2s ease;
}

.floating-close:hover {
    background: #f1f5f9;
    transform: scale(1.08);
}

.floating-icon {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 35px;
    height: 35px;
    border-radius: 999px;
    background: #ffffff;
    border: 2px solid #22d3ee;
    box-shadow: 0 4px 14px rgba(34, 211, 238, 0.18);
    transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1),
                background 0.3s cubic-bezier(0.22, 1, 0.36, 1),
                box-shadow 0.3s cubic-bezier(0.22, 1, 0.36, 1),
                border-color 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}

.floating-icon::before {
    content: '';
    position: absolute;
    inset: -6px;
    border-radius: 999px;
    background: rgba(34, 211, 238, 0.25);
    opacity: 0;
    transform: scale(0.85);
    transition: opacity 0.4s cubic-bezier(0.22, 1, 0.36, 1),
                transform 0.4s cubic-bezier(0.22, 1, 0.36, 1);
    z-index: -1;
}

.floating-icon-img {
    width: 16px;
    height: 16px;
    object-fit: contain;
    transition: filter 0.3s cubic-bezier(0.22, 1, 0.36, 1),
                transform 0.3s cubic-bezier(0.22, 1, 0.36, 1);
    /* default: brand teal tint */
    filter: brightness(0) saturate(100%) invert(18%) sepia(13%) saturate(3203%) hue-rotate(171deg) brightness(97%) contrast(94%);
}

.floating-icon:hover {
    transform: translateX(-3px) scale(1.08);
    background: #22d3ee;
    border-color: #22d3ee;
    box-shadow: 0 8px 22px rgba(34, 211, 238, 0.45);
}

.floating-icon:hover::before {
    opacity: 1;
    transform: scale(1);
}

.floating-icon:hover .floating-icon-img {
    /* invert to white on cyan background */
    filter: brightness(0) invert(1);
    transform: scale(1.05);
}

.floating-icon:focus-visible {
    outline: 2px solid #22d3ee;
    outline-offset: 4px;
}

/* Lucide stroke icon (Phone) — matches the SVG-image icons but uses currentColor */
.floating-icon-stroke {
    color: #153b4f;
    transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1),
                background 0.3s cubic-bezier(0.22, 1, 0.36, 1),
                box-shadow 0.3s cubic-bezier(0.22, 1, 0.36, 1),
                border-color 0.3s cubic-bezier(0.22, 1, 0.36, 1),
                color 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}

.floating-icon-stroke :deep(svg) {
    transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}

.floating-icon-stroke:hover {
    color: #ffffff;
}

.floating-icon-stroke:hover :deep(svg) {
    transform: scale(1.05);
}
</style>
