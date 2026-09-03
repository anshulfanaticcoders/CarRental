<script setup>
import { shallowRef, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { AlertTriangle, CheckCircle2 } from 'lucide-vue-next';
import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';

const props = defineProps({
    open: { type: Boolean, default: false },
    booking: { type: Object, default: null },
});

const emit = defineEmits(['update:open']);
const supplierReference = shallowRef('');
const supplierChecked = shallowRef(false);
const error = shallowRef('');
const submitting = shallowRef(false);

const reset = () => {
    supplierReference.value = '';
    supplierChecked.value = false;
    error.value = '';
    submitting.value = false;
};

const close = () => emit('update:open', false);

const submit = () => {
    if (!supplierChecked.value) {
        error.value = 'Confirm that you found this exact reservation in the supplier portal.';
        return;
    }
    if (supplierReference.value.trim().length < 2) {
        error.value = 'Enter the supplier reference shown in the supplier portal.';
        return;
    }

    submitting.value = true;
    error.value = '';
    router.post(`/customer-bookings/${props.booking.id}/record-supplier-reference`, {
        supplier_checked: true,
        supplier_reference: supplierReference.value.trim(),
    }, {
        preserveScroll: true,
        onSuccess: close,
        onError: (errors) => {
            error.value = errors.supplier_reference
                || errors.supplier_checked
                || 'The supplier reference could not be saved.';
        },
        onFinish: () => { submitting.value = false; },
    });
};

watch(() => props.open, (open) => { if (open) reset(); });
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Record supplier reference</DialogTitle>
                <DialogDescription>
                    Use this only when the supplier portal shows the reservation for
                    <strong>#{{ booking?.booking_number }}</strong>. Saving it queues Stripe capture only; it never creates another supplier reservation.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4 py-3">
                <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
                    <div class="flex gap-2">
                        <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                        <p>Match the customer, vehicle, pickup dates, and location before continuing. A wrong reference could capture the wrong authorization.</p>
                    </div>
                </div>

                <div>
                    <label for="supplier-reference" class="mb-1.5 block text-sm font-medium">Supplier reference</label>
                    <Input
                        id="supplier-reference"
                        v-model="supplierReference"
                        maxlength="191"
                        autocomplete="off"
                        placeholder="Example: EMR-784521"
                    />
                </div>

                <label class="flex cursor-pointer items-start gap-3 rounded-xl border bg-muted/30 p-3 text-sm">
                    <input v-model="supplierChecked" type="checkbox" class="mt-1 h-4 w-4 accent-[#153b4f]" />
                    <span>I found this exact reservation in the supplier portal and verified its booking details.</span>
                </label>

                <p v-if="error" class="text-sm font-medium text-red-600">{{ error }}</p>
            </div>

            <DialogFooter class="gap-2">
                <Button variant="outline" :disabled="submitting" @click="close">Cancel</Button>
                <Button :disabled="submitting || !supplierChecked" @click="submit">
                    <CheckCircle2 class="mr-2 h-4 w-4" aria-hidden="true" />
                    {{ submitting ? 'Saving…' : 'Save reference and capture' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
