
<template>
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Edit Plan</DialogTitle>
        </DialogHeader>
        <form @submit.prevent="updatePlan" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel for="plan_type" value="Plan Type *" />
                    <Input v-model="editForm.plan_type" required />
                </div>
                <div>
                    <InputLabel for="plan_description" value="Plan Description *" />
                    <Textarea v-model="editForm.plan_description" required />
                </div>
                <div>
                    <InputLabel for="plan_value" value="Plan Value *" />
                    <Input v-model="editForm.plan_value" type="number" step="0.01" required />
                </div>
            </div>
            <div>
                <InputLabel value="Plan Features" />
                <div v-for="(feature, index) in editForm.features" :key="index" class="flex items-center gap-2 mb-2">
                    <Input v-model="editForm.features[index]" placeholder="Enter feature" />
                    <Button type="button" variant="destructive" size="sm" @click="removeEditFeature(index)">
                        Remove
                    </Button>
                </div>
                <Button type="button" variant="outline" @click="addEditFeature">
                    Add Feature
                </Button>
            </div>
            <DialogFooter>
                <Button type="submit">Update Addon</Button>
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
import { Textarea } from "@/Components/ui/textarea";

const toast = useToast();

const props = defineProps({
    plan: Object,
});

const emit = defineEmits(['close']); // Define the 'close' event
const editForm = ref({ ...props.plan });
const addEditFeature = () => {
    editForm.value.features.push('');
};

const removeEditFeature = (index) => {
    editForm.value.features.splice(index, 1);
};
// Watch for changes in props.plan (if the plan data is updated dynamically)
watch(() => props.plan, (newPlan) => {
    editForm.value = { ...newPlan };
}, { immediate: true });

const updatePlan = () => {
    router.put(`/admin/plans/${editForm.value.id}`, editForm.value, {
        onSuccess: () => {
            emit('close');
            toast.success('Plan updated successfully!', {
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
