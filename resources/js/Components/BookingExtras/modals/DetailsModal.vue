<script setup>
defineProps({
    show: Boolean,
    displayVehicleName: String,
    currentPackageLabel: String,
    isOkMobility: Boolean,
    isAdobeCars: Boolean,
    currentProduct: Object,
    okMobilityBaseTotal: Number,
    adobeMandatoryProtection: Number,
    getSelectedExtrasDetails: Array,
    formatRentalPrice: Function,
    formatPrice: Function,
    grandTotal: [String, Number],
    payableAmount: [String, Number],
    pendingAmount: [String, Number],
    effectivePaymentPercentage: Number,
});

const emit = defineEmits(['close']);
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="show" class="fixed inset-0 z-[100000] flex items-center justify-center p-4" @click.self="emit('close')">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
                <div class="modal-content relative bg-white rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                    <div class="sticky top-0 bg-white border-b px-6 py-5 flex justify-between items-center rounded-t-3xl z-10">
                        <h2 class="text-2xl font-bold text-gray-900">Booking Details</h2>
                        <button @click="emit('close')" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                            <svg class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="p-6 space-y-5">
                        <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-2xl p-5">
                            <p class="text-sm text-gray-500 mb-2">Vehicle</p>
                            <p class="font-bold text-gray-900 text-lg">{{ displayVehicleName }}</p>
                            <p class="text-sm text-gray-500 mt-1">{{ currentPackageLabel }}</p>
                        </div>
                        <div class="space-y-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Car Package ({{ currentPackageLabel }})</span>
                                <span class="font-semibold text-gray-900">{{ formatRentalPrice(isOkMobility ? (currentProduct?.total || okMobilityBaseTotal) : (currentProduct?.total || 0)) }}</span>
                            </div>
                            <div v-if="isAdobeCars && adobeMandatoryProtection > 0" class="flex justify-between text-sm">
                                <span class="text-amber-600">Mandatory Liability (PLI)</span>
                                <span class="font-semibold text-amber-600">+{{ formatRentalPrice(adobeMandatoryProtection) }}</span>
                            </div>
                            <div v-for="item in getSelectedExtrasDetails" :key="item.id" class="flex justify-between text-sm">
                                <span class="text-gray-600">{{ item.name }} <span v-if="item.qty > 1" class="text-xs text-gray-400">x{{ item.qty }}</span></span>
                                <span class="font-semibold" :class="item.isFree ? 'text-green-600' : 'text-gray-800'">{{ item.isFree ? 'FREE' : formatRentalPrice(item.total) }}</span>
                            </div>
                        </div>
                        <hr class="border-gray-200">
                        <div class="space-y-3">
                            <div class="flex justify-between text-lg">
                                <span class="font-bold text-gray-800">Grand Total</span>
                                <span class="font-bold text-[#1e3a5f]">{{ formatPrice(grandTotal) }}</span>
                            </div>
                            <div class="flex justify-between text-sm bg-green-50 p-4 rounded-xl">
                                <span class="font-semibold text-green-700">Pay Now ({{ effectivePaymentPercentage }}%)</span>
                                <span class="font-bold text-green-700">{{ formatPrice(payableAmount) }}</span>
                            </div>
                            <div class="flex justify-between text-sm bg-amber-50 p-4 rounded-xl">
                                <span class="font-semibold text-amber-700">Pay on Arrival</span>
                                <span class="font-bold text-amber-700">{{ formatPrice(pendingAmount) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="sticky bottom-0 bg-white border-t px-6 py-5 rounded-b-3xl">
                        <button @click="emit('close')" class="btn-cta w-full py-4 rounded-xl bg-gradient-to-r from-[#1e3a5f] to-[#2d5a8f] text-white font-bold shadow-lg">Close</button>
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

/* ── CTA shimmer ── */
@keyframes shimmer {
    0%   { left: -100%; }
    100% { left: 200%; }
}
.btn-cta {
    position: relative;
    overflow: hidden;
}
.btn-cta::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 50%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
    animation: shimmer 3s ease-in-out infinite;
}
</style>
