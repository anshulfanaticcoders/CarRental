<template>
    <AdminDashboardLayout>
        <div class="mx-auto py-6 w-[95%]">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">All Categories</h1>
                    <p class="text-sm text-gray-600 mt-1">Manage vehicle categories</p>
                </div>
                <div class="flex items-center gap-4">
                    <Input
                        v-model="search"
                        placeholder="Search categories..."
                        class="w-80"
                        @input="handleSearch"
                    />
                    <Dialog v-model:open="isCreateDialogOpen">
                        <DialogTrigger as-child>
                            <Button>Create New Category</Button>
                        </DialogTrigger>
                        <CreateCategory @close="isCreateDialogOpen = false" />
                    </Dialog>
                </div>
            </div>

            <!-- Bulk Actions Toolbar -->
            <div v-if="selectedCategories.length > 0" class="flex items-center gap-4 p-3 bg-blue-50 rounded-md">
                <span class="text-sm font-medium text-blue-900">
                    {{ selectedCategories.length }} item{{ selectedCategories.length > 1 ? 's' : '' }} selected
                </span>
                <div class="flex gap-2">
                    <Button size="sm" variant="outline" @click="bulkToggleStatus" :disabled="isBulkUpdating">
                        <span v-if="isBulkUpdating" class="animate-spin mr-2">⚪</span>
                        {{ isBulkUpdating ? 'Updating...' : (isAllSelectedActive ? 'Deactivate All' : 'Activate All') }}
                    </Button>
                    <Button size="sm" variant="destructive" @click="openBulkDeleteDialog" :disabled="isBulkDeleting">
                        <span v-if="isBulkDeleting" class="animate-spin mr-2">⚪</span>
                        {{ isBulkDeleting ? 'Deleting...' : 'Delete Selected' }}
                    </Button>
                    <Button size="sm" variant="outline" @click="clearSelection">
                        Clear Selection
                    </Button>
                </div>
            </div>

            <Dialog v-model:open="isEditDialogOpen">
                <EditCategory :user="editForm" @close="isEditDialogOpen = false" />
            </Dialog>

            <Dialog v-model:open="isViewDialogOpen">
                <ViewCategory :user="viewForm" @close="isViewDialogOpen = false" />
            </Dialog>

            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[50px]">
                                <Checkbox
                                    :checked="areAllSelected"
                                    @update:checked="toggleAllSelection"
                                />
                            </TableHead>
                            <TableHead>ID</TableHead>
                            <TableHead>Image</TableHead>
                            <TableHead>Name</TableHead>
                            <TableHead>Description</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Created At</TableHead>
                            <TableHead>Updated At</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(user,index) in users.data" :key="user.id">
                            <TableCell>
                                <Checkbox
                                    :checked="isCategorySelected(user.id)"
                                    @update:checked="(checked) => toggleCategorySelection(user.id, checked)"
                                />
                            </TableCell>
                            <TableCell>{{ (users.current_page - 1) * users.per_page + index + 1 }}</TableCell>
                            <TableCell>
                                <img
                                    :src="user.image || '/placeholder-image.jpg'"
                                    :alt="user.alt_text || user.name"
                                    class="w-16 h-16 rounded-md object-cover border"
                                >
                            </TableCell>
                            <TableCell class="font-medium">{{ user.name }}</TableCell>
                            <TableCell class="text-sm text-gray-600 max-w-xs truncate">{{ user.description }}</TableCell>
                            <TableCell>
                                <div class="flex items-center gap-2">
                                    <Switch
                                        :checked="user.status"
                                        :disabled="loadingStatusToggles.has(user.id)"
                                        @update:checked="(checked) => toggleCategoryStatus(user.id, checked)"
                                    />
                                    <div v-if="loadingStatusToggles.has(user.id)" class="animate-spin w-4 h-4">⚪</div>
                                    <Badge :variant="user.status ? 'default' : 'secondary'">
                                        {{ user.status ? "Active" : "Inactive" }}
                                    </Badge>
                                </div>
                            </TableCell>
                            <TableCell class="text-sm">{{ formatDate(user.created_at) }}</TableCell>
                            <TableCell class="text-sm">{{ formatDate(user.updated_at) }}</TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button size="sm" variant="outline" @click="openViewDialog(user)">
                                        View
                                    </Button>
                                    <Button size="sm" variant="outline" @click="openEditDialog(user)">
                                        Edit
                                    </Button>
                                    <Button size="sm" variant="destructive" @click="openDeleteDialog(user.id)" :disabled="isDeleting">
                                        {{ isDeleting ? 'Deleting...' : 'Delete' }}
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 flex justify-end">
                <Pagination :current-page="users.current_page" :total-pages="users.last_page"
                    @page-change="handlePageChange" />
            </div>

            <!-- Alert Dialog for Delete Confirmation -->
            <AlertDialog v-model:open="isDeleteDialogOpen">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Do you really want to delete this category? This action cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel @click="isDeleteDialogOpen = false">Cancel</AlertDialogCancel>
                        <AlertDialogAction @click="confirmDelete" :disabled="isDeleting">
                        {{ isDeleting ? 'Deleting...' : 'Delete' }}
                    </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            <!-- Alert Dialog for Bulk Delete Confirmation -->
            <AlertDialog v-model:open="isBulkDeleteDialogOpen">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Delete Selected Categories?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Do you really want to delete {{ selectedCategories.length }} selected categor{{ selectedCategories.length > 1 ? 'ies' : 'y' }}? This action cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel @click="isBulkDeleteDialogOpen = false">Cancel</AlertDialogCancel>
                        <AlertDialogAction @click="confirmBulkDelete" class="bg-red-600 hover:bg-red-700" :disabled="isBulkDeleting">
                            <span v-if="isBulkDeleting" class="animate-spin mr-2">⚪</span>
                            {{ isBulkDeleting ? 'Deleting...' : `Delete ${selectedCategories.length} Item${selectedCategories.length > 1 ? 's' : ''}` }}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
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
import { Checkbox } from "@/Components/ui/checkbox";
import { Switch } from "@/Components/ui/switch";
import editIcon from "../../../../assets/Pencil.svg";
import { Dialog, DialogTrigger } from "@/Components/ui/dialog";
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import CreateCategory from "@/Pages/AdminDashboardPages/VehicleCategories/CreateCategory.vue";
import EditCategory from "@/Pages/AdminDashboardPages/VehicleCategories/EditCategory.vue";
import ViewCategory from "@/Pages/AdminDashboardPages/VehicleCategories/ViewCategory.vue";
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
    filters: Object,
});
const search = ref(props.filters.search || ''); // Initialize search with the filter value
const isCreateDialogOpen = ref(false);
const isEditDialogOpen = ref(false);
const isViewDialogOpen = ref(false);
const isDeleteDialogOpen = ref(false);
const isBulkDeleteDialogOpen = ref(false);
const editForm = ref({});
const viewForm = ref({});
const deleteUserId = ref(null);
const selectedCategories = ref([]);

