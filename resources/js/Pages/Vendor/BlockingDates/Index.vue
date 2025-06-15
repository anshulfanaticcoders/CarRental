<template>
    <MyProfileLayout>
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-semibold text-xl text-gray-800 max-[768px]:text-[0.875rem]">{{ _t('vendorprofilepages', 'manage_blocking_dates_header') }}</h2>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sr. No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ _t('vendorprofilepages', 'table_id_header') }}</th>
                        <th class="px-4 py-2 text-left text-sm font-bold">{{ _t('vendorprofilepages', 'table_image_header') }}</th>
                        <th class="px-4 py-2 text-left text-sm font-bold">{{ _t('vendorprofilepages', 'table_brand_header') }}</th>
                        <th class="px-4 py-2 text-left text-sm font-bold">{{ _t('vendorprofilepages', 'label_model') }}</th>
                        <th class="px-4 py-2 text-left text-sm font-bold">{{ _t('vendorprofilepages', 'table_location_header') }}</th>
                        <th class="px-4 py-2 text-left text-sm font-bold">{{ _t('vendorprofilepages', 'table_booking_dates_header') }}</th>
                        <th class="px-4 py-2 text-left text-sm font-bold">{{ _t('vendorprofilepages', 'table_blocking_dates_header') }}</th>
                        <th class="px-4 py-2 text-left text-sm font-bold">{{ _t('vendorprofilepages', 'actions_table_header') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(vehicle, index) in filteredVehicles" :key="vehicle.id"
                        class="border-b hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">{{ (vehicles.current_page - 1) * vehicles.per_page +
                            index + 1 }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ vehicle.id }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">
                            <Link :href="route('vehicle.show', { locale: usePage().props.locale, id: vehicle.id })">
                                <img :src="getPrimaryImage(vehicle)" :alt="vehicle.brand" class="h-12 w-24 object-cover rounded">
                            </Link>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ vehicle.brand }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ vehicle.model }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ vehicle.full_vehicle_address }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">
                            <div v-if="vehicle.bookings && vehicle.bookings.length > 0">
                                <div v-for="(booking, index) in vehicle.bookings.slice(0, 2)" :key="index" class="mb-1">
                                    {{ _t('vendorprofilepages', 'text_pickup_prefix') }} {{ formatDate(booking.pickup_date) }} - {{ _t('vendorprofilepages', 'text_return_prefix') }} {{ formatDate(booking.return_date) }}
                                </div>
                                <Dialog v-if="vehicle.bookings.length > 2">
                                    <DialogTrigger as-child>
                                        <Button variant="link" class="p-0 h-auto">{{ _t('vendorprofilepages', 'see_more_button') }}</Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>{{ _t('vendorprofilepages', 'all_booking_dates_dialog_title') }}</DialogTitle>
                                        </DialogHeader>
                                        <ul>
                                            <li v-for="booking in vehicle.bookings" :key="booking.id" class="mb-1">
                                                {{ _t('vendorprofilepages', 'text_pickup_prefix') }} {{ formatDate(booking.pickup_date) }} - {{ _t('vendorprofilepages', 'text_return_prefix') }} {{ formatDate(booking.return_date) }}
                                            </li>
                                        </ul>
                                    </DialogContent>
                                </Dialog>
                            </div>
                            <span v-else>{{ _t('vendorprofilepages', 'not_applicable_text') }}</span>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-700">
                            <div v-if="vehicle.blockings && vehicle.blockings.length > 0">
                                <div v-for="(blocking, index) in vehicle.blockings" :key="index" class="mb-1">
                                    {{ formatDate(blocking.blocking_start_date) }} - {{ formatDate(blocking.blocking_end_date) }}
                                </div>
                            </div>
                            <span v-else>{{ _t('vendorprofilepages', 'not_applicable_text') }}</span>
                        </td>
                        <td class="px-4 py-2 text-sm">
                            <Dialog>
                                <DialogTrigger as-child>
                                    <Button @click="prepareEditForm(vehicle)">
                                        {{ vehicle.blockings && vehicle.blockings.length > 0 ? _t('vendorprofilepages', 'edit_blocking_date_dialog_title') : _t('vendorprofilepages', 'add_blocking_date_button') }}
                                    </Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <DialogHeader>
                                        <DialogTitle>{{ _t('vendorprofilepages', 'add_edit_blocking_dates_dialog_title') }}</DialogTitle>
                                        <DialogDescription>{{ _t('vendorprofilepages', 'add_edit_blocking_dates_dialog_description') }}</DialogDescription>
                                    </DialogHeader>
                                    <form @submit.prevent="submitForm(vehicle.id)">
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700">{{ _t('vendorprofilepages', 'label_start_date') }}</label>
                                            <input type="date" v-model="form.blocking_start_date" class="w-full border rounded p-2" :min="today" />
                                        </div>
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700">{{ _t('vendorprofilepages', 'label_end_date') }}</label>
                                            <input type="date" v-model="form.blocking_end_date" class="w-full border rounded p-2" :min="today" />
                                        </div>
                                        <DialogFooter>
                                            <Button type="submit">
                                                {{ _t('vendorprofilepages', 'button_save') }}
                                            </Button>
                                        </DialogFooter>
                                    </form>
                                    <div v-if="vehicle.blockings && vehicle.blockings.length > 0" class="mt-4">
                                        <h3 class="text-lg font-semibold">{{ _t('vendorprofilepages', 'existing_blocking_dates_title') }}</h3>
                                        <ul>
                                            <li v-for="blocking in vehicle.blockings" :key="blocking.id" class="flex justify-between items-center mt-2">
                                                <span>{{ formatDate(blocking.blocking_start_date) }} - {{ formatDate(blocking.blocking_end_date) }}</span>
                                                <Button @click="removeBlockingDate(blocking.id)" variant="destructive" size="sm">
                                                    {{ _t('vendorprofilepages', 'button_remove_blocking_date') }}
                                                </Button>
                                            </li>
                                        </ul>
                                    </div>
                                </DialogContent>
                            </Dialog>
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
            <Pagination :current-page="vehicles.current_page" :total-pages="vehicles.last_page"
                @page-change="handlePageChange" />
        </div>
    </MyProfileLayout>
