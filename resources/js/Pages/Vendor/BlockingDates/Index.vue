<template>
    <MyProfileLayout>
       <div class="p-0 md:p-0 lg:p-6 space-y-6">
            <!-- Enhanced Header -->
            <div class="bg-gradient-to-r from-orange-50 to-amber-50 border border-orange-100 rounded-xl p-6 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-orange-500 bg-opacity-20 rounded-lg">
                        <CalendarX class="w-6 h-6 text-orange-600" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ _t('vendorprofilepages', 'manage_blocking_dates_header') }}</h1>
                        <p class="text-sm text-gray-600 mt-1">Manage vehicle blocking dates and availability</p>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div>
                <div class="rounded-xl border bg-card shadow-sm p-6">
                    <!-- Search Bar -->
                    <div class="relative max-w-md mb-4">
                        <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                        <Input
                            v-model="searchQuery"
                            @input="debouncedSearch"
                            type="text"
                            :placeholder="_t('vendorprofilepages', 'search_vehicles_placeholder')"
                            class="pl-10 w-full"
                        />
                    </div>

                    <!-- Filter Controls -->
                    <div class="flex flex-wrap gap-4 items-end">
                        <!-- Filter By Status -->
                        <div class="min-w-0 flex-1 max-w-xs">
                            <Label class="flex items-center gap-2 text-sm font-medium mb-2">
                                <Filter class="w-4 h-4" />
                                Filter By
                            </Label>
                            <Select v-model="filterBy" @update:modelValue="onFilterChange">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Select filter" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Vehicles</SelectItem>
                                    <SelectItem value="available">Available Only</SelectItem>
                                    <SelectItem value="blocked">Currently Blocked</SelectItem>
                                    <SelectItem value="booked">With Bookings</SelectItem>
                                    <SelectItem value="active_blockings">Active Blockings</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Sort By -->
                        <div class="min-w-0 flex-1 max-w-xs">
                            <Label class="flex items-center gap-2 text-sm font-medium mb-2">
                                <ArrowUpDown class="w-4 h-4" />
                                Sort By
                            </Label>
                            <Select v-model="sortBy" @update:modelValue="onSortChange">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Sort by" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="id">Vehicle ID</SelectItem>
                                    <SelectItem value="brand">Brand</SelectItem>
                                    <SelectItem value="model">Model</SelectItem>
                                    <SelectItem value="created_at">Date Added</SelectItem>
                                    <SelectItem value="active_blockings_count">Active Blockings Count</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Sort Order Toggle -->
                        <div class="flex items-end">
                            <Button
                                variant="outline"
                                size="sm"
                                @click="toggleSortOrder"
                                class="flex items-center gap-2"
                            >
                                <ArrowUp v-if="sortOrder === 'asc'" class="w-4 h-4" />
                                <ArrowDown v-else class="w-4 h-4" />
                                {{ sortOrder === 'asc' ? 'A-Z' : 'Z-A' }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Blocking Dates Statistics Cards -->
            <div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Total Vehicles Card -->
                    <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl p-6 text-white shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-100 text-sm font-medium">Total Vehicles</p>
                                <p class="text-2xl font-bold mt-1">
                                    {{ totalVehicles }}
                                </p>
                            </div>
                            <div class="p-3 bg-white bg-opacity-20 rounded-lg">
                                <Car class="w-6 h-6" />
                            </div>
                        </div>
                    </div>

                    <!-- Active Blockings Card -->
                    <div class="bg-gradient-to-br from-red-500 to-pink-600 rounded-xl p-6 text-white shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-red-100 text-sm font-medium">Active Blockings</p>
                                <p class="text-2xl font-bold mt-1">
                                    {{ activeBlockings }}
                                </p>
                            </div>
                            <div class="p-3 bg-white bg-opacity-20 rounded-lg">
                                <CalendarX class="w-6 h-6" />
                            </div>
                        </div>
                    </div>

                    <!-- Vehicles with Bookings Card -->
                    <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-6 text-white shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-100 text-sm font-medium">With Bookings</p>
                                <p class="text-2xl font-bold mt-1">
                                    {{ vehiclesWithBookings }}
                                </p>
                            </div>
                            <div class="p-3 bg-white bg-opacity-20 rounded-lg">
                                <CalendarCheck class="w-6 h-6" />
                            </div>
                        </div>
                    </div>

                    <!-- Available Vehicles Card -->
                    <div class="bg-gradient-to-br from-teal-500 to-cyan-600 rounded-xl p-6 text-white shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-teal-100 text-sm font-medium">Available</p>
                                <p class="text-2xl font-bold mt-1">
                                    {{ availableVehicles }}
                                </p>
                            </div>
                            <div class="p-3 bg-white bg-opacity-20 rounded-lg">
                                <CheckCircle class="w-6 h-6" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Vehicles -->
            <div v-if="!filteredVehicles.length">
                <div class="rounded-xl border bg-card p-12 text-center">
                    <div class="flex flex-col items-center space-y-4">
                        <CalendarX class="w-16 h-16 text-muted-foreground" />
                        <div class="space-y-2">
                            <h3 class="text-xl font-semibold text-foreground">No vehicles found</h3>
                            <p class="text-muted-foreground">{{ _t('vendorprofilepages', 'no_vehicles_found_text') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vehicles Table -->
            <div v-else>
                <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-[60px]">#</TableHead>
                                <TableHead>ID</TableHead>
                                <TableHead>Vehicle</TableHead>
                                <TableHead>Location</TableHead>
                                <TableHead>Booking Dates</TableHead>
                                <TableHead>Blocking Periods</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(vehicle, index) in filteredVehicles" :key="vehicle.id" class="hover:bg-muted/50 transition-colors">
                                <TableCell class="font-medium text-muted-foreground">
                                    {{ (vehicles.current_page - 1) * vehicles.per_page + index + 1 }}
                                </TableCell>
                                <TableCell>
                                    <div class="font-mono text-sm">#{{ vehicle.id }}</div>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-3">
                                        <div class="relative">
                                            <img
                                                :src="getPrimaryImage(vehicle)"
                                                :alt="vehicle.brand"
                                                class="h-12 w-16 object-cover rounded-lg"
                                            />
                                            <div v-if="vehicle.featured" class="absolute -top-1 -right-1">
                                                <Badge variant="secondary" class="text-xs">Featured</Badge>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="font-medium">{{ vehicle.brand }} {{ vehicle.model }}</div>
                                            <div class="text-sm text-muted-foreground" v-if="vehicle.category">
                                                {{ vehicle.category.name }}
                                            </div>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="max-w-xs truncate">
                                        <div class="flex items-center gap-1">
                                            <MapPin class="w-4 h-4 text-muted-foreground" />
                                            <span class="text-sm">{{ vehicle.full_vehicle_address || 'No address' }}</span>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div v-if="vehicle.bookings && vehicle.bookings.length > 0" class="space-y-1">
                                        <div v-for="(booking, index) in vehicle.bookings.slice(0, 2)" :key="booking.id" class="flex items-center gap-1 text-sm">
                                            <Badge variant="outline" class="text-xs">
                                                {{ formatDate(booking.pickup_date) }} - {{ formatDate(booking.return_date) }}
                                            </Badge>
                                        </div>
                                        <div v-if="vehicle.bookings.length > 2">
                                            <Dialog>
                                                <DialogTrigger as-child>
                                                    <Button variant="link" class="p-0 h-auto text-xs text-blue-600 hover:underline">
                                                        +{{ vehicle.bookings.length - 2 }} more
                                                    </Button>
                                                </DialogTrigger>
                                                <DialogContent class="max-w-md">
                                                    <DialogHeader>
                                                        <DialogTitle>All Booking Dates</DialogTitle>
                                                    </DialogHeader>
                                                    <div class="max-h-60 overflow-y-auto space-y-2">
                                                        <div v-for="booking in vehicle.bookings" :key="booking.id" class="flex items-center gap-2">
                                                            <Badge variant="outline" class="text-xs">
                                                                {{ formatDate(booking.pickup_date) }} - {{ formatDate(booking.return_date) }}
                                                            </Badge>
                                                        </div>
                                                    </div>
                                                </DialogContent>
                                            </Dialog>
                                        </div>
                                    </div>
                                    <span v-else class="text-sm text-muted-foreground">No bookings</span>
                                </TableCell>
                                <TableCell>
                                    <div v-if="vehicle.blockings && vehicle.blockings.length > 0" class="space-y-1">
                                        <div v-for="(blocking, index) in vehicle.blockings" :key="blocking.id">
                                            <Badge :variant="isBlockingActive(blocking) ? 'destructive' : 'secondary'" class="text-xs">
                                                {{ formatDate(blocking.blocking_start_date) }} - {{ formatDate(blocking.blocking_end_date) }}
                                            </Badge>
                                        </div>
                                    </div>
                                    <span v-else class="text-sm text-muted-foreground">No blockings</span>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <Badge :variant="getVehicleStatusBadge(vehicle)" class="capitalize">
                                            {{ getVehicleStatus(vehicle) }}
                                        </Badge>
                                        <div v-if="vehicle.featured">
                                            <Badge variant="secondary" class="text-xs">Featured</Badge>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button size="sm" class="flex items-center gap-2" @click="openManageDialog(vehicle)">
                                        <CalendarX class="w-4 h-4" />
                                        {{ vehicle.blockings && vehicle.blockings.length > 0 ? 'Manage' : 'Add Blocking' }}
                                    </Button>
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

            <!-- Manage Blocking Dates Dialog -->
            <Dialog v-model:open="isManageDialogOpen">
                <DialogContent class="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>{{ selectedVehicle?.blockings && selectedVehicle.blockings.length > 0 ? 'Manage Blocking Dates' : 'Add Blocking Date' }}</DialogTitle>
                        <DialogDescription>
                            {{ selectedVehicle?.brand }} {{ selectedVehicle?.model }} - #{{ selectedVehicle?.id }}
                        </DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="submitForm">
                        <div class="dialog-form-section">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label for="start_date">Start Date</Label>
                                    <VueDatepicker v-model="form.blocking_start_date" :min-date="new Date()"
                                        :disabled-dates="getDisabledDates()" :day-class="getDayClass"
                                        placeholder="Select start date" class="dp-input-custom"
                                        :enable-time-picker="false" :clearable="true"
                                        :format="'yyyy-MM-dd'" auto-apply />
                                </div>
                                <div class="space-y-2">
                                    <Label for="end_date">End Date</Label>
                                    <VueDatepicker v-model="form.blocking_end_date"
                                        :min-date="form.blocking_start_date || new Date()"
                                        :disabled-dates="getDisabledDates()" :day-class="getDayClass"
                                        placeholder="Select end date" class="dp-input-custom"
                                        :enable-time-picker="false" :clearable="true"
                                        :format="'yyyy-MM-dd'" auto-apply />
                                </div>
                            </div>
                        </div>
                        <div class="dialog-button-section">
                            <Button type="button" variant="outline" @click="isManageDialogOpen = false">
                                Cancel
                            </Button>
                            <Button type="submit" :disabled="form.processing" class="flex items-center gap-2">
                                <CalendarX v-if="!form.processing" class="w-4 h-4" />
                                <Loader2 v-else class="w-4 h-4 animate-spin" />
                                {{ form.processing ? 'Saving...' : 'Add Blocking Date' }}
                            </Button>
                        </div>
                    </form>

                    <!-- Existing Blocking Dates -->
                    <div v-if="selectedVehicle?.blockings && selectedVehicle.blockings.length > 0" class="mt-6 pt-6 border-t">
                        <h3 class="text-lg font-semibold mb-4">Existing Blocking Dates</h3>
                        <div class="space-y-2">
                            <div v-for="blocking in selectedVehicle.blockings" :key="blocking.id" class="flex items-center justify-between p-3 bg-muted/30 rounded-lg">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <Badge :variant="isBlockingActive(blocking) ? 'destructive' : 'secondary'" class="text-xs">
                                            {{ formatDate(blocking.blocking_start_date) }} - {{ formatDate(blocking.blocking_end_date) }}
                                        </Badge>
                                        <span v-if="isBlockingActive(blocking)" class="text-xs text-red-600 font-medium">
                                            (Active)
                                        </span>
                                    </div>
                                </div>
                                <Button
                                    @click="showDeleteDialog(blocking.id)"
                                    variant="destructive"
                                    size="sm"
                                    class="flex items-center gap-1"
                                >
                                    <Trash2 class="w-3 h-3" />
                                    Remove
                                </Button>
                            </div>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

            <!-- Delete Confirmation Dialog -->
            <Dialog v-model:open="isDeleteDialogOpen">
                <DialogContent class="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Remove Blocking Date</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to remove this blocking date? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isDeleteDialogOpen = false">
                            Cancel
                        </Button>
                        <Button variant="destructive" @click="removeBlockingDate">
                            Remove Blocking Date
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
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
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import {
    Table,
    TableHeader,
    TableRow,
    TableHead,
    TableBody,
    TableCell
} from "@/Components/ui/table";
import Pagination from '@/Pages/Vendor/BlockingDates/Pagination.vue';
import {
    CalendarX,
    Search,
    Car,
    CalendarCheck,
    CheckCircle,
    MapPin,
    Loader2,
    Trash2,
    AlertTriangle,
    Filter,
    ArrowUpDown,
    ArrowUp,
    ArrowDown,
} from 'lucide-vue-next';
import VueDatepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;

const toast = useToast();
const today = new Date().toISOString().split('T')[0];
const form = ref({
    vehicle_id: '',
    blocking_start_date: '',
    blocking_end_date: '',
    processing: false
});
const props = defineProps({
    vehicles: {
        type: Object,
        required: true
    },
    statistics: {
        type: Object,
        required: true
    },
    filters: {
        type: Object,
        required: true
    },
});

// Initialize filter values from props
const searchQuery = ref(props.filters.search || '');
const filterBy = ref(props.filters.filter_by || 'all');
const sortBy = ref(props.filters.sort_by || 'id');
const sortOrder = ref(props.filters.sort_order || 'desc');

// Apply filters function
const applyFilters = () => {
    router.get(route('vendor.blocking-dates.index', { locale: usePage().props.locale }),
        {
            search: searchQuery.value,
            filter_by: filterBy.value,
            sort_by: sortBy.value,
            sort_order: sortOrder.value,
            page: 1
        },
        { preserveState: true, preserveScroll: true }
    );
};

const handlePageChange = (page) => {
    router.get(route('vendor.blocking-dates.index', { locale: usePage().props.locale }),
        {
            search: searchQuery.value,
            filter_by: filterBy.value,
            sort_by: sortBy.value,
            sort_order: sortOrder.value,
            page
        },
        { preserveState: true, preserveScroll: true }
    );
};

// Simple debounce function
const debounce = (func, wait) => {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

// Search functionality
const debouncedSearch = debounce(() => {
    applyFilters();
}, 300);

// Filter change handlers
const onFilterChange = () => {
    applyFilters();
};

const onSortChange = () => {
    applyFilters();
};

const toggleSortOrder = () => {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    applyFilters();
};

// Dialog state management
const isManageDialogOpen = ref(false);
const selectedVehicle = ref(null);

const openManageDialog = (vehicle) => {
    selectedVehicle.value = vehicle;
    isManageDialogOpen.value = true;
    // Reset form
    form.value = { vehicle_id: '', blocking_start_date: '', blocking_end_date: '', processing: false };
};

const submitForm = async () => {
    // Form validation
    if (!form.value.blocking_start_date) {
        toast.error(_t('vendorprofilepages', 'toast_error_select_start_date'));
        return;
    }

    if (!form.value.blocking_end_date) {
        toast.error(_t('vendorprofilepages', 'toast_error_select_end_date'));
        return;
    }

    form.value.processing = true;

    try {
        await axios.post(route('vendor.blocking-dates.store', { locale: usePage().props.locale }), {
            vehicle_id: selectedVehicle.value.id,
            blocking_start_date: form.value.blocking_start_date,
            blocking_end_date: form.value.blocking_end_date
        });
        toast.success(_t('vendorprofilepages', 'toast_success_blocking_date_added'));

        // Reset form and close dialog
        form.value = { vehicle_id: '', blocking_start_date: '', blocking_end_date: '', processing: false };
        isManageDialogOpen.value = false;
        selectedVehicle.value = null;

        // Reload the page to see changes
        window.location.reload();
    } catch (error) {
        console.error('Error:', error.response?.data || error);
        toast.error(error.response?.data?.message || _t('vendorprofilepages', 'toast_error_add_blocking_date'));
        form.value.processing = false;
    }
};

const prepareEditForm = (vehicle) => {
    // This function is now used to open the dialog for a specific vehicle
};

// Dialog state for blocking date removal
const isDeleteDialogOpen = ref(false);
const blockingToDelete = ref(null);

const showDeleteDialog = (blockingId) => {
    blockingToDelete.value = blockingId;
    isDeleteDialogOpen.value = true;
};

const removeBlockingDate = async () => {
    if (!blockingToDelete.value) return;

    try {
        await axios.delete(route('vendor.blocking-dates.destroy', { locale: usePage().props.locale, 'blocking_date': blockingToDelete.value }));
        toast.success(_t('vendorprofilepages', 'toast_success_blocking_date_removed'));
        isDeleteDialogOpen.value = false;
        blockingToDelete.value = null;
        // Reload the page to see changes
        window.location.reload();
    } catch (error) {
        toast.error(_t('vendorprofilepages', 'toast_error_remove_blocking_date'));
        isDeleteDialogOpen.value = false;
        blockingToDelete.value = null;
    }
};

const filteredVehicles = computed(() => {
    return props.vehicles.data;
});

// Statistics from controller (totals across all pages)
const totalVehicles = computed(() => props.statistics.totalVehicles || 0);
const activeBlockings = computed(() => props.statistics.activeBlockings || 0);
const vehiclesWithBookings = computed(() => props.statistics.vehiclesWithBookings || 0);
const availableVehicles = computed(() => props.statistics.availableVehicles || 0);

const getPrimaryImage = (vehicle) => {
    const primaryImage = vehicle.images?.find(img => img.image_type === 'primary');
    return primaryImage ? primaryImage.image_url : '/images/placeholder.jpg';
};

const isBlockingActive = (blocking) => {
    const today = new Date();
    const blockingStart = new Date(blocking.blocking_start_date);
    const blockingEnd = new Date(blocking.blocking_end_date);
    return blockingStart <= today && blockingEnd >= today;
};

const getVehicleStatus = (vehicle) => {
    const hasActiveBlocking = vehicle.blockings?.some(blocking => isBlockingActive(blocking));
    const hasBookings = vehicle.bookings && vehicle.bookings.length > 0;

    if (hasActiveBlocking) return 'Blocked';
    if (hasBookings) return 'Booked';
    return 'Available';
};

const getVehicleStatusBadge = (vehicle) => {
    const status = getVehicleStatus(vehicle);
    switch (status) {
        case 'Blocked': return 'destructive';
        case 'Booked': return 'secondary';
        case 'Available': return 'default';
        default: return 'outline';
    }
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
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: '2-digit'
    });
};

// Functions for datepicker
const getDisabledDates = () => {
    const disabledDates = [];
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // Add past dates
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    disabledDates.push(new Date(2000, 0, 1), yesterday);

    // Add booked dates
    if (selectedVehicle.value?.bookings) {
        selectedVehicle.value.bookings.forEach(booking => {
            const start = new Date(booking.pickup_date);
            const end = new Date(booking.return_date);
            for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
                disabledDates.push(new Date(d));
            }
        });
    }

    // Add blocked dates
    if (selectedVehicle.value?.blockings) {
        selectedVehicle.value.blockings.forEach(blocking => {
            const start = new Date(blocking.blocking_start_date);
            const end = new Date(blocking.blocking_end_date);
            for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
                disabledDates.push(new Date(d));
            }
        });
    }

    return disabledDates;
};

