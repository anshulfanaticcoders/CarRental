<template>
    <AdminDashboardLayout>
        <div class="container mx-auto p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold tracking-tight">Vehicles Management</h1>
                <div class="flex items-center gap-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-700">
                        <Car class="w-4 h-4 mr-1" />
                        All Vehicles
                    </span>
                </div>
            </div>

            <!-- Enhanced Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Vehicles Card -->
                <div class="relative bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-500 bg-opacity-20 rounded-lg">
                            <Car class="w-6 h-6 text-blue-600" />
                        </div>
                        <Badge variant="secondary" class="bg-blue-500 text-white">
                            Total
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-blue-900">{{ statusCounts?.total || 0 }}</p>
                        <p class="text-sm text-blue-700 mt-1">Total Vehicles</p>
                    </div>
                </div>

                <!-- Available Vehicles Card -->
                <div class="relative bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-green-500 bg-opacity-20 rounded-lg">
                            <CheckCircle class="w-6 h-6 text-green-600" />
                        </div>
                        <Badge variant="secondary" class="bg-green-500 text-white">
                            Available
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-green-900">{{ statusCounts?.available || 0 }}</p>
                        <p class="text-sm text-green-700 mt-1">Available Vehicles</p>
                    </div>
                </div>

                <!-- Rented Vehicles Card -->
                <div class="relative bg-gradient-to-br from-yellow-50 to-yellow-100 border border-yellow-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-yellow-500 bg-opacity-20 rounded-lg">
                            <Calendar class="w-6 h-6 text-yellow-600" />
                        </div>
                        <Badge variant="secondary" class="bg-yellow-500 text-white">
                            Rented
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-yellow-900">{{ statusCounts?.rented || 0 }}</p>
                        <p class="text-sm text-yellow-700 mt-1">Rented Vehicles</p>
                    </div>
                </div>

                <!-- Maintenance Vehicles Card -->
                <div class="relative bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-red-500 bg-opacity-20 rounded-lg">
                            <Wrench class="w-6 h-6 text-red-600" />
                        </div>
                        <Badge variant="secondary" class="bg-red-500 text-white">
                            Maintenance
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-red-900">{{ statusCounts?.maintenance || 0 }}</p>
                        <p class="text-sm text-red-700 mt-1">In Maintenance</p>
                    </div>
                </div>
            </div>

            <!-- Enhanced Search & Filter -->
            <div class="flex flex-col md:flex-row gap-4 items-center justify-center">
                <div class="flex-1 w-full md:w-auto">
                    <div class="relative w-full max-w-md">
                        <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="search"
                            placeholder="Search vehicles by brand, model, owner..."
                            class="pl-10 pr-4 h-12 text-base"
                        />
                    </div>
                </div>
                <div>
                    <Select v-model="statusFilter">
                        <SelectTrigger class="w-40 h-12">
                            <SelectValue placeholder="All Statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Statuses</SelectItem>
                            <SelectItem value="available">Available</SelectItem>
                            <SelectItem value="rented">Rented</SelectItem>
                            <SelectItem value="maintenance">Maintenance</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <Dialog v-model:open="isEditVehicleDialogOpen">
                <EditVehicleDialog :vehicle="editVehicleForm" @close="isEditVehicleDialogOpen = false" />
            </Dialog>

            <Dialog v-model:open="isViewDialogOpen">
                <ViewUser :user="viewForm" @close="isViewDialogOpen = false"/>
            </Dialog>

            <!-- Vehicle Image Preview Dialog -->
            <Dialog v-model:open="isImageModalOpen">
                <DialogContent class="sm:max-w-[80%]">
                    <DialogHeader>
                        <DialogTitle>Vehicle Image</DialogTitle>
                    </DialogHeader>
                    <div class="flex justify-center">
                        <img :src="selectedImage" alt="Vehicle Image" class="max-w-full max-h-[80vh] object-contain">
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="isImageModalOpen = false">Close</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Enhanced Vehicles Table -->
            <div v-if="users.data.length > 0" class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <div class="overflow-x-auto max-w-full">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">ID</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Image</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Owner</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Vehicle</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Location</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Price</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Date Added</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Status</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(vehicle,index) in users.data" :key="vehicle.id" class="hover:bg-muted/25 transition-colors">
                                <TableCell class="whitespace-nowrap px-4 py-3 font-medium">
                                    {{ (users.current_page - 1) * users.per_page + index + 1 }}
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <!-- Vehicle Image -->
                                    <div v-if="vehicle.images && vehicle.images.length > 0" class="relative group cursor-pointer" @click="openImageModal(vehicle.images[0].image_url)">
                                        <img
                                            :src="vehicle.images[0].image_url"
                                            :alt="`${vehicle.brand} ${vehicle.model}`"
                                            class="w-20 h-16 object-cover rounded-lg border border-gray-200 hover:border-blue-400 transition-all pointer-events-none"
                                        />
                                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 rounded-lg transition-all flex items-center justify-center">
                                            <Image class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition-opacity" />
                                        </div>
                                    </div>
                                    <div v-else class="w-20 h-16 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400 text-sm">
                                        No Image
                                    </div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="font-medium">{{ vehicle.User?.first_name }} {{ vehicle.User?.last_name }}</div>
                                    <div class="text-sm text-muted-foreground">Owner ID: {{ vehicle.vendor_id }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="font-medium">{{ vehicle.brand }} {{ vehicle.model }}</div>
                                    <div class="text-sm text-muted-foreground">{{ vehicle.color || 'N/A' }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm">
                                        <div>{{ vehicle.city || 'N/A' }}, {{ vehicle.country || 'N/A' }}</div>
                                        <div class="text-muted-foreground text-xs truncate max-w-[150px]">{{ vehicle.full_vehicle_address }}</div>
                                    </div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm font-medium">
                                        <template v-if="vehicle.price_per_day || vehicle.price_per_week || vehicle.price_per_month">
                                            <div v-if="vehicle.price_per_day" class="text-green-600">
                                                {{ vehicle.vendor_profile?.currency || '$' }}{{ vehicle.price_per_day }}/Day
                                            </div>
                                            <div v-if="vehicle.price_per_week" class="text-blue-600">
                                                {{ vehicle.vendor_profile?.currency || '$' }}{{ vehicle.price_per_week }}/Week
                                            </div>
                                            <div v-if="vehicle.price_per_month" class="text-purple-600">
                                                {{ vehicle.vendor_profile?.currency || '$' }}{{ vehicle.price_per_month }}/Month
                                            </div>
                                        </template>
                                        <span v-else class="text-muted-foreground">Not Set</span>
                                    </div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">{{ formatDate(vehicle.created_at) }}</TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Badge :variant="getStatusBadgeVariant(vehicle.status)" class="capitalize">
                                        {{ vehicle.status }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <Button size="sm" variant="outline" @click="openViewDialog(vehicle)" class="flex items-center gap-1">
                                            <Eye class="w-3 h-3" />
                                            View
                                        </Button>
                                        <Button size="sm" variant="outline" @click="openEditVehicleDialog(vehicle)" class="flex items-center gap-1">
                                            <Edit class="w-3 h-3" />
                                            Edit
                                        </Button>
                                        <Button size="sm" variant="destructive" @click="openDeleteDialog(vehicle.id)" class="flex items-center gap-1">
                                            <Trash2 class="w-3 h-3" />
                                            Delete
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                <div class="flex justify-end pt-4 pr-2">
                    <Pagination :current-page="users.current_page" :total-pages="users.last_page"
                        @page-change="handlePageChange" />
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="rounded-xl border bg-card p-12 text-center">
                <div class="flex flex-col items-center space-y-4">
                    <Car class="w-16 h-16 text-muted-foreground" />
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold text-foreground">No vehicles found</h3>
                        <p class="text-muted-foreground">No vehicles match your current search criteria.</p>
                    </div>
                </div>
            </div>

            <!-- Alert Dialog for Delete Confirmation -->
            <AlertDialog v-model:open="isDeleteDialogOpen">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Do you really want to delete this vehicle? This action cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel @click="isDeleteDialogOpen = false">Cancel</AlertDialogCancel>
                        <AlertDialogAction @click="confirmDelete" :disabled="isDeleting">
                            <span v-if="isDeleting" class="flex items-center gap-2">
                                <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                Deleting...
                            </span>
                            <span v-else>Delete</span>
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, watch } from "vue";
import { router, Link } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import Table from "@/Components/ui/table/Table.vue";
import TableHeader from "@/Components/ui/table/TableHeader.vue";
import TableRow from "@/Components/ui/table/TableRow.vue";
import TableHead from "@/Components/ui/table/TableHead.vue";
import TableBody from "@/Components/ui/table/TableBody.vue";
import TableCell from "@/Components/ui/table/TableCell.vue";
import Button from "@/Components/ui/button/Button.vue";
import Badge from "@/Components/ui/badge/Badge.vue";
import { Input } from "@/Components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';
import {
  Car,
  CheckCircle,
  Calendar,
  Wrench,
  Search,
  Eye,
  Edit,
  Trash2,
  Image
} from 'lucide-vue-next';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogTrigger } from "@/Components/ui/dialog";
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import ViewUser from "@/Pages/AdminDashboardPages/Vehicles/ViewUser.vue";
import EditVehicleDialog from "@/Pages/AdminDashboardPages/Vehicles/EditVehicleDialog.vue";
import Pagination from '@/Components/ReusableComponents/Pagination.vue';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/Components/ui/alert-dialog'

const props = defineProps({
    users: Object,
    statusCounts: Object,
    filters: Object,
});

// Format date
const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const search = ref(props.filters.search || ''); // Initialize search with the filter value
const statusFilter = ref(props.filters?.status || 'all'); // Initialize status filter
const isViewDialogOpen = ref(false);
const isEditVehicleDialogOpen = ref(false);
const isImageModalOpen = ref(false);
const isDeleteDialogOpen = ref(false);
const isDeleting = ref(false);
const selectedImage = ref('');
const viewForm = ref({});
const editVehicleForm = ref({});
const deleteUserId = ref(null);

// Handle search input
const handleSearch = () => {
    const params = {
        search: search.value
    };

    // Only add status parameter if it's not "all"
    if (statusFilter.value && statusFilter.value !== 'all') {
        params.status = statusFilter.value;
    }

    router.get(route('admin.vehicles.index'), params, {
        preserveState: true,
        replace: true,
    });
};

// Filter by status
const filterByStatus = () => {
    const params = {
        search: search.value
    };

    // Only add status parameter if it's not "all"
    if (statusFilter.value && statusFilter.value !== 'all') {
        params.status = statusFilter.value;
    }

    router.get(route('admin.vehicles.index'), params, {
        preserveState: true,
        replace: true,
    });
};

// Watch for search query changes
watch(search, (newValue) => {
    handleSearch();
});

// Watch for status filter changes
watch(statusFilter, (newValue) => {
    filterByStatus();
});

const openViewDialog = (user) => {
    viewForm.value = { ...user };
    isViewDialogOpen.value = true;
};

const openEditVehicleDialog = (vehicle) => {
    editVehicleForm.value = { ...vehicle }; // Use a deep copy if vehicle object is complex
    isEditVehicleDialogOpen.value = true;
};

const openImageModal = (imageUrl) => {
    selectedImage.value = imageUrl;
    isImageModalOpen.value = true;
};

const openDeleteDialog = (id) => {
    deleteUserId.value = id;
    isDeleteDialogOpen.value = true;
};

const confirmDelete = () => {
    isDeleting.value = true;
    router.delete(route('admin.vehicles.destroy', { vendor_vehicle: deleteUserId.value }), {
        onSuccess: () => {
            toast.success('Vehicle deleted successfully');
            isDeleteDialogOpen.value = false;
            isDeleting.value = false;
        },
        onError: (errors) => {
            toast.error('Failed to delete vehicle');
            isDeleting.value = false;
        }
    });
};

const handlePageChange = (page) => {
    const params = {
        page: page,
        search: search.value
    };

    // Only add status parameter if it's not "all"
    if (statusFilter.value && statusFilter.value !== 'all') {
        params.status = statusFilter.value;
    }

    router.get(route('admin.vehicles.index'), params, {
        preserveState: true,
        replace: true,
    });
};

const getStatusBadgeVariant = (status) => {
    switch (status) {
        case 'available':
            return 'default';
        case 'rented':
            return 'secondary';
        case 'maintenance':
            return 'destructive';
        default:
            return 'default';
    }
};
</script>
