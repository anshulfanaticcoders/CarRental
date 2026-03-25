<script setup>
defineProps({
    show: Boolean,
    locationOpeningHours: Array,
    locationOutOfHours: Array,
    locationDaytimeClosures: Array,
    locationDetails: Object,
    formatHourWindow: Function,
});

const emit = defineEmits(['close']);
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/50 px-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h3 class="text-lg font-bold text-[#1e3a5f]">Hours & Policies</h3>
                <button @click="emit('close')" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5 max-h-[70vh] overflow-y-auto">
                <div v-if="locationOpeningHours.length" class="mb-4">
                    <p class="text-sm font-semibold text-gray-800 mb-2">Opening Hours</p>
                    <div class="space-y-1 text-sm text-gray-600">
                        <p v-for="(day, index) in locationOpeningHours" :key="`modal-open-${index}`">
                            <span class="font-medium text-gray-700">{{ day.name }}:</span>
                            <span class="ml-1">{{ formatHourWindow(day) || 'Closed' }}</span>
                        </p>
                    </div>
                </div>
                <div v-if="locationOutOfHours.length" class="mb-4">
                    <p class="text-sm font-semibold text-gray-800 mb-2">Out of Hours Dropoff</p>
                    <div class="space-y-1 text-sm text-gray-600">
                        <p v-for="(day, index) in locationOutOfHours" :key="`modal-out-${index}`">
                            <span class="font-medium text-gray-700">{{ day.name }}:</span>
                            <span class="ml-1">{{ formatHourWindow(day) || 'Unavailable' }}</span>
                        </p>
                    </div>
                </div>
                <div v-if="locationDaytimeClosures.length" class="mb-4">
                    <p class="text-sm font-semibold text-gray-800 mb-2">Daytime Closures</p>
                    <div class="space-y-1 text-sm text-gray-600">
                        <p v-for="(day, index) in locationDaytimeClosures" :key="`modal-closure-${index}`">
                            <span class="font-medium text-gray-700">{{ day.name }}:</span>
                            <span class="ml-1">{{ formatHourWindow(day) || 'None' }}</span>
                        </p>
                    </div>
                </div>
                <p v-if="locationDetails?.out_of_hours_charge" class="text-sm text-gray-600">
                    <span class="font-semibold text-gray-800">Out of Hours Charge:</span>
                    {{ locationDetails.out_of_hours_charge }}
                </p>
            </div>
        </div>
    </div>
</template>