// Loading states
const isDeleting = ref(false);
const isBulkDeleting = ref(false);
const isBulkUpdating = ref(false);
const loadingStatusToggles = ref(new Set());

// Computed properties
const areAllSelected = computed(() => {
    return props.users.data.length > 0 && selectedCategories.value.length === props.users.data.length;
});

const isAllSelectedActive = computed(() => {
    const selectedItems = props.users.data.filter(user => selectedCategories.value.includes(user.id));
    return selectedItems.length > 0 && selectedItems.every(user => user.status);
});

// Bulk operation methods
const isCategorySelected = (categoryId) => {
    return selectedCategories.value.includes(categoryId);
};

const toggleCategorySelection = (categoryId, checked) => {
    if (checked) {
        if (!selectedCategories.value.includes(categoryId)) {
            selectedCategories.value.push(categoryId);
        }
    } else {
        const index = selectedCategories.value.indexOf(categoryId);
        if (index > -1) {
            selectedCategories.value.splice(index, 1);
        }
    }
};

const toggleAllSelection = (checked) => {
    if (checked) {
        selectedCategories.value = props.users.data.map(user => user.id);
    } else {
        selectedCategories.value = [];
    }
};

const clearSelection = () => {
    selectedCategories.value = [];
};

const openBulkDeleteDialog = () => {
    isBulkDeleteDialogOpen.value = true;
};

const confirmBulkDelete = () => {
    isBulkDeleting.value = true;
    router.delete('/vehicles-categories/bulk-delete', {
        data: { ids: selectedCategories.value },
        onSuccess: () => {
            toast.success(`${selectedCategories.value.length} categor${selectedCategories.value.length > 1 ? 'ies' : 'y'} deleted successfully`);
            selectedCategories.value = [];
            isBulkDeleteDialogOpen.value = false;
            isBulkDeleting.value = false;
        },
        onError: (errors) => {
            toast.error('Failed to delete categories');
            isBulkDeleteDialogOpen.value = false;
            isBulkDeleting.value = false;
        }
    });
};

const bulkToggleStatus = () => {
    const newStatus = !isAllSelectedActive.value;
    isBulkUpdating.value = true;
    router.patch('/vehicles-categories/bulk-status', {
        ids: selectedCategories.value,
        status: newStatus
    }, {
        onSuccess: () => {
            toast.success(`${selectedCategories.value.length} categor${selectedCategories.value.length > 1 ? 'ies' : 'y'} ${newStatus ? 'activated' : 'deactivated'} successfully`);
            selectedCategories.value = [];
            isBulkUpdating.value = false;
        },
        onError: (errors) => {
            toast.error('Failed to update category statuses');
            isBulkUpdating.value = false;
        }
    });
};

const toggleCategoryStatus = (categoryId, newStatus) => {
    loadingStatusToggles.value.add(categoryId);
    router.patch(`/vehicles-categories/${categoryId}/status`, {
        status: newStatus
    }, {
        preserveState: true,
        onSuccess: () => {
            toast.success(`Category ${newStatus ? 'activated' : 'deactivated'} successfully`);
            loadingStatusToggles.value.delete(categoryId);
        },
        onError: (errors) => {
            toast.error('Failed to update category status');
            loadingStatusToggles.value.delete(categoryId);
        }
    });
};

// Handle search input
const handleSearch = () => {
    router.get('/vehicles-categories', { search: search.value }, {
        preserveState: true,
        replace: true,
    });
};

const openEditDialog = (user) => {
    editForm.value = { ...user };
    isEditDialogOpen.value = true;
};

const openViewDialog = (user) => {
    viewForm.value = { ...user };
    isViewDialogOpen.value = true;
};

const openDeleteDialog = (id) => {
    deleteUserId.value = id;
    isDeleteDialogOpen.value = true;
};

const confirmDelete = () => {
    isDeleting.value = true;
    router.delete(`/vehicles-categories/${deleteUserId.value}`, {
        onSuccess: () => {
            toast.success('Category deleted successfully');
            isDeleteDialogOpen.value = false;
            isDeleting.value = false;
        },
        onError: (errors) => {
            toast.error('Failed to delete category');
            isDeleting.value = false;
        }
    });
};

const handlePageChange = (page) => {
    router.get(`/vehicles-categories?page=${page}`);
};

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A';
    const date = new Date(dateStr);
    return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`;
};

</script>