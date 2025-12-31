<template>
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Create New Plan</DialogTitle>
        </DialogHeader>
        <form @submit.prevent="submitForm" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel for="plan_type" value="Plan Type *" />
                    <Input v-model="form.plan_type" placeholder="e.g., Basic, Premium, Enterprise" required />
                </div>
                <div>
                    <InputLabel for="plan_value" value="Plan Value *" />
                    <Input v-model="form.plan_value" type="number" step="0.01" placeholder="0.00" required />
                </div>
            </div>
            <div>
                <InputLabel for="plan_description" value="Plan Description *" />
                <Textarea v-model="form.plan_description" placeholder="Describe this plan..." rows="3" required />
            </div>
            <div>
                <InputLabel value="Plan Features *" />
                <div v-for="(feature, index) in form.features" :key="index" class="flex items-center gap-2 mb-2">
                    <Input v-model="form.features[index]" placeholder="Enter feature" />
                    <Button type="button" variant="destructive" size="sm" @click="removeFeature(index)">
                        Remove
                    </Button>
                </div>
                <Button type="button" variant="outline" @click="addFeature" class="w-full">
                    <Plus class="w-4 h-4 mr-2" />
                    Add Feature
                </Button>
            </div>
            <DialogFooter>
                <Button type="submit" :disabled="isSubmitting">
                    <span v-if="isSubmitting" class="flex items-center gap-2">
                        <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                        Creating...
                    </span>
                    <span v-else>Create Plan</span>
                </Button>
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
import { Textarea } from "@/Components/ui/textarea";
import { Plus } from 'lucide-vue-next';
import { toast } from "vue-sonner";

const form = ref({
    plan_type: '',
    plan_description: '',
    plan_value: null,
    features: [''],
});
const emit = defineEmits(['close']);
const isSubmitting = ref(false);

const addFeature = () => {
    form.value.features.push('');
};

const removeFeature = (index) => {
    if (form.value.features.length > 1) {
        form.value.features.splice(index, 1);
    } else {
        toast.error('At least one feature is required');
    }
};

const submitForm = () => {
    // Filter out empty features
    const validFeatures = form.value.features.filter(f => f.trim() !== '');

    if (validFeatures.length === 0) {
        toast.error('At least one feature is required');
        return;
    }

    isSubmitting.value = true;

    const formData = {
        ...form.value,
        features: validFeatures,
    };

    router.post("/admin/plans", formData, {
        onSuccess: () => {
            toast.success('Plan created successfully');
            emit('close'); // Close immediately on success
            form.value = {
                plan_type: '',
                plan_description: '',
                plan_value: null,
                features: ['']
            };
        },
        onError: (errors) => {
            toast.error('Failed to create plan. Please try again.');
            isSubmitting.value = false;
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};
</script>
