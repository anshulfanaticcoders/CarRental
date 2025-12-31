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
                    <InputLabel for="plan_value" value="Plan Value *" />
                    <Input v-model="editForm.plan_value" type="number" step="0.01" required />
                </div>
            </div>
            <div>
                <InputLabel for="plan_description" value="Plan Description *" />
                <Textarea v-model="editForm.plan_description" placeholder="Describe this plan..." rows="3" required />
            </div>
            <div>
                <InputLabel value="Plan Features *" />
                <div v-for="(feature, index) in editForm.features" :key="index" class="flex items-center gap-2 mb-2">
                    <Input v-model="editForm.features[index]" placeholder="Enter feature" />
                    <Button type="button" variant="destructive" size="sm" @click="removeEditFeature(index)">
                        Remove
                    </Button>
                </div>
                <Button type="button" variant="outline" @click="addEditFeature" class="w-full">
                    <Plus class="w-4 h-4 mr-2" />
                    Add Feature
                </Button>
            </div>
            <DialogFooter>
                <Button type="submit" :disabled="isSubmitting">
                    <span v-if="isSubmitting" class="flex items-center gap-2">
                        <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                        Updating...
                    </span>
                    <span v-else>Update Plan</span>
                </Button>
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
import { Textarea } from "@/Components/ui/textarea";
import { Plus } from 'lucide-vue-next';
import { toast } from "vue-sonner";

const props = defineProps({
    plan: Object,
});

const emit = defineEmits(['close']);
const editForm = ref({ ...props.plan });
const isSubmitting = ref(false);

const addEditFeature = () => {
    editForm.value.features.push('');
};

const removeEditFeature = (index) => {
    if (editForm.value.features.length > 1) {
        editForm.value.features.splice(index, 1);
    } else {
        toast.error('At least one feature is required');
    }
};

// Watch for changes in props.plan
watch(() => props.plan, (newPlan) => {
    if (newPlan) {
        editForm.value = { ...newPlan };
    }
}, { immediate: true });

const updatePlan = () => {
    // Filter out empty features
    const validFeatures = editForm.value.features.filter(f => f.trim() !== '');

    if (validFeatures.length === 0) {
        toast.error('At least one feature is required');
        return;
    }

    isSubmitting.value = true;

    const formData = {
        ...editForm.value,
        features: validFeatures,
    };

    router.put(`/admin/plans/${editForm.value.id}`, formData, {
        onSuccess: () => {
            toast.success('Plan updated successfully');
        },
        onError: (errors) => {
            toast.error('Failed to update plan. Please try again.');
        },
        onFinish: () => {
            isSubmitting.value = false;
            emit('close');
        },
    });
};
</script>
