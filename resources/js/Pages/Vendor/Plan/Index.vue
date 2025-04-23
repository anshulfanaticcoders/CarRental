<template>
    <MyProfileLayout>
        <div>
            <div class="mb-6">
                <div class="flex justify-between bg-customLightPrimaryColor p-4 rounded-[12px] mb-[1rem]">
                    <p class="text-[1.75rem] font-bold text-gray-800">
                        Plans
                    </p>
                    <!-- Create Plan Dialog -->
                    <Dialog v-model:open="isCreateDialogOpen">
                        <DialogTrigger as-child>
                            <Button>Create Plan</Button>
                        </DialogTrigger>
                        <DialogContent class="max-h-[90vh] overflow-y-auto p-4">
                            <DialogHeader>
                                <DialogTitle>Create Plan</DialogTitle>
                                <DialogDescription>Enter your plan details below.</DialogDescription>
                            </DialogHeader>
                            <form @submit.prevent="createPlan">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700">Vehicle</label>
                                    <select v-model="newPlan.vehicle_id" class="w-full border rounded p-2">
                                        <option v-for="vehicle in vehicles" :key="vehicle.id" :value="vehicle.id">
                                            {{ vehicle.brand }}
                                        </option>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700">Plan Type</label>
                                    <input type="text" v-model="newPlan.plan_type" class="w-full border rounded p-2" />
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700">Price</label>
                                    <input type="number" v-model="newPlan.price" class="w-full border rounded p-2" />
                                    <div v-if="newPlan.errors.price" class="text-red-500 text-sm mt-1">
                                        {{ newPlan.errors.price }}
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700">Description</label>
                                    <textarea v-model="newPlan.plan_description"
                                        class="w-full border rounded p-2"></textarea>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700">Features</label>
                                    <div v-for="(feature, index) in newPlan.features" :key="index" class="flex mb-2">
                                        <input type="text" v-model="newPlan.features[index]"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                            placeholder="Feature description">
                                        <button type="button" @click="removeNewFeature(index)"
                                            class="ml-2 inline-flex items-center p-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                    <button type="button" @click="addNewFeature"
                                        class="mt-2 inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Add Feature
                                    </button>
                                </div>
                                <DialogFooter>
                                    <Button type="submit">Save</Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>

                </div>
                <p class="text-gray-600">View and Edit your plans, it will reflect to customers during the booking
                    process.</p>
            </div>

            <div class="mb-4">
                <input type="text" v-model="searchQuery" placeholder="Search plans..."
                    class="px-4 py-2 border border-gray-300 rounded-md w-full" />
            </div>

            <div class="bg-white shadow-md rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Vehicle</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Plan Type</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Price</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Plan Description</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Plan Features</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Created</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Updated</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="(plan, index) in filteredPlans" :key="plan.id">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ (plans.current_page - 1) *
                                plans.per_page + index + 1 }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                
                                {{ plan.vehicle?.brand }} {{ plan.vehicle?.model }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ plan.plan_type }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="font-medium">{{ plan.price }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-wrap text-sm text-gray-500">{{ plan.plan_description }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <ul v-if="isValidJSON(plan.features)">
                                    <li v-for="(feature, index) in JSON.parse(plan.features)" :key="index"
                                        class="list-disc">{{ feature }}
                                    </li>
                                </ul>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(plan.created_at)
                                }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(plan.updated_at)
                                }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <Dialog v-model:open="isDialogOpen">
                                    <DialogTrigger as-child>
                                        <Button @click="openEditDialog(plan)">Edit</Button>
                                    </DialogTrigger>
                                    <DialogContent class="max-h-[90vh] overflow-y-auto p-4">
                                        <DialogHeader>
                                            <DialogTitle>Edit Plan</DialogTitle>
                                            <DialogDescription>Update your plan details below.</DialogDescription>
                                        </DialogHeader>
                                        <form @submit.prevent="updatePlan">
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700">Plan Type</label>
                                                <input type="text" v-model="form.plan_type"
                                                    class="w-full border rounded p-2" />
                                            </div>
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700">Price</label>
                                                <input type="number" v-model="form.price"
                                                    class="w-full border rounded p-2" />
                                                <div v-if="form.errors.price" class="text-red-500 text-sm mt-1">
                                                    {{ form.errors.price }}
                                                </div>
                                            </div>
                                            <div class="mb-4">
                                                <label
                                                    class="block text-sm font-medium text-gray-700">Description</label>
                                                <textarea v-model="form.plan_description"
                                                    class="w-full border rounded p-2"></textarea>
                                            </div>
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700">Features</label>
                                                <div v-for="(feature, index) in form.features" :key="index"
                                                    class="flex mb-2">
                                                    <input type="text" v-model="form.features[index]"
                                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                        placeholder="Feature description">
                                                    <button type="button" @click="removeFeature(index)"
                                                        class="ml-2 inline-flex items-center p-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>
                                                <button type="button" @click="addFeature"
                                                    class="mt-2 inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                    Add Feature
                                                </button>
                                            </div>
                                            <DialogFooter>
                                                <Button type="submit">Save</Button>
                                            </DialogFooter>
                                        </form>
                                    </DialogContent>
                                </Dialog>

                                <Button @click="deletePlan(plan.id)"
                                    class="ml-2 bg-red-600 hover:bg-red-700">Delete</Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-[1rem] flex justify-end">
            <Pagination :current-page="plans.current_page" :total-pages="plans.last_page"
                @page-change="handlePageChange" />
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
    vehicles: Array,
    errors: Object,
    filters: {
        type: Object,
        default: () => ({})
    }
});

