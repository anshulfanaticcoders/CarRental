<template>
    <AdminDashboardLayout>
        <div class="container mx-auto p-6 space-y-6">
            <!-- Flash Message -->
            <div v-if="$page.props.flash.success" class="rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">
                {{ $page.props.flash.success }}
            </div>

            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold tracking-tight">Users Management</h1>
                <div class="flex items-center gap-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-700">
                        <Users class="w-4 h-4 mr-1" />
                        All Users
                    </span>
                    <Dialog v-model:open="isCreateDialogOpen">
                        <DialogTrigger as-child>
                            <Button class="flex items-center gap-2">
                                <Shield class="w-4 h-4" />
                                Create New User
                            </Button>
                        </DialogTrigger>
                        <CreateUser @close="isCreateDialogOpen = false" />
                    </Dialog>
                </div>
            </div>

            <!-- Enhanced Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Users Card -->
                <div class="relative bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-500 bg-opacity-20 rounded-lg">
                            <Users class="w-6 h-6 text-blue-600" />
                        </div>
                        <Badge variant="secondary" class="bg-blue-500 text-white">
                            Total
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-blue-900">{{ statusCounts?.total || 0 }}</p>
                        <p class="text-sm text-blue-700 mt-1">Total Users</p>
                    </div>
                </div>

                <!-- Active Users Card -->
                <div class="relative bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-green-500 bg-opacity-20 rounded-lg">
                            <UserCheck class="w-6 h-6 text-green-600" />
                        </div>
                        <Badge variant="secondary" class="bg-green-500 text-white">
                            Active
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-green-900">{{ statusCounts?.active || 0 }}</p>
                        <p class="text-sm text-green-700 mt-1">Active Users</p>
                    </div>
                </div>

                <!-- Inactive Users Card -->
                <div class="relative bg-gradient-to-br from-yellow-50 to-yellow-100 border border-yellow-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-yellow-500 bg-opacity-20 rounded-lg">
                            <Clock class="w-6 h-6 text-yellow-600" />
                        </div>
                        <Badge variant="secondary" class="bg-yellow-500 text-white">
                            Inactive
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-yellow-900">{{ statusCounts?.inactive || 0 }}</p>
                        <p class="text-sm text-yellow-700 mt-1">Inactive Users</p>
                    </div>
                </div>

                <!-- Suspended Users Card -->
                <div class="relative bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-xl p-6 shadow-lg transition-all duration-200 hover:shadow-xl hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-red-500 bg-opacity-20 rounded-lg">
                            <UserX class="w-6 h-6 text-red-600" />
                        </div>
                        <Badge variant="secondary" class="bg-red-500 text-white">
                            Suspended
                        </Badge>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-red-900">{{ statusCounts?.suspended || 0 }}</p>
                        <p class="text-sm text-red-700 mt-1">Suspended Users</p>
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
                            placeholder="Search users by name, email..."
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
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="inactive">Inactive</SelectItem>
                            <SelectItem value="suspended">Suspended</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <Dialog v-model:open="isEditDialogOpen">
                <EditUser :user="editForm" @close="isEditDialogOpen = false" />
            </Dialog>

            <Dialog v-model:open="isViewDialogOpen">
                <ViewUser :user="viewForm" @close="isViewDialogOpen = false" />
            </Dialog>

            <!-- Alert Dialog for Delete Confirmation -->
            <AlertDialog v-model:open="isDeleteDialogOpen">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Do you really want to delete this user? This action cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel @click="isDeleteDialogOpen = false">Cancel</AlertDialogCancel>
                        <AlertDialogAction @click="confirmDelete">Delete</AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            <!-- Enhanced Users Table -->
            <div v-if="users.data.length > 0" class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <div class="overflow-x-auto max-w-full">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">ID</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Name</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Email</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Phone</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Role</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Status</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold">Date Created</TableHead>
                                <TableHead class="whitespace-nowrap px-4 py-3 font-semibold text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(user,index) in users.data" :key="user.id" class="hover:bg-muted/25 transition-colors">
                                <TableCell class="whitespace-nowrap px-4 py-3 font-medium">
                                    {{ (users.current_page - 1) * users.per_page + index + 1 }}
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="font-medium">{{ user.first_name }} {{ user.last_name }}</div>
                                    <div class="text-sm text-muted-foreground">ID: {{ user.id }}</div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-2 h-2 rounded-full"
                                            :class="{
                                                'bg-green-500': user.status === 'active',
                                                'bg-yellow-500': user.status === 'inactive',
                                                'bg-red-500': user.status === 'suspended'
                                            }"
                                        ></div>
                                        {{ user.email }}
                                    </div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">{{ user.phone || 'N/A' }}</TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Badge :variant="getRoleBadgeVariant(user.role)" class="capitalize">
                                        {{ user.role }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">
                                    <Badge :variant="getStatusBadgeVariant(user.status)" class="capitalize">
                                        {{ user.status }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="whitespace-nowrap px-4 py-3">{{ formatDate(user.created_at) }}</TableCell>
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
                    <Users class="w-16 h-16 text-muted-foreground" />
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold text-foreground">No users found</h3>
                        <p class="text-muted-foreground">Get started by creating your first user.</p>
                    </div>
                    <Dialog v-model:open="isCreateDialogOpen">
                        <DialogTrigger as-child>
                            <Button class="flex items-center gap-2">
                                <Shield class="w-4 h-4" />
                                Create New User
                            </Button>
                        </DialogTrigger>
                        <CreateUser @close="isCreateDialogOpen = false" />
                    </Dialog>
                </div>
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";
import Table from "@/Components/ui/table/Table.vue";
import TableHeader from "@/Components/ui/table/TableHeader.vue";
import TableRow from "@/Components/ui/table/TableRow.vue";
import TableHead from "@/Components/ui/table/TableHead.vue";
import TableBody from "@/Components/ui/table/TableBody.vue";
import TableCell from "@/Components/ui/table/TableCell.vue";
import Button from "@/Components/ui/button/Button.vue";
import Badge from "@/Components/ui/badge/Badge.vue";
import { Input } from "@/Components/ui/input";
import editIcon from "../../../../assets/Pencil.svg";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';
import {
  Users,
  Shield,
  UserCheck,
  UserX,
  Clock,
  Search,
  Eye,
  Edit,
  Trash2
} from 'lucide-vue-next';
import { Dialog, DialogTrigger } from "@/Components/ui/dialog";
import AdminDashboardLayout from "@/Layouts/AdminDashboardLayout.vue";
import CreateUser from "@/Pages/AdminDashboardPages/Users/CreateUser.vue";
import EditUser from "@/Pages/AdminDashboardPages/Users/EditUser.vue";
import ViewUser from "@/Pages/AdminDashboardPages/Users/ViewUser.vue";
import Pagination from "@/Pages/AdminDashboardPages/Users/Pagination.vue";
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
    flash: Object,
});
const search = ref(props.filters.search || ''); // Initialize search with the filter value
const statusFilter = ref(props.filters?.status || 'all'); // Initialize status filter
const isCreateDialogOpen = ref(false);
const isEditDialogOpen = ref(false);
const isViewDialogOpen = ref(false);
const isDeleteDialogOpen = ref(false);
const editForm = ref({});
const viewForm = ref({});
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

    router.get('/users', params, {
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

    router.get('/users', params, {
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
    router.delete(`/users/${deleteUserId.value}`).then(() => {
        isDeleteDialogOpen.value = false;
    });
};

const getRoleBadgeVariant = (role) => {
    switch (role) {
        case 'admin': return 'destructive';
        case 'vendor': return 'secondary';
        default: return 'default';
    }
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

    router.get('/users', params, {
        preserveState: true,
        replace: true,
    });
};
const getStatusBadgeVariant = (status) => {
    switch (status) {
        case 'active': return 'default';
        case 'inactive': return 'secondary';
        case 'suspended': return 'destructive';
    }
};

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`;
};



const clearFlash = () => {
    setTimeout(() => {
        router.visit(window.location.pathname, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            data: { flash: null }
        });
    }, 3000); // Clear after 3 seconds
};

// Call clearFlash when flash message exists
if (props.flash?.success) {
    clearFlash();
}

// Watch for search query changes
watch(search, (newValue) => {
    handleSearch();
});

// Watch for status filter changes
watch(statusFilter, (newValue) => {
    filterByStatus();
});
</script>
