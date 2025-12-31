<template>
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Create New Addon</DialogTitle>
        </DialogHeader>
        <form @submit.prevent="submitForm" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel for="extra_type" value="Extra Type *" />
                    <Input v-model="form.extra_type" required />
                </div>
                <div>
                    <InputLabel for="extra_name" value="Extra Name *" />
                    <Input v-model="form.extra_name" required />
                </div>
            </div>
            <div>
                <InputLabel for="description" value="Description *" />
                <textarea v-model="form.description" class="w-full border rounded p-2" required></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel for="quantity" value="Quantity *" />
                    <Input v-model="form.quantity" type="number" required />
                </div>
                <div>
                    <InputLabel for="price" value="Price *" />
                    <Input v-model="form.price" type="number" step="0.01" required />
                </div>
            </div>
            <DialogFooter>
                <Button type="submit" :disabled="isSubmitting">
                    <span v-if="isSubmitting" class="flex items-center gap-2">
                        <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                        Creating...
                    </span>
                    <span v-else>Create Addon</span>
                </Button>
            </DialogFooter>
        </form>
    </DialogContent>
</template>

<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/Components/ui/dialog";
import Input from "@/Components/ui/input/Input.vue";
import InputLabel from "@/Components/InputLabel.vue";
import Button from "@/Components/ui/button/Button.vue";
import { toast } from "vue-sonner";

const form = ref({
    extra_type: '',
    extra_name: '',
    description: '',
    quantity: null,
    price: null
});
const emit = defineEmits(['close']);
const isSubmitting = ref(false);

const handleFileUpload = (event) => {
    form.value.image = event.target.files[0];
};

const submitForm = () => {
    isSubmitting.value = true;
    router.post("/booking-addons", form.value, {
        onSuccess: () => {
            toast.success('Vehicle addon added successfully');
            emit('close'); // Close immediately on success
            form.value = {
                extra_type: '',
                extra_name: '',
                description: '',
                quantity: null,
                price: null
            };
        },
        onError: (errors) => {
            toast.error('Failed to create addon. Please try again.');
            isSubmitting.value = false;
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};
</script>