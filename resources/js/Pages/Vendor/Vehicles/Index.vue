<template>
  <MyProfileLayout>
    <div class="flex justify-between items-center">
      <h2 class="font-semibold text-xl text-gray-800">{{ _t('vendorprofilepages', 'my_vehicles_header') }}</h2>
      <div class="flex space-x-2">
        <button
          v-if="selectedVehicleIds.length > 0"
          @click="confirmBulkDeletion"
          class="px-4 py-2 bg-red-600 border-red-600 border-[1px] text-white rounded-md hover:bg-white hover:text-red-600"
        >
          {{ _t('vendorprofilepages', 'delete_selected_button') }} ({{ selectedVehicleIds.length }})
        </button>
        <Link :href="route('vehicles.create', { locale: usePage().props.locale })"
          class="px-4 py-2 bg-customPrimaryColor border-customPrimaryColor border-[1px] text-white rounded-md hover:bg-white hover:text-customPrimaryColor">
        {{ _t('vendorprofilepages', 'add_new_vehicle_button') }}
        </Link>
      </div>
    </div>

    <div class="py-12">
      <div class="mx-auto">
        <div class="rounded-[12px] mb-4">
          <input type="text" v-model="searchQuery" :placeholder="_t('vendorprofilepages', 'search_vehicles_placeholder')"
            class="px-4 py-2 border border-gray-300 rounded-md w-full" />
        </div>
        <div class="rounded-[12px]">
          <div v-if="filteredVehicles.length" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr class="">
                  <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <input type="checkbox" @change="toggleSelectAll" :checked="isAllSelected" class="rounded"/>
                  </th>
                  <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Sr. No</th>
                  <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">{{ _t('vendorprofilepages', 'table_id_header') }}</th>
                  <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">{{ _t('vendorprofilepages', 'table_image_header') }}</th>
                  <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">{{ _t('vendorprofilepages', 'table_brand_model_header') }}</th>
                  <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                    {{ _t('vendorprofilepages', 'table_transmission_header') }}</th>
                  <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">{{ _t('vendorprofilepages', 'table_fuel_header') }}</th>
                  <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">{{ _t('vendorprofilepages', 'table_location_header') }}
                  </th>
                  <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">{{ _t('vendorprofilepages', 'table_limited_km_header') }}
                  </th>
                  <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                    {{ _t('vendorprofilepages', 'table_cancellation_header') }}
                  </th>
                  <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">{{ _t('vendorprofilepages', 'table_price_header') }}
                  </th>
                  <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">{{ _t('vendorprofilepages', 'status_table_header') }}</th>
                  <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Created At</th>
                  <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">{{ _t('vendorprofilepages', 'actions_table_header') }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(vehicle, index) in filteredVehicles" :key="vehicle.id" class="border-b" :class="{'bg-blue-100': selectedVehicleIds.includes(vehicle.id)}">
                  <td class="px-2 py-4 whitespace-nowrap">
                    <input type="checkbox" :value="vehicle.id" v-model="selectedVehicleIds" class="rounded"/>
                  </td>
                  <td class="px-2 py-4 whitespace-nowrap text-[0.875rem]">{{ (pagination.current_page - 1) *
                    pagination.per_page + index + 1 }}</td>
                  <td class="px-2 py-4 whitespace-nowrap text-[0.875rem]">{{ vehicle.id }}</td>
                  <Link :href="route('vehicle.show', { locale: usePage().props.locale, id: vehicle.id })" class="w-full">
                  <td class="px-2 py-4 whitespace-nowrap">
                    <img :src="getPrimaryImage(vehicle)" :alt="_t('vendorprofilepages', 'alt_no_image')" class="h-12 w-24 object-cover rounded">
                  </td>
                  </Link>
                  <td class="px-2 py-4 whitespace-nowrap text-[0.875rem]">{{ vehicle.brand }} {{ vehicle.model }}</td>
                  <td class="px-2 py-4 whitespace-nowrap text-[0.875rem] capitalize">{{ vehicle.transmission }}</td>
                  <td class="px-2 py-4 whitespace-nowrap text-[0.875rem] capitalize">{{ vehicle.fuel }}</td>
                  <td class="px-2 py-4 whitespace-nowrap text-[0.875rem]">{{ vehicle.full_vehicle_address }}</td>
                  <td class="px-2 py-4 whitespace-nowrap text-[0.875rem]">
                    <template v-if="vehicle.benefits && (vehicle.benefits.limited_km_per_day_range || vehicle.benefits.limited_km_per_week_range || vehicle.benefits.limited_km_per_month_range)">
                      <span v-if="vehicle.benefits.limited_km_per_day_range > 0">
                        {{ vehicle.benefits.limited_km_per_day_range }} {{ _t('vendorprofilepages', 'unit_km_day') }}
                      </span>
                      <span
                        v-if="vehicle.benefits.limited_km_per_day_range && (vehicle.benefits.limited_km_per_week_range || vehicle.benefits.limited_km_per_month_range)">
                        |
                      </span>
                      <span v-if="vehicle.benefits.limited_km_per_week_range > 0">
                        {{ vehicle.benefits.limited_km_per_week_range }} {{ _t('vendorprofilepages', 'unit_km_week') }}
                      </span>
                      <span
                        v-if="vehicle.benefits.limited_km_per_week_range && vehicle.benefits.limited_km_per_month_range">
                        
                      </span>
                      <span v-if="vehicle.benefits.limited_km_per_month_range > 0">
                        |
                        {{ vehicle.benefits.limited_km_per_month_range }} {{ _t('vendorprofilepages', 'unit_km_month') }}
                      </span>
                    </template>
                    <span v-else-if="!vehicle.benefits || (vehicle.benefits && !vehicle.benefits.limited_km_per_day_range && !vehicle.benefits.limited_km_per_week_range && !vehicle.benefits.limited_km_per_month_range)">{{ _t('vendorprofilepages', 'unlimited_km_text') }}</span>
                  </td>

                  <td class="px-2 py-4 whitespace-nowrap text-[0.875rem]">
                    <template v-if="vehicle.benefits && (vehicle.benefits.cancellation_available_per_day || vehicle.benefits.cancellation_available_per_week || vehicle.benefits.cancellation_available_per_month)">
                      <span v-if="vehicle.benefits.cancellation_available_per_day">{{ _t('vendorprofilepages', 'cancellation_day') }}</span>
                      <span
                        v-if="vehicle.benefits.cancellation_available_per_day && (vehicle.benefits.cancellation_available_per_week || vehicle.benefits.cancellation_available_per_month)">
                        | </span>
                      <span v-if="vehicle.benefits.cancellation_available_per_week">{{ _t('vendorprofilepages', 'cancellation_week') }}</span>
                      <span
                        v-if="vehicle.benefits.cancellation_available_per_week && vehicle.benefits.cancellation_available_per_month">
                        | </span>
                      <span v-if="vehicle.benefits.cancellation_available_per_month">{{ _t('vendorprofilepages', 'cancellation_month') }}</span>
                    </template>
                    <span v-else-if="!vehicle.benefits || (vehicle.benefits && !vehicle.benefits.cancellation_available_per_day && !vehicle.benefits.cancellation_available_per_week && !vehicle.benefits.cancellation_available_per_month)">{{ _t('vendorprofilepages', 'not_available_text') }}</span>
                  </td>


                  <td class="px-2 py-4 whitespace-nowrap text-[0.875rem] text-customPrimaryColor font-bold">
                    {{ formatPricing(vehicle) }}
                  </td>

                  <td class="px-2 py-4 whitespace-nowrap text-[0.875rem]">
                    <span :class="getStatusBadgeClass(vehicle.status)" class="px-2 py-1 rounded text-xs capitalize">
                      {{ vehicle.status }}
                    </span>
                  </td>
                  <td class="px-2 py-4 whitespace-nowrap text-[0.875rem]">{{ formatDate(vehicle.created_at) }}</td>
                  <td class="px-2 py-4 whitespace-nowrap text-xs font-medium">
                    <Link :href="route('current-vendor-vehicles.edit', { locale: usePage().props.locale, 'current_vendor_vehicle': vehicle.id })"
                      class="px-3 mr-2 py-1 bg-gray-600 text-white rounded hover:bg-gray-700">
                    {{ _t('vendorprofilepages', 'edit_button') }}
                    </Link>
                    <button @click="confirmDeletion(vehicle)"
                      class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700">
                      {{ _t('vendorprofilepages', 'delete_button_general') }}
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="text-center py-12">
            <p class="text-gray-500">{{ _t('vendorprofilepages', 'no_vehicles_found_text') }}</p>
          </div>
        </div>
      </div>
      <div class="mt-[1rem] flex justify-end">
        <Pagination :current-page="pagination.current_page" :total-pages="pagination.last_page"
          @page-change="handlePageChange" />
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <Modal :show="showDeleteModal" @close="showDeleteModal = false">
      <div class="p-6">
        <h3 class="text-lg font-medium">{{ vehicleToDelete ? _t('vendorprofilepages', 'delete_vehicle_modal_title_single') : _t('vendorprofilepages', 'delete_vehicle_modal_title_bulk') }}</h3>
        <p class="mt-2 text-gray-600">
          {{ vehicleToDelete ? _t('vendorprofilepages', 'delete_vehicle_modal_confirm_single') : _t('vendorprofilepages', 'delete_vehicle_modal_confirm_bulk', {count: selectedVehicleIds.length}) }}
          {{ _t('vendorprofilepages', 'action_cannot_be_undone_text') }}
        </p>
        <div class="mt-4 flex justify-end space-x-3">
          <button @click="showDeleteModal = false; vehicleToDelete = null;" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
            {{ _t('vendorprofilepages', 'cancel_button') }}
          </button>
          <button @click="vehicleToDelete ? deleteVehicle() : deleteSelectedVehicles()" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
            {{ _t('vendorprofilepages', 'delete_button_general') }}
          </button>
        </div>
      </div>
    </Modal>
  </MyProfileLayout>
