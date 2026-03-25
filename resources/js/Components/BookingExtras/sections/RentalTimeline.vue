<script setup>
defineProps({
    pickupLocation: String,
    dropoffLocation: String,
    locationName: String,
    pickupDate: String,
    pickupTime: String,
    dropoffDate: String,
    dropoffTime: String,
})
</script>

<template>
    <section class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 fade-in-up" style="animation-delay:0.05s">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Pickup -->
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-emerald-50 to-green-50 rounded-full flex items-center justify-center border-2 border-emerald-200 shadow-sm flex-shrink-0">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div class="min-w-0">
                    <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Pickup</span>
                    <p class="text-sm font-bold text-gray-900 leading-snug mt-0.5">{{ pickupLocation || locationName }}</p>
                    <p class="text-xs text-gray-500 mt-1 flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        {{ pickupDate }} <span class="text-gray-300">|</span> {{ pickupTime }}
                    </p>
                </div>
            </div>
            <!-- Dropoff -->
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-rose-50 to-pink-50 rounded-full flex items-center justify-center border-2 border-rose-200 shadow-sm flex-shrink-0">
                    <svg class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <span class="text-xs font-bold text-rose-700 uppercase tracking-wider">Dropoff</span>
                    <p class="text-sm font-bold text-gray-900 leading-snug mt-0.5">{{ dropoffLocation || pickupLocation || locationName }}</p>
                    <p class="text-xs text-gray-500 mt-1 flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        {{ dropoffDate }} <span class="text-gray-300">|</span> {{ dropoffTime }}
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
