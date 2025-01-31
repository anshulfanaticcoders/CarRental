<template>
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Create New User</DialogTitle>
        </DialogHeader>
        <form @submit.prevent="submitForm" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel for="first_name" value="First Name *" />
                    <Input v-model="form.first_name" required />
                </div>
                <div>
                    <InputLabel for="last_name" value="Last Name *" />
                    <Input v-model="form.last_name" required />
                </div>
            </div>
            <div>
                <InputLabel for="email" value="Email *" />
                <Input v-model="form.email" type="email" required />
            </div>
            <div>
                <InputLabel for="phone" value="Phone *" />
                <Input v-model="form.phone" required />
            </div>
            <div>
                <InputLabel for="password" value="Password *" />
                <Input v-model="form.password" type="password" required />
            </div>
            <div>
                <InputLabel for="password_confirmation" value="Confirm Password *" />
                <Input v-model="form.password_confirmation" type="password" required />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel for="role" value="Role *" />
                    <Select v-model="form.role" required>
                        <SelectTrigger>
                            <SelectValue placeholder="Select Role" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="customer">Customer</SelectItem>
                        <SelectItem value="vendor">Vendor</SelectItem>
                        <SelectItem value="admin">Admin</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div>
                    <InputLabel for="status" value="Status *" />
                    <Select v-model="form.status" required>
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
            </div>
            <DialogFooter>
                <Button type="submit">Create User</Button>
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
import SelectContent from "@/Components/ui/select/SelectContent.vue";
import SelectItem from "@/Components/ui/select/SelectItem.vue";
import SelectTrigger from "@/Components/ui/select/SelectTrigger.vue";
import Select from "@/Components/ui/select/Select.vue";
import SelectValue from "@/Components/ui/select/SelectValue.vue";
import Button from "@/Components/ui/button/Button.vue";

const form = ref({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    role: '',
    status: ''
});
const emit = defineEmits(['close']); // Define the 'close' event
const submitForm = () => {
    router.post("/users", form.value, {
        onSuccess: () => {
            form.value = {
                first_name: '',
                last_name: '',
                email: '',
                phone: '',
                password: '',
                password_confirmation: '',
                role: '',
                status: ''
            };
            emit('close');
        },
    });
};
</script>