const getDayClass = (date) => {
    if (!selectedVehicle.value) return '';

    const dateStr = date.toISOString().split('T')[0];

    // Check if date is booked
    const isBooked = selectedVehicle.value.bookings?.some(booking => {
        const start = new Date(booking.pickup_date);
        const end = new Date(booking.return_date);
        return date >= start && date <= end;
    });

    // Check if date is blocked
    const isBlocked = selectedVehicle.value.blockings?.some(blocking => {
        const start = new Date(blocking.blocking_start_date);
        const end = new Date(blocking.blocking_end_date);
        return date >= start && date <= end;
    });

    if (isBooked) return 'booked-date';
    if (isBlocked) return 'blocked-date';
    return '';
};


// Initialize filter values from URL params
onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    searchQuery.value = urlParams.get('search') || '';
    filterBy.value = urlParams.get('filter_by') || 'all';
    sortBy.value = urlParams.get('sort_by') || 'id';
    sortOrder.value = urlParams.get('sort_order') || 'desc';
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

/* Datepicker input fixes */
.dp-input-custom {
    width: 100%;
}

:deep(.dp-input-custom .dp__input) {
    width: 100% !important;
    padding: 8px 12px 8px 40px !important;
    border: 1px solid #d1d5db !important;
    border-radius: 6px !important;
    font-size: 14px !important;
    transition: border-color 0.2s ease !important;
}

