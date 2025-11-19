<template>
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Edit Vendor</DialogTitle>
        </DialogHeader>
        <form @submit.prevent="updateUser" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel for="first_name" value="First Name *" />
                    <Input v-model="editForm.user.first_name" required readonly class="bg-gray-200 cursor-not-allowed"/>
                </div>
                <div>
                    <InputLabel for="last_name" value="Last Name *" />
                    <Input v-model="editForm.user.last_name" required readonly class="bg-gray-200 cursor-not-allowed"/>
                </div>
            </div>
            <div>
                <InputLabel for="email" value="Email *" />
                <Input v-model="editForm.company_email" type="email" required readonly class="bg-gray-200 cursor-not-allowed" />
            </div>
            <div>
                <InputLabel for="phone" value="Phone *" />
                <Input v-model="editForm.company_phone_number" required  readonly class="bg-gray-200 cursor-not-allowed"/>
            </div>
            <div>
                <InputLabel for="status" value="Status *" />
                <Select v-model="editForm.status" required>
                    <SelectTrigger>
                        <SelectValue placeholder="Select Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="pending">Pending</SelectItem>
                        <SelectItem value="approved">Approved</SelectItem>
                        <SelectItem value="rejected">Rejected</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <DialogFooter>
                <Button type="submit" :disabled="isSubmitting" class="relative">
                    <span v-if="isSubmitting" class="flex items-center gap-2">
                        <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                        Updating...
                    </span>
                    <span v-else>Update Vendor</span>
                </Button>
            </DialogFooter>
        </form>
    </DialogContent>
</template>

<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/Components/ui/dialog";
import Input from "@/Components/ui/input/Input.vue";
import InputLabel from "@/Components/InputLabel.vue";
import SelectContent from "@/Components/ui/select/SelectContent.vue";
import SelectItem from "@/Components/ui/select/SelectItem.vue";
import SelectTrigger from "@/Components/ui/select/SelectTrigger.vue";
import Select from "@/Components/ui/select/Select.vue";
import SelectValue from "@/Components/ui/select/SelectValue.vue";
import Button from "@/Components/ui/button/Button.vue";
import { useToast } from 'vue-toastification';
const toast = useToast();
const props = defineProps({
    user: Object,
});

const emit = defineEmits(['close']); // Define the 'close' event
const editForm = ref({ ...props.user });
const isSubmitting = ref(false);

// Watch for changes in props.user (if the user data is updated dynamically)
watch(() => props.user, (newUser) => {
    editForm.value = { ...newUser };
}, { immediate: true });

const updateUser = () => {
    // Set loading state
    isSubmitting.value = true;

    const payload = {
        status: editForm.value.status,
    };
    router.put(`/vendors/${editForm.value.id}`, payload, {
        onSuccess: () => {
            emit('close');
            toast.success('Vendor status updated successfully!', {
                position: 'top-right',
                timeout: 3000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
        },
        onError: (errors) => {
            // Show error notification if validation fails
            const errorMessage = Object.values(errors)[0] || 'An error occurred while updating the vendor';
            toast.error(errorMessage, {
                position: 'top-right',
                timeout: 3000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
        },
        onFinish: () => {
            // Reset loading state regardless of success or error
            isSubmitting.value = false;
        },
    });
};
</script>