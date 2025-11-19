<template>
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Vendor Details</DialogTitle>
        </DialogHeader>
        <div class="space-y-4 " >
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel for="first_name" value="First Name" />
                    <Input v-model="user.user.first_name" readonly class="bg-gray-200 cursor-not-allowed" />
                </div>
                <div>
                    <InputLabel for="last_name" value="Last Name" />
                    <Input v-model="user.user.last_name" readonly class="bg-gray-200 cursor-not-allowed" />
                </div>
            </div>
            <div>
                <InputLabel for="company_name" value="Company Name" />
                <Input v-model="user.company_name" readonly class="bg-gray-200 cursor-not-allowed" />
            </div>
            <div>
                <InputLabel for="company_email" value="Company Email" />
                <Input v-model="user.company_email" readonly class="bg-gray-200 cursor-not-allowed" />
            </div>
            <div>
                <InputLabel for="company_phone_number" value="Company Phone Number" />
                <Input v-model="user.company_phone_number" readonly class="bg-gray-200 cursor-not-allowed" />
            </div>
            <div>
                <InputLabel for="company_address" value="Company Address" />
                <Input v-model="user.company_address" readonly class="bg-gray-200 cursor-not-allowed" />
            </div>
            <div>
                <InputLabel for="company_gst_number" value="Company GST Number" />
                <Input v-model="user.company_gst_number" readonly class="bg-gray-200 cursor-not-allowed" />
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