<template>
    <AdminDashboardLayout>
        <div class="container mx-auto p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Addons Management</h1>
                    <p class="text-sm text-muted-foreground mt-1">Manage vehicle booking addons</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="relative w-full max-w-md">
                        <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="search"
                            placeholder="Search addons by type, name..."
                            class="pl-10 pr-4 h-12 text-base"
                            @input="handleSearch"
                        />
                    </div>
                    <Dialog v-model:open="isCreateDialogOpen">
                        <DialogTrigger as-child>
                            <Button class="flex items-center gap-2">
                                <Plus class="w-4 h-4" />
                                Create New Addon
                            </Button>
                        </DialogTrigger>
                        <CreateUser @close="isCreateDialogOpen = false" />
                    </Dialog>
                </div>
            </div>

            <Dialog v-model:open="isEditDialogOpen">
                <EditUser :user="editForm" @close="isEditDialogOpen = false" />
            </Dialog>

            <Dialog v-model:open="isViewDialogOpen">
                <ViewUser :user="viewForm" @close="isViewDialogOpen = false" />
            </Dialog>

            <!-- Enhanced Addons Table -->
            <div v-if="users.data.length > 0" class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <div class="overflow-x-auto max-w-full">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">ID</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Extra Type</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Extra Name</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Description</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Quantity</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Price</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(user, index) in users.data" :key="user.id" class="hover:bg-muted/25 transition-colors">
                                <TableCell class="whitespace-nowrap px-4 py-3 font-medium">
                                    {{ (users.current_page - 1) * users.per_page + index + 1 }}
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Badge variant="secondary" class="capitalize">
                                        {{ user.extra_type }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 font-medium">
                                    {{ user.extra_name }}
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3 max-w-xs truncate">
                                    {{ user.description || 'N/A' }}
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ user.quantity }}
                                    </span>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <span class="font-semibold text-green-600">
                                        ${{ Number(user.price).toFixed(2) }}
                                    </span>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <Button size="sm" variant="outline" @click="openViewDialog(user)" class="flex items-center gap-1">
                                            <Eye class="w-3 h-3" />
                                            View
                                        </Button>
                                        <Button size="sm" variant="outline" @click="openEditDialog(user)" class="flex items-center gap-1">
                                            <Edit class="w-3 h-3" />
                                            Edit
                                        </Button>
                                        <Button size="sm" variant="destructive" @click="openDeleteDialog(user.id)" class="flex items-center gap-1">
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
                    <Package class="w-16 h-16 text-muted-foreground" />
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold text-foreground">No addons found</h3>
                        <p class="text-muted-foreground">Get started by creating your first addon.</p>
                    </div>
                    <Dialog v-model:open="isCreateDialogOpen">
                        <DialogTrigger as-child>
                            <Button class="flex items-center gap-2">
                                <Plus class="w-4 h-4" />
                                Create New Addon
                            </Button>
                        </DialogTrigger>
                        <CreateUser @close="isCreateDialogOpen = false" />
                    </Dialog>
                </div>
            </div>

            <!-- Alert Dialog for Delete Confirmation -->
            <AlertDialog v-model:open="isDeleteDialogOpen">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Do you really want to delete this addon? This action cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel @click="isDeleteDialogOpen = false">Cancel</AlertDialogCancel>
                        <AlertDialogAction @click="confirmDelete" :disabled="isDeleting" class="bg-red-600 hover:bg-red-700">
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
import { ref } from "vue";
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
import { Plus, Search, Eye, Edit, Trash2, Package } from 'lucide-vue-next';
import { Dialog, DialogTrigger } from "@/Components/ui/dialog";
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import CreateUser from "@/Pages/AdminDashboardPages/VehicleAddons/CreateUser.vue";
import EditUser from "@/Pages/AdminDashboardPages/VehicleAddons/EditUser.vue";
import ViewUser from "@/Pages/AdminDashboardPages/VehicleAddons/ViewUser.vue";
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
const search = ref(props.filters.search || '');
const isCreateDialogOpen = ref(false);
const isEditDialogOpen = ref(false);
const isViewDialogOpen = ref(false);
const isDeleteDialogOpen = ref(false);
const isDeleting = ref(false);
const editForm = ref({});
const viewForm = ref({});
const deleteUserId = ref(null);

const handleSearch = () => {
    router.get('/booking-addons', { search: search.value }, {
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
    router.delete(`/booking-addons/${deleteUserId.value}`, {
        onSuccess: () => {
            toast.success('Addon deleted successfully');
            isDeleteDialogOpen.value = false;
            isDeleting.value = false;
        },
        onError: (errors) => {
            toast.error('Failed to delete addon');
            isDeleting.value = false;
        }
    });
};

const handlePageChange = (page) => {
    router.get(`/booking-addons?page=${page}`);
};
</script>
