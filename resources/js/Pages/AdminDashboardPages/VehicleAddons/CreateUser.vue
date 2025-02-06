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
                <Button type="submit">Create Addon</Button>
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
import { useToast } from 'vue-toastification';
const toast = useToast();

const form = ref({
    extra_type: '',
    extra_name: '',
    description: '',
    quantity: null,
    price: null
});
const emit = defineEmits(['close']); // Define the 'close' event
const handleFileUpload = (event) => {
    form.value.image = event.target.files[0];
};
const submitForm = () => {
    router.post("/booking-addons", form.value, {
        onSuccess: () => {
            form.value = {
                extra_type: '',
                extra_name: '',
                description: '',
                quantity: null,
                price: null
            };
            emit('close');
            toast.success('Vehicle addon added successfully!', {
                position: 'top-right',
                timeout: 3000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
        },
    });
};
</script>