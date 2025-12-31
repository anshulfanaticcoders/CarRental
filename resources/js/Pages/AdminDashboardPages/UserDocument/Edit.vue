<template>
    <DialogContent class="sm:max-w-[600px]">
        <DialogHeader>
            <DialogTitle>Verify Document</DialogTitle>
            <DialogDescription>
                Update the verification status of this document.
            </DialogDescription>
        </DialogHeader>
        <form @submit.prevent="updateDocument">
            <div class="grid gap-4 py-4">
                <div class="flex flex-col space-y-2">
                    <Label for="status">Verification Status</Label>
                    <Select id="status" v-model="form.verification_status">
                        <SelectTrigger>
                            <SelectValue placeholder="Select status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="pending">Pending</SelectItem>
                            <SelectItem value="verified">Verified</SelectItem>
                            <SelectItem value="rejected">Rejected</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <DialogFooter>
                <Button type="button" variant="outline" @click="$emit('close')">Cancel</Button>
                <Button type="submit" :disabled="processing" class="relative">
                    <span v-if="processing" class="flex items-center gap-2">
                        <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                        Updating...
                    </span>
                    <span v-else>Update</span>
                </Button>
            </DialogFooter>
        </form>
    </DialogContent>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { 
    DialogContent, 
    DialogHeader, 
    DialogTitle, 
    DialogDescription, 
    DialogFooter 
} from "@/Components/ui/dialog";
import { Label } from "@/Components/ui/label";
import { Input } from "@/Components/ui/input";
import { 
    Select, 
    SelectContent, 
    SelectItem, 
    SelectTrigger, 
    SelectValue 
} from "@/Components/ui/select";
import Button from "@/Components/ui/button/Button.vue";
import { toast } from "vue-sonner";

const props = defineProps({
    document: Object,
});

const emit = defineEmits(['close']);

// Initialize the form with the document data
const form = useForm({
    verification_status: props.document.verification_status,
});

// Watch for changes in the document prop and reinitialize the form
watch(() => props.document, (newDocument) => {
    form.verification_status = newDocument.verification_status;
}, { immediate: true });

const processing = computed(() => form.processing);

const updateDocument = () => {
    form.put(`/admin/user-documents/${props.document.id}`, {
        onSuccess: () => {
            // Show appropriate toast based on verification status
            if (form.verification_status === 'verified') {
                toast.success('Document verified successfully');
            } else if (form.verification_status === 'rejected') {
                toast.error('Document rejected');
            } else if (form.verification_status === 'pending') {
                toast.info('Document marked as pending');
            }
        },
        onError: (errors) => {
            const errorMessage = Object.values(errors)[0] || 'An error occurred while updating the document';
            toast.error(errorMessage);
        },
        onFinish: () => {
            emit('close');
        },
    });
};
</script>