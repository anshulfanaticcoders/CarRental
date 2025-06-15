<template>
    <MyProfileLayout>
        <div>
            <div class="mb-6">
                <div class="flex justify-between bg-customLightPrimaryColor p-4 rounded-[12px] mb-[1rem]">
                    <p class="text-[1.75rem] font-bold text-gray-800">
                        {{ _t('vendorprofilepages', 'plans_title') }}
                    </p>
                </div>
                <p class="text-gray-600">{{ _t('vendorprofilepages', 'plans_view_edit_plans_info') }}</p>
            </div>

            <div class="mb-4">
                <input type="text" v-model="searchQuery" :placeholder="_t('vendorprofilepages', 'plans_search_placeholder')"
                    class="px-4 py-2 border border-gray-300 rounded-md w-full" />
            </div>

            <div class="bg-white shadow-md rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Sr. No</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ _t('vendorprofilepages', 'plans_table_header_id') }}</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ _t('vendorprofilepages', 'table_image_header') }}</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ _t('vendorprofilepages', 'plans_table_header_vehicle') }}</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ _t('vendorprofilepages', 'plans_table_header_plan_type') }}</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ _t('vendorprofilepages', 'plans_table_header_price') }}</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ _t('vendorprofilepages', 'plans_table_header_plan_description') }}</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ _t('vendorprofilepages', 'plans_table_header_plan_features') }}</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ _t('vendorprofilepages', 'plans_table_header_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="(vehicle, index) in filteredVehicles" :key="vehicle.id">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ (vehicles.current_page - 1) *
                                vehicles.per_page + index + 1 }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ vehicle.id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <Link :href="route('vehicle.show', { locale: usePage().props.locale, id: vehicle.id })">
                                    <img :src="getPrimaryImage(vehicle)" :alt="vehicle.brand" class="h-12 w-24 object-cover rounded">
                                </Link>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ vehicle.brand }} {{ vehicle.model }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <ul v-if="vehicle.vendor_plans && vehicle.vendor_plans.length > 0">
                                    <li v-for="plan in vehicle.vendor_plans" :key="plan.id">
                                        {{ plan.plan_type }}
                                    </li>
                                </ul>
                                <span v-else>N/A</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <ul v-if="vehicle.vendor_plans && vehicle.vendor_plans.length > 0">
                                    <li v-for="plan in vehicle.vendor_plans" :key="plan.id">
                                        {{ plan.price }}
                                    </li>
                                </ul>
                                <span v-else>N/A</span>
                            </td>
                            <td class="px-6 py-4 whitespace-wrap text-sm text-gray-500">
                                <ul v-if="vehicle.vendor_plans && vehicle.vendor_plans.length > 0">
                                    <li v-for="plan in vehicle.vendor_plans" :key="plan.id">
                                        {{ plan.plan_description }}
                                    </li>
                                </ul>
                                <span v-else>N/A</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <ul v-if="vehicle.vendor_plans && vehicle.vendor_plans.length > 0">
                                    <li v-for="plan in vehicle.vendor_plans" :key="plan.id">
                                        <ul v-if="isValidJSON(plan.features)">
                                            <li v-for="(feature, index) in JSON.parse(plan.features)" :key="index"
                                                class="list-disc">{{ feature }}
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                                <span v-else>N/A</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <Dialog v-model:open="isDialogOpen[vehicle.id]">
                                    <DialogTrigger as-child>
                                        <Button @click="openEditDialog(vehicle)">
                                            {{ vehicle.vendor_plans && vehicle.vendor_plans.length > 0 ? _t('vendorprofilepages', 'plans_edit_button') : _t('vendorprofilepages', 'plans_add_button') }}
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent class="max-h-[90vh] overflow-y-auto p-4">
                                        <DialogHeader>
                                            <DialogTitle>{{ _t('vendorprofilepages', 'plans_edit_dialog_title') }}</DialogTitle>
                                            <DialogDescription>{{ _t('vendorprofilepages', 'plans_edit_dialog_description') }}</DialogDescription>
                                        </DialogHeader>
                                        <form @submit.prevent="updatePlan(vehicle.id)">
                                            <div v-for="(plan, index) in form.plans" :key="index" class="mb-4 border-b pb-4">
                                                <div class="mb-4">
                                                    <label class="block text-sm font-medium text-gray-700">{{ _t('vendorprofilepages', 'plans_plan_type_label') }}</label>
                                                    <input type="text" v-model="plan.plan_type"
                                                        class="w-full border rounded p-2" />
                                                </div>
                                                <div class="mb-4">
                                                    <label class="block text-sm font-medium text-gray-700">{{ _t('vendorprofilepages', 'plans_price_label') }}</label>
                                                    <input type="number" v-model="plan.price"
                                                        class="w-full border rounded p-2" />
                                                </div>
                                                <div class="mb-4">
                                                    <label
                                                        class="block text-sm font-medium text-gray-700">{{ _t('vendorprofilepages', 'plans_description_label') }}</label>
                                                    <textarea v-model="plan.plan_description"
                                                        class="w-full border rounded p-2"></textarea>
                                                </div>
                                                <div class="mb-4">
                                                    <label class="block text-sm font-medium text-gray-700">{{ _t('vendorprofilepages', 'plans_features_label') }}</label>
                                                    <div v-for="(feature, fIndex) in plan.features" :key="fIndex"
                                                        class="flex mb-2">
                                                        <input type="text" v-model="plan.features[fIndex]"
                                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                            :placeholder="_t('vendorprofilepages', 'plans_feature_description_placeholder')">
                                                        <button type="button" @click="removeFeature(index, fIndex)"
                                                            class="ml-2 inline-flex items-center p-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                    <button type="button" @click="addFeature(index)"
                                                        class="mt-2 inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                        {{ _t('vendorprofilepages', 'plans_add_feature_button') }}
                                                    </button>
                                                </div>
                                                <Button @click="deletePlan(plan.id, vehicle.id)"
                                                    class="ml-2 bg-red-600 hover:bg-red-700">{{ _t('vendorprofilepages', 'plans_delete_button') }}</Button>
                                            </div>
                                            <DialogFooter>
                                                <Button @click="addPlan(vehicle.id)" type="button" class="mr-2">{{ _t('vendorprofilepages', 'plans_add_new_plan_button') }}</Button>
                                                <Button type="submit">{{ _t('vendorprofilepages', 'plans_save_button') }}</Button>
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
            <Pagination :current-page="vehicles.current_page" :total-pages="vehicles.last_page"
                @page-change="handlePageChange" />
        </div>
    </MyProfileLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/Components/ui/dialog';
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { router, useForm, usePage, Link } from '@inertiajs/vue3';
 import Pagination from '@/Components/ReusableComponents/Pagination.vue';
import { useToast } from 'vue-toastification';
import axios from 'axios';

// Define props
const props = defineProps({
    vehicles: Object,
});

// State for dialog open status
const isDialogOpen = ref({});
const toast = useToast();
// Form for updating plan
const form = useForm({
    plans: []
});

// State for search query
const searchQuery = ref('');
watch(searchQuery, (newQuery) => {
    router.get(
        route('VendorPlanIndex', { locale: usePage().props.locale }),
        { search: newQuery },
        { preserveState: true, preserveScroll: true }
    );
});

// Open the edit dialog with the selected plan data
const openEditDialog = (vehicle) => {
    form.plans = vehicle.vendor_plans.map(p => ({...p, features: JSON.parse(p.features || '[]')}));
    isDialogOpen.value[vehicle.id] = true;
};

// Add a new empty feature
function addFeature(planIndex) {
    form.plans[planIndex].features.push('');
}

// Remove a feature by index
function removeFeature(planIndex, featureIndex) {
    form.plans[planIndex].features.splice(featureIndex, 1);
}

// Add a new empty plan
function addPlan(vehicleId) {
    if (form.plans.length < 2) {
        form.plans.push({
            vehicle_id: vehicleId,
            plan_type: '',
            price: '',
            plan_description: '',
            features: []
        });
    } else {
        toast.error('You can add a maximum of two plans per vehicle.');
    }
}

// Function to update plan
const updatePlan = (vehicleId) => {
    form.put(route('VendorPlanUpdate', { locale: usePage().props.locale, id: vehicleId }), {
        preserveState: true,
        onSuccess: () => {
            toast.success('Plans updated successfully!', {
                position: 'top-right',
                timeout: 1000,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
            });
            isDialogOpen.value[vehicleId] = false;
            form.reset();
        }
    });
};

// Function to delete a plan
const deletePlan = (id, vehicleId) => {
    if (confirm('Are you sure you want to delete this plan?')) {
        axios.delete(route('VendorPlanDestroy', { locale: usePage().props.locale, id: id }))
            .then(() => {
                toast.success('Plan deleted successfully!');
                const vehicle = props.vehicles.data.find(v => v.id === vehicleId);
                if (vehicle) {
                    const planIndex = vehicle.vendor_plans.findIndex(p => p.id === id);
                    if (planIndex > -1) {
                        vehicle.vendor_plans.splice(planIndex, 1);
                    }
                }
                const planIndexInForm = form.plans.findIndex(p => p.id === id);
                if (planIndexInForm > -1) {
                    form.plans.splice(planIndexInForm, 1);
                }
            })
            .catch(error => {
                toast.error('Failed to delete plan.');
                console.error(error);
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
    router.get(route('VendorPlanIndex', { locale: usePage().props.locale }), {
        search: searchQuery.value,
        page
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

// Computed property for filtered plans
const filteredVehicles = computed(() => {
    return props.vehicles.data;
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

const getPrimaryImage = (vehicle) => {
  const primaryImage = vehicle.images.find(img => img.image_type === 'primary');
  return primaryImage ? primaryImage.image_url : '/images/placeholder.jpg';
};
</script>

<style scoped>
tr:hover {
    background: rgba(233, 233, 233, 0.119);
}
</style>