</template>

<script setup>
import { ref, computed, watch, getCurrentInstance } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue'
 import Pagination from '@/Components/ReusableComponents/Pagination.vue';

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;

const props = defineProps({
  vehicles: {
    type: Array,
    required: true
  },
  pagination: {
    type: Object,
    required: true
  }
})
const searchQuery = ref('');

// Define filteredVehicles earlier as other computed/watchers depend on it
const filteredVehicles = computed(() => {
  return props.vehicles.filter(vehicle => {
    const query = searchQuery.value.toLowerCase();
    return (
      vehicle.brand.toLowerCase().includes(query) ||
      vehicle.model.toLowerCase().includes(query) ||
      vehicle.transmission.toLowerCase().includes(query) ||
      vehicle.fuel.toLowerCase().includes(query) ||
      vehicle.full_vehicle_address.toLowerCase().includes(query) ||
      vehicle.status.toLowerCase().includes(query)
    );
  });
});

watch(searchQuery, (newQuery) => {
  router.get(
    route('current-vendor-vehicles.index', { locale: usePage().props.locale }),
    { search: newQuery },
    { preserveState: true, preserveScroll: true }
  );
});

const handlePageChange = (page) => {
  router.get(route('current-vendor-vehicles.index', { locale: usePage().props.locale }), { ...props.filters, page }, { preserveState: true, preserveScroll: true });

};
const showDeleteModal = ref(false)
const vehicleToDelete = ref(null)
const selectedVehicleIds = ref([])

