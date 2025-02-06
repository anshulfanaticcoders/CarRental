<template>
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Edit User</DialogTitle>
        </DialogHeader>
        <form @submit.prevent="updateUser" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel for="first_name" value="First Name *" />
                    <Input v-model="editForm.first_name" required />
                </div>
                <div>
                    <InputLabel for="last_name" value="Last Name *" />
                    <Input v-model="editForm.last_name" required />
                </div>
            </div>
            <div>
                <InputLabel for="email" value="Email *" />
                <Input v-model="editForm.email" type="email" required readonly class="bg-gray-200 cursor-not-allowed" />
            </div>
            <div>
                <InputLabel for="phone" value="Phone *" />
                <Input v-model="editForm.phone" required />
            </div>
            <!-- <div>
                <InputLabel for="role" value="Role *" />
                <Select v-model="editForm.role" required>
                    <SelectTrigger>
                        <SelectValue placeholder="Select Role" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="customer">Customer</SelectItem>
                    </SelectContent>
                </Select>
            </div> -->
            <div>
                <InputLabel for="status" value="Status *" />
                <Select v-model="editForm.status" required>
                    <SelectTrigger>
                        <SelectValue placeholder="Select Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="active">Active</SelectItem>
                        <SelectItem value="inactive">Inactive</SelectItem>
                        <SelectItem value="suspended">Suspended</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <DialogFooter>
                <Button type="submit">Update User</Button>
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

const props = defineProps({
    user: Object,
});

const emit = defineEmits(['close']); // Define the 'close' event
const editForm = ref({ ...props.user });

// Watch for changes in props.user (if the user data is updated dynamically)
watch(() => props.user, (newUser) => {
    editForm.value = { ...newUser };
}, { immediate: true });

const updateUser = () => {
    router.put(`/users/${editForm.value.id}`, editForm.value, {
        onSuccess: () => {
            emit('close');
        },
    });
};
</script>