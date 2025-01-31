<template>
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Booking Details</DialogTitle>
        </DialogHeader>
        <div class="max-h-[600px] overflow-y-auto p-4">
        <div class="space-y-4 " >
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel for="booking_name" value="Booking Number" />
                    <Input v-model="user.booking_number" readonly class="bg-gray-200 cursor-not-allowed" />
                </div>
                <div>
                    <InputLabel for="booking_name" value="Booking Number" />
                    <Input v-model="user.booking_number" readonly class="bg-gray-200 cursor-not-allowed" />
                </div>
                <div>
                    <InputLabel for="plan" value="Plan" />
                    <Input v-model="user.plan" readonly class="bg-gray-200 cursor-not-allowed" />
                </div>
            </div>
            <div>
                <InputLabel for="pickup_date" value="Pickup date" />
                <Input v-model="user.pickup_date" readonly class="bg-gray-200 cursor-not-allowed" />
            </div>
            <div>
                <InputLabel for="return_date" value="Return date" />
                <Input v-model="user.return_date" readonly class="bg-gray-200 cursor-not-allowed" />
            </div>
            <div>
                <InputLabel for="pickup_location" value="Pickup location" />
                <Input v-model="user.pickup_location" readonly class="bg-gray-200 cursor-not-allowed" />
            </div>
            <div>
                <InputLabel for="return_location" value="Return location" />
                <Input v-model="user.return_location" readonly class="bg-gray-200 cursor-not-allowed" />
            </div>
            <div>
                <InputLabel for="pickup_time" value="Pickup time" />
                <Input v-model="user.pickup_time" readonly class="bg-gray-200 cursor-not-allowed" />
            </div>
            <div>
                <InputLabel for="return_time" value="Return time" />
                <Input v-model="user.return_time" readonly class="bg-gray-200 cursor-not-allowed" />
            </div>
            <div>
                <InputLabel for="total_days" value="Total days" />
                <Input v-model="user.total_days" readonly class="bg-gray-200 cursor-not-allowed" />
            </div>

            <div>
                <InputLabel for="company_name" value="Company Name" />
                <Input v-model="user.vehicle.vendor_profile_data.company_name" readonly class="bg-gray-200 cursor-not-allowed" />
            </div>
            <div>
                <InputLabel for="company_email" value="Company Email" />
                <Input v-model="user.vehicle.vendor_profile_data.company_email" readonly class="bg-gray-200 cursor-not-allowed" />
            </div>

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