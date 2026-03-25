<script setup>
defineProps({
    show: Boolean,
    images: Array,
    currentIndex: Number,
    displayVehicleName: String,
});

const emit = defineEmits(['close', 'next', 'prev', 'set-index']);
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="show && images.length" class="fixed inset-0 z-[10001] bg-black/90 flex items-center justify-center" @click.self="emit('close')">
                <div class="modal-content relative w-full h-full flex items-center justify-center">
                    <!-- Close -->
                    <button @click="emit('close')" class="absolute top-4 right-4 z-10 w-10 h-10 rounded-full bg-white/10 backdrop-blur text-white flex items-center justify-center hover:bg-white/20 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    <!-- Counter -->
                    <div v-if="images.length > 1" class="absolute top-4 left-4 z-10 bg-white/10 backdrop-blur text-white text-sm font-semibold px-3 py-1.5 rounded-full">
                        {{ currentIndex + 1 }} / {{ images.length }}
                    </div>
                    <!-- Prev -->
                    <button v-if="images.length > 1" @click="emit('prev')" class="absolute left-4 top-1/2 -translate-y-1/2 z-10 w-12 h-12 rounded-full bg-white/10 backdrop-blur text-white flex items-center justify-center hover:bg-white/20 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <!-- Image -->
                    <img :src="images[currentIndex]" :alt="displayVehicleName" class="max-w-[90vw] max-h-[85vh] object-contain rounded-lg shadow-2xl" />
                    <!-- Next -->
                    <button v-if="images.length > 1" @click="emit('next')" class="absolute right-4 top-1/2 -translate-y-1/2 z-10 w-12 h-12 rounded-full bg-white/10 backdrop-blur text-white flex items-center justify-center hover:bg-white/20 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <!-- Dots -->
                    <div v-if="images.length > 1" class="absolute bottom-6 left-1/2 -translate-x-1/2 z-10 flex items-center gap-2">
                        <button v-for="(img, idx) in images" :key="idx" @click="emit('set-index', idx)"
                            class="w-2.5 h-2.5 rounded-full transition-all duration-200"
                            :class="idx === currentIndex ? 'bg-white w-6' : 'bg-white/40 hover:bg-white/70'">
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
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
