<template>
    <DialogContent>
        <DialogHeader>
            <DialogTitle>User Details</DialogTitle>
        </DialogHeader>
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel for="extra_type" value="Extra Type" />
                    <Input v-model="user.extra_type" readonly class="bg-gray-200 cursor-not-allowed" />
                </div>
                <div>
                    <InputLabel for="extra_name" value="Extra Name" />
                    <Input v-model="user.extra_name" readonly class="bg-gray-200 cursor-not-allowed" />
                </div>
            </div>
            <div>
                <InputLabel for="quantity" value="Quantity" />
                <Input v-model="user.quantity" readonly class="bg-gray-200 cursor-not-allowed" />
            </div>
            <div>
                <InputLabel for="price" value="Price" />
                <Input v-model="user.price" readonly class="bg-gray-200 cursor-not-allowed" />
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