<template>
    <MyProfileLayout>
        <div>
            <div class="mb-6">
                <p class="text-[1.75rem] font-bold text-gray-800 bg-customLightPrimaryColor p-4 rounded-[12px] mb-[1rem]">
                    Plans
                </p>
                <p class="text-gray-600">View and Edit your plans, it will reflect to customers during the booking process.</p>
            </div>

            <div class="mb-4">
                <input type="text" v-model="searchQuery" placeholder="Search plans..." class="px-4 py-2 border border-gray-300 rounded-md w-full" />
            </div>

            <div class="bg-white shadow-md rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan Type</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Updated</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="(plan, index) in filteredPlans" :key="plan.id">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ (plans.current_page - 1) * plans.per_page + index + 1 }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ plan.vehicle?.brand }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ plan.plan_type }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="font-medium">{{ plan.price }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(plan.created_at) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(plan.updated_at) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <Dialog v-model:open="isDialogOpen">
                                    <DialogTrigger as-child>
                                        <Button @click="openEditDialog(plan)">Edit</Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>Edit Plan</DialogTitle>
                                            <DialogDescription>Update your plan details below.</DialogDescription>
                                        </DialogHeader>
                                        <form @submit.prevent="updatePlan">
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700">Plan Type</label>
                                                <input type="text" v-model="form.plan_type" class="w-full border rounded p-2" />
                                            </div>
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700">Price</label>
                                                <input type="number" v-model="form.price" class="w-full border rounded p-2" />
                                                <div v-if="form.errors.price" class="text-red-500 text-sm mt-1">
                                                    {{ form.errors.price }}
                                                </div>
                                            </div>
                                            <DialogFooter>
                                                <Button type="submit">Save</Button>
                                            </DialogFooter>
                                        </form>
                                    </DialogContent>
                                </Dialog>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-[1rem] flex justify-end">
            <Pagination 
                :current-page="plans.current_page" 
                :total-pages="plans.last_page"
                @page-change="handlePageChange" 
            />
        </div>
    </MyProfileLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/Components/ui/dialog';
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import Pagination from './Pagination.vue';
import { useToast } from 'vue-toastification';

// Define props
const props = defineProps({
    plans: Object,
    vehicle: Object,
    errors: Object,
    filters: {
        type: Object,
        default: () => ({})
    }
});

// State for dialog open status
const isDialogOpen = ref(false);
const toast = useToast();
// Form for updating plan
const form = useForm({
    id: '',
    plan_type: '',
    price: ''
});

// State for search query
const searchQuery = ref('');
watch(searchQuery, (newQuery) => {
  router.get(
    route('VendorPlanIndex'),
    { search: newQuery },
    { preserveState: true, preserveScroll: true }
  );
});

// Open the edit dialog with the selected plan data
const openEditDialog = (plan) => {
    form.id = plan.id;
    form.plan_type = plan.plan_type;
    form.price = plan.price;
    isDialogOpen.value = true;
};

// Function to update plan
const updatePlan = () => {
    form.put(route('VendorPlanUpdate', form.id), {
        preserveState: true,
        onSuccess: () => {
            toast.success('Plan Price added successfully!', {
                position: 'top-right',
                timeout: 1000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
            // Close the dialog
            isDialogOpen.value = false;
            
            // Reset form after successful update
            form.reset();
        }
    });
};

// Date formatting function
const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Handle pagination
const handlePageChange = (page) => {
    router.get(route('VendorPlanIndex'), { 
        ...props.filters, 
        page 
    }, { 
        preserveState: true, 
        preserveScroll: true 
    });
};

// Computed property for filtered plans
const filteredPlans = computed(() => {
    const query = searchQuery.value.toLowerCase();
    return props.plans.data.filter(plan => {
        return (
            plan.vehicle?.brand.toLowerCase().includes(query) ||
            plan.plan_type.toLowerCase().includes(query) ||
            plan.price.toString().includes(query)
        );
    });
});
</script>

<style scoped>
tr:hover {
    background: rgba(233, 233, 233, 0.119);
}
</style>