:deep(.dp-input-custom .dp__input:focus) {
    outline: none !important;
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
}

:deep(.dp-input-custom .dp__input:hover) {
    border-color: #9ca3af !important;
}

/* Vue Datepicker custom styles */
:deep(.dp__main) {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

:deep(.dp__active_date) {
    background-color: #3b82f6 !important;
    color: white !important;
    border-color: #3b82f6 !important;
}

/* Booked dates (red) */
:deep(.booked-date) {
    background-color: #ef4444 !important;
    color: white !important;
    border: 2px solid #dc2626 !important;
    border-radius: 6px !important;
    font-weight: 600 !important;
    position: relative;
}

:deep(.booked-date::after) {
    content: '📅';
    position: absolute;
    top: 2px;
    right: 2px;
    font-size: 10px;
}

/* Blocked dates (orange) */
:deep(.blocked-date) {
    background-color: #f97316 !important;
    color: white !important;
    border: 2px solid #ea580c !important;
    border-radius: 6px !important;
    font-weight: 600 !important;
    position: relative;
}

:deep(.blocked-date::after) {
    content: '🚫';
    position: absolute;
    top: 2px;
    right: 2px;
    font-size: 10px;
}

/* Disabled dates styling */
:deep(.dp__cell_disabled) {
    background-color: #f3f4f6 !important;
    color: #9ca3af !important;
    cursor: not-allowed !important;
    opacity: 0.6;
    border-radius: 6px !important;
}

:deep(.dp__cell_disabled:hover) {
    background-color: #f3f4f6 !important;
}

/* Dialog spacing fixes */
:deep(.dialog-content) {
    padding: 24px !important;
}

.dialog-form-section {
    margin-bottom: 24px;
}

.dialog-button-section {
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid #e5e7eb;
}
</style>
