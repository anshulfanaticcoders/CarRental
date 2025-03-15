<template>
    <MyProfileLayout>
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-semibold text-xl text-gray-800">Manage Blocking Dates</h2>
            <Dialog>
                <DialogTrigger class="px-4 py-2 bg-customPrimaryColor text-white rounded hover:bg-[#153b4fdc]">
                    Add Blocking Date
                </DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Add Blocking Date</DialogTitle>
                        <DialogDescription>Select a vehicle and set the blocking dates.</DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="submitForm">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Vehicle</label>
                            <select v-model="form.vehicle_id" class="w-full border rounded p-2">
                                <option v-for="vehicle in vehicles" :key="vehicle.id" :value="vehicle.id">
                                    {{ vehicle.brand }} - {{ vehicle.model }}
                                </option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Start Date</label>
                            <input type="date" v-model="form.blocking_start_date" class="w-full border rounded p-2" />
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">End Date</label>
                            <input type="date" v-model="form.blocking_end_date" class="w-full border rounded p-2" />
                        </div>
                        <DialogFooter>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                Save
                            </button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <!-- Search Bar -->
        <div class="mb-4">
            <input type="text" v-model="searchQuery" placeholder="Search vehicles..." class="px-4 py-2 border border-gray-300 rounded-md w-full" />
        </div>
        
        <div v-if="filteredVehicles.length" class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-4 py-2 text-left text-sm font-bold">Brand</th>
                        <th class="px-4 py-2 text-left text-sm font-bold">Model</th>
                        <th class="px-4 py-2 text-left text-sm font-bold">Blocking Start Date</th>
                        <th class="px-4 py-2 text-left text-sm font-bold">Blocking End Date</th>
                        <th class="px-4 py-2 text-left text-sm font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(vehicle,index) in filteredVehicles" :key="vehicle.id" class="border-b hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ vehicle.brand }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ vehicle.model }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ vehicle.blocking_start_date || 'N/A' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ vehicle.blocking_end_date || 'N/A' }}</td>
                        <td class="px-4 py-2 text-sm">
                            <Dialog>
                                <DialogTrigger class="text-blue-600 hover:underline">Edit</DialogTrigger>
                                <DialogContent>
                                    <DialogHeader>
                                        <DialogTitle>Edit Blocking Date</DialogTitle>
                                        <DialogDescription>Modify the blocking dates for this vehicle.</DialogDescription>
                                    </DialogHeader>
                                    <form @submit.prevent="updateBlockingDate(vehicle.id)">
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700">Start Date</label>
                                            <input type="date" v-model="vehicle.blocking_start_date" class="w-full border rounded p-2" />
                                        </div>
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700">End Date</label>
                                            <input type="date" v-model="vehicle.blocking_end_date" class="w-full border rounded p-2" />
                                        </div>
                                        <DialogFooter>
                                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                                Save Changes
                                            </button>
                                        </DialogFooter>
                                    </form>
                                </DialogContent>
                            </Dialog>
                            <button @click="removeBlockingDates(vehicle.id)" class="text-red-600 ml-2 hover:underline">
                                Remove
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-else class="text-center py-6">
            <span class="text-gray-500">No vehicles found.</span>
        </div>

        <!-- Pagination -->
        <div class="mt-[1rem] flex justify-end">
    <Pagination :current-page="pagination.current_page" :total-pages="pagination.last_page"
        @page-change="handlePageChange" />
</div>
    </MyProfileLayout>
</template>

<script setup>
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { ref, computed, watch } from 'vue';
import { usePage, router  } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import axios from 'axios';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/Components/ui/dialog';
import Pagination from './Pagination.vue';

const toast = useToast();
// const { props } = usePage();
// const vehicles = ref(props.vehicles);
const form = ref({ vehicle_id: '', blocking_start_date: '', blocking_end_date: '' });
const searchQuery = ref('');

const props = defineProps({
    vehicles: {
        type: Array,
        filters: Object,
        required: true
    },
    pagination: { 
        type: Object,
        required: true
    }
});
const handlePageChange = (page) => {
    router.get(route('vendor.blocking-dates.index'), { ...props.filters, page }, { preserveState: true, preserveScroll: true });
};

const submitForm = async () => {
    try {
        await axios.post(route('vendor.blocking-dates.store'), form.value);
        toast.success('Blocking date added successfully!', {
                position: 'top-right',
                timeout: 1000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    } catch (error) {
        toast.error('Failed to add blocking date. Please try again.');
    }
};

const updateBlockingDate = async (vehicleId) => {
    try {
        await axios.put(route('vendor.blocking-dates.update', vehicleId), {
            blocking_start_date: vehicles.value.find(v => v.id === vehicleId).blocking_start_date,
            blocking_end_date: vehicles.value.find(v => v.id === vehicleId).blocking_end_date,
        });
        toast.success('Blocking date updated successfully!', {
                position: 'top-right',
                timeout: 1000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    } catch (error) {
        toast.error('Failed to update blocking date. Please try again.');
    }
};

const removeBlockingDates = async (vehicleId) => {
    if (confirm('Are you sure you want to remove blocking dates for this vehicle?')) {
        try {
            await axios.delete(route('vendor.blocking-dates.destroy', vehicleId));
            toast.success('Blocking date removed successfully!', {
                position: 'top-right',
                timeout: 1000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } catch (error) {
            toast.error('Failed to remove blocking dates. Please try again.');
        }
    }
};

const filteredVehicles = computed(() => {
    const query = searchQuery.value.toLowerCase();
    return props.vehicles.filter(vehicle => {
        return (
            vehicle.brand.toLowerCase().includes(query) ||
            vehicle.model.toLowerCase().includes(query) ||
            (vehicle.blocking_start_date || 'N/A').toLowerCase().includes(query) ||
            (vehicle.blocking_end_date || 'N/A').toLowerCase().includes(query)
        );
    });
});

watch(searchQuery, (newQuery) => {
  router.get(
    route('vendor.blocking-dates.index'),
    { search: newQuery },
    { preserveState: true, preserveScroll: true }
  );
});
</script>

<style scoped>
select {
    width: 100%;
}

label {
    margin-bottom: 0.5rem;
}

@media screen and (max-width:480px) {
    
    th{
        font-size: 0.75rem;
    }
   
}
</style>