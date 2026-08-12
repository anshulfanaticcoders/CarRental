<script setup>
import { computed, ref, watch, getCurrentInstance } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const props = defineProps({
    booking: { type: Object, default: null },
    open: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

const page = usePage();
const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;
const t = (key, fallback) => {
    const value = _t('customerbooking', key);
    if (!value || value === key) return fallback;
    return value;
};

const reason = ref('');
const error = ref('');
const submitting = ref(false);

watch(() => props.open, (open) => {
    if (open) {
        reason.value = '';
        error.value = '';
        submitting.value = false;
    }
});

const vehicleLabel = computed(() => {
    const b = props.booking || {};
    return b.vehicle_name || [b.vehicle?.brand, b.vehicle?.model].filter(Boolean).join(' ') || '';
});

const deadlineInfo = computed(() => {
    const deadline = props.booking?.provider_metadata?.cancellation_deadline;
    if (!deadline) return null;
    const deadlineDate = new Date(deadline);
    return {
        deadline: deadlineDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }),
        isExpired: new Date() > deadlineDate,
    };
});

const close = () => {
    if (submitting.value) return;
    emit('close');
};

const submit = async () => {
    const trimmedReason = reason.value.trim();
    if (trimmedReason.length < 3) {
        error.value = t('cancellation_reason_required', 'Please enter a short cancellation reason (at least 3 characters).');
        return;
    }

    submitting.value = true;
    error.value = '';
    try {
        const axios = (await import('axios')).default;
        await axios.post(route('booking.cancel', { locale: page.props.locale }), {
            booking_id: props.booking.id,
            cancellation_reason: trimmedReason,
        });
        emit('close');
        router.reload({ preserveScroll: true });
    } catch (err) {
        error.value = err?.response?.data?.message
            || t('cancel_failed', 'We could not cancel this booking. Please try again or contact support.');
    } finally {
        submitting.value = false;
    }
};
</script>

<template>
    <Teleport to="body">
        <div v-if="open && booking" class="fixed inset-0 z-[9999] bg-black/60 backdrop-blur-sm flex items-center justify-center p-4" @click.self="close">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md flex flex-col">
                <div class="p-5 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">{{ t('cancel_booking_title', 'Cancel this booking?') }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ booking.booking_number }}<template v-if="vehicleLabel"> — {{ vehicleLabel }}</template></p>
                </div>
                <div class="p-5 space-y-3">
                    <p v-if="deadlineInfo" class="text-xs rounded-lg px-3 py-2"
                        :class="deadlineInfo.isExpired ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'">
                        <template v-if="deadlineInfo.isExpired">
                            {{ t('free_cancellation_expired', 'Free cancellation expired') }} {{ deadlineInfo.deadline }} — {{ t('fees_may_apply', 'cancellation fees may apply.') }}
                        </template>
                        <template v-else>
                            {{ t('free_cancellation_until', 'Free cancellation until') }} {{ deadlineInfo.deadline }}
                        </template>
                    </p>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">
                        {{ t('cancellation_reason', 'Cancellation reason') }} <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        v-model="reason"
                        rows="3"
                        maxlength="500"
                        class="w-full rounded-xl border border-gray-200 p-3 text-sm focus:border-[#153B4F] focus:ring-1 focus:ring-[#153B4F] resize-none"
                        :placeholder="t('cancellation_reason_placeholder', 'Tell us briefly why you are cancelling…')"
                    ></textarea>
                    <p v-if="error" class="text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 rounded-lg px-3 py-2">{{ error }}</p>
                </div>
                <div class="p-4 border-t border-gray-200 flex justify-end gap-2">
                    <button type="button" @click="close" :disabled="submitting"
                        class="px-4 py-2.5 text-sm font-semibold rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors disabled:opacity-50">
                        {{ t('keep_booking', 'Keep booking') }}
                    </button>
                    <button type="button" @click="submit" :disabled="submitting"
                        class="px-5 py-2.5 bg-rose-600 text-white text-sm font-semibold rounded-xl hover:bg-rose-700 transition-colors disabled:opacity-60 inline-flex items-center gap-2">
                        <span v-if="submitting" class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                        {{ submitting ? t('cancelling', 'Cancelling…') : t('confirm_cancellation', 'Cancel booking') }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
