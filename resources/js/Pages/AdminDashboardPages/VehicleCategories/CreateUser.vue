<template>
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Create New User</DialogTitle>
        </DialogHeader>
        <form @submit.prevent="submitForm" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel for="name" value="Name *" />
                    <Input v-model="form.name" required />
                </div>
                <div>
                    <InputLabel for="description" value="Description *" />
                    <Input v-model="form.description" required />
                </div>
            </div>
            <div>
                <InputLabel for="image" value="Image *" />
                <input class="border-[2px] rounded-[10px] border-customPrimaryColor p-5 border-dotted" type="file"
                    @change="handleFileUpload" accept="image/jpeg,image/png,image/jpg,image/gif" required />
            </div>
            <DialogFooter>
                <Button type="submit">Create Category</Button>
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
    name: '',
    description: '',
    image: null,
});
const emit = defineEmits(['close']); // Define the 'close' event
const handleFileUpload = (event) => {
    form.value.image = event.target.files[0];
};
const submitForm = () => {
    router.post("/vehicles-categories", form.value, {
        onSuccess: () => {
            form.value = {
                name: "",
                description: "",
                image: "",
            };
            emit('close');
            toast.success('Vehicle Category added successfully!', {
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