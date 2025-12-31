<template>
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Edit Addon</DialogTitle>
        </DialogHeader>
        <form @submit.prevent="updateUser" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel for="extra_type" value="Extra Type *" />
                    <Input v-model="editForm.extra_type" required />
                </div>
                <div>
                    <InputLabel for="extra_name" value="Extra Name *" />
                    <Input v-model="editForm.extra_name" required />
                </div>
            </div>
            <div>
                <InputLabel for="quantity" value="Quantity *" />
                <Input v-model="editForm.quantity" type="email" required readonly class="bg-gray-200 cursor-not-allowed" />
            </div>
            <div>
                <InputLabel for="price" value="Price *" />
                <Input v-model="editForm.price" required />
            </div>
            <DialogFooter>
                <Button type="submit" :disabled="isSubmitting">
                    <span v-if="isSubmitting" class="flex items-center gap-2">
                        <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                        Updating...
                    </span>
                    <span v-else>Update Addon</span>
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
import Button from "@/Components/ui/button/Button.vue";
import { toast } from "vue-sonner";
const props = defineProps({
    user: Object,
});

const emit = defineEmits(['close']);
const editForm = ref({ ...props.user });
const isSubmitting = ref(false);

// Watch for changes in props.user (if the user data is updated dynamically)
watch(() => props.user, (newUser) => {
    editForm.value = { ...newUser };
}, { immediate: true });

const updateUser = () => {
    isSubmitting.value = true;
    router.put(`/booking-addons/${editForm.value.id}`, editForm.value, {
        onSuccess: () => {
            toast.success('Vehicle addon updated successfully');
        },
        onError: (errors) => {
            toast.error('Failed to update addon. Please try again.');
        },
        onFinish: () => {
            isSubmitting.value = false;
            emit('close');
        },
    });
};
</script>