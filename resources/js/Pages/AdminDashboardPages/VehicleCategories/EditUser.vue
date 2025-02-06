<template>
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Edit User</DialogTitle>
        </DialogHeader>
        <form @submit.prevent="updateUser" class="space-y-4">
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <InputLabel for="name" value="Name *" />
                    <Input v-model="editForm.name" required />
                </div>
                <div>
                    <InputLabel for="description" value="Description *" />
                    <Input v-model="editForm.description" required />
                </div>
            </div>
            <div>
                <InputLabel for="image" value="Image" class="mb-2" />
                <div v-if="editForm.existing_image" class="mb-2">
                    <img :src="`/storage/${editForm.existing_image}`" alt="Existing Category Image"
                        class="w-[200px] h-[150px] object-cover mb-2" />
                    <span class="text-sm text-gray-500">Current Image</span>
                </div>
                <input class="border-[2px] rounded-[10px] border-customPrimaryColor p-5 border-dotted" type="file"
                    @change="handleEditFileUpload" accept="image/jpeg,image/png,image/jpg,image/gif" />
            </div>
            <DialogFooter>
                <Button type="submit">Update Category</Button>
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
import { useToast } from 'vue-toastification';
const toast = useToast();

const props = defineProps({
    user: Object,
});

const emit = defineEmits(['close']); // Define the 'close' event
const editForm = ref({
    id: props.user?.id,
    name: props.user?.name,
    description: props.user?.description,
    existing_image: props.user?.image,
    image: null
});

const handleEditFileUpload = (event) => {
    editForm.value.image = event.target.files[0];
};

// Watch for changes in props.user (if the user data is updated dynamically)
watch(() => props.user, (newUser) => {
    editForm.value = {
        ...newUser,
        existing_image: newUser.image,
        image: null
    };
}, { immediate: true });

const updateUser = () => {
    const formData = new FormData();
    formData.append('_method', 'PUT'); // Add this directly to FormData
    formData.append('name', editForm.value.name);
    formData.append('description', editForm.value.description);
    if (editForm.value.image instanceof File) {
        formData.append('image', editForm.value.image);
    }

    // Use router.post with the FormData directly
    router.post(`/vehicles-categories/${editForm.value.id}`, formData, {
        forceFormData: true, // Force Inertia to send as FormData
        onSuccess: () => {
            emit('close');
            toast.success('Vehicle Category updated successfully!', {
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