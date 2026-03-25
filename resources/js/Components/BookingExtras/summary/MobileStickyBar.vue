<script setup>
defineProps({
    isSummaryVisible: Boolean,
    grandTotal: [String, Number],
    payableAmount: [String, Number],
    effectivePaymentPercentage: Number,
    formatPrice: Function,
});

const emit = defineEmits(['scroll-to-summary']);
</script>

<template>
    <Transition name="slide-up">
        <div v-if="!isSummaryVisible"
            class="fixed bottom-0 left-0 right-0 z-[100] bg-white/90 backdrop-blur-md border-t border-gray-100 p-4 lg:hidden shadow-[0_-10px_30px_rgba(0,0,0,0.08)]">
            <div class="flex items-center justify-between gap-4 max-w-lg mx-auto">
                <div class="flex flex-col">
                    <span class="text-xs text-gray-500 font-bold uppercase tracking-wider">Total Price</span>
                    <span class="text-xl font-bold text-gray-900">{{ formatPrice(grandTotal) }}</span>
                    <span v-if="effectivePaymentPercentage > 0" class="text-xs text-emerald-600 font-bold">
                        Pay Now {{ formatPrice(payableAmount) }} ({{ effectivePaymentPercentage }}%)
                    </span>
                </div>
                <button @click="emit('scroll-to-summary')"
                    class="bg-gradient-to-r from-[#1e3a5f] to-[#2d5a8f] text-white px-5 py-3 rounded-xl font-bold text-sm shadow-lg active:scale-95 transition-transform">
                    View Summary &darr;
                </button>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
/* ── Slide Up Transition ── */
.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-up-enter-from,
.slide-up-leave-to {
    transform: translateY(100%);
    opacity: 0;
}
</style>
