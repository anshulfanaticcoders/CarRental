<template>
    <MyProfileLayout>
        <div class="container mx-auto p-6 space-y-6">
            <!-- Flash Message -->
            <div v-if="$page.props.flash.success" class="rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">
                {{ $page.props.flash.success }}
            </div>

            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold tracking-tight">{{ _t('vendorprofilepages', 'my_vehicles_header') }}</h1>
                <div class="flex items-center gap-4">
                    <span v-if="selectedVehicleIds.length > 0" class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-orange-100 text-orange-700">
                        <CheckSquare class="w-4 h-4 mr-1" />
                        {{ selectedVehicleIds.length }} Selected
                    </span>
                    <Button
                        v-if="selectedVehicleIds.length > 0"
                        @click="confirmBulkDeletion"
                        variant="destructive"
                        class="flex items-center gap-2"
                    >
                        <Trash2 class="w-4 h-4" />
                        {{ _t('vendorprofilepages', 'delete_selected_button') }}
                    </Button>
                    <Link :href="route('vehicles.create', { locale: usePage().props.locale })">
                        <Button class="flex items-center gap-2">
                            <Plus class="w-4 h-4" />
                            {{ _t('vendorprofilepages', 'add_new_vehicle_button') }}
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Enhanced Search -->
            <div class="relative w-full max-w-md">
                <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                <Input
                    v-model="searchQuery"
                    :placeholder="_t('vendorprofilepages', 'search_vehicles_placeholder')"
                    class="pl-10 pr-4 h-12 text-base"
                />
            </div>
            <!-- Enhanced Vehicles Table -->
            <div v-if="filteredVehicles.length" class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <div class="overflow-x-auto max-w-full">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">
                                    <input
                                        type="checkbox"
                                        @change="toggleSelectAll"
                                        :checked="isAllSelected"
                                        class="rounded border-gray-300 text-primary focus:ring-primary"
                                    />
                                </TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Sr. No</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">ID</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Image</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">{{ _t('vendorprofilepages', 'table_brand_model_header') }}</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">{{ _t('vendorprofilepages', 'table_transmission_header') }}</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">{{ _t('vendorprofilepages', 'table_fuel_header') }}</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">{{ _t('vendorprofilepages', 'table_location_header') }}</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">{{ _t('vendorprofilepages', 'table_limited_km_header') }}</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">{{ _t('vendorprofilepages', 'table_cancellation_header') }}</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">{{ _t('vendorprofilepages', 'table_price_header') }}</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">{{ _t('vendorprofilepages', 'status_table_header') }}</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Created At</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold text-right">{{ _t('vendorprofilepages', 'actions_table_header') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="(vehicle, index) in filteredVehicles"
                                :key="vehicle.id"
                                class="hover:bg-muted/25 transition-colors"
                                :class="{ 'bg-blue-50': selectedVehicleIds.includes(vehicle.id) }"
                            >
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <input
                                        type="checkbox"
                                        :value="vehicle.id"
                                        v-model="selectedVehicleIds"
                                        class="rounded border-gray-300 text-primary focus:ring-primary"
                                    />
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 font-medium">
                                    {{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Badge variant="outline">#{{ vehicle.id }}</Badge>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Link :href="route('vehicle.show', { locale: usePage().props.locale, id: vehicle.id })" class="block">
                                        <div class="relative group h-12 w-20">
                                            <img
                                                :src="getPrimaryImage(vehicle)"
                                                :alt="_t('vendorprofilepages', 'alt_no_image')"
                                                class="h-12 w-20 object-cover rounded-md border shadow-sm cursor-pointer transition-all duration-200 hover:scale-105"
                                            />
                                            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 rounded-md transition-all duration-200 flex items-center justify-center pointer-events-none">
                                                <Eye class="w-4 h-4 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200" />
                                            </div>
                                        </div>
                                    </Link>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="font-medium">{{ vehicle.brand }} {{ vehicle.model }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Badge variant="secondary" class="capitalize">{{ vehicle.transmission }}</Badge>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Badge variant="secondary" class="capitalize">{{ vehicle.fuel }}</Badge>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 max-w-xs">
                                    <div class="flex items-center gap-1 truncate" :title="vehicle.full_vehicle_address">
                                        <MapPin class="w-3 h-3 text-muted-foreground flex-shrink-0" />
                                        <span class="text-sm">{{ vehicle.full_vehicle_address }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <template v-if="vehicle.benefits && (vehicle.benefits.limited_km_per_day_range || vehicle.benefits.limited_km_per_week_range || vehicle.benefits.limited_km_per_month_range)">
                                        <div class="text-xs space-y-1">
                                            <span v-if="vehicle.benefits.limited_km_per_day_range > 0" class="block">
                                                {{ vehicle.benefits.limited_km_per_day_range }} {{ _t('vendorprofilepages', 'unit_km_day') }}
                                            </span>
                                            <span v-if="vehicle.benefits.limited_km_per_week_range > 0" class="block">
                                                {{ vehicle.benefits.limited_km_per_week_range }} {{ _t('vendorprofilepages', 'unit_km_week') }}
                                            </span>
                                            <span v-if="vehicle.benefits.limited_km_per_month_range > 0" class="block">
                                                {{ vehicle.benefits.limited_km_per_month_range }} {{ _t('vendorprofilepages', 'unit_km_month') }}
                                            </span>
                                        </div>
                                    </template>
                                    <span v-else class="text-xs text-muted-foreground">{{ _t('vendorprofilepages', 'unlimited_km_text') }}</span>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <template v-if="vehicle.benefits && (vehicle.benefits.cancellation_available_per_day || vehicle.benefits.cancellation_available_per_week || vehicle.benefits.cancellation_available_per_month)">
                                        <div class="text-xs space-y-1">
                                            <span v-if="vehicle.benefits.cancellation_available_per_day" class="block">{{ _t('vendorprofilepages', 'cancellation_day') }}</span>
                                            <span v-if="vehicle.benefits.cancellation_available_per_week" class="block">{{ _t('vendorprofilepages', 'cancellation_week') }}</span>
                                            <span v-if="vehicle.benefits.cancellation_available_per_month" class="block">{{ _t('vendorprofilepages', 'cancellation_month') }}</span>
                                        </div>
                                    </template>
                                    <span v-else class="text-xs text-muted-foreground">{{ _t('vendorprofilepages', 'not_available_text') }}</span>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <span class="font-bold text-primary">{{ formatPricing(vehicle) }}</span>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Badge :variant="getStatusBadgeVariant(vehicle.status)" class="capitalize">
                                        {{ vehicle.status }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 text-sm">{{ formatDate(vehicle.created_at) }}</TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <Link :href="route('current-vendor-vehicles.edit', { locale: usePage().props.locale, 'current_vendor_vehicle': vehicle.id })">
                                            <Button size="sm" variant="outline" class="flex items-center gap-1">
                                                <Edit class="w-3 h-3" />
                                                {{ _t('vendorprofilepages', 'edit_button') }}
                                            </Button>
                                        </Link>
                                        <Button
                                            size="sm"
                                            variant="destructive"
                                            @click="confirmDeletion(vehicle)"
                                            class="flex items-center gap-1"
                                        >
                                            <Trash2 class="w-3 h-3" />
                                            {{ _t('vendorprofilepages', 'delete_button_general') }}
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                <div class="flex justify-end pt-4 pr-2">
                    <Pagination
                        :current-page="pagination.current_page"
                        :total-pages="pagination.last_page"
                        @page-change="handlePageChange"
                    />
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="rounded-xl border bg-card p-12 text-center">
                <div class="flex flex-col items-center space-y-4">
                    <Car class="w-16 h-16 text-muted-foreground" />
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold text-foreground">{{ _t('vendorprofilepages', 'no_vehicles_found_text') }}</h3>
                        <p class="text-muted-foreground">Get started by adding your first vehicle to the fleet.</p>
                    </div>
                    <Link :href="route('vehicles.create', { locale: usePage().props.locale })">
                        <Button class="flex items-center gap-2">
                            <Plus class="w-4 h-4" />
                            {{ _t('vendorprofilepages', 'add_new_vehicle_button') }}
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Delete Confirmation Alert Dialog -->
            <AlertDialog v-model:open="showDeleteModal">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle class="flex items-center gap-2">
                            <AlertTriangle class="w-5 h-5 text-red-500" />
                            {{ vehicleToDelete ? _t('vendorprofilepages', 'delete_vehicle_modal_title_single') : _t('vendorprofilepages', 'delete_vehicle_modal_title_bulk') }}
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            {{ vehicleToDelete ? _t('vendorprofilepages', 'delete_vehicle_modal_confirm_single') : _t('vendorprofilepages', 'delete_vehicle_modal_confirm_bulk', {count: selectedVehicleIds.length}) }}
                            {{ _t('vendorprofilepages', 'action_cannot_be_undone_text') }}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter class="flex gap-2">
                        <AlertDialogCancel @click="showDeleteModal = false; vehicleToDelete = null;" class="flex items-center gap-2">
                            <X class="w-4 h-4" />
                            {{ _t('vendorprofilepages', 'cancel_button') }}
                        </AlertDialogCancel>
                        <AlertDialogAction @click="vehicleToDelete ? deleteVehicle() : deleteSelectedVehicles()" class="flex items-center gap-2">
                            <Trash2 class="w-4 h-4" />
                            {{ _t('vendorprofilepages', 'delete_button_general') }}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    </MyProfileLayout>
</template>

<script setup>
import { ref, computed, watch, getCurrentInstance } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue'
import Pagination from '@/Pages/Vendor/Vehicles/Pagination.vue';
import {
    Table,
    TableHeader,
    TableRow,
    TableHead,
    TableBody,
    TableCell,
} from '@/Components/ui/table';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/Components/ui/alert-dialog';
import { Input } from '@/Components/ui/input';
import Badge from '@/Components/ui/badge/Badge.vue';
import { Button } from '@/Components/ui/button';
import {
    Search,
    Plus,
    CheckSquare,
    Trash2,
    Edit,
    Eye,
    MapPin,
    Car,
    AlertTriangle,
    X,
} from 'lucide-vue-next';

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

const getStatusBadgeVariant = (status) => {
    switch (status) {
        case 'available':
            return 'default';
        case 'rented':
            return 'secondary';
        case 'maintenance':
            return 'destructive';
        default:
            return 'outline';
    }
};

// Clear flash message after 3 seconds
const clearFlash = () => {
    setTimeout(() => {
        router.visit(window.location.pathname, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            data: { flash: null }
        });
    }, 3000);
};

// Call clearFlash when flash message exists
if (usePage().props.flash?.success) {
    clearFlash();
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
