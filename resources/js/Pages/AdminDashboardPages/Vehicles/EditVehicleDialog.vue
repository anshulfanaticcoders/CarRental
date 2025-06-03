<template>
    <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
            <DialogTitle>Edit Vehicle: {{ vehicleData.brand }} {{ vehicleData.model }}</DialogTitle>
            <DialogDescription>
                Make changes to the vehicle's location details here. Click save when you're done.
            </DialogDescription>
        </DialogHeader>
        <form @submit.prevent="submitForm" class="grid gap-4 py-4">
            <div class="grid grid-cols-4 items-center gap-4">
                <Label for="city" class="text-right">City</Label>
                <Input id="city" type="text" v-model="form.city" class="col-span-3" />
                <div v-if="form.errors.city" class="col-span-4 text-red-500 text-sm text-right">{{ form.errors.city }}</div>
            </div>

            <div class="grid grid-cols-4 items-center gap-4">
                <Label for="state" class="text-right">State</Label>
                <Input id="state" type="text" v-model="form.state" class="col-span-3" />
                <div v-if="form.errors.state" class="col-span-4 text-red-500 text-sm text-right">{{ form.errors.state }}</div>
            </div>

            <div class="grid grid-cols-4 items-center gap-4">
                <Label for="country" class="text-right">Country</Label>
                <Input id="country" type="text" v-model="form.country" class="col-span-3" />
                <div v-if="form.errors.country" class="col-span-4 text-red-500 text-sm text-right">{{ form.errors.country }}</div>
            </div>
            
            <!-- Add other editable fields here as needed -->
            <DialogFooter>
                <Button type="button" variant="outline" @click="$emit('close')">
                    Cancel
                </Button>
                <Button type="submit" :disabled="form.processing">
                    Save Changes
                </Button>
            </DialogFooter>
        </form>
    </DialogContent>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Input from '@/Components/ui/input/Input.vue';
import Button from '@/Components/ui/button/Button.vue';
import {
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import Label from '@/Components/ui/label/Label.vue';

const props = defineProps({
    vehicle: Object,
    show: Boolean, // To control dialog visibility via v-model:open in parent
});

const emit = defineEmits(['close']);

// Create a local ref for vehicle data to avoid directly mutating props
// and to properly initialize the form when the vehicle prop changes.
const vehicleData = ref(props.vehicle);

const form = useForm({
    city: '',
    state: '',
    country: '',
});

// Watch for changes in the vehicle prop to re-initialize the form
// This is important when the dialog is reused for different vehicles.
watch(() => props.vehicle, (newVehicle) => {
    if (newVehicle) {
        vehicleData.value = newVehicle; // Update local ref
        form.city = newVehicle.city || '';
        form.state = newVehicle.state || '';
        form.country = newVehicle.country || '';
        form.clearErrors(); // Clear previous errors
    }
}, { immediate: true, deep: true });


const submitForm = () => {
    if (!vehicleData.value || !vehicleData.value.id) {
        console.error('Vehicle data or ID is missing.');
        return;
    }
    form.put(route('admin.vehicles.update', { vendor_vehicle: vehicleData.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close'); // Close the dialog on success
            // Optionally, show a success message or trigger a refresh
        },
        onError: (errors) => {
            console.error('Error updating vehicle:', errors);
        }
    });
};
</script>
