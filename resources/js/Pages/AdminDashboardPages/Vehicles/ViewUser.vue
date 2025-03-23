<template>
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Vehicle Details</DialogTitle>
        </DialogHeader>
        <div class="space-y-4">
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
                <InputLabel for="model" value="Model" />
                <Input v-model="user.model" readonly class="bg-gray-200 cursor-not-allowed" />
            </div>
            <div>
                <InputLabel for="brand" value="Brand" />
                <Input v-model="user.brand" readonly class="bg-gray-200 cursor-not-allowed" />
            </div>
            <div>
                <InputLabel for="seating_capacity" value="Seating Capacity" />
                <Input v-model="user.seating_capacity" readonly class="bg-gray-200 cursor-not-allowed" />
            </div>
            <div>
                <InputLabel for="color" value="Color" />
                <Input v-model="user.color" readonly class="bg-gray-200 cursor-not-allowed" />
            </div>
            <div>
                <InputLabel for="location" value="Location" />
                <Input v-model="user.location" readonly class="bg-gray-200 cursor-not-allowed" />
            </div>
            <div v-if="user.price_per_day || user.price_per_week || user.price_per_month">
                <div v-if="user.price_per_day">
                    <InputLabel for="price_per_day" value="Price per day" />
                    <Input id="price_per_day" v-model="user.price_per_day" readonly
                        class="bg-gray-200 cursor-not-allowed text-gray-500" />
                </div>

                <div v-if="user.price_per_week">
                    <InputLabel for="price_per_week" value="Price per week" />
                    <Input id="price_per_week" v-model="user.price_per_week" readonly
                        class="bg-gray-200 cursor-not-allowed text-gray-500" />
                </div>

                <div v-if="user.price_per_month">
                    <InputLabel for="price_per_month" value="Price per month" />
                    <Input id="price_per_month" v-model="user.price_per_month" readonly
                        class="bg-gray-200 cursor-not-allowed text-gray-500" />
                </div>
            </div>
            <span v-else class="text-gray-500">No pricing available</span>

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