// State for dialog open status
const isDialogOpen = ref(false);
const isCreateDialogOpen = ref(false);
const toast = useToast();
// Form for updating plan
const form = useForm({
    id: '',
    plan_type: '',
    price: '',
    plan_description: '',
    features: []
});

// Form for creating new plan
const newPlan = useForm({
    vehicle_id: '',
    plan_type: '',
    price: '',
    plan_description: '',
    features: []
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
    form.plan_description = plan.plan_description;
    form.features = isValidJSON(plan.features) ? JSON.parse(plan.features) : [];
    isDialogOpen.value = true;
};

// Open the create dialog
const openCreateDialog = () => {
    newPlan.reset();
    isCreateDialogOpen.value = true;
};

// Add a new empty feature
function addFeature() {
    form.features.push('');
}

// Add a new empty feature for new plan
function addNewFeature() {
    newPlan.features.push('');
}

// Remove a feature by index
function removeFeature(index) {
    form.features.splice(index, 1);
}

// Remove a feature by index for new plan
function removeNewFeature(index) {
    newPlan.features.splice(index, 1);
}

// Function to update plan
const updatePlan = () => {
    form.put(route('VendorPlanUpdate', form.id), {
        preserveState: true,
        onSuccess: () => {
            toast.success('Plan updated successfully!', {
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

// Function to create a new plan
const createPlan = () => {
    newPlan.post(route('VendorPlanStore'), {
        preserveState: true,
        onSuccess: () => {
            toast.success('Plan created successfully!', {
                position: 'top-right',
                timeout: 1000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
            // Close the dialog
            isCreateDialogOpen.value = false;

            // Reset form after successful creation
            newPlan.reset();
        }
    });
};


// Function to delete a plan
const deletePlan = (id) => {
    if (confirm('Are you sure you want to delete this plan?')) {
        router.delete(route('VendorPlanDestroy', id), {
            onSuccess: () => {
                toast.success('Plan deleted successfully!', {
                    position: 'top-right',
                    timeout: 1000,
                    closeOnClick: true,
                    pauseOnHover: true,
                    draggable: true,
                });
            }
        });
    }
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

// Function to validate if a string is a valid JSON
const isValidJSON = (str) => {
    try {
        JSON.parse(str);
        return true;
    } catch (e) {
        return false;
    }
};
</script>

<style scoped>
tr:hover {
    background: rgba(233, 233, 233, 0.119);
}
</style>