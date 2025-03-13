<template>
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Plan Details</DialogTitle>
        </DialogHeader>
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel for="plan_type" value="Plan Type" />
                    <Input v-model="plan.plan_type" readonly class="bg-gray-200 cursor-not-allowed" />
                </div>
                <div>
                    <InputLabel for="plan_description" value="Plan Description" />
                    <Input v-model="plan.plan_description" readonly class="bg-gray-200 cursor-not-allowed" />
                </div>
                <div>
                    <InputLabel for="plan_value" value="Plan Value" />
                    <Input v-model="plan.plan_value" readonly class="bg-gray-200 cursor-not-allowed" />
                </div>
            </div>
            <div>
                <InputLabel value="Plan Features" />
                <div v-for="(feature, index) in plan.features" :key="index" class="flex items-center gap-2 mb-2">
                    <Input v-model="plan.features[index]" placeholder="Enter feature" readonly class="bg-gray-200 cursor-not-allowed" />
                   
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
    plan: Object,
});

</script>