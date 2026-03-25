<script setup>
import { ref } from 'vue';
import { Leaf } from 'lucide-vue-next';

const props = defineProps({
    vehicleImage: String,
    displayVehicleName: String,
    hasMultipleImages: Boolean,
    currentHeroImage: String,
    allHeroImages: { type: Array, default: () => [] },
    heroImageIndex: { type: Number, default: 0 },
    vehicleSpecs: Object,
    hasVehicleCoords: Boolean,
    isDifferentDropoff: Boolean,
    providerBadge: Object,
    vehicle: Object,
    numberOfDays: Number,
    currentProduct: Object,
    seatingIcon: String,
    doorIcon: String,
    transmissionIcon: String,
    acIcon: String,
    fuelIcon: String,
});

const emit = defineEmits(['hero-next', 'hero-prev', 'set-hero-index', 'show-lightbox', 'show-map']);

const vehicleMapRef = ref(null);
defineExpose({ vehicleMapRef });
</script>

<template>
    <section class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden fade-in-up">
        <!-- Fixed-height row: both halves equal -->
        <div class="flex flex-col md:flex-row h-auto md:h-[240px]">
            <!-- Left: Vehicle Image with carousel for internal -->
            <div class="relative w-full md:w-1/2 h-[220px] md:h-[240px] bg-gray-50 overflow-hidden group">
                <!-- Carousel image (internal) or single image -->
                <img v-if="hasMultipleImages" :src="currentHeroImage" :alt="displayVehicleName" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300" />
                <img v-else-if="vehicleImage" :src="vehicleImage" :alt="displayVehicleName" class="absolute inset-0 w-full h-full object-cover" />
                <div v-else class="absolute inset-0 flex items-center justify-center">
                    <svg class="w-24 h-24 text-gray-300" viewBox="0 0 24 24" fill="currentColor"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>
                </div>
                <!-- Prev / Next arrows (show on hover) -->
                <template v-if="hasMultipleImages">
                    <button @click.stop="emit('hero-prev')" class="absolute left-2 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-black/40 backdrop-blur-sm text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-black/60">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button @click.stop="emit('hero-next')" class="absolute right-2 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-black/40 backdrop-blur-sm text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-black/60">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <!-- Dot indicators -->
                    <div class="absolute bottom-3 left-1/2 -translate-x-1/2 z-20 flex items-center gap-1.5">
                        <button v-for="(img, idx) in allHeroImages" :key="idx" @click.stop="emit('set-hero-index', idx)"
                            class="w-2 h-2 rounded-full transition-all duration-200"
                            :class="idx === heroImageIndex ? 'bg-white w-4' : 'bg-white/50 hover:bg-white/80'">
                        </button>
                    </div>
                </template>
                <!-- Lightbox enlarge button -->
                <button v-if="vehicleImage" @click.stop="emit('show-lightbox')" class="absolute bottom-3 right-3 z-20 w-8 h-8 rounded-lg bg-black/40 backdrop-blur-sm text-white flex items-center justify-center hover:bg-black/60 transition-colors" :class="{ 'bottom-8': hasMultipleImages }" title="View fullscreen">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                </button>
                <!-- Provider badge -->
                <div v-if="providerBadge" class="provider-ribbon" :class="providerBadge.ribbonClassName">
                    {{ providerBadge.label }}
                </div>
                <div class="absolute bottom-3 left-3 flex items-center gap-1.5" :class="{ 'bottom-8': hasMultipleImages }">
                    <span v-if="vehicleSpecs.acriss" class="bg-black/40 backdrop-blur text-white text-[11px] font-bold px-2 py-1 rounded">{{ vehicleSpecs.acriss }}</span>
                    <span v-if="vehicle?.category" class="bg-black/40 backdrop-blur text-white text-[11px] font-medium px-2 py-1 rounded">{{ vehicle.category }}</span>
                </div>
            </div>
            <!-- Right: Map — 50% width, same fixed height -->
            <div v-if="hasVehicleCoords" class="relative w-full md:w-1/2 h-[200px] md:h-[240px] border-t md:border-t-0 md:border-l border-gray-200">
                <div ref="vehicleMapRef" class="absolute inset-0 w-full h-full"></div>
                <div v-if="isDifferentDropoff" class="absolute bottom-2 left-2 z-[1000] flex items-center gap-2 bg-white/90 backdrop-blur-sm rounded px-2 py-1 shadow-sm text-[10px] font-medium text-gray-600">
                    <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Pickup</span>
                    <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Dropoff</span>
                </div>
                <button @click="emit('show-map')" class="absolute bottom-2 right-2 z-[1000] bg-white/90 backdrop-blur-sm rounded-lg p-1.5 shadow hover:bg-white hover:shadow-md transition-all" title="Expand map">
                    <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                </button>
            </div>
        </div>
        <!-- Vehicle name + specs inline below -->
        <div class="px-5 py-4 border-t border-gray-100">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-[#1e3a5f] leading-tight">{{ displayVehicleName }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">{{ vehicle?.category || 'Economy' }} &middot; {{ vehicleSpecs.transmission || 'Manual' }}</p>
                </div>
                <div class="bg-[#1e3a5f]/5 border border-[#1e3a5f]/15 rounded-lg px-3 py-2 text-center flex-shrink-0">
                    <span class="text-lg font-bold text-[#1e3a5f] block leading-none">{{ numberOfDays }}</span>
                    <span class="text-[10px] font-semibold text-[#1e3a5f]/70 uppercase tracking-wider">Days</span>
                </div>
            </div>
            <!-- Specs chips inline -->
            <div class="flex flex-wrap gap-1.5 mt-3">
                <div v-if="vehicleSpecs.passengers" class="flex items-center gap-1.5 px-2 py-1 bg-gray-50 rounded border border-gray-100 text-xs text-gray-600">
                    <img :src="seatingIcon" class="w-3.5 h-3.5" alt="" />
                    {{ vehicleSpecs.passengers }} Seats
                </div>
                <div v-if="vehicleSpecs.doors" class="flex items-center gap-1.5 px-2 py-1 bg-gray-50 rounded border border-gray-100 text-xs text-gray-600">
                    <img :src="doorIcon" class="w-3.5 h-3.5" alt="" />
                    {{ vehicleSpecs.doors }}
                </div>
                <div v-if="vehicleSpecs.transmission" class="flex items-center gap-1.5 px-2 py-1 bg-gray-50 rounded border border-gray-100 text-xs text-gray-600">
                    <img :src="transmissionIcon" class="w-3.5 h-3.5" alt="" />
                    {{ vehicleSpecs.transmission }}
                </div>
                <div v-if="vehicleSpecs.airConditioning" class="flex items-center gap-1.5 px-2 py-1 bg-gray-50 rounded border border-gray-100 text-xs text-gray-600">
                    <img :src="acIcon" class="w-3.5 h-3.5" alt="" />
                    A/C
                </div>
                <div v-if="vehicleSpecs.bagDisplay" class="flex items-center gap-1.5 px-2 py-1 bg-gray-50 rounded border border-gray-100 text-xs text-gray-600">
                    <svg class="w-3.5 h-3.5 text-[#1e3a5f]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    {{ vehicleSpecs.bagDisplay }}
                </div>
                <div v-if="currentProduct?.mileage" class="flex items-center gap-1.5 px-2 py-1 bg-gray-50 rounded border border-gray-100 text-xs text-gray-600">
                    <svg class="w-3.5 h-3.5 text-[#1e3a5f]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    {{ currentProduct.mileage }}
                </div>
                <div v-if="vehicleSpecs.fuel" class="flex items-center gap-1.5 px-2 py-1 bg-gray-50 rounded border border-gray-100 text-xs text-gray-600">
                    <img :src="fuelIcon" class="w-3.5 h-3.5" alt="" />
                    {{ vehicleSpecs.fuel }}
                </div>
                <div v-if="vehicleSpecs.co2" class="flex items-center gap-1.5 px-2 py-1 bg-green-50 rounded border border-green-100 text-xs text-green-700">
                    <component :is="Leaf" class="w-3.5 h-3.5 text-green-600" />
                    {{ vehicleSpecs.co2 }} g/km
                </div>
            </div>
        </div>
    </section>
</template>