// Computed property to check if all filtered vehicles are selected
const isAllSelected = computed(() => {
  if (!filteredVehicles.value.length) return false;
  return filteredVehicles.value.every(vehicle => selectedVehicleIds.value.includes(vehicle.id));
});

// Toggle select all vehicles
const toggleSelectAll = (event) => {
  if (event.target.checked) {
    selectedVehicleIds.value = filteredVehicles.value.map(vehicle => vehicle.id);
  } else {
    selectedVehicleIds.value = [];
  }
};

watch(filteredVehicles, () => {
  // If filtered vehicles change, we might need to prune selectedVehicleIds
  // or ensure the "select all" state is accurate.
  // For now, we'll clear selection if filters change to avoid complexity,
  // or you could implement more sophisticated logic.
  // selectedVehicleIds.value = []; // Simplest approach
  // A more nuanced approach would be to filter selectedVehicleIds based on new filteredVehicles
   selectedVehicleIds.value = selectedVehicleIds.value.filter(id =>
    filteredVehicles.value.some(vehicle => vehicle.id === id)
  );
});


const getPrimaryImage = (vehicle) => {
  const primaryImage = vehicle.images.find(img => img.image_type === 'primary')
  return primaryImage ? `${primaryImage.image_url}` : '/images/placeholder.jpg'
}