</template>

<script setup>
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { ref, computed, watch, onMounted, getCurrentInstance } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
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
import { Button } from '@/Components/ui/button';
 import Pagination from '@/Components/ReusableComponents/Pagination.vue';

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;

const toast = useToast();
const today = new Date().toISOString().split('T')[0];
const form = ref({ 
    vehicle_id: '', 
    blocking_start_date: '', 
    blocking_end_date: '' 
});
const searchQuery = ref('');

const props = defineProps({
    vehicles: {
        type: Object,
        required: true
    },
});

const handlePageChange = (page) => {
    router.get(route('vendor.blocking-dates.index', { locale: usePage().props.locale }), 
        { search: searchQuery.value, page }, 
        { preserveState: true, preserveScroll: true }
    );
};

const submitForm = async (vehicleId) => {
    form.value.vehicle_id = vehicleId;
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
        await axios.post(route('vendor.blocking-dates.store', { locale: usePage().props.locale }), form.value);
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

const prepareEditForm = (vehicle) => {
    // This function is now used to open the dialog for a specific vehicle
};

const removeBlockingDate = async (blockingId) => {
    if (confirm(_t('vendorprofilepages', 'confirm_remove_blocking_date'))) {
        try {
            await axios.delete(route('vendor.blocking-dates.destroy', { locale: usePage().props.locale, 'blocking_date': blockingId }));
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
    return props.vehicles.data;
});

const getPrimaryImage = (vehicle) => {
  const primaryImage = vehicle.images.find(img => img.image_type === 'primary');
  return primaryImage ? primaryImage.image_url : '/images/placeholder.jpg';
};

watch(searchQuery, (newQuery) => {
    router.get(
        route('vendor.blocking-dates.index', { locale: usePage().props.locale }),
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
    const urlParams = new URLSearchParams(window.location.search);
    searchQuery.value = urlParams.get('search') || '';
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
