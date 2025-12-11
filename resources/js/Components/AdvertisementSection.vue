<script setup>
import { onMounted, ref, computed, onUnmounted } from 'vue';
import { useScrollAnimation } from '@/composables/useScrollAnimation';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const props = defineProps({
    heroImage: String,
    advertisements: {
        type: Array,
        default: () => []
    },
});

const currentIndex = ref(0);
let intervalId = null;

const currentAd = computed(() => {
    if (!props.advertisements || props.advertisements.length === 0) return null;
    return props.advertisements[currentIndex.value];
});

const nextAd = () => {
    if (props.advertisements.length > 1) {
        currentIndex.value = (currentIndex.value + 1) % props.advertisements.length;
    }
};

const setAd = (index) => {
    currentIndex.value = index;
    restartTimer();
};

const startTimer = () => {
    if (props.advertisements.length > 1) {
        intervalId = setInterval(nextAd, 7000);
    }
};

const stopTimer = () => {
    if (intervalId) clearInterval(intervalId);
};

const restartTimer = () => {
    stopTimer();
    startTimer();
};

// Animation setup (triggers once when section enters view)
useScrollAnimation('.ad-section-trigger', '.ad-content-wrapper', {
    opacity: 0,
    y: 20,
    duration: 1,
    ease: 'power3.out'
});

onMounted(() => {
    startTimer();
});

onUnmounted(() => {
    stopTimer();
});
</script>

<template>
    <section v-if="currentAd" class="w-full py-6 sm:py-8 md:py-10 lg:py-12 ad-section-trigger overflow-hidden">
        <div class="full-w-container mx-auto sm:px-6 lg:px-8 ad-content-wrapper">
            <!-- Section Header -->
            <div class="mb-6 sm:mb-8 lg:mb-10 px-4 sm:px-0">
                <span class="text-[1rem] sm:text-[1.15rem] text-customPrimaryColor">- Exclusive Offers -</span>
                <h3 class="text-customDarkBlackColor text-2xl sm:text-3xl lg:text-[2.5rem] font-bold mt-2 max-[768px]:text-[1.75rem] max-[768px]:mt-[1rem]">
                    Don't Miss Out on These Deals
                </h3>
            </div>
            
            <div class="relative" @mouseenter="stopTimer" @mouseleave="startTimer">
                <Transition name="fade" mode="out-in">
                    <div :key="currentAd.id" class="grid grid-cols-1 md:grid-cols-12 gap-4 sm:gap-6 lg:gap-8 h-auto">
                        
                        <!-- Main Featured Banner -->
                        <div class="col-span-1 md:col-span-7 lg:col-span-8 relative group cursor-pointer ad-card-main h-[20rem] sm:h-[22rem] md:h-[24rem] lg:h-[28rem]">
                            <div class="absolute inset-0 bg-gradient-to-r from-customPrimaryColor to-[#1e4b63] rounded-[1rem] sm:rounded-[1.5rem] lg:rounded-[2rem] transform transition-transform duration-500 hover:scale-[1.01] shadow-xl overflow-hidden">
                                <!-- Decorative Circles -->
                                <div class="absolute top-[-50%] right-[-10%] w-[20rem] sm:w-[25rem] lg:w-[30rem] h-[20rem] sm:h-[25rem] lg:h-[30rem] rounded-full bg-white/5 blur-3xl"></div>
                                <div class="absolute bottom-[-20%] left-[-10%] w-[15rem] sm:w-[18rem] lg:w-[20rem] h-[15rem] sm:h-[18rem] lg:h-[20rem] rounded-full bg-white/10 blur-2xl"></div>
                                
                                <!-- Content -->
                                <div class="relative z-10 h-full flex flex-col justify-center p-5 sm:p-6 md:p-8 lg:p-12 text-white">
                                    <span class="inline-block py-1 px-2 sm:px-3 rounded-full bg-white/20 backdrop-blur-md text-[10px] sm:text-xs font-semibold tracking-wider mb-3 sm:mb-4 w-fit border border-white/10 uppercase">
                                        {{ currentAd.offer_type }}
                                    </span>
                                    <h2 class="text-xl sm:text-2xl md:text-3xl lg:text-5xl font-bold mb-2 sm:mb-3 lg:mb-4 leading-tight">
                                        {{ currentAd.title }}
                                    </h2>
                                    <p class="text-blue-100 text-sm sm:text-base lg:text-lg mb-4 sm:mb-6 lg:mb-8 max-w-md line-clamp-3">
                                        {{ currentAd.description }}
                                    </p>
                                    
                                    <a :href="`/${page.props.locale}${currentAd.button_link || ''}`" class="group/btn flex items-center gap-2 sm:gap-3 bg-white text-customPrimaryColor px-4 sm:px-6 lg:px-8 py-2.5 sm:py-3 lg:py-4 rounded-lg sm:rounded-xl font-bold text-sm sm:text-base transition-all hover:bg-blue-50 hover:gap-3 sm:hover:gap-4 w-fit shadow-lg hover:shadow-white/20">
                                        {{ currentAd.button_text }}
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5 transform transition-transform group-hover/btn:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Secondary Vertical Banner (Right Side) -->
                        <div class="col-span-1 md:col-span-5 lg:col-span-4 relative group cursor-pointer ad-card-secondary h-[16rem] sm:h-[18rem] md:h-[24rem] lg:h-[28rem]">
                            <div 
                                class="absolute inset-0 bg-customPrimaryColor rounded-[1rem] sm:rounded-[1.5rem] lg:rounded-[2rem] transform transition-transform duration-500 hover:scale-[1.01] shadow-xl overflow-hidden"
                                :style="{ backgroundImage: `url(${currentAd.image_path || heroImage})`, backgroundSize: 'contain', backgroundPosition: 'center', backgroundRepeat: 'no-repeat' }"
                            >
                                <!-- Subtle overlay for hover effect -->
                                <div class="absolute inset-0 bg-black/10 hover:bg-transparent transition-colors"></div>
                            </div>
                        </div>
                    </div>
                </Transition>

                <!-- Dot Indicators (Only show if multiple ads) -->
                <div v-if="advertisements.length > 1" class="flex justify-center gap-2 mt-6 sm:mt-8">
                    <button 
                        v-for="(ad, index) in advertisements" 
                        :key="ad.id"
                        @click="setAd(index)"
                        class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full transition-all duration-300 focus:outline-none"
                        :class="[
                            currentIndex === index 
                                ? 'bg-customPrimaryColor scale-110 w-6 sm:w-8' 
                                : 'bg-gray-300 hover:bg-gray-400'
                        ]"
                        :aria-label="`Go to slide ${index + 1}`"
                    ></button>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
/* Glassmorphism utility */
.backdrop-blur-md {
    backdrop-filter: blur(12px);
}

/* Fade Transition */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
