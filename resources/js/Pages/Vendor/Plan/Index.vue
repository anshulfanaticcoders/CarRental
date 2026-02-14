<template>
    <MyProfileLayout>
        <div class="w-full mx-auto space-y-6">
            <!-- Flash Message -->
            <div v-if="$page.props.flash.success"
                class="rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">
                {{ $page.props.flash.success }}
            </div>

            <!-- Enhanced Header -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-100 rounded-xl p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-500 bg-opacity-20 rounded-lg">
                            <FileText class="w-6 h-6 text-blue-600" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">{{ _t('vendorprofilepages', 'plans_title') }}
                            </h1>
                            <p class="text-sm text-gray-600 mt-1">{{ _t('vendorprofilepages',
                                'plans_view_edit_plans_info') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <Badge variant="secondary" class="mb-1">
                                {{ filteredVehicles.length }} Vehicles
                            </Badge>
                            <p class="text-xs text-gray-500">{{ vehicles.total || 0 }} Total</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Search -->
            <div class="relative w-full max-w-md">
                <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                <Input v-model="searchQuery" :placeholder="_t('vendorprofilepages', 'plans_search_placeholder')"
                    class="pl-10 pr-4 h-12 text-base" />
            </div>

            <!-- Enhanced Plans Table -->
            <div v-if="filteredVehicles.length" class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <div class="overflow-x-auto max-w-full">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Sr. No</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">ID</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Vehicle</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Image</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Plan Type</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Price</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Description</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Features</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold text-right">Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(vehicle, index) in filteredVehicles" :key="vehicle.id"
                                class="hover:bg-muted/25 transition-colors">
                                <TableCell class="whitespace-nowrap px-4 py-3 font-medium">
                                    {{ (vehicles.current_page - 1) * vehicles.per_page + index + 1 }}
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Badge variant="outline" class="text-xs">
                                        #{{ vehicle.id }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="font-medium">{{ vehicle.brand }} {{ vehicle.model }}</div>
                                    <div class="text-sm text-muted-foreground">{{ vehicle.color || 'N/A' }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="relative group">
                                        <img :src="getPrimaryImage(vehicle)" :alt="vehicle.brand"
                                            class="h-12 w-20 object-cover rounded-lg border border-gray-200 transition-all duration-200 group-hover:scale-105 group-hover:shadow-md" />
                                    </div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div v-if="vehicle.vendor_plans && vehicle.vendor_plans.length > 0"
                                        class="space-y-1">
                                        <Badge v-for="plan in vehicle.vendor_plans" :key="plan.id" variant="secondary"
                                            class="text-xs mr-1 mb-1">
                                            {{ plan.plan_type }}
                                        </Badge>
                                    </div>
                                    <Badge v-else variant="outline" class="text-xs">
                                        No Plans
                                    </Badge>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div v-if="vehicle.vendor_plans && vehicle.vendor_plans.length > 0"
                                        class="space-y-1">
                                        <div v-for="plan in vehicle.vendor_plans" :key="plan.id"
                                            class="font-medium text-green-600">
                                            ${{ plan.price }}
                                        </div>
                                    </div>
                                    <span v-else class="text-gray-400">-</span>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 max-w-xs">
                                    <div v-if="vehicle.vendor_plans && vehicle.vendor_plans.length > 0"
                                        class="space-y-1">
                                        <p v-for="plan in vehicle.vendor_plans" :key="plan.id"
                                            class="text-sm line-clamp-2">
                                            {{ plan.plan_description || 'No description' }}
                                        </p>
                                    </div>
                                    <span v-else class="text-gray-400">-</span>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 max-w-xs">
                                    <div v-if="vehicle.vendor_plans && vehicle.vendor_plans.length > 0">
                                        <div v-for="plan in vehicle.vendor_plans" :key="plan.id" class="space-y-1">
                                            <div v-if="isValidJSON(plan.features)">
                                                <div class="flex flex-wrap gap-1">
                                                    <span v-for="(feature, index) in JSON.parse(plan.features)"
                                                        :key="index"
                                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800">
                                                        {{ feature }}
                                                    </span>
                                                </div>
                                            </div>
                                            <span v-else class="text-gray-400">No features</span>
                                        </div>
                                    </div>
                                    <span v-else class="text-gray-400">-</span>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <Dialog v-model:open="isDialogOpen[vehicle.id]">
                                            <DialogTrigger as-child>
                                                <Button @click="openEditDialog(vehicle)" size="sm"
                                                    :variant="vehicle.vendor_plans && vehicle.vendor_plans.length > 0 ? 'outline' : 'default'"
                                                    class="flex items-center gap-1">
                                                    <Edit v-if="vehicle.vendor_plans && vehicle.vendor_plans.length > 0"
                                                        class="w-3 h-3" />
                                                    <Plus v-else class="w-3 h-3" />
                                                    {{ vehicle.vendor_plans && vehicle.vendor_plans.length > 0 ?
                                                        _t('vendorprofilepages', 'plans_edit_button') :
                                                    _t('vendorprofilepages', 'plans_add_button') }}
                                                </Button>
                                            </DialogTrigger>
                                            <DialogContent class="max-h-[90vh] overflow-y-auto p-6">
                                                <DialogHeader>
                                                    <DialogTitle class="flex items-center gap-2">
                                                        <FileText class="w-5 h-5" />
                                                        {{ _t('vendorprofilepages', 'plans_edit_dialog_title') }}
                                                    </DialogTitle>
                                                    <DialogDescription>
                                                        {{ _t('vendorprofilepages', 'plans_edit_dialog_description') }}
                                                    </DialogDescription>
                                                </DialogHeader>
                                                <form @submit.prevent="updatePlan(vehicle.id)">
                                                    <div v-for="(plan, index) in form.plans" :key="index"
                                                        class="mb-6 border-b pb-6 last:border-b-0">
                                                        <div class="flex items-center justify-between mb-4">
                                                            <h4 class="font-semibold text-gray-900">Plan {{ index + 1 }}
                                                            </h4>
                                                            <Button v-if="plan.id"
                                                                @click="deletePlan(plan.id, vehicle.id)"
                                                                variant="destructive" size="sm"
                                                                class="flex items-center gap-1">
                                                                <Trash2 class="w-3 h-3" />
                                                                {{ _t('vendorprofilepages', 'plans_delete_button') }}
                                                            </Button>
                                                        </div>
                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                            <div class="space-y-2">
                                                                <Label :for="`plan_type_${index}`"
                                                                    class="flex items-center gap-1">
                                                                    {{ _t('vendorprofilepages', 'plans_plan_type_label')
                                                                    }}
                                                                    <span class="text-red-500">*</span>
                                                                </Label>
                                                                <Input :id="`plan_type_${index}`" type="text"
                                                                    v-model="plan.plan_type"
                                                                    placeholder="Enter plan type"
                                                                    :class="formErrors[`plans.${index}.plan_type`] ? 'border-red-500 focus:ring-red-500' : ''"
                                                                    @input="clearFieldError(`plans.${index}.plan_type`)" />
                                                                <div v-if="formErrors[`plans.${index}.plan_type`]"
                                                                    class="flex items-center gap-1 text-sm text-red-600 mt-1">
                                                                    <AlertCircle class="w-4 h-4" />
                                                                    {{ formErrors[`plans.${index}.plan_type`] }}
                                                                </div>
                                                            </div>
                                                            <div class="space-y-2">
                                                                <Label :for="`price_${index}`"
                                                                    class="flex items-center gap-1">
                                                                    {{ _t('vendorprofilepages', 'plans_price_label') }}
                                                                    <span class="text-red-500">*</span>
                                                                </Label>
                                                                <Input :id="`price_${index}`" type="number" step="0.01"
                                                                    min="0" v-model="plan.price"
                                                                    placeholder="Enter price"
                                                                    :class="formErrors[`plans.${index}.price`] ? 'border-red-500 focus:ring-red-500' : ''"
                                                                    @input="clearFieldError(`plans.${index}.price`)" />
                                                                <div v-if="formErrors[`plans.${index}.price`]"
                                                                    class="flex items-center gap-1 text-sm text-red-600 mt-1">
                                                                    <AlertCircle class="w-4 h-4" />
                                                                    {{ formErrors[`plans.${index}.price`] }}
                                                                </div>
                                                            </div>
                                                            <div class="space-y-2 md:col-span-2">
                                                                <Label :for="`description_${index}`"
                                                                    class="flex items-center gap-1">
                                                                    {{ _t('vendorprofilepages',
                                                                    'plans_description_label') }}
                                                                    <span class="text-red-500">*</span>
                                                                </Label>
                                                                <Textarea :id="`description_${index}`"
                                                                    v-model="plan.plan_description"
                                                                    placeholder="Enter plan description" rows="3"
                                                                    :class="formErrors[`plans.${index}.plan_description`] ? 'border-red-500 focus:ring-red-500' : ''"
                                                                    @input="clearFieldError(`plans.${index}.plan_description`)" />
                                                                <div v-if="formErrors[`plans.${index}.plan_description`]"
                                                                    class="flex items-center gap-1 text-sm text-red-600 mt-1">
                                                                    <AlertCircle class="w-4 h-4" />
                                                                    {{ formErrors[`plans.${index}.plan_description`] }}
                                                                </div>
                                                            </div>
                                                            <div class="space-y-2 md:col-span-2">
                                                                <Label class="flex items-center gap-2">
                                                                    {{ _t('vendorprofilepages', 'plans_features_label')
                                                                    }}
                                                                    <span
                                                                        class="text-xs text-gray-500 font-normal">(Optional)</span>
                                                                </Label>
                                                                <div class="space-y-2">
                                                                    <div v-for="(feature, fIndex) in plan.features"
                                                                        :key="fIndex" class="flex gap-2 group">
                                                                        <Input type="text"
                                                                            v-model="plan.features[fIndex]"
                                                                            :placeholder="_t('vendorprofilepages', 'plans_feature_description_placeholder')"
                                                                            class="transition-all duration-200 group-hover:border-blue-300" />
                                                                        <Button type="button"
                                                                            @click="removeFeature(index, fIndex)"
                                                                            variant="outline" size="sm"
                                                                            class="opacity-60 hover:opacity-100 hover:bg-red-50 hover:border-red-300 hover:text-red-600 transition-all duration-200">
                                                                            <X class="w-4 h-4" />
                                                                        </Button>
                                                                    </div>
                                                                    <Button type="button" @click="addFeature(index)"
                                                                        variant="outline" size="sm"
                                                                        class="flex items-center gap-2 w-full border-dashed hover:border-solid hover:bg-blue-50 transition-all duration-200"
                                                                        :disabled="plan.features && plan.features.length >= 10">
                                                                        <Plus class="w-4 h-4" />
                                                                        {{ _t('vendorprofilepages',
                                                                        'plans_add_feature_button') }}
                                                                        <span
                                                                            v-if="plan.features && plan.features.length >= 0"
                                                                            class="text-xs text-gray-500">
                                                                            ({{ plan.features ? plan.features.length : 0
                                                                            }}/10)
                                                                        </span>
                                                                    </Button>
                                                                    <p v-if="plan.features && plan.features.length >= 10"
                                                                        class="text-xs text-gray-500 mt-1">
                                                                        Maximum 10 features allowed per plan.
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <DialogFooter class="flex gap-2 mt-6">
                                                        <Button @click="addPlan(vehicle.id)" type="button"
                                                            variant="outline" class="flex items-center gap-2"
                                                            :disabled="form.processing">
                                                            <Plus class="w-4 h-4" />
                                                            {{ _t('vendorprofilepages', 'plans_add_new_plan_button') }}
                                                        </Button>
                                                        <Button type="submit"
                                                            class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700"
                                                            :disabled="form.processing">
                                                            <Loader2 v-if="form.processing"
                                                                class="w-4 h-4 animate-spin" />
                                                            <Save v-else class="w-4 h-4" />
                                                            {{ form.processing ? 'Saving...' : _t('vendorprofilepages',
                                                            'plans_save_button') }}
                                                        </Button>
                                                    </DialogFooter>
                                                </form>
                                            </DialogContent>
                                        </Dialog>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <div class="flex justify-end pt-4 pr-2">
                        <Pagination :current-page="vehicles.current_page" :total-pages="vehicles.last_page"
                            @page-change="handlePageChange" />
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="rounded-xl border bg-card p-12 text-center">
                <div class="flex flex-col items-center space-y-4">
                    <FileText class="w-16 h-16 text-muted-foreground" />
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold text-foreground">No plans found</h3>
                        <p class="text-muted-foreground">No vehicle plans match your search criteria.</p>
                    </div>
                </div>
            </div>
        </div>
    </MyProfileLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';
import Table from "@/Components/ui/table/Table.vue";
import TableHeader from "@/Components/ui/table/TableHeader.vue";
import TableRow from "@/Components/ui/table/TableRow.vue";
import TableHead from "@/Components/ui/table/TableHead.vue";
import TableBody from "@/Components/ui/table/TableBody.vue";
import TableCell from "@/Components/ui/table/TableCell.vue";
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { router, useForm, usePage, Link } from '@inertiajs/vue3';
import Pagination from '@/Pages/Vendor/Vehicles/Pagination.vue';
import { useToast } from 'vue-toastification';
import axios from 'axios';
import {
    Search,
    FileText,
    Edit,
    Plus,
    Trash2,
    Save,
    X,
    Loader2,
    AlertCircle,
} from 'lucide-vue-next';

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
    form.plans = vehicle.vendor_plans.map(p => ({
        ...p,
        features: JSON.parse(p.features || '[]')
    }));
    isDialogOpen.value[vehicle.id] = true;
};

// Add a new empty feature
function addFeature(planIndex) {
    if (!form.plans[planIndex].features) {
        form.plans[planIndex].features = [];
    }

    if (form.plans[planIndex].features.length >= 10) {
        toast.error('Maximum 10 features allowed per plan.', {
            position: 'top-right',
            timeout: 2000,
        });
        return;
    }

    form.plans[planIndex].features.push('');
    console.log('Added feature to plan', planIndex, form.plans[planIndex].features);
}

// Remove a feature by index
function removeFeature(planIndex, featureIndex) {
    if (form.plans[planIndex].features && form.plans[planIndex].features.length > featureIndex) {
        form.plans[planIndex].features.splice(featureIndex, 1);
        console.log('Removed feature from plan', planIndex, form.plans[planIndex].features);
    }
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

// Form validation errors
const formErrors = ref({});

// Validate form before submission
const validateForm = () => {
    const errors = {};

    form.plans.forEach((plan, index) => {
        if (!plan.plan_type?.trim()) {
            errors[`plans.${index}.plan_type`] = 'Plan type is required';
        }
        if (!plan.price || plan.price <= 0) {
            errors[`plans.${index}.price`] = 'Price must be greater than 0';
        }
        if (!plan.plan_description?.trim()) {
            errors[`plans.${index}.plan_description`] = 'Description is required';
        }
    });

    formErrors.value = errors;
    return Object.keys(errors).length === 0;
};

// Clear specific field error
const clearFieldError = (field) => {
    if (formErrors.value[field]) {
        delete formErrors.value[field];
    }
};

// Function to update plan
const updatePlan = (vehicleId) => {
    // Validate form first
    if (!validateForm()) {
        toast.error('Please fill in all required fields correctly.', {
            position: 'top-right',
            timeout: 3000,
        });
        return;
    }

    console.log('Submitting plans:', form.plans);

    form.transform((data) => {
        const transformedData = {
            plans: data.plans.map(plan => ({
                ...plan,
                plan_description: plan.plan_description || '',
                features: plan.features || []
            }))
        };
        console.log('Transformed data:', transformedData);
        return transformedData;
    }).put(route('VendorPlanUpdate', { locale: usePage().props.locale, id: vehicleId }), {
        preserveState: true,
        onStart: () => {
            formErrors.value = {};
        },
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
            formErrors.value = {};
        },
        onError: (errors) => {
            console.error('Update errors:', errors);
            formErrors.value = errors;

            const errorMessage = Object.keys(errors).length > 0
                ? 'Please correct the errors in the form.'
                : 'Error updating plans. Please try again.';

            toast.error(errorMessage, {
                position: 'top-right',
                timeout: 3000,
            });
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
