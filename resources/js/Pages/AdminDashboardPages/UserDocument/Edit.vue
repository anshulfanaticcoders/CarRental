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
                
                <div class="flex flex-col space-y-2">
                    <Label for="expiresAt">Expiration Date</Label>
                    <Input 
                        id="expiresAt" 
                        type="date" 
                        v-model="form.expires_at" 
                        :min="getTodayDate()"
                    />
                </div>

                <div class="flex flex-col space-y-2">
                    <Label>Document Preview</Label>
                    <div class="border rounded-md p-2 flex justify-center">
                        <img :src="document.document_file" alt="Document" class="max-h-[200px] object-contain" />
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button type="button" variant="outline" @click="$emit('close')">Cancel</Button>
                <Button type="submit" :disabled="processing">Update</Button>
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

const props = defineProps({
    document: Object,
});

const emit = defineEmits(['close']);

// Format the date for input type="date" (YYYY-MM-DD)
const formatDateForInput = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date instanceof Date && !isNaN(date) 
        ? date.toISOString().split('T')[0] 
        : '';
};

// Initialize the form with the document data
const form = useForm({
    verification_status: props.document.verification_status,
    expires_at: formatDateForInput(props.document.expires_at),
});

// Watch for changes in the document prop and reinitialize the form
watch(() => props.document, (newDocument) => {
    form.verification_status = newDocument.verification_status;
    form.expires_at = formatDateForInput(newDocument.expires_at);
}, { immediate: true });

// Get today's date in YYYY-MM-DD format for min attribute
const getTodayDate = () => {
    const today = new Date();
    return today.toISOString().split('T')[0];
};

const processing = computed(() => form.processing);

const updateDocument = () => {
    form.put(`/admin/user-documents/${props.document.id}`, {
        onSuccess: () => {
            emit('close');
        },
    });
};
</script>