const getStatusBadgeClass = (status) => {
  const classes = {
    available: 'bg-green-500 text-white',
    rented: 'bg-blue-500 text-white',
    maintenance: 'bg-yellow-500 text-white'
  }
  return classes[status] || 'bg-gray-500 text-white'
}

const confirmDeletion = (vehicle) => {
  vehicleToDelete.value = vehicle
  showDeleteModal.value = true
}

const confirmBulkDeletion = () => {
  vehicleToDelete.value = null; // Ensure we are in bulk delete mode
  if (selectedVehicleIds.value.length > 0) {
    showDeleteModal.value = true;
  }
};

const deleteVehicle = () => {
  router.delete(route('current-vendor-vehicles.destroy', { locale: usePage().props.locale, 'current_vendor_vehicle': vehicleToDelete.value.id }), {
    onSuccess: () => {
      showDeleteModal.value = false
      vehicleToDelete.value = null
      // Optionally, remove from selectedVehicleIds if it was there
      const index = selectedVehicleIds.value.indexOf(vehicleToDelete.value.id);
      if (index > -1) {
        selectedVehicleIds.value.splice(index, 1);
      }
    },
    onError: () => {
        showDeleteModal.value = false;
    }
  })
}

const deleteSelectedVehicles = () => {
  if (selectedVehicleIds.value.length === 0) return;
  router.post(route('current-vendor-vehicles.bulk-destroy', { locale: usePage().props.locale }), { ids: selectedVehicleIds.value }, {
    onSuccess: () => {
      showDeleteModal.value = false;
      selectedVehicleIds.value = [];
    },
    onError: (errors) => {
      console.error('Error deleting selected vehicles:', errors);
      showDeleteModal.value = false;
      // Handle error display if necessary
    }
  });
};

const formatPricing = (vehicle) => {
  if (!vehicle || !vehicle.vendor_profile || !vehicle.vendor_profile.currency) {
    return _t('vendorprofilepages', 'not_applicable_text'); // Fallback if data is missing
  }

  const currencySymbol = vehicle.vendor_profile.currency;
  const prices = [];

  if (vehicle.price_per_day) prices.push(`${currencySymbol}${vehicle.price_per_day}${_t('vendorprofilepages', 'price_per_day_suffix')}`);
  if (vehicle.price_per_week) prices.push(`${currencySymbol}${vehicle.price_per_week}${_t('vendorprofilepages', 'price_per_week_suffix')}`);
  if (vehicle.price_per_month) prices.push(`${currencySymbol}${vehicle.price_per_month}${_t('vendorprofilepages', 'price_per_month_suffix')}`);

  return prices.length ? prices.join(' | ') : _t('vendorprofilepages', 'not_applicable_text');
};

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`;
};

// filteredVehicles definition was moved up

</script>

<style scoped>
@media screen and (max-width:768px) {

  th {
    font-size: 0.75rem;
  }

  td {
    font-size: 0.75rem;
    text-wrap-mode: nowrap;
  }
}
</style >
