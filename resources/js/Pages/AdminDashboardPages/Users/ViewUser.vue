<template>
    <DialogContent>
        <DialogHeader>
            <DialogTitle>User Details</DialogTitle>
        </DialogHeader>
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel for="first_name" value="First Name" />
                    <Input v-model="user.first_name" readonly class="bg-gray-200 cursor-not-allowed" />
                </div>
                <div>
                    <InputLabel for="last_name" value="Last Name" />
                    <Input v-model="user.last_name" readonly class="bg-gray-200 cursor-not-allowed" />
                </div>
            </div>
            <div>
                <InputLabel for="email" value="Email" />
                <Input v-model="user.email" readonly class="bg-gray-200 cursor-not-allowed" />
            </div>
            <div>
                <InputLabel for="phone" value="Phone" />
                <Input v-model="user.phone" readonly class="bg-gray-200 cursor-not-allowed" />
            </div>
            <div>
                <InputLabel for="role" value="Role" />
                <Input v-model="user.role" readonly class="bg-gray-200 cursor-not-allowed" />
            </div>
            <div>
                <InputLabel for="status" value="Status" />
                <Input v-model="user.status" readonly class="bg-gray-200 cursor-not-allowed" />
            </div>
        </div>
    </DialogContent>
</template>

<script setup>
import { DialogContent, DialogHeader, DialogTitle } from "@/Components/ui/dialog";
import Input from "@/Components/ui/input/Input.vue";
import InputLabel from "@/Components/InputLabel.vue";

const props = defineProps({
    user: Object,
});
</script>