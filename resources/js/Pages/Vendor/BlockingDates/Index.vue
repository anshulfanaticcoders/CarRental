<template>
    <MyProfileLayout>
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-semibold text-xl text-gray-800 max-[768px]:text-[0.875rem]">{{ _t('vendorprofilepages', 'manage_blocking_dates_header') }}</h2>
            <Dialog>
                <DialogTrigger class="px-4 py-2 bg-customPrimaryColor text-white rounded hover:bg-[#153b4fdc] max-[768px]:text-[0.75rem]">
                    {{ _t('vendorprofilepages', 'add_blocking_date_button') }}
                </DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{{ _t('vendorprofilepages', 'add_blocking_date_dialog_title') }}</DialogTitle>
                        <DialogDescription>{{ _t('vendorprofilepages', 'add_blocking_date_dialog_description') }}</DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="submitForm">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">{{ _t('vendorprofilepages', 'label_vehicle_select') }}</label>
                            <select v-model="form.vehicle_id" class="w-full border rounded p-2">
                                <option value="">{{ _t('vendorprofilepages', 'placeholder_select_vehicle') }}</option>
                                <option v-for="vehicle in props.vehicles" :key="vehicle.id" :value="vehicle.id">
                                    {{ vehicle.brand }} - {{ vehicle.model }}
                                </option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">{{ _t('vendorprofilepages', 'label_start_date') }}</label>
                            <input type="date" v-model="form.blocking_start_date" class="w-full border rounded p-2" />
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">{{ _t('vendorprofilepages', 'label_end_date') }}</label>
                            <input type="date" v-model="form.blocking_end_date" class="w-full border rounded p-2" />
                        </div>
                        <DialogFooter>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                {{ _t('vendorprofilepages', 'button_save') }}
                            </button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <!-- Search Bar -->
        <div class="mb-4">
            <input type="text" v-model="searchQuery" :placeholder="_t('vendorprofilepages', 'search_vehicles_placeholder')"
                class="px-4 py-2 border border-gray-300 rounded-md w-full" />
        </div>

        <div v-if="filteredVehicles.length" class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ _t('vendorprofilepages', 'table_id_header') }}</th>
                        <th class="px-4 py-2 text-left text-sm font-bold">{{ _t('vendorprofilepages', 'table_brand_header') }}</th>
                        <th class="px-4 py-2 text-left text-sm font-bold">{{ _t('vendorprofilepages', 'label_model') }}</th>
                        <th class="px-4 py-2 text-left text-sm font-bold">{{ _t('vendorprofilepages', 'table_location_header') }}</th>
                        <th class="px-4 py-2 text-left text-sm font-bold">{{ _t('vendorprofilepages', 'table_booking_dates_header') }}</th>
                        <th class="px-4 py-2 text-left text-sm font-bold">{{ _t('vendorprofilepages', 'table_blocking_start_date_header') }}</th>
                        <th class="px-4 py-2 text-left text-sm font-bold">{{ _t('vendorprofilepages', 'table_blocking_end_date_header') }}</th>
                        <th class="px-4 py-2 text-left text-sm font-bold">{{ _t('vendorprofilepages', 'actions_table_header') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(vehicle, index) in filteredVehicles" :key="vehicle.id"
                        class="border-b hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">{{ (pagination.current_page - 1) * pagination.per_page +
                            index + 1 }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ vehicle.brand }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ vehicle.model }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ vehicle.full_vehicle_address }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">
                            <div v-if="vehicle.bookings && vehicle.bookings.length > 0">
                                <div v-for="(booking, index) in vehicle.bookings" :key="index" class="mb-1">
                                    {{ _t('vendorprofilepages', 'text_pickup_prefix') }} {{ formatDate(booking.pickup_date) }} - {{ _t('vendorprofilepages', 'text_return_prefix') }} {{ formatDate(booking.return_date) }}
                                </div>
                            </div>
                            <span v-else>{{ _t('vendorprofilepages', 'not_applicable_text') }}</span>
                        </td>

                        <td class="px-4 py-2 text-sm text-gray-700">
                            <div v-if="vehicle.blockings && vehicle.blockings.length > 0">
                                <div v-for="(blocking, index) in vehicle.blockings" :key="index" class="mb-1">
                                    {{ formatDate(blocking.blocking_start_date) }}
                                </div>
                            </div>
                            <span v-else>{{ _t('vendorprofilepages', 'not_applicable_text') }}</span>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-700">
                            <div v-if="vehicle.blockings && vehicle.blockings.length > 0">
                                <div v-for="(blocking, index) in vehicle.blockings" :key="index" class="mb-1">
                                    {{ formatDate(blocking.blocking_end_date) }}
                                </div>
                            </div>
                            <span v-else>{{ _t('vendorprofilepages', 'not_applicable_text') }}</span>
                        </td>
                        <td class="px-4 py-2 text-sm">
                            <div v-if="vehicle.blockings && vehicle.blockings.length > 0">
                                <div v-for="blocking in vehicle.blockings" :key="blocking.id" class="flex mb-1">
                                    <Dialog>
                                        <DialogTrigger class="text-blue-600 hover:underline mr-2" @click="prepareEditForm(blocking)">{{ _t('vendorprofilepages', 'edit_button') }}</DialogTrigger>
                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle>{{ _t('vendorprofilepages', 'edit_blocking_date_dialog_title') }}</DialogTitle>
                                                <DialogDescription>{{ _t('vendorprofilepages', 'edit_blocking_date_dialog_description') }}
                                                </DialogDescription>
                                            </DialogHeader>
                                            <form @submit.prevent="updateBlockingDate(blocking.id)">
                                                <div class="mb-4">
                                                    <label class="block text-sm font-medium text-gray-700">{{ _t('vendorprofilepages', 'label_start_date') }}</label>
                                                    <input type="date" v-model="editForm.blocking_start_date"
                                                        class="w-full border rounded p-2" />
                                                </div>
                                                <div class="mb-4">
                                                    <label class="block text-sm font-medium text-gray-700">{{ _t('vendorprofilepages', 'label_end_date') }}</label>
                                                    <input type="date" v-model="editForm.blocking_end_date"
                                                        class="w-full border rounded p-2" />
                                                </div>
                                                <DialogFooter>
                                                    <button type="submit"
                                                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                                        {{ _t('vendorprofilepages', 'save_changes_button') }}
                                                    </button>
                                                </DialogFooter>
                                            </form>
                                        </DialogContent>
                                    </Dialog>
                                    <button @click="removeBlockingDates(blocking.id)" class="text-red-600 hover:underline">
                                        {{ _t('vendorprofilepages', 'button_remove_blocking_date') }}
                                    </button>
                                </div>
                            </div>
                            <span v-else>{{ _t('vendorprofilepages', 'not_applicable_text') }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-else class="text-center py-6">
            <span class="text-gray-500">{{ _t('vendorprofilepages', 'no_vehicles_found_text') }}</span>
        </div>

        <!-- Pagination -->
        <div class="mt-4 flex justify-end">
            <Pagination :current-page="pagination.current_page" :total-pages="pagination.last_page"
                @page-change="handlePageChange" />
        </div>
    </MyProfileLayout>
</template>

<script setup>
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { ref, computed, watch, onMounted, getCurrentInstance } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
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
 import Pagination from '@/Components/ReusableComponents/Pagination.vue';

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;

const toast = useToast();
const form = ref({ 
    vehicle_id: '', 
    blocking_start_date: '', 
    blocking_end_date: '' 
});
const editForm = ref({
    blocking_start_date: '',
    blocking_end_date: ''
});
const searchQuery = ref('');

const props = defineProps({
    vehicles: {
        type: Array,
        required: true
    },
    pagination: {
        type: Object,
        required: true
    },
    filters: {
        type: Object,
        default: () => ({})
    }
});

const handlePageChange = (page) => {
    router.get(route('vendor.blocking-dates.index'), 
        { ...props.filters, page }, 
        { preserveState: true, preserveScroll: true }
    );
};

const submitForm = async () => {
    // Form validation
    if (!form.value.vehicle_id) {
        toast.error(_t('vendorprofilepages', 'toast_error_select_vehicle'));
        return;
    }
    
    if (!form.value.blocking_start_date) {
        toast.error(_t('vendorprofilepages', 'toast_error_select_start_date'));
        return;
    }
    
    if (!form.value.blocking_end_date) {
        toast.error(_t('vendorprofilepages', 'toast_error_select_end_date'));
        return;
    }
    
    try {
        await axios.post(route('vendor.blocking-dates.store'), form.value);
        toast.success(_t('vendorprofilepages', 'toast_success_blocking_date_added'));
        // Reset form
        form.value = { vehicle_id: '', blocking_start_date: '', blocking_end_date: '' };
        // Reload to see changes
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    } catch (error) {
        console.error('Error:', error.response?.data || error);
        toast.error(error.response?.data?.message || _t('vendorprofilepages', 'toast_error_add_blocking_date'));
    }
};

const prepareEditForm = (blocking) => {
    editForm.value.blocking_start_date = blocking.blocking_start_date;
    editForm.value.blocking_end_date = blocking.blocking_end_date;
};

const updateBlockingDate = async (blockingId) => {
    try {
        await axios.put(route('vendor.blocking-dates.update', blockingId), {
            blocking_start_date: editForm.value.blocking_start_date,
            blocking_end_date: editForm.value.blocking_end_date,
        });
        toast.success(_t('vendorprofilepages', 'toast_success_blocking_date_updated'));
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    } catch (error) {
        toast.error(_t('vendorprofilepages', 'toast_error_update_blocking_date'));
    }
};

const removeBlockingDates = async (blockingId) => {
    if (confirm(_t('vendorprofilepages', 'confirm_remove_blocking_date'))) {
        try {
            await axios.delete(route('vendor.blocking-dates.destroy', blockingId));
            toast.success(_t('vendorprofilepages', 'toast_success_blocking_date_removed'));
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } catch (error) {
            toast.error(_t('vendorprofilepages', 'toast_error_remove_blocking_date'));
        }
    }
};

const filteredVehicles = computed(() => {
    const query = searchQuery.value.toLowerCase();
    return props.vehicles.filter(vehicle => {
        return (
            vehicle.brand.toLowerCase().includes(query) ||
            vehicle.model.toLowerCase().includes(query) ||
            vehicle.location?.toLowerCase().includes(query)
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

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const options = { year: 'numeric', month: 'short', day: '2-digit' };
    return new Date(dateString).toLocaleDateString('en-US', options);
};

// Initialize searchQuery from URL params
onMounted(() => {
    if (props.filters?.search) {
        searchQuery.value = props.filters.search;
    }
});
</script>

<style scoped>
select {
    width: 100%;
}

label {
    margin-bottom: 0.5rem;
}

@media screen and (max-width:768px) {
    th {
        font-size: 0.75rem;
    }
}
</style>
