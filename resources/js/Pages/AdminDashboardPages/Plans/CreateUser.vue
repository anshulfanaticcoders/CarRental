<template>
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Create New Plan</DialogTitle>
        </DialogHeader>
        <form @submit.prevent="submitForm" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel for="plan_type" value="Plan Type *" />
                    <Input v-model="form.plan_type" required />
                </div>
                <div>
                    <InputLabel for="plan_value" value="Plan Value *" />
                    <Input v-model="form.plan_value" type="number" step="0.01" required />
                </div>
            </div>
            <div>
                <InputLabel value="Plan Features" />
                <div v-for="(feature, index) in form.features" :key="index" class="flex items-center gap-2 mb-2">
                    <Input v-model="form.features[index]" placeholder="Enter feature" />
                    <Button type="button" variant="destructive" size="sm" @click="removeFeature(index)">
                        Remove
                    </Button>
                </div>
                <Button type="button" variant="outline" @click="addFeature">
                    Add Feature
                </Button>
            </div>
            <DialogFooter>
                <Button type="submit">Create Addon</Button>
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
    plan_type: '',
    plan_value: null,
    features: []
});
const emit = defineEmits(['close']); // Define the 'close' event

const addFeature = () => {
    form.value.features.push('');
};

const removeFeature = (index) => {
    form.value.features.splice(index, 1);
};
const submitForm = () => {
    router.post("/plans", form.value, {
        onSuccess: () => {
            form.value = {
                plan_type: '',
                plan_value: null,
                features: []
            };
            emit('close');
            toast.success('Plan added successfully!', {
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