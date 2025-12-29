<template>
    <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
            <DialogTitle>Edit Vehicle: {{ vehicleData.brand }} {{ vehicleData.model }}</DialogTitle>
            <DialogDescription>
                Make changes to the vehicle's location details here. Click save when you're done.
            </DialogDescription>
        </DialogHeader>
        <form @submit.prevent="submitForm" class="grid grid-cols-2 gap-4 py-4">
            <div class="flex flex-col gap-2">
                <Label for="location">Location</Label>
                <Input id="location" type="text" v-model="form.location" />
                <div v-if="form.errors.location" class="text-red-500 text-sm">{{ form.errors.location }}</div>
            </div>

            <div class="flex flex-col gap-2">
                <Label for="city">City</Label>
                <Input id="city" type="text" v-model="form.city" />
                <div v-if="form.errors.city" class="text-red-500 text-sm">{{ form.errors.city }}</div>
            </div>

            <div class="flex flex-col gap-2">
                <Label for="state">State</Label>
                <Input id="state" type="text" v-model="form.state" />
                <div v-if="form.errors.state" class="text-red-500 text-sm">{{ form.errors.state }}</div>
            </div>

            <div class="flex flex-col gap-2">
                <Label for="country">Country</Label>
                <Input id="country" type="text" v-model="form.country" />
                <div v-if="form.errors.country" class="text-red-500 text-sm">{{ form.errors.country }}</div>
            </div>

            <div class="flex flex-col gap-2">
                <Label for="price_per_day">Price Per Day</Label>
                <Input id="price_per_day" type="number" step="0.01" v-model="form.price_per_day" />
                <div v-if="form.errors.price_per_day" class="text-red-500 text-sm">{{ form.errors.price_per_day }}</div>
            </div>

            <div class="flex flex-col gap-2">
                <Label for="price_per_week">Price Per Week</Label>
                <Input id="price_per_week" type="number" step="0.01" v-model="form.price_per_week" />
                <div v-if="form.errors.price_per_week" class="text-red-500 text-sm">{{ form.errors.price_per_week }}</div>
            </div>

            <div class="flex flex-col gap-2">
                <Label for="price_per_month">Price Per Month</Label>
                <Input id="price_per_month" type="number" step="0.01" v-model="form.price_per_month" />
                <div v-if="form.errors.price_per_month" class="text-red-500 text-sm">{{ form.errors.price_per_month }}</div>
            </div>

            <div class="flex flex-col gap-2">
                <Label for="preferred_price_type">Preferred Price Type</Label>
                <select id="preferred_price_type" v-model="form.preferred_price_type" class="border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="day">Day</option>
                    <option value="week">Week</option>
                    <option value="month">Month</option>
                </select>
                <div v-if="form.errors.preferred_price_type" class="text-red-500 text-sm">{{ form.errors.preferred_price_type }}</div>
            </div>
            
            <!-- Add other editable fields here as needed -->
            <DialogFooter class="col-span-2 flex justify-end gap-2 mt-4">
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
    location: '',
    city: '',
    state: '',
    country: '',
    price_per_day: 0,
    price_per_week: 0,
    price_per_month: 0,
    preferred_price_type: 'day',
});

// Watch for changes in the vehicle prop to re-initialize the form
// This is important when the dialog is reused for different vehicles.
watch(() => props.vehicle, (newVehicle) => {
    if (newVehicle) {
        vehicleData.value = newVehicle; // Update local ref
        form.location = newVehicle.location || '';
        form.city = newVehicle.city || '';
        form.state = newVehicle.state || '';
        form.country = newVehicle.country || '';
        form.price_per_day = newVehicle.price_per_day || 0;
        form.price_per_week = newVehicle.price_per_week || 0;
        form.price_per_month = newVehicle.price_per_month || 0;
        form.preferred_price_type = newVehicle.preferred_price_type || 'day';
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
