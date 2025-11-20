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

            <!-- Category Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Vehicles Card (always first) -->
                <div class="relative bg-gradient-to-br from-slate-700 to-slate-900 border border-slate-600 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-white bg-opacity-20 rounded-lg">
                            <Car class="w-6 h-6 text-white" />
                        </div>
                        <Badge class="bg-white text-slate-800 font-bold">
                            {{ totalVehicles }}
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-white">{{ totalVehicles }}</p>
                        <p class="text-lg font-semibold text-slate-200 mt-1">Total Vehicles</p>
                        <p class="text-sm text-slate-400 mt-2">{{ totalVehicles === 1 ? 'Vehicle' : 'Vehicles' }} in Fleet</p>
                    </div>
                </div>

                <!-- Category Cards -->
                <div v-for="(category, index) in categoryStatsData" :key="category.name"
                    :class="[
                        'relative rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02] border',
                        getCategoryCardClass(index)
                    ]">
                    <div class="flex items-center justify-between mb-4">
                        <div :class="getCategoryIconClass(index)">
                            <Car class="w-6 h-6" />
                        </div>
                        <Badge :variant="getCategoryBadgeVariant(index)" class="text-white">
                            {{ category.count }}
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p :class="getCategoryCountClass(index)">{{ category.count }}</p>
                        <p :class="getCategoryNameClass(index)">{{ category.name }}</p>
                        <p :class="getCategoryLabelClass(index)">{{ category.count === 1 ? 'Vehicle' : 'Vehicles' }}</p>
                    </div>
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
  },
  categoryStats: {
    type: Array,
    default: () => []
  },
  totalVehicles: {
    type: Number,
    default: 0
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

// Use category stats from props if available, otherwise calculate from current vehicles (fallback)
const categoryStatsData = computed(() => {
  // If category stats are passed from backend, use them
  if (props.categoryStats && props.categoryStats.length > 0) {
    return props.categoryStats.slice(0, 7); // Show top 7 categories (plus total vehicles card = 8 total)
  }

  // Fallback: Calculate from current page vehicles (not ideal but better than nothing)
  const categories = {};
  props.vehicles.forEach(vehicle => {
    const categoryName = vehicle.category?.name || 'Uncategorized';
    if (!categories[categoryName]) {
      categories[categoryName] = 0;
    }
    categories[categoryName]++;
  });

  return Object.entries(categories)
    .map(([name, count]) => ({ name, count }))
    .sort((a, b) => b.count - a.count)
    .slice(0, 7); // Show top 7 categories (plus total vehicles card = 8 total)
});

// Helper functions for category card styling
const getCategoryCardClass = (index) => {
  const classes = [
    'bg-gradient-to-br from-blue-50 to-indigo-50 border-blue-200',
    'bg-gradient-to-br from-green-50 to-emerald-50 border-green-200',
    'bg-gradient-to-br from-purple-50 to-pink-50 border-purple-200',
    'bg-gradient-to-br from-orange-50 to-amber-50 border-orange-200',
    'bg-gradient-to-br from-red-50 to-rose-50 border-red-200',
    'bg-gradient-to-br from-cyan-50 to-teal-50 border-cyan-200',
    'bg-gradient-to-br from-indigo-50 to-purple-50 border-indigo-200',
    'bg-gradient-to-br from-gray-50 to-slate-50 border-gray-200'
  ];
  return classes[index % classes.length];
};

const getCategoryIconClass = (index) => {
  const classes = [
    'p-3 bg-blue-500 bg-opacity-20 rounded-lg text-blue-600',
    'p-3 bg-green-500 bg-opacity-20 rounded-lg text-green-600',
    'p-3 bg-purple-500 bg-opacity-20 rounded-lg text-purple-600',
    'p-3 bg-orange-500 bg-opacity-20 rounded-lg text-orange-600',
    'p-3 bg-red-500 bg-opacity-20 rounded-lg text-red-600',
    'p-3 bg-cyan-500 bg-opacity-20 rounded-lg text-cyan-600',
    'p-3 bg-indigo-500 bg-opacity-20 rounded-lg text-indigo-600',
    'p-3 bg-gray-500 bg-opacity-20 rounded-lg text-gray-600'
  ];
  return classes[index % classes.length];
};

const getCategoryBadgeVariant = (index) => {
  const variants = ['default', 'secondary', 'destructive', 'outline'];
  return variants[index % variants.length];
};

const getCategoryCountClass = (index) => {
  const classes = [
    'text-3xl font-bold text-blue-900',
    'text-3xl font-bold text-green-900',
    'text-3xl font-bold text-purple-900',
    'text-3xl font-bold text-orange-900',
    'text-3xl font-bold text-red-900',
    'text-3xl font-bold text-cyan-900',
    'text-3xl font-bold text-indigo-900',
    'text-3xl font-bold text-gray-900'
  ];
  return classes[index % classes.length];
};

const getCategoryNameClass = (index) => {
  const classes = [
    'text-sm font-medium text-blue-700 mt-1',
    'text-sm font-medium text-green-700 mt-1',
    'text-sm font-medium text-purple-700 mt-1',
    'text-sm font-medium text-orange-700 mt-1',
    'text-sm font-medium text-red-700 mt-1',
    'text-sm font-medium text-cyan-700 mt-1',
    'text-sm font-medium text-indigo-700 mt-1',
    'text-sm font-medium text-gray-700 mt-1'
  ];
  return classes[index % classes.length];
};

const getCategoryLabelClass = (index) => {
  const classes = [
    'text-xs text-blue-600 mt-2',
    'text-xs text-green-600 mt-2',
    'text-xs text-purple-600 mt-2',
    'text-xs text-orange-600 mt-2',
    'text-xs text-red-600 mt-2',
    'text-xs text-cyan-600 mt-2',
    'text-xs text-indigo-600 mt-2',
    'text-xs text-gray-600 mt-2'
  ];
  return classes[index % classes.length];
};

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
