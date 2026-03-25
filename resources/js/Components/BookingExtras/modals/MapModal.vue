<script setup>
import { ref } from 'vue';

defineProps({
    show: Boolean,
    hasVehicleCoords: Boolean,
    isDifferentDropoff: Boolean,
    pickupLocation: String,
    dropoffLocation: String,
    locationName: String,
});

const emit = defineEmits(['close']);

const mapModalRef = ref(null);
defineExpose({ mapModalRef });
</script>

<template>
    <Transition name="modal">
        <div v-if="show && hasVehicleCoords" class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/60 px-4" @click.self="emit('close')">
            <div class="modal-content bg-white rounded-2xl shadow-2xl w-full max-w-4xl overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
                    <h3 class="text-base font-bold text-[#1e3a5f] flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Location Map
                    </h3>
                    <button @click="emit('close')" class="text-gray-400 hover:text-gray-700 transition-colors p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div ref="mapModalRef" class="w-full h-[60vh]"></div>
                <div v-if="isDifferentDropoff" class="px-5 py-2 border-t border-gray-100 flex items-center gap-4 text-xs text-gray-500">
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Pickup: {{ pickupLocation || locationName }}</span>
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-500"></span> Dropoff: {{ dropoffLocation }}</span>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
/* ── Modal Transitions ── */
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}
.modal-enter-active .modal-content,
.modal-leave-active .modal-content {
    transition: all 0.3s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
.modal-enter-from .modal-content,
.modal-leave-to .modal-content {
    opacity: 0;
    transform: scale(0.95) translateY(10px);
}
.modal-enter-to .modal-content,
.modal-leave-from .modal-content {
    opacity: 1;
    transform: scale(1) translateY(0);
}
</style>
