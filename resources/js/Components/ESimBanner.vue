<template>
  <div class="esim-banner bg-gradient-to-r from-[#1a4d6a] to-[#153B4F] text-white relative overflow-hidden">
    <!-- Marquee Container -->
    <div class="relative h-12 flex items-center">
      <!-- Scrolling Text Container -->
      <div class="marquee-container absolute inset-0 flex items-center" @mouseenter="pauseMarquee" @mouseleave="resumeMarquee">
        <div class="marquee-content flex whitespace-nowrap animate-marquee" :class="{ 'paused': isPaused }">
          <span class="inline-flex items-center space-x-8 px-4">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
            </svg>
            <span class="font-bold">🌍 Traveling Soon? Get Your eSIM!</span>
            <span class="mx-2">•</span>
            <span>Instant data connectivity in 100+ countries. No physical SIM needed.</span>
            <span class="mx-2">•</span>
            <a
              :href="esimLink"
              target="_blank"
              rel="noopener noreferrer"
              class="underline font-bold hover:text-yellow-300 transition-colors"
            >
              📱 Activate eSIM Before Your Trip - Stay Connected Everywhere!
            </a>
            <span class="mx-2">•</span>
            <span>Perfect for your car rental adventures! 🚗</span>
          </span>
          <!-- Duplicate content for seamless loop -->
          <span class="inline-flex items-center space-x-8 px-4">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
            </svg>
            <span class="font-bold">🌍 Traveling Soon? Get Your eSIM!</span>
            <span class="mx-2">•</span>
            <span>Instant data connectivity in 100+ countries. No physical SIM needed.</span>
            <span class="mx-2">•</span>
            <a
              :href="esimLink"
              target="_blank"
              rel="noopener noreferrer"
              class="underline font-bold hover:text-yellow-300 transition-colors"
            >
              📱 Activate eSIM Before Your Trip - Stay Connected Everywhere!
            </a>
            <span class="mx-2">•</span>
            <span>Perfect for your car rental adventures! 🚗</span>
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const isPaused = ref(false)

// E-sim provider link - eSIMaccess partner
const esimLink = 'https://app.esimaccess.com/signup' // eSIMaccess signup link

const pauseMarquee = () => {
  isPaused.value = true
}

const resumeMarquee = () => {
  isPaused.value = false
}
</script>

<style scoped>
.esim-banner {
  z-index: 50;
  position: relative;
}

.marquee-container {
  width: 100%;
  overflow: hidden;
}

.marquee-content {
  display: flex;
  animation: marquee 30s linear infinite;
}

.marquee-content.paused {
  animation-play-state: paused;
}

@keyframes marquee {
  0% {
    transform: translateX(0%);
  }
  100% {
    transform: translateX(-50%);
  }
}

/* RTL support */
[dir="rtl"] .marquee-content {
  animation: marquee-rtl 30s linear infinite;
}

@keyframes marquee-rtl {
  0% {
    transform: translateX(0%);
  }
  100% {
    transform: translateX(50%);
  }
}

[dir="rtl"] .marquee-content.paused {
  animation-play-state: paused;
}
</style>